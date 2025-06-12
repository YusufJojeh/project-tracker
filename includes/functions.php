<?php
// includes/functions.php

// Start session if not already
if ( session_status() === PHP_SESSION_NONE ) {
    session_start();
}

// -----------------------------------------------------------------------------
// 1 ) Session & Authorization Helpers
// -----------------------------------------------------------------------------
if ( !function_exists( 'is_logged_in' ) ) {
    function is_logged_in(): bool {
        return isset( $_SESSION[ 'user_id' ] );
    }
}

if ( !function_exists( 'require_login' ) ) {
    function require_login(): void {
        if ( !is_logged_in() ) {
            header( 'Location: /auth/login.php' );
            exit;
        }
    }
}

if ( !function_exists( 'require_role' ) ) {
    function require_role( string $role ): void {
        require_login();
        if ( ( $_SESSION[ 'role' ] ?? '' ) !== $role ) {
            http_response_code( 403 );
            echo '403 Forbidden';
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 2 ) HTML Escaping
// -----------------------------------------------------------------------------
if ( !function_exists( 'h' ) ) {
    function h( string $str ): string {
        return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
    }
}

// -----------------------------------------------------------------------------
// 3 ) Badge & Time‐Ago Helpers
// -----------------------------------------------------------------------------
if ( !function_exists( 'get_badge_class' ) ) {
    function get_badge_class( string $type ): string {
        return match( $type ) {
            'success' => 'badge badge-success',
            'warning' => 'badge badge-warning',
            'danger'  => 'badge badge-danger',
            default   => 'badge badge-info',
        }
        ;
    }
}

if ( !function_exists( 'time_elapsed_string' ) ) {
    function time_elapsed_string( string $datetime ): string {
        $now  = new DateTime;
        $ago  = new DateTime( $datetime );
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
}

// -----------------------------------------------------------------------------
// 4 ) File‐Upload Helper
// -----------------------------------------------------------------------------
if ( !function_exists( 'handle_file_upload' ) ) {
    function handle_file_upload( array $file, string $uploadDir ): array {
        if ( !is_dir( $uploadDir ) && !mkdir( $uploadDir, 0777, true ) ) {
            return [ 'success' => false, 'error' => 'Could not create directory' ];
        }
        $fname = uniqid() . '_' . basename( $file[ 'name' ] );
        $path  = rtrim( $uploadDir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $fname;
        if ( move_uploaded_file( $file[ 'tmp_name' ], $path ) ) {
            return [ 'success' => true, 'file_name' => $fname, 'file_path' => $path ];
        }
        return [ 'success' => false, 'error' => 'Failed to move uploaded file' ];
    }
}

function has_permission( $pdo, $permission ) {
    // Check if role_id exists in the session
    if ( !isset( $_SESSION[ 'role_id' ] ) ) {
        die( 'Permission error: role_id not set in the session.' );
    }

    // Get the current user's role_id
    $role_id = $_SESSION['role_id']; 

    // Prepare and execute the query to check if the role has the required permission
    $stmt = $pdo->prepare("
        SELECT 1
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id = ? AND p.name = ?
    ");
    $stmt->execute([$role_id, $permission]);

    return $stmt->fetchColumn() !== false;
}

/**
* Denies access ( 403 ) unless the current user has the named permission.
*/

function require_permission($pdo, $permission_name) {
    // Get the role from the session (not role_id)
    if (!isset($_SESSION['role'])) {
        die('Permission error: role not set in session.');
    }

    // Now use $_SESSION['role'] to check the permissions for that role
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions p
                           JOIN role_permissions rp ON rp.permission_id = p.id
                           JOIN roles r ON rp.role_id = r.id
                           WHERE r.name = ? AND p.name = ?');
    $stmt->execute([$_SESSION['role'], $permission_name]);
    $permission_count = $stmt->fetchColumn();

    if ($permission_count == 0) {
        die('Permission error: You do not have the required permission.');
    }
}