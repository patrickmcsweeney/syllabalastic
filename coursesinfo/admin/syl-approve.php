<?php

require_once('include.php');

$current_user = currentUser();

if( !currentUserFlag( "is_admin" ) )
{
	print "<p>Only Admin may edit.</p>";
	exit;
}

$id = preg_replace( '/[^0-9]/', '', $_GET["id"] )+0;
$syllabus = new UoS_Syllabus( $id );
if( !isset( $syllabus ) ) { exitError( "Syllabus not found" ); }
$errors = $syllabus->approve();
if( $errors ) 
{
	renderHeader("Course Info Editor");
	print "Something bad happened!<ul><li>".join( "</li><li>", $errors )."</li></ul>";
	renderFooter();
}
else
{
	header( "Location: /coursesinfo/admin/" );
}

exit;

