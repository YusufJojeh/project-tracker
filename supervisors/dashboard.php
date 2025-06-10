<?php
// supervisors/dashboard.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

// Ensure logged in and is a supervisor
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}
$supervisorId = $_SESSION[ 'user_id' ];

// 1 ) Stats: total projects, pending reviews, approved stages
$statsStmt = $pdo->prepare( "
    SELECT
      COUNT(DISTINCT p.project_id) AS total_projects,
      SUM(CASE WHEN s.status = 'submitted' THEN 1 ELSE 0 END) AS pending_reviews,
      SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) AS approved_stages
    FROM projects p
    LEFT JOIN stages s ON p.project_id = s.project_id
    WHERE p.supervisor_id = ?
" );
$statsStmt->execute( [ $supervisorId ] );
$stats = $statsStmt->fetch( PDO::FETCH_ASSOC );

// 2 ) Pending reviews ( latest 5 )
$pendingStmt = $pdo->prepare( "
    SELECT
      s.stage_id,
      s.title          AS stage_title,
      s.updated_at     AS submission_date,
      p.project_id,
      p.title          AS project_title,
      u.username       AS student_name
    FROM stages s
    JOIN projects p ON s.project_id = p.project_id
    JOIN users u ON p.student_id = u.user_id
    WHERE p.supervisor_id = ? AND s.status = 'submitted'
    ORDER BY s.updated_at DESC
    LIMIT 5
" );
$pendingStmt->execute( [ $supervisorId ] );
$pending_reviews = $pendingStmt->fetchAll( PDO::FETCH_ASSOC );

// 3 ) Active projects with progress
$projectsStmt = $pdo->prepare( "
    SELECT
      p.project_id,
      p.title,
      p.status,
      u.username AS student_name,
      SUM(CASE WHEN s.status = 'submitted' THEN 1 ELSE 0 END)   AS pending_count,
      SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END)    AS approved_count
    FROM projects p
    LEFT JOIN stages s ON p.project_id = s.project_id
    LEFT JOIN users u ON p.student_id = u.user_id
    WHERE p.supervisor_id = ?
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
" );
$projectsStmt->execute( [ $supervisorId ] );
$projects = $projectsStmt->fetchAll( PDO::FETCH_ASSOC );

// 4 ) Recent notifications ( latest 5 )
$notifStmt = $pdo->prepare( "
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
" );
$notifStmt->execute( [ $supervisorId ] );
$notifications = $notifStmt->fetchAll( PDO::FETCH_ASSOC );

// 5 ) Unread count for navbar
$unreadCount = get_unread_notifications_count( $pdo, $supervisorId );
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <title>Supervisor Dashboard | Project Tracker</title>
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css'>
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
  <link rel='stylesheet' href='../dist/css/adminlte.min.css'>
</head>

<body class='hold-transition sidebar-mini layout-fixed'>
  <div class='wrapper'>

    <!-- Navbar -->
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <!-- Sidebar toggle -->
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#'><i class='fas fa-bars'></i></a>
        </li>
      </ul>
      <!-- Right navbar -->
      <ul class='navbar-nav ml-auto'>
        <!-- Notifications -->
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
            <span class='dropdown-item dropdown-header'><?php echo $unreadCount;
?> New</span>
            <div class='dropdown-divider'></div>
            <a href='notifications.php' class='dropdown-item dropdown-footer'>See All Notifications</a>
          </div>
        </li>
        <!-- Logout -->
        <li class='nav-item'>
          <a class='nav-link' href='../auth/logout.php'><i class='fas fa-sign-out-alt'></i></a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Sidebar -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <a href='dashboard.php' class='brand-link text-center'>
        <span class='brand-text font-weight-light'>Project Tracker</span>
      </a>
      <div class='sidebar'>
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column' data-widget='treeview'>
            <li class='nav-item'>
              <a href='dashboard.php' class='nav-link active'>
                <i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='projects.php' class='nav-link'>
                <i class='nav-icon fas fa-project-diagram'></i>
                <p>Projects</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='notifications.php' class='nav-link'>
                <i class='nav-icon far fa-bell'></i>
                <p>Notifications</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='profile.php' class="nav-link <?php echo $current_page==='profile'?'active':'';?>">
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
      </div>
    </aside>


    <!-- Content Wrapper -->
    <div class='content-wrapper p-4'>
      <div class='container-fluid'>

        <!-- Stats boxes -->
        <div class='row'>
          <?php
$boxes = [
    [ 'count'=>$stats[ 'total_projects' ], 'label'=>'Total Projects', 'icon'=>'fas fa-project-diagram', 'bg'=>'info' ],
    [ 'count'=>$stats[ 'pending_reviews' ], 'label'=>'Pending Reviews', 'icon'=>'fas fa-clock', 'bg'=>'warning' ],
    [ 'count'=>$stats[ 'approved_stages' ], 'label'=>'Approved Stages', 'icon'=>'fas fa-check-circle', 'bg'=>'success' ],
    [ 'count'=>$unreadCount, 'label'=>'Unread Notices', 'icon'=>'far fa-bell', 'bg'=>'danger' ],
];
foreach ( $boxes as $box ): ?>
          <div class='col-lg-3 col-6'>
            <div class="small-box bg-<?php echo $box['bg']; ?>">
              <div class='inner'>
                <h3><?php echo $box[ 'count' ];
?></h3>
                <p><?php echo $box[ 'label' ];
?></p>
              </div>
              <div class='icon'><i class="<?php echo $box['icon']; ?>"></i></div>
              <a href='#' class='small-box-footer'>View <i class='fas fa-arrow-circle-right'></i></a>
            </div>
          </div>
          <?php endforeach;
?>
        </div>

        <div class='row'>
          <!-- Active Projects -->
          <div class='col-md-8'>
            <div class='card'>
              <div class='card-header'>
                <h3 class='card-title'>Active Projects</h3>
              </div>
              <div class='card-body p-0'>
                <?php if ( empty( $projects ) ): ?>
                <p class='p-3 text-center text-muted'>No active projects.</p>
                <?php else: ?>
                <div class='table-responsive'>
                  <table class='table table-hover'>
                    <thead>
                      <tr>
                        <th>Project</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ( $projects as $p ):
$total = $p[ 'pending_count' ] + $p[ 'approved_count' ];
$percent = $total ? round( $p[ 'approved_count' ]/$total*100 ) : 0;
?>
                      <tr>
                        <td><?php echo htmlspecialchars( $p[ 'title' ] );
?></td>
                        <td><?php echo htmlspecialchars( $p[ 'student_name' ] );
?></td>
                        <td>
                          <span
                            class="badge badge-<?php echo ($p['status'] === 'completed' ? 'success' : 'secondary'); ?>">
                            <?php echo ucfirst( $p[ 'status' ] );
?>
                          </span>
                        </td>
                        <td>
                          <div class='progress progress-sm'>
                            <div class='progress-bar' style="width:<?php echo $percent; ?>%"></div>
                          </div>
                          <small><?php echo $percent;
?>%</small>
                        </td>
                        <td>
                          <a href="project_view.php?id=<?php echo $p['project_id']; ?>" class='btn btn-info btn-sm'>
                            <i class='fas fa-eye'></i>
                          </a>
                        </td>
                      </tr>
                      <?php endforeach;
?>
                    </tbody>
                  </table>
                </div>
                <?php endif;
?>
              </div>
            </div>
          </div>

          <!-- Pending Reviews & Recent Notices -->
          <div class='col-md-4'>
            <div class='card mb-3'>
              <div class='card-header'>
                <h3 class='card-title'>Pending Reviews</h3>
              </div>
              <div class='card-body p-0'>
                <?php if ( empty( $pending_reviews ) ): ?>
                <p class='p-3 text-center text-muted'>No pending reviews.</p>
                <?php else: ?>
                <div class='list-group list-group-flush'>
                  <?php foreach ( $pending_reviews as $r ): ?>
                  <a href="stage_review.php?id=<?php echo $r['stage_id']; ?>"
                    class='list-group-item list-group-item-action'>
                    <div class='d-flex justify-content-between'>
                      <span><?php echo htmlspecialchars( $r[ 'stage_title' ] );
?></span>
                      <small class='text-muted'><?php echo date( 'Y-m-d', strtotime( $r[ 'submission_date' ] ) );
?></small>
                    </div>
                    <small><?php echo htmlspecialchars( $r[ 'project_title' ] );
?> ·
                      <?php echo htmlspecialchars( $r[ 'student_name' ] );
?></small>
                  </a>
                  <?php endforeach;
?>
                </div>
                <?php endif;
?>
              </div>
            </div>

            <div class='card'>
              <div class='card-header'>
                <h3 class='card-title'>Recent Notices</h3>
              </div>
              <div class='card-body p-0'>
                <?php if ( empty( $notifications ) ): ?>
                <p class='p-3 text-center text-muted'>No recent notifications.</p>
                <?php else: ?>
                <div class='list-group list-group-flush'>
                  <?php foreach ( $notifications as $n ): ?>
                  <div class='list-group-item'>
                    <div class='d-flex justify-content-between'>
                      <strong><?php echo htmlspecialchars( $n[ 'title' ] );
?></strong>
                      <small class='text-muted'><?php echo date( 'Y-m-d', strtotime( $n[ 'created_at' ] ) );
?></small>
                    </div>
                    <small><?php echo htmlspecialchars( $n[ 'message' ] );
?></small>
                  </div>
                  <?php endforeach;
?>
                </div>
                <?php endif;
?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <footer class='main-footer text-center'>
      <strong>&copy;
        <?php echo date( 'Y' );
?> Project Tracker</strong>
    </footer>
  </div>

  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>
