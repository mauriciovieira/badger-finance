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
require_once BADGER_ROOT . '/core/update/common.php';

if (getBadgerDbVersion() !== BADGER_VERSION) {

	if (substr($_SERVER['PHP_SELF'], -23) !== '/core/update/update.php') {

		$url = BADGER_ROOT . '/core/update/update.php';
		
		$logger->log('Update: Redirect to Update URL: ' . $url);
	
		header('Location: ' . $url);
		
		exit;
	}
}
?>