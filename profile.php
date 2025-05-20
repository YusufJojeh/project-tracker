<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_role($_SESSION['role']);

// Get user information
$stmt = $pdo->prepare('
    SELECT u.*, 
           CASE 
               WHEN u.role = "student" THEN s.full_name
               WHEN u.role = "supervisor" THEN sp.full_name
           END as full_name,
           CASE 
               WHEN u.role = "student" THEN s.email
               WHEN u.role = "supervisor" THEN sp.email
           END as email,
           CASE 
               WHEN u.role = "student" THEN s.student_number
               WHEN u.role = "supervisor" THEN sp.employee_id
           END as identification_number
    FROM users u
    LEFT JOIN students s ON u.user_id = s.user_id
    LEFT JOIN supervisors sp ON u.user_id = sp.user_id
    WHERE u.user_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Validate current password
        if (!empty($current_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Current password is incorrect';
            }
            
            // Validate new password
            if (empty($new_password)) {
                $errors[] = 'New password is required when changing password';
            } elseif (strlen($new_password) < 8) {
                $errors[] = 'New password must be at least 8 characters long';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match';
            }
        }
        
        // Validate email
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Validate full name
        if (empty($full_name)) {
            $errors[] = 'Full name is required';
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Update user information
                if ($_SESSION['role'] === 'student') {
                    $stmt = $pdo->prepare('
                        UPDATE students 
                        SET full_name = ?, email = ?
                        WHERE user_id = ?
                    ');
                } else {
                    $stmt = $pdo->prepare('
                        UPDATE supervisors 
                        SET full_name = ?, email = ?
                        WHERE user_id = ?
                    ');
                }
                $stmt->execute([$full_name, $email, $_SESSION['user_id']]);
                
                // Update password if provided
                if (!empty($new_password)) {
                    $stmt = $pdo->prepare('
                        UPDATE users 
                        SET password = ?
                        WHERE user_id = ?
                    ');
                    $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['user_id']]);
                }
                
                $pdo->commit();
                $success = 'Profile updated successfully';
                
                // Refresh user data
                $stmt = $pdo->prepare('
                    SELECT u.*, 
                           CASE 
                               WHEN u.role = "student" THEN s.full_name
                               WHEN u.role = "supervisor" THEN sp.full_name
                           END as full_name,
                           CASE 
                               WHEN u.role = "student" THEN s.email
                               WHEN u.role = "supervisor" THEN sp.email
                           END as email,
                           CASE 
                               WHEN u.role = "student" THEN s.student_number
                               WHEN u.role = "supervisor" THEN sp.employee_id
                           END as identification_number
                    FROM users u
                    LEFT JOIN students s ON u.user_id = s.user_id
                    LEFT JOIN supervisors sp ON u.user_id = sp.user_id
                    WHERE u.user_id = ?
                ');
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Profile';
$current_page = 'profile';
require_once 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Profile</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo h($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo h($success); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Account Information</h3>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?php echo h($user['full_name']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo h($user['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Identification Number</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo h($user['identification_number']); ?>" readonly>
                                    <small class="form-text text-muted">
                                        This is your <?php echo $_SESSION['role'] === 'student' ? 'student' : 'employee'; ?> number
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>Role</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ucfirst(h($user['role'])); ?>" readonly>
                                </div>

                                <hr>

                                <h5>Change Password</h5>
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" class="form-control">
                                    <small class="form-text text-muted">
                                        Leave blank if you don't want to change your password
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Account Security</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Security Tips</h5>
                                <ul class="mb-0">
                                    <li>Use a strong password that includes numbers, letters, and special characters</li>
                                    <li>Never share your password with anyone</li>
                                    <li>Change your password regularly</li>
                                    <li>Log out when you're done using the system</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Important</h5>
                                <p class="mb-0">
                                    If you suspect your account has been compromised, please contact the system administrator immediately.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 