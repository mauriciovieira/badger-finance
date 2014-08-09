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
define ('BADGER_ROOT', '../..');

require_once BADGER_ROOT . '/includes/fileHeaderFrontEnd.inc.php';
require_once BADGER_ROOT . '/modules/account/accountCommon.php';
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';
require_once BADGER_ROOT . '/includes/charts/charts.php';
require_once BADGER_ROOT . '/core/Date/Span.php';
require_once BADGER_ROOT . '/core/widgets/DataGrid.class.php';

updateBalances();

if (isset($_GET['mode'])) {
	$mode = getGPC($_GET, 'mode');
} else if (isset($_POST['mode'])) {
	$mode = getGPC($_POST, 'mode');
} else {
	$mode = 'selectPage';
}

switch ($mode) {
	case 'selectPage':
	default:
		showSelectPage();
		break;

	case 'trendData':
		showTrendData();
		break;

	case 'categoryData':
		showCategoryData();
		break;
}

function showSelectPage() {
	global $tpl;
	global $us;
	global $badgerDb;
	
	$widgets = new WidgetEngine($tpl); 

	$widgets->addCalendarJS();
	$widgets->addToolTipJS();
	$tpl->addJavaScript("js/behaviour.js");
	$tpl->addJavaScript("js/prototype.js");
	$tpl->addJavaScript("js/statistics.js");
	
	$tpl->addHeaderTag('<script type="text/javascript">var badgerHelpChapter = "Statistiken";</script>');
		
	$dataGrid = new DataGrid($tpl, "AccountManagerStatistic");
	$dataGrid->sourceXML = BADGER_ROOT."/core/XML/getDataGridXML.php?q=AccountManager";
	$dataGrid->headerName = array(
		getBadgerTranslation2('statistics','accColTitle'), 
		getBadgerTranslation2('statistics','accColBalance'), 
		getBadgerTranslation2('statistics','accColCurrency'));
	$dataGrid->columnOrder = array("title", "balance", "currency");  
	$dataGrid->headerSize = array(160, 100, 75);
	$dataGrid->cellAlign = array("left", "right", "left");
	$dataGrid->width = '30em';
	$dataGrid->height = '7em';
	$dataGrid->discardSelectedRows = false;
	$dataGrid->initDataGridJS();
	
//	try {
//		$preselectedAccounts = $us->getProperty('statisticsPreselectedAccounts');
//		foreach ($preselectedAccounts as $currentPreselectedAccount) {
//			$tpl->addOnLoadEvent("dataGridAccountManagerStatistic.preselectId('$currentPreselectedAccount');");
//		}
//	} catch (BadgerException $ex) {}
	
	$widgets->addNavigationHead();

	$selectTitle = getBadgerTranslation2('statistics','pageTitle');
	echo $tpl->getHeader($selectTitle);
	
	$widgets->addToolTipLayer();

	$selectFormAction = BADGER_ROOT . '/modules/statistics/statistics.php';
	
	$graphTypeText = getBadgerTranslation2('statistics','type');
	$categoryTypeText = getBadgerTranslation2('statistics','category');
	$timeFrameText = getBadgerTranslation2('statistics','period');
	$summarizeCategoriesText = getBadgerTranslation2('statistics','catMerge');
	$accountsText = getBadgerTranslation2('statistics','accounts');
	$differentCurrencyWarningText = getBadgerTranslation2('statistics','attention');
	$fromText = getBadgerTranslation2('statistics','from');
	$toText = getBadgerTranslation2('statistics','to');
	
	$trendRadio = $widgets->createField('mode', null, 'trendPage', '', false, 'radio', 'checked="checked"');
	$trendLabel = $widgets->createLabel('mode', getBadgerTranslation2('statistics', 'trend'));
	
	$categoryRadio = $widgets->createField('mode', null, 'categoryPage', '', false, 'radio');
	$categoryLabel = $widgets->createLabel('mode', getBadgerTranslation2('statistics', 'categories'));

	$accountSelect = $dataGrid->writeDataGrid();
	$accountField = $widgets->createField('accounts', null, null, '', false, 'hidden');

	$monthArray = array (
		'fullYear' => getBadgerTranslation2('statistics', 'fullYear'),
		'1' => getBadgerTranslation2('statistics','jan'),
		'2' => getBadgerTranslation2('statistics','feb'),
		'3' => getBadgerTranslation2('statistics','mar'),
		'4' => getBadgerTranslation2('statistics','apr'),
		'5' => getBadgerTranslation2('statistics','may'),
		'6' => getBadgerTranslation2('statistics','jun'),
		'7' => getBadgerTranslation2('statistics','jul'),
		'8' => getBadgerTranslation2('statistics','aug'),
		'9' => getBadgerTranslation2('statistics','sep'),
		'10' => getBadgerTranslation2('statistics','oct'),
		'11' => getBadgerTranslation2('statistics','nov'),
		'12' => getBadgerTranslation2('statistics','dec'),
	);
	$monthSelect = $widgets->createSelectField('monthSelect', $monthArray, 'fullYear', '', false, 'onchange="updateDateRange();"');

	$now = new Date();
	$beginOfYear = new Date();
	$beginOfYear->setMonth(1);
	$beginOfYear->setDay(1);
	
	$yearInput = $widgets->createField('yearSelect', 4, $now->getYear(),'', false, 'text', 'onchange="updateDateRange();"');
	
	$startDateField = $widgets->addDateField("startDate", $beginOfYear->getFormatted());
	$endDateField = $widgets->addDateField("endDate", $now->getFormatted());
	
	$inputRadio = $widgets->createField('type', null, 'i', '', false, 'radio', 'checked="checked"');
	$inputLabel = $widgets->createLabel('type', getBadgerTranslation2('statistics','income'));
	
	$outputRadio = $widgets->createField('type', null, 'o', '', false, 'radio');
	$outputLabel = $widgets->createLabel('type', getBadgerTranslation2('statistics','expenses'));

	$summarizeRadio = $widgets->createField('summarize', null, 't', '', false, 'radio', 'checked="checked"');
	$summarizeLabel = $widgets->createLabel('summarize', getBadgerTranslation2('statistics','subCat'));

	$distinguishRadio = $widgets->createField('summarize', null, 'f', '', false, 'radio');
	$distinguishLabel = $widgets->createLabel('summarize', getBadgerTranslation2('statistics','subCat2'));

	$dateFormatField = $widgets->createField('dateFormat', null, $us->getProperty('badgerDateFormat'), null, false, 'hidden');
	$errorMsgAccountMissingField = $widgets->createField('errorMsgAccountMissing', null, getBadgerTranslation2('statistics','errorMissingAcc'), null, false, 'hidden');
	$errorMsgStartBeforeEndField = $widgets->createField('errorMsgStartBeforeEnd', null, getBadgerTranslation2('statistics','errorDate'), null, false, 'hidden');
	$errorMsgEndInFutureField = $widgets->createField('errorMsgEndInFuture', null, getBadgerTranslation2('statistics','errorEndDate'), null, false, 'hidden');

	$submitButton = $widgets->createButton('submit', getBadgerTranslation2('statistics', 'showButton'), 'submitSelect();', "Widgets/accept.gif");

	eval('echo "' . $tpl->getTemplate('statistics/select') . '";');
	eval('echo "' . $tpl->getTemplate('badgerFooter') . '";');
}

