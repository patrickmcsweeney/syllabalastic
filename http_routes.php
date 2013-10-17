<?php

function front_page()
{
	header("Location: /view/modules/201314");
}

function modules_by_year()
{

	#TODO this should be dynamic based on the date
	$modules = R::find('module', "session = ? ORDER BY code", array(F3::get('PARAMS["session"]')));
	
	$modules_by_faculty = array();
	foreach($modules as $module)
	{
		if(!array_key_exists($module->facultycode, $modules_by_faculty))
		{
			$modules_by_faculty[$module->facultycode]['name'] = $module->facultyname;
			$modules_by_faculty[$module->facultycode]['modules'] = array();
		}
		array_push($modules_by_faculty[$module->facultycode]['modules'], $module);
	}

	F3::set('title', 'Module list by course code');
	F3::set('modules', $modules_by_faculty);
	F3::set('userfacultycode', current_user()->facultycode);

	$content = Template::instance()->render("year.htm");
	$content .= Template::instance()->render("createmodule.htm");
	$content .= Template::instance()->render("modulesearch.htm");
	$content .= Template::instance()->render("modulelist.htm");
	F3::set('content', $content);
	echo Template::instance()->render("main.htm");
}

function themes() {
	#TODO this should be dynamic based on the date
	#$programs = R::find('program', "session = ?", array( "201213" ));
	$programs = R::find('program');

	F3::set('title', 'Programs and program themes');
	F3::set('programs', $programs);
	#F3::set('majors', $program->sharedMajor );
	$content = Template::instance()->render("programlist.htm");
	F3::set('content', $content);
	echo Template::instance()->render("main.htm");
}

function create_module() {
	authenticate(F3::get("PARAMS.0"));

	$input = F3::scrub($_POST);

	$existing_module = R::findOne("module", "session = ? AND code = ?", array( $input["session"], $input["modulecode"] ) );
	
	if(isset($existing_module)){
		error("A module with this code already exists");
		return;
	}

	$new_module = R::dispense("module");
	$new_module->code = $input["modulecode"];
	$new_module->session = $input["session"];
	$new_module->title = $input["moduletitle"];
	
	
	R::store($new_module);

	header("Location: /"); 
}

function create_specification() {
	authenticate(F3::get("PARAMS.0"));

	$input = F3::scrub($_POST);

	$theme = R::load("major", $input["majorid"] );
	
	if(!isset($theme)){
		error("This theme does not exist.");
		return;
	}
	if(isset($theme->specification)){
		error("This specification exists already - TODO maybe redirect this to edit?");
		return;
	}

	$specification = R::dispense("specification");
	$specification->major = $theme;
	$specification_id = R::store($specification);
	$theme->specification = $specification;
	
	R::store($theme);

	header("Location: /edit/specification/$specification_id"); 
}

function create_syllabus() {
	authenticate(F3::get("PARAMS.0"));

	$input = F3::scrub($_POST);
	
	if(!($input["session"] > key(date_as_session())))
	{
		#TODO MUST BE UNCOMMENTED 
		error("You cannot create syllabuses for the current or past sessions");
		return;
	
	}

	$existing_module = R::findOne("module", "session = ? AND code = ?", array( $input["session"], $input["modulecode"] ) );
	
	if(!isset($existing_module))
	{
		error("This module does not exists");
		return;
	}
	if(isset($existing_module->provisionalsyllabus))
	{
		error("This syllabus exists already - TODO maybe redirect this to edit?");
		return;
	}

	$syllabus = "";
	#print_r($existing_module->ownSyllabus);
	if($existing_module->ownSyllabus)
	{
		$current_syllabus = reset($existing_module->ownSyllabus);
		$syllabus = R::dup($current_syllabus);
	}
	else
	{

		$syllabus = R::dispense("syllabus");
	}

	$syllabus->module = $existing_module;
	$syllabus->isprovisional = true;
	$syllabus->isunderreview = false;
	$syllabus->educationboardreviewed = false;
	$syllabus->cqareviewed = false;
	$syllabus->courseleaderreviewed = false;
	$syllabus->quinquenialreviewed = false;
	$syllabus->reviewedby = "";
	$syllabus->approvalnote = "";
	$syllabus->timeapproved = null;
	$syllabus_id = R::store($syllabus);
	$existing_module->provisionalsyllabus = $syllabus;
	
	R::store($existing_module);
	
	if(valid_api_key(F3::get("REQUEST.apikey")))
	{
		echo serialize( array( 'provisionalsyllabusid', $syllabus_id) );
		return;
	}
		
	header("Location: /edit/syllabus/$syllabus_id"); 
}

