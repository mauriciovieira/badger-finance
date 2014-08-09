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

function deleteFromNavi($naviId) {
	global $badgerDb;
	
	$sql = "DELETE FROM navi
		WHERE navi_id = $naviId";
	
	$dbResult =& $badgerDb->query($sql);
		
	if (PEAR::isError($dbResult)) {
		//echo "SQL Error: " . $dbResult->getMessage();
		throw new BadgerException('Navigation', 'SQLError', $dbResult->getMessage());
	}
		
	if($badgerDb->affectedRows() != 1){
		throw new BadgerException('Navigation', 'UnknownNavigationId', $naviId);
	}
}

function addToNavi(
	$parentId,
	$position,
	$type,
	$name,
	$url,
	$command
) {
	global $badgerDb;
	
	switch ($type) {
		case 'menu':
			$dbType = 'm';
			break;
		
		case 'separator':
			$dbType = 's';
			break;
		
		case 'item':
		default:
			$dbType = 'i';
			break;
	}
	
	$naviId = $badgerDb->nextId('navi_ids');

	$sql = "INSERT INTO navi (navi_id, parent_id, menu_order, item_type, item_name, icon_url, command)
		VALUES ($naviId, $parentId, $position, '$dbType', '"
		. $badgerDb->escapeSimple($name)
		. "', '"
		. $badgerDb->escapeSimple($url)
		. "', '"
		. $badgerDb->escapeSimple($command)
		. "')";
		
	$dbResult =& $badgerDb->query($sql);
		
	if (PEAR::isError($dbResult)) {
		//echo "SQL Error: " . $dbResult->getMessage();
		throw new BadgerException('Navigation', 'SQLError', $dbResult->getMessage());
	}
	
	return $naviId;
}

function modifyNavi(
	$naviId,
	$parentId,
	$position,
	$type,
	$name,
	$url,
	$command
) {
	global $badgerDb;
	
	switch ($type) {
		case 'menu':
			$dbType = 'm';
			break;
		
		case 'separator':
			$dbType = 's';
			break;
		
		case 'item':
		default:
			$dbType = 'i';
			break;
	}
	
	$sql = "UPDATE navi SET parent_id = $parentId menu_order = $position, item_type = '$dbType', item_name = '"
		. $badgerDb->escapeSimple($name)
		. "', icon_url = '"
		. $badgerDb->escapeSimple($url)
		. "', command = '"
		. $badgerDb->escapeSimple($command)
		. "' WHERE navi_id = $naviId";
		
	$dbResult =& $badgerDb->query($sql);
		
	if (PEAR::isError($dbResult)) {
		//echo "SQL Error: " . $dbResult->getMessage();
		throw new BadgerException('Navigation', 'SQLError', $dbResult->getMessage());
	}
	
	if($badgerDb->affectedRows() != 1){
		throw new BadgerException('Navigation', 'UnknownNavigationId', $naviId);
	}
}
?>