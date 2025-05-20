<?php
// supervisors/stage_review.php
session_start();

// 1 ) Only supervisors may access
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once __DIR__ . '/../config/database.php';

// 2 ) Get the stage ID
$stageId = isset( $_GET[ 'stage_id' ] )
? ( int ) $_GET[ 'stage_id' ]
: 0;

if ( $stageId < 1 ) {
    die( 'Invalid stage ID.' );
}

// 3 ) Handle form submission: insert or update the review
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $comment   = trim( $_POST[ 'comment' ] );
    $grade     = ( float ) $_POST[ 'grade' ];
    $supervisorId = $_SESSION[ 'user_id' ];

    // Check for an existing review by this supervisor
    $check = $pdo->prepare( "
        SELECT review_id
          FROM reviews
         WHERE stage_id = ?
           AND supervisor_id = ?
    " );
    $check->execute( [ $stageId, $supervisorId ] );
    $existing = $check->fetchColumn();

    if ( $existing ) {
        // Update
        $upd = $pdo->prepare( "
            UPDATE reviews
               SET comment    = ?,
                   grade      = ?,
                   updated_at = CURRENT_TIMESTAMP
             WHERE review_id = ?
        " );
        $upd->execute( [ $comment, $grade, $existing ] );
    } else {
        // Insert
        $ins = $pdo->prepare( "
            INSERT INTO reviews
                (stage_id, supervisor_id, comment, grade, created_at)
            VALUES
                (?, ?, ?, ?, CURRENT_TIMESTAMP)
        " );
        $ins->execute( [ $stageId, $supervisorId, $comment, $grade ] );
    }

    header( 'Location: assigned_projects.php' );
    exit;
}

// 4 ) Load stage, project, student ( from users ) and any existing review
$stmt = $pdo->prepare( "
    SELECT
      s.stage_id,
      s.title       AS stage_title,
      s.description AS stage_description,
      s.due_date,
      p.project_id,
      p.title       AS project_title,
      u.user_id     AS student_id,
      u.username    AS student_username,
      u.email       AS student_email,
      r.review_id,
      r.comment     AS existing_comment,
      r.grade       AS existing_grade
    FROM stages      s
    JOIN projects    p ON s.project_id = p.project_id
    JOIN users       u ON p.student_id = u.user_id
    LEFT JOIN reviews r
      ON r.stage_id      = s.stage_id
     AND r.supervisor_id = ?
    WHERE s.stage_id = ?
" );
$stmt->execute( [ $_SESSION[ 'user_id' ], $stageId ] );
$data = $stmt->fetch( PDO::FETCH_ASSOC );

if ( ! $data ) {
    die( '<div class="p-4"><h3>Stage not found or access denied.</h3>'
    . '<p><a href="assigned_projects.php">&larr; Back</a></p></div>' );
}

// Extract for template
$projectTitle    = $data[ 'project_title' ];
$stageTitle      = $data[ 'stage_title' ];
$stageDesc       = $data[ 'stage_description' ];
$stageDue        = $data[ 'due_date' ];
$studentUsername = $data[ 'student_username' ];
$studentEmail    = $data[ 'student_email' ];
$existingComment = $data[ 'existing_comment' ];
$existingGrade   = $data[ 'existing_grade' ];
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8'>
<meta name = 'viewport' content = 'width=device-width,initial-scale=1'>
<title>Review Stage | Project Tracker</title>
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
<a class = 'nav-link' data-widget = 'pushmenu' href = '#'>
<i class = 'fas fa-bars'></i>
</a>
</li>
</ul>
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'>
<a href = '../auth/logout.php' class = 'nav-link'>
<i class = 'fas fa-sign-out-alt'></i> Logout
</a>
</li>
</ul>
</nav>

<!-- Sidebar -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = 'dashboard.php' class = 'brand-link text-center'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column'>
<li class = 'nav-item'>
<a href = 'dashboard.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
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
<div class = 'content-wrapper p-4'>
<div class = 'container-fluid'>

<div class = 'card mb-4'>
<div class = 'card-header bg-primary text-white'>
<i class = 'fas fa-star'></i>
Review "<?php echo htmlspecialchars($stageTitle, ENT_QUOTES, 'UTF-8'); ?>"
</div>
<div class = 'card-body'>
<p>
<strong>Project:</strong>
<?php echo htmlspecialchars( $projectTitle, ENT_QUOTES, 'UTF-8' );
?>
</p>
<p>
<strong>Student:</strong>
<?php echo htmlspecialchars( $studentUsername, ENT_QUOTES, 'UTF-8' );
?>
( <?php echo htmlspecialchars( $studentEmail, ENT_QUOTES, 'UTF-8' );
?> )
</p>
<p>
<strong>Due Date:</strong>
<?php echo date( 'Y-m-d', strtotime( $stageDue ) );
?>
</p>
<?php if ( $stageDesc ): ?>
<p><strong>Description:</strong><br>
<?php echo nl2br( htmlspecialchars( $stageDesc, ENT_QUOTES, 'UTF-8' ) );
?>
</p>
<?php endif;
?>

<form method = 'post'>
<div class = 'form-group'>
<label for = 'comment'>Comments</label>
<textarea id = 'comment' name = 'comment' class = 'form-control' rows = '4'
placeholder = 'Enter your feedback...'><?php
echo htmlspecialchars( $existingComment ?: '', ENT_QUOTES, 'UTF-8' );
?></textarea>
</div>
<div class = 'form-group'>
<label for = 'grade'>Grade</label>
<input type = 'number' step = '0.1' id = 'grade' name = 'grade' class = 'form-control' required value = "<?php echo $existingGrade !== null
                       ? htmlspecialchars($existingGrade, ENT_QUOTES, 'UTF-8')
                       : ''; ?>" placeholder = 'e.g. 8.5'>
</div>
<button type = 'submit' class = 'btn btn-success'>
<i class = 'fas fa-save'></i> Submit Review
</button>
</form>
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

<!-- AdminLTE scripts -->
<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>
</body>

</html>
