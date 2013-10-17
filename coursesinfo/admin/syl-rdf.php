<?php


require_once('include.php');

$current_user = $_SERVER["REMOTE_USER"];

$session = preg_replace( '/[^0-9]/', '', $_GET["session"] );
$code = preg_replace( '/[^A-Z0-9]/', '', $_GET["code"] );

# throws an exception if no syllabus found
$syllabus = UoS_Syllabus::bySessionAndCode( $session, $code );

#header( "Content-type: text/turtle" );
$triples =  $syllabus->toTriples();
$ser = ARC2::getTurtleSerializer();
print $ser->getSerializedTriples($triples);

exit;


