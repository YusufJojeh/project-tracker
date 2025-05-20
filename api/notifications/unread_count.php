<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/notifications.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    exit;
}

$cnt = get_unread_notifications_count($db, $_SESSION['user']['id']);
header('Content-Type: application/json');
echo json_encode(['unread_count' => $cnt]); 