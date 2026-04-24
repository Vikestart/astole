<?php
    $site_title = "Menu Manager";
    require "inc-adm-head.php";
    require "inc-adm-nav.php";

    if ($userdata->row['user_role'] == 3) { header("Location: index.php"); die(); }

    if (isset($_SESSION['Sessionmsg'])) {
        $msgorigin = $_SESSION['Sessionmsg']['origin']; $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
        unset($_SESSION['Sessionmsg']);
    }

    $db = new DBConn();
    $action = $_GET['action'] ?? 'list';

    // --- VIEW 1: MENU BUILDER ---
    if ($action === 'edit' && isset($_GET['menu_id'])) {
        $active_menu_id = (int)$_GET['menu_id'];
        
        $stmt = $db->conn->prepare("SELECT * FROM menus WHERE id = ?");
        $stmt->bind_param("i", $active_menu_id);
        $stmt->execute();
        $active_menu_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$active_menu_data) { header("Location: menus.php"); die(); }

        // Page Fetcher
        $pages = [];
        $res_pages = $db->conn->query("SELECT * FROM pages");
        if($res_pages) { 
            while($p = $res_pages->fetch_assoc()) { 
                $pid = $p['id'] ?? $p['page_id'] ?? 0;
                $ptitle = $p['page_title'] ?? $p['title'] ?? 'Unnamed Page';
                if ($pid) $pages[] = ['id' => $pid, 'title' => $ptitle];
            } 
        }
        usort($pages, function($a, $b) { return strcmp($a['title'], $b['title']); });
?>
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
                <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-list-ul"></i> Builder: <?php echo htmlspecialchars($active_menu_data['name']); ?></h1>
                <a href="menus.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Menus</a>
            </div>

            <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>
            <div id="ajax-msgbox" style="display: none; margin-bottom: 20px;"></div>

            <div class="menu-manager-grid">
                <div class="menu-structure-panel">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0; font-size: 16px;">Structure</h3>
                        <button type="button" id="ajax-save-order-btn" class="btn btn-primary btn-sm" style="display: none;"><i class="fa-solid fa-save"></i> Save Order</button>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;"><i class="fa-solid fa-circle-info"></i> Drag the icons to reorder items or create submenus.</p>
                    
                    <div id="sortable-menu">
                        <div style="text-align: center; padding: 20px; color: var(--text-muted);">Loading menu items...</div>
                    </div>
                </div>

                <div class="menu-form-panel">
                    <h3 style="margin-top: 0; font-size: 16px; margin-bottom: 20px;">Add Menu Link</h3>
                    
                    <form id="ajax-add-menu-form">
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" id="active-menu-id" name="menu_id" value="<?php echo $active_menu_id; ?>">
                        <input type="hidden" id="menu-csrf" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Link Title</label>
                            <input type="text" name="title" class="form-input" placeholder="e.g. About Us" required>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px;">Link Type</label>
                            <div class="radio-toggle-group">
                                <input type="radio" name="link_type" id="add_type_url" value="url" checked>
                                <label for="add_type_url"><i class="fa-solid fa-link"></i> Custom URL</label>
                                
                                <input type="radio" name="link_type" id="add_type_page" value="page">
                                <label for="add_type_page"><i class="fa-solid fa-file-alt"></i> Existing Page</label>
                            </div>
                        </div>

                        <div id="wrapper-url" style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Destination URL</label>
                            <input type="text" name="url" class="form-input" placeholder="e.g. /about.php or https://...">
                        </div>

                        <div id="wrapper-page" style="margin-bottom: 15px; display: none;">
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Select Page</label>
                            <select name="page_id" class="form-input">
                                <option value="">-- Choose a Page --</option>
                                <?php foreach($pages as $pg) { echo '<option value="'.$pg['id'].'">'.htmlspecialchars($pg['title']).'</option>'; } ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Parent Item</label>
                            <select name="parent_id" class="form-input dynamic-parent-select">
                                <option value="">-- Loading --</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px;">Open In</label>
                            <div class="radio-toggle-group">
                                <input type="radio" name="target" id="add_target_self" value="_self" checked>
                                <label for="add_target_self">Same Tab</label>
                                
                                <input type="radio" name="target" id="add_target_blank" value="_blank">
                                <label for="add_target_blank">New Tab</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;"><i class="fa-solid fa-plus"></i> Add Link</button>
                    </form>
                </div>
            </div>
        </section>

        <div id="edit-item-modal" class="admin-modal-overlay">
            <div class="admin-modal-content">
                <button type="button" class="admin-modal-close" onclick="document.getElementById('edit-item-modal').style.display='none'"><i class="fa-solid fa-times"></i></button>
                <h3 style="margin-top: 0; margin-bottom: 20px;">Edit Link</h3>
                <form id="ajax-edit-item-form">
                    <input type="hidden" name="action" value="edit_item">
                    <input type="hidden" name="item_id" id="edit-item-id">
                    <input type="hidden" name="menu_id" value="<?php echo $active_menu_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Link Title</label>
                        <input type="text" name="title" id="edit-title" class="form-input" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px;">Link Type</label>
                        <div class="radio-toggle-group">
                            <input type="radio" name="link_type" id="edit_type_url" value="url">
                            <label for="edit_type_url"><i class="fa-solid fa-link"></i> Custom URL</label>
                            
                            <input type="radio" name="link_type" id="edit_type_page" value="page">
                            <label for="edit_type_page"><i class="fa-solid fa-file-alt"></i> Existing Page</label>
                        </div>
                    </div>

                    <div id="edit-wrapper-url" style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Destination URL</label>
                        <input type="text" name="url" id="edit-url" class="form-input">
                    </div>

                    <div id="edit-wrapper-page" style="margin-bottom: 15px; display: none;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Select Page</label>
                        <select name="page_id" id="edit-page" class="form-input">
                            <option value="">-- Choose a Page --</option>
                            <?php foreach($pages as $pg) { echo '<option value="'.$pg['id'].'">'.htmlspecialchars($pg['title']).'</option>'; } ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Parent Item</label>
                        <select name="parent_id" id="edit-parent" class="form-input dynamic-parent-select">
                            <option value="">-- Loading --</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px;">Open In</label>
                        <div class="radio-toggle-group">
                            <input type="radio" name="target" id="edit_target_self" value="_self">
                            <label for="edit_target_self">Same Tab</label>
                            
                            <input type="radio" name="target" id="edit_target_blank" value="_blank">
                            <label for="edit_target_blank">New Tab</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Save Changes</button>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if(typeof loadMenuAjax === "function") { loadMenuAjax(); }
            });
        </script>

