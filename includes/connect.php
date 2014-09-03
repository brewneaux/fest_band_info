<?php

try {
	$config_file = 'config.ini';
	$config = parse_ini_file($config_file, true);
	if ($config === false) {
		throw new Exception( "died");
	}
	else {
	
	}
} 
catch (Exception $e) {
	echo $e;
	exit;
}

# db configuration 
define('DB_HOST', 'localhost');
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_pass']);
define('DB_NAME', $config['db_name']);

$limit = 30; #item per page
// # db connect
// $link = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Could not connect to MySQL DB ') . mysql_error();
// $db = mysql_select_db(DB_NAME, $link);

$dbi = new mysqli("localhost", $config['db_user'], $config['db_pass'], $config['db_name']);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}


?>
