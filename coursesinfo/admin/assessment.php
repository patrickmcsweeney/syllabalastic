<?php
error_reporting(E_ALL );
ini_set('display_errors',1 );


require_once('include.php');
require_once "ECS/utils.php";
require_once "ECS/courses.php";

renderHeader("Courses info");

$flags = currentUserFlags();
	
$session = "1213";	
if( @$_GET["session"] ) { $session= preg_replace( '/[^0-9]/','',$_GET["session"] ); }

$session = COURSES_GetSession();
$prev = COURSES_getSessionBefore($session);
$next = COURSES_getSessionAfter($session);
print "<p>View: ";
print "<a href='?session=$prev'>".COURSES_SessionName( $prev )."</a> | ";
print "<strong>".COURSES_SessionName( $session )."</strong> | ";
print "<a href='?session=$next'>".COURSES_SessionName( $next )."</a>";
print "</p>";

print "<p>List of all assessment in the ".COURSES_SessionName( $session )." session.</p>";

$session += 0; # double check it's just a number.

$modules = UoS_Syllabus::getSessionCourseinfo($session,true);
ksort( $modules );
$moduleinfo = json_decode(  join( '',file("/var/ecsweb/2012data/moduleinfo.json")), true );

$TABLE_COLS = 8;
$sections = array();
print "<table border='1' cellpadding='1'>";
$names = array( 
	"exam" => "Exam",
	"other" => "Other",
	"labs" => "Labs",
	"cwork" => "Coursework",
	);
print "<tr>";
print "<th>Code</th>";
print "<th>Title</th>";
print "<th>Part of Term</th>";
foreach( $names as $key=>$label ) { print "<th>$label Count</th>"; }
foreach( $names as $key=>$label ) { print "<th>$label Percent</th>"; }
print "</tr>";
foreach( $modules as $course_code => $yos_list )
{
	ksort( $yos_list );
	foreach( $yos_list as $yos => $module_list )
	{
		foreach( $module_list as $module )
		{
			if( ! @$module["active"] ) { continue; } # only show active
			print "<tr>";
			@print  "<th>".htmlspecialchars($module["code"])."</th> ";
			@print  "<td>".htmlspecialchars($module["title"])."</td>"; 
			@print  "<td>".htmlspecialchars($moduleinfo[$module["code"]]["part_of_term"])."</td> ";
			if( ! isset( $module["syllabus_id"] ) )
			{
				print "<td colspan='$TABLE_COLS'>Information not in system yet</td></tr>";	
				continue;
			}
			if( $module["provisional"] )
			{
				print "<td colspan='$TABLE_COLS'>Information not yet confirmed</td></tr>";	
				continue;
			}
			$syllabus = new UoS_Syllabus( $module["syllabus_id"] );
			$graph = new Graphite();
			$graph->ns( "soton", "http://id.southampton.ac.uk/ns/" );
			$graph->ns( "aiiso", "http://purl.org/vocab/aiiso/schema#" );
			$graph->addTriples( $syllabus->toTriples() );
			$counts = array();
			$percent = array();
			$syl = $graph->resource( $syllabus->URI() );
         		foreach( $syl->all( "soton:fpasModuleAssessment" ) as $assessment )
         		{
				$type_uri = $assessment->getString( "soton:fpasModuleAssessmentType" );
				$type = substr( $type_uri, 56 );
				@$counts[$type]++;
				@$percent[$type]+=$assessment->getString( "soton:fpasModuleAssessmentPercent" );
         		}
		
			#print  print 
			foreach( $names as $key=>$label ) { @print "<td>".$counts[$key]."</td>"; }
			foreach( $names as $key=>$label ) { @print "<td>".$percent[$key]."</td>"; }
			print  "</tr>";
		}

	}
}
print "</table>";

renderFooter();


exit;

