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

require_once BADGER_ROOT . '/core/update/common.php';

define ('BADGER_VERSION_TAG', '-- BADGER_VERSION = ' . getBadgerDbVersion());

function sendSqlDump() {
	$result =  BADGER_VERSION_TAG . "\n";
	
	$result .= getDbDump();
	
	$now = new Date();
	
	header('Content-Type: text/sql');
	header('Content-Disposition: attachment; filename="BADGER-' . getBadgerDbVersion() . '-DatabaseBackup-' . $now->getDate() . '.sql"');
	header('Content-Length: ' . strlen($result));
	
	echo $result;
} 

function getDbDump() {
	$result = ''; 
	$tableList = array (
		'account',
		'account_ids_seq',
		'accountIds_seq',
		'account_property',
		'category',
		'category_ids_seq',
		'categoryIds_seq',
		'csv_parser',
		'currency',
		'currency_ids_seq',
		'currencyIds_seq',
		'datagrid_handler',
		'finished_transaction_ids_seq',
		'finishedTransactionIds_seq',
		'finished_transaction',
		'i18n',
		'langs',
		'navi',
		'navi_ids_seq',
		'naviIds_seq',
		'page_settings',
		'planned_transaction_ids_seq',
		'plannedTransactionIds_seq',
		'planned_transaction',
		'session_global',
		'session_master',
		'user_settings'
	);

	foreach ($tableList as $currentTable) {
		$empty = makeEmptyStatement($currentTable);

		$dump = dumpTable($currentTable);
		
		if ($dump) {
			$result .= $empty . $dump;
		}
	}
	
	return $result;
}

function makeEmptyStatement($tableName) {
	return "TRUNCATE TABLE $tableName;\n";
}

function dumpTable($tableName) {
	global $badgerDb;

	$sql = "SELECT * FROM $tableName";
	
	$dbResult =& $badgerDb->query($sql);
	
	if (PEAR::isError($dbResult)) {
		if ($dbResult->getCode() == DB_ERROR_NOSUCHTABLE) {
			return false;
		} else {
			throw new BadgerException('importExport', 'SQLError', $dbResult->getMessage() . ' ' . $sql);
		}
	}
	
	$row = false;
	$result = '';
	
	while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
		$result .= "INSERT INTO $tableName (";
	
		$columns = array_keys($row);
		$first = true;
		foreach ($columns as $currentColumn) {
			if (!$first) {
				$result .= ', ';
			} else {
				$first = false;
			}
			$result .= $currentColumn;
		}
		
		$result .= ') VALUES (';
		
		$first = true;
		foreach ($row as $currentValue) {
			if (!$first) {
				$result .= ', ';
			} else {
				$first = false;
			}
			$result .= $badgerDb->quoteSmart($currentValue);
		}
		
		$result .= ");\n";
	}
	
	return $result;
}
?>