<?php
$DEBUG_SQL = false;

require_once "/usr/local/apache/phplib/ECS/utils.php";
require_once "/usr/local/apache/phplib/ECS_SPARQL/arc/ARC2.php";
require_once "/usr/local/apache/phplib/ECS_SPARQL/Graphite/Graphite.php";
require_once "/usr/local/apache/phplib/ECS_SPARQL/PHP-SPARQL-Lib/sparqllib.php";


class UoS_Syllabus
{
	private $data;
	public $module; # sodding baily
	private $id;

	private $MAIN_TEXT_FIELDS = array( 

      "topics", "learning_outcomes" ,"introduction", "provisional_notes", "assessment_notes", "timetable_notes"
           );

	private $REFERRAL_OPTIONS= array(
		"100EXAM"=> "by examination.",
		"EXAM"=>"by examination, with the original coursework mark being carried forward.",
		"EXAMCWORK"=> "by examination and a new coursework assignment.",
		"CWORK"=>"by set coursework assignment(s).",
		"LAB"=> "by means of a special one-day laboratory session.",
		"REWRITE"=>"by re-write of the project report and re-viva (the original progress report mark will be carried forward).",
		"NONE"=>"There is no referral opportunity for this module in same academic year",
		"NOTES"=>"See notes below" );

	private $SA_TYPES = array( 
		""=>"",
		"lecture"=>"Lecture",
		"examples"=>"Examples Class",
		"tutorial"=>"Tutorial",
		"computer_lab"=>"Computer Lab",
		"specialist_lab"=>"Specialist Lab",
		"field_trip"=>"Field Trip" );
	private $SA_DURATIONS = array( 
		""=>"",
		"1"=>"1 hour", "2"=>"2 hours", "3"=>"3 hours", "4"=>"4 hours", "5"=>"5 hours",
		"6"=>"6 hours", "7"=>"7 hours", "8"=>"8 hours", "9"=>"9 hours", "10"=>"10 hours",
		"11"=>"11 hours", "12"=>"12 hours", "13"=>"13 hours", "14"=>"14 hours", "15"=>"15 hours",
		"16"=>"16 hours", "17"=>"17 hours", "18"=>"18 hours", "19"=>"19 hours", "20"=>"20 hours" );
	private $SA_FREQUENCIES = array( 
		""=>"",
		"1/week" => "Once per week",
		"2/week" => "Twice per week",
		"3/week" => "Three times per week",
		"4/week" => "Four times per week",
		"1/2week" => "Once per fortnight",
		"once" => "Once in module",
	);

	private $ASSESSMENT_TYPES = array(
		"exam" => "Exam",
		"other" => "Other",
		"labs" => "Labs",
		"cwork" => "Coursework",
	);

	private $RESOURCE_TYPES = array(
		''=>'',
		'core'=>"Core textbook",
		'background'=>"Background textbook",
		'otherlib'=>"Other library support required",
		'staff'=>"Staff requirements (including teaching assistants and demonstrators)",
		'teachingspace'=>"Teaching space, layout and equipment required",
		'labspace'=>"Laboratory space and equipment required",
		'computer'=>"Computer requirements",
		'software'=>"Software requirements",
		'online'=>"On-line resources",
		'other'=>"Other resource requirements" );

	private $CHANGE_SCALE = array(
		"cosmetic"=>"Purely Cosmetic",
		"minor"=>"Minor",
		"major"=>"Major", );

	# dirty hacks
	function getModuleInfoFromCache()
	{
		global $all_moduleinfo_cache;
		if( !isset( $all_moduleinfo_cache ))
		{
			$all_moduleinfo_cache = json_decode( file_get_contents( "/var/ecsweb/2012data/moduleinfo.json" ) ,true);
		}
		return $all_moduleinfo_cache;
	}

	function getModulesFromCache()
	{
		global $all_modules_cache;
		if( !isset( $all_modules_cache ))
		{
			$all_modules_cache = json_decode( file_get_contents( "/var/ecsweb/2012data/modules.json" ) ,true);
		}
		return $all_modules_cache;
	}

	function __construct( $syllabus_id )
	{
		if( $syllabus_id != ($syllabus_id+0) )
		{
			throw new Exception( "syllabus_id is not an integer" );
		}

		$this->id = $syllabus_id;

		$this->data = $this->readTableSingle( "NEWSYL_MAIN", $this->MAIN_TEXT_FIELDS );
		if( $this->data == null ) { throw new Exception( "Syllabus record does not exist in database" ); }
		$this->data["resources"] = $this->readTable( "NEWSYL_RESOURCES", array( "details" ) );
		$this->data["changes"] = $this->readTable( "NEWSYL_CHANGES" );
		$this->data["regular_teaching"] = $this->readTable( "NEWSYL_REGULAR_TEACHING" );
		$this->data["assessment"] = $this->readTable( "NEWSYL_ASSESSMENT" );

		$this->module = $this->readTableSingle( "NEWSYL_UNIT_SESSION" );
		
		if( isset( $this->module["code"] ) && isset( $this->module["session"] ) )
		{
			# hopefully we'll have a new system soon but for now use these tables
			
#			$sql = "
#SELECT DISTINCT 
#	TITLE as title,
#	Semester1 as semester_1,
#	Semester2 as semester_2, 
#	Show_Code as show_code
#FROM UNITLIST 
#WHERE 
#	SESSION = ".UoS_Syllabus::esc($this->module["session"])." AND
#	UNIT = ".UoS_Syllabus::esc($this->module["code"])."
#";
#                	$row = ECS_queryFetchFreeOne($sql);
#			if(isset($row) && sizeof($r0w)) 
#			{
#				foreach( $row as $k=>$v ) 
#				{
#					$this->module[$k] = trim($v);
#				}
#			}


			# should really escape the sparql but is read only so low risk
			if( false )
			{	
				$sparql = "
SELECT DISTINCT ?code ?title {
 <http://id.southampton.ac.uk/module/".$this->module["code"]."/20".substr($this->module["session"],0,2)."-20".substr( $this->module["session"],2,2)."> <http://purl.org/vocab/aiiso/schema#name> ?title .
}
";

				$sdb = sparql_connect( "http://sparql.data.southampton.ac.uk/" );	
				$result =  $sdb->query( $sparql,1 ); # 1 second timeout
				if( !isset($result) )
				{
					print "<p>Error: ".$sdb->errno().": ".$sdb->error()."</p>";
				}
				$rows = $result->fetch_all();
				foreach( $rows as $row )
				{
					foreach( $row as $k=>$v ) { $row[$k] = trim( $row[$k] ); }
				}
				if(isset($row) && sizeof($row)) 
				{
					foreach( $row as $k=>$v ) 
					{
						$this->module[$k] = trim($v);
					}
				}
			}
			# nb. this doesn't understand sessions, need to update it for 1314
			$moduleinfo = UoS_Syllabus::getModuleInfoFromCache();
			$this->module[ "title"] = $moduleinfo[ $this->module["code"] ]["name"];


		}

		# set defaults
#print ECS_Dumper( $this->data );
		if( $this->data["referral"] == "" ) 
		{ 
			$this->data["referral"] = "100EXAM"; 
		}

    		if( !isset( $this->data['regular_teaching'] ) || sizeof($this->data['regular_teaching']) == 0 )
    		{
        		$this->data['regular_teaching'] = array( 
            			array( "activity_type"=>"lecture", "duration"=>1, "frequency"=>"3/week" ),
            			array( "activity_type"=>"tutorial", "duration"=>1, "frequency"=>"1/week" ),
        		);
    		}

    		if( !isset( $this->data['library_checked'] ) ) { $this->data["library_checked"] = 0; }
    		if( !isset( $this->data['director_checked'] ) ) { $this->data["director_checked"] = 0; }
    		if( !isset( $this->data['review_checked'] ) ) { $this->data["review_checked"] = 1; }

#print "<hr>";
#print ECS_Dumper( $this->data );
	}

