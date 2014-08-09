<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://www.badger-finance.org 
*
**/
define("BADGER_ROOT", "../..");
require_once(BADGER_ROOT . "/includes/fileHeaderFrontEnd.inc.php");
require_once(BADGER_ROOT . '/modules/account/AccountManager.class.php');
require_once BADGER_ROOT . '/core/navi/naviTools.php';
require_once BADGER_ROOT . '/core/Translation2/translationTools.php';
require_once BADGER_ROOT . '/modules/csvImport/csvImportCommon.php';

$redirectPageAfterSave = "AccountManagerOverview.php";

$am = new AccountManager($badgerDb);
$curMan = new CurrencyManager($badgerDb);

if (isset($_GET['action'])) {
	switch (getGPC($_GET, 'action')) {
		case 'delete':
			//background delete
			//called by dataGrid
			if (isset($_GET['ID'])) {
				$IDs = getGPC($_GET, 'ID', 'integerList');
				
				//check if we can delete this item
				foreach($IDs as $ID){
					$am = new AccountManager($badgerDb); //workaround, because of twice calling 'getAccountById'
					$acc = $am->getAccountById( $ID );

					//delete all transactions in this account
					$acc->deleteAllTransactions();

					//delete account
					$am->deleteAccount($ID);					

					//delete entry in navigation
					deleteFromNavi($us->getProperty("accountNaviId_$ID"));
				}
			} else {
				echo "no ID was transmitted!";	
			}
			break;
		case 'save':
			//add record, update record
			if (isset($_POST['hiddenID'])) {
				updateRecord();
			} else {
				header("Location: $redirectPageAfterSave");
			}
			break;		
		case 'new':
		case 'edit':
			//frontend form for edit or insert
			printFrontend();
			break;
	}	
}
require_once(BADGER_ROOT . "/includes/fileFooter.php");

function printFrontend() {
	global $pageTitle;
	global $tpl;
	global $am;
	global $redirectPageAfterSave;
	
	$widgets = new WidgetEngine($tpl);
	$widgets->addToolTipJS();	
	$widgets->addJSValMessages();
	$tpl->addJavaScript("js/prototype.js");
	$tpl->addOnLoadEvent("Form.focusFirstElement('mainform')");
	$tpl->addJavaScript("js/account.js");
	
	$widgets->addNavigationHead();
	if (isset($_GET['ID'])) {
		$pageTitle = getBadgerTranslation2('accountAccount', 'pageTitleProp');
	} else {
		$pageTitle = getBadgerTranslation2('accountAccount', 'pageTitlePropNew');
	}
	echo $tpl->getHeader($pageTitle);	
	echo $widgets->addToolTipLayer();
	
	
	if (isset($_GET['ID'])) {
		//edit: load values for this ID
		$ID = getGPC($_GET, 'ID', 'integer');
		$account = $am->getAccountById($ID);
		$titleValue = $account->getTitle();
		$descriptionValue = $account->getDescription();
		$lowerLimitValue = is_null($tmp = $account->getLowerLimit()) ? '' : $tmp->getFormatted();
		$upperLimitValue = is_null($tmp = $account->getUpperLimit()) ? '' : $tmp->getFormatted();
		$balanceValue = is_null($tmp = $account->getBalance()) ? '' : $tmp->getFormatted();
		$currencyValue = $account->getCurrency()->getId();
		$deleteOldPlannedTransactionsValue = ($account->getDeleteOldPlannedTransactions() == false ? 'checked="checked"' : '');
		$csvParserValue = $account->getCsvParser();
	} else {
		//new: empty values
		$ID = "new";
		$account = "";
		$titleValue = "";
		$descriptionValue = "";
		$lowerLimitValue = "";
		$upperLimitValue = "";
		$balanceValue = "";
		$currencyValue = "";
		$deleteOldPlannedTransactionsValue = 'checked="checked"';
		$csvParserValue = '';
	}
	//set vars with values
	$FormAction = $_SERVER['PHP_SELF'];
	$legend = getBadgerTranslation2('accountAccount', 'legend');
	$hiddenID = $widgets->createField("hiddenID", 20, $ID, "", false, "hidden");
	$pageHeading = $pageTitle;
	//Fields & Labels
	$titleLabel = $widgets->createLabel("title", getBadgerTranslation2('accountAccount', 'title'), true);
	$titleField = $widgets->createField("title", 30, $titleValue, "", true, "text",  "style='width: 30ex;'");
	$descriptionLabel = $widgets->createLabel("description", getBadgerTranslation2('accountAccount', 'description'), false);
	$descriptionField = $widgets->createTextarea("description", $descriptionValue, "", false, "style='width: 30ex; height: 5em;'");
	$lowerLimitLabel = $widgets->createLabel("lowerLimit", getBadgerTranslation2('accountAccount', 'lowerLimit'), false);
	$lowerLimitField = $widgets->createField("lowerLimit", 30, $lowerLimitValue, "", false, "text", "style='width: 30ex;' regexp='BADGER_NUMBER'");
	$upperLimitLabel = $widgets->createLabel("upperLimit", getBadgerTranslation2('accountAccount', 'upperLimit'), false);
	$upperLimitField = $widgets->createField("upperLimit", 30, $upperLimitValue, "", true, "text", "style='width: 30ex;' regexp='BADGER_NUMBER'");

	$currencyLabel = $widgets->createLabel("currency", getBadgerTranslation2('accountAccount', 'currency'), true);
	$currencies = getCurrencyArray('symbol');
	$currencyField = $widgets->createSelectField("currency", $currencies, $default=$currencyValue, "", false, "style='width: 31ex;'");

	$deleteOldPlannedTransactionsLabel = $widgets->createLabel('deleteOldPlannedTransactions', getBadgerTranslation2('accountAccount', 'deleteOldPlannedTransactions'), false);
	$deleteOldPlannedTransactionsField = $widgets->createField('deleteOldPlannedTransactions', 30, 'on', getBadgerTranslation2('accountAccount', 'deleteOldPlannedTransactionsDescription'), false, 'checkbox', $deleteOldPlannedTransactionsValue);
	
	$csvParserLabel = $widgets->createLabel('csvParser', getBadgerTranslation2('accountAccount', 'csvParser'), false);
	$csvParsers = getParsers(); 
	$csvParsers = array_merge(array ('NULL' => getBadgerTranslation2('accountAccount', 'csvNoParser')), $csvParsers);
	$csvParserField = $widgets->createSelectField('csvParser', $csvParsers, $csvParserValue, '', false, "style='width: 31ex;'");

	//Buttons
	$submitBtn = $widgets->createButton("submitBtn", getBadgerTranslation2('dataGrid', 'save'), "submit", "Widgets/accept.gif", "accesskey='s'");
	$backBtn = $widgets->createButton("backBtn", getBadgerTranslation2('dataGrid', 'back'), "location.href='$redirectPageAfterSave';return false;", "Widgets/back.gif");

	//add vars to template, print site
	eval("echo \"".$tpl->getTemplate("Account/Account")."\";");
	eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");
	
}


