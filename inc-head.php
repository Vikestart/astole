<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once __DIR__ . "/db.php";
    $header_db = new DBConn();

    // --- 1. FETCH GLOBAL SETTINGS ---
    $res = $header_db->conn->query("SELECT setting_key, setting_value FROM settings");
    $global_settings = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $global_settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    $site_name = $global_settings['site_name'] ?? 'Astole';
    $seo_desc = $global_settings['seo_description'] ?? '';
    $ga_id = $global_settings['ga_id'] ?? '';
    $maintenance_mode = (int)($global_settings['maintenance_mode'] ?? 0);

    // --- 2. MAINTENANCE MODE CHECK ---
    $is_admin = false;
    if (isset($_SESSION['UserID'])) {
        $stmt_role = $header_db->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
        $stmt_role->bind_param("i", $_SESSION['UserID']);
        $stmt_role->execute();
        $res_role = $stmt_role->get_result();
        if ($res_role->num_rows === 1 && (int)$res_role->fetch_assoc()['user_role'] === 1) {
            $is_admin = true;
        }
        $stmt_role->close();
    }

    if ($maintenance_mode === 1 && !$is_admin) {
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Under Maintenance | " . htmlspecialchars($site_name) . "</title>
            <style>
                body { background: #f8fafc; color: #334155; font-family: sans-serif; text-align: center; padding: 10% 20px; }
                h1 { font-size: 2.5rem; color: #0f172a; margin-bottom: 10px; }
                p { font-size: 1.2rem; }
            </style>
        </head>
        <body>
            <h1>We'll be right back!</h1>
            <p>Our website is currently undergoing scheduled maintenance. Please check back soon.</p>
        </body>
        </html>");
    }

    // --- 3. DYNAMIC SEO VARIABLES ---
    $final_title = isset($page_title) ? $page_title . " | " . $site_name : $site_name;
    $final_desc = isset($page_desc) ? $page_desc : $seo_desc;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($final_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($final_desc); ?>">

    <?php if (!empty($ga_id)) { ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($ga_id); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars($ga_id); ?>');
    </script>
    <?php } ?>

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9553054176028249" crossorigin="anonymous"></script>

    <script defer src="assets/font-awesome/fontawesome.min.js"></script>
    <script defer src="assets/font-awesome/solid.min.js"></script>
    <script defer src="assets/font-awesome/brands.min.js"></script>

    <link rel="stylesheet" href="assets/main.css">
    <script src="assets/jquery.js"></script>
    <script src="assets/main.js"></script>
</head>
<body>
	<!-- Soft Animated Mesh Gradient Background -->
	<div class="mesh-bg">
		<div class="mesh-blob blob-1"></div>
		<div class="mesh-blob blob-2"></div>
		<div class="mesh-blob blob-3"></div>
	</div>

	<!-- App Layout Wrapper -->
	<div class="page-wrapper">
		
		<!-- Glassmorphism Floating Navigation -->
		<header class="glass-header">
			<div class="header-container">
				<a href="/" class="nav-brand">
					<span class="brand-initials">A.S</span>
				</a>
				
				<nav class="nav-links">
					<a href="/home" class="nav-item <?php echo ($route === 'home' || $route === '') ? 'active' : ''; ?>">Home</a>
					<a href="/experience" class="nav-item <?php echo ($route === 'experience') ? 'active' : ''; ?>">Experience</a>
					<a href="/projects" class="nav-item <?php echo ($route === 'projects') ? 'active' : ''; ?>">Projects</a>
					<a href="/contact" class="nav-item <?php echo ($route === 'contact') ? 'active' : ''; ?>">Contact</a>
				</nav>
				
				<button class="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
			</div>
		</header>

		<!-- Main Content Wrapper -->
		<main class="main-content">