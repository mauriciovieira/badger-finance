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

//Include Session Management
//include(BADGER_ROOT . "/core/SessionManager/session.ses.php");

//UserSettings object named "$us" is already existing
//TemplateEngine object named "$tpl" is already existing


// check if this is a mandatory password change
// because of standard password

//$us->setProperty('badgerPassword',md5("badger"));

if(isset($_POST['SubmitMandatoryChangePassword'])){
	// Validate submitted values
	// Is yet to be implemented

	$validation_change_password = true;
	$validation_change_password_errors = "";
	
	if( md5(getGPC($_POST, 'OldPassword')) != $us->getProperty('badgerPassword')){
		$validation_change_password = false;
		$validation_change_password_errors = $validation_change_password_errors.getBadgerTranslation2('UserSettingsAdmin','error_old_password_not_correct')."<br>";
	};
	
	if( getGPC($_POST, 'NewPassword') != getGPC($_POST, 'NewPasswordConfirm')){
		$validation_change_password = false;
		$validation_change_password_errors = $validation_change_password_errors.getBadgerTranslation2('UserSettingsAdmin','error_confirm_failed')."<br>";
	};
	
	if( getGPC($_POST, 'NewPassword') == ""){
		$validation_change_password = false;
		$validation_change_password_errors = $validation_change_password_errors.getBadgerTranslation2('UserSettingsAdmin','error_empty_password')."<br>";
	};
	
	if( getGPC($_POST, 'NewPassword') == "badger"){
		$validation_change_password = false;
		$validation_change_password_errors = $validation_change_password_errors.getBadgerTranslation2('UserSettingsAdmin','error_standard_password')."<br>";
	};
		
	if(isset($validation_change_password ) && $validation_change_password == true){
		//new password is valid
		
		//change pw in usersettings
		$us->setProperty('badgerPassword',md5(getGPC($_POST, 'NewPassword')));
		
		//set new valid session, with new password
		set_session_var('password',md5(getGPC($_POST, 'NewPassword')));
		
		//redirect 
		header('Location: '.$_SERVER['PHP_SELF']);
		
	};
	
	if(isset($validation_change_password ) && $validation_change_password != true){
		// Initialization
		$tpl->addCSS("style.css");
		$widgets = new WidgetEngine($tpl); 
		$widgets->addJSValMessages();
		$widgets->addToolTipJS();
		$widgets->addNavigationHead();
		echo $tpl->getHeader(getBadgerTranslation2('UserSettingsAdmin','site_name')); //write header */
		echo $widgets->addToolTipLayer();
		//end of Initialization
		
		echo($validation_change_password_errors."<br/>");
		echo("<a href\"".$_SERVER['PHP_SELF']."\">".getBadgerTranslation2('UserSettingsAdmin','linktext_after_failed_mandatory_change')."</a>");
	};
	
	exit;
};

// If user is trying to logout, log him out
if(isset($_GET['logout']) && getGPC($_GET, 'logout', 'boolean') == true){
	session_flush();
	unset($_session['password']);
};

//Retrieve md5´ed password from user settings
$readoutpassword = $us->getProperty('badgerPassword');
$passwordcorrect = false;
if (isset($_session['password']) && $readoutpassword == $_session['password']) {
	$passwordcorrect = true;
}
elseif(isset($_POST['password']) && md5(getGPC($_POST, 'password')) == $readoutpassword ) 		{
	$passwordcorrect = true;
	//create session variable
	set_session_var('password',md5(getGPC($_POST, 'password')));
	
	if (!isset($_session['sessionTimeout']) || $_session['sessionTimeout'] != true) {
		$url = BADGER_ROOT . '/' . $us->getProperty('badgerStartPage');
	
		$logger->log('Login redirect URL: ' . $url);

		header('Location: ' . $url);
		
		exit;
	} else {
		set_session_var('sessionTimeout', null);
	}
};

// Check how many times the user tried to log in, stop working after x times
if(isset($_session['number_of_login_attempts']) ) {
	$attempts = $_session['number_of_login_attempts'];
} else {
	$attempts = 0;
};


if($attempts >= $us->getProperty('badgerMaxLoginAttempts') ){
	set_session_var('locked_out_since',time());
};

//--

if(isset($_session['locked_out_since']) && $passwordcorrect != true)
{
	$locked_out_since = $_session['locked_out_since'];
}else{
	$locked_out_since = 0;
};

//---


