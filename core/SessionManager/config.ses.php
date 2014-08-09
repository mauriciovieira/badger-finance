<?php
/*
 config.ses.php
 Copyright (C) 2003
 Alberto Alcocer Medina-Mora
 root@b3co.com

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/**********start configuration*************/

/*
	database settings
*/

require_once(BADGER_ROOT . "/includes/includes.php");

$_db_user = DB_USERNAME;
$_db_pass = DB_PASSWORD;
$_db_url  = DB_HOST;
$_db_name = DB_DATABASE_NAME;

/*
	if you dont have any table with the following names
	you can leave next two variables as are
*/

$_db_table= "session_global";		// name of the table containing all user defined variables
$_db_table_config = "session_master";	// name of the table that will contain session data

/*
	if _session_timeout is 0, then session will expire until browser is closed
	otherwise session will expire un _session_timeout minutes
*/

// Modified by BADGER

//$_session_timeout = 1800;
$_session_timeout = $us->getProperty('badgerSessionTime');

$GLOBALS['sessionTimeout'] = false;

/*
	this variable is used to generate a more secure sid, because this
	string is only known by you
*/
$secure_key = 'badGERBadGerbAdGeRmushroomMUSHROOM';

/**********end configuration*************/

/*
	conect to db
	disabled by badger
*/
//$_db = mysql_connect($_db_url, $_db_user, $_db_pass)
//	or die("error de coneccion a la base de datos");
//mysql_select_db($_db_name) or die(mysql_error());

/*
	session functions
*/

function new_session(){
	global $_db_table,$_db_table_config,$_session_timeout,$secure_key;
	list($m,$s) = explode(" ",microtime());
	$sess = md5(rand(0,1000).substr($m,2).$secure_key);
	/*if($_session_timeout != 0){
		setcookie('badger_sess', $sess, time()+($_session_timeout*60), '/');
	}else*/{
		setcookie('badger_sess',$sess, 0, '/');
	}
	$sql = "insert into $_db_table_config(sid,id,start,last,ip) values('$sess',0,NOW(),NOW(),'".$_SERVER['REMOTE_ADDR']."')";
	$res = query($sql);
	return $sess;
}

function update_session(){
	global $_db_table_config, $badgerDb, $_session_timeout, $logger;
	$sess = $_COOKIE['badger_sess'];
	$sql = "select logout, UNIX_TIMESTAMP(last) last from $_db_table_config where sid = '$sess'";
	$res = query($sql);
	
	//modified by badger
	//$row = mysql_fetch_array($res);
	
	$row = array();
	
	$res->fetchInto ($row, DB_FETCHMODE_ASSOC);
	
	$logger->log('SESSION MANAGEMENT: last: ' . $row['last'] . ' time: ' . time() . ' diff: ' . (time() - $row['last']));

	if (($row['last'] + $_session_timeout * 60) < time()) {
		$GLOBALS['sessionTimeout'] = true;

		return new_session();
	} else if ($row['logout']!=1){
		$sql = "update $_db_table_config set last = NOW() where sid = '$sess'";
		query($sql);
		if($badgerDb->affectedRows() < 0){
			return new_session();
		}else{
			return $sess;
		}
	} else {
		return new_session();
	}
}

function set_session_var($name,$value){
	global $_db_table,$sess,$_session;
	$keys = array_keys($_session);
	if(index_of($name,$keys) == -1){
		$sql = "insert into $_db_table values('$sess','$name','$value')";
	}else{
		$sql = "update $_db_table set value = '$value' where sid = '$sess' and variable = '$name'";
	}
	query($sql);
	// this line is to avoid another query to db
	$_session[$name]=$value;
	return mysql_affected_rows();
}



function get_session_vars(){
	global $_db_table,$sess;
	$_session = Array();
	$sql = "select variable, value from $_db_table where sid = '$sess'";
	$res = query($sql);
	$row = array();

	while($res->fetchInto ($row,DB_FETCHMODE_ASSOC)){
		$_session[$row['variable']]=$row['value'];
	}
	return $_session;
}

function session_flush(){
	global $sess,$_db_table,$_db_table_config;
	session_kill();
	$sql ="delete from $_db_table where sid ='$sess';";
	query($sql);
	$sql = "delete from $_db_table_config where sid = '$sess'";
	query($sql);
	return (mysql_error()!=""?false:true);
}

function session_kill(){
	global $sess,$_db_table_config;
	$sql = "update $_db_table_config set logout=1";
	query($sql);
	setcookie('badger_sess', false, 0, '/');
}

function get_session_length(){
	global $sess,$_db_table_config;
	$sql = "select NOW()-start from $_db_table_config where sid = '$sess'";
	$res = query($sql);
	$row = array();
	
	$res->fetchInto($row,DB_FETCHMODE_ASSOC);
	
	return $row['NOW()-start'];
}


/*
	miscelaneous functions
*/

function query($sql){
	global $badgerDb;
	//$res = mysql_query($sql,$_db);
	$res =& $badgerDb->query($sql);
	if(PEAR::isError($res)){
		die ("<hr><br>". $res->getMessage() ."<br>".$sql."<hr>");
	}
	return $res;
}

function getmicrotime(){
	list($usec, $sec) =  explode(" ", microtime());
	return ((double)$usec + (double) $sec);
}

function index_of($value,$array){
	$i = 0;
	if(count($array)>0) { 
		while(isset($array[$i]) && $array[$i] != $value && $i < count($array)){
			$i++;
		}
		if(!isset($array[$i])){
			$i--;
		};
		return $array[$i]==$value?$i:-1;
	}
	return -1;
}


?>
