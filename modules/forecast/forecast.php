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
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';
require_once BADGER_ROOT . '/modules/account/accountCommon.php';

$pageHeading = getBadgerTranslation2('forecast','title');
$widgets = new WidgetEngine($tpl); 
$widgets->addToolTipJS();
$widgets->addCalendarJS();
$widgets->addJSValMessages();
$tpl->addJavaScript("js/prototype.js");
$tpl->addJavaScript("js/forecast.js");

$widgets->addNavigationHead();
echo $tpl->getHeader($pageHeading);
echo $widgets->addToolTipLayer();

//Settings formular
//help funktions for automatical calculation of pocket money from the finished transactions
$standardStartDate = new Date();
$standardStartDate->subtractSeconds(60*60*24*180);
$calculatePocketMoneyStartDateField = $widgets->addDateField("startDate", $standardStartDate->getFormatted());
$writeCalcuatedPocketMoneyButton = $widgets->createButton("writePocketMoney", getBadgerTranslation2("forecast", "calculatedPocketMoneyButton"), 'calcPocketMoney2();', "Widgets/accept.gif");
$calculatedPocketMoneyLabel = getBadgerTranslation2("forecast", "calculatedPocketMoneyLabel"). ":";
$writeCalculatedToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "calculatedPocketMoneyToolTip"));

//field for selecting end date of forecasting
$legendSetting = getBadgerTranslation2("forecast", "legendSetting");
$legendGraphs = getBadgerTranslation2("forecast", "legendGraphs");
$endDateLabel =  getBadgerTranslation2("forecast", "endDateField"). ":";
$standardEndDate = new Date();
$standardEndDate->addSeconds(60*60*24*180);
$endDateField = $widgets->addDateField("endDate",$standardEndDate->getFormatted());
$endDateToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "endDateToolTip"));
//get accounts from db & field to select the account for forecsatung
	$am = new AccountManager($badgerDb);
	$account = array();
		    	while ($currentAccount = $am->getNextAccount()) {
		    		$account[$currentAccount->getId()] = $currentAccount->getTitle();	
	}
//Drop down to select account		
$accountLabel =  $widgets->createLabel("selectedAccount", getBadgerTranslation2("forecast", "accountField").":", true);
$accountField = $widgets->createSelectField("selectedAccount", $account, $us->getProperty('forecastStandardAccount'), getBadgerTranslation2("forecast", "accountToolTip"), true, 'style="width: 10em;"');
//field to select saving target, default is 0
$savingTargetLabel =  $widgets->createLabel("savingTarget", getBadgerTranslation2("forecast", "savingTargetField").":", true);
$savingTargetField = $widgets->createField("savingTarget", 5, 0, getBadgerTranslation2("forecast", "savingTargetToolTip"), true, "text", 'style="width: 10em;"');
//field to insert pocketmoney1
$pocketMoney1Label =  $widgets->createLabel("pocketmoney1", getBadgerTranslation2("forecast", "pocketMoney1Field").":", true);
$pocketMoney1Field = $widgets->createField("pocketmoney1", 5, 0, getBadgerTranslation2("forecast", "pocketMoney1ToolTip"), true, "text", 'style="width: 10em;"');
//field to insert pocketmoney2
$pocketMoney2Label =  $widgets->createLabel("pocketmoney2", getBadgerTranslation2("forecast", "pocketMoney2Field").":", true);
$pocketMoney2Field = $widgets->createField("pocketmoney2", 5, 0, getBadgerTranslation2("forecast", "pocketMoney2ToolTip"), true, "text", 'style="width: 10em;"');
//checkbox for lower limit graph
$lowerLimitLabel =  getBadgerTranslation2("forecast", "lowerLimitLabel").":";
$lowerLimitBox = "<input type=\"checkbox\" id=\"lowerLimitBox\" name=\"lowerLimitBox\" value=\"select\" checked=\"checked\"/>";
$lowerLimitToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "lowerLimitToolTip"));
//checkbox for upper limit graph
$upperLimitLabel =  getBadgerTranslation2("forecast", "upperLimitLabel").":";
$upperLimitBox = "<input type=\"checkbox\" id=\"upperLimitBox\" name=\"upperLimitBox\" value=\"select\" checked=\"checked\"/>";
$upperLimitToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "upperLimitToolTip"));
//checkbox for planned transactions graph
$plannedTransactionsLabel =  getBadgerTranslation2("forecast", "plannedTransactionsLabel").":";
$plannedTransactionsBox = "<input type=\"checkbox\" id=\"plannedTransactionsBox\" name=\"plannedTransactionsBox\" value=\"select\" checked=\"checked\"/>";
$plannedTransactionsToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "plannedTransactionsToolTip"));
//checkbox for saving target graph
$savingTargetLabel1 =  getBadgerTranslation2("forecast", "savingTargetLabel").":";
$savingTargetBox = "<input type=\"checkbox\" id=\"savingTargetBox\" name=\"savingTargetBox\" value=\"select\" checked=\"checked\"/>";
$savingTargetToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "showSavingTargetToolTip"));
//checkbox for pocket money1 graph
$pocketMoney1Label1 =  getBadgerTranslation2("forecast", "pocketMoney1Label").":";
$pocketMoney1Box = "<input type=\"checkbox\" id=\"pocketMoney1Box\" name=\"pocketMoney1Box\" value=\"select\" checked=\"checked\"/>";
$pocketMoney1ToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "showPocketMoney1ToolTip"));
//checkbox for pocket money1 graph
$pocketMoney2Label1 =  getBadgerTranslation2("forecast", "pocketMoney2Label").":";
$pocketMoney2Box = "<input type=\"checkbox\" id=\"pocketMoney2Box\" name=\"pocketMoney2Box\" value=\"select\" checked=\"checked\"/>";
$pocketMoneyTool2Tip = $widgets->addToolTip(getBadgerTranslation2("forecast", "showPocketMoney2ToolTip"));
//Create Chart Button
$tooLongTimeSpanWarning = getBadgerTranslation2("forecast", "performanceWarning");
$sendButton = $widgets->createButton("sendData", getBadgerTranslation2("forecast", "sendData"), 'submitForecast();', "Widgets/accept.gif");

$dailyPocketMoneyLabel = getBadgerTranslation2("forecast", "dailyPocketMoneyLabel").":";
$dailyPocketMoneyToolTip = $widgets->addToolTip(getBadgerTranslation2("forecast", "dailyPocketMoneyToolTip")) . "<br />";

$balancedEndDateLabel1 = getBadgerTranslation2("forecast", "printedPocketMoney1Label"). ": ";

$balancedEndDateLabel2 = getBadgerTranslation2("forecast", "printedPocketMoney2Label"). ": ";

eval("echo \"".$tpl->getTemplate("forecast/forecast")."\";");


//show chart
eval("echo \"".$tpl->getTemplate("badgerFooter")."\";");

?>