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
* Parse .csv files from Community America Credit Union.
* Thanks to Cameron Nordholm for providing example data.
**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Community America Credit Union
/**
 * transform csv to array
 * 
 * @param $fp filepointer, $accountId
 * @return array (categoryId, accountId, title, description, valutaDate, amount, transactionPartner)
 */

function parseToArray($fp, $accountId){
	$importedTransactions = array();

	while (!feof($fp)) {
		$currentLine = fgets($fp);
		
		$currentLine = str_replace("\n", '', $currentLine);
		$offset = 0;

		if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2,4}),([^,]{0,23})([^,]{0,20}),[0-9]+,([-0-9.]+)/', $currentLine, $matches)) {
				fclose($fp);
				throw new badgerException('importCsv', 'wrongSeperatorNumber');
			}
			
		$day = $matches[2];
		$month = $matches[1];
		$year = $matches[3];
		if ($year < 100) {
			$year += 2000;
		}
		$valutaDate = new Date("$year-$month-$day");
		
		$partner = $matches[4];
		
		$title = $matches[5];
		
		$amount = new Amount($matches[6]);

		$importedTransactions[] = array (
			'categoryId' => '',
			'accountId' => $accountId,
			'title' => substr(trim($title),0 , 99), //cut title with more than 100 chars
			'description' => '',
			'valutaDate' => $valutaDate,
			'amount' => $amount,
			'transactionPartner' => $partner
		);
		
		$title = '';
		$partnet = '';
		$valutaDate = null;
		$amount = null;
	}
	
	return $importedTransactions;
}
?>