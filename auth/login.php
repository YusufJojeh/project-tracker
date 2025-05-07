<?php
session_start();
require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([ $_POST['username'] ]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        if ($user['role'] === 'supervisor') {
            header('Location: ../supervisors/dashboard.php');
        } else {
            header('Location: ../students/dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!doctype html>
<html lang="en" dir="ltr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login – MyApp</title>
  <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <style>
  html,
  body {
    margin: 0;
    padding: 0;
    background: #1e1e2f;
    color: #f0f0f0;
    /* Center flex container */
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
  }

  .login-card {
    width: 360px;
    background: #2f2f3f;
    border: none;
    border-radius: .5rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
  }

  .login-card .card-header {
    background: #4e73df;
    color: #fff;
    text-align: center;
    font-size: 1.25rem;
    border-top-left-radius: .5rem;
    border-top-right-radius: .5rem;
  }

  .login-card .card-body {
    padding: 2rem;
  }

  .login-card .form-control {
    background: #44475a;
    border: 1px solid #555;
    color: #fff;
  }

  .login-card .form-control:focus {
    background: #545776;
    border-color: #4e73df;
    box-shadow: none;
  }

  .login-card .btn-primary {
    background: #4e73df;
    border-color: #4e73df;
  }

  .login-card .btn-primary:hover {
    background: #2e59d9;
    border-color: #2e59d9;
  }

  .alert {
    background: #e74a3b;
    border: none;
    color: #fff;
  }

  .input-group-text.toggle-password {
    cursor: pointer;
    background: #44475a;
    border-left: none;
  }
  </style>
</head>

<body>
  <div class="card login-card">
    <div class="card-header"><i class="fas fa-sign-in-alt"></i> Sign In</div>
    <div class="card-body">
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
      </div>
      <?php endif; ?>
      <form method="post" novalidate>
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input id="username" name="username" type="text" class="form-control" placeholder="Enter username" required>
          </div>
        </div>
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input id="password" name="password" type="password" class="form-control" placeholder="Enter password"
              required>
            <span class="input-group-text toggle-password">
              <i id="eye-icon" class="fas fa-eye"></i>
            </span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
      </form>
    </div>
  </div>

  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="../dist/js/adminlte.min.js"></script>
  <script>
  document.querySelector('.toggle-password').addEventListener('click', function() {
    const pwField = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    if (pwField.type === 'password') {
      pwField.type = 'text';
      eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      pwField.type = 'password';
      eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  });
  </script>
</body>

</html>