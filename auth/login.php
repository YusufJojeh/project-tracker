<?php
// auth/login.php
session_start();

// Redirect if already logged in
if ( isset( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ../' . $_SESSION[ 'role' ] . 's/dashboard.php' );
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$error    = '';
$username = '';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $username = trim( $_POST[ 'username' ] ?? '' );
    $password = $_POST[ 'password' ] ?? '';

    if ( $username === '' || $password === '' ) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare( "
                SELECT user_id, username, email, password, role
                FROM users
                WHERE username = ?
            " );
            $stmt->execute( [ $username ] );
            $user = $stmt->fetch();

            if ( $user && $password === $user[ 'password' ] ) {
                session_regenerate_id( true );
                $_SESSION[ 'user_id' ]  = $user[ 'user_id' ];
                $_SESSION[ 'username' ] = $user[ 'username' ];
                $_SESSION[ 'email' ]    = $user[ 'email' ];
                $_SESSION[ 'role' ]     = $user[ 'role' ];

                if ( function_exists( 'create_notification' ) ) {
                    create_notification(
                        $pdo,
                        $user[ 'user_id' ],
                        'Login Successful',
                        'You have successfully logged in.'
                    );
                }

                // Redirect by role
                $map = [
                    'admin'      => '../admin/dashboard.php',
                    'supervisor' => '../supervisors/dashboard.php',
                    'student'    => '../students/dashboard.php',
                ];
                header( 'Location: ' . ( $map[ $user[ 'role' ] ] ?? '../auth/login.php' ) );
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch ( PDOException $e ) {
            error_log( 'Login error: ' . $e->getMessage() );
            $error = 'An unexpected error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Login — Project Tracker</title>
  <!-- Google Font: Source Sans Pro -->
  <link rel='stylesheet'
    href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
  <!-- Font Awesome -->
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css'>
  <!-- overlayScrollbars -->
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
  <!-- Theme style -->
  <link rel='stylesheet' href='../dist/css/adminlte.min.css'>
</head>

<body class='hold-transition login-page' style='background: #1e1e2f;'>
  <div class='login-box'>
    <div class='login-logo'>
      <a href='#'><b>Project</b>Tracker</a>
    </div>
    <!-- /.login-logo -->
    <div class='card card-outline card-primary'>
      <div class='card-header text-center'>
        <a href='#' class='h1'><b>Project</b>Tracker</a>
      </div>
      <div class='card-body'>
        <p class='login-box-msg'>Sign in to start your session</p>

        <?php if ( $error ): ?>
        <div class='alert alert-danger'>
          <i class='fas fa-exclamation-circle me-2'></i>
          <?php echo htmlspecialchars( $error );
?>
        </div>
        <?php endif;
?>

        <form action='' method='post' autocomplete='off' novalidate>
          <div class='input-group mb-3'>
            <input type='text' name='username' class='form-control' placeholder='Username'
              value="<?php echo htmlspecialchars($username); ?>" required>
            <div class='input-group-append'>
              <div class='input-group-text bg-primary'>
                <span class='fas fa-user text-white'></span>
              </div>
            </div>
          </div>
          <div class='input-group mb-3'>
            <input type='password' name='password' class='form-control' placeholder='Password' required>
            <div class='input-group-append'>
              <div class='input-group-text bg-primary'>
                <span class='fas fa-lock text-white'></span>
              </div>
            </div>
          </div>
          <div class='row'>
            <div class='col-8'>
              <a href='forgot_password.php'>Forgot password?</a>
            </div>
            <div class='col-4'>
              <button type='submit' class='btn btn-primary btn-block'>
                Sign In
              </button>
            </div>
          </div>
        </form>

      </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->
    <p class='mt-3 text-center text-white'>&copy;
      <?php echo date( 'Y' );
?>Mohammed Nour Abdel Aziz Aksem</p>
  </div>
  <!-- /.login-box -->

  <!-- REQUIRED SCRIPTS -->
  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>