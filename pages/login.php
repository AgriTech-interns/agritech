<?php
session_start();
require_once "config.php";


$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);

        if (!$stmt) {
            $error = "Database error. Please try again later.";
        } else {

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Same generic message as a wrong password, so we don't reveal
                // whether an email is registered.
                $error = "Incorrect email or password.";
            } else {

                if (!isset($user["password"])) {
                    $error = "Database error. Please try again later.";
                } elseif (password_verify($password, $user["password"])) {

                    // Regenerate the session ID on login to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["user_id"];
                    $_SESSION["name"] = $user["fullName"];
                    $_SESSION["role"] = $user["role"];

                    // Normalize the role so stray case/whitespace in the DB
                    // doesn't cause a false "unknown role"
                    $role = strtolower(trim($user["role"]));

                    switch ($role) {
                        case "farmer":
                            header("Location: farmer.php");
                            exit();
                        case "expert":
                            header("Location: experts.php");
                            exit();
                        case "admin":
                            header("Location: admin-dashboard.php");
                            exit();
                        case "buyer":
                            header("Location: buyer-dashboard.php");
                            exit();
                        default:
                            $error = "Unknown user role. Please contact support.";
                    }
    
                } else {
                    $error = "Incorrect email or password.";
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agro-Tech | Login</title>

    <link rel="stylesheet" href="../Assets/index.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/login.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Basic Icons -->
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
<!-- Filled Icons -->
<link href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css" rel="stylesheet">
<!-- Brand Icons -->
<link href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css" rel="stylesheet">
</head>


<body>

    <main class="login-container">

        <!-- LEFT IMAGE SECTION -->
        <section class="image-section">

            <div class="image-overlay"></div>

            <div class="image-content">

                <h1>Welcome Back</h1>

                <p>
                    Connect, collaborate and grow with
                    the Agri-Tech agricultural community.
                </p>

                <div class="benefits">

                    <div class="benefit">
                        <span>✓</span>
                        <p>Connect with agricultural experts</p>
                    </div>

                    <div class="benefit">
                        <span>✓</span>
                        <p>Access valuable farming knowledge</p>
                    </div>

                    <div class="benefit">
                        <span>✓</span>
                        <p>Buy and sell agricultural products</p>
                    </div>

                    <div class="benefit">
                        <span>✓</span>
                        <p>Connect with the farming community</p>
                    </div>

                </div>

            </div>

        </section>


        <!-- RIGHT LOGIN SECTION -->
        <section class="form-section">

            <div class="form-container">

                <!-- LOGO -->
                <div class="logo">

                    <div class="logo-text">
                        <h2>Agri<span>Tech</span></h2>

                        <p>
                            Connecting Farmers, Growing Together
                        </p>
                    </div>

                </div>


                <!-- LOGIN HEADER -->
                <div class="form-header">

                    <h1>Welcome Back!</h1>

                    <p>
                        Login to your AgriTech account to
                        continue your agricultural journey.
                    </p>

                </div>

                <?php if (!empty($error)): ?>
                    <div class="form-error" style="color:#c0392b; margin-bottom:16px;">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <!-- LOGIN FORM -->
                <form action="login.php" method="POST">

                    <!-- EMAIL -->
                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter your email address"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->
                    <div class="form-group">

                        <div class="password-label">

                            <label for="password">
                                Password
                            </label>

                            <a href="forgot_Pasword.php">
                                Forgot Password?
                            </a>

                        </div>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                    </div>


                    <!-- REMEMBER ME -->
                    <div class="remember-me">

                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                        >

                        <label for="remember">
                            Remember me
                        </label>

                    </div>


                    <!-- LOGIN BUTTON -->
                    <button 
                        type="submit"
                        class="login-btn"
                    >
                        Login
                    </button>

                </form>


                <!-- REGISTER LINK -->
                <div class="register-link">

                    <p>
                        Don't have an account?
                        <a href="register.php">
                            Create an Account
                        </a>
                    </p>

                </div>


                <!-- DIVIDER -->
                <div class="divider">
                    <span>or</span>
                </div>


                <!-- BACK TO HOME -->
                <a href="../index.php" class="back-home">
                    ← Back to Home
                </a>

            </div>

        </section>

    </main>

     <script src="../scripts/login.js"></script>

</body>
</html>