<?php
// /core/admin/views/login.php
$site_title = "Sign In";
require_once __DIR__ . "/../components/header.php";

if (isset($_SESSION['Sessionmsg'])) {
    $msgorigin = $_SESSION['Sessionmsg']['origin'];
    $msgtype = $_SESSION['Sessionmsg']['type'];
    $msgicon = $_SESSION['Sessionmsg']['icon'];
    $msgexpire = $_SESSION['Sessionmsg']['expire'];
    $msgtxt = $_SESSION['Sessionmsg']['message'];
    unset($_SESSION['Sessionmsg']);
}

// Generate CSRF Token if missing (since we bypassed standard auth checks)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-icon">A.S</div>
                <h2>Control Panel</h2>
                <p>Sign in to manage your website.</p>
            </div>

            <form action="/core/actions/adm/process-login.php" method="POST" id="form-login">
                <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group">
                    <label>Username or Email</label>
                    <input name="username" class="form-input" type="text" minlength="3" maxlength="40" required autofocus />
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input name="password" class="form-input" type="password" minlength="8" maxlength="128" required />
                </div>

                <button class="btn btn-primary btn-full" type="submit">
                    <i class="fa-solid fa-sign-in-alt"></i> Proceed to Dashboard
                </button>
            </form>
        </div>
    </div>
</body>
</html>