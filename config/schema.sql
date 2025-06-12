-- Project Tracker Database Schema

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- Real passwords will be inserted directly (not hashed)
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('student', 'supervisor', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    student_id INT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(100),
    FOREIGN KEY (student_id) REFERENCES users(user_id)
);

-- Supervisors table
CREATE TABLE supervisors (
    supervisor_id INT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    specialization VARCHAR(100),
    FOREIGN KEY (supervisor_id) REFERENCES users(user_id)
);

-- Projects table
CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    supervisor_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('draft', 'in_progress', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (supervisor_id) REFERENCES supervisors(supervisor_id)
);

-- Stages table
CREATE TABLE stages (
    stage_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    status ENUM('pending', 'submitted', 'reviewed', 'approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id)
);

-- Uploads table
CREATE TABLE uploads (
    upload_id INT PRIMARY KEY AUTO_INCREMENT,
    stage_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stage_id) REFERENCES stages(stage_id)
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    stage_id INT NOT NULL,
    supervisor_id INT NOT NULL,
    grade DECIMAL(4,2),
    feedback TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stage_id) REFERENCES stages(stage_id),
    FOREIGN KEY (supervisor_id) REFERENCES supervisors(supervisor_id)
);

-- Comments table
CREATE TABLE comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- --------------------------------------------------------
-- Table structure for table `roles`
CREATE TABLE `roles` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `permissions`
CREATE TABLE `permissions` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Pivot table for users↔roles
CREATE TABLE `role_user` (
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  INDEX (`role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Pivot table for roles↔permissions
CREATE TABLE `permission_role` (
  `permission_id` INT NOT NULL,
  `role_id`       INT NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  INDEX (`role_id`),
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`)       REFERENCES `roles`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE attachments (
    upload_id INT PRIMARY KEY AUTO_INCREMENT,
    stage_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stage_id) REFERENCES stages(stage_id)
);

-- --------------------------------------------------------
-- Seed initial roles & permissions
INSERT INTO `roles` (`name`) VALUES
  ('student'), ('supervisor'), ('admin');

INSERT INTO `permissions` (`name`) VALUES
  ('project.upload'),
  ('project.view'),
  ('project.review'),
  ('project.comment'),
  ('user.manage'),
  ('role.manage'),
  ('permission.manage');
  
-- assign student permissions
INSERT INTO `permission_role` (`permission_id`,`role_id`)
  SELECT p.id, r.id
  FROM permissions p
  JOIN roles r ON r.name = 'student'
  WHERE p.name IN ('project.upload','project.view');
  
-- assign supervisor permissions
INSERT INTO `permission_role` (`permission_id`,`role_id`)
  SELECT p.id, r.id
  FROM permissions p
  JOIN roles r ON r.name = 'supervisor'
  WHERE p.name IN ('project.review','project.comment');
  
-- assign admin permissions
INSERT INTO `permission_role` (`permission_id`,`role_id`)
  SELECT p.id, r.id
  FROM permissions p
  JOIN roles r ON r.name = 'admin'
  WHERE p.name IN ('user.manage','role.manage','permission.manage');

-- Seed users with real passwords (for simplicity, use plain text passwords)
INSERT INTO users (username, password, email, role) VALUES
  ('admin_user', 'adminpassword', 'admin@example.com', 'admin'),
  ('supervisor_user', 'supervisorpassword', 'supervisor@example.com', 'supervisor'),
  ('student_user', 'studentpassword', 'student@example.com', 'student');

-- Seed a student and supervisor entry
INSERT INTO students (student_id, full_name, student_number, department) VALUES
  (1, 'John Doe', 'S123456', 'Software Engineering');

INSERT INTO supervisors (supervisor_id, full_name, department, specialization) VALUES
  (2, 'Dr. Alice Smith', 'Software Engineering', 'AI and Machine Learning');

-- Seed projects (Software Engineering and AI)
INSERT INTO projects (student_id, supervisor_id, title, description, status) VALUES
  (1, 2, 'AI-Based Traffic Prediction System', 'This project involves creating an AI model to predict traffic patterns based on various parameters.', 'in_progress'),
  (1, 2, 'Software Architecture for Distributed Systems', 'This project focuses on designing the architecture of a distributed system.', 'in_progress');

-- Seed stages for these projects (7 stages for each project)
-- Software Engineering Project Stages
INSERT INTO stages (project_id, title, description, due_date, status) VALUES
  (1, 'Initial Design', 'Designing the system architecture.', '2025-07-01', 'pending'),
  (1, 'Data Collection', 'Collecting data for the model.', '2025-07-10', 'pending'),
  (1, 'Model Development', 'Developing the AI traffic prediction model.', '2025-07-20', 'pending'),
  (1, 'Testing and Evaluation', 'Testing the model on different datasets.', '2025-07-30', 'pending'),
  (1, 'Optimization', 'Optimizing the model for better performance.', '2025-08-10', 'pending'),
  (1, 'Final Report', 'Writing the final report for submission.', '2025-08-15', 'pending'),
  (1, 'Submission', 'Submit the project for final review.', '2025-08-20', 'pending');

-- AI Project Stages
INSERT INTO stages (project_id, title, description, due_date, status) VALUES
  (2, 'Research and Literature Review', 'Conducting research on existing traffic prediction systems using AI.', '2025-06-15', 'pending'),
  (2, 'Data Preprocessing', 'Cleaning and preparing the traffic data.', '2025-06-25', 'pending'),
  (2, 'Model Training', 'Training the AI model using the prepared data.', '2025-07-05', 'pending'),
  (2, 'Model Testing', 'Testing the AI model accuracy.', '2025-07-15', 'pending'),
  (2, 'System Integration', 'Integrating the model with the user interface.', '2025-07-25', 'pending'),
  (2, 'Final Presentation', 'Preparing the final presentation and report.', '2025-08-05', 'pending'),
  (2, 'Submission and Review', 'Submitting the project for final review.', '2025-08-10', 'pending');
