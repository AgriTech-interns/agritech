<?php
/**
 * AgriTech — Initiate CamPay Deposit (Collection Request)
 * POST  /process_deposit.php
 * Body  {"amount": 5000, "phone": "237670000000", "network": "MTN"}
 * Returns {"status":"success","message":"...","reference":"..."} or {"status":"error","message":"..."}
 *
 * Flow:
 *   1. Authenticate with CamPay → get bearer token
 *   2. POST to /api/collect/ → CamPay pushes USSD prompt to user's phone
 *   3. Insert wallet_transaction as 'pending' (webhook upgrades to 'completed')
 */
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/config.php';

/* ── Must be logged in ── */
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

/* ── Only buyers can deposit ── */
$sessionRole = $_SESSION['user_role'] ?? '';
if ($sessionRole !== 'buyer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only buyers can make deposits.']);
    exit;
}

/* ── Only accept POST ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

/* ── Parse JSON body ── */
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request body.']);
    exit;
}

$amount  = (float)($body['amount']  ?? 0);
$phone   = trim(preg_replace('/[^0-9]/', '', (string)($body['phone'] ?? '')));
$network = strtoupper(trim((string)($body['network'] ?? '')));
$uid     = (int)$_SESSION['user_id'];

/* ── Input validation ── */
/* In demo/sandbox mode CamPay caps collections at 25 XAF.
   In production the real business minimum of 1,000 CFA applies. */
$isDemo   = (CAMPAY_ENV !== 'prod');
$minAmount = $isDemo ? 1 : 1000;
$maxAmount = $isDemo ? 25 : 1000000;

if ($amount < $minAmount) {
    echo json_encode(['status' => 'error', 'message' => 'Minimum deposit amount is ' . number_format($minAmount, 0, '.', ',') . ' XAF.']);
    exit;
}

if ($amount > $maxAmount) {
    echo json_encode(['status' => 'error', 'message' => 'Maximum deposit in ' . ($isDemo ? 'demo/sandbox' : 'live') . ' mode is ' . number_format($maxAmount, 0, '.', ',') . ' XAF.']);
    exit;
}

/* Cameroonian phone: 12 digits starting with 237 */
if (!preg_match('/^2376[5789]\d{7}$/', $phone)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid phone number. Use Cameroonian format: 2376XXXXXXXX (12 digits).',
    ]);
    exit;
}

