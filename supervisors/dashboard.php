<?php
// supervisors/dashboard.php
session_start();
require __DIR__ . '/../config/database.php';

// 1 ) Access control: only logged-in supervisors:
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}
$supervisorId = $_SESSION[ 'user_id' ];

// 2 ) Fetch stages for projects assigned to *this* supervisor
$stmt = $pdo->prepare( "
  SELECT
    s.stage_id,
    p.title       AS project_title,
    s.name        AS stage_name,
    s.due_date
  FROM stages s
  JOIN projects p ON s.project_id = p.project_id
  WHERE p.supervisor_id = ?
  ORDER BY s.created_at DESC
" );
$stmt->execute( [ $supervisorId ] );
$stages = $stmt->fetchAll( PDO::FETCH_ASSOC );
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'UTF-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1' />
<title>Supervisor Dashboard – MyApp</title>
<link rel = 'stylesheet' href = '../plugins/bootstrap/css/bootstrap.min.css' />
<link rel = 'stylesheet' href = '../plugins/fontawesome-free/css/all.min.css' />
<link rel = 'stylesheet' href = '../plugins/overlayScrollbars/css/OverlayScrollbars.min.css' />
<link rel = 'stylesheet' href = '../dist/css/adminlte.min.css' />
<style>
body,
.wrapper {
    background: #1e1e2f;
    color: #f0f0f0;
}

.main-sidebar {
    background: #2e2e3d;
}

.nav-sidebar .nav-link {
    color: #c1c1d1;
}

.nav-sidebar .nav-link.active {
    background: #4e73df;
    color: #fff;
}

.card {
    background: #2f2f3f;
    border: none;
    color: #ddd;
    box-shadow: 0 4px 8px rgba( 0, 0, 0, 0.3 );
}

.card-header {
    background: #3b3b4d;
    border-bottom: 2px solid #4e73df;
    color: #fff;
}

.table-hover tbody tr:hover {
    background-color: #3a3a4a;
}

.main-footer {
    background: #2e2e3d;
    color: #f8f9fc;
}
</style>
</head>

<body class = 'hold-transition sidebar-mini layout-fixed'>
<div class = 'wrapper'>
<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-light' style = 'background:#2e2e3d;'>
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link text-light' data-widget = 'pushmenu' href = '#'><i class = 'fas fa-bars'></i></a>
</li>
</ul>
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'>
<a class = 'nav-link text-light' href = '../auth/logout.php'>
<i class = 'fas fa-sign-out-alt'></i> Logout
</a>
</li>
</ul>
</nav>

<!-- Sidebar -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = '#' class = 'brand-link text-center' style = 'background:#4e73df;'>
<span class = 'brand-text font-weight-light'>MyApp</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column'>
<li class = 'nav-item'>
<a href = 'dashboard.php' class = 'nav-link active'>
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Assigned Stages</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'assigned_projects.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-project-diagram'></i>
<p>Assigned Projects</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'profile.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-user'></i>
<p>Profile</p>
</a>
</li>
</ul>
</nav>
</div>
</aside>

<!-- Content Wrapper -->
<div class = 'content-wrapper p-3'>
<div class = 'row mb-4'>
<div class = 'col'>
<h3 class = 'text-light'>Assigned Project Stages</h3>
</div>
</div>
<div class = 'card'>
<div class = 'card-header'>
<i class = 'fas fa-tasks'></i> Stage List
</div>
<div class = 'card-body table-responsive p-0'>
<table class = 'table table-hover table-dark mb-0'>
<thead>
<tr>
<th>Project</th>
<th>Stage</th>
<th>Due Date</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if ( empty( $stages ) ): ?>
<tr>
<td colspan = '4' class = 'text-center text-muted'>No stages assigned yet.</td>
</tr>
<?php else: ?>
<?php foreach ( $stages as $st ): ?>
<tr>
<td><?php echo htmlspecialchars( $st[ 'project_title' ] );
?></td>
<td><?php echo htmlspecialchars( $st[ 'stage_name' ] );
?></td>
<td><?php echo htmlspecialchars( $st[ 'due_date' ] );
?></td>
<td>
<a href = "review_stage.php?stage_id=<?php echo $st['stage_id']; ?>" class = 'btn btn-sm btn-primary'>
<i class = 'fas fa-star'></i> Review
</a>
</td>
</tr>
<?php endforeach;
?>
<?php endif;
?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Footer -->
<footer class = 'main-footer text-center'>
<strong>&copy;
<?php echo date( 'Y' );
?> MyApp</strong>
</footer>
</div>

<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>
</body>

</html>