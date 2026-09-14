 <?php
/**
 * ============================================================================
 * FARMER DASHBOARD — farmer.php
 * ============================================================================
 * This file is intentionally self-contained: it handles authentication,
 * database reads/writes (via AJAX actions used by farmer.js), and renders
 * the dashboard markup — all in one file, per project requirements.
 *
 * SCHEMA NOTE
 * -----------
 * Table/column names below are my best guess at a typical farmer-platform
 * schema (users, farms, crops, farm_activities, messages, notifications,
 * advice). Every query lives inside its own small function in the
 * "DATA ACCESS LAYER" section below, so if your real tables/columns are
 * named differently, you only need to edit that one function — nothing
 * else in this file depends on the schema directly.
 *
 * DB CONNECTION NOTE
 * -------------------
 * This assumes your existing project already exposes a PDO connection.
 * Adjust the require_once path/variable name below to match your project
 * (e.g. it might already be $pdo, $db, or $conn from config/database.php).
 * ============================================================================
 */

// declare(strict_types=1);

// ---------------------------------------------------------------------------
// 1. SESSION / AUTHENTICATION
// ---------------------------------------------------------------------------
require_once __DIR__ . '/config.php';
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'farmer'
) {
    header("Location: login.php");
    exit();
}

$userId  = $_SESSION['user_id'];
$farmerId = $_SESSION['farmer_id'] ?? ($_SESSION['role_id'] ?? null);

// Adjust this to match however your existing login system names the session key.
$currentUserId = $_SESSION['user_id'] ?? $_SESSION['farmer_id'] ?? null;



$currentUserId = (int) $currentUserId;

// ---------------------------------------------------------------------------
// 2. EXISTING DATABASE CONNECTION
// ---------------------------------------------------------------------------
// Replace this include with whatever your project already uses.
// It must ultimately give us a PDO instance in $pdo with
// PDO::ERRMODE_EXCEPTION set (recommended in your existing config).


/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Fail loudly in dev, gracefully in prod — adjust to your project's error handling.
    http_response_code(500);
    die('Database connection ($pdo) was not found. Check the require_once path above.');
}

// ---------------------------------------------------------------------------
// 3. SMALL HELPERS
// ---------------------------------------------------------------------------

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function require_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $sent)) {
        json_out(['ok' => false, 'error' => 'Invalid or expired session token. Please refresh the page.'], 403);
    }
}

function clean(?string $v): string
{
    return htmlspecialchars(trim((string) $v), ENT_QUOTES, 'UTF-8');
}

function esc($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ---------------------------------------------------------------------------
// 4. DATA ACCESS LAYER
//    Every DB touchpoint the dashboard needs, isolated by feature.
//    Ownership (farmer_id / user_id = $currentUserId) is enforced in every
//    query that reads or writes a specific record.
// ---------------------------------------------------------------------------

// --- Farmer profile ---------------------------------------------------------

function getFarmerProfile(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT farmer_id, fullname, email, avatar, farm_name, location_address, farm_size,
                farm_type, main_crops, farming_method, farm_description
         FROM farmers
         WHERE user_id = :uid
         LIMIT 1'
    );
    $stmt->execute([':uid' => $userId]);
    $farmer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$farmer) {
        // Fallback so the page still renders even if the farmer profile row
        // hasn't been created yet — adjust to your onboarding flow.
        return [
            'id' => $userId, 'name' => $_SESSION['user_name'] ?? 'Farmer', 'email' => '',
            'avatar' => '', 'farm_name' => '', 'farm_location' => '', 'farm_size' => '',
            'soil_type' => '', 'main_crops' => '', 'farming_method' => '', 'farm_description' => '',
        ];
    }
    return $farmer;
}

function updateFarmerProfile(PDO $pdo, int $userId, array $f): bool
{
    $stmt = $pdo->prepare(
        'UPDATE farmers SET
            farm_name = :farm_name, farm_location = :farm_location, farm_size = :farm_size,
            soil_type = :soil_type, main_crops = :main_crops, farming_method = :farming_method,
            farm_description = :farm_description, updated_at = NOW()
         WHERE user_id = :uid'
    );
    return $stmt->execute([
        ':farm_name' => $f['farm_name'], ':farm_location' => $f['farm_location'],
        ':farm_size' => $f['farm_size'], ':soil_type' => $f['soil_type'],
        ':main_crops' => $f['main_crops'], ':farming_method' => $f['farming_method'],
        ':farm_description' => $f['farm_description'], ':uid' => $userId,
    ]);
}

// --- Dashboard stats ---------------------------------------------------------

