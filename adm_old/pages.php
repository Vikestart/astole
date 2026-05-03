<?php
$site_title = "Manage Pages";
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
    $page_isnew = ($view_action === 'new');
    
    if ($page_isnew) {
        $site_title = "New Page";
        $page_title = '';
        $page_slug = '';
        $page_desc = '';
        $page_contents = '';
        $db_page_type = 'Standard';
    } else {
        $site_title = "Edit Page";
        if (!isset($_GET['p'])) { header("Location: pages"); die(); }
        
        $page_id = (int)$_GET['p'];
        $_SESSION['acp_page_id'] = $page_id;
        
        $stmt = $db->conn->prepare("SELECT page_title, page_slug, page_desc, page_contents, page_author, page_type FROM pages WHERE page_id = ?");
        $stmt->bind_param("i", $page_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $page_title = $row['page_title'];
            $page_slug = $row['page_slug'];
            $page_desc = $row['page_desc'];
            $page_contents = $row['page_contents'];
            $page_author = $row['page_author'];
            $db_page_type = $row['page_type'] ?? 'Standard';
            
            // SECURITY: Boot out Standard Users trying to edit someone else's page
            if ($userdata->row['user_role'] == 3 && $page_author !== $userdata->row['user_uid']) {
                header("Location: pages"); die();
            }
        } else {
            header("Location: pages"); die();
        }
        $stmt->close();
    }
?>
    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
            <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-<?php echo ($page_isnew) ? "file-plus" : "file-pen"; ?>"></i> <?php echo ($page_isnew) ? "Create New Page" : "Edit Page: " . htmlspecialchars($page_title); ?></h1>
            
            <div style="display: flex; gap: 10px;">
                <a href="pages" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <?php if (!$page_isnew) { ?>
                    <form action="process-page" method="POST" class="form-delete" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this page?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="delpage">
                        <input type="hidden" name="p" value="<?php echo htmlspecialchars($page_id); ?>">
                        <button type="submit" class="btn btn-red"><i class="fa-solid fa-trash-alt"></i> Delete</button>
                    </form>
                <?php } ?>
            </div>
        </div>

        <form action="process-page" method="POST" autocomplete="off" id="page-form">
            <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input name="action" value="<?php echo ($page_isnew) ? "newpage" : "editpage"; ?>" type="hidden" />

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Page Title <span class="req-ast">*</span></label>
                    <input name="pagetitle" class="form-input" type="text" maxlength="50" value="<?php echo htmlspecialchars($page_title); ?>" required />
                </div>
                <div class="form-group">
                    <label>URL Slug <span class="req-ast">*</span> <span class="tooltip-icon" data-tooltip="The web address for this page (e.g., 'about-me'). Use lowercase letters and dashes."><i class="fa-solid fa-question"></i></span></label>
                    <input name="pageslug" class="form-input" type="text" maxlength="100" value="<?php echo htmlspecialchars($page_slug); ?>" required />
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group" style="margin: 0;">
                    <label>Page Type <span class="req-ast">*</span></label>
                    <select name="pagetype" id="page-type-selector" class="form-input">
                        <option value="Standard" <?php if($db_page_type === 'Standard') echo 'selected'; ?>>Standard Page</option>
                        <option value="Ticket portal" <?php if($db_page_type === 'Ticket portal') echo 'selected'; ?>>Ticket Portal</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>SEO Meta Description <span class="tooltip-icon" data-tooltip="A brief summary for search engines. Leave blank to use the global default."><i class="fa-solid fa-question"></i></span></label>
                    <input name="pagedesc" class="form-input" type="text" maxlength="160" value="<?php echo htmlspecialchars($page_desc); ?>" />
                </div>
            </div>

            <div class="form-group" id="content-group">
                <label>Page Content <span class="req-ast">*</span></label>
                <input type="hidden" name="pagecontents" id="hidden-pagecontents">
                <div id="editor-container" style="height: 400px; background: #fff; border-radius: 8px;">
                    <?php if (!$page_isnew) echo $page_contents; ?>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="margin-top: 10px;"><i class="fa-solid fa-save"></i> Save Page</button>
        </form>
    </section>

    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.js"></script>
    <script>
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Start writing your content here...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });

        const typeSelector = document.getElementById('page-type-selector');
        const contentGroup = document.getElementById('content-group');

        function toggleEditor() {
            if (typeSelector.value === 'Ticket portal') {
                contentGroup.style.display = 'none';
            } else {
                contentGroup.style.display = 'block';
            }
        }
        if(typeSelector) {
            typeSelector.addEventListener('change', toggleEditor);
            toggleEditor();
        }

        const pageForm = document.getElementById('page-form');
        if(pageForm) {
            pageForm.addEventListener('submit', function(e) {
                if (typeSelector.value === 'Standard') {
                    document.getElementById('hidden-pagecontents').value = quill.root.innerHTML;
                    if (quill.getText().trim().length === 0) {
                        e.preventDefault();
                        alert("Please enter some content before saving a Standard page.");
                    }
                } else {
                    document.getElementById('hidden-pagecontents').value = ''; 
                }
            });
        }
    </script>

<?php 
// --- VIEW 2: LIST ALL PAGES ---
} else { 
    $pages_res = $db->conn->query("SELECT * FROM pages ORDER BY page_title");
?>
    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
            <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-file-lines"></i> Page Management</h1>
            <a class="btn btn-primary" href="pages?action=new"><i class="fa-solid fa-plus"></i> Create New</a>
        </div>

        <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>URL Slug</th>
                        <th>Type</th>
                        <th>Author</th>
                        <th>Last Updated</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pages_res && $pages_res->num_rows > 0) {
                        while($row = $pages_res->fetch_assoc()) { 
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
                                        <a href="pages?action=edit&p=<?php echo htmlspecialchars($row['page_id']); ?>" style="color: var(--color-heading); font-weight: 700; text-decoration: none; transition: color 0.2s;">
                                            <?php echo htmlspecialchars($row['page_title']); ?>
                                        </a>
                                    <?php } else { ?>
                                        <strong style="color: var(--color-heading); font-weight: 700;"><?php echo htmlspecialchars($row['page_title']); ?></strong>
                                    <?php } ?>
                                </td>
                                <td><span class="badge badge-blue badge-noborder">/<?php echo htmlspecialchars($row['page_slug']); ?></span></td>
                                <td><span class="badge badge-gray"><?php echo htmlspecialchars($row['page_type'] ?? 'Standard'); ?></span></td>
                                <td><?php echo htmlspecialchars($row['page_author']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['page_updated']))); ?></td>
                                <td style="text-align: right;" class="table-actions">
                                    
                                    <a href="../<?php echo htmlspecialchars($row['page_slug']); ?>" target="_blank" title="View"><i class="fa-solid fa-external-link-alt"></i></a>
                                    
                                    <?php if ($can_modify) { ?>
                                        <a href="pages?action=edit&p=<?php echo htmlspecialchars($row['page_id']); ?>" title="Edit" style="margin-left: 10px;"><i class="fa-solid fa-edit"></i></a>
                                        
                                        <form action="process-page" method="POST" class="form-delete" style="display:inline-block; margin-left: 15px;" onsubmit="return confirm('Are you sure you want to permanently delete this page?');">
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
                        <tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No pages found. Create one!</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
<?php } ?>

<?php require "inc-adm-foot.php"; ?>