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

require_once BADGER_ROOT . '/core/XML/DataGridHandler.class.php';
require_once BADGER_ROOT . '/modules/account/Category.class.php';
require_once BADGER_ROOT . '/core/common.php';

/**
 * Manages all Categories.
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1193 $
 */
class CategoryManager extends DataGridHandler {
	/**
	 * List of valid field names.
	 * 
	 * @var array
	 */
	private $fieldNames = array (
		'categoryId',
		'title',
		'description',
		'outsideCapital',
		'parentId',
		'parentTitle',
		'keywords',
		'expense'
	);
		
	/**
	 * Have the query been executed?
	 * 
	 * @var bool
	 */
	private $dataFetched = false;
	
	/**
	 * Has all data been fetched from the DB?
	 * 
	 * @var bool
	 */
	private $allDataFetched = false;
	
	/**
	 * The result object of the DB query.
	 * 
	 * @var object
	 */
	private $dbResult;
	
	/**
	 * List of Categories.
	 * 
	 * @var array of Category
	 */
	private $categories = array();
	
	/**
	 * The key of the current data element.
	 * 
	 * @var integer  
	 */
	private $currentCategory = null;
	
	private $fistRunNextCategory = true;
	
	/**
	 * Creates an CategoryManager.
	 * 
	 * @param $badgerDb object The DB object.
	 */
	function __construct ($badgerDb) {
		parent::__construct($badgerDb);
	}

	/**
	 * Checks if a field named $fieldName exists in this object.
	 * 
	 * @param string $fieldName The name of the field in question.
	 * @return boolean true if this object has this field, false otherwise.
	 */
	public function hasField($fieldName) {
		
		return in_array($fieldName, $this->fieldNames, true);
	}
	
	/**
	 * Returns the field type of $fieldName.
	 * 
	 * @param string $fieldName The name of the field in question.
	 * @throws BadgerException If there is no field $fieldName.
	 * @return string The type of field $fieldName.
	 */
	public function getFieldType($fieldName) {
		$fieldTypes = array (
			'categoryId' => 'integer',
			'title' => 'string',
			'description' => 'string',
			'outsideCapital' => 'boolean',
			'parentId' => 'integer',
			'parentTitle' => 'string',
			'keywords' => 'string',
			'expense' => 'boolean'
		);
	
		if (!isset ($fieldTypes[$fieldName])){
			throw new BadgerException('CategoryManager', 'invalidFieldName', $fieldName); 
		}
		
		return $fieldTypes[$fieldName];    	
	}
	
	
	/**
	 * Returns all valid field names.
	 * 
	 * @return array A list of all field names.
	 */
	public function getAllFieldNames() {
		return $this->fieldNames;
	}
	
	/**
	 * Returns the SQL name of the given field.
	 * 
	 * @param $fieldName string The field name to get the SQL name of.
	 * @throws BadgerException If an unknown field name was given.
	 * @return The SQL name of $fieldName.
	 */
	public function getFieldSQLName($fieldName) {
		$fieldTypes = array (
			'categoryId' => 'c.category_id',
			'title' => 'c.title',
			'description' => 'c.description',
			'outsideCapital' => 'c.outside_capital',
			'parentId' => 'c.parent_id',
			'parentTitle' => 'p.title',
			'keywords' => 'c.keywords',
			'expense' => 'c.expense'
		);
	
		if (!isset ($fieldTypes[$fieldName])){
			throw new BadgerException('CategoryManager', 'invalidFieldName', $fieldName); 
		}
		
		return $fieldTypes[$fieldName];    	
	}

	public function getIdFieldName() {
		return 'categoryId';
	}

