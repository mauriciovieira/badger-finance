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
 * Widget-Engine Class
 * Insert
 * 	- ToolTip
 *  - Fields (text, hidden, password, ..)
 *  - Calendar field
 *  - Select field
 *  - Buttons
 *  - Images
 *  - Navigation head
 *  - AutoComlepte field aka Suggest
 *  
 * @author Sepp, Tom
 */
class WidgetEngine {
	private $ToolTipJSAdded = false;
	private $ToolTipLayerAdded = false;
	private $AutoCompleteJSAdded = false;
	private $CalendarJSAdded = false;
	private $TwistieSectionJSAdded = false;
	private $twistieSectionLastId = 0;
	private $prototypeJSAdded = false;
	private $jsonJSAdded = false;
	private $pageSettingsJSAdded = false;
	private $tpl;
	private $settings;
	private $writtenHeader = false;
	private $inputIds = array ();
	private $labelIds = array ();
	
	public function __construct($tpl) {
		$this->tpl = $tpl;
		$this->settings = $this->tpl->getSettingsObj();

/*	
function _jsVal_Language() {
    this.err_form = "Please enter/select values for the following fields:\n\n";
    this.err_select = "Please select a valid \"%FIELDNAME%\"";
    this.err_enter = "Please enter a valid \"%FIELDNAME%\"";
};
*/
	}
	
	private function getFormatedDateToday($format) {
		// convert dateformat to php date format
		$format = str_replace("dd","d", $format);
		$format = str_replace("mm","m", $format);
		$format = str_replace("yyyy","Y", $format);
		$format = str_replace("yy", "y", $format);
		return date($format, time());
	}
	public function addToolTipJS() {
		$this->tpl->addJavaScript("js/overlib_mini.js");
		$this->tpl->addJavaScript("js/overlib_cssw3c.js");
		$this->ToolTipJSAdded = true;
	}
	public function addCalendarJS() {
		$this->tpl->addJavaScript("js/calendar.js.php?badgerRoot=".$this->tpl->getBadgerRoot());
		$this->tpl->addCSS("Widgets/calendar/style.css");
		
		$this->tpl->addOnLoadEvent("initCalendar();");
		$this->CalendarJSAdded = true;
	}
	public function addAutoCompleteJS() {
		$this->tpl->addJavaScript("js/SuggestFramework.js");
		$this->tpl->addOnLoadEvent("initializeSuggestFramework();");
		$this->AutoCompleteJSAdded = true;
	}
	
	public function addTwistieSectionJS() {
		if ($this->TwistieSectionJSAdded) {
			return;
		}

		$this->tpl->addJavaScript('js/twistieSection.js');
		$this->TwistieSectionJSAdded = true;
	}
	
	public function addPrototypeJS() {
		if ($this->prototypeJSAdded) {
			return;
		}
		
		$this->tpl->addJavaScript('js/prototype.js');
		$this->prototypeJSAdded = true;
	}
	
	public function addJsonJS() {
		if ($this->jsonJSAdded) {
			return;
		}
		
		$this->tpl->addJavaScript('js/json-min.js');
		$this->jsonJSAdded = true;
	}
	
	public function addPageSettingsJS() {
		if ($this->pageSettingsJSAdded) {
			return;
		}
		
		$this->addPrototypeJS();
		$this->addJsonJS();
		$this->tpl->addJavaScript('js/PageSettings.js');
		$this->tpl->addHeaderTag('<script type="text/javascript">var BADGER_GET_PAGE_SETTINGS_URI = "' . BADGER_ROOT . '/core/pageSettings/getPageSettings.php";</script>');
		$this->pageSettingsJSAdded = true;
	}
	
	public function addToolTipLayer() {
		if ($this->tpl->isHeaderWritten()) {		
			$this->ToolTipLayerAdded = true;
			return "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";	
		} else {
			throw new badgerException('widgetsEngine', 'HeaderIsNotWritten', 'Function: addToolTipLayer()'); 
		}
	}
	
	public function addToolTipLink($link, $text, $linkname="") {
		if($this->ToolTipJSAdded) {
			if ($this->ToolTipLayerAdded) {
				if($link=="") $link = "javascript:void(0)";
				if($linkname=="") $linkname = $this->addImage('Widgets/help.gif');
				return "<a href=\"".$link."\" class=\"ToolTip\" tabindex=\"-999\" onmouseover=\"return overlib('". escape4Attr($text) . "', DELAY, 700, CSSW3C, DIVCLASS, 'TTDiv', BODYCLASS, 'TTbodyText');\" onmouseout=\"return nd();\">".$linkname."</a>\n";
			} else 	{
				throw new badgerException('widgetsEngine', 'ToolTipLayerNotAdded');
			}
		} else {
			throw new badgerException('widgetsEngine', 'ToolTipJSNotAdded'); 
		}		
	}

