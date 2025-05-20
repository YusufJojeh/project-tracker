<?php
require_once 'database.php';

// 1. Make sure PDO throws exceptions
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

try {
    // 2. Disable foreign-key checks, drop old tables, re-enable
    $pdo->exec( 'SET FOREIGN_KEY_CHECKS = 0' );
    foreach ( [
        'role_permissions',
        'permissions',
        'notifications',
        'attachments',
        'stages',
        'projects',
        'specializations',
        'departments',
        'users'
    ] as $tbl ) {
        $pdo->exec( "DROP TABLE IF EXISTS `{$tbl}`" );
    }
    $pdo->exec( 'SET FOREIGN_KEY_CHECKS = 1' );

    // 3. Recreate all tables
    $pdo->exec( "
        CREATE TABLE `users` (
            `user_id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin','supervisor','student') NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    " );

    $pdo->exec( "
        CREATE TABLE `departments` (
            `department_id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    " );

    $pdo->exec( "
        CREATE TABLE `specializations` (
            `specialization_id` INT AUTO_INCREMENT PRIMARY KEY,
            `department_id` INT,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE SET NULL
        )
    " );

    $pdo->exec( "
        CREATE TABLE `projects` (
            `project_id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `student_id` INT,
            `supervisor_id` INT,
            `status` ENUM('pending','in_progress','completed','rejected') DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`student_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
            FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
        )
    " );

    $pdo->exec( "
        CREATE TABLE `stages` (
            `stage_id` INT AUTO_INCREMENT PRIMARY KEY,
            `project_id` INT,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `due_date` DATE,
            `status` ENUM('pending','submitted','reviewed','approved','rejected') DEFAULT 'pending',
            `grade` DECIMAL(5,2),
            `feedback` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`project_id`) REFERENCES `projects`(`project_id`) ON DELETE CASCADE
        )
    " );

    $pdo->exec( "
        CREATE TABLE `attachments` (
            `attachment_id` INT AUTO_INCREMENT PRIMARY KEY,
            `stage_id` INT,
            `file_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `uploaded_by` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`stage_id`) REFERENCES `stages`(`stage_id`) ON DELETE CASCADE,
            FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
        )
    " );

    $pdo->exec( "
        CREATE TABLE `notifications` (
            `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT,
            `is_read` BOOLEAN DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
        )
    " );

    $pdo->exec( "
        CREATE TABLE `permissions` (
            `permission_id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(50) NOT NULL UNIQUE,
            `description` TEXT
        )
    " );

    $pdo->exec( "
        CREATE TABLE `role_permissions` (
            `role` ENUM('admin','supervisor','student'),
            `permission_id` INT,
            PRIMARY KEY (`role`,`permission_id`),
            FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`permission_id`) ON DELETE CASCADE
        )
    " );

    // 4. Insert default permissions and admin user inside a transaction
    $pdo->beginTransaction();

    $perms = [
        [ 'manage_users',        'Can manage user accounts' ],
        [ 'manage_departments',  'Can manage departments and specializations' ],
        [ 'manage_projects',     'Can manage projects' ],
        [ 'review_projects',     'Can review and grade projects' ],
        [ 'create_projects',     'Can create new projects' ],
        [ 'upload_files',        'Can upload project files' ],
        [ 'view_statistics',     'Can view system statistics' ],
    ];
    $insP = $pdo->prepare( 'INSERT INTO `permissions` (`name`,`description`) VALUES (?,?)' );
    foreach ( $perms as $p ) {
        $insP->execute( $p );
    }

    $rolePerms = [
        [ 'admin',      1 ], [ 'admin',      2 ], [ 'admin',      3 ], [ 'admin',      7 ],
        [ 'supervisor', 3 ], [ 'supervisor', 4 ], [ 'supervisor', 7 ],
        [ 'student',    5 ], [ 'student',    6 ],
    ];
    $insRP = $pdo->prepare( 'INSERT INTO `role_permissions` (`role`,`permission_id`) VALUES (?,?)' );
    foreach ( $rolePerms as $rp ) {
        $insRP->execute( $rp );
    }

    // default admin
    $passHash = password_hash( 'admin123', PASSWORD_DEFAULT );
    $pdo->prepare( "
        INSERT INTO `users` (`username`,`email`,`password`,`role`)
        VALUES ('admin','admin@example.com',?,'admin')
    " )->execute( [ $passHash ] );

    $pdo->commit();

    echo '✅ Database setup completed successfully.\n';
    echo '   Default admin → username: admin | password: admin123\n';

} catch ( PDOException $e ) {
    // Roll back if we’re mid-transaction
    if ( $pdo->inTransaction() ) {
        $pdo->rollBack();
    }
    echo '❗️ Setup failed: ' . $e->getMessage() . '\n';
    exit( 1 );
}