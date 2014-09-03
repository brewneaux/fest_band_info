<?php

include('connect.php');
//include config file

if($_GET["action"] == "getArtist") {
	$artistid = $_GET['artist'];
	getArtist($dbi,$artistid);
}
elseif ($_GET['action'] == "helloWorld") {
	helloWorld();
}
elseif ($_GET['action'] == "wrongData") {
	$artistid = $_POST['artistid'];
	$element = $_POST['element'];
	$suggestion = $_POST['suggestion'];
	wrongData($dbi,$artistid,$element,$suggestion);
}
else {
	header('HTTP/1.1 400 Bad Request');
}

function getArtist($dbi, $artistname) {

	if (!is_numeric($artistname)){
		echo json_encode('stop that!');
			}

	$query = "SELECT id,band,lastfm_genre,spotify_uri,spotify_web,bandcamp_offsite,bandcamp_url,pathtoimage,youtube_id,lastfm_topsong FROM fest_info_working_1 WHERE id=" . $artistname;


	if($results = $dbi->query($query)) {
		if ($results->num_rows > 1){
			return 'error, too many rows returned';
		}
		else {
			$row = $results->fetch_assoc();
			// header('Content-Type: application/json');
			echo json_encode($row);
		}
	}
}

function helloWorld() {
	echo json_encode('value');
}

// if ($results = mysql_query($query)) {
// 	while ($row = $results->fetch_assoc()) {
	
// 	}
// }

function wrongData($dbi, $artistid,$element,$suggestion) {
	$suggestion = $dbi->real_escape_string($suggestion);

	$query = "INSERT INTO fest_info_suggestions (artistid, element, suggestion) VALUES ('$artistid', '$element', '$suggestion')";

	if ($dbi->query($query)) {
		return true;
	}
	else {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}
}


?>