function view_syllabus() {
	$syllabus = R::load("syllabus", F3::get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}
	$content = "";
	$module = $syllabus->module;
	F3::set("title", $module->code." ".$module->title." (".$module->session.")");
	F3::set("module", $module);
	F3::set("syllabus", $syllabus);
	if($syllabus->isprovisional){
		$content .= Template::instance()->render("provisional.htm");
	}
	$content .= Template::instance()->render("syllabus.htm");	
	F3::set("content", $content);
	echo Template::instance()->render("main.htm");
}

function json_syllabus() {
	$syllabus = R::load("syllabus", F3::get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}
	$module = array("module"=>$syllabus->module->export(), "syllabus"=>$syllabus->getData());
	echo json_encode($module);
}

function ecs_syllabus() {
	$existing_module = R::findOne("module", "session = ? AND code = ?", array( F3::get("PARAMS.session"), F3::get("PARAMS.modulecode") ) );
	if(!isset($existing_module))
	{
		echo json_encode(array("status"=>404));
		return;
	}
	if( ! $existing_module->ownSyllabus )
	{
		echo json_encode(array("status"=>404));
		return;

	}
	$syllabus = reset($existing_module->ownSyllabus);
	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}
	F3::set("syllabus", $syllabus);
	F3::set("module", $existing_module);
	$content = Template::instance()->render("syllabus_ecs.htm");
	$title = $existing_module->code.": ".$existing_module->title;
	$alias = "module/".$existing_module->code;
	$module = array("title"=>$title, "body"=>$content, "alias"=>$alias, "status"=>"200");
	echo json_encode($module);
}

function php_module() {
	$existing_module = R::findOne("module", "session = ? AND code = ?", array( F3::get("PARAMS.session"), F3::get("PARAMS.modulecode") ) );

	if(!isset($existing_module))
	{
		echo("This module does not exists");
		exit;
	}
	if( ! $existing_module->ownSyllabus )
	{
		echo("This module has no syllabus");
		exit;

	}
	$syllabus = reset($existing_module->ownSyllabus);

	if(!isset($syllabus) || !$syllabus->id)
	{
		echo("This syllabus does not exist");
		return;
	}
	$module = array("module"=>$existing_module->export(), "syllabus"=>$syllabus->getData());
	echo serialize($module);
}

function edit_syllabus() {
	global $API_KEYS;
	
	authenticate(F3::get("PARAMS.0"));

	$syllabus = R::load("syllabus", F3::get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}

	if(!$syllabus->isprovisional)
	{
		error("This syllabus is not provisional");
		return;
	}

	$module = $syllabus->module;

	R::store($module);
	F3::set('title', "Editing ".$module->code.": ".$module->title );
	F3::set("content", $syllabus->renderForm( ));
	echo Template::instance()->render("main.htm");
}

function save_syllabus(){
	authenticate(F3::get("PARAMS.0"));
	$syllabus = R::load("syllabus", F3::get('PARAMS["syllabus_id"]'));

	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}
	$data = $syllabus->fromForm();

	R::store($syllabus);
	if(F3::get('REQUEST.passback'))
	{
		header("Location: ".F3::get('REQUEST.passback'));
	}
	header("Location: /view/syllabus/".$syllabus->id);
}


function toreview_syllabus(){
	authenticate(F3::get("PARAMS.0"));
	$syllabus = R::load("syllabus", F3::get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}

	if(!$syllabus->canEdit())
	{
		error("You do not have permission to move this to review");
		return;
	}

	$syllabus->isunderreview = 1;

	R::store($syllabus);
	
	header( "Location: /" );
}


function review_syllabus(){
	authenticate(F3::get("PARAMS.0"));
	$syllabus = R::load("syllabus", F3::get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}
	
	if(!$syllabus->isunderreview)
	{
		error("This syllabus is not under review");
		return;
	}
	
	$user = current_user();
	if(!$syllabus->canBeReviewedBy($user))
	{
		error("You are not a reviewer for this syllabus");	
		return;
	}

	$module = $syllabus->module;
	$content = "";
	if($module->ownSyllabus)
	{
		$previous_syllabus = array_shift($module->ownSyllabus);
	}
	F3::set("title", "Reviewing ".$module->code.": ".$module->title." (".$module->session.") ");
	F3::set("module", $module);
	F3::set("syllabus", $syllabus);

	$review_tools = $syllabus->renderReviewTools();

	if(!isset($previous_syllabus)){
		$content .= Template::instance()->render("syllabus.htm");
	}else{
		F3::set("syllabus", $previous_syllabus);
		F3::set("current_syllabus", Template::instance()->render("syllabus.htm"));
		F3::set("syllabus", $syllabus);
		F3::set("provisional_syllabus", Template::instance()->render("syllabus.htm"));
		$content .= Template::instance()->render("comparesyllabuses.htm");
		
	}
	$content .= $review_tools;
	
	F3::set("content", $content);
	echo Template::instance()->render("main.htm");
}

