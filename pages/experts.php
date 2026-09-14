<?php
// ==========================================
// SESSION + AUTH GUARD
// config.php is expected to call session_start()
// and expose a PDO connection as $pdo (same as register.php)
// ==========================================

require_once "config.php";

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'expert'
) {
    header("Location: login.php");
    exit();
}

$userId  = $_SESSION['user_id'];
$expertId = $_SESSION['expert_id'] ?? ($_SESSION['role_id'] ?? null);


// ==========================================
// SCHEMA ASSUMPTIONS
// Adjust table / column names below to match your DB.
//
//   experts    (expert_id, user_id, specialty, bio,
//                certifications, experience_years,
//                profile_photo, created_at, updated_at)
//   appointments (appointment_id, expert_id, farmer_id,
//                 farmer_name, title, appointment_date,
//                 appointment_time, status, created_at)
//   messages    (message_id, expert_id, farmer_id,
//                sender_name, message_text, is_read, created_at)
//   articles    (article_id, expert_id, title, views,
//                published_at)
//   reviews     (review_id, expert_id, rating, created_at)
// ==========================================

$expertName    = $_SESSION['fullName'] ?? 'Expert';
$expertSpecialty = 'Agricultural Expert';
$profilePhoto  = '../Assets/images/expert-profile.jpg';

$totalFarmersHelped   = 0;
$upcomingCount        = 0;
$publishedArticles    = 0;
$avgRating            = 0;
$reviewCount          = 0;
$profileCompletion    = 0;

$appointments = [];
$messages     = [];
$articles     = [];
$unreadCount  = 0;

