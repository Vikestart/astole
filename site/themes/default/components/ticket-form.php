<?php
// Fetch ticket settings for this component
$db = new \Core\Lib\Database();
$res = $db->getConnection()->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('recaptcha_site', 'ticket_system_enabled', 'ticket_creation_enabled')");

// CRITICAL FIX: Set default fallback values just like your original architecture
$tkt_settings = [
    'ticket_system_enabled' => '1',
    'ticket_creation_enabled' => '1'
];
$rc_site = '';

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tkt_settings[$row['setting_key']] = $row['setting_value'];
        if ($row['setting_key'] === 'recaptcha_site') {
            $rc_site = trim($row['setting_value']);
        }
    }
}

if (isset($tkt_settings['ticket_system_enabled']) && $tkt_settings['ticket_system_enabled'] == '1'):
?>
<div class="ticket-portal-wrapper mt-20">
    
    <div class="text-center mb-30">
        <h2 class="portal-title">Ticket Portal</h2>
        <p class="portal-subtitle">Open a new ticket or check the status of an existing one.</p>
    </div>

    <div id="tp-selection" class="text-center">
        <div class="ticket-choice-grid">
            
            <div onclick="showTicketForm('new')" class="ticket-choice-card">
                <div class="ticket-choice-icon"><i class="fa-solid fa-plus-circle"></i></div>
                <h4 class="ticket-choice-title">Open a New Ticket</h4>
                <p class="ticket-choice-desc">Submit a new query, request, or technical issue to our support team.</p>
                <div class="ticket-choice-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </div>

            <div onclick="showTicketForm('status')" class="ticket-choice-card">
                <div class="ticket-choice-icon"><i class="fa-solid fa-search"></i></div>
                <h4 class="ticket-choice-title">Check Ticket Status</h4>
                <p class="ticket-choice-desc">View updates, track progress, or reply to an existing support ticket.</p>
                <div class="ticket-choice-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </div>

        </div>
    </div>

    <div id="tp-new-ticket" class="ticket-form-card max-w-650" style="display: none;">
        <div class="d-flex justify-between align-center mb-20">
            <h3 class="ticket-reply-title" style="margin:0;"><i class="fa-solid fa-plus-circle mr-5" style="color: var(--color-primary);"></i> Open a Support Ticket</h3>
            <button onclick="showTicketForm('selection')" class="btn-back"><i class="fa-solid fa-arrow-left mr-5"></i> Back</button>
        </div>
        
        <?php if ($tkt_settings['ticket_creation_enabled'] == '1'): ?>
            <form action="/core/actions/front/process-ticket.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="action" value="new_ticket">
                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($route ?? 'home'); ?>">

                <div class="grid-2-col mb-15">
                    <div>
                        <label class="ticket-form-label">Your Name</label>
                        <input type="text" name="client_name" class="ticket-textarea ticket-input" required>
                    </div>
                    <div>
                        <label class="ticket-form-label">Email Address</label>
                        <input type="email" name="client_email" class="ticket-textarea ticket-input" required>
                    </div>
                </div>
                
                <div class="mb-15">
                    <label class="ticket-form-label">Subject</label>
                    <input type="text" name="subject" class="ticket-textarea ticket-input" required>
                </div>
                
                <div class="mb-15">
                    <label class="ticket-form-label">Message Details</label>
                    <textarea name="message" class="ticket-textarea" required></textarea>
                </div>

                <div class="mb-20">
                    <label class="ticket-form-label"><i class="fa-solid fa-paperclip mr-5"></i> Attach Files (Optional)</label>
                    <input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="multi-file-input ticket-file-drop">
                    <div class="file-list-preview d-flex flex-col gap-5 mt-8"></div>
                    <div class="ticket-file-helper"><i class="fa-solid fa-circle-info mr-5"></i> Max size: 5MB per file. Allowed formats: JPG, PNG, WEBP, PDF, TXT.</div>
                </div>

                <?php if (!empty($rc_site)): ?>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <div class="g-recaptcha mb-15" data-sitekey="<?php echo htmlspecialchars($rc_site); ?>" data-action="new_ticket"></div>
                <?php endif; ?>

                <button type="submit" class="ticket-btn-primary w-100">Submit Ticket</button>
            </form>
        <?php else: ?>
            <div class="ticket-closed-panel" style="padding: 15px;">
                <i class="fa-solid fa-lock ticket-closed-icon" style="font-size: 20px;"></i>
                <p class="ticket-closed-text mt-8">Ticket creation is currently disabled.</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="tp-check-status" class="ticket-form-card max-w-600" style="display: none;">
        <div class="d-flex justify-between align-center mb-20">
            <h3 class="ticket-reply-title" style="margin:0;"><i class="fa-solid fa-search mr-5" style="color: var(--color-primary);"></i> Check Ticket Status</h3>
            <button onclick="showTicketForm('selection')" class="btn-back"><i class="fa-solid fa-arrow-left mr-5"></i> Back</button>
        </div>

        <form action="/ticket" method="GET">
            <div class="grid-2-col mb-20">
                <div>
                    <label class="ticket-form-label">Tracking ID</label>
                    <input type="text" name="id" class="ticket-textarea ticket-input" placeholder="TKT-XXXXXX" required>
                </div>
                <div>
                    <label class="ticket-form-label">Email Address</label>
                    <input type="email" name="email" class="ticket-textarea ticket-input" required>
                </div>
            </div>
            
            <button type="submit" class="ticket-btn-primary btn-secondary w-100">View Ticket</button>
        </form>
    </div>

</div>
<?php endif; ?>