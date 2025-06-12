<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Only users with the 'manage_users' permission
// You might want to remove the permission check for admin pages
// require_permission( $pdo, 'manage_users' );

// Available ENUM roles
$roles = [ 'admin', 'supervisor', 'student' ];
$role = isset( $_GET[ 'role' ] ) ? $_GET[ 'role' ] : '';
if ( !in_array( $role, $roles ) ) {
    die( 'Invalid role.' );
}

// Handle form submission
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    // Delete all existing permissions for this role
    $pdo->prepare( 'DELETE FROM role_permissions WHERE role_id = ?' )->execute( [ $role ] );

    // If there are new permissions selected, insert them
    if ( isset( $_POST[ 'permissions' ] ) ) {
        foreach ( $_POST[ 'permissions' ] as $pid ) {
            $pdo->prepare( 'INSERT INTO role_permissions (role, permission_id) VALUES (?, ?)' )->execute( [ $role, $pid ] );
        }
    }
    header( 'Location: index.php' );
    exit;
}

// Fetch all permissions
$all_perms = $pdo->query( 'SELECT id, name FROM permissions' )->fetchAll( PDO::FETCH_ASSOC );

// Fetch assigned permissions for this role
$stmt = $pdo->prepare( 'SELECT permission_id FROM role_permissions WHERE role_id = ?' );
$stmt->execute( [ $role ] );
$assigned = $stmt->fetchAll( PDO::FETCH_COLUMN );

// If there are no assigned permissions, make $assigned an empty array
if ( !$assigned ) {
    $assigned = [];
}

?>

<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'UTF-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
<title>Permissions for Role: <?php echo ucfirst( $role );
?> - Project Tracker</title>
<link rel = 'stylesheet'
href = 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
<link rel = 'stylesheet' href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
<link rel = 'stylesheet' href = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
</head>

<body class = 'hold-transition sidebar-mini'>
<div class = 'wrapper'>
<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link' data-widget = 'pushmenu' href = '#' role = 'button'><i class = 'fas fa-bars'></i></a>
</li>
</ul>
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../auth/logout.php'>
<i class = 'fas fa-sign-out-alt'></i> Logout
</a>
</li>
</ul>
</nav>

<!-- Sidebar -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = '../dashboard.php' class = 'brand-link'>
<img src = 'https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png' alt = 'AdminLTE Logo'

class = 'brand-image img-circle elevation-3' style = 'opacity: .8'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview' role = 'menu'>
<li class = 'nav-item'>
<a href = '../dashboard.php' class = 'nav-link active'>
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a>
</li>
<li class = 'nav-item'>
<a href = '../users.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-users'></i>
<p>Users</p>
</a>
</li>
<li class = 'nav-item'>
<a href = '../departments.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-building'></i>
<p>Departments</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'index.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-user-tag'></i>
<p>Roles & Permissions</p>
</a>
</li>
</ul>
</nav>
</div>
</aside>

<!-- Content Wrapper -->
<div class = 'content-wrapper'>
<section class = 'content-header'>
<div class = 'container-fluid'>
<h1>Permissions for Role: <?php echo ucfirst( $role );
?></h1>
</div>
</section>

<section class = 'content'>
<div class = 'container-fluid'>
<div class = 'card'>
<div class = 'card-body'>
<form method = 'post'>
<div class = 'form-group'>
<?php foreach ( $all_perms as $p ): ?>
<div class = 'custom-control custom-checkbox'>
<input type = 'checkbox' class = 'custom-control-input' id = "perm_<?php echo $p['id']; ?>"
name = 'permissions[]' value = "<?php echo $p['id']; ?>" <?php if ( in_array( $p[ 'id' ], $assigned ) ) echo 'checked';
?>>
<label class = 'custom-control-label' for = "perm_<?php echo $p['id']; ?>">
<?php echo htmlspecialchars( $p[ 'name' ] );
?>
</label>
</div>
<?php endforeach;
?>
</div>
<button type = 'submit' class = 'btn btn-primary'>Save</button>
<a href = 'index.php' class = 'btn btn-default'>Cancel</a>
</form>
</div>
</div>
</div>
</section>
</div>

<!-- Footer -->
<?php include '../../includes/footer.php';
?>
</div>

<script src = 'https://code.jquery.com/jquery-3.6.0.min.js'></script>
<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js'></script>
<script src = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js'></script>
</body>

</html>