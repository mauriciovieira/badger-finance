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

$pageTitle = getBadgerTranslation2('accountCategory', 'pageTitleOverview');

$widgets = new WidgetEngine($tpl);
$dataGrid = new DataGrid($tpl, "CategoryManager");
$dataGrid->sourceXML = BADGER_ROOT."/core/XML/getDataGridXML.php?q=CategoryManager";
$dataGrid->headerName = array(getBadgerTranslation2('accountCategory', 'colparentTitle'),getBadgerTranslation2('accountCategory', 'colTitle'),getBadgerTranslation2('accountCategory', 'colDescription'),getBadgerTranslation2('accountCategory', 'colOutsideCapital'));
$dataGrid->columnOrder = array("parentTitle","title","description","outsideCapital");
$dataGrid->deleteMsg = getBadgerTranslation2('accountCategory', 'deleteMsg');
$dataGrid->headerSize = array(200,200,300,100);
$dataGrid->cellAlign = array("left","left","left","center");
$dataGrid->height = "350px";
$dataGrid->deleteAction = "CategoryManager.php?action=delete&ID=";
$dataGrid->editAction = "CategoryManager.php?action=edit&ID=";
$dataGrid->newAction = "CategoryManager.php?action=new";
$dataGrid->deleteRefreshType = 'refreshDataGrid'; 
$dataGrid->initDataGridJS();

$widgets->addNavigationHead();
echo $tpl->getHeader($pageTitle);

echo "<h1>$pageTitle</h1>";

echo $widgets->createButton("btnNew", getBadgerTranslation2('dataGrid', 'new'), "dataGridCategoryManager.callNewEvent()", "Widgets/table_add.gif");
echo ' ';
echo $widgets->createButton("btnEdit", getBadgerTranslation2('dataGrid', 'edit'), "dataGridCategoryManager.callEditEvent()", "Widgets/table_edit.gif");
echo ' ';
echo $widgets->createButton("btnDelete", getBadgerTranslation2('dataGrid', 'delete'), "dataGridCategoryManager.callDeleteEvent()", "Widgets/table_delete.gif");
		
echo $dataGrid->writeDataGrid();

$legend = getBadgerTranslation2('dataGrid', 'legend');

$currentLanguage = $us->getProperty('badgerLanguage');

$ownCapitalText = getBadgerTranslation2('CategoryManager', 'ownCapital');
$ownCapitalImage = $widgets->addImage("Account/$currentLanguage/own_capital.png", 'title="' . $ownCapitalText . '"');

$outsideCapitalText = getBadgerTranslation2('CategoryManager', 'outsideCapital');
$outsideCapitalImage = $widgets->addImage("Account/$currentLanguage/outside_capital.png", 'title="' . $outsideCapitalText . '"');

eval('echo "' . $tpl->getTemplate('Account/CategoryManagerOverview') . '";');

eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");

require_once(BADGER_ROOT . "/includes/fileFooter.php");
?>