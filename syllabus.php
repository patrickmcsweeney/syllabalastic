<?php

class Model_Syllabus extends RedBean_SimpleModel {

	private $MAIN_TEXT_FIELDS = array( 
		"topics", "learning_outcomes" ,"introduction", "provisional_notes", "assessment_notes", "timetable_notes"
	);

	private $REFERRAL_OPTIONS = array(
		"100EXAM"=> "By examination",
		"EXAM"=>"By examination, with the original coursework mark being carried forward",
		"EXAMCWORK"=> "By examination and a new coursework assignment",
		"CWORK"=>"By set coursework assignment(s)",
		"LAB"=> "By means of a special one-day laboratory session",
		"REWRITE"=>"By re-write of the project report and re-viva (the original progress report mark will be carried forward)",
		"NONE"=>"There is no referral opportunity for this syllabus in same academic year",
		"NOTES"=>"See notes below" 
	);

	private $SA_TYPES = array( 
		""=>"",
		"lecture"=>"Lecture",
		"examples"=>"Examples Class",
		"tutorial"=>"Tutorial",
		"computer_lab"=>"Computer Lab",
		"specialist_lab"=>"Specialist Lab",
		"field_trip"=>"Field Trip" 
	);

	private $SA_DURATIONS = array( 
		""=>"",
		"1"=>"1 hour", "2"=>"2 hours", "3"=>"3 hours", "4"=>"4 hours", "5"=>"5 hours",
		"6"=>"6 hours", "7"=>"7 hours", "8"=>"8 hours", "9"=>"9 hours", "10"=>"10 hours",
		"11"=>"11 hours", "12"=>"12 hours", "13"=>"13 hours", "14"=>"14 hours", "15"=>"15 hours",
		"16"=>"16 hours", "17"=>"17 hours", "18"=>"18 hours", "19"=>"19 hours", "20"=>"20 hours" 
	);

	private $SA_FREQUENCIES = array( 
		""=>"",
		"1/week" => "Once per week",
		"2/week" => "Twice per week",
		"3/week" => "Three times per week",
		"4/week" => "Four times per week",
		"1/2week" => "Once per fortnight",
		"once" => "Once in syllabus",
	);

	private $ASSESSMENT_TYPES = array(
		#"exam" => "Exam", # this is now covered by its own assessment type
		"labs" => "Labs",
		"cwork" => "Coursework",
		"other" => "Other",
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
		'other'=>"Other resource requirements" 
	);

	private $CHANGE_SCALE = array(
		"cosmetic"=>"Purely Cosmetic",
		"minor"=>"Minor",
		"major"=>"Major", 
	);

	private $SEMESTER_TYPES = array(
		""=>"",
		"S1"=>"Semester 1",
		"S2"=>"Semester 2",
		"1"=>"Full Academic Year",
		"NS"=>"Non-Standard",
		"T1"=>"Term 1",
		"T2"=>"Term 2",
		"T3"=>"Term 3",
		"S3"=>"Semester 3", 
	);



	public function canEdit()
	{
		return true;
	}
	
	public function requiresReview()
	{
		if($this->isprovisional)
		{
			return true;
		}
	}
	
	public function canBeReviewedBy($user)
	{
		return $user->can_review($this);
	}

	public function renderForm($flags=array())
	{
		$form = $this->getForm($flags);
		$defaults = $this->getData();
		if(array_key_exists('passback', $flags))
		{
			$defaults['passback'] = $flags['passback'];
		}
		if(array_key_exists('secret', $flags))
		{
			$defaults['secret'] = $flags['secret'];
		}
		return $form->render($defaults);
		#return $this->getForm($flags)->render();
	}

