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
	throw new badgerException('accountOverview', 'noAccountID', '');
}

$am = new AccountManager($badgerDb);
$account = $am->getAccountById($accountID);

$pageTitle = getBadgerTranslation2 ('accountOverview','pageTitle');
$pageTitle .= ": ".$account->getTitle();

$widgets = new WidgetEngine($tpl);
$widgets->addToolTipJS();
$widgets->addCalendarJS();
$widgets->addTwistieSectionJS();

$dataGrid = new DataGrid($tpl,"Account$accountID");
$dataGrid->sourceXML = BADGER_ROOT."/core/XML/getDataGridXML.php?q=Account&qp=$accountID";
$dataGrid->headerName = array(
	getBadgerTranslation2('accountOverview', 'colValutaDate'),
	getBadgerTranslation2('accountOverview', 'colTitle'), 
	getBadgerTranslation2('accountOverview', 'colType'),
	getBadgerTranslation2('accountOverview', 'colAmount'),
	getBadgerTranslation2('accountOverview', 'colBalance'),
	getBadgerTranslation2('accountOverview', 'colCategoryTitle'));
$dataGrid->columnOrder = array("valutaDate","title","type","amount","balance","concatCategoryTitle");  
$dataGrid->height = "350px";
$dataGrid->headerSize = array(90,350,39,80,120,200);
$dataGrid->cellAlign = array("left","left","center","right","right","left");
$dataGrid->deleteRefreshType = "refreshDataGrid";
$dataGrid->deleteAction = "Transaction.php?action=delete&accountID=$accountID&ID=";
$dataGrid->editAction = "Transaction.php?action=edit&accountID=$accountID&ID=";
$dataGrid->newAction = "Transaction.php?action=new&accountID=$accountID";
$dataGrid->initDataGridJS();

$widgets->addNavigationHead();
echo $tpl->getHeader($pageTitle);

// DataGrid Filter
$legendFilter = getBadgerTranslation2('dataGrid', 'filterLegend');

$datagGridFilterArray = $dataGrid->getNumberFilterSelectArray();
$datagGridStringFilterArray = $dataGrid->getStringFilterSelectArray();
$datagGridDateFilterArray = $dataGrid->getDateFilterSelectArray();
	
$titleLabel = $widgets->createLabel("title", getBadgerTranslation2('accountTransaction', 'title'), false);
$titleField = $widgets->createField("title", 30, "", "", false, "text", "");
$titleFilterOperator = $widgets->createSelectField("titleFilter", $datagGridStringFilterArray, "", "", false, "style='width: 95px;'");
	
$valutaDateLabel = $widgets->createLabel("valutaDate", getBadgerTranslation2('accountTransaction', 'valutaDate'), false);
$valutaDateField = $widgets->addDateField("valutaDate", "");
$valutaDateFilterOperator = $widgets->createSelectField("valutaDateFilter", $datagGridDateFilterArray, "", "", false, "style='width: 95px;'");
	
$amountLabel = $widgets->createLabel("amount", getBadgerTranslation2('accountTransaction', 'amount'), false);
$amountField = $widgets->createField("amount", 14, "", "", false, "text", "");
$amountFilterOperator = $widgets->createSelectField("amountFilter", $datagGridFilterArray, "", "", false, "style='width: 95px;' regexp='BADGER_NUMBER'");	

$categoryLabel = $widgets->createLabel("categoryId", getBadgerTranslation2('accountTransaction', 'category'), false, "");
$categoryField = $widgets->createSelectField("categoryId", getCategorySelectArray(true), "", "", false, "style='width: 210px;'");

//$btnFilterOkay = $widgets->createButton("btnFilterOkay", getBadgerTranslation2('dataGrid', 'setFilter'), "dataGridAccount$accountID.filter.setFilterFields(['title','amount','valutaDate','categoryId'])", "Widgets/dataGrid/filter.gif");
$btnFilterOkay = $widgets->createButton("btnFilterOkay", getBadgerTranslation2('dataGrid', 'setFilter'), "submit", "Widgets/dataGrid/filter.gif");
$btnFilterReset = $widgets->createButton("btnFilterReset", getBadgerTranslation2('dataGrid', 'resetFilter'), "dataGridAccount$accountID.filter.resetFilterFields(['title','amount','valutaDate','categoryId'])", "Widgets/cancel.gif");
$formAction = "javascript:dataGridAccount$accountID.filter.setFilterFields(['title','amount','valutaDate','categoryId'])";

