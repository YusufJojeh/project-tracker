<?php
require_once '../../config/database.php';
require_once '../../includes/role_helpers.php';

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

include '../../includes/header.php';
?>
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
name = 'permissions[]' value = "<?php echo $p['permission_id']; ?>"
<?php if ( in_array( $p[ 'permission_id' ], $assigned ) ) echo 'checked';
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