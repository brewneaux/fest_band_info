<?php

include('connect.php');
//include config file

if ($_GET['action'] == "wrongData") {
	$artist_id = $_POST['artist_id'];
	$element = $_POST['element'];
	$suggestion = $_POST['suggestion'];
	wrongData($dbi,$artist_id,$element,$suggestion);
}
elseif ($_GET['action'] == "setStars") {
	$user_id = $_POST['user_id'];
	$artist_id = $_POST['artist_id'];
	$rating = $_POST['rating'];
	setStars($dbi,$user_id,$artist_id,$rating);
}
elseif ($_GET["action"] == "getArtistHTML") {
	$artist_id = $_GET['artist'];
	$user_id = isset($_GET['userid']) ? $_GET['userid'] : 0;
	getArtistHTML($dbi,$artist_id,$user_id);
}
elseif ($_GET["action"] == "getConflicts") {
	$artist_id = $_GET['artist'];
	getConflicts($dbi,$artist_id);
}
elseif ($_GET["action"] == "checkArtistId") {
	$artist_id = $_GET['artist'];
	getConflicts($dbi,$artist_id);
}
elseif ($_GET["action"] == "addArtistToList") {
	$artist_id = $_POST['artistid'];
	$user_id = $_POST['userid'];
	addArtistToList($dbi,$artist_id,$user_id);
} 
else {
	header('HTTP/1.1 400 Bad Request');
}



function wrongData($dbi, $artist_id,$element,$suggestion) {
	$suggestion = $dbi->real_escape_string($suggestion);

	$query = "INSERT INTO fest_info_suggestions (artist_id, element, suggestion) VALUES ('$artist_id', '$element', '$suggestion')";

	if ($dbi->query($query)) {
		return true;
	}
	else {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}
}

function setStars($dbi, $user_id, $artist_id, $rating) {
	if(	!ctype_digit($user_id) 
		&& !ctype_digit($artist_id) 
		&& ctype_digit($rating) 
		&& $rating < 0 
		&& $rating > 100)  {

		$query = "INSERT INTO fest_artist_rating (user, artist, rating) VALUES ('$user_id', '$artist_id', '$rating')";

		if ($dbi->query($query)) {
			return true;
		}
		else {
			header('HTTP/1.1 400 Bad Request - DB');
			exit;
		}

	}
	else {
		header('HTTP/1.1 400 Bad Request - values');
		exit;
	}

}

function getConflicts($dbi, $artist_id) {
	if(!checkArtistId($dbi,$artist_id)) {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}


	$bandname_query = "SELECT i.band, date_format(t.starttime, '%l:%i %p') AS starttime, date_format(t.endtime, '%l:%i %p') AS endtime FROM fest_info_working_1 i join fest_times_temp t on i.band=t.band where id = $artist_id";

	if($bandname_res = $dbi->query($bandname_query)) {
			$band = $bandname_res->fetch_assoc();
	}
	else {
		return "didn't get band name";
	}
	

	    $potential_conflicts_q = <<<QUR2
SELECT band, location, date_format(starttime, '%l:%i %p') AS starttime
FROM fest_times_temp
WHERE 
	(band != '{$band['band']}' OR fest_info_band_id != $artist_id) AND 
    starttime BETWEEN 
             (select starttime - interval 10 minute from fest_times_temp where band = '{$band['band']}')
         AND (select endtime + INTERVAL 10 MINUTE from fest_times_temp where band = '{$band['band']}')
OR endtime BETWEEN 
             (select starttime - interval 10 minute from fest_times_temp where band = '{$band['band']}')
         AND (select endtime + INTERVAL 10 MINUTE from fest_times_temp where band = '{$band['band']}');
QUR2;

	$potential_conflicts = array();

    if($potential_conflicts_res = $dbi->query($potential_conflicts_q)) {
        if ($potential_conflicts_res->num_rows > 0){
            while ($row = $potential_conflicts_res->fetch_assoc()) {
			  $potential_conflicts[] = $row;
			}
        }
        else {
            $row = $results->fetch_assoc();
        }
    }

    $return_html = <<<EOD
    <div class="conflicts">
    <span class='conflictTitle'>These are all of the sets that conflict with
    {$band['band']} - {$band['starttime']} to {$band['endtime']} </span><br />
    We looked ten minutes on either side of their set, and here is who you will miss.<br /><br />

EOD;


    foreach ($potential_conflicts as $conflict_row) {
    	if ($conflict_row['band'] == $band['band']){
    		continue;
    	}
    	$return_html .= '<span class="conflicts_row">' . $conflict_row['band'] . ' - ' . $conflict_row['location'] . ' - ' . $conflict_row['starttime'] . '</span><br />';
    }
    $return_html .= '</div>';
    echo  $return_html;
// close func
}


