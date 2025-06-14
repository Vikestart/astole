<header>
  <div id="nav-mobile">
    <div id="nav-mobile-toggle" class="nav-mobile-item"><i id="nav-mobile-toggle-icon" class="far fa-bars"></i></div>
    <a class="nav-mobile-item nav-mobile-item-right" href="profile.php"><i class="fas fa-user-circle"></i></a>
    <a class="nav-mobile-item" href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
  </div>
  <nav>
    <ol>
      <li><a href="index.php"><i class="fas fa-window-restore"></i>Overview</a></li>
      <li><a href="pages.php"><i class="fas fa-layer-group"></i>Pages</a></li>
      <li><a href="users.php"><i class="fas fa-users"></i>Users</a></li>
      <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
      <li><a href="profile.php"><i class="fas fa-user-circle"></i><?php echo $userdata->row['user_uid']; ?></a></li>
    </ol>
  </nav>
</header>
