<?php
require_once '../../config/database.php';
require_once '../../includes/role_helpers.php';
session_start();
require_permission($db, 'role.manage');

// Get and validate role ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch role by ID
$stmt = $db->prepare("SELECT * FROM roles WHERE id=?");
$stmt->execute([$id]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    die('Role not found.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid request!');
    }

    $role_name = trim($_POST['name']);
    $role_name = htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8');

    $update = $db->prepare("UPDATE roles SET name=? WHERE id=?");
    $update->execute([$role_name, $id]);

    header('Location: index.php');
    exit;
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include '../../includes/header.php';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Edit Role</h1>
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
              <input type="text" class="form-control" id="name" name="name"
                value="<?php echo htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-default">Cancel</a>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<?php include '../../includes/footer.php'; ?>
