<?php
// supervisors/stage_review.php
session_start();

if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once __DIR__ . '/../config/database.php';

$stageId = isset( $_GET[ 'id' ] ) ? ( int )$_GET[ 'id' ] : 0;
if ( $stageId < 1 ) die( 'Invalid stage ID.' );

$error = '';
$success = '';

// --- Process review submission ---
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $grade = isset( $_POST[ 'grade' ] ) ? ( float )$_POST[ 'grade' ] : null;
    $feedback = trim( $_POST[ 'feedback' ] ?? '' );

    // Only update if stage belongs to a project under this supervisor
    $stmt = $pdo->prepare( "
        SELECT s.stage_id
        FROM stages s
        JOIN projects p ON s.project_id = p.project_id
        WHERE s.stage_id = ? AND p.supervisor_id = ?
    " );
    $stmt->execute( [ $stageId, $_SESSION[ 'user_id' ] ] );
    $stage = $stmt->fetch( PDO::FETCH_ASSOC );

    if ( $stage ) {
        $upd = $pdo->prepare( "
            UPDATE stages
            SET grade = ?, feedback = ?, status = 'reviewed', updated_at = NOW()
            WHERE stage_id = ?
        " );
        $upd->execute( [ $grade, $feedback, $stageId ] );
        $success = 'Review saved successfully!';
    } else {
        $error = 'Stage not found or access denied.';
    }
}

// --- Load stage info for display ---
$stmt = $pdo->prepare( "
    SELECT
        s.stage_id,
        s.title AS stage_title,
        s.description AS stage_description,
        s.due_date,
        s.grade,
        s.feedback,
        s.status,
        p.project_id,
        p.title AS project_title,
        u.user_id AS student_id,
        u.username AS student_username,
        u.email AS student_email
    FROM stages s
    JOIN projects p ON s.project_id = p.project_id
    JOIN users u ON p.student_id = u.user_id
    WHERE s.stage_id = ? AND p.supervisor_id = ?
" );
$stmt->execute( [ $stageId, $_SESSION[ 'user_id' ] ] );
$data = $stmt->fetch( PDO::FETCH_ASSOC );

if ( !$data ) {
    die( '<div class="p-4"><h3>Stage not found or access denied.</h3>
    <p><a href="projects.php">&larr; Back</a></p></div>' );
}

// Attachments
$attachmentsStmt = $pdo->prepare( 'SELECT * FROM attachments WHERE stage_id = ? ORDER BY created_at ASC' );
$attachmentsStmt->execute( [ $stageId ] );
$attachments = $attachmentsStmt->fetchAll( PDO::FETCH_ASSOC );

?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8'>
<title>Review Stage | Project Tracker</title>
<link rel = 'stylesheet' href = '../plugins/fontawesome-free/css/all.min.css'>
<link rel = 'stylesheet' href = '../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
<link rel = 'stylesheet' href = '../dist/css/adminlte.min.css'>
</head>

<body class = 'hold-transition sidebar-mini layout-fixed'>
<div class = 'wrapper'>

<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'>
<a href = '../auth/logout.php' class = 'nav-link'><i class = 'fas fa-sign-out-alt'></i> Logout</a>
</li>
</ul>
</nav>

<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = 'dashboard.php' class = 'brand-link text-center'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column'>
<li class = 'nav-item'><a href = 'dashboard.php' class = 'nav-link'><i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a></li>
<li class = 'nav-item'><a href = 'projects.php' class = 'nav-link'><i class = 'nav-icon fas fa-project-diagram'></i>
<p>Projects</p>
</a></li>
<li class = 'nav-item'><a href = 'profile.php' class = 'nav-link'><i class = 'nav-icon fas fa-user'></i>
<p>Profile</p>
</a></li>
<li class = 'nav-item'><a href = '../auth/logout.php' class = 'nav-link'><i

class = 'nav-icon fas fa-sign-out-alt'></i>
<p>Logout</p>
</a></li>
</ul>
</nav>
</div>
</aside>

<div class = 'content-wrapper p-4'>
<div class = 'container-fluid'>

<div class = 'card mb-4'>
<div class = 'card-header bg-primary text-white'>
<i class = 'fas fa-star'></i> Review "<?php echo htmlspecialchars($data['stage_title']); ?>"
</div>
<div class = 'card-body'>

<?php if ( $error ): ?>
<div class = 'alert alert-danger'><?php echo htmlspecialchars( $error );
?></div>
<?php elseif ( $success ): ?>
<div class = 'alert alert-success'><?php echo htmlspecialchars( $success );
?></div>
<?php endif;
?>

<p><strong>Project:</strong> <?php echo htmlspecialchars( $data[ 'project_title' ] );
?></p>
<p><strong>Student:</strong> <?php echo htmlspecialchars( $data[ 'student_username' ] );
?>
( <?php echo htmlspecialchars( $data[ 'student_email' ] );
?> )</p>
<p><strong>Due Date:</strong> <?php echo date( 'Y-m-d', strtotime( $data[ 'due_date' ] ) );
?></p>
<?php if ( $data[ 'stage_description' ] ): ?>
<p><strong>Description:</strong><br><?php echo nl2br( htmlspecialchars( $data[ 'stage_description' ] ) );
?></p>
<?php endif;
?>

<?php if ( $attachments ): ?>
<div class = 'mb-3'>
<strong>Uploaded Files:</strong>
<ul>
<?php foreach ( $attachments as $a ): ?>
<li>
<a href = "../uploads/<?php echo htmlspecialchars($a['file_path']); ?>" target = '_blank'>
<i class = 'fas fa-file'></i>
<?php echo htmlspecialchars( $a[ 'file_name' ] );
?>
</a>
<small class = 'text-muted'>( uploaded at <?php echo $a[ 'created_at' ];
?> )</small>
</li>
<?php endforeach;
?>
</ul>
</div>
<?php endif;
?>

<form method = 'post'>
<div class = 'form-group'>
<label for = 'feedback'>Feedback/Comments</label>
<textarea id = 'feedback' name = 'feedback' class = 'form-control' rows = '4'
placeholder = 'Enter your feedback...'><?php
echo htmlspecialchars( $data[ 'feedback' ] ?? '' );
?></textarea>
</div>
<div class = 'form-group'>
<label for = 'grade'>Grade</label>
<input type = 'number' step = '0.1' min = '0' max = '100' id = 'grade' name = 'grade' class = 'form-control' required
value = "<?php echo ($data['grade'] !== null) ? htmlspecialchars($data['grade']) : ''; ?>"
placeholder = 'e.g. 8.5'>
</div>
<button type = 'submit' class = 'btn btn-success'>
<i class = 'fas fa-save'></i> Save Review
</button>
<a href = "project_view.php?id=<?php echo $data['project_id']; ?>" class = 'btn btn-secondary ml-2'>Back to
Project</a>
</form>

</div>
</div>

</div>
</div>

<footer class = 'main-footer text-center'>
<strong>&copy;
<?php echo date( 'Y' );
?> Project Tracker</strong>
</footer>
</div>

<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>
</body>

</html>