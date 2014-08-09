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
* Parse .csv files from Hamburger Sparkasse (Germany). Tested with files from 31.07.2007

**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Hamburger Sparkasse
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
	 * is set true, a line contains ";" , but not the correct number for this parser (5)
	 *
	 * @var boolean
	 */
	$noValidFile = NULL;
	/**
	 * is set true, after the header was ignored
	 *
	 * @var boolean
	 */
	$headerIgnored = NULL;
	//for every line
	while (!feof($fp)) {
		//read one line
		$rowArray = NULL;
		//ignore header (first line)
		if (!$headerIgnored) {
			for ($headerLine = 0; $headerLine < 1; $headerLine++) {
				$garbage = fgets($fp, 1024);
				//to ignore this code on the next loop run
				$headerIgnored = true;
			}
		}
		//read one line
		$line = fgets($fp, 1024);
		//if line is not empty or is no header
		if (strstr($line, ";")) {
			//if line contains excactly 3 ';', to ensure it is a valid Haspa csv file
			if (substr_count($line, ";") == 3) {
				// divide String to an array
				$transactionArray = explode(";", $line);
				//format date YY-MM-DD or YYYY-MM-DD
				$valutaDate = explode(".", $transactionArray[1]); //Valuta Date
				$valutaDate[3] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
				$valutaDate1 = new Date($valutaDate[3]);
				//get transaction partner
				$transactionpartner1 = explode("  ", $transactionArray[2]);

				//format amount to usersettings
				$transactionArray[3] = str_replace(".", "", $transactionArray[3]);
				$transactionArray[3] = str_replace(",", ".", $transactionArray[3]);
				$amount1 = new Amount($transactionArray[3]);
				/**
				 * transaction array
				 *
				 * @var array
				 */
				$rowArray = array (
					"categoryId" => "",
					"accountId" => $accountId,
					"title" => ucwords(strtolower(substr($transactionpartner1[1],
					0,
					99
					))), // cut title with more than 100 chars
		"description" => "", "valutaDate" => $valutaDate1, "amount" => $amount1, "transactionPartner" => $transactionpartner1[0] //ucwords(strtolower($transactionArray[5]))
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