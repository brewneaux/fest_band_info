<div class='topnav'>
	<span class='alphadropdown alphadropdown--white'><?php echo $alpha_selectors ?> </span>
	<span class='genredropdown genredropdown--white'><?php echo $genres; ?> </span>
	<span class='topBarHome topBarNav'> <a href='index.php'>Home</a></span>
	<span class='topBarSchedule topBarNav'> <a href='schedule.php'>Schedule</a></span>
	<span class='topBarConflict topBarNav'> <a href='conflict.php'>Conflict</a></span>
	<span class='authLinks'> <?php if(!$li) echo "<a class='login' href='login.php'>login</a>" ?> <?php if($li) echo "<a class='logout' href='logout.php'>logout</a>" ?> </span>
</div>