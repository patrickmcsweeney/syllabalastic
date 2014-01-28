<?php


function error_page($f3)
{
	$f3->set('templates', array( "error.htm" ) );
	$title = $f3->get( "ERROR.code" )." ";
	$desc = array( 
		"403"=>"Forbidden",
		"404"=>"Not Found",
		"500"=>"Server Error",
	);
	if( @$desc[$f3->get( "ERROR.code" )] ) 
	{ 
		$title .= $desc[$f3->get( "ERROR.code" )];
	}
	else
	{
		$title .= "Error";
	}
	$f3->set('title', $title );
	if( $f3->get( "ERROR.code" ) == "404" )
	{
		$f3->set( "ERROR.text", "The page or resource you requested does not exist." );
	}

	# the stack traace seems to break the template engine!
	$f3->clear('ERROR.trace' );
	
	echo Template::instance()->render("main.htm");
	exit;
}
	

function front_page($f3)
{
	#syllabuses work one academic year ahead
	# this will probably blow everyones mind :-(
	$session_array = dates_as_sessions(strtotime("+1 year"));
	$next_year = key($session_array);
	header("Location: /view/modules/$next_year");
}


function modules_by_year($f3)
{
	$f3->set("years", dates_as_sessions(null,3)); #null defaults to this year
	$f3->set("selected_year", $f3->get("PARAMS.session"));
	$f3->set('title', 'Module list by course code');
	$templates = array('year.htm');
	
	$user = current_user($f3);
	$user->departmentcode = "FP";
	$f3->set('userdepartmentcode', $user->departmentcode);
	#TODO dont hard code the department code...
	if($f3->exists("REQUEST.allmodules") || !isset($user->departmentcode))
	{
		$modules = R::find('module', "session = ? ORDER BY code", array($f3->get('PARAMS.session')));
	}else{
		$templates[] = "seeallmodules.htm";
		$modules = R::find('module', "session = ? and departmentcode = ? ORDER BY code", array($f3->get('PARAMS.session'), $user->departmentcode));
	}
	
	$modules_by_faculty = array();
	foreach($modules as $module)
	{
		if(!array_key_exists($module->departmentcode, $modules_by_faculty))
		{
			$modules_by_faculty[$module->departmentcode]['name'] = $module->departmentname;
			$modules_by_faculty[$module->departmentcode]['modules'] = array();
		}
		array_push($modules_by_faculty[$module->departmentcode]['modules'], $module);
	}

	$f3->set('modules', $modules_by_faculty);

	#we can't create modules in the past!
	$current_year = dates_as_sessions();
	if($f3->get("PARAMS.session") > key($current_year)){
		array_push( $templates, 'createmodule.htm');
	}

	array_push( $templates, 'modulesearch.htm');
	array_push( $templates, 'modulelist.htm');
	$f3->set('templates', $templates);

	echo Template::instance()->render("main.htm");
}


function ecs_overviews($f3)
{
	$modules = R::find('module', "session = ? ORDER BY code", array($f3->get('PARAMS["session"]')));

	$modules_by_faculty = array();
	header("Content-type: text/plain" );
	foreach($modules as $module)
	{
		$syl = $module->getCurrent();
		if( $syl )
		{
			$modules_by_faculty[$module->facultycode][$module->code] = array(
				"code" => $module->code,
				"title" => $module->title,
				"introduction" => $syl->introduction );
		}
	}

	header("Content-type: text/plain" );
	echo json_encode($modules_by_faculty);
}


function themes($f3)
{
	#TODO this should be dynamic based on the date
	#$programs = R::find('program', "session = ?", array( "201213" ));
	$programs = R::find('program');

	$f3->set('title', 'Programs and program themes');
	$f3->set('programs', $programs);
	#$f3->set('majors', $program->sharedMajor );

	$f3->set('templates', array('programlist.htm'));
	echo Template::instance()->render("main.htm");
}

