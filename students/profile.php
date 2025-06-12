<?php include( '../includes/student_header.php' );
?><?php

$studentId = $_SESSION[ 'user_id' ];

$stmt = $pdo->prepare( 'SELECT username, email, role, created_at FROM users WHERE user_id = ?' );
$stmt->execute( [ $studentId ] );
$student = $stmt->fetch( PDO::FETCH_ASSOC );

if ( !$student ) {
    die( 'Access denied.' );
}

$updateMsg = '';
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $username = trim( $_POST[ 'username' ] );
    $email = trim( $_POST[ 'email' ] );
    $password = trim( $_POST[ 'password' ] );

    if ( !empty( $username ) && !empty( $email ) ) {
        if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            $updateMsg = 'Invalid email format.';
        } else {
            $check = $pdo->prepare( 'SELECT user_id FROM users WHERE email = ? AND user_id != ?' );
            $check->execute( [ $email, $studentId ] );
            if ( $check->fetch() ) {
                $updateMsg = 'This email is already in use.';
            } else {
                if ( !empty( $password ) ) {
                    $hashed = password_hash( $password, PASSWORD_DEFAULT );
                    $upd = $pdo->prepare( 'UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?' );
                    $upd->execute( [ $username, $email, $hashed, $studentId ] );
                } else {
                    $upd = $pdo->prepare( 'UPDATE users SET username = ?, email = ? WHERE user_id = ?' );
                    $upd->execute( [ $username, $email, $studentId ] );
                }
                $updateMsg = 'Profile updated successfully.';
                $student[ 'username' ] = $username;
                $student[ 'email' ] = $email;
            }
        }
    } else {
        $updateMsg = 'Username and email are required.';
    }
}

$projectStmt = $pdo->prepare( 'SELECT project_id, title, created_at FROM projects WHERE student_id = ? ORDER BY created_at DESC' );
$projectStmt->execute( [ $studentId ] );
$projects = $projectStmt->fetchAll( PDO::FETCH_ASSOC );

$unreadCount = get_unread_notifications_count( $pdo, $studentId );

?>

<!-- Content -->
<div class = 'content-wrapper p-4'>
<div class = 'container-fluid'>
<!-- Profile Update -->
<div class = 'card card-primary mb-4'>
<div class = 'card-header'>
<h3 class = 'card-title'>Edit My Profile</h3>
</div>
<div class = 'card-body'>
<?php if ( !empty( $updateMsg ) ): ?>
<div class = 'alert alert-info'><?php echo $updateMsg;
?></div>
<?php endif;
?>
<form method = 'post'>
<div class = 'form-group'>
<label>Username</label>
<input name = 'username' class = 'form-control' value = "<?php echo htmlspecialchars($student['username']); ?>"
required>
</div>
<div class = 'form-group'>
<label>Email</label>
<input name = 'email' type = 'email' class = 'form-control'
value = "<?php echo htmlspecialchars($student['email']); ?>" required>
</div>
<div class = 'form-group'>
<label>New Password ( optional )</label>
<input name = 'password' type = 'password' class = 'form-control'>
</div>
<button type = 'submit' class = 'btn btn-primary'>Update Profile</button>
</form>
</div>
</div>

<!-- Projects -->
<div class = 'card card-primary'>
<div class = 'card-header'>
<h3 class = 'card-title'>My Projects</h3>
</div>
<div class = 'card-body'>
<?php if ( empty( $projects ) ): ?>
<p class = 'text-muted'>You haven't created any projects yet.</p>
        <?php else: ?>
        <ul class="list-group">
          <?php foreach ($projects as $proj): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <a
              href="project_view.php?project_id=<?php echo $proj['project_id']; ?>"><?php echo htmlspecialchars($proj['title']); ?></a>
            <span class="text-muted"><?php echo (new DateTime($proj['created_at']))->format('Y-m-d'); ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Footer --><?php include('../includes/student_footer.php' );
?>
