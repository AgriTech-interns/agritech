<?php 
  include __DIR__ . "/pages/navbar.php";
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriTech | home</title>
    <link rel="stylesheet" href="./Assets/index.css" />
    <link rel="stylesheet" href="./Assets/components.css" />
    <link rel="stylesheet" href="./Assets/home.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    />
    <!-- Basic Icons -->
    <link
      href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css"
      rel="stylesheet"
    />
    <!-- Filled Icons -->
    <link
      href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css"
      rel="stylesheet"
    />
    <!-- Brand Icons -->
    <link
      href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css"
      rel="stylesheet"
    />
  </head>
  <body>

    <!-- ===================================================     HERO SECTION
===================================================== -->

    <main>
      <section class="hero">
        <div class="hero-slider">
          <div
            class="hero-slide active"
            style="
              background-image: url(&quot;./pages/images/farmer with tablet.jpeg&quot;);
            "
          ></div>

          <div
            class="hero-slide"
            style="background-image: url(&quot;./pages/images/hero-equipt.jpg&quot;)"
          ></div>

          <div
            class="hero-slide"
            style="background-image: url(&quot;./pages/images/hero-plant.jpg&quot;)"
          ></div>

          <div
            class="hero-slide"
            style="background-image: url(&quot;./pages/images/herogroup.png&quot;)"
          ></div>
        </div>

        <div class="hero-overlay"></div>

        <div class="hero-content">
          <div class="hero-badge">
            <i class="fa-solid fa-seedling"></i>

            Technology for a stronger agricultural future
          </div>

          <h1>
            Connecting Farmers.

            <span>Growing Possibilities.</span>
          </h1>

          <p>
            From soil testing to market listings, Agro-Tech connects farmers
            with agricultural experts and buyers on one trusted digital
            platform.
          </p>

          <div class="hero-actions">
            <a href="./pages/register.php" class="btn btn-primary">
              Join Agri-Tech

              <i class="fa-solid fa-arrow-right"></i>
            </a>

            <a href="./pages/about.php" class="btn btn-outline">
              Discover Our Platform
            </a>
          </div>

          <!-- Trust -->

          <div class="hero-trust">
            <div class="avatars">
              <span>F</span>
              <span>E</span>
              <span>+</span>
            </div>

            <p>
              Built for farmers, experts and for All agricultural communities.
            </p>
          </div>
        </div>
      </section>

      <!--===================================================
          FEATURED PRODUCTS
          ===================================-->

      <section class="featured-products" id="featured-products">
        <div class="section-product">
          <span class="scetion-lebel"></span>
        </div>
      </section>

      <!-- =====================================================
     PLATFORM STATS
===================================================== -->

      <section class="stats">
        <div class="stats-container">
          <div class="stat">
            <strong>1,000+</strong>

            <span>Farmers & Users</span>
          </div>

          <div class="stat">
            <strong>100+</strong>

            <span>Professionals</span>
          </div>

          <div class="stat">
            <strong>500+</strong>

            <span>Marketplace Listings</span>
          </div>

          <div class="stat">
            <strong>24/7</strong>

            <span>Access to Resources</span>
          </div>
        </div>
      </section>

      <!-- =====================================================
     PROBLEM / INTRODUCTION
===================================================== -->

      <section class="intro section">
        <div class="intro-image">
          <div class="image-label">
            <i class="fa-solid fa-location-dot"></i>

            Supporting Agriculture Across Communities
          </div>
        </div>

        <div class="intro-content">
          <span class="eyebrow"> WHY AGRiTECH? </span>

          <h2>Agriculture works better when people work together.</h2>

          <p>
            Farmers often struggle to find reliable soil and weather data,
            connect with agronomists, or reach buyers beyond their local market.
          </p>

          <p>
            AgriTech brings advisory tools, a trade marketplace, and a network
            of specialists into one accessible digital ecosystem.
          </p>

          <a href="./pages/about.php" class="text-link">
            Learn more about AgriTech

            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </section>

      <!-- =====================================================
     SERVICES
===================================================== -->

      <section class="services section">
        <div class="section-header">
          <span class="eyebrow"> OUR PLATFORM </span>

          <h2>Everything agriculture needs, connected in one place.</h2>

          <p>
            From expert guidance to market access, AgriTech helps agricultural
            communities make better connections and decisions.
          </p>
        </div>

        <div class="service-grid">
          <article class="service-card">
            <div class="service-icon">
              <i class="fa-solid fa-user-tie"></i>
            </div>

            <h3>Agricultural Experts</h3>

            <p>
              Access professional knowledge and practical guidance from
              agricultural specialists.
            </p>

            <a href="./pages/experts.php">
              Meet Our Experts
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </article>

          <article class="service-card">
            <div class="service-icon">
              <i class="fa-solid fa-store"></i>
            </div>

            <h3>Agricultural Marketplace</h3>

            <p>
              Discover products, connect with buyers, and create opportunities
              for agricultural trade.
            </p>

            <a href="./pages/marketplace.php">
              Explore Marketplace
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </article>

          <article class="service-card">
            <div class="service-icon">
              <i class="fa-solid fa-cloud-sun-rain"></i>
            </div>

            <h3>Field Advisory</h3>

            <p>
              irrigation timing, and pest alerts so planting and harvest
              decisions are backed by real field data.
            </p>

            <a href="./pages/blog.php">
              View Advisory Tools
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </article>

          <article class="service-card">
            <div class="service-icon">
              <i class="fa-solid fa-cloud-sun-rain"></i>
            </div>

            <h3>Weather Forecast</h3>

            <p>
              Buea: 32°C • Mostly Sunny Forecast: No rain expected today Great
              day for field work. UV Index is high - stay protected.
            </p>

            <a href="./pages/weather.php">
              View weather Forecast
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </article>

          <article class="service-card">
            <div class="service-icon">
              <i class="fa-solid fa-leaf"></i>
            </div>

            <h3>Grow Your Farm with AgriTech's Relyable Farmers</h3>

            <p>
              Join thousands of farmers using technology to increase
              productivity, improve crop and livestock management, and connect
              with reliable agricultural professionals.
            </p>

            <a href="./pages/farmer.php">
              Meet Our Farmers
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </article>

          <article class="service-card">
            <div class="service-icon">
              <i class="fa-solid fa-cart"></i>
            </div>

            <h3>Buyers are getting a good deal here don't miss out</h3>

            <p>
              Join thousands of buyers to get quality and afordable products
            </p>

            <a href="./pages/buyer-dashboard.php">
              Become a buyer
              <i class="fa-solid fa-arrow-right"></i>
            </a>
          </article>
        </div>
      </section>

      <!-- =====================================================
     HOW IT WORKS