function getArtistHTML($dbi, $artistname, $user_id) {
	if(!checkArtistId($dbi,$artistname)) {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}

	$query = <<<QUR1
SELECT 
	i.id,
	i.band,
	i.genre,
	i.lastfm_genre,
	i.spotify_uri,
	i.spotify_web,
	i.bandcamp_offsite,
	i.bandcamp_url,
	i.pathtoimage,
	i.youtube_id,
	i.lastfm_topsong,
	t.location,
	DATE_FORMAT(t.starttime, '%l:%i %p') AS time,
	DATE_FORMAT(t.starttime, '%W') AS day

FROM fest_info_working_1 AS i
LEFT JOIN fest_times t
	ON t.fest_info_band_id = i.id
WHERE id="{$artistname}" ORDER BY time LIMIT 1;
QUR1;

	if($results = $dbi->query($query)) {
		if ($results->num_rows > 1){
			return 'error, too many rows returned';
		}
		else {
			$row = $results->fetch_assoc();
		}
	}


	$return_html = "<div class='artistleft'><div class='artistdatatop'>
";
	
	if (!empty($row['spotify_uri'])) {
		$return_html .= <<<THH
		<span class="spotifyUri"> <a id="spotifyUriA" href="{$row['spotify_uri']}">Spotify App</a> | <a id="spotifyUriB" href="{$row['spotify_web']}"> Web</a></span>
THH;
}
	if (!empty($row['bandcamp_url'])) {
		$return_html .= <<<THJ
		<span class="bc_url"> <a id="bc_url_id" href="{$row['bandcamp_url']}">Bandcamp </a></span>
THJ;
	}
	if (!empty($row['bandcamp_offsite'])) {
		$return_html .= <<<EOD
	<span class="bc_offsite"><a id="bc_offsite_id" href="{$row['bandcamp_offsite']}">Website</a></span>
EOD;
	}
	if (!empty($row['lastfm_topsong'])) {
		$return_html .= <<<EOD
	<span class="ytTitle">Top Last.FM Track: {$row['lastfm_topsong']}</span>
EOD;
	}
	if (!empty($row['youtube_id'])) {
		$return_html .= <<<EOD
	<div class="ytEmbed" ><div class="ytEmbedOne"><iframe width="300" height="300" src="https://www.youtube.com/embed/{$row['youtube_id']}?theme=light"></iframe></div></div>
EOD;
	}

	if($row['genre'] != 'Dropped'){
	$return_html .= <<<EOD
	</div> 
	<div class='artistdatabottom'>
		<span class='artisttime'>{$row['day']} - {$row['location']} - {$row['time']} </span>
		<span class='conflictrow'><a artistid="$artistname" class='artistlink artistconflict' id="conflict{$artistname}"> Show conflicts popup</a>
EOD;
	if ($user_id > 0){
		$return_html .= "- <a class='artistlink artistadd' band='" . $artistname . "' id='artistadd" . $artistname . "'>Add to your schedule</a></span>";
	}
 
	$return_html .= "</div></div>";
}
	else {
		$return_html .= "</div>";
	}

echo $return_html;
}

function checkArtistId($dbi,$artistid) {
	if (!is_numeric($artistid)){
		echo json_encode('stop that!');
	}
	else {
		if($exists = $dbi->query("SELECT count(*) as `exists` from fest_info_working_1 where id = $artistid")){
			$existstrue = $exists->fetch_row();
			if($existstrue[0]) {
				return true;
			}
			else {
				return false;
			}
		}
		else{
			return false;
		}
	}
}

function addArtistToList($dbi,$artistid,$userid) {
	if(!checkArtistId($dbi,$userid)) {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}
	if (!ctype_digit($userid)){
		return false;
	}

	if($dbi->query("INSERT IGNORE INTO fest_user_schedule (userid, artistid, active) values ($userid, $artistid, 1)")) {
		return true;
	}
	else {
		return false;
	}

}





?>
