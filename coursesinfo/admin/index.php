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

print "<p>List of all modules and draft modules in the ".COURSES_SessionName( $session )." session.</p>";

$session += 0; # double check it's just a number.

$modules = UoS_Syllabus::getSessionCourseinfo($session,true);
ksort( $modules );

print "<form action='syl-create.php'><input type='hidden' value='$session' name='session' />Create provisional syllabus in this session with (provisional) code <input name='code' length='8' /> <input type='submit' value='create' /></form>";
$sections = array( "active"=>array(), "inactive"=>array() );
foreach( $modules as $course_code => $yos_list )
{
	ksort( $yos_list );
	$yearitems = array( "active"=>array(), "inactive"=>array() );
	foreach( $yos_list as $yos => $module_list )
	{
		$items = array( "active"=>array(), "inactive"=>array() );
		ksort( $module_list );
		foreach( $module_list as $module )
		{
			$item = "";
			$item.= "<li>";

			if( isset( $module["code"] )) 
			{
				$item.= "<strong>".htmlspecialchars($module["code"]).":</strong> ";
			}
			elseif( isset( $module["provisional_code"] )) 
			{
				$item.= "<strong>".htmlspecialchars($module["provisional_code"])." (provisional code):</strong> ";
			}
			else
			{
				$item.= "<strong>*no code*</strong> ";
			}

			if( isset( $module["title"] )) 
			{ 
				$item.= htmlspecialchars($module["title"]); 
			}
			elseif( isset( $module["provisional_title"] )) 
			{ 
				$item.= htmlspecialchars($module["provisional_title"])." (provisional title)"; 
			}

			if( isset( $module["syllabus_id"] ) )
			{
				$item.= " [<a href='syl-edit.php?id=".$module["syllabus_id"]."'>Edit Information</a>]";
			}
			else
			{
				$item.= " [<a href='syl-create.php?session=$session&code=".$module["code"]."'>Create provisional course information</a>]";
			}
			if( isset( $module["syllabus_id"] ) && $module["provisional"] && @$module["active"]) 
			{
				$item.= " (course info awaiting confirmation) ";
				if( $flags["is_admin"] )
				{
					$item .= " [<a href='syl-approve.php?id=".$module["syllabus_id"]."'>approve</a>]";
				}
			}
		
			$item.= "</li>";
			if( @$module["active"] ) 
			{ 
				$items["active"] []= $item;
			}
			else
			{ 
				$items["inactive"] []= $item;
			}
		}

		if( sizeof( $items["active"] ) )
		{
			$yearitems["active"] []= "<li>$course_code$yos...<ul>".join( "", $items["active"] )."</ul></li>";
		}
		if( sizeof( $items["inactive"] ) )
		{
			$yearitems["inactive"] []= "<li>$course_code$yos...<ul>".join( "", $items["inactive"] )."</ul></li>";
		}
	}
	if( sizeof( $yearitems["active"] ) )
	{
		$sections["active"] []= "<li>$course_code<ul>".join( "", $yearitems["active"] )."</ul></li>";
	}
	if( sizeof( $yearitems["inactive"] ) )
	{
		$sections["inactive"] []= "<li>$course_code<ul>".join( "", $yearitems["inactive"] )."</ul></li>";
	}
}
#print "<Pre>".htmlspecialchars(print_r( $sections,1 ))."</pre>";

print "<h2>Active Modules</h2>";
print "<ul>";
print join( "",$sections["active"] );
print "</ul>";
print "<h2>In-active Modules</h2>";
print "<p>These course descriptions are not linked to an active module code for this session.</p>";
print "<ul>";
print join( "",$sections["inactive"] );
print "</ul>";
#print ECS_dumper( $modules );


renderFooter();


exit;

