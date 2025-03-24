
<?php
// Extract the current script name from the URL
$current_page = basename($_SERVER['REQUEST_URI'], isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
?>

<div class="sidebar">
  <div>
    <div class="logo-details">
      <i class='bx bxs-building'></i>
      <span class="logo_name">Niels Brock</span>
    </div>
    <ul class="nav-links">
      <li class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">
        <a href="index.php">
          <i class="bx bx-grid-alt"></i>
          <span class="links_name">Dashboard</span>
        </a>
      </li>
      <li class="<?= ($current_page == 'manage_programmes.php') ? 'active' : '' ?>">
        <a href="manage_programmes.php">
          <i class='bx bxs-graduation'></i>
          <span class="links_name">Course Management</span>
        </a>
      </li>
      <li class="<?= ($current_page == 'manage_modules.php') ? 'active' : '' ?>">
        <a href="manage_modules.php">
          <i class="bx bx-book"></i>
          <span class="links_name">Module Management</span>
        </a>
      </li>
      <li class="<?= ($current_page == 'manage_staff.php') ? 'active' : '' ?>">
        <a href="manage_staff.php">
          <i class="bx bx-user"></i>
          <span class="links_name">Staff Management</span>
        </a>
      </li>
      <li class="<?= ($current_page == 'student_interests_management.php') ? 'active' : '' ?>">
        <a href="student_interests_management.php">
        <i class='bx bx-like'></i>
        <span class="links_name"> Interest Management</span>
        </a>
      </li>
    </ul>
  </div>
  <div>
    <ul class="nav-links">
      <li>
        <a href="logout.php">
          <i class="bx bx-log-out"></i>
          <span class="links_name">Logout</span>
        </a>
      </li>
    </ul>
  </div>
</div>