function create_module($f3)
{
	authenticate($f3);

	$input = $f3->scrub($_POST);

	$user = current_user($f3);
	$department_code = $user->departmentcode;

	$next_create_code = $department_code."Provisional000001";

	$last_created_module = R::findOne("module", "session = ? AND code like '%Provisional%' AND departmentcode = ? order by code DESC", array( $input["session"], $department_code ) );

	if(isset($last_created_module)){
		$next_create_code = $last_created_module->code;
		$next_create_code++;
	}

	$next_create_code = $next_create_code;

	$new_module = R::dispense("module");
	$new_module->code = $next_create_code; 
	$new_module->provisionalcode = $next_create_code;
	$new_module->session = $input["session"];
	$new_module->title = $input["moduleprefix"].$input["modulepart"]." - ".$input["moduletitle"];
	$new_module->provisionaltitle = $input["moduleprefix"].$input["modulepart"]." - ".$input["moduletitle"];
	$new_module->departmentcode = $department_code;
	$new_module->departmentname = $user->departmentname;
	$new_module->isprovisional = true;
	
	R::store($new_module);

	header("Location: /");
}

function create_specification($f3)
{
	authenticate($f3);

	$input = $f3->scrub($_REQUEST);

	$theme = R::load("major", $input["majorid"] );

	if(!isset($theme)){
		$f3->error( 404, "This theme does not exist.");
		return;
	}
	if(isset($theme->specification)){
		$f3->error( 500, "This specification exists already - TODO maybe redirect this to edit?");
		return;
	}

	$specification = R::dispense("specification");
	$specification->major = $theme;
	$specification_id = R::store($specification);
	$theme->specification = $specification;

	R::store($theme);

	header("Location: /edit/specification/$specification_id");
}

function create_syllabus($f3)
{
	authenticate($f3);

	$input = $f3->scrub($_REQUEST);
	
	$existing_module = R::findOne("module", "session = ? AND code = ?", array( $input["session"], $input["modulecode"] ) );

	if(!isset($existing_module))
	{
		$f3->error( 500, "There is no syllabus for this module in the central system.");
		return;
	}
	if(isset($existing_module->provisionalsyllabus))
	{
		$f3->error( 500, "This syllabus exists already - TODO maybe redirect this to edit?");
		return;
	}

	$syllabus = "";
	#print_r($existing_module->ownSyllabus);
	if($current_syllabus = $existing_module->getCurrent())
	{
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
	$syllabus->approvedby = "";
	$syllabus->approvedname = "";
	$syllabus->approvalnote = "";
	$syllabus->changessummary = ""; 	# deprecated
	$syllabus->timeapproved = null;
	$syllabus_id = R::store($syllabus);
	$existing_module->provisionalsyllabus = $syllabus;

	R::store($existing_module);

	if(valid_api_key($f3->get("REQUEST.apikey")))
	{
		echo serialize( array( 'provisionalsyllabusid', $syllabus_id) );
		return;
	}

	header("Location: /edit/syllabus/$syllabus_id");
}

function view_syllabus($f3)
{
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}
	$content = "";
	$module = $syllabus->module;
	$f3->set("title", $module->code." ".$module->title." (".$module->session.")");
	$f3->set("module", $module);
	$f3->set("syllabus", $syllabus);

	$templates = array();
	if($syllabus->isprovisional){
		$templates[] = 'provisional.htm';
	}
	$templates[] = 'syllabus.htm';

	$f3->set('templates', $templates);
	echo Template::instance()->render("main.htm");
}

function json_syllabus($f3)
{
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}
	$module = array("module"=>$syllabus->module->export(), "syllabus"=>$syllabus->getData());
	echo json_encode($module);
}