$allowedNetworks = ['MTN', 'ORANGE'];
if (!in_array($network, $allowedNetworks, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Select a valid network: MTN or Orange.']);
    exit;
}

/* ── Generate unique transaction reference ── */
$txRef = 'DEP_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4));

/* ════════════════════════════════════════════════════════
   STEP 1 — Authenticate with CamPay → get bearer token
   NOTE: CamPay /token/ requires form-encoded body, not JSON
════════════════════════════════════════════════════════ */
$tokenUrl = CAMPAY_BASE_URL . 'token/';

$tokenCh = curl_init($tokenUrl);
curl_setopt_array($tokenCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([   // form-encoded, NOT json_encode
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
$tokenErr  = curl_error($tokenCh);
curl_close($tokenCh);

if ($tokenRaw === false || $tokenErr) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Could not connect to payment gateway. Please try again.']);
    exit;
}

$tokenData = json_decode($tokenRaw, true);
$bearerToken = $tokenData['token'] ?? null;

if (!$bearerToken || $tokenCode !== 200) {
    /* Surface the real CamPay error so you can diagnose credential issues */
    $campayErr = $tokenData['detail']
              ?? $tokenData['message']
              ?? $tokenData['error']
              ?? ('HTTP ' . $tokenCode . ' — token response: ' . substr($tokenRaw, 0, 200));
    http_response_code(502);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Payment gateway authentication failed: ' . htmlspecialchars($campayErr),
    ]);
    exit;
}

/* ════════════════════════════════════════════════════════
   STEP 2 — Initiate CamPay collection request
════════════════════════════════════════════════════════ */
$collectUrl = CAMPAY_BASE_URL . 'collect/';

$collectPayload = [
    'amount'            => (string)(int)$amount,   // CamPay expects integer string
    'currency'          => 'XAF',
    'from'              => $phone,
    'description'       => APP_NAME . ' Wallet Deposit',
    'external_reference' => $txRef,
];

$collectCh = curl_init($collectUrl);
curl_setopt_array($collectCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($collectPayload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Token ' . $bearerToken,
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$collectRaw  = curl_exec($collectCh);
$collectCode = curl_getinfo($collectCh, CURLINFO_HTTP_CODE);
$collectErr  = curl_error($collectCh);
curl_close($collectCh);

if ($collectRaw === false || $collectErr) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Payment request failed. Please try again.']);
    exit;
}

$collectData = json_decode($collectRaw, true);

/* CamPay returns reference on success */
$campayRef = $collectData['reference'] ?? null;

if (!$campayRef || $collectCode !== 200) {
    $errMsg = $collectData['message']
           ?? $collectData['detail']
           ?? $collectData['error']
           ?? ('HTTP ' . $collectCode . ': ' . substr($collectRaw, 0, 200));
    echo json_encode(['status' => 'error', 'message' => htmlspecialchars($errMsg)]);
    exit;
}

/* ════════════════════════════════════════════════════════
   STEP 3 — Record pending transaction in DB
   (Wallet balance is credited by campay_webhook.php on
    SUCCESSFUL callback — NOT here, to avoid double-credit)
════════════════════════════════════════════════════════ */
$dbInsertOk = false;
try {
    db_run(
        'INSERT INTO wallet_transactions
            (user_id, type, amount, status, tx_ref, campay_reference, method, phone, note)
         VALUES (?, "deposit", ?, "pending", ?, ?, ?, ?, ?)',
        [
            $uid,
            $amount,
            $txRef,
            $campayRef,
            strtolower($network) === 'orange' ? 'orange_money' : 'mtn_momo',
            $phone,
            'CamPay collection initiated — ref: ' . $campayRef,
        ]
    );
    $dbInsertOk = true;
} catch (Throwable $e) {
    /* Log the real error — most likely campay_reference column missing */
    error_log('AgriTech deposit DB insert error: ' . $e->getMessage());

    /* Try fallback insert without campay_reference (old schema) */
    try {
        db_run(
            'INSERT INTO wallet_transactions
                (user_id, type, amount, status, tx_ref, method, phone, note)
             VALUES (?, "deposit", ?, "pending", ?, ?, ?, ?)',
            [
                $uid,
                $amount,
                $txRef,
                strtolower($network) === 'orange' ? 'orange_money' : 'mtn_momo',
                $phone,
                'CamPay collection initiated — ref: ' . $campayRef . ' [campay_ref col missing, run migration]',
            ]
        );
        $dbInsertOk = true;

        /* Best-effort: try adding the column now for future inserts */
        try {
            db()->exec('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS campay_reference VARCHAR(100) DEFAULT NULL AFTER tx_ref');
            /* Update the row we just inserted with the reference */
            db_run(
                'UPDATE wallet_transactions SET campay_reference = ? WHERE tx_ref = ? AND user_id = ?',
                [$campayRef, $txRef, $uid]
            );
        } catch (Throwable $ignored) {}

    } catch (Throwable $e2) {
        error_log('AgriTech deposit DB fallback error: ' . $e2->getMessage());
    }
}

/* ── Success — USSD prompt sent to phone ── */
echo json_encode([
    'status'    => 'success',
    'message'   => 'Payment request sent! Please check your phone and approve the '
                 . ($network === 'ORANGE' ? 'Orange Money' : 'MTN Mobile Money')
                 . ' prompt to complete your deposit of '
                 . number_format($amount, 0, '.', ',') . ' CFA.',
    'reference' => $txRef,
]);
