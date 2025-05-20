<?php
session_start();

// Redirect if already logged in
if ( isset( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ../' . $_SESSION[ 'role' ] . 's/dashboard.php' );
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $username = trim( $_POST[ 'username' ] ?? '' );
    $password = $_POST[ 'password' ] ?? '';

    if ( empty( $username ) || empty( $password ) ) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $stmt = $pdo->prepare( "
                SELECT user_id, username, email, password, role
                FROM users
                WHERE username = ?
            " );
            $stmt->execute( [ $username ] );
            $user = $stmt->fetch();

            // Compare password directly ( plain text )
            if ( $user && $password === $user[ 'password' ] ) {
                // Regenerate session ID for security
                session_regenerate_id( true );

                // Set session variables
                $_SESSION[ 'user_id' ] = $user[ 'user_id' ];
                $_SESSION[ 'username' ] = $user[ 'username' ];
                $_SESSION[ 'email' ] = $user[ 'email' ];
                $_SESSION[ 'role' ] = $user[ 'role' ];

                // Create login notification
                if ( function_exists( 'create_notification' ) ) {
                    create_notification(
                        $pdo,
                        $user[ 'user_id' ],
                        'Login Successful',
                        'You have successfully logged in to your account.'
                    );
                }

                // Redirect based on role
                switch ( $user[ 'role' ] ) {
                    case 'admin':
                    header( 'Location: ../admin/dashboard.php' );
                    break;
                    case 'supervisor':
                    header( 'Location: ../supervisors/dashboard.php' );
                    break;
                    case 'student':
                    header( 'Location: ../students/dashboard.php' );
                    break;
                    default:
                    $error = 'Invalid user role';
                    break;
                }
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch ( PDOException $e ) {
            $error = 'An error occurred. Please try again later.';
            error_log( 'Login error: ' . $e->getMessage() );
        }
    }
}
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1'>
<title>Login - Project Tracker</title>
<link href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel = 'stylesheet'>
<link href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel = 'stylesheet'>
<style>
html,
body {
    height: 100%;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1e1e2f;
    color: #f0f0f0;
}

.login-box {
    width: 360px;
    max-width: 100%;
}

.card {
    background: #2f2f3f;
    border: none;
    box-shadow: 0 4px 8px rgba( 0, 0, 0, 0.3 );
}

.card-header {
    background: #3b3b4d;
    border-bottom: 2px solid #4e73df;
    color: #fff;
}

.btn-primary {
    background: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background: #2e59d9;
    border-color: #2e59d9;
}

.form-control {
    background: #2f2f3f;
    border: 1px solid #4e73df;
    color: #fff;
}

.form-control:focus {
    background: #2f2f3f;
    border-color: #4e73df;
    color: #fff;
    box-shadow: 0 0 0 0.25rem rgba( 78, 115, 223, 0.25 );
}

.form-label {
    color: #fff;
}

.alert {
    background: rgba( 220, 53, 69, 0.1 );
    border-color: rgba( 220, 53, 69, 0.2 );
    color: #dc3545;
}
</style>
</head>

<body class = 'bg-light'>
<div class = 'container'>
<div class = 'row justify-content-center mt-5'>
<div class = 'col-md-6'>
<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'text-center'>Login</h3>
</div>
<div class = 'card-body'>
<?php if ( $error ): ?>
<div class = 'alert alert-danger'>
<i class = 'fas fa-exclamation-circle me-2'></i>
<?php echo h( $error );
?>
</div>
<?php endif;
?>

<form method = 'POST' action = '' autocomplete = 'off'>
<div class = 'mb-3'>
<label for = 'username' class = 'form-label'>
<i class = 'fas fa-user me-2'></i>Username
</label>
<input type = 'text' class = 'form-control' id = 'username' name = 'username'
value = "<?php echo h($username ?? ''); ?>" required>
</div>
<div class = 'mb-3'>
<label for = 'password' class = 'form-label'>
<i class = 'fas fa-lock me-2'></i>Password
</label>
<input type = 'password' class = 'form-control' id = 'password' name = 'password' required>
</div>
<div class = 'd-grid gap-2'>
<button type = 'submit' class = 'btn btn-primary'>
<i class = 'fas fa-sign-in-alt me-2'></i>Login
</button>
<a href = 'forgot_password.php' class = 'btn btn-link text-light'>
<i class = 'fas fa-key me-2'></i>Forgot Password?
</a>
</div>
</form>
</div>
</div>
</div>
</div>
</div>
<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>

</html>