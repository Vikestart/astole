<?php
// /core/actions/adm/ajax-tickets.php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

ob_start();
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../lib/upload-helper.php';

function outputJSON($data) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

set_error_handler(function($severity, $message, $file, $line) {
    outputJSON(['status' => 'error', 'message' => "PHP Error: $message on line $line"]);
});
set_exception_handler(function($e) {
    outputJSON(['status' => 'error', 'message' => "System Exception: " . $e->getMessage()]);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    outputJSON(['status' => 'error', 'message' => 'Security validation failed.']);
}

$db = new \Core\Lib\Database();
$conn = $db->getConnection();
$logger = new \Core\Lib\ActivityLogger();

// Role Check
$stmt_auth = $conn->prepare("SELECT user_uid, user_role FROM users WHERE user_id = ?");
$stmt_auth->bind_param("i", $_SESSION['UserID']);
$stmt_auth->execute();
$u_res = $stmt_auth->get_result()->fetch_assoc();
$role = $u_res ? (int)$u_res['user_role'] : 3;
$active_user_uid = $u_res ? $u_res['user_uid'] : 'Unknown';
$stmt_auth->close();

if ($role === 3) { outputJSON(['status' => 'error', 'message' => 'Permission denied.']); }

$action = $_POST['action'] ?? '';
$currtime = gmdate("Y-m-d H:i:s");

// ==========================================
// 1. FETCH TICKET LIST (WITH FILTERS & PAGINATION)
// ==========================================
if ($action === 'get_list') {
    $page = max(1, (int)($_POST['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $status_filter = $_POST['filter_status'] ?? 'All';
    $search = trim($_POST['search'] ?? '');

    $where = ["1=1"];
    $params = [];
    $types = "";

    if ($status_filter !== 'All') {
        $where[] = "t.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    if ($search !== '') {
        $where[] = "(t.tracking_id LIKE ? OR t.subject LIKE ? OR t.client_name LIKE ? OR t.client_email LIKE ?)";
        $s_param = "%{$search}%";
        array_push($params, $s_param, $s_param, $s_param, $s_param);
        $types .= "ssss";
    }

    $where_sql = implode(" AND ", $where);

    // Get Total Count for Pagination
    $count_sql = "SELECT COUNT(*) as total FROM tickets t WHERE $where_sql";
    $stmt_c = $conn->prepare($count_sql);
    if (!empty($params)) { $stmt_c->bind_param($types, ...$params); }
    $stmt_c->execute();
    $total_rows = $stmt_c->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);
    $stmt_c->close();

    // Fetch Paginated Data
    $html = '';
    $sql = "SELECT t.*, u.user_uid AS assigned_name FROM tickets t LEFT JOIN users u ON t.assigned_to = u.user_id WHERE $where_sql ORDER BY FIELD(t.status, 'Open', 'Answered', 'Closed'), t.updated_at DESC LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $tickets = $stmt->get_result();
    
    if ($tickets && $tickets->num_rows > 0) {
        while($row = $tickets->fetch_assoc()) {
            $badge_class = 'badge-blue';
            if ($row['status'] == 'Answered') $badge_class = 'badge-green';
            if ($row['status'] == 'Closed') $badge_class = 'badge-gray';
            
            $assigned_html = !empty($row['assigned_name']) ? '<span class="badge badge-gray"><i class="fa-solid fa-user-tie mr-5"></i> '.htmlspecialchars($row['assigned_name']).'</span>' : '<span class="text-muted" style="font-style: italic; font-size: 13px;">Unassigned</span>';

            $html .= '<tr>';
            $html .= '<td><a href="#" onclick="viewTicket('.$row['id'].'); return false;" style="color: var(--color-heading); text-decoration: none; font-weight: 700;">'.htmlspecialchars($row['tracking_id']).'</a></td>';
            $html .= '<td><a href="#" onclick="viewTicket('.$row['id'].'); return false;" style="color: var(--color-heading); font-weight: 600;">'.htmlspecialchars($row['subject']).'</a><div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">'.htmlspecialchars($row['client_name']) . ' (' . htmlspecialchars($row['client_email']) . ')</div></td>';
            $html .= '<td><span class="badge '.$badge_class.'">'.$row['status'].'</span></td>';
            $html .= '<td>'.$assigned_html.'</td>';
            $html .= '<td class="text-muted" style="font-size: 14px;">'.date('M d, Y H:i', strtotime($row['updated_at'])).'</td>';
            $html .= '<td style="text-align: right;" class="table-actions">';
            $html .= '<button class="action-icon-btn view" onclick="viewTicket('.$row['id'].')" title="View Ticket"><i class="fa-solid fa-reply"></i></button>';
            $html .= '<button class="action-icon-btn delete ml-10" onclick="deleteTicket('.$row['id'].')" title="Delete Ticket"><i class="fa-solid fa-trash-alt"></i></button>';
            $html .= '</td></tr>';
        }
    } else {
        $html = '<tr><td colspan="6" class="text-center p-20 text-muted">No support tickets found matching your criteria.</td></tr>';
    }
    $stmt->close();
    
    // Generate Pagination HTML
    $pag_html = '';
    if ($total_pages > 1) {
        $pag_html .= '<div class="d-flex justify-center gap-10 mt-20">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($i === $page) ? 'btn-primary' : 'btn-outline';
            $pag_html .= '<button class="btn '.$active.' btn-sm" onclick="loadList('.$i.')">'.$i.'</button>';
        }
        $pag_html .= '</div>';
    }

    outputJSON(['status' => 'success', 'html' => $html, 'pagination' => $pag_html]);
}

// ==========================================
// 2. FETCH SINGLE TICKET DATA
// ==========================================
if ($action === 'get_ticket') {
    $ticket_id = (int)$_POST['ticket_id'];
    
    // Fetch Ticket
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$ticket) { outputJSON(['status' => 'error', 'message' => 'Ticket not found.']); }

    // Fetch Staff for Assignment Dropdown
    $staff_opts = '<option value="0">-- Unassigned --</option>';
    $res_staff = $conn->query("SELECT user_id, user_uid, user_role FROM users WHERE user_role IN (1, 2) ORDER BY user_uid ASC");
    if ($res_staff) {
        while($staff = $res_staff->fetch_assoc()) { 
            $sel = ($ticket['assigned_to'] == $staff['user_id']) ? 'selected' : '';
            $role_txt = ($staff['user_role'] == 1) ? 'Admin' : 'Mod';
            $staff_opts .= '<option value="'.$staff['user_id'].'" '.$sel.'>'.htmlspecialchars($staff['user_uid']) . ' (' . $role_txt . ')</option>';
        }
    }

    // Build Thread HTML
    $thread_html = '';
    $stmt_rep = $conn->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
    $stmt_rep->bind_param("i", $ticket_id);
    $stmt_rep->execute();
    $res_rep = $stmt_rep->get_result();
    
    while ($reply = $res_rep->fetch_assoc()) {
        if ($reply['sender_type'] === 'System') {
            $formatted_msg = str_replace(['[b]', '[/b]'], ['<strong>', '</strong>'], htmlspecialchars($reply['message']));
            $thread_html .= '<div class="ticket-sys-msg"><span><i class="fa-solid fa-clock-rotate-left mr-5"></i> ' . $formatted_msg . ' &bull; ' . date('M d, Y H:i', strtotime($reply['created_at'])) . '</span></div>';
            continue;
        }

        $is_admin = ($reply['sender_type'] === 'Admin');
        $role_class = $is_admin ? 'admin' : 'client';
        $name_tag = $is_admin ? 'You (Admin)' : htmlspecialchars($ticket['client_name']);
        $icon = $is_admin ? '<i class="fa-solid fa-user-shield"></i>' : '<i class="fa-solid fa-user"></i>';
        
        $thread_html .= '<div class="ticket-msg '.$role_class.'">';
        $thread_html .= '<div class="ticket-msg-meta">'.$icon.' <strong>'.$name_tag.'</strong> <span style="opacity: 0.7;">&bull; '.date('M d, Y H:i', strtotime($reply['created_at'])).'</span></div>';
        $thread_html .= '<div class="ticket-bubble">'.htmlspecialchars($reply['message']).'</div>';
        
        if (!empty($reply['attachment'])) { 
            $files = json_decode($reply['attachment'], true) ?? [$reply['attachment']];
            $thread_html .= '<div class="ticket-attachments">';
            foreach ($files as $file) {
                $thread_html .= '<a href="/uploads/tickets/'.htmlspecialchars($file).'" target="_blank" class="ticket-file-badge"><i class="fa-solid fa-paperclip"></i> '.htmlspecialchars($file).'</a>';
            }
            $thread_html .= '</div>';
        }
        $thread_html .= '</div>';
    }
    $stmt_rep->close();

    outputJSON([
        'status' => 'success', 
        'ticket' => $ticket, 
        'staff_opts' => $staff_opts, 
        'thread_html' => $thread_html
    ]);
}

// ==========================================
// 3. ADMIN ACTIONS
// ==========================================
if ($action === 'admin_reply') {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = trim($_POST['message'] ?? '');
    $status_update = $_POST['status_update'] ?? 'Answered';

    if (empty($message)) { outputJSON(['status' => 'error', 'message' => 'You must provide a reply comment.']); }

    $stmt_tkt = $conn->prepare("SELECT client_name, client_email, tracking_id, status FROM tickets WHERE id = ?");
    $stmt_tkt->bind_param("i", $ticket_id);
    $stmt_tkt->execute();
    $t_data = $stmt_tkt->get_result()->fetch_assoc();
    $stmt_tkt->close();

    if ($t_data) {
        if ($t_data['status'] !== $status_update) {
            $sys_msg = "Status changed from [b]{$t_data['status']}[/b] to [b]{$status_update}[/b] by {$active_user_uid}.";
            $stmt_sys = $conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
            $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
            $stmt_sys->execute();
            $stmt_sys->close();
        }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'][0])) {
            $upload_res = processMultipleAttachments($_FILES['attachment'], __DIR__ . '/../../../uploads/tickets');
            if (is_array($upload_res) && isset($upload_res['error'])) { outputJSON(['status' => 'error', 'message' => $upload_res['error']]); }
            $attachment = $upload_res;
        }

        $stmt_msg = $conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, attachment, created_at) VALUES (?, 'Admin', ?, ?, ?)");
        $stmt_msg->bind_param("isss", $ticket_id, $message, $attachment, $currtime);
        $stmt_msg->execute();
        $stmt_msg->close();

        $stmt_upd = $conn->prepare("UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?");
        $stmt_upd->bind_param("ssi", $status_update, $currtime, $ticket_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $logger->logAdminActivity($_SESSION['UserID'], 'Ticket', "Replied to Tracking ID: {$t_data['tracking_id']} and set status to: {$status_update}");
        outputJSON(['status' => 'success', 'message' => 'Ticket updated and reply sent.']);
    }
}

