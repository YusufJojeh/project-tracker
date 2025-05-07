<?php
session_start();
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}
require __DIR__ . '/../config/database.php';

$projId = isset( $_GET[ 'project_id' ] ) ? ( int )$_GET[ 'project_id' ] : 0;

// Fetch project and ensure it belongs to this supervisor
$stmt = $pdo->prepare( "
    SELECT 
      p.project_id,
      p.title,
      p.description,
      p.created_at,
      u.full_name AS student_name
    FROM projects p
    JOIN users u ON p.student_id = u.user_id
    WHERE p.project_id = ? 
      AND p.supervisor_id = ?
" );
$stmt->execute( [ $projId, $_SESSION[ 'user_id' ] ] );
$project = $stmt->fetch( PDO::FETCH_ASSOC );
if ( !$project ) {
    die( 'Project not found or access denied.' );
}

// Fetch stages with upload & review info
$stmt = $pdo->prepare( "
    SELECT 
      s.stage_id,
      s.name AS stage_name,
      s.due_date,
      u.upload_id,
      u.file_path,
      r.review_id,
      r.grade
    FROM stages s
    LEFT JOIN uploads u ON u.stage_id = s.stage_id
    LEFT JOIN reviews r ON r.stage_id = s.stage_id
    WHERE s.project_id = ?
    ORDER BY s.created_at ASC
" );
$stmt->execute( [ $projId ] );
$stages = $stmt->fetchAll( PDO::FETCH_ASSOC );
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1' />
<title>Project Details – MyApp</title>
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

.content-wrapper {
    background: #2c2f3c;
}

.card {
    background: #3b3b4f;
    border: none;
    color: #f1f1f1;
}

.card-header {
    background: #4e73df;
    color: #fff;
}

.table-hover tbody tr:hover {
    background-color: #3a3a4a;
}

.btn-primary {
    background: #4e73df;
    border-color: #4e73df;
}

.btn-success {
    background: #28a745;
    border-color: #28a745;
}

.btn-info {
    background: #17a2b8;
    border-color: #17a2b8;
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
<li class = 'nav-item'><a class = 'nav-link text-light' data-widget = 'pushmenu' href = '#'><i

class = 'fas fa-bars'></i></a></li>
</ul>
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'><a class = 'nav-link text-light' href = '../auth/logout.php'><i

class = 'fas fa-sign-out-alt'></i> Logout</a></li>
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
<li class = 'nav-item'><a href = 'dashboard.php' class = 'nav-link'><i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a></li>
<li class = 'nav-item'><a href = 'assigned_projects.php' class = 'nav-link'><i

class = 'nav-icon fas fa-project-diagram'></i>
<p>Assigned Projects</p>
</a></li>
<li class = 'nav-item'><a href = 'profile.php' class = 'nav-link'><i class = 'nav-icon fas fa-user'></i>
<p>Profile</p>
</a></li>
</ul>
</nav>
</div>
</aside>

<!-- Content Wrapper -->
<div class = 'content-wrapper p-4'>
<!-- Project Info -->
<div class = 'card mb-4'>
<div class = 'card-header'><i class = 'fas fa-info-circle'></i> Project Details</div>
<div class = 'card-body'>
<p><strong>Title:</strong> <?php echo htmlspecialchars( $project[ 'title' ] );
?></p>
<?php if ( !empty( $project[ 'description' ] ) ): ?>
<p><strong>Description:</strong> <?php echo nl2br( htmlspecialchars( $project[ 'description' ] ) );
?></p>
<?php endif;
?>
<p><strong>Student:</strong> <?php echo htmlspecialchars( $project[ 'student_name' ] );
?></p>
<p><strong>Created On:</strong> <?php echo ( new DateTime( $project[ 'created_at' ] ) )->format( 'Y-m-d' );
?></p>
</div>
</div>

<!-- Stages Table -->
<div class = 'card'>
<div class = 'card-header'><i class = 'fas fa-layer-group'></i> Project Stages</div>
<div class = 'card-body table-responsive p-0'>
<table class = 'table table-hover table-dark mb-0'>
<thead>
<tr>
<th>Stage</th>
<th>Due Date</th>
<th>Uploaded</th>
<th>Reviewed</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ( $stages as $st ): ?>
<tr>
<td><?php echo htmlspecialchars( $st[ 'stage_name' ] );
?></td>
<td><?php echo htmlspecialchars( $st[ 'due_date' ] );
?></td>
<td>
<?php if ( $st[ 'upload_id' ] ): ?>
<a href = "../<?php echo htmlspecialchars($st['file_path']); ?>" class = 'btn btn-sm btn-info'
target = '_blank'>
<i class = 'fas fa-file'></i> View
</a>
<?php else: ?>
<span class = 'text-muted'>No</span>
<?php endif;
?>
</td>
<td>
<?php if ( $st[ 'review_id' ] ): ?>
<span class = 'text-success'>Yes ( <?php echo htmlspecialchars( $st[ 'grade' ] );
?> )</span>
<?php else: ?>
<span class = 'text-warning'>No</span>
<?php endif;
?>
</td>
<td>
<?php if ( !$st[ 'review_id' ] ): ?>
<a href = "review_stage.php?stage_id=<?php echo $st['stage_id']; ?>" class = 'btn btn-sm btn-success'>
<i class = 'fas fa-star'></i> Review
</a>
<?php else: ?>
<a href = "review_stage.php?stage_id=<?php echo $st['stage_id']; ?>" class = 'btn btn-sm btn-primary'>
<i class = 'fas fa-eye'></i> View Review
</a>
<?php endif;
?>
</td>
</tr>
<?php endforeach;
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