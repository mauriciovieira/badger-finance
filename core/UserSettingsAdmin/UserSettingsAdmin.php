<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://www.badger-finance.org 
*
**/
define("BADGER_ROOT", "../..");
require_once(BADGER_ROOT . "/includes/fileHeaderFrontEnd.inc.php");

//$us->setProperty('badgerPassword',md5("badger"));

// Was the form for change of User Settings sent?
if( isset( $_POST['SubmitUserSettings'] ) ){
	// Validate submitted values
	// Is yet to be implemented
	// So lets just say that all is well for now

	$validation_user_settings = true;
	$validation_user_settings_errors = "";
	
	
	// is something written in the change password fields?
	if (
		(
			(isset($_POST['OldPassword']) && getGPC($_POST, 'OldPassword') == "")
			&&
			(isset($_POST['NewPassword']) && getGPC($_POST, 'NewPassword') == "")
			&&
			(isset($_POST['NewPasswordConfirm']) && getGPC($_POST, 'NewPasswordConfirm') == "")
		)
	)
	{
		$change_password = false;
	} else {
		$change_password = true;
		
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
	};
	
	
	if((
		isset($validation_user_settings) && $validation_user_settings == true
		&&
		$change_password == true
		&&
		isset($validation_change_password )	&&	$validation_change_password == true
	)||(
		isset($validation_user_settings) && $validation_user_settings == true
		&&
		$change_password == false
	)){
		$us->setProperty('badgerTemplate',getGPC($_POST, 'Template'));
		$us->setProperty('badgerLanguage',getGPC($_POST, 'Language'));
		$us->setProperty('badgerDateFormat',getGPC($_POST, 'DateFormat'));
		$us->setProperty('badgerMaxLoginAttempts',getGPC($_POST, 'MaximumLoginAttempts', 'integer'));
		$us->setProperty('badgerLockOutTime',getGPC($_POST, 'LockOutTime', 'integer'));
		$us->setProperty('badgerStartPage', getGPC($_POST, 'StartPage'));
		$us->setProperty('badgerSessionTime', getGPC($_POST, 'SessionTime', 'integer'));

		if(getGPC($_POST, 'Seperators') == ".,"){
			$us->setProperty('badgerDecimalSeparator',",");
			$us->setProperty('badgerThousandSeparator',".");
		}else{
			$us->setProperty('badgerDecimalSeparator',".");
			$us->setProperty('badgerThousandSeparator',",");
		};
		if($change_password == true){
			$us->setProperty('badgerPassword',md5(getGPC($_POST, 'NewPassword')));
			//set new valid session, with new password
			set_session_var('password',md5(getGPC($_POST, 'NewPassword')));
		};
		

		if (isset($_POST['futureCalcSpan'])) {
			$us->setProperty('amountFutureCalcSpan', getGPC($_POST, 'futureCalcSpan', 'integer'));
		}

		if (isset($_POST['autoExpandPlannedTransactions'])) {
			$us->setProperty('autoExpandPlannedTransactions', getGPC($_POST, 'autoExpandPlannedTransactions', 'checkbox'));
		}

		$us->setProperty('matchingDateDelta', getGPC($_POST, 'matchingDateDelta', 'integer'));
		$us->setProperty('matchingAmountDelta', getGPC($_POST, 'matchingAmountDelta', 'integer') / 100);
		$us->setProperty('matchingTextSimilarity', getGPC($_POST, 'matchingTextSimilarity', 'integer') / 100);
	};
	
} else {
	$change_password = false;
};

// Re-Initialization of the tpl-engine after tpl change
$tpl = new TemplateEngine($us, BADGER_ROOT);
$tpl->addCSS("style.css", "print, screen");
$tpl->addCSS("print.css", "print");
$tpl->addJavaScript("js/jsval.js");

$widgets = new WidgetEngine($tpl);
$widgets->addJSValMessages(); 
$widgets->addToolTipJS();
$widgets->addNavigationHead();

$pageHeading = getBadgerTranslation2('UserSettingsAdmin', 'title');

echo $tpl->getHeader($pageHeading);
echo $widgets->addToolTipLayer();

// Print form for change of User Settings.

$USFormLabel = getBadgerTranslation2('UserSettingsAdmin','user_settings_heading');
$FsHeading = getBadgerTranslation2('UserSettingsAdmin', 'fs_heading');
//$templates = array();

$templatesString = "\$templates = array(";
$first = true;

//directory listing of the /tpl/ - folder
if($handle = opendir(BADGER_ROOT . '/tpl')){
	while($file = readdir($handle)) {
		if($file != "." && $file != ".." && $file != ".svn") {
			if($first == true) {
				$templatesString .= "\"" . $file . "\"" . "=>" . "\"" . $file . "\"";
				$first = false;
				}
			else {
				$templatesString .= ",\"" . $file . "\"" . "=>" . "\"". $file . "\"";
			};
		};
	};
};

$templatesString .= ");";

