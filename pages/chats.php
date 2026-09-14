<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Register current user as online
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    INSERT INTO chat_user_status
        (user_id, last_seen, is_online)
    VALUES
        (?, NOW(), 1)
    ON DUPLICATE KEY UPDATE
        last_seen = NOW(),
        is_online = 1
");
$stmt->execute([$userId]);

/*
|--------------------------------------------------------------------------
| Find users table
|--------------------------------------------------------------------------
*/

$users = [];

try {
    $stmt = $pdo->prepare("
        SELECT user_id, fullName, email, phone, profile_pic, role
        FROM users
        WHERE user_id != ?
        ORDER BY fullName ASC
    ");
    $stmt->execute([$userId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_id, fullName, email, role
            FROM users
            WHERE user_id != ?
            ORDER BY fullName ASC
        ");
        $stmt->execute([$userId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        $users = [];
    }
}

/*
|--------------------------------------------------------------------------
| Current user's contacts
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT c.contact_id,
           u.fullName,
           u.email,
           u.profile_pic,
           u.role
    FROM chat_contacts c
    INNER JOIN users u ON u.user_id = c.contact_id
    WHERE c.user_id = ?
    ORDER BY u.fullName ASC
");
$stmt->execute([$userId]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agriculture Chat</title>

<link rel="stylesheet" href="../Assets/chats.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>


<body>

<div class="chat-app">

    <!-- MOBILE HEADER -->
    <header class="mobile-header">
        <button id="mobileBack" class="icon-btn">
            <i class="fas fa-arrow-left"></i>
        </button>

        <div class="mobile-title">
            Agri<span>Tech</span> Chat
        </div>

        <button id="mobileSearch" class="icon-btn">
            <i class="fas fa-search"></i>
        </button>
    </header>


    <!-- SIDEBAR -->
    <aside class="chat-sidebar">

        <div class="sidebar-header">

            <div class="profile-mini">
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>

                <div>
                    <strong>Messages</strong>
                    <small>Community Chat</small>
                </div>
            </div>

            <div class="header-actions">

                <button id="newContactBtn"
                        class="icon-btn"
                        title="Add contact">
                    <i class="fas fa-user-plus"></i>
                </button>

                <button id="searchBtn"
                        class="icon-btn"
                        title="Search">
                    <i class="fas fa-search"></i>
                </button>

            </div>

        </div>


        <!-- SEARCH -->
        <div class="search-box" id="searchBox">

            <i class="fas fa-search"></i>

            <input
                type="text"
                id="contactSearch"
                placeholder="Search contacts..."
                autocomplete="off"
            >

        </div>


        <!-- CONTACTS -->
        <div class="contacts-list" id="contactsList">

            <?php if (empty($contacts)): ?>

                <div class="empty-contacts">
                    <i class="fas fa-comments"></i>

                    <h3>No conversations</h3>

                    <p>
                        Add a farmer, buyer or agricultural expert
                        to start chatting.
                    </p>

                    <button class="primary-btn"
                            id="emptyAddContact">
                        <i class="fas fa-user-plus"></i>
                        Add Contact
                    </button>
                </div>

            <?php else: ?>

                <?php foreach ($contacts as $contact): ?>

                    <div
                        class="contact-item"
                        data-id="<?= (int)$contact['contact_id'] ?>"
                        data-name="<?= htmlspecialchars($contact['fullName']) ?>"
                    >

                        <div class="contact-avatar">

                            <?php if (!empty($contact['profile_pic'])): ?>

                                <img
                                    src="<?= htmlspecialchars($contact['profile_pic']) ?>"
                                    alt=""
                                >

                            <?php else: ?>

                                <?= strtoupper(
                                    substr($contact['fullName'], 0, 1)
                                ) ?>

                            <?php endif; ?>

                            <span class="online-dot"
                                  id="online-<?= (int)$contact['contact_id'] ?>">
                            </span>

                        </div>


                        <div class="contact-info">

                            <div class="contact-top">

                                <strong>
                                    <?= htmlspecialchars($contact['fullName']) ?>
                                </strong>

                                <span class="contact-time"
                                      id="time-<?= (int)$contact['contact_id'] ?>">
                                </span>

                            </div>

                            <div class="contact-bottom">

                                <span class="last-message"
                                      id="last-<?= (int)$contact['contact_id'] ?>">
                                    Start conversation
                                </span>

                                <span class="unread-count"
                                      id="unread-<?= (int)$contact['contact_id'] ?>">
                                </span>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </aside>


    <!-- CHAT -->
    <main class="chat-main" id="chatMain">

        <!-- EMPTY STATE -->
        <section class="welcome-screen" id="welcomeScreen">

            <div class="welcome-icon">
                <i class="f">AG</i>
            </div>

            <h1>Agri<span>Tech</span> Chat</h1>

            <p>
                Connect with farmers, agricultural experts,
                buyers and other members of your farming community.
            </p>

            <div class="welcome-features">

                <span>
                    <i class="fas fa-comments"></i>
                    Private messages
                </span>

                <span>
                    <i class="fas fa-check-double"></i>
                    Read receipts
                </span>

                <span>
                    <i class="fas fa-paperclip"></i>
                    Attachments
                </span>

            </div>

        </section>


        <!-- ACTIVE CHAT -->
        <section class="active-chat" id="activeChat">

            <!-- CHAT HEADER -->
            <header class="chat-header">

                <button id="chatBack"
                        class="mobile-back-btn">
                    <i class="fas fa-arrow-left"></i>
                </button>

                <div class="chat-user-avatar">

                    <span id="chatAvatarLetter">U</span>

                    <span class="online-dot"
                          id="chatOnlineDot">
                    </span>

                </div>


                <div class="chat-user-info">

                    <strong id="chatUserName">
                        User
                    </strong>

                    <span id="chatUserStatus">
                        offline
                    </span>

                </div>


                <div class="chat-header-actions">

                    <button class="icon-btn"
                            title="Search messages"
                            id="messageSearchBtn">
                        <i class="fas fa-search"></i>
                    </button>

                    <button class="icon-btn"
                            title="Voice call"
                            id="voiceCallBtn">
                        <i class="fas fa-phone"></i>
                    </button>

                    <button class="icon-btn"
                            title="Video call"
                            id="videoCallBtn">
                        <i class="fas fa-video"></i>
                    </button>

                    <button class="icon-btn"
                            title="More"
                            id="chatMenuBtn">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>

                </div>

            </header>


            <!-- MESSAGE SEARCH -->
            <div class="message-search" id="messageSearch">

                <i class="fas fa-search"></i>

                <input
                    id="messageSearchInput"
                    type="text"
                    placeholder="Search messages..."
                >

                <button id="closeMessageSearch">
                    <i class="fas fa-times"></i>
                </button>

            </div>


            <!-- MESSAGES -->
            <div class="messages-area"
                 id="messagesArea">

                <div class="message-loading"
                     id="messageLoading">

                    <i class="fas fa-spinner fa-spin"></i>

                </div>

            </div>


            <!-- TYPING -->
            <div class="typing-indicator"
                 id="typingIndicator">

                <span></span>
                <span></span>
                <span></span>

                <small>typing...</small>

            </div>


            <!-- REPLY PREVIEW -->
            <div class="reply-preview"
                 id="replyPreview">

                <div>
                    <strong>Replying to</strong>
                    <p id="replyText"></p>
                </div>

                <button id="cancelReply">
                    <i class="fas fa-times"></i>
                </button>

            </div>


            <!-- EMOJI -->
            <div class="emoji-picker"
                 id="emojiPicker">

                <?php
                $emojis = [
                    '😀','😂','😍','😊','😎','😢','😭',
                    '😡','👍','👎','❤️','🔥','🙏','👏',
                    '🎉','🌱','🌾','🌽','🥕','🍅','🌿',
                    '🐄','🐔','🐐','🚜','☀️','🌧️','🌍'
                ];

                foreach ($emojis as $emoji):
                ?>

                    <button class="emoji-btn">
                        <?= $emoji ?>
                    </button>

                <?php endforeach; ?>

            </div>


            <!-- INPUT -->
            <footer class="message-composer">

                <button class="icon-btn"
                        id="emojiBtn"
                        title="Emoji">

                    <i class="far fa-smile"></i>

                </button>


                <button class="icon-btn"
                        id="attachmentBtn"
                        title="Attach">

                    <i class="fas fa-paperclip"></i>

                </button>

                <input
                    type="file"
                    id="attachmentInput"
                    hidden
                    multiple
                >


                <div class="message-input-wrapper">

                    <textarea
                        id="messageInput"
                        rows="1"
                        placeholder="Type a message..."
                    ></textarea>

                </div>


                <button
                    class="send-btn"
                    id="sendBtn"
                    title="Send"
                >

                    <i class="fas fa-paper-plane"></i>

                </button>

            </footer>

        </section>

    </main>

</div>


<!-- ADD CONTACT MODAL -->
<div class="modal"
     id="contactModal">

    <div class="modal-content">

        <div class="modal-header">

            <h2>
                <i class="fas fa-user-plus"></i>
                Add Contact
            </h2>

            <button
                class="close-modal"
                id="closeContactModal">

                <i class="fas fa-times"></i>

            </button>

        </div>


        <form id="contactForm">

            <div class="form-group">

                <label>
                    Search by email, phone or name
                </label>

                <input
                    type="text"
                    id="contactIdentifier"
                    placeholder="Enter email, phone or name"
                    required
                >

            </div>


            <div
                class="contact-results"
                id="contactResults">
            </div>


            <button
                class="primary-btn"
                type="submit"
                id="addContactSubmit">

                <i class="fas fa-user-plus"></i>
                Add Contact

            </button>

        </form>

    </div>

</div>


<!-- CONTEXT MENU -->
<div class="context-menu"
     id="contextMenu">

    <button data-action="reply">
        <i class="fas fa-reply"></i>
        Reply
    </button>

    <button data-action="react">
        <i class="far fa-heart"></i>
        React
    </button>

    <button data-action="copy">
        <i class="far fa-copy"></i>
        Copy
    </button>

    <button data-action="edit">
        <i class="fas fa-pen"></i>
        Edit
    </button>

    <button data-action="delete">
        <i class="far fa-trash-alt"></i>
        Delete
    </button>

</div>


<script>
    window.CHAT_CONFIG = {
        currentUserId: <?= $userId ?>,
        getMessagesUrl: "getmessage.php",
        sendMessageUrl: "sendmessage.php",
        addContactUrl: "addcontact.php"
    };
</script>

<script src="../scripts/chats.js"></script>

</body>
</html>