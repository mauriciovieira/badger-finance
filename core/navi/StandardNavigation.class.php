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

require_once 'Navigation.iface.php';

/**
 * Renders the Navigation with the @link http://www.tohzhiqiang.per.sg/projects/jsdomenubar/ jsDOMenuBar by Toh Zhiqiang.
 *
 * @author Eni Kao
 * @version $LastChangedRevision: 1161 $
 */
class StandardNavigation implements Navigation {
	
	/**
	 * Copy of the $structure parameter of @link setStructure setStructure,
	 * augmented by @link parseIcons parseIcons.
	 * 
	 * @var array
	 */
	private $structure;
	
	/**
	 * The name used for the icon CSS classes.
	 * 
	 * @var string
	 */
	const iconName = 'stdNavIcons';
	
	/**
	 * Registers initjsDOMenu for body.onload.
	 */
	public function __construct() {
		//need to register initjsDOMenu() for body.onload
	}
	
	/**
	 * Sets Navigation Structure.
	 * 
	 * Every navigation structure is an array. It consists of three different types of sub-elements,
	 * which are also arrays. The type of the sub-element is stored in the array element <tt>type</tt>.
	 * There are:
	 * <ul>
	 * 	<li><b>item</b> These are usual menu items. It needs to have the <tt>name</tt> and <tt>command</tt>
	 * 		properties. If <tt>command</tt> starts with "javascript:", it is considered JavaScript code, else
	 * 		it is a URL. Additionally, items can have a <tt>tooltip</tt> and an <tt>icon</tt>.</li>
	 * 	<li><b>separator</b> A menu separator, usually drawn as a line. It has no additional properties.</li>
	 * 	<li><b>menu</b> A sub-menu. It requires the properties <tt>name</tt> and <tt>menu</tt>, whereas
	 * 		the latter holds an array with the same structure as the main array. It can be nested infinitely.
	 * 		There can be support for a <tt>tooltip</tt> and an <tt>icon</tt>.</li>
	 * </ul>
	 * 
	 * Example:
	 * <pre>
	 * 	array (
	 * 		array (
	 * 			'type' => 'item',
	 * 			'name' => 'Log out',
	 * 			'tooltip' => 'Logs the user out',
	 * 			'icon' => 'navi/logout.png',
	 * 			'command' => 'core/session.php?logout'
	 * 		),
	 * 		array (
	 * 			'type' => 'separator',
	 * 		),
	 * 		array (
	 * 			'type' => 'menu',
	 * 			'name' => 'Accounts',
	 * 			'tooltip' => 'All account-related tasks',
	 * 			'icon' => 'navi/account.png',
	 * 			'menu' => array (
	 * 				array (
	 * 					'type' => 'item',
	 * 					'name' => 'Transfer',
	 * 					'tooltip' => 'Transfer amounts to other account',
	 * 					'command' => 'modules/account/transfer.php'
	 * 				)
	 * 			)
	 * 		)
	 * 	)
	 * </pre>
	 * 
	 * @param array $structure The navigation structure in the format described above
	 * @return void
	 */
	public function setStructure($structure) {
		$this->structure = $structure;
	}
	
	/**
	 * Returns the required HTML header values to include
	 * 
	 * @return string All necessary HTML header tags
	 */
	public function getHeader() {
		$staticLinks = '<link rel="stylesheet" type="text/css" href="' . BADGER_ROOT . '/js/jsDOMenuBar/themes/office_xp/office_xp.css" />
			<link rel="stylesheet" type="text/css" href="' . BADGER_ROOT . '/core/navi/getStandardNavigation.php?part=css" />
			<script type="text/javascript" src="' . BADGER_ROOT . '/js/jsDOMenuBar/jsdomenu.js"></script>
			<script type="text/javascript" src="' . BADGER_ROOT . '/js/jsDOMenuBar/jsdomenubar.js"></script>
		';
		//<script type="text/javascript" src="core/navi/StandardNavigation.js.php"></script>
		
		return $staticLinks;
	}
	