function approve_syllabus(){
	authenticate(F3::get("PARAMS.0"));
	$syllabus = R::load("syllabus", F3::get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		error("This syllabus id does not exist");
		return;
	}

	#TODO need to check that the user is a quinquenial reviewer
	if($_POST["reviewtype"] == "quinquenial")
	{
		$syllabus->quinquenialreviewed = true;
	}

	#TODO need to check that the user is a quinquenial reviewer
	if($_POST["reviewtype"] == "courseleader")
	{
		$syllabus->courseleaderreviewed = true;
	}

	if($_POST["reviewtype"] == "cqa")
	{
		$syllabus->cqareviewed = true;
	}

	if($_POST["reviewtype"] == "educationboard")
	{
		$syllabus->educationboardreviewed = true;
	}

	R::store($syllabus);
	
	# this is far too complicated for users to actually do. bottom line is if CQA says its ok then it is
	#if($syllabus->quinquenialreviewed && $syllabus->courseleaderreviewed && $syllabus->cqareviewed && $syllabus->educationboardreviewed) 
	if($syllabus->cqareviewed) 
	{
		$user = current_user();
		$syllabus->isprovisional = 0;
		$syllabus->isunderreview = 0;
		$syllabus->timeapproved = time();
		$syllabus->approvedby = $user->username; 
		$syllabus->approvalnote = $_POST["approvalnote"]; 
		$module = $syllabus->module;
		unset($module->syllabus);
		$module->ownSyllabus = array($syllabus); 
		unset($module->provisionalsyllabus);
		R::store($module);
		
	}

	R::store($syllabus);

	header( "Location: /review/dashboard" );

}

function review_dashboard(){
	global $REVIEWERS;
	authenticate(F3::get("PARAMS.0"));

	$user = current_user();
	if(empty($REVIEWERS[$user->username]))
	{
		error("You are not registered as a module reviewer");
	}

	$syllabuses = R::find('syllabus', " isunderreview=1");
	R::preload($syllabuses, array("module"));
	usort($syllabuses, function($a, $b)
	{
		if(!$a->module || !$b->module){return 0;}
		return strcasecmp($a->module->code, $b->module->code);
	});

	$session=date_as_session(time()+365*24*60*60); //we review for next years modules not this years
	$modules_to_review="";
	$review_count = 0;
	foreach($REVIEWERS[$user->username] as $faculty_code){

		foreach($syllabuses as $syllabus)
		{	
			if(!$syllabus->module){continue;}
			$module = $syllabus->module;
			if(array_key_exists($module->session, $session) && $module->facultycode == $faculty_code)
			{	
				$review_count++;
				F3::set("module", $module);
				F3::set("syllabus", $syllabus);
				$modules_to_review .= Template::instance()->render("reviewmodulelistitem.htm");
			}
		}
	}

	$syllabuses = R::find('syllabus', " isprovisional=1 AND (isunderreview IS NULL OR isunderreview != 1)");
	R::preload($syllabuses, array("module"));
	usort($syllabuses, function($a, $b)
	{
		if(!$a->module || !$b->module){return 0;}
		return strcasecmp($a->module->code, $b->module->code);
	});
	
	$modules_to_submit = "";
	$submit_count = 0;
	foreach($REVIEWERS[$user->username] as $faculty_code){

		foreach($syllabuses as $syllabus)
		{	
			if(!$syllabus->module){continue;}

			if(array_key_exists($module->session, $session) && $syllabus->module->facultycode == $faculty_code)
			{
				$submit_count++;
				$module = $syllabus->module;
				F3::set("module", $module);
				F3::set("syllabus", $syllabus);
				$modules_to_submit .= Template::instance()->render("reviewmodulelistitem.htm");
			}
		}
	}

	F3::set("title", "Your Review Dashboard");
	F3::set("modules_to_review", $modules_to_review);
	F3::set("review_count", $review_count);
	F3::set("modules_to_submit", $modules_to_submit);
	F3::set("submit_count", $submit_count);
	F3::set("content", Template::instance()->render("reviewdashboard.htm"));
	echo Template::instance()->render("main.htm");
}

function login(){
}

function logout(){
	F3::set("SESSION.authenticated", false);
	header("Location: /");
}

function report_usage() {
	$report_start = strtotime("-1 month");	
	$report_end = time();
	if(F3::exists("REQUEST.report_start")){
		$report_start = strtotime(F3::get("REQUEST.report_start"));
	}
	if(F3::exists("REQUEST.report_end")){
		$report_end = strtotime(F3::get("REQUEST.report_end"));
	}

	$syllabuses = R::find('syllabus', " timeapproved > ? and timeapproved < ? ORDER BY timeapproved", array($report_start, $report_end));
	F3::set("title", "Usage report");
	F3::set("syllabuses", $syllabuses);
	F3::set("report_start", date("Y-m-d",$report_start));
	F3::set("report_end", date("Y-m-d",$report_end));
	F3::set("content", Template::instance()->render("report_usage.htm"));
	echo Template::instance()->render("main.htm");
}

?>
