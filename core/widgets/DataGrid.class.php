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
/**
 * DataGrid Class
 * - add DataGrid to the doc
 * - add all necessary javascript variables to the doc
 *  
 * @author Sepp
 */
 require_once BADGER_ROOT . '/includes/fileHeaderBackEnd.inc.php';
 
class DataGrid {
	/**
	 * TemplateEngine Object
	 * @var object
	 */
	private $tpl;
	
	/**
	 * WidgetEngine Object
	 * @var object
	 */
	private $widgetEngine;
	
	/**
	 * Loading Message in the footer of the dataGrid table
	 * @var string
	 */
	private $LoadingMessage;
	
	/**
	 * Unique ID of the dataGrid
	 * @var string
	 */
	public $UniqueId;
	
	/**
	 * Name of the dataGrid columns
	 * @var array
	 */
	public $headerName = array();

	/**
	 * column order (column name must be in the xml!)
	 * @var array
	 */
	public $columnOrder = array();
	
	/**
	 * size of the columns in px
	 * @var array
	 */
	public $headerSize = array();
	
	/**
	 * align of the cells (left, right)
	 * @var array
	 */
	public $cellAlign = array();
	
	/**
	 * text of the javascript alert, confirm deleting
	 * @var string
	 */
	public $deleteMsg;

	/**
	 * text of the javascript alert when the user want to edit a row without selecting one before
	 * @var string
	 */	
	public $noRowSelectedMsg;

	/**
	 * refresh type after deletion of one record in data grid
	 * @var string
	 */
	public $deleteRefreshType;
		
	/**
	 * php-page called for deletion
	 * @var string
	 */
	public $deleteAction;
	
	/**
	 * php-page called for editing
	 * @var string
	 */
	public $editAction;
	
	/**
	 * php-page called for insertion
	 * @var string
	 */
	public $newAction;
	
	/**
	 * text after the number of rows
	 * @var string
	 */
	public $rowCounterName;
	
	/**
	 * width of the datagrid (e.g. 100px, 20em, 100%)
	 * @var string
	 */	
	public $width;
	
	/**
	 * height of the datagrid (e.g. 100px, 20em, 100%)
	 * @var string
	 */	
	public $height;
	
	public $discardSelectedRows = false;

	/**
	 * function function __construct($tpl)
	 * @param object template engine
	 */
	public function __construct($tpl, $uniqueId) {
		$this->tpl = $tpl;
		$this->widgetEngine = new WidgetEngine($tpl); 
		$this->UniqueId = $uniqueId;
		
		// default values
		$this->LoadingMessage = getBadgerTranslation2('dataGrid', 'LoadingMessage');
		$this->deleteMsg = getBadgerTranslation2('dataGrid', 'deleteMsg');
		$this->rowCounterName = getBadgerTranslation2('dataGrid', 'rowCounterName');
		$this->noRowSelectedMsg = getBadgerTranslation2('dataGrid', 'NoRowSelectedMsg');
		
		//TODO: inserted twice of two DG's on one site
		$tpl->addCss("Widgets/dataGrid/dataGridPrint.css", "print");
		$tpl->addCss("Widgets/dataGrid/dataGrid.css", "screen");
		
		$this->widgetEngine->addPageSettingsJS();
		$tpl->addJavaScript("js/behaviour.js");
	}
	
	/**
	 * function writeDataGrid ()
	 * @return string complete dataGrid skeleton (without rows)
	 */
	public function writeDataGrid() {
		if($this->width) $this->width = ' style="width: '.$this->width.';" '; 
		if($this->height) $this->height = ' style="height: '.$this->height.';" '; 
		
		$output = '<form id="dgForm'.$this->UniqueId.'"><div id="dataGrid'.$this->UniqueId.'" '.$this->width.' class="dataGrid">
					<table id="dgTableHead'.$this->UniqueId.'" cellpadding="2" class="dgTableHead" cellspacing="0">
						<tr>
							<td style="width: 25px"><input id="dgSelector'.$this->UniqueId.'" class="dgSelector" type="checkbox" /></td>';
			for ($i=0; $i < count($this->headerName); $i++) {
				$output .= '<td class="dgColumn" id="dgColumn'.$this->UniqueId.$this->columnOrder[$i].'" style="width: '.$this->headerSize[$i].'px">'.
							$this->headerName[$i].'&nbsp;'.
							$this->widgetEngine->addImage('Widgets/dataGrid/dropEmpty.gif', ' id="dgImg'.$this->UniqueId.$this->columnOrder[$i].'"') .
						   '</td>';
			}
		$output .= '		<td>&nbsp;</td>
						</tr>
					</table>';
					
		$output .= '<div class="dgDivScroll" id="dgDivScroll'.$this->UniqueId.'" '.$this->height.'>
					<table id="dgTableData'.$this->UniqueId.'" class="dgTableData" cellpadding="2" cellspacing="0">
						<tbody></tbody>
					</table>
					</div>
							
					<table id="dgTableFoot'.$this->UniqueId.'" class="dgTableFoot" cellpadding="2" cellspacing="0">						
						<tr>
							<td style="width: 130px"><span id="dgCount'.$this->UniqueId.'">0</span>/<span id="dgCountTotal'.$this->UniqueId.'">0</span> '.$this->rowCounterName.'&nbsp;</td>
							<td style="width: 23px"><span id="dgFilterStatus'.$this->UniqueId.'">' .
							$this->widgetEngine->addImage('Widgets/dataGrid/filter.gif') .
							'</span></td>
							<td><span id="dgMessage'.$this->UniqueId.'" class="dgMessage"></span></td>
						</tr>
					</table>
					</div></form>';
		return $output;		
	}
	
