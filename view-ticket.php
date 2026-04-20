<?php
session_start();
require_once "PageManager.php";
require_once "db.php";

$track_id = trim($_GET['id'] ?? '');
$email = filter_var($_GET['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($track_id) || empty($email)) {
    header("Location: /");
    die();
}

$db = new DBConn();

$stmt = $db->conn->prepare("SELECT * FROM tickets WHERE tracking_id = ? AND client_email = ?");
$stmt->bind_param("ss", $track_id, $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $_SESSION['Frontmsg'] = array('type' => 'error', 'message' => 'No ticket found matching that Tracking ID and Email Address.');
    header("Location: /");
    die();
}

$ticket = $res->fetch_assoc();
$stmt->close();

$replies = [];
$stmt_rep = $db->conn->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt_rep->bind_param("i", $ticket['id']);
$stmt_rep->execute();
$res_rep = $stmt_rep->get_result();
while ($r = $res_rep->fetch_assoc()) { $replies[] = $r; }
$stmt_rep->close();

$res_rc = $db->conn->query("SELECT setting_value FROM settings WHERE setting_key = 'recaptcha_site'");
$rc_site = ($res_rc && $res_rc->num_rows === 1) ? trim($res_rc->fetch_assoc()['setting_value']) : '';

$page_title = "Ticket " . htmlspecialchars($ticket['tracking_id']);
require_once "inc-head.php";
?>

<main class="page-container">
    <div class="hero-section" style="min-height: auto; padding: 40px 20px 20px 20px;">
        <div class="hero-badge" style="margin-bottom: 0;">
            <i class="fa-solid fa-chart-line"></i> Technical Consultant & Developer
        </div>
    </div>

    <section class="glass-panel" style="max-width: 900px; margin: -30px auto 40px auto; position: relative; z-index: 10;">
        <?php 
        if (isset($_SESSION['Frontmsg'])) {
            $msgType = $_SESSION['Frontmsg']['type']; 
            $msgTxt = $_SESSION['Frontmsg']['message'];
            $bgColor = ($msgType === 'success') ? '#dcfce7' : '#fee2e2';
            $textColor = ($msgType === 'success') ? '#166534' : '#991b1b';
            echo "<div style='background: $bgColor; color: $textColor; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; border: 1px solid " . (($msgType === 'success') ? '#bbf7d0' : '#fecaca') . ";'>$msgTxt</div>";
            unset($_SESSION['Frontmsg']);
        }
        ?>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 25px;">
            <div>
                <h2 style="margin: 0 0 10px 0; color: #0f172a; font-size: 22px;"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                <div style="color: #64748b; font-size: 14px;">
                    Tracking ID: <strong><?php echo htmlspecialchars($ticket['tracking_id']); ?></strong> &bull; 
                    Created: <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                </div>
            </div>
            <div>
                <?php 
                    $badge_bg = '#dbeafe'; $badge_col = '#1e40af'; $badge_border = '#bfdbfe';
                    if ($ticket['status'] == 'Answered') { $badge_bg = '#dcfce7'; $badge_col = '#166534'; $badge_border = '#bbf7d0'; }
                    if ($ticket['status'] == 'Closed') { $badge_bg = '#f1f5f9'; $badge_col = '#475569'; $badge_border = '#e2e8f0'; }
                ?>
                <span style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_col; ?>; border: 1px solid <?php echo $badge_border; ?>; padding: 6px 14px; border-radius: 20px; font-size: 14px; font-weight: 600;"><?php echo $ticket['status']; ?></span>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 30px 25px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <?php foreach ($replies as $reply) { 
                if ($reply['sender_type'] === 'System') {
                    $clean_msg = htmlspecialchars($reply['message']);
                    $formatted_msg = str_replace(['[b]', '[/b]'], ['<strong>', '</strong>'], $clean_msg);
                    echo '<div style="text-align: center; margin: 20px 0;">';
                    echo '<span style="background: #fff; color: #64748b; font-size: 12px; padding: 6px 14px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"><i class="fa-solid fa-clock-rotate-left" style="margin-right: 5px;"></i> ' . $formatted_msg . ' &bull; ' . date('M d, Y H:i', strtotime($reply['created_at'])) . '</span>';
                    echo '</div>';
                    continue;
                }

                $is_client = ($reply['sender_type'] === 'Client');
                $bubble_bg = $is_client ? '#2563eb' : '#fff';
                $bubble_text = $is_client ? '#fff' : '#0f172a';
                $bubble_border = $is_client ? 'none' : '1px solid #e2e8f0';
                $align = $is_client ? 'margin-left: auto;' : 'margin-right: auto;';
                $name_tag = $is_client ? 'You' : 'Support Team';
                $text_align = $is_client ? 'right' : 'left';
                $shadow = $is_client ? '0 4px 6px -1px rgba(37, 99, 235, 0.2)' : '0 1px 3px rgba(0,0,0,0.05)';
            ?>
                <div style="max-width: 85%; <?php echo $align; ?> margin-bottom: 25px;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px; text-align: <?php echo $text_align; ?>;">
                        <strong><?php echo $name_tag; ?></strong> &bull; <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?>
                    </div>
                    
                    <div style="background: <?php echo $bubble_bg; ?>; color: <?php echo $bubble_text; ?>; padding: 18px 22px; border-radius: 10px; border: <?php echo $bubble_border; ?>; box-shadow: <?php echo $shadow; ?>; line-height: 1.6; font-size: 15px; white-space: pre-wrap;"><?php echo htmlspecialchars($reply['message']); ?></div>
                    
                    <?php 
                    if (!empty($reply['attachment'])) { 
                        $files = json_decode($reply['attachment'], true);
                        if (!is_array($files)) { $files = [$reply['attachment']]; }
                        
                        echo '<div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: ' . ($is_client ? 'flex-end' : 'flex-start') . ';">';
                        foreach ($files as $file) {
                            $btn_bg = $is_client ? 'rgba(37,99,235,0.1)' : '#fff';
                            $btn_col = $is_client ? '#2563eb' : '#475569';
                            $btn_bord = $is_client ? '#bfdbfe' : '#e2e8f0';
                            echo '<a href="/uploads/tickets/' . htmlspecialchars($file) . '" target="_blank" style="display: inline-flex; align-items: center; background: ' . $btn_bg . '; color: ' . $btn_col . '; padding: 6px 14px; border: 1px solid ' . $btn_bord . '; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; white-space: nowrap;"><i class="fa-solid fa-paperclip" style="margin-right: 6px;"></i> ' . htmlspecialchars($file) . '</a>';
                        }
                        echo '</div>';
                    } 
                    ?>
                </div>
            <?php } ?>
        </div>

        <?php if ($ticket['status'] !== 'Closed') { ?>
            <div style="border-top: 1px solid #e2e8f0; padding-top: 25px;">
                <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 20px;">Send a Reply</h3>
                <form action="process-ticket.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="reply_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <input type="hidden" name="auth_email" value="<?php echo htmlspecialchars($ticket['client_email']); ?>">
                    <input type="hidden" name="tracking_id" value="<?php echo htmlspecialchars($ticket['tracking_id']); ?>">

                    <div style="margin-bottom: 15px;">
                        <textarea name="message" style="width: 100%; height: 120px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; resize: vertical;" placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #334155; font-size: 14px;"><i class="fa-solid fa-paperclip"></i> Attach Files (Optional)</label>
                        <input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="multi-file-input" style="width: 100%; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 6px; background: #f8fafc; font-size: 13px;">
                        <div class="file-list-preview" style="display: flex; flex-direction: column; gap: 5px;"></div>
                        <div style="font-size: 12px; color: #94a3b8; margin-top: 8px;"><i class="fa-solid fa-circle-info"></i> Max size: 5MB per file. Allowed formats: JPG, PNG, WEBP, PDF, TXT.</div>
                    </div>

                    <?php if (!empty($rc_site)) { ?>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($rc_site); ?>" data-action="reply_ticket" style="margin-bottom: 15px;"></div>
                    <?php } ?>

                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <button type="submit" style="background: #2563eb; color: white; padding: 10px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Send Reply</button>
                </form>

                <form action="process-ticket.php" method="POST" onsubmit="return confirm('Are you sure you want to close this ticket?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="client_close">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <input type="hidden" name="auth_email" value="<?php echo htmlspecialchars($ticket['client_email']); ?>">
                    <input type="hidden" name="return_url" value="view-ticket.php?id=<?php echo urlencode($track_id); ?>&email=<?php echo urlencode($email); ?>">
                    <button type="submit" style="background: transparent; color: #dc2626; border: 1px solid #dc2626; padding: 9px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Mark as Resolved</button>
                </form>
                    </div>
            </div>
        <?php } else { ?>
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 25px; text-align: center; border-radius: 8px;">
                <i class="fa-solid fa-lock" style="font-size: 24px; color: #94a3b8; margin-bottom: 10px;"></i>
                <h3 style="margin: 0 0 5px 0; color: #475569;">This ticket is closed</h3>
                <p style="margin: 0; color: #64748b; font-size: 14px;">If you need further assistance, please open a new ticket from the support portal.</p>
            </div>
        <?php } ?>
    </section>
</main>

<?php require_once "inc-end.php"; ?>