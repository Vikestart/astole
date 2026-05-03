<?php
$site_title = "Manage Users";
require "inc-adm-head.php";
require "inc-adm-nav.php";

$db = new DBConn();
$view_action = $_GET['action'] ?? 'list';

if (isset($_SESSION['Sessionmsg'])) {
    $msgorigin = $_SESSION['Sessionmsg']['origin']; 
    $msgtype = $_SESSION['Sessionmsg']['type']; 
    $msgicon = $_SESSION['Sessionmsg']['icon']; 
    $msgtxt = $_SESSION['Sessionmsg']['message'];
    unset($_SESSION['Sessionmsg']);
}

// --- VIEW 1: EDITOR (NEW / EDIT) ---
if ($view_action === 'new' || $view_action === 'edit') {
    
    // UI SECURITY: Standard Users (Role 3) have no access to the user editor at all.
    if ($userdata->row['user_role'] == 3) { header("Location: users"); die(); }

    $user_isnew = ($view_action === 'new');
    
    if ($user_isnew) {
        $site_title = "New User";
        $user_name = ''; $user_mail = ''; $user_role_int = 3; $user_id = null;
    } else {
        $site_title = "Edit User";
        if (!isset($_GET['u'])) { header("Location: users"); die(); }
        
        $user_id = (int)$_GET['u']; 
        $stmt_fetch = $db->conn->prepare("SELECT user_uid, user_mail, user_role FROM users WHERE user_id = ?");
        $stmt_fetch->bind_param("i", $user_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows === 1) {
            $row_fetch = $result_fetch->fetch_assoc();
            $user_name = $row_fetch['user_uid'];
            $user_mail = $row_fetch['user_mail'];
            $user_role_int = (int)$row_fetch['user_role'];
        } else {
            header("Location: users"); die();
        }
        $stmt_fetch->close();
    }

    // Dynamic Role Security Logic
    $allowed_roles = [1 => 'Administrator', 2 => 'Moderator', 3 => 'Standard User'];
    $can_change_role = true;
    
    if (!$user_isnew && $user_id === (int)$_SESSION['UserID']) { $can_change_role = false; }
    if ($userdata->row['user_role'] == 2 && $user_role_int == 1) { $can_change_role = false; }
    if ($userdata->row['user_role'] == 2 && $user_isnew) { $allowed_roles = [2 => 'Moderator', 3 => 'Standard User']; }
?>
    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
            <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-<?php echo ($user_isnew) ? "user-plus" : "user-pen"; ?>"></i> <?php echo ($user_isnew) ? "Create New User" : "Edit User: " . htmlspecialchars($user_name); ?></h1>
            
            <div style="display: flex; gap: 10px;">
                <a href="users" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <?php if (!$user_isnew && $user_id !== (int)$_SESSION['UserID']) { ?>
                    <form action="process-user" method="POST" class="form-delete" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="deluser">
                        <input type="hidden" name="u" value="<?php echo htmlspecialchars($user_id); ?>">
                        <button type="submit" class="btn btn-red"><i class="fa-solid fa-trash-alt"></i> Delete</button>
                    </form>
                <?php } ?>
            </div>
        </div>

        <form action="process-user" method="POST" autocomplete="off">
            <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input name="action" value="<?php echo ($user_isnew) ? "newuser" : "edituser"; ?>" type="hidden" />
            <?php if (!$user_isnew) { echo '<input type="hidden" name="u" value="' . $user_id . '">'; } ?>

            <div class="glass-panel" style="padding: 25px; border: 1px solid var(--border); border-radius: 8px;">
                <h3 style="margin-top: 0; font-size: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Account Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Username <span class="req-ast">*</span></label>
                        <input name="user_name" class="form-input" type="text" maxlength="30" value="<?php echo htmlspecialchars($user_name); ?>" required autocomplete="new-password" />
                    </div>
                    <div class="form-group">
                        <label>Email Address <span class="req-ast">*</span></label>
                        <input name="user_mail" class="form-input" type="email" maxlength="100" value="<?php echo htmlspecialchars($user_mail); ?>" required autocomplete="new-password" />
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Password <?php if ($user_isnew) echo '<span class="req-ast">*</span>'; else echo '<span style="font-size: 12px; color: var(--text-muted); font-weight: normal;">(Leave blank to keep current)</span>'; ?></label>
                        <div style="display: flex; gap: 10px;">
                            <input name="user_pass" id="user_pass_field" class="form-input" type="password" <?php if ($user_isnew) echo 'required'; ?> autocomplete="new-password" />
                            <button type="button" id="generate_pass_btn" class="btn btn-secondary" title="Generate Password">
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

            <div style="margin-top: 20px;">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save User Details</button>
            </div>
        </form>
    </section>

<?php 
// --- VIEW 2: LIST ALL USERS ---
} else { 
    $users_res = $db->conn->query("SELECT * FROM users ORDER BY user_id ASC");
    
    function getRoleBadge($roleInt) {
        if ($roleInt == 1) return '<span class="badge" style="background:#f3e8ff; color:#7e22ce;">Administrator</span>';
        if ($roleInt == 2) return '<span class="badge" style="background:#dcfce7; color:#15803d;">Moderator</span>';
        return '<span class="badge" style="background:#f1f5f9; color:#475569;">User</span>';
    }
?>
    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
            <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-users"></i> User Administration</h1>
            <?php if ($userdata->row['user_role'] != 3) { ?>
                <a class="btn btn-primary" href="users?action=new"><i class="fa-solid fa-plus"></i> Create New</a>
            <?php } ?>
        </div>

        <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_res && $users_res->num_rows > 0) {
                        while($row = $users_res->fetch_assoc()) { 
                            
                            // SECURITY MATRIX
                            $can_modify = false;
                            if ($userdata->row['user_role'] == 1) { 
                                $can_modify = true; 
                            } elseif ($userdata->row['user_role'] == 2) {
                                if ($row['user_role'] != 1 || $row['user_id'] == $_SESSION['UserID']) {
                                    $can_modify = true;
                                }
                            } elseif ($userdata->row['user_role'] == 3 && $row['user_id'] == $_SESSION['UserID']) {
                                $can_modify = false; 
                            }
                    ?>
                            <tr>
                                <td><span class="badge badge-gray badge-noborder">#<?php echo $row['user_id']; ?></span></td>
                                <td>
                                    <?php if ($can_modify) { ?>
                                        <a href="users?action=edit&u=<?php echo $row['user_id']; ?>" style="color: var(--color-heading); font-weight: 600; text-decoration: none; transition: color 0.2s;">
                                            <?php echo htmlspecialchars($row['user_uid']); ?>
                                        </a>
                                    <?php } else { ?>
                                        <strong style="color: var(--color-heading); font-weight: 600;"><?php echo htmlspecialchars($row['user_uid']); ?></strong>
                                    <?php } ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['user_mail']); ?></td>
                                <td><?php echo getRoleBadge($row['user_role']); ?></td>
                                <td style="text-align: right;" class="table-actions">
                                    
                                    <?php if ($can_modify) { ?>
                                        <a href="users?action=edit&u=<?php echo $row['user_id']; ?>" title="Edit User" style="margin-left: 10px;"><i class="fa-solid fa-edit"></i></a>
                                        
                                        <?php if ($row['user_id'] != $_SESSION['UserID']) { ?>
                                            <form action="process-user" method="POST" class="form-delete" style="display:inline-block; margin-left: 15px;" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="action" value="deluser">
                                                <input type="hidden" name="u" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                                <button type="submit" class="delete" title="Delete User" style="background:none; border:none; cursor:pointer; font-size:16px; color:var(--text-muted); transition:color 0.2s;"><i class="fa-solid fa-trash-alt"></i></button>
                                            </form>
                                        <?php } else { ?>
                                            <span style="display:inline-block; margin-left: 15px; opacity: 0.3; cursor: not-allowed; font-size:16px; color:var(--text-muted);" title="Cannot delete yourself"><i class="fa-solid fa-trash-alt"></i></span>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span style="display:inline-block; margin-left: 10px; opacity: 0.3; cursor: not-allowed; color: var(--text-muted);" title="Not Allowed"><i class="fa-solid fa-edit"></i></span>
                                        <span style="display:inline-block; margin-left: 15px; opacity: 0.3; cursor: not-allowed; font-size:16px; color:var(--text-muted);" title="Not Allowed"><i class="fa-solid fa-trash-alt"></i></span>
                                    <?php } ?>
                                    
                                </td>
                            </tr>
                    <?php } } else { ?>
                        <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">No users found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
<?php } ?>

<?php require "inc-adm-foot.php"; ?>