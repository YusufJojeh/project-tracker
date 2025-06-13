<?php
// students/project_create.php

session_start();

// التأكد من أن المستخدم طالب ومسجّل دخول
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';
require_role('student');

// جلب جميع المشرفين (supervisors) من جدول users
$stmt = $pdo->prepare("SELECT user_id AS supervisor_id, username AS full_name FROM users WHERE role = 'supervisor' ORDER BY username");
$stmt->execute();
$supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب الإشعارات
$unreadCount   = get_unread_notifications_count($pdo, $_SESSION['user_id']);
$notifications = get_recent_notifications($pdo, $_SESSION['user_id'], 5);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $supervisor_id = $_POST['supervisor_id'] ?? '';

    if ($title === '') {
        $error = 'Project title is required';
    } elseif ($supervisor_id === '') {
        $error = 'Please select a supervisor';
    } else {
        // تأكد أن الطالب فعلاً موجود في جدول الطلاب (students)
        $student_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare('SELECT * FROM students WHERE student_id = ?');
        $stmt->execute([$student_id]);
        if ($stmt->rowCount() == 0) {
            $error = 'You are not registered as a student in the system.';
        } else {
            try {
                $pdo->beginTransaction();
                $insert = $pdo->prepare("
                    INSERT INTO projects
                        (student_id, supervisor_id, title, description, status, created_at, updated_at)
                    VALUES
                        (?, ?, ?, ?, 'draft', NOW(), NOW())
                ");
                $insert->execute([
                    $student_id,
                    $supervisor_id,
                    $title,
                    $description
                ]);
                $project_id = $pdo->lastInsertId();

                // إنشاء إشعار للمشرف
                create_notification(
                    $pdo,
                    (int)$supervisor_id,
                    'New Project Proposal',
                    "Student {$_SESSION['username']} proposed {$title}"
                );

                $pdo->commit();
                header("Location: project_view.php?id={$project_id}");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error creating project: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Create New Project &mdash; Project Tracker</title>
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback" />
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css" />
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css" />
  <link rel="stylesheet" href="../dist/css/adminlte.min.css" />
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button">
            <i class="fas fa-bars"></i>
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <?php if ($unreadCount): ?>
            <span class="badge badge-warning navbar-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-header"><?php echo $unreadCount; ?> Notifications</span>
            <div class="dropdown-divider"></div>
            <?php if (empty($notifications)): ?>
            <a href="#" class="dropdown-item">No notifications</a>
            <?php else: ?>
            <?php foreach ($notifications as $note):
                    $ago = time_elapsed_string($note['created_at']);
                ?>
            <a href="#" class="dropdown-item">
              <i class="fas fa-envelope mr-2"></i> <?php echo h($note['title']); ?>
              <span class="float-right text-muted text-sm"><?php echo $ago; ?></span>
            </a>
            <div class="dropdown-divider"></div>
            <?php endforeach; ?>
            <a href="notifications.php" class="dropdown-item dropdown-footer">See All Notifications</a>
            <?php endif; ?>
          </div>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="#" class="brand-link">
        <img src="../dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3"
          style="opacity: .8">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="image">
            <img src="../dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User">
          </div>
          <div class="info">
            <a href="profile.php" class="d-block"><?php echo h($_SESSION['username']); ?></a>
          </div>
        </div>
        <div class="form-inline">
          <div class="input-group" data-widget="sidebar-search">
            <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-sidebar"><i class="fas fa-search fa-fw"></i></button>
            </div>
          </div>
        </div>
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="projects.php" class="nav-link"><i class="nav-icon fas fa-project-diagram"></i>
                <p>My Projects</p>
              </a></li>
            <li class="nav-item"><a href="project_create.php" class="nav-link active"><i
                  class="nav-icon fas fa-plus-circle"></i>
                <p>Create New Project</p>
              </a></li>
            <li class="nav-item"><a href="stage_upload.php" class="nav-link"><i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a></li>
            <li class="nav-item"><a href="notifications.php" class="nav-link"><i class="nav-icon far fa-bell"></i>
                <p>Notifications
                  <?php if ($unreadCount): ?>
                  <span class="badge badge-warning right"><?php echo $unreadCount; ?></span>
                  <?php endif; ?>
                </p>
              </a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><i class="nav-icon fas fa-user"></i>
                <p>Profile</p>
              </a></li>
            <li class="nav-item"><a href="../auth/logout.php" class="nav-link"><i
                  class="nav-icon fas fa-sign-out-alt"></i>
                <p>Logout</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>Create New Project</h1>
            </div>
            <div class="col-sm-6 text-right">
              <a href="projects.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Projects
              </a>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Project Details</h3>
          </div>
          <form method="post" action="">
            <div class="card-body">
              <div class="form-group">
                <label for="title">Project Title <span class="text-danger">*</span></label>
                <input type="text" id="title" name="title" class="form-control" placeholder="Enter project title"
                  value="<?php echo htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES); ?>" required>
              </div>
              <div class="form-group">
                <label for="description">Project Description</label>
                <textarea id="description" name="description" class="form-control"
                  placeholder="Enter project description"><?php echo htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES); ?></textarea>
              </div>
              <div class="form-group">
                <label for="supervisor_id">Supervisor <span class="text-danger">*</span></label>
                <select id="supervisor_id" name="supervisor_id" class="form-control" required>
                  <option value="">Select Supervisor</option>
                  <?php foreach ($supervisors as $sup): ?>
                  <option value="<?php echo $sup['supervisor_id']; ?>"
                    <?php echo (($_POST['supervisor_id'] ?? '') == $sup['supervisor_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($sup['full_name'], ENT_QUOTES); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Project</button>
              <a href="projects.php" class="btn btn-default">Cancel</a>
            </div>
          </form>
        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </div>
  <!-- ./wrapper -->

  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
</body>

</html>