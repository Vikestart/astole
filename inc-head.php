<?php
require "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
	<meta name="description" content="Aleksander Støle - Technical Consultant & Developer">
	<title>Aleksander Støle | Consultant & Developer</title>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	
	<!-- Modern Corporate/Tech Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	
	<link rel="stylesheet" href="/assets/main.css?v=<?php echo date("mdHis") ?>">
	
	<script src="/assets/font-awesome/fontawesome.min.js"></script>
	<script src="/assets/font-awesome/solid.min.js"></script>
	<script src="/assets/font-awesome/brands.min.js"></script>
	
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9553054176028249" crossorigin="anonymous"></script>
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-D9DKJ2PMQX"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', 'G-D9DKJ2PMQX');
	</script>
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
				<a href="index.php" class="nav-brand">
					<span class="brand-initials">A.S</span>
				</a>
				
				<nav class="nav-links">
					<a href="index.php" class="nav-item active">Home</a>
					<a href="page.php?p=4" class="nav-item">Experience</a>
					<a href="page.php?p=5" class="nav-item">Projects</a>
					<a href="page.php?p=1" class="nav-item">Contact</a>
				</nav>
				
				<button class="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
			</div>
		</header>

		<!-- Main Content Wrapper -->
		<main class="main-content">