<?php
// supervisors/project_view.php
session_start();

// Only supervisors may access
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

$supervisorId = ( int )$_SESSION[ 'user_id' ];

// Fetch project ID from either ?id = or ?project_id =
$projectId = 0;
if ( isset( $_GET[ 'project_id' ] ) ) {
    $projectId = ( int )$_GET[ 'project_id' ];
} elseif ( isset( $_GET[ 'id' ] ) ) {
    $projectId = ( int )$_GET[ 'id' ];
}
if ( $projectId < 1 ) {
    die( 'Invalid project ID.' );
}

// 1 ) Load project + student info
$stmt = $pdo->prepare( "
    SELECT
      p.project_id,
      p.title,
      p.description,
      p.status,
      p.created_at,
      p.updated_at,
      u.username AS student_name,
      u.email    AS student_email
    FROM projects p
    JOIN users u ON p.student_id = u.user_id
    WHERE p.project_id = ?
      AND p.supervisor_id = ?
" );
$stmt->execute( [ $projectId, $supervisorId ] );
$project = $stmt->fetch( PDO::FETCH_ASSOC );
if ( !$project ) {
    die( 'Project not found or access denied.' );
}

// 2 ) Load stages + uploads + reviews for this project
$stagesStmt = $pdo->prepare( "
    SELECT
      s.stage_id,
      s.title         AS stage_title,
      s.description   AS stage_description,
      s.due_date,
      s.status        AS stage_status,
      u.upload_id,
      u.file_name     AS upload_name,
      r.review_id,
      r.grade         AS review_grade,
      r.comment       AS review_comment
    FROM stages s
    LEFT JOIN uploads u
      ON u.stage_id = s.stage_id
    LEFT JOIN reviews r
      ON r.stage_id      = s.stage_id
     AND r.supervisor_id = ?
    WHERE s.project_id = ?
    ORDER BY s.due_date ASC
" );
$stagesStmt->execute( [ $supervisorId, $projectId ] );
$stages = $stagesStmt->fetchAll( PDO::FETCH_ASSOC );

// 3 ) Fetch notifications for navbar
$unreadCount   = get_unread_notifications_count( $pdo, $supervisorId );
$notifications = get_recent_notifications( $pdo, $supervisorId, 5 );
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8' />
  <meta name='viewport' content='width=device-width, initial-scale=1' />
  <title>Project Details &mdash;
    Supervisor</title>
  <!-- Google Font -->
  <link rel='stylesheet'
    href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback' />
  <!-- Font Awesome -->
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css' />
  <!-- overlayScrollbars -->
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css' />
  <!-- AdminLTE -->
  <link rel='stylesheet' href='../dist/css/adminlte.min.css' />
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
    <div class='content-wrapper'>

      <!-- Page Header -->
      <section class='content-header'>
        <div class='container-fluid d-flex justify-content-between align-items-center mb-2'>
          <h1 class='m-0'>Project: <?php echo h( $project[ 'title' ] );
?></h1>
          <a href='projects.php' class='btn btn-secondary btn-sm'>
            <i class='fas fa-arrow-left'></i> Back to Projects
          </a>
        </div>
      </section>

      <!-- Main Content -->
      <section class='content'>
        <div class='container-fluid'>

          <!-- Project Info Card -->
          <div class='card'>
            <div class='card-header bg-primary'>
              <h3 class='card-title'><i class='fas fa-info-circle'></i> Details</h3>
            </div>
            <div class='card-body'>
              <p><strong>Status:</strong>
                <span class="badge badge-<?php echo $project['status']==='completed' ? 'success' : 'secondary'; ?>">
                  <?php echo ucfirst( h( $project[ 'status' ] ) );
?>
                </span>
              </p>
              <p><strong>Description:</strong><br>
                <?php echo nl2br( h( $project[ 'description' ] ) );
?>
              </p>
              <p><strong>Student:</strong>
                <?php echo h( $project[ 'student_name' ] );
?>
                ( <?php echo h( $project[ 'student_email' ] );
?> )
              </p>
              <p>
                <strong>Created:</strong> <?php echo date( 'Y-m-d', strtotime( $project[ 'created_at' ] ) );
?>
                &nbsp;
                |& nbsp;
                <strong>Last Updated:</strong> <?php echo date( 'Y-m-d', strtotime( $project[ 'updated_at' ] ) );
?>
              </p>
            </div>
          </div>

          <!-- Stages Table -->
          <div class='card'>
            <div class='card-header bg-primary'>
              <h3 class='card-title'><i class='fas fa-layer-group'></i> Stages</h3>
            </div>
            <div class='card-body p-0'>
              <?php if ( empty( $stages ) ): ?>
              <div class='p-3 text-center text-muted'>No stages defined for this project.</div>
              <?php else: ?>
              <div class='table-responsive'>
                <table class='table table-hover mb-0'>
                  <thead>
                    <tr>
                      <th>Stage</th>
                      <th>Due Date</th>
                      <th>Status</th>
                      <th>Upload</th>
                      <th>Review</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ( $stages as $s ): ?>
                    <tr>
                      <td><?php echo h( $s[ 'stage_title' ] );
?></td>
                      <td><?php echo date( 'Y-m-d', strtotime( $s[ 'due_date' ] ) );
?></td>
                      <td>
                        <span class="badge badge-<?php
                            echo $s['stage_status']==='approved' ? 'success'
                               : ($s['stage_status']==='rejected' ? 'danger' : 'warning');
                          ?>">
                          <?php echo ucfirst( h( $s[ 'stage_status' ] ) );
?>
                        </span>
                      </td>
                      <td>
                        <?php if ( $s[ 'upload_id' ] ): ?>
                        <a href="../uploads/<?php echo rawurlencode($s['upload_name']); ?>" class='btn btn-sm btn-info'
                          target='_blank'>
                          <i class='fas fa-file'></i>
                        </a>
                        <?php else: ?>
                        <span class='text-muted'>—</span>
                        <?php endif;
?>
                      </td>
                      <td>
                        <?php if ( $s[ 'review_id' ] ): ?>
                        <span class='text-success'>Yes ( <?php echo h( $s[ 'review_grade' ] );
?> )</span>
                        <?php else: ?>
                        <span class='text-warning'>No</span>
                        <?php endif;
?>
                      </td>
                      <td>
                        <a href="stage_review.php?stage_id=<?php echo $s['stage_id']; ?>"
                          class="btn btn-sm btn-<?php echo $s['review_id'] ? 'primary' : 'warning'; ?>"
                          title="<?php echo $s['review_id'] ? 'View Review' : 'Review Stage'; ?>">
                          <i class="fas fa-<?php echo $s['review_id'] ? 'eye' : 'star'; ?>"></i>
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
      </section>
      <!-- /.content -->

    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php';
?>

  </div>
  <!-- ./wrapper -->

  <!-- REQUIRED SCRIPTS -->
  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>
