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
 * Parse .csv files from ERSTE BANK . Tested with examplefile from 2007-07-20 by Juergen
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Erste Bank (www.sparkasse.at)
define ('HEADER_END_MARKER', 'Abschluss');

/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\erste_bank_at.php
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

/*************************Example kann gelöscht werden ***********
 * ERSTE BANK (www.sparkasse.at)
 *
 * Bezeichnung;Valutadatum;Betrag;Währung
 * "******* Abschluss per 31.03.2006 ******* Reklamationen bitte binnen 6 Wochen";"31.03.2006";"0,00";"EUR"
 * "Sollzinsen ";"31.03.2006";"-0,80";"EUR"
 * "Habenzinsen ";"31.03.2006";"0,08";"EUR"
 * "Kest ";"31.03.2006";"-0,02";"EUR"
 * "Kontofuehrungsprovision ";"31.03.2006";"-5,36";"EUR"
 * "Buchungskostenbeitrag ";"31.03.2006";"-1,32";"EUR"
 * "Leben Personenversicherung";"02.05.2006";"-51,07";"EUR"
 * "Überweisung";"07.07.2007";"100,00";"EUR"
 * "Es wurden 8 Umsätze gefunden." 
 ************************/   
   
   $rowArray = NULL;
   
      //if array is not empty or is no header
      while ($transactionArray = fgetcsv ($fp, 1024, ";"))   {
         if (!empty ($transactionArray[0]) ) {
            //last line
            if ( count ($transactionArray) == 1) {
               // do nothing
               continue;
            //if array contains excactly 4 fields , to ensure it is a valid csv file
            } elseif ( count ($transactionArray) == 4) {
   
               //format date YY-MM-DD or YYYY-MM-DD
               $transactionArray[1] = avoid_bad_sign($transactionArray[1]);
               $valutaDate = explode(".", $transactionArray[1]); //Valuta Date
               $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
               $valutaDate1 = new Date($valutaDate[4]);

               //avoid " & \ in the title & description, those characters could cause problems
               // number of checks
               $transactionArray[0] = avoid_bad_sign($transactionArray[0]);
               
            /*************************************************
             * wenn gewünscht, werden hiermit die "*" entfernt
             * dazu muss das Kommentarzeichen "//" gelöscht werden
             ***/
            //   $transactionArray[0] = trim(str_replace("*","",$transactionArray[0]));
            
               $transactionArray[2] = str_replace(",",".",$transactionArray[2]);
               $transactionArray[2] = str_replace("+","",$transactionArray[2]);
               $amount = new Amount($transactionArray[2]);
            /**
             * transaction array
             *
             * @var array
             */
            $rowArray = array (
               "categoryId" => "",
               "accountId" => $accountId,
               "title" => substr($transactionArray[0],0,99), // cut title with more than 100 chars
               "description" => $transactionArray[0].', '.$transactionArray[1],
               "valutaDate" => $valutaDate1,
               "amount" => $amount,
               "transactionPartner" => "Change by yourself"
               );

            } else {
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
}
?> 