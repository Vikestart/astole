<?php
// /core/actions/adm/ajax-menus.php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// ==========================================
// THE UNIVERSAL JSON ERROR CATCHER
// ==========================================
function outputJSON($data) {
    if (ob_get_level()) ob_end_clean(); // Wipe any HTML/Warnings that printed before this
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Convert all PHP Errors and Exceptions into clean JSON responses!
set_error_handler(function($severity, $message, $file, $line) {
    outputJSON(['status' => 'error', 'message' => "PHP Error: $message on line $line"]);
});
set_exception_handler(function($e) {
    outputJSON(['status' => 'error', 'message' => "System Exception: " . $e->getMessage()]);
});

// Start a buffer just to be safe
ob_start();

require_once __DIR__ . '/../../init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    outputJSON(['status' => 'error', 'message' => 'Security validation failed.']);
}

$db = new \Core\Lib\Database();
$conn = $db->getConnection();
$logger = new \Core\Lib\ActivityLogger();

// Safely fetch user role
$stmt_auth = $conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
$stmt_auth->bind_param("i", $_SESSION['UserID']);
$stmt_auth->execute();
$result_auth = $stmt_auth->get_result();
$u_res = $result_auth ? $result_auth->fetch_assoc() : null;
$role = $u_res ? (int)$u_res['user_role'] : 3;
$stmt_auth->close();

if ($role === 3) { outputJSON(['status' => 'error', 'message' => 'Permission denied.']); }

$action = $_POST['action'] ?? '';

