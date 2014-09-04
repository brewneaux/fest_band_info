#!/usr/bin/php

<?php

/*
This script goes and grabs some information for all of the bands
that the Fest has reported as playing on www.thefestfl.com/bands

It uses a KimonoLabs API to do the scraping, which is simply
because theres is so easy that there was no reason for me to do 
it manually.  We go out and make API calls, for each band, to
LastFM, Spotify, Bandcamp, and YouTube.

This stores everything in a MySQL database, and will eventually
have a front end that displays it all pretty-like.

Any questions/comments/etc, pls contact me at 
jon@upthepunxfilm.com

And while you are at it, check out my documentary's website
at upthepunxfilm.com.

Thanks!

*/

/* Don't be lazy, do your initial table setup by hand:

CREATE TABLE `fest_info` (
  `band` varchar(255), 
  `lastfm_genre` varchar(255),
  `lastfm_topsong` varchar(255),
  `spotify_web`  varchar(255),
  `spotify_uri`  varchar(255),
  `spotify_image` varchar(255),
  `bandcamp_offsite` varchar(255),
  `bandcamp_url` varchar(255),
  `youtube_id` varchar(255),
  `youtube_title` varchar(255),
  PRIMARY KEY (`band`)
) ENGINE=MyISAM;

*/

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



// *******
// Connect to your DB
// *******

$dbh = mysqli_connect("localhost",$config['db_user'],$config['db_pass'],$config['db_name']);

if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

if (!$dbh->query("DROP TABLE IF EXISTS fest_info_temp") ||
    !$dbh->query("CREATE TABLE fest_info_temp LIKE fest_info")) {
    echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
}

// *******
// Go get the data from the API
// *******

$attempts = 4;

do { 
	$attempts--;

	$kimono_band_list = @file_get_contents($config['kimono_endpoint']);

	$kimono_length = strlen($kimono_band_list);

	if ($attempts < 4 && $kimono_length == 0) {
		echo "\nCouldn't get the band list. {$attempts} attempts remain.\n";
		sleep(2);
	}

	if ($attempts == 0) {
		echo "Never got band list";
		die;
	}
} while ($kimono_length == 0 && $attempts > 0);
$kimono_band_list = str_replace('"', '', $kimono_band_list);

$kimono_band_list = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $kimono_band_list);

// *******
// If debug is on, lets just do a subset.
// *******
	
	$band_list = str_replace("é","’",array("e","'"),$band_list);
	$band_list = explode("\r\n", $kimono_band_list);
	array_splice($band_list, 0, 2);
	array_pop($band_list);
	$band_list_length = count($band_list);
	// $band_list = array_slice($band_list, 0, 25);


// *******
// If you can't figure out what these are from the name, stop now.
// *******

function lastfmTagsOne($band) {
	global $config;
	$last_fm_genre_url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getTopTags&artist=' . urlencode($band) .'&user=vonwhoopass&api_key=' . $config['lastfm_api'] . '&format=json';
	$lastfmtags_response = json_decode(file_get_contents($last_fm_genre_url), true);
	$genre = $lastfmtags_response['toptags']['tag'][0]['name'];
	return $genre;
}

function lastfmTopSong($band) {
	global $config;
	$last_fm_song_url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getTopTracks&artist=' . urlencode($band) . '&limit=1&user=vonwhoopass&api_key=' . $config['lastfm_api'] . '&format=json';
	$lastfmsong_response = json_decode(file_get_contents($last_fm_song_url), true);
	$top_song = $lastfmsong_response['toptracks']['track']['name'];
	return $top_song;
}

function lastfmImage($band) {
	global $config;
	$lastfm_getartist_url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getInfo&artist=' . urlencode($band) . '&autocorrect=1&limit=1&user=vonwhoopass&api_key=' . $config['lastfm_api'] . '&format=json';
	$getartist_response = json_decode(file_get_contents($lastfm_getartist_url), true);
	$lastfm_url = $getartist_response['artist']['image']['4']['#text'];
	return $lastfm_url;
}	


function spotifyArtist($band) {
	global $config;
	$spotify_url = 'https://api.spotify.com/v1/search?q=' . rawurlencode($band) . '&type=artist&limit=1';
	$spotify_response = json_decode(file_get_contents($spotify_url), true);
	$spotify_web_url =  $spotify_response['artists']['items'][0]['external_urls']['spotify'];
	$spotify_uri = $spotify_response['artists']['items'][0]['uri'];
	$spotify_image_height = 1;
	$spotify_image_url = "none";
	// for ($i = count($spotify_response['artists']['items'][0]['images']); $i >= 0; $i--){
	// 	if ($spotify_response['artists']['items'][0]['images'][$i]['height'] > 350 ) {
	// 		$proper_image_element = $i;
	// 		break;
	// 	}
	// }

	// $spotify_image_url = $spotify_response['artists']['items'][0]['images'][$proper_image_element]['url'];
	// $spotiy_image_height = $spotify_response['artists']['items'][0]['images'][$proper_image_element]['height'];
	return array($spotify_web_url, $spotify_uri, $spotify_image_url, $spotify_image_height);
}

function bandcampCall($band) {
	global $config;
	$bandcamp_request_url = 'http://api.bandcamp.com/api/band/3/search?key=' . $config['bandcamp_api'] . '&name=' . urlencode($band);
	$bandcamp_response = json_decode(file_get_contents($bandcamp_request_url), true);
	$bandcamp_offsite = $bandcamp_response['results'][0]['offsite_url'];
	$bandcamp_url = $bandcamp_response['results'][0]['url'];
	return array($bandcamp_offsite, $bandcamp_url);
}