try {

    // ---- Expert profile (specialty, photo, completion fields) ----
    $stmt = $pdo->prepare(
        "SELECT specialty, bio, certifications, experience_years, profile_photo
         FROM experts
         WHERE expert_id = ?
         LIMIT 1"
    );
    $stmt->execute([$expertId]);
    $expertProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($expertProfile) {

        if (!empty($expertProfile['specialty'])) {
            $expertSpecialty = $expertProfile['specialty'];
        }

        if (!empty($expertProfile['profile_photo'])) {
            $profilePhoto = $expertProfile['profile_photo'];
        }

        // Simple profile-completion score based on filled fields
        $fields = [
            $expertProfile['bio'],
            $expertProfile['certifications'],
            $expertProfile['experience_years'],
            $expertProfile['profile_photo']
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($field)) {
                $filled++;
            }
        }

        $profileCompletion = (int) round(($filled / count($fields)) * 100);
    }


    // ---- Stat: total distinct farmers helped (completed appointments) ----
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT farmer_id) AS total
         FROM appointments
         WHERE expert_id = ? AND status = 'completed'"
    );
    $stmt->execute([$expertId]);
    $totalFarmersHelped = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);


    // ---- Stat: upcoming appointments count ----
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE expert_id = ?
           AND appointment_date >= CURDATE()
           AND status = 'upcoming'"
    );
    $stmt->execute([$expertId]);
    $upcomingCount = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);


    // ---- Stat: published articles ----
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total FROM articles WHERE expert_id = ?"
    );
    $stmt->execute([$expertId]);
    $publishedArticles = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);


    // ---- Stat: average rating ----
    $stmt = $pdo->prepare(
        "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total
         FROM reviews
         WHERE expert_id = ?"
    );
    $stmt->execute([$expertId]);
    $ratingRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $avgRating   = $ratingRow['avg_rating'] ? round((float) $ratingRow['avg_rating'], 1) : 0;
    $reviewCount = (int) ($ratingRow['total'] ?? 0);


    // ---- Upcoming appointments list (next 3) ----
    $stmt = $pdo->prepare(
        "SELECT appointment_id, title, farmer_name, appointment_date, appointment_time
         FROM appointments
         WHERE expert_id = ?
           AND appointment_date >= CURDATE()
           AND status = 'upcoming'
         ORDER BY appointment_date ASC, appointment_time ASC
         LIMIT 3"
    );
    $stmt->execute([$expertId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ---- Recent messages (last 3) ----
    $stmt = $pdo->prepare(
        "SELECT message_id, sender_name, message_text, created_at
         FROM messages
         WHERE expert_id = ?
         ORDER BY created_at DESC
         LIMIT 3"
    );
    $stmt->execute([$expertId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ---- Unread message count (for badge + notification dot) ----
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM messages
         WHERE expert_id = ? AND is_read = 0"
    );
    $stmt->execute([$expertId]);
    $unreadCount = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);


    // ---- Recent articles (last 2) ----
    $stmt = $pdo->prepare(
        "SELECT article_id, title, views, published_at
         FROM articles
         WHERE expert_id = ?
         ORDER BY published_at DESC
         LIMIT 2"
    );
    $stmt->execute([$expertId]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Keep defaults (zeros/empty arrays) if a table/column doesn't
    // match yet — the dashboard still renders instead of crashing.
    // For debugging temporarily: error_log($e->getMessage());
}


// ---- Small helper to format "x minutes/hours/days ago" ----
function agriTechTimeAgo($datetime) {
    if (empty($datetime)) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 60)   return $diff . 's ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return floor($diff / 86400) . ' days ago';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AgriTech | Expert Dashboard</title>

    <link rel="stylesheet" href="../Assets/expert.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/index.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar" id="sidebar">

        <div class="logo">

            <div>
                <h2>Agr<span>Tech</span></h2>
                <span>Expert Portal</span>
            </div>
        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">

            <a href="#" class="nav-link active">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>

            <a href="#appointments.php" class="nav-link">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Appointments</span>
                <?php if ($upcomingCount > 0): ?>
                    <span class="badge"><?php echo $upcomingCount; ?></span>
                <?php endif; ?>
            </a>

            <a href="chats.php" class="nav-link">
                <i class="fa-solid fa-comments"></i>
                <span>Messages</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>

            <a href="#articles.php" class="nav-link">
                <i class="fa-solid fa-newspaper"></i>
                <span>My Articles</span>
            </a>


            <a href="#reviews.php" class="nav-link">
                <i class="fa-solid fa-star"></i>
                <span>Reviews</span>
            </a>

            <a href="profile.php" class="nav-link">
                <i class="fa-solid fa-user"></i>
                <span>My Profile</span>
            </a>

        </nav>


        <!-- Sidebar Bottom -->

        <div class="sidebar-bottom">

            <a href="settings.php" class="nav-link">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>

            <a href="logout.php" class="nav-link logout" id="logoutLink">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">


        <div class="exper-com">

            <!-- TOP HEADER -->

            <header class="topbar">

                <div class="mobile-menu" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </div>

                <div class="page-title">

                    <h1>Expert Dashboard</h1>

                    <p>
                        Welcome back, <?php echo htmlspecialchars($expertName); ?>.
                        Here's your professional overview.
                    </p>

                </div>


                <div class="topbar-actions">

                    <!-- Search -->

                    <div class="search-box">

                        <i class="fa-solid fa-search"></i>

                        <input type="text" id="dashboardSearch" placeholder="Search...">

                    </div>


                    <!-- Notification -->

                    <button class="icon-btn" id="notificationBtn">

                        <i class="fa-regular fa-bell"></i>

                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-dot"></span>
                        <?php endif; ?>

                    </button>


                    <!-- Profile -->

                    <div class="profile" id="profileMenuTrigger">

                        <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Expert profile">

                        <div class="profile-info">

                            <strong><?php echo htmlspecialchars($expertName); ?></strong>

                            <span><?php echo htmlspecialchars($expertSpecialty); ?></span>

                        </div>

                        <i class="fa-solid fa-chevron-down"></i>

                    </div>

                </div>

            </header>
        </div>


        <!-- =========================
             DASHBOARD CONTENT
        ========================== -->

        <section class="dashboard-content">


            <!-- WELCOME CARD -->

            <div class="welcome-card">

                <div>

                    <span class="welcome-label">
                        Expert Overview
                    </span>

                    <h2>
                        Grow Knowledge. Empower Farmers.
                    </h2>

                    <p>
                        Manage your consultations, share
                        agricultural knowledge and connect
                        with farmers across the Agro-Tech
                        community.
                    </p>

                    <button class="primary-btn">
                        <i class="fa-solid fa-plus"></i>
                        <a href="../beckend/register.php">Create New Article</a>
                    </button>

                </div>

                <div class="welcome-icon">
                    <i class="fa-solid fa-seedling"></i>
                </div>

            </div>


            <!-- STATISTICS -->

            <div class="stats-grid">


                <!-- Stat 1 -->

                <div class="stat-card">

                    <div class="stat-icon green">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div>

                        <span>Total Farmers Helped</span>

                        <h2><?php echo $totalFarmersHelped; ?></h2>

                        <small class="positive">
                            <i class="fa-solid fa-arrow-up"></i>
                            Based on completed consultations
                        </small>

                    </div>

                </div>


                <!-- Stat 2 -->

                <div class="stat-card">

                    <div class="stat-icon blue">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>

                    <div>

                        <span>Upcoming Appointments</span>

                        <h2><?php echo str_pad($upcomingCount, 2, '0', STR_PAD_LEFT); ?></h2>

                        <small>
                            <?php echo $upcomingCount > 0 ? 'Check your schedule' : 'No appointments scheduled'; ?>
                        </small>

                    </div>

                </div>


                <!-- Stat 3 -->

                <div class="stat-card">

                    <div class="stat-icon orange">
                        <i class="fa-solid fa-newspaper"></i>
                    </div>

                    <div>

                        <span>Published Articles</span>

                        <h2><?php echo $publishedArticles; ?></h2>

                        <small class="positive">
                            Total published
                        </small>

                    </div>

                </div>


                <!-- Stat 4 -->

                <div class="stat-card">

                    <div class="stat-icon purple">
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <div>

                        <span>Average Rating</span>

                        <h2><?php echo $avgRating > 0 ? $avgRating : '—'; ?></h2>

                        <small>
                            Based on <?php echo $reviewCount; ?> reviews
                        </small>

                    </div>

                </div>

            </div>


            <!-- DASHBOARD GRID -->

            <div class="dashboard-grid">


                <!-- APPOINTMENTS -->

                <div class="dashboard-card appointments-card">

                    <div class="card-header">

                        <div>

                            <h3>Upcoming Appointments</h3>

                            <p>
                                Your scheduled consultations
                            </p>

                        </div>

                        <a href="#">
                            View All
                        </a>

                    </div>


                    <div class="appointment-list" id="appointmentList">

                        <?php if (empty($appointments)): ?>

                            <p class="empty-state">No upcoming appointments.</p>

                        <?php else: ?>

                            <?php foreach ($appointments as $appt): ?>

                                <?php
                                    $apptTimestamp = strtotime($appt['appointment_date']);
                                    $day   = date('d', $apptTimestamp);
                                    $month = strtoupper(date('M', $apptTimestamp));
                                ?>

                                <div class="appointment" data-appointment-id="<?php echo (int) $appt['appointment_id']; ?>">

                                    <div class="appointment-date">

                                        <strong><?php echo htmlspecialchars($day); ?></strong>

                                        <span><?php echo htmlspecialchars($month); ?></span>

                                    </div>

                                    <div class="appointment-info">

                                        <h4><?php echo htmlspecialchars($appt['title']); ?></h4>

                                        <p>
                                            <i class="fa-regular fa-user"></i>
                                            <?php echo htmlspecialchars($appt['farmer_name']); ?>
                                        </p>

                                        <span class="time">
                                            <i class="fa-regular fa-clock"></i>
                                            <?php echo htmlspecialchars($appt['appointment_time']); ?>
                                        </span>

                                    </div>

                                    <button
                                        class="view-btn"
                                        data-appointment-id="<?php echo (int) $appt['appointment_id']; ?>"
                                    >
                                        View
                                    </button>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>


                </div>


                <!-- PROFILE COMPLETION -->

                <div class="dashboard-card profile-card">

                    <div class="card-header">

                        <div>

                            <h3>Profile Completion</h3>

                            <p>
                                Complete your professional profile
                            </p>

                        </div>

                    </div>


                    <div class="profile-progress">

                        <div class="progress-circle">

                            <span><?php echo $profileCompletion; ?>%</span>

                        </div>

                        <div>

                            <h4><?php echo $profileCompletion >= 100 ? 'All set!' : 'Almost there!'; ?></h4>

                            <p>
                                <?php echo $profileCompletion >= 100
                                    ? 'Your professional profile is complete.'
                                    : 'Add your certifications and professional experience.'; ?>
                            </p>

                        </div>

                    </div>


                    <button class="secondary-btn">
                        Complete Profile
                    </button>

                </div>


                <!-- RECENT MESSAGES -->

                <div class="dashboard-card messages-card">

                    <div class="card-header">

                        <div>

                            <h3>Recent Messages</h3>

                            <p>
                                Recent conversations
                            </p>

                        </div>

                        <a href="#">
                            View All
                        </a>

                    </div>


                    <div class="message-list">

                        <?php if (empty($messages)): ?>

                            <p class="empty-state">No messages yet.</p>

                        <?php else: ?>

                            <?php foreach ($messages as $msg): ?>

                                <div class="message" data-message-id="<?php echo (int) $msg['message_id']; ?>">

                                    <img src="../Assets/images/farmer-default.jpg" alt="Farmer">

                                    <div>

                                        <h4><?php echo htmlspecialchars($msg['sender_name']); ?></h4>

                                        <p>
                                            <?php echo htmlspecialchars($msg['message_text']); ?>
                                        </p>

                                        <span>
                                            <?php echo agriTechTimeAgo($msg['created_at']); ?>
                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>


                </div>


                <!-- QUICK ACTIONS -->

                <div class="dashboard-card quick-actions-card">

                    <div class="card-header">

                        <div>

                            <h3>Quick Actions</h3>

                            <p>
                                Manage your expert activities
                            </p>

                        </div>

                    </div>


                    <div class="quick-actions">

                        <a href="#" class="quick-action">

                            <i class="fa-solid fa-pen-to-square"></i>

                            <span>
                                Write Article
                            </span>

                        </a>


                        <a href="#" class="quick-action">

                            <i class="fa-solid fa-calendar-plus"></i>

                            <span>
                                Manage Schedule
                            </span>

                        </a>


                        <a href="#" class="quick-action">

                            <i class="fa-solid fa-comments"></i>

                            <span>
                                Open Messages
                            </span>

                        </a>


                        <a href="#" class="quick-action">

                            <i class="fa-solid fa-user-gear"></i>

                            <span>
                                Edit Profile
                            </span>

                        </a>

                    </div>

                </div>


                <!-- RECENT ARTICLES -->

                <div class="dashboard-card articles-card">

                    <div class="card-header">

                        <div>

                            <h3>My Recent Articles</h3>

                            <p>
                                Your latest published content
                            </p>

                        </div>

                        <a href="#">
                            View All
                        </a>

                    </div>


                    <div class="article-list">

                        <?php if (empty($articles)): ?>

                            <p class="empty-state">You haven't published any articles yet.</p>

                        <?php else: ?>

                            <?php foreach ($articles as $article): ?>

                                <div class="article" data-article-id="<?php echo (int) $article['article_id']; ?>">

                                    <div class="article-icon">
                                        <i class="fa-solid fa-leaf"></i>
                                    </div>

                                    <div>

                                        <h4>
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </h4>

                                        <p>
                                            Published <?php echo agriTechTimeAgo($article['published_at']); ?>
                                        </p>

                                    </div>

                                    <span class="views">
                                        <?php echo number_format((int) $article['views']); ?> views
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>


                </div>

            </div>

        </section>

        </div>
</main>

<script src="../scripts/expert.js"></script>

</body>

</html>