if( $locked_out_since != 0){
	if($locked_out_since - time() + $us->getProperty('badgerLockOutTime') <= 0){
		set_session_var('locked_out_since','0');
		set_session_var('number_of_login_attempts','0');
	} else {
		// if user locked himself out
		// because of too many login attempts
		echo $tpl->getHeader(getBadgerTranslation2('badger_login', 'header')); //write header
		echo "<br/>";
		print (getBadgerTranslation2('badger_login','locked_out_part_1') . ($locked_out_since - time() + $us->getProperty('badgerLockOutTime')) . getBadgerTranslation2('badger_login','locked_out_part_2'));
		echo "<br/><br/>";
		
		if(isset($_GET)){
			$signature = "?";
			foreach( $_GET as $key=>$value ){
				if($key != "send_password"){
					if($signature != "?"){
						$signature = $signature . "&";
					};
				$signature = $signature . $key . "=" . urlencode(getGPC($_GET, $key));
				};
			};
		} else {
			$signature = "";
		};
		set_session_var('number_of_login_attempts','0');
		die("<a href=\"".$_SERVER['PHP_SELF'].$signature."\">".getBadgerTranslation2('badger_login', 'locked_out_refresh')."</a>");
	};
};

//---

//check if standard password or empty password is used.
//if so, ask user to change his password

if (
	isset($_POST['password']) 
		&& md5(getGPC($_POST, 'password')) == $us->getProperty('badgerPassword') 
		&& md5(getGPC($_POST, 'password')) == '7e59cb5b2f52c763bc846471fe5942e4'
	|| (isset($_session['password'])
		&& $_session['password'] == $us->getProperty('badgerPassword')
		&& $_session['password'] == '7e59cb5b2f52c763bc846471fe5942e4')
) {
	// Initialization

	$tpl->addCSS("style.css");
	$widgets = new WidgetEngine($tpl); 
	$widgets->addJSValMessages();
	$widgets->addToolTipJS();
	echo $tpl->getHeader(getBadgerTranslation2('UserSettingsAdmin','site_name')); //write header */
	echo $widgets->addToolTipLayer();
	
	$passwordcorrect = false;
	//end of Initialization
	
	$MandatoryHeading = "<div class=\"USAHeading\">".getBadgerTranslation2('UserSettingsAdmin','mandatory_change_password_heading')."</div><br/>";
	$MandatoryFsHeading = getBadgerTranslation2('UserSettingsAdmin','mandatory_fs_heading');
	$MandatorySelf = $_SERVER['PHP_SELF'];
	
	foreach( $_POST as $key=>$value ){
		if($key != "password" && $key != "logout") {
			$HiddenField = $widgets->createField($key, "", getGPC($_POST, $key), "", true, 'hidden');
			eval("echo \"".$tpl->getTemplate("Login/hiddenField")."\";");
		};
	};
	
	$MandatoryOldPasswordLabel = $widgets->createLabel("OldPassword", getBadgerTranslation2('UserSettingsAdmin','old_password_name'), true);
	$MandatoryOldPasswordField = $widgets->createField("OldPassword", 25, "", getBadgerTranslation2('UserSettingsAdmin','old_password_description'), true, 'password');
	
	$MandatoryNewPasswordLabel = $widgets->createLabel("NewPassword", getBadgerTranslation2('UserSettingsAdmin','new_password_name'), true); 
	$MandatoryNewPasswordField = $widgets->createField("NewPassword", 25, "", getBadgerTranslation2('UserSettingsAdmin','new_password_description'), true, 'password');
	
	$MandatoryNewPasswordConfirmLabel = $widgets->createLabel("NewPasswordConfirm", getBadgerTranslation2('UserSettingsAdmin','new_password_confirm_name'), true);
	$MandatoryNewPasswordConfirmField = $widgets->createField("NewPasswordConfirm", 25, "", getBadgerTranslation2('UserSettingsAdmin','new_password_confirm_description'), true, 'password');
	
	$MandatorySubmit = $widgets->createButton("SubmitMandatoryChangePassword", getBadgerTranslation2('UserSettingsAdmin','submit_button'), "submit", "Widgets/table_save.gif");
		
	eval("echo \"".$tpl->getTemplate("Login/mandatoryLogin")."\";");
	exit();
};


