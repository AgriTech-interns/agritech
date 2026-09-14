<?php $currentPage = 'help'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center | AgriTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/index.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/shared.css">
</head>
<body>
<?php include __DIR__ . '../navbar.php' ?>
<!-- ── HERO ── -->
<div class="page-hero">
    <p class="breadcrumb"><a href="index.php">Home</a> &rsaquo; Help Center</p>
    <h1>Help Center</h1>
    <p>Find answers, guides, and support to get the most out of AgriTech.</p>
</div>

<!-- ── CONTENT ── -->
<main class="page-content">

    <!-- Topic cards -->
    <h2 style="font-family:'Merriweather',serif;font-size:var(--text-2xl);color:var(--color-heading);margin-bottom:var(--space-6);text-align:center;">Browse by Topic</h2>

    <div class="help-grid">
        <a href="faq.php" class="help-card">
            <div class="help-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h3>FAQs</h3>
            <p>Quick answers to the most commonly asked questions.</p>
        </a>
        <a href="#account" class="help-card">
            <div class="help-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h3>Account & Profile</h3>
            <p>Managing your account, settings, and personal details.</p>
        </a>
        <a href="#marketplace" class="help-card">
            <div class="help-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            </div>
            <h3>Marketplace</h3>
            <p>Listing products, buying, selling and managing orders.</p>
        </a>
        <a href="#payments" class="help-card">
            <div class="help-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <h3>Payments</h3>
            <p>Payment methods, transactions, and billing issues.</p>
        </a>
        <a href="#security" class="help-card">
            <div class="help-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h3>Security</h3>
            <p>Keeping your account safe and reporting suspicious activity.</p>
        </a>
        <a href="contact.php" class="help-card">
            <div class="help-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            </div>
            <h3>Contact Support</h3>
            <p>Reach our team directly for personalised assistance.</p>
        </a>
    </div>

    <!-- Account section -->
    <div class="content-section" id="account">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            Account &amp; Profile
        </h2>
        <p><strong>How do I create an account?</strong><br>Click "Sign In" on the navigation bar and complete the registration form. You can sign up as a Farmer, Buyer, or Admin.</p>
        <p><strong>How do I reset my password?</strong><br>On the login page, click "Forgot Password?" and enter your registered email or phone number. You will receive a reset link.</p>
        <p><strong>How do I update my profile?</strong><br>After logging in, navigate to your dashboard and click "Edit Profile" to update your name, photo, location, and contact details.</p>
    </div>

    <!-- Marketplace section -->
    <div class="content-section" id="marketplace">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></span>
            Marketplace
        </h2>
        <p><strong>How do I list a product?</strong><br>Log in as a Farmer, go to your dashboard, click "Add Product" and fill in the product details including name, price, quantity, and photos.</p>
        <p><strong>How do I place an order?</strong><br>Browse the marketplace, select a product, choose your quantity, and click "Add to Cart" or "Buy Now" to complete your order.</p>
        <p><strong>Can I negotiate prices?</strong><br>Yes. You can message the farmer directly using the in-platform messaging feature to discuss pricing before placing an order.</p>
    </div>

    <!-- Payments section -->
    <div class="content-section" id="payments">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
            Payments
        </h2>
        <p><strong>What payment methods are accepted?</strong><br>AgriTech supports Mobile Money (MTN, Orange), bank transfers, and major debit/credit cards.</p>
        <p><strong>When will a seller receive payment?</strong><br>Payments are held in escrow and released to the seller once the buyer confirms receipt of the order.</p>
        <p><strong>What is the refund policy?</strong><br>If a product is not delivered or does not match the listing, buyers can raise a dispute within 7 days. Refunds are processed within 5–10 business days.</p>
    </div>

    <!-- Security section -->
    <div class="content-section" id="security">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
            Security
        </h2>
        <p><strong>How do I secure my account?</strong><br>Use a strong, unique password and enable two-factor authentication from your account settings.</p>
        <p><strong>How do I report a suspicious user or listing?</strong><br>Click the "Report" button on any profile or listing. Our team reviews all reports within 24 hours.</p>
        <p><strong>What should I do if my account is compromised?</strong><br>Reset your password immediately and contact support at <strong>support@gmail.com</strong> or call <strong>+234 800 123 4567</strong>.</p>
    </div>

    <!-- Contact strip -->
    <div class="help-contact">
        <div>
            <h3>Still need help?</h3>
            <p>Our support team is available Monday – Friday, 8 AM – 6 PM WAT.</p>
        </div>
        <a href="contact.php" class="btn-white">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Contact Support
        </a>
    </div>

</main>


 <?php include __DIR__ . '../footer.php' ?>
<script>
const hamburger = document.getElementById('hamburger');
const navMenu   = document.getElementById('navMenu');
hamburger.addEventListener('click', () => {
    const open = navMenu.classList.toggle('open');
    hamburger.classList.toggle('active', open);
    hamburger.setAttribute('aria-expanded', open);
    document.body.classList.toggle('no-scroll', open);
});
navMenu.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('open');
        hamburger.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('no-scroll');
    });
});
</script>
</body>
</html>
