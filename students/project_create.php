<?php
// project_create.php
session_start();
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'student' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}
require_once '../config/database.php';
require_once '../includes/functions.php';
require_role( 'student' );

$error = '';
$success = '';

// Fetch supervisors
$stmt = $pdo->query( '
    SELECT
        s.supervisor_id,
        s.full_name,
        s.title,
        d.name as department,
        sp.name as specialization,
        (SELECT COUNT(*) FROM projects WHERE supervisor_id = s.supervisor_id AND status != "archived") as current_students,
        s.max_students
    FROM supervisors s
    JOIN departments d ON s.department_id = d.department_id
    JOIN specializations sp ON s.specialization_id = sp.specialization_id
    WHERE s.max_students > (
        SELECT COUNT(*)
        FROM projects
        WHERE supervisor_id = s.supervisor_id
        AND status != "archived"
    )
    ORDER BY d.name, s.full_name
' );
$supervisors = $stmt->fetchAll();

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $title              = trim( $_POST[ 'title' ] ?? '' );
    $description        = trim( $_POST[ 'description' ] ?? '' );
    $objectives         = trim( $_POST[ 'objectives' ] ?? '' );
    $methodology        = trim( $_POST[ 'methodology' ] ?? '' );
    $expected_outcomes  = trim( $_POST[ 'expected_outcomes' ] ?? '' );
    $supervisor_id      = $_POST[ 'supervisor_id' ] ?? null;
    $start_date         = $_POST[ 'start_date' ] ?? '';
    $end_date           = $_POST[ 'end_date' ] ?? '';

    if ( empty( $title ) ) {
        $error = 'Project title is required';
    } elseif ( empty( $supervisor_id ) ) {
        $error = 'Please select a supervisor';
    } elseif ( empty( $start_date ) || empty( $end_date ) ) {
        $error = 'Start and end dates are required';
    } elseif ( strtotime( $end_date ) <= strtotime( $start_date ) ) {
        $error = 'End date must be after start date';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare( '
                INSERT INTO projects (
                    student_id, supervisor_id, title, description,
                    objectives, methodology, expected_outcomes,
                    status, start_date, end_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, "proposed", ?, ?)
            ' );
            $stmt->execute( [
                $_SESSION[ 'user_id' ], $supervisor_id, $title, $description,
                $objectives, $methodology, $expected_outcomes,
                $start_date, $end_date
            ] );
            $project_id = $pdo->lastInsertId();

            $stages = [
                [ 'Project Proposal', 'Initial project proposal and requirements', date( 'Y-m-d', strtotime( "$start_date +2 weeks" ) ) ],
                [ 'Literature Review', 'Review of relevant research and literature', date( 'Y-m-d', strtotime( "$start_date +4 weeks" ) ) ],
                [ 'System Design', 'Detailed system architecture and design', date( 'Y-m-d', strtotime( "$start_date +6 weeks" ) ) ],
                [ 'Implementation', 'Core system implementation', date( 'Y-m-d', strtotime( "$start_date +10 weeks" ) ) ],
                [ 'Testing', 'System testing and validation', date( 'Y-m-d', strtotime( "$start_date +12 weeks" ) ) ],
                [ 'Documentation', 'Final documentation and user manual', date( 'Y-m-d', strtotime( "$end_date -2 weeks" ) ) ],
            ];
            $stmt = $pdo->prepare( '
                INSERT INTO stages (project_id, title, description, due_date, status)
                VALUES (?, ?, ?, ?, "pending")
            ' );
            foreach ( $stages as $s ) {
                $stmt->execute( [ $project_id, $s[ 0 ], $s[ 1 ], $s[ 2 ] ] );
            }

            create_notification(
                $pdo,
                $supervisor_id,
                'New Project Proposal',
                'A new project has been proposed for your review',
                'info',
                "/supervisors/project_view.php?id=$project_id"
            );

            $pdo->commit();
            header( "Location: project_view.php?id=$project_id" );
            exit;
        } catch ( Exception $e ) {
            $pdo->rollBack();
            $error = 'Error creating project: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1' />
<title>Create New Project – MyApp</title>
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
    padding: 1.5rem;
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
<li class = 'nav-item'><a href = 'project_create.php' class = 'nav-link active'><i

class = 'nav-icon fas fa-plus-circle'></i>
<p>New Project</p>
</a></li>
<li class = 'nav-item'><a href = 'projects.php' class = 'nav-link'><i class = 'nav-icon fas fa-folder-open'></i>
<p>My Projects</p>
</a></li>
</ul>
</nav>
</div>
</aside>
<!-- Content Wrapper -->
<div class = 'content-wrapper'>
<section class = 'content-header'>
<h1>Create New Project</h1>
<a href = 'projects.php' class = 'btn btn-secondary mb-3'><i class = 'fas fa-arrow-left'></i> Back to Projects</a>
</section>
<section class = 'content'>
<?php if ( $error ): ?>
<div class = 'alert alert-danger'><?php echo h( $error );
?></div>
<?php endif;
?>
<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'card-title'>Project Details</h3>
</div>
<div class = 'card-body'>
<form method = 'post'>
<div class = 'form-group'>
<label>Project Title <span class = 'text-danger'>*</span></label>
<input type = 'text' name = 'title' class = 'form-control' value = "<?php echo h($_POST['title'] ?? ''); ?>"
required>
</div>
<div class = 'form-group'>
<label>Description</label>
<textarea name = 'description' class = 'form-control'
rows = '3'><?php echo h( $_POST[ 'description' ] ?? '' );
?></textarea>
</div>
<div class = 'form-group'>
<label>Objectives</label>
<textarea name = 'objectives' class = 'form-control'
rows = '3'><?php echo h( $_POST[ 'objectives' ] ?? '' );
?></textarea>
</div>
<div class = 'form-group'>
<label>Methodology</label>
<textarea name = 'methodology' class = 'form-control'
rows = '3'><?php echo h( $_POST[ 'methodology' ] ?? '' );
?></textarea>
</div>
<div class = 'form-group'>
<label>Expected Outcomes</label>
<textarea name = 'expected_outcomes' class = 'form-control'
rows = '3'><?php echo h( $_POST[ 'expected_outcomes' ] ?? '' );
?></textarea>
</div>
<div class = 'form-group'>
<label>Supervisor <span class = 'text-danger'>*</span></label>
<select name = 'supervisor_id' class = 'form-control' required>
<option value = ''>Select a supervisor</option>
<?php foreach ( $supervisors as $sup ): ?>
<option value = "<?php echo $sup['supervisor_id']; ?>"
<?php if ( ( $sup[ 'supervisor_id' ] ?? '' ) == ( $_POST[ 'supervisor_id' ] ?? '' ) ) echo 'selected';
?>>
<?php echo h( "{$sup['title']} {$sup['full_name']} ({$sup['department']} - {$sup['specialization']}) — {$sup['current_students']}/{$sup['max_students']}" );
?>
</option>
<?php endforeach;
?>
</select>
</div>
<div class = 'form-row'>
<div class = 'form-group col-md-6'>
<label>Start Date <span class = 'text-danger'>*</span></label>
<input type = 'date' name = 'start_date' class = 'form-control'
value = "<?php echo h($_POST['start_date'] ?? ''); ?>" required>
</div>
<div class = 'form-group col-md-6'>
<label>End Date <span class = 'text-danger'>*</span></label>
<input type = 'date' name = 'end_date' class = 'form-control'
value = "<?php echo h($_POST['end_date'] ?? ''); ?>" required>
</div>
</div>
<button type = 'submit' class = 'btn btn-primary'><i class = 'fas fa-save'></i> Create Project</button>
</form>
</div>
</div>
</section>
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