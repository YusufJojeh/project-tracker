<?php
session_start();

// Check if the user is logged in
if ( empty( $_SESSION[ 'user_id' ] ) ) {
    // Redirect to login if the user is not logged in
    header( 'Location: ./auth/login.php' );
    exit;
}

// Redirect to student or supervisor dashboard based on role
if ( $_SESSION[ 'role' ] === 'student' ) {
    header( 'Location: ../students/dashboard.php' );
} else if ( $_SESSION[ 'role' ] === 'supervisor' ) {
    header( 'Location: ../supervisors/dashboard.php' );
}
exit;