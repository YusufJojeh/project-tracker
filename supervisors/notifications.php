<?php
// supervisors/notifications.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

// Only supervisors allowed
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../auth/login.php');
    exit;
}
$uid = (int)$_SESSION['user_id'];

// Handle mark‐read actions
if (!empty($_GET['action'])) {
    if ($_GET['action'] === 'mark_read' && !empty($_GET['id'])) {
        mark_notification_as_read($pdo, (int)$_GET['id']);
    }
    if ($_GET['action'] === 'mark_all') {
        mark_all_notifications_as_read($pdo, $uid);
    }
    header('Location: notifications.php');
    exit;
}

// Fetch only unread notifications
$stmt = $pdo->prepare("
    SELECT notification_id, message, created_at
    FROM notifications
    WHERE user_id = ? AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->execute([$uid]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount   = count($notifications);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Unread Notifications &mdash; Supervisor</title>
  <!-- AdminLTE -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <!-- Navbar (same as dashboard) -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <!-- Notifications dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <?php if ($unreadCount): ?>
            <span class="badge badge-warning navbar-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-item dropdown-header"><?php echo $unreadCount; ?> New</span>
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

    <!-- Sidebar (same as dashboard) -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="dashboard.php" class="brand-link text-center">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
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
                <p>Projects</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="notifications.php" class="nav-link active">
                <i class="nav-icon far fa-bell"></i>
                <p>Notifications</p>
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
    <div class="content-wrapper">

      <!-- Page header -->
      <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center mb-2">
          <h1 class="m-0">Unread Notifications</h1>
          <?php if ($unreadCount): ?>
          <a href="?action=mark_all" class="btn btn-success btn-sm">
            <i class="fas fa-check-double"></i> Mark All Read
          </a>
          <?php endif; ?>
        </div>
      </section>

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header bg-primary">
              <h3 class="card-title">Your Unread Notifications</h3>
            </div>
            <div class="card-body p-0">
              <?php if (empty($notifications)): ?>
              <div class="p-3 text-center text-muted">No unread notifications.</div>
              <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                <div class="list-group-item d-flex justify-content-between align-items-start font-weight-bold">
                  <div class="mr-3">
                    <i class="far fa-bell fa-lg text-info"></i>
                  </div>
                  <div class="flex-fill">
                    <p class="mb-1"><?php echo htmlspecialchars($n['message'], ENT_QUOTES); ?></p>
                    <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?></small>
                  </div>
                  <div class="text-right">
                    <span class="badge badge-info">Info</span>
                    <div class="mt-2">
                      <a href="?action=mark_read&amp;id=<?php echo $n['notification_id']; ?>"
                        class="btn btn-link btn-sm">Mark read</a>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <footer class="main-footer text-center">
      <strong>&copy; <?php echo date('Y'); ?> Project Tracker</strong>
    </footer>

  </div>
  <!-- ./wrapper -->

  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>