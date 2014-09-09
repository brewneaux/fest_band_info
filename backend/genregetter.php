#!/usr/bin/php

<?php

// Gets accurate genres for bands from the other table.
// Eventually, I'll work this into my other script.  Its just a lot of logic (accidentally).
// table setup is:
// CREATE TABLE `fest_genres_1` (
//   `band` varchar(255) NOT NULL DEFAULT '',
//   `genre` varchar(255) DEFAULT NULL,
//   PRIMARY KEY (`band`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

// Here's the list of genres I chose
$possible_genres = array(
	"melodic-hardcore",
	"post-hardcore",
	"pop-punk",
	"emo",
	"hardcore",
	"screamo",
	"punk",
	"folk",
	"indie",
	"metal",
	"acoustic"
);

$dbh = mysqli_connect("localhost",$config['db_user'],$config['db_pass'],$config['db_name']);

if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// fest_genres_1

$band_list_query = "SELECT band FROM fest_info_working_1";
$band_list_result = $dbh->query($band_list_query);

$band_list = array();


/* fetch associative array */
while ($band_result = mysqli_fetch_row($band_list_result)) {
    array_push($band_list, $band_result[0]);
}

/* free result set */
mysqli_free_result($band_list_result);


echo "Start of script" . PHP_EOL;

foreach ($band_list as $band) {
	$possible_tags = lastfmTagsOne($band);
	$chosen_genre = chooseGenre($possible_tags);

	$insert = $dbh->prepare("REPLACE INTO `fest_genres_1` VALUES (?,?)");
	$insert->bind_param('ss',$band, $chosen_genre);
	if ($result = $insert->execute()){
		$insert->free_result();
	}
	else {
		echo "error";
	}
	echo $band . ' - ' . $chosen_genre . PHP_EOL;
}


$test_json = lastfmTagsOne('American Lies');
// $resutls = chooseGenre($test_json);
print_r($test_json);

function lastfmTagsOne($band) {
	global $config;
	$last_fm_genre_url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getTopTags&artist=' . urlencode($band) .'&user=vonwhoopass&api_key=' . $config['lastfm_api'] . '&format=json';
	$lastfmtags_response = json_decode(file_get_contents($last_fm_genre_url), true);
	if (array_key_exists('toptags', $lastfmtags_response)) {
		$lastfmtags_response = $lastfmtags_response['toptags'];
	}
	else {
		return null;
	}
	if (array_key_exists('tag',$lastfmtags_response)) {
		$lastfmtags_response_tags = $lastfmtags_response['tag'];

		if (isset($lastfmtags_response_tags['0'])){
			$possible_tags = array();

			foreach ($lastfmtags_response_tags as $response_piece) {
				array_push($possible_tags, str_replace(' ', '-', $response_piece['name']));
			}
		}
		else {
			$possible_tags = $lastfmtags_response_tags['name'];
		}
		
		return $possible_tags;
	}
}


function chooseGenre($band_genre_array) {
	global $possible_genres;

	if (is_array($band_genre_array)) {
		foreach ($band_genre_array as $band_genre) {	
			if (str_word_count($band_genre, 0, 2) > 1){
				if (str_word_count($band_genre, 1, 2)['1'] == 'rock') {
					$band_genre = str_word_count($band_genre, 1, 2)['0'];
				}
				else {
					str_replace(' ', '-', $band_genre);
				}
			}
			if (in_array(strtolower($band_genre), $possible_genres)) {
				return $band_genre;
			}
		}
	}
	else {
		if (str_word_count($band_genre_array, 0, 2) > 1){
			if (str_word_count($band_genre_array, 1, 2)['1'] == 'rock') {
				$band_genre_array = str_word_count($band_genre_array, 1, 2)['0'];
			}
			else {
				str_replace(' ', '-', $band_genre_array);
			}	
		}
		if (in_array(strtolower($band_genre_array), $possible_genres)) {
				return $band_genre_array;
		}
	}
}

function lastfmTagsTest($band) {
	global $config;
	$last_fm_genre_url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getTopTags&artist=' . urlencode($band) .'&user=vonwhoopass&api_key=' . $config['lastfm_api'] . '&format=json';
	$lastfmtags_response = json_decode(file_get_contents($last_fm_genre_url), true)['toptags']['tag'];
	return $lastfmtags_response;
}

