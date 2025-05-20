<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only allow admins
if ( !isset( $_SESSION[ 'user_id' ] ) || $_SESSION[ 'role' ] !== 'admin' ) {
    header( 'Location: ../auth/login.php' );
    exit();
}

// Flash message handler

function set_flash( $msg, $type = 'success' ) {
    $_SESSION[ 'flash_msg' ] = $msg;
    $_SESSION[ 'flash_type' ] = $type;
}

function get_flash() {
    if ( isset( $_SESSION[ 'flash_msg' ] ) ) {
        $msg = $_SESSION[ 'flash_msg' ];
        $type = $_SESSION[ 'flash_type' ] ?? 'success';
        unset( $_SESSION[ 'flash_msg' ], $_SESSION[ 'flash_type' ] );
        return "<div class='alert alert-$type'>$msg</div>";
    }
    return '';
}

// Handle add department
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] === 'add' ) {
    $name = trim( $_POST[ 'name' ] );
    if ( $name === '' ) {
        set_flash( 'Department name cannot be empty.', 'danger' );
    } else {
        $stmt = $pdo->prepare( 'SELECT COUNT(*) FROM departments WHERE name = ?' );
        $stmt->execute( [ $name ] );
        if ( $stmt->fetchColumn() > 0 ) {
            set_flash( 'Department name already exists.', 'danger' );
        } else {
            $stmt = $pdo->prepare( 'INSERT INTO departments (name, created_at) VALUES (?, NOW())' );
            if ( $stmt->execute( [ $name ] ) ) {
                set_flash( 'Department added successfully.', 'success' );
            } else {
                set_flash( 'Failed to add department.', 'danger' );
            }
        }
    }
    header( 'Location: departments.php' );
    exit();
}

// Handle delete department
if ( isset( $_GET[ 'delete' ] ) ) {
    $dep_id = intval( $_GET[ 'delete' ] );
    $stmt = $pdo->prepare( 'DELETE FROM departments WHERE department_id = ?' );
    if ( $stmt->execute( [ $dep_id ] ) ) {
        set_flash( 'Department deleted.', 'success' );
    } else {
        set_flash( 'Failed to delete department.', 'danger' );
    }
    header( 'Location: departments.php' );
    exit();
}

// Handle edit department
if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] === 'edit' ) {
    $dep_id = intval( $_POST[ 'department_id' ] );
    $name = trim( $_POST[ 'name' ] );
    if ( $name === '' ) {
        set_flash( 'Department name cannot be empty.', 'danger' );
    } else {
        $stmt = $pdo->prepare( 'SELECT COUNT(*) FROM departments WHERE name = ? AND department_id != ?' );
        $stmt->execute( [ $name, $dep_id ] );
        if ( $stmt->fetchColumn() > 0 ) {
            set_flash( 'Department name already exists.', 'danger' );
        } else {
            $stmt = $pdo->prepare( 'UPDATE departments SET name = ? WHERE department_id = ?' );
            if ( $stmt->execute( [ $name, $dep_id ] ) ) {
                set_flash( 'Department updated successfully.', 'success' );
            } else {
                set_flash( 'Failed to update department.', 'danger' );
            }
        }
    }
    header( 'Location: departments.php' );
    exit();
}

// Get departments list
$stmt = $pdo->query( 'SELECT * FROM departments ORDER BY created_at DESC' );
$departments = $stmt->fetchAll( PDO::FETCH_ASSOC );

