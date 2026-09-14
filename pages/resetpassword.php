<?php
/**
 * reset_password.php
 * Step 3: user sets a new password, but only if they successfully
 * verified a code in the previous step ($_SESSION['reset_verified']).
 */

require_once __DIR__ . '/db.php';
session_start();

$email = $_SESSION['reset_email'] ?? '';

if ($email === '' || empty($_SESSION['reset_verified'])) {
    header('Location: forgot_password.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $messageType = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        $pdo = getDb();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([$hash, $email]);

        // Clean up: this reset flow is now complete.
        unset($_SESSION['reset_email'], $_SESSION['reset_verified']);

        $_SESSION['reset_complete'] = true;
        header('Location: login.php?reset=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="auth-card">
    <h1>Set a new password</h1>
    <p class="subtitle">Choose a strong password for <strong><?= htmlspecialchars($email) ?></strong></p>

    <form method="POST" action="reset_password.php" id="resetForm">
      <div class="field">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" minlength="8" required>
      </div>
      <p class="password-rules">At least 8 characters.</p>

      <div class="field">
        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
      </div>

      <button type="submit" class="btn">Reset password</button>
    </form>

    <?php if ($message): ?>
      <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
  </div>

  <script src="../scripts/logout.js"></script>
</body>
</html>