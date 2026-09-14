<?php
session_start();

/*
|--------------------------------------------------------------------------
| Agriculture Platform - Profile & Settings
|--------------------------------------------------------------------------
| This file is intentionally self-contained.
| You can later connect the values to your existing database.
|--------------------------------------------------------------------------
*/

$user = [
    'name' => $_SESSION['name'] ?? 'Agriculture User',
    'phone' => $_SESSION['phone'] ?? '+237 6XX XXX XXX',
    'about' => $_SESSION['about'] ?? 'Available for farming and agricultural discussions.',
    'role' => $_SESSION['role'] ?? 'Farmer'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $about = trim($_POST['about'] ?? '');

        if ($name !== '') {
            $_SESSION['name'] = $name;
            $user['name'] = $name;
        }

        $_SESSION['about'] = $about;
        $user['about'] = $about;

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
        exit;
    }

    if ($action === 'logout') {
        session_unset();
        session_destroy();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profile & Settings | AgriTech</title>

    <link rel="stylesheet" href="../Assets/profile.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="profile-app">

    <!-- Mobile Header -->
    <header class="mobile-header">
        <button class="back-btn" id="mobileBack">
            <i class="fa-solid fa-arrow-left"></i>
        </button>

        <h1>Profile & Settings</h1>
    </header>


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="settings-sidebar">

        <div class="sidebar-header">

            <button class="back-btn" id="backBtn">
                <i class="fa-solid fa-arrow-left"></i>
            </button>

            <h2>Settings</h2>

        </div>


        <!-- User Profile Preview -->

        <div class="profile-preview">

            <div class="profile-avatar">
                <span id="sidebarAvatar">
                    <?= strtoupper(substr($user['name'], 0, 1)); ?>
                </span>

                <button class="camera-btn" id="changePhotoBtn">
                    <i class="fa-solid fa-camera"></i>
                </button>
            </div>

            <div class="profile-preview-info">

                <h3 id="sidebarName">
                    <?= htmlspecialchars($user['name']); ?>
                </h3>

                <p id="sidebarAbout">
                    <?= htmlspecialchars($user['about']); ?>
                </p>

            </div>

        </div>


        <!-- Search -->

        <div class="settings-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                id="settingsSearch"
                placeholder="Search settings"
            >

        </div>


        <!-- Navigation -->

        <nav class="settings-nav">

            <button class="setting-nav-item active"
                    data-section="account">

                <i class="fa-solid fa-user"></i>

                <span>Account</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="privacy">

                <i class="fa-solid fa-lock"></i>

                <span>Privacy</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="security">

                <i class="fa-solid fa-shield-halved"></i>

                <span>Security</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="two-step">

                <i class="fa-solid fa-key"></i>

                <span>Two-step verification</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="notifications">

                <i class="fa-solid fa-bell"></i>

                <span>Notifications</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="chats">

                <i class="fa-solid fa-comments"></i>

                <span>Chats</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="storage">

                <i class="fa-solid fa-database"></i>

                <span>Storage and data</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="language">

                <i class="fa-solid fa-language"></i>

                <span>App language</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>


            <button class="setting-nav-item"
                    data-section="help">

                <i class="fa-solid fa-circle-question"></i>

                <span>Help</span>

                <i class="fa-solid fa-chevron-right"></i>

            </button>

        </nav>


        <!-- Bottom -->

        <div class="sidebar-bottom">

            <button class="danger-nav" id="logoutBtn">

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Log out</span>

            </button>

            <small>AgriTech</small>

        </div>

    </aside>


    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <main class="settings-content">


        <!-- =====================================================
             ACCOUNT
        ====================================================== -->

        <section class="settings-section active"
                 id="account">

            <div class="section-heading">

                <div>
                    <span class="section-label">PROFILE</span>
                    <h2>Account</h2>
                    <p>Manage your agricultural community profile.</p>
                </div>

            </div>


            <!-- Profile Card -->

            <div class="large-profile-card">

                <div class="large-avatar">

                    <span id="mainAvatar">
                        <?= strtoupper(substr($user['name'], 0, 1)); ?>
                    </span>

                    <button id="mainPhotoBtn">
                        <i class="fa-solid fa-camera"></i>
                    </button>

                </div>

                <div class="large-profile-info">

                    <h3 id="mainName">
                        <?= htmlspecialchars($user['name']); ?>
                    </h3>

                    <p>
                        <i class="fa-solid fa-phone"></i>

                        <?= htmlspecialchars($user['phone']); ?>
                    </p>

                    <span class="role-badge">
                        <i class="fa-solid fa-seedling"></i>
                        <?= htmlspecialchars($user['role']); ?>
                    </span>

                </div>

            </div>


            <!-- Personal Information -->

            <div class="settings-card">

                <div class="card-title">

                    <div class="title-icon">
                        <i class="fa-solid fa-id-card"></i>
                    </div>

                    <div>
                        <h3>Personal information</h3>
                        <p>Update the information people see on your profile.</p>
                    </div>

                </div>


                <form id="profileForm">

                    <input type="hidden"
                           name="action"
                           value="update_profile">


                    <div class="form-group">

                        <label for="profileName">
                            Name
                        </label>

                        <div class="input-with-icon">

                            <i class="fa-solid fa-user"></i>

                            <input
                                type="text"
                                id="profileName"
                                name="name"
                                value="<?= htmlspecialchars($user['name']); ?>"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="profilePhone">
                            Phone number
                        </label>

                        <div class="input-with-icon">

                            <i class="fa-solid fa-phone"></i>

                            <input
                                type="text"
                                id="profilePhone"
                                value="<?= htmlspecialchars($user['phone']); ?>"
                                readonly
                            >

                        </div>

                        <small>
                            Your phone number is connected to your account.
                        </small>

                    </div>


                    <div class="form-group">

                        <label for="profileAbout">
                            About
                        </label>

                        <textarea
                            id="profileAbout"
                            name="about"
                            maxlength="140"
                        ><?= htmlspecialchars($user['about']); ?></textarea>

                        <div class="character-count">
                            <span id="aboutCount">0</span>/140
                        </div>

                    </div>


                    <button type="submit"
                            class="primary-btn">

                        <i class="fa-solid fa-check"></i>

                        Save changes

                    </button>

                </form>

            </div>


            <!-- Invite -->

            <div class="settings-card clickable-card"
                 data-action="invite">

                <div class="card-icon green">
                    <i class="fa-solid fa-user-plus"></i>
                </div>

                <div class="card-content">

                    <h3>Invite a farmer</h3>

                    <p>
                        Invite farmers, buyers and agricultural experts
                        to join your community.
                    </p>

                </div>

                <i class="fa-solid fa-chevron-right arrow"></i>

            </div>

        </section>


        <!-- =====================================================
             PRIVACY
        ====================================================== -->

        <section class="settings-section"
                 id="privacy">

            <div class="section-heading">

                <span class="section-label">PRIVACY</span>

                <h2>Privacy</h2>

                <p>
                    Control who can see your information and contact you.
                </p>

            </div>


            <div class="settings-card">

                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>

                    <div class="row-info">

                        <h3>Last seen</h3>

                        <p>Who can see when you were last active.</p>

                    </div>

                    <select class="setting-select"
                            data-setting="lastSeen">

                        <option>Everyone</option>
                        <option>My contacts</option>
                        <option>Nobody</option>

                    </select>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>

                    <div class="row-info">

                        <h3>About</h3>

                        <p>Who can see your profile description.</p>

                    </div>

                    <select class="setting-select"
                            data-setting="aboutPrivacy">

                        <option>Everyone</option>
                        <option>My contacts</option>
                        <option>Nobody</option>

                    </select>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-image"></i>
                    </div>

                    <div class="row-info">

                        <h3>Profile photo</h3>

                        <p>Control who can see your profile photo.</p>

                    </div>

                    <select class="setting-select"
                            data-setting="profilePhoto">

                        <option>Everyone</option>
                        <option>My contacts</option>
                        <option>Nobody</option>

                    </select>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div class="row-info">

                        <h3>Groups</h3>

                        <p>Choose who can add you to groups.</p>

                    </div>

                    <select class="setting-select"
                            data-setting="groups">

                        <option>Everyone</option>
                        <option>My contacts</option>
                        <option>My contacts except...</option>

                    </select>

                </div>

            </div>


            <div class="settings-card">

                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-user-secret"></i>
                    </div>

                    <div class="row-info">

                        <h3>Read receipts</h3>

                        <p>
                            Allow people to know when you have read
                            their messages.
                        </p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="readReceipts"
                            checked
                        >

                        <span></span>

                    </label>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>

                    <div class="row-info">

                        <h3>Online status</h3>

                        <p>Show when you are currently online.</p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="onlineStatus"
                            checked
                        >

                        <span></span>

                    </label>

                </div>

            </div>

        </section>


        <!-- =====================================================
             SECURITY
        ====================================================== -->

        <section class="settings-section"
                 id="security">

            <div class="section-heading">

                <span class="section-label">SECURITY</span>

                <h2>Security</h2>

                <p>
                    Protect your account and conversations.
                </p>

            </div>


            <div class="settings-card">

                <div class="security-banner">

                    <div class="security-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <div>

                        <h3>Your account is protected</h3>

                        <p>
                            AgriTech uses security controls to help
                            protect your account.
                        </p>

                    </div>

                </div>


                <div class="setting-row clickable-row"
                     data-action="security-alerts">

                    <div class="row-icon">
                        <i class="fa-solid fa-bell"></i>
                    </div>

                    <div class="row-info">

                        <h3>Security notifications</h3>

                        <p>
                            Get notified when your security information
                            changes.
                        </p>

                    </div>

                    <i class="fa-solid fa-chevron-right arrow"></i>

                </div>


                <div class="setting-row clickable-row"
                     data-action="devices">

                    <div class="row-icon">
                        <i class="fa-solid fa-laptop-mobile"></i>
                    </div>

                    <div class="row-info">

                        <h3>Linked devices</h3>

                        <p>
                            Manage computers and devices connected to
                            your account.
                        </p>

                    </div>

                    <i class="fa-solid fa-chevron-right arrow"></i>

                </div>

            </div>

        </section>


        <!-- =====================================================
             TWO STEP
        ====================================================== -->

        <section class="settings-section"
                 id="two-step">

            <div class="section-heading">

                <span class="section-label">ACCOUNT PROTECTION</span>

                <h2>Two-step verification</h2>

                <p>
                    Add an extra layer of protection to your account.
                </p>

            </div>


            <div class="settings-card two-step-card">

                <div class="big-setting-icon">

                    <i class="fa-solid fa-key"></i>

                </div>

                <h3>Protect your account</h3>

                <p>
                    Two-step verification adds a PIN that will be
                    required when registering your phone number again.
                </p>

                <button class="primary-btn"
                        id="enableTwoStep">

                    Enable

                </button>

            </div>

        </section>


        <!-- =====================================================
             NOTIFICATIONS
        ====================================================== -->

        <section class="settings-section"
                 id="notifications">

            <div class="section-heading">

                <span class="section-label">ALERTS</span>

                <h2>Notifications</h2>

                <p>
                    Choose how AgriTech notifies you.
                </p>

            </div>


            <div class="settings-card">

                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>

                    <div class="row-info">

                        <h3>Message notifications</h3>

                        <p>Receive notifications for new messages.</p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="messageNotifications"
                            checked
                        >

                        <span></span>

                    </label>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-volume-high"></i>
                    </div>

                    <div class="row-info">

                        <h3>Notification sounds</h3>

                        <p>Play sound when receiving messages.</p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="notificationSounds"
                            checked
                        >

                        <span></span>

                    </label>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div class="row-info">

                        <h3>Community notifications</h3>

                        <p>Receive alerts from agricultural communities.</p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="communityNotifications"
                            checked
                        >

                        <span></span>

                    </label>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-store"></i>
                    </div>

                    <div class="row-info">

                        <h3>Marketplace notifications</h3>

                        <p>
                            Receive updates about your agricultural
                            products and orders.
                        </p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="marketplaceNotifications"
                            checked
                        >

                        <span></span>

                    </label>

                </div>

            </div>

        </section>


        <!-- =====================================================
             CHATS
        ====================================================== -->

        <section class="settings-section"
                 id="chats">

            <div class="section-heading">

                <span class="section-label">MESSAGING</span>

                <h2>Chats</h2>

                <p>
                    Customize your agricultural conversations.
                </p>

            </div>


            <div class="settings-card">

                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>

                    <div class="row-info">

                        <h3>Chat backup</h3>

                        <p>Back up your conversations.</p>

                    </div>

                    <button class="small-btn">
                        Backup
                    </button>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-download"></i>
                    </div>

                    <div class="row-info">

                        <h3>Enter to send</h3>

                        <p>Use the Enter key to send messages.</p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="enterToSend"
                        >

                        <span></span>

                    </label>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-image"></i>
                    </div>

                    <div class="row-info">

                        <h3>Media visibility</h3>

                        <p>
                            Show received agricultural media in your
                            device gallery.
                        </p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="mediaVisibility"
                            checked
                        >

                        <span></span>

                    </label>

                </div>


                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-palette"></i>
                    </div>

                    <div class="row-info">

                        <h3>Chat wallpaper</h3>

                        <p>Customize the background of your chats.</p>

                    </div>

                    <button class="small-btn"
                            id="wallpaperBtn">

                        Change

                    </button>

                </div>

            </div>

        </section>


        <!-- =====================================================
             STORAGE
        ====================================================== -->

        <section class="settings-section"
                 id="storage">

            <div class="section-heading">

                <span class="section-label">DATA</span>

                <h2>Storage and data</h2>

                <p>
                    Manage your media, storage and data usage.
                </p>

            </div>


            <div class="settings-card">

                <div class="storage-summary">

                    <div class="storage-circle">

                        <i class="fa-solid fa-database"></i>

                    </div>

                    <div>

                        <h3>Storage usage</h3>

                        <p id="storageText">
                            0 MB used
                        </p>

                    </div>

                </div>


                <div class="storage-bar">

                    <span id="storageProgress"></span>

                </div>


                <div class="storage-item">

                    <span>
                        Photos
                    </span>

                    <strong>
                        0 MB
                    </strong>

                </div>


                <div class="storage-item">

                    <span>
                        Videos
                    </span>

                    <strong>
                        0 MB
                    </strong>

                </div>


                <div class="storage-item">

                    <span>
                        Documents
                    </span>

                    <strong>
                        0 MB
                    </strong>

                </div>


                <button class="danger-outline"
                        id="clearStorage">

                    Clear cached data

                </button>

            </div>


            <div class="settings-card">

                <div class="setting-row">

                    <div class="row-icon">
                        <i class="fa-solid fa-mobile-screen"></i>
                    </div>

                    <div class="row-info">

                        <h3>Use less data for calls</h3>

                        <p>
                            Reduce the amount of data used during calls.
                        </p>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            data-setting="lowDataMode"
                        >

                        <span></span>

                    </label>

                </div>

            </div>

        </section>


        <!-- =====================================================
             LANGUAGE
        ====================================================== -->

        <section class="settings-section"
                 id="language">

            <div class="section-heading">

                <span class="section-label">APP</span>

                <h2>App language</h2>

                <p>
                    Choose the language used by AgriTech.
                </p>

            </div>


            <div class="settings-card language-card">

                <div class="language-option selected">

                    <div class="language-icon">
                        <i class="fa-solid fa-globe"></i>
                    </div>

                    <div>

                        <h3>English</h3>
                        <p>English</p>

                    </div>

                    <i class="fa-solid fa-circle-check check"></i>

                </div>


                <div class="language-option">

                    <div class="language-icon">
                        <i class="fa-solid fa-language"></i>
                    </div>

                    <div>

                        <h3>Français</h3>
                        <p>French</p>

                    </div>

                    <i class="fa-regular fa-circle check"></i>

                </div>

            </div>

        </section>


        <!-- =====================================================
             HELP
        ====================================================== -->

        <section class="settings-section"
                 id="help">

            <div class="section-heading">

                <span class="section-label">SUPPORT</span>

                <h2>Help</h2>

                <p>
                    Get assistance with AgriTech.
                </p>

            </div>


            <div class="settings-card">

                <div class="setting-row clickable-row"
                     data-action="help-center">

                    <div class="row-icon">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>

                    <div class="row-info">

                        <h3><a href="help.php">Help center</a></h3>

                        <p>
                            Find answers to common questions.
                        </p>

                    </div>

                    <i class="fa-solid fa-chevron-right arrow"></i>

                </div>


                <div class="setting-row clickable-row"
                     data-action="contact">

                    <div class="row-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>

                    <div class="row-info">

                        <h3><a href="contact.php">Contact us</a></h3>

                        <p>
                            Contact the AgriTech support team.
                        </p>

                    </div>

                    <i class="fa-solid fa-chevron-right arrow"></i>

                </div>


                <div class="setting-row clickable-row"
                     data-action="terms">

                    <div class="row-icon">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>

                    <div class="row-info">

                        <h3><a href="terms.php">Terms</a> and <a href="privacy.php">privacy policy</a></h3>

                        <p>
                            Read our terms and privacy information.
                        </p>

                    </div>

                    <i class="fa-solid fa-chevron-right arrow"></i>

                </div>

            </div>


            <!-- Delete Account -->

            <div class="danger-card">

                <div>

                    <h3>Delete account</h3>

                    <p>
                        Permanently remove your AgriTech account
                        and associated data.
                    </p>

                </div>

                <button id="deleteAccountBtn">
                    Delete account
                </button>

            </div>

        </section>

    </main>

</div>


<!-- =============================================================
     PHOTO INPUT
============================================================= -->

<input
    type="file"
    id="photoInput"
    accept="image/*"
    hidden
>


<!-- =============================================================
     TOAST
============================================================= -->

<div class="toast"
     id="toast">

    <i class="fa-solid fa-circle-check"></i>

    <span id="toastMessage">
        Settings saved.
    </span>

</div>


<!-- =============================================================
     MODAL
============================================================= -->

<div class="modal"
     id="modal">

    <div class="modal-box">

        <button class="modal-close"
                id="modalClose">

            <i class="fa-solid fa-xmark"></i>

        </button>

        <div id="modalContent"></div>

    </div>

</div>


<script src="../scripts/profile.js"></script>

</body>
</html>