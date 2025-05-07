<?php
session_start();

// Check if the user is not logged in and redirect them to the login page
if ( empty( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require __DIR__ . '/../config/database.php';

$studentId = $_SESSION[ 'user_id' ];

// Retrieve user details from the 'users' table
$stmt = $pdo->prepare( 'SELECT full_name AS name, username AS email, created_at, role FROM users WHERE user_id = ?' );
$stmt->execute( [ $studentId ] );
$student = $stmt->fetch( PDO::FETCH_ASSOC );

// Check if the user exists and if the user is a student
if ( !$student || $student[ 'role' ] !== 'student' ) {
    die( 'Access denied.' );
}

// Retrieve the student's projects
$projectStmt = $pdo->prepare('SELECT project_id, title, created_at FROM projects WHERE student_id = ? ORDER BY created_at DESC');
$projectStmt->execute([$studentId]);
$projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profile – MyApp</title>
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
            <li class="nav-item"><a href="project_create.php" class="nav-link"><i
                  class="nav-icon fas fa-plus-circle"></i>
                <p>Create Project</p>
              </a></li>
            <li class="nav-item"><a href="stage_upload.php" class="nav-link"><i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link active"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
      <div class="container">
        <!-- Profile Card -->
        <div class="card mb-4">
          <div class="card-header"><i class="fas fa-user"></i> My Profile</div>
          <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Registered on:</strong> <?php echo (new DateTime($student['created_at']))->format('Y-m-d'); ?>
            </p>
          </div>
        </div>

        <!-- Projects Card -->
        <div class="card">
          <div class="card-header"><i class="fas fa-project-diagram"></i> My Projects</div>
          <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted">You haven't added any projects yet.</p>
<?php else: ?>
<ul class = 'list-group'>
<?php foreach ( $projects as $project ): ?>
<li class = 'list-group-item d-flex justify-content-between align-items-center'>
<a href = "project_view.php?project_id=<?php echo $project['project_id']; ?>">
<?php echo htmlspecialchars( $project[ 'title' ] );
?>
</a>
<span class = 'text-muted'><?php echo ( new DateTime( $project[ 'created_at' ] ) )->format( 'Y-m-d' );
?></span>
</li>
<?php endforeach;
?>
</ul>
<?php endif;
?>
</div>
</div>
</div>
</div>

<!-- Footer -->
<footer class = 'main-footer text-center'>
<strong>&copy;
<?php echo date( 'Y' );
?> MyApp</strong>
</footer>
</div>

<script src = '../plugins/jquery/jquery.min.js'></script>
<script src = '../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
<script src = '../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
<script src = '../dist/js/adminlte.min.js'></script>
</body>

</html>