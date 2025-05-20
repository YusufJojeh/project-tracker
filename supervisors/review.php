<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../auth/login.php');
    exit;
}
require_once '../config/database.php';

$error = '';
$success = '';

$stage_id = $_GET['stage_id'] ?? 0;

// Get stage details
$stmt = $pdo->prepare('
    SELECT 
        s.stage_id,
        s.title as stage_title,
        s.description as stage_description,
        s.due_date,
        s.status,
        p.project_id,
        p.title as project_title,
        st.student_id,
        st.full_name as student_name,
        u.file_name,
        u.file_path,
        u.uploaded_at,
        r.grade,
        r.feedback
    FROM stages s
    JOIN projects p ON s.project_id = p.project_id
    JOIN students st ON p.student_id = st.student_id
    LEFT JOIN uploads u ON s.stage_id = u.stage_id
    LEFT JOIN reviews r ON s.stage_id = r.stage_id
    WHERE s.stage_id = ? AND p.supervisor_id = ?
');
$stmt->execute([$stage_id, $_SESSION['user_id']]);
$stage = $stmt->fetch();

if (!$stage) {
    header('Location: dashboard.php');
    exit;
}

// Get comments
$stmt = $pdo->prepare('
    SELECT 
        c.comment,
        c.created_at,
        u.username,
        u.role
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.project_id = ?
    ORDER BY c.created_at DESC
');
$stmt->execute([$stage['project_id']]);
$comments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = $_POST['grade'] ?? '';
    $feedback = trim($_POST['feedback'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $action = $_POST['action'] ?? '';

    if (empty($grade) && $action === 'approve') {
        $error = 'Please provide a grade';
    } else {
        try {
            $pdo->beginTransaction();

            if ($action === 'approve') {
                // Update or insert review
                $stmt = $pdo->prepare('
                    INSERT INTO reviews (stage_id, supervisor_id, grade, feedback)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    grade = VALUES(grade),
                    feedback = VALUES(feedback)
                ');
                $stmt->execute([$stage_id, $_SESSION['user_id'], $grade, $feedback]);

                // Update stage status
                $stmt = $pdo->prepare('UPDATE stages SET status = ? WHERE stage_id = ?');
                $stmt->execute(['approved', $stage_id]);
            } elseif ($action === 'reject') {
                // Update stage status
                $stmt = $pdo->prepare('UPDATE stages SET status = ? WHERE stage_id = ?');
                $stmt->execute(['pending', $stage_id]);
            }

            // Add comment if provided
            if (!empty($comment)) {
                $stmt = $pdo->prepare('INSERT INTO comments (project_id, user_id, comment) VALUES (?, ?, ?)');
                $stmt->execute([$stage['project_id'], $_SESSION['user_id'], $comment]);
            }

            $pdo->commit();
            $success = 'Review submitted successfully!';
            
            // Refresh page data
            header('Location: review.php?stage_id=' . $stage_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to submit review. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review Stage - Project Tracker</title>
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <style>
        body {
            background: #1e1e2f;
            color: #f0f0f0;
        }
        .content-wrapper {
            background: #1e1e2f;
        }
        .card {
            background: #2f2f3f;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: #3b3b4d;
            border-bottom: 2px solid #4e73df;
            color: #fff;
        }
        .btn-primary {
            background: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background: #2e59d9;
            border-color: #2e59d9;
        }
        .btn-danger {
            background: #e74a3b;
            border-color: #e74a3b;
        }
        .btn-danger:hover {
            background: #be2617;
            border-color: #be2617;
        }
        .form-control {
            background: #44475a;
            border: 1px solid #555;
            color: #fff;
        }
        .form-control:focus {
            background: #545776;
            border-color: #4e73df;
            color: #fff;
            box-shadow: none;
        }
        .badge {
            font-size: 0.9em;
            padding: 0.5em 0.8em;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-light" style="background:#2e2e3d;">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-light" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link text-light" href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="#" class="brand-link text-center" style="background:#4e73df;">
                <span class="brand-text font-weight-light">Project Tracker</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="projects.php" class="nav-link">
                                <i class="nav-icon fas fa-folder"></i>
                                <p>Projects</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reviews.php" class="nav-link active">
                                <i class="nav-icon fas fa-tasks"></i>
                                <p>Reviews</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>Profile</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Review Stage</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Stage Details -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Stage Details</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Project:</strong> <?php echo htmlspecialchars($stage['project_title']); ?></p>
                                            <p><strong>Stage:</strong> <?php echo htmlspecialchars($stage['stage_title']); ?></p>
                                            <p><strong>Student:</strong> <?php echo htmlspecialchars($stage['student_name']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($stage['due_date'])); ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge badge-<?php 
                                                    echo $stage['status'] === 'approved' ? 'success' : 
                                                        ($stage['status'] === 'submitted' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($stage['status']); ?>
                                                </span>
                                            </p>
                                            <p><strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($stage['uploaded_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php if ($stage['stage_description']): ?>
                                        <div class="mt-3">
                                            <h5>Description</h5>
                                            <p><?php echo nl2br(htmlspecialchars($stage['stage_description'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Review Form -->
                            <?php if ($stage['status'] === 'submitted'): ?>
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h3 class="card-title">Submit Review</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($error): ?>
                                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                        <?php endif; ?>
                                        <?php if ($success): ?>
                                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                        <?php endif; ?>
                                        <form method="post">
                                            <div class="form-group">
                                                <label>Grade (0-100)</label>
                                                <input type="number" name="grade" class="form-control" min="0" max="100" 
                                                       value="<?php echo htmlspecialchars($stage['grade'] ?? ''); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Feedback</label>
                                                <textarea name="feedback" class="form-control" rows="4" required><?php 
                                                    echo htmlspecialchars($stage['feedback'] ?? ''); 
                                                ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>Add Comment (Optional)</label>
                                                <textarea name="comment" class="form-control" rows="3"></textarea>
                                            </div>
                                            <div class="btn-group">
                                                <button type="submit" name="action" value="approve" class="btn btn-primary">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <!-- File Download -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Submitted File</h3>
                                </div>
                                <div class="card-body">
                                    <?php if ($stage['file_name']): ?>
                                        <p><strong>File:</strong> <?php echo htmlspecialchars($stage['file_name']); ?></p>
                                        <a href="../uploads/<?php echo $stage['student_id']; ?>/<?php echo $stage['file_path']; ?>" 
                                           class="btn btn-primary btn-block" download>
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">No file submitted</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Comments -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h3 class="card-title">Comments</h3>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($comments)): ?>
                                        <p class="text-muted">No comments yet</p>
                                    <?php else: ?>
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
</body>
</html> 