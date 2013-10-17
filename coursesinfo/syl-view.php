<?php


require_once('ECS/new-syllabus.php');
require_once('json_encode.php');

ECS_OpenCoreDB();

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

# hack to make ucas links work from secure.ecs
$base_url = "";
if($_GET["base_url"] == 'ecs' ) { $base_url = "http://www.ecs.soton.ac.uk"; }

#header( "Content-type: text/turtle" );

if( !isset( $syllabus ) )
{
	print "Module does not exist, or is not yet available online.";
	exit;
}
$graph = new Graphite();
$graph->ns( "soton", "http://id.southampton.ac.uk/ns/" );
$graph->ns( "aiiso", "http://purl.org/vocab/aiiso/schema#" );
$graph->addTriples( $syllabus->toTriples() );

$content = "";

$syl = $graph->resource( $syllabus->URI() );
$title = $syl->label();
#renderHeader( $syl->label() );
if( !@$_GET["single"] ) {
	$content .= "<h2>".$syl->label()."</h2>\n";
	$content .= '
<script type="text/javascript" src="http://www.ecs.soton.ac.uk/sites/all/themes/southampton2/js/uos_tabs.js?m5go3g"></script>
<style>@import url("http://www.ecs.soton.ac.uk/sites/all/themes/southampton/css/uos_tabs.css?m5go3g");</style>
<style>
h2 { 
	font-family: georgia;
	color: #545F2C;
	font-weight: 100;
}
h3 { 
	font-family: georgia;
	color: #545F2C;
	font-weight: 100;
	border-top: solid 1px #ccc;
	border-bottom: solid 1px #ccc;
	clear: both;
}
.col1 {
	width: 49%;
	float: left;
	margin-bottom: 1em;
}
.col2 {
	width: 49%;
	float: left;
	margin-bottom: 1em;
}
</style>

   <div class="uos_tabArea">
     <div class="uos_tabBar">
       <ul>
         <li class="uos_tabCurrent" id="tab_overview"><a href="#overview">Module Overview</a></li>
         <li id="tab_aims"><a href="#aims">Aims and Objectives</a></li>
         <li id="tab_syllabus"><a href="#syllabus">Syllabus</a></li>
';

  if( $syl->has( "soton:fpasModuleTeaching" ) )
  {
         $content.= "<li id='tab_learning'><a href='#learning'>Learning &amp; Teaching</a></li>";
  }

  if( $syl->has( "soton:fpasModuleAssessment", "soton:fpasModuleAssessmentNotes", "soton:fpasModuleReferralPolicy" ))
  {
         $content.= "<li id='tab_assessment'><a href='#assessment'>Assessment</a></li>";
  }
  if( $syl->has( "soton:fpasModuleResource" ) )
  {
         $content.= "<li id='tab_resources'><a href='#resources'>Resources</a></li>";
  }

       $content.= "</ul> </div>";
} // end if !single


if( !@$_GET["single"] ) { 
	$content.= "<style>.uos_tab_pane_title { display: none; }</style>";
}

$content.= "
     <h2 class='uos_tab_pane_title'>Overview</h2>
     <div class='uos_tab' id='tab_pane_overview'>";

$jsinfo = json_decode( join( "", file( "/var/ecsweb/2012data/moduleinfo.json" ) ), true );
$info = $jsinfo[$syl->getString( "soton:bannerModuleCode" )];
$prognames = array();
$ucascodes = array();
$f = file( "/var/ecsweb/2012data/progs.tsv" );
foreach( $f as $line )
{
	list( $prog_id, $name, $ucas_uri, $ucas ) = preg_split( '/\t/', trim($line) );
	$prognames[$prog_id] = $name;
	$ucascodes[$prog_id] = $ucas;
}
$f = file( "/var/ecsweb/2012data/prognames2.tsv" );
foreach( $f as $line )
{
	list( $prog_id, $name ) = preg_split( '/\t/', trim($line) );
	if( @$prog_id ) { $prognames[$prog_id] = $name; }
}


