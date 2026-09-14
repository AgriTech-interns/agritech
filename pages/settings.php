
<?php

/*
|--------------------------------------------------------------------------
| SETTINGS PAGE
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| LOAD YOUR EXISTING DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Your config.php already connects to the database.
|
*/

require_once 'config.php';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;

}


$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| MESSAGE VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';


/*
|--------------------------------------------------------------------------
| GET USER INFORMATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Your database uses users.user_id.
|
*/

try {

    $userQuery = $pdo->prepare("
        SELECT
            user_id,
            fullname,
            email,
            phone,
            role
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $userQuery->execute([$userId]);

    $user = $userQuery->fetch(PDO::FETCH_ASSOC);


    if (!$user) {

        session_destroy();

        header("Location: login.php");
        exit;

    }

} catch (PDOException $e) {

    die(
        "Unable to load account information. "
        . "Please check your users table."
    );

}


/*
|--------------------------------------------------------------------------
| GET USER SETTINGS
|--------------------------------------------------------------------------
*/

try {

    $settingsQuery = $pdo->prepare("
        SELECT *
        FROM user_settings
        WHERE user_id = ?
        LIMIT 1
    ");

    $settingsQuery->execute([$userId]);

    $settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);


    /*
     * Create a settings record automatically
     * if the user does not have one.
     */

    if (!$settings) {

        $insertSettings = $pdo->prepare("
            INSERT INTO user_settings (
                user_id,
                profile_picture,
                theme,
                language,
                two_factor_enabled,
                login_alerts,
                push_notifications,
                message_notifications,
                market_notifications,
                expert_notifications,
                email_notifications,
                profile_visibility,
                show_phone,
                allow_messages
            )
            VALUES (
                ?,
                NULL,
                'light',
                'en',
                0,
                1,
                1,
                1,
                1,
                1,
                1,
                1,
                0,
                1
            )
        ");

        $insertSettings->execute([$userId]);


        /*
         * Load the newly created record.
         */

        $settingsQuery->execute([$userId]);

        $settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

    }

} catch (PDOException $e) {

    die(
        "Unable to load account settings. "
        . "Please make sure the user_settings table is correctly configured."
    );

}


/*
|--------------------------------------------------------------------------
| DEFAULT SETTINGS
|--------------------------------------------------------------------------
*/

$settings = array_merge([

    'profile_picture'       => null,

    'theme'                 => 'light',

    'language'              => 'en',

    'two_factor_enabled'    => 0,

    'login_alerts'          => 1,

    'push_notifications'    => 1,

    'message_notifications' => 1,

    'market_notifications'  => 1,

    'expert_notifications'  => 1,

    'email_notifications'   => 1,

    'profile_visibility'    => 1,

    'show_phone'            => 0,

    'allow_messages'        => 1

], $settings);


/*
|--------------------------------------------------------------------------
| SAVE PROFILE INFORMATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    ($_POST['action'] ?? '') === 'save_profile'
) {

    $fullname = trim($_POST['name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');


    /*
     * Validate name
     */

    if ($fullname === '') {

        $message =
            'Please enter your full name.';

        $messageType =
            'error';

    }


    /*
     * Validate email
     */

    elseif (
        $email !== ''
        &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            'Please enter a valid email address.';

        $messageType =
            'error';

    }


    else {

        try {

            /*
             * Check if another user already
             * has this email.
             *
             * IMPORTANT:
             * Uses user_id, NOT id.
             */

            if ($email !== '') {

                $emailCheck = $pdo->prepare("
                    SELECT user_id
                    FROM users
                    WHERE email = ?
                    AND user_id != ?
                    LIMIT 1
                ");

                $emailCheck->execute([
                    $email,
                    $userId
                ]);


                if ($emailCheck->fetch()) {

                    throw new Exception(
                        "This email address is already being used."
                    );

                }

            }


            /*
             * Update user information.
             *
             * IMPORTANT:
             * Uses users.user_id.
             */

            $updateUser = $pdo->prepare("
                UPDATE users
                SET
                    fullname = ?,
                    email = ?,
                    phone = ?
                WHERE user_id = ?
            ");

            $updateUser->execute([

                $fullname,

                $email,

                $phone,

                $userId

            ]);


            /*
             * Update session information.
             */

            $_SESSION['fullname'] =
                $fullname;

            $_SESSION['name'] =
                $fullname;

            $_SESSION['email'] =
                $email;

            $_SESSION['phone'] =
                $phone;


            /*
             * Update local user information.
             */

            $user['fullname'] =
                $fullname;

            $user['email'] =
                $email;

            $user['phone'] =
                $phone;


            /*
             |--------------------------------------------------------------------------
             | PROFILE PICTURE
             |--------------------------------------------------------------------------
             */

            if (
                isset($_FILES['profile_picture'])
                &&
                $_FILES['profile_picture']['error']
                !== UPLOAD_ERR_NO_FILE
            ) {

                $file =
                    $_FILES['profile_picture'];


                /*
                 * Check upload error.
                 */

                if (
                    $file['error']
                    !== UPLOAD_ERR_OK
                ) {

                    throw new Exception(
                        "There was a problem uploading the profile picture."
                    );

                }


                /*
                 * Maximum file size:
                 * 5 MB
                 */

                if (
                    $file['size']
                    > 5 * 1024 * 1024
                ) {

                    throw new Exception(
                        "Profile picture must be smaller than 5 MB."
                    );

                }


                /*
                 * Allowed image types.
                 */

                $allowedTypes = [

                    'image/jpeg' => 'jpg',

                    'image/png' => 'png',

                    'image/webp' => 'webp'

                ];


                /*
                 * Detect the actual MIME type.
                 */

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );


                $mimeType =
                    $finfo->file(
                        $file['tmp_name']
                    );


                /*
                 * Reject unsupported files.
                 */

                if (
                    !isset(
                        $allowedTypes[$mimeType]
                    )
                ) {

                    throw new Exception(
                        "Only JPG, PNG and WebP images are allowed."
                    );

                }


                /*
                 |--------------------------------------------------------------------------
                 | CREATE UPLOAD DIRECTORY
                 |--------------------------------------------------------------------------
                 |
                 | This assumes:
                 |
                 | settings.php
                 | Assets/
                 |     uploads/
                 |         profiles/
                 |
                 */

                $uploadDirectory =
                    __DIR__
                    . '/Assets/uploads/profiles/';


                /*
                 * Create folder if it does not exist.
                 */

                if (
                    !is_dir(
                        $uploadDirectory
                    )
                ) {

                    if (
                        !mkdir(
                            $uploadDirectory,
                            0755,
                            true
                        )
                    ) {

                        throw new Exception(
                            "Unable to create profile picture directory."
                        );

                    }

                }


                /*
                 * Generate unique filename.
                 */

                $extension =
                    $allowedTypes[$mimeType];


                $fileName =
                    'user_'
                    . $userId
                    . '_'
                    . bin2hex(
                        random_bytes(8)
                    )
                    . '.'
                    . $extension;


                $destination =
                    $uploadDirectory
                    . $fileName;


                /*
                 * Move uploaded image.
                 */

                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )
                ) {

                    throw new Exception(
                        "Unable to save profile picture."
                    );

                }


                /*
                 |--------------------------------------------------------------------------
                 | PATH SAVED IN DATABASE
                 |--------------------------------------------------------------------------
                 */

                $picturePath =
                    'Assets/uploads/profiles/'
                    . $fileName;


                /*
                 |--------------------------------------------------------------------------
                 | DELETE OLD PROFILE PICTURE
                 |--------------------------------------------------------------------------
                 */

                if (
                    !empty(
                        $settings['profile_picture']
                    )
                ) {

                    $oldPicture =
                        __DIR__
                        . '/'
                        . ltrim(
                            $settings['profile_picture'],
                            '/'
                        );


                    $realOldPicture =
                        realpath(
                            $oldPicture
                        );


                    $realUploadDirectory =
                        realpath(
                            $uploadDirectory
                        );


                    if (
                        $realOldPicture !== false
                        &&
                        $realUploadDirectory !== false
                        &&
                        strpos(
                            $realOldPicture,
                            $realUploadDirectory
                        ) === 0
                        &&
                        is_file(
                            $realOldPicture
                        )
                    ) {

                        @unlink(
                            $realOldPicture
                        );

                    }

                }


                /*
                 |--------------------------------------------------------------------------
                 | SAVE PICTURE PATH TO DATABASE
                 |--------------------------------------------------------------------------
                 */

                $pictureUpdate =
                    $pdo->prepare("
                        UPDATE user_settings
                        SET profile_picture = ?
                        WHERE user_id = ?
                    ");


                $pictureUpdate->execute([

                    $picturePath,

                    $userId

                ]);


                /*
                 * Update local settings.
                 */

                $settings['profile_picture'] =
                    $picturePath;

            }


            /*
             * Success message.
             */

            $message =
                'Your profile has been updated successfully.';

            $messageType =
                'success';


        } catch (Exception $e) {

            $message =
                $e->getMessage();

            $messageType =
                'error';

        }

    }

}


/*
|--------------------------------------------------------------------------
| SAVE GENERAL SETTINGS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    ($_POST['action'] ?? '') === 'save_settings'
) {

    try {

        /*
         * Theme
         */

        $theme =
            $_POST['theme']
            ?? 'light';


        $allowedThemes = [

            'light',

            'dark',

            'system'

        ];


        if (
            !in_array(
                $theme,
                $allowedThemes,
                true
            )
        ) {

            $theme =
                'light';

        }


        /*
         * Language
         */

        $language =
            $_POST['language']
            ?? 'en';


        $allowedLanguages = [

            'en',

            'fr'

        ];


        if (
            !in_array(
                $language,
                $allowedLanguages,
                true
            )
        ) {

            $language =
                'en';

        }


        /*
         * Checkbox values.
         */

        $twoFactor =
            isset(
                $_POST['two_factor_enabled']
            )
                ? 1
                : 0;


        $loginAlerts =
            isset(
                $_POST['login_alerts']
            )
                ? 1
                : 0;


        $pushNotifications =
            isset(
                $_POST['push_notifications']
            )
                ? 1
                : 0;


        $messageNotifications =
            isset(
                $_POST['message_notifications']
            )
                ? 1
                : 0;


        $marketNotifications =
            isset(
                $_POST['market_notifications']
            )
                ? 1
                : 0;


        $expertNotifications =
            isset(
                $_POST['expert_notifications']
            )
                ? 1
                : 0;


        $emailNotifications =
            isset(
                $_POST['email_notifications']
            )
                ? 1
                : 0;


        $profileVisibility =
            isset(
                $_POST['profile_visibility']
            )
                ? 1
                : 0;


        $showPhone =
            isset(
                $_POST['show_phone']
            )
                ? 1
                : 0;


        $allowMessages =
            isset(
                $_POST['allow_messages']
            )
                ? 1
                : 0;


        /*
         |--------------------------------------------------------------------------
         | UPDATE SETTINGS
         |--------------------------------------------------------------------------
         */

        $updateSettings =
            $pdo->prepare("
                UPDATE user_settings
                SET
                    theme = ?,
                    language = ?,
                    two_factor_enabled = ?,
                    login_alerts = ?,
                    push_notifications = ?,
                    message_notifications = ?,
                    market_notifications = ?,
                    expert_notifications = ?,
                    email_notifications = ?,
                    profile_visibility = ?,
                    show_phone = ?,
                    allow_messages = ?
                WHERE user_id = ?
            ");


        $updateSettings->execute([

            $theme,

            $language,

            $twoFactor,

            $loginAlerts,

            $pushNotifications,

            $messageNotifications,

            $marketNotifications,

            $expertNotifications,

            $emailNotifications,

            $profileVisibility,

            $showPhone,

            $allowMessages,

            $userId

        ]);


        /*
         * Reload settings.
         */

        $settingsQuery->execute([
            $userId
        ]);


        $settings =
            $settingsQuery->fetch(
                PDO::FETCH_ASSOC
            );


        /*
         * Success.
         */

        $message =
            'Settings saved successfully.';

        $messageType =
            'success';


    } catch (PDOException $e) {

        $message =
            'Unable to save your settings.';

        $messageType =
            'error';

    }

}


