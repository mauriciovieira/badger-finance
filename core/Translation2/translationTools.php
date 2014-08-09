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
require_once BADGER_ROOT . '/core/Translation2/Admin.php';

function addTranslation($page, $id, $de, $en, $es) {
	global $driver;
	global $badgerDbConnectionInfo;
	
	$tra =& Translation2_Admin::factory($driver, $badgerDbConnectionInfo);
	
	$tra->add($id, $page, array('de' => $de, 'en' => $en, 'es' => $es));
}

function modifyTranslation($page, $id, $de, $en, $es) {
	global $driver;
	global $badgerDbConnectionInfo;

	$tra =& Translation2_Admin::factory($driver, $badgerDbConnectionInfo);
	
	$tra->update($id, $page, array('de' => $de, 'en' => $en, 'es' => $es));
}
?>