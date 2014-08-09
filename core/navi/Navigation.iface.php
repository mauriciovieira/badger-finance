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
 * Provides the on-site Navigation.
 * 
 * @author Eni Kao, Paraphil
 * @version $LastChangedRevision: 869 $ 
 */
interface Navigation {
	
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
	public function setStructure($structure);
	
	/**
	 * Returns the required HTML header values to include
	 * 
	 * @return string All necessary HTML header tags
	 */
	public function getHeader();
	
	/**
	 * Returns the Navigation as HTML Fragment
	 * 
	 * @return string the Navigation HTML
	 */
	public function getHTML();
}
?>