	/**
	 * Returns all fields in an array.
	 * 
	 * The result has the following form:
	 * array (
	 *   array (
	 *     'field name 0' => 'value of field 0',
	 *     'field name 1' => 'value of field 1'
	 *   )
	 * );
	 * 
	 * The inner array is repeated for each row.
	 * The fields need to be in the order returned by @link getFieldNames().
	 * 
	 * @return array A list of all fields.
	 */
	public function getAll() {
		global $us;
		
		while ($this->fetchNextCategory());
		
		$this->sortCategories();
		
		$result = array();
		$currResultIndex = 0;
		
		$currentLanguage = $us->getProperty('badgerLanguage');
		
		foreach($this->categories as $currentCategory){
			$parent = $currentCategory->getParent();
			
			if (is_null($parent)) {
				$title = '';
				$parentId = '';
				$parentTitle = $currentCategory->getTitle();
			} else {
				$title = $currentCategory->getTitle();
				$parentId = $parent->getId();
				$parentTitle = $parent->getTitle();
			}
			
			if ($currentCategory->getOutsideCapital()) {
				$image = "Account/$currentLanguage/outside_capital.png";
				$tooltip = getBadgerTranslation2('CategoryManager', 'outsideCapital');
			} else {
				$image = "Account/$currentLanguage/own_capital.png";
				$tooltip = getBadgerTranslation2('CategoryManager', 'ownCapital');
			}
			
			$result[$currResultIndex] = array();
			$result[$currResultIndex]['categoryId'] = $currentCategory->getId(); 

			foreach ($this->selectedFields as $selectedField) {
				switch ($selectedField) {
					case 'title':
						$result[$currResultIndex]['title'] = $title;
						break;
					
					case 'description':
						$result[$currResultIndex]['description'] = $currentCategory->getDescription();
						break;
					
					case 'outsideCapital':
						$result[$currResultIndex]['outsideCapital'] = array (
							'img' => getRelativeTplPath($image),
							'title' => $tooltip
						);
						break;
					
					case 'parentId':
						$result[$currResultIndex]['parentId'] = $parentId;
						break;
					
					case 'parentTitle':
						$result[$currResultIndex]['parentTitle'] = $parentTitle;
						break;
					
					case 'keywords':
						$result[$currResultIndex]['keywords'] = $currentCategory->getKeywords();
						break;
					
					case 'expense':
						$result[$currResultIndex]['expense'] = $currentCategory->getExpense();
						break;
				} //switch
			} //foreach selectedFields
			
			$currResultIndex++;
		} //foreach categories
		
		return $result;
	}
	
	/**
	 * Resets the internal counter of category.
	 */
	public function resetCategories() {
		reset($this->categories);
		$this->currentCategory = null;
	}
	
	/**
	 * Returns the Category identified by $categoryId.
	 * 
	 * @param integer $categoryId The ID of the requested Category.
	 * @throws BadgerException SQLError If an SQL Error occurs.
	 * @throws BadgerException UnknownCategoryId If $categoryId is not in the Database
	 * @return object The Category object identified by $categoryId. 
	 */
	public function getCategoryById($categoryId) {
		settype($categoryId, 'integer');

		if ($this->dataFetched){
			if(isset($this->categories[$categoryId])) {
				return $this->categories[$categoryId];
			}
			while($currentCategory=$this->getNextCategory()){
				if($currentCategory->getId() == $categoryId){
					return $currentCategory;
				}
			}
		}	
		$sql = "SELECT c.category_id, c.parent_id, c.title, c.description, c.outside_capital, c.keywords, c.expense
			FROM category c
			WHERE c.category_id = $categoryId";

		//echo "<pre>$sql</pre>";

		$this->dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($this->dbResult)) {
			//echo "SQL Error: " . $this->dbResult->getMessage();
			throw new BadgerException('CategoryManager', 'SQLError', $this->dbResult->getMessage());
		}
		
		$tmp = $this->dataFetched;
		$this->dataFetched = true;
		
		$currentCategory = $this->fetchNextCategory();
		
		$this->dataFetched = $tmp;