	function approve()
	{
		$esc_values = array( 
			UoS_Syllabus::esc( $this->data['syllabus_id'] ), 
			UoS_Syllabus::esc( $this->data['provisional_session'] ), 
			UoS_Syllabus::esc( $this->data['provisional_code'] ), 
		);
		$sql = "INSERT INTO NEWSYL_UNIT_SESSION ( syllabus_id, session,code  ) VALUES ( ".join( ", ", $esc_values ).")";
		#print $sql."<hr/>";
		ECS_query($sql);
		$this->data['provisonal_code'] = null;
		$this->data['provisonal_session'] = null;
		$errors = $this->save();
		return $errors;
	}

	function readTableSingle( $table_name, $text_fields = array() )
	{
		$sql = "SELECT * FROM $table_name WHERE syllabus_id=".$this->id;
		$row = ECS_queryFetchFree( $sql );
		$row = $row[0];
		if( !isset( $row ) ) { return array(); }
		foreach( $text_fields as $field_name )
		{
 			$row[$field_name] = ECS_GetTextField( "$table_name",$field_name,
				"FROM $table_name WHERE syllabus_id=".$this->id );
		}
		$trimmed_row = array();
		foreach( $row as $column=>$value ) 
		{ 
			if( !isset( $value ) ) { $value = ""; }
			$trimmed_row[$column] = trim( $value ); 
		}
		return $trimmed_row;
	}
		
	function readTable( $table_name, $text_fields = array() )
	{
		$sql = "SELECT * FROM $table_name WHERE syllabus_id=".$this->id;
		$rows = array();
		foreach( ECS_queryFetchFree( $sql ) as $row )
		{
			# add any  longer text fields (not varchar fields, I mean)
			if( sizeof( $text_fields ) )
			{
				if( !array_key_exists( "n", $row ))
				{
					die( "Can't pull long text from multiple field without an 'n' value" );
				}
				foreach( $text_fields as $field_name )
				{
 					$row[$field_name] = trim(ECS_GetTextField( "$table_name",$field_name,
						"FROM $table_name WHERE syllabus_id=".$this->id." AND n=".$row["n"] ));
				}
			}
			$trimmed_row = array();
			foreach( $row as $column=>$value ) { $trimmed_row[$column] = trim( $value ); }

			# for ordered (n) or no-order tables (no n column)
			if( array_key_exists( "n", $trimmed_row ))
			{
				$rows[$row["n"]] = $trimmed_row;
			}
			else
			{
				$rows[] = $trimmed_row;
			}
		}
		ksort( $rows );

		return $rows;
	}	

	function save( $cause = "script")
	{
		$errors = array();
		ignore_user_abort(1);
		ECS_query("BEGIN TRAN SETSYL"); 
		ECS_resetSQLok();

		#print ECS_Dumper( $this->data );
				
		$sql = "DELETE FROM NEWSYL_RESOURCES WHERE syllabus_id=".$this->id;
		ECS_query($sql);
		$sql = "DELETE FROM NEWSYL_ASSESSMENT WHERE syllabus_id=".$this->id;
		ECS_query($sql);
		$sql = "DELETE FROM NEWSYL_REGULAR_TEACHING WHERE syllabus_id=".$this->id;
		ECS_query($sql);
		
		$this->updateTableSingle( "NEWSYL_MAIN", 
			array( "introduction","topics","referral","learning_outcomes",
				"library_checked","director_checked","review_checked",
				"provisional_code","provisional_session","provisional_semester",
				"provisional_title","provisional_notes","assessment_notes", "timetable_notes" ),
			$this->MAIN_TEXT_FIELDS,
			$this->data );
		$this->writeTable( "NEWSYL_REGULAR_TEACHING", 
			array( "activity_type","duration","frequency","group_size" ),
			array(),
			$this->data["regular_teaching"] );
		$this->writeTable( "NEWSYL_RESOURCES", 
			array( "details","isbn","type" ),
			array( "details"),
			$this->data["resources"] );
		$this->writeTable( "NEWSYL_ASSESSMENT", 
			array( "description","frequency","type","percent","exam_duration","weeknos","feedback" ),
			array(),
			$this->data["assessment"] );
		$this->writeTableSingle( "NEWSYL_CHANGES", 
			array( "cause","timestamp", "library_checked","director_checked","review_checked","summary" ),
			array( "summary" ), 
			array( $cause, "**magic**", $this->data["library_checked"], $this->data["director_checked"], $this->data["review_checked"], $this->data["changes_summary"] ) );
		
		


		if(!ECS_SQLok() ) { $errors[]= "SQL Error"; }

		if( sizeof( $errors ) == 0 )
		{
        		ECS_query("COMMIT TRAN ");
			ignore_user_abort(0);
			return array(); # success
		} 
	
        	ECS_query("ROLLBACK TRAN ");
		ignore_user_abort(0);
		return $errors;
	}

