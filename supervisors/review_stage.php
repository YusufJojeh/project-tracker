<?php
session_start();
require __DIR__ . '/../config/database.php';

if ( empty( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

$stage_id = ( int )$_GET[ 'stage_id' ];

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $ins = $pdo->prepare( 'INSERT INTO reviews (stage_id, supervisor_id, comment, grade) VALUES (?, ?, ?, ?)' );
    $ins->execute( [ $stage_id, $_SESSION[ 'user_id' ], $_POST[ 'comment' ], $_POST[ 'grade' ] ] );
    header( 'Location: dashboard.php' );
    exit;
}
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='UTF-8'>
  <title>Review Stage</title>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <link rel='stylesheet' href='../plugins/bootstrap/css/bootstrap.min.css' />
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css' />
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css' />
  <link rel='stylesheet' href='../dist/css/adminlte.min.css' />
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
  }

  .card {
    background: #3b3b4f;
    border: none;
    color: #f1f1f1;
  }

  .form-control {
    background-color: #44475a;
    color: #ffffff;
    border: 1px solid #666;
  }

  .card-header,
  label {
    color: #ffffff;
  }

  .btn,
  .form-control:focus {
    box-shadow: none;
  }

  .btn-success {
    background-color: #28a745;
    border-color: #28a745;
  }

  .btn-success:hover {
    background-color: #218838;
  }

  .main-footer {
    background: #2e2e3d;
    color: #f8f9fc;
  }
  </style>

</head>

<body class='hold-transition sidebar-mini layout-fixed'>
  <div class='wrapper'>
    <!-- Navbar -->
    <nav class='main-header navbar navbar-expand navbar-light' style='background:#2e2e3d;'>
      <ul class='navbar-nav'>
        <li class='nav-item'><a class='nav-link text-light' data-widget='pushmenu' href='#'><i
              class='fas fa-bars'></i></a></li>
      </ul>
      <ul class='navbar-nav ml-auto'>
        <li class='nav-item'><a class='nav-link text-light' href='../auth/logout.php'><i
              class='fas fa-sign-out-alt'></i> Logout</a></li>
      </ul>
    </nav>

    <!-- Sidebar -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <a href='#' class='brand-link text-center' style='background:#4e73df;'>
        <span class='brand-text font-weight-light'>MyApp</span>
      </a>
      <div class='sidebar'>
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column'>
            <li class='nav-item'>
              <a href='dashboard.php' class='nav-link'>
                <i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='profile.php' class='nav-link'>
                <i class='nav-icon fas fa-user'></i>
                <p>Profile</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='assigned_projects.php' class='nav-link'>
                <i class='nav-icon fas fa-tasks'></i>
                <p>Assigned Projects</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content -->
    <div class='content-wrapper p-4'>
      <div class='card'>
        <div class='card-header'>
          <h4><i class='fas fa-star'></i> Review Stage</h4>
        </div>
        <div class='card-body'>
          <form method='post'>
            <div class='mb-3'>
              <label for='comment'>Comments</label>
              <textarea name='comment' id='comment' class='form-control' rows='4'
                placeholder='Enter your feedback...'></textarea>
            </div>
            <div class='mb-3'>
              <label for='grade'>Grade</label>
              <input name='grade' id='grade' type='number' step='0.1' class='form-control' required
                placeholder='e.g., 8.5'>
            </div>
            <button class='btn btn-success'><i class='fas fa-save'></i> Submit Review</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class='main-footer text-center'>
      <strong>&copy;
        <?php echo date( 'Y' );
?> MyApp</strong>
    </footer>
  </div>

  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>