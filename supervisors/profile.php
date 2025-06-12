<?php
// supervisors/profile.php
session_start();

// 1 ) Access control
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}
$supervisorId = ( int )$_SESSION[ 'user_id' ];

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/notifications.php';

// 2 ) Fetch supervisor record
$stmt = $pdo->prepare( "
    SELECT username AS name,
           email,
           role,
           created_at,
           password
      FROM users
     WHERE user_id = ?
" );
$stmt->execute( [ $supervisorId ] );
$supervisor = $stmt->fetch( PDO::FETCH_ASSOC );
if ( !$supervisor ) {
    die( 'Access denied.' );
}

// 3 ) Handle profile edit form submission
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    // Validate inputs for username and email
    $username = trim( $_POST[ 'username' ] );
    $email = trim( $_POST[ 'email' ] );
    $errors = [];

    if ( empty( $username ) ) {
        $errors[] = 'Username is required.';
    }

    if ( empty( $email ) ) {
        $errors[] = 'Email is required.';
    } elseif ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        $errors[] = 'Invalid email format.';
    }

    // If the password change is requested, handle it
    if ( !empty( $_POST[ 'old_password' ] ) || !empty( $_POST[ 'new_password' ] ) || !empty( $_POST[ 'confirm_password' ] ) ) {
        $oldPassword = $_POST[ 'old_password' ];
        $newPassword = $_POST[ 'new_password' ];
        $confirmPassword = $_POST[ 'confirm_password' ];

        // Check if the old password is correct
        if ( !password_verify( $oldPassword, $supervisor[ 'password' ] ) ) {
            $errors[] = 'The old password is incorrect.';
        }

        // Validate new password and confirmation
        if ( empty( $newPassword ) ) {
            $errors[] = 'New password is required.';
        } elseif ( $newPassword !== $confirmPassword ) {
            $errors[] = 'The new password and confirmation password do not match.';
        } elseif ( strlen( $newPassword ) < 8 ) {
            $errors[] = 'The new password must be at least 8 characters long.';
        }

        // If no errors, update the password
        if ( empty( $errors ) ) {
            $hashedPassword = password_hash( $newPassword, PASSWORD_DEFAULT );
            // Update password in the database
            $updateStmt = $pdo->prepare( "
                UPDATE users
                SET password = ?
                WHERE user_id = ?
            " );
            $updateStmt->execute( [ $hashedPassword, $supervisorId ] );
        }
    }

    // If no errors for username/email, update them
    if ( empty( $errors ) ) {
        // Update username and email in the database
        $updateStmt = $pdo->prepare( "
            UPDATE users
            SET username = ?, email = ?
            WHERE user_id = ?
        " );
        $updateStmt->execute( [ $username, $email, $supervisorId ] );

        // Reload the profile data
        header( 'Location: profile.php' );
        exit;
    }
}

// 4 ) Fetch supervised projects
$projStmt = $pdo->prepare( "
    SELECT p.project_id,
           p.title,
           p.created_at,
           u.username AS student_name
      FROM projects p
      JOIN users u ON p.student_id = u.user_id
     WHERE p.supervisor_id = ?
     ORDER BY p.created_at DESC
" );
$projStmt->execute( [ $supervisorId ] );
$projects = $projStmt->fetchAll( PDO::FETCH_ASSOC );

// 5 ) Unread notifications count ( for navbar badge )
$unreadCount = get_unread_notifications_count( $pdo, $supervisorId );
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Supervisor Profile &mdash;
    Project Tracker</title>
  <!-- AdminLTE & dependencies -->
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css'>
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
  <link rel='stylesheet' href='../dist/css/adminlte.min.css'>

</head>

<body class='hold-transition sidebar-mini layout-fixed'>
  <div class='wrapper'>

    <!-- Navbar ( same as dashboard ) -->
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#'><i class='fas fa-bars'></i></a>
        </li>
      </ul>
      <!-- Right navbar -->
      <ul class='navbar-nav ml-auto'>
        <!-- Notifications -->
        <li class='nav-item dropdown'>
          <a class='nav-link' data-toggle='dropdown' href='#'>
            <i class='far fa-bell'></i>
            <?php if ( $unreadCount ): ?>
            <span class='badge badge-warning navbar-badge'><?php echo $unreadCount;
?></span>
            <?php endif;
?>
          </a>
          <div class='dropdown-menu dropdown-menu-lg dropdown-menu-right'>
            <span class='dropdown-item dropdown-header'><?php echo $unreadCount;
?> New</span>
            <div class='dropdown-divider'></div>
            <a href='notifications.php' class='dropdown-item dropdown-footer'>See All Notifications</a>
          </div>
        </li>
        <!-- Logout -->
        <li class='nav-item'>
          <a class='nav-link' href='../auth/logout.php'><i class='fas fa-sign-out-alt'></i></a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Sidebar -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <a href='dashboard.php' class='brand-link text-center'>
        <span class='brand-text font-weight-light'>Project Tracker</span>
      </a>
      <div class='sidebar'>
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column' data-widget='treeview'>
            <li class='nav-item'>
              <a href='dashboard.php' class='nav-link active'>
                <i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='projects.php' class='nav-link'>
                <i class='nav-icon fas fa-project-diagram'></i>
                <p>Projects</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='notifications.php' class='nav-link'>
                <i class='nav-icon far fa-bell'></i>
                <p>Notifications</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='profile.php' class='nav-link active'>
                <i class='nav-icon fas fa-user'></i>
                <p>Profile</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='../auth/logout.php' class='nav-link'>
                <i class='nav-icon fas fa-sign-out-alt'></i>
                <p>Logout</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content Wrapper -->
    <div class='content-wrapper'>
      <!-- Page header -->
      <section class='content-header'>
        <div class='container-fluid'>
          <h1>My Profile</h1>
        </div>
      </section>

      <!-- Main content -->
      <section class='content'>
        <div class='container-fluid'>

          <!-- Profile Card -->
          <div class='card'>
            <div class='card-header bg-primary'>
              <h3 class='card-title'><i class='fas fa-user-tie'></i> Supervisor Profile</h3>
            </div>
            <div class='card-body'>
              <p><strong>Username:</strong> <?php echo htmlspecialchars( $supervisor[ 'name' ], ENT_QUOTES ) ?></p>
              <p><strong>Email:</strong> <?php echo htmlspecialchars( $supervisor[ 'email' ], ENT_QUOTES ) ?></p>
              <p><strong>Member Since:</strong>
                <?php echo ( new DateTime( $supervisor[ 'created_at' ] ) )->format( 'Y-m-d' ) ?>
              </p>
            </div>
          </div>

          <!-- Profile Edit Form -->
          <div class='card'>
            <div class='card-header bg-warning'>
              <h3 class='card-title'><i class='fas fa-edit'></i> Edit Profile</h3>
            </div>
            <div class='card-body'>
              <?php if ( !empty( $errors ) ): ?>
              <div class='alert alert-danger'>
                <ul>
                  <?php foreach ( $errors as $error ): ?>
                  <li><?php echo htmlspecialchars( $error, ENT_QUOTES );
?></li>
                  <?php endforeach;
?>
                </ul>
              </div>
              <?php endif;
?>

              <form method='post'>
                <div class='form-group'>
                  <label for='username'>Username</label>
                  <input type='text' name='username' id='username' class='form-control'
                    value="<?php echo htmlspecialchars($supervisor['name'], ENT_QUOTES) ?>" required>
                </div>
                <div class='form-group'>
                  <label for='email'>Email</label>
                  <input type='email' name='email' id='email' class='form-control'
                    value="<?php echo htmlspecialchars($supervisor['email'], ENT_QUOTES) ?>" required>
                </div>

                <hr>

                <h5>Change Password</h5>
                <div class='form-group'>
                  <label for='old_password'>Old Password</label>
                  <input type='password' name='old_password' id='old_password' class='form-control'>
                </div>
                <div class='form-group'>
                  <label for='new_password'>New Password</label>
                  <input type='password' name='new_password' id='new_password' class='form-control'>
                </div>
                <div class='form-group'>
                  <label for='confirm_password'>Confirm New Password</label>
                  <input type='password' name='confirm_password' id='confirm_password' class='form-control'>
                </div>
                <button type='submit' class='btn btn-success'>
                  <i class='fas fa-save'></i> Save Changes
                </button>
              </form>
            </div>
          </div>

          <!-- Supervised Projects -->
          <div class='card'>
            <div class='card-header bg-primary'>
              <h3 class='card-title'><i class='fas fa-chalkboard-teacher'></i> Supervised Projects</h3>
            </div>
            <div class='card-body p-0'>
              <?php if ( empty( $projects ) ): ?>
              <div class='p-3 text-center text-muted'>You are not supervising any projects yet.</div>
              <?php else: ?>
              <ul class='list-group list-group-flush'>
                <?php foreach ( $projects as $p ): ?>
                <li class='list-group-item d-flex justify-content-between align-items-center'>
                  <a href="project_view.php?project_id=<?php echo $p['project_id'] ?>">
                    <?php echo htmlspecialchars( $p[ 'title' ], ENT_QUOTES ) ?>
                  </a>
                  <span class='text-muted'>
                    <?php echo ( new DateTime( $p[ 'created_at' ] ) )->format( 'Y-m-d' ) ?>
                  </span>
                </li>
                <?php endforeach ?>
              </ul>
              <?php endif ?>
            </div>
          </div>

        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <?php include __DIR__.'/../includes/footer.php';
?>

  </div>
  <!-- ./wrapper -->

  <!-- AdminLTE scripts -->
  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>