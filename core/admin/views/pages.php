<?php
// /core/admin/views/pages.php
$site_title = "Manage Pages";
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/nav.php';
?>

<input type="hidden" id="global-csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

<div id="ajax-msgbox" style="display: none;"></div>

<section id="view-list">
    <div class="admin-header-row">
        <h1 class="admin-header-title"><i class="fa-solid fa-file-lines mr-10"></i> Page Management</h1>
        <button class="btn btn-primary" onclick="showForm(0)"><i class="fa-solid fa-plus"></i> Create New</button>
    </div>

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
            <tbody id="pages-table-body">
                <tr><td colspan="6" style="text-align: center; padding: 30px;"><i class="fa-solid fa-spinner fa-spin mr-10"></i> Loading pages...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section id="view-form" style="display: none;">
    <div class="admin-header-row">
        <h1 class="admin-header-title" id="form-view-title"></h1>
        <div class="d-flex gap-10">
            <button class="btn btn-secondary" onclick="showList()"><i class="fa-solid fa-arrow-left"></i> Back to List</button>
            <button class="btn btn-red" id="header-delete-page-btn" style="display: none;"><i class="fa-solid fa-trash-alt"></i> Delete Page</button>
        </div>
    </div>

    <form autocomplete="off" id="page-form">
        <input type="hidden" name="page_id" id="page-id" value="0">

        <div class="grid-2-col mb-20">
            <div class="form-group mb-0">
                <label>Page Title <span class="req-ast">*</span></label>
                <input name="pagetitle" id="page-title-input" class="form-input" type="text" maxlength="50" required />
            </div>
            <div class="form-group mb-0">
                <label>URL Slug <span class="req-ast">*</span> <span class="tooltip-icon" data-tooltip="The web address for this page (e.g., 'about-me'). Use lowercase letters and dashes."><i class="fa-solid fa-question"></i></span></label>
                <input name="pageslug" id="page-slug-input" class="form-input" type="text" maxlength="40" required />
            </div>
        </div>

        <div class="grid-2-col mb-20">
            <div class="form-group mb-0">
                <label>Page Type <span class="req-ast">*</span></label>
                <select name="pagetype" id="page-type-selector" class="form-input">
                    <option value="standard">Standard Page</option>
                    <option value="ticket_portal">Ticket Portal</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>SEO Meta Description <span class="tooltip-icon" data-tooltip="A brief summary for search engines. Leave blank to use the global default."><i class="fa-solid fa-question"></i></span></label>
                <input name="pagedesc" id="pagedesc" class="form-input" type="text" maxlength="160" />
            </div>
        </div>

        <div class="form-group" id="content-group">
            <label>Page Content <span class="req-ast">*</span></label>
            <input type="hidden" name="pagecontents" id="hidden-pagecontents">
            <div id="editor-container" class="editor-wrap"></div>
        </div>

        <div class="mt-20">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save mr-10"></i> Save Page</button>
        </div>
    </form>
</section>

<div class="admin-modal-overlay" id="delete-modal">
    <div class="admin-modal-content" style="text-align: center; padding: 40px 25px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: var(--danger); margin-bottom: 20px;"></i>
        
        <h2 style="margin-bottom: 10px; color: var(--color-heading); justify-content: center;">Are you sure?</h2>
        
        <p style="color: var(--text-muted); margin-bottom: 30px;">This will permanently delete this page. This action cannot be undone.</p>
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-red" id="confirm-delete-btn"><i class="fa-solid fa-trash-alt mr-10"></i> Delete</button>
        </div>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.js"></script>
<script src="/core/admin/assets/pages.js?v=<?php echo time(); ?>"></script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>