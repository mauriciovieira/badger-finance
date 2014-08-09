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
* Parse .csv files from Volks und Raiffeisenbanken, Badem Württemberg.
* Tested with live-exportfile from V+R Banken 5.12.2006
*---------------------------------------
* Version 2.0  by Jürgen für badger-finance.org
*-------------
********************************************************************/
//The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME V+R Banken Baden Württemberg

define ('HEADER_END_MARKER', 'Buchungstag');
define ('FOOTER_END_MARKER_A', 'Anfangssaldo');
define ('FOOTER_END_MARKER_E', 'Endsaldo');
/**
 * transform xml to array
 * Filename: /badger/modules/csvImport/parser/vr-bank_BW.php
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

        /*****
         * avoid " & \ in the title & description,
         * those characters could cause problems
         */
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
            //Need this complex check as the source file is reported to say "Buchungstag" and Buchungstag
            //(with and without quotes) at random
            if ($tmp !== false && $tmp <= 2) {
               $headerIgnored = true;
            }
            continue;
          }

            if ( !empty( $line ) && !strpos($line, FOOTER_END_MARKER_A) && !strpos($line, FOOTER_END_MARKER_E)) {
                if (substr_count ($line, ";") == 2 ) {
                    $description = "";
                    $line1 = 1; //set the first line of dataset

                    // divide String to an array
                    $transactionArray = explode(";", $line);

                    //format date YY-MM-DD or YYYY-MM-DD
                    $transactionArray[1] = avoid_bad_sign($transactionArray[1]);
                    $valutaDate = explode(".", $transactionArray[1]); //Valuta Date
                    $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
                    $valutaDate1 = new Date($valutaDate[4]);

                    $transactionArray[2] = avoid_bad_sign($transactionArray[2]);
                    $description .= $transactionArray[2];

                } elseif ($line1 == 1 && !strpos($line,';')){
                    $transactionArray[3] = avoid_bad_sign($line);
                    $transactionPartner = $transactionArray[3];
                    $description .= $transactionArray[3];
                    ++$line1;
                } elseif ($line1 == 2 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 == 3 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 == 4 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 == 5 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 == 6 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 == 7 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 == 8 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 == 9 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;
                } elseif ($line1 ==10 && !strpos($line,';')){
                    $description .= avoid_bad_sign($line);
                    $line1++;

                //if line contains excactly 3 ';', to ensure it is a valid VR-Bank csv file
                } elseif (substr_count ($line, ";")==3){
                    $line1 = 0;
                    $transactionArray3 = "";
                    // divide String to an array
                    $transactionArray3 = explode(";", $line);
                    //avoid " & \ in the title & description, those characters could cause problems
                    $transactionArray3[0] = avoid_bad_sign($transactionArray3[0]);
                    $description .= $transactionArray3[0];
                    $transactionArray3[1] = avoid_bad_sign($transactionArray3[1]);

                    // inserts the amount
                    $transactionArray3[2] = avoid_bad_sign($transactionArray3[2]);
                    $transactionArray3[2] = str_replace(",",".",$transactionArray3[2]);

                    // get the sign for balance: i.e. "H" == plus(input); "S" == minus(out)
                    $transactionArray3[3] = str_replace("\"","",$transactionArray3[3]);
                    $transactionArray3[3] = str_replace("\\","",$transactionArray3[3]);

                    // checks for "S" == minus(out) and adds a "-"sign
                    if ($transactionArray3[3] == "S\n") {
                    $transactionArray3[2] = "-".$transactionArray3[2]; //"-".
                    }
                }

                    /**
                     * transaction array
                     *
                     * @var array
                     */
                if ($line1 == 0) {
                  $rowArray = array (
                   "categoryId" => "",
                   "accountId" => $accountId,
                   "title" => substr($transactionArray[2].$transactionArray[3],0,99),// cut title with more than 100 chars
                   "description" => $description,
                   "valutaDate" => $valutaDate1,
                   "amount" => new Amount($transactionArray3[2]),
                   "transactionPartner" => $transactionPartner
                );
              }

            // if a row contains valid data
            if ($rowArray && $line1 ==0){
                /**
                 * array of all transaction arrays
                 *
                 * @var array
                 */
                $importedTransactions[$csvRow] = $rowArray;
                $csvRow++;
                //print_r ($rowArray);
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
                //delete footer (1 line)
                unset($importedTransactions[$csvRow-1]);
                //close file
                fclose ($fp);
                return $importedTransactions;
            }
        }
}
?> 