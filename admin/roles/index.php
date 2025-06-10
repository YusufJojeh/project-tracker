<?php
// admin/roles/index.php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/notifications.php';

// Only users with the 'manage_users' permission
require_permission( $pdo, 'manage_users' );

// Navbar notifications
$unreadCount   = get_unread_notifications_count( $pdo, $_SESSION[ 'user_id' ] );
$notifications = get_recent_notifications( $pdo, $_SESSION[ 'user_id' ], 5 );

// Page metadata ( for header.php )
$page_title   = 'Roles Management';
$current_page = 'roles';

// Pull distinct roles ( strings ) from your role_permissions table
try {
    $stmt  = $pdo->query( 'SELECT DISTINCT role FROM role_permissions ORDER BY role' );
    $roles = $stmt->fetchAll( PDO::FETCH_COLUMN );
} catch ( PDOException $e ) {
    die( 'Error fetching roles: ' . $e->getMessage() );
}

// Render AdminLTE header ( includes <head>, navbar, sidebar )
// include __DIR__ . '/../../includes/header.php';
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Admin Dashboard - Project Tracker</title>
  <!-- Google Font: Source Sans Pro -->
  <link rel='stylesheet'
    href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
  <!-- Font Awesome -->
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
  <!-- Ionicons -->
  <link rel='stylesheet' href='https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css'>
  <!-- Theme style -->
  <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
  <!-- Custom CSS -->
  <style>
  .content-wrapper {
    background-color: #f4f6f9;
  }

  .card {
    box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
    margin-bottom: 1rem;
  }

  .small-box {
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
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
    color: rgba(0, 0, 0, .15);
    z-index: 0;
  }

  .small-box .icon>i {
    font-size: 70px;
    top: 20px;
  }
  </style>

<body class='hold-transition sidebar-mini'>
  <div class='wrapper'>
    <!-- Navbar -->
    <nav class='main-header navbar navbar-expand navbar-white navbar-light'>
      <!-- Left navbar links -->
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' data-widget='pushmenu' href='#' role='button'><i class='fas fa-bars'></i></a>
        </li>
      </ul>
      <!-- Right navbar links -->
      <ul class='navbar-nav ml-auto'>
        <li class='nav-item'>
          <a class='nav-link' href='../auth/logout.php'>
            <i class='fas fa-sign-out-alt'></i> Logout
          </a>
        </li>
      </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class='main-sidebar sidebar-dark-primary elevation-4'>
      <!-- Brand Logo -->
      <a href='dashboard.php' class='brand-link'>
        <img src='https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png' alt='AdminLTE Logo'
          class='brand-image img-circle elevation-3' style='opacity: .8'>
        <span class='brand-text font-weight-light'>Project Tracker</span>
      </a>
      <!-- Sidebar -->
      <div class='sidebar'>
        <!-- Sidebar Menu -->
        <nav class='mt-2'>
          <ul class='nav nav-pills nav-sidebar flex-column' data-widget='treeview' role='menu'>
            <li class='nav-item'>
              <a href='../dashboard.php' class='nav-link active'>
                <i class='nav-icon fas fa-tachometer-alt'></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='../users.php' class='nav-link'>
                <i class='nav-icon fas fa-users'></i>
                <p>Users</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='../departments.php' class='nav-link'>
                <i class='nav-icon fas fa-building'></i>
                <p>Departments</p>
              </a>
            </li>
            <li class='nav-item'>
              <a href='index.php' class='nav-link'>
                <i class='nav-icon fas fa-user-tag'></i>
                <p>Roles & Permissions</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>
    <div class='content-wrapper'>
      <!-- Page header -->
      <section class='content-header'>
        <div class='container-fluid'>
          <h1>Roles Management</h1>
          <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Role
          </a>
        </div>
      </section>

      <!-- Main content -->
      <section class='content'>
        <div class='container-fluid'>
          <div class='card'>
            <div class='card-header'>
              <h3 class='card-title'>Available Roles</h3>
            </div>
            <div class='card-body p-0'>
              <table class='table table-bordered table-striped mb-0'>
                <thead>
                  <tr>
                    <th>Role Name</th>
                    <th class='text-center' style='width:140px'>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ( empty( $roles ) ): ?>
                  <tr>
                    <td colspan='2' class='text-center text-muted'>No roles defined.</td>
                  </tr>
                  <?php else: ?>
                  <?php foreach ( $roles as $role ): ?>
                  <tr>
                    <td>
                      <?php echo htmlspecialchars( ucfirst( $role ), ENT_QUOTES, 'UTF-8' );
?>
                    </td>
                    <td class='text-center'>
                      <a href="permissions.php?role=<?php echo urlencode($role); ?>" class='btn btn-warning btn-sm'>
                        <i class='fas fa-key'></i> Permissions
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
include __DIR__ . '/../../includes/footer.php';
?>