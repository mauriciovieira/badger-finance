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
 * Parse .csv files from HypoVereinsbank (HVB)
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME HypoVereinsbank (HVB)

/**
 * transform csv to array
 * filename ..\modules\csvImport\parser\hvb.php
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

	//for every line
	while ($transactionArray = fgetcsv($fp, 1024, ";")) {
		$rowArray = NULL;

		if ($csvRow == 0) {
			//skip first line
			$csvRow++;

			continue;
		}

		//if array is not empty or is no header
		if (!empty ($transactionArray[0])) {
			//if line contains excactly 8 ',', to ensure it is a valid HypoVereinsbank.csv file
			if (count($transactionArray) == 8) {

				// divide DateString to an array
				//format date DD.MM.YYYY
				$valutaDate = explode(".", $transactionArray[2]); //Valuta Date
				$valutaDate[3] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];

				//avoid " & \ in the title & description, those characters could cause problems
				$transactionArray[5] = str_replace("\"", "", $transactionArray[5]);

				//format amount to usersettings
				$transactionArray[6] = str_replace(".", "", $transactionArray[6]);
				$transactionArray[6] = str_replace(",", ".", $transactionArray[6]);
				$transactionArray[6] = str_replace("\"", "", $transactionArray[6]);
				/**
				 * transaction array
				 *
				 * @var array
				 */
				$rowArray = array (
                  "categoryId" => "",
                  "accountId" => $accountId,
                  "title" => substr($transactionArray[5],
				0,
				99
				), // cut title with more than 100 chars
               "description" => substr($transactionArray[5], 100),
               "valutaDate" => new Date($valutaDate[3]),
               "amount" => new Amount($transactionArray[6]),
               "transactionPartner" => $transactionArray[3] . ' ' . $transactionArray[4]
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
			$importedTransactions[] = $rowArray;
			$csvRow++;
		}
		//print_r($importedTransactions);
		//echo "<br>";

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