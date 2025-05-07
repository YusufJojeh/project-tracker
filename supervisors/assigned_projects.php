<?php
// supervisors/assigned_projects.php
session_start();
require __DIR__ . '/../config/database.php';

// Access control: only supervisors
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../auth/login.php');
    exit;
}
$supervisorId = $_SESSION['user_id'];

// Fetch projects assigned to this supervisor
$stmt = $pdo->prepare("
  SELECT
    p.project_id,
    p.title,
    p.created_at,
    u.full_name AS student_name
  FROM projects p
  JOIN users u ON p.student_id = u.user_id
  WHERE p.supervisor_id = ?
  ORDER BY p.created_at DESC
");
$stmt->execute([$supervisorId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>View Projects – MyApp</title>
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

  .card {
    background: #2f2f3f;
    border: none;
    color: #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
  }

  .card-header {
    background: #3b3b4d;
    border-bottom: 2px solid #4e73df;
    color: #fff;
  }

  .table-hover tbody tr:hover {
    background-color: #3a3a4a;
  }

  .main-footer {
    background: #2e2e3d;
    color: #f8f9fc;
  }

  .btn-view {
    background: #17a2b8;
    border-color: #17a2b8;
    color: #fff;
  }

  .btn-view:hover {
    background: #138496;
  }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-light" style="background:#2e2e3d;">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link text-light" data-widget="pushmenu" href="#">
            <i class="fas fa-bars"></i>
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link text-light" href="../auth/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="#" class="brand-link text-center" style="background:#4e73df;">
        <span class="brand-text font-weight-light">MyApp</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <!-- Assigned Stages -->
            <li class="nav-item">
              <a href="dashboard.php"
                class="nav-link <?php echo basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':''; ?>">
                <i class="nav-icon fas fa-layer-group"></i>
                <p>Assigned Stages</p>
              </a>
            </li>
            <!-- View Projects -->
            <li class="nav-item">
              <a href="assigned_projects.php"
                class="nav-link <?php echo basename($_SERVER['PHP_SELF'])==='assigned_projects.php'?'active':''; ?>">
                <i class="nav-icon fas fa-project-diagram"></i>
                <p>View Projects</p>
              </a>
            </li>
            <!-- Profile -->
            <li class="nav-item">
              <a href="profile.php"
                class="nav-link <?php echo basename($_SERVER['PHP_SELF'])==='profile.php'?'active':''; ?>">
                <i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper p-3">
      <div class="row mb-4">
        <div class="col">
          <h3 class="text-light">All Assigned Projects</h3>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <i class="fas fa-project-diagram"></i> Projects
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover table-dark mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Student</th>
                <th>Created</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($projects)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted">No projects assigned yet.</td>
              </tr>
              <?php else: foreach ($projects as $proj): ?>
              <tr>
                <td><?php echo htmlspecialchars($proj['title']); ?></td>
                <td><?php echo htmlspecialchars($proj['student_name']); ?></td>
                <td><?php echo (new DateTime($proj['created_at']))->format('Y-m-d'); ?></td>
                <td>
                  <a href="project_view.php?project_id=<?php echo $proj['project_id']; ?>" class="btn btn-sm btn-view">
                    <i class="fas fa-eye"></i> View
                  </a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
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