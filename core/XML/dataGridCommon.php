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

function getDataGridOrder($handler) {
	$order = array();

	//Filter order parameters to valid entries
	for ($i = 0; $i <= 2; $i++) {
		if (isset($_REQUEST["ok$i"])) {
			if ($handler->hasField(unescaped($_REQUEST, "ok$i"))) {
				$order[$i]['key'] = unescaped($_REQUEST, "ok$i");
	
				if (isset($_REQUEST["od$i"])) {
					switch ($_REQUEST["od$i"]) {
						case 'a':
						default:
							$order[$i]['dir'] = 'asc';
							break;
						
						case 'd':
							$order[$i]['dir'] = 'desc';
							break;
					}
				} else {
					//no order given
					$order[$i]['dir'] = 'a';
				}
			} else {
				//unknown order key
				echo 'Unknown order key: ' . $_REQUEST["ok$i"];
				exit;
			}
		} else {
			//unset order key, do not process further
			break;
		}
	}
	
	return $order;
}

function getDataGridFilter($handler) {
	$filter = array();
	$i = 0;
	
	//Filter filter parameters to valid enties
	while (isset($_REQUEST["fk$i"]) && isset($_REQUEST["fo$i"]) && isset($_REQUEST["fv$i"])) {
		if ($handler->hasField(unescaped($_REQUEST, "fk$i"))) {
			$filter[$i]['key'] = unescaped($_REQUEST, "fk$i");
			
			if (isLegalOperator(unescaped($_REQUEST, "fo$i"))) {
				$filter[$i]['op'] = unescaped($_REQUEST, "fo$i");
				
				try {
					$filter[$i]['val'] = transferType($handler->getFieldType(unescaped($_REQUEST, "fk$i")), unescaped($_REQUEST, "fv$i"));
				} catch (TransferException $ex) {
					//Untransferable Data Type
					echo 'Illegal filter value: ' . $_REQUEST["fv$i"];
					exit;
				}
			} else {
				//illegal filter operator
				echo 'Illegal filter operator: ' . $_REQUEST["fo$i"];
				exit;
			}
		} else {
			//unknown filter key
			echo 'Unknown filter key: ' . $_REQUEST["fk$i"];
			exit;
		}
	
		$i++;
	}
	
	return $filter;
}

function getDataGridSelectedFields($handler) {
	if (isset($_REQUEST['sf'])) {
		$selectedFields = explode(',', unescaped($_REQUEST, 'sf'));
	} else {
		$selectedFields = $handler->getAllFieldNames();
	}
	
	return $selectedFields;
}

/**
 * Indicates an unsuccessful transfer from string to target type
 */
class TransferException extends Exception {
	/**
	 * Default handler
	 */
	function TransferException($message = null, $code = 0) {
		parent::__construct($message, $code);
	}
}

/**
 * Checks if $op is a legal operator.
 * 
 * Legal operators are:
 * <ul>
 *   <li>eq - equal, ==</li>
 *   <li>lt - lower than, &lt;</li>
 *   <li>le - lower or equal, &lt;=</li>
 *   <li>gt - greater than, &gt;</li>
 *   <li>ge - greater or equal, &gt;=</li>
 *   <li>ne - not equal, !=</li>
 *   <li>bw - begins with (not case sensitive)</li>
 *   <li>ew - ends with (not case sensitive)</li>
 *   <li>ct - contains (not case sensitive)</li>
 * </ul>
 * 
 * @param string $op - The string to check for operator
 * @return boolean true if $op is a legal operator, false otherwise
 */
function isLegalOperator($op) {
	$legalOperators = array (
		'eq',
		'lt',
		'le',
		'gt',
		'ge',
		'ne',
		'bw',
		'ew',
		'ct' 	
	);
	
	return in_array($op, $legalOperators, true);
}

/**
 * Casts $str to $type.
 * 
 * @param string $type - The desired target data type.
 * @param string $str - The source data
 * @throws TransferException - if an error occured while transfering
 * @returns mixed $str cast to $type.
 */
function transferType($type, $str) {
	switch ($type) {
		case 'int':
		case 'integer':
		case 'string':
		case 'boolean':
		case 'bool':
		case 'float':
		case 'double':
			if (settype($str, $type)) {
				return $str;
			} else {
				throw new TransferException();
			}
			break;
			
		case 'Amount':
		case 'amount':
			return new Amount($str, true);
		
		case 'Date':
		case 'date':
			return new Date($str, true);
			
		default:
			throw new TransferException();
	}
}
?>