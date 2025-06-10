<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ( session_status() === PHP_SESSION_NONE ) session_start();
require_permission( $pdo, 'manage_users' );

// Available ENUM roles
$roles = [ 'admin', 'supervisor', 'student' ];
$role = isset( $_GET[ 'role' ] ) ? $_GET[ 'role' ] : '';
if ( !in_array( $role, $roles ) ) {
    die( 'Invalid role.' );
}

// Handle form submission
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $pdo->prepare( 'DELETE FROM role_permissions WHERE role = ?' )->execute( [ $role ] );
    if ( isset( $_POST[ 'permissions' ] ) ) {
        foreach ( $_POST[ 'permissions' ] as $pid ) {
            $pdo->prepare( 'INSERT INTO role_permissions (role, permission_id) VALUES (?, ?)' )->execute( [ $role, $pid ] );
        }
    }
    header( 'Location: index.php' );
    exit;
}

// All permissions
$all_perms = $pdo->query( 'SELECT * FROM permissions' )->fetchAll( PDO::FETCH_ASSOC );

// Assigned permissions
$stmt = $pdo->prepare( 'SELECT permission_id FROM role_permissions WHERE role = ?' );
$stmt->execute( [ $role ] );
$assigned = $stmt->fetchAll( PDO::FETCH_COLUMN );

// include '../../includes/header.php';
?>
<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'UTF-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
<title>Admin Dashboard - Project Tracker</title>
<!-- Google Font: Source Sans Pro -->
<link rel = 'stylesheet'
href = 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
<!-- Font Awesome -->
<link rel = 'stylesheet' href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
<!-- Ionicons -->
<link rel = 'stylesheet' href = 'https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css'>
<!-- Theme style -->
<link rel = 'stylesheet' href = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
<!-- Custom CSS -->
<style>
.content-wrapper {
    background-color: #f4f6f9;
}

.card {
    box-shadow: 0 0 1px rgba( 0, 0, 0, .125 ), 0 1px 3px rgba( 0, 0, 0, .2 );
    margin-bottom: 1rem;
}

.small-box {
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 0 1px rgba( 0, 0, 0, .125 ), 0 1px 3px rgba( 0, 0, 0, .2 );
}

.small-box>.inner {
    padding: 10px;
}

.small-box h3 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
    white-space: nowrap;
    padding: 0;
}

.small-box p {
    font-size: 1rem;
}

.small-box .icon {
    color: rgba( 0, 0, 0, .15 );
    z-index: 0;
}

.small-box .icon>i {
    font-size: 70px;
    top: 20px;
}
</style>

<body class = 'hold-transition sidebar-mini'>
<div class = 'wrapper'>
<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<!-- Left navbar links -->
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link' data-widget = 'pushmenu' href = '#' role = 'button'><i class = 'fas fa-bars'></i></a>
</li>
</ul>
<!-- Right navbar links -->
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../auth/logout.php'>
<i class = 'fas fa-sign-out-alt'></i> Logout
</a>
</li>
</ul>
</nav>

<!-- Main Sidebar Container -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<!-- Brand Logo -->
<a href = 'dashboard.php' class = 'brand-link'>
<img src = 'https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png' alt = 'AdminLTE Logo'

class = 'brand-image img-circle elevation-3' style = 'opacity: .8'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<!-- Sidebar -->
<div class = 'sidebar'>
<!-- Sidebar Menu -->
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
<div class = 'content-wrapper'>
<div class = 'content-header'>
<div class = 'container-fluid'>
<h1 class = 'm-0'>Permissions for Role: <?php echo ucfirst( $role );
?></h1>
</div>
</div>
<section class = 'content'>
<div class = 'container-fluid'>
<div class = 'card'>
<div class = 'card-body'>
<form method = 'post'>
<div class = 'form-group'>
<?php foreach ( $all_perms as $p ): ?>
<div class = 'custom-control custom-checkbox'>
<input type = 'checkbox' class = 'custom-control-input' id = "perm_<?php echo $p['permission_id']; ?>"
name = 'permissions[]' value = "<?php echo $p['permission_id']; ?>" <?php if ( in_array( $p[ 'permission_id' ], $assigned ) ) echo 'checked';
?>>
<label class = 'custom-control-label' for = "perm_<?php echo $p['permission_id']; ?>">
<?php echo htmlspecialchars( $p[ 'name' ] );
?> -
<small><?php echo htmlspecialchars( $p[ 'description' ] );
?></small>
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
<?php include '../../includes/footer.php';
?>