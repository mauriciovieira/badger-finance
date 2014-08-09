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
define("BADGER_ROOT", "../.."); 
require_once BADGER_ROOT . '/includes/fileHeaderBackEnd.inc.php';
require_once BADGER_ROOT . '/core/pageSettings/PageSettings.class.php';
require_once BADGER_ROOT . '/core/pageSettings/JSON.php';

$logger->log('getPageSetting: REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

$pageSettings = new PageSettings($badgerDb);

$page = getGPC($_REQUEST, 'page');

switch (getGPC($_REQUEST, 'action')) {
	case 'getSettingNamesList':
		$json = new Services_JSON();
		echo $json->encode($pageSettings->getSettingNamesList($page));
		break; 
	
	case 'getSettingRaw':
		echo $pageSettings->getSettingRaw($page, getGPC($_REQUEST, 'settingName'));
		break;
	
	case 'setSettingRaw':
		$pageSettings->setSettingRaw($page, getGPC($_REQUEST, 'settingName'), getGPC($_REQUEST, 'setting'));
		break;
		 
	case 'getSettingSer':
		$json = new Services_JSON();
		echo $json->encode($pageSettings->getSettingSer($page, getGPC($_REQUEST, 'settingName')));
		break;
	
	case 'setSettingSer':
		$json = new Services_JSON();
		$pageSettings->setSettingSer($page, getGPC($_REQUEST, 'settingName'), $json->decode(getGPC($_REQUEST, 'setting')));
		break;
	
	case 'deleteSetting':
		$pageSettings->deleteSetting($page, getGPC($_REQUEST, 'settingName'));
		break;
}
?>