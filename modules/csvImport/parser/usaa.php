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
* Parse .csv files from USAA-BANK.  Not tested yet
**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME USAA BANK

define ('HEADER_END_MARKER', 'Buchungstag');

/**
 * transform csv to array
 * Filename: badger/modules/csvImport/parser/usaa.php
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

        //for every line
        while ($transactionArray = fgetcsv ($fp, 1024, ","))   {
            $rowArray = NULL;

            //if array is not empty or is no header
            if (!empty ($transactionArray[0]) ) {
                //if line contains excactly 5 ',', to ensure it is a valid USAA.csv file
                if ( count ($transactionArray) == 5) {

                    // divide DateString to an array
                    //format date YY-MM-DD or YYYY-MM-DD
                    $valutaDate = explode("/", $transactionArray[0]); //Valuta Date
                    $valutaDate[3] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];

                    //avoid " & \ in the title, those characters could cause problems
                    $transactionArray[2] = str_replace("\"","",$transactionArray[2]);
                    //$transactionArray[2] = str_replace("\\","",$transactionArray[2]);

                    //avoid " & \ in the description, those characters could cause problems
                    $transactionArray[3] = str_replace("\"","",$transactionArray[3]);
                    //$transactionArray[3] = str_replace("\\","",$transactionArray[3]);

                    //format amount to usersettings
                    // not needed here by Juergen
                    //$transactionArray[4] = str_replace(",",".",$transactionArray[2]);
                    //$transactionArray[4] = str_replace("\"","",$transactionArray[2]);
                    //$amount1 = new Amount($transactionArray[4]);
                    /**
                     * transaction array
                     *
                     * @var array
                     */
                    $rowArray = array (
                       "categoryId" => "",
                       "accountId" => $accountId,
                       "title" => substr($transactionArray[2],0,99), // cut title with more than 100 chars
                       "description" => $transactionArray[3],
                       "valutaDate" => new Date($valutaDate[3]),
                       "amount" => new Amount($transactionArray[4]),
                       "transactionPartner" => "USAA BANK"
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
                    //print_r($importedTransactions);
                    //echo "<br>";

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
    //}
}
?> 