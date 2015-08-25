<?php
function remove_escaped_html_comments($string)
{
	return preg_replace('/&lt;!--.*?--&gt;/s', '', $string);
}

function is_assoc($array) {
  foreach (array_keys($array) as $k => $v) {
    if ($k !== $v)
      return true;
  }
  return false;
}

function clean_html($string)
{
	#remove comments
	$string = preg_replace('/<!--.*?-->/s', '', $string);
	#remove style attributes
	$string = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $string);
	#remove empty html tags except ones with t in (tr, td, tbody)
	$string = preg_replace('/<([^t\/>][^>]*)>\s*<\/\1>/s', '', $string);
	
	return preg_replace('/^\s*/s', '', $string);
}

function output_csv($data_table, $headings, $filename)
{
        header('Content-Type: application/octet-stream');
        header("Pragma: ");
        header("Cache-Control: ");
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename='.$filename);
	
	$fh = fopen ( "php://output", "w" );
	fputcsv($fh, $headings );
	foreach($data_table as $row)
	{
		fputcsv( $fh, $row );
	}
	exit;
}

function output_pdf($f3, $url, $filename)
{
        header("Pragma: ");
        header("Cache-Control: ");
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename="'.$filename.'"');

        echo shell_exec($f3->get("ROOT")."/lib/wkhtmltox/bin/wkhtmltopdf --margin-top 25mm --margin-bottom 25mm --margin-left 5mm --margin-right 5mm --print-media-type --images --quiet $url - ");
	exit;

}

function authenticate($f3, $pass_through = null)
{

	if ($pass_through === null)
	{
		$pass_through = $f3->get('PARAMS.0');
	}
	
	#already authenticated
	if($f3->get("SESSION.authenticated") == true)
	{
		return true;
	}

	if(valid_api_key($f3->get("REQUEST.apikey")))
	{
		return true;
	} 
	if(valid_secret($f3->get("REQUEST.secret")))
	{
		return true;
	}
	
	#not yet been asked to authenticate
	if(!(array_key_exists("username",$_POST) && array_key_exists("password", $_POST)))
	{
		$f3->set("title","Login");
		$f3->set("pass_through", $pass_through);
		$f3->set("REQUEST", $_REQUEST);
		$f3->set("templates", array("login.htm"));
		
		echo Template::instance()->render("main.htm");
		exit;
	}

	#have submitted username and password but havent been given a session yet
	// LDAP extension required
	if (!extension_loaded('ldap')) {
		// Unable to continue
		$f3->error( 500,'LDAP module is not installed');
		return;
	}
	$domain_address = "ldaps://nlbldap.soton.ac.uk/";
	$dc=ldap_connect($domain_address);
	if (!$dc) {
		// Connection failed
		trigger_error(sprintf($domain_address));
		return FALSE;
	}
	ldap_set_option($dc,LDAP_OPT_PROTOCOL_VERSION,3);
	ldap_set_option($dc,LDAP_OPT_REFERRALS,0);

	if (!ldap_bind($dc)) 
	{
		// Bind failed
		trigger_error("bind failed");
		return FALSE;
	}
	$result=ldap_search($dc,"dc=soton,dc=ac,dc=uk",'cn='.$_POST["username"]);

	if (ldap_count_entries($dc,$result)==0)
	{
		// Didn't return a single record
//TODO prompt for login
		$f3->error( 403,"Unrecognised username");
		return FALSE;
	}
	// Bind using credentials
	$info=ldap_get_entries($dc,$result);
	if (!@ldap_bind($dc,$info[0]['dn'],$_POST["password"]))
	{
		// Bind failed
		$f3->error( 403,"Unrecognised password");
	}
	@ldap_unbind($dc);

	if(!array_key_exists("extensionattribute10",$info[0]) || $info[0]['extensionattribute10'][0]!='Active')
	{
		$f3->error( 403,"Your account appears to be expired. Contact serviceline on x25656.");
	}

	if(!array_key_exists("extensionattribute9",$info[0]) || ($info[0]['extensionattribute9'][0]!='staff' && $info[0]['extensionattribute9'][0]!='generic'))
	{
		$f3->error( 403,"Only staff may log into this service");
	}

	$user = R::findOne('user', ' username = ?', array($_POST["username"]));

	if(!isset($user))
	{
		$user = R::dispense("user");
	}

	$user->update_from_ldap_data($info);

	$f3->set("SESSION.authenticated", true);
	$f3->set("SESSION.userid", $user->id );
	$f3->set("SESSION.user", $user );

}

