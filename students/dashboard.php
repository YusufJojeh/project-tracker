<?php
// students/dashboard.php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require __DIR__ . '/../config/database.php';
$student_id = $_SESSION['user_id'];

// 1) Summary stats
$stats = ['total_projects'=>0,'total_stages'=>0,'completed_stages'=>0];
$stmt = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE student_id=?');
$stmt->execute([$student_id]); $stats['total_projects'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM stages s JOIN projects p ON s.project_id=p.project_id WHERE p.student_id=?');
$stmt->execute([$student_id]); $stats['total_stages'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT s.stage_id) FROM stages s JOIN projects p ON s.project_id=p.project_id JOIN uploads u ON s.stage_id=u.stage_id WHERE p.student_id=?');
$stmt->execute([$student_id]); $stats['completed_stages'] = (int)$stmt->fetchColumn();

// 2) Projects per month (6m)
$stmt = $pdo->prepare('SELECT MONTH(created_at) mon, COUNT(*) cnt FROM projects WHERE student_id=? AND created_at>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY mon');
$stmt->execute([$student_id]); $raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$projByMonth=[]; for($i=5;$i>=0;$i--){ $lbl=date('M',strtotime("-{$i} months")); $m=(int)date('n',strtotime("-{$i} months")); $projByMonth[$lbl]=$raw[$m]??0; }

// 3) Stage status
$stmt=$pdo->prepare('SELECT SUM(due_date<CURDATE()) overdue, SUM(due_date>=CURDATE()) upcoming FROM stages s JOIN projects p ON s.project_id=p.project_id WHERE p.student_id=?');
$stmt->execute([$student_id]); $s=$stmt->fetch();
$statusData=['Overdue'=> (int)$s['overdue'], 'Upcoming'=> (int)$s['upcoming']];

// 4) Grades
$stmt=$pdo->prepare('SELECT p.title, AVG(r.grade) avg_grade FROM reviews r JOIN stages s ON r.stage_id=s.stage_id JOIN projects p ON s.project_id=p.project_id WHERE p.student_id=? GROUP BY p.project_id');
$stmt->execute([$student_id]); $gr=$stmt->fetchAll();
$gradeLabels=array_column($gr,'title'); $gradeValues=array_map(fn($r)=>round((float)$r['avg_grade'],2),$gr);

// 5) Projects list
$stmt=$pdo->prepare('SELECT project_id,title,created_at FROM projects WHERE student_id=? ORDER BY created_at DESC');
$stmt->execute([$student_id]); $projects=$stmt->fetchAll();
$threshold=(new DateTime())->modify('-1 month');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard – MyApp</title>
  <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css" />
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css" />
  <link rel="stylesheet" href="../dist/css/adminlte.min.css" />
  <style>
  /* main background dark */
  body,
  .wrapper {
    background: #1e1e2f;
    color: #f0f0f0;
  }

  /* gradient info-boxes */
  .bg-project-gradient {
    background: linear-gradient(45deg, #4e73df, #2e59d9) !important;
    color: #fff;
  }

  .bg-stage-gradient {
    background: linear-gradient(45deg, #1cc88a, #17a673) !important;
    color: #fff;
  }

  .bg-completed-gradient {
    background: linear-gradient(45deg, #f6c23e, #e0a800) !important;
    color: #333;
  }

  .info-box {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
  }

  .info-box-icon {
    font-size: 2rem;
  }

  /* cards */
  .card {
    background: #2f2f3f;
    border: none;
    color: #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
  }

  .card-header {
    background: #3b3b4d;
    border-bottom: 2px solid #4e73df;
    color: #fff;
  }

  /* sidebar */
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

  /* table */
  .table-hover tbody tr:hover {
    background-color: #3a3a4a;
  }

  /* responsive */
  @media (max-width:576px) {
    .info-box-text {
      font-size: 0.9rem;
    }

    .info-box-number {
      font-size: 1.2rem;
    }
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
        <span class="brand-text font-weight-light">MyApp</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item"><a href="#" class="nav-link active"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a></li>
            <li class="nav-item"><a href="project_create.php" class="nav-link"><i
                  class="nav-icon fas fa-plus-circle"></i>
                <p>New Project</p>
              </a></li>
            <li class="nav-item"><a href="stage_upload.php" class="nav-link"><i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a></li>
            <li class="nav-item"><a href="analysis.php" class="nav-link"><i class="nav-icon fas fa-chart-line"></i>
                <p>Analyze</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>
    <!-- Content Wrapper -->
    <div class="content-wrapper p-3">
      <div class="row">
        <?php
        $map = [
          'total_projects'=>['Projects','bg-project-gradient','fas fa-folder-open'],
          'total_stages'=>['Stages','bg-stage-gradient','fas fa-layer-group'],
          'completed_stages'=>['Completed','bg-completed-gradient','fas fa-check-circle']
        ];
        foreach($stats as $key=>$val):
          list($label,$bgClass,$icon) = $map[$key];
      ?>
        <div class="col-md-4 col-sm-6 mb-4">
          <div class="info-box <?php echo $bgClass; ?>">
            <span class="info-box-icon"><i class="<?php echo $icon; ?>"></i></span>
            <div class="info-box-content">
              <span class="info-box-text"><?php echo $label; ?></span>
              <span class="info-box-number"><?php echo $val; ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Charts -->
      <div class="row">
        <div class="col-lg-6 mb-3">
          <div class="card">
            <div class="card-header"><i class="fas fa-chart-bar"></i> Projects (6m)</div>
            <div class="card-body"><canvas id="projChart"></canvas></div>
          </div>
        </div>
        <div class="col-lg-6 mb-3">
          <div class="card">
            <div class="card-header"><i class="fas fa-clock"></i> Stage Status</div>
            <div class="card-body"><canvas id="statusChart"></canvas></div>
          </div>
        </div>
      </div>
      <!-- Grades & Table -->
      <div class="row">
        <div class="col-lg-6 mb-3">
          <div class="card">
            <div class="card-header"><i class="fas fa-graduation-cap"></i> Avg Grades</div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <?php foreach($gradeLabels as $i=>$t): ?>
                <li class="list-group-item d-flex justify-content-between">
                  <?php echo htmlspecialchars($t); ?><span><?php echo $gradeValues[$i]; ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-lg-6 mb-3">
          <div class="card">
            <div class="card-header"><i class="fas fa-table"></i> My Projects</div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover table-dark mb-0">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($projects as $p): $d=new DateTime($p['created_at']); $can=$d>=$threshold; ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p['title']); ?></td>
                    <td><?php echo $d->format('Y-m-d'); ?></td>
                    <td>
                      <a class="btn btn-sm btn-info"
                        href="project_view.php?project_id=<?php echo $p['project_id']; ?>"><i
                          class="fas fa-eye"></i></a>
                      <?php if($can):?><a class="btn btn-sm btn-danger"
                        href="project_delete.php?project_id=<?php echo $p['project_id']; ?>"
                        onclick="return confirm('Delete?');"><i class="fas fa-trash"></i></a><?php endif;?>
                    </td>
                  </tr>
                  <?php endforeach;?>
                </tbody>
              </table>
            </div>
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  new Chart(document.getElementById('projChart'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_keys($projByMonth)); ?>,
      datasets: [{
        label: 'Projects',
        data: <?php echo json_encode(array_values($projByMonth)); ?>,
        backgroundColor: '#4e73df'
      }]
    }
  });
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode(array_keys($statusData)); ?>,
      datasets: [{
        data: <?php echo json_encode(array_values($statusData)); ?>,
        backgroundColor: ['#e74a3b', '#1cc88a']
      }]
    }
  });
  </script>
</body>

</html>