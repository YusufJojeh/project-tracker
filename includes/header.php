<?php
// includes/header.php

// 1) Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 2) Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// 3) Bootstrap your app
require_once __DIR__ . '/../config/database.php';     // <-- loads $pdo
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notifications.php';

// 4) Get current user info
$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'] ?? '';

// Fetch username
$stmt = $pdo->prepare('SELECT username FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$name = $stmt->fetchColumn() ?: 'User';

// 5) Get unread count and last 5 notifications
$unreadCount   = get_unread_notifications_count($pdo, $userId);
$stmt          = $pdo->prepare(
    'SELECT notification_id, title, message, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 5'
);
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title ?? 'Project Tracker', ENT_QUOTES) ?></title>
  <link rel="stylesheet" href="/plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="/dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-light" style="background:#2e2e3d;">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link text-light" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
      </ul>

      <ul class="navbar-nav ml-auto">
        <!-- Notifications Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link text-light" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
            <span class="badge badge-danger navbar-badge"><?php echo $unreadCount ?></span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-header"><?php echo $unreadCount ?> New Notifications</span>
            <div class="dropdown-divider"></div>

            <?php if (empty($notifications)): ?>
            <a href="#" class="dropdown-item text-center">No notifications</a>
            <?php else: ?>
            <?php foreach ($notifications as $n): ?>
            <a href="#" class="dropdown-item <?php echo $n['is_read'] ? '' : 'font-weight-bold' ?>">
              <div class="small text-muted"><?php echo htmlspecialchars($n['created_at'], ENT_QUOTES) ?></div>
              <div><?php echo htmlspecialchars($n['title'], ENT_QUOTES) ?></div>
              <div class="text-wrap"><?php echo htmlspecialchars($n['message'], ENT_QUOTES) ?></div>
            </a>
            <div class="dropdown-divider"></div>
            <?php endforeach; ?>
            <?php endif; ?>

            <a href="/notifications.php" class="dropdown-item dropdown-footer">View All Notifications</a>
          </div>
        </li>

        <!-- User Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link text-light" data-toggle="dropdown" href="#">
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($name, ENT_QUOTES) ?>
          </a>
          <div class="dropdown-menu dropdown-menu-right">
            <a href="/profile.php" class="dropdown-item">
              <i class="fas fa-user mr-2"></i> Profile
            </a>
            <div class="dropdown-divider"></div>
            <a href="/auth/logout.php" class="dropdown-item">
              <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
          </div>
        </li>
      </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="#" class="brand-link text-center" style="background:#4e73df;">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <?php if ($role === 'student'): ?>
            <li class="nav-item">
              <a href="/students/dashboard.php"
                class="nav-link <?php echo ($current_page ?? '')==='dashboard'?'active':'' ?>">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/students/projects.php"
                class="nav-link <?php echo ($current_page ?? '')==='projects'?'active':'' ?>">
                <i class="nav-icon fas fa-project-diagram"></i>
                <p>My Projects</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/students/project_create.php"
                class="nav-link <?php echo ($current_page ?? '')==='new_project'?'active':'' ?>">
                <i class="nav-icon fas fa-plus-circle"></i>
                <p>New Project</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/students/stage_upload.php"
                class="nav-link <?php echo ($current_page ?? '')==='upload'?'active':'' ?>">
                <i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a>
            </li>
            <?php elseif ($role === 'supervisor'): ?>
            <li class="nav-item">
              <a href="/supervisors/dashboard.php"
                class="nav-link <?php echo ($current_page ?? '')==='dashboard'?'active':'' ?>">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/supervisors/projects.php"
                class="nav-link <?php echo ($current_page ?? '')==='projects'?'active':'' ?>">
                <i class="nav-icon fas fa-project-diagram"></i>
                <p>Projects</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="/supervisors/reviews.php"
                class="nav-link <?php echo ($current_page ?? '')==='reviews'?'active':'' ?>">
                <i class="nav-icon fas fa-tasks"></i>
                <p>Reviews</p>
              </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="/profile.php" class="nav-link <?php echo ($current_page ?? '')==='profile'?'active':'' ?>">
                <i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>
  </div>

  <script src="/plugins/jquery/jquery.min.js"></script>
  <script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="/dist/js/adminlte.min.js"></script>
</body>

</html>