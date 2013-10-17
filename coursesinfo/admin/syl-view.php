<?php


require_once('include.php');

$current_user = $_SERVER["REMOTE_USER"];

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

#header( "Content-type: text/turtle" );


$graph = new Graphite();
$graph->ns( "soton", "http://id.southampton.ac.uk/ns/" );
$graph->ns( "aiiso", "http://purl.org/vocab/aiiso/schema#" );
$graph->addTriples( $syllabus->toTriples() );

$syl = $graph->resource( $syllabus->URI() );
renderHeader( $syl->label() );
#<script type="text/javascript" src="http://admissions.ecs.soton.ac.uk/sites/all/themes/southampton/js/uos_tabs.js?m5go3g"></script>
#<style>@import url("http://admissions.ecs.soton.ac.uk/sites/all/themes/southampton/css/uos_tabs.css?m5go3g");</style>
?>
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

<?
print "<h2>".$syl->label()."</h2>";
?>
   <p>Please note: This specification provides a concise summary of the main features of the programme and the learning outcomes that a typical student might reasonably be expected to achieve and demonstrate if s/he takes full advantage of the learning opportunities that are provided. More detailed information can be found in the programme handbook (or other appropriate guide). </p>
   <div class='uos_tabArea'>
     <div class='uos_tabBar'>
       <ul>
         <li class='uos_tabCurrent' id='tab_overview'><a href='#overview'>Module Overview</a></li>
         <li id='tab_aims'><a href='#aims'>Aims and Objectives</a></li>
         <li id='tab_syllabus'><a href='#syllabus'>Syllabus</a></li>
         <li id='tab_learning'><a href='#learning'>Learning and Teaching</a></li>
         <li id='tab_assessment'><a href='#assessment'>Assessment</a></li>
       </ul>
     </div>
     <div class='uos_tab' id='tab_pane_overview'>
<?php
print $syl->getString( "soton:fpasModuleIntroduction" );
print "<h3>Module Details</h3>";
print "<div class=\"col1\">";
print "<strong>Title:</strong> ".$syl->getString( "aiiso:name" )."<br />";
print "<strong>Code:</strong> ".$syl->getString( "soton:bannerModuleCode" )."<br />";
print "<strong>Year:</strong> "."TODO"."<br />";
print "<strong>Semester:</strong> "."TODO"."<br />";
print "</div>";
print "<div class=\"col2\">";
print "<strong>CATS points:</strong> TODO <strong>ECTS points:</strong> TODO<br />";
print "<strong>Level:</strong> TODO<br />";
print "<strong>Co-ordinator(s):</strong> TODO<br />";
print "</div>";

print "<h3>Pre-requisites and / or co-requisites</h3>";
print "<p>TODO</p>";

print "<h3>Programmes in which this module is compulsory</h3>";
print "<p>TODO</p>";
?>
     </div>

     <div class='uos_tab' id='tab_pane_aims'>
<?php
print $syl->getString( "soton:fpasModuleLearningOutcomes" );
?>
     </div>

     <div class='uos_tab' id='tab_pane_syllabus'>
<?php
print $syl->getString( "soton:fpasModuleTopics" );
?>
     </div>

     <div class='uos_tab' id='tab_pane_learning'>
TODO
     </div>

     <div class='uos_tab' id='tab_pane_assessment'>
TODO
     </div>
   </div>
  </div>
<script>
uos_bindTabs( {
        "tab_overview": { "pane": "tab_pane_overview", "selected": true },
        "tab_aims": { "pane": "tab_pane_aims" },
        "tab_syllabus": { "pane": "tab_pane_syllabus" },
        "tab_learning": { "pane": "tab_pane_learning" },
        "tab_assessment": { "pane": "tab_pane_assessment" }
} );
</script>

<?php




//print $graph->dump();
renderFooter();


exit;