if($passwordcorrect == false) {	
	// Initialization
	$widgets = new WidgetEngine($tpl);
	$tpl->addJavaScript("js/prototype.js");
	$tpl->addOnLoadEvent("Form.focusFirstElement('loginForm')");
	$widgets->addJSValMessages();
	$widgets->addToolTipJS(); 
		
	echo $tpl->getHeader(getBadgerTranslation2('badger_login', 'header')); //write header
	echo $widgets->addToolTipLayer();
	// End of Initialization
		
	$FsHeading = getBadgerTranslation2('badger_login', 'fs_heading'); 
	
	set_session_var('number_of_login_attempts',$attempts + 1);
	$Heading = "<div class=\"LSPrompt\">" . getBadgerTranslation2('badger_login', 'enter_password') . "</div><br />";
		
	if(isset($_GET['logout']) && getGPC($_GET, 'logout') == "true"){
		$Action = BADGER_ROOT . '/' . $us->getProperty('badgerStartPage');
	}else{
		$Action = $_SERVER['PHP_SELF'];
	};
	
	$PasswordInput = $widgets->createField("password", 50, "", "", true, 'password');
		
	$Feedback = "";
		
	//--
		
	if (isset($GLOBALS['sessionTimeout']) && $GLOBALS['sessionTimeout'] === true) {
		$get_vars = "";
		$isfirst = true;
			
		foreach( $_GET as $key=>$value ){
			if($key != "logout"){
				
				if($key != "password" && $key != "logout") {
					if ($isfirst == true){
						$get_vars .= "?";
						$isfirst = false;
					}else{
						$get_vars .= "&";
					};
				$get_vars .= $key . "=" . urlencode(getGPC($_GET, $key));
				};
			};
		};
		
		unset($isfirst);
			
		$Action .= $get_vars;
			
		eval("echo \"".$tpl->getTemplate("Login/login1")."\";");

		foreach( $_POST as $key=>$value ){
			if($key != "password" && $key != "logout") {
				$HiddenField = $widgets->createField($key, "", getGPC($_POST, $key), "", true, 'hidden');
				//eval("\$rows .=  \"".$tpl->getTemplate("Login/hiddenField")."\";");
				eval("echo \"".$tpl->getTemplate("Login/hiddenField")."\";");
			};
		};
		
		$Feedback = getBadgerTranslation2('badger_login', 'sessionTimeout') . '<br />';
		
		set_session_var('sessionTimeout', true);
	} else {
		eval("echo \"".$tpl->getTemplate("Login/login1")."\";");
	}
			
	//--
		
	//echo $widgets->createButton("submit", getBadgerTranslation2('UserSettingsAdmin','submit_button'), "submit", "Widgets/table_save.gif");
	$SubmitButton = $widgets->createButton("submit", getBadgerTranslation2('UserSettingsAdmin','login_button'), "submit", "Widgets/accept.gif");
		
	if(isset($_POST['password']) && getGPC($_POST, 'password') == ""){
		//print(getBadgerTranslation2('badger_login', 'empty_password')."<br /><br />");
		$Feedback .= getBadgerTranslation2('badger_login', 'empty_password')."<br /><br />";
	}elseif(isset($_POST['password'])){
		//print(getBadgerTranslation2('badger_login', 'wrong_password')."<br /><br />");
		$Feedback .= getBadgerTranslation2('badger_login', 'wrong_password')."<br /><br />";
	};
	
	if(isset($_GET['send_password']) && getGPC($_GET, 'send_password') == "true"){
		//print(getBadgerTranslation2('badger_login', 'ask_really_send')."<br/>");
		$Feedback .= getBadgerTranslation2('badger_login', 'ask_really_send')."<br/>";
		//print("<a href=\"".$_SERVER['PHP_SELF'].$signature."send_password=truetrue\">".getBadgerTranslation2('badger_login', 'ask_really_send_link')."</a><br/>");
		$Feedback .= "<a href=\"".$_SERVER['PHP_SELF'].$signature."send_password=truetrue\">".getBadgerTranslation2('badger_login', 'ask_really_send_link')."</a><br/>";
	};
		
	if(isset($_GET['send_password']) && getGPC($_GET, 'send_password') == "truetrue"){
		//send an E-Mail with a new password to the email adress read from the user settings object
		$newpassword = rand ( 0 , 16000 );
		$newpassword = md5($newpassword);
		$newpassword = substr ( $newpassword, 0, 12 );
		if(mail ( $us->getProperty('badgerPassword'), getBadgerTranslation2('badger_login', 'password_sent_mail_subject'), getBadgerTranslation2('badger_login', 'password_sent_mail_part_1').$newpassword.getBadgerTranslation2('badger_login', 'password_sent_mail_part_2'), 'From: forgottenpassword@donotreply.com') ){
			//print(getBadgerTranslation2('badger_login', 'sent_password')."<br /><br />");
			$Feedback .= getBadgerTranslation2('badger_login', 'sent_password')."<br /><br />";
		} else {
			//print(getBadgerTranslation2('badger_login', 'sent_password_failed')."<br/>");
			$Feedback .= getBadgerTranslation2('badger_login', 'sent_password_failed')."<br/>";
		};
	};
	if(isset($_GET['logout']) && getGPC($_GET, 'logout', 'boolean') == true){
		//echo getBadgerTranslation2('badger_login', 'you_are_logout');
		$Feedback .= getBadgerTranslation2('badger_login', 'you_are_logout');
	};
	
	eval("echo \"".$tpl->getTemplate("Login/login2")."\";");
	exit();		
} else{
	set_session_var('number_of_login_attempts', 0);
};
?>