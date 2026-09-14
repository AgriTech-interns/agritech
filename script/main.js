document.addEventListener('DOMContentLoaded', () => {

    /* ==========================================
       1. Mobile Navigation Menu Toggle
       ========================================== */
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    const navLinks = document.querySelector('.navbar-nav');
    const navActions = document.querySelector('.navbar-actions');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks?.classList.toggle('mobile-active');
            navActions?.classList.toggle('mobile-active');
            
            const icon = mobileMenuBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bx-menu');
                icon.classList.toggle('bx-x');
            }
        });
    }

    /* ==========================================
       2. Dynamic Navbar Underline / Active State
       ========================================== */
    const navItems = document.querySelectorAll('.navbar-nav .nav-item');
    const currentPath = window.location.pathname.split('/').pop();

    navItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        const href = link?.getAttribute('href');

        if (href === currentPath || (currentPath === '' && href === 'index.html')) {
            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        }

        item.addEventListener('click', () => {
            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        });
    });

    /* ==========================================
       3. Real-time Live Search Functionality
       ========================================== */
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const cards = document.querySelectorAll('.card');
    const featuredArticle = document.querySelector('.featured')?.closest('article');
    const articlesGrid = document.getElementById('articlesGrid');

    // Create a "No Results" element if it doesn't exist yet
    let noResultsMsg = document.getElementById('noResultsMsg');
    if (!noResultsMsg && articlesGrid) {
        noResultsMsg = document.createElement('p');
        noResultsMsg.id = 'noResultsMsg';
        noResultsMsg.textContent = 'No articles found matching your search.';
        noResultsMsg.style.cssText = 'display: none; text-align: center; color: #6b7280; font-size: 1.1rem; grid-column: 1 / -1; margin: 40px 0; width: 100%;';
        articlesGrid.parentElement.appendChild(noResultsMsg);
    }

    // Main search filter logic
    const filterArticles = () => {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        let visibleCount = 0;

        // 1. Filter Grid Cards
        cards.forEach(card => {
            const content = card.textContent.toLowerCase();
            if (content.includes(query)) {
                card.style.display = ''; // Reset display
                visibleCount++;
            } else {
                card.style.display = 'none'; // Hide card
            }
        });

        // 2. Filter Featured Article
        if (featuredArticle) {
            const featuredContent = featuredArticle.textContent.toLowerCase();
            if (featuredContent.includes(query) || query === '') {
                featuredArticle.style.display = '';
                visibleCount++;
            } else {
                featuredArticle.style.display = 'none';
            }
        }

        // 3. Toggle "No Results" message
        if (noResultsMsg) {
            noResultsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    };

    // Event Listener 1: As the user types in the input
    if (searchInput) {
        searchInput.addEventListener('input', filterArticles);
    }

    // Event Listener 2: When user clicks the "Search" button
    if (searchBtn) {
        searchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            filterArticles();
        });
    }

    /* ==========================================
       4. Newsletter Form Handler
       ========================================== */
    const newsletterForm = document.querySelector('.newsletter form');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailInput = newsletterForm.querySelector('input[type="email"]');
            const userEmail = emailInput?.value.trim();

            if (userEmail) {
                const submitBtn = newsletterForm.querySelector('button');
                const originalText = submitBtn.textContent;
                
                submitBtn.textContent = 'Subscribed! ✓';
                submitBtn.style.backgroundColor = '#2e7d32';
                submitBtn.style.color = '#ffffff';
                submitBtn.disabled = true;

                emailInput.value = '';

                setTimeout(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }

    /* ==========================================
       5. Smooth Scroll for Anchor Links
       ========================================== */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId !== '#') {
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

});