// ==========================================
// 1. TOP-LEVEL MENU CRUD
// ==========================================
if ($action === 'get_menu_list') {
    $html = '';
    $menus = $conn->query("
        SELECT m.*, COUNT(mi.id) as item_count 
        FROM menus m 
        LEFT JOIN menu_items mi ON m.id = mi.menu_id 
        GROUP BY m.id 
        ORDER BY m.name ASC
    ");
    
    if ($menus && $menus->num_rows > 0) {
        while($row = $menus->fetch_assoc()) {
            $name = htmlspecialchars($row['name'] ?? '');
            $identifier = htmlspecialchars($row['identifier'] ?? '');
            
            $html .= '<tr>';
            $html .= '<td><a href="#" onclick="showBuilder('.$row['id'].'); return false;" style="color: var(--color-heading); font-weight: 600;">'.$name.'</a></td>';
            $html .= '<td><span style="font-family: monospace; background: var(--bg-body); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--border); font-size: 13px; color: var(--text-muted);">'.$identifier.'</span></td>';
            $html .= '<td><span class="badge badge-gray">'.$row['item_count'].' items</span></td>';
            $html .= '<td style="text-align: right;" class="table-actions">';
            $html .= '<button class="action-icon-btn view" onclick="openSettingsModal('.$row['id'].', \''.htmlspecialchars($row['name'] ?? '', ENT_QUOTES).'\', \''.htmlspecialchars($row['identifier'] ?? '', ENT_QUOTES).'\')" title="Settings"><i class="fa-solid fa-cog"></i></button>';
            $html .= '<button class="action-icon-btn edit ml-10" onclick="showBuilder('.$row['id'].')" title="Edit Menu Structure"><i class="fa-solid fa-pen"></i></button>';
            $html .= '<button class="action-icon-btn delete ml-10" onclick="deleteMenu('.$row['id'].')" title="Delete Menu"><i class="fa-solid fa-trash-alt"></i></button>';
            $html .= '</td></tr>';
        }
    } else {
        $html = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">No menus found.</td></tr>';
    }
    outputJSON(['status' => 'success', 'html' => $html]);
}

if ($action === 'create_menu' || $action === 'edit_menu') {
    $name = trim($_POST['menu_name'] ?? '');
    $identifier = trim(strtolower($_POST['menu_identifier'] ?? ''));
    $identifier = preg_replace('/[^a-z0-9_-]/', '', $identifier);

    if(empty($name) || empty($identifier)) { outputJSON(['status' => 'error', 'message' => 'Name and Identifier are required.']); }

    if ($action === 'create_menu') {
        $stmt = $conn->prepare("INSERT INTO menus (name, identifier) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $identifier);
        if ($stmt->execute()) {
            $logger->logAdminActivity($_SESSION['UserID'], 'Settings', "Created new menu: {$name}");
            outputJSON(['status' => 'success', 'message' => 'Menu created successfully.']);
        } else {
            outputJSON(['status' => 'error', 'message' => 'That identifier is already in use.']);
        }
    } else {
        $menu_id = (int)$_POST['menu_id'];
        $stmt = $conn->prepare("UPDATE menus SET name = ?, identifier = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $identifier, $menu_id);
        if ($stmt->execute()) {
            $logger->logAdminActivity($_SESSION['UserID'], 'Settings', "Updated menu settings for: {$name}");
            outputJSON(['status' => 'success', 'message' => 'Menu settings updated.']);
        } else {
            outputJSON(['status' => 'error', 'message' => 'That identifier is already in use.']);
        }
    }
}

if ($action === 'delete_menu') {
    $menu_id = (int)$_POST['menu_id'];
    $stmt_n = $conn->prepare("SELECT name FROM menus WHERE id = ?");
    $stmt_n->bind_param("i", $menu_id);
    $stmt_n->execute();
    $n_result = $stmt_n->get_result();
    $n_res = $n_result ? $n_result->fetch_assoc() : null;
    $name_del = $n_res ? $n_res['name'] : 'Unknown Menu';
    $stmt_n->close();

    $conn->query("DELETE FROM menu_items WHERE menu_id = " . $menu_id);
    $stmt = $conn->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->bind_param("i", $menu_id);
    if($stmt->execute()) {
        $logger->logAdminActivity($_SESSION['UserID'], 'Settings', "Deleted menu: {$name_del}");
        outputJSON(['status' => 'success', 'message' => 'Menu permanently deleted.']);
    }
}

// ==========================================
// 2. MENU ITEMS BUILDER LOGIC
// ==========================================
$menu_id = (int)($_POST['menu_id'] ?? 0);

if ($action === 'get_builder_data') {
    $stmt = $conn->prepare("SELECT name FROM menus WHERE id = ?");
    $stmt->bind_param("i", $menu_id);
    $stmt->execute();
    $m_result = $stmt->get_result();
    $m_res = $m_result ? $m_result->fetch_assoc() : null;
    $menu_name = $m_res ? $m_res['name'] : 'Unknown Menu';
    $stmt->close();

    // FIX: Safely pull the correct 'page_id' column
    $page_lookup = [];
    $pages_html = '<option value="">-- Choose a Page --</option>';
    $res_pages = $conn->query("SELECT page_id, page_title FROM pages ORDER BY page_title ASC");
    if($res_pages) { 
        while($p = $res_pages->fetch_assoc()) { 
            $page_lookup[$p['page_id']] = $p['page_title'] ?? 'Unnamed Page';
            $pages_html .= '<option value="'.$p['page_id'].'">'.htmlspecialchars($p['page_title'] ?? '').'</option>';
        } 
    }

    $items = [];
    $res = $conn->query("SELECT * FROM menu_items WHERE menu_id = {$menu_id} ORDER BY sort_order ASC");
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
                
                $url = $item['url'] ?? ''; 
                $title = $item['title'] ?? '';
                $target = $item['target'] ?? '_self';
                $fetched_title = $item['fetched_page_title'] ?? 'Unknown';
                
                $link_display = !empty($item['page_id']) ? "Page: " . htmlspecialchars($fetched_title) : htmlspecialchars($url);
                $target_badge = $target === '_blank' ? '<span class="badge badge-gray" style="font-size: 10px; margin-right:5px;"><i class="fa-solid fa-external-link-alt"></i></span>' : '';
                $hier_class = is_null($item['parent_id']) ? 'is-top-item' : 'is-sub-item';
                
                $data_attrs = "data-id='{$item['id']}' data-title='".htmlspecialchars($title, ENT_QUOTES)."' data-target='{$target}' data-parent='".($item['parent_id'] ?? '')."' data-page='".($item['page_id'] ?? '')."' data-url='".htmlspecialchars($url, ENT_QUOTES)."'";

                $html .= "
                <div class='menu-item-row draggable {$hier_class}' draggable='true' data-id='{$item['id']}' style='margin-left: {$indent}px;'>
                    <div class='menu-item-drag-handle'><i class='fa-solid fa-grip-vertical'></i></div>
                    <div class='menu-item-details'>
                        <span class='menu-item-title'>".htmlspecialchars($title)."</span>
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
    if (empty($rendered_html)) { $rendered_html = '<div class="text-center p-20 text-muted">No links added yet.</div>'; }

    $parent_opts_html = '<option value="">-- Top Level (No Parent) --</option>';
    foreach ($items as $item) {
        if (is_null($item['parent_id'])) {
            $parent_opts_html .= '<option value="'.$item['id'].'">'.htmlspecialchars($item['title'] ?? '').'</option>';
        }
    }

    outputJSON([
        'status' => 'success', 
        'menu_name' => $menu_name,
        'html' => $rendered_html, 
        'parent_opts' => $parent_opts_html,
        'pages_opts' => $pages_html
    ]);
}

if ($action === 'add_item' || $action === 'edit_item') {
    $title = trim($_POST['title'] ?? '');
    $link_type = $_POST['link_type'] ?? 'url';
    $target = $_POST['target'] === '_blank' ? '_blank' : '_self';
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $url = ($link_type === 'url') ? trim($_POST['url'] ?? '') : '';
    $page_id = ($link_type === 'page') ? (int)($_POST['page_id'] ?? 0) : null;
    if ($page_id === 0) $page_id = null;

    if (empty($title)) { outputJSON(['status' => 'error', 'message' => 'Title is required.']); }

    if ($action === 'add_item') {
        $stmt_max = $conn->prepare("SELECT MAX(sort_order) AS max_sort FROM menu_items WHERE menu_id = ? AND IFNULL(parent_id, 0) = ?");
        $p_check = $parent_id ?? 0;
        $stmt_max->bind_param("ii", $menu_id, $p_check);
        $stmt_max->execute();
        $max_result = $stmt_max->get_result();
        $max_res = $max_result ? $max_result->fetch_assoc() : null;
        $next_sort = ($max_res && $max_res['max_sort'] !== null) ? (int)$max_res['max_sort'] + 1 : 0;
        $stmt_max->close();

        $stmt = $conn->prepare("INSERT INTO menu_items (menu_id, parent_id, page_id, title, url, sort_order, target) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissis", $menu_id, $parent_id, $page_id, $title, $url, $next_sort, $target);
        $stmt->execute();
        outputJSON(['status' => 'success', 'message' => 'Link added successfully.']);
    } else {
        $item_id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("UPDATE menu_items SET parent_id=?, page_id=?, title=?, url=?, target=? WHERE id=?");
        $stmt->bind_param("iisssi", $parent_id, $page_id, $title, $url, $target, $item_id);
        $stmt->execute();
        outputJSON(['status' => 'success', 'message' => 'Link updated successfully.']);
    }
}

if ($action === 'delete_item') {
    $item_id = (int)$_POST['item_id'];
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    outputJSON(['status' => 'success', 'message' => 'Link deleted.']);
}

if ($action === 'update_order') {
    $sort_data = json_decode($_POST['sort_order_data'] ?? '[]', true);
    if (is_array($sort_data)) {
        $stmt = $conn->prepare("UPDATE menu_items SET sort_order = ?, parent_id = ? WHERE id = ?");
        foreach ($sort_data as $item) {
            $order = (int)$item['order']; 
            $id = (int)$item['id'];
            $pid = !empty($item['parent_id']) ? (int)$item['parent_id'] : null;
            
            // SAFETY FIX: Prevent an item from accidentally becoming its own parent!
            if ($pid === $id) { $pid = null; }
            
            $stmt->bind_param("iii", $order, $pid, $id);
            $stmt->execute();
        }
        outputJSON(['status' => 'success', 'message' => 'Menu hierarchy saved.']);
    }
}

// Fallback if action is missing or invalid
outputJSON(['status' => 'error', 'message' => 'Invalid Request Action']);