===================================================== -->

      <section class="how-it-works section">
        <div class="section-header">
          <span class="eyebrow"> HOW IT WORKS </span>

          <h2>Getting started is simple.</h2>
        </div>

        <div class="steps">
          <div class="step">
            <span class="step-number"> 01 </span>

            <div>
              <h3>
                <a href="./pages/register.php">Create an Account</a>
              </h3>

              <p>
                Sign up and select your role as a farmer, expert, veterinarian,
                buyer, or other agricultural professional.
              </p>
            </div>
          </div>

          <div class="step">
            <span class="step-number"> 02 </span>

            <div>
              <h3>Connect</h3>

              <p>
                Find experts, Find agricultural products, and Farmers that match
                your needs.
              </p>
            </div>
          </div>

          <div class="step">
            <span class="step-number"> 03 </span>

            <div>
              <h3>Grow Together</h3>

              <p>
                Access knowledge, share experiences, trade products, and build
                stronger agricultural connections.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- =====================================================
     EXPERTS
===================================================== -->

      <section class="experts section">
        <div class="section-header split-header">
          <div>
            <span class="eyebrow"> PROFESSIONAL NETWORK </span>

            <h2>Get the right advice when you need it.</h2>
          </div>

          <a href="./pages/experts.php" class="text-link">
            View all experts

            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>

        <div class="expert-grid">
          <article class="expert-card">
            <div class="expert-avatar">
              <i class="fa-solid fa-seedling"></i>
            </div>

            <div>
              <span class="expert-role"> CROP PRODUCTION </span>

              <h3>Crop & Soil Specialists</h3>

              <p>
                Guidance on crop production, soil management, and farm
                productivity.
              </p>
            </div>
          </article>

          <article class="expert-card">
            <div class="expert-avatar">
              <i class="fa-solid fa-chart-line"></i>
            </div>

            <div>
              <span class="expert-role"> FARM MANAGEMENT </span>

              <h3>Agricultural Consultants</h3>

              <p>
                Practical insights to help improve farm operations and
                decision-making.
              </p>
            </div>
          </article>

          <article class="expert-card">
            <div class="expert-avatar">
              <i class="fa-solid fa-stethoscope"></i>
            </div>

            <div>
              <span class="expert-role"> ANIMAL HEALTH </span>

              <h3>Veterinary Advisors</h3>

              <p>
                Livestock health checks, vaccination schedules, and
                disease-prevention support for herds of any size.
              </p>
            </div>
          </article>
        </div>
      </section>

      <!-- =====================================================
     KNOWLEDGE HUB
===================================================== -->

      <section class="knowledge section">
        <div class="knowledge-content">
          <span class="eyebrow"> KNOWLEDGE HUB </span>

          <h2>Learn from knowledge you can trust.</h2>

          <p>
            Explore practical farming guides, agricultural research, and
            educational materials designed to support better decisions.
          </p>

          <a href="./pages/blog.php" class="btn btn-primary">
            Explore Knowledge Hub

            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>

        <div class="knowledge-list">
          <div>
            <i class="fa-solid fa-book"></i>

            <span> Farming Guides </span>
          </div>

          <div>
            <i class="fa-solid fa-file-lines"></i>

            <span> Research & Publications </span>
          </div>

          <div>
            <i class="fa-solid fa-video"></i>

            <span> Educational Resources </span>
          </div>
        </div>
      </section>

      <!-- =====================================================
     FINAL CTA
===================================================== -->

      <section class="final-cta">
        <div>
          <span class="eyebrow"> JOIN THE MOVEMENT </span>

          <h2>Let's build a stronger agricultural future together.</h2>

          <p>
            Join farmers, experts, buyers, and use technology to create new
            opportunities.
          </p>
        </div>

        <a href="./pages/register.php" class="btn btn-white">
          Create Your Account

          <i class="fa-solid fa-arrow-right"></i>
        </a>
      </section>
    </main>

    <script>
      const heroSlide = document.querySelectorAll(".hero-slide");

      let index = 0;

      function showSlide() {
        heroSlide[index].classList.remove("active");
        index++;

        if (index >= heroSlide.length) {
          index = 0;
        }

        heroSlide[index].classList.add("active");
      }
      setInterval(showSlide, 5000);
    </script>
    <?php include './pages/footer.php'; ?>
  </body>
</html>
