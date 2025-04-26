<?php
session_start();
require __DIR__ . '/../config/database.php';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $stmt = $pdo->prepare( 'SELECT * FROM users WHERE username = ?' );
    $stmt->execute( [ $_POST[ 'username' ] ] );
    $user = $stmt->fetch();
    if ( $user && password_verify( $_POST[ 'password' ], $user[ 'password' ] ) ) {
        $_SESSION[ 'user_id' ]   = $user[ 'user_id' ];
        $_SESSION[ 'role' ]      = $user[ 'role' ];
        $_SESSION[ 'full_name' ] = $user[ 'full_name' ];
        header( 'Location: ../public/index.php' );
        exit;
    } else {
        $error = 'اسم المستخدم أو كلمة المرور خاطئة';
    }
}
?>
<!doctype html>
<html lang='ar' dir='rtl'>

<head>
  <meta charset='utf-8'>
  <link href='../public/css/bootstrap.min.css' rel='stylesheet'>
</head>

<body class='d-flex align-items-center vh-100 bg-light'>
  <form method='post' class='mx-auto p-4 bg-white rounded shadow' style='width:320px;'>
    <h4 class='mb-3 text-center'>تسجيل الدخول</h4>
    <?php if ( !empty( $error ) ): ?>
    <div class='alert alert-danger'>
      <? echo $error;
?>
    </div>
    <?php endif;
?>
    <div class='mb-3'><input name='username' class='form-control' placeholder='اسم المستخدم' required></div>
    <div class='mb-3'><input name='password' type='password' class='form-control' placeholder='كلمة المرور' required>
    </div>
    <button class='btn btn-primary w-100'>دخول</button>
  </form>
  <script src='/public/js/bootstrap.bundle.min.js'></script>
</body>

</html>