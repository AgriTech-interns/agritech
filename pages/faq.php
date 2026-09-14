<?php $currentPage = 'faq'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs | AgriTech</title>
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
    <p class="breadcrumb"><a href="index.php">Home</a> &rsaquo; FAQs</p>
    <h1>Frequently Asked Questions</h1>
    <p>Everything you need to know about using the AgriTech platform.</p>
</div>

<!-- ── CONTENT ── -->
<main class="page-content">

    <!-- General -->
    <div class="content-section">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
            General
        </h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                What is AgriTech?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">AgriTech is a digital platform that connects farmers, buyers, and agricultural experts across Africa. It provides a marketplace for agricultural products, expert consultations, and business tools to help grow your agricultural enterprise.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                Who can use AgriTech?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">AgriTech is open to farmers, agricultural buyers, cooperatives, agribusinesses, and agricultural experts. Anyone with an interest in the agricultural value chain is welcome to join.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                Is AgriTech free to use?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">Registering and browsing on AgriTech is completely free. A small transaction fee applies when you complete a sale through the marketplace. Premium plans are available for advanced features.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                In which countries is AgriTech available?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">AgriTech is currently available in Cameroon, Nigeria, Ghana, and Kenya. We are actively expanding to additional African countries. Stay updated via our newsletter.</div>
        </div>
    </div>

    <!-- Account -->
    <div class="content-section">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            Account &amp; Registration
        </h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                How do I create an account?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">Click "Sign In" in the top navigation bar, fill in your name, email or phone number, select your role (Farmer, Buyer, or Expert), and create a password. Verify your email or phone to activate your account.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                I forgot my password. How do I reset it?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">On the login page, click "Forgot Password?" and enter the email address or phone number associated with your account. You will receive a password reset link within a few minutes.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                Can I change my account role after registration?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">Role changes require verification and approval. Contact our support team at support@gmail.com with your request and valid proof of your new role (e.g. a business registration document).</div>
        </div>
    </div>

    <!-- Marketplace -->
    <div class="content-section">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></span>
            Marketplace &amp; Orders
        </h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                How do I list my farm products?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">Log in to your Farmer account, navigate to your dashboard, and click "Add Product". Fill in the product name, description, price, available quantity, harvest date, and upload clear photos. Your listing will be visible immediately after submission.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                How do I track my order?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">After placing an order, go to your Buyer dashboard and click "My Orders". Each order shows a real-time status: Pending, Confirmed, In Transit, or Delivered. You will also receive SMS and email notifications at each stage.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                What happens if a seller cancels my order?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">If a seller cancels your order, you will receive an instant notification and a full refund to your original payment method within 3–5 business days. You can also browse alternative listings immediately.</div>
        </div>
    </div>

    <!-- Payments -->
    <div class="content-section">
        <h2>
            <span class="section-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
            Payments &amp; Refunds
        </h2>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                What payment methods are accepted?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">We accept MTN Mobile Money, Orange Money, Visa/Mastercard, and direct bank transfers. All transactions are secured using end-to-end encryption.</div>
        </div>

        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                How do I request a refund?
                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-answer">Go to "My Orders", find the order in question, and click "Raise a Dispute". Describe the issue and attach photos if applicable. Our team will investigate and process eligible refunds within 5–10 business days.</div>
        </div>
    </div>

    <!-- Still need help -->
    <div class="help-contact">
        <div>
            <h3>Didn't find your answer?</h3>
            <p>Our support team is ready to help. Reach us any weekday between 8 AM – 6 PM WAT.</p>
        </div>
        <a href="help.php" class="btn-white">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Visit Help Center
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
function toggleFaq(btn) {
    const answer  = btn.nextElementSibling;
    const isOpen  = btn.classList.contains('open');
    document.querySelectorAll('.faq-question.open').forEach(q => {
        q.classList.remove('open');
        q.nextElementSibling.classList.remove('open');
    });
    if (!isOpen) {
        btn.classList.add('open');
        answer.classList.add('open');
    }
}
</script>


</body>
</html>
