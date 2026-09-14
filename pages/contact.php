<?php



require_once "config.php";

// Default state for this request
$formMessage = "";
$formStatus  = "";
$name        = "";
$email       = "";
$subject     = "";
$message     = "";

// Handle the "Send us a Message" form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["contact_submit"])) {

    // Collect + sanitize input
    $name    = trim($_POST["name"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($name === "" || $email === "" || $message === "") {
        $formMessage = "Please fill in your name, email, and message.";
        $formStatus  = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formMessage = "Please enter a valid email address.";
        $formStatus  = "error";
    } elseif (!isset($conn) || !($conn instanceof mysqli)) {
        $formMessage = "Sorry, we're having trouble connecting right now. Please try again later.";
        $formStatus  = "error";
    } else {
        // Prepared statement to prevent SQL injection
        $stmt = $conn->prepare(
            "INSERT INTO contact_messages (name, email, subject, message, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $formMessage = "Thanks, $name! Your message has been sent. We'll get back to you soon.";
            $formStatus  = "success";
            // Clear the fields only on success so a fresh form is shown
            $name = $email = $subject = $message = "";
        } else {
            $formMessage = "Something went wrong while saving your message. Please try again.";
            $formStatus  = "error";
        }

        $stmt->close();
    }

    // Store the result in the session and redirect (Post/Redirect/Get)
    // so refreshing the page never resubmits the form.
    $_SESSION["contact_form_message"] = $formMessage;
    $_SESSION["contact_form_status"]  = $formStatus;

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    header("Location: contact.php");
    exit;
}

// On a normal (GET) page load, pick up any flash message left by a redirect
if (isset($_SESSION["contact_form_message"])) {
    $formMessage = $_SESSION["contact_form_message"];
    $formStatus  = $_SESSION["contact_form_status"];
    unset($_SESSION["contact_form_message"], $_SESSION["contact_form_status"]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AGRI-TECH - Contact Us</title>

    <link rel="stylesheet" href="../Assets/contact.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/index.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>

<body>
    <?php include __DIR__ . '../navbar.php'; ?>

    <!--==========================
        TOP BAR
    ===========================-->

    <section class="top-bar">

           <h1>Contact Us</h1>

            <p>Home / Contact Us</p>
        
        <div class="container top-container">

            <div class="top-left">

                <span>
                    That's right, we only sell 100% organic
                </span>

                <span>
                    <i class="fa-solid fa-location-dot"></i>
                    Cameroon, +237
                </span>

                <span>
                    <i class="fa-solid fa-phone"></i>
                    +237 699999999
                </span>

            </div>

            <div class="top-right">

                <a href="#"><i class="fab fa-twitter"></i></a>

                <a href="#"><i class="fab fa-youtube"></i></a>

                <a href="#"><i class="fab fa-instagram"></i></a>

            </div>

        </div>

    </section>



    <!--==========================
        CONTACT
    ===========================-->

    <section class="contact">

        <div class="container contact-grid">

            <!-- Left Side -->

            <div class="contact-form">

                <span class="small-title">

                    HAVE QUESTIONS?

                </span>

                <h2>

                    Send us a Massage

                </h2>

                <?php if ($formMessage !== ""): ?>
                    <div class="form-notice form-notice-<?php echo htmlspecialchars($formStatus); ?>">
                        <?php echo htmlspecialchars($formMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php">

                    <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name); ?>">

                    <div class="row">

                        <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
                        <input type="text" name="subject" placeholder="Subject" value="<?php echo htmlspecialchars($subject); ?>">

                    </div>

                    <textarea name="message" placeholder="Give us feedbak"><?php echo htmlspecialchars($message); ?></textarea>

                    <button type="submit" name="contact_submit">

                        <i class="fa-solid fa-paper-plane"></i>

                        Get In Touch

                    </button>

                </form>

            </div>

            <!-- Right Side -->

            <div class="contact-info">

                <h2>

                    Contact Information

                </h2>

                <p>
                    Let's connect have a problem or just
                    want to say
                    HELLO


                </p>

                <div class="single-info">

                    <div class="circle green">

                        <i class="fa-solid fa-phone"></i>

                    </div>

                    <div>

                        <h4>Hotline</h4>

                        <span>+237 675767786</span>

                    </div>

                </div>

                <div class="single-info">

                    <div class="circle yellow">

                        <i class="fa-solid fa-location-dot"></i>

                    </div>

                    <div>

                        <h4>Our Location</h4>

                        <span>

                        molyko Street,

                            The Grand Avenue

                            UB Block,

                            Buea city

                        </span>

                    </div>

                </div>

                <div class="single-info">

                    <div class="circle dark">

                        <i class="fa-solid fa-envelope"></i>

                    </div>

                    <div>

                        <h4>Official Email</h4>

                        <span>

                            info@Agri-Tech.com

                        </span>

                    </div>

                </div>

                <img src="7.JPG" class="wheat">

            </div>

        </div>

    </section>


    <?php include __DIR__  . '/footer.php' ?>

</body>

</html>