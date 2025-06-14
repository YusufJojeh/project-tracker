<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

$project_id = (int)($_GET['project_id'] ?? $_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id=? AND student_id=?");
$stmt->execute([$project_id, $_SESSION['user_id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) die('Not allowed');

$unreadCount = get_unread_notifications_count($pdo, $_SESSION['user_id']);

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tools_used = trim($_POST['tools_used'] ?? '');

    if ($title === '') {
        $error = 'Please enter a project title';
    } else {
        $upd = $pdo->prepare("UPDATE projects SET title=?, description=?, tools_used=?, updated_at=NOW() WHERE project_id=?");
        $upd->execute([$title, $description, $tools_used, $project_id]);
        $success = 'Changes saved successfully!';
        // Reload updated data
        $stmt->execute([$project_id, $_SESSION['user_id']]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        // Optionally redirect:
        // header('Location: project_view.php?id=' . $project_id); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Project | Project Tracker</title>
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <style>
  body,
  html {
    font-family: 'Source Sans Pro', Arial, sans-serif;
  }

  label {
    font-weight: bold;
  }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
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
            <a href="notifications.php" class="dropdown-item dropdown-footer">View All Notifications</a>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i></a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="dashboard.php" class="brand-link text-center">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="projects.php" class="nav-link"><i class="nav-icon fas fa-project-diagram"></i>
                <p>My Projects</p>
              </a></li>
            <li class="nav-item"><a href="project_create.php" class="nav-link"><i
                  class="nav-icon fas fa-plus-circle"></i>
                <p>Create Project</p>
              </a></li>
            <li class="nav-item"><a href="stage_upload.php" class="nav-link"><i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a></li>
            <li class="nav-item"><a href="notifications.php" class="nav-link"><i class="nav-icon far fa-bell"></i>
                <p>Notifications</p>
              </a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a></li>
            <li class="nav-item"><a href="../auth/logout.php" class="nav-link"><i
                  class="nav-icon fas fa-sign-out-alt"></i>
                <p>Logout</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <div class="content-wrapper p-4">
      <div class="container-fluid">
        <div class="card card-primary mx-auto" style="max-width:650px;">
          <div class="card-header">
            <h4 class="card-title mb-0"><i class="fas fa-edit"></i> Edit Project</h4>
          </div>
          <div class="card-body">

            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
              <div class="form-group">
                <label>Title<span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                  value="<?php echo htmlspecialchars($project['title']); ?>" required>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"
                  rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
              </div>
              <div class="form-group">
                <label>Tools Used</label>
                <input type="text" name="tools_used" class="form-control"
                  value="<?php echo htmlspecialchars($project['tools_used']); ?>">
              </div>
              <button class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
              <a href="project_view.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">Back to Project</a>
            </form>
          </div>
        </div>
      </div>
    </div>

    <footer class="main-footer text-center">
      <strong>&copy; <?php echo date('Y'); ?> Project Tracker</strong>
    </footer>
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>