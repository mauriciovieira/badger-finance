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
function getBadgerDbVersion() {
	global $us;
	
	try {
		$dbVersion = $us->getProperty('badgerDbVersion');
	} catch (BadgerException $ex) {
		$dbVersion = '1.0 beta';
		$us->setProperty('badgerDbVersion', $dbVersion);
	}
	
	return $dbVersion;
}
?>