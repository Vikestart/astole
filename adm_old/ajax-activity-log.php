<?php
session_start();
require "../db.php";

if (!isset($_SESSION['UserID'])) { die(); }

$db = new DBConn();
$stmt_auth = $db->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
$stmt_auth->bind_param("i", $_SESSION['UserID']);
$stmt_auth->execute();
$role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
$stmt_auth->close();

if ($role != 1) { die(); }

$offset = (int)($_GET['offset'] ?? 0);
$limit = 20; // Fetch 20 records at a time

$stmt = $db->conn->prepare("SELECT a.*, u.user_uid FROM activity_log a LEFT JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();

while ($log = $res->fetch_assoc()) {
    $badge_class = 'badge-gray';
    if ($log['action_type'] === 'User') $badge_class = 'badge-blue';
    if ($log['action_type'] === 'Page') $badge_class = 'badge-green';
    if ($log['action_type'] === 'Settings') $badge_class = 'badge-red';
    if ($log['action_type'] === 'Security') $badge_class = 'badge-yellow'; 
    if ($log['action_type'] === 'Ticket') $badge_class = 'badge-blue'; // Defaults beautifully if you lack purple

    echo '<tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background=\'var(--bg-body)\'" onmouseout="this.style.background=\'transparent\'">';
    echo '<td style="padding: 15px 20px; font-size: 14px; color: var(--text-muted); white-space: nowrap;">' . date('M d, Y - H:i', strtotime($log['created_at'])) . '</td>';
    echo '<td style="padding: 15px 20px;"><div style="font-weight: 600; color: var(--color-heading); font-size: 14px;">' . htmlspecialchars($log['user_uid'] ?? 'System / Deleted User') . '</div></td>';
    echo '<td style="padding: 15px 20px;"><span class="badge ' . $badge_class . '">' . htmlspecialchars($log['action_type']) . '</span></td>';
    echo '<td style="padding: 15px 20px; font-size: 14px; color: var(--text-main);">' . htmlspecialchars($log['action_desc']) . '</td>';
    echo '<td style="padding: 15px 20px; font-size: 13px; color: var(--text-muted); font-family: monospace;">' . htmlspecialchars($log['ip_address']) . '</td>';
    echo '</tr>';
}
$stmt->close();
?>