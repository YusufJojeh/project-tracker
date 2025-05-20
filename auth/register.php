<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role) || empty($full_name)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($role === 'student' && empty($student_number)) {
        $error = 'Student number is required for student accounts';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists';
        } else {
            try {
                $pdo->beginTransaction();

                // Create user
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $user_id = $pdo->lastInsertId();

                // Create role-specific record
                if ($role === 'student') {
                    $stmt = $pdo->prepare('INSERT INTO students (student_id, full_name, student_number, department) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$user_id, $full_name, $student_number, $department]);
                } elseif ($role === 'supervisor') {
                    $stmt = $pdo->prepare('INSERT INTO supervisors (supervisor_id, full_name, department) VALUES (?, ?, ?)');
                    $stmt->execute([$user_id, $full_name, $department]);
                }

                $pdo->commit();
                $success = 'Registration successful! You can now login.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Project Tracker</title>
    <link rel="stylesheet" href="../plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <style>
        body {
            background: #1e1e2f;
            color: #f0f0f0;
        }
        .register-box {
            margin-top: 5vh;
        }
        .card {
            background: #2f2f3f;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: #3b3b4d;
            border-bottom: 2px solid #4e73df;
            color: #fff;
        }
        .btn-primary {
            background: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background: #2e59d9;
            border-color: #2e59d9;
        }
        .form-control {
            background: #44475a;
            border: 1px solid #555;
            color: #fff;
        }
        .form-control:focus {
            background: #545776;
            border-color: #4e73df;
            color: #fff;
            box-shadow: none;
        }
    </style>
</head>
<body class="hold-transition">
    <div class="register-box">
        <div class="card">
            <div class="card-header text-center">
                <h1 class="h4">Create Account</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="supervisor">Supervisor</option>
                        </select>
                    </div>
                    <div class="form-group student-fields" style="display: none;">
                        <label>Student Number</label>
                        <input type="text" name="student_number" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Register</button>
                        </div>
                    </div>
                </form>
                <p class="mb-1 mt-3 text-center">
                    <a href="login.php" class="text-light">Already have an account? Sign In</a>
                </p>
            </div>
        </div>
    </div>
    <script src="../plugins/jquery/jquery.min.js"></script>
    <script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/adminlte.min.js"></script>
    <script>
        document.querySelector('select[name="role"]').addEventListener('change', function() {
            const studentFields = document.querySelector('.student-fields');
            studentFields.style.display = this.value === 'student' ? 'block' : 'none';
        });
    </script>
</body>
</html> 