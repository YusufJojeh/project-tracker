<?php
session_start();

// Check if the user is logged in and is an admin
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';

// Accept either ?project_id = or ?id = for backwards compatibility
$projectId = 0;
if (isset($_GET['project_id'])) {
    $projectId = (int)$_GET['project_id'];
} elseif (isset($_GET['id'])) {
    $projectId = (int)$_GET['id'];
}

// Admin's user ID (admin will have access to all projects)
$adminId = $_SESSION['user_id']; // You can replace this with a specific admin identifier if needed

// 1) Load the project, ensuring the project exists
$stmt = $pdo->prepare("
  SELECT
    p.*,
    stu.username AS student_name,
    sup.username AS supervisor_name
  FROM projects p
  JOIN users stu ON p.student_id = stu.user_id
  JOIN users sup ON p.supervisor_id = sup.user_id
  WHERE p.project_id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    die('Project not found.');
}

// 2) Load its stages
$stagesStmt = $pdo->prepare("
  SELECT *
  FROM stages
  WHERE project_id = ?
  ORDER BY due_date ASC
");
$stagesStmt->execute([$projectId]);
$stages = $stagesStmt->fetchAll(PDO::FETCH_ASSOC);

// 3) Prepare attachments query (check if attachments table exists)
$attachments = [];
try {
    // Check if the attachments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'attachments'");
    if ($stmt->rowCount() > 0) {
        // Attachments table exists, so fetch the attachments
        $attachStmt = $pdo->prepare("
          SELECT *
          FROM attachments
          WHERE stage_id = ?
          ORDER BY created_at ASC
        ");
        foreach ($stages as $stage) {
            $attachStmt->execute([$stage['stage_id']]);
            $attachments[$stage['stage_id']] = $attachStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    // If an error occurs, the attachments table might be missing or there's another issue
    error_log("Error fetching attachments: " . $e->getMessage());
    $attachments = [];  // Ensure it remains empty if an error occurs
}

// 4) Unread notifications count for admin
$unreadCount = get_unread_notifications_count($pdo, $adminId);
?>

<!-- Include Header for Admin -->
<?php include('../includes/admin_header.php'); ?>

<!-- Content Wrapper -->
<div class="content-wrapper p-4">
  <div class="container-fluid">
    <!-- Project Details -->
    <div class="card mb-4">
      <div class="card-header bg-primary">
        <h3 class="card-title text-white"><?php echo htmlspecialchars($project['title']); ?></h3>
      </div>
      <div class="card-body">
        <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
        <p><strong>Status:</strong>
          <span class="badge badge-<?php echo match($project['status']) {
                        'completed'   => 'success',
                        'in_progress' => 'primary',
                        'pending'     => 'warning',
                        default       => 'danger',
                    }; ?>"><?php echo ucfirst($project['status']); ?></span>
        </p>
        <p><strong>Student:</strong> <?php echo htmlspecialchars($project['student_name']); ?></p>
        <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($project['supervisor_name']); ?></p>
        <p><strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($project['created_at'])); ?></p>
        <?php if ($project['updated_at']): ?>
        <p><strong>Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($project['updated_at'])); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stages -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Project Stages</h3>
      </div>
      <div class="card-body">
        <div class="row">
          <?php foreach ($stages as $stage):
                        // Check for attachments in the existing table
                        $stageAttachments = isset($attachments[$stage['stage_id']]) ? $attachments[$stage['stage_id']] : [];
                    ?>
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between">
                <span><?php echo htmlspecialchars($stage['title']); ?></span>
                <span class="badge badge-<?php echo match($stage['status']) {
                                        'submitted' => 'info',
                                        'approved'  => 'success',
                                        'rejected'  => 'danger',
                                        default     => 'secondary',
                                    }; ?>"><?php echo ucfirst($stage['status']); ?></span>
              </div>
              <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($stage['description'])); ?></p>
                <p><strong>Due:</strong> <?php echo htmlspecialchars($stage['due_date']); ?></p>

                <!-- Grade and Feedback -->
                <?php if (isset($stage['grade']) && $stage['grade'] !== null): ?>
                <p><strong>Grade:</strong> <?php echo number_format($stage['grade'], 2); ?></p>
                <?php endif; ?>
                <?php if (isset($stage['feedback']) && $stage['feedback'] !== ''): ?>
                <p><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($stage['feedback'])); ?></p>
                <?php endif; ?>

                <!-- Attachments -->
                <?php if ($stageAttachments): ?>
                <h6>Attachments</h6>
                <ul>
                  <?php foreach ($stageAttachments as $file): ?>
                  <li>
                    <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank">
                      <?php echo htmlspecialchars($file['file_name']); ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <a href="stage_view.php?stage_id=<?php echo $stage['stage_id']; ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-eye"></i> View Stage
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if (empty($stages)): ?>
          <div class="col-12">
            <p class="text-muted">No stages for this project.</p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<?php include('../includes/admin_footer.php'); ?>

<!-- Scripts -->
<script src="../plugins/jquery/jquery.min.js"></script>
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
</body>

</html>