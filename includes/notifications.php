<?php
// includes/notifications.php

// --- Create a new notification ---

function create_notification( $pdo, $user_id, $title, $message ) {
    $stmt = $pdo->prepare( '
        INSERT INTO notifications (user_id, title, message)
        VALUES (?, ?, ?)
    ' );
    return $stmt->execute( [ $user_id, $title, $message ] );
}

// --- Count unread notifications ---

function get_unread_notifications_count( $pdo, $user_id ) {
    $stmt = $pdo->prepare( '
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ' );
    $stmt->execute( [ $user_id ] );
    return $stmt->fetchColumn();
}

// --- Get recent notifications ( default 5 ) ---

function get_recent_notifications( $pdo, $user_id, $limit = 5 ) {
    $stmt = $pdo->prepare( '
        SELECT *
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ' );
    $stmt->bindValue( 1, $user_id, PDO::PARAM_INT );
    $stmt->bindValue( 2, $limit, PDO::PARAM_INT );
    $stmt->execute();
    return $stmt->fetchAll( PDO::FETCH_ASSOC );
}

// --- Mark notification as read ---

function mark_notification_as_read( $pdo, $notification_id ) {
    $stmt = $pdo->prepare( '
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ?
    ' );
    return $stmt->execute( [ $notification_id ] );
}

// --- Mark all as read ---

function mark_all_notifications_as_read( $pdo, $user_id ) {
    $stmt = $pdo->prepare( '
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ? AND is_read = 0
    ' );
    return $stmt->execute( [ $user_id ] );
}

// --- Time elapsed helper ---

function time_elapsed_string( $datetime ) {
    $now = new DateTime;
    $ago = new DateTime( $datetime );
    $diff = $now->diff( $ago );

    if ( $diff->d > 7 ) {
        return date( 'M j, Y', strtotime( $datetime ) );
    }
    if ( $diff->d > 0 ) {
        return $diff->d . 'd ago';
    }
    if ( $diff->h > 0 ) {
        return $diff->h . 'h ago';
    }
    if ( $diff->i > 0 ) {
        return $diff->i . 'm ago';
    }
    return 'just now';
}

// project-tracker/includes/notifications.php

/**
* Mark a single notification as read.
*/

/**
* Given a notification type, return an AdminLTE badge class.
*/

function get_notification_badge_class( string $type ): string {
    switch ( $type ) {
        case 'success':
        return 'badge badge-success';
        case 'warning':
        return 'badge badge-warning';
        case 'danger':
        return 'badge badge-danger';
        default:
        return 'badge badge-info';
    }
}

/**
* Turn a timestamp into a human-friendly 'time ago'.
*/