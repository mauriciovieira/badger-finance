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
 * Transfers data from internal DataGridHandler to the DataGrid JavaScript widge.
 * 
 * Is called by the DataGrid JavaScript widget. Evaluates calling parameters and passes
 * them to the appropriate DataGridHandler. Takes care of calling security.
 * 
 * The following calling parameters (via GET) are recognized:
 * q (query): The DataGrid handler name
 * qp (query parameters): Parameters to the DataGrid handler, passed untouched.
 * 
 * ok[0-2] (order key 0 to 2): The order keys, need to be one of the column names.
 * od[0-2] (order direction 0 to 2): The direction of the order. Valid values: 'a' (ascending; default), 'b' (descending)
 * 
 * fk[n] (filter key n): The filter key of filter n, need to be one of the column names.
 * fo[n] (filter operator n): One of the operators defined by @see isLegalOperator().
 * fv[n] (filter value n): The value to compare the key with.
 * 
 * Example calling URL: getDataGridXML.php?q=transfers&qp=7&fk0=type&fo0=eq&fv0=Fuel&fk1=text&fo1=ct&fv1=BP&ok0=date&od0=d  
 * 
 * @author Eni Kao, paraphil
 * @version $LastChangedRevision: 1129 $
 */

define("BADGER_ROOT", "../.."); 
require_once BADGER_ROOT . '/includes/fileHeaderBackEnd.inc.php';
require_once BADGER_ROOT . '/core/XML/DataGridRepository.class.php';
require_once BADGER_ROOT . '/core/XML/DataGridXML.class.php';
require_once BADGER_ROOT . '/core/Amount.class.php';
require_once BADGER_ROOT . '/modules/account/CategoryManager.class.php';
require_once BADGER_ROOT . '/core/XML/dataGridCommon.php';

$logger->log('getDataGridXML: REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

//q parameter is mandatory
if (!isset($_REQUEST['q'])){
	echo 'Missing Parameter q';
	exit;
}

$dgr = new DataGridRepository($badgerDb);

//Unknown DataGridHandler if no result
try{
	$handlerData = $dgr->getHandler($_REQUEST['q']);
} catch (BadgerException $ex){
	echo 'Unknown DataGridHandler';
	exit;		
}

//Include file containing DataGridHandler
require_once BADGER_ROOT . $handlerData['path'];

//Pass query parameters, if available
if (isset($_REQUEST['qp'])) {
	$param = unescaped($_REQUEST, 'qp');
	$handler = new $handlerData['class']($badgerDb, $param);
} else {
	$handler = new $handlerData['class']($badgerDb);
}

$order = getDataGridOrder($handler);
$filter = getDataGridFilter($handler);
$selectedFields = getDataGridSelectedFields($handler);

//Prepare Handler
$handler->setOrder($order);
$handler->setFilter($filter);
$handler->setSelectedFields($selectedFields);

//Get data
$rows = $handler->getAll();
$columns = $handler->getFieldNames();

$dgx = new DataGridXML($columns, $rows);

header('Content-Type: text/xml');

// Fix for Opera 9 compressed XML error
if (isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'opera') === false) {
	if (ini_get('zlib.output_compression') == false) {
		if (function_exists('ob_gzhandler')) {
			@ob_start('ob_gzhandler');
		}
	}
}

echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";

//construct XML
echo $dgx->getXML();

require_once BADGER_ROOT . "/includes/fileFooter.php";
?>