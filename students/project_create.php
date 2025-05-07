<?php
// students/project_create.php
session_start();
if ( empty( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ../auth/login.php' );
    exit;
}
require __DIR__ . '/../config/database.php';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $studentId   = $_SESSION[ 'user_id' ];
    $title       = trim( $_POST[ 'title' ] );
    $description = trim( $_POST[ 'description' ] );
    $toolsInput  = trim( $_POST[ 'tools_used' ] );
    $toolsUsed   = $toolsInput !== '' ? $toolsInput : null;

    // Insert project
    $stmt = $pdo->prepare( "
        INSERT INTO projects (student_id, title, description, tools_used)
        VALUES (?, ?, ?, ?)
    " );
    $stmt->execute( [
        $studentId,
        $title,
        $description,
        $toolsUsed,
    ] );
    $projectId = $pdo->lastInsertId();

    // Add default stages
    $defaultStages = [
        'Idea Definition',
        'Feasibility Study',
        'Design',
        'Development',
        'Final Submission'
    ];
    $insStage = $pdo->prepare( "
        INSERT INTO stages (project_id, name, due_date, created_at)
        VALUES (?, ?, NULL, NOW())
    " );
    foreach ( $defaultStages as $stageName ) {
        $insStage->execute( [ $projectId, $stageName ] );
    }

    header( 'Location: dashboard.php' );
    exit;
}
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1' />
<title>Create Project – MyApp</title>
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
    background: #f4f6f9;
    color: #333;
}

.card {
    box-shadow: 0 4px 10px rgba( 0, 0, 0, 0.1 );
}

.card-header {
    background: #4e73df;
    color: #fff;
    border-bottom: none;
}

.btn-primary {
    background: #4e73df;
    border-color: #4e73df;
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
<a href = '#' class = 'brand-link text-center' style = 'background:#4e73df;'><span class = 'brand-text font-weight-light'
style = 'color:#fff;'>MyApp</span></a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column'>
<li class = 'nav-item'><a href = 'dashboard.php' class = 'nav-link'><i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a></li>
<li class = 'nav-item'><a href = 'project_create.php' class = 'nav-link active'><i

class = 'nav-icon fas fa-plus-circle'></i>
<p>Create Project</p>
</a></li>
<li class = 'nav-item'><a href = 'stage_upload.php' class = 'nav-link'><i class = 'nav-icon fas fa-upload'></i>
<p>Upload Stage</p>
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
<div class = 'container'>
<div class = 'card'>
<div class = 'card-header'><i class = 'fas fa-plus-circle'></i> Create New Project</div>
<div class = 'card-body'>
<form method = 'post'>
<div class = 'form-group'>
<label for = 'title'>Title</label>
<input id = 'title' name = 'title' class = 'form-control' required />
</div>
<div class = 'form-group'>
<label for = 'description'>Description</label>
<textarea id = 'description' name = 'description' class = 'form-control' rows = '4' required></textarea>
</div>
<div class = 'form-group'>
<label for = 'tools_used'>Tools Used <small>( optional )</small></label>
<input id = 'tools_used' name = 'tools_used' class = 'form-control' />
</div>
<button type = 'submit' class = 'btn btn-primary'><i class = 'fas fa-check'></i> Add Project</button>
<a href = 'dashboard.php' class = 'btn btn-secondary'><i class = 'fas fa-arrow-left'></i> Cancel</a>
</form>
</div>
</div>
</div>
</div>
<!-- Footer -->
<footer class = 'main-footer text-center' style = 'background:#2e2e3d; color:#f8f9fc;'>
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