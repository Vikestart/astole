<?php
// /core/admin/views/menus.php
$site_title = "Menu Manager";
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/nav.php';

if (isset($userdata->row['user_role']) && $userdata->row['user_role'] == 3) { 
    header("Location: /adm"); die(); 
}
?>

<input type="hidden" id="global-csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
<div id="ajax-msgbox" style="display: none;"></div>

<section id="view-list">
    <div class="admin-header-row">
        <h1 class="admin-header-title"><i class="fa-solid fa-list-ul mr-10"></i> Menus</h1>
        <button class="btn btn-primary" onclick="document.getElementById('create-menu-modal').style.display='flex'"><i class="fa-solid fa-plus"></i> Create Menu</button>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Menu Name</th>
                    <th>Identifier</th>
                    <th>Links</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="menus-table-body">
                <tr><td colspan="4" style="text-align: center; padding: 30px;"><i class="fa-solid fa-spinner fa-spin mr-10"></i> Loading menus...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section id="view-builder" style="display: none;">
    <div class="admin-header-row">
        <h1 class="admin-header-title" id="builder-title"></h1>
        <div class="d-flex gap-10">
            <button class="btn btn-secondary" onclick="showList()"><i class="fa-solid fa-arrow-left"></i> Back to Menus</button>
            <button class="btn btn-red" id="header-delete-menu-btn"><i class="fa-solid fa-trash-alt"></i> Delete Menu</button>
        </div>
    </div>

    <input type="hidden" id="active-menu-id" value="0">

    <div class="menu-manager-grid">
        <div class="menu-structure-panel">
            <div class="d-flex justify-between align-center mb-15">
                <h3 class="mb-0">Structure</h3>
            </div>
            <p class="text-muted mb-20"><i class="fa-solid fa-circle-info mr-5"></i> Drag the icons to reorder items or create submenus. Changes are saved automatically.</p>
            
            <div id="sortable-menu">
                <div class="text-center p-20 text-muted">Loading menu items...</div>
            </div>
        </div>

        <div class="menu-form-panel">
            <h3 class="mt-0 mb-20">Add Menu Link</h3>
            
            <form id="ajax-add-item-form">
                <div class="form-group">
                    <label>Link Title</label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. About Us" required>
                </div>

                <div class="form-group">
                    <label>Link Type</label>
                    <div class="radio-toggle-group">
                        <input type="radio" name="link_type" id="add_type_url" value="url" checked>
                        <label for="add_type_url"><i class="fa-solid fa-link"></i> Custom URL</label>
                        
                        <input type="radio" name="link_type" id="add_type_page" value="page">
                        <label for="add_type_page"><i class="fa-solid fa-file-alt"></i> Existing Page</label>
                    </div>
                </div>

                <div class="form-group" id="wrapper-url">
                    <label>Destination URL</label>
                    <input type="text" name="url" class="form-input" placeholder="e.g. /about.php or https://...">
                </div>

                <div class="form-group" id="wrapper-page" style="display: none;">
                    <label>Select Page</label>
                    <select name="page_id" class="form-input page-select-dropdown">
                        <option value="">-- Loading --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Parent Item</label>
                    <select name="parent_id" class="form-input dynamic-parent-select">
                        <option value="">-- Loading --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Open In</label>
                    <div class="radio-toggle-group">
                        <input type="radio" name="target" id="add_target_self" value="_self" checked>
                        <label for="add_target_self">Same Tab</label>
                        
                        <input type="radio" name="target" id="add_target_blank" value="_blank">
                        <label for="add_target_blank">New Tab</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 justify-center"><i class="fa-solid fa-plus mr-5"></i> Add Link</button>
            </form>
        </div>
    </div>
</section>

<div id="create-menu-modal" class="admin-modal-overlay">
    <div class="admin-modal-content">
        <button type="button" class="admin-modal-close" onclick="document.getElementById('create-menu-modal').style.display='none'"><i class="fa-solid fa-times"></i></button>
        <h3 class="mt-0 mb-20">Create New Menu</h3>
        <form id="create-menu-form">
            <div class="form-group">
                <label>Menu Name</label>
                <input type="text" name="menu_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Unique Identifier (e.g. footer_nav)</label>
                <input type="text" name="menu_identifier" class="form-input" pattern="[a-zA-Z0-9_-]+" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 justify-center">Save Menu</button>
        </form>
    </div>
</div>

<div id="settings-menu-modal" class="admin-modal-overlay">
    <div class="admin-modal-content">
        <button type="button" class="admin-modal-close" onclick="document.getElementById('settings-menu-modal').style.display='none'"><i class="fa-solid fa-times"></i></button>
        <h3 class="mt-0 mb-20">Menu Settings</h3>
        <form id="edit-menu-form">
            <input type="hidden" name="menu_id" id="settings-menu-id">
            <div class="form-group">
                <label>Menu Name</label>
                <input type="text" name="menu_name" id="settings-menu-name" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Unique Identifier</label>
                <input type="text" name="menu_identifier" id="settings-menu-identifier" class="form-input" pattern="[a-zA-Z0-9_-]+" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 justify-center">Update Settings</button>
        </form>
    </div>
</div>

<div id="edit-item-modal" class="admin-modal-overlay">
    <div class="admin-modal-content">
        <button type="button" class="admin-modal-close" onclick="document.getElementById('edit-item-modal').style.display='none'"><i class="fa-solid fa-times"></i></button>
        <h3 class="mt-0 mb-20">Edit Link</h3>
        <form id="ajax-edit-item-form">
            <input type="hidden" name="item_id" id="edit-item-id">

            <div class="form-group">
                <label>Link Title</label>
                <input type="text" name="title" id="edit-title" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Link Type</label>
                <div class="radio-toggle-group">
                    <input type="radio" name="link_type" id="edit_type_url" value="url">
                    <label for="edit_type_url"><i class="fa-solid fa-link"></i> Custom URL</label>
                    
                    <input type="radio" name="link_type" id="edit_type_page" value="page">
                    <label for="edit_type_page"><i class="fa-solid fa-file-alt"></i> Existing Page</label>
                </div>
            </div>

            <div class="form-group" id="edit-wrapper-url">
                <label>Destination URL</label>
                <input type="text" name="url" id="edit-url" class="form-input">
            </div>

            <div class="form-group" id="edit-wrapper-page" style="display: none;">
                <label>Select Page</label>
                <select name="page_id" id="edit-page" class="form-input page-select-dropdown">
                    <option value="">-- Loading --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Parent Item</label>
                <select name="parent_id" id="edit-parent" class="form-input dynamic-parent-select">
                    <option value="">-- Loading --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Open In</label>
                <div class="radio-toggle-group">
                    <input type="radio" name="target" id="edit_target_self" value="_self">
                    <label for="edit_target_self">Same Tab</label>
                    
                    <input type="radio" name="target" id="edit_target_blank" value="_blank">
                    <label for="edit_target_blank">New Tab</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 justify-center">Save Changes</button>
        </form>
    </div>
</div>

<div class="admin-modal-overlay" id="delete-modal">
    <div class="admin-modal-content" style="text-align: center; padding: 40px 25px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: var(--danger); margin-bottom: 20px;"></i>
        <h2 style="margin-bottom: 10px; color: var(--color-heading); justify-content: center;">Are you sure?</h2>
        <p style="color: var(--text-muted); margin-bottom: 30px;">This action is permanent and cannot be undone.</p>
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-red" id="confirm-delete-btn"><i class="fa-solid fa-trash-alt mr-10"></i> Delete</button>
        </div>
    </div>
</div>

<script src="/core/admin/assets/menus.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../components/footer.php'; ?>