	public function fromForm($flags=array())
	{
		$data = array();
		#echo "<pre>",htmlentities(print_r($_POST, true)),"</pre>";
		$this->getForm($flags)->fromForm( $data, $_POST );
		$module_info = @$data["module"];
		unset( $data["module"] );
		if( $this->module->isprovisional )
		{
			$this->module->title = $module_info["provisionaltitle"];
			$this->module->provisionaltitle = $module_info["provisionaltitle"];

			$this->module->credits = $module_info["provisionalcredits"];
			$this->module->provisionalcredits = $module_info["provisionalcredits"];

			$this->module->provisionalcode = $module_info["provisionalcode"];

			$this->module->provisionalsemestercode = $module_info["provisionalsemestercode"];
			$this->module->semestercode = $module_info["provisionalsemestercode"];
			$this->module->semestername = @$this->SEMESTER_TYPES[ $module_info["provisionalsemestercode"] ];

			$this->module->provisionalnotes = $module_info["provisionalnotes"];
		}
		$this->setData($data);
		return $data;
	}

	public function issues()
	{
		$issues = array();
		return $issues;
	}

	public function setData($data)
	{
		
		foreach( $data as $field => $value )
		{       
			if(! is_array($value))
			{
				$this->$field = $value;
				continue;
			}

			$sub_objects = array();
			foreach( $value as $sub_object_in_array )
			{
				$sub_object = R::dispense($field);
				foreach( $sub_object_in_array as $sub_field => $sub_value)
				{
					$sub_object->$sub_field = $sub_value;
				}
				$sub_objects[] = $sub_object;
			}
			$field_name = "own".ucfirst($field);
			$this->$field_name = $sub_objects;

		}
	}

	public function getData(){
		$sub_objects = array("regularteaching", "resources", "exam", "continuousassessment" );
		$data = $this->unbox()->export();
		foreach($sub_objects as $sub_object)
		{
			$property_name = "own".ucfirst($sub_object);
			$data[$sub_object] = R::exportAll($this->$property_name);
		}
		return $data;
	}
	