	function writeTable( $table_name, $fields, $text_fields = array(), $data )
	{
		$row_number = 0;
		foreach( $data as $row )
		{
			$esc_fields = array( "syllabus_id", "n" );
			$esc_values = array( $this->id, $row_number );
			foreach( $fields as $field )
			{	
				$esc_fields []= "[".ECS_dbEscape($field)."]";
				$esc_values []= "'".ECS_dbEscape($row[$field])."'";
			}
			$sql = "INSERT INTO $table_name ( ".join( ", ", $esc_fields )." ) VALUES ( ".join( ", ", $esc_values ).")";
			ECS_query($sql);
		
			foreach( $text_fields as $field )
			{	
				if( $row[$field] == "" ) { continue; }
        			ECS_PutTextField($table_name,$field,"FROM $table_name WHERE syllabus_id=".$this->id." AND n=$row_number", $row[$field]);
			}

			$row_number += 1;	
		}
	}
	function updateTableSingle( $table_name, $fields, $text_fields, $row )
	{
		$esc_bits = array( "syllabus_id" );

		$esc_values = array( $this->id );
		$esc_bits = array();
		foreach( $fields as $field )
		{	
			$esc_bits []= ECS_dbEscape($field)."='".ECS_dbEscape($row[$field])."'";
		}
		$sql = "UPDATE $table_name SET ".join( ", ", $esc_bits )." WHERE syllabus_id=".$this->id;
		ECS_query($sql);
	
		foreach( $text_fields as $field )
		{	
			if( $row[$field] == "" ) { continue; }
       			ECS_PutTextField($table_name,$field,"FROM $table_name WHERE syllabus_id=".$this->id, $row[$field]);
		}
	}
	function writeTableSingle( $table_name, $fields, $text_fields, $row )
	{
		$esc_fields = array( "syllabus_id" );
		$esc_values = array( $this->id );
		foreach( $fields as $field )
		{	
			$esc_fields []= "[".ECS_dbEscape($field)."]";
			if( $table_name == "NEWSYL_CHANGES" && $field == "timestamp" )
			{
				$esc_values []= "{ fn NOW() }"; # hackyhackachakc
			}
			else
			{
				$esc_values []= "'".ECS_dbEscape($row[$field])."'";
			}
		}
		$sql = "INSERT INTO $table_name ( ".join( ", ", $esc_fields )." ) VALUES ( ".join( ", ", $esc_values ).")";
		ECS_query($sql);
	
		foreach( $text_fields as $field )
		{	
			if( $row[$field] == "" ) { continue; }
       			ECS_PutTextField($table_name,$field,"FROM $table_name WHERE syllabus_id=".$this->id, $row[$field]);
		}
	}
		
	function debug()
	{
		print "SYLLABUS DEBUG: #".$this->id."\n";
		print "data:\n";
		print_r( $this->data );
		print "module:\n";
		print_r( $this->module );
	}
	function debugWeb()
	{
		print "<h2>SYLLABUS DEBUG: #".$this->id."</h2>\n";
		print "<h3>data:</h3>\n";
		print "<pre>".htmlspecialchars( print_r( $this->data ,true))."</pre>";
		print "<h3>module:</h3>\n";
		print "<pre>".htmlspecialchars( print_r( $this->module ,true))."</pre>";
	}

	function title()
	{
		if( array_key_exists( "code", $this->module ) ) 
		{
			# later get the imported title from banner
			return $this->module["session"]." / ".$this->module["code"]." / ".$this->module["title"];
		}
	
		return "(provisional #".$this->id.") ".  $this->data['provisional_session']." / ".  $this->data['provisional_code']." / ".  $this->data['provisional_title'];
	}

