<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../' . $_SESSION['role'] . 's/dashboard.php');
    exit;
}

require_once '../config/database.php';      // $pdo must be defined here
require_once '../includes/functions.php';   // for your `h()` function, etc.

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT user_id, username, email, password, role
                FROM users
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Optional: create notification if function exists
                if (function_exists('create_notification')) {
                    create_notification(
                        $pdo,
                        $user['user_id'],
                        'Login Successful',
                        'You have successfully logged in to your account.'
                    );
                }

                // Redirect to correct dashboard
                $role = $user['role'];
                if ($role === 'admin')       header('Location: ../admin/dashboard.php');
                elseif ($role === 'supervisor') header('Location: ../supervisors/dashboard.php');
                elseif ($role === 'student')    header('Location: ../students/dashboard.php');
                else $error = 'Invalid user role';

                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang='en'>

<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Login - Project Tracker</title>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
  <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css' rel='stylesheet'>
  <style>
  body {
    min-height: 100vh;
    background: linear-gradient(120deg, #283e51 0%, #485563 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cairo', 'Segoe UI', Arial, sans-serif;
  }

  .login-container {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
  }

  .login-card {
    border: none;
    border-radius: 2rem;
    backdrop-filter: blur(8px);
    background: rgba(38, 50, 56, 0.85);
    box-shadow: 0 8px 32px rgba(60, 60, 120, 0.18), 0 2px 6px #2223;
    padding: 2.5rem 2rem 2rem 2rem;
    color: #e8eaf6;
  }

  .login-card .card-header {
    border-bottom: none;
    background: none;
    text-align: center;
    padding-bottom: 0.5rem;
  }

  .login-card .brand-logo {
    font-size: 2.7rem;
    color: #4e73df;
    margin-bottom: 0.25rem;
    letter-spacing: 2px;
    font-weight: bold;
    text-shadow: 1px 2px 8px #2a2a40a0;
  }

  .form-label {
    font-weight: 500;
    color: #bbdefb;
    letter-spacing: .5px;
  }

  .form-control {
    background: rgba(44, 62, 80, .55);
    border-radius: 1rem;
    color: #fff;
    border: 1.5px solid #4e73df90;
    min-height: 48px;
    font-size: 1.13rem;
    box-shadow: none;
    transition: all .25s;
  }

  .form-control:focus {
    background: rgba(44, 62, 80, .7);
    border-color: #4e73df;
    color: #fff;
    outline: none;
    box-shadow: 0 0 0 2px #4e73df44;
  }

  .btn-primary {
    border-radius: 1rem;
    background: linear-gradient(90deg, #4e73df, #3056d3);
    border: none;
    font-weight: bold;
    letter-spacing: 1px;
    font-size: 1.15rem;
    min-height: 48px;
    margin-bottom: 0.2rem;
    box-shadow: 0 1px 6px #0002;
    transition: all .15s;
  }

  .btn-primary:hover,
  .btn-primary:focus {
    background: linear-gradient(90deg, #3056d3, #4e73df);
    color: #fff;
    transform: scale(1.02);
    box-shadow: 0 2px 18px #4e73df40;
  }

  .btn-link {
    color: #4e73df;
    text-decoration: underline;
    font-weight: 500;
  }

  .alert {
    border-radius: 1rem;
    border: none;
    padding: 1rem;
    margin-bottom: 1.25rem;
    font-size: 1rem;
  }

  @media (max-width: 500px) {
    .login-card {
      padding: 2rem 0.75rem;
      border-radius: 1rem;
    }

    .brand-logo {
      font-size: 2rem !important;
    }
  }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-card shadow">
      <div class="card-header pb-2">
        <span class="brand-logo">Project Tracker</span>
        <div class="fw-bold mb-2" style="font-size: 1.5rem;">
          <i class="fas fa-user-circle me-2"></i>Sign In
        </div>
      </div>
      <div class="card-body pt-2">
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>
          <div><?php echo h($error); ?></div>
        </div>
        <?php endif; ?>
        <form method="POST" action="" autocomplete="off">
          <div class="mb-3">
            <label for="username" class="form-label"><i class="fas fa-user me-2"></i>Username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo h($username); ?>"
              required autocomplete="username">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
            <input type="password" class="form-control" id="password" name="password" required
              autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-sign-in-alt me-2"></i>Login
          </button>
          <div class="d-flex justify-content-between mt-2">
            <a href="forgot_password.php" class="btn btn-link p-0"><i class="fas fa-key me-1"></i>Forgot Password?</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
</body>

</html>