eval ($templatesString);


$TemplateLabel = $widgets->createLabel("Template", getBadgerTranslation2('UserSettingsAdmin','template_name'), true);
$TemplateField = $widgets->createSelectField("Template", $templates, $default=$us->getProperty('badgerTemplate'), $description=getBadgerTranslation2('UserSettingsAdmin','template_description'), true, 'style="width: 10em;"');

$langs = $tr->getLangs();
$LanguageLabel = $widgets->createLabel("Language", getBadgerTranslation2('UserSettingsAdmin','language_name'), true);

$LanguageField = $widgets->createSelectField("Language", $langs, $default=$us->getProperty('badgerLanguage'), $description=getBadgerTranslation2('UserSettingsAdmin','language_description'), $mandatory=true, 'style="width: 10em;"');

$date_formats = array(
	"dd.mm.yyyy" => getBadgerTranslation2('DateFormats','dd.mm.yyyy'),
	"dd/mm/yyyy" => getBadgerTranslation2('DateFormats','dd/mm/yyyy'),
	"dd-mm-yyyy" => getBadgerTranslation2('DateFormats','dd-mm-yyyy'),
	"yyyy-mm-dd" => getBadgerTranslation2('DateFormats','yyyy-mm-dd'),
	"yyyy/mm/dd" => getBadgerTranslation2('DateFormats','yyyy/mm/dd'),
	'mm/dd/yy' => getBadgerTranslation2('DateFormats', 'mm/dd/yy')
);

$DateFormatLabel = $widgets->createLabel("DateFormat", getBadgerTranslation2('UserSettingsAdmin','date_format_name'), true);
$DateFormatField = $widgets->createSelectField("DateFormat", $date_formats, $default=$us->getProperty('badgerDateFormat'), $description=getBadgerTranslation2('UserSettingsAdmin','date_format_description'), $mandatory=true, 'style="width: 10em;"');

$seperators = array(
	".," => "12.345,67",
	",." => "12,345.67"
);

if($us->getProperty('badgerDecimalSeparator') == ","){
	$seperators_default = ".,";
}else{
	$seperators_default = ",.";
};

$SeperatorsLabel = $widgets->createLabel("Seperators", getBadgerTranslation2('UserSettingsAdmin','seperators_name'), true);
$SeperatorsField = $widgets->createSelectField("Seperators", $seperators, $default=$seperators_default, $description=getBadgerTranslation2('UserSettingsAdmin','seperators_description'), $mandatory=true, 'style="width: 10em;"');
		
$MaxLoginLabel = $widgets->createLabel("MaximumLoginAttempts", getBadgerTranslation2('UserSettingsAdmin','maximum_login_attempts_name'), true);
$MaxLoginField = $widgets->createField("MaximumLoginAttempts", 0, $us->getProperty('badgerMaxLoginAttempts'), getBadgerTranslation2('UserSettingsAdmin','maximum_login_attempts_description'), true, 'text', ' regexp="BADGER_NUMBER" style="width: 10em;"');


$LockOutTimeLabel = $widgets->createLabel("LockOutTime", getBadgerTranslation2('UserSettingsAdmin','lock_out_time_name'), true);
$LockOutTimeField = $widgets->createField("LockOutTime", 0, $us->getProperty('badgerLockOutTime'), getBadgerTranslation2('UserSettingsAdmin','lock_out_time_description'), true, 'text', ' regexp="BADGER_NUMBER" style="width: 10em;"');


$StartPageLabel = $widgets->createLabel("StartPage", getBadgerTranslation2('UserSettingsAdmin','start_page_name'), true);
$StartPageField = $widgets->createField("StartPage", 0, $us->getProperty('badgerStartPage'), getBadgerTranslation2('UserSettingsAdmin','start_page_description'), true, 'text', 'style="width: 10em;"');

$SessionTimeLabel = $widgets->createLabel("SessionTime", getBadgerTranslation2('UserSettingsAdmin','session_time_name'), true);
$SessionTimeField = $widgets->createField("SessionTime", 0, $us->getProperty('badgerSessionTime'), getBadgerTranslation2('UserSettingsAdmin','session_time_description'), true, 'text', 'regexp="BADGER_NUMBER" style="width: 10em;"');

try {
	$preCalc = $us->getProperty('amountFutureCalcSpan');
} catch (BadgerException $ex) {
	$preCalc = 12;
}

$futureCalcSpanLabel = $widgets->createLabel('futureCalcSpan', getBadgerTranslation2('UserSettingsAdmin', 'futureCalcSpanLabel'), true);
$futureCalcSpanField = $widgets->createField('futureCalcSpan', 0, $preCalc, getBadgerTranslation2('UserSettingsAdmin', 'futureCalcSpanDescription'), true, 'text', 'regexp="BADGER_NUMBER" style="width: 10em;"');

