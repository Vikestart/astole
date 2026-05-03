<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$track_id = trim($_GET['id'] ?? '');
$email = filter_var($_GET['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($track_id) || empty($email)) {
    header("Location: /");
    die();
}

// Instantiate the new Core Database
$db = new \Core\Lib\Database();

$stmt = $db->getConnection()->prepare("SELECT t.*, u.user_uid AS assigned_name FROM tickets t LEFT JOIN users u ON t.assigned_to = u.user_id WHERE t.tracking_id = ? AND t.client_email = ?");
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
$stmt_rep = $db->getConnection()->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt_rep->bind_param("i", $ticket['id']);
$stmt_rep->execute();
$res_rep = $stmt_rep->get_result();
while ($r = $res_rep->fetch_assoc()) { $replies[] = $r; }
$stmt_rep->close();

$res_rc = $db->getConnection()->query("SELECT setting_value FROM settings WHERE setting_key = 'recaptcha_site'");
$rc_site = ($res_rc && $res_rc->num_rows === 1) ? trim($res_rc->fetch_assoc()['setting_value']) : '';

$page_title = "Ticket " . htmlspecialchars($ticket['tracking_id']);

// Load the dynamic header component
$this->component('header', ['page_title' => $page_title]);
?>

<main class="page-container">
    <div class="hero-section ticket-hero">
        <div class="hero-badge">
            <i class="fa-solid fa-chart-line mr-5"></i> Technical Consultant & Developer
        </div>
    </div>

    <section class="glass-panel ticket-main-panel">
        <?php 
        if (isset($_SESSION['Frontmsg'])) {
            $msgType = $_SESSION['Frontmsg']['type']; 
            $msgTxt = $_SESSION['Frontmsg']['message'];
            $msgClass = ($msgType === 'success') ? 'front-msgbox-success' : 'front-msgbox-error';
            echo "<div class='front-msgbox $msgClass'>$msgTxt</div>";
            unset($_SESSION['Frontmsg']);
        }
        ?>

        <div class="ticket-view-header">
            <div>
                <h2 class="ticket-view-title"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                <div class="ticket-view-meta">
                    Tracking ID: <strong><?php echo htmlspecialchars($ticket['tracking_id']); ?></strong> &bull; 
                    Created: <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                </div>
            </div>
            <div class="d-flex align-center gap-10">
                <?php if (!empty($ticket['assigned_name'])) { ?>
                    <span class="ticket-badge-pill ticket-badge-ghost">
                        <i class="fa-solid fa-user-tie mr-5"></i> Agent: <?php echo htmlspecialchars($ticket['assigned_name']); ?>
                    </span>
                <?php } ?>
                
                <?php 
                    $badge_class = 'ticket-badge-blue';
                    if ($ticket['status'] == 'Answered') { $badge_class = 'ticket-badge-green'; }
                    if ($ticket['status'] == 'Closed') { $badge_class = 'ticket-badge-gray'; }
                ?>
                <span class="ticket-badge-pill <?php echo $badge_class; ?>"><?php echo $ticket['status']; ?></span>
            </div>
        </div>

        <div class="ticket-thread-container">
            <?php foreach ($replies as $reply) { 
                
                if ($reply['sender_type'] === 'System') {
                    $clean_msg = htmlspecialchars($reply['message']);
                    $formatted_msg = str_replace(['[b]', '[/b]'], ['<strong>', '</strong>'], $clean_msg);
                    echo '<div class="ticket-system-msg"><span><i class="fa-solid fa-clock-rotate-left mr-5"></i> ' . $formatted_msg . ' &bull; ' . date('M d, Y H:i', strtotime($reply['created_at'])) . '</span></div>';
                    continue;
                }

                $is_client = ($reply['sender_type'] === 'Client');
                $role_class = $is_client ? 'client' : 'admin';
                $name_tag = $is_client ? 'You' : 'Support Team';
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
                            echo '<a href="/site/uploads/tickets/' . htmlspecialchars($file) . '" target="_blank" class="ticket-attachment-btn"><i class="fa-solid fa-paperclip mr-6"></i> ' . htmlspecialchars($file) . '</a>';
                        }
                        echo '</div>';
                    } 
                    ?>
                </div>
            <?php } ?>
        </div>

        <?php if ($ticket['status'] !== 'Closed') { ?>
            <div class="ticket-reply-container">
                <h3 class="ticket-reply-title">Send a Reply</h3>
                
                <form action="/core/actions/process-ticket.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="reply_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <input type="hidden" name="auth_email" value="<?php echo htmlspecialchars($ticket['client_email']); ?>">
                    <input type="hidden" name="tracking_id" value="<?php echo htmlspecialchars($ticket['tracking_id']); ?>">

                    <div class="mb-15">
                        <textarea name="message" class="ticket-textarea" placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <div class="mb-20">
                        <label class="ticket-form-label"><i class="fa-solid fa-paperclip mr-5"></i> Attach Files (Optional)</label>
                        <input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="multi-file-input ticket-file-drop">
                        <div class="file-list-preview d-flex flex-col gap-5 mt-8"></div>
                        <div class="ticket-file-helper"><i class="fa-solid fa-circle-info mr-5"></i> Max size: 5MB per file. Allowed formats: JPG, PNG, WEBP, PDF, TXT.</div>
                    </div>

                    <?php if (!empty($rc_site)) { ?>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        <div class="g-recaptcha mb-15" data-sitekey="<?php echo htmlspecialchars($rc_site); ?>" data-action="reply_ticket"></div>
                    <?php } ?>

                    <div class="ticket-action-row">
                        <button type="submit" class="ticket-btn-primary">Send Reply</button>
                </form>

                <form action="/core/actions/process-ticket.php" method="POST" class="confirm-close-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="client_close">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <input type="hidden" name="auth_email" value="<?php echo htmlspecialchars($ticket['client_email']); ?>">
                    <input type="hidden" name="return_url" value="view-ticket.php?id=<?php echo urlencode($track_id); ?>&email=<?php echo urlencode($email); ?>">
                    <button type="submit" class="ticket-btn-danger">Mark as Resolved</button>
                </form>
                    </div>
            </div>
        <?php } else { ?>
            <div class="ticket-closed-panel">
                <i class="fa-solid fa-lock ticket-closed-icon"></i>
                <h3 class="ticket-closed-title">This ticket is closed</h3>
                <p class="ticket-closed-text">If you need further assistance, please open a new ticket from the support portal.</p>
            </div>
        <?php } ?>
    </section>
</main>

<?php 
// Load the dynamic footer component
$this->component('footer'); 
?>