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

	private $REPEAT_YEAR = array(
		"internal"=>"Repeat year internally",
		"external"=>"Repeat year externally",
	);


	private $SA_TYPES = array( 
		""=>"",
		"lecture"=>"Lecture",
		"seminar"=>"Seminar",
		"tutorial"=>"Tutorial",
		"computer_lab"=>"Computer Lab",
		"specialist_lab"=>"Specialist Lab",
		"project_supervision"=>"Project supervision",
		"field_trip"=>"Fieldwork",  
		"examples"=>"Demonstration or Examples Session"
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
		'journals'=>"Journals",
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
	public $OUTCOME_TYPES = array(
		""=>"",
		"aim"=>"Aim",
		"knowledge"=>"Knowledge and Understanding",
    		"subjectintelectual"=>"Subject Specific Intellectual",
    		"transferable"=>"Transferable and Generic",
 		"subjectpractical"=>"Subject Specific Practical",
		"disciplinespecific"=>"Disciplinary Specific",
	);

	public $GRADUATE_ATTRIBUTES = array(
		"globalcitizenship"=>"Global Citizenship",
		"ethicalleadership"=>"Ethical Leadership",
		"researchandinquiry"=>"Research and Inquiry",
		"academic" => "Academic",
		"communcicationskills"=>"Communication Skills",
		"reflectivelearner"=>"Reflective Learner",
	);
	public $LEVEL_CODES = array(
		"UG" => "Undergraduate",
		"PC" => "Postgraduate taught",
		"PR" => "Postgraduate research",
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
		#print_r($defaults);exit;
		return $form->render($defaults);
		#return $this->getForm($flags)->render();
	}

	public function fromForm($flags=array())
	{
		$data = array();
		#echo "<pre>",htmlentities(print_r($_POST, true)),"</pre>";
		$this->getForm($flags)->fromForm( $data, $_POST );
		#print_r($data);exit;
		$module_info = @$data["module"];
		unset( $data["module"] );
		if( $this->module->isprovisional )
		{
			$this->module->title = $module_info["provisionaltitle"];
			$this->module->provisionaltitle = $module_info["provisionaltitle"];

			$this->module->credits = $module_info["provisionalcredits"];
			$this->module->provisionalcredits = $module_info["provisionalcredits"];

			$this->module->code = $module_info["provisionalcode"];
			$this->module->provisionalcode = $module_info["provisionalcode"];

			$this->module->provisionalsemestercode = $module_info["provisionalsemestercode"];
			$this->module->semestercode = $module_info["provisionalsemestercode"];
			$this->module->semestername = @$this->SEMESTER_TYPES[ $module_info["provisionalsemestercode"] ];

			$this->module->provisionalreqs = $module_info["provisionalreqs"];
			$this->module->provisionalprogs = $module_info["provisionalprogs"];
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
			
			$field_name = "own".ucfirst($field);
			$this->$field_name = array();
			$sub_objects = array();
			if($field=='graduateattributes'){

				foreach($value as $attribute)
				{
					$sub_object = R::dispense($field);
					$sub_object->$field = $attribute;
					$sub_objects[] = $sub_object;
						
				}
				$this->$field_name = $sub_objects;
				continue;
 
			}
			if($field=='repeatyear'){

				$sub_opbjects = array();
				foreach($value as $attribute)
				{
					$sub_object = R::dispense($field);
					$sub_object->$field = $attribute;
					$sub_objects[] = $sub_object;
						
				}
				$this->$field_name = $sub_objects;
				continue;
 
			}

			foreach( $value as $sub_object_in_array )
			{
				$sub_object = R::dispense($field);
				foreach( $sub_object_in_array as $sub_field => $sub_value)
				{
					$sub_object->$sub_field = $sub_value;
				}
				$sub_objects[] = $sub_object;
			}
			$this->$field_name = $sub_objects;

		}
	}
	
	public function getData(){
		$sub_objects = array("regularteaching", "resources", "exam", "continuousassessment", "itemisedlearningoutcomes" );
		$data = $this->unbox()->export();
		foreach($sub_objects as $sub_object)
		{
			$property_name = "own".ucfirst($sub_object);
			$data[$sub_object] = R::exportAll($this->$property_name);
		}
		
		$attributes = array();
		foreach($this->ownGraduateattributes as $attribute)
		{
			$attributes[] = $attribute->graduateattributes;
		}
		$data['graduateattributes'] = $attributes;
		
		$repeat_year = array();
		foreach($this->ownRepeatyear as $repeat)
		{
			$repeat_year[] = $repeat->repeatyear;
		}
		$data['repeatyear'] = $repeat_year;

		return $data;
	}
	
	public function getLearningOutcomes()
	{

		# this is just a convenience method for rendering in categories
		# it plays on phps ordering in associative arrays which chris doesnt like but until it stops working its staying this way :-P
		$outcomes = array();
		foreach($this->OUTCOME_TYPES as $key => $val)
		{
			$outcomes[$key] = array();
		}

		foreach($this->ownItemisedlearningoutcomes as $outcome)
		{
			$outcomes[$outcome->outcometype][] = $outcome->outcome;
		}

		return $outcomes;


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
		$intro = "";
		# Section 1.
		if( ! $this->module->isprovisional )
		{
			$intro = $form->add( "SECTION", array( 
				"title" => "Module information", 
				"layout" => "section" ));
			$syllabus_data = "";
			$syllabus_data.= "<p>Title: ".htmlentities($this->module->title)."</p>";
			$syllabus_data.= "<p>Code and Session: ".htmlentities($this->module->code)." (".$this->module->session.")</p>";
			$syllabus_data.= "<p>ECTS Credits: ".htmlentities($this->module->credits)."</p>";
			$syllabus_data.= "<p>".htmlentities($this->module->semestername)."</p>";

			$semesters = array();
			if( $this->semester_1 ) { $semesters []= "1"; }
			if( $this->semester_2 ) { $semesters []= "2"; }
			#$syllabus_data.= "<p>Semester: ".join( " &amp; ", $semesters )."</p>";
			
			$intro->add( "INFO", array( 
				"layout" => "section",
				"content_html" => $syllabus_data ));
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
			$mod_combo->add( "TEXT", array( 
				"id"=>"provisionalmodulelead",
				"title"=>"Staff id of module lead",
				"layout"=>"vertical",
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
				"title"=>"ECTS Credits",
				"layout"=>"vertical",
			));
			$mod_combo->add( "TEXT", array( 
				"id"=>"provisionalmodulereplacement",
				"title"=>"Replacement module (specify module which can be discontinued if applicable)",
				"layout"=>"vertical",
			));
			$mod_combo->add( "TEXT", array( 
				"id"=>"provisionalexternalexaminer",
				"title"=>"Name of external examiner",
				"layout"=>"vertical",
			));
			$mod_combo->add( "TEXT", array( 
				"id"=>"provisionallevel",
				"title"=>"Level (4,5,6,7,8 in the FHEQ)",
				"layout"=>"vertical",
			));
			$mod_combo->add( "HTML", array( 
				"id"=>"provisionalprogs",
				"title"=>"Programmes",
				"description"=>"To which degree programmes should this module be offered? If possible specify core/compulsory/option.",
				"layout"=>"section"
			));
			$mod_combo->add( "HTML", array( 
				"id"=>"provisionalreqs",
				"title"=>"Pre-requisites",
				"description"=>"What are the pre-requisites and exclusions for this module? ",
				"layout"=>"section"
			));
			$mod_combo->add( "HTML", array( 
				"id"=>"provisionalnotes",
				"title"=>"Notes",
				"description"=>"Other notes on this provisional module. ",
				"layout"=>"section"
			));
		}

		$intro->add( "TEXT", array( 
				"id"=>"moduleleadprofileurl",
				"title"=>"Module Lead Profile URL",
				"layout"=>"vertical",
			));

		$intro->add( "TEXT", array( 
				"id"=>"externalexaminer",
				"title"=>"External Examiner",
				"layout"=>"vertical",
			));


		$s2 = $form->add( "SECTION", array(
			"title" => "Description",
			));
		$s2->add( "HTML", array( 
			"layout" => "section",
			"id" => "introduction", # TODO change field name
			"title" => "Module overview",
			"rows" => 10,
			"description" => "
	This section should be used to give a summary of the syllabus, its aims, and (for core / compulsory modules) how it fits in with the programme as a whole or (for optional modules) why students might choose to take it. You can also give a general indication of pre-requisite knowledge and skills which are assumed.
	",
		));

#		$s2->add( "HTML", array( 
#			"layout" => "section",
#			"id" => "learningoutcomes", # TODO change field name
#			"title" => "1.2 Learning Outcomes",
#			"rows" => 10,
#			"description" => "
#	This section should be used to list the intended learning outcomes of the syllabus. You can refer to <a href='https://sharepoint.soton.ac.uk/sites/ese/quality_handbook/default.aspx'>guidance in the quality handbook</a> for advice on these. For a standard 15 credit syllabus, 5 to 8 outcomes should be sufficient. Please do not repeat the list of topics for the syllabus, which are given in the following section.
#	",
#		));
		$learningitems = $s2->add( "LIST", array( 
			"id" => "itemisedlearningoutcomes",
			"layout" => "section",
			"min-items" => 5,
			"title" => "Aims and Learning Outcomes",
			"description_html" => '
<p>This section should be used to list the intended learning outcomes of the syllabus. You can refer to <a href="http://www.southampton.ac.uk/quality/">guidance in the quality handbook</a> for advice on these. For a standard 7.5 ECTS syllabus, 5 to 8 outcomes should be sufficient. Please do not repeat the list of topics for the syllabus, which are given in the following section.</p>
<div class="deprecated">
<h4>Your previous learning outcomes were</h4>
'.$this->learningoutcomes.'
</div>

<p>Having successfully completed this module, you will be able to:</p>' ) 
		)->setListType( "COMBO", array( "layout" ));

		$learningitems->add("CHOICE", array(
			"id" => "outcometype",
			"title"=>"Outcome Type",
			"mode" => "pull-down",
			"choices" => $this->OUTCOME_TYPES,
		));
		$learningitems->add("TEXT", array(
			"id" => "outcome",
			"size"=>"60",
		));
		$s2->add("INFO", array("description_html"=>"<p>Knowledge and Understanding learning outcomes should be written
    as noun phrases (for example, 'the relationship between English and
    French realism')</p>


    <p>All other learning outcomes should be written as verb phrases (for
    example, 'compare different narrative modes').</p>
"));
		$s2->add("INFO", array(
			"id" => "graduateattributes",
			"title"=>"Graduate Attributes",
			"choices" => $this->GRADUATE_ATTRIBUTES,
			"description_html" => "<p>Graduate Attributes are the personal qualities, skills and understandings that University of Southampton students have the opportunity to develop. They include but extend beyond subject-specific knowledge of an academic discipline and its technical proficiencies. The Graduate Attributes are achieved through the successful attainment of the learning outcomes of the programmes, and successful engagement with the University’s co-curriculum e.g. the Graduate Passport.</p>

        <p>A checklist for embedding the graduate attributes is available at: <a href='https://sharepoint.soton.ac.uk/sites/ese/quality_handbook/Handbook/Employability%20Statement.aspx'>https://sharepoint.soton.ac.uk/sites/ese/quality_handbook/Handbook/Employability%20Statement.aspx</a></p>
",
		));

		$s2->add( "HTML", array( 
			"layout" => "section",
			"id" => "topics", # TODO change field name
			"rows" => 10,
			"title" => "Summary of syllabus content",
			"description" => "A summary of contents covered, perhaps 10 to 20 bullet points." ) );

		$s2->add( "HTML", array( 
			"layout" => "section",
			"id" => "teachingandlearningtext", # TODO change field name
			"rows" => 10,
			"title" => "Summary of teaching and learning methods",
			"description" => "A free text summary of the teaching and learning methods used on this module. Please do not go into specific detail as this is covered in 'Breakdown of teaching and learning months' section" ) );



		### Assessment

		$s1 = $form->add( "SECTION", array(
			"title" => "Summary of Assessment and Feedback Methods",
			));


		$ass_combo = $s1->add( "LIST", array( 
			"id" => "continuousassessment",
			"layout" => "section",
			"title" => "Other assessment methods",
			"description_html" => "
	This section allows you to provide data for student workload monitoring, and key information sets. Note that the total percentages across all assessment activities and examination (above) should add up to 100. Please indicate the week or weeks of the semester assessment is planed to occur. Week 1 is the start of teaching, and week 12 is the last week before exams, which is typically reserved for revision. Note that assignment deadlines should therefore not occur during weeks 12 to 15.  Finally, indicate when and how you will provide feedback on assignments -- for example, you might state that <i>after 2 weeks individual feedback sheets will be returned, and a generic feedback will be provided in-class.</i>
	" ) )->setListType( "COMBO", array( "layout" ));
		$ass_combo->add( "CHOICE", array(
			"id" => "type",
			"layout" => "vertical",
			"title" => "Type",
			"choices" => $this->ASSESSMENT_TYPES,
			"lots-of-class" => true,
			"mode" => "radio" ) );
		

		$ass_combo->add( "TEXTAREA", array(
			"id" => "description",
			"layout" => "block",
			"title" => "Description" ));

		$ass_combo->add( "TEXT", array(
			"id" => "weeknos",
			"title" => "Week no(s)" ));
		$ass_combo->add( "TEXT", array(
			"id" => "percent",
			"size" => 3,
			"suffix" => "%",
			"title" => "Total Percentage" ));

		$ass_combo->add( "TEXTAREA", array(
			"id" => "feedback",
			"rows" => "3",
			"layout" => "block",
			"title" => "Feedback" ));

		$exam_combo = $s1->add( "LIST", array( 
			"id" => "exam",
			"layout" => "section",
			"title" => "Examination method",
			"description_html" => "
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
		$s1->add( "CHOICE", array( 
			"id" => "referral",
			"layout" => "section",
			"title" => "Referral Policy",
			"surround"=>"floraform/component_surround.htm",
			"description" => "
	Each syllabus must have a defined referral policy, which must apply to all students who refer.
	University policy requires that failure should be redeemable, so students need an opportunity to correct any failure (typically during the summer break, but possibly also within the academic year itself, or if neither of these is possible, then the following year).
	",
			"prefix" => "On referral, this unit will be assessed ",
			"choices" => $this->REFERRAL_OPTIONS,
			"mode" => "pull-down" ) );

		$s1->add( "MULTICHOICE", array( 
			"id" => "repeatyear",
			"title" => "Method of repeat year",
			"description" => "
	Indicate whether a module can be repeated externally, or if it is only possible to repeat internally by ticking the appropriate boxes.
	",
			"choices" => $this->REPEAT_YEAR,
			"mode" => "pull-down" ) );

		$s1->add( "HTML", array( 
			"id" => "assessmentnotes",
			"title" => "Assessment Notes",
			"description_html" => "
	If there are special aspects related to assessment, please state them here.  As one possible example, <i>where there are multiple worksheets, the best 8 out of 10 marks will be taken; or if a minimum attendance of 8 out of 10 laboratory sessions is required before a mark can be returned.</i>  Finally, if there is a field trip, please state the arrangements and cost implications.
	",
			"layout" => "section",
		));

		$s1->add( "HTML", array( 
			"id" => "referralnotes",
			"title" => "Referral Notes",
			"description_html" => "
	If there is specific information about the referral which is not captured above please state it here. For example <i>This module only refers the exam (50%) and 'Road surfaces essay'(50%) it does retake the lab work</i>. You may need to specify the new percentage values of the assessment components here.
	",
			"layout" => "section",
		));

		$s1->add( "HTML", array( 
			"id" => "specialfeatures",
			"title" => "Special Features",
			"description" => "
		State anything which makes this module special which students should be aware of when choosing it.
	",
			"layout" => "section",
		));




		$s3 = $form->add( "SECTION", array(
			"title" => "Resources"));
			
		$res_list = $s3->add( "LIST", array(
			"id" => "resources",
			"layout" => "section",
			));

		$software_combo = $form->factory("COMBO", array(
					"fields"=>array(
						array( "TEXT" => array(
								"id" => "title",
								"layout" => "block",
								"surround" => "software_surround.htm",
								"title" => "Software name (auto completes if currently offered)" ) ),
						array( "TEXT" => array(
								"id" => "version",
								"layout" => "block",
								"title" => "Prefered version" ) ),
						array( "TEXTAREA" => array(
								"description" => "Additional details which might be relevent",
								"id" => "details",
								"rows" => 3,
								"layout" => "block",
								"title" => "Additional notes" ) )
						)
				      ));
		$other_combo = $form->factory("COMBO", array(
					"fields"=>array(
						array( "TEXTAREA" => array(
								"id" => "details",
								"rows" => 3,
								"layout" => "block",
								"title" => "Details" ) )
						)
				      ));
		$book_combo = $form->factory("COMBO", array(
					"fields"=>array(
						array( "TEXT" => array(
								"id" => "isbn",
								"title" => "ISBN" ) ),	
						array( "HTML" => array(
								"id" => "details",
								"rows" => 2,
								"layout" => "block",
								"title" => "Details" ) )
						)
				      )
		     );
		$res_cond = $res_list->add( "CONDITIONAL", array(
					"conditions"=>array(
						array(
							"software",$software_combo
						), 
						array(
							"", $book_combo 
						)
					)
				));
		$res_cond->add( "CHOICE", array( 
			"id" => "type",
			"title" => "Type",
			"choices" => $this->RESOURCE_TYPES,
			"mode" => "pull-down" ) );



	#	$res_combo->add( "TEXT", array(
	#		"id" => "isbn",
	#		"title" => "ISBN" ) );	
	#	$res_combo->add( "HTML", array(
	#		"id" => "details",
	#		"rows" => 2,
	#		"layout" => "block",
	#		"title" => "Details" ) );	


		$s4 = $form->add( "SECTION", array(
			"title" => "3. Additional Information",
			));

		$s4->add( "HTML", array( 
			"id" => "costimplications",
			"title" => "4.1 Cost Implications",
			"description" => "
		Please list any cost implications to the student which are not covered by their tuition fees.
	",
			"layout" => "section",
		));

		$s4->add( "HTML", array( 
			"id" => "healthandsafety",
			"title" => "Health and Safety",
			"description" => "
		Please briefly describe any health and safety implications of this module.
	",
			"layout" => "section",
		));

		$s6 = $form->add( "SECTION", array(
			"title" => "KIS hours",
			));

		$reg_combo = $s6->add( "LIST", array( 
			"id" => "regularteaching",
			"layout" => "section",
			"title" => "Breakdown of teaching and learning methods",
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
		$s6->add( "HTML", array( 
			"id" => "timetablenotes",
			"title" => "Timetabling Requirements",
			"description" => "
	If there are special timetabling requirements, for example, a specific venue or specialist facilities are needed, please indicate these in this field.  This information is provided to the Central Timetabling Unit, and is not visible to students.
	",
			"layout" => "section",
		));


		$s5 = $form->add( "SECTION", array(
			"title" => "Changes",
			));
		$s5->add( "TEXTAREA", array(
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

	function kisContactHours()
	{
		$kis_contact_hours = array();
		$kis_contact_hours["Lectures"] = 0;
		$kis_contact_hours["Seminars (including sessions with outside speakers)"] = 0;
		$kis_contact_hours["Tutorials"] = 0;
		$kis_contact_hours["Practical Classes and Workshops (including Boat work)"] = 0;
		$kis_contact_hours["Project supervision"] = 0;
		$kis_contact_hours["Fieldwork"] = 0;
		$kis_contact_hours["Demonstration Sessions"] = 0;
		$kis_contact_hours["Supervised time in studios/workshops/laboratories"] = 0;
		$kis_contact_hours["External Visits"] = 0;
		$kis_contact_hours["Summer Workshops"] = 0;
		$kis_contact_hours["Work Based Learning"] = 0;
		$kis_contact_hours["Total"] = 0;

		foreach($this->ownRegularteaching as $teaching)
		{
			$type = $this->getConstant($teaching->activitytype);
			$key = "";
			if($type == "Lecture"){ $key = "Lectures"; }
			elseif($type == "Tutorial"){ $key = "Tutorials"; }
			elseif($type == "Computer Lab"){ $key = "Supervised time in studios/workshops/laboratories"; }
			else{   $key = $type; }
			if(array_key_exists($key, $kis_contact_hours))
			{
				$kis_contact_hours[$key] += $teaching->studenthours;
				$kis_contact_hours["Total"] += $teaching->studenthours;
			}
		}
		return $kis_contact_hours;
	}
	
	function kisIndependantHours()
	{
		$total = 0;
		$kis_contact_hours = $this->kisContactHours();
		$kis_independant_hours = array();
		$kis_independant_hours["Preparation for scheduled sessions"] = $kis_contact_hours["Lectures"]/2;
		$kis_independant_hours["Follow-up work"] = $kis_contact_hours["Lectures"]/2;

		$kis_independant_hours["Revision"] = 0;
		$total += $kis_contact_hours["Lectures"];
		$kis_independant_hours["Wider reading or practice"] = 0; 
		$kis_independant_hours["Completion of assessment task"] = 0;
		$kis_independant_hours["Placement Hours"] = 0;
		$kis_independant_hours["Year Placement"] = 0;
		$kis_independant_hours["6 Month Placement"] = 0;

		$revision_per_exam = 10;
		foreach($this->ownExam as $exam)
		{
			$kis_independant_hours["Revision"] += $revision_per_exam;
			$kis_independant_hours["Completion of assessment task"] += $exam->examduration;
			$total +=  $revision_per_exam + $exam->examduration;
		}

		foreach($this->ownContinuousassessment as $assessment)
		{
			// 140 not 150 because there should be at least 10 hours of independant study left at the end
			$remaining_time = 140 - ( $total + $kis_contact_hours["Total"] ) ;
			$assessment_hours = floor((intval(preg_replace('/[^0-9]*/', "", $assessment->percent))/100)*$remaining_time);
			$kis_independant_hours["Completion of assessment task"] += $assessment_hours;
			$total +=  $assessment_hours;
		}
		$kis_independant_hours["Wider reading or practice"] = 150 - ( $total + $kis_contact_hours["Total"] ) ;
		$total += $kis_independant_hours["Wider reading or practice"];
		$kis_independant_hours["Total"] = $total;
		return $kis_independant_hours;
	}
	
}