$autoExpandPlannedTransactionsLabel = $widgets->createLabel("autoExpandPlannedTransactions", getBadgerTranslation2('UserSettingsAdmin', 'autoExpandPlannedTransactionsName'), true);
$autoExpandPlannedTransactionsField = $widgets->createField("autoExpandPlannedTransactions", 0, 1, getBadgerTranslation2('UserSettingsAdmin','autoExpandPlannedTransactionsDescription'), false, 'checkbox', $us->getProperty('autoExpandPlannedTransactions') ? 'checked="checked"' : '');

try {
	$matchingDateDelta = $us->getProperty('matchingDateDelta');
} catch (BadgerException $ex) {
	$matchingDateDelta = 5;
}

try {
	$matchingAmountDelta = 100 * $us->getProperty('matchingAmountDelta');
} catch (BadgerException $ex) {
	$matchingAmountDelta = 10;
}

try {
	$matchingTextSimilarity = 100 * $us->getProperty('matchingTextSimilarity');
} catch (BadgerException $ex) {
	$matchingTextSimilarity = 25;
}

$matchingHeading = getBadgerTranslation2('UserSettingsAdmin', 'matchingHeading');

$matchingDateDeltaLabel = $widgets->createLabel('matchingDateDelta', getBadgerTranslation2('UserSettingsAdmin', 'matchingDateDeltaLabel'), true);
$matchingDateDeltaField = $widgets->createField('matchingDateDelta', 0, $matchingDateDelta, getBadgerTranslation2('UserSettingsAdmin', 'matchingDateDeltaDescription'), true, 'text', ' regexp="BADGER_NUMBER" style="width: 10em;"');

$matchingAmountDeltaLabel = $widgets->createLabel('matchingAmountDelta', getBadgerTranslation2('UserSettingsAdmin', 'matchingAmountDeltaLabel'), true);
$matchingAmountDeltaField = $widgets->createField('matchingAmountDelta', 0, $matchingAmountDelta, getBadgerTranslation2('UserSettingsAdmin', 'matchingAmountDeltaDescription'), true, 'text', ' regexp="BADGER_NUMBER" style="width: 10em;"');

$matchingTextSimilarityLabel = $widgets->createLabel('matchingTextSimilarity', getBadgerTranslation2('UserSettingsAdmin', 'matchingTextSimilarityLabel'), true);
$matchingTextSimilarityField = $widgets->createField('matchingTextSimilarity', 0, $matchingTextSimilarity, getBadgerTranslation2('UserSettingsAdmin', 'matchingTextSimilarityDescription'), true, 'text', ' regexp="BADGER_NUMBER" style="width: 10em;"');

// Print Form for change of password 

$PWFormLabel = getBadgerTranslation2('UserSettingsAdmin','change_password_heading');

$OldPwLabel = $widgets->createLabel("OldPassword", getBadgerTranslation2('UserSettingsAdmin','old_password_name'), false);
$OldPwField = $widgets->createField("OldPassword", 20, "", getBadgerTranslation2('UserSettingsAdmin','old_password_description'), false, 'password');

$NewPwLabel = $widgets->createLabel("NewPassword", getBadgerTranslation2('UserSettingsAdmin','new_password_name'), false);
$NewPwField = $widgets->createField("NewPassword", 20, "", getBadgerTranslation2('UserSettingsAdmin','new_password_description'), false, 'password');

$ConfPwLabel = $widgets->createLabel("NewPasswordConfirm", getBadgerTranslation2('UserSettingsAdmin','new_password_confirm_name'), false);
$ConfPwField = $widgets->createField("NewPasswordConfirm", 20, "", getBadgerTranslation2('UserSettingsAdmin','new_password_confirm_description'), false, 'password');

$btnSubmit = $widgets->createButton("SubmitUserSettings", getBadgerTranslation2('UserSettingsAdmin','submit_button'), "submit", "Widgets/accept.gif", "accesskey='s'");

// Begin of Feedback

$Feedback = "<br/>";

if((
		isset($validation_user_settings) && $validation_user_settings == true
		&&
		$change_password == true
		&&
		isset($validation_change_password )	&&	$validation_change_password == true
	)||(
		isset($validation_user_settings) && $validation_user_settings == true
		&&
		$change_password == false
	)
){
	if($change_password == true){
		$Feedback .= getBadgerTranslation2('UserSettingsAdmin','password_change_commited')."<br/>";
	};
$Feedback .= getBadgerTranslation2('UserSettingsAdmin','user_settings_change_commited')."<br/><br/>";
};

if($change_password == true && isset($validation_change_password ) && $validation_change_password != true){
	$Feedback .= $validation_change_password_errors;
};

// If Validation for User Settings had returned
// a bad result, print the error messages
if(isset($validation_user_settings) && $validation_user_settings != true){
	$Feedback .= "<div class=\"USAError\">".$validation_user_settings_errors."</div><br/><br/>";
};

// End of Feedback

eval("echo \"".$tpl->getTemplate("UserSettingsAdmin/UserSettingsAdmin")."\";");
//--


//--

eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");
?>