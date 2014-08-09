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

require_once (BADGER_ROOT . '/core/Amount.class.php');

/**
 * Defines the interface required for an DataGridHandler.
 * 
 * @see getDataGridXML.php
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1004 $
 */
abstract class DataGridHandler {
	
	/**
	 * The DB object.
	 * 
	 * @var object DB
	 */
	protected $badgerDb;
	
	/**
	 * All order criteria, structure described in setOrder().
	 * 
	 * @var array
	 */
	protected $order = array();
	
	/**
	 * All filter criteria, structure described in setFilter().
	 */
	protected $filter = array();
	
	protected $selectedFields = array();
	
	/**
	 * Initializes the DB object.
	 * 
	 * @param object $badgerDb The DB object.
	 */
	public function __construct($badgerDb, $params = null) {
		$this->badgerDb = $badgerDb;
	}
	
	/**
	 * Checks if a field named $fieldName exists in this object.
	 * 
	 * @param string $fieldName The name of the field in question.
	 * @return boolean true if this object has this field, false otherwise.
	 */
	public abstract function hasField($fieldName);
	
	/**
	 * Returns the field type of $fieldName.
	 * 
	 * @param string $fieldName The name of the field in question.
	 * @throws BadgerException If there is no field $fieldName.
	 * @return string The type of field $fieldName.
	 */
	public abstract function getFieldType($fieldName);
	
	/**
	 * Returns the SQL name of the given field.
	 * 
	 * @param $fieldName string The field name to get the SQL name of.
	 * @throws BadgerException If an unknown field name was given.
	 * @return The SQL name of $fieldName.
	 */
	public abstract function getFieldSQLName($fieldName);
	
	public abstract function getIdFieldName();
	
	/**
	 * Sets the order to return the results.
	 * 
	 * $order has the following structure:
	 * array (
	 *   array (
	 *     'key' => 'valid field name',
	 *     'dir' => either 'asc' or 'desc'
	 *   )
	 * );
	 * 
	 * The inner array can be repeated at most twice.
	 *
	 * @param array $order The order this object should return the results, in above form.
	 * @throws BadgerException If $order has the wrong format or an invalid field name was given.
	 * @return void
	 */
	public function setOrder($order){
		$this->order = array ();
		$numOrders = 0;
	
		if (!is_array($order)){
			throw new BadgerException('DataGridHandler', 'orderParamNoArray'); 
		}
	
		foreach ($order as $key => $val){
	   		if (!is_array($val)){
				throw new BadgerException('DataGridHandler', 'orderArrayElementNoArray', $key); 
			}
			if(!isset($val['key'])){
				throw new BadgerException('DataGridHandler', 'orderKeyIndexNotDefined', $key);
			}
			if(!isset($val['dir'])){
				throw new BadgerException('DataGridHandler', 'orderDirIndexNotDefined', $key);
			}
			
			if(!$this->hasField($val['key'])){
				throw new BadgerException('DataGridHandler', 'orderIllegalField', $val['key']);
			}
			if(strtolower($val['dir'])  != 'asc' && strtolower($val['dir'])  != 'desc'){
				throw new BadgerException('DataGridHandler', 'orderIllegalDirection', $val['dir']);
			}
			
			$this->order[] = array (
				'key' => $val['key'],
				'dir' => $val['dir']
			);
			
			$numOrders++;
			
			if ($numOrders >= 3) {
				break;
			}
		}
	}
	
	
	/**
	 * Sets the filter(s) to limit the results to.
	 * 
	 * $filter has the following structure:
	 * array (
	 *   array (
	 *     'key' => 'valid field name',
	 *     'op' => 'valid operator'
	 *     'val => comparison value
	 *   )
	 * );
	 * 
	 * The inner array can be repeated.
	 * 
	 * @param array $filter The filter(s) this object should return the results, in above form.
	 * @throws BadgerException If $filter has the wrong format or an invalid field name was given.
	 * @return void
	 */
	public function setFilter($filter) {
		$this->filter = array ();
	
		if (!is_array($filter)){
			throw new BadgerException('DataGridHandler', 'filterParamNoArray'); 
		}
	
		foreach ($filter as $key => $val){
	   		if (!is_array($val)){
				throw new BadgerException('DataGridHandler', 'filterArrayElementNoArray', $key); 
			}
			if(!isset($val['key'])){
				throw new BadgerException('DataGridHandler', 'filterKeyIndexNotDefined', $key);
			}
			if(!isset($val['op'])){
				throw new BadgerException('DataGridHandler', 'filterOpIndexNotDefined', $key);
			}
			if(!isset($val['val'])){
				throw new BadgerException('DataGridHandler', 'filterValIndexNotDefined', $key);
			}

			if(!$this->hasField($val['key'])){
				throw new BadgerException('DataGridHandler', 'filterIllegalField', $val['key']);
			}

			//We trust the caller to check op and val
			
			$this->filter[] = array (
				'key' => $val['key'],
				'op' => $val['op'],
				'val' => $val['val']
			);
		}
		
	}
	
