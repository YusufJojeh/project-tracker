<?php
// students/project_view.php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

// Accept either ?project_id= or ?id= for backwards compatibility
$projectId = 0;
if (isset($_GET['project_id'])) {
    $projectId = (int)$_GET['project_id'];
} elseif (isset($_GET['id'])) {
    $projectId = (int)$_GET['id'];
}

$studentId = $_SESSION['user_id'];

// 1) Load the project, ensure it belongs to this student
$stmt = $pdo->prepare("
  SELECT
    p.*,
    stu.username    AS student_name,
    sup.username    AS supervisor_name
  FROM projects p
    JOIN users stu ON p.student_id    = stu.user_id
    JOIN users sup ON p.supervisor_id = sup.user_id
  WHERE p.project_id = ? AND p.student_id = ?
");
$stmt->execute([$projectId, $studentId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    die('Access denied or project not found.');
}

// 2) Load its stages
$stagesStmt = $pdo->prepare("
  SELECT *
  FROM stages
  WHERE project_id = ?
  ORDER BY due_date ASC
");
$stagesStmt->execute([$projectId]);
$stages = $stagesStmt->fetchAll(PDO::FETCH_ASSOC);

// 3) Prepare attachments query
$attachStmt = $pdo->prepare("
  SELECT *
  FROM attachments
  WHERE stage_id = ?
  ORDER BY created_at ASC
");

// 4) Unread notifications count
$unreadCount = get_unread_notifications_count($pdo, $studentId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>View Project – <?php echo htmlspecialchars($project['title']); ?></title>
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
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
        <!-- Notifications dropdown -->
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
        <li class="nav-item">
          <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="dashboard.php" class="brand-link text-center" style="background:#4e73df;">
        <span class="brand-text font-weight-light text-white">Project Tracker</span>
      </a>
      <div class="sidebar">
        <!-- User panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="info">
            <a href="profile.php" class="d-block text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
          </div>
        </div>
        <!-- Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item">
              <a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="projects.php" class="nav-link"><i class="nav-icon fas fa-project-diagram"></i>
                <p>My Projects</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="create_project.php" class="nav-link"><i class="nav-icon fas fa-plus-circle"></i>
                <p>Create Project</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="upload_stage.php?project_id=<?php echo $projectId; ?>" class="nav-link">
                <i class="nav-icon fas fa-upload"></i>
                <p>Upload to Stage</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="notifications.php" class="nav-link">
                <i class="nav-icon far fa-bell"></i>
                <p>Notifications</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="profile.php" class="nav-link"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="../auth/logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i>
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
        <!-- Project Details -->
        <div class="card mb-4">
          <div class="card-header bg-primary">
            <h3 class="card-title text-white"><?php echo htmlspecialchars($project['title']); ?></h3>
          </div>
          <div class="card-body">
            <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
            <p><strong>Status:</strong>
              <span class="badge badge-<?php echo match($project['status']) {
              'completed'   => 'success',
              'in_progress' => 'primary',
              'pending'     => 'warning',
              default       => 'danger',
            }; ?>"><?php echo ucfirst($project['status']); ?></span>
            </p>
            <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($project['supervisor_name']); ?></p>
            <p><strong>Created:</strong> <?php echo date('Y-m-d H:i',strtotime($project['created_at'])); ?></p>
            <?php if ($project['updated_at']): ?>
            <p><strong>Updated:</strong> <?php echo date('Y-m-d H:i',strtotime($project['updated_at'])); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Stages -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Project Stages</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <?php foreach ($stages as $stage):
              $attachStmt->execute([$stage['stage_id']]);
              $attachments = $attachStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header d-flex justify-content-between">
                    <span><?php echo htmlspecialchars($stage['title']); ?></span>
                    <span class="badge badge-<?php echo match($stage['status']) {
                      'submitted' => 'info',
                      'approved'  => 'success',
                      'rejected'  => 'danger',
                      default     => 'secondary',
                    }; ?>"><?php echo ucfirst($stage['status']); ?></span>
                  </div>
                  <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($stage['description'])); ?></p>
                    <p><strong>Due:</strong> <?php echo htmlspecialchars($stage['due_date']); ?></p>
                    <?php if ($stage['grade'] !== null): ?>
                    <p><strong>Grade:</strong> <?php echo number_format($stage['grade'],2); ?></p>
                    <?php endif; ?>
                    <?php if ($stage['feedback']): ?>
                    <p><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($stage['feedback'])); ?></p>
                    <?php endif; ?>

                    <!-- Attachments -->
                    <?php if ($attachments): ?>
                    <h6>Attachments</h6>
                    <ul>
                      <?php foreach ($attachments as $file): ?>
                      <li>
                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank">
                          <?php echo htmlspecialchars($file['file_name']); ?>
                        </a>
                      </li>
                      <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <a href="stage_view.php?stage_id=<?php echo $stage['stage_id']; ?>" class="btn btn-sm btn-primary">
                      <i class="fas fa-eye"></i> View Stage
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>

              <?php if (empty($stages)): ?>
              <div class="col-12">
                <p class="text-muted">No stages for this project.</p>
              </div>
              <?php endif; ?>
            </div>
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