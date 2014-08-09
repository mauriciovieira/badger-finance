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
 * Parse .csv files from DBS Bank, Singapore . Tested with examplefile from forum 2007-06-26 by Juergen
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME DBS Singapore
define ('HEADER_END_MARKER', 'Transaction');

/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\dbs_singapore.php
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
   $headerIgnored = FALSE;
   function avoid_bad_sign($strings) {
      $strings = str_replace("\"", "", $strings);
      $strings = str_replace("\\", "", $strings);
      $strings = str_replace("\n", " ",$strings);
      $strings = str_replace("\r", " ",$strings);
      return $strings;
   }
   // check the valueDate, if day | month have only 1 digit and adds an leading zero
   // those numbers could cause problems
   function strlen2($string) {
      if (strlen(trim($string)) == 1) {
         $string = "0".trim($string);
      }
      return $string;
   }
   // check the valueDate, if year have only 2 digits.
   // If the number of the year is between 50 and 99 it will add a leading 19 else a leading 20
   // those numbers could cause problems
   function strlen4($string) {
      if (strlen(trim($string)) == 2) {
         if ($string >= 50 && $string <= 99) {
            $string = "19".trim($string);
         } else {
            $string = "20".trim($string);
         }
      }
      return trim($string);
   }
   
   while (!feof($fp)) {
   
   $rowArray = NULL;
   
   //read one line
   $line = fgets($fp, 1024);
         //ignore header (first 6 lines)
         if (!$headerIgnored){
            for ($headerLine = 0; $headerLine < 6; $headerLine++) {
               $garbage = fgets($fp, 1024);
               //to ignore this code on the next loop run
               $headerIgnored = true;
            }
         }
      //if array is not empty or is no header
      while ($transactionArray = fgetcsv ($fp, 1024, ","))   {
         if (!empty ($transactionArray[0]) ) {
   echo count ($transactionArray); //debug
   print_r($transactionArray);
            //if array contains excactly 7 fields , to ensure it is a valid csv file
            if ( count ($transactionArray) == 8 || count ($transactionArray) == 9) {
   
               //format date YY-MM-DD or YYYY-MM-DD
               $transactionArray[0] = avoid_bad_sign($transactionArray[0]);
               $valutaDate = explode(" ", $transactionArray[0]); //Valuta Date
               $valutaDate[0] = $valutaDate[0];
               $valutaDate[1] = str_replace(array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'),
                                    array('01','02','03','04','05','06','07','08','09','10','11','12'),$valutaDate[1]);
               $valutaDate[2] = $valutaDate[2];
               $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
               $valutaDate1 = new Date($valutaDate[4]);
   
               //avoid " & \ in the title & description, those characters could cause problems
               // number of checks
               $transactionArray[1] = avoid_bad_sign($transactionArray[1]);
               if (!empty($transactionArray[4])){
                 $transactionArray[4] = ', '.avoid_bad_sign($transactionArray[4]);
               }
               if (!empty($transactionArray[5])){
               $transactionArray[5] = ', '.avoid_bad_sign($transactionArray[5]);
               }
               if (!empty($transactionArray[6])){
               $transactionArray[6] = ', '.avoid_bad_sign($transactionArray[6]);
               }
               if (!empty($transactionArray[7])){
               $transactionArray[7] = ', '.avoid_bad_sign($transactionArray[7]);
               }
               
               //format amount to usersettings
               if (empty($transactionArray[2])) {
   
                  //check for a "." in that credit-array to build the decimals
                  if (!strpos($transactionArray[3],'.')){
                     $transactionArray[3] = $transactionArray[3].".";
                  }
   
                  $amount = new Amount($transactionArray[3]);
               } else {
   
                  $transactionArray[2] = "-".$transactionArray[2];
                  //check for a "." in that debit-array to build the decimals
                  if (!strpos($transactionArray[2],'.')){
                     $transactionArray[2] = $transactionArray[2].".";
                  }
   
                  $amount = new Amount($transactionArray[2]);
               }
            /**
             * transaction array
             *
             * @var array
             */
            $rowArray = array (
               "categoryId" => "",
               "accountId" => $accountId,
               "title" => substr($transactionArray[1].$transactionArray[4],0,99), // cut title with more than 100 chars
               "description" => $transactionArray[1].$transactionArray[4].$transactionArray[5].$transactionArray[6].$transactionArray[7],
               "valutaDate" => $valutaDate1,
               "amount" => $amount,
               "transactionPartner" => "Change by yourself"
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