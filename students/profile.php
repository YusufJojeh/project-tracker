<?php
// students/profile.php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

// 1) Fetch this student’s user record
$studentId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT username, email, role, created_at
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    die('Access denied.');
}

// 2) Fetch the student’s projects
$projectStmt = $pdo->prepare("
    SELECT project_id, title, created_at
    FROM projects
    WHERE student_id = ?
    ORDER BY created_at DESC
");
$projectStmt->execute([$studentId]);
$projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);

// 3) Unread notifications count for the navbar
$unreadCount = get_unread_notifications_count($pdo, $studentId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Profile | Project Tracker</title>
  <!-- AdminLTE & dependencies -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Sidebar toggle -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
      </ul>
      <!-- Right navbar -->
      <ul class="navbar-nav ml-auto">
        <!-- Notifications -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <?php if ($unreadCount): ?>
            <span class="badge badge-warning navbar-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-header"><?php echo $unreadCount; ?> Notifications</span>
            <div class="dropdown-divider"></div>
            <a href="notifications.php" class="dropdown-item dropdown-footer">See All Notifications</a>
          </div>
        </li>
        <!-- Logout -->
        <li class="nav-item">
          <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i></a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <!-- Brand Logo -->
      <a href="dashboard.php" class="brand-link">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
        <!-- User panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="info">
            <a href="profile.php" class="d-block"><?php echo htmlspecialchars($student['username']); ?></a>
          </div>
        </div>
        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
            <li class="nav-item">
              <a href="dashboard.php" class="nav-link">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="projects.php" class="nav-link">
                <i class="nav-icon fas fa-project-diagram"></i>
                <p>My Projects</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="create_project.php" class="nav-link">
                <i class="nav-icon fas fa-plus-circle"></i>
                <p>Create Project</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="stage_upload.php" class="nav-link">
                <i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="notifications.php" class="nav-link">
                <i class="nav-icon far fa-bell"></i>
                <p>Notifications</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="profile.php" class="nav-link active">
                <i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="../auth/logout.php" class="nav-link">
                <i class="nav-icon fas fa-sign-out-alt"></i>
                <p>Logout</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
      <div class="container-fluid">
        <!-- Profile Card -->
        <div class="card card-primary mb-4">
          <div class="card-header">
            <h3 class="card-title">My Profile</h3>
          </div>
          <div class="card-body">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($student['role'])); ?></p>
            <p><strong>Member Since:</strong> <?php echo (new DateTime($student['created_at']))->format('Y-m-d'); ?></p>
          </div>
        </div>

        <!-- Projects List -->
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">My Projects</h3>
          </div>
          <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted">You haven't created any projects yet.</p>
            <?php else: ?>
            <ul class="list-group">
              <?php foreach ($projects as $proj): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="project_view.php?project_id=<?php echo $proj['project_id']; ?>">
                  <?php echo htmlspecialchars($proj['title']); ?>
                </a>
                <span class="text-muted"><?php echo (new DateTime($proj['created_at']))->format('Y-m-d'); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer text-center">
      <strong>&copy; <?php echo date('Y'); ?> Project Tracker</strong>
    </footer>
  </div>

  <!-- Scripts -->
  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>