// Edit mode
$edit_department = null;
if ( isset( $_GET[ 'edit' ] ) ) {
    $edit_id = intval( $_GET[ 'edit' ] );
    foreach ( $departments as $dep ) {
        if ( $dep[ 'department_id' ] == $edit_id ) {
            $edit_department = $dep;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'UTF-8'>
<title>Departments Management - Project Tracker</title>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1'>
<!-- AdminLTE CSS -->
<link rel = 'stylesheet' href = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
<link rel = 'stylesheet' href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
</head>

<body class = 'hold-transition sidebar-mini'>
<div class = 'wrapper'>
<!-- Sidebar ( copy from your layout if needed ) -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = 'dashboard.php' class = 'brand-link'>
<img src = 'https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png' alt = 'AdminLTE Logo'

class = 'brand-image img-circle elevation-3' style = 'opacity: .8'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview' role = 'menu'>
<li class = 'nav-item'><a href = 'dashboard.php' class = 'nav-link'><i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a></li>
<li class = 'nav-item'><a href = 'users.php' class = 'nav-link'><i class = 'nav-icon fas fa-users'></i>
<p>Users</p>
</a></li>
<li class = 'nav-item'><a href = 'departments.php' class = 'nav-link active'><i

class = 'nav-icon fas fa-building'></i>
<p>Departments</p>
</a></li>
<li class = 'nav-item'><a href = './roles/index.php' class = 'nav-link'></i>
<p>Roles & Permissions</p>
</a></li>
</ul>
</nav>
</div>
</aside>

<div class = 'content-wrapper'>
<section class = 'content-header'>
<div class = 'container-fluid'>
<div class = 'row mb-2'>
<div class = 'col-sm-6'>
<h1>Departments Management</h1>
</div>
</div>
</div>
</section>

<section class = 'content'>
<div class = 'container-fluid'>
<?php echo get_flash();
?>
<div class = 'card'>
<div class = 'card-header'>
<?php if ( $edit_department ): ?>
<h3 class = 'card-title'>Edit Department</h3>
<?php else: ?>
<h3 class = 'card-title'>Add Department</h3>
<?php endif;
?>
</div>
<div class = 'card-body'>
<form method = 'post' action = ''>
<?php if ( $edit_department ): ?>
<input type = 'hidden' name = 'action' value = 'edit'>
<input type = 'hidden' name = 'department_id'
value = "<?php echo htmlspecialchars($edit_department['department_id']); ?>">
<div class = 'form-group'>
<label for = 'dep_name'>Department Name</label>
<input type = 'text' name = 'name' class = 'form-control' id = 'dep_name'
value = "<?php echo htmlspecialchars($edit_department['name']); ?>" required>
</div>
<button type = 'submit' class = 'btn btn-primary'><i class = 'fas fa-save'></i> Update</button>
<a href = 'departments.php' class = 'btn btn-secondary'>Cancel</a>
<?php else: ?>
<input type = 'hidden' name = 'action' value = 'add'>
<div class = 'form-group'>
<label for = 'dep_name'>Department Name</label>
<input type = 'text' name = 'name' class = 'form-control' id = 'dep_name' required>
</div>
<button type = 'submit' class = 'btn btn-primary'><i class = 'fas fa-plus'></i> Add</button>
<?php endif;
?>
</form>
</div>
</div>
<!-- List -->
<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'card-title'>Departments List</h3>
</div>
<div class = 'card-body table-responsive'>
<table class = 'table table-bordered table-hover'>
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Created At</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ( $departments as $dep ): ?>
<tr>
<td><?php echo htmlspecialchars( $dep[ 'department_id' ] );
?></td>
<td><?php echo htmlspecialchars( $dep[ 'name' ] );
?></td>
<td><?php echo htmlspecialchars( $dep[ 'created_at' ] );
?></td>
<td>
<a href = "departments.php?edit=<?php echo htmlspecialchars($dep['department_id']); ?>"

class = 'btn btn-info btn-sm'><i class = 'fas fa-edit'></i> Edit</a>
<a href = "departments.php?delete=<?php echo htmlspecialchars($dep['department_id']); ?>"

class = 'btn btn-danger btn-sm'
onclick = "return confirm('Are you sure you want to delete this department?');">
<i class = 'fas fa-trash'></i> Delete
</a>
</td>
</tr>
<?php endforeach;
?>
<?php if ( count( $departments ) == 0 ): ?>
<tr>
<td colspan = '4' class = 'text-center'>No departments found.</td>
</tr>
<?php endif;
?>
</tbody>
</table>
</div>
</div>
</div>
</section>
</div>
</div>
<!-- JS -->
<script src = 'https://code.jquery.com/jquery-3.6.0.min.js'></script>
<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js'></script>
<script src = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js'></script>
</body>

</html>