function getDashboardStats(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM crops WHERE farmer_id = ?');
    $stmt->execute([$userId]);
    $totalCrops = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare ("SELECT COUNT(*) FROM crops WHERE farmer_id = ?
     AND status = 'seedling', 'vegetative', 'flowering', 'maturity'");
    // $stmt->execute([$userId]);
    $activeCrops = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE farmer_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingActivities = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $unreadMessages = (int) $stmt->fetchColumn();

    return compact('totalCrops', 'activeCrops', 'pendingActivities', 'unreadMessages');
}

// --- Crops --------------------------------------------------------------------

function listCrops(PDO $pdo, int $userId, string $search = '', string $status = ''): array
{
    $sql = 'SELECT id, name, variety, planting_date, harvest_date, farm_field,
                   quantity, unit, status, notes
            FROM crops WHERE farmer_id = :uid';
    $params = [':uid' => $userId];

    if ($search !== '') {
        $sql .= ' AND (name LIKE :search OR variety LIKE :search)';
        $params[':search'] = "%{$search}%";
    }
    if ($status !== '' && $status !== 'all') {
        $sql .= ' AND status = :status';
        $params[':status'] = $status;
    }
    $sql .= ' ORDER BY planting_date DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function saveCrop(PDO $pdo, int $userId, array $c, ?int $cropId = null): int|false
{
    if ($cropId) {
        // Ownership check baked into the WHERE clause.
        $stmt = $pdo->prepare(
            'UPDATE crops SET name=:name, variety=:variety, planting_date=:planting_date,
                harvest_date=:harvest_date, farm_field=:farm_field, quantity=:quantity,
                unit=:unit, status=:status, notes=:notes, updated_at=NOW()
             WHERE id=:id AND farmer_id=:uid'
        );
        $ok = $stmt->execute([
            ':name' => $c['name'], ':variety' => $c['variety'], ':planting_date' => $c['planting_date'],
            ':harvest_date' => $c['harvest_date'], ':farm_field' => $c['farm_field'],
            ':quantity' => $c['quantity'], ':unit' => $c['unit'], ':status' => $c['status'],
            ':notes' => $c['notes'], ':id' => $cropId, ':uid' => $userId,
        ]);
        return $ok ? $cropId : false;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO crops (farmer_id, name, variety, planting_date, harvest_date, farm_field,
             quantity, unit, status, notes, created_at)
         VALUES (:uid, :name, :variety, :planting_date, :harvest_date, :farm_field,
             :quantity, :unit, :status, :notes, NOW())'
    );
    $ok = $stmt->execute([
        ':uid' => $userId, ':name' => $c['name'], ':variety' => $c['variety'],
        ':planting_date' => $c['planting_date'], ':harvest_date' => $c['harvest_date'],
        ':farm_field' => $c['farm_field'], ':quantity' => $c['quantity'], ':unit' => $c['unit'],
        ':status' => $c['status'], ':notes' => $c['notes'],
    ]);
    return $ok ? (int) $pdo->lastInsertId() : false;
}

function deleteCrop(PDO $pdo, int $userId, int $cropId): bool
{
    $stmt = $pdo->prepare('DELETE FROM crops WHERE id = :id AND farmer_id = :uid');
    $stmt->execute([':id' => $cropId, ':uid' => $userId]);
    return $stmt->rowCount() > 0;
}

// --- Farm activities -----------------------------------------------------------

function listActivities(PDO $pdo, int $userId, string $search = ''): array
{
    $sql = "SELECT fa.id, fa.type, fa.date, fa.description, fa.status, c.name AS crop_name
            FROM farm_activities fa
            LEFT JOIN crops c ON c.id = fa.crop_id
            WHERE fa.farmer_id = :uid";
    $params = [':uid' => $userId];
    if ($search !== '') {
        $sql .= ' AND (fa.type LIKE :s OR fa.description LIKE :s OR c.name LIKE :s)';
        $params[':s'] = "%{$search}%";
    }
    $sql .= ' ORDER BY fa.date DESC LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function saveActivity(PDO $pdo, int $userId, array $a, ?int $activityId = null): int|false
{
    if ($activityId) {
        $stmt = $pdo->prepare(
            'UPDATE farm_activities SET type=:type, crop_id=:crop_id, date=:date,
                description=:description, status=:status, updated_at=NOW()
             WHERE id=:id AND farmer_id=:uid'
        );
        $ok = $stmt->execute([
            ':type' => $a['type'], ':crop_id' => $a['crop_id'] ?: null, ':date' => $a['date'],
            ':description' => $a['description'], ':status' => $a['status'],
            ':id' => $activityId, ':uid' => $userId,
        ]);
        return $ok ? $activityId : false;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO farm_activities (farmer_id, type, crop_id, date, description, status, created_at)
         VALUES (:uid, :type, :crop_id, :date, :description, :status, NOW())'
    );
    $ok = $stmt->execute([
        ':uid' => $userId, ':type' => $a['type'], ':crop_id' => $a['crop_id'] ?: null,
        ':date' => $a['date'], ':description' => $a['description'], ':status' => $a['status'],
    ]);
    return $ok ? (int) $pdo->lastInsertId() : false;
}

function deleteActivity(PDO $pdo, int $userId, int $activityId): bool
{
    $stmt = $pdo->prepare('DELETE FROM farm_activities WHERE id = :id AND farmer_id = :uid');
    $stmt->execute([':id' => $activityId, ':uid' => $userId]);
    return $stmt->rowCount() > 0;
}

// --- Messages (WhatsApp-style) --------------------------------------------------

function listConversations(PDO $pdo, int $userId, string $search = ''): array
{
    // Assumes a `messages` table with sender_id/receiver_id, and a `users`
    // table for the other participant's name/avatar/online state.
    $sql = "SELECT
                u.id AS contact_id, u.name AS contact_name, u.avatar AS contact_avatar,
                u.is_online AS contact_online,
                (SELECT m2.body FROM messages m2
                    WHERE (m2.sender_id = u.id AND m2.receiver_id = :uid1)
                       OR (m2.sender_id = :uid2 AND m2.receiver_id = u.id)
                    ORDER BY m2.created_at DESC LIMIT 1) AS last_message,
                (SELECT m3.created_at FROM messages m3
                    WHERE (m3.sender_id = u.id AND m3.receiver_id = :uid3)
                       OR (m3.sender_id = :uid4 AND m3.receiver_id = u.id)
                    ORDER BY m3.created_at DESC LIMIT 1) AS last_time,
                (SELECT COUNT(*) FROM messages m4
                    WHERE m4.sender_id = u.id AND m4.receiver_id = :uid5 AND m4.is_read = 0) AS unread_count
            FROM users u
            WHERE u.id IN (
                SELECT DISTINCT CASE WHEN sender_id = :uid6 THEN receiver_id ELSE sender_id END
                FROM messages WHERE sender_id = :uid7 OR receiver_id = :uid8
            )";
    $params = [
        ':uid1' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId,
        ':uid5' => $userId, ':uid6' => $userId, ':uid7' => $userId, ':uid8' => $userId,
    ];
    if ($search !== '') {
        $sql .= ' AND u.name LIKE :search';
        $params[':search'] = "%{$search}%";
    }
    $sql .= ' ORDER BY last_time DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listMessages(PDO $pdo, int $userId, int $contactId, int $afterId = 0): array
{
    $sql = 'SELECT id, sender_id, receiver_id, body, is_read, created_at
            FROM messages
            WHERE ((sender_id = :uid AND receiver_id = :cid)
                OR (sender_id = :cid2 AND receiver_id = :uid2))';
    $params = [':uid' => $userId, ':cid' => $contactId, ':cid2' => $contactId, ':uid2' => $userId];
    if ($afterId > 0) {
        $sql .= ' AND id > :after';
        $params[':after'] = $afterId;
    }
    $sql .= ' ORDER BY created_at ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Mark incoming messages from this contact as read.
    $mark = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = :cid AND receiver_id = :uid AND is_read = 0');
    $mark->execute([':cid' => $contactId, ':uid' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sendMessage(PDO $pdo, int $userId, int $contactId, string $body): int|false
{
    $stmt = $pdo->prepare(
        'INSERT INTO messages (sender_id, receiver_id, body, is_read, created_at)
         VALUES (:sid, :rid, :body, 0, NOW())'
    );
    $ok = $stmt->execute([':sid' => $userId, ':rid' => $contactId, ':body' => $body]);
    return $ok ? (int) $pdo->lastInsertId() : false;
}

function deleteMessage(PDO $pdo, int $userId, int $messageId): bool
{
    // Only the sender may delete their own message.
    $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id AND sender_id = :uid');
    $stmt->execute([':id' => $messageId, ':uid' => $userId]);
    return $stmt->rowCount() > 0;
}

// --- Notifications ---------------------------------------------------------------

function listNotifications(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, type, message, is_read, created_at
         FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 30'
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markNotificationRead(PDO $pdo, int $userId, ?int $id = null): bool
{
    if ($id) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid');
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid');
    return $stmt->execute([':uid' => $userId]);
}

// --- Agricultural advice -----------------------------------------------------------

function listAdvice(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, title, category, content, created_at FROM advice ORDER BY created_at DESC LIMIT 20'
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Weather --------------------------------------------------------------------

function getWeather(PDO $pdo, int $userId): ?array
{
    // If your project already stores weather pulls (e.g. from a cron job
    // hitting a weather API), read the latest row here. Returns null when
    // no data source exists yet so the frontend can show an honest empty state.
    try {
        $stmt = $pdo->prepare(
            'SELECT temperature, condition_text, humidity, wind_speed, rain_probability, forecast_date
             FROM weather_data WHERE farmer_id = :uid ORDER BY forecast_date DESC LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        // Table may not exist yet in every project — fail soft.
        return null;
    }
}

// ---------------------------------------------------------------------------
// 5. AJAX ACTION ROUTER
//    farmer.js calls this same file with ?action=... for every dynamic
//    operation. Handled and exited before any HTML is rendered.
// ---------------------------------------------------------------------------

$action = $_REQUEST['action'] ?? null;

if ($action !== null) {
    try {
        switch ($action) {

            // ---- Dashboard ----
            case 'get_dashboard_stats':
                json_out(['ok' => true, 'data' => getDashboardStats($pdo, $currentUserId)]);
                break;

            // ---- Farm profile ----
            case 'get_farm':
                json_out(['ok' => true, 'data' => getFarmerProfile($pdo, $currentUserId)]);
                break;

            case 'update_farm':
                require_csrf();
                $f = [
                    'farm_name' => clean($_POST['farm_name'] ?? ''),
                    'farm_location' => clean($_POST['farm_location'] ?? ''),
                    'farm_size' => clean($_POST['farm_size'] ?? ''),
                    'soil_type' => clean($_POST['soil_type'] ?? ''),
                    'main_crops' => clean($_POST['main_crops'] ?? ''),
                    'farming_method' => clean($_POST['farming_method'] ?? ''),
                    'farm_description' => clean($_POST['farm_description'] ?? ''),
                ];
                if ($f['farm_name'] === '') {
                    json_out(['ok' => false, 'error' => 'Farm name is required.'], 422);
                }
                $ok = updateFarmerProfile($pdo, $currentUserId, $f);
                json_out(['ok' => $ok, 'message' => $ok ? 'Farm information updated successfully.' : 'Could not save changes.']);
                break;

            // ---- Crops ----
            case 'get_crops':
                $search = clean($_GET['q'] ?? '');
                $status = clean($_GET['status'] ?? '');
                json_out(['ok' => true, 'data' => listCrops($pdo, $currentUserId, $search, $status)]);
                break;

            case 'save_crop':
                require_csrf();
                $c = [
                    'name' => clean($_POST['name'] ?? ''),
                    'variety' => clean($_POST['variety'] ?? ''),
                    'planting_date' => clean($_POST['planting_date'] ?? ''),
                    'harvest_date' => clean($_POST['harvest_date'] ?? ''),
                    'farm_field' => clean($_POST['farm_field'] ?? ''),
                    'quantity' => clean($_POST['quantity'] ?? ''),
                    'unit' => clean($_POST['unit'] ?? ''),
                    'status' => clean($_POST['status'] ?? 'growing'),
                    'notes' => clean($_POST['notes'] ?? ''),
                ];
                if ($c['name'] === '' || $c['planting_date'] === '') {
                    json_out(['ok' => false, 'error' => 'Crop name and planting date are required.'], 422);
                }
                $cropId = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
                $result = saveCrop($pdo, $currentUserId, $c, $cropId);
                json_out(['ok' => (bool) $result, 'id' => $result, 'message' => $cropId ? 'Crop updated successfully.' : 'Crop added successfully.']);
                break;

            case 'delete_crop':
                require_csrf();
                $id = (int) ($_POST['id'] ?? 0);
                $ok = $id > 0 && deleteCrop($pdo, $currentUserId, $id);
                json_out(['ok' => $ok, 'message' => $ok ? 'Crop deleted.' : 'Could not delete that crop.']);
                break;

            // ---- Activities ----
            case 'get_activities':
                $search = clean($_GET['q'] ?? '');
                json_out(['ok' => true, 'data' => listActivities($pdo, $currentUserId, $search)]);
                break;

            case 'save_activity':
                require_csrf();
                $a = [
                    'type' => clean($_POST['type'] ?? ''),
                    'crop_id' => (int) ($_POST['crop_id'] ?? 0),
                    'date' => clean($_POST['date'] ?? ''),
                    'description' => clean($_POST['description'] ?? ''),
                    'status' => clean($_POST['status'] ?? 'pending'),
                ];
                if ($a['type'] === '' || $a['date'] === '') {
                    json_out(['ok' => false, 'error' => 'Activity type and date are required.'], 422);
                }
                $activityId = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
                $result = saveActivity($pdo, $currentUserId, $a, $activityId);
                json_out(['ok' => (bool) $result, 'id' => $result, 'message' => $activityId ? 'Activity updated.' : 'Activity logged.']);
                break;

            case 'delete_activity':
                require_csrf();
                $id = (int) ($_POST['id'] ?? 0);
                $ok = $id > 0 && deleteActivity($pdo, $currentUserId, $id);
                json_out(['ok' => $ok, 'message' => $ok ? 'Activity deleted.' : 'Could not delete that activity.']);
                break;

            // ---- Messages ----
            case 'get_conversations':
                $search = clean($_GET['q'] ?? '');
                json_out(['ok' => true, 'data' => listConversations($pdo, $currentUserId, $search)]);
                break;

            case 'get_messages':
                $contactId = (int) ($_GET['contact_id'] ?? 0);
                $after = (int) ($_GET['after_id'] ?? 0);
                if ($contactId <= 0) {
                    json_out(['ok' => false, 'error' => 'Missing conversation.'], 422);
                }
                json_out(['ok' => true, 'data' => listMessages($pdo, $currentUserId, $contactId, $after)]);
                break;

            case 'send_message':
                require_csrf();
                $contactId = (int) ($_POST['contact_id'] ?? 0);
                $body = trim((string) ($_POST['body'] ?? ''));
                if ($contactId <= 0 || $body === '') {
                    json_out(['ok' => false, 'error' => 'A message needs a recipient and some text.'], 422);
                }
                $id = sendMessage($pdo, $currentUserId, $contactId, clean($body));
                json_out(['ok' => (bool) $id, 'id' => $id]);
                break;

            case 'delete_message':
                require_csrf();
                $id = (int) ($_POST['id'] ?? 0);
                $ok = $id > 0 && deleteMessage($pdo, $currentUserId, $id);
                json_out(['ok' => $ok]);
                break;

            // ---- Notifications ----
            case 'get_notifications':
                json_out(['ok' => true, 'data' => listNotifications($pdo, $currentUserId)]);
                break;

            case 'mark_notification_read':
                require_csrf();
                $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
                $ok = markNotificationRead($pdo, $currentUserId, $id);
                json_out(['ok' => $ok]);
                break;

            // ---- Advice ----
            case 'get_advice':
                json_out(['ok' => true, 'data' => listAdvice($pdo)]);
                break;

            // ---- Weather ----
            case 'get_weather':
                json_out(['ok' => true, 'data' => getWeather($pdo, $currentUserId)]);
                break;

            default:
                json_out(['ok' => false, 'error' => 'Unknown action.'], 400);
        }
    } catch (PDOException $e) {
        // Avoid leaking schema/query details to the client.
        json_out(['ok' => false, 'error' => 'A database error occurred. Please try again.'], 500);
    }
    exit;
}


// ---------------------------------------------------------------------------
// 6. INITIAL PAGE DATA (first paint — everything after this is via AJAX)
// ---------------------------------------------------------------------------

$farmer = getFarmerProfile($pdo, $currentUserId);
$stats = getDashboardStats($pdo, $currentUserId);
$farmerName = trim($farmer['fullname'] ?? $farmer['name'] ?? '');

if ($farmerName === '') {
    $farmerName = $_SESSION['user_name'] ?? 'Farmer';
}
$farmerInitial = mb_strtoupper(mb_substr($farmerName, 0, 1));

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farmer Dashboard | AgriTech</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../Assets/farmer.css">
<link rel="stylesheet" href="../Assets/index.css">
</head>
<body id="body" data-theme="light">

<!-- ============ TOAST CONTAINER ============ -->
<div id="toastContainer" class="toast-container" ></div>

<!-- ============ MOBILE OVERLAY ============ -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<div class="app-wrapper" id="appWrapper">



  <!-- ============================================================
       SIDEBAR
  ============================================================= -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-box" id="logoBox">
        <span class="logo-text">Agri<span>Tech</span></span>
      </div>
      <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close sidebar">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="sidebar-profile" id="sidebarProfile">
      <div class="sidebar-profile-img">
         <?php if (!empty($farmer['avatar'])): ?>
          <img src="<?= esc($farmer['avatar']) ?>" alt="">
        <?php else: ?>
          <span><?= esc($farmerInitial) ?></span>
        <?php endif; ?>
      </div>
      <div class="sidebar-profile-info">
        <p class="sidebar-profile-name"><?= esc($farmerName) ?></p>
        <p class="sidebar-profile-role"><?= esc($farmer['farm_name'] ?: ' ') ?></p>
      </div>
    </div>
    

    <nav class="sidebar-nav" id="sidebarNav">
      <p class="nav-section-title">Main</p>
      <ul class="nav-list">
        <li class="nav-item active" data-section="dashboard">
          <a href="#dashboard" class="nav-link">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item" data-section="myfarm">
          <a href="#myfarm" class="nav-link">
            <i class="fa-solid fa-tractor"></i>
            <span>My Farm</span>
          </a>
        </li>
        <li class="nav-item" data-section="crops">
          <a href="#crops" class="nav-link">
            <i class="fa-solid fa-wheat-awn"></i>
            <span>Crops</span>
          </a>
        </li>
        <li class="nav-item" data-section="weather">
          <a href="#weather" class="nav-link">
            <i class="fa-solid fa-cloud-sun-rain"></i>
            <span>Weather</span>
          </a>
        </li>
        <li class="nav-item" data-section="activities">
          <a href="#activities" class="nav-link">
            <i class="fa-solid fa-list-check"></i>
            <span>Farm Activities</span>
          </a>
        </li>
        <li class="nav-item" data-section="calendar">
          <a href="#calendar" class="nav-link">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Farm Calendar</span>
          </a>
        </li>
      </ul>

      <p class="nav-section-title">Marketplace</p>
      <ul class="nav-list">
        <li class="nav-item" data-section="marketplace">
          <a href="marketplace.php" class="nav-link">
            <i class="fa-solid fa-store"></i>
            <span>Marketplace</span>
          </a>
        </li>
        <li class="nav-item" data-section="myproducts">
          <a href="#myproducts" class="nav-link">
            <i class="fa-solid fa-box-open"></i>
            <span>My Products</span>
          </a>
        </li>
      </ul>

      <p class="nav-section-title">Support &amp; Insights</p>
      <ul class="nav-list">
        <li class="nav-item" data-section="experts">
          <a href="#experts" class="nav-link">
            <i class="fa-solid fa-user-graduate"></i>
            <span>Agricultural Experts</span>
          </a>
        </li>
        <li class="nav-item" data-section="aiassistant">
          <a href="ai.php" class="nav-link">
            <i class="fa-solid fa-robot"></i>
            <span>AI Farming Assistant</span>
          </a>
        </li>
        <li class="nav-item" data-section="messages">
          <a href="chats.php" class="nav-link">
            <i class="fa-solid fa-comments"></i>
            <span>Messages</span>
          </a>
        </li>
        <li class="nav-item" data-section="notifications">
          <a href="#notifications" class="nav-link">
            <i class="fa-solid fa-bell"></i>
            <span>Notifications</span>
          </a>
        </li>
      </ul>

      <p class="nav-section-title">Management</p>
      <ul class="nav-list">
        <li class="nav-item" data-section="reports">
          <a href="#reports" class="nav-link">
            <i class="fa-solid fa-chart-line"></i>
            <span>Reports</span>
          </a>
        </li>
        <li class="nav-item" data-section="expenses">
          <a href="#expenses" class="nav-link">
            <i class="fa-solid fa-coins"></i>
            <span>Expenses</span>
          </a>
        </li>
      </ul>

      <p class="nav-section-title">Account</p>
      <ul class="nav-list">
        <li class="nav-item" data-section="profile">
          <a href="profile.php" class="nav-link">
            <i class="fa-solid fa-user"></i>
            <span>Profile</span>
          </a>
        </li>
    
        <li class="nav-item" data-section="help">
          <a href="help.php" class="nav-link">
            <i class="fa-solid fa-circle-question"></i>
            <span>Help</span>
          </a>
        </li>
        <li class="nav-item" id="logoutNavItem">
          <a href="logout.php" class="nav-link" id="logoutBtn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </nav>
  </aside>

  <!-- ============================================================
       MAIN CONTENT WRAPPER
  ============================================================= -->
  <div class="main-content" id="mainContent">

    <!-- ============ TOP NAVBAR ============ -->
    <header class="topbar" id="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle sidebar">
          <i class="fa-solid fa-bars"></i>
        </button>
        <h1 class="page-title" id="pageTitle">Dashboard</h1>
      </div>

      <div class="topbar-center">
        <div class="global-search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="globalSearchInput" placeholder="Search farms, crops, products, experts...">
        </div>
      </div>

      <div class="topbar-right">
        <span class="current-date" id="currentDate">Loading date...</span>

        <button class="icon-btn" id="themeToggleBtn" aria-label="Toggle dark mode">
          <i class="fa-solid fa-moon"></i>
        </button>

        <button class="icon-btn" id="messageBtn" aria-label="Messages">
          <i class="fa-solid fa-envelope"></i>
        </button>

        <button class="icon-btn" id="notificationBtn" aria-label="Notifications">
          <i class="fa-solid fa-bell"></i>
        </button>
  
      </div>
    </header>

    <!-- Mobile bottom navigation -->
    <nav class="mobile-nav" id="mobileNav">
      <a href="#dashboard" class="mobile-nav-item active" data-section="dashboard">
        <i class="fa-solid fa-gauge-high"></i><span>Home</span>
      </a>
      <a href="#myfarm" class="mobile-nav-item" data-section="myfarm">
        <i class="fa-solid fa-tractor"></i><span>Farm</span>
      </a>
      <a href="#marketplace" class="mobile-nav-item" data-section="marketplace">
        <i class="fa-solid fa-store"></i><span>Market</span>
      </a>
      <a href="#aiassistant" class="mobile-nav-item" data-section="aiassistant">
        <i class="fa-solid fa-robot"></i><span>AI</span>
      </a>
      <a href="#profile" class="mobile-nav-item" data-section="profile">
        <i class="fa-solid fa-user"></i><span>Profile</span>
      </a>
    </nav>

    <!-- ============================================================
         DASHBOARD MAIN AREA (sections switched via JS)
    ============================================================= -->
    <main class="dashboard-main" id="dashboardMain">

      <!-- ================= 1. DASHBOARD HOME ================= -->
      <section class="content-section active" id="dashboard">

        <div class="welcome-card" id="welcomeCard">
          <div class="welcome-text">
            <h2 id="welcomeGreeting">Good Morning, Farmer Nelly!</h2>
            <p id="welcomeMessage">"The farmer has to be an optimist or he wouldn't still be a farmer." Keep growing, keep thriving today.</p>
          </div>
          <div class="welcome-illustration">
            <i class="fa-solid fa-sun"></i>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid" id="statsGrid">
          <div class="stat-card" id="statTotalFarms">
            <div class="stat-icon farms"><i class="fa-solid fa-tractor"></i></div>
            <div class="stat-info">
              <h3 id="statTotalFarmsValue">4</h3>
              <p>Total Farms</p>
              <span class="trend up"><i class="fa-solid fa-arrow-trend-up"></i> +1 this season</span>
            </div>
          </div>

          <div class="stat-card" id="statTotalCrops">
            <div class="stat-icon crops"><i class="fa-solid fa-wheat-awn"></i></div>
            <div class="stat-info">
              <h3 id="statTotalCropsValue">12</h3>
              <p>Total Crops</p>
              <span class="trend up"><i class="fa-solid fa-arrow-trend-up"></i> +3 new</span>
            </div>
          </div>

          <div class="stat-card" id="statActiveCrops">
            <div class="stat-icon active"><i class="fa-solid fa-leaf"></i></div>
            <div class="stat-info">
              <h3 id="statActiveCropsValue">8</h3>
              <p>Active Crops</p>
              <span class="trend neutral"><i class="fa-solid fa-minus"></i> stable</span>
            </div>
          </div>

          <div class="stat-card" id="statHarvestReady">
            <div class="stat-icon harvest"><i class="fa-solid fa-basket-shopping"></i></div>
            <div class="stat-info">
              <h3 id="statHarvestReadyValue">3</h3>
              <p>Harvest Ready</p>
              <span class="trend up"><i class="fa-solid fa-arrow-trend-up"></i> ready this week</span>
            </div>
          </div>

          <div class="stat-card" id="statMyProducts">
            <div class="stat-icon products"><i class="fa-solid fa-box-open"></i></div>
            <div class="stat-info">
              <h3 id="statMyProductsValue">9</h3>
              <p>My Products</p>
              <span class="trend up"><i class="fa-solid fa-arrow-trend-up"></i> +2 listed</span>
            </div>
          </div>

          <div class="stat-card" id="statPendingOrders">
            <div class="stat-icon orders"><i class="fa-solid fa-truck-fast"></i></div>
            <div class="stat-info">
              <h3 id="statPendingOrdersValue">5</h3>
              <p>Pending Orders</p>
              <span class="trend down"><i class="fa-solid fa-arrow-trend-down"></i> -1 fulfilled</span>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-block">
          <div class="section-block-header">
            <h3>Quick Actions</h3>
          </div>
          <div class="quick-actions-grid" id="quickActionsGrid">
            <button class="quick-action-btn" id="qaAddFarm">
              <i class="fa-solid fa-plus"></i><span>Add Farm</span>
            </button>
            <button class="quick-action-btn" id="qaAddCrop">
              <i class="fa-solid fa-plus"></i><span>Add Crop</span>
            </button>
            <button class="quick-action-btn" id="qaRecordActivity">
              <i class="fa-solid fa-clipboard-list"></i><span>Record Activity</span>
            </button>
            <button class="quick-action-btn" id="qaAddProduct">
              <i class="fa-solid fa-plus"></i><span>Add Product</span>
            </button>
            <button class="quick-action-btn" id="qaFindExpert">
              <i class="fa-solid fa-user-graduate"></i><span>Find Expert</span>
            </button>
            <button class="quick-action-btn" id="qaViewWeather">
              <i class="fa-solid fa-cloud-sun"></i><span>View Weather</span>
            </button>
            <button class="quick-action-btn" id="qaCreateReport">
              <i class="fa-solid fa-file-lines"></i><span>Create Report</span>
            </button>
          </div>
        </div>

        <!-- Dashboard bottom widgets -->
        <div class="dashboard-widgets-grid">
          <div class="widget-card" id="dashboardWeatherWidget">
            <div class="widget-header">
              <h3><i class="fa-solid fa-cloud-sun-rain"></i> Today's Weather</h3>
              <a href="#weather" class="widget-link nav-link-inline" data-section="weather">Full forecast</a>
            </div>
            <div class="widget-body" id="dashboardWeatherBody">
              <p>Buea &mdash; 26°C, Partly Cloudy. Light rain expected in the afternoon.</p>
            </div>
          </div>

          <div class="widget-card" id="dashboardActivityWidget">
            <div class="widget-header">
              <h3><i class="fa-solid fa-list-check"></i> Recent Activities</h3>
              <a href="#activities" class="widget-link nav-link-inline" data-section="activities">View all</a>
            </div>
            <div class="widget-body" id="dashboardActivityBody"></div>
          </div>

          <div class="widget-card" id="dashboardNotificationsWidget">
            <div class="widget-header">
              <h3><i class="fa-solid fa-bell"></i> Latest Notifications</h3>
              <a href="#notifications" class="widget-link nav-link-inline" data-section="notifications">View all</a>
            </div>
            <div class="widget-body" id="dashboardNotifBody"></div>
          </div>
        </div>
      </section>

      <!-- ================= 2. MY FARM ================= -->
      <section class="content-section" id="myfarm">
        <div class="section-heading">
          <div>
            <h2>My Farm</h2>
            <p>Manage all of your registered farms in one place.</p>
          </div>
          <button class="btn-primary" id="addFarmBtn"><i class="fa-solid fa-plus"></i> Add Farm</button>
        </div>

        <div class="filter-bar">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="farmSearchInput" placeholder="Search farms by name or location...">
          </div>
          <select id="farmStatusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="fallow">Fallow</option>
            <option value="preparation">In Preparation</option>
          </select>
        </div>

        <div class="farm-cards-grid" id="farmCardsContainer"></div>

        <div class="empty-state" id="farmEmptyState" hidden>
          <i class="fa-solid fa-tractor"></i>
          <h3>No farms found</h3>
          <p>Add your first farm to start managing your agricultural operations.</p>
          <button class="btn-primary" id="emptyStateAddFarmBtn"><i class="fa-solid fa-plus"></i> Add Farm</button>
        </div>
      </section>

      <!-- ================= 3. CROPS ================= -->
      <section class="content-section" id="crops">
        <div class="section-heading">
          <div>
            <h2>Crops</h2>
            <p>Track growth stages and health of every crop.</p>
          </div>
          <button class="btn-primary" id="addCropBtn"><i class="fa-solid fa-plus"></i> Add Crop</button>
        </div>

        <div class="filter-bar">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="cropSearchInput" placeholder="Search crops...">
          </div>
          <select id="cropFarmFilter" class="filter-select">
            <option value="all">All Farms</option>
          </select>
          <select id="cropStageFilter" class="filter-select">
            <option value="all">All Stages</option>
            <option value="seedling">Seedling</option>
            <option value="vegetative">Vegetative</option>
            <option value="flowering">Flowering</option>
            <option value="maturity">Maturity</option>
            <option value="harvest">Harvest Ready</option>
          </select>
          <div class="view-toggle">
            <button class="view-toggle-btn active" id="cropGridViewBtn" aria-label="Grid view"><i class="fa-solid fa-grip"></i></button>
            <button class="view-toggle-btn" id="cropTableViewBtn" aria-label="Table view"><i class="fa-solid fa-table-list"></i></button>
          </div>
        </div>

        <div class="crop-cards-grid" id="cropCardsContainer"></div>

        <div class="table-wrapper" id="cropTableWrapper" hidden>
          <table class="data-table" id="cropTable">
            <thead>
              <tr>
                <th>Crop</th><th>Variety</th><th>Farm</th><th>Planted</th>
                <th>Expected Harvest</th><th>Stage</th><th>Quantity</th>
                <th>Health</th><th>Progress</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="cropTableBody"></tbody>
          </table>
        </div>

        <div class="empty-state" id="cropEmptyState" hidden>
          <i class="fa-solid fa-wheat-awn"></i>
          <h3>No crops found</h3>
          <p>Start tracking a crop to see it appear here.</p>
          <button class="btn-primary" id="emptyStateAddCropBtn"><i class="fa-solid fa-plus"></i> Add Crop</button>
        </div>
      </section>

      <!-- ================= 4. WEATHER ================= -->
      <section class="content-section" id="weather">
        <div class="section-heading">
          <div>
            <h2>Weather</h2>
            <p>Stay ahead of the weather for smarter farm decisions.</p>
          </div>
          <div class="location-selector">
            <i class="fa-solid fa-location-dot"></i>
            <select id="weatherLocationSelect">
              <option value="buea">Buea, Cameroon</option>
              <option value="douala">Douala, Cameroon</option>
              <option value="bamenda">Bamenda, Cameroon</option>
              <option value="yaounde">Yaoundé, Cameroon</option>
              <option value="limbe">Limbe, Cameroon</option>
            </select>
          </div>
        </div>

        <div class="weather-main-card" id="weatherMainCard">
          <div class="weather-main-left">
            <p class="weather-location" id="weatherLocationText">Buea, South West, Cameroon</p>
            <div class="weather-temp-row">
              <i class="fa-solid fa-cloud-sun weather-big-icon" id="weatherMainIcon"></i>
              <h2 id="weatherTempValue">26°C</h2>
            </div>
            <p class="weather-condition" id="weatherConditionText">Partly Cloudy</p>
          </div>
          <div class="weather-main-right" id="weatherMetricsGrid">
            <div class="weather-metric"><i class="fa-solid fa-droplet"></i><span id="weatherHumidity">78%</span><small>Humidity</small></div>
            <div class="weather-metric"><i class="fa-solid fa-wind"></i><span id="weatherWind">14 km/h</span><small>Wind Speed</small></div>
            <div class="weather-metric"><i class="fa-solid fa-cloud-rain"></i><span id="weatherRainChance">62%</span><small>Rain Probability</small></div>
            <div class="weather-metric"><i class="fa-solid fa-cloud"></i><span id="weatherCloud">54%</span><small>Cloud Coverage</small></div>
            <div class="weather-metric"><i class="fa-solid fa-sun"></i><span id="weatherUV">6 (High)</span><small>UV Index</small></div>
            <div class="weather-metric"><i class="fa-solid fa-sunrise"></i><span id="weatherSunrise">6:12 AM</span><small>Sunrise</small></div>
            <div class="weather-metric"><i class="fa-solid fa-sunset"></i><span id="weatherSunset">6:34 PM</span><small>Sunset</small></div>
          </div>
        </div>

        <div class="section-block">
          <div class="section-block-header"><h3>7-Day Forecast</h3></div>
          <div class="forecast-row" id="forecastRow"></div>
        </div>

        <div class="section-block">
          <div class="section-block-header"><h3>Weather Alerts</h3></div>
          <div class="weather-alerts-grid" id="weatherAlertsGrid">
            <div class="alert-card heavy-rain" data-alert="heavy-rain">
              <i class="fa-solid fa-cloud-showers-heavy"></i>
              <div><h4>Heavy Rain</h4><p>Possible heavy rainfall in the next 48 hours. Secure drainage on low farms.</p></div>
            </div>
            <div class="alert-card extreme-heat" data-alert="extreme-heat">
              <i class="fa-solid fa-temperature-high"></i>
              <div><h4>Extreme Heat</h4><p>Temperatures may exceed 34°C. Increase irrigation frequency.</p></div>
            </div>
            <div class="alert-card strong-wind" data-alert="strong-wind">
              <i class="fa-solid fa-wind"></i>
              <div><h4>Strong Wind</h4><p>Gusts up to 40 km/h expected. Stake young plants and seedlings.</p></div>
            </div>
            <div class="alert-card storm" data-alert="storm">
              <i class="fa-solid fa-cloud-bolt"></i>
              <div><h4>Storm Warning</h4><p>Thunderstorms possible this weekend. Avoid spraying activities.</p></div>
            </div>
            <div class="alert-card drought" data-alert="drought">
              <i class="fa-solid fa-sun-plant-wilt"></i>
              <div><h4>Drought Watch</h4><p>Below-average rainfall this month. Monitor soil moisture closely.</p></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 5. FARM ACTIVITIES ================= -->
      <section class="content-section" id="activities">
        <div class="section-heading">
          <div>
            <h2>Farm Activities</h2>
            <p>Log and track everything happening on your farms.</p>
          </div>
          <button class="btn-primary" id="addActivityBtn"><i class="fa-solid fa-plus"></i> Add Activity</button>
        </div>

        <div class="filter-bar">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="activitySearchInput" placeholder="Search activities...">
          </div>
          <select id="activityTypeFilter" class="filter-select">
            <option value="all">All Types</option>
            <option value="planting">Planting</option>
            <option value="fertilizing">Fertilizing</option>
            <option value="irrigation">Irrigation</option>
            <option value="weeding">Weeding</option>
            <option value="pest-control">Pest Control</option>
            <option value="spraying">Spraying</option>
            <option value="pruning">Pruning</option>
            <option value="harvesting">Harvesting</option>
          </select>
        </div>

        <div class="table-wrapper">
          <table class="data-table" id="activitiesTable">
            <thead>
              <tr>
                <th>Activity</th><th>Crop</th><th>Farm</th><th>Date</th>
                <th>Cost</th><th>Status</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="activitiesTableBody"></tbody>
          </table>
        </div>

        <div class="empty-state" id="activityEmptyState" hidden>
          <i class="fa-solid fa-list-check"></i>
          <h3>No activities recorded</h3>
          <p>Record a farm activity to build your timeline.</p>
        </div>
      </section>

      <!-- ================= 6. FARM CALENDAR ================= -->
      <section class="content-section" id="calendar">
        <div class="section-heading">
          <div>
            <h2>Farm Calendar</h2>
            <p>Plan planting, spraying, harvest dates and expert visits.</p>
          </div>
          <button class="btn-primary" id="addEventBtn"><i class="fa-solid fa-plus"></i> Add Event</button>
        </div>

        <div class="calendar-layout">
          <div class="calendar-card">
            <div class="calendar-header">
              <button class="icon-btn" id="prevMonthBtn" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button>
              <h3 id="currentMonthLabel">August 2026</h3>
              <button class="icon-btn" id="nextMonthBtn" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="calendar-weekdays">
              <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
          </div>

          <div class="upcoming-events-card">
            <h3>Upcoming Events</h3>
            <div class="upcoming-events-list" id="upcomingEventsList"></div>
          </div>
        </div>
      </section>

      <!-- ================= 7. MARKETPLACE ================= -->
      <section class="content-section" id="marketplace">
        <div class="section-heading">
          <div>
            <h2>Marketplace</h2>
            <p>Discover produce, seeds, fertilizers and equipment from other farmers.</p>
          </div>
          <button class="btn-primary" id="viewCartBtn">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <span class="cart-count-badge" id="cartCountBadge">0</span>
          </button>
        </div>

        <div class="filter-bar">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="marketplaceSearchInput" placeholder="Search products...">
          </div>
          <select id="marketplaceCategoryFilter" class="filter-select">
            <option value="all">All Categories</option>
            <option value="maize">Maize</option>
            <option value="cocoa">Cocoa</option>
            <option value="cassava">Cassava</option>
            <option value="rice">Rice</option>
            <option value="vegetables">Vegetables</option>
            <option value="fruits">Fruits</option>
            <option value="seeds">Seeds</option>
            <option value="fertilizers">Fertilizers</option>
            <option value="equipment">Equipment</option>
            <option value="livestock">Livestock Products</option>
          </select>
          <select id="marketplacePriceFilter" class="filter-select">
            <option value="all">Any Price</option>
            <option value="low">Under 10,000 XAF</option>
            <option value="mid">10,000 - 50,000 XAF</option>
            <option value="high">Above 50,000 XAF</option>
          </select>
        </div>

        <div class="marketplace-grid" id="marketplaceGrid"></div>
      </section>

      <!-- ================= 8. SHOPPING CART (panel) ================= -->
      <section class="content-section" id="cart">
        <div class="section-heading">
          <div>
            <h2>Shopping Cart</h2>
            <p>Review your items before placing an order.</p>
          </div>
          <button class="btn-secondary" id="clearCartBtn"><i class="fa-solid fa-trash"></i> Clear Cart</button>
        </div>

        <div class="cart-layout">
          <div class="cart-items-list" id="cartItemsList"></div>

          <div class="cart-summary-card">
            <h3>Order Summary</h3>
            <div class="cart-summary-row">
              <span>Subtotal</span>
              <span id="cartSubtotal">0 XAF</span>
            </div>
            <div class="cart-summary-row">
              <span>Delivery</span>
              <span id="cartDelivery">2,000 XAF</span>
            </div>
            <div class="cart-summary-row total">
              <span>Total</span>
              <span id="cartTotal">0 XAF</span>
            </div>
            <button class="btn-primary full-width" id="placeOrderBtn"><i class="fa-solid fa-check"></i> Place Order</button>
          </div>
        </div>

        <div class="empty-state" id="cartEmptyState" hidden>
          <i class="fa-solid fa-cart-shopping"></i>
          <h3>Your cart is empty</h3>
          <p>Browse the marketplace to add products to your cart.</p>
          <button class="btn-primary nav-link-inline" data-section="marketplace">Go to Marketplace</button>
        </div>
      </section>

      <!-- ================= 9. MY PRODUCTS ================= -->
      <section class="content-section" id="myproducts">
        <div class="section-heading">
          <div>
            <h2>My Products</h2>
            <p>Manage the products you're selling on the marketplace.</p>
          </div>
          <button class="btn-primary" id="addProductBtn"><i class="fa-solid fa-plus"></i> Add Product</button>
        </div>

        <div class="filter-bar">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="myProductsSearchInput" placeholder="Search your products...">
          </div>
          <select id="myProductsStatusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="sold">Sold</option>
            <option value="deactivated">Deactivated</option>
          </select>
        </div>

        <div class="table-wrapper">
          <table class="data-table" id="myProductsTable">
            <thead>
              <tr>
                <th>Image</th><th>Name</th><th>Category</th><th>Price</th>
                <th>Quantity</th><th>Location</th><th>Status</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="myProductsTableBody"></tbody>
          </table>
        </div>

        <div class="empty-state" id="myProductsEmptyState" hidden>
          <i class="fa-solid fa-box-open"></i>
          <h3>No products listed</h3>
          <p>Add a product to start selling on the marketplace.</p>
        </div>
      </section>

      <!-- ================= 10. AGRICULTURAL EXPERTS ================= -->
      <section class="content-section" id="experts">
        <div class="section-heading">
          <div>
            <h2>Agricultural Experts</h2>
            <p>Connect with certified agronomists and specialists.</p>
          </div>
        </div>

        <div class="filter-bar">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="expertSearchInput" placeholder="Search experts by specialization...">
          </div>
          <select id="expertAvailabilityFilter" class="filter-select">
            <option value="all">Any Availability</option>
            <option value="available">Available Now</option>
            <option value="busy">Busy</option>
          </select>
        </div>

        <div class="experts-grid" id="expertsGrid"></div>
      </section>

      <!-- ================= 11. AI FARMING ASSISTANT ================= -->
      <section class="content-section" id="aiassistant">
        <div class="section-heading">
          <div>
            <h2>AI Farming Assistant</h2>
            <p>Ask anything about crops, weather, pests, or best practices.</p>
          </div>
          <button class="btn-secondary" id="clearChatBtn"><i class="fa-solid fa-trash"></i> Clear Chat</button>
        </div>

        <div class="ai-chat-layout">
          <div class="ai-chat-window">
            <div class="ai-chat-header">
              <div class="ai-avatar"><i class="fa-solid fa-robot"></i></div>
              <div>
                <h4>Agro-Tech AI Assistant</h4>
                <small>Online &middot; Ready to help</small>
              </div>
            </div>

            <div class="ai-chat-history" id="aiChatHistory"></div>

            <div class="ai-chat-input-row">
              <input type="text" id="aiChatInput" placeholder="Type your farming question...">
              <button class="icon-btn send-btn" id="aiSendBtn" aria-label="Send message"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
          </div>

          <div class="ai-suggestions-card">
            <h4>Suggested Questions</h4>
            <div class="suggested-questions-list" id="suggestedQuestionsList">
              <button class="suggested-question-btn">Why are my maize leaves turning yellow?</button>
              <button class="suggested-question-btn">When should I plant maize?</button>
              <button class="suggested-question-btn">How can I control pests?</button>
              <button class="suggested-question-btn">What fertilizer should I use?</button>
              <button class="suggested-question-btn">Is it good to spray today?</button>
              <button class="suggested-question-btn">How can I improve my soil?</button>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 12. MESSAGES ================= -->
      <section class="content-section" id="messages">
        <div class="section-heading">
          <div>
            <h2>Messages</h2>
            <p>Chat with experts, buyers and Agro-Tech support.</p>
          </div>
        </div>

        <div class="messages-layout">
          <div class="conversation-list-panel">
            <div class="search-box conversation-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="conversationSearchInput" placeholder="Search conversations...">
            </div>
            <div class="conversation-list" id="conversationList"></div>
          </div>

          <div class="active-conversation-panel" id="activeConversationPanel">
            <div class="conversation-header" id="conversationHeader">
              <div class="conversation-contact">
                <img src="https://i.pravatar.cc/100?img=12" alt="" id="conversationAvatar">
                <div>
                  <h4 id="conversationContactName">Select a conversation</h4>
                  <small id="conversationOnlineStatus">&nbsp;</small>
                </div>
              </div>
            </div>

            <div class="conversation-messages" id="conversationMessages"></div>

            <div class="conversation-input-row">
              <input type="text" id="conversationMessageInput" placeholder="Type a message...">
              <button class="icon-btn send-btn" id="conversationSendBtn" aria-label="Send message"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 13. NOTIFICATIONS ================= -->
      <section class="content-section" id="notifications">
        <div class="section-heading">
          <div>
            <h2>Notifications</h2>
            <p>Everything that needs your attention.</p>
          </div>
          <div class="btn-group">
            <button class="btn-secondary" id="markAllReadBtn"><i class="fa-solid fa-check-double"></i> Mark all as read</button>
          </div>
        </div>

        <div class="filter-bar">
          <select id="notificationTypeFilter" class="filter-select">
            <option value="all">All Notifications</option>
            <option value="weather">Weather</option>
            <option value="crops">Crops</option>
            <option value="harvest">Harvest</option>
            <option value="experts">Experts</option>
            <option value="marketplace">Marketplace</option>
            <option value="activities">Activities</option>
          </select>
        </div>

        <div class="notifications-list" id="notificationsList"></div>

        <div class="empty-state" id="notificationsEmptyState" hidden>
          <i class="fa-solid fa-bell-slash"></i>
          <h3>No notifications</h3>
          <p>You're all caught up!</p>
        </div>
      </section>

      <!-- ================= 14. REPORTS ================= -->
      <section class="content-section" id="reports">
        <div class="section-heading">
          <div>
            <h2>Reports &amp; Analytics</h2>
            <p>Understand your farm's performance over time.</p>
          </div>
          <div class="btn-group">
            <button class="btn-secondary" id="printReportBtn"><i class="fa-solid fa-print"></i> Print Report</button>
            <button class="btn-primary" id="generateReportBtn"><i class="fa-solid fa-file-circle-plus"></i> Generate Report</button>
          </div>
        </div>

        <div class="filter-bar">
          <select id="reportTimeFilter" class="filter-select">
            <option value="week">This Week</option>
            <option value="month" selected>This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
          </select>
        </div>

        <div class="reports-grid">
          <div class="report-card">
            <h4><i class="fa-solid fa-wheat-awn"></i> Crop Production</h4>
            <div class="chart-container" id="cropProductionChart"></div>
          </div>
          <div class="report-card">
            <h4><i class="fa-solid fa-basket-shopping"></i> Harvest Quantity</h4>
            <div class="chart-container" id="harvestQuantityChart"></div>
          </div>
          <div class="report-card">
            <h4><i class="fa-solid fa-coins"></i> Farm Expenses</h4>
            <div class="chart-container" id="farmExpensesChart"></div>
          </div>
          <div class="report-card">
            <h4><i class="fa-solid fa-tag"></i> Product Sales</h4>
            <div class="chart-container" id="productSalesChart"></div>
          </div>
          <div class="report-card">
            <h4><i class="fa-solid fa-sack-dollar"></i> Revenue</h4>
            <div class="chart-container" id="revenueChart"></div>
          </div>
          <div class="report-card">
            <h4><i class="fa-solid fa-chart-pie"></i> Estimated Profit</h4>
            <div class="chart-container" id="profitChart"></div>
          </div>
        </div>
      </section>

      <!-- ================= 15. EXPENSES ================= -->
      <section class="content-section" id="expenses">
        <div class="section-heading">
          <div>
            <h2>Expenses</h2>
            <p>Track spending across all your farms.</p>
          </div>
          <button class="btn-primary" id="addExpenseBtn"><i class="fa-solid fa-plus"></i> Add Expense</button>
        </div>

        <div class="filter-bar">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="expenseSearchInput" placeholder="Search expenses...">
          </div>
          <select id="expenseCategoryFilter" class="filter-select">
            <option value="all">All Categories</option>
            <option value="seeds">Seeds</option>
            <option value="fertilizer">Fertilizer</option>
            <option value="labor">Labor</option>
            <option value="equipment">Equipment</option>
            <option value="transportation">Transportation</option>
            <option value="irrigation">Irrigation</option>
            <option value="pest-control">Pest Control</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="table-wrapper">
          <table class="data-table" id="expensesTable">
            <thead>
              <tr>
                <th>Description</th><th>Category</th><th>Amount</th><th>Date</th>
                <th>Farm</th><th>Notes</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="expensesTableBody"></tbody>
          </table>
        </div>

        <div class="empty-state" id="expensesEmptyState" hidden>
          <i class="fa-solid fa-coins"></i>
          <h3>No expenses recorded</h3>
          <p>Add an expense to start tracking your farm spending.</p>
        </div>
      </section>

      <!-- ================= 16. PROFILE ================= -->
      <section class="content-section" id="profile">
        <div class="section-heading">
          <div>
            <h2>My Profile</h2>
            <p>Your personal and farming information.</p>
          </div>
          <button class="btn-primary" id="editProfileBtn"><i class="fa-solid fa-pen"></i> Edit Profile</button>
        </div>

        <div class="profile-card">
          <div class="profile-card-top">
            <div class="profile-photo-wrap">
              <img src="https://i.pravatar.cc/150?img=47" alt="Farmer Nelly" id="profilePhoto">
            </div>
            <div class="profile-basic-info">
              <h2 id="profileName">Farmer Nelly Ashu</h2>
              <p id="profileTagline">Crop  &middot; Buea, Cameroon</p>
            </div>
          </div>

          <div class="profile-details-grid">
            <div class="profile-detail-item">
              <i class="fa-solid fa-envelope"></i>
              <div><small>Email</small><span id="profileEmail">nelly.ashu@agrotechmail.com</span></div>
            </div>
            <div class="profile-detail-item">
              <i class="fa-solid fa-phone"></i>
              <div><small>Phone</small><span id="profilePhone">+237 6 77 12 34 56</span></div>
            </div>
            <div class="profile-detail-item">
              <i class="fa-solid fa-location-dot"></i>
              <div><small>Location</small><span id="profileLocation">Buea, South West, Cameroon</span></div>
            </div>
            <div class="profile-detail-item">
              <i class="fa-solid fa-clock-rotate-left"></i>
              <div><small>Farming Experience</small><span id="profileExperience">8 years</span></div>
            </div>
            <div class="profile-detail-item">
              <i class="fa-solid fa-wheat-awn"></i>
              <div><small>Main Crops</small><span id="profileMainCrops">Maize, Cocoa, Cassava</span></div>
            </div>
            <div class="profile-detail-item">
              <i class="fa-solid fa-tractor"></i>
              <div><small>Number of Farms</small><span id="profileFarmCount">4 farms</span></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 17. SETTINGS ================= -->
      <section class="content-section" id="settings">
        <div class="section-heading">
          <div>
            <h2>Settings</h2>
            <p>Customize your Agro-Tech experience.</p>
          </div>
        </div>

        <div class="settings-layout">
          <div class="settings-card">
            <h3><i class="fa-solid fa-palette"></i> Appearance</h3>
            <div class="settings-row">
              <span>Light Mode</span>
              <label class="switch">
                <input type="radio" name="themeMode" id="lightModeRadio" value="light" checked>
                <span class="slider"></span>
              </label>
            </div>
            <div class="settings-row">
              <span>Dark Mode</span>
              <label class="switch">
                <input type="radio" name="themeMode" id="darkModeRadio" value="dark">
                <span class="slider"></span>
              </label>
            </div>
          </div>

          <div class="settings-card">
            <h3><i class="fa-solid fa-bell"></i> Notifications</h3>
            <div class="settings-row">
              <span>Weather Alerts</span>
              <label class="switch"><input type="checkbox" id="notifWeatherToggle" checked><span class="slider"></span></label>
            </div>
            <div class="settings-row">
              <span>Crop Alerts</span>
              <label class="switch"><input type="checkbox" id="notifCropToggle" checked><span class="slider"></span></label>
            </div>
            <div class="settings-row">
              <span>Marketplace Notifications</span>
              <label class="switch"><input type="checkbox" id="notifMarketplaceToggle" checked><span class="slider"></span></label>
            </div>
            <div class="settings-row">
              <span>Expert Messages</span>
              <label class="switch"><input type="checkbox" id="notifExpertToggle" checked><span class="slider"></span></label>
            </div>
          </div>

          <div class="settings-card">
            <h3><i class="fa-solid fa-table-cells-large"></i> Dashboard</h3>
            <div class="settings-row">
              <span>Widget Preferences</span>
              <button class="btn-secondary" id="widgetPrefsBtn">Manage Widgets</button>
            </div>
          </div>

          <div class="settings-card">
            <h3><i class="fa-solid fa-language"></i> Language</h3>
            <div class="settings-row">
              <span>Select Language</span>
              <select id="languageSelect" class="filter-select">
                <option value="en">English</option>
                <option value="fr">Français</option>
              </select>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 18. HELP ================= -->
      <section class="content-section" id="help">
        <div class="section-heading">
          <div>
            <h2>Help Center</h2>
            <p>Find answers, tips and support.</p>
          </div>
        </div>

        <div class="help-layout">
          <div class="help-card">
            <h3><i class="fa-solid fa-circle-question"></i> Frequently Asked Questions</h3>
            <div class="faq-list" id="faqList">
              <div class="faq-item">
                <button class="faq-question">How do I add a new farm? <i class="fa-solid fa-chevron-down"></i></button>
                <div class="faq-answer">Go to "My Farm" and click "Add Farm", then fill in the details in the popup form.</div>
              </div>
              <div class="faq-item">
                <button class="faq-question">How do I list a product for sale? <i class="fa-solid fa-chevron-down"></i></button>
                <div class="faq-answer">Go to "My Products" and click "Add Product". Your listing will appear in the Marketplace.</div>
              </div>
              <div class="faq-item">
                <button class="faq-question">Can I use the dashboard offline? <i class="fa-solid fa-chevron-down"></i></button>
                <div class="faq-answer">Your data is saved locally in your browser, so recently loaded data remains accessible offline.</div>
              </div>
              <div class="faq-item">
                <button class="faq-question">How do I contact an expert? <i class="fa-solid fa-chevron-down"></i></button>
                <div class="faq-answer">Visit "Agricultural Experts", choose a specialist, and click "Contact Expert" or "Request Consultation".</div>
              </div>
            </div>
          </div>

          <div class="help-card">
            <h3><i class="fa-solid fa-lightbulb"></i> Farming Tips</h3>
            <ul class="tips-list" id="farmingTipsList">
              <li>Rotate crops each season to preserve soil nutrients.</li>
              <li>Test soil pH before applying fertilizer.</li>
              <li>Water early morning or late evening to reduce evaporation loss.</li>
              <li>Scout your fields weekly for early pest detection.</li>
            </ul>
          </div>

          <div class="help-card">
            <h3><i class="fa-solid fa-rocket"></i> Getting Started Guide</h3>
            <ol class="getting-started-list">
              <li>Add your farm details under "My Farm".</li>
              <li>Register the crops you're growing under "Crops".</li>
              <li>Log activities like planting and spraying as you go.</li>
              <li>Check "Weather" daily before planning field work.</li>
              <li>List surplus produce on the "Marketplace".</li>
            </ol>
          </div>

          <div class="help-card">
            <h3><i class="fa-solid fa-headset"></i> Contact Support</h3>
            <p>Need more help? Reach the Agro-Tech support team.</p>
            <button class="btn-primary" id="contactSupportBtn"><i class="fa-solid fa-paper-plane"></i> Contact Support</button>
          </div>
        </div>
      </section>

    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="app-footer" id="appFooter">
      <p>&copy; <span id="footerYear">2026</span> Agro-Tech. All rights reserved.</p>
      <div class="footer-links">
        <a href="#help" class="nav-link-inline" data-section="help">Help</a>
        <a href="#settings" class="nav-link-inline" data-section="settings">Settings</a>
        <span>Built for the modern farmer.</span>
      </div>
    </footer>
  </div>
</div>

<!-- ============================================================
     MODALS
============================================================= -->

<!-- Add Farm Modal -->
<div class="modal" id="addFarmModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Add Farm</h3><button class="modal-close-btn" data-close-modal="addFarmModal">&times;</button></div>
    <div class="modal-body">
      <form id="addFarmForm">
        <div class="form-group"><label>Farm Name</label><input type="text" id="farmNameInput" required></div>
        <div class="form-group"><label>Location</label><input type="text" id="farmLocationInput" required></div>
        <div class="form-row">
          <div class="form-group"><label>Size (hectares)</label><input type="number" id="farmSizeInput" step="0.1" required></div>
          <div class="form-group"><label>Soil Type</label>
            <select id="farmSoilTypeInput">
              <option>Loamy</option><option>Clay</option><option>Sandy</option><option>Silty</option><option>Peaty</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Irrigation</label>
            <select id="farmIrrigationInput">
              <option>Drip Irrigation</option><option>Sprinkler</option><option>Rain-fed</option><option>Manual</option>
            </select>
          </div>
          <div class="form-group"><label>Main Crop</label><input type="text" id="farmMainCropInput"></div>
        </div>
        <div class="form-group"><label>Status</label>
          <select id="farmStatusInput"><option value="active">Active</option><option value="fallow">Fallow</option><option value="preparation">In Preparation</option></select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="addFarmModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Farm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Farm Modal -->
<div class="modal" id="editFarmModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Edit Farm</h3><button class="modal-close-btn" data-close-modal="editFarmModal">&times;</button></div>
    <div class="modal-body">
      <form id="editFarmForm">
        <input type="hidden" id="editFarmId">
        <div class="form-group"><label>Farm Name</label><input type="text" id="editFarmNameInput" required></div>
        <div class="form-group"><label>Location</label><input type="text" id="editFarmLocationInput" required></div>
        <div class="form-row">
          <div class="form-group"><label>Size (hectares)</label><input type="number" id="editFarmSizeInput" step="0.1"></div>
          <div class="form-group"><label>Soil Type</label>
            <select id="editFarmSoilTypeInput"><option>Loamy</option><option>Clay</option><option>Sandy</option><option>Silty</option><option>Peaty</option></select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Irrigation</label>
            <select id="editFarmIrrigationInput"><option>Drip Irrigation</option><option>Sprinkler</option><option>Rain-fed</option><option>Manual</option></select>
          </div>
          <div class="form-group"><label>Main Crop</label><input type="text" id="editFarmMainCropInput"></div>
        </div>
        <div class="form-group"><label>Status</label>
          <select id="editFarmStatusInput"><option value="active">Active</option><option value="fallow">Fallow</option><option value="preparation">In Preparation</option></select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="editFarmModal">Cancel</button>
          <button type="submit" class="btn-primary">Update Farm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Farm Modal -->
<div class="modal" id="viewFarmModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Farm Details</h3><button class="modal-close-btn" data-close-modal="viewFarmModal">&times;</button></div>
    <div class="modal-body" id="viewFarmModalBody"></div>
  </div>
</div>

<!-- Add Crop Modal -->
<div class="modal" id="addCropModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Add Crop</h3><button class="modal-close-btn" data-close-modal="addCropModal">&times;</button></div>
    <div class="modal-body">
      <form id="addCropForm">
        <div class="form-row">
          <div class="form-group"><label>Crop Name</label><input type="text" id="cropNameInput" required></div>
          <div class="form-group"><label>Variety</label><input type="text" id="cropVarietyInput"></div>
        </div>
        <div class="form-group"><label>Farm</label><select id="cropFarmInput" required></select></div>
        <div class="form-row">
          <div class="form-group"><label>Planting Date</label><input type="date" id="cropPlantingDateInput" required></div>
          <div class="form-group"><label>Expected Harvest Date</label><input type="date" id="cropHarvestDateInput"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Growth Stage</label>
            <select id="cropStageInput">
              <option value="seedling">Seedling</option><option value="vegetative">Vegetative</option>
              <option value="flowering">Flowering</option><option value="maturity">Maturity</option>
              <option value="harvest">Harvest Ready</option>
            </select>
          </div>
          <div class="form-group"><label>Quantity</label><input type="text" id="cropQuantityInput" placeholder="e.g. 2 hectares"></div>
        </div>
        <div class="form-group"><label>Health</label>
          <select id="cropHealthInput"><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="addCropModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Crop</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Crop Modal -->
<div class="modal" id="editCropModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Edit Crop</h3><button class="modal-close-btn" data-close-modal="editCropModal">&times;</button></div>
    <div class="modal-body">
      <form id="editCropForm">
        <input type="hidden" id="editCropId">
        <div class="form-row">
          <div class="form-group"><label>Crop Name</label><input type="text" id="editCropNameInput" required></div>
          <div class="form-group"><label>Variety</label><input type="text" id="editCropVarietyInput"></div>
        </div>
        <div class="form-group"><label>Farm</label><select id="editCropFarmInput"></select></div>
        <div class="form-row">
          <div class="form-group"><label>Planting Date</label><input type="date" id="editCropPlantingDateInput"></div>
          <div class="form-group"><label>Expected Harvest Date</label><input type="date" id="editCropHarvestDateInput"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Growth Stage</label>
            <select id="editCropStageInput">
              <option value="seedling">Seedling</option><option value="vegetative">Vegetative</option>
              <option value="flowering">Flowering</option><option value="maturity">Maturity</option>
              <option value="harvest">Harvest Ready</option>
            </select>
          </div>
          <div class="form-group"><label>Quantity</label><input type="text" id="editCropQuantityInput"></div>
        </div>
        <div class="form-group"><label>Health</label>
          <select id="editCropHealthInput"><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="editCropModal">Cancel</button>
          <button type="submit" class="btn-primary">Update Crop</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Crop Modal -->
<div class="modal" id="viewCropModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Crop Details</h3>
      <button class="modal-close-btn" data-close-modal="viewCropModal">&times;</button>
    </div>
    <div class="modal-body" id="viewCropModalBody"></div>
  </div>
</div>

<!-- Add Activity Modal -->
<div class="modal" id="addActivityModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Record Activity</h3>
      <button class="modal-close-btn" data-close-modal="addActivityModal">&times;</button>
    </div>
    <div class="modal-body">
      <form id="addActivityForm">
        <div class="form-group"><label>Activity Type</label>
          <select id="activityTypeInput">
            <option value="planting">Planting</option>
            <option value="fertilizing">Fertilizing</option>
            <option value="irrigation">Irrigation</option>
            <option value="weeding">Weeding</option>
            <option value="pest-control">Pest Control</option>
            <option value="spraying">Spraying</option>
            <option value="pruning">Pruning</option>
            <option value="harvesting">Harvesting</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Crop</label><select id="activityCropInput"></select></div>
          <div class="form-group"><label>Farm</label><select id="activityFarmInput"></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Date</label><input type="date" id="activityDateInput" required></div>
          <div class="form-group"><label>Cost (XAF)</label><input type="number" id="activityCostInput"></div>
        </div>
        <div class="form-group"><label>Status</label>
          <select id="activityStatusInput"><option value="completed">Completed</option><option value="in-progress">In Progress</option><option value="planned">Planned</option></select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="addActivityModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Activity</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Calendar Event Modal -->
<div class="modal" id="addEventModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Add Calendar Event</h3><button class="modal-close-btn" data-close-modal="addEventModal">&times;</button></div>
    <div class="modal-body">
      <form id="addEventForm">
        <div class="form-group"><label>Event Title</label><input type="text" id="eventTitleInput" required></div>
        <div class="form-group"><label>Event Type</label>
          <select id="eventTypeInput">
            <option value="planting">Planting</option><option value="fertilizing">Fertilizing</option>
            <option value="irrigation">Irrigation</option><option value="spraying">Spraying</option>
            <option value="harvesting">Harvesting</option><option value="inspection">Farm Inspection</option>
            <option value="expert">Expert Appointment</option>
          </select>
        </div>
        <div class="form-group"><label>Date</label><input type="date" id="eventDateInput" required></div>
        <div class="form-group"><label>Notes</label><textarea id="eventNotesInput" rows="3"></textarea></div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="addEventModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Product Modal -->
<div class="modal" id="addProductModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Add Product</h3><button class="modal-close-btn" data-close-modal="addProductModal">&times;</button></div>
    <div class="modal-body">
      <form id="addProductForm">
        <div class="form-group"><label>Product Name</label><input type="text" id="productNameInput" required></div>
        <div class="form-row">
          <div class="form-group"><label>Category</label>
            <select id="productCategoryInput">
              <option value="maize">Maize</option><option value="cocoa">Cocoa</option><option value="cassava">Cassava</option>
              <option value="rice">Rice</option><option value="vegetables">Vegetables</option><option value="fruits">Fruits</option>
              <option value="seeds">Seeds</option><option value="fertilizers">Fertilizers</option>
              <option value="equipment">Equipment</option><option value="livestock">Livestock Products</option>
            </select>
          </div>
          <div class="form-group"><label>Price (XAF)</label><input type="number" id="productPriceInput" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Quantity</label><input type="text" id="productQuantityInput" placeholder="e.g. 50 bags"></div>
          <div class="form-group"><label>Location</label><input type="text" id="productLocationInput"></div>
        </div>
        <div class="form-group"><label>Image URL</label><input type="url" id="productImageInput" placeholder="https://..."></div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="addProductModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Product Modal -->
<div class="modal" id="editProductModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Product</h3>
      <button class="modal-close-btn" data-close-modal="editProductModal">&times;</button>
    </div>
    <div class="modal-body">
      <form id="editProductForm">
        <input type="hidden" id="editProductId">
        <div class="form-group">
          <label>Product Name</label>
          <input type="text" id="editProductNameInput" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Category</label>
            <select id="editProductCategoryInput">
              <option value="maize">Maize</option>
              <option value="cocoa">Cocoa</option>
              <option value="cassava">Cassava</option>
              <option value="rice">Rice</option>
              <option value="vegetables">Vegetables</option>
              <option value="fruits">Fruits</option>
              <option value="seeds">Seeds</option>
              <option value="fertilizers">Fertilizers</option>
              <option value="equipment">Equipment</option>
              <option value="livestock">Livestock Products</option>
            </select>
          </div>
          <div class="form-group"><label>Price (XAF)</label><input type="number" id="editProductPriceInput"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Quantity</label><input type="text" id="editProductQuantityInput"></div>
          <div class="form-group"><label>Location</label><input type="text" id="editProductLocationInput"></div>
        </div>
        <div class="form-group"><label>Image URL</label><input type="url" id="editProductImageInput"></div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="editProductModal">Cancel</button>
          <button type="submit" class="btn-primary">Update Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Product Modal -->
<div class="modal" id="viewProductModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Product Details</h3><button class="modal-close-btn" data-close-modal="viewProductModal">&times;</button></div>
    <div class="modal-body" id="viewProductModalBody"></div>
  </div>
</div>

<!-- Contact Expert Modal -->
<div class="modal" id="contactExpertModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Contact Expert</h3><button class="modal-close-btn" data-close-modal="contactExpertModal">&times;</button></div>
    <div class="modal-body">
      <form id="contactExpertForm">
        <input type="hidden" id="contactExpertId">
        <div class="form-group"><label>Message</label><textarea id="contactExpertMessageInput" rows="4" placeholder="Describe your question or issue..."></textarea></div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="contactExpertModal">Cancel</button>
          <button type="submit" class="btn-primary">Send Message</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Request Consultation Modal -->
<div class="modal" id="requestConsultationModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Request Consultation</h3><button class="modal-close-btn" data-close-modal="requestConsultationModal">&times;</button></div>
    <div class="modal-body">
      <form id="requestConsultationForm">
        <input type="hidden" id="consultationExpertId">
        <div class="form-group"><label>Preferred Date</label><input type="date" id="consultationDateInput" required></div>
        <div class="form-group"><label>Topic</label><input type="text" id="consultationTopicInput" placeholder="e.g. Pest management"></div>
        <div class="form-group"><label>Additional Notes</label><textarea id="consultationNotesInput" rows="3"></textarea></div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="requestConsultationModal">Cancel</button>
          <button type="submit" class="btn-primary">Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal" id="editProfileModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Edit Profile</h3><button class="modal-close-btn" data-close-modal="editProfileModal">&times;</button></div>
    <div class="modal-body">
      <form id="editProfileForm">
        <div class="form-group"><label>Full Name</label><input type="text" id="editProfileNameInput" required></div>
        <div class="form-row">
          <div class="form-group"><label>Email</label><input type="email" id="editProfileEmailInput" required></div>
          <div class="form-group"><label>Phone</label><input type="tel" id="editProfilePhoneInput"></div>
        </div>
        <div class="form-group"><label>Location</label><input type="text" id="editProfileLocationInput"></div>
        <div class="form-row">
          <div class="form-group"><label>Farming Experience (years)</label><input type="number" id="editProfileExperienceInput"></div>
          <div class="form-group"><label>Main Crops</label><input type="text" id="editProfileMainCropsInput"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="editProfileModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Expense Modal -->
<div class="modal" id="addExpenseModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Add Expense</h3><button class="modal-close-btn" data-close-modal="addExpenseModal">&times;</button></div>
    <div class="modal-body">
      <form id="addExpenseForm">
        <div class="form-group"><label>Description</label><input type="text" id="expenseDescriptionInput" required></div>
        <div class="form-row">
          <div class="form-group"><label>Category</label>
            <select id="expenseCategoryInput">
              <option value="seeds">Seeds</option><option value="fertilizer">Fertilizer</option><option value="labor">Labor</option>
              <option value="equipment">Equipment</option><option value="transportation">Transportation</option>
              <option value="irrigation">Irrigation</option><option value="pest-control">Pest Control</option><option value="other">Other</option>
            </select>
          </div>
          <div class="form-group"><label>Amount (XAF)</label><input type="number" id="expenseAmountInput" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Date</label><input type="date" id="expenseDateInput" required></div>
          <div class="form-group"><label>Farm</label><select id="expenseFarmInput"></select></div>
        </div>
        <div class="form-group"><label>Notes</label><textarea id="expenseNotesInput" rows="2"></textarea></div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="addExpenseModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Generate Report Modal -->
<div class="modal" id="generateReportModal">
  <div class="modal-content">
    <div class="modal-header"><h3>Generate Report</h3><button class="modal-close-btn" data-close-modal="generateReportModal">&times;</button></div>
    <div class="modal-body">
      <form id="generateReportForm">
        <div class="form-group"><label>Report Type</label>
          <select id="reportTypeInput">
            <option value="production">Crop Production</option>
            <option value="harvest">Harvest Summary</option>
            <option value="expenses">Expenses</option>
            <option value="sales">Product Sales</option>
            <option value="revenue">Revenue &amp; Profit</option>
          </select>
        </div>
        <div class="form-group"><label>Period</label>
          <select id="reportPeriodInput"><option value="week">This Week</option><option value="month">This Month</option><option value="quarter">This Quarter</option><option value="year">This Year</option></select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" data-close-modal="generateReportModal">Cancel</button>
          <button type="submit" class="btn-primary">Generate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal" id="confirmDeleteModal">
  <div class="modal-content small">
    <div class="modal-header"><h3>Confirm Delete</h3><button class="modal-close-btn" data-close-modal="confirmDeleteModal">&times;</button></div>
    <div class="modal-body">
      <p id="confirmDeleteMessage">Are you sure you want to delete this item? This action cannot be undone.</p>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close-modal="confirmDeleteModal">Cancel</button>
        <button type="button" class="btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="../scripts/farm.js"></script>
</body>
</html>