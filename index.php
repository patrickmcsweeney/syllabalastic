<?php
$path_to_base_dir = "";
require_once("includes.php");

$f3->set('getConstant',function($syllabus, $key){
	return $syllabus->getConstant("$key");
});

if (!$f3->exists('SESSION.authenticated'))
{
	$f3->set('SESSION.authenticated', false);
}

$f3->run();

?>

