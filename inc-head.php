<?php
require "db.php";
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta name="description" content="Aleksander Støle">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<meta charset="UTF-8">
<title>Aleksander Støle</title>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="/assets/main.css?v=<?php echo date("mdHis") ?>">
<script defer src="/assets/font-awesome/fontawesome.min.js"></script>
<script defer src="/assets/font-awesome/solid.min.js"></script>
<script defer src="/assets/font-awesome/brands.min.js"></script>
</head>
<body>
		<header>
			<div id="nav-mobile">
				<div id="nav-mobile-toggle" class="nav-mobile-item"><i id="nav-mobile-toggle-icon" class="far fa-bars"></i></div>
				<a class="nav-mobile-item nav-mobile-item-logo" href="index.php">aleksander støle</a>
			</div>
			<nav>
				<ol>
					<li class="logo"><a class="logo" href="index.php">aleksander støle</a></li>
					<li><a class="menu-item" href="index.php"><i class="fa-solid fa-house"></i>Home</a></li>
					<li><a class="menu-item" href="page.php?p=4"><i class="fa-solid fa-file-user"></i>CV</a></li>
					<li><a class="menu-item" href="page.php?p=5"><i class="fa-solid fa-project-diagram"></i>Projects</a></li>
					<li><a class="menu-item" href="page.php?p=1"><i class="fa-solid fa-envelope-open-text"></i>Contact</a></li>
				</ol>
			</nav>
		</header>
		<main>
