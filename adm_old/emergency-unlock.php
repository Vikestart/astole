<?php
	die();
    require "../db.php";
    $db = new DBConn();
    
    // Generates a hash for the temporary password: Password123!
    $temp_hash = password_hash("Password123!", PASSWORD_DEFAULT);
    
    // Assuming your Admin account is user_id 1
    $db->conn->query("UPDATE users SET user_pass = '$temp_hash' WHERE user_id = 1");
    
    echo "<h1>Account Unlocked!</h1>";
    echo "<p>Your password for User ID 1 has been reset to: <strong>Password123!</strong></p>";
    echo "<p>Go login, and then <strong>IMMEDIATELY DELETE THIS SCRIPT FROM YOUR SERVER.</strong></p>";
?>