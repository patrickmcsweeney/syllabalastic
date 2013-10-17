<?php

require_once('ECS/new-syllabus.php');
require_once('FloraForm/lib/FloraForm.php');

ECS_OpenCoreDB();




function currentUser()
{
	global $_SERVER;
	return $_SERVER["REMOTE_USER"];
}

function currentUserFlags()
{
	$current_user = currentUser();

	$flags = array( 
		"is_director" => false,
		"is_review" => false,
		"is_admin" => false,
		"is_library" => false,
	);

	if( $current_user == "amg" )
	{
		$flags["is_director"] = true;
		$flags["is_admin"] = true;
		$flags["is_review"] = true;
	}

	if( $current_user == "cjg" )
	{
		$flags["is_director"] = true;
		$flags["is_admin"] = true;
		$flags["is_review"] = true;
	}
	# for now anyone can set the library stuff
	$flags["is_library"] = true;

	return $flags;
}

function currentUserFlag( $flag )
{
	$flags = currentUserFlags();

	return $flags[$flag];
}

function exitError( $message )
{
	print "<h2>$message</h2>";
	exit;
}







function renderHeader($title)
{
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="FloraForm/resources/ff.css"></link>
<title><?php print $title ?></title>
<style>
body { 
	margin-left: 5em;
	margin-right: 5em;
	margin-bottom: 5em;
	font-family: sans-serif;
}
#textbooks_changed_container {
	margin-top: 1em;
}
#assessment_list .ff_radio_option_cwork,
#assessment_list .ff_radio_option_labs,
#assessment_list .ff_radio_option_other {
	float: right;
}

#regular_teaching_0_group_size_container,
#regular_teaching_1_group_size_container,
#regular_teaching_2_group_size_container,
#regular_teaching_3_group_size_container,
#regular_teaching_4_group_size_container,
#regular_teaching_5_group_size_container,
#regular_teaching_6_group_size_container,
#regular_teaching_7_group_size_container,
#regular_teaching_8_group_size_container,
#regular_teaching_9_group_size_container,
#regular_teaching_10_group_size_container
{
	display: block;
	text-align:right;
}
</style>

  <script type="text/javascript" src='FloraForm/resources/ff.js' ></script>
  <script type="text/javascript" src='/js/jquery-ui/js/jquery-1.7.2.min.js'></script>
  <script type="text/javascript" src='/js/jquery-ui/js/jquery-ui-1.8.20.custom.min.js'></script>
  <script language='javascript' type='text/javascript' src='/tinymce/tiny_mce/tiny_mce.js'></script>
<script>
ff_init();
</script>
</head>
<body>
<?
}

function renderFooter()
{
?>
</body>
</html>
<?php
}

