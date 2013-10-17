<?php

require_once('include.php');

$current_user = $_SERVER["REMOTE_USER"];

$code = @$_GET["code"];
$session = @$_GET["session"];

if( isset( $code ) && isset( $session ) )
{
	# better just check it doesn't already exist
	$syllabus = UoS_Syllabus::bySessionAndCode( $session, $code, true );
	if( isset( $syllabus ) ) { die( "Syllabus already exists" ); }
}

$syllabus_id = UoS_Syllabus::create( $current_user, $session, $code );

$edit_url = "https://secure.ecs.soton.ac.uk/coursesinfo/admin/syl-edit.php?id=$syllabus_id";

header( "Location: $edit_url" );
exit;