/*
|--------------------------------------------------------------------------
| PROFILE PICTURE
|--------------------------------------------------------------------------
*/

$profilePicture =
    !empty(
        $settings['profile_picture']
    )
        ? $settings['profile_picture']
        : null;


/*
|--------------------------------------------------------------------------
| ACCOUNT ROLE
|--------------------------------------------------------------------------
*/

$accountRole =
    ucfirst(
        strtolower(
            $user['role']
            ?? 'user'
        )
    );


/*
|--------------------------------------------------------------------------
| SAFE DISPLAY VALUES
|--------------------------------------------------------------------------
*/

$fullName =
    htmlspecialchars(
        $user['fullname']
        ?? '',
        ENT_QUOTES,
        'UTF-8'
    );


$email =
    htmlspecialchars(
        $user['email']
        ?? '',
        ENT_QUOTES,
        'UTF-8'
    );


$phone =
    htmlspecialchars(
        $user['phone']
        ?? '',
        ENT_QUOTES,
        'UTF-8'
    );


$role =
    htmlspecialchars(
        $accountRole,
        ENT_QUOTES,
        'UTF-8'
    );

?>

<!DOCTYPE html>

<html lang="<?= htmlspecialchars($settings['language']?? 'en') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"  content="width=device-width, initial-scale=1.0">
    <title>
        Settings | Agri-Tech
    </title>
    <link rel="stylesheet" href="../Assets/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="settings-page">

    <div class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside
        class="settings-sidebar"
        id="settingsSidebar"
    >

        <div class="sidebar-header">

            <div class="sidebar-logo">

                <i class="fa-solid fa-seedling"></i>

            </div>


            <div>

                <h2>
                    AgriTech
                </h2>

                <span>
                    Account Settings
                </span>

            </div>


            <button
                class="close-sidebar"
                id="closeSidebar"
                type="button"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <nav class="settings-navigation">


            <button
                class="settings-nav-item active"
                data-section="account"
                type="button"
            >

                <i class="fa-solid fa-user"></i>

                <span>
                    Profile
                </span>

            </button>


            <button
                class="settings-nav-item"
                data-section="security"
                type="button"
            >

                <i class="fa-solid fa-lock"></i>

                <span>
                    Security
                </span>

            </button>


            <button
                class="settings-nav-item"
                data-section="notifications"
                type="button"
            >

                <i class="fa-solid fa-bell"></i>

                <span>
                    Notifications
                </span>

            </button>


            <button
                class="settings-nav-item"
                data-section="appearance"
                type="button"
            >

                <i class="fa-solid fa-palette"></i>

                <span>
                    Appearance
                </span>

            </button>


            <button
                class="settings-nav-item"
                data-section="language"
                type="button"
            >

                <i class="fa-solid fa-globe"></i>

                <span>
                    Language
                </span>

            </button>


            <button
                class="settings-nav-item"
                data-section="privacy"
                type="button"
            >

                <i class="fa-solid fa-shield-halved"></i>

                <span>
                    Privacy
                </span>

            </button>


            <button
                class="settings-nav-item"
                data-section="payments"
                type="button"
            >

                <i class="fa-solid fa-credit-card"></i>

                <span>
                    Payments
                </span>

            </button>


            <button
                class="settings-nav-item"
                data-section="devices"
                type="button"
            >

                <i class="fa-solid fa-mobile-screen"></i>

                <span>
                    Devices
                </span>

            </button>

        </nav>


        <div class="sidebar-bottom">


            <button
                class="settings-nav-item logout-item"
                id="logoutBtn"
                type="button"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>
                    Logout
                </span>

            </button>


            <button
                class="settings-nav-item delete-item"
                id="deleteAccountBtn"
                type="button"
            >

                <i class="fa-solid fa-trash"></i>

                <span>
                    Delete Account
                </span>

            </button>

        </div>

    </aside>


    <!-- =========================================================
         MAIN AREA
    ========================================================== -->

    <main class="settings-main">


        <!-- =====================================================
             TOP BAR
        ====================================================== -->

        <header class="settings-header">

            <div class="header-left">

                <button
                    class="mobile-menu-btn"
                    id="openSidebar"
                    type="button"
                >

                    <i class="fa-solid fa-bars"></i>

                </button>


                <div>

                    <h1>
                        Settings
                    </h1>

                    <p>
                        Manage your AgriTech account and preferences
                    </p>

                </div>

            </div>


            <div class="header-actions">


                <button
                    class="header-icon"
                    id="notificationBtn"
                    title="Notifications"
                    type="button"
                >

                    <i class="fa-solid fa-bell"></i>

                    <span class="notification-dot"></span>

                </button>


                <button
                    class="header-profile"
                    id="profileMenuBtn"
                    type="button"
                >

                    <div class="header-avatar">


                        <?php if (!empty($profilePicture)): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $profilePicture,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="Profile"
                            >

                        <?php else: ?>

                            <i class="fa-solid fa-user"></i>

                        <?php endif; ?>


                    </div>


                    <span>
                        <?= $fullName ?>
                    </span>


                    <i class="fa-solid fa-chevron-down"></i>

                </button>

            </div>

        </header>


        <!-- =====================================================
             ALERT MESSAGE
        ====================================================== -->

        <?php if ($message): ?>

            <div
                class="settings-alert <?= htmlspecialchars(
                    $messageType,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                id="serverAlert"
            >

                <i
                    class="fa-solid
                    <?= $messageType === 'success'
                        ? 'fa-circle-check'
                        : 'fa-circle-exclamation' ?>"
                ></i>


                <span>
                    <?= htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>


                <button
                    type="button"
                    onclick="this.parentElement.remove()"
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <div class="settings-content">


            <!-- =================================================
                 ACCOUNT / PROFILE
            ================================================== -->

            <section
                class="settings-section active"
                id="account"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Account Settings
                        </h2>

                        <p>
                            Manage your personal profile information.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    class="settings-card profile-form"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="save_profile"
                    >


                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-user"></i>

                        </div>


                        <div>

                            <h3>
                                Profile Information
                            </h3>

                            <p>
                                Keep your account information up to date.
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         PROFILE PICTURE
                    ================================================== -->

                    <div class="profile-picture-area">


                        <div
                            class="large-avatar"
                            id="profileAvatar"
                        >


                            <?php if (!empty($profilePicture)): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $profilePicture,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="Profile picture"
                                    id="profilePreview"
                                >

                            <?php else: ?>

                                <i
                                    class="fa-solid fa-user"
                                    id="profilePlaceholder"
                                ></i>

                            <?php endif; ?>


                        </div>


                        <div class="avatar-actions">


                            <h4>
                                Profile Picture
                            </h4>


                            <p>
                                Use a clear image so other members can identify you.
                            </p>


                            <label class="upload-btn">

                                <i class="fa-solid fa-camera"></i>

                                Change Picture


                                <input
                                    type="file"
                                    id="avatarInput"
                                    name="profile_picture"
                                    accept="image/jpeg,image/png,image/webp"
                                    hidden
                                >

                            </label>


                            <button
                                type="button"
                                class="remove-picture"
                                id="removePicture"
                            >

                                <i class="fa-solid fa-trash"></i>

                                Remove

                            </button>

                        </div>

                    </div>


                    <!-- =================================================
                         FULL NAME
                    ================================================== -->

                    <div class="form-group">

                        <label for="name">
                            Full Name
                        </label>


                        <div class="input-wrapper">

                            <i class="fa-solid fa-user"></i>


                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= $fullName ?>"
                                placeholder="Enter your full name"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>


                    <!-- =================================================
                         EMAIL
                    ================================================== -->

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>


                        <div class="input-wrapper">

                            <i class="fa-solid fa-envelope"></i>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= $email ?>"
                                placeholder="Enter your email"
                                autocomplete="email"
                            >

                        </div>

                    </div>


                    <!-- =================================================
                         PHONE
                    ================================================== -->

                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>


                        <div class="input-wrapper">

                            <i class="fa-solid fa-phone"></i>


                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= $phone ?>"
                                placeholder="Enter your phone number"
                                autocomplete="tel"
                            >

                        </div>

                    </div>


                    <!-- =================================================
                         ACCOUNT TYPE
                    ================================================== -->

                    <div class="form-group">

                        <label for="accountType">
                            Account Type
                        </label>


                        <div class="input-wrapper">

                            <i class="fa-solid fa-id-badge"></i>


                            <input
                                type="text"
                                id="accountType"
                                value="<?= $role ?>"
                                readonly
                            >

                        </div>


                        <small>
                            Your account type is managed by Agro-Tech.
                        </small>

                    </div>


                    <!-- =================================================
                         SAVE PROFILE
                    ================================================== -->

                    <div class="card-footer">

                        <button
                            type="submit"
                            class="primary-btn"
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Changes

                        </button>

                    </div>

                </form>

            </section>


            <!-- =================================================
                 SECURITY
            ================================================== -->

            <section
                class="settings-section"
                id="security"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Security
                        </h2>

                        <p>
                            Protect your account and manage login security.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    class="settings-card"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="save_settings"
                    >


                    <input
                        type="hidden"
                        name="theme"
                        value="<?= htmlspecialchars(
                            $settings['theme']
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="language"
                        value="<?= htmlspecialchars(
                            $settings['language']
                        ) ?>"
                    >


                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-lock"></i>

                        </div>


                        <div>

                            <h3>
                                Password & Security
                            </h3>

                            <p>
                                Keep your Agro-Tech account secure.
                            </p>

                        </div>

                    </div>


                    <div class="setting-row">

                        <div class="setting-info">

                            <h4>
                                Change Password
                            </h4>

                            <p>
                                Update your password regularly to protect your account.
                            </p>

                        </div>


                        <button
                            type="button"
                            class="secondary-btn"
                            id="changePasswordBtn"
                        >
                            Change Password
                        </button>

                    </div>


                    <div class="setting-row">

                        <div class="setting-info">

                            <h4>
                                Two-Factor Authentication
                            </h4>

                            <p>
                                Add another layer of security to your account.
                            </p>

                        </div>


                        <label class="switch">

                            <input
                                type="checkbox"
                                name="two_factor_enabled"
                                <?= !empty(
                                    $settings['two_factor_enabled']
                                )
                                    ? 'checked'
                                    : '' ?>
                            >

                            <span class="slider"></span>

                        </label>

                    </div>


                    <div class="setting-row">

                        <div class="setting-info">

                            <h4>
                                Login Alerts
                            </h4>

                            <p>
                                Receive alerts when your account is accessed from a new device.
                            </p>

                        </div>


                        <label class="switch">

                            <input
                                type="checkbox"
                                name="login_alerts"
                                <?= !empty(
                                    $settings['login_alerts']
                                )
                                    ? 'checked'
                                    : '' ?>
                            >

                            <span class="slider"></span>

                        </label>

                    </div>


                    <div class="card-footer">

                        <button
                            type="submit"
                            class="primary-btn"
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Security Settings

                        </button>

                    </div>

                </form>

            </section>


            <!-- =================================================
                 NOTIFICATIONS
            ================================================== -->

            <section
                class="settings-section"
                id="notifications"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Notifications
                        </h2>

                        <p>
                            Choose what notifications you want to receive.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    class="settings-card"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="save_settings"
                    >


                    <input
                        type="hidden"
                        name="theme"
                        value="<?= htmlspecialchars(
                            $settings['theme']
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="language"
                        value="<?= htmlspecialchars(
                            $settings['language']
                        ) ?>"
                    >


                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-bell"></i>

                        </div>


                        <div>

                            <h3>
                                Notification Preferences
                            </h3>

                            <p>
                                Control how Agro-Tech keeps you informed.
                            </p>

                        </div>

                    </div>


                    <?php

                    $notificationSettings = [

                        'push_notifications' => [
                            'title' => 'Push Notifications',
                            'description' => 'Receive notifications directly on your device.'
                        ],

                        'message_notifications' => [
                            'title' => 'Messages',
                            'description' => 'Get notified when someone sends you a message.'
                        ],

                        'market_notifications' => [
                            'title' => 'Market Updates',
                            'description' => 'Receive updates about agricultural products and markets.'
                        ],

                        'expert_notifications' => [
                            'title' => 'Expert Updates',
                            'description' => 'Receive agricultural advice and expert recommendations.'
                        ],

                        'email_notifications' => [
                            'title' => 'Email Notifications',
                            'description' => 'Receive important account information by email.'
                        ]

                    ];

                    ?>


                    <?php foreach (
                        $notificationSettings
                        as $settingName => $settingData
                    ): ?>


                        <div class="setting-row">

                            <div class="setting-info">

                                <h4>
                                    <?= htmlspecialchars(
                                        $settingData['title']
                                    ) ?>
                                </h4>

                                <p>
                                    <?= htmlspecialchars(
                                        $settingData['description']
                                    ) ?>
                                </p>

                            </div>


                            <label class="switch">

                                <input
                                    type="checkbox"
                                    name="<?= htmlspecialchars(
                                        $settingName
                                    ) ?>"
                                    <?= !empty(
                                        $settings[$settingName]
                                    )
                                        ? 'checked'
                                        : '' ?>
                                >

                                <span class="slider"></span>

                            </label>

                        </div>


                    <?php endforeach; ?>


                    <div class="card-footer">

                        <button
                            type="submit"
                            class="primary-btn"
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Notification Settings

                        </button>

                    </div>

                </form>

            </section>


            <!-- =================================================
                 APPEARANCE
            ================================================== -->

            <section
                class="settings-section"
                id="appearance"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Appearance
                        </h2>

                        <p>
                            Customize how Agro-Tech looks on your device.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    class="settings-card"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="save_settings"
                    >


                    <input
                        type="hidden"
                        name="language"
                        value="<?= htmlspecialchars(
                            $settings['language']
                        ) ?>"
                    >


                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-palette"></i>

                        </div>


                        <div>

                            <h3>
                                Theme
                            </h3>

                            <p>
                                Select your preferred interface appearance.
                            </p>

                        </div>

                    </div>


                    <div class="theme-options">


                        <button
                            type="submit"
                            name="theme"
                            value="light"
                            class="theme-option
                            <?= $settings['theme'] === 'light'
                                ? 'active'
                                : '' ?>"
                        >

                            <div class="theme-preview light-preview">

                                <div></div>

                                <div></div>

                            </div>


                            <strong>
                                Light
                            </strong>

                            <span>
                                Clean and bright
                            </span>

                        </button>


                        <button
                            type="submit"
                            name="theme"
                            value="dark"
                            class="theme-option
                            <?= $settings['theme'] === 'dark'
                                ? 'active'
                                : '' ?>"
                        >

                            <div class="theme-preview dark-preview">

                                <div></div>

                                <div></div>

                            </div>


                            <strong>
                                Dark
                            </strong>

                            <span>
                                Easy on the eyes
                            </span>

                        </button>


                        <button
                            type="submit"
                            name="theme"
                            value="system"
                            class="theme-option
                            <?= $settings['theme'] === 'system'
                                ? 'active'
                                : '' ?>"
                        >

                            <div class="theme-preview system-preview">

                                <div></div>

                                <div></div>

                            </div>


                            <strong>
                                System
                            </strong>

                            <span>
                                Use device preference
                            </span>

                        </button>


                    </div>

                </form>

            </section>


            <!-- =================================================
                 LANGUAGE
            ================================================== -->

            <section
                class="settings-section"
                id="language"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Language
                        </h2>

                        <p>
                            Choose the language used throughout Agro-Tech.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    class="settings-card"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="save_settings"
                    >


                    <input
                        type="hidden"
                        name="theme"
                        value="<?= htmlspecialchars(
                            $settings['theme']
                        ) ?>"
                    >


                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-globe"></i>

                        </div>


                        <div>

                            <h3>
                                Language Preferences
                            </h3>

                            <p>
                                Select your preferred language.
                            </p>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="languageSelect">
                            Application Language
                        </label>


                        <div class="select-wrapper">

                            <i class="fa-solid fa-language"></i>


                            <select
                                id="languageSelect"
                                name="language"
                            >

                                <option
                                    value="en"
                                    <?= $settings['language'] === 'en'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    English
                                </option>


                                <option
                                    value="fr"
                                    <?= $settings['language'] === 'fr'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Français
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="card-footer">

                        <button
                            type="submit"
                            class="primary-btn"
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Language

                        </button>

                    </div>

                </form>

            </section>


            <!-- =================================================
                 PRIVACY
            ================================================== -->

            <section
                class="settings-section"
                id="privacy"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Privacy
                        </h2>

                        <p>
                            Control who can see and interact with your account.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    class="settings-card"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="save_settings"
                    >


                    <input
                        type="hidden"
                        name="theme"
                        value="<?= htmlspecialchars(
                            $settings['theme']
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="language"
                        value="<?= htmlspecialchars(
                            $settings['language']
                        ) ?>"
                    >


                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-shield-halved"></i>

                        </div>


                        <div>

                            <h3>
                                Privacy Controls
                            </h3>

                            <p>
                                Manage your visibility and communication preferences.
                            </p>

                        </div>

                    </div>


                    <div class="setting-row">

                        <div class="setting-info">

                            <h4>
                                Profile Visibility
                            </h4>

                            <p>
                                Allow other Agro-Tech members to view your profile.
                            </p>

                        </div>


                        <label class="switch">

                            <input
                                type="checkbox"
                                name="profile_visibility"
                                <?= !empty(
                                    $settings['profile_visibility']
                                )
                                    ? 'checked'
                                    : '' ?>
                            >

                            <span class="slider"></span>

                        </label>

                    </div>


                    <div class="setting-row">

                        <div class="setting-info">

                            <h4>
                                Show Phone Number
                            </h4>

                            <p>
                                Allow other users to see your phone number.
                            </p>

                        </div>


                        <label class="switch">

                            <input
                                type="checkbox"
                                name="show_phone"
                                <?= !empty(
                                    $settings['show_phone']
                                )
                                    ? 'checked'
                                    : '' ?>
                            >

                            <span class="slider"></span>

                        </label>

                    </div>


                    <div class="setting-row">

                        <div class="setting-info">

                            <h4>
                                Allow Direct Messages
                            </h4>

                            <p>
                                Allow other verified members to message you.
                            </p>

                        </div>


                        <label class="switch">

                            <input
                                type="checkbox"
                                name="allow_messages"
                                <?= !empty(
                                    $settings['allow_messages']
                                )
                                    ? 'checked'
                                    : '' ?>
                            >

                            <span class="slider"></span>

                        </label>

                    </div>


                    <div class="card-footer">

                        <button
                            type="submit"
                            class="primary-btn"
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Privacy Settings

                        </button>

                    </div>

                </form>

            </section>


            <!-- =================================================
                 PAYMENTS
            ================================================== -->

            <section
                class="settings-section"
                id="payments"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Payments
                        </h2>

                        <p>
                            Manage your payment preferences and transactions.
                        </p>

                    </div>

                </div>


                <div class="settings-card">

                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-credit-card"></i>

                        </div>


                        <div>

                            <h3>
                                Payment Settings
                            </h3>

                            <p>
                                Manage payment methods for Agro-Tech services.
                            </p>

                        </div>

                    </div>


                    <div class="payment-empty">

                        <div class="empty-icon">

                            <i class="fa-solid fa-wallet"></i>

                        </div>


                        <h3>
                            No payment method added
                        </h3>


                        <p>
                            Add a payment method when you are ready to make
                            purchases or receive payments through Agro-Tech.
                        </p>


                        <button
                            class="primary-btn"
                            id="addPaymentBtn"
                            type="button"
                        >

                            <i class="fa-solid fa-plus"></i>

                            Add Payment Method

                        </button>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 DEVICES
            ================================================== -->

            <section
                class="settings-section"
                id="devices"
            >

                <div class="section-heading">

                    <div>

                        <h2>
                            Devices
                        </h2>

                        <p>
                            Review devices that have accessed your account.
                        </p>

                    </div>

                </div>


                <div class="settings-card">

                    <div class="card-title">

                        <div class="title-icon">

                            <i class="fa-solid fa-mobile-screen"></i>

                        </div>


                        <div>

                            <h3>
                                Active Devices
                            </h3>

                            <p>
                                Manage where your Agro-Tech account is signed in.
                            </p>

                        </div>

                    </div>


                    <div class="device-item">

                        <div class="device-icon">

                            <i class="fa-solid fa-desktop"></i>

                        </div>


                        <div class="device-info">

                            <h4>
                                This Device
                            </h4>


                            <p>

                                <span id="deviceBrowser">
                                    Web Browser
                                </span>

                                · Active now

                            </p>

                        </div>


                        <span class="current-device">
                            Current
                        </span>

                    </div>


                    <div class="device-item">

                        <div class="device-icon">

                            <i class="fa-solid fa-mobile-screen"></i>

                        </div>


                        <div class="device-info">

                            <h4>
                                Mobile Device
                            </h4>


                            <p>
                                No recent activity recorded.
                            </p>

                        </div>


                        <button
                            class="remove-device"
                            type="button"
                        >
                            Remove
                        </button>

                    </div>


                    <button
                        class="danger-outline"
                        id="logoutAllBtn"
                        type="button"
                    >

                        <i class="fa-solid fa-arrow-right-from-bracket"></i>

                        Log Out Of All Other Devices

                    </button>

                </div>

            </section>


            <!-- =================================================
                 DANGER ZONE
            ================================================== -->

            <section class="danger-zone">

                <div>

                    <div class="danger-title">

                        <i class="fa-solid fa-triangle-exclamation"></i>


                        <div>

                            <h3>
                                Danger Zone
                            </h3>


                            <p>
                                These actions can permanently affect your account.
                            </p>

                        </div>

                    </div>

                </div>


                <button
                    class="delete-account-btn"
                    id="deleteAccountBottom"
                    type="button"
                >

                    Delete Account

                </button>

            </section>


        </div>

    </main>

