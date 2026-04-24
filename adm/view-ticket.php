<?php
    $site_title = "View Ticket";
    require "inc-adm-head.php";
    require "inc-adm-nav.php";

    if ($userdata->row['user_role'] == 3) { header("Location: index.php"); die(); }

    if (isset($_SESSION['Sessionmsg'])) {
        $msgorigin = $_SESSION['Sessionmsg']['origin']; $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
        unset($_SESSION['Sessionmsg']);
    }

    $ticket_id = (int)($_GET['id'] ?? 0);
    $db = new DBConn();

    $stmt = $db->conn->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) { header("Location: tickets.php"); die(); }
    $ticket = $res->fetch_assoc();
    $stmt->close();

    $replies = [];
    $stmt_rep = $db->conn->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
    $stmt_rep->bind_param("i", $ticket_id);
    $stmt_rep->execute();
    $res_rep = $stmt_rep->get_result();
    while ($r = $res_rep->fetch_assoc()) { $replies[] = $r; }
    $stmt_rep->close();

    $staff_members = [];
    $res_staff = $db->conn->query("SELECT user_id, user_uid, user_role FROM users WHERE user_role IN (1, 2) ORDER BY user_uid ASC");
    if ($res_staff) {
        while($row = $res_staff->fetch_assoc()) { $staff_members[] = $row; }
    }
?>

<section>
    <div class="ticket-page-header">
        <h1><i class="fa-solid fa-ticket-alt"></i> Ticket: <?php echo htmlspecialchars($ticket['tracking_id']); ?></h1>
        <a href="tickets.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Tickets</a>
    </div>

    <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

    <div class="ticket-details-panel">
        <div>
            <h2 class="ticket-details-title"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
            <div class="ticket-details-meta">
                <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($ticket['client_name']); ?> 
                <a href="mailto:<?php echo htmlspecialchars($ticket['client_email']); ?>">(&lt;<?php echo htmlspecialchars($ticket['client_email']); ?>&gt;)</a>
            </div>
            <div class="ticket-assign-panel" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border);">
                <form action="process-ticket.php" method="POST" style="display: flex; align-items: center; gap: 10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="assign_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    
                    <label style="font-size: 14px; font-weight: 500;"><i class="fa-solid fa-user-tie"></i> Assigned To:</label>
                    <select name="assigned_to" class="form-input" style="width: auto; padding: 6px 12px; height: auto;" onchange="this.form.submit()">
                        <option value="0">-- Unassigned --</option>
                        <?php foreach($staff_members as $staff) { ?>
                            <option value="<?php echo $staff['user_id']; ?>" <?php if($ticket['assigned_to'] == $staff['user_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($staff['user_uid']) . ' (' . ($staff['user_role'] == 1 ? 'Admin' : 'Mod') . ')'; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <noscript><button type="submit" class="btn btn-secondary btn-sm">Save</button></noscript>
                </form>
            </div>
        </div>
        <div>
            <?php 
                $badge_class = 'badge-blue';
                if ($ticket['status'] == 'Answered') $badge_class = 'badge-green';
                if ($ticket['status'] == 'Closed') $badge_class = 'badge-gray';
            ?>
            <span class="badge <?php echo $badge_class; ?>"><?php echo $ticket['status']; ?></span>
        </div>
    </div>

    <div class="ticket-thread-panel">
        <?php foreach ($replies as $reply) { 
            
            // System Log
            if ($reply['sender_type'] === 'System') {
                $clean_msg = htmlspecialchars($reply['message']);
                $formatted_msg = str_replace(['[b]', '[/b]'], ['<strong>', '</strong>'], $clean_msg);
                echo '<div class="ticket-system-msg"><span><i class="fa-solid fa-clock-rotate-left"></i> ' . $formatted_msg . ' &bull; ' . date('M d, Y H:i', strtotime($reply['created_at'])) . '</span></div>';
                continue;
            }

            // Chat Bubble
            $is_admin = ($reply['sender_type'] === 'Admin');
            $role_class = $is_admin ? 'admin' : 'client';
            $name_tag = $is_admin ? 'You (Admin)' : htmlspecialchars($ticket['client_name']);
        ?>
            <div class="ticket-msg-wrapper <?php echo $role_class; ?>">
                <div class="ticket-msg-header">
                    <strong><?php echo $name_tag; ?></strong> &bull; <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?>
                </div>
                
                <div class="ticket-bubble"><?php echo htmlspecialchars($reply['message']); ?></div>
                
                <?php 
                // Attachments Array
                if (!empty($reply['attachment'])) { 
                    $files = json_decode($reply['attachment'], true);
                    if (!is_array($files)) { $files = [$reply['attachment']]; }
                    
                    echo '<div class="ticket-attachments">';
                    foreach ($files as $file) {
                        echo '<a href="/uploads/tickets/' . htmlspecialchars($file) . '" target="_blank" class="ticket-attachment-btn"><i class="fa-solid fa-paperclip"></i> ' . htmlspecialchars($file) . '</a>';
                    }
                    echo '</div>';
                } 
                ?>
            </div>
        <?php } ?>
    </div>

    <div class="ticket-reply-panel">
        <form action="process-ticket.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="admin_reply">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">

            <div style="margin-bottom: 20px;">
                <label class="ticket-form-label">Post a Reply</label>
                <textarea name="message" class="form-input" style="height: 120px; resize: vertical;" placeholder="Type your response to the client here..." required></textarea>
            </div>
            
            <div>
                <label class="ticket-form-label"><i class="fa-solid fa-paperclip"></i> Attach Files (Optional)</label>
                <input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="multi-file-input ticket-file-drop">
                <div class="file-list-preview" style="display: flex; flex-direction: column; gap: 5px;"></div>
                <div class="ticket-file-helper"><i class="fa-solid fa-circle-info"></i> Max size: 5MB per file. Allowed formats: JPG, PNG, WEBP, PDF, TXT.</div>
            </div>

            <div class="ticket-form-actions">
                <div class="ticket-status-group">
                    <label>Change Status to:</label>
                    <select name="status_update" class="form-input">
                        <option value="Answered" <?php if($ticket['status'] != 'Closed') echo 'selected'; ?>>Answered (Awaiting Client)</option>
                        <option value="Open">Open (Still working on it)</option>
                        <option value="Closed" <?php if($ticket['status'] == 'Closed') echo 'selected'; ?>>Closed (Resolved)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send & Update</button>
            </div>
        </form>
    </div>
</section>

<?php require "inc-adm-foot.php"; ?>