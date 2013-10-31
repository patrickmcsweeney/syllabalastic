<?php

$f3=require($path_to_base_dir.'lib/fatfree-master/lib/base.php');
$f3->config($path_to_base_dir.'config.ini');

#Note on first installation, you will need to fill in
#and rename passwords.ini.template to passwords.ini
$f3->config($path_to_base_dir.'passwords.ini');

$includes = array
(
        'functions.php',
        'http_routes.php',
        'lib/redbean/rb.php',
        'syllabus.php',
        'user.php',
        'lib/floraform/FloraForm.php'
);
foreach ($includes as $file)
{
#path_to_base_dir should be set by the script which does the include
        require_once($path_to_base_dir.$file);
}


$db_name = $f3->get('db_name');
$db_password = $f3->get('db_password');
$db_user = $f3->get('db_user');
$db_host = $f3->get('db_host');

R::setup("mysql:host=$db_host;dbname=$db_name",$db_user,$db_password);

$API_KEYS = array($f3->get('api_key'));
$REVIEWERS = array(  
        "pm5c08" => array("F7"),
        "bjc1f08" => array("F7"),
        "ms1r10" => array("F7"),
        "mjw7" => array("F7"),
        "nmg" => array("F7"),
        "mcf" => array("F7"),
        "asw1v08" => array("F7"),
        "fenglian" => array("F7"),
        "gbw" => array("F7"),
        "dan1" => array("F7"),
        "srg" => array("F7"),
        "gravell" => array("F7"),
        "mz1" => array("F7"),
        "ck7" => array("F7"),
        "phc1" => array("F7"),
        "chdg" => array("F7"),
        "rwe" => array("F7"),
        "dm3" => array("F2"),
        "bl2" => array("F2"),
        "cqafee" => array("F2"),
        "cqafshs" => array("F8"),
        "alexfurr" => array("F8")
);