</div>


<!-- =============================================================
     CONFIRMATION MODAL
============================================================== -->

<div
    class="modal"
    id="confirmationModal"
>

    <div class="modal-content">


        <button
            class="modal-close"
            id="closeModal"
            type="button"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div
            class="modal-icon"
            id="modalIcon"
        >

            <i class="fa-solid fa-circle-question"></i>

        </div>


        <h3 id="modalTitle">
            Are you sure?
        </h3>


        <p id="modalMessage">
            Please confirm this action.
        </p>


        <div class="modal-actions">

            <button
                class="secondary-btn"
                id="cancelModal"
                type="button"
            >
                Cancel
            </button>


            <button
                class="primary-btn"
                id="confirmModal"
                type="button"
            >
                Confirm
            </button>

        </div>

    </div>

</div>


<!-- =============================================================
     CHANGE PASSWORD MODAL
============================================================== -->

<div
    class="modal"
    id="passwordModal"
>

    <div class="modal-content">


        <button
            class="modal-close"
            id="closePasswordModal"
            type="button"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <div class="modal-icon">

            <i class="fa-solid fa-lock"></i>

        </div>


        <h3>
            Change Password
        </h3>


        <p>
            Enter your new password below.
        </p>


        <form
            method="POST"
            id="passwordForm"
        >

            <input
                type="hidden"
                name="action"
                value="change_password"
            >


            <div class="form-group">

                <label for="currentPassword">
                    Current Password
                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        name="current_password"
                        id="currentPassword"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                    >

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>

            </div>


            <div class="form-group">

                <label for="newPassword">
                    New Password
                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        name="new_password"
                        id="newPassword"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                    >

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>

            </div>


            <div class="form-group">

                <label for="confirmPassword">
                    Confirm New Password
                </label>


                <div class="password-wrapper">

                    <input  type="password" name="confirm_password" id="confirmPassword" required>
                    <button type="button" class="toggle-password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="password-strength">
              <div class="strength-bar">
                    <span id="strengthBar"></span>
                </div>
                <small id="strengthText">
                    Enter a password
                </small>
            </div>
            <button type="submit" class="primary-btn full-btn">
                Update Password
            </button>
        </form>
    </div>
</div>

<script src="../scripts/settings.js"></script>

</body>

</html>