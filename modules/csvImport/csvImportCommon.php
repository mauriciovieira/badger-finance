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

function getParsers() {
	$baseDir = BADGER_ROOT . '/modules/csvImport/parser/';

	$parsers = array();

	$parserDir = dir($baseDir);
	while (false !== ($parserFileName = $parserDir->read())) {
		if (is_file($baseDir . $parserFileName)) {
			$parserFile = fopen($baseDir . $parserFileName, "r");
			while (!feof($parserFile)) {
				$line = fgets($parserFile);
				if (preg_match('/[\s]*\/\/[\s]*BADGER_REAL_PARSER_NAME[\s]+([^\n]+)/', $line, $match)) {
					$parsers[$parserFileName] = $match[1];
					break;
				}
			}
			fclose($parserFile);
		}
	}
	$parserDir->close();
	
	uasort($parsers, 'compareNoCase');

	return $parsers;
}

function compareNoCase($a, $b) {
	return strcasecmp($a, $b);
}
?>