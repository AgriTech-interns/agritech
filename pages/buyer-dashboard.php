<?php
 //--- Auth guard: block access unless the user is logged in as a buyer ---
 session_start();

 // Adjust these session keys to match whatever your login script actually sets.
  if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    header('Location: login.php');
    exit;
 }
?> 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriTech | Buyer Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../Assets/buyer-dashboard.css">
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo"><i class="fa-solid fa-leaf"></i> <span>Agri<b>Tech</b></span></div>

        <ul class="nav-list">
            <li class="active" data-target="overview"><i class="fa-solid fa-house"></i> Overview</li>
            <li data-target="pending"><i class="fa-solid fa-truck-fast"></i> Pending Orders</li>
            <li data-target="history"><i class="fa-solid fa-clock-rotate-left"></i> Order History</li>
            <li><i class="fa-solid fa-store"></i> <a href="marketplace.html">Marketplace</a></li>
            <li><i class="fa-solid fa-envelope"></i> <a href="chats.php">Messages</a></li>
            <li><i class="fa-solid fa-gear"></i><a href="settings.php"> Settings</a></li>
        </ul>

        <div class="sidebar-profile">
            <img id="buyer-avatar" src="./images/Birds.jpg" alt="">
            <div>
                <b id="buyer-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Buyer'); ?></b>
                <small>Buyer</small>
            </div>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <header class="topbar">
            <div>
                <h1>My Orders</h1>
                <p>Track what you've bought and what's still on the way.</p>
            </div>
            <div class="topbar-right">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="order-search" placeholder="Search by product or order ID…">
                </div>
                <button id="refresh-btn" class="icon-btn" title="Refresh">
                    <i class="fa-solid fa-rotate"></i>
                </button>
            </div>
        </header>

        <p id="status-msg" class="status-msg" role="status"></p>

        <!-- STAT CARDS -->
        <section class="stats" id="stats">
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fa-solid fa-bag-shopping"></i></div>
                <div><h4>Total Orders</h4><h2 id="stat-total">0</h2></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fa-solid fa-truck"></i></div>
                <div><h4>Pending Delivery</h4><h2 id="stat-pending">0</h2></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fa-solid fa-box-open"></i></div>
                <div><h4>Delivered</h4><h2 id="stat-delivered">0</h2></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fa-solid fa-coins"></i></div>
                <div><h4>Total Spent</h4><h2 id="stat-spent">0 CFA</h2></div>
            </div>
        </section>

        <!-- PENDING ORDERS -->
        <section class="panel" id="pending">
            <div class="panel-header">
                <h3>Pending Orders</h3>
                <span class="live-dot" title="Auto-updating"></span>
            </div>
            <div id="pending-list" class="pending-list">
                <p class="empty-msg">Loading pending orders…</p>
            </div>
        </section>

        <!-- ORDER HISTORY -->
        <section class="panel" id="history">
            <div class="panel-header">
                <h3>Order History</h3>
            </div>
            <div class="table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="history-body">
                        <tr><td colspan="7" class="empty-msg">Loading order history…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>

<!-- TRACKING MODAL -->
<div id="track-modal" class="modal-overlay hidden">
    <div class="modal">
        <button id="track-close" class="modal-close" aria-label="Close">&times;</button>

        <div class="modal-header">
            <h3 id="track-title">Tracking order</h3>
            <p id="track-subtitle">—</p>
        </div>

        <div id="track-timeline" class="timeline"></div>

        <div class="modal-footer">
            <span id="track-updated">—</span>
            <span class="live-dot" title="Auto-updating"></span>
        </div>
    </div>
</div>

<script src="../scripts/buyer-dashboard.js"></script>
</body>
</html>