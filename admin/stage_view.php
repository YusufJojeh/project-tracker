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

// Check if the stage_id is set
if (isset($_GET['stage_id'])) {
    $stageId = (int)$_GET['stage_id'];
} else {
    die('Stage ID is missing.');
}

// Fetch the stage details
$stmt = $pdo->prepare("
  SELECT
    s.*,
    p.title AS project_title,
    p.description AS project_description,
    p.student_id,
    p.supervisor_id
  FROM stages s
  JOIN projects p ON s.project_id = p.project_id
  WHERE s.stage_id = ?
");
$stmt->execute([$stageId]);
$stage = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stage) {
    die('Stage not found.');
}

// Fetch the student and supervisor names
$studentStmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
$studentStmt->execute([$stage['student_id']]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

$supervisorStmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
$supervisorStmt->execute([$stage['supervisor_id']]);
$supervisor = $supervisorStmt->fetch(PDO::FETCH_ASSOC);

// Fetch attachments related to the stage
$attachmentsStmt = $pdo->prepare("
  SELECT *
  FROM uploads
  WHERE stage_id = ?
  ORDER BY upload_id ASC
");
$attachmentsStmt->execute([$stageId]);
$attachments = $attachmentsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- Include Header for Admin -->
<?php include('../includes/admin_header.php'); ?>

<!-- Content Wrapper -->
<div class="content-wrapper p-4">
  <div class="container-fluid">
    <!-- Stage Details -->
    <div class="card mb-4">
      <div class="card-header bg-primary">
        <h3 class="card-title text-white"><?php echo htmlspecialchars($stage['title']); ?> - Stage View</h3>
      </div>
      <div class="card-body">
        <p><strong>Project Title:</strong> <?php echo htmlspecialchars($stage['project_title']); ?></p>
        <p><strong>Project Description:</strong> <?php echo nl2br(htmlspecialchars($stage['project_description'])); ?>
        </p>
        <p><strong>Student:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
        <p><strong>Supervisor:</strong> <?php echo htmlspecialchars($supervisor['username']); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($stage['status']); ?></p>
        <p><strong>Due Date:</strong> <?php echo htmlspecialchars($stage['due_date']); ?></p>

        <!-- Grade and Feedback -->
        <?php if (isset($stage['grade']) && $stage['grade'] !== null): ?>
        <p><strong>Grade:</strong> <?php echo number_format($stage['grade'], 2); ?></p>
        <?php endif; ?>
        <?php if (isset($stage['feedback']) && $stage['feedback'] !== ''): ?>
        <p><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($stage['feedback'])); ?></p>
        <?php endif; ?>

        <!-- Attachments -->
        <?php if ($attachments): ?>
        <h6>Attachments</h6>
        <ul>
          <?php foreach ($attachments as $file): ?>
          <li>
            <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank">
              <?php echo htmlspecialchars($file['file_name']); ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p>No attachments available for this stage.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Back Button -->
    <a href="project_view.php?project_id=<?php echo $stage['project_id']; ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Back to Project
    </a>
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