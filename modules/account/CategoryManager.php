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
require_once(BADGER_ROOT . '/modules/account/CategoryManager.class.php');
require_once(BADGER_ROOT . '/modules/account/AccountManager.class.php');


$redirectPageAfterSave = "CategoryManagerOverview.php";

$cm = new CategoryManager($badgerDb);
$am = new AccountManager($badgerDb);

$order = array (
	array(
		'key' => 'parentTitle',
		'dir' => 'asc'
		)
);	
$cm->setOrder($order);

if (isset($_GET['action'])) {
	switch (getGPC($_GET, 'action')) {
		case 'delete':
			//background delete
			//called by dataGrid
			if (isset($_GET['ID'])) {
				$IDs = getGPC($_GET, 'ID', 'integerList');
				
				//for all categories which should be deleted
				foreach($IDs as $ID){
					//for all accounts:
//					$am = new AccountManager($badgerDb);
//					while( $account = $am->getNextAccount() ) {					
//						//set filter: get all transaction with this category
//			 			$filter = array (
//							array (
//								'key' => 'categoryId',
//								'op' => 'eq',
//								'val' => $ID
//							)
//						);
//						$account->setFilter($filter);
//						$account2 = clone $account;
//						//flush category
//						while($ta = $account->getNextTransaction()) {
//							$ta->setCategory(NULL);
//						}
//					} //accounts
					
					//delete category
					$cm->deleteCategory($ID);
				}

				exit();
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
eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");
	
function printFrontend() {
	global $tpl;
	global $cm;
	global $order;
	global $redirectPageAfterSave;
	
	if (isset($_GET['ID'])) {
		//edit: load values for this ID
		$ID = getGPC($_GET, 'ID', 'integer');
		$pageTitle = getBadgerTranslation2 ('accountCategory','pageTitleEdit');
		$category = $cm->getCategoryById($ID);
		$titleValue = $category->getTitle();
		$descriptionValue = $category->getDescription();
		if($category->getOutsideCapital() == "1"){
			$outsideCapitalValue = "checked";
		}else{
			$outsideCapitalValue = "";
		};
		$keywordsValue = $category->getKeywords();
		if($category->getParent()) {
			$parentValue = $category->getParent()->getTitle();
			$parentId = $category->getParent()->getId();
		} else {
			$parentValue = "";
			$parentId = "";
		}
		$expenseValue = $category->getExpense();
	} else {
		//new: empty values
		$pageTitle = getBadgerTranslation2 ('accountCategory','pageTitleNew');
		$ID = "new";
		$titleValue = "";
		$descriptionValue = "";
		$outsideCapitalValue = "";
		$keywordsValue = "";
		$parentValue = "";
		$parentId = "";
		$expenseValue = null;
	}
	
	$widgets = new WidgetEngine($tpl);
	$widgets->addToolTipJS();	
	$widgets->addJSValMessages();
	$tpl->addJavaScript("js/prototype.js");
	$tpl->addOnLoadEvent("Form.focusFirstElement('mainform')");
	$widgets->addNavigationHead();
	echo $tpl->getHeader($pageTitle);
	echo $widgets->addToolTipLayer();
	
	
	//set vars with values
	$FormAction = $_SERVER['PHP_SELF'];
	$legend = getBadgerTranslation2('accountCategory', 'legend');
	$hiddenID = $widgets->createField("hiddenID", 20, $ID, "", false, "hidden");
	$pageHeading = $pageTitle;
	
	//Fields & Labels
	$titleLabel = $widgets->createLabel("title", getBadgerTranslation2('accountCategory', 'title'), true);
	$titleField = $widgets->createField("title", 30, $titleValue, "", true, "text", "style='width: 30ex;'");
	
	$descriptionLabel = $widgets->createLabel("description", getBadgerTranslation2('accountCategory', 'description'), false);
	$descriptionField = $widgets->createTextarea("description", $descriptionValue, "", false, "style='width: 30ex; height: 5em;'");
	
	$parentLabel = $widgets->createLabel("parent", getBadgerTranslation2('accountCategory', 'parent'), false);
	$parentCats = array(""=>getBadgerTranslation2('CategoryManager','no_parent'));
	while ($cat = $cm->getNextCategory()) {
		$cat->getParent();
	}
	
	$cm->resetCategories();
	
	while ($cat = $cm->getNextCategory()) {
		if(is_null($cat->getParent())){
			$parentCats[$cat->getId()] = $cat->getTitle();
		};
	};
	$parentField = $widgets->createSelectField("parent", $parentCats, $parentId, '', false, "style='width: 31ex;'");
	
	$outsideCapitalLabel = $widgets->createLabel("outsideCapital", getBadgerTranslation2('accountCategory', 'outsideCapital'), false);
	$outsideCapitalField = $widgets->createField("outsideCapital", 30,"on", "", false, "checkbox", $outsideCapitalValue);
	
	$keywordsLabel = $widgets->createLabel('keywords', getBadgerTranslation2('accountCategory', 'keywordsLabel'), false);
	$keywordsField = $widgets->createTextarea('keywords', $keywordsValue, getBadgerTranslation2('accountCategory', 'keywordsDescription'), false, "style='width: 30ex; height: 5em;'");
	
	$expenseRowLabel = getBadgerTranslation2('accountCategory', 'expenseRowLabel');
	$incomeField = $widgets->createField('expense', 0, 'income', '', false, 'radio', ($expenseValue === false ? 'checked="checked"' : ''));
	$incomeLabel = $widgets->createLabel('expense', getBadgerTranslation2('accountCategory', 'expenseIncome'), false);
	$expenseField = $widgets->createField('expense', 0, 'expense', '', false, 'radio', ($expenseValue === true ? 'checked="checked"' : ''));
	$expenseLabel = $widgets->createLabel('expense', getBadgerTranslation2('accountCategory', 'expenseExpense'), false);
	
	//Buttons
	$submitBtn = $widgets->createButton("submitBtn", getBadgerTranslation2('dataGrid', 'save'), "submit", "Widgets/accept.gif", "accesskey='s'");
	$backBtn = $widgets->createButton("backBtn", getBadgerTranslation2('dataGrid', 'back'), "location.href='$redirectPageAfterSave';return false;", "Widgets/back.gif");

	//add vars to template, print site
	eval("echo \"".$tpl->getTemplate("Account/Category")."\";");
}


function updateRecord() {
	global $redirectPageAfterSave;
	global $cm;
	
	if (isset($_POST['expense'])) {
		if (getGPC($_POST, 'expense') === 'expense') {
			$expense = true;
		} else {
			$expense = false;
		}
	} else {
		$expense = null;
	}
		
	switch (getGPC($_POST, 'hiddenID')) {
	case 'new':
		//add new record
		//check if $_POST['symbol'], $_POST['longName'] is set?????
		
		$Cat = $cm->addCategory(getGPC($_POST, 'title'), getGPC($_POST, 'description'), getGPC($_POST, 'outsideCapital', 'checkbox'), getGPC($_POST, 'keywords'), $expense);
		if(isset($_POST['parent']) && getGPC($_POST, 'parent') != ""){
			$Cat->setParent($cm->getCategoryById(getGPC($_POST, 'parent', 'integer')));
		};
		break;
	default:
		//update record
		$Cat = $cm->getCategoryById(getGPC($_POST, 'hiddenID', 'integer'));
		$Cat->setTitle(getGPC($_POST, 'title'));
		$Cat->setDescription(getGPC($_POST, 'description'));
		
		$Cat->setOutsideCapital(getGPC($_POST, 'outsideCapital', 'checkbox'));
		
		$Cat->setKeywords(getGPC($_POST, 'keywords'));
		
		$Cat->setExpense($expense);
		
		if(isset($_POST['parent']) && getGPC($_POST, 'parent') != ""){
			$Cat->setParent($cm->getCategoryById(getGPC($_POST, 'parent', 'integer')));
		};//elseif(isset($_POST['parent']) && getGPC($_POST, 'parent') == ""){
			//$Cat->setParent(null);
		//};
		
	}
	//REDIRECT
	header("Location: $redirectPageAfterSave");

}