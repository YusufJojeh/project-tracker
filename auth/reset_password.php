<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: ../' . $_SESSION['role'] . 's/dashboard.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

try {
    // Verify token
    $stmt = $pdo->prepare('
        SELECT pr.*, u.role
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.user_id
        WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
    ');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($password)) {
                $error = 'Please enter a new password';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } else {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare('
                    UPDATE users 
                    SET password = ? 
                    WHERE user_id = ?
                ');
                $stmt->execute([$hashed_password, $reset['user_id']]);
                
                // Mark token as used
                $stmt = $pdo->prepare('
                    UPDATE password_resets 
                    SET used = 1 
                    WHERE token = ?
                ');
                $stmt->execute([$token]);
                
                $success = 'Your password has been reset successfully. You can now login with your new password.';
            }
        }
    }
} catch (Exception $e) {
    $error = 'Error processing request: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Project Tracker</title>
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <style>
        body {
            background: #f4f6f9;
        }
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 400px;
            margin: 0;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background: #4e73df;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .card-header h1 {
            font-size: 24px;
            margin: 0;
        }
        .card-body {
            padding: 30px;
        }
        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        .btn-primary {
            background: #4e73df;
            border-color: #4e73df;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background: #2e59d9;
            border-color: #2e59d9;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <div class="card">
            <div class="card-header">
                <h1>Reset Password</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo h($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo h($success); ?>
                        <div class="mt-3">
                            <a href="login.php" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt"></i> Go to Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="post">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="New Password" required>
                            </div>
                            <small class="form-text text-muted">
                                Password must be at least 8 characters long
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" name="confirm_password" class="form-control" 
                                       placeholder="Confirm New Password" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p class="mb-0">
                            <a href="login.php" class="text-muted">
                                <i class="fas fa-arrow-left"></i> Back to Login
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
</body>
</html> 