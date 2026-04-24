<?php
session_start();
require_once "../db.php";
require_once "admin-functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    returnWithMsg("menus", "error", "times-circle", 5000, "Security validation failed.", "menus.php");
}

if (!isset($_SESSION['UserID'])) { header("Location: login.php"); die(); }

$db = new DBConn();

// Verify Role
$stmt_auth = $db->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
$stmt_auth->bind_param("i", $_SESSION['UserID']);
$stmt_auth->execute();
$user_role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
$stmt_auth->close();

if ($user_role == 3) { returnWithMsg("menus", "error", "times-circle", 5000, "Permission denied.", "index.php"); }

$action = $_POST['action'] ?? '';

// === CREATE NEW MENU ===
if ($action === 'create_menu') {
    $name = trim($_POST['menu_name'] ?? '');
    $identifier = trim(strtolower($_POST['menu_identifier'] ?? ''));
    $identifier = preg_replace('/[^a-z0-9_-]/', '', $identifier);

    if(empty($name) || empty($identifier)) {
        returnWithMsg("menus", "error", "times-circle", 4000, "Name and Identifier are required.", "menus.php");
    }

    $stmt = $db->conn->prepare("INSERT INTO menus (name, identifier) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $identifier);
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        $stmt->close();
        returnWithMsg("menus", "success", "check-circle", 3000, "Menu created successfully.", "menus.php?menu_id=" . $new_id, $db->conn, 'Content', "Created new menu: {$name}");
    } else {
        $stmt->close();
        returnWithMsg("menus", "error", "times-circle", 4000, "That identifier is already in use. Please choose another.", "menus.php");
    }
}

// === EDIT MENU SETTINGS ===
if ($action === 'edit_menu') {
    $menu_id = (int)$_POST['menu_id'];
    $name = trim($_POST['menu_name'] ?? '');
    $identifier = trim(strtolower($_POST['menu_identifier'] ?? ''));
    $identifier = preg_replace('/[^a-z0-9_-]/', '', $identifier);

    if(empty($name) || empty($identifier)) {
        returnWithMsg("menus", "error", "times-circle", 4000, "Name and Identifier are required.", "menus.php?menu_id=".$menu_id);
    }

    $stmt = $db->conn->prepare("UPDATE menus SET name = ?, identifier = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $identifier, $menu_id);
    if ($stmt->execute()) {
        $stmt->close();
        returnWithMsg("menus", "success", "check-circle", 3000, "Menu settings updated.", "menus.php?menu_id=" . $menu_id, $db->conn, 'Content', "Updated menu settings for: {$name}");
    } else {
        $stmt->close();
        returnWithMsg("menus", "error", "times-circle", 4000, "That identifier is already in use by another menu.", "menus.php?menu_id=".$menu_id);
    }
}

// === DELETE MENU ===
if ($action === 'delete_menu') {
    $menu_id = (int)$_POST['menu_id'];
    
    // Get name for log
    $stmt_n = $db->conn->prepare("SELECT name FROM menus WHERE id = ?");
    $stmt_n->bind_param("i", $menu_id);
    $stmt_n->execute();
    $name_del = $stmt_n->get_result()->fetch_assoc()['name'] ?? 'Unknown Menu';
    $stmt_n->close();

    $stmt = $db->conn->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->bind_param("i", $menu_id);
    $stmt->execute();
    $stmt->close();

    returnWithMsg("menus", "success", "trash-alt", 3000, "Menu deleted successfully.", "menus.php", $db->conn, 'Content', "Deleted menu: {$name_del}");
}

// === ADD MENU ITEM ===
if ($action === 'add_item') {
    $menu_id = (int)$_POST['menu_id'];
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $target = $_POST['target'] === '_blank' ? '_blank' : '_self';

    if (empty($title) || empty($url)) {
        returnWithMsg("menus", "error", "times-circle", 4000, "Title and URL are required.", "menus.php");
    }

    // Get the highest sort_order to place this at the bottom automatically
    $stmt_max = $db->conn->prepare("SELECT MAX(sort_order) AS max_sort FROM menu_items WHERE menu_id = ?");
    $stmt_max->bind_param("i", $menu_id);
    $stmt_max->execute();
    $max_res = $stmt_max->get_result()->fetch_assoc();
    $next_sort = ($max_res['max_sort'] !== null) ? $max_res['max_sort'] + 1 : 0;
    $stmt_max->close();

    $stmt = $db->conn->prepare("INSERT INTO menu_items (menu_id, title, url, sort_order, target) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $menu_id, $title, $url, $next_sort, $target);
    $stmt->execute();
    $stmt->close();

    returnWithMsg("menus", "success", "check-circle", 3000, "Link added to menu.", "menus.php", $db->conn, 'Content', "Added menu item: {$title}");
}

// === DELETE MENU ITEM ===
if ($action === 'delete_item') {
    $item_id = (int)$_POST['item_id'];

    // Get title for the log
    $stmt_title = $db->conn->prepare("SELECT title FROM menu_items WHERE id = ?");
    $stmt_title->bind_param("i", $item_id);
    $stmt_title->execute();
    $t_res = $stmt_title->get_result()->fetch_assoc();
    $title_del = $t_res ? $t_res['title'] : 'Unknown Link';
    $stmt_title->close();

    $stmt = $db->conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();

    returnWithMsg("menus", "success", "trash-alt", 3000, "Link removed from menu.", "menus.php", $db->conn, 'Content', "Deleted menu item: {$title_del}");
}

// === UPDATE ORDER ===
if ($action === 'update_order') {
    $sort_data = json_decode($_POST['sort_order_data'] ?? '[]', true);

    if (is_array($sort_data) && !empty($sort_data)) {
        $stmt = $db->conn->prepare("UPDATE menu_items SET sort_order = ? WHERE id = ?");
        
        foreach ($sort_data as $index => $item_id) {
            $order = (int)$index;
            $id = (int)$item_id;
            $stmt->bind_param("ii", $order, $id);
            $stmt->execute();
        }
        $stmt->close();

        returnWithMsg("menus", "success", "sort", 3000, "Menu order saved successfully.", "menus.php", $db->conn, 'Content', "Updated navigation menu order.");
    }
    
    header("Location: menus.php");
    exit();
}
?>