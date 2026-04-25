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

    // --- 4. FETCH MAIN NAVIGATION ---
    // Safely build a lookup array for page slugs to prevent JOIN crashes
    $page_slugs = [];
    $res_pages = $header_db->conn->query("SELECT * FROM pages");
    if ($res_pages) {
        while ($p = $res_pages->fetch_assoc()) {
            $pid = $p['id'] ?? $p['page_id'] ?? 0;
            $pslug = $p['page_slug'] ?? $p['slug'] ?? '';
            if ($pid) { $page_slugs[$pid] = $pslug; }
        }
    }

    $nav_items = [];
    $stmt_nav = $header_db->conn->prepare("
        SELECT mi.* FROM menu_items mi 
        JOIN menus m ON mi.menu_id = m.id 
        WHERE m.identifier = 'main_nav' 
        ORDER BY mi.sort_order ASC
    ");
    
    if ($stmt_nav) {
        $stmt_nav->execute();
        $res_nav = $stmt_nav->get_result();
        while($row = $res_nav->fetch_assoc()) {
            $slug = '';
            if (!empty($row['page_id']) && isset($page_slugs[$row['page_id']])) {
                $slug = $page_slugs[$row['page_id']];
            }
            
            // Resolve the true URL (Page Slug vs Custom URL)
            $final_url = (!empty($row['page_id']) && !empty($slug)) ? '/' . ltrim($slug, '/') : $row['url'];
            $row['final_url'] = $final_url;
            $nav_items[] = $row;
        }
        $stmt_nav->close();
    }

    // Recursive function to build standard links and nested glass dropdowns
    function buildFrontendMenu($items, $parent_id = null, $current_route = '') {
        $html = '';
        foreach ($items as $item) {
            if ($item['parent_id'] == $parent_id) {
                $has_children = false;
                foreach ($items as $sub) { if ($sub['parent_id'] == $item['id']) { $has_children = true; break; } }
                
                $route_match = trim(str_replace('/', '', $item['final_url']));
                $active_class = ($current_route === $route_match || ($current_route === '' && $route_match === 'home')) ? 'active' : '';
                $target = ($item['target'] === '_blank') ? ' target="_blank" rel="noopener noreferrer"' : '';
                
                if ($has_children) {
                    $html .= '<div class="nav-dropdown">';
                    // Split the link and the toggle button for mobile accessibility
                    $html .= '<div class="nav-dropdown-trigger">';
                    $html .= '<a href="'.htmlspecialchars($item['final_url']).'" class="nav-item '.$active_class.'"'.$target.'>'.htmlspecialchars($item['title']).'</a>';
                    $html .= '<button class="submenu-toggle" aria-label="Toggle Submenu"><i class="fa-solid fa-chevron-down"></i></button>';
                    $html .= '</div>';
                    // Dropdown menu content
                    $html .= '<div class="nav-dropdown-menu">';
                    $html .= buildFrontendMenu($items, $item['id'], $current_route);
                    $html .= '</div></div>';
                } else {
                    $html .= '<a href="'.htmlspecialchars($item['final_url']).'" class="nav-item '.$active_class.'"'.$target.'>'.htmlspecialchars($item['title']).'</a>';
                }
            }
        }
        return $html;
    }
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

    <script defer src="/assets/font-awesome/fontawesome.min.js"></script>
    <script defer src="/assets/font-awesome/solid.min.js"></script>
    <script defer src="/assets/font-awesome/brands.min.js"></script>

    <link rel="stylesheet" href="/assets/main.css">
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
					<?php echo buildFrontendMenu($nav_items, null, $route ?? ''); ?>
				</nav>
				
				<button class="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
			</div>
		</header>

		<!-- Main Content Wrapper -->
		<main class="main-content">