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
* Parse .csv files from comdirect bank AG (Germany). Tested with an examplefile from 23.11.2007
* Filename: comdirect_depot.php
**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME comdirect Bank AG Depot

define ('HEADER_END_MARKER', 'Stück/Nominale');
define ('FOOTER_END_MARKER', 'Depotwert');

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
   function avoid_bad_sign($strings) {
      $strings = str_replace("\"", "", $strings);
      $strings = str_replace("\\", "", $strings);
      $strings = str_replace("\n", " ",$strings);
      $strings = str_replace("\r", " ",$strings);
      return $strings;

   }
   // check the valueDate, if year have only 2 digits.
   // If the number of the year is between 70 and 99 it will add a leading 19 else a leading 20
   // those numbers could cause problems
   function strlen4($string) {
      if (strlen(trim($string)) == 2) {
         if ($string >= 70 && $string <= 99) {
            $string = "19".trim($string);
         } else {
            $string = "20".trim($string);
         }
      }
      return trim($string);
   }
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
/*         
         //break on first empty line, e. g. if we are done with the transactions
         if (trim($line) === '') {
            break;
         }
*/         
            //if line is not empty or is no header
            if (!strstr($line, FOOTER_END_MARKER) ) { //
                //if line contains excactly 16 ;, to ensure it is a valid comdirect_depot csv file
                if (substr_count ($line, ";")==16){
                    // divide String to an array
                    $transactionArray = explode(";", $line);

                    //format date YY-MM-DD or YYYY-MM-DD
                    $transactionArray[12] = avoid_bad_sign($transactionArray[12]);                   
                    $transactionArray[12] = explode(".", $transactionArray[12]); //Valuta Date
                    $transactionArray[12][2] = strlen4($transactionArray[12][2]);
                    $transactionArray[12] = $transactionArray[12][2] . "-" . $transactionArray[12][1] . "-" . $transactionArray[12][0];
                    $valutaDate1 = new Date($transactionArray[12]);

                     //format amount to usersettings / "Differenz zum Vortagesschlusskurs"
                    $transactionArray[9] = avoid_bad_sign($transactionArray[9]);
                    $amount1 = new Amount(str_replace(",", ".", $transactionArray[9]));
                   
                    $transactionArray[0] = avoid_bad_sign($transactionArray[0]);
                    $transactionArray[1] = avoid_bad_sign($transactionArray[1]);
                    $transactionArray[2] = avoid_bad_sign($transactionArray[2]);
                    $transactionArray[3] = avoid_bad_sign($transactionArray[3]);
                    $transactionArray[4] = avoid_bad_sign($transactionArray[4]);
                    $transactionArray[5] = avoid_bad_sign($transactionArray[5]);                   
                    $transactionArray[6] = avoid_bad_sign($transactionArray[6]);
                    $transactionArray[7] = avoid_bad_sign($transactionArray[7]);                   
                    $transactionArray[8] = avoid_bad_sign($transactionArray[8]);
                    $transactionArray[10] = avoid_bad_sign($transactionArray[10]);
                    $transactionArray[11] = avoid_bad_sign($transactionArray[11]);
                    $transactionArray[13] = avoid_bad_sign($transactionArray[13]);
                    $transactionArray[14] = avoid_bad_sign($transactionArray[14]);
                    $transactionArray[15] = avoid_bad_sign($transactionArray[15]);                   
                    $transactionArray[16] = avoid_bad_sign($transactionArray[16]);                   
                    $description = $transactionArray[0].' Stück, ';
                    $description .= 'Kurs: '.$transactionArray[6];
                    $description .= ', '.$transactionArray[3];
                    $description .= ' ('.$transactionArray[5].')';
                    // optional: decomand this line, insert any text after comma, replace n with arraynumber
                    //$description .= ', '.$transactionArray[n]; 
                    /**
                     * transaction array
                     *
                     * @var array
                     */
                    $rowArray = array (
                       "categoryId" => "",
                       "accountId" => $accountId,
                       "title" => substr($transactionArray[1],0,99),// cut title with more than 100 chars
                       "description" => $description,
                       "valutaDate" => $valutaDate1,
                       "amount" => $amount1,
                       "transactionPartner" => $transactionArray[4] //change what ever you need
                    );
                //} else{ // NOT NEEDED
                   // $noValidFile = 'true';
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