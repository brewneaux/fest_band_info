<?php
require 'includes/master.inc.php';
require 'includes/connect.php';
require_once 'includes/Mobile_Detect.php';

$db = Database::getDatabase();

try {
	$config_file = 'includes/config.ini';
	$config = parse_ini_file($config_file, true);
	if ($config === false) {
		throw new Exception( "died");
	}
}
catch (Exception $e) {
	echo $e;
	exit;
}

$mobile_detector = new Mobile_Detect;
// $is_mobile = $mobile_detector->is_mobile() || $mobile_detector->isTablet();
if ($mobile_detector->isMobile() || $mobile_detector->isTablet()) {
	$is_mobile = 1;
}

if($Auth->loggedIn()) {
 	$userid = $Auth->id;
 	$li = 1;
 }
 else {
 	redirect(WEB_ROOT . 'login.php');
 }

$query = <<<EOQ
                SELECT 
                    s.artistid, 
                    i.band, 
                    i.genre,
                    i.spotify_web,
                    i.spotify_uri,
                    t.location,
                    date_format(t.starttime, '%a %l:%i') AS starttime,
                    date_format(t.endtime, '%l:%i') AS endtime
                FROM
                    fest_user_schedule AS s
                    JOIN fest_info_working_1 AS i
                        ON i.id = s.artistid
                    JOIN fest_times AS t
                        ON t.fest_info_band_id = s.artistid
                WHERE
EOQ;

if(!isset($_GET['day'])) {
           $query .= " s.userid = {$userid} ORDER BY t.starttime";
}
elseif (strtolower($_GET['day']) == 'friday') {
	$query .= " s.userid = {$userid} and date(t.starttime) = '2014-10-31' ORDER BY t.starttime";
}
elseif (strtolower($_GET['day']) == 'saturday') {
	$query .= " s.userid = {$userid} and date(t.starttime) = '2014-11-01' ORDER BY t.starttime";
}
elseif (strtolower($_GET['day']) == 'sunday') {
	$query .= " s.userid = {$userid} and date(t.starttime) = '2014-11-02' ORDER BY t.starttime";
}

$schedule_result = $db->query($query);
$schedule_rows = $db->getRows($schedule_result);


$fest_info = new festInfo();
$genres = $fest_info->genreBuilder();
$alpha_selectors = letterGrouper($letter);


?>


<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<head>

	<!-- Basic Page Needs
  ================================================== -->
	<meta charset="utf-8">
	<title>Fest 13: Check Yr Schedule</title>
	<meta name="description" content="">
	<meta name="author" content="">

	<!-- Mobile Specific Metas
  ================================================== -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<!-- CSS
  ================================================== -->
	<link rel="stylesheet" href="stylesheets/base.css">
	<link rel="stylesheet" href="stylesheets/skeleton.css">
	<link rel="stylesheet" href="stylesheets/layout.css">
	<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">

	
 	<script src="js/jquery.js"></script>
 	<script src="js/jqueryui.js"></script>
 	<script src="js/jquery-ias.min.js"></script>
	<!-- <link rel="stylesheet" href="js/jquery-ui.css"> -->

<!-- Different JS for different types of devices - so we can do starring, datachecking, etc -->
<?php 
if($is_mobile) {
	if ($li == 1){
		echo '<script type="text/javascript" src="js/mobile_liscripts.js"></script>';
		}
	else {
		echo '<script type="text/javascript" src="js/mobile_nliscripts.js"></script>';
	}
}
else {
	if ($li == 1){
		echo '<script type="text/javascript" src="js/liscripts.js"></script>';
		}
	else {
		echo '<script type="text/javascript" src="js/nliscripts.js"></script>';
	}
}
?>

<body userid='<?php if($userid){echo $userid;} ?>'>

<?php require 'includes/topnav.php'; ?>

	<div class="container">
		<div class="sixteen columns">
			<div class="remove-bottom festHeader" class="" >Fest 13: Check yr Schedule </div>
			<div class="remove-bottom daySelect"> <a href='schedule.php'>All</a>  <a href='schedule.php?day=friday'>Friday</a>  <a href='schedule.php?day=saturday'>Saturday</a>  <a href='schedule.php?day=Sunday'>Sunday</a></div>

		</div>

<?php foreach ($schedule_rows as $row) { ?>
<div class='row schedulerow'>
	<div class='three columns scheduletime'>
		<span class='bandtime'><?php echo $row['starttime'] . ' - ' . $row['endtime']; ?></span>
	</div>
	
	<div class="six columns ">
		<span class='scheduleband'><?php echo $row['band'] . "<br />"; ?></span>
		<span class='schedulelocation'>
			<?php echo $row['location'] . '<br />' ; ?>
		</span>
	</div>
</div>
 


<?php } ?>
</div>
</body>
</html>
