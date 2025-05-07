<?php
// students/stage_upload.php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require __DIR__ . '/../config/database.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($project_id <= 0) {
    die('Invalid project ID.');
}

// fetch stages
$stmt = $pdo->prepare('SELECT stage_id, name FROM stages WHERE project_id = ?');
$stmt->execute([$project_id]);
$stages = $stmt->fetchAll();

// handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stage_id = (int)$_POST['stage_id'];
    $file      = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = time() . '_' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
        $ins = $pdo->prepare('INSERT INTO uploads (stage_id, file_path, uploaded_at) VALUES (?, ?, NOW())');
        $ins->execute([$stage_id, $filename]);
        header('Location: project_view.php?project_id=' . $project_id);
        exit;
    } else {
        $error = 'Upload failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Upload Stage File – MyApp</title>
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

  .btn-primary {
    background: #4e73df;
    border-color: #4e73df;
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
      <a href="#" class="brand-link text-center" style="background:#4e73df;"><span class="brand-text font-weight-light"
          style="color:#fff;">MyApp</span></a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="project_view.php?project_id=<?php echo $project_id; ?>" class="nav-link"><i
                  class="nav-icon fas fa-folder-open"></i>
                <p>Back to Project</p>
              </a></li>
            <li class="nav-item"><a href="project_create.php" class="nav-link"><i
                  class="nav-icon fas fa-plus-circle"></i>
                <p>New Project</p>
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
        <div class="card">
          <div class="card-header"><i class="fas fa-upload"></i> Upload File for Stage</div>
          <div class="card-body">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
              <div class="form-group">
                <label for="stage_id">Select Stage</label>
                <select id="stage_id" name="stage_id" class="form-control" required>
                  <option value="" disabled selected>-- Choose Stage --</option>
                  <?php foreach ($stages as $s): ?>
                  <option value="<?php echo $s['stage_id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="file">Choose File</label>
                <input type="file" id="file" name="file" class="form-control" required />
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
              <a href="project_view.php?project_id=<?php echo $project_id; ?>" class="btn btn-secondary"><i
                  class="fas fa-arrow-left"></i> Cancel</a>
            </form>
          </div>
        </div>
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