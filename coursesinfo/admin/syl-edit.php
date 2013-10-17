<?php

require_once('include.php');

$flags = currentUserFlags();

if( $_SERVER["REQUEST_METHOD"] == "POST" )
{
	$id = $_POST["syllabus_id"];
	$syllabus = new UoS_Syllabus( $id );
	if( !isset( $syllabus ) ) { exit_error( "Syllabus not found" ); }

	$syllabus->fromForm($flags);
	$issues = $syllabus->issues();
	if( sizeof($errors ))
	{
		renderHeader("Course Info Editor");
		print "<div class='issues'>Some issues exist:<ul><li>".join( "</li><li>", $issues )."</div>";
		print $syllabus->renderForm($flags);
		renderFooter();
		exit;
	}	
	# OK, let's save it then.
	$errors = $syllabus->save();
	if( $errors ) 
	{
		renderHeader("Course Info Editor");
		print "Something bad happened!<ul><li>".join( "</li><li>", $errors )."</li></ul>";
		print $syllabus->renderForm($flags);
		renderFooter();
	}
	else
	{
		header( "Location: /coursesinfo/syl-view.php?id=$id&single=1" );
	}

	#print dumper( $syllabus );

}
else
{
	if( isset( $_GET["id"] ) )
	{
		$id = preg_replace( '/[^0-9]/', '', $_GET["id"] )+0;
		$syllabus = new UoS_Syllabus( $id );
	}
	else
	{
		$session = preg_replace( '/[^0-9]/', '', $_GET["session"] );
		$code = preg_replace( '/[^A-Z0-9]/', '', $_GET["code"] );
	
		# throws an exception if no syllabus found
		$syllabus = UoS_Syllabus::bySessionAndCode( $session, $code );
	}
	#if( !isset( $syllabus ) ) { exit_error( "Syllabus not found" ); }

	renderHeader("Course Info Editor");
	print $syllabus->renderForm($flags);
	renderFooter();
}

function exit_error( $message )
{
	print "<h2>$message</h2>";
	exit;
}

exit;

