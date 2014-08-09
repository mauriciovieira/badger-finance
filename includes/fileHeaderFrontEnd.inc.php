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
 * hopefully kill all problems with [un|pre]installed PEAR
 */
ini_set('include_path', '.' . PATH_SEPARATOR . BADGER_ROOT . '/core');

require_once(BADGER_ROOT . "/includes/includes.php");
require_once(BADGER_ROOT . "/core/UserSettings.class.php");
$us = new UserSettings($badgerDb);
$tpl = new TemplateEngine($us, BADGER_ROOT);
$tpl->addCSS("style.css", "print, screen");
$tpl->addCSS("print.css", "print");
$tpl->addJavaScript("js/jsval.js");
require(BADGER_ROOT . "/includes/login.php");
require BADGER_ROOT . '/core/update/checkUpdate.php';
?>
