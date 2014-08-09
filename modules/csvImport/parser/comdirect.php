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
* Parse .csv files from comdirect bank AG (Germany). Tested with a file from 14.09.2006
**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME comdirect Bank AG

define ('HEADER_END_MARKER', 'Buchungstag');

/**
 * transform csv to array
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
				if ($tmp !== false && $tmp <= 2) {
					$headerIgnored = true;
				}
				
				continue;
			}
			
			//break on first empty line, e. g. if we are done with the transactions
			if (trim($line) === '') {
				break;
			}
			
            //if line is not empty or is no header
            if (strstr($line, ";")) { // 
                //if line contains excactly 4 ;, to ensure it is a valid Postbank csv file
                if (substr_count ($line, ";")==4){
                    // divide String to an array
                    $transactionArray = explode(";", $line);
                    //format date YY-MM-DD or YYYY-MM-DD
                  if (!empty ($transactionArray[1]) ) { // added, while ";"NoValidData";"  //jh ack as juergen
                    $transactionArray[1] = str_replace("\"","",$transactionArray[1]);
                    $transactionArray[1] = str_replace("\\","",$transactionArray[1]);                   
                    $valutaDate = explode(".", $transactionArray[1]); //Valuta Date
                    $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
                    $valutaDate1 = new Date($valutaDate[4]);
                    //avoid " & \ in the title & description, those characters could cause problems
                    $transactionArray[2] = str_replace("\"","",$transactionArray[2]);
                    $transactionArray[2] = str_replace("\\","",$transactionArray[2]);                   
                    $transactionArray[3] = str_replace("\"","",$transactionArray[3]);
                    $transactionArray[3] = str_replace("\\","",$transactionArray[3]);                   

                    $transactionPartner = $transactionArray[3];       
                    //format amount to usersettings
                    $transactionArray[4] = str_replace("\"","",$transactionArray[4]);
                    $transactionArray[4] = str_replace("\\","",$transactionArray[4]);                   
                    $transactionArray[4] = str_replace(".","", $transactionArray[4]);
                    $transactionArray[4] = str_replace(",",".",$transactionArray[4]);
                    $amount1 = new Amount($transactionArray[4]);
                    /**
                     * transaction array
                     *
                     * @var array
                     */
                    $rowArray = array (
                       "categoryId" => "",
                       "accountId" => $accountId,
                       "title" => substr($transactionArray[3],0,99),// cut title with more than 100 chars
                       "description" => $transactionArray[2],
                       "valutaDate" => $valutaDate1,
                       "amount" => $amount1,
                       "transactionPartner" => $transactionPartner
                    ); //print_r($rowArray);
                  } // added, while ";"NoValidData";"  //jh ack as juergen
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
