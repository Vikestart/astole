<?php
$site_title = "Manage Pages";
require "inc-adm-head.php";
require "inc-adm-nav.php";

$pages = new DBConn();
$pages->result = $pages->conn->query("SELECT * FROM pages ORDER BY page_title");

if (isset($_SESSION['Sessionmsg'])) {
    $msgorigin = $_SESSION['Sessionmsg']['origin'];
    $msgtype = $_SESSION['Sessionmsg']['type'];
    $msgicon = $_SESSION['Sessionmsg']['icon'];
    $msgtxt = $_SESSION['Sessionmsg']['message'];
    unset($_SESSION['Sessionmsg']);
}
?>
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-file-lines"></i> Page Management</h1>
        <a class="btn btn-primary" href="edit-page.php?t=new"><i class="fa-solid fa-plus"></i> Create New</a>
    </div>

    <?php if (isset($msgtxt) && in_array($msgorigin, ['delpage', 'newpage', 'editpage'])) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>URL Slug</th>
                    <th>Author</th>
                    <th>Last Updated</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pages->result->num_rows > 0) {
                    while($row = $pages->result->fetch_assoc()) { 
                        
                        // --- PERMISSION MATRIX ---
                        $can_modify = false;
                        if ($userdata->row['user_role'] == 1 || $userdata->row['user_role'] == 2) {
                            $can_modify = true;
                        } elseif ($userdata->row['user_role'] == 3 && $row['page_author'] === $userdata->row['user_uid']) {
                            $can_modify = true;
                        }
                ?>
                        <tr>
                            <td>
                                <?php if ($can_modify) { ?>
                                    <a href="edit-page.php?t=edit&p=<?php echo htmlspecialchars($row['page_id']); ?>" style="color: var(--color-heading); font-weight: 700; text-decoration: none; transition: color 0.2s;">
                                        <?php echo htmlspecialchars($row['page_title']); ?>
                                    </a>
                                <?php } else { ?>
                                    <strong style="color: var(--color-heading); font-weight: 700;"><?php echo htmlspecialchars($row['page_title']); ?></strong>
                                <?php } ?>
							</td>
                            <td><span class="badge badge-blue bage-noborder">/<?php echo htmlspecialchars($row['page_slug']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['page_author']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['page_updated']))); ?></td>
                            <td style="text-align: right;" class="table-actions">
                                
                                <a href="../<?php echo htmlspecialchars($row['page_slug']); ?>" target="_blank" title="View"><i class="fa-solid fa-external-link-alt"></i></a>
                                
                                <?php if ($can_modify) { ?>
                                    <a href="edit-page.php?t=edit&p=<?php echo htmlspecialchars($row['page_id']); ?>" title="Edit" style="margin-left: 10px;"><i class="fa-solid fa-edit"></i></a>
                                    
                                    <form action="process-page.php" method="POST" class="form-delete" style="display:inline-block; margin-left: 15px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="delpage">
                                        <input type="hidden" name="p" value="<?php echo htmlspecialchars($row['page_id']); ?>">
                                        <button type="submit" class="delete" title="Delete" style="background:none; border:none; cursor:pointer; font-size:16px; color:var(--text-muted); transition:color 0.2s;"><i class="fa-solid fa-trash-alt"></i></button>
                                    </form>
                                <?php } else { ?>
                                    <span style="display:inline-block; margin-left: 10px; opacity: 0.3; cursor: not-allowed; color: var(--text-muted);" title="Not Allowed"><i class="fa-solid fa-edit"></i></span>
                                    <span style="display:inline-block; margin-left: 15px; opacity: 0.3; cursor: not-allowed; font-size:16px; color:var(--text-muted);" title="Not Allowed"><i class="fa-solid fa-trash-alt"></i></span>
                                <?php } ?>

                            </td>
                        </tr>
                <?php } } else { ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">No pages found. Create one!</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php require "inc-adm-foot.php"; ?>