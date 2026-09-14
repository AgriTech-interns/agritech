<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agro-Tech | Reviews</title>

    <link rel="stylesheet" href="../Assets/review.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/index.css">
</head>

<?php 
  include  __DIR__ . "../navbar.php";
?>

<body>

   
    <!-- ================= RATING SUMMARY ================= -->
    <section class="rating-section">

        <div class="rating-summary">

            <div class="rating-number">
                <strong>4.8</strong>

                <div class="stars">
                    ★★★★★
                </div>

                <p>
                    Based on community reviews
                </p>
            </div>


            <div class="rating-bars">

                <div class="rating-row">
                    <span>5 ★</span>

                    <div class="bar">
                        <span style="width: 90%;"></span>
                    </div>

                    <small>90%</small>
                </div>


                <div class="rating-row">
                    <span>4 ★</span>

                    <div class="bar">
                        <span style="width: 75%;"></span>
                    </div>

                    <small>75%</small>
                </div>


                <div class="rating-row">
                    <span>3 ★</span>

                    <div class="bar">
                        <span style="width: 35%;"></span>
                    </div>

                    <small>35%</small>
                </div>


                <div class="rating-row">
                    <span>2 ★</span>

                    <div class="bar">
                        <span style="width: 15%;"></span>
                    </div>

                    <small>15%</small>
                </div>

            </div>

        </div>

    </section>


    <!-- ===========
        <!-- Mobile ====== REVIEWS ================= -->
    <section class="reviews-section">

        <div class="section-heading">

            <span>COMMUNITY FEEDBACK</span>

            <h2>
                Experiences From Our Users
            </h2>

            <p>
                Hear from members of the AgriTech community.
            </p>

        </div>


        <div class="review-grid">

            <!-- REVIEW 1 -->
            <article class="review-card">

                <div class="review-top">

                    <div class="user">

                        <div class="avatar">
                            J
                        </div>

                        <div>
                            <h3>JOANA</h3>
                            <small>Farmer</small>
                        </div>

                    </div>

                    <div class="stars">
                        ★★★★★
                    </div>

                </div>

                <p class="review-text">
                    "AgriTech has made it much easier for me to
                    connect with agricultural experts and get
                    reliable information about my farm."
                </p>

                <span class="review-date">
                    Verified user
                </span>

            </article>


            <!-- REVIEW 2 -->
            <article class="review-card">

                <div class="review-top">

                    <div class="user">

                        <div class="avatar">
                            M
                        </div>

                        <div>
                            <h3>MATER</h3>
                            <small>Veterinarian</small>
                        </div>

                    </div>

                    <div class="stars">
                        ★★★★★
                    </div>

                </div>

                <p class="review-text">
                    "The platform provides a convenient way to
                    communicate with farmers and understand their
                    needs. It has improved how I provide support."
                </p>

                <span class="review-date">
                    Verified user
                </span>

            </article>


            <!-- REVIEW 3 -->
            <article class="review-card">

                <div class="review-top">

                    <div class="user">

                        <div class="avatar">
                            P
                        </div>

                        <div>
                            <h3>Paul</h3>
                            <small>Agricultural Expert</small>
                        </div>

                    </div>

                    <div class="stars">
                        ★★★★★
                    </div>

                </div>

                <p class="review-text">
                    "The knowledge resources and community features
                    provide farmers with useful information that can
                    support better agricultural decisions."
                </p>

                <span class="review-date">
                    Verified user
                </span>

            </article>


            <!-- REVIEW 4 -->
            <article class="review-card">

                <div class="review-top">

                    <div class="user">

                        <div class="avatar">
                            MR
                        </div>

                        <div>
                            <h3>Mary ROSE</h3>
                            <small>Buyer</small>
                        </div>

                    </div>

                    <div class="stars">
                        ★★★★☆
                    </div>

                </div>

                <p class="review-text">
                    "The marketplace makes it easier to discover
                    agricultural products and communicate with
                    sellers."
                </p>

                <span class="review-date">
                    Verified user
                </span>

            </article>

        </div>

    </section>


    <!-- ================= LEAVE REVIEW ================= -->
    <section class="leave-review">

        <div class="leave-content">

            <span>SHARE YOUR EXPERIENCE</span>

            <h2>
                Have You Used AgriTech?
            </h2>

            <p>
                Your feedback helps us improve the platform and
                serve the agricultural community better.
            </p>

            <a href="login.php" class="review-btn">
                Write a Review
            </a>

        </div>

    </section>


       <!-- FOOTER DESIGN -->

    <footer class="footer">

    <div class="footer-container">

        <!-- Brand -->
        <div class="footer-column brand-column">

            <div class="footer-logo">
                <i class="fas fa-seedling"></i>
                <span>AgriTech</span>
            </div>

            <p>
                Empowering agriculture through technology,
                collaboration, and innovation.
            </p>

            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>

        </div>


        <!-- Company -->
        <div class="footer-column">

            <h3>Company</h3>

            <a href="about.php">About Us</a>
            <a href="#">Buyer</a>
            <a href="#">Farmer</a>
            <a href="contact.php">Contact Us</a>

        </div>


        <!-- Services -->
        <div class="footer-column">

            <h3>Services</h3>

            <a href="marketplace.php">Marketplace</a>
            <a href="experts.php">Experts</a>
            <a href="blog.php">Blog</a>

        </div>


        <!-- Support -->
        <div class="footer-column">

            <h3>Support</h3>

            <a href="../beckend/help.php">Help Center</a>
            <a href="../beckend/faq.php">FAQs</a>
            <a href="#">Privacy Policy</a>
            <a href="../beckend/terms.php">Terms & Conditions</a>

        </div>


        <!-- Newsletter -->
        <div class="footer-column newsletter">

            <h3>Stay Updated</h3>

            <p>
                Subscribe to receive agricultural news,
                expert tips, and platform updates.
            </p>

            <form>

                <input
                    type="email"
                    placeholder="Enter your email"
                    required
                >

                <button type="submit">
                    Subscribe
                </button>

            </form>

        </div>

    </div>


    <!-- Bottom Footer -->

    <div class="footer-bottom">

        <p>
            © 2026 Agri-Tech. All Rights Reserved.
        </p>

        <div>

            <a href="#">Privacy Policy</a>

            <span>|</span>

            <a href="../beckend/terms.php">Terms & Conditions</a>

        </div>

    </div>

</footer>
</body>
</html>