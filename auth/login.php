<?php
session_start();

// Include necessary files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize error message variable
$error = '';

// Check if the user is already logged in
if ( isset( $_SESSION[ 'user_id' ] ) ) {
    // Redirect to their dashboard based on the role
    $folders = array(
        'admin'      => 'admin',
        'supervisor' => 'supervisors',
        'student'    => 'students'
    );
    $role   = $_SESSION[ 'role' ];
    $folder = isset( $folders[ $role ] ) ? $folders[ $role ] : 'auth';
    header( 'Location: ../' . $folder . '/dashboard.php' );
    exit();
}

// Handle form submission
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $username = trim( $_POST[ 'username' ] ?? '' );
    $password = trim( $_POST[ 'password' ] ?? '' );

    if ( $username === '' || $password === '' ) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Prepare and execute query to fetch user information from the database
            $stmt = $pdo->prepare( 'SELECT user_id, username, email, password, role FROM users WHERE username = ? LIMIT 1' );
            $stmt->execute( [ $username ] );
            $user = $stmt->fetch( PDO::FETCH_ASSOC );

            // Plain-text comparison for password check ( no hashing )
            if ( $user && $password === $user[ 'password' ] ) {
                // User authenticated, set session variables
                session_regenerate_id( true );
                $_SESSION[ 'user_id' ] = $user[ 'user_id' ];
                $_SESSION[ 'username' ] = $user[ 'username' ];
                $_SESSION[ 'email' ] = $user[ 'email' ];
                $_SESSION[ 'role' ] = $user[ 'role' ];
                $_SESSION[ 'role_id' ] = $user[ 'role_id' ];
                // Optional: Create a login notification ( if function exists )
                if ( function_exists( 'create_notification' ) ) {
                    create_notification(
                        $pdo,
                        $user[ 'user_id' ],
                        'Login Successful',
                        'You have successfully logged in.'
                    );
                }

                // Redirect to the appropriate dashboard based on the user role
                $targets = array(
                    'admin'      => 'admin/dashboard.php',
                    'supervisor' => 'supervisors/dashboard.php',
                    'student'    => 'students/dashboard.php'
                );
                $path = isset( $targets[ $user[ 'role' ] ] ) ? $targets[ $user[ 'role' ] ] : 'auth/login.php';
                header( 'Location: ../' . $path );
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch ( PDOException $ex ) {
            error_log( 'Login error: ' . $ex->getMessage() );
            $error = 'An unexpected error occurred. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'utf-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1'>
<title>Login — Project Tracker</title>

<!-- Google Font -->
<link rel = 'stylesheet'
href = 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
<!-- Font Awesome -->
<link rel = 'stylesheet' href = '../plugins/fontawesome-free/css/all.min.css'>
<!-- Overlay Scrollbars -->
<link rel = 'stylesheet' href = '../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
<!-- AdminLTE -->
<link rel = 'stylesheet' href = '../dist/css/adminlte.min.css'>
</head>

<body class = 'hold-transition login-page' style = 'background: #1e1e2f;'>
<div class = 'login-box'>
<div class = 'login-logo'>
<a href = '#'><b>Project</b>Tracker</a>
</div>

<div class = 'card card-outline card-primary'>
<div class = 'card-header text-center'>
<a href = '#' class = 'h1'><b>Project</b>Tracker</a>
</div>
<div class = 'card-body'>
<p class = 'login-box-msg'>Sign in to start your session</p>

<!-- Error Display -->
<?php if ( $error !== '' ) {
    ?>
    <div class = 'alert alert-danger'>
    <i class = 'fas fa-exclamation-circle me-2'></i>
    <?php echo htmlspecialchars( $error );
    ?>
    </div>
    <?php }
    ?>

    <!-- Login Form -->
    <form action = '' method = 'post' autocomplete = 'off' novalidate>
    <div class = 'input-group mb-3'>
    <input type = 'text' name = 'username' class = 'form-control' placeholder = 'Username'
    value = "<?php echo htmlspecialchars($username); ?>" required>
    <div class = 'input-group-append'>
    <div class = 'input-group-text bg-primary'>
    <span class = 'fas fa-user text-white'></span>
    </div>
    </div>
    </div>

    <div class = 'input-group mb-3'>
    <input type = 'password' name = 'password' class = 'form-control' placeholder = 'Password' required>
    <div class = 'input-group-append'>
    <div class = 'input-group-text bg-primary'>
    <span class = 'fas fa-lock text-white'></span>
    </div>
    </div>
    </div>

    <div class = 'row'>
    <div class = 'col-8'>
    <a href = 'forgot_password.php'>Forgot password?</a>
    </div>
    <div class = 'col-4'>
    <button type = 'submit' class = 'btn btn-primary btn-block'>
    Sign In
    </button>
    </div>
    </div>
    </form>
    </div>
    </div>

    <p class = 'mt-3 text-center text-white'>
    &copy;
    <?php echo date( 'Y' );
    ?> Mohammed Nour Abdel Aziz Aksem
    </p>
    </div>

    <!-- Scripts -->
    <script src = '../plugins/jquery/jquery.min.js'></script>
    <script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
    <script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
    <script src = '../dist/js/adminlte.min.js'></script>
    </body>

    </html>