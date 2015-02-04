<!doctype html>
<html>
<head>
    <!-- META -->
    <meta charset="utf-8">
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo URL; ?>css/style.css" />
</head>
<body>
    <!-- wrapper, to center website -->
    <div class="wrapper">

        <!-- logo -->
        <div class="logo">GeoLag</div>

        <!-- navigation -->
        <ul class="navigation">
            <li <?php if ($this->checkForActiveController($filename, "home")) { echo ' class="active" '; } ?> >
                <a href="<?php echo URL; ?>home/index">Index</a>
            </li>


            <?php if (Session::get('user_logged_in') == true) { ?>
                <!-- for logged in users -->
                <li <?php if ($this->checkForActiveController($filename, "dashboard")) { echo ' class="active" '; } ?> >
                    <a href="<?php echo URL; ?>dashboard/index">Dashboard</a>
                </li>   
                <li <?php if ($this->checkForActiveController($filename, "dashboard")) { echo ' class="active" '; } ?> >
                    <a href="<?php echo URL; ?>photos/index">Upload</a>
                </li>
                <li <?php if ($this->checkForActiveController($filename, "dashboard")) { echo ' class="active" '; } ?> >
                    <a href="<?php echo URL; ?>game/">Geolag</a>
                </li>  
                <li <?php if ($this->checkForActiveController($filename, "dashboard")) { echo ' class="active" '; } ?> >
                    <a href="<?php echo URL; ?>songs">songs</a>
                </li> 
                <li <?php if ($this->checkForActiveController($filename, "dashboard")) { echo ' class="active" '; } ?> >
                    <a href="<?php echo URL; ?>blogs">Hamburger</a>
                </li> 
                
        

            <?php } else { ?>
                <!-- for not logged in users -->
                <li <?php if ($this->checkForActiveControllerAndAction($filename, "login/index")) { echo ' class="active" '; } ?> >
                    <a href="<?php echo URL; ?>login/index">Login</a>
                </li>
                <li <?php if ($this->checkForActiveControllerAndAction($filename, "login/register")) { echo ' class="active" '; } ?> >
                    <a href="<?php echo URL; ?>login/register">Register</a>
                </li>
            <?php } ?>
        </ul>

        <!-- my account -->
        <ul class="navigation right">
        <?php if (Session::get('user_logged_in') == true) : ?>
            <li <?php if ($this->checkForActiveController($filename, "login")) { echo ' class="active" '; } ?> >
                <a href="<?php echo URL; ?>login/showprofile">My Account</a>
                <ul class="navigation-submenu">
                    <li <?php if ($this->checkForActiveController($filename, "login")) { echo ' class="active" '; } ?> >
                        <a href="<?php echo URL; ?>login/editusername">Edit my username</a>
                    </li>
                    <li <?php if ($this->checkForActiveController($filename, "login")) { echo ' class="active" '; } ?> >
                        <a href="<?php echo URL; ?>login/edituseremail">Edit my email</a>
                    </li>
                    <li <?php if ($this->checkForActiveController($filename, "login")) { echo ' class="active" '; } ?> >
                        <a href="<?php echo URL; ?>login/logout">Logout</a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
        </ul>