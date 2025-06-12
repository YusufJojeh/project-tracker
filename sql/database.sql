-- Create database and use it
DROP DATABASE IF EXISTS project_tracker;
CREATE DATABASE project_tracker;
USE project_tracker;

-- Users Table Creation
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users Table Seeding
INSERT INTO users (username, email, password, role) VALUES
('admin1', 'admin1@example.com', 'password_hash_1', 'admin'),
('supervisor1', 'supervisor1@example.com', 'password_hash_2', 'supervisor'),
('student1', 'student1@example.com', 'password_hash_3', 'student'),
('admin2', 'admin2@example.com', 'password_hash_4', 'admin'),
('supervisor2', 'supervisor2@example.com', 'password_hash_5', 'supervisor'),
('student2', 'student2@example.com', 'password_hash_6', 'student');

-- Departments Table Creation
CREATE TABLE departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Departments Table Seeding
INSERT INTO departments (name, description) VALUES
('Department 1', 'Software Engineering and Systems Development'),
('Department 2', 'Project Management and Analysis'),
('Department 3', 'Data Science and Machine Learning'),
('Department 4', 'Human-Computer Interaction'),
('Department 5', 'Cybersecurity and Information Assurance');

-- Specializations Table Creation
CREATE TABLE specializations (
    specialization_id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- Specializations Table Seeding
INSERT INTO specializations (department_id, name, description) VALUES
(1, 'Software Architecture', 'Design and structure of software systems'),
(2, 'Project Management', 'Planning, execution, and control of projects'),
(3, 'Machine Learning', 'Development of algorithms and models'),
(4, 'User Experience Design', 'Improvement of interaction between users and systems'),
(5, 'Network Security', 'Protection of systems from cyber threats');

-- Projects Table Creation
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

-- Projects Table Seeding
INSERT INTO projects (title, description, student_id, supervisor_id, status) VALUES
('Project 1', 'This is a software development project focusing on web development.', 6, 5, 'in_progress'),
('Project 2', 'A data science project analyzing user data for insights.', 5, 4, 'pending'),
('Project 3', 'A machine learning model for predictive analytics.', 4, 3, 'in_progress'),
('Project 4', 'Designing a secure networking system for a company.', 6, 5, 'pending');

-- Stages Table Creation
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

-- Stages Table Seeding
INSERT INTO stages (project_id, title, description, due_date, status, grade, feedback) VALUES
(1, 'Stage 1: Requirement Gathering', 'Gather and document software requirements.', '2025-06-15', 'completed', 85.00, 'Well documented requirements.'),
(1, 'Stage 2: Design', 'Create design architecture for the project.', '2025-06-20', 'in_progress', NULL, NULL),
(2, 'Stage 1: Data Collection', 'Collect relevant data for analysis.', '2025-06-18', 'completed', 90.00, 'Good data quality.'),
(3, 'Stage 1: Model Training', 'Train the initial machine learning model with available data.', '2025-06-25', 'pending', NULL, NULL);

-- Permissions Table Creation
CREATE TABLE permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Permissions Table Seeding
INSERT INTO permissions (name, description) VALUES
('manage_users', 'Can manage user accounts'),
('manage_departments', 'Can manage departments and specializations'),
('manage_projects', 'Can manage projects'),
('review_projects', 'Can review and grade projects'),
('create_projects', 'Can create new projects'),
('upload_files', 'Can upload project files'),
('view_statistics', 'Can view system statistics');

-- Role Permissions Table Creation
CREATE TABLE role_permissions (
    role ENUM('admin', 'supervisor', 'student'),
    permission_id INT,
    PRIMARY KEY (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
);

-- Role Permissions Table Seeding
INSERT INTO role_permissions (role, permission_id) VALUES
('admin', 1),
('admin', 2),
('admin', 3),
('admin', 4),
('supervisor', 3),
('supervisor', 4),
('student', 5),
('student', 6);
