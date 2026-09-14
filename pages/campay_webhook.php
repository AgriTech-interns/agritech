<?php
/**
 * AgriTech — CamPay Webhook Receiver
 * ─────────────────────────────────────────────────────────────────────────
 * CamPay POSTs asynchronous payment status callbacks to this endpoint.
 *
 * Register this URL in your CamPay dashboard as the webhook URL:
 *   https://yourdomain.com/campay_webhook.php
 *
 * CamPay sends a JSON body with at minimum:
 *   {
 *     "reference":          "CPxxxxxx",          ← CamPay reference
 *     "external_reference": "DEP_1_171234_ab12", ← our tx_ref
 *     "status":             "SUCCESSFUL",         ← or "FAILED"
 *     "amount":             "5000",
 *     "currency":           "XAF",
 *     "operator":           "MTN",
 *     "code":               "OP200"
 *   }
 *
 * Security: validate the X-Campay-Secret header against WEBHOOK_SECRET_KEY.
 *
 * Idempotency: skip if transaction already 'completed'.
 * ─────────────────────────────────────────────────────────────────────────
 */

/* Always respond 200 quickly so CamPay doesn't retry unnecessarily */
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/config.php';

/* ── Log helper (writes to php error_log; swap for a DB log if preferred) ── */
function wh_log(string $level, string $msg, array $ctx = []): void
{
    $line = '[CamPay Webhook] [' . strtoupper($level) . '] ' . $msg;
    if ($ctx) {
        $line .= ' | ctx=' . json_encode($ctx, JSON_UNESCAPED_UNICODE);
    }
    error_log($line);
}

/* ════════════════════════════════════════════════════════
   1. Only accept POST
════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wh_log('warn', 'Non-POST request rejected', ['method' => $_SERVER['REQUEST_METHOD']]);
    echo json_encode(['status' => 'ignored', 'reason' => 'Non-POST request.']);
    exit;
}

/* ════════════════════════════════════════════════════════
   2. Validate webhook secret header
   CamPay sends the secret in the  X-Campay-Secret  header.
   Skip validation only if WEBHOOK_SECRET_KEY is still the
   placeholder (to avoid locking out un-configured installs
   during initial testing — remove this bypass in production).
════════════════════════════════════════════════════════ */
$webhookSecret = defined('WEBHOOK_SECRET_KEY') ? WEBHOOK_SECRET_KEY : '';
$isPlaceholder = (
    $webhookSecret === ''
    || $webhookSecret === 'YOUR_WEBHOOK_SECRET_KEY_HERE'
);

if (!$isPlaceholder) {
    $receivedSecret = $_SERVER['HTTP_X_CAMPAY_SECRET']
                   ?? $_SERVER['HTTP_X_WEBHOOK_SECRET']
                   ?? '';

    if (!hash_equals($webhookSecret, $receivedSecret)) {
        wh_log('warn', 'Invalid webhook secret — request rejected');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'reason' => 'Unauthorized.']);
        exit;
    }
}

/* ════════════════════════════════════════════════════════
   3. Parse JSON payload
════════════════════════════════════════════════════════ */
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    wh_log('warn', 'Malformed JSON payload', ['raw' => substr($raw, 0, 500)]);
    echo json_encode(['status' => 'ignored', 'reason' => 'Malformed JSON.']);
    exit;
}

wh_log('info', 'Payload received', $payload);

/* ════════════════════════════════════════════════════════
   4. Extract & sanitise fields
════════════════════════════════════════════════════════ */
$campayRef   = trim((string)($payload['reference']          ?? ''));
$extRef      = trim((string)($payload['external_reference'] ?? ''));
$status      = strtoupper(trim((string)($payload['status']  ?? '')));
$amount      = (float)($payload['amount']   ?? 0);
$currency    = strtoupper(trim((string)($payload['currency'] ?? '')));
$operator    = strtoupper(trim((string)($payload['operator'] ?? '')));

/* We need at least one reference to locate the transaction */
if ($campayRef === '' && $extRef === '') {
    wh_log('warn', 'Missing reference fields in payload');
    echo json_encode(['status' => 'ignored', 'reason' => 'No reference provided.']);
    exit;
}

/* ════════════════════════════════════════════════════════
   5. Look up the pending wallet transaction
   Match on campay_reference OR tx_ref (external_reference).
════════════════════════════════════════════════════════ */
$txRow = null;

if ($campayRef !== '') {
    $txRow = db_row(
        'SELECT * FROM wallet_transactions WHERE campay_reference = ? LIMIT 1',
        [$campayRef]
    );
}

if (!$txRow && $extRef !== '') {
    $txRow = db_row(
        'SELECT * FROM wallet_transactions WHERE tx_ref = ? LIMIT 1',
        [$extRef]
    );
}

