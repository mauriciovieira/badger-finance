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

require_once BADGER_ROOT . '/includes/fileHeaderFrontEnd.inc.php';
require_once BADGER_ROOT . '/modules/account/accountCommon.php';
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';
require_once BADGER_ROOT . '/core/widgets/DataGrid.class.php';

$FILTER_ID_MARKER = '__FILTER_ID__';

$widgets = new WidgetEngine($tpl); 

$widgets->addCalendarJS();
$widgets->addToolTipJS();
$widgets->addTwistieSectionJS();
$widgets->addPrototypeJS();
$widgets->addPageSettingsJS();
$tpl->addJavaScript("js/behaviour.js");
$tpl->addJavaScript("js/statistics2.js");

$dgAccounts = new DataGrid($tpl, 'Statistics2Accounts');
$dgAccounts->sourceXML = BADGER_ROOT . '/core/XML/getDataGridXML.php?q=AccountManager';
$dgAccounts->headerName = array(
	getBadgerTranslation2('statistics','accColTitle'), 
	getBadgerTranslation2('statistics','accColBalance'), 
	getBadgerTranslation2('statistics','accColCurrency'));
$dgAccounts->columnOrder = array('title', 'balance', 'currency');  
$dgAccounts->headerSize = array(160, 100, 75);
$dgAccounts->cellAlign = array('left', 'right', 'left');
$dgAccounts->width = '30em';
$dgAccounts->height = '7em';
$dgAccounts->discardSelectedRows = true;
$dgAccounts->initDataGridJS();


$dgResult = new DataGrid($tpl, 'Statistics2Result');
$dgResult->sourceXML = '';//BADGER_ROOT . '/core/XML/getDataGridXML.php?q=MultipleAccounts&qp=1';
$dgResult->headerName = array(
	getBadgerTranslation2('statistics2', 'colAccountName'),
	getBadgerTranslation2('accountOverview', 'colValutaDate'),
	getBadgerTranslation2('accountOverview', 'colTitle'), 
	getBadgerTranslation2('accountOverview', 'colAmount'),
	getBadgerTranslation2('accountOverview', 'colCategoryTitle'));
$dgResult->columnOrder = array('accountTitle', 'valutaDate', 'title', 'amount', 'concatCategoryTitle');  
$dgResult->height = "350px";
$dgResult->headerSize = array(200, 90,350,80,200);
$dgResult->cellAlign = array('left', 'left', 'left', 'right', 'left');
$dgResult->deleteRefreshType = 'refreshDataGrid';
$dgResult->discardSelectedRows = true;
$dgResult->initDataGridJS();

$widgets->addNavigationHead();

$pageTitle = getBadgerTranslation2('statistics2', 'pageTitle');

$tpl->addOnLoadEvent('loadPageSettingNamesList(); loadPageSetting(true);');

echo $tpl->getHeader($pageTitle);
	
$widgets->addToolTipLayer();

$dataGridFilterArray = DataGrid::getNumberFilterSelectArray();
$dataGridDateFilterArray = DataGrid::getDateFilterSelectArray();
$dataGridStringFilterArray = DataGrid::getStringFilterSelectArray();

$pageSettingsContent =
	$widgets->createSelectField('pageSettingsSelect', array(), '', '', false, 'onchange="loadPageSetting();"')
	. '&nbsp;'
	. $widgets->createButton('pageSettingSave', getBadgerTranslation2('statistics2', 'pageSettingSave'), 'savePageSetting();')
	. '&nbsp;'
	. $widgets->createButton('pageSettingDelete', getBadgerTranslation2('statistics2', 'pageSettingDelete'), 'deletePageSetting();')
;
$pageSettingsTwistie = $widgets->addTwistieSection(getBadgerTranslation2('statistics2', 'pageSettingsTwistieTitle'), $pageSettingsContent);

$pageSettingJS = '<script type="text/javascript">var newNamePrompt = "' . getBadgerTranslation2('statistics2', 'pageSettingNewNamePrompt') . '";</script>';

