<?php
/**
 * AgriTech — Manual Deposit Status Checker
 * Polls CamPay for the status of a pending deposit and credits the wallet.
 * Called from the wallet page after a deposit is initiated.
 *
 * POST /check_deposit_status.php
 * Body {"reference": "DEP_1_171234_ab12"}   ← our internal tx_ref
 * Returns {"status":"success","credited":true,"balance":10,"message":"..."}
 */
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/config.php';

/* ── Auth ── */
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$uid  = (int)$_SESSION['user_id'];
$body = json_decode(file_get_contents('php://input'), true);
$txRef = trim((string)($body['reference'] ?? ''));

if ($txRef === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Reference required.']);
    exit;
}

/* ── Find transaction in DB ── */
$tx = db_row(
    'SELECT * FROM wallet_transactions WHERE tx_ref = ? AND user_id = ? LIMIT 1',
    [$txRef, $uid]
);

if (!$tx) {
    echo json_encode(['status' => 'error', 'message' => 'Transaction not found.']);
    exit;
}

/* Already completed — just return current balance */
if ($tx['status'] === 'completed') {
    $balance = (float)db_val('SELECT wallet_balance FROM users WHERE id = ?', [$uid]);
    echo json_encode([
        'status'   => 'success',
        'credited' => true,
        'balance'  => $balance,
        'message'  => 'Deposit already credited.',
    ]);
    exit;
}

if ($tx['status'] === 'failed') {
    echo json_encode([
        'status'   => 'success',
        'credited' => false,
        'message'  => 'This transaction was marked as failed by CamPay.',
    ]);
    exit;
}

/* ── Get CamPay reference to query ── */
$campayRef = $tx['campay_reference'] ?? null;

if (!$campayRef) {
    echo json_encode(['status' => 'error', 'message' => 'No CamPay reference found. Cannot check status.']);
    exit;
}

/* ════════════════════════════════════════════════════════
   STEP 1 — Authenticate with CamPay
════════════════════════════════════════════════════════ */
$tokenCh = curl_init(CAMPAY_BASE_URL . 'token/');
curl_setopt_array($tokenCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'username' => CAMPAY_USERNAME,
        'password' => CAMPAY_PASSWORD,
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$tokenRaw  = curl_exec($tokenCh);
$tokenCode = curl_getinfo($tokenCh, CURLINFO_HTTP_CODE);
curl_close($tokenCh);

$bearerToken = json_decode($tokenRaw, true)['token'] ?? null;

if (!$bearerToken || $tokenCode !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'Could not authenticate with payment gateway.']);
    exit;
}

/* ════════════════════════════════════════════════════════
   STEP 2 — Query CamPay transaction status
   GET /api/transaction/{campay_reference}/
════════════════════════════════════════════════════════ */
$statusUrl = CAMPAY_BASE_URL . 'transaction/' . rawurlencode($campayRef) . '/';

$statusCh = curl_init($statusUrl);
curl_setopt_array($statusCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Token ' . $bearerToken,
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$statusRaw  = curl_exec($statusCh);
$statusCode = curl_getinfo($statusCh, CURLINFO_HTTP_CODE);
curl_close($statusCh);

$statusData   = json_decode($statusRaw, true);
$campayStatus = strtoupper($statusData['status'] ?? '');

/* ════════════════════════════════════════════════════════
   STEP 3 — Credit wallet if SUCCESSFUL
════════════════════════════════════════════════════════ */
if ($campayStatus === 'SUCCESSFUL') {

    try {
        $pdo = db();
        $pdo->beginTransaction();

        /* Lock row — prevent double-credit on concurrent polls */
        $stmt = $pdo->prepare(
            'SELECT status FROM wallet_transactions WHERE id = ? FOR UPDATE'
        );
        $stmt->execute([(int)$tx['id']]);
        $lockedStatus = $stmt->fetchColumn();

        if ($lockedStatus === 'completed') {
            /* Already credited by a concurrent request or webhook */
            $pdo->rollBack();
            $balance = (float)db_val('SELECT wallet_balance FROM users WHERE id = ?', [$uid]);
            echo json_encode([
                'status'   => 'success',
                'credited' => true,
                'balance'  => $balance,
                'message'  => 'Deposit confirmed and credited to your wallet.',
            ]);
            exit;
        }

        /* Credit wallet */
        $pdo->prepare(
            'UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?'
        )->execute([(float)$tx['amount'], $uid]);

        /* Mark transaction completed */
        $pdo->prepare(
            'UPDATE wallet_transactions SET status = "completed",
             note = CONCAT(COALESCE(note,""), " | Credited via status poll")
             WHERE id = ?'
        )->execute([(int)$tx['id']]);

        $pdo->commit();

        $newBalance = (float)db_val('SELECT wallet_balance FROM users WHERE id = ?', [$uid]);

        echo json_encode([
            'status'   => 'success',
            'credited' => true,
            'balance'  => $newBalance,
            'message'  => number_format((float)$tx['amount'], 0, '.', ',')
                        . ' XAF has been credited to your wallet.',
        ]);

    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('AgriTech status-poll credit error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error crediting deposit.']);
    }

} elseif ($campayStatus === 'FAILED') {

    db_run(
        'UPDATE wallet_transactions SET status = "failed",
         note = CONCAT(COALESCE(note,""), " | Marked failed via status poll")
         WHERE id = ?',
        [(int)$tx['id']]
    );

    echo json_encode([
        'status'   => 'success',
        'credited' => false,
        'message'  => 'Payment was not successful. Please try again.',
    ]);

} else {
    /* Still PENDING */
    echo json_encode([
        'status'   => 'success',
        'credited' => false,
        'pending'  => true,
        'message'  => 'Payment is still being processed (status: ' . ($campayStatus ?: 'PENDING') . '). Please wait and try again.',
    ]);
}
