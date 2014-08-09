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
 * Parse .csv files from Star Money . Tested with examplefile from 2007-06-03 by Juergen
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Star Money CSV
define ('HEADER_END_MARKER', 'Betrag');

/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\star_money.php
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
   $headerIgnored = true;
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
   
   // check the arrayField, if any data exists then add a ","
   function isEmpty($field) {
      if (empty($field)){
         return;
      } else {
         return ", ".$field;
      }
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
      //if array is not empty or is no header
      while ($transactionArray = fgetcsv ($fp, 1024, ";"))   {
   
         if (!empty ($transactionArray[0]) ) {
            //if array contains excactly 5 fields , to ensure it is a valid csv file
            if ( count ($transactionArray) == 42) {
   
               //format date YY-MM-DD or YYYY-MM-DD
               $transactionArray[3] = avoid_bad_sign($transactionArray[3]);
               $valutaDate = explode(".", $transactionArray[3]); //Valuta Date
               $valutaDate[0] = strlen2($valutaDate[0]);
               $valutaDate[1] = strlen2($valutaDate[1]);
               $valutaDate[2] = strlen4($valutaDate[2]);
               $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
               $valutaDate1 = new Date($valutaDate[4]);
   
               //avoid " & \ in the title & description, those characters could cause problems
               // number of checks
               $transactionArray[1] = avoid_bad_sign($transactionArray[1]);
               $transactionArray[2] = avoid_bad_sign($transactionArray[2]);
               $transactionArray[4] = avoid_bad_sign($transactionArray[4]);
               $transactionArray[5] = avoid_bad_sign($transactionArray[5]);
               $transactionArray[6] = avoid_bad_sign($transactionArray[6]);
               $transactionArray[7] = avoid_bad_sign($transactionArray[7]);
               $transactionArray[8] = avoid_bad_sign($transactionArray[8]);
               $transactionArray[9] = avoid_bad_sign($transactionArray[9]);
               $transactionArray[10] = avoid_bad_sign($transactionArray[10]);
               $transactionArray[11] = avoid_bad_sign($transactionArray[11]);
               $transactionArray[12] = avoid_bad_sign($transactionArray[12]);
               $transactionArray[13] = avoid_bad_sign($transactionArray[13]);
               $transactionArray[14] = avoid_bad_sign($transactionArray[14]);
               $transactionArray[15] = avoid_bad_sign($transactionArray[15]);
               $transactionArray[16] = avoid_bad_sign($transactionArray[16]);
               $transactionArray[17] = avoid_bad_sign($transactionArray[17]);
               $transactionArray[18] = avoid_bad_sign($transactionArray[18]);
               $transactionArray[19] = avoid_bad_sign($transactionArray[19]);
               $transactionArray[20] = avoid_bad_sign($transactionArray[20]);
               $transactionArray[21] = avoid_bad_sign($transactionArray[21]);
               $transactionArray[22] = avoid_bad_sign($transactionArray[22]);
               $transactionArray[23] = avoid_bad_sign($transactionArray[23]);
               $transactionArray[24] = avoid_bad_sign($transactionArray[24]);
               $transactionArray[25] = avoid_bad_sign($transactionArray[25]);
               $transactionArray[26] = avoid_bad_sign($transactionArray[26]);
               $transactionArray[27] = avoid_bad_sign($transactionArray[27]);
               $transactionArray[28] = avoid_bad_sign($transactionArray[28]);
               $transactionArray[29] = avoid_bad_sign($transactionArray[29]);
               $transactionArray[30] = avoid_bad_sign($transactionArray[30]);
               $transactionArray[31] = avoid_bad_sign($transactionArray[31]);
               $transactionArray[32] = avoid_bad_sign($transactionArray[32]);
               $transactionArray[33] = avoid_bad_sign($transactionArray[33]);
               $transactionArray[34] = avoid_bad_sign($transactionArray[34]);
               $transactionArray[35] = avoid_bad_sign($transactionArray[35]);
               $transactionArray[36] = avoid_bad_sign($transactionArray[36]);
               $transactionArray[37] = avoid_bad_sign($transactionArray[37]);
               $transactionArray[38] = avoid_bad_sign($transactionArray[38]);
               $transactionArray[39] = avoid_bad_sign($transactionArray[39]);
               if (empty($transactionArray[6])) {
                  $transactionArray[42] = $transactionArray[1].", ".$transactionArray[23];
               } else {
                  $transactionArray[42] = $transactionArray[1].", ".
                                    $transactionArray[6].
                                 ", BLZ =".$transactionArray[4].
                                 ", Kto.=".$transactionArray[5];
               }
               
               // build description array
               $transactionArray[43] = $transactionArray[26].
                                 isEmpty($transactionArray[27]).
                                 isEmpty($transactionArray[28]).
                                 isEmpty($transactionArray[29]).
                                 isEmpty($transactionArray[30]).
                                 isEmpty($transactionArray[31]).
                                 isEmpty($transactionArray[32]).
                                 isEmpty($transactionArray[33]).
                                 isEmpty($transactionArray[34]).
                                 isEmpty($transactionArray[35]).
                                 isEmpty($transactionArray[36]).
                                 isEmpty($transactionArray[37]).
                                 isEmpty($transactionArray[38]).
                                 isEmpty($transactionArray[39]);

               //format amount to usersettings
                  if (strpos($transactionArray[0],',')){
                     $transactionArray[0] = str_replace(",", ".", $transactionArray[0]);
                  }
                  $amount = new Amount($transactionArray[0]);

         //   echo $transactionArray[2]; //for debug only
         //   echo $transactionArray[3]; //for debug only
         //  print_r($transactionArray);//for debug only
               /**
                * transaction array
                *
                * @var array
                */
               $rowArray = array (
                  "categoryId" => "",
                  "accountId" => $accountId,
                  "title" => substr($transactionArray[23],0,99), // cut title with more than 100 chars
                  "description" => $transactionArray[43],
                  "valutaDate" => $valutaDate1,
                  "amount" => $amount,
                  "transactionPartner" => $transactionArray[42]
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