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
define('BADGER_ROOT', '../..'); 
require_once BADGER_ROOT . '/includes/fileHeaderBackEnd.inc.php';
require_once BADGER_ROOT . '/modules/account/accountCommon.php';


//help functions for automatical calculation of pocket money from the finished transactions

$startSpendingDate = getGPC($_POST, 'startDate', 'DateFormatted');
$accountId = getGPC($_POST, 'selectedAccount', 'int');
$spendingMoney = getSpendingMoney($accountId, $startSpendingDate);
$spendingMoney->mul(-1);
$calculatedPocketMoney = $spendingMoney->getFormatted();

echo $calculatedPocketMoney;

require_once BADGER_ROOT . "/includes/fileFooter.php";
?>