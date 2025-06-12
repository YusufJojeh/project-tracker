<?php include( '../includes/student_header.php' );
?><?php

require_once __DIR__ . '/../config/database.php';

if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'student' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

// Validate project ID
$id = isset( $_GET[ 'id' ] ) ? ( int )$_GET[ 'id' ] : 0;

// Fetch project info
$stmt = $pdo->prepare( 'SELECT * FROM projects WHERE project_id = ? AND student_id = ?' );
$stmt->execute( [ $id, $_SESSION[ 'user_id' ] ] );
$project = $stmt->fetch();

if ( !$project ) {
    die( 'Access denied: Project not found or you do not have permission to edit.' );
}

// Handle POST submission
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $title = trim( $_POST[ 'title' ] );
    $description = trim( $_POST[ 'description' ] );
    $tools_used = trim( $_POST[ 'tools_used' ] );

    $upd = $pdo->prepare( 'UPDATE projects SET title = ?, description = ?, tools_used = ? WHERE project_id = ?' );
    $upd->execute( [ $title, $description, $tools_used, $id ] );

    header( 'Location: projects.php' );
    exit;
}
?>

<!-- Include Header -->

<!-- Content Wrapper -->
<div class = 'container'>
<h3 class = 'mb-4 text-center'>Edit Project</h3>
<form method = 'post' class = 'bg-white p-4 rounded shadow' novalidate>
<div class = 'mb-3'>
<label class = 'form-label'>Title</label>
<input name = 'title' class = 'form-control' value = "<?= htmlspecialchars($project['title']) ?>" required>
</div>
<div class = 'mb-3'>
<label class = 'form-label'>Description</label>
<textarea name = 'description' class = 'form-control'
rows = '4'>< ?= htmlspecialchars( $project[ 'description' ] ) ?></textarea>
</div>
<div class = 'mb-3'>
<label class = 'form-label'>Tools Used</label>
<input name = 'tools_used' class = 'form-control' value = "<?= htmlspecialchars($project['tools_used'] ?? '') ?>">
</div>
<button type = 'submit' class = 'btn btn-primary w-100'>Save Changes</button>
</form>
</div>

<!-- Footer -->
<footer class = 'main-footer'>
<div class = 'float-right d-none d-sm-block'>
<b>Version</b> 1.0.0
</div>
<strong>&copy;
<?php echo date( 'Y' );
?> <a href = '#'>Project Tracker</a>.</strong> All rights reserved.
</footer>

<!-- jQuery -->
<script src = 'https://code.jquery.com/jquery-3.6.0.min.js'></script>
<!-- Bootstrap 4 -->
<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js'></script>
<!-- AdminLTE App -->
<script src = '../dist/js/adminlte.min.js'></script>

<!-- REQUIRED SCRIPTS -->
<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>

</body>

</html>