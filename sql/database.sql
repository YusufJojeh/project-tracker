-- Drop and recreate database
DROP DATABASE IF EXISTS project_tracker;
CREATE DATABASE project_tracker;
USE project_tracker;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO users (username, email, password, role)
VALUES
('admin', 'admin@example.com',  '$2y$10$abcdefghijklmnopqrstuvwxyzabcdefghi', 'admin'),         -- fake hashed pw
('supervisor1', 'super1@example.com', '$2y$10$abcdefghijklmnopqrstuvwxyzabcde2', 'supervisor'),
('student1', 'student1@example.com',  '$2y$10$abcdefghijklmnopqrstuvwxyzabcde3', 'student');

-- Departments table
CREATE TABLE departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (name, description) VALUES
('Computer Science', 'CS Department'),
('Electrical Engineering', 'EE Department');

-- Specializations table
CREATE TABLE specializations (
    specialization_id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

INSERT INTO specializations (department_id, name, description) VALUES
(1, 'Software Engineering', 'SE Spec.'),
(2, 'Power Systems', 'Power Spec.');

-- Projects table
CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    student_id INT,
    supervisor_id INT,
    status ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (supervisor_id) REFERENCES users(user_id)
);

INSERT INTO projects (title, description, student_id, supervisor_id, status)
VALUES
('Library Management System', 'A digital library platform.', 3, 2, 'in_progress'),
('Smart Grid Project', 'Electric grid optimization.', 3, 2, 'pending');

-- Project stages table
CREATE TABLE stages (
    stage_id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    status ENUM('pending', 'submitted', 'reviewed', 'approved', 'rejected') DEFAULT 'pending',
    grade DECIMAL(5,2),
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id)
);

INSERT INTO stages (project_id, title, description, due_date, status)
VALUES
(1, 'Requirements Analysis', 'Gather requirements', '2024-12-01', 'submitted'),
(1, 'Design Phase', 'System design', '2025-01-10', 'pending');

-- Attachments table
CREATE TABLE attachments (
    attachment_id INT PRIMARY KEY AUTO_INCREMENT,
    stage_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stage_id) REFERENCES stages(stage_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

INSERT INTO attachments (stage_id, file_name, file_path, uploaded_by)
VALUES
(1, 'requirements.pdf', '/uploads/requirements.pdf', 3);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

INSERT INTO notifications (user_id, title, message, is_read)
VALUES
(3, 'Stage 1 Graded', 'You received a grade on stage 1.', 0),
(2, 'New Project Assigned', 'You have a new student project to supervise.', 0);

-- Permissions table
CREATE TABLE permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

INSERT INTO permissions (name, description) VALUES
('manage_users', 'Can manage user accounts'),
('manage_departments', 'Can manage departments and specializations'),
('manage_projects', 'Can manage projects'),
('review_projects', 'Can review and grade projects'),
('create_projects', 'Can create new projects'),
('upload_files', 'Can upload project files'),
('view_statistics', 'Can view system statistics');

-- Role permissions table
CREATE TABLE role_permissions (
    role ENUM('admin', 'supervisor', 'student'),
    permission_id INT,
    PRIMARY KEY (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
);

INSERT INTO role_permissions (role, permission_id) VALUES
('admin', 1), ('admin', 2), ('admin', 3), ('admin', 7),
('supervisor', 3), ('supervisor', 4), ('supervisor', 7),
('student', 5), ('student', 6);
