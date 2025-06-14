<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';
require_role('student');

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    header('Location: projects.php');
    exit;
}

// Get project details (users table for student and supervisor)
$stmt = $pdo->prepare('
    SELECT p.*, stu.username as student_name, sup.username as supervisor_name, sup.user_id as supervisor_id
    FROM projects p
    JOIN users stu ON p.student_id = stu.user_id
    LEFT JOIN users sup ON p.supervisor_id = sup.user_id
    WHERE p.project_id = ? AND p.student_id = ?
');
$stmt->execute([$project_id, $_SESSION['user_id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header('Location: projects.php');
    exit;
}

$error = $success = '';

// Handle new stage creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_stage'])) {
    $stage_title = trim($_POST['stage_title'] ?? '');
    $stage_description = trim($_POST['stage_description'] ?? '');
    $stage_due_date = trim($_POST['stage_due_date'] ?? '');

    if ($stage_title === '') {
        $error = 'Stage title is required';
    } elseif ($stage_due_date === '') {
        $error = 'Stage due date is required';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO stages (project_id, title, description, due_date, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, "pending", NOW(), NOW())
            ');
            $stmt->execute([$project_id, $stage_title, $stage_description, $stage_due_date]);
            $success = 'Stage created successfully!';
        } catch (Exception $e) {
            $error = 'Error creating stage: ' . $e->getMessage();
        }
    }
}

// Handle stage submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_stage'])) {
    $stage_id = $_POST['stage_id'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $file = $_FILES['submission_file'] ?? null;

    if (empty($description)) {
        $error = 'Please provide a description of your submission';
    } else {
        try {
            $pdo->beginTransaction();
            // Handle file upload
            $file_path = null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/stages/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('stage_') . '.' . $file_extension;
                $file_path = 'uploads/stages/' . $file_name;
                move_uploaded_file($file['tmp_name'], $upload_dir . $file_name);
            }
            // Update stage
            $stmt = $pdo->prepare('
                UPDATE stages 
                SET status = "submitted", 
                    updated_at = NOW(),
                    submission_description = ?,
                    submission_file = ?
                WHERE stage_id = ? AND project_id = ?
            ');
            $stmt->execute([$description, $file_path, $stage_id, $project_id]);
            // Create notification for supervisor if exists
            if (!empty($project['supervisor_id'])) {
                create_notification(
                    $pdo,
                    $project['supervisor_id'],
                    'Stage Submission',
                    "Student {$project['student_name']} has submitted a stage for review",
                    'info',
                    "/supervisors/stage_review.php?id={$stage_id}"
                );
            }
            $pdo->commit();
            $success = 'Stage submitted successfully';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error submitting stage: ' . $e->getMessage();
        }
    }
}

// Get project stages
$stmt = $pdo->prepare('
    SELECT * 
    FROM stages 
    WHERE project_id = ? 
    ORDER BY due_date ASC
');
$stmt->execute([$project_id]);
$stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

function get_stage_status_class($status) {
    return match ($status) {
        'approved' => 'success',
        'submitted' => 'primary',
        'pending' => 'warning',
        'rejected' => 'danger',
        default => 'secondary',
    };
}

$page_title = 'Manage Stages - ' . $project['title'];
$current_page = 'projects';
$unreadCount = get_unread_notifications_count($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($page_title); ?></title>
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
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
            <a href="notifications.php" class="dropdown-item dropdown-footer">See All Notifications</a>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i></a>
        </li>
      </ul>
    </nav>
    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <a href="dashboard.php" class="brand-link">
        <span class="brand-text font-weight-light">Project Tracker</span>
      </a>
      <div class="sidebar">
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a></li>
            <li class="nav-item"><a href="projects.php" class="nav-link"><i class="nav-icon fas fa-project-diagram"></i>
                <p>My Projects</p>
              </a></li>
            <li class="nav-item"><a href="project_create.php" class="nav-link"><i
                  class="nav-icon fas fa-plus-circle"></i>
                <p>Create Project</p>
              </a></li>
            <li class="nav-item"><a href="stage_upload.php" class="nav-link"><i class="nav-icon fas fa-upload"></i>
                <p>Upload Stage</p>
              </a></li>
            <li class="nav-item"><a href="manage_stages.php?id=<?php echo $project_id; ?>" class="nav-link active"><i
                  class="nav-icon fas fa-tasks"></i>
                <p>Manage Stages</p>
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

    <div class="content-wrapper">
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Project Stages</h1>
              <p class="text-muted"><?php echo h($project['title']); ?></p>
            </div>
            <div class="col-sm-6">
              <div class="float-sm-right">
                <a href="project_view.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">
                  <i class="fas fa-arrow-left"></i> Back to Project
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="content">
        <div class="container-fluid">

          <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo h($error); ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
          <div class="alert alert-success"><?php echo h($success); ?></div>
          <?php endif; ?>

          <!-- Stage creation form -->
          <div class="card mb-4">
            <div class="card-header bg-primary">
              <h3 class="card-title text-white"><i class="fas fa-plus-circle"></i> Add New Stage</h3>
            </div>
            <form method="post">
              <input type="hidden" name="create_stage" value="1">
              <div class="card-body row">
                <div class="form-group col-md-4">
                  <label>Stage Title <span class="text-danger">*</span></label>
                  <input type="text" name="stage_title" class="form-control" required>
                </div>
                <div class="form-group col-md-5">
                  <label>Description</label>
                  <input type="text" name="stage_description" class="form-control">
                </div>
                <div class="form-group col-md-3">
                  <label>Due Date <span class="text-danger">*</span></label>
                  <input type="date" name="stage_due_date" class="form-control" required>
                </div>
              </div>
              <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Stage</button>
              </div>
            </form>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Project Timeline</h3>
                </div>
                <div class="card-body">
                  <div class="timeline">
                    <?php foreach ($stages as $stage): ?>
                    <div class="time-label">
                      <span class="bg-<?php echo get_stage_status_class($stage['status']); ?>">
                        <?php echo date('M j, Y', strtotime($stage['due_date'])); ?>
                      </span>
                    </div>
                    <div>
                      <i class="fas fa-tasks bg-<?php echo get_stage_status_class($stage['status']); ?>"></i>
                      <div class="timeline-item">
                        <span class="time">
                          <i class="fas fa-clock"></i>
                          <?php echo date('h:i A', strtotime($stage['due_date'])); ?>
                        </span>
                        <h3 class="timeline-header">
                          <?php echo h($stage['title']); ?>
                          <span class="badge badge-<?php echo get_stage_status_class($stage['status']); ?>">
                            <?php echo ucfirst($stage['status']); ?>
                          </span>
                        </h3>
                        <div class="timeline-body">
                          <?php echo nl2br(h($stage['description'])); ?>
                          <?php if ($stage['status'] === 'pending'): ?>
                          <button type="button" class="btn btn-primary btn-sm mt-2" data-toggle="modal"
                            data-target="#submitModal<?php echo $stage['stage_id']; ?>">
                            <i class="fas fa-upload"></i> Submit Work
                          </button>

                          <!-- Submission Modal -->
                          <div class="modal fade" id="submitModal<?php echo $stage['stage_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title">Submit Stage Work</h5>
                                  <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                  </button>
                                </div>
                                <form method="post" enctype="multipart/form-data">
                                  <div class="modal-body">
                                    <input type="hidden" name="stage_id" value="<?php echo $stage['stage_id']; ?>">
                                    <div class="form-group">
                                      <label>Description</label>
                                      <textarea name="description" class="form-control" rows="4" required></textarea>
                                      <small class="form-text text-muted">
                                        Describe your work and any important notes for the supervisor
                                      </small>
                                    </div>
                                    <div class="form-group">
                                      <label>File Upload</label>
                                      <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="submission_file"
                                          id="file<?php echo $stage['stage_id']; ?>">
                                        <label class="custom-file-label"
                                          for="file<?php echo $stage['stage_id']; ?>">Choose file</label>
                                      </div>
                                      <small class="form-text text-muted">
                                        Upload your work (PDF, DOC, DOCX, ZIP, etc.)
                                      </small>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" name="submit_stage" class="btn btn-primary">Submit</button>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                          <?php elseif ($stage['status'] === 'submitted' || $stage['status'] === 'reviewed' || $stage['status'] === 'approved'): ?>
                          <div class="mt-2">
                            <?php if ($stage['submission_file']): ?>
                            <a href="../<?php echo h($stage['submission_file']); ?>" class="btn btn-info btn-sm"
                              target="_blank">
                              <i class="fas fa-download"></i> Download Submission
                            </a>
                            <?php endif; ?>
                            <?php if ($stage['status'] === 'reviewed' && !empty($stage['feedback'])): ?>
                            <div class="mt-2"><strong>Feedback:</strong><br><?php echo nl2br(h($stage['feedback'])); ?>
                            </div>
                            <?php endif; ?>
                          </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- File input label script -->
    <script>
    document.querySelectorAll('.custom-file-input').forEach(input => {
      input.addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'Choose file';
        this.nextElementSibling.textContent = fileName;
      });
    });
    </script>
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
  </div>
</body>

</html>