<?php
/**
 * AgriTech — Withdrawal Status Checker
 * Polls CamPay for a pending withdrawal and marks it completed.
 * POST /check_withdrawal_status.php
 * Body {"reference": "WTH_1_..._ab12"}
 * Returns {"status":"success","completed":true,"balance":55,"message":"..."}
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/config.php';

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

$uid   = (int)$_SESSION['user_id'];
$body  = json_decode(file_get_contents('php://input'), true);
$txRef = trim((string)($body['reference'] ?? ''));

if ($txRef === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Reference required.']);
    exit;
}

$tx = db_row(
    'SELECT * FROM wallet_transactions WHERE tx_ref = ? AND user_id = ? AND type = "withdrawal" LIMIT 1',
    [$txRef, $uid]
);

if (!$tx) {
    echo json_encode(['status' => 'error', 'message' => 'Transaction not found.']);
    exit;
}

if ($tx['status'] === 'completed') {
    $balance = (float)db_val('SELECT wallet_balance FROM users WHERE id = ?', [$uid]);
    echo json_encode(['status' => 'success', 'completed' => true, 'balance' => $balance,
        'message' => 'Withdrawal already completed.']);
    exit;
}
if ($tx['status'] === 'failed') {
    echo json_encode(['status' => 'success', 'completed' => false,
        'message' => 'This withdrawal was marked as failed.']);
    exit;
}

$campayRef = $tx['campay_reference'] ?? null;
if (!$campayRef) {
    echo json_encode(['status' => 'error', 'message' => 'No CamPay reference found.']);
    exit;
}

/* Auth */
$tCh = curl_init(CAMPAY_BASE_URL . 'token/');
curl_setopt_array($tCh, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>http_build_query(['username'=>CAMPAY_USERNAME,'password'=>CAMPAY_PASSWORD]),
    CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT=>15, CURLOPT_CONNECTTIMEOUT=>8, CURLOPT_SSL_VERIFYPEER=>true]);
$tRaw  = curl_exec($tCh);
$tCode = curl_getinfo($tCh, CURLINFO_HTTP_CODE);
curl_close($tCh);
$bearer = json_decode($tRaw, true)['token'] ?? null;

if (!$bearer || $tCode !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'Could not authenticate with payment gateway.']);
    exit;
}

/* Query status */
$sCh = curl_init(CAMPAY_BASE_URL . 'transaction/' . rawurlencode($campayRef) . '/');
curl_setopt_array($sCh, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPGET=>true,
    CURLOPT_HTTPHEADER=>['Content-Type: application/json', 'Authorization: Token '.$bearer],
    CURLOPT_TIMEOUT=>15, CURLOPT_CONNECTTIMEOUT=>8, CURLOPT_SSL_VERIFYPEER=>true]);
$sRaw  = curl_exec($sCh);
$sCode = curl_getinfo($sCh, CURLINFO_HTTP_CODE);
curl_close($sCh);
$sData  = json_decode($sRaw, true);
$status = strtoupper($sData['status'] ?? '');

if ($status === 'SUCCESSFUL') {
    try {
        $pdo = db();
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT status FROM wallet_transactions WHERE id=? FOR UPDATE');
        $st->execute([(int)$tx['id']]);
        if ($st->fetchColumn() !== 'pending') {
            $pdo->rollBack();
            $balance = (float)db_val('SELECT wallet_balance FROM users WHERE id=?', [$uid]);
            echo json_encode(['status'=>'success','completed'=>true,'balance'=>$balance,
                'message'=>'Withdrawal confirmed.']);
            exit;
        }
        $pdo->prepare('UPDATE wallet_transactions SET status="completed",
            note=CONCAT(COALESCE(note,"")," | Confirmed via status poll") WHERE id=?')
            ->execute([(int)$tx['id']]);
        $pdo->commit();
        $balance = (float)db_val('SELECT wallet_balance FROM users WHERE id=?', [$uid]);
        echo json_encode(['status'=>'success','completed'=>true,'balance'=>$balance,
            'message'=>'Withdrawal of '.number_format((float)$tx['amount'],0,'.',',').' XAF confirmed.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo json_encode(['status'=>'error','message'=>'Database error: '.$e->getMessage()]);
    }
} elseif ($status === 'FAILED') {
    db_run('UPDATE wallet_transactions SET status="failed",
        note=CONCAT(COALESCE(note,"")," | Marked failed via poll") WHERE id=?', [(int)$tx['id']]);
    echo json_encode(['status'=>'success','completed'=>false,
        'message'=>'Withdrawal failed on CamPay side.']);
} else {
    echo json_encode(['status'=>'success','completed'=>false,'pending'=>true,
        'message'=>'Withdrawal is still processing ('.($status ?: 'PENDING').').']);
}
