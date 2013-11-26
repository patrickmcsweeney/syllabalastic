<?php

$f3=require($path_to_base_dir.'lib/fatfree-master/lib/base.php');
$f3->config($path_to_base_dir.'config.ini');

#Note on first installation, you will need to fill in
#and rename passwords.ini.template to passwords.ini
$f3->config($path_to_base_dir.'passwords.ini');

$includes = array
(
        'functions.php',
        'http_routes.php',
        'lib/redbean/rb.php',
        'syllabus.php',
        'user.php',
        'lib/floraform/FloraForm.php'
);
foreach ($includes as $file)
{
#path_to_base_dir should be set by the script which does the include
        require_once($path_to_base_dir.$file);
}


$db_name = $f3->get('db_name');
$db_password = $f3->get('db_password');
$db_user = $f3->get('db_user');
$db_host = $f3->get('db_host');

R::setup("mysql:host=$db_host;dbname=$db_name",$db_user,$db_password);

$API_KEYS = array($f3->get('api_key'));
$REVIEWERS = array(  
        "pm5c08" => array(
		'module' => array(
			'facultycode' => array("F7")
#			'themecode' => array(),
#			'code' => array()
		)
	),
        "bjc1f08" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "ms1r10" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "mjw7" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "nmg" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "mcf" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "asw1v08" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "fenglian" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "gbw" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "dan1" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "srg" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "gravell" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "mz1" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "ck7" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "phc1" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "chdg" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "rwe" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	),
        "dm3" => array(
		'module' => array(
			'facultycode' => array("F2")
		)
	),
        "bl2" => array(
		'module' => array(
			'facultycode' => array("F2")
		)
	),
        "cqafee" => array(
		'module' => array(
			'facultycode' => array("F2")
		)
	),
        "cqafshs" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "alexfurr" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "cs1m12" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "af05v" => array(
		'module' => array(
			'facultycode' => array("F7")
		)
	)
);


