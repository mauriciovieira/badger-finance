<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Financial Management
* Visit http://badger.berlios.org 
*
**/
define('BADGER_ROOT', '../..'); 
require_once BADGER_ROOT . '/includes/fileHeaderBackEnd.inc.php';
require_once BADGER_ROOT . '/modules/account/accountCommon.php';

//include charts.php to access the InsertChart function
require_once(BADGER_ROOT . "/includes/charts/charts.php");


header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";

# validate if date is in future
$selectedDate = getGPC($_POST, 'endDate', 'DateFormatted');
$today = new Date();
$noFutureDates = NULL;
$noLowerLimit = NULL;
$noUpperLimit = NULL;
$noGraphChosen = NULL;
$insertChart = NULL;
//to avoid a date in the past or the same date as today
if ($today->compare($today, $selectedDate) != 1){
	$selectedSavingTarget = getGPC($_POST, 'savingTarget', 'AmountFormatted');
	$savingTarget = $selectedSavingTarget->get();	
	$endDate = $selectedDate->getDate();
	$account = getGPC($_POST, 'selectedAccount', 'int');
	//save selected account as standard account
	$us->setProperty('forecastStandardAccount',$account);
	
	$selectedPocketMoney1 = getGPC($_POST, 'pocketmoney1', 'AmountFormatted');
	$pocketMoney1 = $selectedPocketMoney1->get();		
	$viewPocketMoney1 = $selectedPocketMoney1->getFormatted();
	$selectedPocketMoney2 = getGPC($_POST, 'pocketmoney2', 'AmountFormatted');
	$pocketMoney2 = $selectedPocketMoney2->get();	
	$viewPocketMoney2 = $selectedPocketMoney2->getFormatted();
	$dailyPocketMoneyLabel = NULL;
	$dailyPocketMoneyValue = NULL;
	$dailyPocketMoneyToolTip = NULL;
	$balancedEndDate2 = NULL;
	$balancedEndDateLabel2 = NULL;
	$printedPocketMoney2EndValue = NULL;
	$balancedEndDate1 = NULL;
	$balancedEndDateLabel1 = NULL;
	$printedPocketMoney1EndValue = NULL;
	
	$errors = array();

	$am1 = new AccountManager($badgerDb);
	$currentAccount1 = $am1->getAccountById($account);
	//if no graph was chosen
	if (
		!getGPC($_POST, 'lowerLimitBox', 'checkbox')
		&& !getGPC($_POST, 'upperLimitBox', 'checkbox')
		&& !getGPC($_POST, 'plannedTransactionsBox', 'checkbox')
		&& !getGPC($_POST, 'savingTargetBox', 'checkbox')
		&& !getGPC($_POST, 'pocketMoney1Box', 'checkbox')
		&& !getGPC($_POST, 'pocketMoney2Box', 'checkbox')
	) {
		$noGraphChosen = getBadgerTranslation2("forecast", "noGraphchosen");
		echo "<errors><error>$noGraphChosen</error></errors>";
	} else {
		if (getGPC($_POST, 'lowerLimitBox', 'checkbox')) {
			if (!is_null($currentAccount1->getLowerLimit()->get())){
				$showLowerLimit = 1;
			} else {
				$showLowerLimit = 0;
				$errors[] = getBadgerTranslation2("forecast", "noLowerLimit");
			}
		} else {
			$showLowerLimit = 0;
		}
		if (getGPC($_POST, 'upperLimitBox', 'checkbox')) {
			if (!is_null($currentAccount1->getUpperLimit()->get())){
				$showUpperLimit = 1;
			} else {
				$showUpperLimit = 0;
				$errors[] = getBadgerTranslation2("forecast", "noUpperLimit");
			}
		} else {
			$showUpperLimit = 0;
		}
		if (getGPC($_POST, 'plannedTransactionsBox', 'checkbox')) {
			$showPlannedTransactions = 1;
		} else {
			$showPlannedTransactions = 0;
		}
		if (getGPC($_POST, 'savingTargetBox', 'checkbox')) {
			$showSavingTarget = 1;
		} else {
			$showSavingTarget = 0;
		}
		if (getGPC($_POST, 'pocketMoney1Box', 'checkbox')) {
			$showPocketMoney1 = 1;
		} else {
			$showPocketMoney1 = 0;
		}
		if (getGPC($_POST, 'pocketMoney2Box', 'checkbox')) {
			$showPocketMoney2 = 1;
		} else {
			$showPocketMoney2 = 0;
		}
		
		if (count($errors) != 0) {
			echo '<errors>';
			foreach ($errors as $error) {
				echo "<error>$error</error>";
			}
			echo '</errors>';
		}
		//create the chart
		$insertChart = InsertChart ( BADGER_ROOT . "/includes/charts/charts.swf", BADGER_ROOT . "/includes/charts/charts_library", BADGER_ROOT . "/modules/forecast/forecastChart.php?endDate=$endDate&account=$account&savingTarget=$savingTarget&pocketMoney1=$pocketMoney1&pocketMoney2=$pocketMoney2&showLowerLimit=$showLowerLimit&showUpperLimit=$showUpperLimit&showPlannedTransactions=$showPlannedTransactions&showSavingTarget=$showSavingTarget&showPocketMoney1=$showPocketMoney1&showPocketMoney2=$showPocketMoney2", 800, 400, "ECE9D8", true);
		
		$am = new AccountManager($badgerDb);
		$totals = array();
		$currentAccount = $am->getAccountById($account);
		$startDate = new Date ();
		$currentBalances = getDailyAmount($currentAccount, $startDate, $selectedDate);
		$accountCurrency = $currentAccount->getCurrency()->getSymbol();
		foreach ($currentBalances as $balanceKey => $balanceVal) {
			if (isset($totals[$balanceKey])) {
				$totals[$balanceKey]->add($balanceVal);
			} else {
				$totals[$balanceKey] = $balanceVal;
			}
		}
		//calculate spending money, if saving target should be reached
		$countDay = count($totals)-1; //get numbers of days between today & endDate
		$laststanding = new Amount($totals[$selectedDate->getDate()]);
		$endDateBalance = $laststanding; //get balance of end date
		$freeMoney = new Amount($endDateBalance->sub($savingTarget)); //endDateBalance - saving target = free money to spend
		$dailyPocketMoney = new Amount ($freeMoney->div($countDay)); //calculate daily pocket money = free money / count of Days

		$day = 0;
		$pocketMoney1EndValue = "";
		$pocketMoney2EndValue = "";
		foreach($totals as $key => $val) {
			$tmp = new Date($key);
			
			$PocketMoney1Loop = new Amount ($pocketMoney1);
			$val2 = new Amount ($val->get());
			if ($showPocketMoney1 ==1){
				$pocketMoney1EndValue = $val2->sub($PocketMoney1Loop->mul($day))->get();
			}
			$PocketMoney2Loop = new Amount ($pocketMoney2);
			$val3 = new Amount ($val->get());
			if ($showPocketMoney2 == 1){
				$pocketMoney2EndValue = $val3->sub($PocketMoney2Loop->mul($day))->get();
			}
			$day++;
		} //foreach($totals as $key => $val) {

		echo '<forecastData>';
		$insertChart = urlencode($insertChart);
		echo "<insertChart>$insertChart</insertChart>";
		if ($showSavingTarget == 1){
			$dailyPocketMoneyValue = $dailyPocketMoney->getFormatted();
			echo "<dailyPocketMoneyValue>$dailyPocketMoneyValue</dailyPocketMoneyValue>";
		}
		if ($pocketMoney1EndValue){
			$printedPocketMoney1EndValue = new Amount ($pocketMoney1EndValue);
			$balancedEndDate1 = $printedPocketMoney1EndValue->getFormatted();
			echo "<balancedEndDate1>$balancedEndDate1</balancedEndDate1>";
		}
		if ($pocketMoney2EndValue){
			$printedPocketMoney2EndValue = new Amount ($pocketMoney2EndValue);
			$balancedEndDate2 = $printedPocketMoney2EndValue->getFormatted();
			echo "<balancedEndDate2>$balancedEndDate2</balancedEndDate2>";
		}
		echo "<accountCurrency>$accountCurrency</accountCurrency>";
		echo '</forecastData>';
	}
} else { //if ($today->compare($today, $selectedDate)!=1){
	$noFutureDates = getBadgerTranslation2("forecast", "onlyFutureDates");
	echo "<errors><error>$noFutureDates</error></errors>";
}
require_once BADGER_ROOT . "/includes/fileFooter.php";
?>