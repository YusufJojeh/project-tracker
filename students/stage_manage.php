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

// Get project details
$stmt = $pdo->prepare('
    SELECT p.*, s.full_name as student_name, sp.full_name as supervisor_name
    FROM projects p
    JOIN students s ON p.student_id = s.student_id
    LEFT JOIN supervisors sp ON p.supervisor_id = sp.supervisor_id
    WHERE p.project_id = ? AND p.student_id = ?
');
$stmt->execute([$project_id, $_SESSION['user_id']]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Handle stage submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_stage'])) {
    $stage_id = $_POST['stage_id'];
    $description = trim($_POST['description']);
    $file = $_FILES['submission_file'] ?? null;
    
    if (empty($description)) {
        $error = 'Please provide a description of your submission';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Handle file upload
            $file_path = null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/stages/';
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
                    submission_date = NOW(),
                    submission_description = ?,
                    submission_file = ?
                WHERE stage_id = ? AND project_id = ?
            ');
            $stmt->execute([$description, $file_path, $stage_id, $project_id]);
            
            // Create notification for supervisor
            create_notification(
                $pdo,
                $project['supervisor_id'],
                'Stage Submission',
                "Student {$project['student_name']} has submitted a stage for review",
                'info',
                "/supervisors/stage_review.php?id={$stage_id}"
            );
            
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
$stages = $stmt->fetchAll();

$page_title = 'Manage Stages - ' . $project['title'];
$current_page = 'projects';
require_once '../includes/header.php';
?>

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
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>

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
                                                <?php echo time_elapsed_string($stage['due_date']); ?>
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
                                                    <button type="button" class="btn btn-primary btn-sm mt-2" 
                                                            data-toggle="modal" 
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
                                                                                <input type="file" class="custom-file-input" name="submission_file" id="file<?php echo $stage['stage_id']; ?>">
                                                                                <label class="custom-file-label" for="file<?php echo $stage['stage_id']; ?>">Choose file</label>
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
                                                <?php elseif ($stage['status'] === 'submitted'): ?>
                                                    <div class="mt-2">
                                                        <strong>Submitted:</strong> 
                                                        <?php echo date('M j, Y g:i A', strtotime($stage['submission_date'])); ?>
                                                        <?php if ($stage['submission_file']): ?>
                                                            <br>
                                                            <a href="../<?php echo h($stage['submission_file']); ?>" class="btn btn-info btn-sm" target="_blank">
                                                                <i class="fas fa-download"></i> Download Submission
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($stage['status'] === 'reviewed'): ?>
                                                    <div class="mt-2">
                                                        <strong>Feedback:</strong><br>
                                                        <?php echo nl2br(h($stage['feedback'])); ?>
                                                        <?php if ($stage['submission_file']): ?>
                                                            <br>
                                                            <a href="../<?php echo h($stage['submission_file']); ?>" class="btn btn-info btn-sm" target="_blank">
                                                                <i class="fas fa-download"></i> Download Submission
                                                            </a>
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

<script>
// Update file input label with selected filename
document.querySelectorAll('.custom-file-input').forEach(input => {
    input.addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'Choose file';
        this.nextElementSibling.textContent = fileName;
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 