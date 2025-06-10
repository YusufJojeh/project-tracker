<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only allow admins
if ( !isset( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'admin' ) {
    header( 'Location: ../auth/login.php' );
    exit();
}

// Validate user ID
if ( !isset( $_GET[ 'id' ] ) || !ctype_digit( $_GET[ 'id' ] ) ) {
    header( 'Location: users.php' );
    exit();
}
$user_id = intval( $_GET[ 'id' ] );

// Fetch user data
$stmt = $pdo->prepare( 'SELECT * FROM users WHERE user_id = ?' );
$stmt->execute( [ $user_id ] );
$user = $stmt->fetch( PDO::FETCH_ASSOC );
if ( !$user ) {
    header( 'Location: users.php' );
    exit();
}

// Initialize variables
$username = $user[ 'username' ];
$email = $user[ 'email' ];
$role = $user[ 'role' ];
$success = '';
$errors = [];

// Handle form submission
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $username = trim( $_POST[ 'username' ] );
    $email    = trim( $_POST[ 'email' ] );
    $role     = $_POST[ 'role' ];
    $password = $_POST[ 'password' ];
    $password_confirm = $_POST[ 'password_confirm' ];

    // Validate input
    if ( empty( $username ) ) $errors[] = 'Username is required.';
    if ( empty( $email ) ) $errors[] = 'Email is required.';
    if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) $errors[] = 'Invalid email format.';
    if ( empty( $role ) || !in_array( $role, [ 'admin', 'supervisor', 'student' ] ) ) $errors[] = 'Invalid role selected.';

    // Only validate password if it's provided
    if (!empty($password) && $password !== $password_confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check for existing username/email (excluding this user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->execute([$username, $email, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Username or email already exists.";
    }

    // Update user if no errors
    if (empty($errors)) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, email=?, role=?, password=? WHERE user_id=?";
            $params = [$username, $email, $role, $hashed_password, $user_id];
        } else {
            $sql = "UPDATE users SET username=?, email=?, role=? WHERE user_id=?";
            $params = [$username, $email, $role, $user_id];
        }

        if ($pdo->prepare($sql)->execute($params)) {
            $success = "User updated successfully!";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $username = $user['username'];
            $email = $user['email'];
            $role = $user['role'];
        } else {
            $errors[] = "Failed to update user. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit User - Project Tracker</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- AdminLTE -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <!-- Left navbar links -->
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#' role='button'><i class='fas fa-bars'></i></a>
        </li>
      </ul>
      <!-- Right navbar links -->
      <ul class='navbar-nav ml-auto'>
        <li class='nav-item'>
          <a class='nav-link' href='../auth/logout.php'>
            <i class='fas fa-sign-out-alt'></i> Logout
          </a>
        </li>
      </ul>
    </nav>
    <!-- Sidebar (copy from your layout if needed) -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="dashboard.php" class="brand-link">
        <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTE Logo"
          class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="users.php" class="nav-link"><i class="nav-icon fas fa-users"></i>
                <p>Users</p>
              </a></li>
            <li class="nav-item"><a href="departments.php" class="nav-link"><i class="nav-icon fas fa-building"></i>
                <p>Departments</p>
              </a></li>
            <li class="nav-item"><a href="roles.php" class="nav-link"><i class="nav-icon fas fa-user-tag"></i>
                <p>Roles & Permissions</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>Edit User</h1>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="container-fluid">
          <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
          <?php endif; ?>
          <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul>
              <?php foreach ($errors as $err): ?>
              <li><?php echo htmlspecialchars($err); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
          <div class="card">
            <div class="card-body">
              <form method="post" action="">
                <div class="form-group">
                  <label for="username">Username</label>
                  <input type="text" class="form-control" id="username" name="username"
                    value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" name="email"
                    value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                  <label for="role">Role</label>
                  <select class="form-control" id="role" name="role" required>
                    <option value="">Select a role</option>
                    <option value="admin" <?php if ($role === 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="supervisor" <?php if ($role === 'supervisor') echo 'selected'; ?>>Supervisor</option>
                    <option value="student" <?php if ($role === 'student') echo 'selected';
    ?>>Student</option>
                  </select>
                </div>
                <div class='form-group'>
                  <label for='password'>Password ( leave blank to keep current )</label>
                  <input type='password' class='form-control' id='password' name='password' autocomplete='new-password'>
                </div>
                <div class='form-group'>
                  <label for='password_confirm'>Confirm Password</label>
                  <input type='password' class='form-control' id='password_confirm' name='password_confirm'
                    autocomplete='new-password'>
                </div>
                <button type='submit' class='btn btn-primary'><i class='fas fa-save'></i> Update User</button>
                <a href='users.php' class='btn btn-secondary'>Cancel</a>
              </form>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
  <!-- JS -->
  <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
  <script src='https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js'></script>
  <script src='https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js'></script>
</body>

</html>
