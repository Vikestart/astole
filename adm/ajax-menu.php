<?php
session_start();
require_once "../db.php";
require_once "activity-logger.php"; // Hooking into native logger

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Security validation failed.']);
    exit;
}

if (!isset($_SESSION['UserID'])) { echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit; }

$db = new DBConn();

$stmt_auth = $db->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
$stmt_auth->bind_param("i", $_SESSION['UserID']);
$stmt_auth->execute();
$role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
$stmt_auth->close();
if ($role == 3) { echo json_encode(['status' => 'error', 'message' => 'Permission denied.']); exit; }

$action = $_POST['action'] ?? '';
$menu_id = (int)($_POST['menu_id'] ?? 1);

// --- PROCESS ACTIONS ---
if ($action === 'add_item' || $action === 'edit_item') {
    $title = trim($_POST['title'] ?? '');
    $link_type = $_POST['link_type'] ?? 'url';
    $target = $_POST['target'] === '_blank' ? '_blank' : '_self';
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $url = ($link_type === 'url') ? trim($_POST['url'] ?? '') : '';
    $page_id = ($link_type === 'page') ? (int)($_POST['page_id'] ?? 0) : null;
    if ($page_id === 0) $page_id = null;

    if (empty($title)) { echo json_encode(['status' => 'error', 'message' => 'Title is required.']); exit; }

    if ($action === 'add_item') {
        $stmt_max = $db->conn->prepare("SELECT MAX(sort_order) AS max_sort FROM menu_items WHERE menu_id = ? AND IFNULL(parent_id, 0) = ?");
        $p_check = $parent_id ?? 0;
        $stmt_max->bind_param("ii", $menu_id, $p_check);
        $stmt_max->execute();
        $next_sort = ($stmt_max->get_result()->fetch_assoc()['max_sort'] ?? -1) + 1;
        $stmt_max->close();

        $stmt = $db->conn->prepare("INSERT INTO menu_items (menu_id, parent_id, page_id, title, url, sort_order, target) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissis", $menu_id, $parent_id, $page_id, $title, $url, $next_sort, $target);
        $stmt->execute();
        $stmt->close();
        if (function_exists('logAdminActivity')) logAdminActivity($db->conn, $_SESSION['UserID'], 'Content', "Added menu link: {$title}");
    } else {
        $item_id = (int)$_POST['item_id'];
        $stmt = $db->conn->prepare("UPDATE menu_items SET parent_id=?, page_id=?, title=?, url=?, target=? WHERE id=?");
        $stmt->bind_param("iisssi", $parent_id, $page_id, $title, $url, $target, $item_id);
        $stmt->execute();
        $stmt->close();
        if (function_exists('logAdminActivity')) logAdminActivity($db->conn, $_SESSION['UserID'], 'Content', "Edited menu link: {$title}");
    }
}

if ($action === 'delete_item') {
    $item_id = (int)$_POST['item_id'];
    $stmt = $db->conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
    if (function_exists('logAdminActivity')) logAdminActivity($db->conn, $_SESSION['UserID'], 'Content', "Deleted a menu link.");
}

// Rewritten to handle Parent Swapping
if ($action === 'update_order') {
    $sort_data = json_decode($_POST['sort_order_data'] ?? '[]', true);
    if (is_array($sort_data)) {
        $stmt = $db->conn->prepare("UPDATE menu_items SET sort_order = ?, parent_id = ? WHERE id = ?");
        foreach ($sort_data as $item) {
            $order = (int)$item['order']; 
            $id = (int)$item['id'];
            $pid = !empty($item['parent_id']) ? (int)$item['parent_id'] : null;
            $stmt->bind_param("iii", $order, $pid, $id);
            $stmt->execute();
        }
        $stmt->close();
        if (function_exists('logAdminActivity')) logAdminActivity($db->conn, $_SESSION['UserID'], 'Content', "Reordered navigation menu items.");
    }
}

// --- GENERATE RENDERED HTML & PARENT DROPDOWN ---

// 1. Create a foolproof Page Lookup array (mirrors menus.php)
$page_lookup = [];
$res_pages = $db->conn->query("SELECT * FROM pages");
if($res_pages) { 
    while($p = $res_pages->fetch_assoc()) { 
        $pid = $p['id'] ?? $p['page_id'] ?? 0;
        $ptitle = $p['page_title'] ?? $p['title'] ?? 'Unnamed Page';
        if ($pid) $page_lookup[$pid] = $ptitle;
    } 
}

// 2. Fetch Items and map the titles
$items = [];
$res = $db->conn->query("SELECT * FROM menu_items WHERE menu_id = {$menu_id} ORDER BY sort_order ASC");
if ($res) { 
    while ($r = $res->fetch_assoc()) { 
        if (!empty($r['page_id']) && isset($page_lookup[$r['page_id']])) {
            $r['fetched_page_title'] = $page_lookup[$r['page_id']];
        }
        $items[] = $r; 
    } 
}

function buildMenuHTML($items, $parent_id = null, $depth = 0) {
    $html = '';
    foreach ($items as $item) {
        if ($item['parent_id'] == $parent_id) {
            $indent = $depth * 30;
            $link_display = !empty($item['page_id']) ? "Page: " . htmlspecialchars($item['fetched_page_title'] ?? 'Unknown') : htmlspecialchars($item['url']);
            $target_badge = $item['target'] === '_blank' ? '<span class="badge badge-gray" style="font-size: 10px; margin-right:5px;"><i class="fa-solid fa-external-link-alt"></i></span>' : '';
            
            // Add identifying class for JS Drag & Drop logic
            $hier_class = is_null($item['parent_id']) ? 'is-top-item' : 'is-sub-item';

            $data_attrs = "data-id='{$item['id']}' data-title='".htmlspecialchars($item['title'], ENT_QUOTES)."' data-target='{$item['target']}' data-parent='".($item['parent_id'] ?? '')."' data-page='".($item['page_id'] ?? '')."' data-url='".htmlspecialchars($item['url'], ENT_QUOTES)."'";

            $html .= "
            <div class='menu-item-row draggable {$hier_class}' draggable='true' data-id='{$item['id']}' style='margin-left: {$indent}px;'>
                <div class='menu-item-drag-handle'><i class='fa-solid fa-grip-vertical'></i></div>
                <div class='menu-item-details'>
                    <span class='menu-item-title'>".htmlspecialchars($item['title'])."</span>
                    <span class='menu-item-url'>{$link_display}</span>
                </div>
                <div class='menu-item-actions'>
                    {$target_badge}
                    <button type='button' class='action-icon-btn edit ajax-edit-btn' {$data_attrs} title='Edit'><i class='fa-solid fa-pen'></i></button>
                    <button type='button' class='action-icon-btn delete ajax-delete-btn' data-id='{$item['id']}' title='Delete'><i class='fa-solid fa-trash-alt'></i></button>
                </div>
            </div>";
            $html .= buildMenuHTML($items, $item['id'], $depth + 1);
        }
    }
    return $html;
}

$rendered_html = buildMenuHTML($items);
if (empty($rendered_html)) { $rendered_html = '<div style="text-align: center; padding: 30px; color: var(--text-muted); font-style: italic;">No links added yet.</div>'; }

$parent_opts_html = '<option value="">-- Top Level (No Parent) --</option>';
foreach ($items as $item) {
    if (is_null($item['parent_id'])) {
        $parent_opts_html .= '<option value="'.$item['id'].'">'.htmlspecialchars($item['title']).'</option>';
    }
}

echo json_encode(['status' => 'success', 'html' => $rendered_html, 'parent_opts' => $parent_opts_html]);
?>