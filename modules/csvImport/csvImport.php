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
define("BADGER_ROOT", "../.."); 
require_once(BADGER_ROOT . '/includes/fileHeaderFrontEnd.inc.php');
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';
require_once BADGER_ROOT . '/modules/account/CategoryManager.class.php';
require_once BADGER_ROOT . '/modules/account/accountCommon.php';
require_once BADGER_ROOT . '/core/Date/Calc.php';
require_once BADGER_ROOT . '/modules/csvImport/csvImportCommon.php';

$pageHeading = getBadgerTranslation2('importCsv', 'pageHeading');
$legend = getBadgerTranslation2('importCsv','legend');

//include widget functionalaty
$widgets = new WidgetEngine($tpl); 
$widgets->addToolTipJS();
$widgets->addCalendarJS();
$widgets->addJSValMessages();
$tpl->addJavaScript('/js/prototype.js');
$tpl->addJavaScript('/js/csvImport.js');

$widgets->addNavigationHead();
echo $tpl->getHeader($pageHeading);
echo $widgets->addToolTipLayer();

//create account manger object
$am = new AccountManager($badgerDb);
$am->setOrder(array (array ('key' => 'title', 'dir' => 'asc')));

//if no Upload yet, show form
if (!isset($_POST['btnSubmit'])){
	if (!isset($_POST['Upload'])){	
		
		$fileLabel =  $widgets->createLabel("", getBadgerTranslation2("importCsv", "selectFile").":", true);
		# widget for browse field has to be developed
		//$fileField = $widgets->createField("file", 50, "", "description", true);
		$fileField = "<input name=\"file\" type=\"file\" size=\"50\" required=\"required\" />";
		
		$accountSelectLabel =  $widgets->createLabel("accountSelect", getBadgerTranslation2("importCsv", "targetAccount").":", true);					      	
		//get accounts
		$account = array();
	    $accountParserJS = "var accountParsers = new Array();\n";  
		$accountParsers = array();
    	while ($currentAccount = $am->getNextAccount()) {
    		$account[$currentAccount->getId()] = $currentAccount->getTitle();
    		$accountParsers[$currentAccount->getId()] = $currentAccount->getCsvParser(); 
    		$accountParserJS .= "accountParsers['" . $currentAccount->getId() . "'] = '" . $currentAccount->getCsvParser() . "';\n";
    	}
    	$accountParserJS .= "updateParser()\n";
    	
		try {
			$standardAccount = $us->getProperty("csvImportStandardAccount");
		} catch (BadgerException $ex) {
			$standardAccount = '';
		}

	    $accountSelectFile = $widgets->createSelectField("accountSelect", $account, $standardAccount, getBadgerTranslation2("importCsv", "toolTopAccountSelect"), true, "onchange='updateParser();'");

		$selectParserLabel =  $widgets->createLabel("parserSelect", getBadgerTranslation2("importCsv", "selectParser").":", true);
/*
    		//sql to get CSV Parsers
	    	$sql = "SELECT * FROM csv_parser";
	      	$parser = array();
	      	$res =& $badgerDb->query($sql);
	      	while ($res->fetchInto ($row)){ 
	      		$parser[$row[2]] = $row[1];
	      	}
*/	      	
		$parser = getParsers();

      	$selectParserFile = $widgets->createSelectField("parserSelect", $parser, null, getBadgerTranslation2("importCsv", "toolTipParserSelect"));
		

		$uploadButton = $widgets->createButton("Upload", getBadgerTranslation2("importCsv", "upload"), "submit", "Widgets/table_save.gif");
		//use tempate engine
		eval("echo \"".$tpl->getTemplate("CsvImport/csvImportSelectFileForm")."\";");
		
	}
}
if (isset($_POST['Upload'])){
	$uploadTitle = getBadgerTranslation2('importCsv', 'uploadTitle');
	
	// for every file
	foreach($_FILES as $file_name => $file_array) {
		//if a file is chosen
		if (isset($_POST["file"])){
		 	#eval("echo \"".$tpl->getTemplate("CsvImport/csvImportWarning")."\";");
		}
		if (is_uploaded_file($file_array['tmp_name'])) {
			//open file
			$fp = fopen($file_array['tmp_name'], "r");
	 		//open selected parser
	 		require_once(BADGER_ROOT . "/modules/csvImport/parser/" . getGPC($_POST, 'parserSelect'));
	 		//save last used parser
	 		$accountId = getGPC($_POST, 'accountSelect', 'integer');
	 		//save last used account
	 		$us->setProperty("csvImportStandardAccount", $accountId);
	 		
	 		//call to parse function
	 		$foundTransactions = parseToArray($fp, $accountId);
	 		//delete existing transactions, criteria are accountid, date & amount
	 		$LookupTransactionNumber = count($foundTransactions);
	 		$filteredTransactions = 0;
	 		$importedTransactionNumber = 0;
	 		$importedTransactions = NULL;
	 		//for every transaction received from the parser
	 		for ($foundTransactionNumber = 0; $foundTransactionNumber < $LookupTransactionNumber; $foundTransactionNumber++) {
	 			$am4 = new AccountManager($badgerDb);
	 			$account4 = $am4->getAccountById($foundTransactions[$foundTransactionNumber]["accountId"]);
	 			//set filter to read existing transactions from database
	 			$account4->setFilter(array (
					array (
						'key' => 'valutaDate',
						'op' => 'eq',
						'val' => $foundTransactions[$foundTransactionNumber]["valutaDate"]
					),
					array (
						'key' => 'amount',
						'op' => 'eq',
						'val' => $foundTransactions[$foundTransactionNumber]["amount"]
					)
				));
				//if there is a transaction with same amount & valutaDate in the database
				if ($existing = $account4->getNextFinishedTransaction()) {
					$filteredTransactions++;
				} else {
					$importedTransactions[$importedTransactionNumber] = importMatching($foundTransactions[$foundTransactionNumber], $accountId);
 					$importedTransactionNumber++;
				}
	 		}
	 		if ($filteredTransactions != 0){	
	 			//feedback to user about filtered transactions
	 			echo $filteredTransactions . " " . getBadgerTranslation2("importCsv", "echoFilteredTransactionNumber");
	 		}
	 		
	 		$transactionNumber = count($importedTransactions);
			$tplOutput = NULL;
	 		//show content of the array, using the template engine
	 		if ($transactionNumber > 0){  		
   				$tableHeadSelect = $widgets->createLabel("", getBadgerTranslation2("importCsv", "select"), true);
   				$HeadSelectToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "selectToolTip"));
   				$tableHeadCategory = $widgets->createLabel("", getBadgerTranslation2("importCsv", "category"), true);
				$HeadCategoryToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "categoryToolTip"));
				$tableHeadValutaDate = $widgets->createLabel("", getBadgerTranslation2("importCsv", "valutaDate"), true);
   				$HeadValueDateToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "valuedateToolTip"));
   				$tableHeadTitle = $widgets->createLabel("", getBadgerTranslation2("importCsv", "title"), true);
   				$HeadTitleToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "titleToolTip"));
   				$tableHeadAmount = $widgets->createLabel("", getBadgerTranslation2("importCsv", "amount"), true);
   				$HeadAmountToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "amountToolTip"));
   				$tableHeadTransactionPartner = $widgets->createLabel("", getBadgerTranslation2("importCsv", "transactionPartner"), true);
   				$HeadTransactionPartnerToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "transactionPartnerToolTip"));
   				$tableHeadDescription = $widgets->createLabel("", getBadgerTranslation2("importCsv", "description"), true);
   				$HeadDescriptionToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "descriptionToolTip"));
   				$tableHeadPeriodical = $widgets->createLabel("", getBadgerTranslation2("importCsv", "periodical"), true);
   				$HeadPeriodicalToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "periodicalToolTip"));
   				$tableHeadExceptional = $widgets->createLabel("", getBadgerTranslation2("importCsv", "Exceptional"), true);
   				$HeadExceptionalToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "ExceptionalToolTip"));
   				$tableHeadOutside = $widgets->createLabel("", getBadgerTranslation2("importCsv", "outsideCapital"), true);
   				$HeadOutsideToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "outsideCapitalToolTip"));
   				$tableHeadAccount = $widgets->createLabel("", getBadgerTranslation2("importCsv", "account"), true);
				$HeadAccountToolTip =  $widgets->addToolTip(getBadgerTranslation2("importCsv", "accountToolTip"));
				$tableHeadMatching = $widgets->createLabel('', getBadgerTranslation2('importCsv', 'matchingHeader'), true);
				$HeadMatchingToolTip = $widgets->addToolTip(getBadgerTranslation2('importCsv', 'matchingToolTip'));
				
				//get accounts	
				$am1 = new AccountManager($badgerDb);
				$account1 = array();
		    	while ($currentAccount = $am1->getNextAccount()) {
		    		$account1[$currentAccount->getId()] = $currentAccount->getTitle();	
		    	}

   				for ($outputTransactionNumber = 0; $outputTransactionNumber < $transactionNumber; $outputTransactionNumber++) {
   					
			    	$matchingTransactions = array();
					$disableFields = '';
			    	if (isset($importedTransactions[$outputTransactionNumber]['similarTransactions'])) {
			    		foreach($importedTransactions[$outputTransactionNumber]['similarTransactions'] as $similarity => $currentTransaction) {
			    			$matchingTransactions[$currentTransaction->getId()] = $currentTransaction->getTitle() . ' ' . round($similarity * 100, 0) . ' %'; 
			    		}
			    		
			    		$disableFields = ' disabled="disabled"';
			    	}
		    		$matchingTransactions['none'] = getBadgerTranslation2('importCsv', 'dontMatchTransaction');

					$tableSelectCheckbox = "<input type=\"checkbox\" name=\"select" . $outputTransactionNumber . "\" value=\"select\" checked=\"checked\" />";

					$tableSelectCategory= $widgets->createSelectField("categorySelect".$outputTransactionNumber, getCategorySelectArray(false), $importedTransactions[$outputTransactionNumber]['categoryId'], '', false, $disableFields);
						    	
				    $tableValutaDate = $widgets->addDateField("valutaDate".$outputTransactionNumber, $importedTransactions[$outputTransactionNumber]["valutaDate"]->getFormatted());
				    						    
					$tableTitle = $widgets->createField("title".$outputTransactionNumber, 30, $importedTransactions[$outputTransactionNumber]["title"]);
   							
					$tableAmount = $widgets->createField("amount".$outputTransactionNumber, 8, $importedTransactions[$outputTransactionNumber]["amount"]->getFormatted());
   							
					$tableTransactionPartner = $widgets->createField("transactionPartner".$outputTransactionNumber, 15, $importedTransactions[$outputTransactionNumber]["transactionPartner"]);
   							
					$tableDescription = $widgets->createField("description".$outputTransactionNumber, 12, $importedTransactions[$outputTransactionNumber]["description"]);
   							
					$tablePeriodicalCheckbox = "<input type=\"checkbox\" id =\"periodical$outputTransactionNumber\" name=\"periodical" . $outputTransactionNumber . "\" value=\"select\" $disableFields/>";
   							
					$tableExceptionalCheckbox = "<input type=\"checkbox\" id=\"exceptional$outputTransactionNumber\" name=\"exceptional" . $outputTransactionNumber . "\" value=\"select\" $disableFields/>";
   					
   					$tableOutsideCheckbox = "<input type=\"checkbox\" id=\"outside$outputTransactionNumber\" name=\"outside" . $outputTransactionNumber . "\" value=\"select\" $disableFields/>";

			    	$tableSelectAccount= $widgets->createSelectField("account2Select".$outputTransactionNumber, $account1, $importedTransactions[$outputTransactionNumber]["accountId"], '', false, $disableFields);
			    	
			    	$tableSelectMatchingTransaction = $widgets->createSelectField('matchingTransactionSelect' . $outputTransactionNumber, $matchingTransactions, null, '', true, 'onchange="updateDisabledFields(' . $outputTransactionNumber . ');"');
			    	
					//echo ("\$tplOutput .= \"".$tpl->getTemplate("CsvImport/csvImportSelectTransactions2")."\";");
					eval("\$tplOutput .= \"".$tpl->getTemplate("CsvImport/csvImportSelectTransactions2")."\";");
   				}
   				$hiddenField = "<input type=\"hidden\" name=\"tableRows\" value=\"" . $transactionNumber . " \">";
   				$hiddenAccountId = $widgets->createField('hiddenAccountId', 0, $accountId, null, false, 'hidden');
				$buttonSubmit = $widgets->createButton("btnSubmit", getBadgerTranslation2("importCsv", "save"), "submit", "Widgets/table_save.gif");
				
	 			eval("echo \"".$tpl->getTemplate("CsvImport/csvImportSelectTransactions1")."\";");
	 		} else{
	 			echo " " . getBadgerTranslation2("importCsv", "noNewTransactions");
	 		}	
	  	
		}
	}	
}
if (isset($_POST['btnSubmit'])){		

	// create array with the selected transaction from the form above

	// to count number of selected transactions
	$selectedTransaction = 0;
	//initalise array
	$writeToDbArray = NULL;

	$am3 = new AccountManager($badgerDb);

	$baseAccountId = getGPC($_POST, 'hiddenAccountId', 'integer');
	$baseAccount = $am3->getAccountById($baseAccountId); 

	$cm1 = new CategoryManager($badgerDb);

	$targetAccounts = array();

	$targetAccounts["x$baseAccountId"] = $baseAccount;
	
	//for all rows
	for ($selectedTransactionNumber = 0; $selectedTransactionNumber < getGPC($_POST, 'tableRows', 'integer'); $selectedTransactionNumber++) {
		//reset tableRowArray
		$tableRowArray = NULL;
		// if the transaction was selected
		if (getGPC($_POST, "select$selectedTransactionNumber", 'checkbox')) {
			if (getGPC($_POST, 'matchingTransactionSelect' . $selectedTransactionNumber) == 'none') {
				$account3 = $am3->getAccountById(getGPC($_POST, 'account2Select' . $selectedTransactionNumber, 'integer'));
				
				$targetAccounts['x' . $account3->getId()] = $account3;
				
				$transactionCategory = NULL;
				if (!getGPC($_POST, 'categorySelect' . $selectedTransactionNumber) == NULL){
					if (getGPC($_POST, 'categorySelect' . $selectedTransactionNumber) != "NULL"){
						$transactionCategory = $cm1->getCategoryById(getGPC($_POST, 'categorySelect' . $selectedTransactionNumber, 'integer'));
					}
				}
				$account3->addFinishedTransaction(
					getGPC($_POST, 'amount' . $selectedTransactionNumber, 'AmountFormatted'),
					getGPC($_POST, 'title' . $selectedTransactionNumber),
					getGPC($_POST, 'description' . $selectedTransactionNumber),
					getGPC($_POST, 'valutaDate' . $selectedTransactionNumber, 'DateFormatted'),
					getGPC($_POST, 'transactionPartner' . $selectedTransactionNumber),
					$transactionCategory,
					getGPC($_POST, "outside" . $selectedTransactionNumber, 'checkbox'),
					getGPC($_POST, "exceptional" . $selectedTransactionNumber, 'checkbox'),
					getGPC($_POST, 'periodical' . $selectedTransactionNumber, 'checkbox')
				);
			} else {
				//Update existing transaction
				$transaction = $baseAccount->getFinishedTransactionById(getGPC($_POST, 'matchingTransactionSelect' . $selectedTransactionNumber, 'integer'));
				$transaction->setTitle($transaction->getTitle() . ' - ' . getGPC($_POST, 'title' . $selectedTransactionNumber));
				$transaction->setDescription(
					$transaction->getDescription()
					. "\n"
					. getGPC($_POST, 'description' . $selectedTransactionNumber)
					. "\n"
					. getBadgerTranslation2('importCsv', 'descriptionFieldImportedPartner')
					. getGPC($_POST, 'transactionPartner' . $selectedTransactionNumber)
					. "\n"
					. getBadgerTranslation2('importCsv', 'descriptionFieldOrigValutaDate')
					. $transaction->getValutaDate()->getFormatted()
					. "\n"
					. getBadgerTranslation2('importCsv', 'descriptionFieldOrigAmount')
					. $transaction->getAmount()->getFormatted()
				);
				$transaction->setValutaDate(getGPC($_POST, 'valutaDate' . $selectedTransactionNumber, 'DateFormatted'));
				$transaction->setAmount(getGPC($_POST, 'amount' . $selectedTransactionNumber, 'AmountFormatted'));
				if (strpos($transaction->getType(), 'Planned') !== false) {
					$transaction->setPlannedTransaction(null);
				}
			}
		}
	}

	$submitTitle = getBadgerTranslation2('importCsv', 'submitTitle');
	echo "<h1>$submitTitle</h1>\n";
	
	if ($selectedTransactionNumber > 0) {
		// echo success message & number of written transactions
		echo 
			$selectedTransactionNumber
			. ' '
			. getBadgerTranslation2("importCsv", "successfullyWritten")
			. '<ul>';
		;
		foreach ($targetAccounts as $currentAccount) {
			echo
				'<li><a href="'
				. BADGER_ROOT
				. '/modules/account/AccountOverview.php?accountID='
				. $currentAccount->getId()
				. '">'
				. htmlentities($currentAccount->getTitle())
				. '</a></li>'
			;	
		}
		echo '</ul>';
		
	}else {
		//echo no transactions selected
		echo getBadgerTranslation2("importCsv", "noTransactionSelected");
	}
} 		
eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");
require_once(BADGER_ROOT . "/includes/fileFooter.php");

