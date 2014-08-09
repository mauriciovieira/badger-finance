<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Finance Management
* Visit http://www.badger-finance.org 
*
**/
define("BADGER_ROOT", "../.."); 
require_once(BADGER_ROOT . "/includes/fileHeaderFrontEnd.inc.php");

// set the parameters to connect to your db
$dbinfo = array(
    'hostspec' => DB_HOST,
    'database' => DB_DATABASE_NAME,
    'phptype'  => DB_TYPE,
    'username' => DB_USERNAME,
    'password' => DB_PASSWORD
);

$driver = 'DB';

require_once BADGER_ROOT.'/core/Translation2/Admin.php';
$tra =& Translation2_Admin::factory($driver, $dbinfo);

if(PEAR::isError( $tra )){
	die( $tra->getMessage());	
};

// If required variables have been $_POSTed, add a new Translation
// using the $_POSTed variables.

if(isset($_POST['formsent']) && $_POST['formsent'] == "1" ){
	if(isset($_POST['id']) && $_POST['id'] == ""){
		die("Missing ID. Translation was not added.<br/><br/>");};
	if(isset($_POST['page']) && $_POST['page'] == ""){
		die("Missing page. Translation was not added.<br/><br/>");};
	
	$langs = $tra->getLangs();
	$stringArray = array();
	foreach( $langs as $key=>$value ){
		$stringArray[$key] = $_POST[$key];
	};
	$tra->add($_POST['id'], $_POST['page'], $stringArray);
	print("Translation was added.<br/><br/>");
}

//Print a form to add a new translation with all available languages

$langs = $tra->getLangs();
	
print("<b>Add new Translation:</b><br/><br/>");

print("<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" accept-charset=\"ISO-8859-1\">");

print("Page:<br>");
print("<input name=\"page\" id=\"page\" size=\"50\" value=\"\" type=\"text\"><br>");

print("ID:<br>");
print("<input name=\"id\" id=\"id\" size=\"50\" value=\"\" type=\"text\"><br>");

foreach( $langs as $key=>$value ){
	print($value . ":<br>");
	print("<input name=\"" .$key. "\" id=\"" .$key. "\" size=\"50\" value=\"\" type=\"text\"><br>");
	print("<br>");
};
print("<input value=\"1\" name=\"formsent\" type=\"hidden\">");
print("<input value=\"Go!\" name=\"submit\" type=\"submit\">");
print("</form>");

require_once(BADGER_ROOT . "/includes/fileFooter.php");
?>