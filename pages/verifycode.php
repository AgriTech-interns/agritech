<?php
/**
 * verify_code.php
 * Step 2: user enters the 6-digit code they received by email.
 * On success we mark a session flag allowing them to reach
 * reset_password.php.
 */

require_once  'config.php';


$email = $_SESSION['reset_email'] ?? '';
$message = '';
$messageType = '';

if ($email === '') {
    // No email in session — user must start over.
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeInput = trim($_POST['code'] ?? '');

    if (!preg_match('/^\d{6}$/', $codeInput)) {
        $message = 'Enter the 6-digit code exactly as received.';
        $messageType = 'error';
    } else {
        $conn = getDb();

        // Get the most recent, unused, non-expired code for this email.
        $stmt = $conn->prepare(
            'SELECT id, code_hash, expires_at FROM password_resets
             WHERE email = ? AND used = 0
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$email]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $message = 'No active code found. Please request a new one.';
            $messageType = 'error';
        } elseif (strtotime($reset['expires_at']) < time()) {
            $message = 'This code has expired. Please request a new one.';
            $messageType = 'error';
        } elseif (!password_verify($codeInput, $reset['code'])) {
            $message = 'Incorrect code. Please try again.';
            $messageType = 'error';
        } else {
            // Success — mark code as used and allow password reset.
            $update = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
            $update->execute([$reset['id']]);

            $_SESSION['reset_verified'] = true;
            header('Location: resetpassword.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Code</title>
<link rel="stylesheet" href="../Assets/forgot.css">
<link rel="stylesheet" href="../Assets/index.css">
</head>
<body>
  <div class="auth-card">
    <h1>Enter your code</h1>
    <p class="subtitle">We sent a 6-digit code to <strong><?= htmlspecialchars($email) ?></strong></p>

    <form method="POST" action="verify_code.php">
      <div class="field">
        <label for="code">Verification code</label>
        <input
          type="text"
          id="code"
          name="code"
          class="code-input"
          inputmode="numeric"
          pattern="\d{6}"
          maxlength="6"
          placeholder="------"
          autocomplete="one-time-code"
          required>
      </div>
      <button type="submit" class="btn">Verify code</button>
    </form>

    <?php if ($message): ?>
      <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <a href="forgot_Pasword.php" class="back-link">Resend code</a>
  </div>

  <script src="../scripts/logout.js"></script>
</body>
</html>