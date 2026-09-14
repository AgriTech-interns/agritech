/* ===================================================================
   AgriTech — Buyer Dashboard (front-end only)
   Mock data lives in `orders` below. Replace loadOrders() with a
   fetch() to your PHP endpoint when you wire this up to a backend —
   everything else (render, filter, search, modal) works unchanged.
   =================================================================== */

(function () {

    /* ---------------------------------------------------------------
       MOCK DATA
       --------------------------------------------------------------- */

    const STEPS = ["placed", "processing", "shipped", "out_for_delivery", "delivered"];
    const STEP_LABELS = {
        placed: "Order Placed",
        processing: "Processing",
        shipped: "Shipped",
        out_for_delivery: "Out for Delivery",
        delivered: "Delivered",
    };

    let orders = [
        { id: 1042, product: "Maize (50kg bags)", qty: 3, total: 125000, status: "delivered", date: "2026-06-14" },
        { id: 1043, product: "Cassava tubers",     qty: 5, total: 68000,  status: "shipped",   date: "2026-07-20" },
        { id: 1044, product: "Tomato crates",      qty: 2, total: 42000,  status: "pending",   date: "2026-07-28" },
        { id: 1045, product: "Pineapples",         qty: 4, total: 30000,  status: "processing",date: "2026-07-30" },
        { id: 1046, product: "Cabbage (bulk)",     qty: 6, total: 21500,  status: "delivered", date: "2026-06-02" },
        { id: 1047, product: "NPK Fertilizer",     qty: 1, total: 55000,  status: "out_for_delivery", date: "2026-08-01" },
    ];

    /* ---------------------------------------------------------------
       ELEMENTS
       --------------------------------------------------------------- */

    const navItems = document.querySelectorAll(".nav-list li[data-target]");
    const pendingPanel = document.getElementById("pending");
    const historyPanel = document.getElementById("history");
    const searchInput = document.getElementById("order-search");
    const refreshBtn = document.getElementById("refresh-btn");
    const statusMsg = document.getElementById("status-msg");

    const pendingList = document.getElementById("pending-list");
    const historyBody = document.getElementById("history-body");

    const statTotal = document.getElementById("stat-total");
    const statPending = document.getElementById("stat-pending");
    const statDelivered = document.getElementById("stat-delivered");
    const statSpent = document.getElementById("stat-spent");

    const trackModal = document.getElementById("track-modal");
    const trackClose = document.getElementById("track-close");
    const trackTitle = document.getElementById("track-title");
    const trackSubtitle = document.getElementById("track-subtitle");
    const trackTimeline = document.getElementById("track-timeline");
    const trackUpdated = document.getElementById("track-updated");

    let trackedOrderId = null;
    let trackInterval = null;

    /* ---------------------------------------------------------------
       SIDEBAR NAV — overview shows everything, the other two filter
       which panel is visible
       --------------------------------------------------------------- */

    navItems.forEach((item) => {
        item.addEventListener("click", () => {
            navItems.forEach((i) => i.classList.remove("active"));
            item.classList.add("active");

            const target = item.dataset.target;
            pendingPanel.classList.toggle("section-hidden", target === "history");
            historyPanel.classList.toggle("section-hidden", target === "pending");
        });
    });

    /* ---------------------------------------------------------------
       STATS
       --------------------------------------------------------------- */

    function renderStats() {
        const pendingCount = orders.filter((o) => o.status !== "delivered").length;
        const deliveredCount = orders.filter((o) => o.status === "delivered").length;
        const totalSpent = orders.reduce((sum, o) => sum + o.total, 0);

        statTotal.textContent = orders.length;
        statPending.textContent = pendingCount;
        statDelivered.textContent = deliveredCount;
        statSpent.textContent = "CFA " + totalSpent.toLocaleString();
    }

    /* ---------------------------------------------------------------
       PENDING ORDERS
       --------------------------------------------------------------- */

    function stepIndex(status) {
        const i = STEPS.indexOf(status);
        return i === -1 ? 0 : i;
    }

    function renderPending(term) {
        const pending = orders.filter((o) => o.status !== "delivered").filter((o) => matchesSearch(o, term));

        if (pending.length === 0) {
            pendingList.innerHTML = '<p class="empty-msg">No pending orders match your search.</p>';
            return;
        }

        pendingList.innerHTML = pending.map((o) => {
            const idx = stepIndex(o.status);
            const percent = Math.round((idx / (STEPS.length - 1)) * 100);

            return `
                <article class="order-card" data-order-id="${o.id}">
                    <div class="order-card-top">
                        <div>
                            <h4>${o.product}</h4>
                            <p class="order-meta">Order #${o.id} · Qty ${o.qty} · Placed ${formatDate(o.date)}</p>
                        </div>
                        <div class="order-price">CFA ${o.total.toLocaleString()}</div>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width:${percent}%"></div>
                    </div>
                    <div class="progress-caption">
                        <span>Status: <b>${STEP_LABELS[o.status]}</b></span>
                        <button class="track-link" data-order-id="${o.id}">View tracking →</button>
                    </div>
                </article>
            `;
        }).join("");

        pendingList.querySelectorAll("[data-order-id]").forEach((el) => {
            el.addEventListener("click", () => openTracking(Number(el.dataset.orderId)));
        });
    }

    /* ---------------------------------------------------------------
       ORDER HISTORY
       --------------------------------------------------------------- */

    function renderHistory(term) {
        const filtered = orders.filter((o) => matchesSearch(o, term));

        if (filtered.length === 0) {
            historyBody.innerHTML = '<tr><td colspan="7" class="empty-msg">No orders match your search.</td></tr>';
            return;
        }

        historyBody.innerHTML = filtered.map((o) => `
            <tr>
                <td>#${o.id}</td>
                <td>${o.product}</td>
                <td>${o.qty}</td>
                <td>CFA ${o.total.toLocaleString()}</td>
                <td>${formatDate(o.date)}</td>
                <td><span class="badge badge-${o.status}">${STEP_LABELS[o.status]}</span></td>
                <td>${o.status !== "delivered" ? `<button class="track-link" data-order-id="${o.id}">Track</button>` : ""}</td>
            </tr>
        `).join("");

        historyBody.querySelectorAll("[data-order-id]").forEach((el) => {
            el.addEventListener("click", () => openTracking(Number(el.dataset.orderId)));
        });
    }

    /* ---------------------------------------------------------------
       TRACKING MODAL
       --------------------------------------------------------------- */

    function openTracking(orderId) {
        const order = orders.find((o) => o.id === orderId);
        if (!order) return;

        trackedOrderId = orderId;
        trackTitle.textContent = `Tracking Order #${order.id}`;
        trackSubtitle.textContent = `${order.product} · Qty ${order.qty}`;

        renderTimeline(order);
        trackUpdated.textContent = "Updated " + new Date().toLocaleTimeString();

        trackModal.classList.remove("hidden");

        // simulate real-time updates while the modal is open
        clearInterval(trackInterval);
        trackInterval = setInterval(() => {
            trackUpdated.textContent = "Updated " + new Date().toLocaleTimeString();
        }, 10000);
    }

    function closeTracking() {
        trackModal.classList.add("hidden");
        trackedOrderId = null;
        clearInterval(trackInterval);
    }

    function renderTimeline(order) {
        const idx = stepIndex(order.status);

        trackTimeline.innerHTML = STEPS.map((step, i) => {
            const state = i < idx ? "done" : i === idx ? "current" : "upcoming";
            const icon = state === "done" ? '<i class="fa-solid fa-check"></i>' : "";
            return `
                <div class="timeline-step timeline-step--${state}">
                    <span class="timeline-dot">${icon}</span>
                    <div>
                        <span class="timeline-label">${STEP_LABELS[step]}</span>
                        ${state !== "upcoming" ? `<span class="timeline-time">${state === "current" ? "In progress" : formatDate(order.date)}</span>` : ""}
                    </div>
                </div>
            `;
        }).join("");
    }

    trackClose.addEventListener("click", closeTracking);
    trackModal.addEventListener("click", (e) => {
        if (e.target === trackModal) closeTracking();
    });

    /* ---------------------------------------------------------------
       SEARCH
       --------------------------------------------------------------- */

    function matchesSearch(order, term) {
        if (!term) return true;
        return order.product.toLowerCase().includes(term) || String(order.id).includes(term);
    }

    searchInput.addEventListener("input", () => {
        const term = searchInput.value.trim().toLowerCase();
        renderPending(term);
        renderHistory(term);
    });

    /* ---------------------------------------------------------------
       REFRESH
       --------------------------------------------------------------- */

    refreshBtn.addEventListener("click", () => {
        refreshBtn.classList.add("spinning");
        setStatus("Refreshing…");

        setTimeout(() => {
            renderAll();
            refreshBtn.classList.remove("spinning");
            setStatus("Up to date · " + new Date().toLocaleTimeString());
        }, 500);
    });

    function setStatus(message, isError) {
        statusMsg.textContent = message;
        statusMsg.classList.toggle("error", !!isError);
    }

    /* ---------------------------------------------------------------
       HELPERS
       --------------------------------------------------------------- */

    function formatDate(iso) {
        return new Date(iso).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
    }

    /* ---------------------------------------------------------------
       INIT
       --------------------------------------------------------------- */

    function renderAll() {
        const term = searchInput.value.trim().toLowerCase();
        renderStats();
        renderPending(term);
        renderHistory(term);
    }

    renderAll();
    setStatus("Up to date · " + new Date().toLocaleTimeString());

})();