if ($action === 'assign_ticket') {
    $ticket_id = (int)$_POST['ticket_id'];
    $new_assignee = (int)($_POST['assigned_to'] ?? 0);
    $assignee_val = ($new_assignee === 0) ? null : $new_assignee;

    $stmt_check = $conn->prepare("SELECT assigned_to, tracking_id FROM tickets WHERE id = ?");
    $stmt_check->bind_param("i", $ticket_id);
    $stmt_check->execute();
    $t_info = $stmt_check->get_result()->fetch_assoc();
    $curr_assigned = $t_info['assigned_to'] ?? null;
    $tracking_id = $t_info['tracking_id'] ?? 'Unknown';
    $stmt_check->close();

    if ($curr_assigned !== $assignee_val) {
        $stmt_upd = $conn->prepare("UPDATE tickets SET assigned_to = ?, updated_at = ? WHERE id = ?");
        $stmt_upd->bind_param("isi", $assignee_val, $currtime, $ticket_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $staff_name = "Unassigned";
        if ($assignee_val) {
            $stmt_u = $conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
            $stmt_u->bind_param("i", $assignee_val);
            $stmt_u->execute();
            $staff_name = $stmt_u->get_result()->fetch_assoc()['user_uid'] ?? 'Unknown';
            $stmt_u->close();
        }

        $sys_msg = "Ticket assigned to [b]{$staff_name}[/b] by {$active_user_uid}.";
        $stmt_sys = $conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
        $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
        $stmt_sys->execute();
        $stmt_sys->close();

        $logger->logAdminActivity($_SESSION['UserID'], 'Ticket', "Assigned ticket {$tracking_id} to {$staff_name}");
        outputJSON(['status' => 'success', 'message' => "Ticket assigned to {$staff_name}."]);
    }
    outputJSON(['status' => 'success', 'message' => "No changes made."]);
}

// === DELETE TICKET ===
if ($action === 'delete_ticket') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    
    // 1. Strict Check: Did we receive a valid ID?
    if ($ticket_id <= 0) {
        outputJSON(['status' => 'error', 'message' => 'Invalid Ticket ID passed to the server.']);
    }
    
    // 2. Fetch the tracking ID for the log AND verify the ticket exists
    $stmt_trk = $conn->prepare("SELECT tracking_id FROM tickets WHERE id = ?");
    $stmt_trk->bind_param("i", $ticket_id);
    $stmt_trk->execute();
    $trk_res = $stmt_trk->get_result()->fetch_assoc();
    $tracking_id_del = $trk_res ? $trk_res['tracking_id'] : 'Unknown';
    $stmt_trk->close();

    if ($tracking_id_del === 'Unknown') {
        outputJSON(['status' => 'error', 'message' => "Ticket ID {$ticket_id} does not exist in the database."]);
    }

    // 3. Delete Attachments securely
    $stmt_att = $conn->prepare("SELECT attachment FROM ticket_replies WHERE ticket_id = ? AND attachment IS NOT NULL");
    $stmt_att->bind_param("i", $ticket_id);
    $stmt_att->execute();
    $res_att = $stmt_att->get_result();
    $upload_dir = __DIR__ . '/../../../uploads/tickets/';
    while($r = $res_att->fetch_assoc()) {
        $files = json_decode($r['attachment'], true) ?? [$r['attachment']];
        foreach($files as $f) { @unlink($upload_dir . basename($f)); } 
    }
    $stmt_att->close();

    // 4. Delete all child replies first to clear Foreign Key constraints
    $stmt_rep = $conn->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?");
    $stmt_rep->bind_param("i", $ticket_id);
    $stmt_rep->execute();
    $stmt_rep->close();

    // 5. Delete the main ticket and verify affected rows
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $deleted_rows = $stmt->affected_rows;
    $stmt->close();
    
    // 6. Final verification before reporting success
    if ($deleted_rows === 0) {
        outputJSON(['status' => 'error', 'message' => 'Database refused to delete the ticket. Check constraints.']);
    }
    
    $logger->logAdminActivity($_SESSION['UserID'], 'Ticket', "Deleted Tracking ID: {$tracking_id_del} and attachments.");
    outputJSON(['status' => 'success', 'message' => 'Ticket and all replies permanently deleted.']);
}