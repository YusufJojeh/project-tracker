<?php
// students/dashboard.php
session_start();
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'student' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

// Fetch projects
$stmt = $pdo->prepare( "
    SELECT
        p.*,
        u.username AS supervisor_name,
        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id) AS total_stages,
        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id AND status = 'approved') AS completed_stages
    FROM projects p
    JOIN users u ON p.supervisor_id = u.user_id
    WHERE p.student_id = ?
    ORDER BY p.updated_at DESC
" );
$stmt->execute( [ $_SESSION[ 'user_id' ] ] );
$projects = $stmt->fetchAll( PDO::FETCH_ASSOC );

// Fetch notifications
$notifications = get_recent_notifications( $pdo, $_SESSION[ 'user_id' ], 5 );
$unreadCount   = get_unread_notifications_count( $pdo, $_SESSION[ 'user_id' ] );
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Student Dashboard | Project Tracker</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel='stylesheet'
    href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
  <!-- Font Awesome -->
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css'>
  <!-- overlayScrollbars -->
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
  <!-- Theme style -->
  <link rel='stylesheet' href='../dist/css/adminlte.min.css'>
</head>

<body class='hold-transition sidebar-mini layout-fixed'>
  <div class='wrapper'>
    <!-- Navbar -->
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <!-- Left navbar: toggle sidebar -->
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#' role='button'>
            <i class='fas fa-bars'></i>
          </a>
        </li>
      </ul>
      <!-- Right navbar: notifications -->
      <ul class='navbar-nav ml-auto'>
        <!-- Notifications dropdown -->
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
            <?php if ( empty( $notifications ) ): ?>
            <a href='#' class='dropdown-item'>No notifications</a>
            <?php else: ?>
            <?php foreach ( $notifications as $note ):
$ago = time_elapsed_string( $note[ 'created_at' ] );
?>
            <a href='#' class='dropdown-item'>
              <i class='fas fa-envelope mr-2'></i> <?php echo h( $note[ 'title' ] );
?>
              <span class='float-right text-muted text-sm'><?php echo $ago;
?></span>
            </a>
            <div class='dropdown-divider'></div>
            <?php endforeach;
?>
            <a href='notifications.php' class='dropdown-item dropdown-footer'>See All Notifications</a>
            <?php endif;
?>
          </div>
        </li>
        <!-- Fullscreen & Control Sidebar omitted for brevity -->
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <!-- Main Sidebar Container -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <!-- Brand Logo -->
      <a href='#' class='brand-link'>
        <img src='../dist/img/AdminLTELogo.png' alt='Logo' class='brand-image img-circle elevation-3'
          style='opacity: .8'>
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

        <!-- SidebarSearch Form -->
        <div class='form-inline'>
          <div class='input-group' data-widget='sidebar-search'>
            <input class='form-control form-control-sidebar' type='search' placeholder='Search' aria-label='Search'>
            <div class='input-group-append'>
              <button class='btn btn-sidebar'><i class='fas fa-search fa-fw'></i></button>
            </div>
          </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column' data-widget='treeview' role='menu' data-accordion='false'>

            <!-- Dashboard -->
            <li class='nav-item'>
              <a href='dashboard.php' class="nav-link <?php echo $current_page==='dashboard'?'active':'';?>">
                <i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a>
            </li>

            <!-- My Projects -->
            <li class='nav-item'>
              <a href='projects.php' class="nav-link <?php echo $current_page==='projects'?'active':'';?>">
                <i class='nav-icon fas fa-project-diagram'></i>
                <p>My Projects</p>
              </a>
            </li>

            <!-- New Project -->
            <li class='nav-item'>
              <a href='project_create.php' class="nav-link <?php echo $current_page==='new_project'?'active':'';?>">
                <i class='nav-icon fas fa-plus-circle'></i>
                <p>Create New Project</p>
              </a>
            </li>

            <!-- Upload Stage -->
            <li class='nav-item'>
              <a href='stage_upload.php' class="nav-link <?php echo $current_page==='upload_stage'?'active':'';?>">
                <i class='nav-icon fas fa-upload'></i>
                <p>Upload Stage</p>
              </a>
            </li>

            <!-- Notifications -->
            <li class='nav-item'>
              <a href='notifications.php' class="nav-link <?php echo $current_page==='notifications'?'active':'';?>">
                <i class='nav-icon far fa-bell'></i>
                <p>Notifications
                  <?php if ( $unreadCount ): ?>
                  <span class='badge badge-warning right'><?php echo $unreadCount;
?></span>
                  <?php endif;
?>
                </p>
              </a>
            </li>

            <!-- Profile -->
            <li class='nav-item'>
              <a href='profile.php' class="nav-link <?php echo $current_page==='profile'?'active':'';?>">
                <i class='nav-icon fas fa-user'></i>
                <p>Profile</p>
              </a>
            </li>

            <!-- Logout -->
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

    <!-- Content Wrapper. Contains page content -->
    <div class='content-wrapper'>
      <!-- Page header -->
      <section class='content-header'>
        <div class='container-fluid'>
          <h1>Student Dashboard</h1>
        </div>
      </section>

      <!-- Main content -->
      <section class='content'>

        <!-- Stats boxes -->
        <div class='row'>
          <div class='col-lg-3 col-6'>
            <div class='small-box bg-info'>
              <div class='inner'>
                <h3><?php echo count( $projects );
