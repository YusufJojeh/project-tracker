<?php
// Start session if not already started
if ( session_status() !== PHP_SESSION_ACTIVE ) {
    session_start();
}

// Redirect to login if user is not logged in
if ( empty( $_SESSION[ 'user_id' ] ) ) {
    header( 'Location: ../auth/login.php' );
    exit;
}

$role = $_SESSION[ 'role' ];
$name = $_SESSION[ 'full_name' ];
?>
<!doctype html>
<html lang='ar' dir='rtl'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <link href='../public/css/bootstrap.min.css' rel='stylesheet'>
  <title>نظام متابعة مشاريع التخرج</title>
</head>

<body class='bg-light'>
  <nav class='navbar navbar-expand-lg navbar-light bg-white shadow-sm'>
    <div class='container-fluid'>
      <a class='navbar-brand' href='#'>متابعة المشاريع</a>
      <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navMenu'>
        <span class='navbar-toggler-icon'></span>
      </button>
      <div class='collapse navbar-collapse' id='navMenu'>
        <ul class='navbar-nav me-auto'>
          <?php if ( $role === 'student' ): ?>
          <li class='nav-item'><a class='nav-link' href='../students/dashboard.php'>لوحة الطالب</a></li>
          <?php else: ?>
          <li class='nav-item'><a class='nav-link' href='../supervisors/dashboard.php'>لوحة المشرف</a></li>
          <?php endif;
?>
        </ul>
        <ul class='navbar-nav'>
          <li class='nav-item'><span class='nav-link'><?php echo $name;
?></span></li>
          <li class='nav-item'><a class='nav-link' href='../auth/logout.php'>تسجيل خروج</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class='container my-4'>
    <!-- Your content goes here -->
  </div>

  <script src='../public/js/bootstrap.bundle.min.js'></script>
</body>

</html>