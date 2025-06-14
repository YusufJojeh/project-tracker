<?php
// students/project_create.php

session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
require_once '../config/database.php';
require_once '../includes/functions.php';
require_role('student');

$error = '';

// Fetch supervisors from users table
try {
    $stmt = $pdo->prepare("SELECT user_id AS supervisor_id, username FROM users WHERE role = 'supervisor' ORDER BY username");
    $stmt->execute();
    $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error loading supervisors: ' . $e->getMessage();
    $supervisors = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $supervisor_id = $_POST['supervisor_id'] ?? '';

    if ($title === '') {
        $error = 'Project title is required.';
    } elseif (empty($supervisor_id)) {
        $error = 'Please select a supervisor.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, student_id, supervisor_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())");
            $stmt->execute([$title, $description, $_SESSION['user_id'], $supervisor_id]);
            $project_id = $pdo->lastInsertId();

            $pdo->commit();
            header("Location: project_view.php?id=$project_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error creating project: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Create New Project | Project Tracker</title>
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

<body class='hold-transition sidebar-mini layout-fixed'>
  <div class='wrapper'>
    <!-- Navbar -->
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#' role='button'>
            <i class='fas fa-bars'></i>
          </a>
        </li>
      </ul>
      <ul class='navbar-nav ml-auto'>
        <li class='nav-item'>
          <a href='../auth/logout.php' class='nav-link'><i class='fas fa-sign-out-alt'></i> Logout</a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Sidebar -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <a href='#' class='brand-link'>
        <img src='../dist/img/AdminLTELogo.png' alt='Logo' class='brand-image img-circle elevation-3'
          style='opacity: .8'>
        <span class='brand-text font-weight-light'>Project Tracker</span>
      </a>
      <div class='sidebar'>
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column' data-widget='treeview' role='menu' data-accordion='false'>
            <li class='nav-item'>
              <a href='dashboard.php' class='nav-link'>
                <i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='projects.php' class='nav-link'>
                <i class='nav-icon fas fa-project-diagram'></i>
                <p>My Projects</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='project_create.php' class='nav-link active'>
                <i class='nav-icon fas fa-plus-circle'></i>
                <p>Create New Project</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='stage_upload.php' class='nav-link'>
                <i class='nav-icon fas fa-upload'></i>
                <p>Upload Stage</p>
              </a>
            </li>

            <li class='nav-item'>
              <a href='profile.php' class='nav-link'>
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
    <!-- /.sidebar -->

    <!-- Content Wrapper. Contains page content -->
    <div class='content-wrapper'>
      <section class='content-header'>
        <div class='container-fluid'>
          <h1>Create New Project</h1>
          <a href='projects.php' class='btn btn-secondary mb-2'><i class='fas fa-arrow-left'></i> Back to Projects</a>
        </div>
      </section>
      <section class='content'>
        <div class='container-fluid'>
          <?php if ($error): ?>
          <div class='alert alert-danger'><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <div class='card card-primary'>
            <div class='card-header'>
              <h3 class='card-title'>Project Details</h3>
            </div>
            <form method='post' action=''>
              <div class='card-body'>
                <div class='form-group'>
                  <label for='title'>Project Title <span class='text-danger'>*</span></label>
                  <input type='text' name='title' id='title' class='form-control'
                    value='<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>' required>
                </div>
                <div class='form-group'>
                  <label for='description'>Description</label>
                  <textarea name='description' id='description' class='form-control'
                    rows='4'><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                <div class='form-group'>
                  <label for='supervisor_id'>Supervisor <span class='text-danger'>*</span></label>
                  <select name='supervisor_id' id='supervisor_id' class='form-control' required>
                    <option value=''>Select Supervisor</option>
                    <?php foreach ($supervisors as $sup): ?>
                    <option value='<?php echo $sup['supervisor_id']; ?>'
                      <?php if (($_POST['supervisor_id'] ?? '') == $sup['supervisor_id']) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($sup['username']); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class='card-footer'>
                <button type='submit' class='btn btn-primary'><i class='fas fa-save'></i> Create Project</button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <footer class='main-footer'>
      <div class='float-right d-none d-sm-inline'>Version 1.0.0</div>
      <strong>&copy; <?php echo date('Y'); ?> Project Tracker.</strong> All rights reserved.
    </footer>
  </div>
  <!-- ./wrapper -->
  <!-- REQUIRED SCRIPTS -->
  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>