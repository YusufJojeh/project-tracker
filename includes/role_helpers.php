<?php
// includes/role_helpers.php

// 1 ) Fetch the user's role from the users table
// if ( ! function_exists( 'get_user_role' ) ) {
//     /**
//      * @param PDO $pdo
//      * @param int $userId
//      * @return string  the role name (e.g. "admin", "supervisor", "student")
//      */
//     function get_user_role( PDO $pdo, int $userId ): string {
//         $stmt = $pdo->prepare( 'SELECT role FROM users WHERE user_id = ?' );
//         $stmt->execute( [ $userId ] );
//         return (string) $stmt->fetchColumn();
//     }
// }

// // 2) Get all permission names for a given role enum
// if ( ! function_exists( 'get_role_permissions' ) ) {
//     function get_role_permissions( PDO $pdo, string $roleName ): array {
//         $stmt = $pdo->prepare( "
//             SELECT p.name
//               FROM permissions p
//               JOIN role_permissions rp ON rp.permission_id = p.permission_id
//              WHERE rp.role = ?
//         " );
//         $stmt->execute( [ $roleName ] );
//         return $stmt->fetchAll( PDO::FETCH_COLUMN );
//     }
// }

// // 3) Check if the currently logged-in user has a specific permission
// if ( ! function_exists( 'has_permission' ) ) {
//     function has_permission( PDO $pdo, string $permission ): bool {
//         if ( empty( $_SESSION['user_id'] ) ) {
//             return false;
//         }
//         $role = get_user_role( $pdo, (int) $_SESSION['user_id'] );
//         if ( ! $role ) {
//             return false;
//         }
//         $perms = get_role_permissions( $pdo, $role );
//         return in_array( $permission, $perms, true );
//     }
// }

// // 4) Require a specific permission (403 if not granted)
// if ( ! function_exists( 'require_permission' ) ) {
//     function require_permission( PDO $pdo, string $permission ): void {
//         if ( ! has_permission( $pdo, $permission ) ) {
//             header( 'HTTP/1.1 403 Forbidden' );
//             exit( 'Access denied' );
//         }
//     }
// }