	/**
	 * Returns the CSS definitions required for the navigation.
	 * 
	 * @return string The CSS definitions required for the navigtion.
	 */
	public function getCSS() {
		return $this->parseIcons(StandardNavigation::iconName, $this->structure);
	}

	/**
	 * Returns the JS calls required for the navigation.
	 * 
	 * @return string The JS calls required for the navigation.
	 */
	public function getJS() {
//echo "<pre>"; print_r($this->structure); echo "</pre>\n";
		//We need the names of the CSS icon classes
		$structure =& $this->parseIconIds(StandardNavigation::iconName, $this->structure);
		
		//absolute / fixed / static
		$result = 'menuBar = new jsDOMenuBar("static", "staticMenuBar", false);
			menuBar.setActivateMode("click");
		';
		
		$menuNum = 0;
		
		foreach ($structure as $mainElement) {
			switch ($mainElement['type']) {
				case 'item':
					$action = $this->calcCommand($mainElement['command']);
						
					//Add MenuItem
					$result .= "menuBar.addMenuBarItem(new menuBarItem('$mainElement[name]', '', 'mainMenu$menuNum', '', '$action'));\n";
					
					//Show icon, if available
					if (isset($mainElement['iconId'])) {
						$result .= "menuBar.items.mainMenu$menuNum.showIcon('$mainElement[iconId]');\n";
					}
					
					$menuNum++;
					break;
					
				case 'menu':
					$result .= $this->renderSubMenu("mainMenu$menuNum", $mainElement['menu']);
					
					//Add SubMenu
					$result .= "\nmenuBar.addMenuBarItem(new menuBarItem('" . $mainElement['name'] . "', mainMenu$menuNum, 'mainMenu$menuNum'));\n";

					//Show icon, if available
					if (isset($mainElement['iconId'])) {
						$result .= "menuBar.items.mainMenu$menuNum.showIcon('$mainElement[iconId]');\n";
					}
					
					$menuNum++;
				break;
				
				//jsDOMenuBar does not support separators on top level
			}
		}
		
		return $result;
	}
	
	/**
	 * Returns the Navigation as HTML Fragment
	 * 
	 * @return string the Navigation HTML
	 */
	public function getHTML() {
		return '<script type="text/javascript" src="' . BADGER_ROOT . '/core/navi/getStandardNavigation.php?part=js"></script>';
	}
	
	/**
	 * Walks recursively through $structure, creates CSS classes in result 
	 * and iconId properties in $this->structure
	 * 
	 * @param array $name The name of this level
	 * @param array $structure The unprocessed sub-treee of $this->structure
	 * 
	 * @return string A string with all requiered CSS classes
	 */
	private function parseIcons($name, &$structure) {
		$result = '';
		
		$numElement = 0;
		
		$isOpera = isset($_SERVER['HTTP_USER_AGENT']) && (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'opera') !== false);
		//echo $_SERVER['HTTP_USER_AGENT'] . $isOpera;

		$unindentIcon = $isOpera && ($name == StandardNavigation::iconName);
		
		foreach ($structure as $key => $currentElement) {
			$iconId = "{$name}_{$numElement}";
			
			if (isset($currentElement['icon']) && $currentElement['icon'] != '') {
				$result .=  ".$iconId {
					background-image: url('$currentElement[icon]');
					background-repeat: no-repeat; /* Do not alter this line! */
					height: 16px;
					left: " . ($unindentIcon ? '-22px' : '2px') . ";
					position: absolute; /* Do not alter this line! */
					width: 16px;
				 }\n";
				
				$numElement++;
			}

