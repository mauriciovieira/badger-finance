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
/**
 * @author baxxter, sperber 
 * @version $LastChangedRevision$
 */
 
 /**
 * uses Log.php to log data
 */
 require_once BADGER_ROOT.'/core/log/Log.php';
 
 	/**
	 * Name and path of the log file
	 * 
	 * @var string
	 */	
 $filename = LOG_FILE_NAME;
  	/**
	 * Now() in predefined format
	 * 
	 * @var String
	 */	
 $eventDate = date(LOG_DATE_FORMAT); 

 /* Write some entries to the log file. */
  	/**
	 * configuration of the text file
	 * 
	 * @var array of strings
	 */	
 $conf = array('lineFormat' => '%2$s [%3$s] %4$s', 'timeFormat' => '%H:%M:%S');
 //creates logging object 
 $logger = &Log::singleton('file', $filename, $eventDate, $conf);
?>