eval('$filterContent = "' . $tpl->getTemplate('Account/StandardFilter') . '";');

$standardFilter =  $widgets->addTwistieSection("Filter",$filterContent);

// DataGrid 
$btnNewFinished = $widgets->createButton("btnNewFinished", getBadgerTranslation2('accountTransaction', 'newFinishedTrans'), "dataGridAccount$accountID.callNewEvent('type=finished')", "Account/finished_transaction_new.gif");
$btnNewPlanned = $widgets->createButton("btnNewPlanned", getBadgerTranslation2('accountTransaction', 'newPlannedTrans'), "dataGridAccount$accountID.callNewEvent('type=planned')", "Account/planned_transaction_new.gif");
$btnEdit = $widgets->createButton("btnEdit", getBadgerTranslation2('dataGrid', 'edit'), "dataGridAccount$accountID.callEditEvent()", "Widgets/table_edit.gif");
$btnDelete = $widgets->createButton("btnDelete", getBadgerTranslation2('dataGrid', 'delete'), "dataGridAccount$accountID.callDeleteEvent()", "Widgets/table_delete.gif");
$btnShowPlannedTransactions = $widgets->createButton("btnShowPlannedTransactions", getBadgerTranslation2('accountOverview', 'showPlannedTrans'),  "location.href = location.href.replace(/AccountOverview\.php/, 'AccountOverviewPlanned.php');", "Account/planned_transaction.png");
$btnGotoToday = $widgets->createButton("btnGotoToday", getBadgerTranslation2('dataGrid', 'gotoToday'), "dataGridAccount$accountID.gotoToday()", "Widgets/dataGrid/goto.gif");
$dgHtml = $dataGrid->writeDataGrid();

$legend = getBadgerTranslation2('dataGrid', 'legend');

$finishedTransactionText = getBadgerTranslation2('Account', 'finishedTransaction');
$finishedTransactionImage = $widgets->addImage('Account/finished_transaction.png', 'title="' . $finishedTransactionText . '"');

$finishedTransferalSourceTransactionText = getBadgerTranslation2('Account', 'FinishedTransferalSourceTransaction');
$finishedTransferalSourceTransactionImage = $widgets->addImage('Account/finished_transferal_source_transaction.png', 'title="' . $finishedTransferalSourceTransactionText . '"');

$finishedTransferalTargetTransactionText = getBadgerTranslation2('Account', 'FinishedTransferalTargetTransaction');
$finishedTransferalTargetTransactionImage = $widgets->addImage('Account/finished_transferal_target_transaction.png', 'title="' . $finishedTransferalTargetTransactionText . '"');

$plannedTransactionText = getBadgerTranslation2('Account', 'plannedTransaction');
$plannedTransactionImage = $widgets->addImage('Account/planned_transaction.png', 'title="' . $plannedTransactionText . '"');

$plannedTransferalSourceTransactionText = getBadgerTranslation2('Account', 'PlannedTransferalSourceTransaction');
$plannedTransferalSourceTransactionImage = $widgets->addImage('Account/planned_transferal_source_transaction.png', 'title="' . $plannedTransferalSourceTransactionText . '"');

$plannedTransferalTargetTransactionText = getBadgerTranslation2('Account', 'PlannedTransferalTargetTransaction');
$plannedTransferalTargetTransactionImage = $widgets->addImage('Account/planned_transferal_target_transaction.png', 'title="' . $plannedTransferalTargetTransactionText . '"');

eval('echo "' . $tpl->getTemplate('Account/AccountOverview') . '";');

eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");

require_once(BADGER_ROOT . "/includes/fileFooter.php");
?>