?></h3>
                <p>My Projects</p>
              </div>
              <div class='icon'><i class='fas fa-project-diagram'></i></div>
              <a href='project_create.php' class='small-box-footer'>
                New Project <i class='fas fa-plus-circle'></i>
              </a>
            </div>
          </div>
          <div class='col-lg-3 col-6'>
            <div class='small-box bg-warning'>
              <div class='inner'>
                <h3><?php echo $unreadCount;
?></h3>
                <p>Unread Notices</p>
              </div>
              <div class='icon'><i class='far fa-bell'></i></div>
              <a href='notifications.php' class='small-box-footer'>
                View All <i class='fas fa-arrow-circle-right'></i>
              </a>
            </div>
          </div>
        </div>
        <!-- /.row -->

        <div class='row'>
          <!-- Notifications card -->
          <section class='col-lg-4 connectedSortable'>
            <div class='card'>
              <div class='card-header'>
                <h3 class='card-title'><i class='far fa-bell'></i> Recent Notifications</h3>
              </div>
              <div class='card-body p-0'>
                <?php if ( empty( $notifications ) ): ?>
                <p class='p-3'>No new notifications.</p>
                <?php else: ?>
                <ul class='products-list product-list-in-card pl-2 pr-2'>
                  <?php foreach ( $notifications as $note ): ?>
                  <li class='item'>
                    <div class='product-img'><i class='far fa-envelope fa-2x'></i></div>
                    <div class='product-info'>
                      <a href='#' class='product-title'><?php echo h( $note[ 'title' ] );
?>
                        <span class='badge badge-info float-right'><?php echo time_elapsed_string( $note[ 'created_at' ] );
?></span>
                      </a>
                      <span class='product-description'><?php echo h( $note[ 'message' ] );
?></span>
                    </div>
                  </li>
                  <?php endforeach;
?>
                </ul>
                <?php endif;
?>
              </div>
              <div class='card-footer text-center'><a href='notifications.php'>See All Notifications</a></div>
            </div>
          </section>

          <!-- Projects table -->
          <section class='col-lg-8 connectedSortable'>
            <div class='card'>
              <div class='card-header border-0'>
                <h3 class='card-title'><i class='fas fa-project-diagram'></i> My Projects</h3>
              </div>
              <div class='card-body table-responsive p-0'>
                <table class='table table-striped table-valign-middle'>
                  <thead>
                    <tr>
                      <th>Title</th>
                      <th>Supervisor</th>
                      <th>Status</th>
                      <th>Progress</th>
                      <th>Updated</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ( $projects as $proj ):
$progress = $proj[ 'total_stages' ]
? ( $proj[ 'completed_stages' ]/$proj[ 'total_stages' ] )*100 : 0;
$badge   = match( $proj[ 'status' ] ) {
    'completed'=>'success',
    'in_progress'=>'primary',
    'pending'=>'warning',
    default=>'danger'
}
;
?>
                    <tr>
                      <td><?php echo h( $proj[ 'title' ] );
?></td>
                      <td><?php echo h( $proj[ 'supervisor_name' ] );
?></td>
                      <td><span class="badge badge-<?php echo $badge;?>">
                          <?php echo ucfirst( $proj[ 'status' ] );
?></span>
                      </td>
                      <td style='width:150px'>
                        <div class='progress progress-xs'>
                          <div class="progress-bar bg-<?php echo $badge;?>" role='progressbar'
                            style="width: <?php echo $progress;?>%;"></div>
                        </div>
                        <small><?php echo round( $progress );
?>%</small>
                      </td>
                      <td><?php echo date( 'Y-m-d H:i', strtotime( $proj[ 'updated_at' ] ) );
?></td>
                      <td>
                        <a href="project_view.php?id=<?php echo $proj['project_id'];?>" class='text-primary mr-2'>
                          <i class='fas fa-eye'></i>
                        </a>
                        <a href="project_edit.php?id=<?php echo $proj['project_id'];?>" class='text-warning mr-2'>
                          <i class='fas fa-edit'></i>
                        </a>
                        <a href="stage_upload.php?project_id=<?php echo $proj['project_id'];?>" class='text-success'>
                          <i class='fas fa-plus-circle'></i>
                        </a>
                      </td>
                    </tr>
                    <?php endforeach;
?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
        <!-- /.row -->

      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class='main-footer'>
      <div class='float-right d-none d-sm-inline'>Version 1.0.0</div>
      <strong>&copy;
        <?php echo date( 'Y' );
?> Project Tracker.</strong> All rights reserved.
    </footer>
  </div>
  <!-- ./wrapper -->

  <!-- REQUIRED SCRIPTS -->
  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>
