<?php
// Example PHP script to hash passwords

// Passwords that need to be hashed
$password1 = 'student_password';
$password2 = 'supervisor_password';

// Generate hashed passwords
$hashed_password1 = password_hash( $password1, PASSWORD_BCRYPT );
$hashed_password2 = password_hash( $password2, PASSWORD_BCRYPT );

// Output the hashed passwords for insertion into the database
echo "Hashed password for student1: $hashed_password1\n";
echo "Hashed password for supervisor1: $hashed_password2\n";
?>