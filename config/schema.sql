-- Project Tracker Database Schema

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
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