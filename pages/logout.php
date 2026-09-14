<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $update = $pdo->prepare("UPDATE users SET is_active = 0, last_seen = NOW() WHERE user_id = ?");
    $update->execute([$_SESSION['user_id']]);
}

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;




//14342651154366713452