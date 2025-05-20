<?php
// supervisors/assigned_projects.php
session_start();

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/notifications.php';

// Only supervisors allowed
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}
$supervisorId = ( int )$_SESSION[ 'user_id' ];

// Fetch all projects supervised by this user
$stmt = $pdo->prepare( "
    SELECT
      p.project_id,
      p.title,
      p.created_at,
      u.username AS student_name
    FROM projects p
    JOIN users   u ON p.student_id = u.user_id
    WHERE p.supervisor_id = ?
    ORDER BY p.created_at DESC
" );
$stmt->execute( [ $supervisorId ] );
$projects = $stmt->fetchAll( PDO::FETCH_ASSOC );

// Unread count for navbar badge
$unreadCount = get_unread_notifications_count( $pdo, $supervisorId );
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8'>
<meta name = 'viewport' content = 'width=device-width,initial-scale=1'>
<title>View Projects &mdash;
Supervisor</title>
<!-- AdminLTE & dependencies -->
<link rel = 'stylesheet' href = '../plugins/fontawesome-free/css/all.min.css'>
<link rel = 'stylesheet' href = '../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
<link rel = 'stylesheet' href = '../dist/css/adminlte.min.css'>
</head>

<body class = 'hold-transition sidebar-mini layout-fixed'>
<div class = 'wrapper'>

<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<!-- sidebar toggle -->
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link' data-widget = 'pushmenu' href = '#'><i class = 'fas fa-bars'></i></a>
</li>
</ul>
<!-- right nav -->
<ul class = 'navbar-nav ml-auto'>
<!-- notifications -->
<li class = 'nav-item dropdown'>
<a class = 'nav-link' data-toggle = 'dropdown' href = '#'>
<i class = 'far fa-bell'></i>
<?php if ( $unreadCount ): ?>
<span class = 'badge badge-warning navbar-badge'><?php echo $unreadCount ?></span>
<?php endif;
?>
</a>
<div class = 'dropdown-menu dropdown-menu-lg dropdown-menu-right'>
<span class = 'dropdown-item dropdown-header'><?php echo $unreadCount ?> New</span>
<div class = 'dropdown-divider'></div>
<a href = 'notifications.php' class = 'dropdown-item dropdown-footer'>See All Notifications</a>
</div>
</li>
<!-- logout -->
<li class = 'nav-item'>
<a class = 'nav-link' href = '../auth/logout.php'><i class = 'fas fa-sign-out-alt'></i></a>
</li>
</ul>
</nav>
<!-- /.navbar -->

<!-- Sidebar -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = 'dashboard.php' class = 'brand-link text-center'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview'>
<li class = 'nav-item'>
<a href = 'dashboard.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'assigned_projects.php' class = 'nav-link active'>
<i class = 'nav-icon fas fa-project-diagram'></i>
<p>View Projects</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'notifications.php' class = 'nav-link'>
<i class = 'nav-icon far fa-bell'></i>
<p>Notifications</p>
</a>
</li>
<li class = 'nav-item'>
<a href = '../auth/logout.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-sign-out-alt'></i>
<p>Logout</p>
</a>
</li>
</ul>
</nav>
</div>
</aside>

<!-- Content Wrapper -->
<div class = 'content-wrapper'>
<!-- Header -->
<section class = 'content-header'>
<div class = 'container-fluid d-flex justify-content-between align-items-center mb-2'>
<h1 class = 'm-0'>All Assigned Projects</h1>
</div>
</section>

<!-- Main content -->
<section class = 'content'>
<div class = 'container-fluid'>

<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'card-title'>Projects</h3>
</div>
<div class = 'card-body p-0'>
<table class = 'table table-hover'>
<thead>
<tr>
<th>Title</th>
<th>Student</th>
<th>Created</th>
<th style = 'width:120px'>Action</th>
</tr>
</thead>
<tbody>
<?php if ( empty( $projects ) ): ?>
<tr>
<td colspan = '4' class = 'text-center text-muted'>No projects assigned yet.</td>
</tr>
<?php else: foreach ( $projects as $proj ): ?>
<tr>
<td><?php echo htmlspecialchars( $proj[ 'title' ], ENT_QUOTES ) ?></td>
<td><?php echo htmlspecialchars( $proj[ 'student_name' ], ENT_QUOTES ) ?></td>
<td><?php echo date( 'Y-m-d', strtotime( $proj[ 'created_at' ] ) ) ?></td>
<td>
<a href = "project_view.php?project_id=<?php echo $proj['project_id'] ?>"

class = 'btn btn-info btn-sm'>
<i class = 'fas fa-eye'></i> View
</a>
</td>
</tr>
<?php endforeach;
endif;
?>
</tbody>
</table>
</div>
</div>

</div>
</section>
</div>
<!-- /.content-wrapper -->

<!-- Footer -->
<footer class = 'main-footer text-center'>
<strong>&copy;
<?php echo date( 'Y' ) ?> Project Tracker</strong>
</footer>
</div>
<!-- ./wrapper -->

<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>
</body>

</html>