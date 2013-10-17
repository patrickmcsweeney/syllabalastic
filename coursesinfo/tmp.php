<?php
$MAIN_TEXT_FIELDS = array( 

"topics", "learning_outcomes" ,"introduction", "provisional_notes", "assessment_notes", "timetable_notes"
   );

$REFERRAL_OPTIONS= array(
	"100EXAM"=> "by examination.",
	"EXAM"=>"by examination, with the original coursework mark being carried forward.",
	"EXAMCWORK"=> "by examination and a new coursework assignment.",
	"CWORK"=>"by set coursework assignment(s).",
	"LAB"=> "by means of a special one-day laboratory session.",
	"REWRITE"=>"by re-write of the project report and re-viva (the original progress report mark will be carried forward).",
	"NONE"=>"There is no referral opportunity for this module in same academic year",
	"NOTES"=>"See notes below" );

$SA_TYPES = array( 
	""=>"",
	"lecture"=>"Lecture",
	"examples"=>"Examples Class",
	"tutorial"=>"Tutorial",
	"computer_lab"=>"Computer Lab",
	"specialist_lab"=>"Specialist Lab",
	"field_trip"=>"Field Trip" );
$SA_DURATIONS = array( 
	""=>"",
	"1"=>"1 hour", "2"=>"2 hours", "3"=>"3 hours", "4"=>"4 hours", "5"=>"5 hours",
	"6"=>"6 hours", "7"=>"7 hours", "8"=>"8 hours", "9"=>"9 hours", "10"=>"10 hours",
	"11"=>"11 hours", "12"=>"12 hours", "13"=>"13 hours", "14"=>"14 hours", "15"=>"15 hours",
	"16"=>"16 hours", "17"=>"17 hours", "18"=>"18 hours", "19"=>"19 hours", "20"=>"20 hours" );
$SA_FREQUENCIES = array( 
	""=>"",
	"1/week" => "Once per week",
	"2/week" => "Twice per week",
	"3/week" => "Three times per week",
	"4/week" => "Four times per week",
	"1/2week" => "Once per fortnight",
	"once" => "Once in module",
);

$ASSESSMENT_TYPES = array(
	"exam" => "Exam",
	"other" => "Other",
	"labs" => "Labs",
	"cwork" => "Coursework",
);

$RESOURCE_TYPES = array(
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

$CHANGE_SCALE = array(
	"cosmetic"=>"Purely Cosmetic",
	"minor"=>"Minor",
	"major"=>"Major", );

function render()
{
	$html = array();
	$html[]= "<article>";
	$html[]="<h1>".$this->title()."</h1>";
	if( array_key_exists( "code", $this->module ) )
	{
		$html []= "<p>Module: ".htmlspecialchars($this->module["code"])." (".COURSES_SessionName( $this->module["session"] ).")</p>";
		$semesters = array();
		if( $this->module["semester_1"] ) { $semesters []= "1"; }
		if( $this->module["semester_2"] ) { $semesters []= "2"; }
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

