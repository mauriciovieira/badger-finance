<?php
/*
 * ____          _____   _____ ______ _____
 *|  _ \   /\   |  __ \ / ____|  ____|  __ \
 *| |_) | /  \  | |  | | |  __| |__  | |__) |
 *|  _ < / /\ \ | |  | | | |_ |  __| |  _  /
 *| |_) / ____ \| |__| | |__| | |____| | \ \
 *|____/_/    \_\_____/ \_____|______|_|  \_\
 * Open Source Finance Management
 * Visit http://www.badger-finance.org
 *
 * Parse .csv files from IBC Bank . Tested with eamplefile from 2006-12-18 by Jürgen
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME International Bank of Commerce (IBC)
define ('HEADER_END_MARKER', 'Date');

/** 
 * transform csv to array 
 * 
 * File to store: ..\modules\csvImport\parser\ibc_bank.php 
 * 
 * @param $fp filepointer, $accountId 
 * @return array (categoryId, accountId, title, description, valutaDate, amount, transactionPartner) 
 */ 

function parseToArray($fp, $accountId){
	/** 
	 * count Rows of csv 
	 * 
	 * @var int 
	 */ 
	$csvRow = 0;
	/** 
	 * is set true, a line contains "\t" (tabs), but not the correct number for this parser (5) 
	 * 
	 * @var boolean 
	 */ 
	$noValidFile = NULL;
	/** 
	 * is set true, after the header was ignored 
	 * 
	 * @var boolean 
	 */ 
	$headerIgnored = true;
	function avoid_bad_sign($strings) {
		$strings = str_replace("\"", "", $strings);
		$strings = str_replace("\\", "", $strings);
		$strings = str_replace("\n", " ",$strings);
		return $strings;
	}
	
	while (!feof($fp)) {
	
		$rowArray = NULL;
	
		//read one line
		$line = fgets($fp, 1024);
	
		//skip header
		if (!$headerIgnored) {
			$tmp = strpos($line, HEADER_END_MARKER);
			//Need this complex check as the source file is reported to say "Date" and Date
			//(with and without quotes) at random
			if ($tmp !== false && $tmp <= 2) {
				$headerIgnored = true;
			}
			continue;
		}
		//if array is not empty or is no header
		while ($transactionArray = fgetcsv ($fp, 1024, ","))   {
	
			if (!empty ($transactionArray[0]) ) {
				//if array contains excactly 5 fields , to ensure it is a valid IBC Bank.csv file
				if ( count ($transactionArray) == 5) {
	
					//format date YY-MM-DD or YYYY-MM-DD
					$transactionArray[0] = avoid_bad_sign($transactionArray[0]);
					$valutaDate = explode("/", $transactionArray[0]); //Valuta Date
					$valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[0] . "-" . $valutaDate[1];
					$valutaDate1 = new Date($valutaDate[4]);
	
					//avoid " & \ in the title & description, those characters could cause problems
					// number of checks
					$transactionArray[1] = avoid_bad_sign($transactionArray[1]);
	
					//avoid " & \ in the title & description, those characters could cause problems
					$transactionArray[2] = avoid_bad_sign($transactionArray[2]);
					if (empty($transactionArray[1])) {
						$description = $transactionArray[2];
					} else {
						$description = "No.".$transactionArray[1]." ".$transactionArray[2];
					}
					//format amount to usersettings
					if (empty($transactionArray[3])) {
	
						//check for a "." in that credit-array to build the decimals
						if (!strpos($transactionArray[4],'.')){
							$transactionArray[4] = $transactionArray[4].".";
						}
	
						$amount = new Amount($transactionArray[4]);
					} else {
	
						//check for a "." in that debit-array to build the decimals
						if (!strpos($transactionArray[3],'.')){
							$transactionArray[3] = $transactionArray[3].".";
						}
	
						$amount = new Amount($transactionArray[3]);
					}
	
	            /** 
	             * transaction array 
	             * 
	             * @var array 
	             */ 
	            $rowArray = array (
	               "categoryId" => "", 
	               "accountId" => $accountId, 
	               "title" => substr($transactionArray[2],0,99), // cut title with more than 100 chars 
	               "description" => $description, 
	               "valutaDate" => $valutaDate1, 
	               "amount" => $amount, 
	               "transactionPartner" => "" 
	               );
	
				} else{
					$noValidFile = 'true';
				}
			}
	
			// if a row contains valid data
			if ($rowArray){
	        /** 
	         * array of all transaction arrays 
	         * 
	         * @var array 
	         */ 
	        $importedTransactions[$csvRow] = $rowArray;
	        $csvRow++;
			}
		}
	}
	if ($noValidFile) {
		throw new badgerException('importCsv', 'wrongSeperatorNumber');
		//close file
		fclose ($fp);
	} else {
		if ($csvRow == 0){
			throw new badgerException('importCsv', 'noSeperator');
			//close file
			fclose ($fp);
		} else{
			//close file
			fclose ($fp);
			return $importedTransactions;
		}
	}
}
?>