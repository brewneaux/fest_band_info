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

CREATE TABLE `fest_times` (
  `band` varchar(255), 
  `fest_info_band_id` int,
  `location` varchar(50),
  `starttime` datetime,
  `endtime` datetime,
  `mid_set_time` datetime,
  PRIMARY KEY (location,starttime),
  key (`fest_info_band_id`),
  key (`mid_set_time`),
  key (`starttime`),
  key (`endtime`)
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

if (!$dbh->query("DROP TABLE IF EXISTS fest_times_temp") ||
    !$dbh->query("CREATE TABLE fest_times_temp LIKE fest_times")) {
    echo "Table creation failed: (" . $dbh->errno . ") " . $dbh->error;
}

echo "db connected, table created \n";
// *******
// Go get the data from the API
// *******


$kimono_days = array(
	'friday' => "https://www.kimonolabs.com/api/csv/ciaevx5w?apikey=rtynIstUEMiMt7h4CHeor2xgG76WoC64",
	'saturday' => "https://www.kimonolabs.com/api/csv/60kq7jru?apikey=rtynIstUEMiMt7h4CHeor2xgG76WoC64",
	'sunday' => "https://www.kimonolabs.com/api/csv/7uj3kqo6?apikey=rtynIstUEMiMt7h4CHeor2xgG76WoC64"
);
foreach ($kimono_days as $day => $link) {

	$attempts = 4;
	do { 
		$attempts--;
		
		print_r($link);
		echo "\n";
		error_reporting(E_ALL);

		$kimono_band_list = file_get_contents($link);

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

	echo "got band list $day \n";
	$kimono_band_list = str_replace("é", "e",$kimono_band_list);
	$kimono_band_list = str_replace("’","'",$kimono_band_list);
	$kimono_band_list = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $kimono_band_list);

	$band_list = explode("\r\n", $kimono_band_list);
	array_splice($band_list, 0, 2);
	$band_list_length = count($band_list);
	array_splice($band_list, -2, $band_list_length);

	foreach ($band_list as &$band) {
		$mystery_acoustic_set_counter = 1;
		$band_array = explode(',', $band);

		if ($band_array[1] != 'DOORS') {
			if ($day == 'friday'){
				$settime = explode(' - ', $band_array[0]);
				$settime[0] = date("Y-m-d H:i:s", strtotime('2014-10-31 ' . $settime[0]));
				$settime[1] = date("Y-m-d H:i:s", strtotime('2014-10-31 ' . $settime[1]));

			}
			elseif ($day == 'saturday'){
				$settime = explode(' - ', $band_array[0]);
				$settime[0] = date("Y-m-d H:i:s", strtotime('2014-11-01 ' . $settime[0]));
				$settime[1] = date("Y-m-d H:i:s", strtotime('2014-11-01 ' . $settime[1]));
			}
			elseif ($day == 'sunday'){
				$settime = explode(' - ', $band_array[0]);
				$settime[0] = date("Y-m-d H:i:s", strtotime('2014-11-02 ' . $settime[0]));
				$settime[1] = date("Y-m-d H:i:s", strtotime('2014-11-02 ' . $settime[1]));
			}

		}
		elseif ($band_array[1] == "Mystery Acoustic Set") {
			echo 'yes';
			$band_array[1] = $band_array[1] . '-' . $mystery_acoustic_set_counter;
			$mystery_acoustic_set_counter++;
			echo $band_array[1] . "\n";
		}
		else {
			$band_array[1] = $band_array[1] . '-' . $band_array[2];
		}

		 

		$band = '"' . $band_array[1] . '","' . $band_array[2]  . '","' .  $settime[0] . '","' .  $settime[1] . '"';
		$insert = <<<EOQ
INSERT INTO fest_times_temp (band,location,starttime,endtime) 
VALUES ("$band_array[1]", "$band_array[2]", "$settime[0]", "$settime[1]")
EOQ;

		if ($dbh->query($insert) === TRUE) {
		    // echo "successfully loaded set time $band_array[1]\n";
		}
		else {
			echo "fail. $dbh->error\n";
		}
	}
}

echo "Finished loading bands \n";

if (!$dbh->query("DROP TABLE IF EXISTS fest_doors_temp") ||
    !$dbh->query("CREATE TABLE fest_doors_temp LIKE fest_doors") ||
    !$dbh->query("INSERT INTO fest_doors_temp SELECT band as location, starttime AS opens FROM fest_times_temp WHERE band LIKE 'door%'")) {
    echo "Couldn't Load doors table: (" . $dbh->errno . ") " . $dbh->error . "\n";
}
else {
	printf("Affected rows (doors): %d\n", $dbh->affected_rows);
}

$dbh->query("DELETE FROM fest_times_temp WHERE band like 'doors-%'");
	printf("Affected rows (delete): %d\n", $dbh->affected_rows);

if(!$dbh->query("ALTER TABLE fest_times_temp ADD PRIMARY KEY (band,location,starttime)")) {
	echo "failed to create primary key\n\t$dbh->error\n\n";
}

if(!$dbh->query("UPDATE fest_times_temp t JOIN fest_info_working_1 w on t.band = w.band set t.fest_info_band_id = w.id")) {
	echo "couldn't update id's \n\t$dbh->error\n\n";
}
else {
	echo "udpated $dbh->affected_rows band ids\n";
}

if(!$dbh->query("UPDATE fest_times_temp set starttime = starttime + INTERVAL 1 DAY WHERE hour(starttime) < 11") ||
   !$dbh->query("UPDATE fest_times_temp set endtime = endtime + INTERVAL 1 DAY WHERE hour(endtime) < 11") ) {
	echo "couldn't update id's \n\t$dbh->error\n\n";
}
else {
	echo "udpated $dbh->affected_rows band ids\n";
}

$update_missing_ids = <<<EOD
update fest_times t
join fest_info_working_1 w
on left(t.band, 5) = left(w.band, 5)
set fest_info_band_id = w.id
where fest_info_band_id is null;
EOD;

if($dbh->query($update_missing_ids)) {
	echo "udpated the rest of the ids - $dbh->affected_rows\n";
}


// string(19) "12:20 PM - 12:27 PM"