function ecs_syllabus($f3)
{
	$existing_module = R::findOne("module", "session = ? AND code = ?", array( $f3->get("PARAMS.session"), $f3->get("PARAMS.modulecode") ) );
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
	$syllabus = $existing_module->getCurrent();
	if(!$syllabus)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}
	foreach($syllabus->ownResources as $resource)
	{
		if($resource->type == "core")
		{
			$syllabus->hascore = true;
		}else{
			$syllabus->hasother = true;
		}
	}
	$f3->set("syllabus", $syllabus);
	$f3->set("module", $existing_module);
	$content = Template::instance()->render("syllabus_ecs.htm");
	$title = $existing_module->code.": ".$existing_module->title;
	$alias = "module/".$existing_module->code;
	$module = array("title"=>$title, "body"=>$content, "alias"=>$alias, "status"=>"200");
	echo json_encode($module);
}

function php_module($f3)
{
	$existing_module = R::findOne("module", "session = ? AND code = ?", array( $f3->get("PARAMS.session"), $f3->get("PARAMS.modulecode") ) );

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
	$syllabus = $existing_module->getCurrent();
	if(!$syllabus)
	{
		echo("This syllabus does not exist");
		return;
	}
	$module = array("module"=>$existing_module->export(), "syllabus"=>$syllabus->getData());
	echo serialize($module);
}

function edit_syllabus($f3)
{
	global $API_KEYS;

	authenticate($f3);

	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}

	if(!$syllabus->isprovisional)
	{
		$f3->error( 500, "This syllabus is not provisional");
		return;
	}

	$module = $syllabus->module;

	if(valid_api_key($f3->get("REQUEST.apikey")))
	{
		$secret = create_secret($f3->get("REQUEST.apikey"));
		echo serialize( $syllabus->renderForm(array("secret"=>$secret, "passback"=>$f3->get("REQUEST.passback"))));
		return;
	}

	$f3->set('title', "Editing ".$module->code.": ".$module->title );

	$f3->set('rendered_html_content', $syllabus->renderForm());
	$f3->set('templates', array('rendered_html.htm'));
	echo Template::instance()->render("main.htm");
}

function save_syllabus($f3)
{
	authenticate($f3);
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));

	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}

	$syllabus->fromForm();

	# if re-editing a provisional syl with a changes summary
	# this is now moved to the per edit log and cleared.
	$changessummary = $syllabus->changessummary;
	$syllabus->changessummary="";

	if( $syllabus->module->isprovisional )
	{
		R::store( $syllabus->module );
	}

	R::store($syllabus);

	$user = current_user($f3);
	$new_log = R::dispense("syllabuseditlog");
	$new_log->timestamp = time();
	$new_log->user = $user;
	$new_log->username = $user->username;
	$new_log->name = $user->familyname.", ".$user->givenname;
	$new_log->syllabus = $syllabus;
	$new_log->summary = $changessummary;
	R::store($new_log);

	if($f3->get('REQUEST.passback'))
	{
		header("Location: ".$f3->get('REQUEST.passback'));
		return;
	}
	header("Location: /view/syllabus/".$syllabus->id);
}


function toreview_syllabus($f3)
{
	authenticate($f3);
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}

	if(!$syllabus->canEdit())
	{
		$f3->error( 403, "You do not have permission to move this to review");
		return;
	}

	$syllabus->isunderreview = 1;

	R::store($syllabus);

	header( "Location: /" );
}


function review_syllabus($f3)
{
	authenticate($f3);
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}

	if(!$syllabus->isunderreview)
	{
		$f3->error( 500, "This syllabus is not under review");
		return;
	}

	$user = current_user($f3);
	if(!$syllabus->canBeReviewedBy($user))
	{
		$f3->error( 403, "You are not a reviewer for this syllabus");
		return;
	}

	$module = $syllabus->module;
	$content = "";

	#Check to see if there is a currently published syllabus
	$current_syllabus = $module->getCurrent();

	$f3->set("title", "Reviewing ".$module->code.": ".$module->title." (".$module->session.") ");
	$f3->set("module", $module);

	$f3->set("syllabus", $syllabus);

	$templates = array();
	if(!$current_syllabus){
		$content .= Template::instance()->render("syllabus.htm");
		$templates[] = 'syllabus.htm';
	}else{
		$f3->set("syllabuses", array('current'=>$current_syllabus, 'provisional'=>$syllabus));
		$templates[] = "comparesyllabuses.htm";

	}

	$templates[] = 'reviewtools.htm';
	$f3->set('templates', $templates);
	echo Template::instance()->render("main.htm");
}

