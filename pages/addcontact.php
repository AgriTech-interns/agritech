<?php
// --- DEBUG SWITCH -----------------------------------------------------
// Set to true while you diagnose issues, then back to false before going
// live (it can leak DB/schema details otherwise).
const ADDCONTACT_DEBUG = true;

if (ADDCONTACT_DEBUG) {
    ini_set('display_errors', '0'); // never dump raw HTML into the JSON response
    error_reporting(E_ALL);
}

// Catch anything that would otherwise print HTML/warnings and corrupt
// the JSON response.
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('$pdo was not set up by config.php');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Startup failed',
        'debug'   => ADDCONTACT_DEBUG ? $e->getMessage() : null,
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

// chats.js sends everything as POST FormData, including the action.
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {

/*
|--------------------------------------------------------------------------
| Search users by email, phone or name
|--------------------------------------------------------------------------
| chats.js posts: action=search, identifier=<text>
| chats.js expects back: { success, users: [{ id, fullname, role, ... }] }
*/
if ($action === 'search') {

    // chats.js's field is "identifier" (not "q").
    $query = trim($_POST['identifier'] ?? $_GET['identifier'] ?? '');

    if ($query === '' || mb_strlen($query) < 2) {
        echo json_encode(['success' => true, 'users' => []]);
        exit;
    }

    $like = '%' . $query . '%';

    try {
        $stmt = $pdo->prepare("
            SELECT u.user_id AS id, u.fullName AS fullname, u.email, u.phone, u.profile_pic, u.role,
                   EXISTS (
                       SELECT 1 FROM chat_contacts c
                       WHERE c.user_id = ? AND c.contact_id = u.user_id
                   ) AS already_added
            FROM users u
            WHERE u.user_id != ?
              AND (u.fullName LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)
            ORDER BY u.fullName ASC
            LIMIT 20
        ");
        $stmt->execute([$userId, $userId, $like, $like, $like]);
    } catch (Throwable $e) {
        // Fallback if the phone column doesn't exist.
        $stmt = $pdo->prepare("
            SELECT u.user_id AS id, u.fullName AS fullname, u.email, NULL AS phone, NULL AS profile_pic, u.role,
                   EXISTS (
                       SELECT 1 FROM chat_contacts c
                       WHERE c.user_id = ? AND c.contact_id = u.user_id
                   ) AS already_added
            FROM users u
            WHERE u.user_id != ?
              AND (u.fullName LIKE ? OR u.email LIKE ?)
            ORDER BY u.fullName ASC
            LIMIT 20
        ");
        $stmt->execute([$userId, $userId, $like, $like]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as &$row) {
        $row['id'] = (int) $row['id'];
        $row['already_added'] = (bool) $row['already_added'];
    }

    $debug = null;
    if (ADDCONTACT_DEBUG && empty($users)) {
        $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $sample = $pdo->query('SELECT user_id, fullName, email FROM users LIMIT 5')
                       ->fetchAll(PDO::FETCH_ASSOC);
        $debug = [
            'search_term'       => $query,
            'like_pattern'      => $like,
            'total_users_in_db' => $totalUsers,
            'sample_users'      => $sample,
        ];
    }

    echo json_encode(['success' => true, 'users' => $users, 'debug' => $debug]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Add a contact
|--------------------------------------------------------------------------
| chats.js posts: action=add_contact, contact_id=<id>
*/
if ($action === 'add_contact') {

    $contactId = (int) ($_POST['contact_id'] ?? 0);

    if ($contactId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contact_id']);
        exit;
    }

    if ($contactId === $userId) {
        echo json_encode(['success' => false, 'message' => 'You cannot add yourself']);
        exit;
    }

    $check = $pdo->prepare("SELECT user_id, fullName, email, role FROM users WHERE user_id = ?");
    $check->execute([$contactId]);
    $targetUser = $check->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        $debug = null;
        if (ADDCONTACT_DEBUG) {
            $countStmt = $pdo->query('SELECT COUNT(*) FROM users');
            $debug = [
                'looked_up_contact_id' => $contactId,
                'total_users_in_table' => (int) $countStmt->fetchColumn(),
            ];
        }
        echo json_encode(['success' => false, 'message' => 'User not found', 'debug' => $debug]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO chat_contacts (user_id, contact_id, created_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE user_id = user_id
        ");
        $stmt->execute([$userId, $contactId]);

        // Reverse contact so the other user sees the conversation too.
        $stmt->execute([$contactId, $userId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Could not add contact',
            'debug'   => ADDCONTACT_DEBUG ? $e->getMessage() : null,
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'contact' => [
            'id'       => (int) $targetUser['user_id'],
            'fullname' => $targetUser['fullName'],
            'email'    => $targetUser['email'],
            'role'     => $targetUser['role'],
        ],
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action', 'debug' => ADDCONTACT_DEBUG ? ['action_received' => $action] : null]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'debug'   => ADDCONTACT_DEBUG ? $e->getMessage() . ' @ line ' . $e->getLine() : null,
    ]);
    exit;
}