<?php
/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require user to be logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Require user to have specific role
 */
function require_role($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header('Location: ../auth/login.php');
        exit();
    }
}

/**
 * Get user's full name
 */
function get_user_name($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT username FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Get user's email
 */
function get_user_email($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Get user's department
 */
function get_user_department($pdo, $user_id) {
    $stmt = $pdo->prepare('
        SELECT d.name
        FROM users u
        LEFT JOIN students s ON u.user_id = s.student_id
        LEFT JOIN supervisors sp ON u.user_id = sp.supervisor_id
        LEFT JOIN departments d ON s.department_id = d.department_id OR sp.department_id = d.department_id
        WHERE u.user_id = ?
    ');
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Get supervisor's specialization
 */
function get_supervisor_specialization($pdo, $supervisor_id) {
    $stmt = $pdo->prepare('
        SELECT s.name
        FROM supervisors sp
        JOIN specializations s ON sp.specialization_id = s.specialization_id
        WHERE sp.supervisor_id = ?
    ');
    $stmt->execute([$supervisor_id]);
    return $stmt->fetchColumn();
}

/**
 * Format date
 */
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

/**
 * Get the Bootstrap badge class for a project status
 */
function get_status_badge_class($status) {
    switch ($status) {
        case 'completed':
            return 'success';
        case 'in_progress':
            return 'primary';
        case 'pending':
            return 'warning';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Get the Bootstrap badge class for a stage status
 */
function get_stage_status_class($status) {
    switch ($status) {
        case 'pending':
            return 'secondary';
        case 'submitted':
            return 'info';
        case 'reviewed':
            return 'primary';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Get project progress percentage
 */
function get_project_progress($pdo, $project_id) {
    $stmt = $pdo->prepare('
        SELECT
            COUNT(*) as total_stages,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as completed_stages
        FROM stages
        WHERE project_id = ?
    ');
    $stmt->execute([$project_id]);
    $result = $stmt->fetch();

    if ($result['total_stages'] > 0) {
        return round(($result['completed_stages'] / $result['total_stages']) * 100);
    }
    return 0;
}

/**
 * Get project grade
 */
function get_project_grade($pdo, $project_id) {
    $stmt = $pdo->prepare('
        SELECT AVG(grade) as average_grade
        FROM stages
        WHERE project_id = ? AND status = "approved"
    ');
    $stmt->execute([$project_id]);
    return $stmt->fetchColumn();
}

/**
 * Get supervisor's current student count
 */
function get_supervisor_student_count($pdo, $supervisor_id) {
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM projects
        WHERE supervisor_id = ? AND status != "rejected"
    ');
    $stmt->execute([$supervisor_id]);
    return $stmt->fetchColumn();
}

/**
 * Check if supervisor can accept more students
 */
function can_supervisor_accept_students($pdo, $supervisor_id) {
    $current_count = get_supervisor_student_count($pdo, $supervisor_id);
    $max_students = 5; // Maximum number of students per supervisor
    return $current_count < $max_students;
}

/**
 * Get student's GPA
 */
function get_student_gpa($pdo, $student_id) {
    $stmt = $pdo->prepare('
        SELECT AVG(grade) as gpa
        FROM stages s
        JOIN projects p ON s.project_id = p.project_id
        WHERE p.student_id = ? AND s.status = "approved"
    ');
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn();
}

/**
 * Get department name
 */
function get_department_name($pdo, $department_id) {
    $stmt = $pdo->prepare('SELECT name FROM departments WHERE department_id = ?');
    $stmt->execute([$department_id]);
    return $stmt->fetchColumn();
}

/**
 * Get specialization name
 */
function get_specialization_name($pdo, $specialization_id) {
    $stmt = $pdo->prepare('SELECT name FROM specializations WHERE specialization_id = ?');
    $stmt->execute([$specialization_id]);
    return $stmt->fetchColumn();
}

/**
 * Get user's role
 */
function get_user_role($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Check if user has permission
 */
function has_permission($pdo, $user_id, $permission_name) {
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.permission_id
        JOIN users u ON u.role = rp.role
        WHERE u.user_id = ? AND p.name = ?
    ');
    $stmt->execute([$user_id, $permission_name]);
    return $stmt->fetchColumn() > 0;
}

/**
 * HTML escape function
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format file size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get file icon class
 */
function get_file_icon_class($file_type) {
    $icons = [
        'pdf' => 'fas fa-file-pdf',
        'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word',
        'xls' => 'fas fa-file-excel',
        'xlsx' => 'fas fa-file-excel',
        'ppt' => 'fas fa-file-powerpoint',
        'pptx' => 'fas fa-file-powerpoint',
        'zip' => 'fas fa-file-archive',
        'rar' => 'fas fa-file-archive',
        'txt' => 'fas fa-file-alt',
        'jpg' => 'fas fa-file-image',
        'jpeg' => 'fas fa-file-image',
        'png' => 'fas fa-file-image',
        'gif' => 'fas fa-file-image'
    ];

    $extension = strtolower(pathinfo($file_type, PATHINFO_EXTENSION));
    return isset($icons[$extension]) ? $icons[$extension] : 'fas fa-file';
}

/**
 * Format time elapsed
 */


/**
 * Function to get unread notifications count
 */


/**
 * Function to mark notification as read
 */


/**
 * Function to handle file uploads
 */
function handle_file_upload($file, $upload_dir) {
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = basename($file['name']);
    $file_path = $upload_dir . '/' . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return [
            'success' => true,
            'file_name' => $file_name,
            'file_path' => $file_path
        ];
    }

    return [
        'success' => false,
        'error' => 'Failed to upload file'
    ];
}