$content.= $syl->getString( "soton:fpasModuleIntroduction" );
$content.= "<h4>Module Details</h4>";
$content.= "<p class=\"col1\">";
$content.= "<strong>Title:</strong> ".$syl->getString( "aiiso:name" )."<br />";
$content.= "<strong>Code:</strong> ".$syl->getString( "soton:bannerModuleCode" )."<br />";
#$content.= "<strong>Year:</strong> "."TODO"."<br />";
$content.= "</p>";
$content.= "<p class=\"col2\">";
if( @$info["credits"] ) { $content.= "<strong>Credits:</strong> ".$info["credits"]."<br />"; }

if( $info["part_of_term"] == '1' ) { $content.= "<strong>Semester:</strong> "."1 and 2"."<br />"; }
if( $info["part_of_term"] == 'S1' ) { $content.= "<strong>Semester:</strong> "."1"."<br />"; }
if( $info["part_of_term"] == 'S2' ) { $content.= "<strong>Semester:</strong> "."2"."<br />"; }
$content.= "</p>";
#$content.= "<strong>CATS points:</strong> TODO <strong>ECTS points:</strong> TODO<br />";
#$content.= "<strong>Level:</strong> TODO<br />";
#$content.= "<strong>Co-ordinator(s):</strong> TODO<br />";
#$content.= "</p>";

if( sizeof( $info["compulsory"] ))
{
	$content.= "<h4 style='clear:both'>Programmes in which this module is compulsory</h4>";
	$content.= "<ul>";
	foreach( $info["compulsory"] as $progpos )
	{
		list( $prog_id, $yos ) = preg_split( '/-/', $progpos );
		if( @$prognames[$prog_id] )
		{
			if( @$ucascodes[$prog_id] )
			{
				$content.= "<li><a href='$base_url/ucas/".$ucascodes[$prog_id]."'>".$prognames[$prog_id]." (".$ucascodes[$prog_id].")</a></li>";
			}
			else
			{
				$content.= "<li>".$prognames[$prog_id]."</li>";
			}
		}
	}
	$content.= "</ul>";
}
if( sizeof( $info["optional"] ))
{
	$content.= "<h4 style='clear:both'>Programmes in which this module is optional</h4>";
	$content.= "<ul>";
	foreach( $info["optional"] as $progpos )
	{
		list( $prog_id, $yos ) = preg_split( '/-/', $progpos );
		if( @$prognames[$prog_id] )
		{
			if( @$ucascodes[$prog_id] )
			{
				$content.= "<li><a href='$base_url/ucas/".$ucascodes[$prog_id]."'>".$prognames[$prog_id]." (".$ucascodes[$prog_id].")</a></li>";
			}
			else
			{
				$content.= "<li>".$prognames[$prog_id]."</li>";
			}
		}
	}
	$content.= "</ul>";
}
if( sizeof( $info["prereq"] ) || isset( $info["prereq_note"] )  )
{
	$content.= "<h4 style='clear:both'>Pre-requisites and / or co-requisites</h4>";
	if( sizeof( $info["prereq"] ) )
	{
		$content.= "<ul>";
		foreach( $info["prereq"] as $or_group )
		{
			$j = array();
			foreach( $or_group as $code )
			{
				if( array_key_exists( $code, $jsinfo ) )
				{
					$j []= "<a href='/module/$code'>$code</a>";
				}
				else
				{
					$j []= $code;
				}
			}
			$content.= "<li>".join( " or ", $j )."</li>";
		}	
		$content.= "</ul>";
	}
	if( isset( $info["prereq_note"] )  )
	{
		$content.= "<p><strong>Pre-requisites notes/exceptions:</strong> ";
		$content.= $info["prereq_note"]."</p>";
	}
	#$content.= "<pre>".htmlspecialchars(print_r( $info, true ))."</pre>";
}

$content.= "
       <div style='clear:both'></div>
     </div>

<h2 class='uos_tab_pane_title'>Aims and Objectives</h2>
     <div class='uos_tab' id='tab_pane_aims'>
