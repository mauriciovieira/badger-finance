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
 * Template-Engine Class
 * Get the Template (*.tpl) from the tpl-Folder
 * 
 * @author Sepp
 */
class TemplateEngine {
	private $templatecache;
	private $theme;
	public $badgerRoot;
	private $additionalHeaderTags;
	private $settings;
	private $jsOnLoadEvents = array();
	private $writtenHeader = false;

	/**
	 * function __construct($settings, $badgerRoot)
	 * @param object $settings
	 * @param string $badgerRoot (e.g. "../..")
	 */
	function __construct($settings, $badgerRoot) {
		$this->settings = $settings;
		$this->theme = $this->settings->getProperty("badgerTemplate");
		$this->badgerRoot = $badgerRoot;		
	}	
	
	/**
	 * function getSettingsObj ()
	 * @return object user settings object
	 */
	function getSettingsObj() {
		return $this->settings;
	}
	
	/**
	 * function getTemplate ($template)
	 * @param string $template name of the Template (/tpl/ThemeName/ + $template + .tpl )
	 * @return string content of the template
	 */
	public function getTemplate($template) {	
		if(!isset($templatecache[$template])) {
			$filename = $this->badgerRoot.'/tpl/'.$this->theme.'/'.$template.'.tpl';
			if(file_exists($filename)) {
				$templatefile=str_replace("\"","\\\"",implode(file($filename),''));
			} else 	{
				//if none is existing -> try to get the standard one
				$filename = $this->badgerRoot.'/tpl/Standard/'.$template.'.tpl';
				if(file_exists($filename)) {
					$templatefile=str_replace("\"","\\\"",implode(file($filename),''));
				} else 	{
					throw new badgerException('templateEngine', 'noTemplate', $this->badgerRoot.'/tpl/'.$this->theme.'/'.$template.'.tpl'); 
					//$templatefile='<!-- TEMPLATE NOT FOUND: '.$filename.' -->';
				}
			}
			$templatefile = str_replace("{BADGER_ROOT}",$this->badgerRoot,$templatefile);
			$templatefile = preg_replace("'<if ([^>]*?)>(.*?)</if>'si", "\".( (\\1) ? \"\\2\" : \"\").\"", $templatefile);
			$templatecache[$template] = $templatefile;
		}
		return $templatecache[$template];
	}
	
	/**
	 * function getHeader ($pageTitle)
	 * @param string $pageTitle The name of the XHTML-Page
	 */
	public function getHeader($pageTitle) {
		global $print, $us;
		// standard badger header -> /tpl/ThemeName/badgerHeader.tpl
		$template = "badgerHeader";
		$JSOnLoadEvents = "";
		
		// create Page Title; add Bagder site name for the DB
		$pageTitle .= " - ".$this->settings->getProperty("badgerSiteName");
		
		// create start page link
		$startPageLink = BADGER_ROOT . '/' . $us->getProperty('badgerStartPage');
		
		// Set character set
		header ('Content-Type: text/html; charset=ISO-8859-1');

		// write XHTML Processing Instruction
		echo '<?xml version="1.0" encoding="iso-8859-1"?>';
		
		// transfer additionalHeaderTags (JS, CSS) to $var ($var must be in template)
		$additionalHeaderTags = $this->additionalHeaderTags;
		
		//add help functionality
		$additionalHeaderTags .= '<script type="text/javascript">var badgerHelpLang = "' . $us->getProperty('badgerLanguage') . '"; var badgerHelpRoot = "' . $this->badgerRoot . '/modules/help";</script>' . "\n";
		$additionalHeaderTags .= '<script type="text/javascript" src="' . $this->badgerRoot . '/js/help.js' . '"></script>' . "\n";
		
		// create onload-Event
		if($this->jsOnLoadEvents) {
			$JSOnLoadEvents = "\t<script type=\"text/javascript\">\n";
			$JSOnLoadEvents .=  "\twindow.onload = function () {\n";		
			foreach ($this->jsOnLoadEvents as $key => $value) {
	        	$JSOnLoadEvents .= "\t\t".$value."\n";
	        }
	        $JSOnLoadEvents .= "\t}\n";
	        $JSOnLoadEvents .= "\t</script>";
		}
		
		// write complete header
		$this->writtenHeader = true;
		eval("echo \"".$this->getTemplate($template)."\";");
	}
	
	/**
	 * function addCSS($cssFile)
	 * @param string $cssFile file name of the CSS-Page
	 */
	public function addCSS($cssFile, $cssMedia="") {
		if (!$this->writtenHeader) {
			if($cssMedia!="") $cssMedia = "media=\"$cssMedia\"";
			$this->additionalHeaderTags = $this->additionalHeaderTags."\t<link href=\"".$this->badgerRoot.'/tpl/'.$this->theme."/".$cssFile."\" rel=\"stylesheet\" $cssMedia type=\"text/css\" />\n";
		} else {
			throw new badgerException('templateEngine', 'HeaderIsAlreadyWritten', 'Function: addCSS()'); 
		}
	}

	/**
	 * function addJavaScript($JSFile)
	 * @param string $JSFile file name of the JS-Page (e.g. "js/prototype.js")
	 */
	public function addJavaScript($JSFile) {
		if (!$this->writtenHeader) {
			$this->additionalHeaderTags = $this->additionalHeaderTags."\t<script type=\"text/javascript\" src=\"".$this->badgerRoot."/".$JSFile."\"></script>\n";
		} else {
			throw new badgerException('templateEngine', 'HeaderIsAlreadyWritten', 'Function: addJavaScript()'); 
		}
	}
	
	/**
	 * function addHeaderTag($HeaderTag)
	 * @param string $HeaderTag complete header tag (e.g. "<script>...</script>")
	 */
	public function addHeaderTag($HeaderTag) {
		if (!$this->writtenHeader) {
			$this->additionalHeaderTags = $this->additionalHeaderTags."\t".$HeaderTag."\n";
		} else {
			throw new badgerException('templateEngine', 'HeaderIsAlreadyWritten', 'Function: addHeaderTag()'); 
		}		
	}
	
	/**
	 * function addOnLoadEvent($eventFunction)
	 * @param string $eventFunction entry for the onLoadEvent of the body (e.g. "initCalendar();")
	 */
	public function addOnLoadEvent($eventFunction) {
		if (!$this->writtenHeader) {
			$this->jsOnLoadEvents[] = "$eventFunction";
		} else {
			throw new badgerException('templateEngine', 'HeaderIsAlreadyWritten', 'Function: addOnLoadEvent()'); 
		}
	}
		
	/**
	 * function getThemeName()
	 * @return string current theme name (e.g. "Standard")
	 */
	public function getThemeName() {
		return $this->theme;
	}
	
	/**
	 * function getBadgerRoot()
	 * @return string Badger Root (e.g. "../..")
	 */
	public function getBadgerRoot() {
		return $this->badgerRoot;
	}
	
	/**
	 * function isHeaderWritten()
	 * @return boolean true if the function getHeader is called previously
	 */
	public function isHeaderWritten() {
		return $this->writtenHeader;
	}

}
?>