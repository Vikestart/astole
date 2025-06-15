<?php
	$isLoginPage = true;
	$site_title = "Login";
	require "inc-adm-head.php";
?>

	<main id="page-login">

		<section>

			<h1>AdminCP</h1>

			<div class="login-box">

				<form action="process-login.php" method="POST" id="form-login">

					<?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-fullwidth msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

					<input name="username" class="form-login-field" type="text" minlength="3" maxlength="40" placeholder="Username or email" required />
					<input name="password" class="form-login-field" type="password" minlength="8" maxlength="20" placeholder="Password" required />
					<button id="form-login-submit" class="form-login-submit" type="submit"><i class="fa-solid fa-sign-in-alt" data-fa-transform="up-1"></i>Proceed</button>

				</form>

			</div>

		</section>

	</main>

<?php require "inc-adm-foot.php" ?>