function showTrendData() {
	global $badgerDb;
	global $logger;
	
	$logger->log('statistics::showTrendData: REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

	if (!isset($_GET['accounts']) || !isset($_GET['startDate']) || !isset($_GET['endDate'])) {
		throw new BadgerException('statistics', 'missingParameter');
	}
	
	updatePreselection();

	$accountIds = getGPC($_GET, 'accounts', 'integerList');
	$startDate = getGPC($_GET, 'startDate', 'Date');
	$endDate = getGPC($_GET, 'endDate', 'Date');
	
	$now = new Date();
	$now->setHour(0);
	$now->setMinute(0);
	$now->setSecond(0);
	
	if ($endDate->after($now)) {
		$endDate = $now;
	}

	$accountManager = new AccountManager($badgerDb);

	$totals = array();
	$accounts = array();

	$currentAccountIndex = 0;

	foreach($accountIds as $currentAccountId) {
		$currentAccount = $accountManager->getAccountById($currentAccountId);
		
		$accounts[$currentAccountIndex][0] = $currentAccount->getTitle();
		
		$currentBalances = getDailyAmount($currentAccount, $startDate, $endDate);
		
		foreach ($currentBalances as $balanceKey => $balanceVal) {
			if (isset($totals[$balanceKey])) {
				$totals[$balanceKey]->add($balanceVal);
			} else {
				$totals[$balanceKey] = $balanceVal;
			}
			
			$accounts[$currentAccountIndex][] = $balanceVal->get();
		}
		
		$currentAccountIndex++;
	}
	
	$numDates = count($totals);
	
	$chart = array ();
	//for documentation for the following code see: http://www.maani.us/charts/index.php?menu=Reference
	$chart [ 'chart_type' ] = "line";
	$chart [ 'axis_category' ] = array (   'skip'         =>  $numDates / 12,
	                                       'font'         =>  "Arial", 
	                                       'bold'         =>  false, 
	                                       'size'         =>  10, 
	                                       'color'        =>  "000000", 
	                                       'alpha'        =>  100,
	                                       'orientation'  =>  "horizontal"
	                                   ); 
	$chart [ 'axis_ticks' ] = array (   'value_ticks'      =>  true, 
	                                    'category_ticks'   =>  true, 
	                                    'position'         =>  "centered", 
	                                    'major_thickness'  =>  2, 
	                                    'major_color'      =>  "000000", 
	                                    'minor_thickness'  =>  1, 
	                                    'minor_color'      =>  "000000",
	                                    'minor_count'      =>  4
	                                ); 
	
	$chart [ 'axis_value' ] = array (   'min'           =>  0, //automatically adjusted  
	                                    'max'           =>  0, //automatically adjusted
	                                    'steps'         =>  10,  
	                                    'prefix'        =>  "", 
	                                    'suffix'        =>  "", 
	                                    'decimals'      =>  0,
	                                    'decimal_char'  =>  ".",
	                                    'separator'     =>  "", 
	                                    'show_min'      =>  true, 
	                                    'font'          =>  "Arial", 
	                                    'bold'          =>  false, 
	                                    'size'          =>  10, 
	                                    'color'         =>  "000000", 
	                                    'alpha'         =>  75,
	                                    'orientation'   =>  "horizontal"
	                                   );
	
	$chart [ 'chart_border' ] = array (   'top_thickness'     =>  1,
	                                      'bottom_thickness'  =>  1,
	                                      'left_thickness'    =>  1,
	                                      'right_thickness'   =>  1,
	                                      'color'             =>  "000000"
	                                   );
	
	$chart [ 'chart_pref' ] = array (   'line_thickness'  =>  1,  
	                                    'point_shape'     =>  "none", 
	                                    'fill_shape'      =>  false
	                                  ); 
	
	$chart [ 'chart_grid_h' ] = array (   'thickness'  =>  1,
	                                      'color'      =>  "000000",
	                                      'alpha'      =>  15,
	                                      'type'       =>  "solid"
	                                   );
	$chart [ 'chart_grid_v' ] = array (   'thickness'  =>  1,
	                                      'color'      =>  "000000",
	                                      'alpha'      =>  5,
	                                      'type'       =>  "dashed"
	                                   );
	$chart [ 'chart_rect' ] = array ( 'x'=>50,
	                                  'y'=>50,
	                                  'width'=>700,
	                                  'height'=>300,
	                                  'positive_color'  =>  "ffffff",
	                                  'negative_color'  =>  "000000",
	                                  'positive_alpha'  =>  100,
	                                  'negative_alpha'  =>  10
	                                );
	$chart [ 'chart_value' ] = array (  'prefix'         =>  "", 
	                                    'suffix'         =>  "", 
	                                    'decimals'       =>  0,
	                                    'decimal_char'   =>  ".",  
	                                    'separator'      =>  "",
	                                    'position'       =>  "cursor",
	                                    'hide_zero'      =>  true, 
	                                    'as_percentage'  =>  false, 
	                                    'font'           =>  "Arial", 
	                                    'bold'           =>  false, 
	                                    'size'           =>  10, 
	                                    'color'          =>  "000000", 
	                                    'alpha'          =>  90
	                                  ); 
	$chart [ 'chart_transition' ] = array( 'type'      =>  "none",
	                                        'delay'     =>  1, 
	                                        'duration'  =>  1, 
	                                        'order'     =>  "all"                                 
	                                      ); 
	                               
	$chart [ 'legend_rect' ] = array (   'x'               =>  50,
	                                     'y'               =>  5, 
	                                     'width'           =>  700, 
	                                     'height'          =>  5, 
	                                     'margin'          =>  5,
	                                     'fill_color'      =>  "FFFFFF",
	                                     'fill_alpha'      =>  100, 
	                                     'line_color'      =>  "000000",
	                                     'line_alpha'      =>  100, 
	                                     'line_thickness'  =>  1
	                                 ); 
	$chart [ 'legend_label' ] = array (   'layout'  =>  "horizontal",
	                                      'bullet'  =>  "circle",
	                                      'font'    =>  "Arial", 
	                                      'bold'    =>  false, 
	                                      'size'    =>  11, 
	                                      'color'   =>  "000000", 
	                                      'alpha'   =>  90
	                                  ); 
	$chart [ 'legend_transition' ] = array ( 'type'      =>  "none",
	                                         'delay'     =>  1, 
	                                         'duration'  =>  1 
	                                       ); 
	$chart [ 'series_color' ] = array (
		"FF0000",
		"00FF00",
		"0000FF", 
		"FF8000",
		"404040",
		"800040",
/*
		'000070',
		'FFFF99',
		'007000',
		'FFCC99',
		'700070',
		'CC99FF',
		'660000',
		'9999FF',
		'006666',
		'CCFF99',
		'A35200',
		'FFCA7A'
*/
	);                                       
	
	$chart['chart_data'] = array();
	
	$chart['chart_data'][0][0] = '';
	if (count($accounts) > 1) {
		$chart['chart_data'][1][0] = getBadgerTranslation2('statistics','trendTotal');
	} else {
		$chart['chart_data'][1][0] = utf8_encode($accounts[0][0]);
	}
	
	foreach($totals as $key => $val) {
		$tmp = new Date($key);
		$chart['chart_data'][0][] = $tmp->getFormatted();
		$chart['chart_data'][1][] = $val->get();
	}
	
	if (count($accounts) > 1) {
		foreach($accounts as $val) {
			$chart['chart_data'][] = $val;
		}
	}
	
	SendChartData($chart);
}

function showCategoryData() {
	global $badgerDb;
	global $logger;
	
	$logger->log('showCategoryData: REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
		
	if (!isset($_GET['accounts']) || !isset($_GET['startDate']) || !isset($_GET['endDate']) || !isset($_GET['type']) || !isset($_GET['summarize'])) {
		throw new BadgerException('statistics', 'missingParameter');
	}
	
	updatePreselection();

	$accounts = getGPC($_GET, 'accounts', 'integerList');
	$startDate = getGPC($_GET, 'startDate', 'Date');
	$endDate = getGPC($_GET, 'endDate', 'Date');
	
	$type = getGPC($_GET, 'type');
	if ($type !== 'o') {
		$type = 'i';
	}

	if (getGPC($_GET, 'summarize') !== 't') {
		$summarize = false;
	} else {
		$summarize = true;
	}
	
	$categories = gatherCategories($accounts, $startDate, $endDate, $type, $summarize);

	$sum = new Amount();
	foreach ($categories as $currentCategory) {
		$sum->add($currentCategory['amount']);
	}

	$chart = array();
	
	//for documentation for the following code see: http://www.maani.us/charts/index.php?menu=Reference
	$chart['chart_type'] = '3d pie';
	$chart [ 'axis_category' ] = array (   'skip'         =>  0,
	                                       'font'         =>  "Arial", 
	                                       'bold'         =>  false, 
	                                       'size'         =>  10, 
	                                       'color'        =>  "000000", 
	                                       'alpha'        =>  100,
	                                       'orientation'  =>  "horizontal"
	                                   ); 
	
	$chart [ 'chart_rect' ] = array ( 'x'=>150,
	                                  'y'=>50,
	                                  'width'=>500,
	                                  'height'=>250,
	                                  'positive_color'  =>  "ffffff",
	                                  'negative_color'  =>  "000000",
	                                  'positive_alpha'  =>  0,
	                                  'negative_alpha'  =>  0
	                                );
	$chart [ 'chart_value' ] = array (  'prefix'         =>  "", 
	                                    'suffix'         =>  "", 
	                                    'decimals'       =>  0,
	                                    'decimal_char'   =>  ".",  
	                                    'separator'      =>  "",
	                                    'position'       =>  "outside",
	                                    'hide_zero'      =>  true, 
	                                    'as_percentage'  =>  false, 
	                                    'font'           =>  "Arial", 
	                                    'bold'           =>  false, 
	                                    'size'           =>  10, 
	                                    'color'          =>  "000000", 
	                                    'alpha'          =>  90
	                                  ); 
	$chart [ 'chart_transition' ] = array( 'type'      =>  "none",
	                                        'delay'     =>  1, 
	                                        'duration'  =>  1, 
	                                        'order'     =>  "all"                                 
	                                      ); 
	                               
	$chart [ 'legend_rect' ] = array (   'x'               =>  700,
	                                     'y'               =>  400, 
	                                     'width'           =>  0, 
	                                     'height'          =>  0, 
	                                     'margin'          =>  5,
	                                     'fill_color'      =>  "FFFFFF",
	                                     'fill_alpha'      =>  100, 
	                                     'line_color'      =>  "000000",
	                                     'line_alpha'      =>  100, 
	                                     'line_thickness'  =>  1
	                                 ); 
	$chart [ 'legend_label' ] = array (   'layout'  =>  "vertical",
	                                      'bullet'  =>  "circle",
	                                      'font'    =>  "Arial", 
	                                      'bold'    =>  false, 
	                                      'size'    =>  11, 
	                                      'color'   =>  "000000", 
	                                      'alpha'   =>  90
	                                  ); 
	$chart [ 'legend_transition' ] = array ( 'type'      =>  "none",
	                                         'delay'     =>  1, 
	                                         'duration'  =>  1 
	                                       ); 
	$chart [ 'series_color' ] = array (
		'000070',
		'FFFF99',
		'007000',
		'FFCC99',
		'700070',
		'CC99FF',
		'660000',
		'9999FF',
		'006666',
		'CCFF99',
		'A35200',
		'FFCA7A'
	);                                       
	
	$chart['chart_data'] = array();
	$chart['chart_value_text'] = array();
	
	$chart['chart_data'][0][0] = '';
	$chart['chart_data'][1][0] = '';
	$chart['chart_value_text'][0][0] = '';
	$chart['chart_value_text'][1][0] = '';
	
	foreach($categories as $key => $val) {
		$chart['chart_data'][0][] = utf8_encode($val['title']);
		$chart['chart_value_text'][0][] = null;
		if ($type == 'i') {
			$chart['chart_data'][1][] = $val['amount']->get();
		} else {
			$chart['chart_data'][1][] = $val['amount']->mul(-1)->get();
		}
		$chart['chart_value_text'][1][] = utf8_encode($val['title'] . "\n" . $val['amount']->getFormatted() . "\n" . round($val['amount']->div($sum)->mul($type == 'i' ? 100 : -100)->get(), 2) . ' %'); 
	}
	
	SendChartData($chart);
}

function gatherCategories($accountIds, $startDate, $endDate, $type, $summarize) {
	global $badgerDb;

	$accountManager = new AccountManager($badgerDb);

	$categories = array(
		'none' => array (
			'title' => getBadgerTranslation2('statistics', 'noCategoryAssigned'),
			'count' => 0,
			'amount' => new Amount(0)
		)
	);

	foreach($accountIds as $currentAccountId) {
		$currentAccount = $accountManager->getAccountById($currentAccountId);
		
		//echo 'Account: ' . $currentAccount->getTitle() . '<br />';

		$currentAccount->setFilter(array (
			array (
				'key' => 'valutaDate',
				'op' => 'ge',
				'val' => $startDate
			),
			array (
				'key' => 'valutaDate',
				'op' => 'le',
				'val' => $endDate
			)
		));

		while ($currentTransaction = $currentAccount->getNextFinishedTransaction()) {
			if ($type == 'i') {
				if ($currentTransaction->getAmount()->compare(0) < 0) {
					continue;
				}
			} else {
				if ($currentTransaction->getAmount()->compare(0) > 0) {
					continue;
				}
			}
			
			if (!is_null($category = $currentTransaction->getCategory())) {
				if ($summarize && $category->getParent()) {
					$category = $category->getParent();
				}

				if (isset($categories[$category->getId()])) {
					$categories[$category->getId()]['count']++;
					$categories[$category->getId()]['amount']->add($currentTransaction->getAmount());
				} else {
					$categories[$category->getId()] = array (
						'title' => $category->getTitle(),
						'count' => 1,
						'amount' => $currentTransaction->getAmount()
					);
				}
			} else {
				$categories['none']['count']++;
				$categories['none']['amount']->add($currentTransaction->getAmount());
			}
		}
	}
	
	//uasort($categories, 'compareCategories');

	if ($categories['none']['count'] == 0) {
		unset($categories['none']);
	}
	
	return $categories;
}

function compareCategories($a, $b) {
	return $a['amount']->compare($b['amount']);
}

function updatePreselection() {
	global $us;
	
	$accountIds = getGPC($_GET, 'accounts', 'integerList');
	
	$us->setProperty('statisticsPreselectedAccounts', $accountIds);
}
?>