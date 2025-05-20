<?php
require_once '../../config/database.php';
require_once '../../includes/role_helpers.php';
session_start();

// Permission check: Only allow users with 'role.manage' permission
require_permission($db, 'role.manage');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid request!');
    }

    // Input sanitization and XSS prevention
    $role_name = trim($_POST['name']);
    $role_name = htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8');

    // Insert new role
    $stmt = $db->prepare("INSERT INTO roles (name) VALUES (?)");
    $stmt->execute([$role_name]);

    // Redirect to roles index
    header('Location: index.php');
    exit;
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../../includes/header.php';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Add Role</h1>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
              <label for="name">Role Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="index.php" class="btn btn-default">Cancel</a>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
