<?php
// students/stage_view.php
session_start();
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'student' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notifications.php';

$studentId = $_SESSION[ 'user_id' ];
$stageId   = isset( $_GET[ 'stage_id' ] ) ? ( int )$_GET[ 'stage_id' ] : 0;

// 1 ) Load this stage and ensure it belongs to one of this student’s projects
$stmt = $pdo->prepare( "
    SELECT
      s.stage_id, s.title AS stage_title, s.description, s.due_date,
      s.status, s.grade, s.feedback, s.created_at, s.updated_at,
      p.project_id, p.title AS project_title,
      sup.username AS supervisor_name
    FROM stages s
    JOIN projects p ON s.project_id = p.project_id
    JOIN users sup ON p.supervisor_id = sup.user_id
    WHERE s.stage_id = ? AND p.student_id = ?
" );
$stmt->execute( [ $stageId, $studentId ] );
$stage = $stmt->fetch( PDO::FETCH_ASSOC );
if ( !$stage ) {
    die( 'Access denied or stage not found.' );
}

// 2 ) Load attachments for this stage
$attStmt = $pdo->prepare( "
    SELECT attachment_id, file_name, file_path, created_at
    FROM attachments
    WHERE stage_id = ?
    ORDER BY created_at ASC
" );
$attStmt->execute( [ $stageId ] );
$attachments = $attStmt->fetchAll( PDO::FETCH_ASSOC );

// 3 ) Notification count
$unreadCount = get_unread_notifications_count( $pdo, $studentId );
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8'>
<meta name = 'viewport' content = 'width=device-width,initial-scale=1'>
<title>Stage View | Project Tracker</title>
<!-- AdminLTE & dependencies -->
<link rel = 'stylesheet' href = '../plugins/fontawesome-free/css/all.min.css'>
<link rel = 'stylesheet' href = '../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
<link rel = 'stylesheet' href = '../dist/css/adminlte.min.css'>
</head>

<body class = 'hold-transition sidebar-mini layout-fixed'>
<div class = 'wrapper'>

<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link' data-widget = 'pushmenu' href = '#'><i class = 'fas fa-bars'></i></a>
</li>
</ul>
<ul class = 'navbar-nav ml-auto'>
<!-- Notifications -->
<li class = 'nav-item dropdown'>
<a class = 'nav-link' data-toggle = 'dropdown' href = '#'>
<i class = 'far fa-bell'></i>
<?php if ( $unreadCount ): ?>
<span class = 'badge badge-warning navbar-badge'><?php echo $unreadCount;
?></span>
<?php endif;
?>
</a>
<div class = 'dropdown-menu dropdown-menu-lg dropdown-menu-right'>
<span class = 'dropdown-header'><?php echo $unreadCount;
?> Notifications</span>
<div class = 'dropdown-divider'></div>
<a href = 'notifications.php' class = 'dropdown-item dropdown-footer'>See All Notifications</a>
</div>
</li>
<!-- Logout -->
<li class = 'nav-item'>
<a class = 'nav-link' href = '../auth/logout.php'><i class = 'fas fa-sign-out-alt'></i></a>
</li>
</ul>
</nav>
<!-- /.navbar -->

<!-- Main Sidebar -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = 'dashboard.php' class = 'brand-link'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview'>
<li class = 'nav-item'>
<a href = 'dashboard.php' class = 'nav-link'><i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'projects.php' class = 'nav-link'><i class = 'nav-icon fas fa-project-diagram'></i>
<p>My Projects</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'create_project.php' class = 'nav-link'><i class = 'nav-icon fas fa-plus-circle'></i>
<p>Create Project</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'stage_upload.php' class = 'nav-link'><i class = 'nav-icon fas fa-upload'></i>
<p>Upload Stage</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'notifications.php' class = 'nav-link'><i class = 'nav-icon far fa-bell'></i>
<p>Notifications</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'profile.php' class = 'nav-link'><i class = 'nav-icon fas fa-user'></i>
<p>Profile</p>
</a>
</li>
<li class = 'nav-item'>
<a href = '../auth/logout.php' class = 'nav-link'><i class = 'nav-icon fas fa-sign-out-alt'></i>
<p>Logout</p>
</a>
</li>
</ul>
</nav>
</div>
</aside>

<!-- Content Wrapper -->
<div class = 'content-wrapper p-4'>
<div class = 'container-fluid'>

<!-- Back Button -->
<a href = "project_view.php?project_id=<?php echo $stage['project_id']; ?>" class = 'btn btn-secondary mb-3'>
&laquo;
Back to Project
</a>

<!-- Stage Card -->
<div class = 'card card-primary mb-4'>
<div class = 'card-header'>
<h3 class = 'card-title'><?php echo htmlspecialchars( $stage[ 'stage_title' ] );
?></h3>
</div>
<div class = 'card-body'>
<p><strong>Project:</strong>
<a href = "project_view.php?project_id=<?php echo $stage['project_id']; ?>">
<?php echo htmlspecialchars( $stage[ 'project_title' ] );
?>
</a>
</p>
<p><strong>Supervisor:</strong> <?php echo htmlspecialchars( $stage[ 'supervisor_name' ] );
?></p>
<p><strong>Status:</strong>
<span class = "badge badge-<?php
              echo match($stage['status']) {
                'submitted' => 'info',
                'approved'  => 'success',
                'rejected'  => 'danger',
                default     => 'secondary',
              };
            ?>"><?php echo ucfirst( $stage[ 'status' ] );
?></span>
</p>
<p><strong>Due Date:</strong> <?php echo htmlspecialchars( $stage[ 'due_date' ] );
?></p>
<?php if ( $stage[ 'grade' ] !== null ): ?>
<p><strong>Grade:</strong> <?php echo number_format( $stage[ 'grade' ], 2 );
?></p>
<?php endif;
?>
<?php if ( $stage[ 'feedback' ] ): ?>
<p><strong>Feedback:</strong><br><?php echo nl2br( htmlspecialchars( $stage[ 'feedback' ] ) );
?></p>
<?php endif;
?>
<p><small class = 'text-muted'>
Created on <?php echo date( 'Y-m-d H:i', strtotime( $stage[ 'created_at' ] ) );
?>
<?php if ( $stage[ 'updated_at' ] ): ?>
&mdash;
Updated on <?php echo date( 'Y-m-d H:i', strtotime( $stage[ 'updated_at' ] ) );
?>
<?php endif;
?>
</small></p>
</div>
</div>

<!-- Attachments -->
<div class = 'card card-secondary'>
<div class = 'card-header'>
<h3 class = 'card-title'>Attachments</h3>
</div>
<div class = 'card-body'>
<?php if ( $attachments ): ?>
<ul class = 'list-group'>
<?php foreach ( $attachments as $file ): ?>
<li class = 'list-group-item d-flex justify-content-between align-items-center'>
<a href = "../uploads/<?php echo urlencode($file['file_path']); ?>" target = '_blank'>
<?php echo htmlspecialchars( $file[ 'file_name' ] );
?>
</a>
<span class = 'text-muted'><?php echo date( 'Y-m-d', strtotime( $file[ 'created_at' ] ) );
?></span>
</li>
<?php endforeach;
?>
</ul>
<?php else: ?>
<p class = 'text-muted'>No files uploaded for this stage.</p>
<?php endif;
?>
</div>
</div>

</div>
</div>

<!-- Footer -->
<footer class = 'main-footer text-center'>
<strong>&copy;
<?php echo date( 'Y' );
?> Project Tracker</strong>
</footer>
</div>

<!-- Scripts -->
<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>
</body>

</html>
