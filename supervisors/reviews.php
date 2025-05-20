<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../auth/login.php');
    exit;
}
require_once '../config/database.php';

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'due_date';
$order = $_GET['order'] ?? 'asc';

// Build query based on filters
$query = '
    SELECT 
        s.stage_id,
        s.title as stage_title,
        s.due_date,
        s.status,
        p.project_id,
        p.title as project_title,
        st.student_id,
        st.full_name as student_name,
        u.uploaded_at,
        r.grade,
        r.feedback
    FROM stages s
    JOIN projects p ON s.project_id = p.project_id
    JOIN students st ON p.student_id = st.student_id
    LEFT JOIN uploads u ON s.stage_id = u.stage_id
    LEFT JOIN reviews r ON s.stage_id = r.stage_id
    WHERE p.supervisor_id = ?
';

$params = [$_SESSION['user_id']];

if ($status !== 'all') {
    $query .= ' AND s.status = ?';
    $params[] = $status;
}

$query .= ' ORDER BY ';

switch ($sort) {
    case 'project':
        $query .= 'p.title ' . $order;
        break;
    case 'student':
        $query .= 'st.full_name ' . $order;
        break;
    case 'grade':
        $query .= 'r.grade ' . $order;
        break;
    default:
        $query .= 's.due_date ' . $order;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN s.status = "submitted" THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN s.status = "approved" THEN 1 ELSE 0 END) as approved,
        AVG(r.grade) as avg_grade
    FROM stages s
    JOIN projects p ON s.project_id = p.project_id
    LEFT JOIN reviews r ON s.stage_id = r.stage_id
    WHERE p.supervisor_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reviews - Project Tracker</title>
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
        .table {
            color: #f0f0f0;
        }
        .table thead th {
            border-bottom: 2px solid #4e73df;
            color: #fff;
        }
        .table td {
            border-top: 1px solid #444;
        }
        .badge {
            font-size: 0.9em;
            padding: 0.5em 0.8em;
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
                            <h1 class="m-0">Reviews</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <!-- Statistics -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Stages</h5>
                                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Reviews</h5>
                                    <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Approved Stages</h5>
                                    <h2 class="mb-0"><?php echo $stats['approved']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Average Grade</h5>
                                    <h2 class="mb-0"><?php echo number_format($stats['avg_grade'], 1); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Filters</h3>
                        </div>
                        <div class="card-body">
                            <form method="get" class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="submitted" <?php echo $status === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                            <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Sort By</label>
                                        <select name="sort" class="form-control">
                                            <option value="due_date" <?php echo $sort === 'due_date' ? 'selected' : ''; ?>>Due Date</option>
                                            <option value="project" <?php echo $sort === 'project' ? 'selected' : ''; ?>>Project</option>
                                            <option value="student" <?php echo $sort === 'student' ? 'selected' : ''; ?>>Student</option>
                                            <option value="grade" <?php echo $sort === 'grade' ? 'selected' : ''; ?>>Grade</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Order</label>
                                        <select name="order" class="form-control">
                                            <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>Ascending</option>
                                            <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>Descending</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Reviews Table -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Review List</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Stage</th>
                                            <th>Student</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reviews as $review): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($review['project_title']); ?></td>
                                                <td><?php echo htmlspecialchars($review['stage_title']); ?></td>
                                                <td><?php echo htmlspecialchars($review['student_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($review['due_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $review['status'] === 'approved' ? 'success' : 
                                                            ($review['status'] === 'submitted' ? 'warning' : 'secondary'); 
                                                    ?>">
                                                        <?php echo ucfirst($review['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($review['grade']): ?>
                                                        <?php echo $review['grade']; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="review.php?stage_id=<?php echo $review['stage_id']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> Review
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($reviews)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No reviews found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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