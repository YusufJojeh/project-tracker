<?php
session_start();

// Include necessary files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/notifications.php';

// Ensure the user is logged in and has 'manage_users' permission
if ( !isset( $_SESSION[ 'user_id' ] ) || !isset( $_SESSION[ 'role' ] ) || $_SESSION[ 'role' ] !== 'admin' ) {
    header( 'Location: ../auth/login.php' );
    exit();
}

// Navbar notifications
$unreadCount   = get_unread_notifications_count( $pdo, $_SESSION[ 'user_id' ] );
$notifications = get_recent_notifications( $pdo, $_SESSION[ 'user_id' ], 5 );

// Page metadata ( for header.php )
$page_title   = 'Roles Management';
$current_page = 'roles';

// Fetch distinct roles from the roles table
try {
    $stmt = $pdo->query( 'SELECT DISTINCT name FROM roles ORDER BY name' );
    $roles = $stmt->fetchAll( PDO::FETCH_COLUMN );
} catch ( PDOException $e ) {
    die( 'Error fetching roles: ' . $e->getMessage() );
}

// Render AdminLTE header ( includes <head>, navbar, sidebar )
include __DIR__ . '/../../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'UTF-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
<title><?php echo $page_title;
?> - Project Tracker</title>
<!-- Google Font: Source Sans Pro -->
<link rel = 'stylesheet'
href = 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
<!-- Font Awesome -->
<link rel = 'stylesheet' href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
<!-- Ionicons -->
<link rel = 'stylesheet' href = 'https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css'>
<!-- Theme style -->
<link rel = 'stylesheet' href = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
</head>

<body class = 'hold-transition sidebar-mini'>
<div class = 'wrapper'>
<!-- Navbar -->

<!-- Main Sidebar Container -->

<!-- Content Wrapper -->
<div class = 'content-wrapper'>
<!-- Page header -->
<section class = 'content-header'>
<div class = 'container-fluid'>
<h1><?php echo $page_title;
?></h1>
<a href = 'create.php' class = 'btn btn-primary'>
<i class = 'fas fa-plus'></i> Create Role
</a>
</div>
</section>

<!-- Main content -->
<section class = 'content'>
<div class = 'container-fluid'>
<div class = 'card'>
<div class = 'card-header'>
<h3 class = 'card-title'>Available Roles</h3>
</div>
<div class = 'card-body p-0'>
<table class = 'table table-bordered table-striped mb-0'>
<thead>
<tr>
<th>Role Name</th>
<th class = 'text-center' style = 'width:140px'>Actions</th>
</tr>
</thead>
<tbody>
<?php if ( empty( $roles ) ): ?>
<tr>
<td colspan = '2' class = 'text-center text-muted'>No roles defined.</td>
</tr>
<?php else: ?>
<?php foreach ( $roles as $role ): ?>
<tr>
<td><?php echo htmlspecialchars( ucfirst( $role ), ENT_QUOTES, 'UTF-8' );
?></td>
<td class = 'text-center'>
<a href = "permissions.php?role=<?php echo urlencode($role); ?>" class = 'btn btn-warning btn-sm'>
<i class = 'fas fa-key'></i> Permissions
</a>
</td>
</tr>
<?php endforeach;
?>
<?php endif;
?>
</tbody>
</table>
</div>
</div>
</div>
</section>
</div>

<?php
// Render AdminLTE footer ( includes closing </body></html> )
include __DIR__ . '/../../includes/admin_footer.php';
?>

</div>

<!-- Scripts -->
<script src = 'https://code.jquery.com/jquery-3.6.0.min.js'></script>
<script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js'></script>
<script src = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js'></script>
</body>

</html>