function current_user($f3)
{
	return R::load('user', $f3->get('SESSION.userid'));
}

#returns an array keyed on sessions as stored in the database (e.g. 201516)
#with values as sessions as displayed (e.g. 2015-16)
#$date -- the earliest date in the array
#$n --  param defines how may consecutive sessions will be in the array
function dates_as_sessions($date=null, $n=1)
{
	if($date === null){
		$date = time();
	}

	$year = date('Y', $date);
	$year--;
	$next_year = date('y', $date);
	if (date('n') > 10)
	{
		$year++;
		$next_year++;
	}

	$sessions = array();
	for ($i = 0; $i < $n; $i++)
	{
		$k = "$year$next_year";
		$v = "$year-$next_year";
		$sessions[$k] = $v;
		$year++;
		$next_year++;
	}

	return $sessions;
}


# $date should be a unix time as provided by time() or strtotime
function date_as_session($date=null)
{
	return dates_as_sessions($date);

	if($date === null){
		$date = time();
	}
	#if its after october we are in the new academic 
	if(date('n') > 10)
	{
		return array(date('Y', $date).(date('y',$date) + 1 )=> date('Y',$date)."-".(date('y',$date)+1));
	}
	return array((date('Y', $date)-1).date('y', $date) => (date('Y')-1)."-".date('y', $date));
}

function valid_api_key($key)
{
	global $API_KEYS;
	if(in_array($key, $API_KEYS))
	{
		return true;
	} 
	return false;
}

function create_secret($api_key)
{
	$secret = R::dispense("secretkey");
	$secret->secret = md5("9b49a808af453e5cde8d9bbec9fe4385".$api_key.time());
	$secret->issuetime = time();
	R::store($secret);
	return $secret->secret;

}

function valid_secret($secret)
{
	$secret_key = R::findOne('secretkey','secret=?',array($secret));

	if(isset($secret_key))
	{
		$a_day = 60*60*24;
		if( (time() - $secret_key->issuetime) < $a_day && !$secret_key->used)
		{
			$secret_key->used = true;
			R::store($secret_key);
			return true;	
			
		}
	}

	return false;
}

function tick($msg = "tick" ) 
{
	$f3 = Base::instance();
	$mt = microtime(true);
	print sprintf( "<p>%s: %0.3f since start. %0.3f since last tick. Memory used: %si MBi. </p>\n",
		$msg,
		$mt - $f3->get('page_load_start' ),
		$mt - $f3->get('last_tick' ),
		memory_get_usage()/ 1000000 );
	$f3->set('last_tick', $mt );
}

function listFaculties()
{
	$things = R::$f->begin()->addSQL(' SELECT DISTINCT facultycode, facultyname ')->from('module')->get();

	$faculties = array();
	foreach ($things as $pair){
		$faculties[$pair['facultycode']] = $pair['facultyname'];
	}

	asort($faculties);
	return $faculties;
}

function listSessions()
{
	$sessions = R::getCol(' SELECT DISTINCT session FROM module ORDER BY session');

	return $sessions;
}

function currentSession($offset_years = 0)
{
	$start_year = date('Y') + $offset_years;
	$now_month = date('m');
	if($now_month < 10){
		$start_year--;
	}
	$end_year = $start_year + 1;
	$end_year = substr($end_year, 2);

	$academic_session = "$start_year$end_year";

	return $academic_session;
}

function last_known_current_syllabus($module_code)
{
	$existing_module = R::findOne("module", " currentsyllabus_id is not null AND code = ? order by session desc ", array( $module_code ) );
	if(!$existing_module)
	{
		return null;
	}
	return $existing_module->getCurrent();

}
?>
