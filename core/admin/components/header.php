<?php
// /core/admin/components/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Fetch User Data if logged in (Used by the navigation panel)
$userdata = new stdClass();
if (!empty($_SESSION['UserID'])) {
    $db = new \Core\Lib\Database();
    $stmt_userdata = $db->getConnection()->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt_userdata->bind_param("i", $_SESSION['UserID']);
    $stmt_userdata->execute();
    $result_userdata = $stmt_userdata->get_result();
    
    if ($result_userdata->num_rows === 1) {
        $userdata->row = $result_userdata->fetch_assoc();
    }
    $stmt_userdata->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="robots" content="noindex, nofollow">
    <title>ACP | <?php echo isset($site_title) ? htmlspecialchars($site_title) : 'Dashboard'; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/core/admin/assets/adm.css?v=<?php echo date('mdHis'); ?>">
    <script defer src="/core/assets/font-awesome/fontawesome.min.js"></script>
    <script defer src="/core/assets/font-awesome/solid.min.js"></script>
    <script defer src="/core/assets/font-awesome/brands.min.js"></script>
</head>
<body>