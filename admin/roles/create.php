<?php
// admin/roles/create.php
session_start();

// Database & helper functions
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// TEMPORARY: only allow admins
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden: admins only.';
    exit;
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }
    // Input sanitization
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Role name cannot be empty';
    } else {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $ins = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
        $ins->execute([$safeName]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Role &mdash; Admin</title>
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- AdminLTE -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Left navbar -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
      </ul>
      <!-- Right navbar -->
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a href="../auth/logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </nav>

    <!-- Main Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="../dashboard.php" class="brand-link text-center">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item"><a href="../dashboard.php" class="nav-link"><i
                  class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="../users.php" class="nav-link"><i class="nav-icon fas fa-users"></i>
                <p>Users</p>
              </a></li>
            <li class="nav-item"><a href="../departments.php" class="nav-link"><i class="nav-icon fas fa-building"></i>
                <p>Departments</p>
              </a></li>
            <li class="nav-item"><a href="index.php" class="nav-link active"><i class="nav-icon fas fa-user-tag"></i>
                <p>Roles & Permissions</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
      <!-- Page header -->
      <section class="content-header">
        <div class="container-fluid">
          <h1>Create New Role</h1>
        </div>
      </section>

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
          <div class="card">
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                  <label for="name">Role Name</label>
                  <input type="text" id="name" name="name" class="form-control" placeholder="e.g. supervisor" required>
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save
                </button>
                <a href="index.php" class="btn btn-default">Cancel</a>
              </form>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
      <div class="float-right d-none d-sm-inline"><b>Version</b> 1.0.0</div>
      <strong>&copy; <?php echo date('Y'); ?> Project Tracker.</strong> All rights reserved.
    </footer>

  </div>
  <!-- ./wrapper -->

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>

</html>