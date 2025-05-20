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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare('
                SELECT u.*, 
                       CASE 
                           WHEN u.role = "student" THEN s.email
                           WHEN u.role = "supervisor" THEN sp.email
                       END as user_email
                FROM users u
                LEFT JOIN students s ON u.user_id = s.user_id
                LEFT JOIN supervisors sp ON u.user_id = sp.user_id
                WHERE s.email = ? OR sp.email = ?
            ');
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token
                $stmt = $pdo->prepare('
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([$user['user_id'], $token, $expires]);
                
                // Send reset email
                $reset_link = 'http://' . $_SERVER['HTTP_HOST'] . '/auth/reset_password.php?token=' . $token;
                $to = $email;
                $subject = 'Password Reset Request';
                $message = "
                    Hello {$user['full_name']},
                    
                    You have requested to reset your password. Click the link below to reset your password:
                    
                    {$reset_link}
                    
                    This link will expire in 1 hour.
                    
                    If you did not request this password reset, please ignore this email.
                    
                    Best regards,
                    Project Tracker Team
                ";
                $headers = 'From: noreply@projecttracker.com' . "\r\n" .
                          'Reply-To: noreply@projecttracker.com' . "\r\n" .
                          'X-Mailer: PHP/' . phpversion();
                
                if (mail($to, $subject, $message, $headers)) {
                    $success = 'Password reset instructions have been sent to your email';
                } else {
                    $error = 'Error sending reset email. Please try again later.';
                }
            } else {
                $error = 'No account found with that email address';
            }
        } catch (Exception $e) {
            $error = 'Error processing request: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - Project Tracker</title>
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
                <h1>Forgot Password</h1>
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
                    </div>
                <?php endif; ?>

                <p class="text-muted">
                    Enter your email address and we'll send you instructions to reset your password.
                </p>

                <form method="post">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                            <input type="email" name="email" class="form-control" 
                                   placeholder="Email" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
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
            </div>
        </div>
    </div>

    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
</body>
</html> 