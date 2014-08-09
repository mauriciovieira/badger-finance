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
* Parse .csv files from "First direct (UK)". Tested with examplefile from 2007-03-18 
**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME First direct (UK)
define ('HEADER_END_MARKER', 'Date');

/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\first_direct_(UK).php
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
      function avoid_bad_sign($strings) {
         $strings = str_replace("\"", "", $strings);
         $strings = str_replace("\\", "", $strings);
         $strings = str_replace("\n", " ",$strings);
         return $strings;
        }
/******************************************************************************
 * Date,Description,Amount,Balance
 *
 * 16/03/2007,"PAYPAL PAYMENT",-3.83,-160.28
 *
 * 15/03/2007,"PAYPAL PAYMENT",-6.50,-156.45
 *
 * 15/03/2007,"SANTANDER CONSUMER",-242.22,-149.95
 *
 * 14/03/2007,"BP Brian Leighton Howden",-31.60,92.27
 *
 * 14/03/2007,"PAYPAL PAYMENT",-10.79,123.87
 *
 * 14/03/2007,"PAYPAL PAYMENT",-4.94,134.66
 *
 * 13/03/2007,"PAYPAL PAYMENT",-7.99,139.60
 *
 ******************************************************************************/
   //for every line
   while (!feof($fp)) {
      $rowArray = NULL;

      //read one line
      $line = fgets($fp, 1024);

      //skip header
      if (!$headerIgnored) {
         $tmp = strpos($line, HEADER_END_MARKER);
         //Need this complex check as the source file is reported to say "Date" and Date
         //(with and without quotes) at random
         if ($tmp !== false && $tmp <= 2) {
            $headerIgnored = true;
         }
         continue;
      }
      
       while ($transactionArray = fgetcsv($fp, 1024, ",")) {

      if (count($transactionArray) == 1){       //empty line?
         continue;
      }
      //if array contains excactly 4 fields , to ensure it is a valid first_direct_(UK).csv file
      if (count($transactionArray) == 4) {
         // Replaces the date with prior date at $transactionArray[0]
         if (!empty($transactionArray[0])) {
            $transactionDate = $transactionArray[0];
         } else {
            $transactionArray[0] = $transactionDate;
         }
         //format amount to usersettings
         //format date YY-MM-DD or YYYY-MM-DD
         $transactionArray[0] = avoid_bad_sign($transactionArray[0]);
         $valutaDate = explode("/", $transactionArray[0]); //Valuta Date
         $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
         $valutaDate1 = new Date($valutaDate[4]);

         $transactionArray[1] = avoid_bad_sign($transactionArray[1]);

         //check for a "." in here, to build the decimals
         if (!strpos($transactionArray[2],'.')){
            $transactionArray[2] = $transactionArray[2].".";
         }
         $amount = new Amount($transactionArray[2]);

         /**
          * transaction array
          *
          * @var array
          */
         $rowArray = array (
            "categoryId" => "",
            "accountId" => $accountId,
            "title" => substr($transactionArray[1], 0,   99), // cut title with more than 100 chars
            "description" => $transactionArray[1] ,
            "valutaDate" => $valutaDate1,
            "amount" => $amount,
            "transactionPartner" => "Data missing" //
         );
      } else {
         $noValidFile = 'true';
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