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
echo "reading config\n";
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
echo "config read. conneting to db \n";


// *******
// Connect to your DB
// *******

$dbh = mysqli_connect("localhost",$config['db_user'],$config['db_pass'],$config['db_name']);

if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

if (!$dbh->query("DROP TABLE IF EXISTS fest_info_temp") ||
    !$dbh->query("CREATE TABLE fest_info_temp LIKE fest_info_working_1")) {
    echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
}

echo "db connected, table created \n";
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

echo "got band list \n";
$band_list = str_replace("é", "e",$band_list);
$band_list = str_replace("’","'",$band_list);
$kimono_band_list = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $kimono_band_list);

// *******
// If debug is on, lets just do a subset.
// *******
	

	$band_list = explode("\r\n", $kimono_band_list);
	array_splice($band_list, 0, 2);
	array_pop($band_list);
	$band_list_length = count($band_list);
	// $band_list = array_slice($band_list, 0, 25);

// Now, we insert just the bands into the empty table, so we can see which we need to go get, or deactivate.
echo $band_list_length;
$query_string_1 = '';
foreach ($band_list as $band) {
	$band = $dbh->real_escape_string($band);
	$query_string_1 .= "(\"$band\"),";
}
$query_string_1 = substr($query_string_1, 0, -1);

// $band_list_comma = $dbh->real_escape_string($band_list_comma);
$insert_all_query = "INSERT INTO fest_info_temp (band) VALUES $query_string_1";

if ($dbh->query($insert_all_query) === TRUE) {
    printf("Table filled\n");
}
else {
	echo 'fail';
	 printf("Errormessage: %s\n", $dbh->error);
}

$update_deactive_query = "UPDATE fest_info_working_1 a LEFT JOIN fest_info_temp b ON a.band = b.band SET a.genre = 'Dropped' where b.band is null";

if ($dbh->query($update_deactive_query)) {
	echo "Dropped $dbh->affected_rows bands\n";
}
else {
	echo "Failed - $dbh->error";
}


$band_list_query = "SELECT band FROM fest_info_temp as t WHERE NOT EXISTS (SELECT TRUE FROM fest_info_working_1 as w where t.band = w.band)";

$band_list_to_add = array();
if ($band_list_result = $dbh->query($band_list_query)) {
	/* fetch associative array */
	while ($band_result = mysqli_fetch_row($band_list_result)) {
	    array_push($band_list_to_add, $band_result[0]);
	}
	/* free result set */
	mysqli_free_result($band_list_result);
}
print_r($band_list_to_add);

// *******
// If you can't figure out what these are from the name, stop now.
// *******

function DEPlastfmTagsOne($band) {
	global $config;
	$last_fm_genre_url = 'http://ws.audioscrobbler.com/2.0/?method=artist.getTopTags&artist=' . urlencode($band) .'&user=vonwhoopass&api_key=' . $config['lastfm_api'] . '&format=json';
	$lastfmtags_response = json_decode(file_get_contents($last_fm_genre_url), true);
	$genre = $lastfmtags_response['toptags']['tag'][0]['name'];
	return $genre;
}

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
		
		$chosen_genre = chooseGenre($possible_tags);
		return $chosen_genre;
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
	for ($i = count($spotify_response['artists']['items'][0]['images']); $i >= 0; $i--){
		if ($spotify_response['artists']['items'][0]['images'][$i]['height'] > 350 ) {
			$proper_image_element = $i;
			break;
		}
	}

	$spotify_image_url = $spotify_response['artists']['items'][0]['images'][$proper_image_element]['url'];
	$spotiy_image_height = $spotify_response['artists']['items'][0]['images'][$proper_image_element]['height'];
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

foreach ($band_list_to_add as $band) {	
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
	$path_to_image 	  = str_replace(' ', '', $dbh->real_escape_string($band));

	$insert = $dbh->prepare("INSERT INTO `fest_info_temp` VALUES (0,?,?,?,?,?,?,?,?,?,?,?,?,?)");
	$dummy = '';
	$insert->bind_param('sssssssssssss',$band,$genre,$dummy,$top_song,$spotify_web,$spotify_uri,$spotify_image,$spotify_image_height,$bandcamp_offsite,$bandcamp_url,$youtube_id,$youtube_title, $path_to_image);

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
	echo "inserted $band \n";
}

echo "Done inserting\n";

// Add the bands from the temp table to the real table

$update_info_table_query = <<<EOQ
INSERT INTO fest_info_working_1 
(
	band,
	genre,
	lastfm_genre,
	lastfm_topsong,
	spotify_web,
	spotify_uri,
	spotify_image,
	height,
	bandcamp_offsite,
	bandcamp_url,
	youtube_id,
	youtube_title,
	pathtoimage
)
SELECT
	band,
	genre,
	lastfm_genre,
	lastfm_topsong,
	spotify_web,
	spotify_uri,
	spotify_image,
	height,
	bandcamp_offsite,
	bandcamp_url,
	youtube_id,
	youtube_title,
	pathtoimage
FROM fest_info_temp
WHERE pathtoimage IS NOT NULL 
AND (band IS NOT NULL OR band != '')
EOQ;

if ($dbh->query($update_info_table_query) === TRUE) {
    echo "successfully loaded new bands\n";
}
else {
	echo "fail. $dbh->error";
}

if ($image_url_array) {
	foreach ($image_url_array as $band => $image) {
		if (!$image){
			continue;
		}
		$filename = strtolower(str_replace(' ', '', $band));
		file_put_contents("../img/{$filename}.jpg", file_get_contents($image));

	}
}

// Had trouble getting this to work, so I do it by hand for now.

// $img = new Imagick("../img/*.jpg");
// foreach($images as $image) {

// 	$img->modulateImage(100, 0, 100);
// 	$img->ThumbnailImage(300,180);
// }
// $img->writeImage();

// echo "finished images\n";




?>