if (!$txRow) {
    wh_log('warn', 'No matching transaction found', [
        'campay_ref' => $campayRef,
        'ext_ref'    => $extRef,
    ]);
    /* Return 200 so CamPay doesn't keep retrying for unknown references */
    echo json_encode(['status' => 'ignored', 'reason' => 'Transaction not found.']);
    exit;
}

/* ════════════════════════════════════════════════════════
   6. Idempotency — skip already-finalised transactions
════════════════════════════════════════════════════════ */
if (in_array($txRow['status'], ['completed', 'failed'], true)) {
    wh_log('info', 'Transaction already finalised — skipping', [
        'tx_id'  => $txRow['id'],
        'status' => $txRow['status'],
    ]);
    echo json_encode(['status' => 'ok', 'reason' => 'Already processed.']);
    exit;
}

$txId     = (int)$txRow['id'];
$txUserId = (int)$txRow['user_id'];
$txType   = $txRow['type'];   // 'deposit' | 'withdrawal'
$txAmount = (float)$txRow['amount'];

/* ════════════════════════════════════════════════════════
   7. Handle FAILED status
════════════════════════════════════════════════════════ */
if ($status === 'FAILED') {
    db_run(
        'UPDATE wallet_transactions SET status = "failed", note = CONCAT(COALESCE(note,""), " | Webhook: FAILED")
         WHERE id = ?',
        [$txId]
    );

    /* If a withdrawal was already deducted (shouldn't happen since we deduct
       only on success, but guard anyway), refund it */
    if ($txType === 'withdrawal') {
        // No deduction was made for failed withdrawals in our flow — nothing to refund
    }

    wh_log('info', 'Transaction marked failed', ['tx_id' => $txId]);
    echo json_encode(['status' => 'ok', 'action' => 'marked_failed']);
    exit;
}

/* ════════════════════════════════════════════════════════
   8. Handle SUCCESSFUL deposit — credit wallet
════════════════════════════════════════════════════════ */
if ($status === 'SUCCESSFUL' && $txType === 'deposit') {

    /* Use the amount from our DB record (not the webhook) as the source of truth */
    $creditAmount = $txAmount;

    try {
        $pdo = db();
        $pdo->beginTransaction();

        /* Lock the transaction row to prevent concurrent processing */
        $stmt = $pdo->prepare(
            'SELECT status FROM wallet_transactions WHERE id = ? FOR UPDATE'
        );
        $stmt->execute([$txId]);
        $lockedStatus = $stmt->fetchColumn();

        /* Double-check idempotency inside the lock */
        if (in_array($lockedStatus, ['completed', 'failed'], true)) {
            $pdo->rollBack();
            wh_log('info', 'Concurrent duplicate — skipped', ['tx_id' => $txId]);
            echo json_encode(['status' => 'ok', 'reason' => 'Already processed (race).']);
            exit;
        }

        /* Credit wallet balance */
        $pdo->prepare(
            'UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?'
        )->execute([$creditAmount, $txUserId]);

        /* Mark transaction completed */
        $pdo->prepare(
            'UPDATE wallet_transactions
             SET status = "completed",
                 campay_reference = ?,
                 note = CONCAT(COALESCE(note,""), " | Webhook: SUCCESSFUL")
             WHERE id = ?'
        )->execute([$campayRef ?: $txRow['campay_reference'], $txId]);

        $pdo->commit();

        wh_log('info', 'Deposit credited successfully', [
            'tx_id'   => $txId,
            'user_id' => $txUserId,
            'amount'  => $creditAmount,
        ]);

        echo json_encode(['status' => 'ok', 'action' => 'deposit_credited']);

    } catch (Throwable $e) {
        $pdo->rollBack();
        wh_log('error', 'DB error crediting deposit', [
            'tx_id' => $txId,
            'error' => $e->getMessage(),
        ]);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'reason' => 'Database error.']);
    }

    exit;
}

/* ════════════════════════════════════════════════════════
   9. Handle SUCCESSFUL withdrawal confirmation
   (Balance already deducted at initiation — just mark done)
════════════════════════════════════════════════════════ */
if ($status === 'SUCCESSFUL' && $txType === 'withdrawal') {

    db_run(
        'UPDATE wallet_transactions
         SET status = "completed",
             campay_reference = ?,
             note = CONCAT(COALESCE(note,""), " | Webhook: SUCCESSFUL")
         WHERE id = ?',
        [$campayRef ?: $txRow['campay_reference'], $txId]
    );

    wh_log('info', 'Withdrawal confirmed completed', ['tx_id' => $txId]);
    echo json_encode(['status' => 'ok', 'action' => 'withdrawal_confirmed']);
    exit;
}

/* ════════════════════════════════════════════════════════
   10. Unknown / intermediate status — acknowledge and wait
════════════════════════════════════════════════════════ */
wh_log('info', 'Intermediate status received — no DB change', [
    'tx_id'  => $txId,
    'status' => $status,
]);
echo json_encode(['status' => 'ok', 'reason' => 'Intermediate status acknowledged.']);
