<?php
// supervisors/projects.php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

// Only supervisors
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../auth/login.php');
    exit;
}
$supervisorId = (int)$_SESSION['user_id'];

// Fetch all projects supervised by this user
$stmt = $pdo->prepare("
    SELECT
      p.project_id,
      p.title,
      p.status,
      p.created_at,
      SUM(CASE WHEN s.status = 'submitted' THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN s.status = 'approved'  THEN 1 ELSE 0 END) AS approved_count
    FROM projects p
    LEFT JOIN stages s ON p.project_id = s.project_id
    WHERE p.supervisor_id = ?
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
");
$stmt->execute([$supervisorId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unread notifications count for navbar badge
$unreadCount = get_unread_notifications_count($pdo, $supervisorId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Projects &mdash; Supervisor</title>
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

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
    <div class="content-wrapper">
      <!-- Content Header -->
      <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center mb-2">
          <h1 class="m-0">My Assigned Projects</h1>
        </div>
      </section>

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">

          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Projects List</h3>
            </div>
            <div class="card-body p-0">
              <?php if (empty($projects)): ?>
              <div class="p-3 text-center text-muted">
                You have no projects assigned.
              </div>
              <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Title</th>
                      <th>Status</th>
                      <th>Pending Reviews</th>
                      <th>Approved Stages</th>
                      <th>Created</th>
                      <th style="width:120px">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($projects as $p):
                      $total   = $p['pending_count'] + $p['approved_count'];
                      $percent = $total ? round($p['approved_count'] / $total * 100) : 0;
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($p['title'], ENT_QUOTES) ?></td>
                      <td>
                        <span class="badge badge-<?php echo $p['status'] === 'completed' ? 'success' : 'secondary'; ?>">
                          <?php echo ucfirst(htmlspecialchars($p['status'], ENT_QUOTES)) ?>
                        </span>
                      </td>
                      <td><?php echo (int)$p['pending_count'] ?></td>
                      <td><?php echo (int)$p['approved_count'] ?></td>
                      <td><?php echo date('Y-m-d', strtotime($p['created_at'])) ?></td>
                      <td>
                        <a href="project_view.php?project_id=<?php echo $p['project_id'] ?>" class="btn btn-info btn-sm"
                          title="View">
                          <i class="fas fa-eye"></i>
                        </a>
                        <?php if ($p['pending_count'] > 0): ?>
                        <a href="stage_review.php?id=<?php echo $p['project_id'] ?>" class="btn btn-warning btn-sm"
                          title="Review">
                          <i class="fas fa-star"></i>
                        </a>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <?php include __DIR__ . '/../includes/footer.php'; ?>

  </div>
  <!-- ./wrapper -->

  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>