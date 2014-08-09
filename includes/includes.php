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
 
 //Includes

/**
 * hopefully kill all problems with [un|pre]installed PEAR
 */
#ini_set('include_path', '.' . PATH_SEPARATOR . BADGER_ROOT . '/core');

/**
 * The current Version of BADGER
 */
define('BADGER_VERSION', '1.0');
define ('BADGER_RELEASE_DATE', '2009-02-08');

/**
 * Common functions
 */
require_once BADGER_ROOT . '/core/common.php';

/**
 * Date class
 */
require_once BADGER_ROOT . '/core/Date.php';

/**
 * Amount class
 */
require_once BADGER_ROOT . '/core/Amount.class.php';

/**
 * makes use of the dbAdapter database abstraction layer
 */
 require_once BADGER_ROOT.'/core/dbAdapter/DB.php';

/**
 * global config values
 */
 require_once BADGER_ROOT.'/includes/config.inc.php';
 
 /**
 * makes global logging functionality available on all pages
 */
 require_once BADGER_ROOT.'/core/log/badgerLog.php';
 
 /**
 * makes global badgerException class available
 */
 require_once BADGER_ROOT.'/core/exceptionHandler/BadgerException.class.php';
 
 /**
 * Exception handling function available on all pages including this file
 */
 require_once BADGER_ROOT.'/core/exceptionHandler/handleBadgerException.php';
 
  /**
 * Catches uncaught exceptions
 */
 require_once BADGER_ROOT.'/core/exceptionHandler/catchException.php';
 
/**
 * makes translation libs available
 */
require_once BADGER_ROOT.'/includes/openDbConnection.php'; 
 
/**
 * makes translation libs available
 */
require_once BADGER_ROOT.'/core/Translation2/Translation2.php';

/**
 * implements custom translation method
 */
require_once BADGER_ROOT.'/core/Translation2/badgerTranslation2.php';

/**
 * makes template engine available
 */
require_once BADGER_ROOT.'/core/templateEngine/templateEngine.class.php';

/**
 * makes widget engine available
 */
require_once BADGER_ROOT.'/core/widgets/WidgetsEngine.class.php';

/**
 * makes user settings available
 */
require_once(BADGER_ROOT . "/core/UserSettings.class.php");

/**
 * Include Session Management
 */
require_once(BADGER_ROOT . "/core/SessionManager/session.ses.php");
?>