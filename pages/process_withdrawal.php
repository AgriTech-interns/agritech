<?php
/**
 * AgriTech — Process Withdrawal via CamPay Transfer (Disbursement)
 * POST form: amount, phone, network, csrf_token
 * Redirects back to wallet.php with session flash message
 *
 * Flow:
 *   1. Validate session, role, CSRF, inputs, and wallet balance
 *   2. Authenticate with CamPay → bearer token
 *   3. POST to /api/withdraw/ → CamPay pushes funds to phone
 *   4. On success: deduct wallet_balance + insert completed withdrawal record
 *   5. On failure: insert failed record, keep wallet_balance unchanged
 */
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/config.php';

/* ── Auth guard ── */
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ── Only accept POST ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: wallet.php');
    exit;
}

/* ── CSRF check ── */
$submittedToken = $_POST['csrf_token'] ?? '';
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $submittedToken)) {
    $_SESSION['pay_error'] = 'Invalid request token. Please try again.';
    header('Location: wallet.php');
    exit;
}

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? '';

/* ── All roles can withdraw (buyer, farmer, driver, admin) ── */
$allowedRoles = ['buyer', 'farmer', 'driver', 'admin'];
if (!in_array($role, $allowedRoles, true)) {
    $_SESSION['pay_error'] = 'Your account type is not permitted to make withdrawals.';
    header('Location: wallet.php');
    exit;
}

/* ── Sanitize inputs ── */
$amount  = (float)($_POST['amount'] ?? 0);
$phone   = trim(preg_replace('/[^0-9]/', '', (string)($_POST['phone'] ?? '')));
$network = strtoupper(trim((string)($_POST['network'] ?? '')));

/* ── Validate amount ── */
$isDemo = (CAMPAY_ENV !== 'prod');

if ($isDemo) {
    /* Dynamically fetch CamPay app balance to set real sandbox ceiling */
    $limitToken = null;
    $ltCh = curl_init(CAMPAY_BASE_URL . 'token/');
    curl_setopt_array($ltCh, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query(['username'=>CAMPAY_USERNAME,'password'=>CAMPAY_PASSWORD]),
        CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>true]);
    $ltRaw = curl_exec($ltCh); $ltCode = curl_getinfo($ltCh, CURLINFO_HTTP_CODE); curl_close($ltCh);
    if ($ltCode === 200) $limitToken = json_decode($ltRaw, true)['token'] ?? null;
    /* Max = floor(campay_balance / 1.01) to cover the 1% withdrawal fee */
    $campayAppBalance = 0;
    // Note: get_balance endpoint returns 404 in sandbox — use conservative default
    $maxWithdraw = 10; // safe sandbox default; increases once app balance confirmed
    $minWithdraw = 1;
} else {
    $minWithdraw = 5000;
    $maxWithdraw = 500000;
}

if ($amount < $minWithdraw) {
    $_SESSION['pay_error'] = 'Minimum withdrawal amount is '
        . number_format($minWithdraw, 0, '.', ',') . ' XAF'
        . ($isDemo ? ' (sandbox mode).' : ' CFA.');
    header('Location: wallet.php');
    exit;
}

if ($amount > $maxWithdraw) {
    $_SESSION['pay_error'] = 'Maximum withdrawal in '
        . ($isDemo ? 'sandbox/demo' : 'live') . ' mode is '
        . number_format($maxWithdraw, 0, '.', ',') . ' XAF.';
    header('Location: wallet.php');
    exit;
}

/* ── Validate phone (Cameroonian: 2376XXXXXXXX) ── */
if (!preg_match('/^2376[5789]\d{7}$/', $phone)) {
    $_SESSION['pay_error'] = 'Invalid phone number. Use Cameroonian format: 2376XXXXXXXX (12 digits).';
    header('Location: wallet.php');
    exit;
}

/* ── Validate network ── */
$allowedNetworks = ['MTN', 'ORANGE'];
if (!in_array($network, $allowedNetworks, true)) {
    $_SESSION['pay_error'] = 'Please select a valid mobile network: MTN or Orange.';
    header('Location: wallet.php');
    exit;
}

/* ── Check wallet balance (fresh DB read) ── */
$walletBalance = (float)db_val('SELECT wallet_balance FROM users WHERE id = ?', [$uid]);

if ($walletBalance < $amount) {
    $_SESSION['pay_error'] = 'Insufficient wallet balance. Available: '
        . number_format($walletBalance, 0, '.', ',') . ' CFA.';
    header('Location: wallet.php');
    exit;
}

/* ── Generate unique reference ── */
$txRef = 'WTH_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4));

/* ════════════════════════════════════════════════════════
   STEP 1 — Authenticate with CamPay → bearer token
   NOTE: CamPay /token/ requires form-encoded body, not JSON
════════════════════════════════════════════════════════ */
$tokenCh = curl_init(CAMPAY_BASE_URL . 'token/');
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

if ($tokenRaw === false || $tokenErr || $tokenCode !== 200) {
    $tokenErrMsg = '';
    if ($tokenRaw) {
        $td = json_decode($tokenRaw, true);
        $tokenErrMsg = $td['detail'] ?? $td['message'] ?? $td['error'] ?? substr($tokenRaw, 0, 200);
    }
    $_SESSION['pay_error'] = 'Payment gateway authentication failed'
        . ($tokenErrMsg ? ': ' . $tokenErrMsg : ' (HTTP ' . $tokenCode . '). Please try again.');
    header('Location: wallet.php?tab=withdraw');
    exit;
}