	public function addToolTip($text, $imageSrc="Widgets/help.gif") {
		if($this->ToolTipJSAdded) {
			if ($this->ToolTipLayerAdded) {
				$mouseEvents = "onmouseover=\"return overlib('" . escape4Attr($text) . "', DELAY, 700, CSSW3C, DIVCLASS, 'TTDiv', BODYCLASS, 'TTbodyText');\" onmouseout=\"return nd();\"";
				
				//return image with tooltip
				return $this->addImage($imageSrc, $mouseEvents);
			} else 	{
				throw new badgerException('widgetsEngine', 'ToolTipLayerNotAdded');
			}
		} else {
			throw new badgerException('widgetsEngine', 'ToolTipJSNotAdded'); 
		}		
	}
	
	public function addDateField($fieldname, $startdate = null) {
		$format = $this->settings->getProperty("badgerDateFormat");
		if(is_null($startdate)) {
			$startdate = $this->getFormatedDateToday($format);				
		}
		
		$strDateField = ""; 

		if($this->CalendarJSAdded) {
			$strDateField = "<input type=\"text\" id=\"".$fieldname."\" name=\"".$fieldname."\" size=\"10\" maxlength=\"10\" value=\"" . escape4Attr($startdate) . "\" />\n"; 
			$strDateField .= "<a href=\"javascript:void(0)\" onclick=\"showCalendar(this, mainform.".$fieldname.", '".$format."',1,-1,-1)\" tabindex=\"-999\">" . $this->addImage('Widgets/calendar/calendar.jpg') . "</a>\n";
			return $strDateField;
		} else {
			throw new badgerException('widgetsEngine', 'CalendarJSNotAdded'); 
		}
	}
	
	public function addAutoCompleteField($fieldname) {
		if($this->AutoCompleteJSAdded) {
			return "<input id=\"".$fieldname."\" name=\"".$fieldname."\" type=\"text\" action=\"autocomplete.html\" />";
		} else {
			throw new badgerException('widgetsEngine', 'AutoCompleteJSNotAdded'); 
		}
	}
	
	public function addTwistieSection($title, $innerHTML, $id = null, $startOpen = true) {
		if (is_null($id)) {
			$id = 'twistie' . ++$this->twistieSectionLastId;
		}
		
		$result = "<div class='twistieClosed' id='{$id}Closed'";
		if ($startOpen == true) {
			$result .= ' style="display:none;"';
		}
		$result .= " onclick=\"showTwistie('$id');\">";
		$result .= $this->addImage('Widgets/twistie/arrow_out.png', "class=\"showTwistie\" ");
		$result .= "&nbsp;$title</div>\n";
		
		$result .= "<fieldset id='{$id}Opened'";
		if ($startOpen == false) {
			$result .= ' style="display:none;"';
		}
		$result .= "><legend onclick=\"hideTwistie('$id');\">";
		$result .= $this->addImage('Widgets/twistie/arrow_in.png', "class=\"hideTwistie\" ");
		$result .= "&nbsp;$title</legend>\n";
		$result .= $innerHTML;
		$result .= '</fieldset>';
		
		return $result;
	}
	
	public function createLabel($field, $name, $mandatory=false) {
		if (!array_key_exists($field, $this->labelIds)) {
			$this->labelIds[$field] = 0;
			$id = $field;
		} else {
			$id = $field . '_' . $this->labelIds[$field];
			$this->labelIds[$field]++;
		}

		if( $mandatory ) {
			return "<label for='$id' id='label$id' class='mandatory'>$name</label>";
		} else {
			return "<label for='$id' id='label$id'>$name</label>";
		}
	}
	