<?php 
    // --- VIEW 2: ALL MENUS TABLE ---
    } else { 
        // Fetch all menus and count their items
        $menus = $db->conn->query("
            SELECT m.*, COUNT(mi.id) as item_count 
            FROM menus m 
            LEFT JOIN menu_items mi ON m.id = mi.menu_id 
            GROUP BY m.id 
            ORDER BY m.name ASC
        ");
?>
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
                <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-list-ul"></i> Menus</h1>
                <button class="btn btn-primary" onclick="document.getElementById('create-menu-modal').style.display='flex'"><i class="fa-solid fa-plus"></i> Create New Menu</button>
            </div>

            <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border);">
                        <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Menu Name</th>
                        <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Identifier</th>
                        <th style="padding: 15px; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Links</th>
                        <th style="padding: 15px; text-align: right; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($menus && $menus->num_rows > 0) {
                        while($row = $menus->fetch_assoc()) { 
                    ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 15px;">
                                    <a href="menus.php?action=edit&menu_id=<?php echo $row['id']; ?>" style="color: var(--color-heading); font-weight: 600; text-decoration: none; transition: color 0.2s;">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </a>
                                </td>
                                <td style="padding: 15px;">
                                    <span style="font-family: monospace; background: var(--bg-body); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--border); font-size: 13px; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($row['identifier']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px;">
                                    <span class="badge badge-gray"><?php echo $row['item_count']; ?> items</span>
                                </td>
                                <td style="padding: 15px; text-align: right;" class="table-actions">
                                    <button onclick="openSettingsModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['identifier'], ENT_QUOTES); ?>')" title="Settings" style="background:none; border:none; cursor:pointer; font-size:16px; color:var(--text-muted); transition:color 0.2s;"><i class="fa-solid fa-cog"></i></button>

                                    <a href="menus.php?action=edit&menu_id=<?php echo $row['id']; ?>" title="Edit Menu Structure" style="margin-left: 15px;"><i class="fa-solid fa-pen"></i></a>
                                    
                                    <form action="process-menu.php" method="POST" class="form-delete" style="display:inline-block; margin-left: 15px;" onsubmit="return confirm('Are you sure you want to permanently delete this menu?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="delete_menu">
                                        <input type="hidden" name="menu_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="delete" title="Delete Menu" style="background:none; border:none; cursor:pointer; font-size:16px; color:var(--text-muted); transition:color 0.2s;"><i class="fa-solid fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                    <?php } } else { ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">No menus found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>

        <div id="create-menu-modal" class="admin-modal-overlay">
            <div class="admin-modal-content">
                <button type="button" class="admin-modal-close" onclick="document.getElementById('create-menu-modal').style.display='none'"><i class="fa-solid fa-times"></i></button>
                <h3 style="margin-top: 0; margin-bottom: 20px;">Create New Menu</h3>
                <form action="process-menu.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="create_menu">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Menu Name</label>
                        <input type="text" name="menu_name" class="form-input" required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Unique Identifier (e.g. footer_nav)</label>
                        <input type="text" name="menu_identifier" class="form-input" pattern="[a-zA-Z0-9_-]+" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Save Menu</button>
                </form>
            </div>
        </div>

        <div id="settings-menu-modal" class="admin-modal-overlay">
            <div class="admin-modal-content">
                <button type="button" class="admin-modal-close" onclick="document.getElementById('settings-menu-modal').style.display='none'"><i class="fa-solid fa-times"></i></button>
                <h3 style="margin-top: 0; margin-bottom: 20px;">Menu Settings</h3>
                <form action="process-menu.php" method="POST" style="margin-bottom: 10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="edit_menu">
                    <input type="hidden" name="menu_id" id="settings-menu-id" value="">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Menu Name</label>
                        <input type="text" name="menu_name" id="settings-menu-name" class="form-input" required>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 5px;">Unique Identifier</label>
                        <input type="text" name="menu_identifier" id="settings-menu-identifier" class="form-input" pattern="[a-zA-Z0-9_-]+" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Update Settings</button>
                </form>
            </div>
        </div>

        <script>
            function openSettingsModal(id, name, identifier) {
                document.getElementById('settings-menu-id').value = id;
                document.getElementById('settings-menu-name').value = name;
                document.getElementById('settings-menu-identifier').value = identifier;
                document.getElementById('settings-menu-modal').style.display = 'flex';
            }
        </script>

<?php } ?>

<?php require "inc-adm-foot.php"; ?>