$filters['unselected'] = '';
$filters['title'] =
	getBadgerTranslation2('statistics2', 'titleFilter') 
	. $widgets->createSelectField("titleOperator$FILTER_ID_MARKER", $dataGridStringFilterArray, "", "", false, "style='width: 95px;'")
	. '&nbsp;'
	. $widgets->createField("title$FILTER_ID_MARKER", 30, "", "", false, "text", "")
	;
$filters['description'] = 
	getBadgerTranslation2('statistics2', 'descriptionFilter')
	. $widgets->createSelectField("descriptionOperator$FILTER_ID_MARKER", $dataGridStringFilterArray, "", "", false, "style='width: 95px;'")
	. '&nbsp;'
	. $widgets->createField("description$FILTER_ID_MARKER", 30, "", "", false, "text", "")
	;
$filters['valutaDate'] =
	getBadgerTranslation2('statistics2', 'valutaDateFilter')
	. $widgets->createSelectField("valutaDateOperator$FILTER_ID_MARKER", $dataGridDateFilterArray, "", "", false, "style='width: 95px;'")
	. '&nbsp;'
	.$widgets->addDateField("valutaDate$FILTER_ID_MARKER", "")
	;
$filters['valutaDateBetween'] =
	getBadgerTranslation2('statistics2', 'valutaDateBetweenFilter')
	. $widgets->addDateField("valutaDateStart$FILTER_ID_MARKER", "")
	. getBadgerTranslation2('statistics2', 'valutaDateBetweenFilterConj')
	. $widgets->addDateField("valutaDateEnd$FILTER_ID_MARKER", "")
	. getBadgerTranslation2('statistics2', 'valutaDateBetweenFilterInclusive')
	;
$filters['valutaDateAgo'] = 
	getBadgerTranslation2('statistics2', 'valutaDateAgoFilter')
	. $widgets->createField("valutaDateAgo$FILTER_ID_MARKER", 3, "", "", false, "integer", "")
	. getBadgerTranslation2('statistics2', 'valutaDateAgoFilterDaysAgo')
	;
$filters['amount'] =
	getBadgerTranslation2('statistics2', 'amountFilter')
	. $widgets->createSelectField("amountOperator$FILTER_ID_MARKER", $dataGridFilterArray, "", "", false, "style='width: 95px;'")
	. '&nbsp;'
	. $widgets->createField("amount$FILTER_ID_MARKER", 3, "", "", false, "integer", "")
	;
