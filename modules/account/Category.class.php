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
 * A category (of transactions).
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1089 $
*/
class Category {
	/**
	 * The DB object.
	 * 
	 * @var object DB
	 */
	private $badgerDb;

	/**
	 * The CategoryManager this category belongs to.
	 * 
	 * @var object CategoryManager
	 */
	private $categoryManager;
	
	/**
	 * The id of this category.
	 * 
	 * @var integer
	 */
	private $id;
	
	/**
	 * The title of this category.
	 * 
	 * @var string
	 */
	private $title;
	
	/**
	 * The description of this category.
	 * 
	 * @var string
	 */
	private $description;
	
	/**
	 * The origin of this category.
	 * 
	 * @var boolean
	 */
	private $outsideCapital;
	
	/**
	 * The parent of this category, if any.
	 * 
	 * @var object Category
	 */
	private $parent;
	
	/**
	 * A list of all children of this category, if any.
	 * 
	 * @var array of Category
	 */
	private $children;
	
	private $keywords;
	
	private $expense;
	
	/**
	 * Creates a Category.
	 * 
	 * @param $badgerDb object The DB object.
	 * @param $categoryManager object The CategoryManager object who created this Category.
	 * @param $data mixed An associative array with the values out of the DB OR the id of the Category.
	 * @param $title string The title of the Category.
	 * @param $description string The description of the Category.
	 * @param $outsideCapital boolean The origin of the Category.
	 * @param $parent object Category object with the parent of the Category.
	 */
	public function __construct(
		&$badgerDb,
		&$categoryManager,
		$data,
		$title = null,
		$description = null,
		$outsideCapital = null,
		$parent = null,
		$keywords = null,
		$expense = null
	) {
		$this->badgerDb = $badgerDb;
		$this->categoryManager = $categoryManager;
		
    	if (is_array($data)) {
			$this->id = $data['category_id'];
			$this->title = $data['title'];
			$this->description = $data['description'];
			$this->outsideCapital = $data['outside_capital'];
			$this->parent = $data['parent_id'];
			$this->keywords = $data['keywords'];
			$this->expense = $data['expense'];
			if (!is_null($this->expense)) {
				settype($this->expense, 'boolean');				
			} 
    	} else {
    		$this->id = $data;
    		$this->title = $title;
    		$this->description = $description;
    		$this->outsideCapital = $outsideCapital;
    		$this->parent = $parent;
    		$this->keywords = $keywords;
    		$this->expense = $expense;
    	}
	}
	
	/**
	 * Returns the id.
	 * 
	 * @return integer The id of this category.
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Returns the title.
	 * 
	 * @return string The title of this category.
	 */
	public function getTitle() {
		return $this->title;
	}
	
 	/**
 	 * Sets the title.
 	 * 
 	 * @param $title string The title of this category.
 	 */
 	public function setTitle($title) {
		$this->title = $title;
		
		$sql = "UPDATE category
			SET title = '" . $this->badgerDb->escapeSimple($title) . "'
			WHERE category_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Category', 'SQLError', $dbResult->getMessage());
		}
	}

	/**
	 * Returns the description.
	 * 
	 * @return string The description of this category.
	 */
	public function getDescription() {
		return $this->description;
	}
	
 	/**
 	 * Sets the description.
 	 * 
 	 * @param $description string The description of this category.
 	 */
 	public function setDescription($description) {
		$this->description = $description;
		
		$sql = "UPDATE category
			SET description = '" . $this->badgerDb->escapeSimple($description) . "'
			WHERE category_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Category', 'SQLError', $dbResult->getMessage());
		}
	}

	/**
	 * Returns the origin.
	 * 
	 * @return boolean true if this category is outside capital.
	 */
	public function getOutsideCapital() {
		return $this->outsideCapital;
	}
	
 	/**
 	 * Sets the origin.
 	 * 
 	 * @param $outsideCapital boolean true if this category is outside capital.
 	 */
 	public function setOutsideCapital($outsideCapital) {
		$this->outsideCapital = $outsideCapital;
		
		$sql = "UPDATE category
			SET outside_capital = " . $this->badgerDb->quoteSmart($outsideCapital) . "
			WHERE category_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Category', 'SQLError', $dbResult->getMessage());
		}
	}

	public function getKeywords() {
		return $this->keywords;
	}
	
 	public function setKeywords($keywords) {
		$this->keywords = $keywords;
		
		$sql = "UPDATE category
			SET keywords = '" . $this->badgerDb->escapeSimple($keywords) . "'
			WHERE category_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Category', 'SQLError', $dbResult->getMessage());
		}
	}

	public function getExpense() {
		return $this->expense;
	}
	
 	public function setExpense($expense) {
		$this->expense = $expense;
		
		$sql = "UPDATE category
			SET expense = " . $this->badgerDb->quoteSmart($expense) . "
			WHERE category_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Category', 'SQLError', $dbResult->getMessage());
		}
	}

	/**
	 * Returns the parent.
	 * 
	 * @return object Category object with the parent of this category.
	 */
	public function getParent() {
		if (
			!($this->parent instanceof Category)
			&& $this->parent
		) {
			$this->parent = $this->categoryManager->getCategoryById($this->parent);
		}
		
		return $this->parent;
	}
	
	/**
	 * Sets the parent.
	 * 
	 * @param $parent object Category object with the parent of this category.
	 */
 	public function setParent($parent) {
		$this->parent = $parent;
		
		if (!is_null($parent)) {
			$parent->addChild($this);
			
			$sql = "UPDATE category
				SET parent_id = " . $parent->getId() . "
				WHERE category_id = " . $this->id;
		} else {
			$sql = "UPDATE category
				SET parent_id = NULL
				WHERE category_id = " . $this->id;
		}
	
		$dbResult =& $this->badgerDb->query($sql);
		//echo $sql;
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Category', 'SQLError', $dbResult->getMessage());
		}
	}

	/**
	 * Returns the children.
	 * 
	 * @return array List of Category objects with the children of this category.
	 */
	public function getChildren() {
		if (!is_array($this->children)) {
			$sql = "SELECT category_id
				FROM category
				WHERE parent_id = " . $this->id . " ORDER BY title ASC";
	
			$dbResult =& $this->badgerDb->query($sql);
			
			if (PEAR::isError($dbResult)) {
				//echo "SQL Error: " . $dbResult->getMessage();
				throw new BadgerException('Category', 'SQLError', $dbResult->getMessage());
			}
			
			$row = false;
			
			while($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
				$this->children[$row['category_id']] = $this->categoryManager->getCategoryByid($row['category_id']);
			}
		}

		return $this->children;
	}
	
	/**
	 * Adds a child.
	 * 
	 * Does no changes to the database, this is done in setParent().
	 * 
	 * @param $child object The Category object of the new child.
	 */
	private function addChild($child) {
		$this->children[$child->getId()] = $child;
	}
}
?>