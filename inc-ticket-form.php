<?php
// Ensure this file is only loaded if the ticket system is enabled globally
if (isset($tkt_settings['ticket_system_enabled']) && $tkt_settings['ticket_system_enabled'] == '1') {
?>
<div class="ticket-portal-wrapper" style="margin-top: 20px;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="font-size: 24px; color: var(--color-heading); margin-bottom: 10px;">Support Portal</h2>
        <p style="color: #64748b; margin: 0;">Open a new ticket or check the status of an existing one.</p>
    </div>

    <div id="tp-selection" style="text-align: center;">
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

    <div id="tp-new-ticket" style="display: none; background: #f8fafc; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0; max-width: 650px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 1.25rem;"><i class="fa-solid fa-plus-circle" style="color: var(--color-primary); margin-right: 8px;"></i> Open a Support Ticket</h3>
            <button onclick="showTicketForm('selection')" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 14px; font-weight: 600; padding: 5px; transition: color 0.2s;" onmouseover="this.style.color='#0f172a';" onmouseout="this.style.color='#64748b';"><i class="fa-solid fa-arrow-left"></i> Back</button>
        </div>
        
        <?php if ($tkt_settings['ticket_creation_enabled'] == '1') { ?>
            <form action="process-ticket.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="action" value="new_ticket">
                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($route ?? 'home'); ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label class="ticket-form-label">Your Name</label>
                        <input type="text" name="client_name" class="ticket-textarea" style="height: auto; padding: 10px;" required>
                    </div>
                    <div>
                        <label class="ticket-form-label">Email Address</label>
                        <input type="email" name="client_email" class="ticket-textarea" style="height: auto; padding: 10px;" required>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label class="ticket-form-label">Subject</label>
                    <input type="text" name="subject" class="ticket-textarea" style="height: auto; padding: 10px;" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label class="ticket-form-label">Message Details</label>
                    <textarea name="message" class="ticket-textarea" required></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="ticket-form-label"><i class="fa-solid fa-paperclip"></i> Attach Files (Optional)</label>
                    <input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="multi-file-input ticket-file-drop">
                    <div class="file-list-preview" style="display: flex; flex-direction: column; gap: 5px; margin-top: 8px;"></div>
                    <div class="ticket-file-helper"><i class="fa-solid fa-circle-info"></i> Max size: 5MB per file. Allowed formats: JPG, PNG, WEBP, PDF, TXT.</div>
                </div>

                <?php if (!empty($rc_site)) { ?>
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($rc_site); ?>" data-action="new_ticket" style="margin-bottom: 15px;"></div>
                <?php } ?>

                <button type="submit" class="ticket-btn-primary" style="width: 100%;">Submit Ticket</button>
            </form>
        <?php } else { ?>
            <div class="ticket-closed-panel" style="padding: 15px;">
                <i class="fa-solid fa-lock ticket-closed-icon" style="font-size: 20px;"></i>
                <p class="ticket-closed-text" style="margin-top: 10px;">Ticket creation is currently disabled.</p>
            </div>
        <?php } ?>
    </div>

    <div id="tp-check-status" style="display: none; background: #f8fafc; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0; max-width: 600px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 1.25rem;"><i class="fa-solid fa-search" style="color: var(--color-primary); margin-right: 8px;"></i> Check Ticket Status</h3>
            <button onclick="showTicketForm('selection')" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 14px; font-weight: 600; padding: 5px; transition: color 0.2s;" onmouseover="this.style.color='#0f172a';" onmouseout="this.style.color='#64748b';"><i class="fa-solid fa-arrow-left"></i> Back</button>
        </div>

        <form action="view-ticket.php" method="GET">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div>
                    <label class="ticket-form-label">Tracking ID</label>
                    <input type="text" name="id" class="ticket-textarea" style="height: auto; padding: 10px;" placeholder="TKT-XXXXXX" required>
                </div>
                <div>
                    <label class="ticket-form-label">Email Address</label>
                    <input type="email" name="email" class="ticket-textarea" style="height: auto; padding: 10px;" required>
                </div>
            </div>
            
            <button type="submit" class="ticket-btn-primary" style="width: 100%; background: #475569;">View Ticket</button>
        </form>
    </div>

    <script>
        function showTicketForm(type) {
            document.getElementById('tp-selection').style.display = 'none';
            document.getElementById('tp-new-ticket').style.display = 'none';
            document.getElementById('tp-check-status').style.display = 'none';
            
            if(type === 'new') {
                document.getElementById('tp-new-ticket').style.display = 'block';
            } else if(type === 'status') {
                document.getElementById('tp-check-status').style.display = 'block';
            } else {
                document.getElementById('tp-selection').style.display = 'block';
            }
        }
    </script>

</div>
<?php } ?>