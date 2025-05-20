<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if ( !isset( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'admin' ) {
    header( 'Location: ../auth/login.php' );
    exit();
}

// Use $pdo as database connection
if ( !isset( $pdo ) ) {
    die( 'Database connection error.' );
}

// Define fallback for 'h' function if not present
if ( !function_exists( 'h' ) ) {
    function h( $string ) {
        return htmlspecialchars( $string, ENT_QUOTES, 'UTF-8' );
    }
}

// Define fallback for status badge class if not present
if ( !function_exists( 'get_status_badge_class' ) ) {
    function get_status_badge_class( $status ) {
        switch ( $status ) {
            case 'completed': return 'success';
            case 'in_progress': return 'info';
            case 'pending': return 'warning';
            case 'rejected': return 'danger';
            default: return 'secondary';
        }
    }
}

// Get statistics with real data
$stats = [
    'total_students'    => $pdo->query( "SELECT COUNT(*) FROM users WHERE role = 'student'" )->fetchColumn(),
    'total_supervisors' => $pdo->query( "SELECT COUNT(*) FROM users WHERE role = 'supervisor'" )->fetchColumn(),
    'total_projects'    => $pdo->query( 'SELECT COUNT(*) FROM projects' )->fetchColumn(),
    'active_projects'   => $pdo->query( "SELECT COUNT(*) FROM projects WHERE status = 'in_progress'" )->fetchColumn(),
    'completed_projects'=> $pdo->query( "SELECT COUNT(*) FROM projects WHERE status = 'completed'" )->fetchColumn(),
    'pending_projects'  => $pdo->query( "SELECT COUNT(*) FROM projects WHERE status = 'pending'" )->fetchColumn()
];

// Get recent users with more details
$stmt = $pdo->query( "
    SELECT u.user_id, u.username, u.email, u.role, u.created_at,
           COUNT(p.project_id) as total_projects
    FROM users u
    LEFT JOIN projects p ON u.user_id = p.student_id OR u.user_id = p.supervisor_id
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
    LIMIT 5
" );
$recent_users = $stmt->fetchAll( PDO::FETCH_ASSOC );

// Get recent projects
$stmt = $pdo->query( "
    SELECT p.project_id, p.title, p.status, p.created_at,
           s.username as student_name,
           sp.username as supervisor_name
    FROM projects p
    LEFT JOIN users s ON p.student_id = s.user_id
    LEFT JOIN users sp ON p.supervisor_id = sp.user_id
    ORDER BY p.created_at DESC
    LIMIT 5
" );
$recent_projects = $stmt->fetchAll( PDO::FETCH_ASSOC );

// Get project statistics by status
$stmt = $pdo->query( "
    SELECT status, COUNT(*) as count
    FROM projects
    GROUP BY status
" );
$project_stats = $stmt->fetchAll( PDO::FETCH_KEY_PAIR );
?>

<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'UTF-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
<title>Admin Dashboard - Project Tracker</title>
<!-- Google Font: Source Sans Pro -->
<link rel = 'stylesheet'
href = 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
<!-- Font Awesome -->
<link rel = 'stylesheet' href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
<!-- Ionicons -->
<link rel = 'stylesheet' href = 'https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css'>
<!-- Theme style -->
<link rel = 'stylesheet' href = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
<!-- Custom CSS -->
<style>
.content-wrapper {
    background-color: #f4f6f9;
}

.card {
    box-shadow: 0 0 1px rgba( 0, 0, 0, .125 ), 0 1px 3px rgba( 0, 0, 0, .2 );
    margin-bottom: 1rem;
}

.small-box {
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 0 1px rgba( 0, 0, 0, .125 ), 0 1px 3px rgba( 0, 0, 0, .2 );
}

.small-box>.inner {
    padding: 10px;
}

.small-box h3 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
    white-space: nowrap;
    padding: 0;
}

.small-box p {
    font-size: 1rem;
}

.small-box .icon {
    color: rgba( 0, 0, 0, .15 );
    z-index: 0;
}

.small-box .icon>i {
    font-size: 70px;
    top: 20px;
}
</style>
</head>

<body class = 'hold-transition sidebar-mini'>
<div class = 'wrapper'>
<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<!-- Left navbar links -->
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link' data-widget = 'pushmenu' href = '#' role = 'button'><i class = 'fas fa-bars'></i></a>
</li>
</ul>
<!-- Right navbar links -->
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../auth/logout.php'>
<i class = 'fas fa-sign-out-alt'></i> Logout
</a>
</li>
</ul>
</nav>

<!-- Main Sidebar Container -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<!-- Brand Logo -->
<a href = 'dashboard.php' class = 'brand-link'>
<img src = 'https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png' alt = 'AdminLTE Logo'

class = 'brand-image img-circle elevation-3' style = 'opacity: .8'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<!-- Sidebar -->
<div class = 'sidebar'>
<!-- Sidebar Menu -->
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview' role = 'menu'>
<li class = 'nav-item'>
<a href = 'dashboard.php' class = 'nav-link active'>
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'users.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-users'></i>
<p>Users</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'departments.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-building'></i>
<p>Departments</p>
</a>
</li>
<li class = 'nav-item'>
<a href = './roles/index.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-user-tag'></i>
<p>Roles & Permissions</p>
</a>
</li>
</ul>
</nav>
</div>
</aside>

<!-- Content Wrapper -->
<div class = 'content-wrapper'>
<!-- Content Header -->
<div class = 'content-header'>
<div class = 'container-fluid'>
<div class = 'row mb-2'>
<div class = 'col-sm-6'>
<h1 class = 'm-0'>Dashboard</h1>
</div>
</div>
</div>
</div>

<!-- Main content -->
<div class = 'content'>
<div class = 'container-fluid'>
<!-- Statistics Cards -->
<div class = 'row'>
<div class = 'col-lg-3 col-6'>
<div class = 'small-box bg-info'>
<div class = 'inner'>
<h3><?php echo $stats[ 'total_students' ];
?></h3>
<p>Total Students</p>
</div>
<div class = 'icon'>
<i class = 'fas fa-user-graduate'></i>
</div>
</div>
</div>
<div class = 'col-lg-3 col-6'>
<div class = 'small-box bg-success'>
<div class = 'inner'>
<h3><?php echo $stats[ 'total_supervisors' ];
?></h3>
<p>Total Supervisors</p>
</div>
<div class = 'icon'>
<i class = 'fas fa-chalkboard-teacher'></i>
</div>
</div>
</div>
<div class = 'col-lg-3 col-6'>
<div class = 'small-box bg-warning'>
<div class = 'inner'>
<h3><?php echo $stats[ 'total_projects' ];
?></h3>
<p>Total Projects</p>
</div>
<div class = 'icon'>
<i class = 'fas fa-project-diagram'></i>
</div>
</div>
</div>
<div class = 'col-lg-3 col-6'>
<div class = 'small-box bg-danger'>
<div class = 'inner'>
<h3><?php echo $stats[ 'active_projects' ];
?></h3>
<p>Active Projects</p>
</div>
<div class = 'icon'>
<i class = 'fas fa-tasks'></i>
</div>
</div>
</div>
</div>

<!-- Main content row -->
<div class = 'row'>
<!-- Recent Users -->
<div class = 'col-md-6'>
<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'card-title'>
<i class = 'fas fa-users mr-1'></i>
Recent Users
</h3>
</div>
<div class = 'card-body p-0'>
<div class = 'table-responsive'>
<table class = 'table table-hover'>
<thead>
<tr>
<th>Username</th>
<th>Role</th>
<th>Projects</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ( $recent_users as $user ): ?>
<tr>
<td>
<div class = 'd-flex align-items-center'>
<i class = 'fas fa-user-circle fa-2x mr-2'></i>
<div>
<div><?php echo h( $user[ 'username' ] );
?></div>
<small class = 'text-muted'><?php echo h( $user[ 'email' ] );
?></small>
</div>
</div>
</td>
<td>
<span

class = "badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'supervisor' ? 'success' : 'info'); ?>">
<?php echo ucfirst( $user[ 'role' ] );
?>
</span>
</td>
<td><?php echo $user[ 'total_projects' ];
?></td>
<td>
<a href = "edit_user.php?id=<?php echo $user['user_id']; ?>" class = 'btn btn-sm btn-primary'>
<i class = 'fas fa-edit'></i>
</a>
<a href = "delete_user.php?id=<?php echo $user['user_id']; ?>" class = 'btn btn-sm btn-danger'
onclick = "return confirm('Are you sure?')">
<i class = 'fas fa-trash'></i>
</a>
</td>
</tr>
<?php endforeach;
?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<!-- Recent Projects -->
<div class = 'col-md-6'>
<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'card-title'>
<i class = 'fas fa-project-diagram mr-1'></i>
Recent Projects
</h3>
</div>
<div class = 'card-body p-0'>
<div class = 'table-responsive'>
<table class = 'table table-hover'>
<thead>
<tr>
<th>Title</th>
<th>Student</th>
<th>Supervisor</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ( $recent_projects as $project ): ?>
<tr>
<td>
<div class = 'd-flex align-items-center'>
<i class = 'fas fa-file-alt fa-2x mr-2'></i>
<div>
<div><?php echo h( $project[ 'title' ] );
?></div>
<small class = 'text-muted'><?php echo date( 'M d, Y', strtotime( $project[ 'created_at' ] ) );
?></small>
</div>
</div>
</td>
<td><?php echo h( $project[ 'student_name' ] );
?></td>
<td><?php echo h( $project[ 'supervisor_name' ] );
?></td>
<td>
<span class = "badge badge-<?php echo get_status_badge_class($project['status']); ?>">
<?php echo ucfirst( str_replace( '_', ' ', $project[ 'status' ] ) );
?>
</span>
</td>
<td>
<a href = "view_project.php?id=<?php echo $project['project_id']; ?>"

class = 'btn btn-sm btn-info'>
<i class = 'fas fa-eye'></i>
</a>
</td>
</tr>
<?php endforeach;
?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<!-- Project Statistics -->
<div class = 'row'>
<div class = 'col-md-12'>
<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'card-title'>
<i class = 'fas fa-chart-pie mr-1'></i>
Project Statistics
</h3>
</div>
<div class = 'card-body'>
<div class = 'row'>
<div class = 'col-md-3'>
<div class = 'info-box'>
<span class = 'info-box-icon bg-warning'>
<i class = 'fas fa-clock'></i>
</span>
<div class = 'info-box-content'>
<span class = 'info-box-text'>Pending</span>
<span class = 'info-box-number'><?php echo isset( $project_stats[ 'pending' ] ) ? $project_stats[ 'pending' ] : 0;
?></span>
</div>
</div>
</div>
<div class = 'col-md-3'>
<div class = 'info-box'>
<span class = 'info-box-icon bg-info'>
<i class = 'fas fa-spinner'></i>
</span>
<div class = 'info-box-content'>
<span class = 'info-box-text'>In Progress</span>
<span class = 'info-box-number'><?php echo isset( $project_stats[ 'in_progress' ] ) ? $project_stats[ 'in_progress' ] : 0;
?></span>
</div>
</div>
</div>
<div class = 'col-md-3'>
<div class = 'info-box'>
<span class = 'info-box-icon bg-success'>
<i class = 'fas fa-check'></i>
</span>
<div class = 'info-box-content'>
<span class = 'info-box-text'>Completed</span>
<span class = 'info-box-number'><?php echo isset( $project_stats[ 'completed' ] ) ? $project_stats[ 'completed' ] : 0;
?></span>
</div>
</div>
</div>
<div class = 'col-md-3'>
<div class = 'info-box'>
<span class = 'info-box-icon bg-danger'>
<i class = 'fas fa-times'></i>
</span>
<div class = 'info-box-content'>
<span class = 'info-box-text'>Rejected</span>
<span class = 'info-box-number'><?php echo isset( $project_stats[ 'rejected' ] ) ? $project_stats[ 'rejected' ] : 0;
?></span>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Footer -->
<footer class = 'main-footer'>
<div class = 'float-right d-none d-sm-block'>
<b>Version</b> 1.0.0
</div>
<strong>Copyright &copy;
<?php echo date( 'Y' );
?> <a href = '#'>Project Tracker</a>.</strong> All rights reserved.
</footer>
</div>

<!-- jQuery -->
<script src = 'https://code.jquery.com/jquery-3.6.0.min.js'></script>
<!-- Bootstrap 4 -->
<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js'></script>
<!-- AdminLTE App -->
<script src = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js'></script>
</body>

</html>