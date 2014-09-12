<?PHP
    require 'includes/master.inc.php';

    if($Auth->loggedIn()) redirect('simple/..');

    if(!empty($_POST['username']))
    {
        if($Auth->isValid($_POST['username']) &&  $Auth->isValid($_POST['password'])){
            if($Auth->login($_POST['username'], $_POST['password']))
            {
                if(isset($_REQUEST['r']) && strlen($_REQUEST['r']) > 0)
                    redirect($_REQUEST['r']);
                else
                    redirect(WEB_ROOT);
            }
            else
                $Error->add('username', "We're sorry, you have entered an incorrect username and password. Please try again.");
        }
        else {
            $Error->add('badchar', "You can only use alphanumeric characters, along with ., #, \, -, _, $");
        }
    }


    if($Auth->isValid($_POST['regusername']) && $_POST['regpassword'] == $_POST['confpassword'] && $Auth->isValid($_POST['regpassword'])){
        if(!empty($_POST['regusername']))
            {
                if($Auth->createNewUser($_POST['regusername'], $_POST['regpassword'])) {
                    $Auth->login($_POST['regusername'], $_POST['regpassword']);
                    redirect(WEB_ROOT);
                }
                else {
                    $Error->add('nocreated', "Login not created - that username may already be in use.");
                }
            }
    }
    elseif ( $_POST['regpassword'] != $_POST['confpassword']) {
         $Error->add('passnomatch', "The passwords you entered didn't match. Try again, pls.");
     } 
    else {
            $Error->add('badchar', "There was an unspecified error. Please keep in mind you can only use alphanumeric characters, along with ., #, \, -, _, $");
        }


    // Clean the submitted username before redisplaying it.
    $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fest13 Info: Login Page</title>


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

    <script src="js/jquery.js"></script>
    <script src="js/jqueryui.js"></script>
    <script src="js/jquery-ias.min.js"></script>
    <!-- <link rel="stylesheet" href="js/jquery-ui.css"> -->

</head>
<body>
<div class='container'>     <div class="sixteen columns">

    <div class="remove-bottom festHeader" class="" style="margin-top: 40px">Fest 13: Totally unofficial since 1987!</div>
</div>
<!-- <div class='three columns'> &nbsp; </div> -->
<div class='loginContainer 10 columns offset-by-three'>
    <div class='loginError'>
        <?PHP echo $Error; ?>
    </div>
    <div class='returningLogin four columns'>
        <span id='loginTitle'> Log In </span> <br />
        <span id='loginDescription'>  </span>    
        <form action="<?PHP echo $_SERVER['PHP_SELF']; ?>" method="post">
            <p><label for="username">Username:</label> <input type="text" name="username" value="<?PHP echo $username;?>" id="username" /></p>
            <p><label for="password">Password:</label> <input type="password" name="password" value="" id="password" /></p>
            <p><input type="submit" name="btnlogin" value="Login" id="btnlogin" /></p>
        </form>
    </div>

    <div class='createUser four columns offset-by-one'>
        <span id='createTitle'> CREATE USER </span> <br />
        <span id='createDescription'>  </span>
        <form action="<?PHP echo $_SERVER['PHP_SELF']; ?>" method="post">            
            <p><label for="regusername">Username:</label> <input type="text" name="regusername" value="<?PHP echo $regusername;?>" id="regusername" /></p>
            <p><label for="regpassword">Password:</label> <input type="password" name="regpassword" value="" id="regpassword" /></p>
            <p><label for="confpassword">Confirm Password:</label> <input type="password" name="confpassword" value="" id="confpassword" /></p>
            <p><input type="submit" name="btnlogin" value="Login" id="btnlogin" /></p>
            <input type="hidden" name="r" value="<?PHP echo htmlspecialchars(@$_REQUEST['r']); ?>" id="r">
        </form>
    </div>

</div>
</div> 
</body>
</html>