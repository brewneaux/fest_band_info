<?PHP
    // Stick your DBOjbect subclasses in here (to help keep things tidy).

    class Fest extends DBObject
    {

    	public function getArtist() {

			$artistname = $_GET['artist'];

			$query = "SELECT id,band,lastfm_genre,spotify_uri,spotify_web,bandcamp_offsite,pathtoimage,youtube_id,lastfm_topsong FROM fest_info_working_1 WHERE id=" . $artistname;


			if($results = $dbi->query($query)) {
				if ($results0->num_rows > 1){
					return 'error, too many rows returned';
				}
				else {
					$row = $results->fetch_assoc();
					// header('Content-Type: application/json');
					echo json_encode($row);
				}
			}
    	}



    }

    		if($_GET["action"] == "getArtist")
		  getArtist();