		if($currentCategory) {
			return $currentCategory;
		} else {
			$this->allDataFetched = false;	
			throw new BadgerException('CategoryManager', 'UnknownCategoryId', $categoryId);
		}
	}
		
	/**
	 * Returns the next Category.
	 * 
	 * @return mixed The next Category object or false if we are at the end of the list.
	 */
	public function getNextCategory() {
		if ($this->fistRunNextCategory) {
			while (!$this->allDataFetched) {
				$this->fetchNextCategory();
			}
			
			$this->sortCategories();
			
			$this->fistRunNextCategory = false;
		}

		return nextByKey($this->categories, $this->currentCategory);
	}

	/**
	 * Deletes the Category identified by $categoryId.
	 * 
	 * @param integer $categoryId The ID of the Category to delete.
	 * @throws BadgerException SQLError If an SQL Error occurs.
	 * @throws BadgerException UnknownCategoryId If $categoryId is not in the Database
	 */
	public function deleteCategory($categoryId){
		settype($categoryId, 'integer');

		if(isset($this->categories[$categoryId])){
			unset($this->categories[$categoryId]);
		}
		$sql = "UPDATE finished_transaction ft
			INNER JOIN category c on ft.category_id = c.category_id
			SET ft.category_id = NULL
			WHERE ft.category_id = $categoryId
				OR c.parent_id = $categoryId"
		;
		$dbResult =& $this->badgerDb->query($sql);
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('CategoryManager', 'SQLError', $dbResult->getMessage());
		}
		
		$sql = "UPDATE planned_transaction pt
			INNER JOIN category c on pt.category_id = c.category_id
			SET pt.category_id = NULL
			WHERE pt.category_id = $categoryId
				OR c.parent_id = $categoryId"
		;
				$dbResult =& $this->badgerDb->query($sql);
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('CategoryManager', 'SQLError', $dbResult->getMessage());
		}
		
		$sql= "DELETE FROM category
				WHERE category_id = $categoryId
					OR parent_id = $categoryId";
				
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('CategoryManager', 'SQLError', $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() < 1){
			throw new BadgerException('CategoryManager', 'UnknownCategoryId', $categoryId);
		}
	}
	
	/**
	 * Creates a new Category.
	 * 
	 * @param string $title The title of the new Category.
	 * @param string $description The description of the new Category.
	 * @param boolean $outsideCapital The origin of the new Category.
	 * @throws BadgerException SQLError If an SQL Error occurs.
	 * @throws BadgerException insertError If the account cannot be inserted.
	 * @return object The new Account object.
	 */
	public function addCategory($title, $description = null, $outsideCapital = null, $keywords = null, $expense = null) {
		$categoryId = $this->badgerDb->nextId('category_ids');
		
		$sql = "INSERT INTO category
			(category_id, title ";
			
		if($description){
			$sql .= ", description";
		}
		
		if($outsideCapital){
			$sql .= ", outside_capital";
		}
		
		if ($keywords) {
			$sql .= ", keywords";
		}
		
		if (!is_null($expense)) {
			$sql .= ", expense";
		}

		$sql .= ")
			VALUES ($categoryId, '" . $this->badgerDb->escapeSimple($title) . "'";
	
		if($description){
			$sql .= ", '".  $this->badgerDb->escapeSimple($description) . "'";
		}
	
		if($outsideCapital){
			$sql .= ", ".  $this->badgerDb->quoteSmart($outsideCapital);
		}
		
		if ($keywords) {
			$sql .= ", '" . $this->badgerDb->escapeSimple($keywords) . "'";
		}
			
		if(!is_null($expense)){
			$sql .= ", ".  $this->badgerDb->quoteSmart($expense);
		}

		$sql .= ")";
		
		
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('CategoryManager', 'SQLError', $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() != 1){
			throw new BadgerException('CategoryManager', 'insertError', $dbResult->getMessage());
		}
		
		$this->categories[$categoryId] = new Category($this->badgerDb, $this, $categoryId, $title, $description, $outsideCapital, null, $keywords, $expense);
		
		return $this->categories[$categoryId];	
	}
	
	/**
	 * Prepares and executes the SQL query.
	 * 
	 * @throws BadgerException If an SQL error occured.
	 */
	private function fetchFromDB() {
		global $logger;
		
		if($this->dataFetched){
			return;
		}
		
		$sql = "SELECT c.category_id, c.parent_id, c.title, c.description, c.outside_capital, c.keywords, c.expense
			FROM category c
				LEFT OUTER JOIN category p ON c.parent_id = p.category_id
			";
					
		$where = $this->getFilterSQL();
		if($where) {
			$sql .= "WHERE $where\n ";
		} 
		
		$order = $this->getOrderSQL();				
		if($order) {
			$sql .= "ORDER BY $order\n ";
		}
		
		$this->dbResult =& $this->badgerDb->query($sql);

		$logger->log("CategoryManager::fetchFromDB SQL query: $sql");
		
		if (PEAR::isError($this->dbResult)) {
			//echo "SQL Error: " . $this->dbResult->getMessage();
			throw new BadgerException('CategoryManager', 'SQLError', $this->dbResult->getMessage());
		}
		
		$this->dataFetched = true; 	
	}

	/**
	 * Fetches the next category from DB.
	 * 
	 * @return mixed The fetched Category object or false if there are no more.
	 */
	private function fetchNextCategory() {
		$this->fetchFromDB();
		$row = false;
		
		if($this->dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)){

			//echo "<pre>"; print_r($row); echo "</pre>";

			$this->categories[$row['category_id']] = new Category($this->badgerDb, $this, $row);
			return $this->categories[$row['category_id']];
		} else {
			$this->allDataFetched = true;
			return false;    	
		}
	}
	
	function compareCategories($aa, $bb) {
		$tmp = 0;

		$default = 0;
		
		for ($run = 0; isset($this->order[$run]); $run++) {
			if ($this->order[$run]['dir'] == 'asc') {
				$a = $aa;
				$b = $bb;
				$default = -1;
			} else {
				$a = $bb;
				$b = $aa;
				$default = 1;
			}
			
			switch ($this->order[$run]['key']) {
				case 'categoryId':
					$tmp = $a->getId() - $b->getId();
					break;
				
				case 'title':
//					echo 'a: ' . print_r(is_null($a->getParent()), true);
//					echo ' b: ' . print_r(is_null($b->getParent()), true);
					if (is_null($a->getParent()) && is_null($b->getParent())) {
						$tmp = 0;
					} else if ($a->getParent() && is_null($b->getParent())) {
						$tmp = 1;
					} else if (is_null($a->getParent()) && $b->getParent()) {
						$tmp = -1;
					} else {
						$tmp = strncasecmp($a->getTitle(), $b->getTitle(), 9999);
					}
//					echo '<br />';
					break;

				case 'description':
					$tmp = strncasecmp($a->getDescription(), $b->getDescription(), 9999);
					break;
					
				case 'outsideCapital':
					$tmp = $a->getOutsideCapital() - $b->getOutsideCapital();
					break;
				
				case 'parentId':
					if ($a->getParent() && $b->getParent()) {
						$tmp = $a->getParent()->getId() - $b->getParent()->getId();
					} else if ($a->getParent() && !$b->getParent()) {
						$tmp = -1;
					} else if (!$a->getParent() && $b->getParent()) {
						$tmp = 1;
					}
					break;

				case 'parentTitle':				
					if (!is_null($a->getParent()) && !is_null($b->getParent())) {
						$tmp = strncasecmp($a->getParent()->getTitle() . $a->getTitle(), $b->getParent()->getTitle() . $b->getTitle(), 9999);
					} else if (!is_null($a->getParent()) && is_null($b->getParent())) {
						$tmp = strncasecmp($a->getParent()->getTitle() . $a->getTitle(), $b->getTitle(), 9999);
					} else if (is_null($a->getParent()) && !is_null($b->getParent())) {
						$tmp = strncasecmp($a->getTitle(), $b->getParent()->getTitle() . $b->getTitle(), 9999);
					} else if (is_null($a->getParent()) && is_null($b->getParent())) {
						$tmp = strncasecmp($a->getTitle(), $b->getTitle(), 9999);
					}
					break;
				
				case 'keywords':
					$tmp = strncasecmp($a->getKeywords(), $b->getKeywords(), 9999);
					break;
				
				case 'expense':
					$tmp = $a->getExpense() - $b->getExpense();
					break;
			}
			
			if ($tmp != 0) {
				return $tmp;
			}
		}

	return $default;
	}
	
	private function sortCategories() {
		uasort($this->categories, array('CategoryManager', 'compareCategories'));
	}
}
?>