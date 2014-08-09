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
 * Parse .csv files from Chase Visa Bank . Tested with examplefile from 2007-05-11 by Juergen
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Chase Visa

/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\ChaseVisa.php
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
   $headerIgnored = TRUE;
   function avoid_bad_sign($strings) {
      $strings = str_replace("\"", "", $strings);
      $strings = str_replace("\\", "", $strings);
      $strings = str_replace("\n", " ",$strings);
      $strings = str_replace("\r", " ",$strings);
      return $strings;
   }
   
   
   $rowArray = NULL;
   
      //if array is not empty or is no header
      while ($transactionArray = fgetcsv ($fp, 1024, ","))   {
         if (!empty ($transactionArray[0]) ) {
            //if array contains excactly 4 fields , to ensure it is a valid csv file
            if ( count ($transactionArray) == 4) {
   
               //format date YY-MM-DD or YYYY-MM-DD
               $transactionArray[1] = avoid_bad_sign($transactionArray[1]);
               $valutaDate[0] = substr($transactionArray[1],0,4);
               $valutaDate[1] = substr($transactionArray[1],4,2);
               $valutaDate[2] = substr($transactionArray[1],6,2);
               $valutaDate[4] = $valutaDate[0] . "-" . $valutaDate[1] . "-" . $valutaDate[2];
               $valutaDate1 = new Date($valutaDate[4]);

               //avoid " & \ in the title & description, those characters could cause problems
               // number of checks
               $transactionArray[0] = avoid_bad_sign($transactionArray[0]);
               $transactionArray[2] = avoid_bad_sign($transactionArray[2]);
               $transactionArray[3] = avoid_bad_sign($transactionArray[3]);
               $amount = new Amount($transactionArray[3]);
            /**
             * transaction array
             *
             * @var array
             */
            $rowArray = array (
               "categoryId" => "",
               "accountId" => $accountId,
               "title" => substr($transactionArray[2],0,99), // cut title with more than 100 chars
               "description" => $transactionArray[0].', '.$transactionArray[2],
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