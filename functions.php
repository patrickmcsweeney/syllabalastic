<?php

##RENAME THIS FILE

function error($message){
	F3::set("title","Error");
	F3::set("content", $message);
	echo Template::serve("main.htm");
	exit;
}

function authenticate($pass_through)
	{
	
	#already authenticated
	if(F3::get("SESSION.authenticated") == true)
	{
		return true;
	}

	if(valid_api_key(F3::get("REQUEST.apikey")))
	{
		return true;
	} 
	if(valid_secret(F3::get("REQUEST.secret")))
	{
		return true;
	}
	
	#not yet been asked to authenticate
	if(!(array_key_exists("username",$_POST) && array_key_exists("password", $_POST)))
	{
		F3::set("title","Login");
		F3::set("pass_through", $pass_through);
		F3::set("REQUEST", $_REQUEST);
		F3::set("content", Template::serve("login.htm"));
		
		echo Template::serve("main.htm");
		exit;
	}

	#have submitted username and password but havent been given a session yet
	// LDAP extension required
	if (!extension_loaded('ldap')) {
		// Unable to continue
		error('LDAP module is not installed');
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
		error("<p>Unrecognised username</p>".Template::serve("login.htm"));
		return FALSE;
	}
	// Bind using credentials
	$info=ldap_get_entries($dc,$result);
	if (!@ldap_bind($dc,$info[0]['dn'],$_POST["password"]))
	{
		// Bind failed
		error("<p>Unrecognised password</p>".Template::serve("login.htm"));
	}
	@ldap_unbind($dc);

	if(!array_key_exists("extensionattribute10",$info[0]) || $info[0]['extensionattribute10'][0]!='Active')
	{
		error("Your account appears to be expired. Contact serviceline on x25656.");
	}

	if(!array_key_exists("extensionattribute9",$info[0]) || $info[0]['extensionattribute9'][0]!='staff')
	{
		error("Only staff may log into this service");
	}

	if($info[0]['cn'][0]!=$_POST["username"])
	{
		error("Unexpected login failure. This should never happen.");
		return FALSE;
	}

	$user = R::findOne('user', ' username = ?', array($_POST["username"]));

	if(!isset($user))
	{
		$user = R::dispense("user");
	}

	$user->staffid = $info[0]['employeenumber'][0];
	$user->username = $info[0]['name'][0];
	$bits = explode(',',$info[0]['dn']);
	$faculty_bits = explode("OU=", $bits[2]);
	$user->facultycode = $faculty_bits[1];
	$user->facultyname = $info[0]['department'][0];
		
	$userid = R::store($user);

	F3::set("SESSION.authenticated", true);
	F3::set("SESSION.userid", $userid );

}

function current_user()
{
	return R::load('user', F3::get('SESSION.userid'));
}

# $date should be a unix time as provided by time() or strtotime
function date_as_session($date=null)
{
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

function valid_secret($secret)
{
	$secret_key = R::findOne('secretkey','secret=?',array($secret));

	if(isset($secret_key))
	{
		$a_day = 60*60*24;
		if( (time() - $secretkey->issuetime) < $a_day && !$secret_key->used)
		{
			$secret_key->used = true;
			R::store($secret_key);
			return true;	
			
		}
	}

	return false;
}

?>
