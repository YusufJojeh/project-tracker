<?php
// supervisors/profile.php
session_start();

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../auth/login.php');
    exit;
}
$supervisorId = (int)$_SESSION['user_id'];

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/notifications.php';

// Fetch supervisor record
$stmt = $pdo->prepare("SELECT username, email, password, role, created_at FROM users WHERE user_id = ?");
$stmt->execute([$supervisorId]);
$supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$supervisor) {
    die('Access denied.');
}

$unreadCount = get_unread_notifications_count($pdo, $supervisorId);

// Handle profile update
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail    = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    // Basic validation
    if ($newUsername === '' || $newEmail === '') {
        $error = "Username and Email cannot be empty.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Check unique username/email except self
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $check->execute([$newUsername, $newEmail, $supervisorId]);
        if ($check->fetchColumn() > 0) {
            $error = "Username or email is already taken.";
        }
        // Password (if changing)
        elseif ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "Passwords do not match.";
            } else {
                // Update all fields including password
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET username=?, email=?, password=? WHERE user_id=?");
                $upd->execute([$newUsername, $newEmail, $hashed, $supervisorId]);
                $success = "Profile and password updated successfully!";
                $supervisor['username'] = $newUsername;
                $supervisor['email'] = $newEmail;
            }
        } else {
            // Update username/email only
            $upd = $pdo->prepare("UPDATE users SET username=?, email=? WHERE user_id=?");
            $upd->execute([$newUsername, $newEmail, $supervisorId]);
            $success = "Profile updated successfully!";
            $supervisor['username'] = $newUsername;
            $supervisor['email'] = $newEmail;
        }
    }
}

