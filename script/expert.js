/* ==========================================================
   AgriTech — Expert Dashboard interactivity
   Pairs with experts.php (which renders real data server-side).
   This file only handles UI behaviour: navigation, search,
   notifications, and the appointment-details popup.
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {

    /* ------------------------------------------------------
       1. MOBILE SIDEBAR TOGGLE
    ------------------------------------------------------ */
    const sidebar       = document.getElementById('sidebar');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('sidebar-open');
        });

        // Close the sidebar when tapping outside it on mobile
        document.addEventListener('click', function (e) {
            const isOpen = sidebar.classList.contains('sidebar-open');
            const clickedInside = sidebar.contains(e.target) || mobileMenuBtn.contains(e.target);

            if (isOpen && !clickedInside) {
                sidebar.classList.remove('sidebar-open');
            }
        });
    }


    /* ------------------------------------------------------
       2. LIVE SEARCH
       Filters appointments, messages and articles already
       rendered on the page by the PHP above.
    ------------------------------------------------------ */
    const searchInput = document.getElementById('dashboardSearch');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = searchInput.value.trim().toLowerCase();

            const searchableGroups = [
                '#appointmentList .appointment',
                '.message-list .message',
                '.article-list .article'
            ];

            searchableGroups.forEach(function (selector) {
                document.querySelectorAll(selector).forEach(function (item) {
                    const text = item.textContent.toLowerCase();
                    item.style.display = (term === '' || text.includes(term)) ? '' : 'none';
                });
            });
        });
    }


    /* ------------------------------------------------------
       3. NOTIFICATION BELL
       Lightweight dropdown; dismisses the red dot on open.
       Wire this to a real notifications table/endpoint later
       if you add one — for now it's a self-contained UI piece.
    ------------------------------------------------------ */
    const notificationBtn = document.getElementById('notificationBtn');

    if (notificationBtn) {
        let panel = null;

        notificationBtn.addEventListener('click', function (e) {
            e.stopPropagation();

            if (panel) {
                panel.remove();
                panel = null;
                return;
            }

            const dot = notificationBtn.querySelector('.notification-dot');
            if (dot) dot.style.display = 'none';

            panel = document.createElement('div');
            panel.className = 'agritech-notification-panel';
            panel.style.cssText = [
                'position:absolute', 'right:0', 'margin-top:8px',
                'width:260px', 'background:#fff', 'border-radius:10px',
                'box-shadow:0 8px 24px rgba(0,0,0,0.15)', 'padding:14px',
                'font-size:14px', 'color:#333', 'z-index:1000'
            ].join(';');
            panel.innerHTML = '<strong style="display:block;margin-bottom:6px;">Notifications</strong>' +
                               '<span style="color:#888;">You\'re all caught up.</span>';

            notificationBtn.style.position = 'relative';
            notificationBtn.appendChild(panel);
        });

        document.addEventListener('click', function (e) {
            if (panel && !notificationBtn.contains(e.target)) {
                panel.remove();
                panel = null;
            }
        });
    }


    /* ------------------------------------------------------
       4. PROFILE MENU
    ------------------------------------------------------ */
    const profileTrigger = document.getElementById('profileMenuTrigger');

    if (profileTrigger) {
        let menu = null;

        profileTrigger.addEventListener('click', function (e) {
            e.stopPropagation();

            if (menu) {
                menu.remove();
                menu = null;
                return;
            }

            menu = document.createElement('div');
            menu.className = 'agritech-profile-menu';
            menu.style.cssText = [
                'position:absolute', 'right:0', 'margin-top:8px',
                'min-width:160px', 'background:#fff', 'border-radius:10px',
                'box-shadow:0 8px 24px rgba(0,0,0,0.15)', 'overflow:hidden',
                'z-index:1000', 'font-size:14px'
            ].join(';');

            const items = [
                { label: 'My Profile', href: 'profile.php' },
                { label: 'Settings',   href: 'settings.php' },
                { label: 'Logout',     href: 'logout.php' }
            ];

            items.forEach(function (item) {
                const link = document.createElement('a');
                link.href = item.href;
                link.textContent = item.label;
                link.style.cssText = 'display:block;padding:10px 14px;color:#333;text-decoration:none;';
                link.addEventListener('mouseenter', function () { link.style.background = '#f5f5f5'; });
                link.addEventListener('mouseleave', function () { link.style.background = ''; });
                menu.appendChild(link);
            });

            profileTrigger.style.position = 'relative';
            profileTrigger.appendChild(menu);
        });

        document.addEventListener('click', function (e) {
            if (menu && !profileTrigger.contains(e.target)) {
                menu.remove();
                menu = null;
            }
        });
    }


    /* ------------------------------------------------------
       5. APPOINTMENT "VIEW" -> DETAILS MODAL
       Reads the data already rendered in the DOM for that
       appointment card, no extra request needed.
    ------------------------------------------------------ */
    document.querySelectorAll('.view-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const card = btn.closest('.appointment');
            if (!card) return;

            const title = card.querySelector('h4') ? card.querySelector('h4').textContent.trim() : '';
            const farmer = card.querySelector('.appointment-info p') ? card.querySelector('.appointment-info p').textContent.trim() : '';
            const time = card.querySelector('.time') ? card.querySelector('.time').textContent.trim() : '';
            const date = card.querySelector('.appointment-date') ? card.querySelector('.appointment-date').textContent.trim() : '';

            showAppointmentModal({ title, farmer, time, date });
        });
    });

    function showAppointmentModal(data) {
        const overlay = document.createElement('div');
        overlay.style.cssText = [
            'position:fixed', 'inset:0', 'background:rgba(0,0,0,0.5)',
            'display:flex', 'align-items:center', 'justify-content:center',
            'z-index:2000'
        ].join(';');

        const box = document.createElement('div');
        box.style.cssText = [
            'background:#fff', 'border-radius:12px', 'padding:24px',
            'width:90%', 'max-width:360px', 'font-family:inherit'
        ].join(';');

        box.innerHTML =
            '<h3 style="margin:0 0 12px;">' + escapeHtml(data.title) + '</h3>' +
            '<p style="margin:4px 0;"><strong>With:</strong> ' + escapeHtml(data.farmer) + '</p>' +
            '<p style="margin:4px 0;"><strong>When:</strong> ' + escapeHtml(data.date) + ' &middot; ' + escapeHtml(data.time) + '</p>' +
            '<button type="button" style="margin-top:16px;padding:8px 16px;border:none;border-radius:8px;background:#2e7d32;color:#fff;cursor:pointer;">Close</button>';

        box.querySelector('button').addEventListener('click', function () {
            overlay.remove();
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.remove();
        });

        overlay.appendChild(box);
        document.body.appendChild(overlay);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }


    /* ------------------------------------------------------
       6. QUICK ACTIONS + "COMPLETE PROFILE"
       Centralised here so you only have to fix URLs in one
       place once the matching pages exist.
    ------------------------------------------------------ */
    const quickActionRoutes = {
        'Write Article':    'article-editor.php',
        'Manage Schedule':  'schedule.php',
        'Open Messages':    'messages.php',
        'Edit Profile':     'profile.php'
    };

    document.querySelectorAll('.quick-action').forEach(function (link) {
        const label = link.querySelector('span') ? link.querySelector('span').textContent.trim() : '';

        link.addEventListener('click', function (e) {
            if (quickActionRoutes[label]) {
                e.preventDefault();
                window.location.href = quickActionRoutes[label];
            }
            // Unmapped labels fall through to whatever href is set in the HTML.
        });
    });

    const completeProfileBtn = document.querySelector('.profile-card .secondary-btn');
    if (completeProfileBtn) {
        completeProfileBtn.addEventListener('click', function () {
            window.location.href = 'profile.php';
        });
    }

});