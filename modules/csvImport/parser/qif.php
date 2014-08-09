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
* Parse .csv files from quicken software. Tested with examplefile from 2007-03-17 
**/
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME QIF Import
define ('HEADER_END_MARKER', '!Type:Bank');
define ('DATASET_BEGIN', 'D');
define ('DATASET_END', '^');
/**
 * transform csv to array
 *
 * File to store: ..\modules\csvImport\parser\qif.php
 *
 * @param $fp filepointer, $accountId
 * @return array (categoryId, accountId, title, description, valutaDate, amount, transactionPartner)
 */

function parseToArray($fp, $accountId) {
   $transactionAddress = "";
   $transactionCategory = "";
   $transactionCategorySub =  "";
   $transactionCleared = "";
   $transactionDollar = "";
   $transactionE = "";
   $transactionI = "";
   $transactionMemo = "";
   $transactionNumber = "";
   $transactionPartner = "";
   $transactionQ = "";
   $transactionY = "";
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
   /*****
    * any needed functions in this program
    *
    */
   // avoid " & \ in the title & description, those characters could cause problems
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
/***************************************************************************************************
 * Each item in a bank, cash, credit card, other liability,
 * or other asset account must begin with a letter that indicates the field in the Quicken register.
 * The non-split items can be in any sequence:
 * Field    Indicator Explanation
 * D    Date
 * T    Amount
 * C    Cleared status
 * N    Num (check or reference number)                              // first entry in description
 * P    Payee                                                 // entry in partner and title
 * M    Memo                                                // second entry in description
 * A    Address (up to five lines; the sixth line is an optional message)   // third entry in description
 * L    Category (Category/Subcategory/Transfer/Class)
 * S    Category in split (Category/Transfer/Class)
 * E    Memo in split                                          
 * $    Dollar amount of split
 * ^    End of the entry
 ***************************************************************************************************/
   while (!feof($fp)) {
      $rowArray = NULL;
      
      //read one line
      $line = fgets($fp, 1024);

      //skip header
      if (!$headerIgnored) {
         $tmp = strpos($line, HEADER_END_MARKER);
         //Need this complex check as the source file is reported to say "Date" and Date
         //(with and without quotes) at random
         if (!$line == HEADER_END_MARKER) {
            $noValidFile = true;
         }
         if ($tmp !== false && $tmp <= 2) {
            $headerIgnored = true;
         }
         continue;
      }
   //for every line
      // Replaces the date with prior date at $date
      if ($line{0} == DATASET_BEGIN) {
         //all needed vars must be blank
         $transactionAddress = "";
         $transactionCategory = "";
         $transactionCategorySub =  "";
         $transactionCleared = "";
         $transactionDollar = "";
         $transactionE = "";
         $transactionI = "";
         $transactionMemo = "";
         $transactionNumber = "";
         $transactionPartner = "";
         $transactionQ = "";
         $transactionY = "";
         
         $date = substr($line,1);
         //format date YY-MM-DD or YYYY-MM-DD
         $date = avoid_bad_sign($date);
         $date = str_replace("'", ".",$date);
         $valutaDate = explode(".", $date); //Valuta Date 
         $valutaDate[0] = strlen2($valutaDate[0]);
         $valutaDate[1] = strlen2($valutaDate[1]);
         $valutaDate[2] = strlen4($valutaDate[2]);
         $date = $valutaDate[2]."-".$valutaDate[0]."-".$valutaDate[1];
      }
      elseif ($line{0} == "T") {
       $amount = substr($line,1);
         $amount = str_replace(",", ".",$amount);
         $amount = new Amount($amount); 
      }
      elseif ($line{0} == "P") {
         $transactionPartner = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "N") {
         $transactionNumber = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "M") {
         $transactionMemo = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "A") {
         $transactionAddress = $transactionAddress .avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "L") {
         $transactionCategorySub = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "S") {
         $transactionCategory = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "$") {
         $transactionDollar = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "I") {
         $transactionI = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "Y") {
         $transactionY = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "C") {
         $transactionCleared = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "Q") {
         $transactionQ = avoid_bad_sign(substr($line,1));
      }
      elseif ($line{0} == "E") {
         $transactionE = avoid_bad_sign(substr($line,1));
      }
      /**
       * transaction array
       *
       * @var array
       */
      if ($line{0} == DATASET_END) {
/*echo $description = $transactionPartner
               .$transactionNumber
               .$transactionMemo
               .$transactionAddress
               .$transactionCategorySub
               .$transactionCategory
               .$transactionDollar
               .$transactionI
               .$transactionCleared
               .$transactionQ
               .$transactionE; */

         $rowArray = array (
            "categoryId" => "",
            "accountId" => $accountId,
            "title" => substr($transactionPartner, 0,   99), // cut title with more than 100 chars

               // Insert your facts, what you wanna have to import in BADGER
               // DO NOT forget the "." between the vars and watch the comma at the end of the line
            "description" => $transactionNumber.$transactionMemo.$transactionAddress.$transactionE,
            "valutaDate" => new Date($date),
            "amount" => $amount,
            "transactionPartner" => $transactionPartner
         );
      }   

      // if a row contains valid data
         if ($rowArray && $line{0} == DATASET_END) {
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