function approve_syllabus($f3)
{
	authenticate($f3);
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
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
		$user = current_user($f3);
		$syllabus->isprovisional = 0;
		$syllabus->isunderreview = 0;
		$syllabus->timeapproved = time();
		$syllabus->approvedby = $user->username;
		$syllabus->approvedname = $user->familyname.", ".$user->givenname;
		$syllabus->approvalnote = $_POST["approvalnote"];
		$module = $syllabus->module;
		$module->currentsyllabus_id = $syllabus->id;
		unset($module->provisionalsyllabus);
		R::store($module);

	}

	R::store($syllabus);

	header( "Location: /review/dashboard" );

}

function review_dashboard($f3)
{
	authenticate($f3);

	if (!$f3->exists("PARAMS.session"))
	{
		$default_date = dates_as_sessions(strtotime("+1 year"));
		$f3->reroute($f3->get('PARAMS.0') . '/' . key($default_date));
		return;
	}

	$user = current_user($f3);
	$session = $f3->get('PARAMS.session');

	if (!$user->is_reviewer())
	{
		$f3->error( 403, "You are not registered as a module reviewer");
	}

	$f3->set("years", dates_as_sessions(null,3)); #null defaults to this year
	$f3->set("selected_year", $session);

	$syllabuses = $user->syllabuses_awaiting_review($session);
	$f3->set('syllabuses_awaiting_review', $syllabuses);
	$f3->set('syllabuses_awaiting_review_count', count($syllabuses));

	$syllabuses = $user->syllabuses_awaiting_submission($session);
	$f3->set('syllabuses_awaiting_submission', $syllabuses);
	$f3->set('syllabuses_awaiting_submission_count', count($syllabuses));

	$f3->set("title", "Your Review Dashboard");
	$f3->set('templates', array('year.htm','reviewdashboard.htm'));

	echo Template::instance()->render("main.htm");
}

function login($f3)
{
	authenticate($f3);

	header("Location: /");
}

function logout($f3)
{
	$f3->set("SESSION.authenticated", false);
	$f3->set("SESSION.userid", null);
	$f3->set("SESSION.user", null);
	header("Location: /");
}

function report_usage($f3)
{
	$report_start = strtotime("-1 month");
	$report_end = time();
	if($f3->exists("REQUEST.report_start")){
		$report_start = strtotime($f3->get("REQUEST.report_start"));
	}
	if($f3->exists("REQUEST.report_end")){
		$report_end = strtotime($f3->get("REQUEST.report_end"));
	}

	$syllabuses = R::find('syllabus', " timeapproved > ? and timeapproved < ? ORDER BY timeapproved", array($report_start, $report_end));

	$f3->set("title", "Usage report");
	$f3->set("syllabuses", $syllabuses);
	$f3->set("report_start", date("Y-m-d",$report_start));
	$f3->set("report_end", date("Y-m-d",$report_end));

	$f3->set('templates', array('report_usage'));

	echo Template::instance()->render("main.htm");
}

function report_books($f3)
{
	$faculty_code = $f3->get("PARAMS.faculty");
	$session = key(dates_as_sessions());

	$sql = "select distinct syllabus.* from syllabus, module where syllabus.module_id = module.id and syllabus.isprovisional != 1 and module.session=? and module.facultycode = ? order by module.code";


	$params = array($session, $faculty_code);
	$syllabuses = R::convertToBeans("syllabus", R::getAll( $sql, $params));

	$f3->set("syllabuses", $syllabuses);
	$f3->set("title", "Text book usage");
	$f3->set('templates', array('report_books.htm'));

	echo Template::instance()->render("main.htm");

}

function about_syllabalastic($f3)
{
	$f3->set("title", "About Syllabalastic");
	$f3->set('templates', array('about_syllabalastic.htm'));

	echo Template::instance()->render("main.htm");
}

?>