	public function createField($fieldname, $size, $value="", $description="", $required=false, $type="text", $valCondition=""){
		// 'required' and 'regexp' are no XHTML attributes to input field
		// -> we've to add an extended namespace
		
		// formatings of numbers
		$compValue = str_replace(".","", str_replace(",","", $value));
		if (is_numeric($compValue)) {
			if ($compValue<0) {
				$class = "inputNumberMinus";
			} else {
				$class = "inputNumber";
			}
		} else {
			$class = "inputString";
		}
		
		//$valCondition
		//example:
		//    minvalue="10" maxvalue="90" regexp="money"
		//    callback="jsFunction"

		//required
		if ($required) $required = "required='required'";
		
		if (!array_key_exists($fieldname, $this->inputIds)) {
			$this->inputIds[$fieldname] = 0;
			$id = $fieldname;
		} else {
			$id = $fieldname . '_' . $this->inputIds[$fieldname];
			$this->inputIds[$fieldname]++;
		}
		
		$output = "<input type='$type' id='$id' name='$fieldname' size='$size' class='$class' value='" . escape4Attr($value) . "' $required $valCondition />";
		if($description) {
			$output .= "&nbsp;" . $this->addToolTip($description);
		}
		return $output;
	}
	
	public function createButton($name, $text, $action, $img="", $addTags=""){
		if ($action=="submit") { 
			$action = "if(!validateCompleteForm(this.form, 'error')) return false;";
			$type = "submit";
		} else {
			$type = "button";		
		}
		
		$output = "<button $addTags name='$name' id='$name' onclick=\"".$action."\" type=\"$type\">\n";
		$output .= "<table cellspacing='0' cellpadding='0'>\n";
		$output .= "\t<tr>\n";
		if ($img) {
			$output .= "\t\t<td>".$this->addImage($img)."</td>\n";
		}
		$output .= "\t\t<td nowrap='nowrap'>&nbsp;$text</td>\n";
		$output .= "\t</tr>\n";
		$output .= "</table>\n";
		$output .= "</button>";
			
		return $output;
	}
	
	public function addImage($file, $addAttributes="") {
		$filename = $this->tpl->getBadgerRoot() . '/tpl/' . $this->tpl->getThemeName() . "/$file";
		if (!file_exists($filename)) {
			//if none is existing -> try to get the standard one
			$filename = $this->tpl->getBadgerRoot() . "/tpl/Standard/$file";
		}
		if (!file_exists($filename)) {
			throw new badgerException('widgetEngine', 'noImage', $this->tpl->getBadgerRoot() . '/tpl/' . $this->tpl->getThemeName() . "/$file"); 
		}

		return "<img src='$filename' border='0' $addAttributes />";
	}
	
	public function createSelectField($name, $options, $default="", $description="", $mandatory=false, $selectAdditional="") {	
		$selectField = "<select name='$name' id='$name' $selectAdditional >\n";
		if(isset($options)) {
			foreach( $options as $key=>$value ){
				//default value
				$selected = (($key==$default) ? "selected" : "");
				//options 
				$selectField .= "\t<option $selected value='$key'>$value</option>\n";
			};
		}
		$selectField .= "</select>\n";
		if($description) {
			$selectField .= $this->addToolTip($description);
		}
		return $selectField;
	}
	
	public function createTextarea($fieldname, $value='', $description = '', $required = false, $specialAttributes = '') {
		//required
		if ($required) $required = "required='required'";
		
		$output = "<textarea id='$fieldname' name='$fieldname' $required $specialAttributes>";
		$output .= htmlentities($value);
		$output .= '</textarea>';
		if($description) {
			$output .= "&nbsp;" . $this->addToolTip($description);
		}
		
		return $output;
	}
	
	function addNavigationHead() {
		$tplNavigationHead = "";
		//Navigation Head
		eval("\$tplNavigationHead = \"".$this->tpl->getTemplate("Navigation/header")."\";");
		$this->tpl->addHeaderTag($tplNavigationHead);
		$this->tpl->addOnLoadEvent("loadNavigation()");		
	}

	function addJSValMessages() {
		$this->tpl->addHeaderTag("<script type=\"text/javascript\">");
		$this->tpl->addHeaderTag("function _jsVal_Language() {");
    	$this->tpl->addHeaderTag("	this.err_form = '" . getBadgerTranslation2('jsVal', 'err_form') . "';");
    	$this->tpl->addHeaderTag("	this.err_select = '" . getBadgerTranslation2('jsVal', 'err_select') . "';");
    	$this->tpl->addHeaderTag("	this.err_enter = '" . getBadgerTranslation2('jsVal', 'err_enter') . "';");
		$this->tpl->addHeaderTag("}");
		$this->tpl->addHeaderTag("</script>");
	}
}