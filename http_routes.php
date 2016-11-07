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
	if( !$f3->exists("ERROR.text") && $f3->get( "ERROR.code" ) == "404" )
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

function programs($f3)
{
	#syllabuses work one academic year ahead
	# this will probably blow everyones mind :-(
	$session_array = dates_as_sessions(strtotime("+1 year"));
	$next_year = key($session_array);
	header("Location: /view/programs/$next_year");
}

function modules_by_year($f3)
{
	$f3->set("years", dates_as_sessions(null,3)); #null defaults to this year
	$f3->set("selected_year", $f3->get("PARAMS.session"));
	$f3->set('title', 'Module list by course code');
	$templates = array('year.htm');
	
	$user = current_user($f3);
	$f3->set('userfacultycode', $user->facultycode);

	if($f3->exists("REQUEST.allmodules") || !isset($user->facultycode))
	{
		$faculty_codes = array_keys(listFaculties());
		$f3->set("facultycodes", $faculty_codes);
	}else{
		$templates[] = "seeallmodules.htm";
		$faculty_codes = array($user->facultycode);
		$f3->set("facultycodes", $faculty_codes);
	}
	
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

function programs_by_year($f3)
{
	$f3->set("years", dates_as_sessions(null,3)); #null defaults to this year
	$f3->set("selected_year", $f3->get("PARAMS.session"));
	$f3->set('title', 'Programs');
	$templates = array('year.htm');
	$programs = R::find('program', "session = ? ORDER BY code", array($f3->get('PARAMS.session')));
	
	$majors = R::find('major', "session = ? ORDER BY code", array($f3->get('PARAMS.session')));
	$majormap = array();
	foreach( $majors as $major ) 
	{ 
		$majormap[ $major->program_id ? $major->program_id : "??" ][] = $major; 
	}

	array_push( $templates, 'programlist.htm');
	$f3->set('templates', $templates);
	$f3->set('programs', $programs);
	$f3->set('majors', $majormap);

	echo Template::instance()->render("main.htm");
}

function learning_outcomes_by_major($f3)
{
	$f3->set("selected_year", $f3->get("PARAMS.session"));
	$f3->set("major_code", $f3->get("PARAMS.major_code"));

	$majors = R::find(
		'major', "code = ? AND session = ?", 
		array( $f3->get("PARAMS.major_code"), $f3->get("PARAMS.session") ) );

	if( !sizeof( $majors ) )
	{
		$f3->error( 404, "No major with that code on record." );
		return;
	}
	$major = array_shift( $majors );
	
	$f3->set("title", $major->code." ".$major->title." (".$major->session.")");
	$f3->set("major", $major);


	$f3->set('templates', array( "learning_outcomes_by_major.htm" ));
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

	$_REQUEST["modulecode"] = $next_create_code;
	$_REQUEST["session"] = $input["session"];
	create_syllabus($f3);
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
	$provisional = $existing_module->getProvisional();
	if($provisional)
	{
		# if a provisional syllabus already exists redirect to editing that
		$f3->reroute( "/edit/syllabus/". $provisional->id );
		return;
	}

	$syllabus = "";
	#print_r($existing_module->ownSyllabus);
	if($current_syllabus = $existing_module->getCurrent())
	{
		$syllabus = R::dup($current_syllabus);
	}
	elseif($past_module = R::findOne("module", " currentsyllabus_id is not null AND code = ? order by session desc ", array( $input["modulecode"] ) ))
	{
		$past_syllabus = $past_module->getCurrent();
		$syllabus = R::dup($past_syllabus);
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
	$syllabus->bigreview = false;
	$syllabus->courseleaderreviewed = false;
	$syllabus->quinquenialreviewed = false;
	$syllabus->approvedby = "";
	$syllabus->approvedname = "";
	$syllabus->approvalnote = "";
	$syllabus->changessummary = ""; 	# deprecated
	$syllabus->lock = 0;
	$syllabus->timeapproved = null;
	$syllabus->ownSyllabuseditlog = array();
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
	if( $f3->exists('PARAMS["modulecode"]')){
		$session = currentSession();

		if($f3->exists('PARAMS.session'))
		{
			$session = $f3->get('PARAMS.session');
		}

		$syllabus =  last_known_current_syllabus( $f3->get("PARAMS.modulecode"), $session );
	}else{
		$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	}
	if(!$syllabus || !$syllabus->id)
	{
		$f3->error( 404, "This syllabus does not exist or this module has no current syllabus");
		return;
	}
	$content = "";
	$module = $syllabus->module;
	$f3->set("title", $module->code." ".$module->title." (".$module->session.")");
	$f3->set("module", $module);
	$f3->set("syllabus", $syllabus);

	$first_occurence = R::findOne("module", " code = ? ORDER BY session ASC ", array($module->code) );
	$f3->set("firstoccurence", $first_occurence->session);

	$templates = array();
	if($syllabus->isprovisional){
		$templates[] = 'provisional.htm';
	}else{
		$templates[] = 'livesyllabus.htm';
	}
	#$templates[] = 'syllabus.htm';
	$f3->set("kis_contact_hours", $syllabus->kisContactHours());
	
	$f3->set("kis_independant_hours", $syllabus->kisIndependantHours());
	
	$templates[] = 'module_profile.htm';
	

	$f3->set('templates', $templates);
	echo Template::instance()->render("main.htm");
}

function pdf_module_profile($f3)
{
        $url = $f3->get("SCHEME")."://".$f3->get("HOST")."/view/moduleprofile/".$f3->get("PARAMS.modulecode");
	output_pdf( $f3, $url, $f3->get("PARAMS.modulecode").".pdf");
}

function view_module_profile($f3)
{
	if($f3->exists("PARAMS.session"))
	{
		$module = R::findOne("module", "session = ? AND code = ?", array( $f3->get("PARAMS.session"), $f3->get("PARAMS.modulecode") ) );
	}else{
		$module = R::findOne("module", " currentsyllabus_id is not null AND code = ? order by session desc ", array( $f3->get("PARAMS.modulecode") ) );
		
	}
	if(!isset($module))
	{
		$f3->error(404, "No module with this code exists");
	}

	$syllabus = $module->getCurrent();
	if(!$syllabus)
	{
		$f3->error(404, "There is no syllabus for this mo");
	}

	$module = $syllabus->module;
	$f3->set("title", $module->code." ".$module->title." (".$module->session.")");
	$f3->set("module", $module);
	$f3->set("syllabus", $syllabus);
	
	$author = array_pop($syllabus->with( ' ORDER BY timestamp DESC ' )->ownSyllabuseditlog);
	$f3->set("author", $author);
	
	$first_occurence = R::findOne("module", " code = ? ORDER BY session ASC ", array($module->code) );
	$f3->set("firstoccurence", $first_occurence->session);

	$kis_contact_hours =  $syllabus->kisContactHours();
	$f3->set("kis_contact_hours", $syllabus->kisContactHours());
	
	$f3->set("kis_independant_hours", $syllabus->kisIndependantHours());
	
	$templates = array();
	$templates[] = 'module_profile.htm';

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

	$people = $syllabus->module->sharedPerson;

	if( count( $people ) )
	{
		$person = array_shift($people);
		$module["person"] = $person->export();
	}

	echo json_encode($module);
}

function json_module($f3)
{
	$module_code = $f3->get("PARAMS.modulecode");
	$syllabus = last_known_current_syllabus($module_code);
	$module = R::findOne("module", " code = ? order by session desc ", array($module_code));

	if(!$syllabus->id)
	{
		$f3->error( 404, "This module does not have an approved syllabus");
		return;
	}
	$module_data = array("module"=>$module->export(), "syllabus"=>$syllabus->getData());

	$people = $module->sharedPerson;

	if( count( $people ) )
	{
		$person = array_shift($people);
		$module_data["person"] = $person->export();
	}

	echo json_encode($module_data);
}

function json_modules_department($f3)
{
	$department_code = $f3->get("PARAMS.departmentcode");

	$modules = R::find("module", " departmentcode = ? and session = ? ", array($department_code, "201617"));

	$modules_to_encode = array();
	foreach( $modules as $module )
	{
		$module_code = $module->code;
		$syllabus = last_known_current_syllabus($module_code);

		if(!$syllabus or !$syllabus->id)
		{
			# This module does not have an approved syllabus
			continue;
		}
		$module_to_encode = array("module"=>$module->export(), "syllabus"=>$syllabus->getData());
		$people = $module->sharedPerson;

		if( count( $people ) )
		{
			$person = array_shift($people);
			$module_to_encode["person"] = $person->export();
		}
		$modules_to_encode[] = $module_to_encode;
	
	}
	
	echo json_encode($modules_to_encode);
}

function pdf_syllabus($f3)
{
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}
	$module = $syllabus->module;

        $filename = $module->code."_".$module->session."_syllabus.pdf";
        $url = $f3->get("SCHEME")."://".$f3->get("HOST")."/view/syllabus/".$syllabus->id;

	output_pdf($f3, $url, $filename);
}

function ecs_syllabus($f3)
{
	if($f3->exists("PARAMS.session"))
	{
		$existing_module = R::findOne("module", " currentsyllabus_id is not null and session <= ? AND code = ? order by session desc ", array( $f3->get("PARAMS.session"), $f3->get("PARAMS.modulecode") ) );
	}else{
		$existing_module = R::findOne("module", " currentsyllabus_id is not null AND code = ? order by session desc ", array( $f3->get("PARAMS.modulecode") ) );
		
	}
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

#returns nice digestible xml for site publisher
function site_publisher_module($f3)
{
	$existing_module = R::findOne("module", " currentsyllabus_id is not null AND code = ? order by session desc ", array( $f3->get("PARAMS.modulecode") ) );
		
	if(!isset($existing_module))
	{
		$f3->error(404, "No module with this code found");
		return;
	}


	$xml = 	$existing_module->sitePublisherXML($f3);

	$output = $xml->saveXML();

	header( "content-type: application/xml; charset=utf-8" );

	$output = remove_escaped_html_comments($output);
	#echo preg_replace('/&rsquo;/', "'" ,$output);
	# site publisher has some pretty creative tastes about whats xml
	echo preg_replace('/\so\s/','',iconv('UTF-8', 'ASCII//TRANSLIT', $output));
}

function site_publisher_list($f3)
{
	$module_list;
	if($f3->exists("PARAMS.site"))
	{
		$site_modules = json_decode(file_get_contents(__DIR__."/etc/modulessites.json"), true);

		if($f3->get("PARAMS.site") == "engineeringug")
		{
     			$modules = R::getCol( ' SELECT code FROM module where session=201617 and facultycode="F2" ' );
		}else{
			$modules = @$site_modules[$f3->get("PARAMS.site")];
		}

		if(!$modules){
			$f3->error(400,"No modules in this site. Check site identifier");
		}
		$module_list = R::find("module", " currentsyllabus_id is not null and code in (".R::genSlots($modules).") order by session desc", $modules);
	}else{
		$module_list = R::find("module", " currentsyllabus_id is not null order by session desc");
	}

	$xml = new DOMDocument( "1.0", "utf-8" );
	$xml_list = $xml->createElement("Modules");
	$xml->appendChild($xml_list);
	$codes_complete = array();
	foreach($module_list as $module)
	{
		if(in_array($module->code, $codes_complete))
		{
			continue;
		}
		$codes_complete[] = $module->code;

		$module_xml = $module->sitePublisherXML($f3);		

		if($module_xml){
			$module_xml = $xml->importNode( $module_xml->documentElement , true );
		
			$xml_list->appendChild($module_xml);
		}

	}

	$output = $xml->saveXML();

	header( "content-type: application/xml; charset=utf-8" );

	$output = remove_escaped_html_comments($output);

	#echo preg_replace('/&rsquo;/', "'" ,$output);
	# site publisher has some pretty creative tastes about whats xml
	# it transliterates bullet marks into o so remove them.
	echo preg_replace('/\so\s/','',iconv('UTF-8', 'ASCII//TRANSLIT', $output));
}

function php_module($f3)
{
	$existing_module = R::findOne("module", "session = ? AND code = ?", array( $f3->get("PARAMS.session"), $f3->get("PARAMS.modulecode") ) );

	if(!isset($existing_module))
	{
		echo("This module does not exists");
		exit;
	}
	if( $existing_module->getProvisional() )
	{
		$syllabus = $existing_module->getProvisional();
	}else
	{
		$syllabus = last_known_current_syllabus($existing_module->code, $existing_module->session);
	}

	if(!$syllabus)
	{
		echo("This syllabus does not exist");
		return;
	}
	
	$module_leader = array_shift($existing_module->sharedPerson);
	$module = array("module"=>$existing_module->export(), "module_leader"=>$module_leader->export(), "syllabus"=>$syllabus->getData());
	echo serialize($module);
}

function edit_syllabus($f3)
{
	global $API_KEYS, $REVIEWERS;

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
	$lock_length = $f3->get("LOCK_LENGTH") * 60; #specified in minutes in config
	if(($syllabus->lock + $lock_length) > time())
	{
		$f3->error( 409, "Sorry this syllabus is locked for editing by another user. They will have the lock until they save or after ".$f3->get("LOCK_LENGTH")." minutes without saving their work.");
	}

	$syllabus->lock = time();
	R::store($syllabus);

	$module = $syllabus->module;

	$user = current_user($f3);

	if( in_array($module->facultycode, array("F2", "F8")) && !@$REVIEWERS[$user->username] )
	{
		$f3->error( 403, "Sorry this syllabus is no longer editable by users directly. Please contact your CQA office to update this module.");
		return;
	}

	if(valid_api_key($f3->get("REQUEST.apikey")))
	{
		$secret = create_secret($f3->get("REQUEST.apikey"));
		echo serialize( $syllabus->renderForm(array("secret"=>$secret, "passback"=>$f3->get("REQUEST.passback"))));
		return;
	}
	$f3->set("syllabusid", $syllabus->id);
	$f3->set('title', "Editing ".$module->code.": ".$module->title );
	$form = $syllabus->renderForm();

	$f3->set('rendered_html_content', $form);
	$f3->set('ESCAPE', false);
	$f3->set('templates', array('rendered_html.htm', "releaseeditlock.htm"));
	echo Template::instance()->render("main.htm");
	$f3->set('ESCAPE', true);
}

function release_edit_lock($f3)
{
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	$syllabus->lock = 0;
	R::store($syllabus);
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
	$syllabus->lock=0;

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

	$user = current_user($f3);
	$new_log = R::dispense("syllabuseditlog");
	$new_log->timestamp = time();
	$new_log->user = $user;
	$new_log->username = $user->username;
	$new_log->name = $user->familyname.", ".$user->givenname;
	$new_log->syllabus = $syllabus;
	$new_log->summary = "SUBMITTED FOR REVIEW";
	R::store($new_log);

	header( "Location: /" );
}

function return_syllabus($f3)
{
	authenticate($f3);
	$syllabus = R::load("syllabus", $f3->get('PARAMS["syllabus_id"]'));
	$user = current_user($f3);
	if(!$syllabus->id)
	{
		$f3->error( 404, "This syllabus id does not exist");
		return;
	}

	if(!$syllabus->canBeReviewedBy($user))
	{
		$f3->error( 403, "You cannot review this syllabus so you do not have permission to return it.");
		return;
	}

	$syllabus->isunderreview = 0;

	R::store($syllabus);

	$new_log = R::dispense("syllabuseditlog");
	$new_log->timestamp = time();
	$new_log->user = $user;
	$new_log->username = $user->username;
	$new_log->name = $user->familyname.", ".$user->givenname;
	$new_log->syllabus = $syllabus;
	$new_log->summary = "RETURNED FOR FURTHER EDITING";
	R::store($new_log);

	header( "Location: /review/dashboard" );
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
	$current_syllabus = last_known_current_syllabus($module->code);

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

	$syllabus->cqareviewed = true;

	if(@$_POST["bigreview"])
	{
		$syllabus->bigreview = true;
	}

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

function reports($f3)
{
	$f3->set("title", "Reports");
	$f3->set('templates', array('reports.htm'));
	$module = R::dispense('module');
	$f3->set("faculties", listFaculties());
	echo Template::instance()->render("main.htm");
}

function report_usage($f3)
{
	$f3->set("faculties", listFaculties());
	$f3->set("sessions", listSessions());

	$faculty = "fp"; //TODO get user's faculty...
	if($f3->exists("PARAMS.faculty")){
		$faculty = $f3->get("PARAMS.faculty");
	} elseif($f3->exists("REQUEST.faculty")){
		$faculty = $f3->get("REQUEST.faculty");
	}
	$f3->set('faculty', $faculty);

	$report_start = strtotime("-1 month");
	$report_end = time();
	if($f3->exists("REQUEST.report_start")){
		$report_start = strtotime($f3->get("REQUEST.report_start"));
	}
	if($f3->exists("REQUEST.report_end")){
		$report_end = strtotime($f3->get("REQUEST.report_end"));
	}

	// Add 1 year as we're planning for next year
	$academic_session = currentSession(1);
	if($f3->exists("REQUEST.academic_session")){
		$academic_session = $f3->get("REQUEST.academic_session");
	}
	$f3->set("academic_session", $academic_session);

	//$syllabuses = R::find('syllabus', " timeapproved > ? and timeapproved < ? and module.facultycode = ? ORDER BY timeapproved", array($report_start, $report_end, $faculty));
	$sql = 'SELECT syllabus.* '.
		'FROM syllabus INNER JOIN module ON syllabus.module_id = module.id '.
		'WHERE timeapproved > ? and timeapproved < ? and module.facultycode = ? '.
		'ORDER BY timeapproved';

	$syllabuses = R::convertToBeans('syllabus', R::getAll($sql,
		array($report_start, $report_end, $faculty)));

	if($f3->exists("REQUEST.csv"))
	{
		$headings = array("code", "title", "date updated", "module leader");
		$data_to_csv = array();
		foreach($syllabuses as $syllabus)
		{
			$module = $syllabus->module;
			if(!$module) { continue; }
			$row = array($module->code, $module->title, date("Y-m-d", $syllabus->timeapproved));
			if(count($module->sharedPerson) > 0) 
			{
				$person = array_pop($module->sharedPerson);
				$row[] = $person->firstname.' '.$person->lastname;
			}
			$data_to_csv[] = $row;
			
		}
		$filename = "syllabus_updates_".date("Y-m-d", $report_start)."_".date("Y-m-d", $report_start).".csv";
		output_csv( $data_to_csv, $headings, $filename );
		exit;
	}

	if($f3->exists("REQUEST.pdf"))
	{
		$tmp_dir = sys_get_temp_dir()."/".time();
		mkdir($tmp_dir);

		$zip = new ZipArchive();
		$zip->open($tmp_dir.'/module_profiles.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
		foreach($syllabuses as $syllabus)
		{
			$module = $syllabus->module;
			$module_code = $module->code;
			$module_url = "http://".$_SERVER['HTTP_HOST']."/pdf/moduleprofile/".$module_code;
			$file_path = "$tmp_dir/$module_code.pdf";
			file_put_contents($file_path, file_get_contents($module_url));
			$zip->addFile($file_path, "module_profiles/$module_code.pdf");
		}
		$zip->close();

		output_zip($tmp_dir."/module_profiles.zip");
		exit;
	}
	
	$f3->set("title", "Recently edited syllabuses report");
	$f3->set("syllabuses", $syllabuses);
	$f3->set("report_start", date("Y-m-d",$report_start));
	$f3->set("report_end", date("Y-m-d",$report_end));

	$f3->set('templates', array('report_usage.htm'));
	echo Template::instance()->render("main.htm");

}

function report_assessment($f3)
{
	$f3->set("faculties", listFaculties());
	$f3->set("sessions", listSessions());

	$faculty = "fp"; //TODO get user's faculty...
	if($f3->exists("PARAMS.faculty")){
		$faculty = $f3->get("PARAMS.faculty");
	} elseif($f3->exists("REQUEST.faculty")){
		$faculty = $f3->get("REQUEST.faculty");
	}
	$f3->set('faculty', $faculty);

	// Add 1 year as we're planning for next year
	$academic_session = currentSession();
	if($f3->exists("REQUEST.academic_session")){
		$academic_session = $f3->get("REQUEST.academic_session");
	}
	$f3->set("academic_session", $academic_session);

	$modules = R::find("module", " facultycode = ? and session = ? order by code ", array($faculty, $academic_session)); 

	$headings = array("code", "title", "Summary of Assessment and Feedback Methods", "Examination method", "Referral Policy", "Method of repeat year", "Assessment Notes", "Referral Notes");
	$data_to_csv = array();
	foreach($modules as $module)
	{ 
		$syllabus = last_known_current_syllabus( $module->code, $module->session );

		if(!$syllabus) { continue; }	
		$f3->set("syllabus", $syllabus);

		$assessment = "";

		foreach($syllabus->ownContinuousassessment as $continuous_assessment)
		{
			$assessment .= "Title: " . $continuous_assessment->description . "\n";
			$assessment .= "% contribution to final mark: " . $continuous_assessment->percent . "\n";
			$assessment .= "Feedback: " . $continuous_assessment->feedback . "\n";
			$assessment .= "\n";
		}

		$exams = "";
		foreach($syllabus->ownExam as $exam)
		{
			$exams .= "Exam: ".$exam->percent."%, ".$exam->examduration." hours\n\n"; 
		}

		$referral = $syllabus->getConstant($syllabus->referral);
		$repeat_year = "";
		
		foreach($syllabus->ownRepeatyear as $method_of_repeat)
		{
				$repeat_year .= $method_of_repeat->repeatyear." ";
		}

		$row = array($module->code, $module->title, $assessment, $exams, $referral, $repeat_year, strip_tags($syllabus->assessmentnotes), strip_tags($syllabus->referralnotes));

		$data_to_csv[] = $row;
		
	}
	
	$filename = "syllabus_assessment_".date("Y-m-d").".csv";
	output_csv( $data_to_csv, $headings, $filename );
	exit;
}

function report_current_syllabus_urls($f3)
{
	$session = key(dates_as_sessions());
	$module_codes = array();
	
	if($f3->exists("PARAMS.faculty"))
	{
		$module_codes= R::getAll("select code from module where session=? and facultycode=?", array($session, $f3->get("PARAMS.faculty")));
	}
	else
	{
		$module_codes = R::getAll("select code from module where session=? ", array($session));
	}

	$data_to_csv = array();
	foreach($module_codes as $results)
	{
		$code = $results["code"];
		
		$module_with_syll = R::findOne("module", " currentsyllabus_id is not null AND code = ? order by session desc ", array( $code ) );
		if(!$module_with_syll){ continue; }
		$subject_code = substr($code,0,4);
		$course_number = substr($code,4);
		$url = "https://syllabus.soton.ac.uk/view/moduleprofile/$code";
		$approval_date = strtoupper(date("d-M-Y", $module_with_syll->getCurrent()->timeapproved));
		$data_to_csv[] = array($subject_code, $course_number, $url, $module_with_syll->title, $approval_date);
	}

	$headings = array("subject code", "course number", "URL", "module title", "creation date");
	output_csv($data_to_csv, $headings, "syllabus_urls.csv");
	
}

function report_current_syllabus_learning_outcomes($f3)
{
	$session = key(dates_as_sessions());
	$module_codes = array();
	
	if($f3->exists("PARAMS.faculty"))
	{
		$module_codes= R::getAll("select code from module where session=? and facultycode=?", array($session, $f3->get("PARAMS.faculty")));
	}
	else
	{
		$module_codes = R::getAll("select code from module where session=? ", array($session));
	}

	$data_to_csv = array();
	foreach($module_codes as $results)
	{
		$code = $results["code"];
		
		$module_with_syl = R::findOne("module", " currentsyllabus_id is not null AND code = ? order by session desc ", array( $code ) );
		if(!$module_with_syl){ continue; }

		$syllabus = $module_with_syl->getCurrent();
		$outcomes = $syllabus->getLearningOutcomes();
		foreach($outcomes as $outcome_type => $outcome_set)
		{
			foreach($outcome_set as $outcome)
			{
				$data_to_csv[] = array($module_with_syl->code, $module_with_syl->session, $outcome_type, $outcome);
			}
		}	
		

	}
	$headings = array("module code", "session", "outcome type", "outcome");
	output_csv($data_to_csv, $headings, "syllabus_outcomes.csv");
	
}

function report_module_profiles($f3)
{
	header("Pragma: ");
        header("Cache-Control: ");
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename="module_profiles.zip"');

	echo file_get_contents($f3->get("ROOT")."/tmp/moduleprofiles/".$f3->get("PARAMS.faculty")."_module_profiles.zip");
}

function report_unedited_modules($f3)
{

	$f3->set("faculties", listFaculties());
	$f3->set("sessions", listSessions());
	$faculty = "fp"; //TODO get user's faculty...
	if($f3->exists("PARAMS.faculty")){
		$faculty = $f3->get("PARAMS.faculty");
	} elseif($f3->exists("REQUEST.faculty")){
		$faculty = $f3->get("REQUEST.faculty");
	}
	$f3->set('faculty', $faculty);
	

	$report_start = strtotime("-1 month");
	if($f3->exists("REQUEST.report_start")){
		$report_start = strtotime($f3->get("REQUEST.report_start"));
	}

	// Add 1 year as we're planning for next year
	$academic_session = currentSession(1);
	if($f3->exists("REQUEST.academic_session")){
		$academic_session = $f3->get("REQUEST.academic_session");
	}
	$f3->set("academic_session", $academic_session);

	$sql = 'SELECT DISTINCT module.* '.
		'FROM module LEFT JOIN ('.
			'SELECT module_id FROM syllabus WHERE timeapproved > ?'.
		') syls on syls.module_id = module.id '.
		'WHERE syls.module_id IS NULL and module.session=? and module.facultycode=?'.
		'ORDER BY code';
	$modules = R::convertToBeans('module', R::getAll($sql,
		array($report_start, $academic_session, $faculty)));

	$f3->set("title", "Unedited modules report");
	$f3->set("modules", $modules);
	$f3->set("report_start", date("Y-m-d",$report_start));

	$f3->set('templates', array('report_unedited_modules.htm'));

	echo Template::instance()->render("main.htm");
}

function report_books($f3)
{
	$faculty_code = $f3->get("PARAMS.faculty");
	$session = key(dates_as_sessions());

	$sql = "select distinct syllabus.* from syllabus, module where syllabus.module_id = module.id and syllabus.isprovisional != 1 and module.session=? and module.facultycode = ? order by module.code";


	$params = array($session, $faculty_code);
	$syllabuses = R::convertToBeans("syllabus", R::getAll( $sql, $params));
	
	if(!$f3->exists("REQUEST.csv"))
	{
		$f3->set("syllabuses", $syllabuses);
		$f3->set("title", "Text book usage");
		$f3->set('templates', array('report_books.htm'));

		echo Template::instance()->render("main.htm");
		exit;
	}
	
	$headings = array("code", "title", "session", "type", "isbn (if specified)", "details");
	$data_to_csv = array();
	foreach($syllabuses as $syllabus)
	{
		$module = $syllabus->module;
		foreach($syllabus->ownResources as $resource)
		{
			if($resource->type =="background" || $resource->type == "core")
			{
				$details_clean = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($resource->details))); 
				$data_to_csv[] = array( $module->code, $module->title, $module->session, $resource->type, $resource->isbn, $details_clean );
			}
		}
	}
	output_csv($data_to_csv, $headings, "books_${session}_$faculty_code.csv");
}

function about_syllabalastic($f3)
{
	$f3->set("title", "About Syllabalastic");
	$f3->set('templates', array('about_syllabalastic.htm'));

	echo Template::instance()->render("main.htm");
}

function view_module_history($f3)
{
	$module_code = $f3->get("PARAMS.modulecode");
	$f3->set('title', 'History of syllabus for module "'.$module_code.'"');
	$f3->set('templates', array('module_history.htm'));
	$modules = R::find('module', "code = ? ORDER BY session DESC", array( $module_code ) );
	if( !sizeof( $modules ) )
	{
		$f3->error( 404, "No module with that code on record." );
		return;
	}
	$f3->set('modules', $modules );
	echo Template::instance()->render("main.htm");
}

?>
