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
define ('BADGER_ROOT', '../..');

require_once BADGER_ROOT . '/includes/fileHeaderBackEnd.inc.php';
require_once BADGER_ROOT . '/core/XML/dataGridCommon.php';
require_once BADGER_ROOT . '/includes/jpGraph/src/jpgraph.php';
require_once BADGER_ROOT . '/includes/jpGraph/src/jpgraph_bar.php';
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';
require_once BADGER_ROOT . '/modules/statistics2/colors.php';

define('MAX_LABELS', 30);

$graph = new Graph(1200, 600);
$graph->SetScale('textlin');

$accountIds = getGPC($_GET, 'accounts', 'integerList');

$accountManager = new AccountManager($badgerDb);

$type = getGPC($_GET, 'type');
switch ($type) {
	case 'w':
	case 'm':
	case 'q':
	case 'y':
		break;
	
	default:
		$type = 'm';
		break;
}

if (getGPC($_GET, 'summarize') !== 't') {
	$summarize = false;
} else {
	$summarize = true;
}

$amounts = array();
$amounts['none'] = array();
$labels = array();
$labels['none'] = getBadgerTranslation2('statistics', 'noCategoryAssigned');

$beginDates = array();
$endDates = array();

$order = array (
	array (
		'key' => 'valutaDate',
		'dir' => 'asc'
	)
);

foreach($accountIds as $currentAccountId) {
	$currentAccount = $accountManager->getAccountById($currentAccountId);
	
	$filter = getDataGridFilter($currentAccount);
	$currentAccount->setFilter($filter);
	$currentAccount->setOrder($order);

	while ($currentTransaction = $currentAccount->getNextTransaction()) {
		$date = $currentTransaction->getValutaDate();

		if (is_null($date)) {
			continue;
		}


		switch ($type) {
			case 'w':
				$dateKey = $date->getYear() . '-' . sprintf('%02d', $date->getWeekOfYear());
				$beginDate = new Date(Date_Calc::beginOfWeek($date->getDay(), $date->getMonth(), $date->getYear(), '%Y-%m-%d'));
				$endDate = new Date(Date_Calc::endOfWeek($date->getDay(), $date->getMonth(), $date->getYear(), '%Y-%m-%d'));
				break;
			
			case 'm':
				$dateKey = $date->getYear() . '-' . sprintf('%02d', $date->getMonth());
				$beginDate = new Date(Date_Calc::beginOfMonth($date->getMonth(), $date->getYear(), '%Y-%m-%d'));
				$endDate = new Date(Date_Calc::endOfMonthBySpan(0, $date->getMonth(), $date->getYear(), '%Y-%m-%d'));
				break;
			
			case 'q':
				$dateKey = $date->getYear() . '-' . $date->getQuarterOfYear();
				switch ($date->getQuarterOfYear()) {
					case 1:
						$beginDate = new Date($date->getYear() . '-01-01');
						$endDate = new Date($date->getYear() . '-03-31');
						break;
					
					case 2:
						$beginDate = new Date($date->getYear() . '-04-01');
						$endDate = new Date($date->getYear() . '-06-30');
						break;
					
					case 3:
						$beginDate = new Date($date->getYear() . '-07-01');
						$endDate = new Date($date->getYear() . '-09-30');
						break;
					
					case 4:
						$beginDate = new Date($date->getYear() . '-10-01');
						$endDate = new Date($date->getYear() . '-12-31');
						break;
				}
				break;
			
			case 'y':
				$dateKey = $date->getYear() . '-';
				$beginDate = new Date($date->getYear() . '-01-01');
				$endDate = new Date($date->getYear() . '-12-31');
				break;
		}
		
		if (!isset($beginDates[$dateKey])) {
			$beginDates[$dateKey] = $beginDate;
			$endDates[$dateKey] = $endDate;
		}
		
		if (!is_null($category = $currentTransaction->getCategory())) {
			if ($summarize && $category->getParent()) {
				$category = $category->getParent();
			}
			
			$categoryKey = $category->getId();
		} else {
			$categoryKey = 'none';
		}
		
		if (!isset($labels[$categoryKey])) {
			$labels[$categoryKey] = $category->getTitle();
			$amounts[$categoryKey] = array();
		}
		
		if (isset($amounts[$categoryKey][$dateKey])) {
			$amounts[$categoryKey][$dateKey]->add($currentTransaction->getAmount());
		} else {
			$amounts[$categoryKey][$dateKey] = new Amount($currentTransaction->getAmount());
		}
	}
}