function importMatching($importedTransaction, $accountId) {
	global $us;
	global $badgerDb;

	static $dateDelta = null;
	static $amountDelta = null;
	static $textSimilarity = null;
	static $categories = null;
	
	if (is_null($dateDelta)) {
		try {
			$dateDelta = $us->getProperty('matchingDateDelta');
		} catch (BadgerException $ex) {
			$dateDelta = 5;
		}
		
		try {
			$amountDelta = $us->getProperty('matchingAmountDelta');
		} catch (BadgerException $ex) {
			$amountDelta = 0.1;
		}
		
		try {
			$textSimilarity = $us->getProperty('matchingTextSimilarity');
		} catch (BadgerException $ex) {
			$textSimilarity = 0.25;
		}
		
		$categoryManager = new CategoryManager($badgerDb);
		while ($currentCategory = $categoryManager->getNextCategory()) {
			$categories[$currentCategory->getId()] = preg_split('/[\n]+/', $currentCategory->getKeywords(), -1, PREG_SPLIT_NO_EMPTY);
		}
	}

	if (!$importedTransaction['valutaDate']) {
		return $importedTransaction;
	}

	$minDate = new Date($importedTransaction['valutaDate']);
	$minDate->subtractSeconds($dateDelta * 24 * 60 * 60);
	
	$maxDate = new Date($importedTransaction['valutaDate']);
	$maxDate->addSeconds($dateDelta * 24 * 60 * 60);
	
	if (!$importedTransaction['amount']) {
		return $importedTransaction;
	}
	
	$minAmount = new Amount($importedTransaction['amount']);
	$minAmount->mul(1 - $amountDelta);
	
	$maxAmount = new Amount($importedTransaction['amount']);
	$maxAmount->mul(1 + $amountDelta);
	
	$accountManager = new AccountManager($badgerDb);
	$account = $accountManager->getAccountById($accountId);
	
	if ($minAmount->compare(0) < 0) {
		$tmpAmount = $maxAmount;
		$maxAmount = $minAmount;
		$minAmount = $tmpAmount;
		unset($tmpAmount);
	}
	
	$account->setFilter(array (
		array (
			'key' => 'valutaDate',
			'op' => 'ge',
			'val' => $minDate
		),
		array (
			'key' => 'valutaDate',
			'op' => 'le',
			'val' => $maxDate
		),
		array (
			'key' => 'amount',
			'op' => 'ge',
			'val' => $minAmount
		),
		array (
			'key' => 'amount',
			'op' => 'le',
			'val' => $maxAmount
		)
	));
	
	$similarTransactions = array();

	while ($currentTransaction = $account->getNextTransaction()) {
		$titleSimilarity = getSimilarity($importedTransaction['title'], $currentTransaction->getTitle(), $textSimilarity);
		$descriptionSimilarity = getSimilarity($importedTransaction['description'], $currentTransaction->getDescription(), $textSimilarity);
		$transactionPartnerSimilarity = getSimilarity($importedTransaction['transactionPartner'], $currentTransaction->getTransactionPartner(), $textSimilarity);
		
		$currDate = $currentTransaction->getValutaDate();
		$impDate = $importedTransaction['valutaDate'];
		$dateSimilarity = 1 - (abs(Date_Calc::dateDiff(
			$currDate->getDay(), $currDate->getMonth(), $currDate->getYear(),
			$impDate->getDay(), $impDate->getMonth(), $impDate->getYear())
		) / $dateDelta);
		
		$cmpAmount = new Amount($currentTransaction->getAmount());
		$impAmount = new Amount($importedTransaction['amount']);
		$cmpAmount->sub($impAmount);
		$cmpAmount->abs();
		$impAmount->mul($amountDelta);
		$impAmount->abs();
		$amountSimilarity = 1 - $cmpAmount->div($impAmount)->get();
		
		$currentTextSimilarity = ($titleSimilarity + $descriptionSimilarity + $transactionPartnerSimilarity) / 3;

		if ($currentTextSimilarity >= $textSimilarity) {
			$overallSimilarity = ($titleSimilarity + $descriptionSimilarity + $transactionPartnerSimilarity + $dateSimilarity + $amountSimilarity) / 5;
			
			//$similarTransactions["$overallSimilarity t:$titleSimilarity d:$descriptionSimilarity tp:$transactionPartnerSimilarity vd:$dateSimilarity a:$amountSimilarity"] = $currentTransaction;
			$similarTransactions["$overallSimilarity"] = $currentTransaction;
		}
	}
	
	krsort($similarTransactions);
	
	if (count($similarTransactions)) {
		$importedTransaction['similarTransactions'] = $similarTransactions;
		
		return $importedTransaction;
	}
	
	if ($importedTransaction['categoryId']) {
		return $importedTransaction;
	}
	
	$transactionStrings = array (
		$importedTransaction['title'],
		$importedTransaction['description'],
		$importedTransaction['transactionPartner']
	);

	foreach ($transactionStrings as $currentTransactionString) {
		foreach ($categories as $currentCategoryId => $keywords) {
			foreach ($keywords as $keyword) {
				if (stripos($currentTransactionString, trim($keyword)) !== false) {
					$importedTransaction['categoryId'] = $currentCategoryId;
					
					break 3;
				} //if keyword found
			} //foreach keywords
		} //foreach categories
	} //foreach transactionStrings
	
	return $importedTransaction;
}

function getSimilarity ($haystack, $needle, $default) {
	if ($haystack == '' || $needle == '') {
		return $default;
	}

	if (stripos($haystack, $needle) !== false) {
		return 1;
	}

	$result = 0;
	similar_text(strtolower($haystack), strtolower($needle), $result);
	return $result / 100;	
}
?>