<?php

$f3=require('lib/fatfree-master/lib/base.php');
$f3->config('config.ini');

#Note on first installation, you will need to fill in
#and rename passwords.ini.template to passwords.ini
$f3->config('passwords.ini');

$includes = array
(
	'includes.php',
	'functions.php',
	'http_routes.php',
	'lib/redbean/rb.php',
	'syllabus.php',
	'lib/floraform/FloraForm.php'
);
foreach ($includes as $file)
{
	require_once(__DIR__.'/'.$file);
}

$f3->set('getConstant',function($syllabus, $key){
	return $syllabus->getConstant("$key");
});

$db_name = $f3->get('db_name');
$db_password = $f3->get('db_password');
$db_user = $f3->get('db_user');
$db_host = $f3->get('db_host');

R::setup("mysql:host=$db_host;dbname=$db_name",$db_user,$db_password);

$f3->run();

?>