			//walk through recursively
			if ($currentElement['type'] == 'menu') {
				$result .= $this->parseIcons($iconId, $currentElement['menu']);
			}
		}
		
		return $result;
	} 

	/**
	 * Walks recursively through $structure, creates iconId properties in $structure
	 * 
	 * @param array $name The name of this level
	 * @param array $structure The unprocessed sub-treee of $this->structure
	 * 
	 * @return array The modified $structure
	 */
	private function parseIconIds($name, &$structure) {
		$numElement = 0;
		foreach ($structure as $key => $currentElement) {
			$iconId = "{$name}_{$numElement}";
			
			if (isset($currentElement['icon']) && $currentElement['icon'] != '') {
				$structure[$key]['iconId'] = $iconId; 
				
				$numElement++;
			}

			//walk through recursively
			if ($currentElement['type'] == 'menu') {
				$structure[$key]['menu'] =& $this->parseIconIds($iconId, $currentElement['menu']);
			}
		}
		
		return $structure;
	} 

	/**
	 * Calculates menu width.
	 * 
	 * This is essentially a hack, as we guess the relationship of small vs. wide characters.
	 * 
	 * @param string $longestName The longest name in the sub-Menu.
	 * @return string A correct value for CSS property width
	 */
	private function calcMenuWidth($longestName) {
		//return ((int) ((strlen($longestName) * 1.3) + 3)) . 'ex';
		//patched the jsDOMenuBar code to accept auto
		return 'auto';
	}
	
	/**
	 * Translates internal javascript command to the one used by jsDOMenuBar.
	 * 
	 * @param string $command A command in the format of $structure
	 * @return string The command translated to jsDOMenuBar format.
	 */
	private function calcCommand($command) {
		if (substr($command, 0, 11) != 'javascript:') {
			return $command;
		} else {
			return 'code:' . addslashes(substr($command, 11));
		}
	}
	
	/**
	 * Recursively translates the internal $structure to JavaScript calls suited to jsDOMenuBar.
	 * 
	 * @param string $menuName The name of this sub-menu
	 * @param array $structure The sub-structure inside this sub-menu
	 * @return string The JavaScript calls for jsDOMMenuBar
	 */
	private function renderSubMenu($menuName, $structure) {

		$longestName = '';
		$menuNum = 0;
		
		$result = '';
		
		foreach ($structure as $currentElement) {
			$currentId = "{$menuName}_{$menuNum}";
			
			switch($currentElement['type']) {
				case 'separator':
					//add separator
					$result .= "$menuName.addMenuItem(new menuItem('-'));\n";
					break;
				
				case 'item':
					//add MenuItem
					$result .= "$menuName.addMenuItem(new menuItem('$currentElement[name]', '$currentId', '" . $this->calcCommand($currentElement['command']) . "'));\n";
					
					//add icon, if defined
					if (isset($currentElement['iconId'])) {
						$result .= "$menuName.items.$currentId.showIcon('$currentElement[iconId]');\n";
					}

					//calculate longest name
					if (strlen($longestName) < strlen($currentElement['name'])) {
						$longestName = $currentElement['name'];
					}

					$menuNum++;
					break;
					
				case 'menu':
					//add sub-menu
					$result .= "$menuName.addMenuItem(new menuItem('$currentElement[name]', '$currentId'));\n";
					$result .= $this->RenderSubMenu($currentId, $currentElement['menu']);;
					$result .= "$menuName.items.$currentId.setSubMenu($currentId);\n";

					//add icon, if defined
					if (isset($currentElement['iconId'])) {
						$result .= "$menuName.items.$currentId.showIcon('$currentElement[iconId]');\n";
					}
					
					//calculate longest name
					if (strlen($longestName) < strlen($currentElement['name'])) {
						$longestName = $currentElement['name'];
					}

					$menuNum++;
					break;
			}
		}
		
		//we know only now how wide the menu should be, but the calls above refer to this JS object.
		//Therefore we prepend the Menu creation call to $result
		$result = "$menuName = new jsDOMenu('" . $this->calcMenuWidth($longestName) . "', 'absolute');\n" . $result;
		
		return $result;
	}
}
?>