	public function setSelectedFields($fields) {
		$this->selectedFields = array();
		
		$this->selectedFields[0] = $this->getIdFieldName();

		foreach ($fields as $field) {
			if (!$this->hasField($field)) {
				throw new BadgerException('DataGridHandler', 'illegalFieldSelected', $field);
			}
			
			if ($field != $this->getIdFieldName()) {
				$this->selectedFields[] = $field;
			}
		}
	}

	public function getFieldNames() {
		return $this->selectedFields;
	}
	
	/**
	 * Returns all valid field names.
	 * 
	 * @return array A list of all field names.
	 */
	public abstract function getAllFieldNames();

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
	public abstract function getAll();
	
	/**
	 * Returns the order critera as SQL string. The 'ORDER BY' clause is not included.
	 * 
	 * @return string The order criteria as SQL string.
	 */
	protected function getOrderSQL() {
		$result = '';
		$firstrun = true;
		
		foreach ($this->order as $val){
			if($firstrun) {
				$firstrun = false;
			} else {
				$result .= ', ';
			}
			$result .= $this->getFieldSQLName($val['key']) . ' ' . $val['dir'];
		}	
		
		return $result;    	
	}
	
	/**
	 * Returns the filter criteria as SQL string. The 'WHERE' clause is not included.
	 * 
	 * @return string The filter criteria as SQL string.
	 */
	protected function getFilterSQL() {
		$result = '';
		$firstrun = true;
		
		foreach ($this->filter as $val){
			if($firstrun) {
				$firstrun = false;
			} else {
				$result .= "\nAND ";
			}
			
			if ($val['op'] == 'bw'
				|| $val['op'] == 'ew'
				|| $val['op'] == 'ct'
			) {
				//we need to treat everything as string
				$result .= "LOWER(CONVERT(" . $this->getFieldSQLName($val['key']) . ", CHAR)) LIKE ";
				
				if ($val['val'] instanceof Amount) {
					$stringVal = $val['val']->getFormatted();
				} else if ($val['val'] instanceof Date) {
					$stringVal = $val['val']->getFormatted();
				} else {
					$stringVal = (string) strtolower($val['val']);
				}
				
				switch ($val['op']) {
					case 'bw':
	    				$result .= "'" . addslashes($stringVal) . "%'";
	    				break;
	    				
					case 'ew':
	    				$result .= "'%" . addslashes($stringVal) . "'";
	    				break;
	    				
					case 'ct': 	
	    				$result .= "'%" . addslashes($stringVal) . "%'";
	    				break;
				}
			} else {
				//standard comparison
				$result .= $this->getFieldSQLName($val['key']);

				switch ($val['op']) {
					case 'eq':
						$result .= ' = ';
						break;
						
					case 'lt':
						$result .= ' < ';
						break;
						
					case 'le':
						$result .= ' <= ';
						break;
						
					case 'gt':
						$result .= ' > ';
						break;
						
					case 'ge':
						$result .= ' >= ';
						break;
						
					case 'ne':
						$result .= ' != ';
						break;
				}
				$result .= $this->formatForDB($val['val'], $this->getFieldType($val['key']));
			}
		}	
		
		return $result;
	}
	
	/**
	 * Formats $val, which is of $type, ready for a SQL statement.
	 * 
	 * @param $val mixed The value that should be formatted.
	 * @param $type string The type of $val.
	 * @return string $val in a SQL-ready form.
	 */
	protected function formatForDB($val, $type) {
		switch ($type) {
			case 'int':
			case 'integer':
			case 'float':
			case 'double':
				return $val;
			
			case 'boolean':
			case 'bool':
				return ($val ? 1 : 0);

			case 'string':
				return "'" . addslashes($val) . "'";
				
			case 'Amount':
			case 'amount':
				return "'" . $val->get() . "'";
			
			case 'Date':
			case 'date':
				return "'" . $val->getDate() . "'";
		}
	}
}
?>