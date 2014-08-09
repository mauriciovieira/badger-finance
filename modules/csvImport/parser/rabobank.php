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
* Parse .csv files from Rabobank NL. Tested with eamplefile from 2006-10-20  
**/
// The next line determines the displayed name of this parser. 
// BADGER_REAL_PARSER_NAME Rabobank 

/** 
 * transform csv to array 
 * 
 * File to store: ..\modules\csvImport\parser\rabobank.php 
 * 
 * @param $fp filepointer, $accountId 
 * @return array (categoryId, accountId, title, description, valutaDate, amount, transactionPartner) 
 */

function parseToArray($fp, $accountId) {
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

	/*  EXAMPLE by RABOBANK / NL 
	1. your own account number(10 characters) 
	2. Currencie(3 characters, like EUR) 
	3. Interest date(8 characters in this format YYYYMMDD 
	4. If you payed money or if you got money( 1 Character, Just a C or a D, Credit or Debit) 
	5. Amount of money(up to 14 characters(for people with big money;)) format: EEE.CC the E stands for whole euro's and the C for eurocents) 
	6. Account number of the other party(10 characters) 
	7. Other party's name(24 characters) 
	8. Book date(8 Characters in this format YYYYMMDD) 
	9. Book code(2 Characters) 
	10. Budget code(6 characters) 
	11. Description 1 (32 characters) 
	12. Description 2 (32 characters) 
	13. Description 3 (32 characters) 
	14. Description 4 (32 characters) 
	15. Description 5 (32 characters) 
	16. Description 6 (32 characters) 
	*/
	//for every line 
	while ($transactionArray = fgetcsv($fp, 1024, ",")) {
		$rowArray = NULL;

		//if array is not empty or is no header 
		if (!empty ($transactionArray[0])) {
			//if array contains excactly 16 fields , to ensure it is a valid Rabobank.csv file 
			if (count($transactionArray) == 16) {

				//avoid " & \ in the title & description, those characters could cause problems 
				$transactionArray[10] = str_replace("\"", "", $transactionArray[10]);
				$transactionArray[10] = str_replace("\\", "", $transactionArray[10]);

				//format amount to usersettings                  
				$transactionArray[2] = str_replace(",", ".", $transactionArray[2]);
				$transactionArray[2] = str_replace("\"", "", $transactionArray[2]);
				// get the sign for balance: i.e. "C" == CREDIT(plus, input); "D" == DEBIT(minus, out) 
				$transactionArray[4] = str_replace("\"", "", $transactionArray[4]);
				$transactionArray[4] = str_replace("\\", "", $transactionArray[4]);

				// checks for "D" == Debit(out) and adds a "-"sign 
				if ($transactionArray[3] == "D") {
					$transactionArray[4] = "-" . $transactionArray[4];
				}
				/** 
				 * transaction array 
				 * 
				 * @var array 
				 */
				$rowArray = array (
					"categoryId" => "",
					"accountId" => $accountId,
					"title" => substr($transactionArray[10], 0,	99), // cut title with more than 100 chars 
					"description" => $transactionArray[8] . $transactionArray[11] . $transactionArray[12] . $transactionArray[13] . $transactionArray[14] . $transactionArray[15],
					"valutaDate" => new Date($transactionArray[7]),
					"amount" => new Amount($transactionArray[4]),
					"transactionPartner" => substr($transactionArray[10], 0, 99) // cut transactionPartner with more than 100 chars 
				);
			} else {
				$noValidFile = 'true';
			}
		}

		// if a row contains valid data 
		if ($rowArray) {
			/** 
			 * array of all transaction arrays 
			 * 
			 * @var array 
			 */
			$importedTransactions[$csvRow] = $rowArray;
			$csvRow++;
		}

	}
	if ($noValidFile) {
		throw new badgerException('importCsv', 'wrongSeperatorNumber');
		//close file 
		fclose($fp);
	} else {
		if ($csvRow == 0) {
			throw new badgerException('importCsv', 'noSeperator');
			//close file 
			fclose($fp);
		} else {
			//close file 
			fclose($fp);
			return $importedTransactions;
		}
	}
}
?>