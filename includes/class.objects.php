<?PHP
    // Stick your DBOjbect subclasses in here (to help keep things tidy).

    class User extends DBObject
    {
        public function __construct($id = null)
        {
            parent::__construct('users', array('nid', 'username', 'password', 'level', 'ip', 'cdate'), $id);
        }

        public function checkUserId($user_id) {
            $db = Database::getDatabase();

            if (!is_numeric($user_id)){
                echo json_encode('stop that!');
            }
            else {
                $user_result = $db->query("SELECT 1 FROM users WHERE userid = {$user_id}");
                $user_exists = $db->getValue($user_result);
                
                if($user_exists) {
                    return true;
                }
                else {
                    return false;
                }
            }
        }

        public function getScheduleQuery($user_id) {
            if(!checkUserId($user_id)){
                return false;
            }
            else {
                $query = <<<EOQ
                SELECT 
                    s.artistid, 
                    i.band, 
                    i.genre,
                    i.spotify_web,
                    i.spotify_uri,
                    t.location,
                    t.starttime,
                    t.endtime
                FROM
                    fest_user_schedule AS s
                    JOIN fest_info_working_1 AS i
                        ON i.id = s.artistid
                    JOIN fest_times AS t
                        ON t.fest_info_band_id = s.artistid
                WHERE
                    s.userid = {$user_id}
                ORDER BY t.starttime
EOQ;
                return $query;
            }
        }

    }

    class festInfo extends DBObject
    {

        public function __construct()
        {
            parent::__construct('fest_info_working_1', array("id","band","genre","lastfm_genre","lastfm_topsong","spotify_web","spotify_uri","spotify_image","height","bandcamp_offsite","bandcamp_url","youtube_id","youtube_title","pathtoimag"), $id);

        }

        public function genreArray()
        {
            $genre_query = 'SELECT DISTINCT genre FROM fest_info_working_1';

            $db = Database::getDatabase();

            $genre_result = $db->query($genre_query);
            $genre_result_array = $db->getValues($genrere_result);
            return $genre_result_array;
        }

        public function genreBuilder()
        {
            $genre_result_array = self::genreArray();
            $genre_dropdown = '<select id="genredropdown" class="genredropdown__select genredropdown__select--white genredropdown" name="URL" onchange="genredropdownGo()">';
            $genre_dropdown .= '<option>Sort by Genre</option>';
            $genre_dropdown .= '<option value=index.php>ALL</option>';
            foreach ($genre_result_array as $genre) {
                $genre_dropdown .= '<option value=genre.php?genre=' . $genre . '>' . $genre . '</option>';
            }
            $genre_dropdown .= '</select>';
            return $genre_dropdown;
        }

        




    }


