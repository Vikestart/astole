<?php
if (isset($_GET['t'])) {
    $page_type = $_GET['t'];
    if ($page_type == "new") {
        $site_title = "New User"; $user_isnew = true;
        $user_name = ''; $user_mail = ''; $user_role_int = 3; $user_id = null;
    } else if ($page_type == "edit") {
        $site_title = "Edit User"; $user_isnew = false;
    } else { header("Location: users.php"); die(); }
} else { header("Location: users.php"); die(); }

require "inc-adm-head.php";
require "inc-adm-nav.php";

// UI SECURITY: Standard Users (Role 3) have no access to the user editor at all.
if ($userdata->row['user_role'] == 3) { 
    header("Location: users.php"); die(); 
}

if ($user_isnew === false) {
    if (!isset($_GET['u'])) { header("Location: users.php"); die(); }
    $user_id = (int)$_GET['u']; 

    $db_conn = new DBConn();
    $stmt_fetch = $db_conn->conn->prepare("SELECT user_uid, user_mail, user_role FROM users WHERE user_id = ?");
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows === 1) {
        $row = $result_fetch->fetch_assoc();
        $user_name = $row['user_uid'];
        $user_mail = $row['user_mail'];
        $user_role_int = (int)$row['user_role'];
    } else { header("Location: users.php"); die(); }
    $stmt_fetch->close();

    // UI SECURITY: Kick Moderators if they try to URL-hack their way to an Admin's edit page
    if ($userdata->row['user_role'] == 2 && $user_role_int == 1) { header("Location: users.php"); die(); }
    // UI SECURITY: Kick Standard Users if they try to URL-hack someone else's page
    if ($userdata->row['user_role'] == 3 && $userdata->row['user_id'] != $user_id) { header("Location: users.php"); die(); }
}

if (isset($_SESSION['Sessionmsg'])) {
    $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
    unset($_SESSION['Sessionmsg']);
}

// --- DYNAMIC ROLE DROPDOWN LOGIC ---
$can_change_role = false;
$allowed_roles = [];

if ($user_isnew) {
    if ($userdata->row['user_role'] == 1) { $can_change_role = true; $allowed_roles = [1 => 'Administrator', 2 => 'Moderator', 3 => 'Standard User']; }
    elseif ($userdata->row['user_role'] == 2) { $can_change_role = true; $allowed_roles = [2 => 'Moderator', 3 => 'Standard User']; }
} else {
    // ONLY allow change if Target is NOT Self
    if ($userdata->row['user_id'] != $user_id) { 
        if ($userdata->row['user_role'] == 1) { $can_change_role = true; $allowed_roles = [1 => 'Administrator', 2 => 'Moderator', 3 => 'Standard User']; }
        elseif ($userdata->row['user_role'] == 2 && $user_role_int != 1) { $can_change_role = true; $allowed_roles = [2 => 'Moderator', 3 => 'Standard User']; }
    }
}
?>
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-<?php echo ($user_isnew) ? "user-plus" : "user-edit"; ?>"></i> <?php echo ($user_isnew) ? "Add New User" : "Edit User: " . htmlspecialchars($user_name); ?></h1>
        <?php if (!$user_isnew && $userdata->row['user_id'] != $user_id && ($userdata->row['user_role'] == 1 || ($userdata->row['user_role'] == 2 && $user_role_int != 1))) { ?>
            <form action="process-user.php" method="POST" class="form-delete" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="deluser">
                <input type="hidden" name="u" value="<?php echo htmlspecialchars($user_id); ?>">
                <button type="submit" class="btn btn-red"><i class="fa-solid fa-trash-alt"></i> Delete User</button>
            </form>
        <?php } ?>
    </div>

    <form action="process-user.php" method="POST" autocomplete="off">
        <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input name="action" value="<?php echo ($user_isnew) ? "newuser" : "edituser"; ?>" type="hidden" />
        <?php if (!$user_isnew) { ?><input name="user_id" type="hidden" value="<?php echo htmlspecialchars($user_id); ?>" /><?php } ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <div class="form-group">
                    <label>Username</label>
                    <input name="user_name" class="form-input" type="text" minlength="3" maxlength="20" value="<?php echo htmlspecialchars($user_name); ?>" required />
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input name="user_mail" class="form-input" type="email" maxlength="50" value="<?php echo htmlspecialchars($user_mail); ?>" required />
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label><?php echo ($user_isnew) ? "Assign Password" : "New Password (leave blank to keep current)"; ?></label>
                    <div style="display: flex; gap: 10px;">
                        <input name="user_pass" id="user_pass_field" class="form-input" type="password" minlength="8" maxlength="128" <?php echo ($user_isnew) ? "required" : ""; ?> autocomplete="new-password" />
                        <button id="generate_pass_btn" class="btn" style="background: var(--bg-body); border: 1px solid var(--border); color: var(--text-main); flex-shrink: 0;" title="Generate Secure Password">
                            <i class="fa-solid fa-key" style="margin: 0;"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Account Role</label>
                    <?php if ($can_change_role) { ?>
                        <select name="user_role" class="form-input" style="background-color: #fff; cursor: pointer;">
                            <?php foreach ($allowed_roles as $val => $label) { ?>
                                <option value="<?php echo $val; ?>" <?php if ($user_role_int == $val) echo 'selected'; ?>><?php echo $label; ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { 
                        $role_names = [1 => 'Administrator', 2 => 'Moderator', 3 => 'Standard User'];
                        echo '<input class="form-input" type="text" value="' . $role_names[$user_role_int] . '" disabled style="background: var(--bg-body); color: var(--text-muted);" />';
                        echo '<input type="hidden" name="user_role" value="' . $user_role_int . '">';
                    } ?>
                </div>
            </div>
        </div>

        <div style="margin-top: 10px; border-top: 1px solid var(--border); padding-top: 20px;">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save User Details</button>
        </div>
    </form>
</section>
<?php require "inc-adm-foot.php"; ?>