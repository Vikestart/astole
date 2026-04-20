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
?>

<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-ticket-alt"></i> Ticket: <?php echo htmlspecialchars($ticket['tracking_id']); ?></h1>
        <a href="tickets.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Tickets</a>
    </div>

    <?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

    <div style="background: var(--bg-body); padding: 25px; border-radius: 8px 8px 0 0; border: 1px solid var(--border); border-bottom: none; display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h2 style="margin: 0 0 5px 0; font-size: 20px; color: var(--color-heading);"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
            <div style="color: var(--text-muted); font-size: 14px;">
                <i class="fa-solid fa-user" style="margin-right: 5px;"></i> <?php echo htmlspecialchars($ticket['client_name']); ?> 
                <a href="mailto:<?php echo htmlspecialchars($ticket['client_email']); ?>" style="color: var(--text-main); margin-left: 5px;">(&lt;<?php echo htmlspecialchars($ticket['client_email']); ?>&gt;)</a>
            </div>
        </div>
        <div>
            <?php 
                $badge_class = 'badge-blue';
                if ($ticket['status'] == 'Answered') $badge_class = 'badge-green';
                if ($ticket['status'] == 'Closed') $badge_class = 'badge-gray';
            ?>
            <span class="badge <?php echo $badge_class; ?>" style="font-size: 14px; padding: 6px 14px;"><?php echo $ticket['status']; ?></span>
        </div>
    </div>

    <div style="background: var(--bg-body-alt); padding: 30px 25px; border: 1px solid var(--border);">
        <?php foreach ($replies as $reply) { 
            
            if ($reply['sender_type'] === 'System') {
                $clean_msg = htmlspecialchars($reply['message']);
                $formatted_msg = str_replace(['[b]', '[/b]'], ['<strong>', '</strong>'], $clean_msg);
                echo '<div class="ticket-system-msg"><span><i class="fa-solid fa-clock-rotate-left" style="margin-right: 5px;"></i> ' . $formatted_msg . ' &bull; ' . date('M d, Y H:i', strtotime($reply['created_at'])) . '</span></div>';
                continue;
            }

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
                if (!empty($reply['attachment'])) { 
                    $files = json_decode($reply['attachment'], true);
                    if (!is_array($files)) { $files = [$reply['attachment']]; }
                    
                    echo '<div class="ticket-attachments">';
                    foreach ($files as $file) {
                        echo '<a href="/uploads/tickets/' . htmlspecialchars($file) . '" target="_blank" class="ticket-attachment-btn"><i class="fa-solid fa-paperclip" style="margin-right: 6px;"></i> ' . htmlspecialchars($file) . '</a>';
                    }
                    echo '</div>';
                } 
                ?>
            </div>
        <?php } ?>
    </div>

    <div style="background: var(--bg-body); padding: 25px; border-radius: 0 0 8px 8px; border: 1px solid var(--border); border-top: none;">
        <form action="process-ticket.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="admin_reply">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--color-heading);">Post a Reply</label>
                <textarea name="message" class="form-input" style="height: 120px; resize: vertical;" placeholder="Type your response to the client here..." required></textarea>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--color-heading);"><i class="fa-solid fa-paperclip"></i> Attach Files (Optional)</label>
                <input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="multi-file-input" style="width: 100%; padding: 8px; border: 1px dashed var(--border); border-radius: 6px; background: var(--bg-body-alt); font-size: 13px; color: var(--color-heading);">
                <div class="file-list-preview" style="display: flex; flex-direction: column; gap: 5px;"></div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;"><i class="fa-solid fa-circle-info"></i> Max size: 5MB per file. Allowed formats: JPG, PNG, WEBP, PDF, TXT.</div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center;">
                    <label style="margin-right: 15px; font-weight: 600; color: var(--text-muted);">Change Status to:</label>
                    <select name="status_update" class="form-input" style="width: 260px; cursor: pointer;">
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