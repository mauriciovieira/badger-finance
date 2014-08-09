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
 **/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME BAWAG Austria (www.bawag.at)

/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\bawag_austria.php
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
/*************************Example
 * 1. BAWAG (www.bawag.at)
 *
 * 1234567890;Abbuchung Einzugsermächtigung OG/000001246|VERBUND 31000 0010081;17.07.2007;17.07.2007;-58,00;EUR
 * 1234567890;BAUHAUS 1100 0794P K1 14.07. UM 12.07 VD/00000;16.07.2007;14.07.2007;-16,67;EUR
 * 1234567890;A.T.U. 1230 0620P K1 07.07. UM 12.06 VD/00000;09.07.2007;07.07.2007;-5,99;EUR
 * 1234567890;GEHAELTER 4/07 VD/00000 11000 DIE Firma;24.04.2007;25.04.2007;+350,00;EUR
 ************************/   
   
   $rowArray = NULL;
   
      //if array is not empty or is no header
      while ($transactionArray = fgetcsv ($fp, 1024, ";"))   {
         if (!empty ($transactionArray[0]) ) {
            //if array contains excactly 6 fields , to ensure it is a valid csv file
            if ( count ($transactionArray) == 6) {
   
               //format date YY-MM-DD or YYYY-MM-DD
               $transactionArray[3] = avoid_bad_sign($transactionArray[3]);
               $valutaDate = explode(".", $transactionArray[3]); //Valuta Date
               $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
               $valutaDate1 = new Date($valutaDate[4]);

               //avoid " & \ in the title & description, those characters could cause problems
               // number of checks
               $transactionArray[0] = avoid_bad_sign($transactionArray[0]);
               $transactionArray[2] = avoid_bad_sign($transactionArray[1]);
                    $transactionArray[4] = str_replace(",",".",$transactionArray[4]);
                    $transactionArray[4] = str_replace("+","",$transactionArray[4]);
               $amount = new Amount($transactionArray[4]);
            /**
             * transaction array
             *
             * @var array
             */
            $rowArray = array (
               "categoryId" => "",
               "accountId" => $accountId,
               "title" => substr($transactionArray[1],0,99), // cut title with more than 100 chars
               "description" => $transactionArray[1].', '.$transactionArray[2],
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