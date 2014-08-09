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

require(BADGER_ROOT . "/includes/login_backend.php");

?>
