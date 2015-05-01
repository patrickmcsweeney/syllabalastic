<?php
$path_to_base_dir = "";
require_once("includes.php");

if (!$f3->exists('SESSION.authenticated'))
{
	$f3->set('SESSION.authenticated', false);
}

$f3->run();

?>