// Fetch supervised projects
$projStmt = $pdo->prepare("
    SELECT p.project_id, p.title, p.created_at, u.username AS student_name
    FROM projects p
    JOIN users u ON p.student_id = u.user_id
    WHERE p.supervisor_id = ?
    ORDER BY p.created_at DESC
");
$projStmt->execute([$supervisorId]);
$projects = $projStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Supervisor Profile &mdash; Project Tracker</title>
  <link rel='stylesheet' href='../plugins/fontawesome-free/css/all.min.css'>
  <link rel='stylesheet' href='../plugins/overlayScrollbars/css/OverlayScrollbars.min.css'>
  <link rel='stylesheet' href='../dist/css/adminlte.min.css'>
</head>

<body class='hold-transition sidebar-mini layout-fixed'>
  <div class='wrapper'>

    <!-- Navbar -->
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#'><i class='fas fa-bars'></i></a>
        </li>
      </ul>
      <ul class='navbar-nav ml-auto'>
        <li class='nav-item dropdown'>
          <a class='nav-link' data-toggle='dropdown' href='#'>
            <i class='far fa-bell'></i>
            <?php if ($unreadCount): ?>
            <span class='badge badge-warning navbar-badge'><?php echo $unreadCount; ?></span>
            <?php endif; ?>
          </a>
          <div class='dropdown-menu dropdown-menu-lg dropdown-menu-right'>
            <span class='dropdown-item dropdown-header'><?php echo $unreadCount; ?> New</span>
            <div class='dropdown-divider'></div>
            <a href='notifications.php' class='dropdown-item dropdown-footer'>See All Notifications</a>
          </div>
        </li>
        <li class='nav-item'>
          <a class='nav-link' href='../auth/logout.php'><i class='fas fa-sign-out-alt'></i></a>
        </li>
      </ul>
    </nav>

    <!-- Sidebar -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <a href='dashboard.php' class='brand-link text-center'>
        <span class='brand-text font-weight-light'>Project Tracker</span>
      </a>
      <div class='sidebar'>
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column' data-widget='treeview'>
            <li class='nav-item'><a href='dashboard.php' class='nav-link'><i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a></li>
            <li class='nav-item'><a href='projects.php' class='nav-link'><i class='nav-icon fas fa-project-diagram'></i>
                <p>Projects</p>
              </a></li>
            <li class='nav-item'><a href='notifications.php' class='nav-link'><i class='nav-icon far fa-bell'></i>
                <p>Notifications</p>
              </a></li>
            <li class='nav-item'><a href='profile.php' class='nav-link active'><i class='nav-icon fas fa-user'></i>
                <p>Profile</p>
              </a></li>
            <li class='nav-item'><a href='../auth/logout.php' class='nav-link'><i
                  class='nav-icon fas fa-sign-out-alt'></i>
                <p>Logout</p>
              </a></li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content Wrapper -->
    <div class='content-wrapper'>
      <section class='content-header'>
        <div class='container-fluid'>
          <h1>My Profile</h1>
        </div>
      </section>

      <section class='content'>
        <div class='container-fluid' style="max-width:650px;">
          <div class='card'>
            <div class='card-header bg-primary'>
              <h3 class='card-title'><i class='fas fa-user-tie'></i> Supervisor Profile</h3>
            </div>
            <div class='card-body'>
              <?php if ($error): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
              <?php elseif ($success): ?>
              <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
              <?php endif; ?>
              <?php if (!isset($_GET['edit'])): ?>
              <p><strong>Username:</strong> <?php echo htmlspecialchars($supervisor['username']); ?></p>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($supervisor['email']); ?></p>
              <p><strong>Member Since:</strong> <?php echo (new DateTime($supervisor['created_at']))->format('Y-m-d') ?>
              </p>
              <a href="profile.php?edit=1" class="btn btn-warning"><i class="fas fa-edit"></i> Edit Profile</a>
              <?php else: ?>
              <form method="post" autocomplete="off">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" name="username" class="form-control"
                    value="<?php echo htmlspecialchars($supervisor['username']); ?>" required>
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" class="form-control"
                    value="<?php echo htmlspecialchars($supervisor['email']); ?>" required>
                </div>
                <hr>
                <h6>Change Password (optional)</h6>
                <div class="form-group">
                  <label>New Password <small class="text-muted">(leave blank to keep unchanged)</small></label>
                  <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                  <label>Confirm New Password</label>
                  <input type="password" name="password_confirm" class="form-control" minlength="6"
                    autocomplete="new-password">
                </div>
                <button class="btn btn-primary" name="edit_profile"><i class="fas fa-save"></i> Save Changes</button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
              </form>
              <?php endif; ?>
            </div>
          </div>

          <!-- Supervised Projects -->
          <div class='card'>
            <div class='card-header bg-primary'>
              <h3 class='card-title'><i class='fas fa-chalkboard-teacher'></i> Supervised Projects</h3>
            </div>
            <div class='card-body p-0'>
              <?php if (empty($projects)): ?>
              <div class='p-3 text-center text-muted'>You are not supervising any projects yet.</div>
              <?php else: ?>
              <ul class='list-group list-group-flush'>
                <?php foreach ($projects as $p): ?>
                <li class='list-group-item d-flex justify-content-between align-items-center'>
                  <a href="project_view.php?project_id=<?php echo $p['project_id'] ?>">
                    <?php echo htmlspecialchars($p['title']) ?>
                  </a>
                  <span class='text-muted'>
                    <?php echo (new DateTime($p['created_at']))->format('Y-m-d') ?>
                  </span>
                </li>
                <?php endforeach ?>
              </ul>
              <?php endif ?>
            </div>
          </div>
        </div>
      </section>
    </div>
    <!-- /.content-wrapper -->

    <?php include __DIR__.'/../includes/footer.php'; ?>
  </div>
  <!-- ./wrapper -->

  <script src='../plugins/jquery/jquery.min.js'></script>
  <script src='../plugins/bootstrap/js/bootstrap.bundle.min.js'></script>
  <script src='../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js'></script>
  <script src='../dist/js/adminlte.min.js'></script>
</body>

</html>