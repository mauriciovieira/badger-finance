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
* Parse .csv files from Löchgauer Bank (Germany). Tested with files from 07.08.2006
* written by juergen
*/
//Probably valid up to 31.10.2006 (date of planed VR-internal Software-Changing)
//The next line determines the displayed name of this parser.
// BADGER_REAL_PARSER_NAME Löchgauer Bank
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
         * is set true, a line contains ";" , but not the correct number for this parser (5)
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
            //ignore header (first 13 lines)
            if (!$headerIgnored){
                for ($headerLine = 0; $headerLine < 13; $headerLine++) {
                    $garbage = fgets($fp, 1024);
                    //to ignore this code on the next loop run
                    $headerIgnored = true;
                }
            }
            //read one line
            $line = fgets($fp, 1024); //echo substr_count ($line, ";"); echo $line;
/*" Example dataset.csv
31.07.2006";"01.08.2006";"LASTSCHR.             $transactionArray[0],$transactionArray[1],$transactionArray[2]
Empfänger.                                      $transactionArray[3] //transactionPartner
Textzeile 1                                     $transactionArray[4]
Textzeile 2                                     $transactionArray[4]
KNr 0047110000 BLZ 12345678";"EUR";"24,09";"S"  $transactionArray[5],$transactionArray[6],$transactionArray[7],$transactionArray[8]
*/
            //if line is not empty or is no header
                //if line contains excactly 2 ';', to ensure it is a valid VR-Bank csv file
                if (substr_count ($line, ";")==2){
                    $description = "";
                    $line1 = 1; //set the first line of dataset
                    // divide String to an array
                    $transactionArray = explode(";", $line);
                    //format date YY-MM-DD or YYYY-MM-DD
                    $transactionArray[1] = str_replace("\"","",$transactionArray[1]);
                    $transactionArray[1] = str_replace("\\","",$transactionArray[1]);
                    $valutaDate = explode(".", $transactionArray[1]); //Valuta Date
                    $valutaDate[4] = $valutaDate[2] . "-" . $valutaDate[1] . "-" . $valutaDate[0];
                    $valutaDate1 = new Date($valutaDate[4]);

                    $transactionArray[2] = str_replace("\"","",$transactionArray[2]);
                    $transactionArray[2] = str_replace("\n","",$transactionArray[2]);
                    $description .= $transactionArray[2];

                //if line contains excactly 3 ';', to ensure it is a valid VR-Bank csv file
                } elseif ($line1 == 1 && !strpos($line,';')){
                    $transactionPartner = $transactionArray[3] = str_replace("\n","",$line); $line1++;
                } elseif ($line1 == 2 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 3 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 4 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 5 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 6 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 7 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 8 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 9 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
                } elseif ($line1 == 10 && !strpos($line,';')){
                    $description .= str_replace("\n","",$line).", "; $line1++;
               
                //if line contains excactly 3 ';', to ensure it is a valid Löchgauer-Bank csv file
                } elseif (substr_count ($line, ";")==3){
                    $line1 = 0;
                    // divide String to an array
                    $transactionArray3 = explode(";", $line);
                    //avoid " & \ in the title & description, those characters could cause problems
                    $transactionArray3[0] = str_replace("\"","",$transactionArray3[0]);
                    $transactionArray3[0] = str_replace("\\","",$transactionArray3[0]);
                    $transactionArray[5] = $transactionArray3[0];
                   
                    $transactionArray3[1] = str_replace("\"","",$transactionArray3[1]);
                    $transactionArray3[1] = str_replace("\\","",$transactionArray3[1]);
                    $transactionArray[6] = $transactionArray3[1];
                   
                    $transactionArray3[2] = str_replace("\"","",$transactionArray3[2]);
                    $transactionArray3[2] = str_replace("\\","",$transactionArray3[2]);
                    $transactionArray3[2] = str_replace(",",".",$transactionArray3[2]);
                    $amount = $transactionArray[7] = $transactionArray3[2];

                    $transactionArray3[3] = str_replace("\"","",$transactionArray3[3]);
                    $transactionArray3[3] = str_replace("\\","",$transactionArray3[3]);
                    $transactionArray[8] = $transactionArray3[3];

                    if ($transactionArray3[3] == "S") {
                    $transactionArray3[2] = "-".$transactionArray3[2]; //"-".
                    }
                    $amount1 = new Amount($transactionArray3[2]);
                   // print_r ($transactionArray3);
                } else{
                   // $noValidFile = 'true';
                }
                $transactionArray[4] = $description;
             
                    /**
                     * transaction array
                     *
                     * @var array
                     */
              if ($line1 == 0) {
	                $rowArray = array (
	                   "categoryId" => "",
	                   "accountId" => $accountId,
	                   "title" => substr($transactionArray[3],0,99),// cut title with more than 100 chars
	                   "description" => $transactionArray[4].", ".$transactionArray[5],
	                   "valutaDate" => $valutaDate1,
	                   "amount" => new Amount($transactionArray3[2]),
	                   "transactionPartner" => $transactionPartner
	                );
                //print_r ($transactionArray);
                //echo "<br>";
                }
            // if a row contains valid data
            if ($rowArray && $line1 ==0){
                /**
                 * array of all transaction arrays
                 *
                 * @var array
                 */
                $importedTransactions[$csvRow] = $rowArray;
                $csvRow++;
                //print_r ($rowArray);
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
                //delete footer (1 line)
                unset($importedTransactions[$csvRow-1]);
                //close file
                fclose ($fp);
                return $importedTransactions;
            }
        }
}
?>