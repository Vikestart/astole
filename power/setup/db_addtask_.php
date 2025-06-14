<?php
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
	die('Direct access to this file is not permitted!');
}

$servername = 'localhost';
$username = 'aleksand_login';
$password = 'Xnqbs9508!!';
$dbname = 'aleksand_power';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
	die('Connection failed: ' . $conn->connect_error);
}

mysqli_set_charset($conn,"utf8");
date_default_timezone_set('Europe/Oslo');

$bilag = mysqli_real_escape_string($conn, $_POST['bilag']);
$avdeling = substr($bilag, 0,4);
$type = mysqli_real_escape_string($conn, $_POST['type']);
$time = mysqli_real_escape_string($conn, $_POST['time']);

$sql = "INSERT INTO setuplist (avdeling,bilag,type,time) VALUES ('$avdeling','$bilag','$type','$time')";
$result = $conn->query($sql);

header('Content-type: application/json');
echo json_encode($response_array);

$conn = null;
?>