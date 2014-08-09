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
define ('BADGER_ROOT', '..');

require_once BADGER_ROOT . '/includes/fileHeaderFrontEnd.inc.php';

$widgets = new WidgetEngine($tpl); 
$widgets->addNavigationHead();

$pageHeading = getBadgerTranslation2('about', 'title');
echo $tpl->getHeader($pageHeading);

$badgerImage = $widgets->addImage('badger-logo.gif');
$version = BADGER_VERSION;
$from = getBadgerTranslation2('about', 'from');
//$dateObj = new Date(filemtime(BADGER_ROOT . '/includes/includes.php'));
//$date = $dateObj->getFormatted();
$date = BADGER_RELEASE_DATE;
$releasedUnder = getBadgerTranslation2('about', 'published');
$copyrightBy = getBadgerTranslation2('about', 'members');
$developerTeam = getBadgerTranslation2('about', 'team');
$usedComponents = getBadgerTranslation2('about', 'programms');
$contributors = getBadgerTranslation2('about','contributors');
$by = getBadgerTranslation2('about', 'by');

$pageHeading = $pageHeading;
eval('echo "' . $tpl->getTemplate('about') . '";');
eval('echo "' . $tpl->getTemplate('badgerFooter') . '";');

require_once BADGER_ROOT . '/includes/fileFooter.php';
?>