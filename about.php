<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agritech | About</title>

    <link rel="stylesheet" href="../Assets/about.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/index.css">
    <link rel="stylesheet" href="./index1.css">
    <link rel="stylesheet" href="./component.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <?php include __DIR__ . '../navbar.php'; ?>

    <!-- Hero -->
    <section class="hero">
        <div class="overlay">
            <div class="hero-text">
                <h1>About Us</h1>
                <p>
                    We are passionate about transforming agriculture by connecting
                    farmers, buyers, and businesses through innovative technology.
                </p>
            </div>
        </div>
    </section>

    <!-- About -->
    <section class="about">
        <div class="about-image">
            <img src="./images/AGRI.webp" alt="Farmers working in a green agricultural field">
        </div>

        <div class="about-content">
            <p class="eyebrow">Who We Are</p>
            <h2>Building a Digital Future for Agriculture</h2>

            <p>
                Agritech is a digital platform and farm management system designed
                to connect farmers with buyers. We eliminate middlemen,
                promote fair prices, and ensure fresh produce reaches households
                and businesses efficiently.
            </p>

            <p>
                Our platform also helps farmers manage their farms,
                track sales, and monitor deliveries.
            </p>

        </div>
    </section>

    <!-- Mission / Vision / Values -->
    <section class="cards">
        <div class="card">
            <i class="fa-solid fa-seedling" aria-hidden="true"></i>
            <h3>Our Mission</h3>
            <p>
                Empowering farmers through technology by improving food
                distribution and ensuring food security.
            </p>
        </div>

        <div class="card">
            <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
            <h3>Our Vision</h3>
            <p>
                To become the leading marketplace connecting every farmer,
                from local to international markets, through innovative technology.
            </p>
        </div>

        <div class="card">
            <i class="fa-solid fa-star" aria-hidden="true"></i>
            <h3>Our Values</h3>
            <ul>
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Integrity</li>
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Sustainability</li>
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Empowerment</li>
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Satisfaction</li>
            </ul>
        </div>
    </section>

    <!-- Statistics -->
    <section class="stats">
        <div>
            <h2>2,540+</h2>
            <p>Registered Farmers</p>
        </div>
        <div>
            <h2>15,320+</h2>
            <p>Products Sold</p>
        </div>
        <div>
            <h2>8,240+</h2>
            <p>Happy Buyers</p>
        </div>
        <div>
            <h2>120+</h2>
            <p>Communities</p>
        </div>
        <div>
            <h2>98%</h2>
            <p>Satisfaction Rate</p>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="choose">
        <div class="choose-text">
            <p class="eyebrow">Why Choose Us</p>
            <h2>The Best Choice for Farmers and Buyers</h2>

            <div class="list">
                <ul>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Direct Access</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Secure Payment</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Competitive Price</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Farm Management</li>
                </ul>
                <ul>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Quality Products</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Fast Delivery</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Tracking Orders</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Dedicated Support</li>
                </ul>
            </div>
        </div>

        <div class="choose-image">
            <img src="./images/CUL.jpg" alt="Fresh agricultural produce ready for market">
        </div>
    </section>

    <!-- Call To Action -->
    <section class="cta">
        <div>
            <h2>Join AgriTech Today</h2>
            <p>
                And be part of a growing community of farmers and buyers building
                a better, sustainable future together.
            </p>
        </div>

        <div class="buttons">
            <a href="./register.php">Register</a>
        </div>
    </section>

    <?php include __DIR__ . '/footer.php' ?>

</body>
</html>