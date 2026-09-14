/* ===================================================================
   AgriTech — Admin Dashboard
   All data below is mock/sample data. Swap loadUsers()/loadOrders()/
   loadApprovals()/loadRevenue() for real fetch() calls to your PHP
   endpoints when wiring this up to a backend.
   =================================================================== */

(function () {

    /* ---------------------------------------------------------------
       MOCK DATA
       --------------------------------------------------------------- */

    const users = [
        { id: 1, name: "Jean Paul",     email: "jeanpaul@agritech.cm",   role: "farmer", joined: "2026-02-14", status: "active" },
        { id: 2, name: "Aminatou Njoya",email: "aminatou@agritech.cm",   role: "buyer",  joined: "2026-03-02", status: "active" },
        { id: 3, name: "Dr. Fon Elvis", email: "fon.elvis@agritech.cm",  role: "expert", joined: "2026-03-19", status: "active" },
        { id: 4, name: "Sarah Mbeki",   email: "sarah.m@agritech.cm",    role: "farmer", joined: "2026-04-05", status: "inactive" },
        { id: 5, name: "Peter Achu",    email: "peter.achu@agritech.cm", role: "buyer",  joined: "2026-05-11", status: "active" },
        { id: 6, name: "Grace Tabi",    email: "grace.tabi@agritech.cm", role: "expert", joined: "2026-06-01", status: "active" },
        { id: 7, name: "Moussa Bello",  email: "moussa.b@agritech.cm",   role: "farmer", joined: "2026-06-20", status: "active" },
        { id: 8, name: "Linda Ekema",   email: "linda.e@agritech.cm",    role: "buyer",  joined: "2026-07-08", status: "inactive" },
    ];

    const orders = [
        { id: 1042, buyer: "Aminatou Njoya", product: "Maize (50kg bags)", total: 125000, status: "delivered" },
        { id: 1043, buyer: "Peter Achu",     product: "Cassava tubers",    total: 68000,  status: "shipped" },
        { id: 1044, buyer: "Linda Ekema",    product: "Tomato crates",     total: 42000,  status: "pending" },
        { id: 1045, buyer: "Aminatou Njoya", product: "Pineapples",        total: 30000,  status: "pending" },
        { id: 1046, buyer: "Peter Achu",     product: "Cabbage (bulk)",    total: 21500,  status: "delivered" },
        { id: 1047, buyer: "Linda Ekema",    product: "NPK Fertilizer",    total: 55000,  status: "shipped" },
    ];

    const approvals = [
        { id: 1, title: "Dr. Fon Elvis — Expert verification", detail: "Submitted agronomy certification for review.", type: "expert" },
        { id: 2, title: "Moussa Bello — New product listing", detail: '"Organic Cassava" pending catalog approval.', type: "listing" },
        { id: 3, title: "Grace Tabi — Expert verification", detail: "Submitted soil-science credentials for review.", type: "expert" },
    ];

    const revenueByMonth = [
        { label: "Feb", value: 420000 },
        { label: "Mar", value: 510000 },
        { label: "Apr", value: 380000 },
        { label: "May", value: 610000 },
        { label: "Jun", value: 705000 },
        { label: "Jul", value: 640000 },
    ];

    const activity = [
        { icon: "fa-user-plus", color: "green", text: "Moussa Bello registered as a farmer", time: "2h ago" },
        { icon: "fa-box", color: "blue", text: "Order #1046 marked delivered", time: "4h ago" },
        { icon: "fa-clipboard-check", color: "amber", text: "New expert verification submitted", time: "6h ago" },
        { icon: "fa-triangle-exclamation", color: "red", text: "Order #1044 flagged: payment delayed", time: "1d ago" },
    ];

    const ROLE_COLORS = { farmer: "#22c55e", buyer: "#2563eb", expert: "#d97706" };

    /* ---------------------------------------------------------------
       ELEMENTS
       --------------------------------------------------------------- */

    const sidebar = document.getElementById("sidebar");
    const sidebarClose = document.getElementById("sidebar-close");
    const sidebarOpen = document.getElementById("sidebar-open");
    const navItems = document.querySelectorAll(".nav-item");
    const sections = document.querySelectorAll(".section");
    const pageTitle = document.getElementById("page-title");
    const pageSubtitle = document.getElementById("page-subtitle");
    const globalSearch = document.getElementById("global-search");

    const SECTION_META = {
        overview:  { title: "Overview",  subtitle: "Platform activity at a glance." },
        users:     { title: "Users",     subtitle: "Manage farmers, buyers, and experts." },
        orders:    { title: "Orders",    subtitle: "Track every order across the platform." },
        approvals: { title: "Approvals", subtitle: "Review pending verifications and listings." },
        settings:  { title: "Settings",  subtitle: "Platform-wide configuration." },
    };

    let activeSection = "overview";
    let userRoleFilter = "all";
    let orderStatusFilter = "all";

    /* ---------------------------------------------------------------
       SIDEBAR TOGGLE
       --------------------------------------------------------------- */

    function closeSidebar() {
        sidebar.classList.add("sidebar-hidden");
        sidebarOpen.classList.add("show");
    }

    function openSidebar() {
        sidebar.classList.remove("sidebar-hidden");
        sidebarOpen.classList.remove("show");
    }

    sidebarClose.addEventListener("click", closeSidebar);
    sidebarOpen.addEventListener("click", openSidebar);

    /* ---------------------------------------------------------------
       SECTION SWITCHING
       --------------------------------------------------------------- */

    navItems.forEach((item) => {
        item.addEventListener("click", () => {
            const target = item.dataset.section;
            activeSection = target;

            navItems.forEach((i) => i.classList.remove("active"));
            item.classList.add("active");

            sections.forEach((s) => s.classList.remove("active"));
            document.getElementById(`section-${target}`).classList.add("active");

            pageTitle.textContent = SECTION_META[target].title;
            pageSubtitle.textContent = SECTION_META[target].subtitle;

            globalSearch.value = "";
            renderActiveSection();

            // on small screens, picking a page closes the sidebar automatically
            if (window.innerWidth <= 720) closeSidebar();
        });
    });

    /* ---------------------------------------------------------------
       STATS
       --------------------------------------------------------------- */

    function renderStats() {
        const totalRevenue = orders.reduce((sum, o) => sum + o.total, 0);
        document.getElementById("stat-users").textContent = users.length;
        document.getElementById("stat-orders").textContent = orders.length;
        document.getElementById("stat-revenue").textContent = "CFA " + totalRevenue.toLocaleString();
        document.getElementById("stat-approvals").textContent = approvals.length;
        document.getElementById("approvals-badge").textContent = approvals.length;
    }

    /* ---------------------------------------------------------------
       CHARTS (hand-drawn SVG — no chart library)
       --------------------------------------------------------------- */

    function renderRevenueChart() {
        const svg = document.getElementById("revenue-chart");
        const width = 480, height = 220, padding = 30;
        const max = Math.max(...revenueByMonth.map((d) => d.value));
        const barWidth = (width - padding * 2) / revenueByMonth.length - 14;

        let bars = "";
        revenueByMonth.forEach((d, i) => {
            const barHeight = (d.value / max) * (height - padding * 2);
            const x = padding + i * ((width - padding * 2) / revenueByMonth.length) + 7;
            const y = height - padding - barHeight;
            bars += `<rect class="bar" x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="4"><title>${d.label}: CFA ${d.value.toLocaleString()}</title></rect>`;
            bars += `<text class="axis-label" x="${x + barWidth / 2}" y="${height - padding + 16}" text-anchor="middle">${d.label}</text>`;
        });

        svg.innerHTML = bars;
    }

    function renderRoleDonut() {
        const svg = document.getElementById("role-donut");
        const legend = document.getElementById("role-legend");
        const counts = { farmer: 0, buyer: 0, expert: 0 };
        users.forEach((u) => counts[u.role]++);

        const total = users.length;
        const radius = 80, cx = 100, cy = 100, strokeWidth = 26;
        const circumference = 2 * Math.PI * radius;
        let offset = 0;
        let circles = "";

        Object.keys(counts).forEach((role) => {
            const value = counts[role];
            const fraction = value / total;
            const dash = fraction * circumference;
            circles += `<circle cx="${cx}" cy="${cy}" r="${radius}" fill="none" stroke="${ROLE_COLORS[role]}"
                stroke-width="${strokeWidth}" stroke-dasharray="${dash} ${circumference - dash}"
                stroke-dashoffset="${-offset}" transform="rotate(-90 ${cx} ${cy})"><title>${role}: ${value}</title></circle>`;
            offset += dash;
        });

        svg.innerHTML = circles + `<text x="${cx}" y="${cy + 6}" text-anchor="middle" font-family="Fraunces, serif" font-weight="700" font-size="26">${total}</text>`;

        legend.innerHTML = Object.keys(counts).map((role) => `
            <li>
                <span class="legend-swatch" style="background:${ROLE_COLORS[role]}"></span>
                <span>${role.charAt(0).toUpperCase() + role.slice(1)}s</span>
                <span class="count">${counts[role]}</span>
            </li>
        `).join("");
    }

    /* ---------------------------------------------------------------
       ACTIVITY FEED
       --------------------------------------------------------------- */

    const ACTIVITY_COLORS = { green: ["#e7f6ec", "#14532d"], blue: ["#e8eefd", "#2563eb"], amber: ["#fdf1e0", "#d97706"], red: ["#fde8e8", "#dc2626"] };

    function renderActivity() {
        const list = document.getElementById("activity-list");
        list.innerHTML = activity.map((a) => {
            const [bg, fg] = ACTIVITY_COLORS[a.color];
            return `
                <li>
                    <span class="activity-icon" style="background:${bg};color:${fg}"><i class="fa-solid ${a.icon}"></i></span>
                    <span>${a.text}<br><span class="activity-time">${a.time}</span></span>
                </li>
            `;
        }).join("");
    }

    /* ---------------------------------------------------------------
       USERS TABLE
       --------------------------------------------------------------- */

    function initials(name) {
        return name.split(" ").map((p) => p[0]).slice(0, 2).join("").toUpperCase();
    }

    function renderUsers() {
        const body = document.getElementById("users-table-body");
        const empty = document.getElementById("users-empty");
        const term = globalSearch.value.trim().toLowerCase();

        const filtered = users.filter((u) => {
            const matchesRole = userRoleFilter === "all" || u.role === userRoleFilter;
            const matchesSearch = term === "" || u.name.toLowerCase().includes(term) || u.email.toLowerCase().includes(term);
            return matchesRole && matchesSearch;
        });

        body.innerHTML = filtered.map((u) => `
            <tr>
                <td>
                    <div class="user-cell">
                        <span class="avatar-dot" style="background:${ROLE_COLORS[u.role]}">${initials(u.name)}</span>
                        ${u.name}
                    </div>
                </td>
                <td>${u.email}</td>
                <td><span class="badge badge-${u.role}">${u.role}</span></td>
                <td>${new Date(u.joined).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })}</td>
                <td><span class="badge badge-${u.status}">${u.status}</span></td>
                <td><button class="row-action" data-user-id="${u.id}" aria-label="Remove user"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
        `).join("");

        empty.classList.toggle("hidden", filtered.length !== 0);

        body.querySelectorAll(".row-action").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = Number(btn.dataset.userId);
                const index = users.findIndex((u) => u.id === id);
                if (index !== -1) {
                    users.splice(index, 1);
                    renderUsers();
                    renderStats();
                    renderRoleDonut();
                }
            });
        });
    }

    document.querySelectorAll("#user-filter-tabs .filter-tab").forEach((tab) => {
        tab.addEventListener("click", () => {
            document.querySelectorAll("#user-filter-tabs .filter-tab").forEach((t) => t.classList.remove("active"));
            tab.classList.add("active");
            userRoleFilter = tab.dataset.role;
            renderUsers();
        });
    });

    /* ---------------------------------------------------------------
       ORDERS TABLE
       --------------------------------------------------------------- */

    function renderOrders() {
        const body = document.getElementById("orders-table-body");
        const empty = document.getElementById("orders-empty");
        const term = globalSearch.value.trim().toLowerCase();

        const filtered = orders.filter((o) => {
            const matchesStatus = orderStatusFilter === "all" || o.status === orderStatusFilter;
            const matchesSearch = term === "" ||
                o.buyer.toLowerCase().includes(term) ||
                o.product.toLowerCase().includes(term) ||
                String(o.id).includes(term);
            return matchesStatus && matchesSearch;
        });

        body.innerHTML = filtered.map((o) => `
            <tr>
                <td>#${o.id}</td>
                <td>${o.buyer}</td>
                <td>${o.product}</td>
                <td>CFA ${o.total.toLocaleString()}</td>
                <td><span class="badge badge-${o.status}">${o.status.replace("_", " ")}</span></td>
            </tr>
        `).join("");

        empty.classList.toggle("hidden", filtered.length !== 0);
    }

    document.querySelectorAll("#order-filter-tabs .filter-tab").forEach((tab) => {
        tab.addEventListener("click", () => {
            document.querySelectorAll("#order-filter-tabs .filter-tab").forEach((t) => t.classList.remove("active"));
            tab.classList.add("active");
            orderStatusFilter = tab.dataset.status;
            renderOrders();
        });
    });

    /* ---------------------------------------------------------------
       APPROVALS
       --------------------------------------------------------------- */

    function renderApprovals() {
        const list = document.getElementById("approvals-list");
        const empty = document.getElementById("approvals-empty");

        list.innerHTML = approvals.map((a) => `
            <div class="approval-card" data-approval-id="${a.id}">
                <div class="approval-info">
                    <h4>${a.title}</h4>
                    <p>${a.detail}</p>
                </div>
                <div class="approval-actions">
                    <button class="btn btn-approve" data-action="approve" data-id="${a.id}">Approve</button>
                    <button class="btn btn-reject" data-action="reject" data-id="${a.id}">Reject</button>
                </div>
            </div>
        `).join("");

        empty.classList.toggle("hidden", approvals.length !== 0);

        list.querySelectorAll("button[data-action]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = Number(btn.dataset.id);
                const index = approvals.findIndex((a) => a.id === id);
                if (index !== -1) {
                    approvals.splice(index, 1);
                    renderApprovals();
                    renderStats();
                }
            });
        });
    }

    /* ---------------------------------------------------------------
       SEARCH (scoped to whichever section is active)
       --------------------------------------------------------------- */

    globalSearch.addEventListener("input", () => {
        if (activeSection === "users") renderUsers();
        if (activeSection === "orders") renderOrders();
    });

    /* ---------------------------------------------------------------
       INIT
       --------------------------------------------------------------- */

    function renderActiveSection() {
        if (activeSection === "overview") {
            renderStats();
            renderRevenueChart();
            renderRoleDonut();
            renderActivity();
        } else if (activeSection === "users") {
            renderUsers();
        } else if (activeSection === "orders") {
            renderOrders();
        } else if (activeSection === "approvals") {
            renderApprovals();
        }
    }

    renderStats();
    renderRevenueChart();
    renderRoleDonut();
    renderActivity();
    renderUsers();
    renderOrders();
    renderApprovals();

})();
