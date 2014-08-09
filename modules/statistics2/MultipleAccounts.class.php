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
require_once BADGER_ROOT . '/core/XML/DataGridHandler.class.php';
require_once BADGER_ROOT . '/modules/account/Account.class.php';
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';

class MultipleAccounts extends DataGridHandler {
	private $fieldNames	= array (
		'transactionId',
		'type',
		'title',
		'description',
		'valutaDate',
		'amount',
		'outsideCapital',
		'transactionPartner',
		'categoryId',
		'categoryTitle',
		'parentCategoryId',
		'parentCategoryTitle',
		'concatCategoryTitle',
		'sum',
		'balance',
		'plannedTransactionId',
		'exceptional',
		'periodical',
		'accountTitle'
	);
	
	private $accountManager = null;
	
	private $accounts = array();
	
	private $transactions = array();
	
	private $dataFetched = false;
	
	private $currentTransaction = null;
	
	function __construct(&$badgerDb, $params = null) {
		$this->badgerDb = $badgerDb;
		
		$this->accountManager = new AccountManager($badgerDb);
		
		$accountIds = explode(',', $params);
		foreach ($accountIds as $key => $val) {
			settype($accountIds[$key], 'integer');
		}
		
		foreach ($accountIds as $currentAccountId) {
			$account = $this->accountManager->getAccountById($currentAccountId);
			$account->setType('finished');
			$this->accounts[] = $account;
		}
		
		$this->dataFetched = false;
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
			'transactionId' => 'integer',
			'type' => 'string',
			'title' => 'string',
			'description' => 'string',
			'valutaDate' => 'date',
			'amount' => 'amount',
			'outsideCapital' => 'boolean',
			'transactionPartner' => 'string',
			'categoryId' => 'integer',
			'categoryTitle' => 'string',
			'parentCategoryId' => 'integer',
			'parentCategoryTitle' => 'string',
			'concatCategoryTitle' => 'string',
			'exceptional' => 'boolean',
			'periodical' => 'boolean',
			'sum' => 'amount',
			'balance' => 'amount',
			'plannedTransactionId' => 'integer',
			'accountTitle' => 'string'
		);
	
		if (!isset ($fieldTypes[$fieldName])){
			throw new BadgerException('MultipleAccounts', 'invalidFieldName', $fieldName); 
		}
		
		return $fieldTypes[$fieldName];    	
	}

	public function getIdFieldName() {
		return 'transactionId';
	}
	
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
		$fieldSQLNames = array (
			'transactionId' => 'ft2.transaction_id',
			'type' => 'ft2.__TYPE__',
			'title' => 'ft2.title',
			'description' => 'ft2.description',
			'valutaDate' => 'ft2.valuta_date',
			'amount' => 'ft2.amount',
			'outsideCapital' => 'ft2.outside_capital',
			'transactionPartner' => 'ft2.transaction_parter',
			'categoryId' => 'ft2.category_id',
			'categoryTitle' => 'ft2.category_title',
			'parentCategoryId' => 'ft2.parent_category_id',
			'parentCategoryTitle' => 'ft2.parent_category_title',
			'concatCategoryTitle' => 'CONCAT(IF(NOT ft2.parent_category_title IS NULL, CONCAT(ft2.parent_category_title, \' - \'), \'\'), IF(ft2.category_title IS NULL, \'\', ft2.category_title))'					,
			'sum' => 'ft2.__SUM__',
			'balance' => 'ft2.balance',
			'plannedTransactionId' => 'ft2.planned_transaction_id',
			'exceptional' => 'ft2.exceptional',
			'periodical' => 'ft2.periodical',
			'accountTitle' => 'ERROR'
		);

		if (!isset ($fieldSQLNames[$fieldName])){
			throw new BadgerException('Account', 'invalidFieldName', $fieldName); 
		}
		
		return $fieldSQLNames[$this->type][$fieldName];    	
	}

	public function getAll() {
		$this->fetchTransactions();

		return getAllTransactions($this->transactions, $this->selectedFields, $this->order, null, null);
	}
	
	public function getNextTransaction() {
		$this->fetchTransactions();
		
		return nextByKey($this->transactions, $this->currentTransaction);
	}

	private function fetchTransactions() {
		if ($this->dataFetched) {
			return;
		}

		foreach($this->accounts as $currentAccount) {
			$newOrder = array();
			foreach ($this->order as $currentOrder) {
				if ($currentOrder['key'] != 'accountTitle') {
					$newOrder[] = $currentOrder;
				}
			}
			$currentAccount->setOrder($newOrder);

			$newFilter = array();
			foreach ($this->filter as $currentFilter) {
				if ($currentFilter['key'] != 'accountTitle') {
					$newFilter[] = $currentFilter;
				}
			}
			$currentAccount->setFilter($newFilter);
			
			while($currentTransaction = $currentAccount->getNextTransaction()) {
				$this->transactions[] = $currentTransaction;
			}
		}
		
		$compare = new CompareTransaction($this->order);
		
		uasort($this->transactions, array($compare, 'compare'));
	}
}
?>