	function render()
	{
		$html = array();
		#$html[]= ECS_Dumper( $this->module );
		#$html[]= ECS_Dumper( $this->data );
		$html[]= "<style>
.uos_syl_datagrid th { 
	text-align: right;
}

</style>";
		$html[]= "<article>";
		$html[]="<h1>".$this->title()."</h1>";
		if( array_key_exists( "code", $this->module ) )
		{
			$html []= "<p>Module: ".htmlspecialchars($this->module["code"])." (".COURSES_SessionName( $this->module["session"] ).")</p>";
			$semesters = array();
			if( $this->module["semester_1"] ) { $semesters []= "1"; }
			if( $this->module["semester_2"] ) { $semesters []= "2"; }
			# $html []= "<p>Semester: ".join( " &amp; ", $semesters )."</p>";
		}
		else
		{
			$html []= "<p>This module has not yet been assigned a code in our database. Provisional information:</p>";
			$html []= "<table class='uos_syl_datagrid'>";
			$html []= "<tr><th>Provisional Code:</th><td>".htmlspecialchars( $this->data["provisional_code"] )."</td></tr>";
			$html []= "<tr><th>Provisional Session:</th><td>".htmlspecialchars( $this->data["provisional_session"] )."</td></tr>";
			$html []= "<tr><th>Provisional Title:</th><td>".htmlspecialchars( $this->data["provisional_title"] )."</td></tr>";
			$html []= "<tr><th>Provisional Semester:</th><td>".htmlspecialchars( $this->data["provisional_semester"] )."</td></tr>";
			$html []= "<tr><th>Comments:</th><td>".htmlspecialchars( $this->data["provisional_notes"] )."</td></tr>";
		}

		if( $this->data["introduction"] != "" )
		{
			$html []= "<h2>Introduction</h2>";
			$html []= $this->data["introduction"];
		}
		if( $this->data["learning_outcomes"] != "" )
		{
			$html []= "<h2>Learning Outcomes</h2>";
			$html []= $this->data["learning_outcomes"];
		}
		if( $this->data["topics"] != "" )
		{
			$html []= "<h2>Topics</h2>";
			$html []= $this->data["topics"];
		}

		$html []= "<h2>Teaching</h2>";

		$html []= "<ul>";
		foreach( $this->data["regular_teaching"] as $item )
		{
			$html []= "<li>".$this->SA_TYPES[$item['activity_type']];
			$html []= ", ".$this->SA_DURATIONS[$item['duration']];
			$html []= ", ".$this->SA_FREQUENCIES[$item['frequency']];
			if( @$item["group_size"] ) { $html []= ", group size ".$item['group_size']; }
			$html []= ".</li>";
		}
		$html []= "</ul>";

		if( $this->data["assessment_notes"] != "" )
		{
			$html []= "<h2>Assessment Notes</h2>";
			$html []= $this->data["assessment_notes"];
		}

		$html []= "<h2>Assessment</h2>";

		$html []= "<ul>";
		foreach( $this->data["assessment"] as $item )
		{
			$html []= "<li>".htmlspecialchars($item["description"]).", ";
			$html []= ", ".$this->ASSESSMENT_TYPES[$item['type']];
			$html []= ", ".htmlspecialchars( $item["frequency"] );
			$html []= ", ".htmlspecialchars( $item["percent"] )."%";
			$html []= ", exam dur=".htmlspecialchars( $item["exam_duration"] );
			$html []= ", week nos=".htmlspecialchars( $item["weeknos"] );
			$html []= ", feedback=".htmlspecialchars( $item["feedback"] );
		}
		$html []= "</ul>";

		$html []= "<h2>Referral</h2>";
		$html []= "<p>On referral, this unit will be assessed: <strong>";
		$html []= $this->REFERRAL_OPTIONS[$this->data["referral"]];
		$html []= "</strong></p>";

		$html []= "<h2>Resources</h2>";

		$html []= "<ul>";
		foreach( $this->data["resources"] as $item )
		{
			$html []= "<li>";
			$html []= $this->RESOURCE_TYPES[$item['type']];
			$html []= ", ".$item["details"];
			$html []= ", ".htmlspecialchars( $item["isbn"] );
		}
		$html []= "</ul>";

		$html[]= "</article>";
		return join( "", $html );
	}	











	
	private function getForm( $flags = array() )
	{
		$form = new FloraForm( array( "heading"=>1, resourcesURL=>"https://secure.ecs.soton.ac.uk/coursesinfo/admin/FloraForm/resources" ) );

#		$form->add( "INFO", array( 
#			"content_html" => "<h1>FPAS Syllabus Editor</h1>" ));

		# Section 1.

		if( array_key_exists( "code", $this->module ) ) 
		{
			$intro = $form->add( "SECTION", array( 
				"title" => $this->module["code"].": ".$this->module["title"],
				"layout" => "section" ));
			$module_data = "";
			$module_data.= "<p>Code and Session: ".htmlspecialchars($this->module["code"])." (".COURSES_SessionName( $this->module["session"] ).")</p>";

			$semesters = array();
			if( $this->module["semester_1"] ) { $semesters []= "1"; }
			if( $this->module["semester_2"] ) { $semesters []= "2"; }
			#$module_data.= "<p>Semester: ".join( " &amp; ", $semesters )."</p>";
			
			$intro->add( "INFO", array( 
				"layout" => "section",
				"content_html" => $module_data ));
			$intro->add( "HIDDEN", array( "id"=>"provisional_title",));
			$intro->add( "HIDDEN", array( "id"=>"provisional_code",));
			$intro->add( "HIDDEN", array( "id"=>"provisional_session",));
			$intro->add( "HIDDEN", array( "id"=>"provisional_semester",));
			$intro->add( "HIDDEN", array( "id"=>"provisional_notes",));
	
		}
		else
		{
			$intro = $form->add( "SECTION", array( 
				"title" => "Provisional Module Description",
				"layout" => "section" ));
			$intro->add( "INFO", array( 
				"layout" => "section",
				"content_html" => "<p>This module description has not yet been formally linked with a code and session.</p>" ));
	
			$intro->add( "TEXT", array( 
				"id"=>"provisional_title",
				"title"=>"Provisional Module Title",
				"layout"=>"vertical"
			));
			$intro->add( "TEXT", array( 
				"id"=>"provisional_code",
				"title"=>"Provisional Module Code",
				"layout"=>"vertical"
			));
			$intro->add( "CHOICE", array( 
				"id"=>"provisional_session",
				"title"=>"Provisional Session",
				"choices"=>array( 
					"" => "-",
					"1213" => "2012-2013",
					"1314" => "2013-2014",
					"1415" => "2014-2015",
					"1516" => "2015-2016",
					"1617" => "2016-2017", )
			));
			$intro->add( "TEXT", array( 
				"id"=>"provisional_semester",
				"title"=>"Provisional Semester",
				"layout"=>"vertical"
			));
			$intro->add( "HTML", array( 
				"id"=>"provisional_notes",
				"title"=>"Provisional Notes",
				"layout"=>"section"
			));
		}



		$s2 = $form->add( "SECTION", array(
			"title" => "1. Description",
			));
		$s2->add( "HTML", array( 
			"layout" => "section",
			"id" => "introduction", # TODO change field name
			"title" => "1.1 Introduction",
			"rows" => 10,
			"description" => "
This section should be used to give a summary of the module, its aims, and (for core / compulsory modules) how it fits in with the programme as a whole or (for optional modules) why students might choose to take it. You can also give a general indication of pre-requisite knowledge and skills which are assumed.
",
		));

		$s2->add( "HTML", array( 
			"layout" => "section",
			"id" => "learning_outcomes", # TODO change field name
			"title" => "1.2 Learning Outcomes",
			"rows" => 10,
			"description" => "
This section should be used to list the intended learning outcomes of the module. You can refer to <a href='http://www.opendatacompetition.soton.ac.uk/newsite/'>guidance in the quality handbook</a> for advice on these. For a standard 15 credit module, 5 to 8 outcomes should be sufficient. Please do not repeat the list of topics for the module, which are given in the following section.
",
		));

		$s2->add( "HTML", array( 
			"layout" => "section",
			"id" => "topics", # TODO change field name
			"rows" => 10,
			"title" => "1.3 Topics",
			"description" => "A summary of contents covered, perhaps 10 to 20 bullet points." ) );

		### Assessment
	
		$s1 = $form->add( "SECTION", array(
			"title" => "2. Basic Information",
			));

		$reg_combo = $s1->add( "LIST", array( 
			"id" => "regular_teaching",
			"layout" => "section",
			"title" => "2.1 Scheduled Teaching Activities",
			"description" => "This section allows you to provide data for timetabling purposes and key information sets. For each scheduled activity, please indicate the nature of the activity, its duration, and its frequency (ie the number of sessions).  If the class divides into groups for this activity, please give the (maximum) group size; in this case, the frequency should be given from the student perspective. Otherwise, leave this field blank. ") )->setListType( "COMBO" );
		$reg_combo->add( "CHOICE", array(
			"id" => "activity_type",
			"title" => "Type",
			"choices" => $this->SA_TYPES,
			"mode" => "pull-down" ) );
		$reg_combo->add( "CHOICE", array(
			"id" => "duration",
			"title" => "Duration",
			"choices" => $this->SA_DURATIONS,
			"mode" => "pull-down" ) );
		$reg_combo->add( "CHOICE", array(
			"id" => "frequency",
			"title" => "Frequency",
			"choices" => $this->SA_FREQUENCIES,
			"mode" => "pull-down" ) );
		$reg_combo->add( "TEXT", array( 
			"id" => "group_size",
			"size" => 2,
			"title" => "Group Size" ));

		

#		$s1->add( "INFO", array( 
#			"layout" => "section",
#			"title" => "2.1.2 Demonstrator Support",
#			"description" => "This field summarises the demonstrator support which will be provided for this module. If you need something different, please email the demonstrator support coordinator in your Academic Unit.",
#			"content" => "!!!!!Some content here!" ) );

		$ass_combo = $s1->add( "LIST", array( 
			"id" => "assessment",
			"layout" => "section",
			"title" => "2.2 Summative Assessment",
			"description" => "
This section allows you to provide data for student workload monitoring, and key information sets. Note that the total percentages across all assessment activities should add up to 100. For an exam, give the planned duration. For other assessment activities, please indicate the week or weeks of the semester when they are planned to occur, where week 1 is the start of teaching, and week 12 is the last week before exams, which is typically reserved for revision. Note that assignment deadlines should therefore not occur during weeks 12 to 15.  Finally, indicate when and how you will provide feedback on assignments -- for example, you might state that <i>after 2 weeks individual feedback sheets will be returned, and a generic feedback will be provided in-class.</i>
" ) )->setListType( "COMBO", array( "layout" ));
		

		$ass_combo->add( "TEXT", array(
			"id" => "description",
			"layout" => "vertical",
			"title" => "Description" ));
		$ass_combo->add( "TEXT", array(
			"id" => "frequency",
			"size" => 3,
			"layout" => "vertical2up",
			"title" => "Frequency" ));
		$ass_combo->add( "TEXT", array(
			"id" => "percent",
			"layout" => "vertical2up",
			"size" => 3,
			"suffix" => "%",
			"title" => "Total Percentage" ));
		$ass_combo->add( "CHOICE", array(
			"id" => "type",
			"layout" => "vertical",
			"title" => "Type",
			"choices" => $this->ASSESSMENT_TYPES,
			"lots-of-class" => true,
			"mode" => "radio" ) );
#		$ass_combo->add( "TEXT", array(
#			"id" => "notes",
#			"title" => "Notes" ));

		$ass_combo->add( "INFO", array(
			"layout" => "vertical2up",
			"content_html" => "<div style='text-align:center'>If exam, give duration</div>" ));
		$ass_combo->add( "INFO", array(
			"layout" => "vertical2up",
			"content_html" => "<div style='text-align:center'>Otherwise give week No(s)</div>" ));

		$ass_combo->add( "TEXT", array(
			"id" => "exam_duration",
			"layout" => "vertical2up",
			"size" => 3,
			"suffix"=>" hours",
			"title" => "Exam Duration" ));
		$ass_combo->add( "TEXT", array(
			"id" => "weeknos",
			"layout" => "vertical2up",
			"title" => "Week no(s)" ));
		$ass_combo->add( "INFO", array( 
			"layout" => "block",
			"content_html" => "Feedback:" ));
		$ass_combo->add( "TEXTAREA", array(
			"id" => "feedback",
			"rows" => "3",
			"layout" => "block",
			"title" => "Feedback" ));

		$s1->add( "CHOICE", array( 
			"id" => "referral",
			"layout" => "section",
			"title" => "2.3 Referral Policy",
			"description" => "
Each module must have a defined referral policy, which must apply to all students who refer.
University policy requires that failure should be redeemable, so students need an opportunity to correct any failure (typically during the summer break, but possibly also within the academic year itself, or if neither of these is possible, then the following year).
",
			"prefix" => "On referral, this unit will be assessed ",
			"choices" => $this->REFERRAL_OPTIONS,
			"mode" => "pull-down" ) );
##		$s1->add( "TEXT", array( 
#			"id" => "referral_notes",
#			"title" => "Referral Notes",
#		));
		$s1->add( "HTML", array( 
			"id" => "assessment_notes",
			"title" => "2.4 Assessment Notes",
			"description" => "
If there are special aspects related to assessment, please state them here.  As one possible example, <i>where there are multiple worksheets, the best 8 out of 10 marks will be taken; or if a minimum attendance of 8 out of 10 laboratory sessions is required before a mark can be returned.</i>  Finally, if there is a field trip, please state the arrangements and cost implications.
",
			"layout" => "section",
		));
		$s1->add( "HTML", array( 
			"id" => "timetable_notes",
			"title" => "2.5 Timetabling Requirements",
			"description" => "
If there are special timetabling requirements, for example, a specific venue or specialist facilities are needed, please indicate these in this field.  This information is provided to the Central Timetabling Unit, and is not visible to students.
",
			"layout" => "section",
		));




		$s3 = $form->add( "SECTION", array(
			"title" => "3. Resources"));
			
		$res_combo = $s3->add( "LIST", array(
			"id" => "resources",
			"layout" => "section",
			))->setListType( "COMBO" );
		$res_combo->add( "CHOICE", array( 
			"id" => "type",
			"title" => "Type",
			"choices" => $this->RESOURCE_TYPES,
			"mode" => "pull-down" ) );
		$res_combo->add( "TEXT", array(
			"id" => "isbn",
			"title" => "ISBN" ) );	
		$res_combo->add( "HTML", array(
			"id" => "details",
			"rows" => 2,
			"layout" => "block",
			"title" => "Details" ) );	



		$s4 = $form->add( "SECTION", array(
			"title" => "4. Changes",
			));
		$s4->add( "HTML", array(
			"id" => "changes_summary",
			"title" => "4.1 Recent Changes",
			"description" => "Please use this section to summarise recent changes to the syllabus, and why they were made. If this was in response to student comments, please quote some of them, or link to the questionnaire data.",
			"layout"=>"section" ));
		$s4->add( "SECTION", array(
			"title" => "4.2 Nature of Edit",
			"description" => "
Changes which are significant need to be reviewed by the director of programmes and/or FPC. In addition, each module should be peer reviewed once every five years (at least).   If you have made a significant change to module content, please set the approval flag to No.  If you have changed the list of textbooks, please set the library flag to No.
",
			));


		# The restriction of options is the only restriction. A malicious user could currently 
		# hack their response to approve a course, but that would be a very odd thing to do.
	
		$options = array( "0"=>"No", "1"=>"Yes" );
		if( !$this->data["library_checked"] && !$flags["is_library"] ) { $options = array( "0"=>"No (the library will set this to 'yes' once its confirmed)" ); }
		$s4->add( "CHOICE", array( 
			"id" => "library_checked",
			"title" => "There is a copy of each textbook in the University library",
			"choices" => $options,
			"layout" => "vertical",
			"mode" => "radio" ) );

		$options = array( "0"=>"No", "1"=>"Yes" );
		if( !$this->data["director_checked"] && !$flags["is_director"] ) { $options = array( "0"=>"No (only director or FPC may set this to 'Yes')" ); }
		$s4->add( "CHOICE", array( 
			"id" => "director_checked",
			"title" => "The content of this module has been approved by Director of Programmes and/or FPC",
			"choices" => $options,
			"layout" => "vertical",
			"mode" => "radio" ) );

		$options = array( "0"=>"No", "1"=>"Yes" );
		if( !$this->data["review_checked"] && !$flags["is_review"] ) { $options = array( "0"=>"No (only specifically authorised staff may set this to 'yes')" ); }
		$s4->add( "CHOICE", array( 
			"id" => "review_checked",
			"title" => "The content of this module has been subject to quinquennial review",
			"choices" => $options,
			"layout" => "vertical",
			"mode" => "radio" ) );
	
		$form->add( "HIDDEN", array( 
			"id" => "syllabus_id",
		));
		$form->add( "SUBMIT", array( 
			"title" => "Submit Changes",
		));




		return $form;	
	}

	function renderForm($flags)
	{
		return $this->getForm($flags)->render( $this->data );
	}

	function fromForm($flags)
	{
		global $_POST;
		$this->getForm($flags)->fromForm( $this->data, $_POST );
	}
	function issues()
	{
		$issues = array();
		return $issues;
	}

	

	###########################
	# Open Data Functions
	###########################

	function URI()
	{
		if( !isset( $this->module["session"] ) ) { return null; }
		if( !isset( $this->module["code"] ) ) { return null; }
		return "http://id.southampton.ac.uk/module/".$this->module["code"]."/20".substr($this->module["session"],0,2 )."-20".substr($this->module["session"],2,2 );
	}

	function toTriples()
	{
		$uri = $this->URI();
		if( !isset( $uri ) ) { return array(); }

		$triples = array();
		$ns = array();

		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri, 
			"p_type"=>"uri", "p"=>"http://www.w3.org/1999/02/22-rdf-syntax-ns#type", 
			"o_type"=>"uri", "o"=>"http://id.southampton.ac.uk/ns/Module", 
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri, 
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/inAcademicSession",
			"o_type"=>"uri", "o"=>"http://id.southampton.ac.uk/academic-session/20".substr($this->module["session"],0,2 )."-20".substr($this->module["session"],2,2 )
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri, 
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/bannerModuleCode",
			"o_type"=>"literal", "o"=>$this->module["code"],
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri, 
			"p_type"=>"uri", "p"=>"http://purl.org/vocab/aiiso/schema#name", 
			"o_type"=>"literal", "o"=>$this->module["title"],
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri, 
			"p_type"=>"uri", "p"=>"http://www.w3.org/2000/01/rdf-schema#label",
			"o_type"=>"literal", "o"=>$this->module["code"].": ".$this->module["title"]." (20".substr($this->module["session"],0,2 )."-20".substr($this->module["session"],2,2 )." session)" );
		$triples []= array( 
			"s_type"=>"uri", "s"=>"http://id.southampton.ac.uk/module/".$this->module["code"],
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/bannerModuleSeriesHasModule",
			"o_type"=>"uri", "o"=>$uri, 
		);

		# TODO     [semester_1] => 1 [semester_2] => 0

		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri,
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleIntroduction",
			"o_type"=>"literal", "o"=>$this->data["introduction"], "o_datatype"=>"http://purl.org/xtypes/Fragment-HTML" 
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri,
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTopics",
			"o_type"=>"literal", "o"=>$this->data["topics"], "o_datatype"=>"http://purl.org/xtypes/Fragment-HTML" 
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri,
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentNotes",
			"o_type"=>"literal", "o"=>$this->data["assessment_notes"], "o_datatype"=>"http://purl.org/xtypes/Fragment-HTML" 
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri,
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleLearningOutcomes",
			"o_type"=>"literal", "o"=>$this->data["learning_outcomes"], "o_datatype"=>"http://purl.org/xtypes/Fragment-HTML" 
		);
		$triples []= array( 
			"s_type"=>"uri", "s"=>$uri,
			"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleReferralPolicy",
			"o_type"=>"uri", "o"=>"http://id.southampton.ac.uk/ns/FpasModuleReferralPolicy-".$this->data["referral"],
		);

		$ref_policy = "On referral, this unit will be assessed ".$this->REFERRAL_OPTIONS[$this->data["referral"]];
		if( $this->data["referral"] == "NONE" ) { $ref_policy = $this->REFERRAL_OPTIONS[$this->data["referral"]]; }
		$triples []= array( 
			"s_type"=>"uri", "s"=>"http://id.southampton.ac.uk/ns/FpasModuleReferralPolicy-".$this->data["referral"],
			"p_type"=>"uri", "p"=>"http://www.w3.org/2000/01/rdf-schema#label",
			"o_type"=>"literal", "o"=>$ref_policy 
		);


		foreach( $this->data["resources"] as $resource )
		{
			$triples []= array( 
				"s_type"=>"uri", "s"=>$uri,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleResource",
				"o_type"=>"uri", "o"=>"$uri#resource-".$resource["n"],
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#resource-".$resource["n"],
				"p_type"=>"uri", "p"=>"http://purl.org/dc/terms/description",
				"o_type"=>"literal", "o"=>$resource["details"], "o_datatype"=>"http://purl.org/xtypes/Fragment-HTML" 
			);
			if( $resource["type"] != "" )
			{
				$triples []= array( 
					"s_type"=>"uri", "s"=>"$uri#resource-".$resource["n"],
					"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleResourceType",
					"o_type"=>"uri", "o"=>"http://id.southampton.ac.uk/ns/FpasModuleResourceType-".$resource["type"]
				);
				$triples []= array( 
					"s_type"=>"uri", "s"=>"http://id.southampton.ac.uk/ns/FpasModuleResourceType-".$resource["type"],
					"p_type"=>"uri", "p"=>"http://www.w3.org/2000/01/rdf-schema#label",
					"o_type"=>"literal", "o"=>$this->RESOURCE_TYPES[ $resource["type"] ]
				);
			}
			if( isset( $resource["isbn"] ) && $resource["isbn"] != "" )
			{
				$triples []= array( 
					"s_type"=>"uri", "s"=>"$uri#resource-".$resource["n"],
					"p_type"=>"uri", "p"=>"http://purl.org/ontology/bibo/isbn",
					"o_type"=>"uri", "o"=>"urn:isbn:".$resource["isbn"]
				);
			}
		}

		foreach( $this->data["regular_teaching"] as $n=>$teaching )
		{
			$triples []= array( 
				"s_type"=>"uri", "s"=>$uri,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTeaching",
				"o_type"=>"uri", "o"=>"$uri#teaching-".$n,
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#teaching-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTeachingType",
				"o_type"=>"uri", "o"=>"http://id.southampton.ac.uk/ns/FpasModuleTeachingType-".$teaching["activity_type"]
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"http://id.southampton.ac.uk/ns/FpasModuleTeachingType-".$teaching["activity_type"],
				"p_type"=>"uri", "p"=>"http://www.w3.org/2000/01/rdf-schema#label",
				"o_type"=>"literal", "o"=>$this->SA_TYPES[$teaching["activity_type"]]
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#teaching-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTeachingDuration",
				"o_type"=>"literal", "o"=>$teaching["duration"] 
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#teaching-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTeachingDurationDescription",
				"o_type"=>"literal", "o"=>$this->SA_DURATIONS[$teaching["duration"]]
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#teaching-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTeachingFrequency",
				"o_type"=>"literal", "o"=>$teaching["frequency"] 
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#teaching-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTeachingFrequencyDescription",
				"o_type"=>"literal", "o"=>$this->SA_FREQUENCIES[$teaching["frequency"]]
			);
			if( @ $teaching["group_size"] )
			{
				$triples []= array( 
					"s_type"=>"uri", "s"=>"$uri#teaching-".$n,
					"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleTeachingGroupSize",
					"o_type"=>"literal", "o"=>$teaching["group_size"] 
				);
			}
		}
		foreach( $this->data["assessment"] as $n=>$assessment )
		{
			$triples []= array( 
				"s_type"=>"uri", "s"=>$uri,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessment",
				"o_type"=>"uri", "o"=>"$uri#assessment-".$n,
			);
			$ass_type = "http://id.southampton.ac.uk/ns/FpasModuleAssessmentType-".$assessment["type"];
			if( $ass_type == "" )
			{
				$ass_type = "unknown";
				$ass_label = "Unknown";
			}
			else
			{
				$ass_label = $this->ASSESSMENT_TYPES[$assessment["type"]];
			}
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#assessment-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentType",
				"o_type"=>"uri", "o"=>$ass_type,
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>$ass_type,
				"p_type"=>"uri", "p"=>"http://www.w3.org/2000/01/rdf-schema#label",
				"o_type"=>"literal", "o"=>$ass_label 
			);

			if( $assessment["frequency"] != 0 && $assessment["frequency"] != "" )
			{
				$triples []= array( 
					"s_type"=>"uri", "s"=>"$uri#assessment-".$n,
					"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentFrequency",
					"o_type"=>"literal", "o"=>$assessment["frequency"] 
				);
			}
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#assessment-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentPercent",
				"o_type"=>"literal", "o"=>$assessment["percent"] 
			);
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri#assessment-".$n,
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentDescription",
				"o_type"=>"literal", "o"=>$assessment["description"] 
			);
			if( $assessment["exam_duration"] != 0 && $assessment["exam_duration"] != "" )
			{
				$triples []= array( 
					"s_type"=>"uri", "s"=>"$uri#assessment-".$n,
					"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentExamDuration",
					"o_type"=>"literal", "o"=>$assessment["exam_duration"] 
				);
			}
			if( $assessment["weeknos"] != "" )
			{
				$triples []= array( 
					"s_type"=>"uri", "s"=>"$uri#assessment-".$n,
					"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentWeekNos",
					"o_type"=>"literal", "o"=>$assessment["weeknos"] 
				);
			}
			if( $assessment["feedback"] != "" )
			{
				$triples []= array( 
					"s_type"=>"uri", "s"=>"$uri#assessment-".$n,
					"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentFeedback",
					"o_type"=>"literal", "o"=>$assessment["feedback"] 
				);
			}
		}
		if( $this->data["assessment_notes"] != "" )
		{
			$triples []= array( 
				"s_type"=>"uri", "s"=>"$uri",
				"p_type"=>"uri", "p"=>"http://id.southampton.ac.uk/ns/fpasModuleAssessmentNotes",
				"o_type"=>"literal", "o"=>$this->data["assessment_notes"]
			);
		}

		return $triples;
	}




	####################################
	# static functions
	####################################
	

	static function getSessionCourseinfo( $session, $include_provisional = false )
	{
		if( ! preg_match( '/^\d\d\d\d$/', $session ) )
		{
			throw new Exception( "\$session is not a 4 digit code" );
		}

		$modules = array();
	

		if( $include_provisional ) 
		{	
			# first get all the provisional data for the session (we delete
			# this in a moment if there's a proper value set in NEWSYL_UNIT_SESSION
			$sql = "
SELECT 
	syllabus_id,
	provisional_code,
	provisional_semester,
	provisional_notes,
	provisional_title
FROM 
	NEWSYL_MAIN
WHERE 
	provisional_session = '$session' 
";
			$rows = ECS_queryFetchFree($sql);

			$prov_code_count = 0;
			foreach( $rows as $row )
			{
				foreach( $row as $k=>$v ) { $row[$k] = trim( $row[$k] ); }
				$unit_code = $row["provisional_code"];
				if( $unit_code == "" ) 
				{ 
					$unit_code = sprintf( "XXXX%04d", $prov_code_count++ );
				}
				$course_code = substr( $unit_code, 0, 4 );
				$yos = substr( $unit_code, 4, 1 );
				$row["provisional"] = true;
				$modules[ $course_code ][ $yos ][ $unit_code ] = $row;
			}
		}



	if(false){
		# no longer used
		$sql = "
SELECT DISTINCT 
	DEGS.Faculty_Unit_code as code,
	UNITLIST.TITLE as title,
	UNITLIST.Semester1 as semester_1,
	UNITLIST.Semester2 as semester_2, 
	UNITLIST.Show_Code as show_code
FROM DEGS, UNITLIST 
WHERE 
	UNITLIST.SESSION = '$session' 
	AND DEGS.SESSION = '$session' 
	AND DEGS.UNIT = UNITLIST.UNIT 
";
		#$rows = ECS_queryFetchFree($sql);
	}


	if(false){
		# we now get this from a cache
		$sparql = "
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX soton: <http://id.southampton.ac.uk/ns/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

SELECT DISTINCT ?code ?title ?semester_1 ?semester_2 ?show_code  {
 ?module soton:inAcademicSession <http://id.southampton.ac.uk/academic-session/20".substr($session,0,2)."-20".substr( $session,2,2)."> .
 ?module a soton:Module .
 ?module soton:bannerModuleCode ?code .
 ?module soton:bannerOrgUnit ?org .
 FILTER ( ?org = <http://id.southampton.ac.uk/org/FP> || ?org = <http://id.southampton.ac.uk/org/WF> )
 ?module <http://purl.org/vocab/aiiso/schema#name> ?title .
}
";

		$sdb = sparql_connect( "http://sparql.data.southampton.ac.uk/" );	
		$result =  $sdb->query( $sparql,1 ); # 1 second timeout
		if( !isset($result) )
		{
			print "<p>Error: ".$sdb->errno().": ".$sdb->error()."</p>";
		}
		$rows = $result->fetch_all();
	}


	$rows = UoS_Syllabus::getModulesFromCache();


		# Add non ECS/Physics courses that should be included but for
		# which we don't have a proper way to find out yet.
		$rows []= array( "code"=>"MATH1055", "title"=>"Mathematics for Electrical and Electronic Engineering" );
		$rows []= array( "code"=>"COMP3033", "title"=>"Computational Biology" );
		foreach( $rows as $row )
		{
			if( $row["code"] == "COMP3028" ) { continue; }
			if( $row["code"] == "COMP3031" ) { continue; }
			if( $row["code"] == "ELEC1030" ) { continue; }
			if( $row["code"] == "PHYS3005" ) { continue; }
			if( $row["code"] == "PHYS6019" ) { continue; }
			if( $row["code"] == "PHYS6020" ) { continue; }
			if( $row["code"] == "PHYS6021" ) { continue; }
			if( $row["code"] == "PHYS6022" ) { continue; }

			foreach( $row as $k=>$v ) { $row[$k] = trim( $row[$k] ); }
			$unit_code = $row["code"];
			if( $unit_code == "" ) { continue; }
		
			$course_code = substr( $unit_code, 0, 4 );
			$yos = substr( $unit_code, 4, 1 );

			# add & over-write values but don't remove any existing values
			foreach( $row as $key=>$value )
			{
				$modules[ $course_code ][ $yos ][ $unit_code ][ $key ] = $value;
			}
			if( !isset( $modules[ $course_code ][ $yos ][ $unit_code ][ "provisional" ] ) )
			{
				$modules[ $course_code ][ $yos ][ $unit_code ][ "provisional" ]  = true;
			}
			$modules[ $course_code ][ $yos ][ $unit_code ][ "active" ] = true;
		}
		#print "<Pre>".print_r( $modules,1 )."</pre>";

		# add syllabus ID if known
		$sql = "
SELECT 
	syllabus_id, 
	code
FROM 
	NEWSYL_UNIT_SESSION
WHERE 
	session = '$session' 
";
		$rows = ECS_queryFetchFree($sql);
		foreach( $rows as $row )
		{
			foreach( $row as $k=>$v ) { $row[$k] = trim( $row[$k] ); }
			$unit_code = $row["code"];
			if( $unit_code == "" ) { continue; }
		
			$course_code = substr( $unit_code, 0, 4 );
			$yos = substr( $unit_code, 4, 1 );
			$modules[ $course_code ][ $yos ][ $unit_code ]["syllabus_id"] = $row["syllabus_id"];
			$modules[ $course_code ][ $yos ][ $unit_code ]["code"] = $row["code"];
			$modules[ $course_code ][ $yos ][ $unit_code ]["provisional"] = false;
		}

		return $modules;
	}

	static function bySessionAndCode( $session, $code, $include_provisional = false )
	{
		if( ! preg_match( '/^\d\d\d\d$/', $session ) )
		{
			throw new Exception( "\$session is not a 4 digit code" );
		}
		if( ! preg_match( '/^[A-Z]{4}\d\d\d\d$/', $code ) )
		{
			throw new Exception( "\$code is not a 8 char course code code" );
		}

		# try legit ones first
		$sql = "
SELECT 
	syllabus_id
FROM 
	NEWSYL_UNIT_SESSION
WHERE 
	session = '$session' AND
	code = '$code'
";
		$rows = ECS_queryFetchFree($sql);
		if( sizeof( $rows ) ) 
		{
			return new UoS_Syllabus( $rows[0]["syllabus_id"] );
		}

		if( !$include_provisional ) { return null; }

		# ok, no legit ones,

		$sql = "
SELECT 
	syllabus_id
FROM 
	NEWSYL_MAIN
WHERE 
	provisional_code = '$code' AND
	provisional_session = '$session' 
";

		$rows = ECS_queryFetchFree($sql);
		if( sizeof( $rows ) ) 
		{
			return new UoS_Syllabus( $rows[0]["syllabus_id"] );
		}
		return null;
	}

	static function create( $cause="script", $session=null, $code=null )
	{
		ECS_query("BEGIN TRAN"); 
		ECS_resetSQLok();

		$check_fields = array("library_checked","director_checked","review_checked" );
		$checks = array( 1,0,1);

		$esc_fields = array_merge( array( "provisional_session", "provisional_code" ), $check_fields );
		$esc_values = array_merge( array( UoS_Syllabus::esc( $session ), UoS_Syllabus::esc( $code ) ), $checks );
		$sql = "INSERT INTO NEWSYL_MAIN ( ".join( ", ", $esc_fields )." ) VALUES ( ".join( ", ", $esc_values ).")";
		ECS_query($sql);

                $sql = 'SELECT @@Identity as ident';
                $row = ECS_queryFetchFreeOne($sql);
                $id = $row['ident'];

		
		$esc_fields = array_merge( array( "syllabus_id","cause","timestamp","summary"), $check_fields );
		$esc_values = array_merge( array( $id, UoS_Syllabus::esc($cause), "{ fn NOW() }", "'Created'" ), $checks );
		$sql = "INSERT INTO NEWSYL_CHANGES ( ".join( ", ", $esc_fields )." ) VALUES ( ".join( ", ", $esc_values ).")";
		ECS_query($sql);


		if(!ECS_SQLok() ) { $errors[]= "SQL Error"; }
		if( sizeof( $errors ) == 0 )
		{
        		ECS_query("COMMIT TRAN");
			ignore_user_abort(0);
			return $id;
		} 
	
        	ECS_query("ROLLBACK TRAN");
		ignore_user_abort(0);
		throw new Exception( join( ", ", $errors ));
		return null;
	}

	static function esc( $value )
	{
		if( $value == null ) { return "NULL"; }
		return "'".ECS_dbEscape( $value )."'";
	}
}

