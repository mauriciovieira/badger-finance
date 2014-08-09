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
require_once BADGER_ROOT . '/includes/jpGraph/src/jpgraph_pie.php';
require_once BADGER_ROOT . '/includes/jpGraph/src/jpgraph_pie3d.php';
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';
require_once BADGER_ROOT . '/modules/statistics2/colors.php';

define('MAX_CATEGORIES', 12);
define('MIN_PERCENTAGE', 0.02);

$graph = new PieGraph(1200, 600);

$accountIds = getGPC($_GET, 'accounts', 'integerList');

$accountManager = new AccountManager($badgerDb);

$type = getGPC($_GET, 'type');
if ($type !== 'o') {
	$type = 'i';
}

if (getGPC($_GET, 'summarize') !== 't') {
	$summarize = false;
} else {
	$summarize = true;
}

$amounts = array();
$amounts['none'] = new Amount(0);
$labels = array();
$labels['none'] = getBadgerTranslation2('statistics', 'noCategoryAssigned');

foreach($accountIds as $currentAccountId) {
	$currentAccount = $accountManager->getAccountById($currentAccountId);
	
	$filter = getDataGridFilter($currentAccount);
	$currentAccount->setFilter($filter);

	while ($currentTransaction = $currentAccount->getNextTransaction()) {
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

			if (isset($labels['c' . $category->getId()])) {
				$amounts['c' . $category->getId()]->add($currentTransaction->getAmount());
			} else {
				$labels['c' . $category->getId()] = $category->getTitle();
				$amounts['c' . $category->getId()] = new Amount($currentTransaction->getAmount());
			}
		} else {
			$amounts['none']->add($currentTransaction->getAmount());
		}
	}
}

if ($amounts['none']->compare(0) == 0) {
	unset($amounts['none']);
	unset($labels['none']);
}

if (count($amounts) == 0) {
	echo getBadgerTranslation2('statistics2Graph', 'noMatchingTransactions');

	require_once BADGER_ROOT . "/includes/fileFooter.php";
	exit;
}

array_multisort($amounts, $labels);

$other = new Amount(0);

if (count($amounts) > MAX_CATEGORIES) {
	$i = 0;
	foreach ($amounts as $currentId => $currentAmount) {
		$i++;
		if ($i > MAX_CATEGORIES) {
			$other->add($currentAmount);
			unset($amounts[$currentId]);
			unset($labels[$currentId]);
		}
	}
}

$total = new Amount(0);
$total->add($other);
foreach ($amounts as $currentAmount) {
	$total->add($currentAmount);
}
foreach ($amounts as $currentId => $currentAmount) {
	$percentage = new Amount($currentAmount);
	$percentage->div($total);
	
	if ($percentage->compare(MIN_PERCENTAGE) < 0) {
		$other->add($currentAmount);
		unset($amounts[$currentId]);
		unset($labels[$currentId]);
	}
}

if ($other->compare(0) != 0) {
	$amounts['other'] = $other;
	$labels['other'] = getBadgerTranslation2('statistics2', 'miscCategories');
}

$data = array();
$dataNames = array();

foreach ($amounts as $currentAmount) {
	$data[] = $currentAmount->mul($type == 'i' ? 1 : -1)->get();
	$dataNames[] = $currentAmount->getFormatted();
}

$legends = array();
foreach ($labels as $currentKey => $currentLabel) {
	$legends[] = $currentLabel . ' - ' . $amounts[$currentKey]->getFormatted();
}

$targets = array();
foreach($labels as $currentId => $currentLabel) {
	if ($currentId != 'none' && $currentId != 'other') {
		$targets[] = 'javascript:reachThroughCategory(\'' . substr($currentId, 1) . '\');';
	} else {
		$targets[] = '';
	}
}

$pie = new PiePlot3D($data);
$pie->SetLegends($legends);
$pie->SetCSIMTargets($targets, $dataNames);
$pie->value->SetFont(FF_VERA);
$pie->value->SetFormatCallback('formatPercentage');
$pie->SetCenter(0.33, 0.5);
//$pie->SetSliceColors($chartColors);

$graph->Add($pie);

$graph->legend->SetFont(FF_VERA);
$graph->legend->SetPos(0.03, 0.05);
$graph->SetMargin(10, 10, 10, 10);
$graph->SetShadow();
$graph->SetAntiAliasing();

$graph-> StrokeCSIM(basename(__FILE__));

require_once BADGER_ROOT . "/includes/fileFooter.php";

function formatPercentage($val) {
	global $us;

	$str = sprintf('%1.2f %%', $val);
	
	return str_replace('.', $us->getProperty('badgerDecimalSeparator'), $str);
}
?>