function youtubeSearch($band,$top_song) {
	global $config;
	if ($top_song == '') {
		$top_song = 'band';
	}
	$youtube_search_url = 'https://www.googleapis.com/youtube/v3/search?part=id,snippet&q=' . rawurlencode($band) . "+" . rawurlencode($top_song) . '&type=video&maxResults=1&key=' . $config['google_api'];
	$youtube_response = json_decode(file_get_contents($youtube_search_url), true);
	$youtube_id =$youtube_response['items'][0]['id']['videoId'];
	$youtube_title =$youtube_response['items'][0]['snippet']['title'];
	return array($youtube_id, $youtube_title);
}

// *******
// Now, lets loop through the bands, and gather the results.
// I know that I could, in theory, break down each return in one less row
// but I like the clarity that this gives. I get yelled at all the time at work
// for the verbose-ness of my code, but I don't care. 
// If I have to come back and read this in a month, I'll know exactly
// what is happening.
// *******

$image_url_array = array();

foreach ($band_list as $band) {	
	global $dbh;
	$last_fm_image_url = lastfmImage($band);
	$genre = lastfmTagsOne($band);
	$top_song = lastfmTopSong($band);
	$spotify_return = spotifyArtist($band);
		$spotify_web = $spotify_return[0];
		$spotify_uri = $spotify_return[1];
		$spotify_image = $spotify_return[2];
		$spotify_image_height = $spotify_return[3];
	$bandcamp_return = bandcampCall($band);
		$bandcamp_offsite = $bandcamp_return[0];
		$bandcamp_url = $bandcamp_return[1];
	$youtube_return = youtubeSearch($band, $top_song);
		$youtube_id = $youtube_return[0];
		$youtube_title = $youtube_return[1];

	// escape all the things. Just in case. I don't trust the Fest.
	$genre            = $dbh->real_escape_string($genre) ?: ' ';
	$top_song         = $dbh->real_escape_string($top_song) ?: ' ';
	$spotify_web      = $dbh->real_escape_string($spotify_web) ?: ' ';
	$spotify_uri      = $dbh->real_escape_string($spotify_uri) ?: ' ';
	$spotify_image    = $dbh->real_escape_string($spotify_image) ?: ' ';
	$spotify_image_height = $dbh->real_escape_string($spotify_image_height) ?: ' ';
	$last_fm_image_url = $dbh->real_escape_string($last_fm_image_url) ?: ' ';
	$bandcamp_offsite = $dbh->real_escape_string($bandcamp_offsite) ?: ' ';
	$bandcamp_url     = $dbh->real_escape_string($bandcamp_url) ?: ' ';
	$youtube_id       = $dbh->real_escape_string($youtube_id) ?: ' ';
	$youtube_title    = $dbh->real_escape_string($youtube_title) ?: ' ';

	$insert = $dbh->prepare("INSERT INTO `fest_info_temp` VALUES (0,?,?,?,?,?,?,?,?,?,?,?,?)");

	$insert->bind_param('ssssssssssss',$band,$genre,$top_song,$spotify_web,$spotify_uri,$spotify_image,$spotify_image_height,$last_fm_image_url,$bandcamp_offsite,$bandcamp_url,$youtube_id,$youtube_title);

// eventually I"ll make the echo go away, but I'm still debugging.
	if ($result = $insert->execute()){
		$insert->free_result();
	}
	else {
		echo "error";
	}
	// setting up for some image stuff later.
	if (!$spotify_image) {
		$image_url_array[$band] = $last_fm_image_url;
	}
	else {
		$image_url_array[$band] = $spotify_image;
	}
}

// IDE's might yell about the way I'm getting the first element of the array 2 lines down.
// It works, and this is a syntax holdover from Perl in my brain.
$fest_info_temp_table_count = mysqli_query($dbh,"SELECT COUNT(*) FROM fest_info_temp;");
$temp_count = mysqli_fetch_array($fest_info_temp_table_count)[0];

$real_table_count = mysqli_query($dbh,"SELECT COUNT(*) FROM fest_info;");
$real_count = mysqli_fetch_array($real_table_count)[0];

// if ($real_count == 0) {
// 	echo "realcount was 0";
// 	if(!$dbh->query("DROP TABLE IF EXISTS fest_info") ||
// 	!$dbh->query("RENAME TABLE fest_info_temp TO fest_info")){
// 		echo "\nDrop and rename failed\n";
// 	}
// 	else {
// 		echo "\nDropped fest_info, fest_info_temp was renamed\n";
// 	}
// }
// elseif ($temp_count > $real_count) {
// 	if (!$dbh->query(
// 		"INSERT INTO fest_info
// 			SELECT * FROM fest_info_temp AS temp
// 			WHERE NOT EXISTS 
// 			(
// 				SELECT TRUE
// 				FROM fest_info AS real
// 				WHERE temp.band = real.band
// 			);"
// 		)
// 	) {
// 		echo "\nINSERT WHERE NOT EXIST FAILED.";
// 	}
// 	else {
// 		printf("Affected rows (INSERT): %d\n", $dbh->affected_rows);
// 	}
// }

// mysqli_close($dbh);

// if ($argv[1] == 'firstrun') {
// 	foreach ($image_url_array as $band => $image) {
// 		if (!$image){
// 			continue;
// 		}
// 		$filename = strtolower(str_replace(' ', '', $band));
// 		file_put_contents("/home2/enim/public_html/fest13/php/img/{$filename}.jpg", file_get_contents($image));
// 	}
// }


// ?>