	public function getConstant( $constant_name )
	{
		foreach(array_keys(get_class_vars(__CLASS__)) as $key)
		{
			$object_constant = $this->$key;
			if(array_key_exists($constant_name, $object_constant))
			{
				return $object_constant[$constant_name];
			}
		}
	}
	public function getForm( $flags = array() )
	{
		$url = "http";
		if(isset($_SERVER['HTTPS']))
		{
			$url .= "s";
		}
		
		$url .= "://".$_SERVER['HTTP_HOST'];
		$params = array( "heading"=>1, "resourcesURL"=>"$url/html_assets/floraform" ); 
		$action = "$url/save/syllabus/".$this->id;
		$params["action"] = $action;
		$form = new FloraForm($params);

		if(array_key_exists('passback', $flags))
		{
			$form->add( "HIDDEN", array( "id"=>"passback"));
		}

		if(array_key_exists('secret', $flags))
		{
			$form->add( "HIDDEN", array( "id"=>"secret"));
		}

		# Section 1.
		if( ! $this->module->isprovisional )
		{
			$intro = $form->add( "SECTION", array( 
				"title" => "Basic information", 
				"layout" => "section" ));
			$syllabus_data = "";
			$syllabus_data.= "<p>Title: ".htmlentities($this->module->title)."</p>";
			$syllabus_data.= "<p>Code and Session: ".htmlentities($this->module->code)." (".$this->module->session.")</p>";
			$syllabus_data.= "<p>Credits: ".htmlentities($this->module->credits)."</p>";
			$syllabus_data.= "<p>".htmlentities($this->module->semestername)."</p>";

			$semesters = array();
			if( $this->semester_1 ) { $semesters []= "1"; }
			if( $this->semester_2 ) { $semesters []= "2"; }
			#$syllabus_data.= "<p>Semester: ".join( " &amp; ", $semesters )."</p>";
			
			$intro->add( "INFO", array( 
				"layout" => "section",
				"content_html" => $syllabus_data ));
			$intro->add( "HIDDEN", array( "id"=>"provisionaltitle"));
			$intro->add( "HIDDEN", array( "id"=>"provisionalcode",));
			$intro->add( "HIDDEN", array( "id"=>"provisionalsession",));
			$intro->add( "HIDDEN", array( "id"=>"provisionalsemester",));
			$intro->add( "HIDDEN", array( "id"=>"provisionalnotes",));
			$intro->add( "HIDDEN", array( "id"=>"provisionalcredits",));
		}
		else
		{
			$intro = $form->add( "SECTION", array( 
				"title" => "Provisional Module Description",
				"layout" => "section" ));
			$intro->add( "INFO", array( 
				"layout" => "section",
				"content_html" => "<p>This syllabus description has not yet been formally linked with a module code.</p>" ));

			$mod_combo = $intro->add( "COMBO", array(
				"id"=>"module",
			));
			$mod_combo->add( "TEXT", array( 
				"id"=>"provisionaltitle",
				"title"=>"Module Title",
				"layout"=>"vertical"
			));
			$mod_combo->add( "TEXT", array( 
				"id"=>"provisionalcode",
				"title"=>"Module Code",
				"layout"=>"vertical"
			));
			$mod_combo->add( "CHOICE", array( 
				"id"=>"provisionalsemestercode",
				"choices" => $this->SEMESTER_TYPES,
				"mode" => "pull-down",
				"title"=>"Semester",
				"layout"=>"vertical"
			));
			$mod_combo->add( "TEXT", array( 
				"id"=>"provisionalcredits",
				"title"=>"Credits",
				"layout"=>"vertical"
			));
			$mod_combo->add( "HTML", array( 
				"id"=>"provisionalnotes",
				"title"=>"Notes",
				"description"=>"
Notes on this provisional module. These will not be visible to students.",
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
	This section should be used to give a summary of the syllabus, its aims, and (for core / compulsory syllabuss) how it fits in with the programme as a whole or (for optional syllabuss) why students might choose to take it. You can also give a general indication of pre-requisite knowledge and skills which are assumed.
	",
		));

		$s2->add( "HTML", array( 
			"layout" => "section",
			"id" => "learningoutcomes", # TODO change field name
			"title" => "1.2 Learning Outcomes",
			"rows" => 10,
			"description" => "
	This section should be used to list the intended learning outcomes of the syllabus. You can refer to <a href='https://sharepoint.soton.ac.uk/sites/ese/quality_handbook/default.aspx'>guidance in the quality handbook</a> for advice on these. For a standard 15 credit syllabus, 5 to 8 outcomes should be sufficient. Please do not repeat the list of topics for the syllabus, which are given in the following section.
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
			"id" => "regularteaching",
			"layout" => "section",
			"title" => "2.1 Scheduled Teaching Activities",
			"description" => "This section allows you to provide data for timetabling purposes and key information sets. For each scheduled activity, please indicate the nature of the activity, its duration, and its frequency (ie the number of sessions).  If the class divides into groups for this activity, please give the (maximum) group size; in this case, the frequency should be given from the student perspective. Otherwise, leave this field blank. ") )->setListType( "COMBO" );
		$reg_combo->add( "CHOICE", array(
			"id" => "activitytype",
			"title" => "Type",
			"choices" => $this->SA_TYPES,
			"mode" => "pull-down" ) );
		$reg_combo->add( "TEXT", array( 
			"id" => "groupsize",
			"size" => 2,
			"title" => "Maximum Group Size" ));
		$reg_combo->add( "TEXT", array(
			"id" => "studenthours",
			"title" => "Hours per semester a student will spend on this activity",
			"size"=> 2 ) );
#TODO upgrade to new Flora form so we dont have to add this little hack in textarea should render its own title.
		$reg_combo->add( "TEXTAREA", array(
			"id" => "teachingdescription",
			"rows" => "2",
			"layout" => "block",
			"title" => "Description" ));
# too complicated and not flexible enough for what people want to be able to say
#		$reg_combo->add( "CHOICE", array(
#			"id" => "duration",
#			"title" => "Duration",
#			"choices" => $this->SA_DURATIONS,
#			"mode" => "pull-down" ) );
#		$reg_combo->add( "CHOICE", array(
#			"id" => "frequency",
#			"title" => "Frequency",
#			"choices" => $this->SA_FREQUENCIES,
#			"mode" => "pull-down" ) );
		$exam_combo = $s1->add( "LIST", array( 
			"id" => "exam",
			"layout" => "section",
			"title" => "2.2 Examination",
			"description" => "
	This section is required for key information sets. Note that the total percentages across examination and other assessment activities (below) should add up to 100. For an exam, give the planned duration. </i>
	" ) )->setListType( "COMBO", array( "layout" ));
		$exam_combo->add( "TEXT", array(
			"id" => "percent",
			"size" => 1,
			"suffix" => "%",
			"title" => "Total Percentage" ));
		$exam_combo->add( "TEXT", array(
			"id" => "examduration",
			"size" => 1,
			"suffix"=>" hours",
			"title" => "Exam Duration" ));


		$ass_combo = $s1->add( "LIST", array( 
			"id" => "continuousassessment",
			"layout" => "section",
			"title" => "2.2 Other Assessment",
			"description" => "
	This section allows you to provide data for student workload monitoring, and key information sets. Note that the total percentages across all assessment activities and examination (above) should add up to 100. Please indicate the week or weeks of the semester assessment is planed to occur. Week 1 is the start of teaching, and week 12 is the last week before exams, which is typically reserved for revision. Note that assignment deadlines should therefore not occur during weeks 12 to 15.  Finally, indicate when and how you will provide feedback on assignments -- for example, you might state that <i>after 2 weeks individual feedback sheets will be returned, and a generic feedback will be provided in-class.</i>
	" ) )->setListType( "COMBO", array( "layout" ));
		$ass_combo->add( "CHOICE", array(
			"id" => "type",
			"layout" => "vertical",
			"title" => "Type",
			"choices" => $this->ASSESSMENT_TYPES,
			"lots-of-class" => true,
			"mode" => "radio" ) );
		

		$ass_combo->add( "TEXT", array(
			"id" => "description",
			"layout" => "vertical",
			"title" => "Description" ));
#		$ass_combo->add( "TEXT", array(
#			"id" => "frequency",
#			"size" => 3,
#			"layout" => "vertical2up",
#			"title" => "Frequency" ));
		$ass_combo->add( "TEXT", array(
			"id" => "weeknos",
			"title" => "Week no(s)" ));
		$ass_combo->add( "TEXT", array(
			"id" => "percent",
			"size" => 3,
			"suffix" => "%",
			"title" => "Total Percentage" ));
	#		$ass_combo->add( "TEXT", array(
	#			"id" => "notes",
	#			"title" => "Notes" ));

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
	Each syllabus must have a defined referral policy, which must apply to all students who refer.
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
			"id" => "assessmentnotes",
			"title" => "2.4 Assessment Notes",
			"description" => "
	If there are special aspects related to assessment, please state them here.  As one possible example, <i>where there are multiple worksheets, the best 8 out of 10 marks will be taken; or if a minimum attendance of 8 out of 10 laboratory sessions is required before a mark can be returned.</i>  Finally, if there is a field trip, please state the arrangements and cost implications.
	",
			"layout" => "section",
		));
		$s1->add( "HTML", array( 
			"id" => "timetablenotes",
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
		$s4->add( "TEXTAREA", array(
			"id" => "changessummary",
			"rows"=>5,
			"description" => "A summary of these changes, and why they were made. If this was in response to student comments, please quote some of them, or the link to the questionnaire data.",
			"layout"=>"section" ));

		$form->add( "HIDDEN", array( 
			"id" => "syllabusid",
		));
		$form->add( "SUBMIT", array( 
			"text" => "Save Changes",
		));


		return $form;	
	}

	function isCurrent()	
	{
		$module = $this->module;
		if( !$module ) { return false; }
		$current = $module->getCurrent();
		if( !$current ) { return false; }
		return ( $this->id == $current->id );
	}
}
