<?php
$site_title = "Manage Users";
require "inc-adm-head.php";
require "inc-adm-nav.php";

$users = new DBConn();
$users->result = $users->conn->query("SELECT * FROM users ORDER BY user_id ASC");

if (isset($_SESSION['Sessionmsg'])) {
    $msgorigin = $_SESSION['Sessionmsg']['origin']; $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
    unset($_SESSION['Sessionmsg']);
}

function getRoleBadge($roleInt) {
    if ($roleInt == 1) return '<span class="badge" style="background:#f3e8ff; color:#7e22ce;">Administrator</span>';
    if ($roleInt == 2) return '<span class="badge" style="background:#dcfce7; color:#15803d;">Moderator</span>';
    return '<span class="badge" style="background:#f1f5f9; color:#475569;">User</span>';
}
?>
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-users"></i> User Administration</h1>
        <?php if ($userdata->row['user_role'] == 1 || $userdata->row['user_role'] == 2) { ?>
            <a class="btn btn-primary" href="edit-user.php?t=new"><i class="fa-solid fa-user-plus"></i> Add User</a>
        <?php } ?>
    </div>

    <?php if (isset($msgtxt) && in_array($msgorigin, ['deluser', 'newuser', 'edituser'])) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Last Seen</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->result->num_rows > 0) {
                    while($row = $users->result->fetch_assoc()) { 
                        
                        // --- PERMISSION MATRIX ---
                        $can_edit = false; $can_delete = false;
                        if ($userdata->row['user_role'] == 1) {
                            $can_edit = true;
                            if ($userdata->row['user_id'] != $row['user_id']) $can_delete = true;
                        } elseif ($userdata->row['user_role'] == 2) {
                            if ($row['user_role'] != 1) $can_edit = true;
                            if ($userdata->row['user_id'] != $row['user_id'] && $row['user_role'] != 1) $can_delete = true;
                        } elseif ($userdata->row['user_id'] == $row['user_id']) {
                            $can_edit = true; // Regular users can edit themselves
                        }
                ?>
                        <tr>
                            <td>
                                <?php if ($can_edit) { ?>
                                    <a href="edit-user.php?t=edit&u=<?php echo htmlspecialchars($row['user_id']); ?>" style="color: var(--color-heading); font-weight: 700; transition: color 0.2s;">
                                        <?php echo htmlspecialchars($row['user_uid']); ?>
                                    </a>
                                <?php } else { ?>
                                    <strong><?php echo htmlspecialchars($row['user_uid']); ?></strong>
                                <?php } ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['user_mail']); ?></td>
                            <td><?php echo getRoleBadge($row['user_role']); ?></td>
                            <td><?php echo ($row['user_lastseen'] !== "0000-00-00 00:00:00") ? htmlspecialchars(date('M d, Y H:i', strtotime($row['user_lastseen']))) : '<span style="color: var(--text-muted);">Never logged in</span>'; ?></td>
                            
                            <td style="text-align: right;" class="table-actions">
                                <?php if ($can_edit) { ?>
                                    <a href="edit-user.php?t=edit&u=<?php echo htmlspecialchars($row['user_id']); ?>" title="Edit User"><i class="fa-solid fa-user-edit"></i></a>
                                <?php } ?>
                                
                                <?php if ($can_delete) { ?>
                                    <form action="process-user.php" method="POST" class="form-delete" style="display:inline-block; margin-left: 15px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="deluser">
                                        <input type="hidden" name="u" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                                        <button type="submit" class="delete" title="Delete User" style="background:none; border:none; cursor:pointer; font-size:16px; color:var(--text-muted); transition:color 0.2s;"><i class="fa-solid fa-trash-alt"></i></button>
                                    </form>
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
<?php require "inc-adm-foot.php"; ?>