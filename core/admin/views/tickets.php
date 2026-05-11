<?php
// /core/admin/views/tickets.php
$site_title = "Support Tickets";
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
        <h1 class="admin-header-title"><i class="fa-solid fa-ticket-alt mr-10"></i> Tickets</h1>
    </div>

    <div class="d-flex align-center justify-between mb-20">
        <div class="d-flex align-center gap-10">
            <input type="text" id="filter-search" class="form-input" placeholder="Search ID, Subject, Client..." style="width: 250px; margin: 0;">
            <select id="filter-status" class="form-input" style="width: auto; margin: 0;">
                <option value="All">All Statuses</option>
                <option value="Open">Open</option>
                <option value="Answered">Answered</option>
                <option value="Closed">Closed</option>
            </select>
            <button class="btn btn-secondary" onclick="loadList(1)"><i class="fa-solid fa-filter mr-5"></i> Filter</button>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tracking ID</th>
                    <th>Subject / Client</th>
                    <th>Status</th>
                    <th>Staff</th>
                    <th>Last Updated</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="tickets-table-body">
                <tr><td colspan="6" class="text-center p-20"><i class="fa-solid fa-spinner fa-spin mr-10"></i> Loading tickets...</td></tr>
            </tbody>
        </table>
    </div>
    
    <div id="tickets-pagination"></div>
</section>

<section id="view-ticket" style="display: none;">
    <div class="admin-header-row">
        <h1 class="admin-header-title" id="ticket-header-title"></h1>
        <div class="d-flex gap-10">
            <button class="btn btn-secondary" onclick="showList()"><i class="fa-solid fa-arrow-left"></i> Back to Tickets</button>
            <button class="btn btn-red" id="header-delete-ticket-btn"><i class="fa-solid fa-trash-alt"></i> Delete Ticket</button>
        </div>
    </div>

    <div class="ticket-grid">
        
        <div>
            <div class="ticket-card mb-20">
                <div id="ticket-thread-container" class="ticket-thread-box">
                    </div>
            </div>

            <div class="ticket-card">
                <form id="ajax-reply-form" enctype="multipart/form-data">
                    <input type="hidden" id="reply-ticket-id" name="ticket_id">
                    <h3 class="mt-0 mb-15"><i class="fa-solid fa-reply mr-5"></i> Post a Reply</h3>
                    
                    <div class="form-group">
                        <textarea name="message" class="form-input ticket-reply-textarea" placeholder="Type your response to the client here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fa-solid fa-paperclip mr-5"></i> Attach Files (Optional)</label>
                        <input type="file" name="attachment[]" id="file-input" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="form-input">
                        <div id="file-preview" class="text-muted mt-10" style="font-size: 13px;">Max size: 5MB per file. Formats: JPG, PNG, WEBP, PDF, TXT.</div>
                    </div>

                    <div class="d-flex justify-between align-center ticket-reply-footer">
                        <div class="d-flex align-center gap-10">
                            <label class="ticket-status-select-label">Change Status:</label>
                            <select name="status_update" id="reply-status-select" class="form-input ticket-status-select">
                                <option value="Answered">Answered (Awaiting Client)</option>
                                <option value="Open">Open (Still working on it)</option>
                                <option value="Closed">Closed (Resolved)</option>
                            </select>
                        </div>
                        <button type="submit" id="reply-submit-btn" class="btn btn-primary"><i class="fa-solid fa-paper-plane mr-5"></i> Send & Update</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="ticket-card ticket-sidebar">
            
            <div class="ticket-info-box">
                <div class="text-muted ticket-info-label">Subject</div>
                <h3 class="mt-0 mb-15 ticket-info-subject" id="detail-subject"></h3>
                
                <div class="text-muted ticket-info-label" style="margin-bottom: 8px;">Client Profile</div>
                <div class="d-flex align-center gap-10">
                    <div id="detail-avatar" class="ticket-client-avatar"></div>
                    <div class="text-muted ticket-client-details" id="detail-client"></div>
                </div>
            </div>
            
            <div class="d-flex align-center justify-between mb-20 pb-20 ticket-status-row">
                <span class="ticket-status-label">Current Status</span>
                <span class="badge ticket-status-badge-lg" id="detail-status-badge"></span>
            </div>

            <div>
                <form id="ajax-assign-form">
                    <input type="hidden" id="assign-ticket-id" name="ticket_id">
                    <label class="d-flex align-center gap-10 mb-10 ticket-assign-label"><i class="fa-solid fa-user-tie text-muted"></i> Assigned Staff</label>
                    <select name="assigned_to" id="assign-select" class="form-input w-100"></select>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="admin-modal-overlay" id="delete-modal">
    <div class="admin-modal-content text-center modal-sm">
        <i class="fa-solid fa-triangle-exclamation modal-icon-lg"></i>
        <h2 class="modal-title-lg">Are you sure?</h2>
        <p class="text-muted modal-text">This will permanently delete this ticket and all attached files. This action cannot be undone.</p>
        <div class="d-flex justify-center gap-15">
            <button class="btn btn-outline btn-lg" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-red btn-lg" id="confirm-delete-btn"><i class="fa-solid fa-trash-alt mr-5"></i> Delete</button>
        </div>
    </div>
</div>

<script src="/core/admin/assets/tickets.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../components/footer.php'; ?>