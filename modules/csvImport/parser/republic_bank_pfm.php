<?php
/*
 * ____        _____   _____ ______ _____
 *|  _ \   /\   |  __ \ / ____|  ____|  __ \
 *| |_) | /  \  | |  | | |  __| |__  | |__) |
 *|  _ < / /\ \ | |  | | | |_ |  __| |  _  /
 *| |_) / ____ \| |__| | |__| | |____| | \ \
 *|____/_/   \_\_____/ \_____|______|_|  \_\
 * Open Source Finance Management
 * Visit http://www.badger-finance.org
 *
 * Parse .csv files from Republic Bank . Tested with examplefile from 2007-05-04 by Juergen
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Republic Bank PFM
define ('HEADER_END_MARKER', 'Transaction');

/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\republic_bank_pfm.php
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
   
/**************************************** E X A M P L E *************************************
 * 0_Transaction Number, 1_Date, 2_Description, 3_Memo, 4_Debit, 5_Credit, 6_Balance, 7_Check Number, 8_Fees
 * 6996,5/1/2007,Description    Minus   ,,47,,1123.72,,
 * 6997,5/1/2007,Description   Minus   ,,24.3,,1099.42,,
 * 7007,5/2/2007,Description   Minus   ,,15.03,,1084.39,,
 * 4711,5/3/2007,Description   PLUS   ,3,,30.06,1144.45,,
 ********************************************************************************************/   
   while (!feof($fp)) {
   
   $rowArray = NULL;
   
   //read one line
   $line = fgets($fp, 1024);
         //ignore header (first 5 lines)
         if (!$headerIgnored){
            for ($headerLine = 0; $headerLine < 4; $headerLine++) {
               $garbage = fgets($fp, 1024);
               //to ignore this code on the next loop run
               $headerIgnored = true;
            }
         }
      //if array is not empty or is no header
      while ($transactionArray = fgetcsv ($fp, 1024, ","))   {
         if (!empty ($transactionArray[0]) ) {
            //if array contains excactly 9 fields , to ensure it is a valid csv file
            if ( count ($transactionArray) == 9) {
   
               //format date YY-MM-DD or YYYY-MM-DD
               $transactionArray[1] = avoid_bad_sign($transactionArray[1]);
               $valutaDate = explode("/", $transactionArray[1]); //Valuta Date
               $valutaDate[0] = strlen2($valutaDate[0]);
               $valutaDate[1] = strlen2($valutaDate[1]);
               $valutaDate[2] = strlen4($valutaDate[2]);
               $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[0] . "-" . $valutaDate[1];
               $valutaDate1 = new Date($valutaDate[4]);
   
               //avoid " & \ in the title & description, those characters could cause problems
               // number of checks
               $transactionArray[2] = avoid_bad_sign($transactionArray[2]);
               $transactionArray[3] = avoid_bad_sign($transactionArray[3]);
               
               //format amount to usersettings
               if (empty($transactionArray[4])) {
   
                  //check for a "." in that credit-array to build the decimals
                  if (!strpos($transactionArray[5],'.')){
                     $transactionArray[5] = $transactionArray[5].".";
                  }
   
                  $amount = new Amount($transactionArray[5]);
               } else {
   
                  $transactionArray[4] = "-".$transactionArray[4];
                  //check for a "." in that debit-array to build the decimals
                  if (!strpos($transactionArray[4],'.')){
                     $transactionArray[4] = $transactionArray[4].".";
                  }
   
                  $amount = new Amount($transactionArray[4]);
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
               "description" => $transactionArray[2].', '.$transactionArray[0].', '.$transactionArray[3],
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