";
$content.= $syl->getString( "soton:fpasModuleLearningOutcomes" );
$content .="
     </div>

<h2 class='uos_tab_pane_title'>Syllabus</h2>
     <div class='uos_tab' id='tab_pane_syllabus'>";
$content.= $syl->getString( "soton:fpasModuleTopics" );
     $content.="</div>";

  if( $syl->has( "soton:fpasModuleTeaching" ) )
  {
     $content.= "<h2 class='uos_tab_pane_title'>Learning and Teaching</h2>"; 
     $content.= "<div class='uos_tab' id='tab_pane_learning'>";
     $content.= "<ul style='font-size:130%'>";
     foreach( $syl->all( "soton:fpasModuleTeaching" ) as $teaching )
     {
	 $content.= "<li style='margin-bottom:0.5em'>";
	 $content.= $teaching->get( "soton:fpasModuleTeachingType" )->label();
	 $d = $teaching->get( "soton:fpasModuleTeachingDuration" );
	 $content.= ", $d hour".($d==1?"":"s");
	 $content.= ", ".$teaching->get( "soton:fpasModuleTeachingFrequencyDescription" );
	 $content.= "</li>";
         #$content.= $teaching->dump();
     }
     $content.= "</ul>";
     $content.= "</div>";
  }

  if( $syl->has( "soton:fpasModuleAssessment", "soton:fpasModuleAssessmentNotes", "soton:fpasModuleReferralPolicy" ))
  {
     $content.= "<h2 class='uos_tab_pane_title'>Assessment</h2>";  
     $content.= "<div class='uos_tab' id='tab_pane_assessment'>";
     if( $syl->has( "soton:fpasModuleAssessment" ))
     {
         $pie = ( sizeof( $syl->all( "soton:fpasModuleAssessment" ) ) > 1 );
$pie = false;
	 if( $pie )
         {
		$content.="
?>
<script language='javascript' src='http://www.ecs.soton.ac.uk/sites/all/themes/southampton2/js/bluff/js-class.js' type='text/javascript'></script>
<script language='javascript' src='http://www.ecs.soton.ac.uk/sites/all/themes/southampton2/js/bluff/bluff-min.js' type='text/javascript'></script>
<script language='javascript' src='http://www.ecs.soton.ac.uk/sites/all/themes/southampton2/js/bluff/excanvas.js' type='text/javascript'></script>
<script>
jQuery(document).ready( function () { 
  var pie = new Bluff.Mini.Pie('module-assess-pie', '300x300' );
  pie.title_font_size = 50;
  pie.legend_font_size = 30;
  pie.marker_font_size = 50;
  pie.font_color = 'black';
  pie.hide_legend = true;
  //pie.legend_position = 'bottom';
  pie.hide_labels_less_than = 3;
  pie.zero_degree = 270;
  pie.set_background({colors: ['white','white']});
pie.colors[0]='#e9e9ff';
pie.colors[7]='#ffcccc';
pie.colors[8]='#ccffcc';
pie.colors[9]='#ccccff';";
         foreach( $syl->all( "soton:fpasModuleAssessment" ) as $assessment )
         {
             $content.= "pie.data( '".$assessment->getString( "soton:fpasModuleAssessmentPercent" )."% - ";
             $content.= $assessment->getString( "soton:fpasModuleAssessmentDescription" );
             $content.= "', ".$assessment->getString( "soton:fpasModuleAssessmentPercent" )." );\n";
         }
$content.= "
pie.draw();
} );
</script>
<div style='float:right'>
<canvas id='module-assess-pie' style='width:300px;height:300px'></canvas>
</div>";
         }
         $content.= "<ul style='font-size:130%'>";
         foreach( $syl->all( "soton:fpasModuleAssessment" ) as $assessment )
         {
	     $content.= "<li style='margin-bottom:0.5em'>";
	     #$content.= $assessment->get( "soton:fpasModuleTeachingType" )->label();
	     #$d = $assessment->get( "soton:fpasModuleTeachingDuration" );
	     #$content.= ", Duration $d hour".($d==1?"":"s");
	     #$content.= ", Frequency ".$assessment->get( "soton:fpasModuleTeachingFrequency" );
             $content.= $assessment->getString( "soton:fpasModuleAssessmentPercent" )."% - ";
             $content.= $assessment->getString( "soton:fpasModuleAssessmentDescription" );
             if( $assessment->has( "soton:fpasModuleAssessmentFrequency" ) )
             {
                 $content.= ", frequency: ".$assessment->getString( "soton:fpasModuleAssessmentFrequency" );
             }
             if( $assessment->has( "soton:fpasModuleAssessmentExamDuration" ) )
             {
                 $content.= ", exam duration: ".$assessment->getString( "soton:fpasModuleAssessmentExamDuration" );
             }
             if( $assessment->has( "soton:fpasModuleAssessmentWeekNos" ) )
             {
                 $content.= ", week nos: ".$assessment->getString( "soton:fpasModuleAssessmentWeekNos" );
             }
             if( $assessment->has( "soton:fpasModuleAssessmentFeedback" ) )
             {
                 $content.= ", feedback: ".$assessment->getString( "soton:fpasModuleAssessmentFeedback" );
             }
	     $content.= "</li>";
         }
         $content.= "</ul>";
     }
     
     if( $syl->has( "soton:fpasModuleAssessmentNotes" )) 
     {
         $content.= "<p>".$syl->getString( "soton:fpasModuleAssessmentNotes" )."</p>";
     }
     $content.= "<p><strong>Referral policy:</strong> ".$syl->get( "soton:fpasModuleReferralPolicy" )->label()."</p>";

     $content.= "  <div style='clear:both'></div>";

     $content.= "</div>";
  }

  if( $syl->has( "soton:fpasModuleResource" ) )
  {
     $content.= "<h2 class='uos_tab_pane_title'>Resources</h2>";
     $content.= "<div class='uos_tab' id='tab_pane_resources'>";

     $resourcesByType = array();
     foreach( $syl->all( "soton:fpasModuleResource" ) as $resource )
     {
       $type = "Other";
       if( $resource->has(  "soton:fpasModuleResourceType" ) )
       {
         $type= $resource->get( "soton:fpasModuleResourceType" )->label();
       }
       $resourcesByType[$type][] = "<div>".$resource->getString("dcterms:description")."</div>";
     }

     $content.= "<dl>";
     $firstTypes = array( "Core textbook", "Background textbook" );
     foreach( $firstTypes as $type )
     {
       if( @$resourcesByType[$type] )
       {
          $content.= "<dt><strong>$type:</strong></dt><dd>".join( "", $resourcesByType[$type] );
          unset( $resourcesByType[$type] );
       }
     }
     foreach( $resourcesByType as $type=>$items )
     {
       if( @$resourcesByType[$type] )
       {
          $content.= "<dt><strong>$type:</strong></dt><dd>".join( "", $resourcesByType[$type] );
          unset( $resourcesByType[$type] );
       }
     }
     $content.= "</dl>";
     $content.= "</div>";
  }






  $content.="</div>";
$content.= "<div style='font-size:200%'>";
#$content.= $graph->dump();
$content.= "</div>";
$content .='
<script>
uos_bindTabs( [
        { "fragment": "overview", "tab": "tab_overview", "pane": "tab_pane_overview", "selected": true },
        { "fragment": "aims", "tab": "tab_aims", "pane": "tab_pane_aims" },
        { "fragment": "syllabus", "tab": "tab_syllabus", "pane": "tab_pane_syllabus" },
        { "fragment": "learning", "tab": "tab_learning", "pane": "tab_pane_learning" },
        { "fragment": "assessment", "tab": "tab_assessment", "pane": "tab_pane_assessment" },
        { "fragment": "resources", "tab": "tab_resources", "pane": "tab_pane_resources" }
] );
</script>
';


if( @$_GET["json"] )
{
	header( "Content-type: text/plain" );
	print json_encode( array("title"=>$title, "content"=>$content, "alias"=>$alias));
}
else
{
	print $content;
}
exit;



