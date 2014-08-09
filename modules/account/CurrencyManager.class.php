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
require_once BADGER_ROOT . '/modules/account/Currency.class.php';
require_once BADGER_ROOT . '/core/common.php';

/**
 * Manages all Currencies.
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1024 $
 */
class CurrencyManager extends DataGridHandler {
	/**
	 * List of valid field names.
	 * 
	 * @var array
	 */
	private $fieldNames = array (
		'currencyId',
		'symbol',
		'longName'
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
	 * List of Currencies.
	 * 
	 * @var array of Currency
	 */
	private $currencies = array();
	
	/**
	 * The key of the current data element.
	 * 
	 * @var integer  
	 */
	private $currentCurrency = null;
	
	/**
	 * Creates a CurrencyManager.
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
			'currencyId' => 'integer',
			'symbol' => 'string',
			'longName' => 'string'
		);
	
		if (!isset ($fieldTypes[$fieldName])){
			throw new BadgerException('CurrencyManager', 'invalidFieldName', $fieldName); 
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
			'currencyId' => 'c.currency_id',
			'symbol' => 'c.symbol',
			'longName' => 'c.long_name'
		);
	
		if (!isset ($fieldTypes[$fieldName])){
			throw new BadgerException('CurrencyManager', 'invalidFieldName', $fieldName); 
		}
		
		return $fieldTypes[$fieldName];    	
	}

	public function getIdFieldName() {
		return 'currencyId';
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
		while($this->fetchNextCurrency());
		
		$result = array();
		$currResultIndex = 0;
		
		foreach($this->currencies as $currentCurrency){
			$result[$currResultIndex] = array();
			$result[$currResultIndex]['currencyId'] = $currentCurrency->getId(); 

			foreach ($this->selectedFields as $selectedField) {
				switch ($selectedField) {
					case 'symbol':
						$result[$currResultIndex]['symbol'] = $currentCurrency->getSymbol();
						break;
					
					case 'longName':
						$result[$currResultIndex]['longName'] = $currentCurrency->getLongName();
						break;
				} //switch
			} //foreach selectedFields
			
			$currResultIndex++;
		} //foreach currencies
		
		return $result;
	}
	
	/**
	 * Resets the internal counter of currency.
	 */
	public function resetCurrencies() {
		reset($this->currencies);
		$this->currentCurrency = null;
	}
	
	/**
	 * Returns the Currency identified by $currencyId.
	 * 
	 * @param integer $currencyId The ID of the requested Currency.
	 * @throws BadgerException SQLError If an SQL Error occurs.
	 * @throws BadgerException UnknownCurrencyId If $currencyId is not in the Database
	 * @return object The Currency object identified by $currencyId. 
	 */
	public function getCurrencyById($currencyId) {
		settype($currencyId, 'integer');

		if ($this->dataFetched){
			if(isset($this->currencies[$currencyId])) {
				return $this->currencies[$currencyId];
			}
			while ($currentCurrency = $this->fetchNextCurrency()) {
				if($currentCurrency->getId() == $currencyId) {
					return $currentCurrency;
				}
			}
		}	
		$sql = "SELECT c.currency_id, c.symbol, c.long_name
			FROM currency c
			WHERE c.currency_id = $currencyId";

		//echo "<pre>$sql</pre>";

		$this->dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($this->dbResult)) {
			//echo "SQL Error: " . $this->dbResult->getMessage();
			throw new BadgerException('CurrencyManager', 'SQLError', $this->dbResult->getMessage());
		}
		
		$tmp = $this->dataFetched;
		$this->dataFetched = true;
		
		$currentCurrency = $this->fetchNextCurrency();
		
		$this->dataFetched = $tmp;

		if($currentCurrency) {
			return $currentCurrency;
		} else {
			$this->allDataFetched = false;	
			throw new BadgerException('CurrencyManager', 'UnknownCurrencyId', $currencyId);
		}
	}
		
	/**
	 * Returns the next Currency.
	 * 
	 * @return mixed The next Currency object or false if we are at the end of the list.
	 */
	public function getNextCurrency() {
		if (!$this->allDataFetched) {
			$this->fetchNextCurrency();
		}

		return nextByKey($this->currencies, $this->currentCurrency);
	}

	/**
	 * Deletes the Currency identified by $currencyId.
	 * 
	 * @param integer $currencyId The ID of the Currency to delete.
	 * @throws BadgerException SQLError If an SQL Error occurs.
	 * @throws BadgerException UnknownCurrencyId If $currencyId is not in the Database
	 */
	public function deleteCurrency($currencyId){
		settype($currencyId, 'integer');

		if(isset($this->currencies[$currencyId])){
			unset($this->currencies[$currencyId]);
		}
		$sql= "DELETE FROM currency
				WHERE currency_id = $currencyId";
				
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('CurrencyManager', 'SQLError', $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() != 1){
			throw new BadgerException('CurrencyManager', 'UnknownCurrencyId', $currencyId);
		}
	}
	
	/**
	 * Creates a new Currency.
	 * 
	 * @param string $symbol The symbol of the new Currency.
	 * @param string $longName The long name of the new Currency.
	 * @throws BadgerException SQLError If an SQL Error occurs.
	 * @throws BadgerException insertError If the currency cannot be inserted.
	 * @return object The new Currency object.
	 */
	public function addCurrency($symbol, $longName) {
		$currencyId = $this->badgerDb->nextId('currency_ids');
		
		$sql = "INSERT INTO currency
			(currency_id, symbol, long_name) VALUES ($currencyId, '" . $this->badgerDb->escapeSimple($symbol) . "', '" . $this->badgerDb->escapeSimple($longName) . "')";
			
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('CurrencyManager', 'SQLError', $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() != 1){
			throw new BadgerException('CurrencyManager', 'insertError', $dbResult->getMessage());
		}
		
		$this->currencies[$currencyId] = new Currency($this->badgerDb, $this, $currencyId, $symbol, $longName);
		
		return $this->currencies[$currencyId];	
	}
	
	/**
	 * Prepares and executes the SQL query.
	 * 
	 * @throws BadgerException If an SQL error occured.
	 */
	private function fetchFromDB() {
		if($this->dataFetched){
			return;
		}
		
		$sql = "SELECT c.currency_id, c.symbol, c.long_name
			FROM currency c\n";
					
		$where = $this->getFilterSQL();
		if($where) {
			$sql .= "WHERE $where\n ";
		} 
		
		$order = $this->getOrderSQL();				
		if($order) {
			$sql .= "ORDER BY $order\n ";
		}
		
		$this->dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($this->dbResult)) {
			//echo "SQL Error: " . $sql;
			throw new BadgerException('CurrencyManager', 'SQLError', $this->dbResult->getMessage());
		}
		
		$this->dataFetched = true; 	
	}

	/**
	 * Fetches the next currency from DB.
	 * 
	 * @return mixed The fetched Currency object or false if there are no more.
	 */
	private function fetchNextCurrency() {
		$this->fetchFromDB();
		$row = false;
		
		if($this->dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)){

			//echo "<pre>"; print_r($row); echo "</pre>";

			$this->currencies[$row['currency_id']] = new Currency($this->badgerDb, $this, $row);
			return $this->currencies[$row['currency_id']];
		} else {
			$this->allDataFetched = true;
			return false;    	
		}
	}
}
?>