<?php
  
// Buffer output and start the session before anything else can be echoed.
// This guarantees header("Location: ...") below can never fail with a
// "headers already sent" error, which is the most common reason a
// role-based redirect silently fails to reach the dashboard. wyrqg2262836



require_once "config.php";


// ==========================================
// ALLOWED ROLES AND DASHBOARDS
// ==========================================

$roleDashboards = [
    'farmer'  => '../pages/farmer.php',
    'expert'  => 'experts.php',
    'buyer'   => '../pages/buyer-dashboard.php'
];



// ==========================================
// FLASH ERROR + OLD FORM VALUES
// ==========================================

$formError = $_SESSION['register_error'] ?? '';

$old = $_SESSION['register_old'] ?? [
    'fullName' => '',
    'email'    => '',
    'phone'    => '',
    'role'     => ''
];

unset(
    $_SESSION['register_error'],
    $_SESSION['register_old']
);


// ==========================================
// FORM SUBMISSION
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form values
    $fullName        = trim($_POST['fullName'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $role            = strtolower(trim($_POST['role'] ?? ''));
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';


    // Save entered values in case validation fails
    $old = [
        'fullName' => $fullName,
        'email'    => $email,
        'phone'    => $phone,
        'role'     => $role
    ];


    // ==========================================
    // ERROR HANDLER
    // ==========================================

    $fail = function ($message) use ($old) {

        $_SESSION['register_error'] = $message;
        $_SESSION['register_old']   = $old;

        header("Location: register.php");
        exit();
    };


    // ==========================================
    // REQUIRED FIELDS
    // ==========================================

    if (
        $fullName === '' ||
        $email === '' ||
        $phone === '' ||
        $role === ''
    ) {
        $fail("Please fill in all required fields.");
    }


    // ==========================================
    // VALIDATE EMAIL
    // ==========================================

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fail("Please enter a valid email address.");
    }


    // ==========================================
    // VALIDATE ROLE
    // ==========================================

    if (!array_key_exists($role, $roleDashboards)) {

        $fail(
            "Please select a valid role. Received: " .
            htmlspecialchars($role)
        );
    }


    // ==========================================
    // TERMS AND CONDITIONS
    // ==========================================

    if (!isset($_POST['terms'])) {
        $fail(
            "You must agree to the Terms of Service and Privacy Policy."
        );
    }


    // ==========================================
    // PASSWORD VALIDATION
    // ==========================================

    if ($password !== $confirmPassword) {
        $fail("Passwords do not match.");
    }


    if (strlen($password) < 8) {
        $fail("Password must be at least 8 characters long.");
    }


    // ==========================================
    // CHECK IF EMAIL ALREADY EXISTS
    // PDO VERSION
    // ==========================================

    try {

        $check = $pdo->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $check->execute([$email]);

        $existingUser = $check->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $fail("Email already exists.");
        }

    } catch (PDOException $e) {

        $fail("Database error while checking email. Please try again later.");
    }


    // ==========================================
    // HASH PASSWORD
    // ==========================================

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    // ==========================================
    // AUTOMATIC FIELDS
    // ==========================================

    $is_active = 1;

    $now = date('Y-m-d H:i:s');

    $createdAt = $now;
    $updatedAt = $now;


    // ==========================================
    // INSERT USER
    // ==========================================

    try {

        $sql = $pdo->prepare(
            "INSERT INTO users
            (
                fullName,
                email,
                phone,
                password,
                role,
                is_active,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );


        $sql->execute([
            $fullName,
            $email,
            $phone,
            $hashedPassword,
            $role,
            $is_active,
            $createdAt,
            $updatedAt
        ]);


        // Get newly created user's ID
        $newUserId = $pdo->lastInsertId();


        // ==========================================
        // CREATE LOGIN SESSION
        // ==========================================

        $_SESSION['user_id']  = $newUserId;
        $_SESSION['fullName'] = $fullName;
        $_SESSION['email']    = $email;
        $_SESSION['role']     = $role;


        // ==========================================
        // REDIRECT TO CORRECT DASHBOARD
        // ==========================================

        ob_end_clean();

        header(
            "Location: " . $roleDashboards[$role]
        );

        exit();


    } catch (PDOException $e) {

        // For development, you can temporarily use:
        // $fail("Database error: " . $e->getMessage());

        $fail(
            "Registration failed. Please try again later."
        );
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agro-Tech | Create Account</title>

    <link rel="stylesheet" href="../Assets/register.css">
    <link rel="stylesheet" href="../Assets/index.css">
    <link rel="stylesheet" href="../Assets/components.css">

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

    <div class="register-container">

        <!-- LEFT IMAGE SECTION -->
        <div class="image-section">

            <div class="image-overlay"></div>

            <div class="image-content">

                <h1>Join AgriTech</h1>

                <p>
                    Connect with farmers,
                    agricultural experts and buyers.
                </p>

                <div class="benefits">

                    <div class="benefit">
                        <span>✓</span>
                        <p>Connect with agricultural experts</p>
                    </div>

                    <div class="benefit">
                        <span>✓</span>
                        <p>Access agricultural knowledge</p>
                    </div>

                    <div class="benefit">
                        <span>✓</span>
                        <p>Buy and sell agricultural products</p>
                    </div>

                    <div class="benefit">
                        <span>✓</span>
                        <p>Join the farming community</p>
                    </div>

                </div>

            </div>

        </div>


        <!-- RIGHT REGISTRATION SECTION -->
        <div class="form-section">

            <div class="form-container">

                <!-- LOGO -->
                <div class="logo">
                
                    <div>
                        <h2>Agri<span>Tech</span></h2>
                        <p>Connecting Farmers, Growing Together</p>
                    </div>
                </div>


                <!-- FORM HEADER -->
                <div class="form-header">

                    <h1>Create an Account</h1>

                    <p>
                        Join AgriTech and start growing with
                        a connected agricultural community.
                    </p>

                </div>

                <?php if ($formError !== ''): ?>
                    <div class="form-notice form-notice-error">
                        <?php echo htmlspecialchars($formError); ?>
                    </div>
                <?php endif; ?>

                <!-- REGISTRATION FORM -->
                <form action="./register.php" method="POST">

                    <!-- NAME -->

                        <div class="form-group">
                            <label for="fullName">
                                Full Name
                            </label>

                            <input
                                type="text" id="fullName" name="fullName" placeholder="Enter full name"
                                value="<?php echo htmlspecialchars($old['fullName']); ?>"
                                required >
                        </div>


                    <!-- EMAIL -->
                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($old['email']); ?>"
                            required
                        >

                    </div>


                    <!-- PHONE -->
                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <input
                            type="tel" id="phone" name="phone"
                            placeholder="Enter your phone number"
                            value="<?php echo htmlspecialchars($old['phone']); ?>"
                            required
                        >

                    </div>


                    <!-- ROLE -->
                    <div class="form-group">

                        <label for="role">
                            Select Your Role
                        </label>

                        <select id="role" name="role" required>

                            <option value="">
                                Choose your role
                            </option>

                            <option value="farmer" <?php echo $old['role'] === 'farmer' ? 'selected' : ''; ?>>
                                Farmer
                            </option>


                            <option value="expert" <?php echo $old['role'] === 'experts' ? 'selected' : ''; ?>>
                                Expert
                            </option>

                            <option value="buyer" <?php echo $old['role'] === 'buyer' ? 'selected' : ''; ?>>
                                Buyer
                            </option>

                        </select>

                    </div>


                    <!-- PASSWORD -->
                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password" id="password" name="password"
                            placeholder="Create a password"
                            required
                        >

                    </div>


                    <!-- CONFIRM PASSWORD -->
                    <div class="form-group">

                        <label for="confirmPassword">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirmPassword"
                            placeholder="Confirm your password"
                            required
                        >

                    </div>


                    <!-- TERMS -->
                    <div class="terms">

                        <input
                            type="checkbox"
                            id="terms"
                            name="terms"
                            required
                        >

                        <label for="terms">
                            I agree to the
                            <a href="terms.php">Terms of Service</a>
                            and
                            <a href="#">Privacy Policy</a>
                        </label>

                    </div>


                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="register-btn"
                    >
                        Create Account
                    </button>

                </form>


                <!-- LOGIN -->
                <div class="login-link">

                    <p>
                        Already have an account?
                        <a href="login.php">
                            Login
                        </a>
                    </p>

                </div>

            </div>

        </div>

    </div>

    <script src="../scripts/register.js"></script>

</body>
</html>