function updateRecord() {
	global $redirectPageAfterSave;
	global $am; //Account Manager
	global $curMan; //Currency Manager
	global $us;
	
	if (isset($_POST['hiddenID'])) {
		switch (getGPC($_POST, 'hiddenID')) {
		case 'new':
			//add new record
			$ID = $am->addAccount(
				getGPC($_POST, 'title'),
				$curMan->getCurrencyById(getGPC($_POST, 'currency', 'integer')),
				getGPC($_POST, 'description'),
				getGPC($_POST, 'lowerLimit', 'AmountFormatted'),
				getGPC($_POST, 'upperLimit', 'AmountFormatted'),
				getGPC($_POST, 'csvParser'),
				!getGPC($_POST, 'deleteOldPlannedTransactions', 'checkbox')
			);
			
			$naviId = addToNavi(
				$us->getProperty('accountNaviParent'),
				$us->getProperty('accountNaviNextPosition'),
				'item',
				'Account' . $ID->getId(),
				'account.gif',
				'{BADGER_ROOT}/modules/account/AccountOverview.php?accountID=' . $ID->getId()
			);
			$us->setProperty('accountNaviId_' . $ID->getId(), $naviId);
			$us->setProperty('accountNaviNextPosition', $us->getProperty('accountNaviNextPosition') + 1);
			addTranslation('Navigation', 'Account' . $ID->getId(), getGPC($_POST, 'title'), getGPC($_POST, 'title'), getGPC($_POST, 'title'));
			
			$account = $ID;
			break;
			
		default:
			//update record
			$account = $am->getAccountById(getGPC($_POST, 'hiddenID', 'integer'));
			$account->setTitle(getGPC($_POST, 'title'));
			$account->setDescription(getGPC($_POST, 'description'));
			$account->setCurrency($curMan->getCurrencyById(getGPC($_POST, 'currency', 'integer')));
			$account->setLowerLimit(getGPC($_POST, 'lowerLimit', 'AmountFormatted'));
			$account->setUpperLimit(getGPC($_POST, 'upperLimit', 'AmountFormatted'));
			$account->setDeleteOldPlannedTransactions(!getGPC($_POST, 'deleteOldPlannedTransactions', 'checkbox'));
			$account->setCsvParser(getGPC($_POST, 'csvParser'));

			modifyTranslation('Navigation', 'Account' . $account->getId(), getGPC($_POST, 'title'), getGPC($_POST, 'title'), getGPC($_POST, 'title'));
		}
		
		$account->expandPlannedTransactions(new Date('1000-01-01'));
		
		//REDIRECT
		header("Location: $redirectPageAfterSave");
	}	
}

function getCurrencyArray($sortBy){
	
	global $badgerDb;
	
	$curMan = new CurrencyManager($badgerDb);
	$order = array ( 
	      array(
	           'key' => $sortBy,
	           'dir' => 'asc'
	           )
	 );
      
 	$curMan->setOrder($order);
	
	$curs = array();
	while ($cur = $curMan->getNextCurrency()) {
		$curs[$cur->getId()] = $cur->getLongName();
	};
	
	return $curs;
}
?>