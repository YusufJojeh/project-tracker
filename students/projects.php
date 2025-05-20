<?php
// students/projects.php
session_start();
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'student' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

// Fetch all projects for this student
$stmt = $pdo->prepare( "
    SELECT
        p.*,
        u.username AS supervisor_name
    FROM projects p
    JOIN users u ON p.supervisor_id = u.user_id
    WHERE p.student_id = ?
    ORDER BY p.created_at DESC
" );
$stmt->execute( [ $_SESSION[ 'user_id' ] ] );
$projects = $stmt->fetchAll( PDO::FETCH_ASSOC );

// Notifications count for sidebar badge
$unreadCount = get_unread_notifications_count( $pdo, $_SESSION[ 'user_id' ] );

?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>My Projects | Project Tracker</title>
  <!-- Google Font -->
  <link rel='stylesheet'
    href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
  <!-- Font Awesome -->
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css'>
  <!-- overlayScrollbars -->
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
  <!-- AdminLTE -->
  <link rel='stylesheet' href='../dist/css/adminlte.min.css'>
</head>

<body class='hold-transition sidebar-mini layout-fixed'>
  <div class='wrapper'>

    <!-- Navbar -->
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <!-- Sidebar toggle-->
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#'><i class='fas fa-bars'></i></a>
        </li>
      </ul>
      <!-- Right navbar: notifications -->
      <ul class='navbar-nav ml-auto'>
        <li class='nav-item dropdown'>
          <a class='nav-link' data-toggle='dropdown' href='#'>
            <i class='far fa-bell'></i>
            <?php if ( $unreadCount ): ?>
            <span class='badge badge-warning navbar-badge'><?php echo $unreadCount;
?></span>
            <?php endif;
?>
          </a>
          <div class='dropdown-menu dropdown-menu-lg dropdown-menu-right'>
            <span class='dropdown-header'><?php echo $unreadCount;
?> Notifications</span>
            <div class='dropdown-divider'></div>
            <a href='notifications.php' class='dropdown-item dropdown-footer'>See All Notifications</a>
          </div>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <!-- Brand -->
      <a href='#' class='brand-link'>
        <img src='../dist/img/AdminLTELogo.png' alt='Logo' class='brand-image img-circle elevation-3'>
        <span class='brand-text font-weight-light'>Project Tracker</span>
      </a>

      <!-- Sidebar -->
      <div class='sidebar'>
        <!-- User panel -->
        <div class='user-panel mt-3 pb-3 mb-3 d-flex'>
          <div class='image'>
            <img src='../dist/img/user2-160x160.jpg' class='img-circle elevation-2' alt='User'>
          </div>
          <div class='info'>
            <a href='profile.php' class='d-block'><?php echo h( $_SESSION[ 'username' ] );
?></a>
          </div>
        </div>

        <!-- Sidebar menu -->
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column' data-widget='treeview'>
            <li class='nav-item'>
              <a href='dashboard.php' class='nav-link'>
                <i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='projects.php' class='nav-link active'>
                <i class='nav-icon fas fa-project-diagram'></i>
                <p>My Projects</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='project_create.php' class='nav-link'>
                <i class='nav-icon fas fa-plus-circle'></i>
                <p>Create New Project</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='stage_upload.php' class='nav-link'>
                <i class='nav-icon fas fa-upload'></i>
                <p>Upload Stage</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='notifications.php' class='nav-link'>
                <i class='nav-icon far fa-bell'></i>
                <p>Notifications</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='profile.php' class='nav-link'>
                <i class='nav-icon fas fa-user'></i>
                <p>Profile</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='../auth/logout.php' class='nav-link'>
                <i class='nav-icon fas fa-sign-out-alt'></i>
                <p>Logout</p>
              </a>
            </li>
          </ul>
        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper -->
    <div class='content-wrapper'>
      <!-- Page header -->
      <section class='content-header'>
        <div class='container-fluid'>
          <h1>My Projects</h1>
        </div>
      </section>

      <!-- Main content -->
      <section class='content'>
        <div class='container-fluid'>
          <!-- Projects table -->
          <div class='card'>
            <div class='card-header'>
              <h3 class='card-title'>Projects List</h3>
            </div>
            <div class='card-body table-responsive p-0'>
              <table class='table table-hover text-nowrap'>
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Supervisor</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ( $projects as $proj ):
$badge = match( $proj[ 'status' ] ) {
    'completed'   => 'success',
    'in_progress' => 'primary',
    'pending'     => 'warning',
    default       => 'danger',
}
;
?>
                  <tr>
                    <td><?php echo h( $proj[ 'title' ] );
?></td>
                    <td><?php echo h( $proj[ 'supervisor_name' ] );
?></td>
                    <td><span class="badge badge-<?php echo $badge; ?>"><?php echo ucfirst( $proj[ 'status' ] );
?></span>
                    </td>
                    <td><?php echo date( 'Y-m-d', strtotime( $proj[ 'created_at' ] ) );
?></td>
                    <td><?php echo date( 'Y-m-d', strtotime( $proj[ 'updated_at' ] ) );
?></td>
                    <td>
                      <a href="project_view.php?id=<?php echo $proj['project_id']; ?>" class='btn btn-info btn-sm'>
                        <i class='fas fa-eye'></i>
                      </a>
                      <a href="project_edit.php?id=<?php echo $proj['project_id']; ?>" class='btn btn-warning btn-sm'>
                        <i class='fas fa-edit'></i>
                      </a>
                      <a href="stage_upload.php?project_id=<?php echo $proj['project_id']; ?>"
                        class='btn btn-success btn-sm'>
                        <i class='fas fa-upload'></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach;
?>
                  <?php if ( empty( $projects ) ): ?>
                  <tr>
                    <td colspan='6' class='text-center'>No projects found.</td>
                  </tr>
                  <?php endif;
?>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <footer class='main-footer'>
      <div class='float-right d-none d-sm-inline'>Version 1.0</div>
      <strong>&copy;
        <?php echo date( 'Y' );
?> Project Tracker.</strong> All rights reserved.
    </footer>
  </div>
  <!-- ./wrapper -->

  <!-- Scripts -->
  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>