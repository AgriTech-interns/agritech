<?php
/**
 * forgot_password.php
 * Step 1: user submits their email. If it belongs to a real account,
 * we generate a 6-digit code, store it (hashed) with a 10-minute
 * expiry, and email it to them.
 *
 * NOTE ON SECURITY: We deliberately show the SAME success message
 * whether or not the email exists, so this form can't be used to
 * find out which emails are registered.
 */

require_once "./config.php";
require_once './mailer.php';

$message = '';
$messageType = ''; // 'success' | 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Please enter a valid email address.';
        $messageType = 'error';

    } else {

        // --------------------------------------------------
        // 1. Check if a reset code was recently requested
        // --------------------------------------------------

        $stmt = $pdo->prepare(
            'SELECT created_at
             FROM password_resets
             WHERE email = ?
             ORDER BY created_at DESC
             LIMIT 1'
        );

        $stmt->execute([$email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $last = $row['created_at'] ?? null;

        $tooSoon = false;

        if ($last) {
            $tooSoon = (time() - strtotime($last)) < 60;
        }


        // --------------------------------------------------
        // 2. Only create a new code if 60 seconds have passed
        // --------------------------------------------------

        if (!$tooSoon) {

            // Find the user
            $stmt = $pdo->prepare(
                'SELECT user_id, fullName
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);


            // --------------------------------------------------
            // 3. If user exists, generate reset code
            // --------------------------------------------------

            if ($user) {

                // Generate secure 6-digit code
                $code = str_pad(
                    (string) random_int(0, 999999),
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                // Code expires after 10 minutes
                $expiresAt = date(
                    'Y-m-d H:i:s',
                    time() + (10 * 60)
                );

                // Hash the code before storing it
                $hashedCode = password_hash(
                    $code,
                    PASSWORD_DEFAULT
                );


                // --------------------------------------------------
                // 4. Insert reset code
                // --------------------------------------------------

                try {

                    $insert = $pdo->prepare(
                        'INSERT INTO password_resets
                        (email, code_hash, expires_at)
                        VALUES (?, ?, ?)'
                    );

                    $insert->execute([$email, $hashedCode, $expiresAt]);

                } catch (PDOException $e) {

                    // For debugging temporarily: error_log($e->getMessage());
                    die('Could not save reset code. Please try again later.');
                }


                // --------------------------------------------------
                // 5. Send the code to the user
                // --------------------------------------------------

                sendResetCodeEmail(
                    $email,
                    $user['fullName'] ?? '',
                    $code
                );
            }


            // Store email in session
            $_SESSION['reset_email'] = $email;
        }


        // --------------------------------------------------
        // 6. Same message whether account exists or not
        // --------------------------------------------------

        $message =
            'If that email is registered, a 6-digit code has been sent. ' .
            'It expires in 10 minutes.';

        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<link rel="stylesheet" href="../Assets/forgot.css">
<link rel="stylesheet" href="../Assets/index.css">
</head>
<body>
  <div class="auth-card">
    <h1>Forgot your password?</h1>
    <p class="subtitle">Enter your email and we'll send you a 6-digit reset code.</p>

    <form method="POST" action="forgot_Pasword.php" id="forgotForm">
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required>
      </div>
      <button type="submit" class="btn" id="submitBtn">Send code</button>
    </form>

    <?php if ($message): ?>
      <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
      <?php if ($messageType === 'success'): ?>
        <a href="verifycode.php" class="back-link">Enter your code &rarr;</a>
      <?php endif; ?>
    <?php endif; ?>

    <a href="login.php" class="back-link">Back to login</a>
  </div>

<script src="../scripts/logout.js"></script>
</body>
</html>