	/**
	 * function initDataGridJS ()
	 */
	public function initDataGridJS() {
		global $badgerDb;
		global $tpl;
		
		$us = new UserSettings($badgerDb);
		if ($this->discardSelectedRows == true) {
			$this->discardSelectedRows = "true";
		} else {
			$this->discardSelectedRows = "false";
		}
		
		$this->tpl->addJavaScript('js/dataGrid.js');
		//add global variable dataGrid'.$this->UniqueId.'
		$this->tpl->addHeaderTag('<script>dataGrid'.$this->UniqueId.' = new Object()</script>');
		$this->tpl->addOnLoadEvent('badgerRoot = "'. $tpl->getBadgerRoot() .'";');
		$this->tpl->addOnLoadEvent('dataGrid'.$this->UniqueId.' = new DataGrid( {');
		$this->tpl->addOnLoadEvent('  uniqueId: "'. $this->UniqueId .'",');
		$this->tpl->addOnLoadEvent('  sourceXML: "'.$this->sourceXML.'",');
		$this->tpl->addOnLoadEvent('  headerName: new Array("'.implode('","',$this->headerName).'"),');
		$this->tpl->addOnLoadEvent('  columnOrder: new Array("'.implode('","',$this->columnOrder).'"),');
		$this->tpl->addOnLoadEvent('  headerSize: new Array('.implode(',',$this->headerSize).'),');
		$this->tpl->addOnLoadEvent('  cellAlign: new Array("'.implode('","',$this->cellAlign).'"),');
		$this->tpl->addOnLoadEvent('  noRowSelectedMsg: "'. $this->noRowSelectedMsg .'",');
		$this->tpl->addOnLoadEvent('  deleteMsg: "'. $this->deleteMsg .'",');
		$this->tpl->addOnLoadEvent('  deleteRefreshType: "'. $this->deleteRefreshType .'",');
		$this->tpl->addOnLoadEvent('  deleteAction: "'. $this->deleteAction .'",');
		$this->tpl->addOnLoadEvent('  editAction: "'. $this->editAction .'",');
		$this->tpl->addOnLoadEvent('  newAction: "'. $this->newAction .'",');
		$this->tpl->addOnLoadEvent('  discardSelectedRows: '. $this->discardSelectedRows .',');
		$this->tpl->addOnLoadEvent('  loadingMessage: "'.$this->LoadingMessage.'",');
		
		try {$dgParameter = $us->getProperty('dgParameter'.$this->UniqueId); } catch(BadgerException $e) {};
		if ( isset($dgParameter) ) {		
				$this->tpl->addOnLoadEvent('  parameter: "'.$dgParameter.'",');
		}	
		$this->tpl->addOnLoadEvent('  tplPath: "'.BADGER_ROOT.'/tpl/'.$this->tpl->getThemeName().'/Widgets/dataGrid/"');
		$this->tpl->addOnLoadEvent('});');
		$this->tpl->addOnLoadEvent('$("dataGrid'.$this->UniqueId.'").obj = dataGrid'.$this->UniqueId.';');
		//$this->tpl->addOnLoadEvent('dataGrid'.$this->UniqueId.'.htmlDiv = $("dataGrid'.$this->UniqueId.'");');

		$this->tpl->addOnLoadEvent('Behaviour.register(dataGrid'.$this->UniqueId.'.behaviour);');
		$this->tpl->addOnLoadEvent('Behaviour.apply();');
		$this->tpl->addOnLoadEvent('Event.observe($("dataGrid'.$this->UniqueId.'"), \'keypress\', dataGrid'.$this->UniqueId.'.KeyEvents, false);');
		//TODO: find a solution that's working with multiple datagrids
		//$this->tpl->addOnLoadEvent('Event.observe(window, \'unload\', dataGrid'.$this->UniqueId.'.saveSelectedRows, false);');
		//$this->tpl->addOnLoadEvent('window.addEventListener(\'unload\', dataGrid'.$this->UniqueId.'.saveSelectedRows, false);');
	}
	
	public static function getNumberFilterSelectArray() {
		$filterArray = array ();
		
		$filterArray["eq"] = "=";
		$filterArray["lt"] = "&lt;";
		$filterArray["le"] = "&lt;=";
		$filterArray["gt"] = "&gt;";
		$filterArray["ge"] = "&gt;=";
		$filterArray["ne"] = "&lt;&gt;";
		
		return $filterArray;
	}

	public static function getStringFilterSelectArray() {
		$filterArray = array ();
		
		$filterArray["eq"] = getBadgerTranslation2('dataGridFilter', 'stringEqualTo');;
		$filterArray["ne"] = getBadgerTranslation2('dataGridFilter', 'StringNotEqual');;
		$filterArray["bw"] = getBadgerTranslation2('dataGridFilter', 'beginsWith');
		$filterArray["ew"] = getBadgerTranslation2('dataGridFilter', 'endsWith');
		$filterArray["ct"] = getBadgerTranslation2('dataGridFilter', 'contains');
		
		return $filterArray;
	}

	public static function getDateFilterSelectArray() {
		$filterArray = array ();
		
		$filterArray["eq"] = getBadgerTranslation2('dataGridFilter', 'dateEqualTo');
		$filterArray["lt"] = getBadgerTranslation2('dataGridFilter', 'dateBefore');
		$filterArray["le"] = getBadgerTranslation2('dataGridFilter', 'dateBeforeEqual');
		$filterArray["gt"] = getBadgerTranslation2('dataGridFilter', 'dateAfter');
		$filterArray["ge"] = getBadgerTranslation2('dataGridFilter', 'dateAfterEqual');
		$filterArray["ne"] = getBadgerTranslation2('dataGridFilter', 'dateNotEqual');
		
		return $filterArray;
	}
}
?>