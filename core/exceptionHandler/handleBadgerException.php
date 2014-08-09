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
 * @version $LastChangedRevision: 869 $
 */

/**
 * function called upon by global exception handler
 * 
 * @param object $e  exception  thrown
 * @return void
 */
function handleBadgerException($e){
	
	/**
	 * Object containing global logging information
	 * 
	 * @var object
	 */	
	global $logger; 
	
	echo "<b>" ;
	echo getBadgerTranslation2( 'badgerException' , 'Error' );
	echo "</b><br />";
	echo getBadgerTranslation2( $e->getBadgerErrorPage(), $e->getBadgerErrorId());	

	/**
	 * Compiled error message
	 * 
	 * @var string 
	 */
	$loggedError = "ERROR: - ERROR Module: " . $e->getBadgerErrorPage() . ", ERROR Code: ". $e->getBadgerErrorId(). ", Error Description: ".getBadgerTranslation2( $e->getBadgerErrorPage(), $e->getBadgerErrorId(), 'en') ." ON LINE " . $e->getLine() . " IN FILE " . $e->getFile(). " ADDITIONAL INFO " . $e->getAdditionalInfo();// compile error message to be logged
	$logger->log($loggedError); //write to log file
}

?>