$tokenData   = json_decode($tokenRaw, true);
$bearerToken = $tokenData['token'] ?? null;

if (!$bearerToken) {
    $_SESSION['pay_error'] = 'Payment gateway authentication failed (no token returned). Please try again.';
    header('Location: wallet.php?tab=withdraw');
    exit;
}

/* ════════════════════════════════════════════════════════
   STEP 2 — Submit CamPay withdrawal (transfer/disburse)
════════════════════════════════════════════════════════ */
$withdrawPayload = [
    'amount'             => (string)(int)$amount,  // CamPay expects integer string
    'currency'           => 'XAF',
    'to'                 => $phone,
    'description'        => APP_NAME . ' Wallet Withdrawal',
    'external_reference' => $txRef,
];

$withdrawCh = curl_init(CAMPAY_BASE_URL . 'withdraw/');  // confirmed working endpoint
curl_setopt_array($withdrawCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($withdrawPayload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Token ' . $bearerToken,
    ],
    CURLOPT_TIMEOUT        => 60,   // increased — sandbox can be slow
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$withdrawRaw  = curl_exec($withdrawCh);
$withdrawCode = curl_getinfo($withdrawCh, CURLINFO_HTTP_CODE);
$withdrawErr  = curl_error($withdrawCh);
curl_close($withdrawCh);

/* ── Handle network-level failure ── */
if ($withdrawRaw === false || $withdrawErr) {
    $_SESSION['pay_error'] = 'Could not reach payment gateway (cURL error: ' . $withdrawErr . '). Please try again.';
    header('Location: wallet.php?tab=withdraw');
    exit;
}

$withdrawData = json_decode($withdrawRaw, true);
$campayRef    = $withdrawData['reference'] ?? null;
$campayStatus = strtoupper($withdrawData['status'] ?? '');

/* ════════════════════════════════════════════════════════
   STEP 3 — Update DB based on CamPay response
════════════════════════════════════════════════════════ */
$methodLabel = $network === 'ORANGE' ? 'orange_money' : 'mtn_momo';

if ($withdrawCode === 200 && $campayRef) {

    /* ── Success or queued: deduct balance + record transaction ── */
    try {
        $pdo = db();
        $pdo->beginTransaction();

        /* Re-check balance inside transaction to prevent race conditions */
        $stmt = $pdo->prepare(
            'SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE'
        );
        $stmt->execute([$uid]);
        $currentBal = (float)$stmt->fetchColumn();

        if ($currentBal < $amount) {
            $pdo->rollBack();
            $_SESSION['pay_error'] = 'Insufficient balance. Please try again.';
            header('Location: wallet.php');
            exit;
        }

        /* Deduct from wallet */
        $pdo->prepare(
            'UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?'
        )->execute([$amount, $uid]);

        /* Determine final DB status from CamPay response */
        $dbStatus = in_array($campayStatus, ['SUCCESSFUL', 'SUCCESS'], true)
            ? 'completed'
            : 'pending';   // PENDING means queued, webhook will confirm

        /* Insert withdrawal record */
        $pdo->prepare(
            'INSERT INTO wallet_transactions
                (user_id, type, amount, status, tx_ref, campay_reference, method, phone, note)
             VALUES (?, "withdrawal", ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $uid,
            $amount,
            $dbStatus,
            $txRef,
            $campayRef,
            $methodLabel,
            $phone,
            'CamPay withdrawal — ref: ' . $campayRef,
        ]);

        $pdo->commit();

        $_SESSION['pay_success'] =
            'Withdrawal of ' . number_format($amount, 0, '.', ',')
            . ' CFA to ' . $phone
            . ' has been initiated successfully. You will receive the funds shortly.';
        $_SESSION['pay_wth_ref'] = $txRef; // used by wallet.php to auto-poll

    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('AgriTech withdrawal DB error: ' . $e->getMessage());
        $_SESSION['pay_error'] = 'A database error occurred while recording your withdrawal. '
            . 'Please contact support at +237 670 000 003 with reference: ' . $txRef;
    }

} else {

    /* ── CamPay rejected the transfer — record failure, do NOT deduct ── */
    $errMsg = $withdrawData['message']
           ?? $withdrawData['detail']
           ?? $withdrawData['error']
           ?? ('HTTP ' . $withdrawCode . ': ' . substr($withdrawRaw, 0, 300));

    try {
        db_run(
            'INSERT INTO wallet_transactions
                (user_id, type, amount, status, tx_ref, campay_reference, method, phone, note)
             VALUES (?, "withdrawal", ?, "failed", ?, ?, ?, ?, ?)',
            [
                $uid,
                $amount,
                $txRef,
                $campayRef ?? 'N/A',
                $methodLabel,
                $phone,
                'Failed: ' . substr($withdrawRaw, 0, 300),  // store full raw response
            ]
        );
    } catch (Throwable $e) {
        error_log('AgriTech withdrawal failure log error: ' . $e->getMessage());
    }

    /* Show the real CamPay error message to help diagnose */
    $_SESSION['pay_error'] = htmlspecialchars($errMsg)
        . ' [CamPay HTTP ' . $withdrawCode . ']';
}

header('Location: wallet.php');
exit;