$filters['outsideCapital'] =
	getBadgerTranslation2('statistics2', 'outsideCapitalFilter')
	. $widgets->createField("outsideCapital$FILTER_ID_MARKER", null, '1', '', false, 'radio')
	. $widgets->createLabel("outsideCapital$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'outsideCapitalFilterOutside'))
	. '&nbsp;'
	. $widgets->createField("outsideCapital$FILTER_ID_MARKER", null, '0', '', false, 'radio')
	. $widgets->createLabel("outsideCapital$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'outsideCapitalFilterInside'))
	;
$filters['transactionPartner'] =
	getBadgerTranslation2('statistics2', 'transactionPartnerFilter')
	. $widgets->createSelectField("transactionPartnerOperator$FILTER_ID_MARKER", $dataGridStringFilterArray, "", "", false, "style='width: 95px;'")
	. '&nbsp;'
	. $widgets->createField("transactionPartner$FILTER_ID_MARKER", 30, "", "", false, "text", "")
	;
$filters['category'] =
	getBadgerTranslation2('statistics2', 'categoryFilter')
	. $widgets->createField("categoryOp$FILTER_ID_MARKER", null, 'eq', '', false, 'radio')
	. $widgets->createLabel("categoryOp$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'categoryFilterIs'))
	. '&nbsp;'
	. $widgets->createField("categoryOp$FILTER_ID_MARKER", null, 'ne', '', false, 'radio')
	. $widgets->createLabel("categoryOp$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'categoryFilterIsNot'))
	. '&nbsp;'
	. $widgets->createSelectField("categoryId$FILTER_ID_MARKER", getCategorySelectArray(true), "", "", false, "style='width: 210px;'")
	;
$filters['exceptional'] =
	getBadgerTranslation2('statistics2', 'exceptionalFilter')
	. $widgets->createField("exceptional$FILTER_ID_MARKER", null, '1', '', false, 'radio')
	. $widgets->createLabel("exceptional$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'exceptionalFilterExceptional'))
	. '&nbsp;'
	. $widgets->createField("exceptional$FILTER_ID_MARKER", null, '0', '', false, 'radio')
	. $widgets->createLabel("exceptional$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'exceptionalFilterNotExceptional'))
	;
$filters['periodical'] =
	getBadgerTranslation2('statistics2', 'periodicalFilter')
	. $widgets->createField("periodical$FILTER_ID_MARKER", null, '1', '', false, 'radio')
	. $widgets->createLabel("periodical$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'periodicalFilterPeriodical'))
	. '&nbsp;'
	. $widgets->createField("periodical$FILTER_ID_MARKER", null, '0', '', false, 'radio')
	. $widgets->createLabel("periodical$FILTER_ID_MARKER", getBadgerTranslation2('statistics2', 'periodicalFilterNotPeriodical'))
	;
	

$availableFilters = array (
	'unselected' => getBadgerTranslation2('statistics2', 'availableFiltersUnselected'),
	'title' => getBadgerTranslation2('statistics2', 'availableFiltersTitle'),
	'description' => getBadgerTranslation2('statistics2', 'availableFiltersDescription'),
	'valutaDate' => getBadgerTranslation2('statistics2', 'availableFiltersValutaDate'),
	'valutaDateBetween' => getBadgerTranslation2('statistics2', 'availableFiltersValutaDateBetween'),
	'valutaDateAgo' => getBadgerTranslation2('statistics2', 'availableFiltersValutaDateAgo'),
	'amount' => getBadgerTranslation2('statistics2', 'availableFiltersAmount'),
	'outsideCapital' => getBadgerTranslation2('statistics2', 'availableFiltersOutsideCapital'),
	'transactionPartner' => getBadgerTranslation2('statistics2', 'availableFiltersTransactionPartner'),
	'category' => getBadgerTranslation2('statistics2', 'availableFiltersCategory'),
	'exceptional' => getBadgerTranslation2('statistics2', 'availableFiltersExceptional'),
	'periodical' => getBadgerTranslation2('statistics2', 'availableFiltersPeriodical'),
	'delete' => getBadgerTranslation2('statistics2', 'availableFiltersDelete')
);

$dateFormat = $widgets->createField('dateFormat', null, $us->getProperty('badgerDateFormat'), null, false, 'hidden');

$content = "<div style=\"float: left;\">";
$content .= $widgets->createSelectField("filterSelect$FILTER_ID_MARKER", $availableFilters, "", "", false, "onchange=\"setFilterContent('$FILTER_ID_MARKER');\"");
$content .= "</div><div id=\"filterContent$FILTER_ID_MARKER\"></div>";
$filterLineEmpty = "<div id='filterLineEmpty' style='display:none;'>$content</div>";

$filtersEmpty = '';

foreach ($filters as $currentName => $currentFilter) {
	$filtersEmpty .= "<div id='{$currentName}Empty' style='display:none;'>$currentFilter</div>";
}

$dataGridAccounts = $dgAccounts->writeDataGrid();
$filterCaption = getBadgerTranslation2('statistics2', 'filterCaption');
$addFilterButton = $widgets->createButton('addFilter', getBadgerTranslation2('statistics2', 'addFilterButton'), 'addFilterLineX();');

eval('$filterBoxContent = "' . $tpl->getTemplate('statistics2/filterBox') . '";');
$filterBox = $widgets->addTwistieSection(
	getBadgerTranslation2('statistics2', 'twistieCaptionInput'),
	$filterBoxContent,
	null,
	true
);

$ACTIVE_OS_MARKER = '__ACTIVE_OS__';

$outputSelectionTrend = '<div id="outputSelectionTrend" style="display: inline; vertical-align: top;">'
	. '<fieldset style="display: inline; vertical-align: top;">'
	. '<legend>'
	. getBadgerTranslation2('statistics2', 'outputSelectionTrendStartValue')
	. '</legend>'
	. '<p>'
	. $widgets->createField("outputSelectionTrendStart$ACTIVE_OS_MARKER", null, 'b', '', false, 'radio', 'checked="checked"')
	. $widgets->createLabel("outputSelectionTrendStart$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTrendStartValueBalance'))
	. '</p><p>'
	. $widgets->createField("outputSelectionTrendStart$ACTIVE_OS_MARKER", null, '0', '', false, 'radio')
	. $widgets->createLabel("outputSelectionTrendStart$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTrendStartValueZero'))
	. '</p>'
	. '</fieldset>'
	. '<fieldset style="display: inline; vertical-align: top;">'
	. '<legend>'
	. getBadgerTranslation2('statistics2', 'outputSelectionTrendTickLabels')
	. '</legend>'
	. '<p>'
	. $widgets->createField("outputSelectionTrendTicks$ACTIVE_OS_MARKER", null, 's', '', false, 'radio', 'checked="checked"')
	. $widgets->createLabel("outputSelectionTrendTicks$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTrendTickLabelsShow'))
	. '</p><p>'
	. $widgets->createField("outputSelectionTrendTicks$ACTIVE_OS_MARKER", null, 'h', '', false, 'radio')
	. $widgets->createLabel("outputSelectionTrendTicks$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTrendTickLabelsHide'))
	. '</p>'
	. '</fieldset>'
	. '</div>';	

$outputSelectionCategory = '<div id="outputSelectionCategory">'
	. '<fieldset style="display: inline; vertical-align: top;">'
	. '<legend>'
	. getBadgerTranslation2('statistics2', 'outputSelectionCategoryType')
	. '</legend>'
	. '<p>'
	. $widgets->createField("outputSelectionCategoryType$ACTIVE_OS_MARKER", null, 'i', '', false, 'radio', 'checked="checked"')
	. $widgets->createLabel("outputSelectionCategoryType$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionCategoryTypeInput'))
	. '</p><p>'
	. $widgets->createField("outputSelectionCategoryType$ACTIVE_OS_MARKER", null, 'o', '', false, 'radio')
	. $widgets->createLabel("outputSelectionCategoryType$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionCategoryTypeOutput'))
	. '</p>'
	. '</fieldset>'
	. '<fieldset style="display: inline; vertical-align: top;">'
	. '<legend>'
	. getBadgerTranslation2('statistics2', 'outputSelectionCategorySubCategories')
	. '</legend>'
	. '<p>'
	. $widgets->createField("outputSelectionCategorySummarize$ACTIVE_OS_MARKER", null, 't', '', false, 'radio', 'checked="checked"')
	. $widgets->createLabel("outputSelectionCategorySummarize$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionCategorySubCategoriesSummarize'))
	. '</p><p>'
	. $widgets->createField("outputSelectionCategorySummarize$ACTIVE_OS_MARKER", null, 'f', '', false, 'radio')
	. $widgets->createLabel("outputSelectionCategorySummarize$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionCategorySubCategoriesNoSummarize'))
	. '</p>'
	. '</fieldset>'
	. '</div>';

$outputSelectionTimespan = '<div id="outputSelectionTimespan">'
	. '<fieldset style="display: inline; vertical-align: top;">'
	. '<legend>'
	. getBadgerTranslation2('statistics2', 'outputSelectionTimespanType')
	. '</legend>'
	. '<p>'
	. $widgets->createField("outputSelectionTimespanType$ACTIVE_OS_MARKER", null, 'w', '', false, 'radio')
	. $widgets->createLabel("outputSelectionTimespanType$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTimespanTypeWeek'))
	. '</p><p>'
	. $widgets->createField("outputSelectionTimespanType$ACTIVE_OS_MARKER", null, 'm', '', false, 'radio')
	. $widgets->createLabel("outputSelectionTimespanType$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTimespanTypeMonth'))
	. '</p><p>'
	. $widgets->createField("outputSelectionTimespanType$ACTIVE_OS_MARKER", null, 'q', '', false, 'radio', 'checked="checked"')
	. $widgets->createLabel("outputSelectionTimespanType$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTimespanTypeQuarter'))
	. '</p><p>'
	. $widgets->createField("outputSelectionTimespanType$ACTIVE_OS_MARKER", null, 'y', '', false, 'radio')
	. $widgets->createLabel("outputSelectionTimespanType$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionTimespanTypeYear'))
	. '</p>'
	. '</fieldset>'
	. '<fieldset style="display: inline; vertical-align: top;">'
	. '<legend>'
	. getBadgerTranslation2('statistics2', 'outputSelectionCategorySubCategories')
	. '</legend>'
	. '<p>'
	. $widgets->createField("outputSelectionTimespanSummarize$ACTIVE_OS_MARKER", null, 't', '', false, 'radio', 'checked="checked"')
	. $widgets->createLabel("outputSelectionTimespanSummarize$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionCategorySubCategoriesSummarize'))
	. '</p><p>'
	. $widgets->createField("outputSelectionTimespanSummarize$ACTIVE_OS_MARKER", null, 'f', '', false, 'radio')
	. $widgets->createLabel("outputSelectionTimespanSummarize$ACTIVE_OS_MARKER", getBadgerTranslation2('statistics2', 'outputSelectionCategorySubCategoriesNoSummarize'))
	. '</p>'
	. '</fieldset>'
	. '</div>';


$outputSelection = "<div id='outputSelections' style='display:none;'>
	$outputSelectionTrend
	$outputSelectionCategory
	$outputSelectionTimespan
</div>";

$outputSelectionContent = '<fieldset style="width: 8em; display: inline; vertical-align: top;">'
	. '<legend>'
	. getBadgerTranslation2('statistics2', 'outputSelectionGraphType')
	. '</legend>'
	. '<p>'
	. $widgets->createField("outputSelectionType", null, 'Trend', '', false, 'radio', 'checked="checked" onchange="updateOutputSelection();" onclick="updateOutputSelection();"')
	. $widgets->createLabel("outputSelectionType", getBadgerTranslation2('statistics2', 'outputSelectionGraphTypeTrend'))
	. '</p><p>'
	. $widgets->createField("outputSelectionType", null, 'Category', '', false, 'radio', 'onchange="updateOutputSelection();" onclick="updateOutputSelection();"')
	. $widgets->createLabel("outputSelectionType", getBadgerTranslation2('statistics2', 'outputSelectionGraphTypeCategory'))
	. '</p><p>'
	. $widgets->createField("outputSelectionType", null, 'Timespan', '', false, 'radio', 'onchange="updateOutputSelection();" onclick="updateOutputSelection();"')
	. $widgets->createLabel("outputSelectionType", getBadgerTranslation2('statistics2', 'outputSelectionGraphTypeTimespan'))
	. '</p>'
	. '</fieldset>'
	. "<div id='outputSelectionContent' style='display: inline; vertical-align: top;'>"
	. str_replace($ACTIVE_OS_MARKER, '', $outputSelectionTrend)
	. '</div>';

$outputSelectionTwistie = $widgets->addTwistieSection(getBadgerTranslation2('statistics2', 'twistieCaptionOutputSelection'), $outputSelectionContent, null, true);	

$analyzeButton = $widgets->createButton('applyFilter', getBadgerTranslation2('statistics2', 'analyzeButton'), 'applyFilterX();');

$graphTwistie = $widgets->addTwistieSection(getBadgerTranslation2('statistics2', 'twistieCaptionGraph'), '<div id="graphContent"></div>', null, true);

$outputTwistie = $widgets->addTwistieSection(getBadgerTranslation2('statistics2', 'twistieCaptionOutput'), '<div id="resultGridContainer" style="display:none;">' . $dgResult->writeDataGrid() . '</div>', null, true);

eval('echo "' . $tpl->getTemplate('statistics2/statistics2') . '";');
eval('echo "' . $tpl->getTemplate('badgerFooter') . '";');
?>