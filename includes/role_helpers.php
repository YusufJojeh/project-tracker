<?php
// Get the role for a given user_id

function get_user_role( PDO $pdo, $user_id ) {
    $stmt = $pdo->prepare( 'SELECT role FROM users WHERE user_id = ?' );
    $stmt->execute( [ $user_id ] );
    return $stmt->fetchColumn();
}

// Get all permission names for a given role ENUM

function get_role_permissions( PDO $pdo, $role_name ) {
    $stmt = $pdo->prepare( "
        SELECT p.name
        FROM permissions p
        INNER JOIN role_permissions rp ON rp.permission_id = p.permission_id
        WHERE rp.role = ?
    " );
    $stmt->execute( [ $role_name ] );
    return $stmt->fetchAll( PDO::FETCH_COLUMN );
}

// Check if the currently logged-in user has a specific permission

function has_permission( PDO $pdo, $permission ) {
    if ( empty( $_SESSION[ 'user_id' ] ) ) return false;
    $role = get_user_role( $pdo, $_SESSION[ 'user_id' ] );
    if ( !$role ) return false;
    $perms = get_role_permissions( $pdo, $role );
    return in_array( $permission, $perms );
}

// Require a specific permission, or block access

function require_permission( PDO $pdo, $permission ) {
    if ( !has_permission( $pdo, $permission ) ) {
        header( 'HTTP/1.1 403 Forbidden' );
        exit( 'Access denied' );
    }
}
