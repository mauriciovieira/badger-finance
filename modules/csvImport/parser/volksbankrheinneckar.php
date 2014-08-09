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
* Parse .csv files from Volksbank (Rhein Neckar Area) (Germany). Tested with files from 22.07.2006
* It should work with files from every Volksbank in Germany, but it was not tested with others
**/
//Probably valid up to 31.10.2006 (date of planed VR-internal Software-Changing)
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Volksbank Rhein-Neckar
/**
 * transform csv to array
 * 
 * @param $fp filepointer, $accountId
 * @return array (categoryId, accountId, title, description, valutaDate, amount, transactionPartner)
 */

define('VOLKSBANK_START_DATA_MARKER', "Buchungstag;Valuta;Vorgang/Verwendungszweck;;Umsatz;\n");

function parseToArray($fp, $accountId){
	$headerParsed = false;
	$continueField = false;
	$validFile = false;
	$title = '';

	$importedTransactions = array();

	while (!feof($fp)) {
		$currentLine = fgets($fp);
		
		if (!$headerParsed) {
			if ($currentLine == VOLKSBANK_START_DATA_MARKER) {
				$headerParsed = true;
				$validFile = true;
			}
			
			continue;
		}
		
		$currentLine = str_replace("\n", '', $currentLine);
		$offset = 0;

		if (!$continueField) {
			if ($currentLine == '') {
				break;
			}

			if (!preg_match('/^"[0-9.]+?";"([0-9]{1,2}).([0-9]{1,2}).([0-9]{4})";/', $currentLine, $matches, PREG_OFFSET_CAPTURE)) {
				fclose($fp);
				throw new badgerException('importCsv', 'wrongSeperatorNumber');
			}
			
			$offset = $matches[0][1] + strlen($matches[0][0]);
			$day = $matches[1][0];
			$month = $matches[2][0];
			$year = $matches[3][0];
			$valutaDate = new Date("$year-$month-$day");
			
			if (!preg_match('/"(.+?)"{0,1}$/', $currentLine, $matches, PREG_OFFSET_CAPTURE, $offset)) {
				fclose($fp);
				throw new badgerException('importCsv', 'wrongSeperatorNumber');
			}
			
			$offset = $matches[0][1] + strlen($matches[0][0]);
			$description = $matches[1][0];
			if (substr($currentLine, -1, 1) != '"') {
				$continueField = true;
				
				continue;
			}
		}
		
		if ($continueField) {
			if (!preg_match('/(^([^"]*)(";){0,1}).*$/', $currentLine, $matches, PREG_OFFSET_CAPTURE, $offset)) {
				fclose($fp);
				throw new badgerException('importCsv', 'wrongSeperatorNumber');
			}
			
			$offset = $matches[1][1] + strlen($matches[1][0]);
			$title .= ' ' . $matches[2][0];
			if (substr($matches[1][0], -1, 1) != ';') {
				continue;
			}
		}

		if (!preg_match('/"([A-Z]{1,3})";"([0-9.,]+)";"(S|H)"$/', $currentLine, $matches, PREG_OFFSET_CAPTURE)) {
			fclose($fp);
			throw new badgerException('importCsv', 'wrongSeperatorNumber');
		}
			
		$currency = $matches[1][0];
		$amountStr = $matches[2][0];
		$amountStr = str_replace('.', '', $amountStr);
		$amountStr = str_replace(',', '.', $amountStr);
		$amount = new Amount($amountStr);
		$posNeg = $matches[3][0];
		if ($posNeg == 'S') {
			$amount->mul(-1);
		}
		
		$importedTransactions[] = array (
			'categoryId' => '',
			'accountId' => $accountId,
			'title' => substr(trim($title),0 , 99), //cut title with more than 100 chars
			'description' => trim($description),
			'valutaDate' => $valutaDate,
			'amount' => $amount,
			'transactionPartner' => ''
		);
		
		$continueField = false;
		$title = '';
		$description = '';
		$valutaDate = null;
		$amount = null;
	}
	
	if (!$validFile) {
		fclose ($fp);
		throw new badgerException('importCsv', 'noSeperator');
	}

	return $importedTransactions;
}
?>