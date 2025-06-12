<?php
// Include the database configuration
require_once __DIR__ . '/../config/database.php';

// Ensure the user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo 'not_logged_in';
    exit;
}

$userId = $_SESSION['user_id'];

// Mark the notification as read
if (isset($_POST['mark_read'])) {
    $notificationId = $_POST['mark_read'];

    // Update the notification status in the database
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?');
    $stmt->execute([$notificationId, $userId]);

    echo 'success'; // Return success message to the AJAX call
}
?>
