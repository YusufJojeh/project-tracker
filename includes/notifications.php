<?php
// includes/notifications.php

// require_once __DIR__ . '/functions.php';

// -----------------------------------------------------------------------------
// 1 ) Create a new notification
// -----------------------------------------------------------------------------

function create_notification( PDO $pdo, int $user_id, string $title, string $message ): bool {
    $stmt = $pdo->prepare( "
        INSERT INTO notifications (user_id, title, message)
        VALUES (?, ?, ?)
    " );
    return $stmt->execute( [ $user_id, $title, $message ] );
}

// -----------------------------------------------------------------------------
// 2 ) Count unread notifications
// -----------------------------------------------------------------------------

function get_unread_notifications_count( PDO $pdo, int $userId ): int {
    $stmt = $pdo->prepare( "
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    " );
    $stmt->execute( [ $userId ] );
    return ( int )$stmt->fetchColumn();
}

// -----------------------------------------------------------------------------
// 3 ) Fetch recent notifications ( default 5 )
// -----------------------------------------------------------------------------

function get_recent_notifications( $pdo, $user_id, $limit ) {
    $stmt = $pdo->prepare( '
    SELECT message, is_read, created_at
    FROM notifications
    WHERE user_id = ? AND is_read = FALSE
    ORDER BY created_at DESC
    LIMIT ?
    ' );
    $stmt->execute( [ $user_id, $limit ] );
    return $stmt->fetchAll( PDO::FETCH_ASSOC );
}
// -----------------------------------------------------------------------------
// 4 ) Mark one or all notifications as read
// -----------------------------------------------------------------------------

function mark_notification_as_read( PDO $pdo, int $notificationId ): bool {
    $stmt = $pdo->prepare( "
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ?
    " );
    return $stmt->execute( [ $notificationId ] );
}

function mark_all_notifications_as_read( PDO $pdo, int $userId ): bool {
    $stmt = $pdo->prepare( "
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
    " );
    return $stmt->execute( [ $userId ] );
}