<?php
// supervisors/stage_review.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

// Only supervisors allowed
if ( empty( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

$supervisorId = ( int )$_SESSION[ 'user_id' ];
$stageId       = isset( $_GET[ 'id' ] ) ? ( int )$_GET[ 'id' ] : 0;
if ( $stageId < 1 ) {
    die( 'Invalid stage ID.' );
}

// Handle form submission: insert or update review
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $comment = trim( $_POST[ 'comment' ] );
    $grade   = ( float )$_POST[ 'grade' ];

    // Check for existing review
    $check = $pdo->prepare( "
        SELECT review_id
          FROM reviews
         WHERE stage_id = ?
           AND supervisor_id = ?
    " );
    $check->execute( [ $stageId, $supervisorId ] );
    $existing = $check->fetchColumn();

    if ( $existing ) {
        $upd = $pdo->prepare( "
            UPDATE reviews
               SET comment    = ?,
                   grade      = ?,
                   updated_at = CURRENT_TIMESTAMP
             WHERE review_id = ?
        " );
        $upd->execute( [ $comment, $grade, $existing ] );
    } else {
        $ins = $pdo->prepare( "
            INSERT INTO reviews
                (stage_id, supervisor_id, comment, grade, created_at)
            VALUES
                (?, ?, ?, ?, CURRENT_TIMESTAMP)
        " );
        $ins->execute( [ $stageId, $supervisorId, $comment, $grade ] );
    }
    header( 'Location: assigned_projects.php' );
    exit;
}

// Load stage + project + student + existing review ( if any )
$stmt = $pdo->prepare( "
    SELECT
      s.stage_id,
      s.title      AS stage_title,
      s.description AS stage_description,
      s.due_date,
      p.project_id,
      p.title      AS project_title,
      u.user_id    AS student_id,
      u.username   AS student_username,
      u.email      AS student_email,
      r.review_id,
      r.comment    AS existing_comment,
      r.grade      AS existing_grade
    FROM stages      s
    JOIN projects    p ON s.project_id = p.project_id
    JOIN users       u ON p.student_id = u.user_id
    LEFT JOIN reviews r
      ON r.stage_id      = s.stage_id
     AND r.supervisor_id = ?
    WHERE s.stage_id = ?
" );
$stmt->execute( [ $supervisorId, $stageId ] );
$data = $stmt->fetch( PDO::FETCH_ASSOC );

if ( !$data ) {
    die( 'Stage not found or access denied.' );
}

// Unread notifications count ( for navbar badge )
$unreadCount = get_unread_notifications_count( $pdo, $supervisorId );
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <title>Review Stage &mdash;
    Project Tracker</title>
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

        <div class='card card-primary'>
          <div class='card-header'>
            <h3 class='card-title'>
              <i class='fas fa-star'></i>
              Review Stage: <?php echo htmlspecialchars( $data[ 'stage_title' ], ENT_QUOTES ) ?>
            </h3>
          </div>
          <div class='card-body'>
            <p><strong>Project:</strong>
              <?php echo htmlspecialchars( $data[ 'project_title' ], ENT_QUOTES ) ?>
            </p>
            <p><strong>Student:</strong>
              <?php echo htmlspecialchars( $data[ 'student_username' ], ENT_QUOTES ) ?>
              ( <?php echo htmlspecialchars( $data[ 'student_email' ], ENT_QUOTES ) ?> )
            </p>
            <p><strong>Due Date:</strong>
              <?php echo date( 'Y-m-d', strtotime( $data[ 'due_date' ] ) ) ?>
            </p>
            <?php if ( $data[ 'stage_description' ] ): ?>
            <p><strong>Description:</strong><br>
              <?php echo nl2br( htmlspecialchars( $data[ 'stage_description' ], ENT_QUOTES ) ) ?>
            </p>
            <?php endif;
?>

            <form method='post'>
              <div class='form-group'>
                <label for='comment'>Comments</label>
                <textarea id='comment' name='comment' class='form-control' rows='4'
                  placeholder='Enter your feedback...'><?php
echo htmlspecialchars( $data[ 'existing_comment' ] ?? '', ENT_QUOTES );
?></textarea>
              </div>
              <div class='form-group'>
                <label for='grade'>Grade</label>
                <input type='number' step='0.1' id='grade' name='grade' class='form-control' required
                  placeholder='e.g. 8.5' value="<?php echo isset($data['existing_grade'])
                              ? htmlspecialchars($data['existing_grade'], ENT_QUOTES)
                              : ''; ?>">
              </div>
              <button type='submit' class='btn btn-success'>
                <i class='fas fa-save'></i> Submit Review
              </button>
              <a href='assigned_projects.php' class='btn btn-secondary ml-2'>
                <i class='fas fa-arrow-left'></i> Back
              </a>
            </form>
          </div>
        </div>

      </div>
    </div>
    <!-- /.content-wrapper -->

    <?php include __DIR__ . '/../includes/footer.php';
?>

  </div>
  <!-- ./wrapper -->

  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>