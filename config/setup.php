<?php

$host   = '127.0.0.1';
$user   = 'root';
$pass   = '';

$dbName = 'project_tracker';

try {
    $pdo = new PDO( "mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ] );

    $pdo->exec( "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" );
    $pdo->exec( "USE `{$dbName}`" );

    // Disable foreign key checks to allow table drop and create operations
    $pdo->exec( 'SET FOREIGN_KEY_CHECKS = 0' );
    foreach ( [ 'reviews', 'uploads', 'stages', 'projects', 'users' ] as $tbl ) {
        $pdo->exec( "DROP TABLE IF EXISTS `{$tbl}`" );
    }
    $pdo->exec( 'SET FOREIGN_KEY_CHECKS = 1' );

    // Create the 'users' table
    $pdo->exec( "
        CREATE TABLE users (
            user_id     INT AUTO_INCREMENT PRIMARY KEY,
            username    VARCHAR(50) UNIQUE NOT NULL,
            password    VARCHAR(255) NOT NULL,
            role        ENUM('student','supervisor') NOT NULL,
            full_name   VARCHAR(100) NOT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE projects (
            project_id   INT AUTO_INCREMENT PRIMARY KEY,
            student_id   INT NOT NULL,
            title        VARCHAR(255) NOT NULL,
            description  TEXT,
            tools_used   VARCHAR(255),
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB;

        CREATE TABLE stages (
            stage_id     INT AUTO_INCREMENT PRIMARY KEY,
            project_id   INT NOT NULL,
            name         VARCHAR(100) NOT NULL,
            due_date     DATE,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
        ) ENGINE=InnoDB;

        CREATE TABLE uploads (
            upload_id    INT AUTO_INCREMENT PRIMARY KEY,
            stage_id     INT NOT NULL,
            file_path    VARCHAR(255) NOT NULL,
            uploaded_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (stage_id) REFERENCES stages(stage_id) ON DELETE CASCADE
        ) ENGINE=InnoDB;

        CREATE TABLE reviews (
            review_id     INT AUTO_INCREMENT PRIMARY KEY,
            stage_id      INT NOT NULL,
            supervisor_id INT NOT NULL,
            comment       TEXT,
            grade         DECIMAL(5,2),
            reviewed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (stage_id)      REFERENCES stages(stage_id)      ON DELETE CASCADE,
            FOREIGN KEY (supervisor_id) REFERENCES users(user_id)        ON DELETE CASCADE
        ) ENGINE=InnoDB;
    " );

    // Adding supervisor_id column to projects table if it doesn't exist (alteration)
    $pdo->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS supervisor_id INT NOT NULL");

    // Sample data for users (students and supervisor)
    $addUser = $pdo->prepare('INSERT INTO users ( username, password, role, full_name ) VALUES ( ?, ?, ?, ? )');
    $addUser->execute([ 'alice', password_hash( 'alice123', PASSWORD_DEFAULT ), 'student', 'Alice Johnson' ]);
    $addUser->execute([ 'bob',   password_hash( 'bob123',   PASSWORD_DEFAULT ), 'student', 'Bob Smith' ]);
    $addUser->execute([ 'dr_wu', password_hash( 'wu2025',   PASSWORD_DEFAULT ), 'supervisor', 'Dr. Wen Wu' ]);

    // Get user IDs
    $alice   = $pdo->query("SELECT user_id FROM users WHERE username='alice'")->fetchColumn();
    $bob     = $pdo->query("SELECT user_id FROM users WHERE username='bob'")->fetchColumn();
    $super   = $pdo->query("SELECT user_id FROM users WHERE username='dr_wu'")->fetchColumn();

    // Add projects
    $addProj = $pdo->prepare('INSERT INTO projects ( student_id, title, description, tools_used, supervisor_id ) VALUES ( ?, ?, ?, ?, ? )');
    $addProj->execute([ $alice, 'AI Chatbot', 'Build a simple AI chatbot with PHP.', 'PHP, JavaScript', $super ]);
    $proj1 = $pdo->lastInsertId();
    $addProj->execute([ $bob, 'Website Redesign', 'Redesign company website UI/UX.', 'HTML, CSS, Bootstrap', $super ]);
    $proj2 = $pdo->lastInsertId();

    // Add stages
    $addStage = $pdo->prepare('INSERT INTO stages ( project_id, name, due_date ) VALUES ( ?, ?, ? )');
    $addStage->execute([ $proj1, 'Requirement Analysis', date('Y-m-d', strtotime('+7 days')) ]);
    $st1 = $pdo->lastInsertId();
    $addStage->execute([ $proj1, 'Implementation', date('Y-m-d', strtotime('+21 days')) ]);
    $st2 = $pdo->lastInsertId();
    $addStage->execute([ $proj2, 'Wireframe Creation', date('Y-m-d', strtotime('+10 days')) ]);
    $st3 = $pdo->lastInsertId();
    $addStage->execute([ $proj2, 'Visual Design', date('Y-m-d', strtotime('+25 days')) ]);
    $st4 = $pdo->lastInsertId();

    // Add uploads
    $addUp = $pdo->prepare('INSERT INTO uploads ( stage_id, file_path ) VALUES ( ?, ? )');
    $addUp->execute([ $st1, 'uploads/alice_requirements.pdf' ]);
    $addUp->execute([ $st3, 'uploads/bob_wireframe.png' ]);

    // Add reviews
    $addRev = $pdo->prepare('INSERT INTO reviews ( stage_id, supervisor_id, comment, grade ) VALUES ( ?, ?, ?, ? )');
    $addRev->execute([ $st1, $super, 'Solid analysis, proceed to next stage.', 92.50 ]);
    $addRev->execute([ $st3, $super, 'Wireframes need more annotations.', 78.00 ]);

    echo '✅ project_tracker created, tables built, sample data inserted.\n';

} catch (PDOException $e) {
    echo '❌ Error: ' . $e->getMessage() . '\n';
    exit( 1 );
}
?>