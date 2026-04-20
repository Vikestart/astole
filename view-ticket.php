<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

require_once "db.php";
$db = new DBConn();

$track_id = trim($_GET['id'] ?? '');
$email = filter_var($_GET['email'] ?? '', FILTER_SANITIZE_EMAIL);

$ticket = null;
$replies = [];

if (!empty($track_id) && !empty($email)) {
    // 1. Fetch Ticket Header
    $stmt = $db->conn->prepare("SELECT * FROM tickets WHERE tracking_id = ? AND client_email = ? LIMIT 1");
    $stmt->bind_param("ss", $track_id, $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 1) {
        $ticket = $res->fetch_assoc();
        
        // 2. Fetch Chat Thread
        $stmt_rep = $db->conn->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
        $stmt_rep->bind_param("i", $ticket['id']);
        $stmt_rep->execute();
        $res_rep = $stmt_rep->get_result();
        while ($r = $res_rep->fetch_assoc()) {
            $replies[] = $r;
        }
        $stmt_rep->close();
    }
    $stmt->close();
}

$page_title = $ticket ? "Ticket: " . htmlspecialchars($ticket['tracking_id']) : "Ticket Not Found";
require_once "inc-head.php";

// Fetch global recaptcha for the reply form
$rc_site = '';
$res_rc = $db->conn->query("SELECT setting_value FROM settings WHERE setting_key = 'recaptcha_site'");
if ($res_rc && $res_rc->num_rows === 1) { $rc_site = trim($res_rc->fetch_assoc()['setting_value']); }
?>

<main class="page-container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
    
    <?php if (isset($_SESSION['Frontmsg'])) {
        $msgType = $_SESSION['Frontmsg']['type']; $msgTxt = $_SESSION['Frontmsg']['message'];
        $bgColor = ($msgType === 'success') ? '#dcfce7' : '#fee2e2'; $textColor = ($msgType === 'success') ? '#166534' : '#991b1b';
        echo "<div style='background: $bgColor; color: $textColor; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;'>$msgTxt</div>";
        unset($_SESSION['Frontmsg']);
    } ?>

    <?php if (!$ticket) { ?>
        <div style="text-align: center; background: #fff; padding: 50px 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <i class="fa-solid fa-search" style="font-size: 40px; color: #94a3b8; margin-bottom: 20px;"></i>
            <h2 style="margin: 0 0 10px 0; color: #0f172a;">Ticket Not Found</h2>
            <p style="color: #64748b;">We couldn't find a ticket matching that Tracking ID and Email combination.</p>
            <a href="javascript:history.back()" style="display: inline-block; margin-top: 20px; background: #2563eb; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Go Back</a>
        </div>
    <?php } else { ?>

        <div style="background: #fff; padding: 25px; border-radius: 8px 8px 0 0; border: 1px solid #e2e8f0; border-bottom: none;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <h1 style="margin: 0; font-size: 24px; color: #0f172a;"><?php echo htmlspecialchars($ticket['subject']); ?></h1>
                <?php 
                    $b_color = '#e2e8f0'; $t_color = '#475569';
                    if ($ticket['status'] == 'Open') { $b_color = '#dbeafe'; $t_color = '#1e40af'; }
                    if ($ticket['status'] == 'Answered') { $b_color = '#dcfce7'; $t_color = '#166534'; }
                ?>
                <span style="background: <?php echo $b_color; ?>; color: <?php echo $t_color; ?>; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;"><?php echo $ticket['status']; ?></span>
            </div>
            <div style="color: #64748b; font-size: 14px;">
                <strong>ID:</strong> <?php echo htmlspecialchars($ticket['tracking_id']); ?> &nbsp;|&nbsp; 
                <strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 30px 25px; border: 1px solid #e2e8f0;">
            <?php foreach ($replies as $reply) { 
                // SYSTEM HISTORY LOG VIEW
                if ($reply['sender_type'] === 'System') {
                    echo '<div style="text-align: center; margin: 20px 0;">';
                    echo '<span style="background: #fff; color: #64748b; font-size: 12px; padding: 6px 14px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"><i class="fa-solid fa-clock-rotate-left"></i> ' . htmlspecialchars($reply['message']) . ' &bull; ' . date('M d H:i', strtotime($reply['created_at'])) . '</span>';
                    echo '</div>';
                    continue;
                }
                // COMMENTS VIEW
                $is_admin = ($reply['sender_type'] === 'Admin');
                $bubble_bg = $is_admin ? '#fff' : '#2563eb';
                $bubble_text = $is_admin ? '#1e293b' : '#fff';
                $bubble_border = $is_admin ? '1px solid #e2e8f0' : '1px solid #2563eb';
                $align = $is_admin ? 'margin-right: auto;' : 'margin-left: auto;';
                $name_tag = $is_admin ? 'Support Team' : 'You';
            ?>
                <div style="max-width: 85%; <?php echo $align; ?> margin-bottom: 25px;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px; text-align: <?php echo $is_admin ? 'left' : 'right'; ?>;">
                        <strong><?php echo $name_tag; ?></strong> &bull; <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?>
                    </div>
                    <div style="background: <?php echo $bubble_bg; ?>; color: <?php echo $bubble_text; ?>; padding: 15px 20px; border-radius: 8px; border: <?php echo $bubble_border; ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.05); line-height: 1.6; font-size: 15px; white-space: pre-wrap;"><?php echo htmlspecialchars($reply['message']); ?></div>
                </div>
            <?php } ?>
        </div>

        <?php if ($ticket['status'] !== 'Closed') { ?>
            <div style="background: #fff; padding: 25px; border-radius: 0 0 8px 8px; border: 1px solid #e2e8f0; border-top: none;">
                <form id="ticket-reply-form" action="process-ticket.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="reply_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <input type="hidden" name="auth_email" value="<?php echo htmlspecialchars($ticket['client_email']); ?>">
                    <input type="hidden" name="tracking_id" value="<?php echo htmlspecialchars($ticket['tracking_id']); ?>">
                    <input type="hidden" name="return_url" value="view-ticket.php?id=<?php echo urlencode($track_id); ?>&email=<?php echo urlencode($email); ?>">

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #334155;">Post a Reply</label>
                        <textarea name="message" style="width: 100%; height: 100px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; resize: vertical;" required></textarea>
                    </div>

                    <?php if (!empty($rc_site)) { ?>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($rc_site); ?>" data-action="reply_ticket" style="margin-bottom: 15px;"></div>
                    <?php } ?>

                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <button type="submit" style="background: #475569; color: white; padding: 10px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;">Send Reply</button>
                        </form>
                        <form action="process-ticket.php" method="POST" onsubmit="return confirm('Are you sure you want to close this ticket? You will not be able to send further replies.');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="client_close">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <input type="hidden" name="auth_email" value="<?php echo htmlspecialchars($ticket['client_email']); ?>">
                            <input type="hidden" name="return_url" value="view-ticket.php?id=<?php echo urlencode($track_id); ?>&email=<?php echo urlencode($email); ?>">
                            <button type="submit" style="background: transparent; color: #dc2626; border: 1px solid #dc2626; padding: 9px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Mark as Resolved</button>
                        </form>
                    </div>
                </form>
            </div>
        <?php } else { ?>
            <div style="background: #fff; padding: 20px; border-radius: 0 0 8px 8px; border: 1px solid #e2e8f0; border-top: none; text-align: center; color: #64748b;">
                <i class="fa-solid fa-lock" style="margin-right: 5px;"></i> This ticket has been closed. Replies are disabled.
            </div>
        <?php } ?>

    <?php } ?>
</main>

<?php require_once "inc-end.php"; ?>