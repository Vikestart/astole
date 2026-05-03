<?php
// /core/actions/adm/ajax-pages.php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../lib/admin-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Security validation failed.']);
    exit;
}

$db = new \Core\Lib\Database();
$conn = $db->getConnection();
$action = $_POST['action'] ?? '';

// Fetch active user role & UID for permissions matrix
$active_user_uid = 'Unknown';
$user_role = 3;
if (isset($_SESSION['UserID'])) {
    $stmt_u = $conn->prepare("SELECT user_uid, user_role FROM users WHERE user_id = ?");
    $stmt_u->bind_param("i", $_SESSION['UserID']);
    $stmt_u->execute();
    $u_res = $stmt_u->get_result()->fetch_assoc();
    if ($u_res) {
        $active_user_uid = $u_res['user_uid'];
        $user_role = (int)$u_res['user_role'];
    }
    $stmt_u->close();
}

// 1. FETCH PAGE LIST (HTML)
if ($action === 'get_list') {
    $html = '';
    $res = $conn->query("SELECT * FROM pages ORDER BY page_title");
    
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $can_modify = ($user_role === 1 || $user_role === 2 || ($user_role === 3 && $row['page_author'] === $active_user_uid));
            
            $html .= '<tr>';
            $html .= '<td>';
            if ($can_modify) {
                $html .= '<a href="#" onclick="showForm(' . $row['page_id'] . '); return false;" style="color: var(--color-heading); font-weight: 700;">' . htmlspecialchars($row['page_title']) . '</a>';
            } else {
                $html .= '<strong style="color: var(--color-heading); font-weight: 700;">' . htmlspecialchars($row['page_title']) . '</strong>';
            }
            $html .= '</td>';
            $html .= '<td><span class="badge badge-blue badge-noborder">/' . htmlspecialchars($row['page_slug']) . '</span></td>';
            $html .= '<td><span class="badge badge-gray">' . htmlspecialchars($row['page_type'] ?? 'standard') . '</span></td>';
            $html .= '<td>' . htmlspecialchars($row['page_author']) . '</td>';
            $html .= '<td>' . htmlspecialchars(date('M d, Y', strtotime($row['page_updated']))) . '</td>';
            $html .= '<td style="text-align: right;" class="table-actions">';
            $html .= '<a href="/' . htmlspecialchars($row['page_slug']) . '" target="_blank" class="action-icon-btn view" title="View Public Page"><i class="fa-solid fa-external-link-alt fa-sm"></i></a>';
            
            if ($can_modify) {
                $html .= '<button onclick="showForm(' . $row['page_id'] . ')" title="Edit" class="action-icon-btn edit ml-10"><i class="fa-solid fa-edit"></i></button>';
                $html .= '<button onclick="deletePage(' . $row['page_id'] . ')" title="Delete" class="action-icon-btn delete ml-10"><i class="fa-solid fa-trash-alt"></i></button>';
            } else {
                $html .= '<span class="action-disabled ml-10" title="Not Allowed"><i class="fa-solid fa-edit"></i></span>';
                $html .= '<span class="action-disabled ml-10" title="Not Allowed"><i class="fa-solid fa-trash-alt"></i></span>';
            }
            $html .= '</td></tr>';
        }
    } else {
        $html = '<tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No pages found. Create one!</td></tr>';
    }
    
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
}

// 2. FETCH SINGLE PAGE DATA (JSON)
if ($action === 'get_page') {
    $id = (int)$_POST['page_id'];
    $stmt = $conn->prepare("SELECT * FROM pages WHERE page_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// 3. SAVE PAGE (INSERT OR UPDATE)
if ($action === 'save_page') {
    $page_id = (int)$_POST['page_id'];
    $pagetitle = trim($_POST['pagetitle'] ?? '');
    $pageslug = trim($_POST['pageslug'] ?? ''); 
    $pagedesc = trim($_POST['pagedesc'] ?? '');
    $pagetype = $_POST['pagetype'] ?? 'standard';
    $pagecontents = trim($_POST['pagecontents'] ?? '');
    $currtime = gmdate("Y-m-d H:i:s"); 

    if (empty($pagetitle) || ($pagetype === 'standard' && empty($pagecontents))) { 
        echo json_encode(['status' => 'error', 'message' => 'Page title and contents are required.']);
        exit;
    }

    if ($page_id === 0) { // Insert
        $stmt = $conn->prepare("INSERT INTO pages (page_title, page_slug, page_desc, page_author, page_created, page_updated, page_contents, page_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $pagetitle, $pageslug, $pagedesc, $active_user_uid, $currtime, $currtime, $pagecontents, $pagetype); 
        $stmt->execute();
        $logger = new \Core\Lib\ActivityLogger();
        $logger->logAdminActivity($_SESSION['UserID'], 'Page', 'Created page: ' . $pagetitle);
        echo json_encode(['status' => 'success', 'message' => 'Page created successfully!']);
    } else { // Update
        $stmt = $conn->prepare("UPDATE pages SET page_title = ?, page_slug = ?, page_desc = ?, page_updated = ?, page_contents = ?, page_type = ? WHERE page_id = ?");
        $stmt->bind_param("ssssssi", $pagetitle, $pageslug, $pagedesc, $currtime, $pagecontents, $pagetype, $page_id); 
        $stmt->execute();
        $logger = new \Core\Lib\ActivityLogger();
        $logger->logAdminActivity($_SESSION['UserID'], 'Page', 'Updated page: ' . $pagetitle);
        echo json_encode(['status' => 'success', 'message' => 'Page updated successfully!']);
    }
    exit;
}

// 4. DELETE PAGE
if ($action === 'delete_page') {
    $del_id = (int)$_POST['page_id'];
    
    // Fetch title for logger
    $stmt_t = $conn->prepare("SELECT page_title FROM pages WHERE page_id = ?");
    $stmt_t->bind_param("i", $del_id);
    $stmt_t->execute();
    $del_title = $stmt_t->get_result()->fetch_assoc()['page_title'] ?? 'Unknown Page';
    $stmt_t->close();

    $stmt = $conn->prepare("DELETE FROM pages WHERE page_id = ?");
    $stmt->bind_param("i", $del_id);
    if($stmt->execute()) {
        $logger = new \Core\Lib\ActivityLogger();
        $logger->logAdminActivity($_SESSION['UserID'], 'Page', 'Deleted page: ' . $del_title);
        echo json_encode(['status' => 'success', 'message' => 'Page permanently deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete page.']);
    }
    $stmt->close();
    exit;
}