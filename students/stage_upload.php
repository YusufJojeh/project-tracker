<?php include( '../includes/student_header.php' );
?><?php

if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'student' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notifications.php';

$error   = '';
$success = '';

// Fetch all stages for this student’s projects
$stmt = $pdo->prepare( '
    SELECT p.project_id, p.title AS project_title,
           s.stage_id, s.title AS stage_title, s.status, s.due_date
    FROM projects p
    JOIN stages s ON p.project_id = s.project_id
    WHERE p.student_id = ?
    ORDER BY p.created_at DESC, s.due_date ASC
' );
$stmt->execute( [ $_SESSION[ 'user_id' ] ] );
$stages = $stmt->fetchAll();

// Handle file upload
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $stage_id = $_POST[ 'stage_id' ] ?? '';
    $comment  = trim( $_POST[ 'comment' ] ?? '' );

    if ( !$stage_id ) {
        $error = 'Please select a stage.';
    } elseif ( empty( $_FILES[ 'file' ][ 'name' ] ) ) {
        $error = 'Please choose a file to upload.';
    } else {
        $file        = $_FILES[ 'file' ];
        $allowed_ext = [ 'pdf', 'doc', 'docx', 'zip', 'rar' ];
        $ext         = strtolower( pathinfo( $file[ 'name' ], PATHINFO_EXTENSION ) );

        if ( !in_array( $ext, $allowed_ext ) ) {
            $error = 'Invalid file type. Allowed: ' . implode( ', ', $allowed_ext );
        } elseif ( $file[ 'size' ] > 10 * 1024 * 1024 ) {
            $error = 'File too large (max 10MB).';
        } else {
            $upload_dir = __DIR__ . '/../uploads/' . $_SESSION[ 'user_id' ];
            if ( !is_dir( $upload_dir ) ) mkdir( $upload_dir, 0777, true );

            $unique = uniqid() . '_' . basename( $file[ 'name' ] );
            $target = $upload_dir . '/' . $unique;

            if ( move_uploaded_file( $file[ 'tmp_name' ], $target ) ) {
                $pdo->beginTransaction();
                // Insert into attachments
                $ins = $pdo->prepare( '
                    INSERT INTO attachments (stage_id, file_name, file_path, uploaded_by)
                    VALUES (?, ?, ?, ?)
                ' );
                $ins->execute( [ $stage_id, $file[ 'name' ], $unique, $_SESSION[ 'user_id' ] ] );
                // Update stage status
                $upd = $pdo->prepare( 'UPDATE stages SET status = ? WHERE stage_id = ?' );
                $upd->execute( [ 'submitted', $stage_id ] );
                // Optional comment
                if ( $comment ) {
                    $c = $pdo->prepare( '
                        INSERT INTO comments (project_id, user_id, comment)
                        SELECT project_id, ?, ? FROM stages WHERE stage_id = ?
                    ' );
                    $c->execute( [ $_SESSION[ 'user_id' ], $comment, $stage_id ] );
                }
                $pdo->commit();
                $success = 'Upload successful!';
            } else {
                $pdo->rollBack();
                $error = 'Upload failed.';
            }
        }
    }
}

// Unread notifications for navbar badge
$unread = get_unread_notifications_count( $pdo, $_SESSION[ 'user_id' ] );
?>

<!-- Include Header -->

<!-- Content Wrapper -->
<div class = 'content-wrapper p-4'>
<div class = 'container-fluid'>
<div class = 'card'>
<div class = 'card-header'><i class = 'fas fa-upload'></i> Upload to Stage</div>
<div class = 'card-body'>
<?php if ( $error ): ?>
<div class = 'alert alert-danger'><?php echo htmlspecialchars( $error );
?></div>
<?php endif;
?>
<?php if ( $success ): ?>
<div class = 'alert alert-success'><?php echo htmlspecialchars( $success );
?></div>
<?php endif;
?>

<form method = 'post' enctype = 'multipart/form-data'>
<div class = 'form-group'>
<label for = 'stage_id'>Stage</label>
<select name = 'stage_id' id = 'stage_id' class = 'form-control' required>
<option value = ''>-- Select Stage --</option>
<?php foreach ( $stages as $st ): if ( $st[ 'status' ] !== 'approved' ): ?>
<option value = "<?php echo $st['stage_id']; ?>">
<?php echo htmlspecialchars( $st[ 'project_title' ] . ' &mdash; ' . $st[ 'stage_title' ] );
?>
( Due <?php echo date( 'Y-m-d', strtotime( $st[ 'due_date' ] ) );
?> )
</option>
<?php endif;
endforeach;
?>
</select>
</div>
<div class = 'form-group'>
<label for = 'file'>File</label>
<input type = 'file' name = 'file' id = 'file' class = 'form-control' required>
</div>
<div class = 'form-group'>
<label for = 'comment'>Comment <small>( optional )</small></label>
<textarea name = 'comment' id = 'comment' class = 'form-control' rows = '3'></textarea>
</div>
<button type = 'submit' class = 'btn btn-primary'><i class = 'fas fa-upload'></i> Upload</button>
<a href = 'projects.php' class = 'btn btn-secondary'>Cancel</a>
</form>
</div>
</div>
</div>
</div>

<!-- Footer -->
<?php include( '../includes/student_footer.php' );
?>

<!-- Scripts -->
<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>
</body>

</html>
