<?php
session_start();

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$supervisorId = $_SESSION['user_id'];

// Get supervisor details
$stmt = $pdo->prepare('SELECT full_name AS name, username AS email, created_at, role FROM users WHERE user_id = ?');
$stmt->execute([$supervisorId]);
$supervisor = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure the user is a supervisor
if (!$supervisor || $supervisor['role'] !== 'supervisor') {
    die('Access denied.');
}

// Get projects supervised by this supervisor (supervisor_id in projects table)
$projectStmt = $pdo->prepare('
    SELECT p.project_id, p.title, p.created_at, u.full_name AS student_name
    FROM projects p
    JOIN users u ON p.student_id = u.user_id
    WHERE p.supervisor_id = ?
    ORDER BY p.created_at DESC
');
$projectStmt->execute([$supervisorId]);
$projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Supervisor Profile – MyApp</title>
  <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css" />
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css" />
  <link rel="stylesheet" href="../dist/css/adminlte.min.css" />
  <style>
  body,
  .wrapper {
    background: #1e1e2f;
    color: #f0f0f0;
  }

  .main-sidebar {
    background: #2e2e3d;
  }

  .nav-sidebar .nav-link {
    color: #c1c1d1;
  }

  .nav-sidebar .nav-link.active {
    background: #4e73df;
    color: #fff;
  }

  .content-wrapper {
    background: #2c2f3c;
    color: #f0f0f0;
  }

  .card {
    background: #3b3b4f;
    border: none;
    color: #f1f1f1;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  }

  .card-header {
    background: #4e73df;
    color: #fff;
    border-bottom: none;
  }

  .btn-primary {
    background: #4e73df;
    border-color: #4e73df;
  }

  footer {
    background: #2e2e3d;
    color: #f8f9fc;
  }

  .list-group-item {
    background-color: #44475a;
    color: #fff;
  }

  .list-group-item a {
    color: #aad4ff;
    text-decoration: none;
  }

  .list-group-item a:hover {
    text-decoration: underline;
  }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-light" style="background:#2e2e3d;">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link text-light" data-widget="pushmenu" href="#"><i
              class="fas fa-bars"></i></a></li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a class="nav-link text-light" href="../auth/logout.php"><i
              class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="#" class="brand-link text-center" style="background:#4e73df;">
        <span class="brand-text font-weight-light" style="color:#fff;">SPTS</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link active"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper p-4">
      <div class="container">
        <!-- Profile Card -->
        <div class="card mb-4">
          <div class="card-header"><i class="fas fa-user-tie"></i> Supervisor Profile</div>
          <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($supervisor['name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($supervisor['email']); ?></p>
            <p><strong>Member since:</strong> <?php echo (new DateTime($supervisor['created_at']))->format('Y-m-d'); ?>
            </p>
          </div>
        </div>

        <!-- Supervised Projects -->
        <div class="card">
          <div class="card-header"><i class="fas fa-chalkboard-teacher"></i> Supervised Projects</div>
          <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted">You are not supervising any projects yet.</p>
            <?php else: ?>
            <ul class="list-group">
              <?php foreach ($projects as $project): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <a href="project_view.php?project_id=<?php echo $project['project_id']; ?>">
                    <?php echo htmlspecialchars($project['title']); ?>
                  </a>
                  <br>
                  <small class="text-muted">Student: <?php echo htmlspecialchars($project['student_name']); ?></small>
                </div>
                <span class="text-muted"><?php echo (new DateTime($project['created_at']))->format('Y-m-d'); ?></span>
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
      <strong>&copy; <?php echo date('Y'); ?> MyApp</strong>
    </footer>
  </div>

  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>