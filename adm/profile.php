<?php
	$site_title = "My Profile";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

	if (isset($_SESSION['Sessionmsg'])) {
		$msgorigin = $_SESSION['Sessionmsg']['origin']; $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
		unset($_SESSION['Sessionmsg']);
	}

	$active_user_uid = $userdata->row['user_uid'];
	$userpages = new DBConn();
	$stmt_pages = $userpages->conn->prepare("SELECT * FROM pages WHERE page_author = ? ORDER BY page_updated DESC");
	$stmt_pages->bind_param("s", $active_user_uid);
	$stmt_pages->execute();
	$result_pages = $stmt_pages->get_result();
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    
    <section style="margin-bottom: 20px;">
        <h2 class="h1_underscore" style="font-size: 18px; margin-bottom: 20px;"><i class="fa-solid fa-id-card"></i> Profile Details</h2>
        <form action="process-profile.php" method="POST" autocomplete="off" class="track-changes">
            <?php if (isset($msgtxt) && $msgorigin == "changebasics") { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input name="action" value="changebasics" type="hidden" />

            <div class="form-group">
                <label>Username</label>
                <input name="user_name" class="form-input" type="text" minlength="3" maxlength="20" value="<?php echo htmlspecialchars($userdata->row['user_uid']); ?>" required />
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input name="user_mail" class="form-input" type="email" maxlength="50" value="<?php echo htmlspecialchars($userdata->row['user_mail']); ?>" required />
            </div>
            <button class="btn btn-primary btn-full" type="submit"><i class="fa-solid fa-save"></i> Save Details</button>
        </form>
    </section>

    <section style="margin-bottom: 20px;">
        <h2 class="h1_underscore" style="font-size: 18px; margin-bottom: 20px;"><i class="fa-solid fa-shield-halved"></i> Security</h2>
        <form action="process-profile.php" method="POST" autocomplete="off" class="track-changes">
            <?php if (isset($msgtxt) && $msgorigin == "changepass") { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input name="action" value="changepass" type="hidden" />

            <div class="form-group">
                <label>Current Password</label>
                <input name="user_currpass" class="form-input" type="password" required />
            </div>
            
            <div class="form-group" style="margin-bottom: 10px;">
                <label>New Password</label>
                <div style="display: flex; gap: 10px;">
                    <input name="user_newpass" id="profile_newpass" class="form-input" type="password" maxlength="128" autocomplete="new-password" />
                    <button id="profile_generate_btn" class="btn" style="background: var(--bg-body); border: 1px solid var(--border); color: var(--text-main); flex-shrink: 0;" title="Generate Secure Password">
                        <i class="fa-solid fa-key" style="margin: 0;"></i>
                    </button>
                </div>
                
                <div id="password-strength-container" style="display:none; margin-top: 15px;">
                    <div class="strength-meter"><div id="strength-bar" class="strength-bar"></div></div>
                    <span id="strength-text" class="strength-text"></span>
                    
                    <ul class="password-reqs">
                        <li id="req-length" class="req-unmet"><span class="icon-container"><i class="fa-solid fa-times"></i></span> At least 8 characters</li>
                        <li id="req-upper" class="req-unmet"><span class="icon-container"><i class="fa-solid fa-times"></i></span> One uppercase letter</li>
                        <li id="req-lower" class="req-unmet"><span class="icon-container"><i class="fa-solid fa-times"></i></span> One lowercase letter</li>
                        <li id="req-number" class="req-unmet"><span class="icon-container"><i class="fa-solid fa-times"></i></span> One number</li>
                        <li id="req-symbol" class="req-unmet"><span class="icon-container"><i class="fa-solid fa-times"></i></span> One symbol (!@#$...)</li>
                    </ul>
                </div>
            </div>

            <div class="form-group" id="confirm-password-group" style="display:none;">
                <label>Confirm New Password</label>
                <input name="user_confirmpass" id="profile_confirmpass" class="form-input" type="password" maxlength="128" />
            </div>

            <button class="btn btn-primary btn-full" type="submit"><i class="fa-solid fa-lock"></i> Update Password</button>
        </form>
    </section>

    <section style="margin-bottom: 20px;">
        <h2 class="h1_underscore" style="font-size: 18px; margin-bottom: 20px;"><i class="fa-solid fa-globe"></i> Preferences</h2>
        <form action="process-profile.php" method="POST" autocomplete="off" class="track-changes">
            <?php if (isset($msgtxt) && $msgorigin == "changetimezone") { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input name="action" value="changetimezone" type="hidden" />

            <div class="form-group">
                <label>Local Timezone</label>
                <select name="user_timezone" class="form-input" style="background-color: #fff; cursor: pointer;">
                    <?php
                    function formatOffset($offset) {
                        $hours = $offset / 3600; $remainder = $offset % 3600;
                        $sign = $hours > 0 ? '+' : '-'; $hour = (int) abs($hours); $minutes = (int) abs($remainder / 60);
                        if ($hour == 0 && $minutes == 0) { $sign = ' '; }
                        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');
                    }
                    $utc = new DateTimeZone('UTC'); $dt = new DateTime('now', $utc);
                    foreach(DateTimeZone::listIdentifiers() as $tz) {
                        $current_tz = new DateTimeZone($tz); $offset =  $current_tz->getOffset($dt);
                        $transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
                        $abbr = isset($transition[0]['abbr']) ? $transition[0]['abbr'] : '';
                        $selected = ($tz == $userdata->row['user_timezone']) ? 'selected' : '';
                        echo '<option value="' .$tz. '" ' .$selected. '>' .$tz. ' [' .$abbr. ' '. formatOffset($offset). ']</option>';
                    }
                    ?>
                </select>
            </div>
            <button class="btn btn-primary btn-full" type="submit"><i class="fa-solid fa-clock"></i> Save Timezone</button>
        </form>
    </section>

</div>

<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h2 style="margin: 0; font-size: 20px; color: var(--color-heading);"><i class="fa-solid fa-layer-group"></i> Your Authored Pages</h2>
        <a class="btn btn-primary" href="edit-page.php?t=new"><i class="fa-solid fa-plus"></i> Draft New Page</a>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>URL Slug</th>
                    <th>Last Updated</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_pages->num_rows > 0) {
                    while($row = $result_pages->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <a href="edit-page.php?t=edit&p=<?php echo htmlspecialchars($row['page_id']); ?>" style="color: var(--color-heading); font-weight: 700; text-decoration: none; transition: color 0.2s;">
                                    <?php echo htmlspecialchars($row['page_title']); ?>
                                </a>
                            </td>
                            <td><span class="badge badge-blue">/<?php echo htmlspecialchars($row['page_slug']); ?></span></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['page_updated']))); ?></td>
                            <td style="text-align: right;" class="table-actions">
                                <a href="../<?php echo htmlspecialchars($row['page_slug']); ?>" target="_blank" title="View Public Page"><i class="fa-solid fa-external-link-alt"></i></a>
                                <a href="edit-page.php?t=edit&p=<?php echo htmlspecialchars($row['page_id']); ?>" title="Edit Page"><i class="fa-solid fa-edit"></i></a>
                                
                                <form action="process-page.php" method="POST" class="form-delete" style="display:inline-block; margin-left: 15px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delpage">
                                    <input type="hidden" name="p" value="<?php echo htmlspecialchars($row['page_id']); ?>">
                                    <button type="submit" class="delete" title="Delete Page" style="background:none; border:none; cursor:pointer; font-size:16px; color:var(--text-muted); transition:color 0.2s;"><i class="fa-solid fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                <?php } } else { ?>
                    <tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">You haven't authored any pages yet.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php require "inc-adm-foot.php"; ?>