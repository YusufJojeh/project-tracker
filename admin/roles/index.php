<?php
require_once '../../config/database.php';
require_once '../../includes/role_helpers.php';

if ( session_status() === PHP_SESSION_NONE ) session_start();
require_permission( $pdo, 'manage_users' );
// Only allow users with manage_users permission

// ENUM roles
$roles = [ 'admin', 'supervisor', 'student' ];

include '../../includes/header.php';
?>
<div class = 'content-wrapper'>
<div class = 'content-header'>
<div class = 'container-fluid'>
<div class = 'row mb-2'>
<div class = 'col-sm-6'>
<h1 class = 'm-0'>Roles Management</h1>
</div>
</div>
</div>
</div>
<section class = 'content'>
<div class = 'container-fluid'>
<div class = 'card'>
<div class = 'card-body'>
<table class = 'table table-bordered table-striped'>
<thead>
<tr>
<th>Role Name</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ( $roles as $role ): ?>
<tr>
<td><?php echo htmlspecialchars( ucfirst( $role ) );
?></td>
<td>
<a href = "permissions.php?role=<?php echo urlencode($role); ?>" class = 'btn btn-warning btn-sm'>
<i class = 'fas fa-key'></i> Permissions
</a>
</td>
</tr>
<?php endforeach;
?>
</tbody>
</table>
</div>
</div>
</div>
</section>
</div>
<?php include '../../includes/footer.php';
?>
