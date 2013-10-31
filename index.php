<?php
$path_to_base_dir = "";
require_once("includes.php");

$f3->set('getConstant',function($syllabus, $key){
	return $syllabus->getConstant("$key");
});

$f3->run();

?>

