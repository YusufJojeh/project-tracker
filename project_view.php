<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_login();

$error = '';
$success = '';

// Get project ID from URL
$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    header('Location: projects.php');
    exit;
}

// Get project details with supervisor and student information
$stmt = $pdo->prepare('
    SELECT 
        p.*,
        s.full_name as student_name,
        s.student_number,
        sp.full_name as supervisor_name,
        sp.title as supervisor_title,
        d.name as department_name,
        spec.name as specialization_name,
        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id) as total_stages,
        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id AND status = "completed") as completed_stages
    FROM projects p
    JOIN students s ON p.student_id = s.student_id
    LEFT JOIN supervisors sp ON p.supervisor_id = sp.supervisor_id
    JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN specializations spec ON sp.specialization_id = spec.specialization_id
    WHERE p.project_id = ?
');
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Check if user has permission to view this project
if ($_SESSION['role'] === 'student' && $project['student_id'] !== $_SESSION['user_id']) {
    header('Location: projects.php');
    exit;
}

// Get project stages
$stmt = $pdo->prepare('
    SELECT 
        s.*,
        (SELECT COUNT(*) FROM uploads WHERE stage_id = s.stage_id) as upload_count,
        (SELECT COUNT(*) FROM reviews WHERE stage_id = s.stage_id) as review_count
    FROM stages s
    WHERE s.project_id = ?
    ORDER BY s.stage_number
');
$stmt->execute([$project_id]);
$stages = $stmt->fetchAll();

// Get project comments
$stmt = $pdo->prepare('
    SELECT 
        c.*,
        CASE 
            WHEN c.user_role = "student" THEN s.full_name
            WHEN c.user_role = "supervisor" THEN sp.full_name
            ELSE u.username
        END as commenter_name
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN students s ON c.user_id = s.student_id AND c.user_role = "student"
    LEFT JOIN supervisors sp ON c.user_id = sp.supervisor_id AND c.user_role = "supervisor"
    WHERE c.project_id = ?
    ORDER BY c.created_at DESC
');
$stmt->execute([$project_id]);
$comments = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_project'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $methodology = trim($_POST['methodology'] ?? '');
        $expected_outcomes = trim($_POST['expected_outcomes'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        if (empty($title)) {
            $error = 'Please fill in the project title';
        } else {
            try {
                $stmt = $pdo->prepare('
                    UPDATE projects 
                    SET 
                        title = ?,
                        description = ?,
                        objectives = ?,
                        methodology = ?,
                        expected_outcomes = ?,
                        status = ?,
                        start_date = ?,
                        end_date = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE project_id = ?
                ');
                $stmt->execute([
                    $title,
                    $description,
                    $objectives,
                    $methodology,
                    $expected_outcomes,
                    $status,
                    $start_date,
                    $end_date,
                    $project_id
                ]);
                $success = 'Project updated successfully';
                
                // Refresh project data
                $stmt = $pdo->prepare('
                    SELECT 
                        p.*,
                        s.full_name as student_name,
                        s.student_number,
                        sp.full_name as supervisor_name,
                        sp.title as supervisor_title,
                        d.name as department_name,
                        spec.name as specialization_name,
                        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id) as total_stages,
                        (SELECT COUNT(*) FROM stages WHERE project_id = p.project_id AND status = "completed") as completed_stages
                    FROM projects p
                    JOIN students s ON p.student_id = s.student_id
                    LEFT JOIN supervisors sp ON p.supervisor_id = sp.supervisor_id
                    JOIN departments d ON s.department_id = d.department_id
                    LEFT JOIN specializations spec ON sp.specialization_id = spec.specialization_id
                    WHERE p.project_id = ?
                ');
                $stmt->execute([$project_id]);
                $project = $stmt->fetch();
            } catch (Exception $e) {
                $error = 'Error updating project: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['add_comment'])) {
        $comment = trim($_POST['comment'] ?? '');
        if (empty($comment)) {
            $error = 'Please enter a comment';
        } else {
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO comments (project_id, user_id, user_role, comment)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$project_id, $_SESSION['user_id'], $_SESSION['role'], $comment]);
                
                // Create notification for the other party
                $notification_title = 'New Comment';
                $notification_message = $_SESSION['role'] === 'student' ? 
                    'Your supervisor commented on your project' : 
                    'Your student commented on their project';
                create_notification(
                    $pdo,
                    $_SESSION['role'] === 'student' ? $project['supervisor_id'] : $project['student_id'],
                    $notification_message,
                    $notification_title,
                    'comment',
                    "project_view.php?id=$project_id"
                );
                
                $success = 'Comment added successfully';
                
                // Refresh comments
                $stmt = $pdo->prepare('
                    SELECT 
                        c.*,
                        CASE 
                            WHEN c.user_role = "student" THEN s.full_name
                            WHEN c.user_role = "supervisor" THEN sp.full_name
                            ELSE u.username
                        END as commenter_name
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN students s ON c.user_id = s.student_id AND c.user_role = "student"
                    LEFT JOIN supervisors sp ON c.user_id = sp.supervisor_id AND c.user_role = "supervisor"
                    WHERE c.project_id = ?
                    ORDER BY c.created_at DESC
                ');
                $stmt->execute([$project_id]);
                $comments = $stmt->fetchAll();
            } catch (Exception $e) {
                $error = 'Error adding comment: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Project Details';
$current_page = 'projects';
require_once 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Project Details</h1>
                </div>
                <div class="col-sm-6">
                    <div class="float-sm-right">
                        <a href="projects.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Projects
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

            <div class="row">
                <div class="col-md-8">
                    <!-- Project Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Project Information</h3>
                            <?php if ($_SESSION['role'] === 'student'): ?>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#editProjectModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h4><?php echo h($project['title']); ?></h4>
                            <p class="text-muted">
                                <i class="fas fa-user-graduate"></i> <?php echo h($project['student_name']); ?> 
                                (<?php echo h($project['student_number']); ?>)
                            </p>
                            <?php if ($project['supervisor_name']): ?>
                                <p class="text-muted">
                                    <i class="fas fa-chalkboard-teacher"></i> <?php echo h($project['supervisor_title']); ?> 
                                    <?php echo h($project['supervisor_name']); ?>
                                </p>
                            <?php endif; ?>
                            <p class="text-muted">
                                <i class="fas fa-building"></i> <?php echo h($project['department_name']); ?>
                                <?php if ($project['specialization_name']): ?>
                                    - <?php echo h($project['specialization_name']); ?>
                                <?php endif; ?>
                            </p>
                            <hr>
                            <h5>Description</h5>
                            <p><?php echo nl2br(h($project['description'])); ?></p>
                            
                            <?php if ($project['objectives']): ?>
                                <h5>Objectives</h5>
                                <p><?php echo nl2br(h($project['objectives'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($project['methodology']): ?>
                                <h5>Methodology</h5>
                                <p><?php echo nl2br(h($project['methodology'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($project['expected_outcomes']): ?>
                                <h5>Expected Outcomes</h5>
                                <p><?php echo nl2br(h($project['expected_outcomes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Stages -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Project Stages</h3>
                        </div>
                        <div class="card-body">
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo ($project['completed_stages'] / $project['total_stages']) * 100; ?>%">
                                    <?php echo round(($project['completed_stages'] / $project['total_stages']) * 100); ?>%
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Stage</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Files</th>
                                            <th>Reviews</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stages as $stage): ?>
                                            <tr>
                                                <td><?php echo h($stage['stage_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo get_status_badge_class($stage['status']); ?>">
                                                        <?php echo ucfirst(h($stage['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo format_date($stage['due_date']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo $stage['upload_count']; ?> files
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-warning">
                                                        <?php echo $stage['review_count']; ?> reviews
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="stage_view.php?id=<?php echo $stage['stage_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Comments</h3>
                        </div>
                        <div class="card-body">
                            <form method="post" class="mb-4">
                                <div class="form-group">
                                    <textarea name="comment" class="form-control" rows="3" 
                                              placeholder="Add a comment..." required></textarea>
                                </div>
                                <button type="submit" name="add_comment" class="btn btn-primary">
                                    <i class="fas fa-comment"></i> Add Comment
                                </button>
                            </form>

                            <div class="comments-list">
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment mb-3">
                                        <div class="comment-header">
                                            <strong><?php echo h($comment['commenter_name']); ?></strong>
                                            <small class="text-muted float-right">
                                                <?php echo format_datetime($comment['created_at']); ?>
                                            </small>
                                        </div>
                                        <div class="comment-body">
                                            <?php echo nl2br(h($comment['comment'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Project Status -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Project Status</h3>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Status:</strong>
                                <span class="badge badge-<?php echo get_status_badge_class($project['status']); ?>">
                                    <?php echo ucfirst(h($project['status'])); ?>
                                </span>
                            </p>
                            <?php if ($project['start_date']): ?>
                                <p>
                                    <strong>Start Date:</strong><br>
                                    <?php echo format_date($project['start_date']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($project['end_date']): ?>
                                <p>
                                    <strong>End Date:</strong><br>
                                    <?php echo format_date($project['end_date']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($project['grade']): ?>
                                <p>
                                    <strong>Grade:</strong><br>
                                    <?php echo h($project['grade']); ?>
                                </p>
                            <?php endif; ?>
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo format_datetime($project['created_at']); ?>
                            </p>
                            <?php if ($project['updated_at']): ?>
                                <p>
                                    <strong>Last Updated:</strong><br>
                                    <?php echo format_datetime($project['updated_at']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Project Timeline</h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php
                                $events = [];
                                // Add project creation
                                $events[] = [
                                    'date' => $project['created_at'],
                                    'title' => 'Project Created',
                                    'icon' => 'fas fa-plus-circle',
                                    'color' => 'primary'
                                ];
                                // Add project updates
                                if ($project['updated_at'] && $project['updated_at'] !== $project['created_at']) {
                                    $events[] = [
                                        'date' => $project['updated_at'],
                                        'title' => 'Project Updated',
                                        'icon' => 'fas fa-edit',
                                        'color' => 'info'
                                    ];
                                }
                                // Add stage completions
                                foreach ($stages as $stage) {
                                    if ($stage['status'] === 'completed') {
                                        $events[] = [
                                            'date' => $stage['completed_at'],
                                            'title' => $stage['stage_name'] . ' Completed',
                                            'icon' => 'fas fa-check-circle',
                                            'color' => 'success'
                                        ];
                                    }
                                }
                                // Sort events by date
                                usort($events, function($a, $b) {
                                    return strtotime($a['date']) - strtotime($b['date']);
                                });
                                ?>
                                <?php foreach ($events as $event): ?>
                                    <div class="timeline-item">
                                        <i class="fas <?php echo $event['icon']; ?> bg-<?php echo $event['color']; ?>"></i>
                                        <div class="timeline-item-content">
                                            <h3 class="timeline-header"><?php echo h($event['title']); ?></h3>
                                            <div class="timeline-body">
                                                <?php echo format_datetime($event['date']); ?>
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

<!-- Edit Project Modal -->
<?php if ($_SESSION['role'] === 'student'): ?>
<div class="modal fade" id="editProjectModal" tabindex="-1" role="dialog" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo h($project['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo h($project['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Objectives</label>
                        <textarea name="objectives" class="form-control" rows="3"><?php echo h($project['objectives']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Methodology</label>
                        <textarea name="methodology" class="form-control" rows="3"><?php echo h($project['methodology']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Expected Outcomes</label>
                        <textarea name="expected_outcomes" class="form-control" rows="3"><?php echo h($project['expected_outcomes']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="draft" <?php echo $project['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="proposed" <?php echo $project['status'] === 'proposed' ? 'selected' : ''; ?>>Proposed</option>
                            <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $project['start_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $project['end_date']; ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_project" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 