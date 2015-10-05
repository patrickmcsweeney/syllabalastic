<?php
$f3=require($path_to_base_dir.'lib/fatfree-master/lib/base.php');
$f3->set('page_load_start', microtime(true));
$f3->set('last_tick', microtime(true));

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
        'module.php',
        'user.php',
        'lib/floraform/FloraForm.php'
);

$department_map = unserialize(file_get_contents(__DIR__."/etc/departments.php")); 

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

#Initialise API keys
$API_KEYS = array();
if($f3->get('api_key')) {
    #config API key
    array_push($API_KEYS,$f3->get('api_key'));
}

$REVIEWERS = array(
        "pm5c08" => array(
		'module' => array(
			'facultycode' => array( "F1", "F2", "F3", "F4", "F5", "F6", "F7", "F8", "wf", "fp"),
			'departmentcode' => array("FP", "JF")
#			'themecode' => array(),
#			'code' => array()
		)
	),
        "pm2" => array(
		'module' => array(
			'facultycode' => array( "F1", "F2", "F3", "F4", "F5", "F6", "F7", "F8", "wf", "fp"),
			'departmentcode' => array("FP", "JF")
#			'themecode' => array(),
#			'code' => array()
		)
	),
        "ob1a12" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp", "jf")
		)
	),
       "bjc1f08" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
       "lm4e09" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
       "pll" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
       "fpascqa" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
       "ls7" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "ms1r10" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "mjw7" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "nmg" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "mcf" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "asw1v08" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "fenglian" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "jr1a06" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "mjcoe" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "lac" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "act" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "stefano" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "ak3w07" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "peterw" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "rgm1y07" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "mdr1f06" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "raw1" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "bc1d11" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "dan1" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "srg" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "gravell" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "mz1" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "ck7" => array(
		'module' => array(
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "ajbird" => array(
		'module' => array(
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "gbw" => array(
		'module' => array(
			'departmentcode' => array("FP", "JF"),
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "phc1" => array(
		'module' => array(
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "chdg" => array(
		'module' => array(
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "rwe" => array(
		'module' => array(
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "dm3" => array(
		'module' => array(
			'facultycode' => array("F2")
		)
	),
        "cdb1a13" => array(
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
        "af05v" => array(
		'module' => array(
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "totl" => array(
		'module' => array(
			'facultycode' => array("F7", "wf", "fp")
		)
	),
        "ch" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "ch" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "saw4" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "jet" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "cgw1c13" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "cs1m12" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "jer1e13" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "rp1v12" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "trs1m13" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
        "ajf1u10" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
	        "mgb1f14" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
	        "gg1v14" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
	        "bgs1m14" => array(
		'module' => array(
			'facultycode' => array("F8")
		)
	),
);

$EMAIL_ALERTS = array( "pm5c08", "cqafee", "cqafshs" );
#$EMAIL_ALERTS = array( "pm5c08", "pm2" );

$f3->set('getConstant',function($syllabus, $key){
        return $syllabus->getConstant("$key");
});
