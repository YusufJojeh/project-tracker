<?php
// includes/sidebar.php
?>
<aside class = 'main-sidebar sidebar-dark-primary elevation-4'>
<a href = '/supervisors/dashboard.php' class = 'brand-link text-center' style = 'background:#4e73df;'>
<span class = 'brand-text font-weight-light'>MyApp</span>
</a>
<div class = 'sidebar'>
<nav class = 'mt-2'>
<ul class = 'nav nav-pills nav-sidebar flex-column' data-widget = 'treeview'>
<li class = 'nav-item'>
<a href = '../supervisors/dashboard.php'

class = "nav-link <?php if($current_page==='dashboard') echo 'active'; ?>">
<i class = 'nav-icon fas fa-tachometer-alt'></i>
<p>Dashboard</p>
</a>
</li>
<li class = 'nav-item'>
<a href = '../supervisors/projects.php' class = "nav-link <?php if($current_page==='projects') echo 'active'; ?>">
<i class = 'nav-icon fas fa-folder-open'></i>
<p>Projects</p>
</a>
</li>
<li class = 'nav-item'>
<a href = '/supervisors/notifications.php'

class = "nav-link <?php if($current_page==='notifications') echo 'active'; ?>">
<i class = 'nav-icon fas fa-bell'></i>
<p>Notifications</p>
</a>
</li>
</ul>
</nav>
</div>
</aside>