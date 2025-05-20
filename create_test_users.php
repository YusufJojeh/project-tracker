<?php
require_once 'config/database.php';

// Test users data
$users = [
    [
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin'
    ],
    [
        'username' => 'supervisor1',
        'email' => 'supervisor1@example.com',
        'password' => password_hash('super123', PASSWORD_DEFAULT),
        'role' => 'supervisor'
    ],
    [
        'username' => 'student1',
        'email' => 'student1@example.com',
        'password' => password_hash('student123', PASSWORD_DEFAULT),
        'role' => 'student'
    ]
];

// Insert users
$stmt = $pdo->prepare("
    INSERT INTO users (username, email, password, role)
    VALUES (?, ?, ?, ?)
");

foreach ($users as $user) {
    try {
        $stmt->execute([
            $user['username'],
            $user['email'],
            $user['password'],
            $user['role']
        ]);
        echo "Created user: {$user['username']}\n";
    } catch (PDOException $e) {
        echo "Error creating user {$user['username']}: " . $e->getMessage() . "\n";
    }
}

echo "\nTest users created successfully!\n";
echo "Admin credentials: admin / admin123\n";
echo "Supervisor credentials: supervisor1 / super123\n";
echo "Student credentials: student1 / student123\n"; 