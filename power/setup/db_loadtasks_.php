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

$deleteschedule = "DELETE FROM setuplist WHERE time < (NOW() - INTERVAL 2 HOUR)";
$conn->query($deleteschedule);

$avdeling = mysqli_real_escape_string($conn, $_POST['valgtavd']);

$sql = "SELECT * FROM setuplist WHERE avdeling='".$avdeling."' ORDER BY 'time'";
$result = $conn->query($sql);

if (mysqli_num_rows($result) > 0) {

	foreach ($result as $row) {
		
		$return[] = [
			'avdeling' => $row['avdeling'],
			'bilag' => $row['bilag'],
			'type' => $row['type'],
			'time' => $row['time']
		];
	}

	header('Content-type: application/json');
	echo json_encode($return, JSON_UNESCAPED_UNICODE);
	
} else {
	
	header('Content-type: application/json');
	echo json_encode('', JSON_UNESCAPED_UNICODE);
	
}

$conn = null;
?>