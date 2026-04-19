<?php
if (isset($_GET['t'])) {
    $page_type = $_GET['t'];
    if ($page_type == "new") {
        $site_title = "New Page";
        $page_isnew = true;
        $page_title = '';
        $page_slug = '';
        $page_contents = '';
    } else if ($page_type == "edit") {
        $site_title = "Edit Page";
        $page_isnew = false;
    } else {
        header("Location: pages.php"); die();
    }
} else {
    header("Location: pages.php"); die();
}

require "inc-adm-head.php";
require "inc-adm-nav.php";

if ($page_isnew === false) {
    $page_id = (int)$_GET['p'];
    $_SESSION['acp_page_id'] = $page_id;
    $mysqli = new DBConn();
    
    // Updated Query to grab the author
    $stmt = $mysqli->conn->prepare("SELECT page_title, page_slug, page_contents, page_author FROM pages WHERE page_id = ?");
    $stmt->bind_param("i", $page_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $page_title = $row['page_title'];
        $page_slug = $row['page_slug'];
        $page_contents = $row['page_contents'];
        $page_author = $row['page_author'];
        
        // SECURITY: Boot out Standard Users trying to edit someone else's page
        if ($userdata->row['user_role'] == 3 && $page_author !== $userdata->row['user_uid']) {
            header("Location: pages.php"); die();
        }
        
    } else {
        header("Location: pages.php"); die();
    }
    $stmt->close();
}

if (isset($_SESSION['Sessionmsg'])) {
    $msgtype = $_SESSION['Sessionmsg']['type'];
    $msgicon = $_SESSION['Sessionmsg']['icon'];
    $msgtxt = $_SESSION['Sessionmsg']['message'];
    unset($_SESSION['Sessionmsg']);
}
?>
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-<?php echo ($page_isnew) ? "file-plus" : "file-pen"; ?>"></i> <?php echo ($page_isnew) ? "Create New Page" : "Edit Page: " . htmlspecialchars($page_title); ?></h1>
        <?php if (!$page_isnew) { ?>
            <form action="process-page.php" method="POST" class="form-delete" style="display:inline;">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
				<input type="hidden" name="action" value="delpage">
				<input type="hidden" name="p" value="<?php echo htmlspecialchars($page_id); ?>">
				<button type="submit" class="btn btn-red"><i class="fa-solid fa-trash-alt"></i> Delete</button>
			</form>
        <?php } ?>
    </div>

    <form action="process-page.php" method="POST" autocomplete="off" id="page-form">
        <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input name="action" value="<?php echo ($page_isnew) ? "newpage" : "editpage"; ?>" type="hidden" />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Page Title</label>
                <input name="pagetitle" class="form-input" type="text" maxlength="50" value="<?php echo htmlspecialchars($page_title); ?>" required />
            </div>
            <div class="form-group">
                <label>URL Slug (e.g., 'about-me')</label>
                <input name="pageslug" class="form-input" type="text" maxlength="100" value="<?php echo htmlspecialchars($page_slug); ?>" required />
            </div>
        </div>

        <div class="form-group">
            <label>Page Content</label>
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

    document.getElementById('page-form').addEventListener('submit', function(e) {
        document.getElementById('hidden-pagecontents').value = quill.root.innerHTML;
        if (quill.getText().trim().length === 0) {
            e.preventDefault();
            alert("Please enter some content before saving.");
        }
    });
</script>
<?php require "inc-adm-foot.php"; ?>