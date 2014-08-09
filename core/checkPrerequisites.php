<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://badger.berlios.org 
*
**/
error_reporting(E_ALL);
ini_set('display_errors', true);

define('BADGER_ROOT', '..');

$title = 'BADGER finance prerequisites check';
$error = false;

echo '<?xml version="1.0" encoding="iso-8859-1"?>';
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
echo '<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">';
echo '<head>';
echo "<title>$title</title>";
echo '<style type="text/css">
	* {
		font-family: Arial, sans-serif;
	}
	.passed {
		background-color: green;
		font-weight: bold;
		padding: 5px;
	}
	.failed {
		background-color: red;
		font-weight: bold;
		padding: 5px;
	}
</style>';
echo '</head>';
echo '<body>';
echo "<h1>$title</h1>";

echo '<h2>PHP version</h2>';
$php4 = function_exists('version_compare');
if ($php4) {
	$php5 = (version_compare(phpversion(), '5.0.0') > 0);
	if ($php5) {
		echo '<p class="passed">PHP version ' . phpversion() . '</p>';
	} //php5
} else {
	$php5 = false;
} //php4
if (!$php5) {
	echo '<p class="failed">PHP version ' . phpversion() . '</p>';
	echo '<p>BADGER finance needs at least PHP version 5.</p>';
	$error = true;
} //!php5

echo '<h2>bcmath extension</h2>';
$bcmath = extension_loaded('bcmath');
if ($bcmath) {
	echo '<p class="passed">loaded</p>';
} else {
	echo '<p class="failed">not loaded</p>';
	echo '<p>The BCMath Arbitrary Precision Mathematics extension is needed for exact calculation of amounts.</p>';
	$error = true;
} //bcmath

echo '<h2>mysql extension</h2>';
$mysql = extension_loaded('mysql');
if ($mysql) {
	echo '<p class="passed">loaded</p>';
} else {
	echo '<p class="failed">not loaded</p>';
	echo '<p>The MySQL extension is the only database backend BADGER finance is tested with. We currently do not support any other database backend.</p>';
	$error = true;
} //mysql

echo '<h2>gd extension</h2>';
$gd = extension_loaded('gd');
if ($gd) {
	echo '<p class="passed">loaded</p>';
} else {
	echo '<p class="failed">not loaded</p>';
	echo '<p>The GD extension is required for drawing the charts of advanced statistics. Badger is usable without this, but only half as pretty.</p>';
	$error = true;
} //mysql

echo '<h2>Config file</h2>';
$configFilePath = '../includes/config.inc.php';
$configFile = file_exists($configFilePath);
if ($configFile) {
	echo '<p class="passed">exists</p>';
	
	echo '<h2>Parsing config file</h2>';
	$parsingConfigFile = include $configFilePath;
	if ($parsingConfigFile) {
		echo '<p class="passed">parsed</p>';
		
		echo '<h2>Config settings defined</h2>';
		$dbType = checkSetting('DB_TYPE');
		$dbUsername = checkSetting('DB_USERNAME');
		$dbPassword = checkSetting('DB_PASSWORD', false);
		$dbHost = checkSetting('DB_HOST');
		$dbDatabaseName = checkSetting('DB_DATABASE_NAME');
		$logFileName = checkSetting('LOG_FILE_NAME');
		$logDateFormat = checkSetting('LOG_DATE_FORMAT');
		
		if ($logFileName) {
			echo '<h2>Log file writeable</h2>';
			$logFileWriteable = is_writable(LOG_FILE_NAME);
			if ($logFileWriteable) {
				echo '<p class="passed">writable</p>';
			} else {
				echo '<p class="failed">not writable</p>';
				echo '<p>The log file needs to be writable to the web server process. Often, you can achieve this by executing <i>chmod 666 &lt;filename&gt;</i>.</p>'; 
				$error = true;
			} //logFileWritable
		} //logFileName
		
		if ($dbType) {
			echo '<h2>Database type</h2>';
			if (DB_TYPE == 'mysql') {
				echo '<p class="passed">mysql</p>';
				
				if ($mysql && $dbUsername && $dbPassword && $dbHost && $dbDatabaseName) {
					echo '<h2>Database connection</h2>';
					
					echo '<h3>Load database abstraction layer</h3>';
					
					$dbAbstractionLayer = include '../core/dbAdapter/DB.php';
					if ($dbAbstractionLayer) {
						echo '<p class="passed">suceeded</p>';
						
						echo '<h3>Open database connection</h3>';

						$badgerDbConnectionInfo = array(
							'phptype' => DB_TYPE,
							'username' => DB_USERNAME,
							'password' => DB_PASSWORD,
							'hostspec' => DB_HOST,
							'database' => DB_DATABASE_NAME
						);
						$badgerDb =& DB::Connect($badgerDbConnectionInfo, array('debug' => 9999));
						$dbConnection = !PEAR::isError($badgerDb);
						if ($dbConnection) {
							echo '<p class="passed">opened</p>';
						} else {
							echo '<p class="failed">failed</p>';
							echo '<p>The database connection could not be opened. Error message:</p>';
							echo '<pre>' . $badgerDb->getMessage() . "<br />\n" . $badgerDb->getUserInfo() . "<br />\n" . $badgerDb->getDebugInfo() . '</pre>';
							$error = true;
						} //dbConnection
					} else {
						echo '<p class="failed">failed</p>';
						echo '<p>BADGER finance uses the DB database abstraction layer. Check if all files were downloaded and unpacked correctly.</p>';
						$error = true;
					} //dbAbstractionLayer
				} //prerequisites of database connection
			} else {
				echo '<p class="failed">not mysql</p>';
				echo '<p>Currently, BADGER finance supports only MySQL as database backend.</p>';
				$error = true;
			} //dbType mysql
		} //dbType
	} else {
		echo '<p class="failed">not parsed</p>';
		$error = true;
	} //parsing config file
} else {
	echo '<p class="failed">does not exist</p>';
	echo '<p>You need to have the config file <i>' . realpath($configFilePath) . '</i>.</p>';
	$error = true;
} //configFile

echo '<h1>Prerequisites check summary</h1>';
if (!$error) {
	echo '<p class="passed">All checks passed. BADGER finance will work on your system.</p>';
} else {
	echo '<p class="failed">Some checks failed. Please correct these issues and re-run this check.</p>';
}

echo '<h2>Note</h2><p>This check does not test if all files are correctly unpacked. It also does not check if the database content was installed correctly.</p>';

echo '</body></html>';

function checkSetting($settingName, $showValue = true) {
	global $error;
	
	echo "<h3>$settingName</h3>";
	$ok = defined($settingName);
	if ($ok) {
		if ($showValue) {
			$value = constant($settingName);
		} else {
			$value = '(not shown)';
		}

		echo '<p class="passed">Value: ' . $value . '</p>';
		return true;
	} else {
		echo '<p class="failed">Not defined</p>';
		$error = true;
		return false;
	}
} // function checkSetting
?>