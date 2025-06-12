<?php
// admin_header.php
?>

<!DOCTYPE html>
<html lang = 'en'>

<head>
<meta charset = 'UTF-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
<title>Admin Dashboard - Project Tracker</title>
<!-- Google Font: Source Sans Pro -->
<link rel = 'stylesheet'
href = 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback'>
<!-- Font Awesome -->
<link rel = 'stylesheet' href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
<!-- Ionicons -->
<link rel = 'stylesheet' href = 'https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css'>
<!-- Theme style -->
<link rel = 'stylesheet' href = 'https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
<!-- Custom CSS -->
<style>
.content-wrapper {
    background-color: #f4f6f9;
}

.card {
    box-shadow: 0 0 1px rgba( 0, 0, 0, .125 ), 0 1px 3px rgba( 0, 0, 0, .2 );
    margin-bottom: 1rem;
}

.small-box {
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 0 1px rgba( 0, 0, 0, .125 ), 0 1px 3px rgba( 0, 0, 0, .2 );
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
    color: rgba( 0, 0, 0, .15 );
    z-index: 0;
}

.small-box .icon>i {
    font-size: 70px;
    top: 20px;
}
</style>
</head>

<body class = 'hold-transition sidebar-mini'>
<div class = 'wrapper'>
<!-- Navbar -->
<nav class = 'main-header navbar navbar-expand navbar-white navbar-light'>
<!-- Left navbar links -->
<ul class = 'navbar-nav'>
<li class = 'nav-item'>
<a class = 'nav-link' data-widget = 'pushmenu' href = '#' role = 'button'><i class = 'fas fa-bars'></i></a>
</li>
</ul>
<!-- Right navbar links -->
<ul class = 'navbar-nav ml-auto'>
<li class = 'nav-item'>
<a class = 'nav-link' href = '../auth/logout.php'>
<i class = 'fas fa-sign-out-alt'></i> Logout
</a>
</li>
</ul>
</nav>

<!-- Main Sidebar Container -->
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<!-- Brand Logo -->
<a href = 'dashboard.php' class = 'brand-link'>
<img src = 'https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png' alt = 'AdminLTE Logo'

class = 'brand-image img-circle elevation-3' style = 'opacity: .8'>
<span class = 'brand-text font-weight-light'>Project Tracker</span>
</a>
<!-- Sidebar -->
<div class = 'sidebar'>
<!-- Sidebar Menu -->
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview' role = 'menu'>
<li class = 'nav-item'>
<a href = 'dashboard.php' class = 'nav-link active'>
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'users.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-users'></i>
<p>Users</p>
</a>
</li>
<li class = 'nav-item'>
<a href = 'departments.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-building'></i>
<p>Departments</p>
</a>
</li>
<li class = 'nav-item'>
<a href = './roles/index.php' class = 'nav-link'>
<i class = 'nav-icon fas fa-user-tag'></i>
<p>Roles & Permissions</p>
</a>
</li>
</ul>
</nav>
</div>
</aside>
<?php
// admin_header.php ends