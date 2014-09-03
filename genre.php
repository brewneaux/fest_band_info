<?php
require 'includes/master.inc.php';
require 'includes/connect.php';

// This is for the infinite scroll
$page = (int) (!isset($_GET['p'])) ? 1 : $_GET['p'];

// Build some SQL to get either all of them or certain letters - see functions.inc.php
$genre = $_GET['genre'];
$sql = buildGenreQuery($genre);

// Build a string for the letter pagination. $alpha_selectors becomes a string for links. 
$alpha_selectors = letterGrouper($letter);

$db = Database::getDatabase();

//sanitize post value
$group_number = filter_var($_POST["group_no"], FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_HIGH);

# find out query stat point
$start = ($page * $limit) - $limit;

if( mysql_num_rows(mysql_query($sql)) > ($page * $limit) ){
	$next = ++$page;
}
$query = mysql_query( $sql . " LIMIT {$start}, {$limit}");

if (mysql_num_rows($query) < 1) {
	header('HTTP/1.0 404 Not Found');
	echo 'Page not found!';
	exit();
}

 if($Auth->loggedIn()) {
 	$li = 1;
 }

$festInfo = new festInfo();
$genres = $festInfo->genreBuilder();

// <?php echo $Auth->user->username; 

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
	<title>Fest 13: All yr band needs</title>
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

	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

	<!-- Favicons
	================================================== -->
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
	<link rel="apple-touch-icon" sizes="72x72" href="images/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="114x114" href="images/apple-touch-icon-114x114.png">
 	<script src="js/jquery.js"></script>
 	<script src="js/jqueryui.js"></script>
 	 <script src="js/jquery-ias.min.js"></script>
	<!-- <link rel="stylesheet" href="js/jquery-ui.css"> -->

 	
<?php 
if ($li == 1){
	echo '<script type="text/javascript" src="js/liscripts.js"></script>';
	}
else {
	echo '<script type="text/javascript" src="js/nliscripts.js"></script>';
}
?>
    <script type="text/javascript">
        $(document).ready(function() {
        	// Infinite Ajax Scroll configuration
            jQuery.ias({
                container : '.container', // main container where data goes to append
                item: '.item', // single items
                pagination: '.nav', // page navigation
                next: '.nav a', // next page selector
                loader: '<img src="css/ajax-loader.gif"/>', // loading gif
                triggerPageThreshold: 3 // show load more if scroll more than this
            });
        });
    </script>

</head>
<body>



	<!-- Primary Page Layout
	================================================== -->

	<!-- Delete everything in this .container and get started on your own site! -->
	<div class='topnav'>
		<span class='alphadropdown alphadropdown--white'><?php echo $alpha_selectors ?> </span>
		<span class='genredropdown genredropdown--white'><?php echo $genres; ?> </span>
		<span class='authLinks'> <?php if(!$li) echo "<a class='login' href='login.php'>login</a>" ?> <?php if($li) echo "<a class='logout' href='logout.php'>logout</a>" ?> </span>
	</div>


	<div class="container">
		<div class="sixteen columns">
			<div class="remove-bottom festHeader" class="" style="margin-top: 40px">Fest 13: All yr band needs</div>


		</div>
	<!-- loop row data -->
	<?php while ($row = mysql_fetch_array($query)): ?>
	<div class="sixteen column item band" class="bandclick" wrappernumber=<?php echo '"' . $wrappernumber . '"';?> style="background-image:url(<?php echo $row['pathtoimage'];?>);" id="item-<?php echo $row['id']?>">
			<span class="bandblock" id="bandblock<?php echo $row['id']?>" >
				<span class="bandname <?php echo $row['band']?>" ><?php echo $row['band']?><br /></span>
				<span class="genre <?php echo $row['band']?>"> <?php echo $row['genre']?></span>
				<button class="close" id="close<?php echo $row['id']?>" style='display:none;'onclick="closeStuff()">close</button>
			</span>	

	</div>


	<?php endwhile?>
	

		 <!-- loop row data -->

<?php if (isset($next)): ?>
	<div class="nav">
		<a href='index.php?p=<?php echo $next?>'>Next</a>
	</div>
	<?php endif?>
	<div id="pagehider"></div>
	</div><!-- container -->

    <script type="text/javascript">
    	$(document).ready(function(){
    		$("#pagehider").css("height", $(document).height()).hide();
		});
    </script>
    <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-54429804-1', 'auto');
  ga('send', 'pageview');

</script>

<!-- End Document
================================================== -->
</body>
</html>
