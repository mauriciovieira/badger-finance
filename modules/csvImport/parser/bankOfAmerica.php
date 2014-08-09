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
* Parse .csv files from Bank of America. Tested with a file of 2006-11-28
**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Bank of America

define ('HEADER_END_MARKER', ',Beginning balance as of');

/**
 * transform csv to array
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
	$headerIgnored = false;
	//for every line
	while (!feof($fp)) {
	    //read one line
	    $rowArray = NULL;
	    $line = fgets($fp, 1024);
	
		//skip header
		if (!$headerIgnored) {
			$tmp = strpos($line, HEADER_END_MARKER);
			//Need this complex check as the source file is reported to say "Buchungstag" and Buchungstag
			//(with and without quotes) at random
			if ($tmp !== false && $tmp <= 11) {
				$headerIgnored = true;
			}
			
			continue;
		}
	
		//break on first empty line, e. g. if we are done with the transactions
		if (trim($line) === '') {
			break;
		}
	
		//if line is not empty or is no header
		if (strstr($line, ",")) { // 

			$matchArray = array();
			preg_match_all('/([^,]+),"([^"]+)","([^"]+)","([^"]+)"/', $line, $matchArray);
			if (count($matchArray) == 5) {
				// divide String to an array
				$transactionArray = array();
				for ($i = 1; $i <= 4; $i++) {
					$transactionArray[$i - 1] = $matchArray[$i][0];
				}
				//format date YY-MM-DD or YYYY-MM-DD
				if (!empty ($transactionArray[1]) ) {
					$valutaDate = explode("/", $transactionArray[0]); //Valuta Date
					$valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[0] . "-" . $valutaDate[1];
					$valutaDate1 = new Date($valutaDate[4]);

					//avoid " & \ in the title & description, those characters could cause problems
					$transactionTitle = substr($transactionArray[1], 0, 100);

					//format amount to usersettings
					$amount1 = new Amount($transactionArray[2]);
					
					/**
					 * transaction array
					 *
					 * @var array
					 */
					$rowArray = array (
						"categoryId" => "",
						"accountId" => $accountId,
						"title" => $transactionTitle,
						"description" => '',
						"valutaDate" => $valutaDate1,
						"amount" => $amount1,
						"transactionPartner" => ''
					);
				}
			} else {
			    $noValidFile = 'true';
		    }
		}

		// if a row contains valid data
		if ($rowArray)  {
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
		//close file
		fclose ($fp);

		throw new badgerException('importCsv', 'wrongSeperatorNumber');
	} else if ($csvRow == 0){
		//close file
		fclose ($fp);

		throw new badgerException('importCsv', 'noSeperator');
	} else {
		//close file
		fclose ($fp);

		return $importedTransactions;
    }
}
?>