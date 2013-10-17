<?php
error_reporting(E_ALL );
ini_set('display_errors',1 );


require_once('admin/include.php');
require_once "ECS/utils.php";
require_once "ECS/courses.php";

renderHeader("Courses info");
	
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

print "<p>List of all modules and draft modules in the ".COURSES_SessionName( $session )." session.</p>";

$session += 0; # double check it's just a number.

$modules = UoS_Syllabus::getSessionCourseinfo($session,true);
ksort( $modules );

print "<form action='syl-create.php'><input type='hidden' value='$session' name='session' />Create provisional syllabus in this session with (provisional) code <input name='code' length='8' /> <input type='submit' value='create' /></form>";
print "<ul>";
foreach( $modules as $course_code => $yos_list )
{
	print "<li>$course_code<ul>";
	ksort( $yos_list );
	foreach( $yos_list as $yos => $module_list )
	{
		print "<li>$course_code$yos...<ul>";
		ksort( $module_list );
		foreach( $module_list as $module )
		{
			#print "<li>".ECS_Dumper( $module )."</li>";
			print "<li>";
			if( isset( $module["syllabus_id"] ) && $module["provisional"] ) 
			{
				print "(course info awaiting confirmation) ";
			}
			if( isset( $module["code"] )) 
			{
				print "<strong>".htmlspecialchars($module["code"]).":</strong> ";
			}
			elseif( isset( $module["provisional_code"] )) 
			{
				print "<strong>".htmlspecialchars($module["provisional_code"])." (provisional code):</strong> ";
			}
			else
			{
				print "<strong>*no code*</strong> ";
			}
			if( isset( $module["title"] )) 
			{ 
				print htmlspecialchars($module["title"]); 
			}
			elseif( isset( $module["provisional_title"] )) 
			{ 
				print htmlspecialchars($module["provisional_title"])." (provisional title)"; 
			}

			if( isset( $module["syllabus_id"] ) )
			{
				print " [<a href='syl-edit.php?id=".$module["syllabus_id"]."'>Edit Information</a>]";
			}
			else
			{
				print " [<a href='syl-create.php?session=$session&code=".$module["code"]."'>Create provisional course information</a>]";
			}

			print "</li>";
		}
		print "</ul></li>";
	}
	print "</ul></li>";
}
print "</ul>";
#print ECS_dumper( $modules );


renderFooter();


exit;

