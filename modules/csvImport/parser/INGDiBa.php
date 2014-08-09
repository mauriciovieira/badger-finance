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
* Parse .csv files from INGDiBa (Germany) for an "Extra Konto"
* 
* last change by Sepp (05.02.2008): DiBa has changes csv format
**/
/* AUFBAU:
 * 1: Buchungsdatum                   
 * 2: Valuta                   
 * 3: Auftraggeber/Empfänger
 * 4: Buchungstext
 * 5: Verwendungszweck
 * 6: Betrag
 * 7: Währung
 */ 
// The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME ING DiBa (Extra Konto)
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
        $headerIgnored = NULL;
        //for every line
        while (!feof($fp)) {
            //read one line
            $rowArray = NULL;
            //ignore header (first 9 lines)
            if (!$headerIgnored){
                for ($headerLine = 0; $headerLine < 8; $headerLine++) {
                    $garbage = fgets($fp, 1024);
                    //to ignore this code on the next loop run
                    $headerIgnored = true;
                }
            }
            //read one line
            $line = fgets($fp, 1024);
            //if line is not empty or is no header
            if (strstr($line, ";")) { // 
            
                //if line contains excactly 6 ;, to ensure it is a valid csv file
                if (substr_count ($line, ";")==6){
                    // divide String to an array
                    $transactionArray = explode(";", $line);
                    
                    // 0. Booking date (Buchungsdatum)
                    //$transactionArray[0] =   UNUSED
                    
                    // 1. Valuta Date (Wertstellung)                    
                    $transValuta = str_replace("\"","",$transactionArray[1]);
                    $transValuta = explode(".", $transValuta); //Valuta Date
                    $transValuta[4] = $transValuta[2] . "-" . $transValuta[1] . "-" . $transValuta[0];
                    $valutaDateObj = new Date($transValuta[4]);
                    
                    // 2. Transaction Partner (Auftraggeber/Empfänger)
                    $transactionPartner = $transactionArray[2]; 
                    $transactionPartner = str_replace("\"","",$transactionPartner);
                    $transactionPartner = str_replace("\\","",$transactionPartner);
                    $transactionPartner = trim($transactionPartner);
                    
					// 3. Description (Buchungstext, eg. Gutschrift)
                    $transDescription = $transactionArray[3];
                    $transDescription = str_replace("\"","",$transDescription);
                    $transDescription = str_replace("\\","",$transDescription);
                    $transDescription = trim($transDescription);
                    
                    // 4. Title (Verwendungszweck)
					$transTitle = $transactionArray[4];
                    $transTitle = str_replace("\"","",$transTitle);
                    $transTitle = str_replace("\\","",$transTitle);
                    $transTitle = trim($transTitle);
                               
                    // 5. Amount (Betrag)
                    $transAmount = $transactionArray[5];
					$transAmount = str_replace("\"","", $transAmount);
                    $transAmount = str_replace("\\","", $transAmount);
                    //format amount to usersettings
                    //$transAmount = str_replace(".","", $transAmount);
                    //$transAmount = str_replace(",",".",$transAmount);
                    $transAmountObj = new Amount($transAmount);
                    
                    
                    /**
                     * transaction array
                     *
                     * @var array
                     */
                    $rowArray = array (
                       "categoryId" => "",
                       "accountId" => $accountId,
                       "title" => substr($transTitle, 0,99),// cut title with more than 100 chars
                       "description" => $transDescription,
                       "valutaDate" => $valutaDateObj,
                       "amount" => $transAmountObj,
                       "transactionPartner" => $transactionPartner
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