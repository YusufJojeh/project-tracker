<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$studentId = $_SESSION['user_id'];

// اجلب كل المشاريع لهذا الطالب
$stmt = $pdo->prepare("SELECT * FROM projects WHERE student_id = ?");
$stmt->execute([$studentId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تحليل البيانات
$totalProjects = count($projects);
$latestProject = null;
$earliestDate = null;
$latestDate = null;
$toolsFrequency = [];

if ($totalProjects > 0) {
    foreach ($projects as $proj) {
        $created = new DateTime($proj['created_at']);
        if (!$earliestDate || $created < $earliestDate) $earliestDate = $created;
        if (!$latestDate || $created > $latestDate) $latestDate = $created;
        if (!$latestProject || $created > new DateTime($latestProject['created_at'])) {
            $latestProject = $proj;
        }

        // احسب تكرار الأدوات
        $tools = explode(',', $proj['tools_used']);
        foreach ($tools as $tool) {
            $tool = strtolower(trim($tool));
            if (!empty($tool)) {
                $toolsFrequency[$tool] = ($toolsFrequency[$tool] ?? 0) + 1;
            }
        }
    }

    arsort($toolsFrequency); // رتب من الأكثر استخدامًا
    $mostUsedTool = array_key_first($toolsFrequency);
} else {
    $mostUsedTool = 'N/A';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title>Analysis – MyApp</title>
  <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css" />
  <link rel="stylesheet" href="../dist/css/adminlte.min.css" />
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
      <a href="#" class="brand-link text-center" style="background:#4e73df;"><span class="brand-text font-weight-light"
          style="color:#fff;">MyApp</span></a>
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
            <li class="nav-item"><a href="stage_upload.php" class="nav-link"><i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a></li>
            <li class="nav-item"><a href="analysis.php" class="nav-link active"><i
                  class="nav-icon fas fa-chart-line"></i>
                <p>Analysis</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper p-4">
      <div class="container">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white"><i class="fas fa-chart-pie"></i> Project Analysis</div>
          <div class="card-body">
            <p><strong>Total Projects:</strong> <?= $totalProjects ?></p>
            <p><strong>Most Used Tool:</strong> <?= htmlspecialchars($mostUsedTool) ?></p>
            <p><strong>Earliest Project:</strong> <?= $earliestDate ? $earliestDate->format('Y-m-d') : 'N/A' ?></p>
            <p><strong>Latest Project:</strong> <?= $latestDate ? $latestDate->format('Y-m-d') : 'N/A' ?></p>
            <?php if ($latestProject): ?>
            <hr>
            <h5>Latest Project Details</h5>
            <p><strong>Title:</strong> <?= htmlspecialchars($latestProject['title']) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($latestProject['description']) ?></p>
            <p><strong>Tools:</strong> <?= htmlspecialchars($latestProject['tools_used']) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($totalProjects > 0): ?>
        <div class="card">
          <div class="card-header bg-info text-white"><i class="fas fa-list"></i> Recent Projects</div>
          <div class="card-body">
            <ul class="list-group">
              <?php foreach ($projects as $project): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($project['title']) ?>
                <span
                  class="badge badge-secondary"><?= (new DateTime($project['created_at']))->format('Y-m-d') ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <footer class="main-footer text-center" style="background:#2e2e3d; color:#f8f9fc;">
      <strong>&copy; <?= date('Y') ?> MyApp</strong>
    </footer>
  </div>

  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>