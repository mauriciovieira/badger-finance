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

function getGPC($array, $key, $type = 'string', $escaped = false) {
	if ($type == 'checkbox') {
		return isset($array[$key]);
	}

	if ($escaped) {
		$val = escaped($array, $key);
	} else {
		$val = unescaped($array, $key);
	}
	
	switch ($type) {
		case 'int':
		case 'integer':
			settype($val, 'integer');
			break;
		
		case 'float':
		case 'double':
			settype($val, 'float');
			break;
		
		case 'bool':
		case 'boolean':
			settype($val, 'boolean');
			break;
		
		case 'Amount':
			$val = new Amount($val);
			break;
		
		case 'AmountFormatted':
			$val = new Amount($val, true);
			break;
		
		case 'Date':
			$val = new Date($val);
			break;
		
		case 'DateFormatted':
			$val = new Date($val, true);
			break;
		
		case 'intList':
		case 'integerList':
			$arr = explode(',', $val);
			$val = array();
			foreach ($arr as $elm) {
				settype($elm, 'integer');
				$val[] = $elm;
			}
			break;
		
		case 'floatList':
		case 'doubleList':
			$arr = explode(',', $val);
			$val = array();
			foreach ($arr as $elm) {
				settype($elm, 'float');
				$val[] = $elm;
			}
			break;
		
		case 'stringList':
			$val = explode(',', $val);
			break;
		
		case 'string':
		default:
			
	}
	
	return $val;
}

/**
 * Gets the escaped $key value out of $array.
 * 
 * Example use: escaped($_GET, 'query');
 * Considers magic_quotes_gpc.
 * 
 * @param array $array The array of $key, typically a superglobal.
 * @param string $key The key of the requested value. 
 * @return mixed The value of key $key in $array in escaped form.
 */
function escaped($array, $key) {
	if (!isset($array[$key])) {
		throw new BadgerException('common', 'gpcFieldUndefined', $key);
	}

	if (!is_array($array[$key])) {
		if (get_magic_quotes_gpc()) {
			return $array[$key];
		} else {
			return addslashes($array[$key]);
		}
	} else {
		return escapeArray($array[$key]);
	}
}

function escapeArray($array) {
	$result = array();
	
	foreach ($array as $key => $val) {
		if (!is_array($val)) {
			if (get_magic_quotes_gpc()) {
				$result[$key] = $val;
			} else {
				$result[$key] = addslashes($val);
			}
		} else {
			$result[$key] = escapeArray($val);
		}
	}
	
	return $result;
}

/**
 * Gets the unescaped $key value out of $array.
 * 
 * Example use: unescaped($_GET, 'query');
 * Considers magic_quotes_gpc.
 * 
 * @param array $array The array of $key, typically a superglobal.
 * @param string $key The key of the requested value. 
 * @return mixed The value of key $key in $array in unescaped form.
 */
function unescaped($array, $key) {
	if (!isset($array[$key])) {
		throw new BadgerException('common', 'gpcFieldUndefined', $key);
	}

	if (!is_array($array[$key])) {
		if (!get_magic_quotes_gpc()) {
			return $array[$key];
		} else {
			return stripslashes($array[$key]);
		}
	} else {
		return unescapeArray($array[$key]);
	}
}

function unescapeArray($array) {
	$result = array();
	
	foreach ($array as $key => $val) {
		if (!is_array($val)) {
			if (!get_magic_quotes_gpc()) {
				$result[$key] = $val;
			} else {
				$result[$key] = stripslashes($val);
			}
		} else {
			$result[$key] = unescapeArray($val);
		}
	}
	
	return $result;
}

/**
 * Gets the element of $array following the key $key.
 * 
 * @param $array array The array to traverse.
 * @param $key mixed The key to advance by one.
 * @return mixed The next array element or false if at the end of the array.
 */
function nextByKey(&$array, &$key) {
	if (is_null(key($array))) {
		return false;
	}
	
	if (!is_null($key)) {
		if (key($array) != $key) {
			reset($array);
			$currentKey = key($array);
			while (!is_null($currentKey) && ($currentKey != $key)) {
				next($array);
				$currentKey = key($array);
				
			}
		}

		$result = next($array);
	} else {
		$result = current($array);
	}
	
	$key = key($array);
	
	return $result;
}

function getRelativeTplPath($path) {
	global $us;
	
	$currentTemplate = $us->getProperty('badgerTemplate');
	
	$filename = BADGER_ROOT . "/tpl/$currentTemplate/$path";
	if (!file_exists($filename)) {
		$currentTemplate = 'Standard';
	}
	
	return "tpl/$currentTemplate/$path";
}

function escape4Attr($string) {
	return str_replace(
		array ("'", '"'),
		array ('&#39;', '&quot;'),
		$string
	);
}

function showDebugTrace($argX = '') {
	if (!defined('DEBUG') || !DEBUG) {
		return;
	}
	
	$i = 0;

	echo '<pre>';
	$debug = debug_backtrace();
	foreach ($debug as $call) {
		echo "$i: $argX function $call[function], args: ";
		foreach ($call['args'] as $arg) {
			echo "$arg, ";
		}
		echo "\n";
		
		$i++;
	}
	echo '</pre>';
}

?>