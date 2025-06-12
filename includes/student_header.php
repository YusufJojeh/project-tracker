<?php
// students/dashboard.php
session_start();
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'student' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

// Fetch projects
$stmt = $pdo->prepare( "
    SELECT
        p.*,
        u.username AS supervisor_name,
        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id) AS total_stages,
        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id AND status = 'approved') AS completed_stages
    FROM projects p
    JOIN users u ON p.supervisor_id = u.user_id
    WHERE p.student_id = ?
    ORDER BY p.updated_at DESC
" );
$stmt->execute( [ $_SESSION[ 'user_id' ] ] );
$projects = $stmt->fetchAll( PDO::FETCH_ASSOC );

// Fetch notifications
$notifications = get_recent_notifications( $pdo, $_SESSION[ 'user_id' ], 5 );
$unreadCount   = get_unread_notifications_count( $pdo, $_SESSION[ 'user_id' ] );
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1'>
<title>Student Dashboard | Project Tracker</title>

<!-- Google Font: Source Sans Pro -->
<link rel = 'stylesheet'
href = 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
<!-- Font Awesome -->
<link rel = 'stylesheet' href = '../plugins/fontawesome-free/css/all.min.css'>
<!-- overlayScrollbars -->
<link rel = 'stylesheet' href = '../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
<!-- Theme style -->
<link rel = 'stylesheet' href = '../dist/css/adminlte.min.css'>
</head>

<body class = 'hold-transition sidebar-mini layout-fixed'>
<div class = 'wrapper'>
<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<!-- Left navbar: toggle sidebar -->
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link' data-widget = 'pushmenu' href = '#' role = 'button'>
<i class = 'fas fa-bars'></i>
</a>
</li>
</ul>
<!-- Right navbar: notifications -->
<ul class = 'navbar-nav ml-auto'>
<!-- Notifications dropdown -->
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
<?php if ( empty( $notifications ) ): ?>
<a href = '#' class = 'dropdown-item'>No notifications</a>
<?php else: ?>
<?php foreach ( $notifications as $note ):
$ago = time_elapsed_string( $note[ 'created_at' ] );
?>
<a href = '#' class = 'dropdown-item'>
<i class = 'fas fa-envelope mr-2'></i> <?php echo h( $note[ 'title' ] );
?>
<span class = 'float-right text-muted text-sm'><?php echo $ago;
?></span>
</a>
<div class = 'dropdown-divider'></div>
<?php endforeach;
?>
<a href = 'notifications.php' class = 'dropdown-item dropdown-footer'>See All Notifications</a>
<?php endif;
?>
</div>
</li>
<!-- Fullscreen & Control Sidebar omitted for brevity -->
</ul>
</nav>
<!-- /.navbar -->

<!-- Main Sidebar Container -->
<!-- Main Sidebar Container -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<!-- Brand Logo -->
<a href = '#' class = 'brand-link'>
<img src = '../dist/img/AdminLTELogo.png' alt = 'Logo' class = 'brand-image img-circle elevation-3'
style = 'opacity: .8'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>

<!-- Sidebar -->
<div class = 'sidebar'>
<!-- User panel -->
<div class = 'user-panel mt-3 pb-3 mb-3 d-flex'>
<div class = 'image'>
<img src = '../dist/img/user2-160x160.jpg' class = 'img-circle elevation-2' alt = 'User'>
</div>
<div class = 'info'>
<a href = 'profile.php' class = 'd-block'><?php echo h( $_SESSION[ 'username' ] );
?></a>
</div>
</div>

<!-- SidebarSearch Form -->
<div class = 'form-inline'>
<div class = 'input-group' data-widget = 'sidebar-search'>
<input class = 'form-control form-control-sidebar' type = 'search' placeholder = 'Search' aria-label = 'Search'>
<div class = 'input-group-append'>
<button class = 'btn btn-sidebar'><i class = 'fas fa-search fa-fw'></i></button>
</div>
</div>
</div>

<!-- Sidebar Menu -->
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview' role = 'menu' data-accordion = 'false'>

<!-- Dashboard -->
<li class = 'nav-item'>
<a href = 'dashboard.php' class = "nav-link <?php echo $current_page==='dashboard'?'active':'';?>">
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a>
</li>

<!-- My Projects -->
<li class = 'nav-item'>
<a href = 'projects.php' class = "nav-link <?php echo $current_page==='projects'?'active':'';?>">
<i class = 'nav-icon fas fa-project-diagram'></i>
<p>My Projects</p>
</a>
</li>

<!-- New Project -->
<li class = 'nav-item'>
<a href = 'project_create.php' class = "nav-link <?php echo $current_page==='new_project'?'active':'';?>">
<i class = 'nav-icon fas fa-plus-circle'></i>
<p>Create New Project</p>
</a>
</li>

<!-- Upload Stage -->
<li class = 'nav-item'>
<a href = 'stage_upload.php' class = "nav-link <?php echo $current_page==='upload_stage'?'active':'';?>">
<i class = 'nav-icon fas fa-upload'></i>
<p>Upload Stage</p>
</a>
</li>

<!-- Notifications -->
<li class = 'nav-item'>
<a href = 'notifications.php' class = "nav-link <?php echo $current_page==='notifications'?'active':'';?>">
<i class = 'nav-icon far fa-bell'></i>
<p>Notifications
<?php if ( $unreadCount ): ?>
<span class = 'badge badge-warning right'><?php echo $unreadCount;
?></span>
<?php endif;
?>
</p>
</a>
</li>

<!-- Profile -->
<li class = 'nav-item'>
<a href = 'profile.php' class = "nav-link <?php echo $current_page==='profile'?'active':'';?>">
<i class = 'nav-icon fas fa-user'></i>
<p>Profile</p>
</a>
</li>

<!-- Logout -->
<li class = 'nav-item'>
<a href = '../auth/logout.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-sign-out-alt'></i>
<p>Logout</p>
</a>
</li>

</ul>
</nav>
<!-- /.sidebar-menu -->
</div>
<!-- /.sidebar -->
</aside>

<!-- Content Wrapper. Contains page content -->
<div class = 'content-wrapper'>
<!-- Page header -->
<section class = 'content-header'>
<div class = 'container-fluid'>
<h1>Student Dashboard</h1>
</div>
</section>
