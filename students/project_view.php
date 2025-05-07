<?php
// students/project_view.php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require __DIR__ . '/../config/database.php';

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$studentId = $_SESSION['user_id'];
// load project
$stmt = $pdo->prepare("
    SELECT project_id, student_id, title, description, tools_used, created_at
    FROM projects
    WHERE project_id = ? AND student_id = ?
");
$stmt->execute([$projectId, $studentId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    die('Access denied or project not found.');
}

// decode tools_used
$tools = [];
if (!empty($project['tools_used'])) {
    $decoded = json_decode($project['tools_used'], true);
    if (json_last_error()===JSON_ERROR_NONE && is_array($decoded)) {
        $tools = $decoded;
    } else {
        foreach (explode(',', $project['tools_used']) as $t) {
            if (trim($t)!=='') $tools[] = trim($t);
        }
    }
}

// load stages
$stagesStmt = $pdo->prepare("
    SELECT stage_id, name, due_date, created_at
    FROM stages
    WHERE project_id = ?
    ORDER BY due_date ASC
");
$stagesStmt->execute([$projectId]);
$stages = $stagesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Project – MyApp</title>
  <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
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
    background: #f4f6f9;
    color: #333;
  }

  .card {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  }

  .card-header {
    background: #4e73df;
    color: #fff;
  }

  .badge-status-upcoming {
    background: #1cc88a;
  }

  .badge-status-overdue {
    background: #e74a3b;
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
      <a href="dashboard.php" class="brand-link text-center" style="background:#4e73df;"><span
          class="brand-text font-weight-light" style="color:#fff;">MyApp</span></a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="project_create.php" class="nav-link"><i
                  class="nav-icon fas fa-plus-circle"></i>
                <p>Create Project</p>
              </a></li>
            <li class="nav-item"><a href="stage_upload.php?project_id=<?php echo $projectId; ?>" class="nav-link"><i
                  class="nav-icon fas fa-upload"></i>
                <p>Upload to Stage</p>
              </a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>
    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
      <div class="container">
        <!-- Project Header -->
        <div class="card mb-4">
          <div class="card-header">
            <h2 class="mb-0"><?php echo htmlspecialchars($project['title']); ?></h2>
          </div>
          <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
            <?php if(count($tools)>0): ?>
            <h6>Tools Used:</h6>
            <?php foreach($tools as $t): ?>
            <span class="badge badge-info mr-1"><?php echo htmlspecialchars($t); ?></span>
            <?php endforeach;?>
            <?php endif;?>
          </div>
        </div>
        <!-- Stages -->
        <h4 class="mb-3">Project Stages</h4>
        <div class="row">
          <?php if(empty($stages)): ?>
          <div class="col-12">
            <p class="text-muted">No stages defined.</p>
          </div>
          <?php endif;?>
          <?php foreach($stages as $stage):
          // uploads
          $uStmt = $pdo->prepare("SELECT file_path, uploaded_at FROM uploads WHERE stage_id=?");
          $uStmt->execute([$stage['stage_id']]);
          $uploads = $uStmt->fetchAll(PDO::FETCH_ASSOC);
          // review
          $rStmt = $pdo->prepare("SELECT comment, grade, reviewed_at FROM reviews WHERE stage_id=?");
          $rStmt->execute([$stage['stage_id']]);
          $review = $rStmt->fetch(PDO::FETCH_ASSOC);
          $due = new DateTime($stage['due_date']);
          $now = new DateTime();
          $overdue = $due < $now;
        ?>
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?php echo htmlspecialchars($stage['name']); ?></strong>
                <span class="badge <?php echo $overdue?'badge-status-overdue':'badge-status-upcoming'; ?>">
                  <?php echo $overdue?'Overdue':'Upcoming'; ?>
                </span>
              </div>
              <div class="card-body">
                <p><strong>Due:</strong> <?php echo $due->format('Y-m-d'); ?></p>
                <h6>Files</h6>
                <?php if($uploads): ?>
                <ul class="list-group mb-3">
                  <?php foreach($uploads as $f): ?>
                  <li class="list-group-item">
                    <a href="../uploads/<?php echo rawurlencode($f['file_path']); ?>"
                      target="_blank"><?php echo basename($f['file_path']); ?></a>
                    <small
                      class="text-muted float-right"><?php echo (new DateTime($f['uploaded_at']))->format('Y-m-d H:i'); ?></small>
                  </li>
                  <?php endforeach;?>
                </ul>
                <?php else: ?>
                <p class="text-muted"><em>No files uploaded.</em></p>
                <?php endif;?>
                <a href="stage_upload.php?project_id=<?php echo $projectId; ?>"
                  class="btn btn-sm btn-outline-primary mb-3"><i class="fas fa-upload"></i> Upload File</a>
                <h6>Supervisor Review</h6>
                <?php if($review): ?>
                <p><span class="badge badge-secondary">Grade: <?php echo htmlspecialchars($review['grade']); ?></span>
                </p>
                <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                <p class="text-muted">Reviewed:
                  <?php echo (new DateTime($review['reviewed_at']))->format('Y-m-d H:i'); ?></p>
                <?php else: ?>
                <p class="text-warning"><em>Pending review</em></p>
                <?php endif;?>
              </div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
      </div>
    </div>
    <!-- Footer -->
    <footer class="main-footer text-center" style="background:#2e2e3d; color:#f8f9fc;">
      <strong>&copy; <?php echo date('Y'); ?> MyApp</strong>
    </footer>
  </div>
  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>