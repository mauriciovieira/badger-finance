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
define("BADGER_ROOT", "../..");
require_once(BADGER_ROOT . "/includes/fileHeaderFrontEnd.inc.php");
require_once(BADGER_ROOT . "/core/widgets/DataGrid.class.php");
require_once(BADGER_ROOT . '/modules/account/AccountManager.class.php');
require_once BADGER_ROOT . '/modules/account/accountCommon.php';

if (isset($_GET['accountID'])) {
	$accountID = getGPC($_GET, 'accountID', 'integer');
} else {
	throw new badgerException('accountOverviewPlanned', 'noAccountID', '');
}

$am = new AccountManager($badgerDb);
$account = $am->getAccountById($accountID);

$pageTitle = getBadgerTranslation2 ('accountOverviewPlanned','pageTitle');
$pageTitle .= ": ".$account->getTitle();

$widgets = new WidgetEngine($tpl);
$dataGrid = new DataGrid($tpl, "Account".$accountID."planned");
$dataGrid->sourceXML = BADGER_ROOT."/core/XML/getDataGridXML.php?q=Account&qp=$accountID;planned";
$dataGrid->headerName = array(
	getBadgerTranslation2('accountOverview', 'colTitle'),
	getBadgerTranslation2('accountOverviewPlanned', 'colBeginDate'),
	getBadgerTranslation2('accountOverviewPlanned', 'colEndDate'),
	//getBadgerTranslation2('accountOverviewPlanned', 'colUnit'),
	//getBadgerTranslation2('accountOverviewPlanned', 'colFrequency'),
	getBadgerTranslation2('accountOverviewPlanned', 'colRepeatText'),
	getBadgerTranslation2('accountOverview', 'colAmount'),
	getBadgerTranslation2('accountOverview', 'colCategoryTitle'));
//$dataGrid->columnOrder = array("title","beginDate", "endDate", "repeatUnit", "repeatFrequency", "amount", "categoryTitle");  
$dataGrid->columnOrder = array("title","beginDate", "endDate", "repeatText", "amount", "categoryTitle");  
$dataGrid->height = "350px";
//$dataGrid->headerSize = array(210, 95, 85, 70, 75, 120, 200);
$dataGrid->headerSize = array(210, 95, 85, 85, 120, 200);
//$dataGrid->cellAlign = array("left", "right", "right", "left", "right", "right", "left");
$dataGrid->cellAlign = array("left", "right", "right", "left", "right", "left");
$dataGrid->deleteAction = "Transaction.php?action=delete&backTo=planned&accountID=$accountID&ID=";
$dataGrid->editAction = "Transaction.php?action=edit&backTo=planned&accountID=$accountID&ID=";
$dataGrid->newAction = "Transaction.php?action=new&backTo=planned&accountID=$accountID";
$dataGrid->initDataGridJS();

$widgets->addNavigationHead();
echo $tpl->getHeader($pageTitle);

echo "<h1>$pageTitle</h1>";

echo $widgets->createButton("btnNewPlanned", getBadgerTranslation2('accountTransaction', 'newPlannedTrans'), "dataGridAccount".$accountID."planned.callNewEvent('type=planned')", "Widgets/table_add.gif");
echo ' ';
echo $widgets->createButton("btnEdit", getBadgerTranslation2('dataGrid', 'edit'), "dataGridAccount".$accountID."planned.callEditEvent()", "Widgets/table_edit.gif");
echo ' ';
echo $widgets->createButton("btnDelete", getBadgerTranslation2('dataGrid', 'delete'), "dataGridAccount".$accountID."planned.callDeleteEvent()", "Widgets/table_delete.gif");
echo ' ';
echo $widgets->createButton("btnShowTransactions", getBadgerTranslation2('accountOverviewPlanned', 'showTrans'),  "location.href = location.href.replace(/AccountOverviewPlanned\.php/, 'AccountOverview.php');", "Account/finished_transaction.png");

echo $dataGrid->writeDataGrid();

eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");

require_once(BADGER_ROOT . "/includes/fileFooter.php");
?>