if (count($amounts['none']) == 0) {
	unset($amounts['none']);
	unset($labels['none']);
}

$allKeys = array();
foreach($amounts as $currentAmounts) {
	$allKeys = array_merge($allKeys, $currentAmounts);
}

if (count($allKeys) == 0) {
	echo getBadgerTranslation2('statistics2Graph', 'noMatchingTransactions');

	require_once BADGER_ROOT . "/includes/fileFooter.php";
	exit;
}



ksort($allKeys);

if ($type != 'y') {
	$tickLabels = array_keys($allKeys);
} else {
	$tickLabels = array();
	
	foreach ($allKeys as $key => $val) {
		$tickLabels[] = substr($key, 0, 4);
	}
}

$data = array();
$dataNames = array();
$targets = array();
$numDatas = 0;
foreach ($amounts as $currentCategoryId => $currentAmounts) {
	$data[$currentCategoryId] = array();
	$dataNames[$currentCategoryId] = array();
	$targets[$currentCategoryId] = array();
	
	foreach ($allKeys as $currentKey => $val) {
		if (isset($currentAmounts[$currentKey])) {
			$data[$currentCategoryId][] = $currentAmounts[$currentKey]->get();
			$dataNames[$currentCategoryId][] = $currentAmounts[$currentKey]->getFormatted();
		} else {
			$data[$currentCategoryId][] = 0;
			$dataNames[$currentCategoryId][] = '';
			$numDatas++;
		}
		
		if ($currentCategoryId != 'none') {
			$targets[$currentCategoryId][] = "javascript:reachThroughTimespan('" . $beginDates[$currentKey]->getFormatted() . "', '" . $endDates[$currentKey]->getFormatted() . "', '$currentCategoryId');";
		} else {
			$targets[$currentCategoryId][] = '';
		}
	}
}

$colorIndex = 0;

$bars = array();

foreach ($labels as $currentCategoryId => $currentLabel) {
	$bar = new BarPlot($data[$currentCategoryId]);
	$bar->SetFillColor($chartColors[$colorIndex % count($chartColors)]);
	$bar->SetLegend($labels[$currentCategoryId]);
	$bar->SetCSIMTargets($targets[$currentCategoryId], $dataNames[$currentCategoryId]);
	
	$bars[] = $bar;
	
	$colorIndex++;
}

$groupBar = new GroupBarPlot($bars);

$graph->add($groupBar);

$graph->xaxis->SetTickLabels($tickLabels);
$graph->xaxis->SetFont(FF_VERA, FS_BOLD);
$graph->xaxis->SetColor('black', 'green');
$interval = $numDatas / MAX_LABELS;
if ($interval < 1) {
	$interval = 1;
}
//echo "numDatas: $numDatas; interval: $interval";
$graph->xaxis->SetTextLabelInterval($interval);

$graph->yaxis->SetFont(FF_VERA);

$graph->legend->SetFont(FF_VERA);
$graph->legend->Pos(0.5, 0.03, 'center', 'top');
$graph->legend->SetColumns(5);
$graph->legend->SetHColMargin(10);

$graph->SetMargin(80, 20, 60, 20);
$graph->StrokeCSIM(basename(__FILE__));

require_once BADGER_ROOT . "/includes/fileFooter.php";

?>