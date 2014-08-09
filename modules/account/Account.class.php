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

require_once BADGER_ROOT . '/core/common.php';
require_once BADGER_ROOT . '/core/XML/DataGridHandler.class.php';
require_once BADGER_ROOT . '/core/Amount.class.php';
require_once BADGER_ROOT . '/modules/account/Currency.class.php';
require_once BADGER_ROOT . '/core/Date.php';
require_once BADGER_ROOT . '/modules/account/AccountManager.class.php';
require_once BADGER_ROOT . '/modules/account/CurrencyManager.class.php';
require_once BADGER_ROOT . '/modules/account/FinishedTransaction.class.php';
require_once BADGER_ROOT . '/modules/account/PlannedTransaction.class.php';
require_once BADGER_ROOT . '/modules/account/accountCommon.php';

/**
 * An (financial) Account.
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1185 $
 */
class Account extends DataGridHandler {
	
	/**
	 * List of valid field names.
	 * 
	 * @var array
	 */
	private $fieldNames = array (
		'transaction' => array (
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
			'periodical'
		),
		'planned' => array (
			'plannedTransactionId',
			'title',
			'description',
			'amount',
			'outsideCapital',
			'transactionPartner',
			'beginDate',
			'endDate',
			'repeatUnit',
			'repeatFrequency',
			'repeatText',
			'categoryId',
			'categoryTitle',
			'parentCategoryId',
			'parentCategoryTitle',
		),
		'finished' => array (
			'finishedTransactionId',
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
			'exceptional',
			'periodical'
		)
	);

	/**
	 * The ID of the account in the database.
	 * 
	 * @var integer
	 */
	private $id;
	
	/**
	 * The title of the account.
	 * 
	 * @var string
	 */
	private $title;
	
	/**
	 * The description of the account.
	 * 
	 * @var string
	 */
	private $description;
	
	/**
	 * The lower limit of the account (for alerting).
	 * 
	 * @var object Amount
	 */
	private $lowerLimit;

	/**
	 * The upper limit of the account (for alerting).
	 * 
	 * @var object Amount
	 */
	private $upperLimit;

	/**
	 * The current balance of the account.
	 * 
	 * @var object Amount
	 */
	private $balance;
	
	/**
	 * The currency of the account.
	 * 
	 * @var object Currency.
	 */
	private $currency;
	
	/**
	 * The date up to when we should calculate planned transactions.
	 * 
	 * @var object Date
	 */
	private $targetFutureCalcDate;
	
	private $lastCalcDate;
	
	private $csvParser;
	
	private $deleteOldPlannedTransactions;

	private static $plannedTransactionsExpanded = array();

	/**
	 * List of planned transactions.
	 * 
	 * @var array
	 */
	private $plannedTransactions = array();
	
	/**
	 * List of finished (and, after call to expandPlannedTransactions(), the expanded planned transactions).
	 * 
	 * @var array
	 */
	private $finishedTransactions = array();
	
	/**
	 * list of all properties
	 * 
	 * @var array
	 */
	private $properties;

	/**
	 * Type of requested data (all transactions / only planned / only finished).
	 * 
	 * @var string
	 */
	private $type = null;
	
	/**
	 * The AccountManager who created this Account.
	 * 
	 * @var object AccountManager
	 */
	private $accountManager;
	
	/**
	 * Has the query to finished transactions been executed?
	 * 
	 * @var bool
	 */
	private $finishedDataFetched = false;
	
	private $fetchOnlyFinishedData = false;
	
	/**
	 * Has the query to planned transactions been executed?
	 * 
	 * @var bool
	 */
	private $plannedDataFetched = false;

	/**
	 * Has all finished data been fetched from the DB?
	 * 
	 * @var bool
	 */
	private $allFinishedDataFetched = false;
	
	/**
	 * Has all planned data been fetched from the DB?
	 * 
	 * @var bool
	 */
	private $allPlannedDataFetched = false;
	
	/**
	 * Has the planned data been expanded?
	 * 
	 * @var bool
	 */
	private $plannedDataExpanded = false;
	
	/**
	 * The key of the current finished data element.
	 * 
	 * @var mixed string (if expanded) or integer  
	 */
	private $currentFinishedTransaction = null;

	/**
	 * The key of the current planned data element.
	 * 
	 * @var integer
	 */
	private $currentPlannedTransaction = null;

	/**
	 * The result object of the finished transaction DB query.
	 * 
	 * @var object
	 */
	private $dbResultFinished;

	/**
	 * The result object of the planned transaction DB query.
	 * 
	 * @var object
	 */
	private $dbResultPlanned;
	
	/**
	 * Creates an Account.
	 * 
	 * @param $badgerDb object The DB object.
	 * @param $accountManager mixed The AccountManager object who created this Account OR the qp part out of getDataGridXML.php.
	 * @param $data mixed An associative array with the values out of the DB OR the id of the Account.
	 * @param $title string The title of the Account.
	 * @param $description string The description of the Account.
	 * @param $lowerLimit object An Amount object with the lower limit of the Account.
	 * @param $upperLimit object An Amount object with the upper limit of the Account.
	 * @param $currency object An Currency object with the currency of the Account.
	 */
	function __construct(
		&$badgerDb,
		&$accountManager,
		$data = null,
		$title = null,
		$description = null,
		$lowerLimit = null,
		$upperLimit = null,
		$currency = null,
		$csvParser = null,
		$deleteOldPlannedTransactions = null,
		$expandPlannedTransactions = true
	) {
		global $us;
		
		$this->badgerDb = $badgerDb;
		
		$this->targetFutureCalcDate = getTargetFutureCalcDate();			
		
		$this->type = 'transaction';

		if (!is_string($accountManager)) {
			//called with data array or all parameters
			$this->accountManager = $accountManager;
			
			if (is_array($data)) {
				//called with data array
				$this->id = $data['account_id'];
				$this->title = $data['title'];
				$this->description = $data['description'];
				$this->lowerLimit = new Amount($data['lower_limit']);
				$this->upperLimit = new Amount($data['upper_limit']);
				$this->balance = new Amount($data['balance']);
				$this->lastCalcDate = new Date($data['last_calc_date']);
				$this->csvParser = $data['csv_parser'];
				$this->deleteOldPlannedTransactions = $data['delete_old_planned_transactions'];
				
				if ($data['currency_id']) {
					$currencyManager = new CurrencyManager($badgerDb);
					$this->currency = $currencyManager->getCurrencyById($data['currency_id']);
				}
				
				$expandPlannedTransactions = $title;
			} else {
				//called with all parameters
				$this->id = $data;
				$this->title = $title;
				$this->description = $description;
				$this->lowerLimit = $lowerLimit;
				$this->upperLimit = $upperLimit;
				$this->currency = $currency;
				$this->balance = new Amount(0);
				$this->lastCalcDate = new Date('1000-01-01');
				$this->csvParser = $csvParser;
				$this->deleteOldPlannedTransactions = $deleteOldPlannedTransactions;
			}
		} else {
			//called from getDataGridXML.php
			$this->accountManager = new AccountManager($badgerDb);

			//Filter out given parameters
			list($selectedId, $type, $targetDays) = explode(';', $accountManager . ';;');
			settype($selectedId, 'integer');
			if (in_array($type, array('transaction', 'finished', 'planned'), true)) {
				$this->type = $type; 
			}
			
			settype($targetDays, 'integer');
			if ($targetDays) {
				$this->targetFutureCalcDate = new Date();
				$this->targetFutureCalcDate->addSeconds($targetDays * 24 * 60 * 60);
			}
			
			//copy account data
			$tmpAccount = $this->accountManager->getAccountById($selectedId);

			$this->id = $tmpAccount->getId();
			$this->title = $tmpAccount->getTitle();
			$this->description = $tmpAccount->getDescription();
			$this->lowerLimit = $tmpAccount->getLowerLimit();
			$this->upperLimit = $tmpAccount->getUpperLimit();
			$this->balance = $tmpAccount->getBalance();
			$this->currency = $tmpAccount->getCurrency();
			$this->lastCalcDate = $tmpAccount->getLastCalcDate();
			$this->csvParser = $tmpAccount->getCsvParser();
			$this->deleteOldPlannedTransactions = $tmpAccount->getDeleteOldPlannedTransactions();
		}

		//Get all properties
    	$sql = "SELECT prop_key, prop_value
			FROM account_property
			WHERE account_id = " . $this->id;
		
		$res =& $badgerDb->query($sql);

		$this->properties = array();
		
		$row = array();
		
		while ($res->fetchInto($row, DB_FETCHMODE_ASSOC)) {
			$this->properties[$row['prop_key']] = $row['prop_value'];
		}

		if ($expandPlannedTransactions) {
			$this->expandPlannedTransactions();
		}
	}
	
	/**
	 * Checks if a field named $fieldName exists in this object.
	 * 
	 * @param string $fieldName The name of the field in question.
	 * @return boolean true if this object has this field, false otherwise.
	 */
	public function hasField($fieldName) {
		
		return in_array($fieldName, $this->fieldNames[$this->type], true);
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
			'plannedTransactionId' => 'integer',
			'finishedTransactionId' => 'integer',
			'type' => 'string',
			'title' => 'string',
			'description' => 'string',
			'valutaDate' => 'date',
			'beginDate' => 'date',
			'endDate' => 'date',
			'amount' => 'amount',
			'outsideCapital' => 'boolean',
			'transactionPartner' => 'string',
			'categoryId' => 'integer',
			'categoryTitle' => 'string',
			'parentCategoryId' => 'integer',
			'parentCategoryTitle' => 'string',
			'concatCategoryTitle' => 'string',
			'repeatUnit' => 'string',
			'repeatFrequency' => 'integer',
			'repeatText' => 'string',
			'exceptional' => 'boolean',
			'periodical' => 'boolean',
			'sum' => 'amount',
			'balance' => 'amount'
		);
	
		if (!isset ($fieldTypes[$fieldName])){
			throw new BadgerException('Account', 'invalidFieldName', $fieldName); 
		}
		
		return $fieldTypes[$fieldName];    	
	}

	public function getAllFieldNames() {
		return $this->fieldNames[$this->type];
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
			'transaction' => array (
				'transactionId' => 'ft2.transaction_id',
				'type' => 'ft2.__TYPE__',
				'title' => 'ft2.title',
				'description' => 'ft2.description',
				'valutaDate' => 'ft2.valuta_date',
				'amount' => 'ft2.amount',
				'outsideCapital' => 'ft2.outside_capital',
				'transactionPartner' => 'ft2.transaction_partner',
				'categoryId' => 'ft2.category_id',
				'categoryTitle' => 'ft2.category_title',
				'parentCategoryId' => 'ft2.parent_category_id',
				'parentCategoryTitle' => 'ft2.parent_category_title',
				'concatCategoryTitle' => 'CONCAT(IF(NOT ft2.parent_category_title IS NULL, CONCAT(ft2.parent_category_title, \' - \'), \'\'), IF(ft2.category_title IS NULL, \'\', ft2.category_title))'					,
				'sum' => 'ft2.__SUM__',
				'balance' => 'ft2.balance',
				'plannedTransactionId' => 'ft2.planned_transaction_id',
				'exceptional' => 'ft2.exceptional',
				'periodical' => 'ft2.periodical'
			),
			'planned' => array (
				'plannedTransactionId' => 'pt.planned_transaction_id',
				'title' => 'pt.title',
				'description' => 'pt.description',
				'amount' => 'pt.amount',
				'outsideCapital' => 'pt.outside_capital',
				'transactionPartner' => 'pt.transaction_partner,',
				'beginDate' => 'pt.begin_date',
				'endDate' => 'pt.end_date',
				'repeatUnit' => 'pt.repeat_unit',
				'repeatFrequency' => 'pt.repeat_frequency',
				'repeatText' => 'CONCAT(pt.repeat_unit, pt_repeat_frequency)',
				'categoryId' => 'pt.category_id',
				'categoryTitle' => 'c.title',
				'parentCategoryId' => 'pc.category_id',
				'parentCategoryTitle' => 'pc.title'
			),
			'finished' => array (
				'finishedTransactionId' => 'ft2.transaction_id',
				'title' => 'ft2.title',
				'description' => 'ft2.description',
				'valutaDate' => 'ft2.valuta_date',
				'amount' => 'ft2.amount',
				'outsideCapital' => 'ft2.outside_capital',
				'transactionPartner' => 'ft2.transaction_partner',
				'categoryId' => 'ft2.category_id',
				'categoryTitle' => 'ft2.category_title',
				'parentCategoryId' => 'ft2.parent_category_id',
				'parentCategoryTitle' => 'ft2.parent_category_title',
				'concatCategoryTitle' => 'CONCAT(IF(NOT ft2.parent_category_title IS NULL, CONCAT(ft2.parent_category_title, \' - \'), \'\'), IF(ft2.category_title IS NULL, \'\', ft2.category_title))'					,
				'exceptional' => 'ft2.exceptional',
				'periodical' => 'ft2.periodical'
			)
		);
	
		if (!isset ($fieldSQLNames[$this->type][$fieldName])){
			throw new BadgerException('Account', 'invalidFieldName', $fieldName); 
		}
		
		return $fieldSQLNames[$this->type][$fieldName];    	
	}
	
	public function getIdFieldName() {
		switch ($this->type) {
			case 'transaction':
				return 'transactionId';
			
			case 'planned':
				return 'plannedTransactionId';
			
			case 'finished':
				return 'finishedTransactionId';
		}
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

		switch ($this->type) {
			case 'transaction':
				return $this->getAllTransaction();
			
			case 'finished':
				return $this->getAllFinished();
			
			case 'planned':
				return $this->getAllPlanned();
		}
	}

	private function getAllTransaction() {

		$this->fetchTransactions();

		return getAllTransactions($this->finishedTransactions, $this->selectedFields, $this->order, $this->upperLimit, $this->lowerLimit);
	}
	
	private function getAllFinished() {
		while ($this->fetchNextFinishedTransaction());
		
		$result = array();
		$currResultIndex = 0;
	
		foreach($this->finishedTransactions as $currentTransaction){
			$classAmount = ($currentTransaction->getAmount()->compare(0) >= 0) ? 'dgPositiveAmount' : 'dgNegativeAmount'; 
	
			$category = $currentTransaction->getCategory();
			if (!is_null($category)) {
				$parentCategory = $category->getParent();
			} else {
				$parentCategory = null;
			}
	
			if ($parentCategory) {
				$concatCategoryTitle = $parentCategory->getTitle() . ' - ';
			} else {
				$concatCategoryTitle = '';
			}
			if ($category) {
				$concatCategoryTitle .= $category->getTitle();
			}
	
			$result[$currResultIndex] = array();
			$result[$currResultIndex]['finishedTransactionId'] = $currentTransaction->getId(); 
	
			foreach ($this->selectedFields as $selectedField) {
				switch ($selectedField) {
					case 'accountTitle':
						$result[$currResultIndex]['accountTitle'] = $currentTransaction->getAccount()->getTitle();
						break;
					
					case 'title':
						$result[$currResultIndex]['title'] = $currentTransaction->getTitle();
						break;
					
					case 'description':
						$result[$currResultIndex]['description'] = $currentTransaction->getDescription();
						break;
				
					case 'valutaDate':
						$result[$currResultIndex]['valutaDate'] = ($tmp = $currentTransaction->getValutaDate()) ? $tmp->getFormatted() : '';
						break;
						
					case 'amount':
						$result[$currResultIndex]['amount'] = array (
							'class' => $classAmount,
							'content' => $currentTransaction->getAmount()->getFormatted()
						);
						break;
					
					case 'outsideCapital':
						$result[$currResultIndex]['outsideCapital'] = is_null($tmp = $currentTransaction->getOutsideCapital()) ? '' : $tmp;
						break;
					
					case 'transactionPartner':
						$result[$currResultIndex]['transactionPartner'] = $currentTransaction->getTransactionPartner();
						break;
						
					case 'categoryId':
						$result[$currResultIndex]['categoryId'] = ($category) ? $category->getId() : '';
						break;
					
					case 'categoryTitle':
						$result[$currResultIndex]['categoryTitle'] = ($category) ? $category->getTitle() : '';
						break;
					
					case 'parentCategoryId':
						$result[$currResultIndex]['parentCategoryId'] = ($parentCategory) ? $parentCategory->getId() : '';
						break;
					
					case 'parentCategoryTitle':
						$result[$currResultIndex]['parentCategoryTitle'] = ($parentCategory) ? $parentCategory->getTitle() : '';
						break;
					
					case 'concatCategoryTitle':
						$result[$currResultIndex]['concatCategoryTitle'] = $concatCategoryTitle;
						break;
					
					case 'exceptional':
						$result[$currResultIndex]['exceptional'] = is_null($tmp = $currentTransaction->getExceptional()) ? '' : $tmp;
						break;
					
					case 'periodical':
						$result[$currResultIndex]['periodical'] = is_null($tmp = $currentTransaction->getPeriodical()) ? '' : $tmp;
						break;
				} //switch
			} //foreach selectedFields
			
			$currResultIndex++;
		} //foreach finishedTransactions
		
		return $result;
	}

	private function getAllPlanned() {
		$result = array();
		$currResultIndex = 0;

		while ($this->fetchNextPlannedTransaction());

		foreach($this->plannedTransactions as $currentTransaction){
			$classAmount = ($currentTransaction->getAmount()->compare(0) >= 0) ? 'dgPositiveAmount' : 'dgNegativeAmount'; 
	
			$category = $currentTransaction->getCategory();
			if (!is_null($category)) {
				$parentCategory = $category->getParent();
			} else {
				$parentCategory = null;
			}
	
			$result[$currResultIndex] = array();
			$result[$currResultIndex]['plannedTransactionId'] = 'p' . $currentTransaction->getId() . '_X';
	
			foreach ($this->selectedFields as $selectedField) {
				switch ($selectedField) {
					case 'title':
						$result[$currResultIndex]['title'] = $currentTransaction->getTitle();
						break;
					
					case 'description':
						$result[$currResultIndex]['description'] = $currentTransaction->getDescription();
						break;
				
					case 'amount':
						$result[$currResultIndex]['amount'] = array (
							'class' => $classAmount,
							'content' => $currentTransaction->getAmount()->getFormatted()
						);
						break;
					
					case 'outsideCapital':
						$result[$currResultIndex]['outsideCapital'] = is_null($tmp = $currentTransaction->getOutsideCapital()) ? '' : $tmp;
						break;
					
					case 'transactionPartner':
						$result[$currResultIndex]['transactionPartner'] = $currentTransaction->getTransactionPartner();
						break;
						
					case 'beginDate':
						$result[$currResultIndex]['beginDate'] = $currentTransaction->getBeginDate()->getFormatted();
						break;
						
					case 'endDate':
						$result[$currResultIndex]['endDate'] = ($tmp = $currentTransaction->getEndDate()) ? $tmp->getFormatted() : '';
						break;
						
					case 'repeatUnit':
						 $result[$currResultIndex]['repeatUnit'] = getBadgerTranslation2('Account', $currentTransaction->getRepeatUnit());
						 break;
					
					case 'repeatFrequency':
						$result[$currResultIndex]['repeatFrequency'] = $currentTransaction->getRepeatFrequency();
						break;
						
					case 'repeatText':
						$result[$currResultIndex]['repeatText'] = 
							$this->ordinal($currentTransaction->getRepeatFrequency())
							. ' '
							. getBadgerTranslation2('Account', 'text' . $currentTransaction->getRepeatUnit())
						;
						break;
						
					case 'categoryId':
						$result[$currResultIndex]['categoryId'] = ($category) ? $category->getId() : '';
						break;
					
					case 'categoryTitle':
						$result[$currResultIndex]['categoryTitle'] = ($category) ? $category->getTitle() : '';
						break;
					
					case 'parentCategoryId':
						$result[$currResultIndex]['parentCategoryId'] = ($parentCategory) ? $parentCategory->getId() : '';
						break;
					
					case 'parentCategoryTitle':
						$result[$currResultIndex]['parentCategoryTitle'] = ($parentCategory) ? $parentCategory->getTitle() : '';
						break;
					
				} //switch
			} //foreach selectedFields
			
			$currResultIndex++;
		} //foreach plannedTransactions
		
		return $result;
	}

	/**
	 * Returns the finished transaction identified by $finishedTransactionId.
	 * 
	 * @param $finishedTransactionId integer The id of the requested finished transaction.
	 * @throws BadgerException If $finishedTransactionId is unknown to the DB.
	 * @return object FinishedTransaction object of the finished transaction identified by $finishedTransactionId.
	 */
	public function getFinishedTransactionById($finishedTransactionId, $transferalTransaction = null){
		settype($finishedTransactionId, 'integer');

		if ($this->finishedDataFetched) {
			if (isset($this->finishedTransactions[$finishedTransactionId])) {
				return $this->finishedTransactions[$finishedTransactionId];
			}
//			while ($currentTransaction = $this->fetchNextFinishedTransaction($transferalTransaction)) {
//				if ($currentTransaction->getId() === $finishedTransactionId) {
//					
//					return $currentTransaction;
//				}
//			}
		}
		
		$sql = "SELECT * FROM (
					SELECT *, (@balance := @balance + ft1.amount) balance FROM (
						SELECT ft.finished_transaction_id, ft.title, ft.description, ft.valuta_date, ft.amount, 
							ft.outside_capital, ft.transaction_partner, ft.category_id, ft.exceptional,
							ft.periodical, ft.planned_transaction_id, ft.transferal_transaction_id, 
							ft.transferal_source
						FROM finished_transaction ft
						WHERE finished_transaction_id = $finishedTransactionId
						ORDER BY ft.valuta_date ASC
					) ft1
				) ft2\n"
		;
				
		$this->dbResultFinished =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($this->dbResultFinished)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $this->dbResultFinished->getMessage());
		}
		
		$tmp = $this->finishedDataFetched;
		$this->finishedDataFetched = true;
		
		$currentTransaction = $this->fetchNextFinishedTransaction($transferalTransaction);
		
		$this->finishedDataFetched = $tmp;
		
		if($currentTransaction){
			return $currentTransaction;
		} else {
			$this->allFinishedDataFetched = false;	
			throw new BadgerException('Account', 'UnknownFinishedTransactionId', $finishedTransactionId);
		}
	}
	
	/**
	 * Deletes the finished transaction identified by $finishedTransactionId.
	 * 
	 * @param $finishedTransactionId integer The id of the finished transaction to delete.
	 * @throws BadgerException If $finishedTransactionId is unknown to the DB.
	 */
	public function deleteFinishedTransaction($finishedTransactionId){
		settype($finishedTransactionId, 'integer');

		$sql= "DELETE FROM finished_transaction
				WHERE finished_transaction_id = $finishedTransactionId
					OR transferal_transaction_id = $finishedTransactionId"
		;
		
//		if (
//			!is_null($tmp = $this->getFinishedTransactionById($finishedTransactionId))
//			&& !is_null($tmp2 = $tmp->getTransferalTransaction())
//		) {
//			$sql .= " OR finished_transaction_id = " . $tmp2->getId();
//		}		
		
				
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() < 1){
			throw new BadgerException('Account', 'UnknownFinishedTransactionId', $finishedTransactionId);
		}

		//We should clean up the TransferalTransaction out of the corresponding Account object, but this is complex

		if(isset($this->finishedTransactions[$finishedTransactionId])){
			unset($this->finishedTransactions[$finishedTransactionId]);
		}
	}
	
	/**
	 * Adds a new finished transaction to this account.
	 * 
	 * @param $amount object Amount object with the amount of the new finished transaction.
	 * @param $title string Title of the new finished transaction.
	 * @param $description string Description of the new finished transaction.
	 * @param $valutaDate object Date object with the valuta date of the new finished transaction.
	 * @param $transactionPartner string Transaction partner of the new finished transaction.
	 * @param $category object Category object with the category of the new finished transaction.
	 * @param $outsideCapital bool True if the new finished transaction is outside capital, false otherwise.
	 * @throws BadgerException If an error occured while inserting.
	 * @return object The new FinishedTransaction object.
	 */
	public function addFinishedTransaction(
		$amount,
		$title = null,
		$description = null,
		$valutaDate = null,
		$transactionPartner = null,
		$category = null,
		$outsideCapital = null,
		$exceptional = null,
		$periodical = null,
		$plannedTransaction = null,
		$transferalAccount = null,
		$transferalAmount = null,
		$transferalTransaction = null
	) {
		$finishedTransactionId = $this->badgerDb->nextId('finished_transaction_ids');
		
		$sql = "INSERT INTO finished_transaction
			(finished_transaction_id, account_id, amount ";
			
		if ($title) {
			$sql .= ", title";
		}

		if($description){
			$sql .= ", description";
		}
		
		if($valutaDate){
			$sql .= ", valuta_date";
		}
		
		if($transactionPartner){
			$sql .= ", transaction_partner";
		}
		
		if ($category) {
			$sql .= ", category_id";
		}
		
		if ($outsideCapital) {
			$sql .= ", outside_capital";
		}
		
		if ($exceptional) {
			$sql .= ", exceptional";
		}
		
		if ($periodical) {
			$sql .= ", periodical";
		}
		
		if ($plannedTransaction) {
			$sql .= ", planned_transaction_id";
		}
		
		$sql .= ")
			VALUES ($finishedTransactionId, " . $this->id . ", '" . $amount->get() . "'";
	
		if ($title) {
			$sql .= ", '" . $this->badgerDb->escapeSimple($title) . "'";
		}

		if($description){
			$sql .= ", '".  $this->badgerDb->escapeSimple($description) . "'";
		}
	
		if($valutaDate){
			$sql .= ", '".  $valutaDate->getDate() . "'";
		}
			
		if($transactionPartner){
			$sql .= ", '".  $this->badgerDb->escapeSimple($transactionPartner) . "'";
		}
		
		if($category) {
			$sql .= ", " . $category->getId();
		}
		
		if ($outsideCapital) {
			 $sql .= ", " . $this->badgerDb->quoteSmart($outsideCapital);
		}
		
		if ($exceptional) {
			 $sql .= ", " . $this->badgerDb->quoteSmart($exceptional);
		}
		
		if ($periodical) {
			 $sql .= ", " . $this->badgerDb->quoteSmart($periodical);
		}
		
		if ($plannedTransaction) {
			$sql .= ", " . $plannedTransaction->getId();
		}
		
		$sql .= ")";
		
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() != 1){
			throw new BadgerException('Account', 'insertError', $dbResult->getMessage());
		}
		
		if (is_null($plannedTransaction)) {
			if (is_null($transferalAccount) && is_null($transferalTransaction)) {
				$type = 'FinishedTransaction';
			} else {
				$type = 'FinishedTransferalTransaction';
			}
		} else {
			if (is_null($transferalAccount) && is_null($transferalTransaction)) {
				$type = 'PlannedTransaction';
			} else {
				$type = 'PlannedTransferalTransaction';
			}
		}
		
		$newTransaction = new FinishedTransaction(
			$this->badgerDb,
			$this,
			$finishedTransactionId,
			$title,
			$amount,
			$description,
			$valutaDate,
			$transactionPartner,
			$category,
			$outsideCapital,
			$exceptional,
			$periodical,
			$plannedTransaction,
			$type
		);
		$this->finishedTransactions[$finishedTransactionId] = $newTransaction;
		
		if ($transferalTransaction) {
			$newTransaction->setTransferalTransaction($transferalTransaction);
		} else {
			if (!is_null($transferalAccount)) {
				if (!is_null($plannedTransaction)) {
					$transferalPlannedTransaction = $plannedTransaction->getTransferalTransaction();
				} else {
					$transferalPlannedTransaction = null;
				}			
			
				$transferalTransaction = $transferalAccount->addFinishedTransaction(
					$transferalAmount,
					$title,
					$description,
					$valutaDate,
					$transactionPartner,
					$category,
					$outsideCapital,
					$exceptional,
					$periodical,
					$transferalPlannedTransaction,
					null,
					null,
					$newTransaction
				);				

				$newTransaction->setTransferalTransaction($transferalTransaction);
				$newTransaction->setTransferalSource(true);
			}
		}

		return $newTransaction;	
	}

	/**
	 * Returns the planned transaction identified by $plannedTransactionId.
	 * 
	 * @param $plannedTransactionId integer The id of the requested planned transaction.
	 * @throws BadgerException If $plannedTransactionId is unknown to the DB.
	 * @return object PlannedTransaction object of the planned transaction identified by $plannedTransactionId.
	 */
	public function getPlannedTransactionById($plannedTransactionId, $transferalTransaction = null){
		$plannedTransactionId = PlannedTransaction::sanitizeId($plannedTransactionId);

		if ($this->plannedDataFetched) {
			if (isset($this->plannedTransactions[$plannedTransactionId])) {
				return $this->plannedTransactions[$plannedTransactionId];
			}
//			while ($currentTransaction = $this->fetchNextPlannedTransaction($transferalTransaction)) {
//				if ($currentTransaction->getId() === $plannedTransactionId) {
//					
//					return $currentTransaction;
//				}
//			}
		}	
		$sql = "SELECT pt.planned_transaction_id, pt.title, pt.description, pt.amount, 
				pt.outside_capital, pt.transaction_partner, pt.begin_date, pt.end_date, pt.repeat_unit, 
				pt.repeat_frequency, pt.category_id, pt.transferal_transaction_id, pt.transferal_source
			FROM planned_transaction pt 
			WHERE pt.account_id = $this->id
				AND planned_transaction_id = " .  $plannedTransactionId;
		
		$this->dbResultPlanned =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($this->dbResultPlanned)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $this->dbResultPlanned->getMessage());
		}
		
		$tmp = $this->plannedDataFetched;
		$this->plannedDataFetched = true;
		
		$currentTransaction = $this->fetchNextPlannedTransaction($transferalTransaction);
		
		$this->plannedDataFetched = $tmp;
		
		if($currentTransaction){
			return $currentTransaction;
		} else {
			$this->allPlannedDataFetched = false;	
			throw new BadgerException('Account', 'UnknownPlannedTransactionId', $plannedTransactionId);
		}
	}
	
	/**
	 * Deletes the planned transaction identified by $plannedTransactionId.
	 * 
	 * @param $plannedTransactionId integer The id of the planned transaction to delete.
	 * @throws BadgerException If $plannedTransactionId is unknown to the DB.
	 */
	public function deletePlannedTransaction($plannedTransactionId){
		$plannedTransactionId = PlannedTransaction::sanitizeId($plannedTransactionId);

		$sql = "SELECT transferal_transaction_id 
			FROM planned_transaction
			WHERE planned_transaction_id = $plannedTransactionId"
		;
		
		$dbResult =& $this->badgerDb->query($sql);
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n". $dbResult->getMessage());
		}
		
		$dbResult->fetchInto($row, DB_FETCHMODE_ASSOC);
		$transferalId = $row['transferal_transaction_id'];
		
		$sql= "DELETE FROM planned_transaction
				WHERE planned_transaction_id = $plannedTransactionId
					OR transferal_transaction_id = $plannedTransactionId"
		;
				
//		$transferalId = null;
//		if (
//			!is_null($tmp = $this->getPlannedTransactionById($plannedTransactionId))
//			&& !is_null($tmp2 = $tmp->getTransferalTransaction())
//		) {
//			$transferalId = $tmp2->getId();
//			$sql .= " OR planned_transaction_id = $transferalId";
//		}		
		$dbResult =& $this->badgerDb->query($sql);
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n". $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() < 1){
			throw new BadgerException('Account', 'UnknownPlannedTransactionId', $plannedTransactionId);
		}
		
		$sql = "DELETE FROM finished_transaction
					WHERE planned_transaction_id = $plannedTransactionId"
		;
		if (!is_null($transferalId)) {
			$sql .= " OR planned_transaction_id = $transferalId";
		}		
		$dbResult =& $this->badgerDb->query($sql);
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}

		//We should clean up the TransferalTransaction out of the corresponding Account object, but this is complex
		
		if(isset($this->plannedTransactions[$plannedTransactionId])){
			unset($this->plannedTransactions[$plannedTransactionId]);
		}
	}
	
	/**
	 * Adds a new planned transaction to this account.
	 * 
	 * @param $title string Title of the new planned transaction.
	 * @param $amount object Amount object with the amount of the new planned transaction.
	 * @param $repeatUnit string The repeat unit (day, week, month, year) of the new planned transaction.
	 * @param $repeatFrequency integer The repeat frequency of the new planned transaction.
	 * @param $beginDate object Date object with the begin date of the new planned transaction.
	 * @param $endDate object Date object with the end date of the new planned transaction.
	 * @param $description string Description of the new planned transaction.
	 * @param $transactionPartner string Transaction partner of the new planned transaction.
	 * @param $category object Category object with the category of the new planned transaction.
	 * @param $outsideCapital bool True if the new planned transaction is outside capital, false otherwise.
	 * @throws BadgerException If an error occured while inserting.
	 * @return object The new PlannedTransaction object.
	 */
	public function addPlannedTransaction(
		$title,
		$amount,
		$repeatUnit,
		$repeatFrequency,
		$beginDate,
		$endDate = null,
		$description = null,
		$transactionPartner = null,
		$category = null,
		$outsideCapital = null,
		$transferalAccount = null,
		$transferalAmount = null,
		$transferalTransaction = null
	) {
		$plannedTransactionId = $this->badgerDb->nextId('planned_transaction_ids');
		
		$sql = "INSERT INTO planned_transaction
			(planned_transaction_id, account_id, title, amount, repeat_unit, repeat_frequency, begin_date ";
			
		if ($endDate) {
			$sql .= ", end_date";
		}

		if($description){
			$sql .= ", description";
		}
		
		if($transactionPartner){
			$sql .= ", transaction_partner";
		}
		
		if ($category) {
			$sql .= ", category_id";
		}
		
		if ($outsideCapital) {
			$sql .= ", outside_capital";
		}
		
		$sql .= ")
			VALUES ($plannedTransactionId, " . $this->id . ", '" . $this->badgerDb->escapeSimple($title) . "', '" . $amount->get() . "', '" . $this->badgerDb->escapeSimple($repeatUnit) . "', " . $repeatFrequency . ", '" . $beginDate->getDate() . "'";  
	
		if($endDate){
			$sql .= ", '".  $endDate->getDate() . "'";
		}
			
		if($description){
			$sql .= ", '".  $this->badgerDb->escapeSimple($description) . "'";
		}
	
		if($transactionPartner){
			$sql .= ", '".  $this->badgerDb->escapeSimple($transactionPartner) . "'";
		}
		
		if($category) {
			$sql .= ", " . $category->getId();
		}
		
		if ($outsideCapital) {
			 $sql .= ", " . $this->badgerDb->quoteSmart($outsideCapital);
		}
		
		$sql .= ")";
		
		
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
		
		if($this->badgerDb->affectedRows() != 1){
			throw new BadgerException('Account', 'insertError', $dbResult->getMessage());
		}
		
		if (is_null($transferalAccount) && is_null($transferalTransaction)) {
			$type = 'PlannedTransaction';
		} else {
			$type = 'PlannedTransferalTransaction';
		}

		$newTransaction = new PlannedTransaction(
			$this->badgerDb,
			$this,
			$plannedTransactionId,
			$repeatUnit,
			$repeatFrequency,
			$beginDate,
			$endDate,
			$title,
			$amount, 
			$description,
			$transactionPartner,
			$category,
			$outsideCapital,
			$type
		);
		
		$this->plannedTransactions[$plannedTransactionId] = $newTransaction;

		if ($transferalTransaction) {
			$newTransaction->setTransferalTransaction($transferalTransaction);
		} else {
			if (!is_null($transferalAccount)) {
			
				$transferalTransaction = $transferalAccount->addPlannedTransaction(
					$title,
					$transferalAmount,
					$repeatUnit,
					$repeatFrequency,
					$beginDate,
					$endDate,
					$description,
					$transactionPartner,
					$category,
					$outsideCapital,
					null,
					null,
					$newTransaction
				);				

				$newTransaction->setTransferalTransaction($transferalTransaction);
				$newTransaction->setTransferalSource(true);
			}
		}

    	return $newTransaction;	
	}
	
	public function deleteAllTransactions() {
		$this->deleteAllFinishedTransactions();
		$this->deleteAllPlannedTransactions();
	}
	
	public function deleteAllFinishedTransactions() {
		while ($currentFinishedTransaction = $this->getNextFinishedTransaction()) {
			$this->deleteFinishedTransaction($currentFinishedTransaction->getId());
		}
	}

	public function deleteAllPlannedTransactions() {
		while ($currentPlannedTransaction = $this->getNextPlannedTransaction()) {
			$this->deletePlannedTransaction($currentPlannedTransaction->getId());
		}
	}

	/**
	 * Returns the ID.
	 * 
	 * @return integer The ID of this account.
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Returns the title.
	 * 
	 * @return string The title of this account.
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * Sets the title.
	 * 
	 * @param $title string The title of this account.
	 */
	public function setTitle($title) {
		$this->title = $title;
		
		$sql = "UPDATE account
			SET title = '" . $this->badgerDb->escapeSimple($title) . "'
			WHERE account_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	/**
	 * Returns the description.
	 * 
	 * @return string The description of this account.
	 */
	public function getDescription() {
		return $this->description;
	}
	
	/**
	 * Sets the description.
	 * 
	 * @param $description string The description of this account.
	 */
	public function setDescription($description) {
		$this->description = $description;
		
		$sql = "UPDATE account
			SET description = '" . $this->badgerDb->escapeSimple($description) . "'
			WHERE account_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	/**
	 * Returns the lower limit.
	 * 
	 * @return object An Amount with the lower limit of this account.
	 */
	public function getLowerLimit() {
		return $this->lowerLimit;
	}
	
	/**
	 * Sets the lower limit.
	 * 
	 * @param $lowerLimit object Amount object with the lower limit of this account. 
	 */
	public function setLowerLimit($lowerLimit) {
		$this->lowerLimit = $lowerLimit;
		
		$sql = "UPDATE account
			SET lower_limit = '" . $lowerLimit->get() . "'
			WHERE account_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	/**
	 * Returns the upper limit.
	 * 
	 * @return object An Amount with the upper limit of this account.
	 */
	public function getUpperLimit() {
		return $this->upperLimit;
	}
	
	/**
	 * Sets the upper limit.
	 * 
	 * @param $upperLimit object Amount object with the upper limit of this account. 
	 */
	public function setUpperLimit($upperLimit) {
		$this->upperLimit = $upperLimit;
		
		$sql = "UPDATE account
			SET upper_limit = '" . $upperLimit->get() . "'
			WHERE account_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	/**
	 * Returns the current balance.
	 * 
	 * @return object An Amount with the current amount of this account.
	 */
	public function getBalance() {
		return $this->balance;
	}
	
	/**
	 * Returns the currency.
	 * 
	 * @return object The Currency of this account.
	 */
	public function getCurrency() {
		return $this->currency;
	}

	/**
	 * Sets the currency.
	 * 
	 * @param $currency object Currency object with the currency of this account.
	 */
	public function setCurrency($currency) {
		$this->currency = $currency;
		
		$sql = "UPDATE account
			SET currency_id = '" . $currency->getId() . "'
			WHERE account_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	/**
	 * Returns the date up to when the planned transactions will be expanded.
	 * 
	 * @return object A Date object with the date up to when the planned transactions will be expanded.
	 */
	public function getTargetFutureCalcDate() {
		return $this->targetFutureCalcDate;
	}
	
	/**
	 * Sets the date up to when the planned transactions will be expanded.
	 * 
	 * @param $date object A Date object with the date up to when the planned transactions will be expanded.
	 */
	public function setTargetFutureCalcDate($date) {
		$this->targetFutureCalcDate = $date;
	}
	
	public function getLastCalcDate() {
		return $this->lastCalcDate;
	}
	
	protected function setLastCalcDate($date) {
		$this->lastCalcDate = $date;
		
		$sql = "UPDATE account
			SET last_calc_date = '" . $date->getDate() . "'
			WHERE account_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}

	public function getCsvParser() {
		return $this->csvParser;
	}
	
	public function setCsvParser($csvParser) {

		if (strtoupper($csvParser) === 'NULL') {
			$value = 'NULL';
			$this->csvParser = null;
		} else {
			$value = "'" . $this->badgerDb->escapeSimple($csvParser) . "'";
			$this->csvParser = $csvParser;
		} 

		$sql = "UPDATE account
			SET csv_parser = $value
			WHERE account_id = " . $this->id;
	

		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	public function getDeleteOldPlannedTransactions() {
		return $this->deleteOldPlannedTransactions;
	}
	
	public function setDeleteOldPlannedTransactions($deleteOldPlannedTransactions) {
		$this->deleteOldPlannedTransactions = $deleteOldPlannedTransactions;
		
		$sql = "UPDATE account
			SET delete_old_planned_transactions = " . $this->badgerDb->quoteSmart($deleteOldPlannedTransactions) . "
			WHERE account_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
	}
	
	/**
	 * Expands the planned transactions.
	 * 
	 * All occurences of planned transactions between now and the targetFutureCalcDate will be inserted
	 * in finishedTransactions. For distinction the planned transactions will have a 'p' as first character
	 * in their id.
	 * 
	 * @throws BadgerException If an illegal repeat unit is used.
	 */
	public function expandPlannedTransactions($startDate = null) {
		if (
			!isset(self::$plannedTransactionsExpanded[$this->id])
			|| self::$plannedTransactionsExpanded[$this->id] === false
		) {
			self::$plannedTransactionsExpanded[$this->id] = true;

			$now = new Date();
			$now->setHour(0);
			$now->setMinute(0);
			$now->setSecond(0);
			
			if (
				$this->lastCalcDate->before($now)
				|| !is_null($startDate)
			) {		
				$accountManager = new AccountManager($this->badgerDb);
				$plannedAccount = $accountManager->getAccountById($this->id);
				
				while($currentPlannedTransaction = $plannedAccount->getNextPlannedTransaction()) {
					if (is_null($startDate)) {
						$startDate = $this->lastCalcDate;
					}

					$currentPlannedTransaction->expand($startDate, $this->targetFutureCalcDate);
					$currentPlannedTransaction->deletePlannedTransactions(new Date('1000-01-01'), $now);
				}
				
				$this->setLastCalcDate($now);
			}

		self::$plannedTransactionsExpanded[$this->id] = false;
		}

	}

	/**
	 * Resets the internal counter of finished transactions.
	 */
	public function resetFinishedTransactions() {
		reset($this->finishedTransactions);
		$this->currentFinishedTransaction = null;
	}
	
	/**
	 * Resets the internal counter of planned transactions.
	 */
	public function resetPlannedTransaction() {
		reset($this->plannedTransactions);
		$this->currentPlannedTransaction = null;
	}
	
	/**
	 * Returns the next finished transaction.
	 * 
	 * @return mixed The next FinishedTransaction object or false if we are at the end of the list.
	 */
	public function getNextFinishedTransaction() {
		if (!$this->allFinishedDataFetched) {
			$this->fetchOnlyFinishedData = true;
			$this->fetchNextFinishedTransaction();
		}

		return nextByKey($this->finishedTransactions, $this->currentFinishedTransaction);
	}
	
	/**
	 * Returns the next planned transaction.
	 * 
	 * @return mixed The next PlannedTransaction object or false if we are at the end of the list.
	 */
	public function getNextPlannedTransaction() {
		if (!$this->allPlannedDataFetched) {
			$this->fetchNextPlannedTransaction();
		}

		return nextByKey($this->plannedTransactions, $this->currentPlannedTransaction);
	}
	
	/**
	 * Returns the next transaction.
	 * 
	 * Essentially the same as getNextFinishedTransaction, but first fetches all planned transactions
	 * and expands them.
	 * 
	 * @return mixed The next FinishedTransaction object or false if we are at the end of the list.
	 */
	public function getNextTransaction() {
		$this->fetchTransactions();
		
		return nextByKey($this->finishedTransactions, $this->currentFinishedTransaction);
	}
	
    /**
     * reads out the property defined by $key
     * 
     * @param string $key key of the requested value
     * @throws BadgerException if unknown key is passed
     * @return mixed the value referenced by $key
     */
    public function getProperty($key) {
    	if (isset($this->properties[$key])) {
    		return $this->properties[$key];
    	} else {
    		throw new BadgerException('Account', 'illegalPropertyKey', $key);
    	}
    }
    
    /**
     * sets property $key to $value
     * 
     * @param string $key key of the target value
     * @param string $value the value referneced by $key 
     * @return void
     */
    public function setProperty($key, $value) {
       	if (isset($this->properties[$key])) {
    		$sql = "UPDATE account_property
				SET prop_value = '" . $this->badgerDb->escapeSimple($value) . "'
				WHERE prop_key = '" . $this->badgerDb->escapeSimple($key) . "'
					AND account_id = " . $this->id;
    		
    		$this->badgerDb->query($sql);
       	} else {
       		$sql = "INSERT INTO account_property (prop_key, account_id, prop_value)
				VALUES ('" . $this->badgerDb->escapeSimple($key) . "', "
				. $this->id . ", 
				'" . $this->badgerDb->escapeSimple($value) . "')";
				
			$this->badgerDb->query($sql);	
    		
       	}

       	$this->properties[$key] = $value;
    }

	/**
	 * deletes property $key
	 * 
	 * @param string $key key of the target value
	 * @throws BadgerException if unknown key is passed
	 * @return void 
	 */
 	public function delProperty($key) {
		if (isset($this->properties[$key])) {
    		$sql = "DELETE FROM account_property
				WHERE prop_key = '" . $this->badgerDb->escapeSimple($key) . "'
					AND account_id = " . $this->id;
				
    		
    		$this->badgerDb->query($sql);
			  		
    		unset ($this->properties[$key]);
    	} else {
    		throw new BadgerException('Account', 'illegalPropertyKey', $key);
    	}
    }

	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Fetches all planned and finished transactions, expands the planned transactions and sorts the finishedTransaction array.
	 */
	private function fetchTransactions() {

		if ($this->allPlannedDataFetched && $this->allFinishedDataFetched && $this->plannedDataExpanded) {
			return;
		}
		while ($this->fetchNextFinishedTransaction());
		
		//while ($this->fetchNextPlannedTransaction());

		//$this->expandPlannedTransactions();
		
		//$compare = new CompareTransaction($this->order);
		
		//uasort($this->finishedTransactions, array($compare, 'compare'));
		
		$this->plannedDataExpanded = true;
	}
	
	/**
	 * Fetches the next finished transaction from DB.
	 * 
	 * @return mixed The fetched FinishedTransaction object or false if there are no more.
	 */
	private function fetchNextFinishedTransaction($transferalTransaction = null) {
		$this->fetchFinishedFromDB();

		$row = false;
		
		if($this->dbResultFinished->fetchInto($row, DB_FETCHMODE_ASSOC)){
			$this->finishedTransactions[$row['finished_transaction_id']] = new FinishedTransaction(
				$this->badgerDb,
				$this,
				$row,
				$transferalTransaction
				);
			return $this->finishedTransactions[$row['finished_transaction_id']];
		} else {
			$this->allFinishedDataFetched = true;
			return false;    	
		}
		
	}

	/**
	 * Fetches the next planned transaction from DB.
	 * 
	 * @return mixed The fetched PlannedTransaction object or false if there are no more.
	 */
	private function fetchNextPlannedTransaction($transferalTransaction = null) {
		$this->fetchPlannedFromDB();

		$row = false;
		
		if($this->dbResultPlanned->fetchInto($row, DB_FETCHMODE_ASSOC)){
			$this->plannedTransactions[$row['planned_transaction_id']] = new PlannedTransaction(
				$this->badgerDb,
				$this,
				$row,
				$transferalTransaction
			);
			return $this->plannedTransactions[$row['planned_transaction_id']];
		} else {
			$this->allPlannedDataFetched = true;
			return false;    	
		}
		
	}

	/**
	 * Prepares and executes the SQL query for finished transactions.
	 * 
	 * @throws BadgerException If an SQL error occured.
	 */
	private function fetchFinishedFromDB() {
		if($this->finishedDataFetched) {
			return;
		}
		
		if ($this->getTypeFilter() == 'planned') {
			return;
		}

		$sqlPrepare = 'SET @balance = 0';
		
		$order = $this->getOrderSQL();				
		$order = preg_replace('/ft2\.__SUM__ (asc|desc),*/', '', $order);
		$order = trim(preg_replace('/ft2\.__TYPE__ (asc|desc),*/', '', $order));
		if (substr($order, -1, 1) === ',') {
			$order = substr($order, 0, strlen($order) - 1);
		}
		
		$innerOrder = $order;
		$innerOrder = preg_replace('/ft2\.balance (asc|desc),*/', '', $innerOrder);
		$innerOrder = preg_replace('/ft2\.valuta_date (asc|desc),*/', '', $innerOrder);
		$innerOrder = str_replace('ft2.', '', $innerOrder);
		$innerOrder = trim($innerOrder);
		if (substr($innerOrder, -1, 1) === ',') {
			$innerOrder = substr($innerOrder, 0, strlen($innerOrder) - 1);
		}
		

		$sql = "SELECT * FROM (
					SELECT *, (@balance := @balance + ft1.amount) balance FROM (
						SELECT ft.finished_transaction_id, ft.title, ft.description, ft.valuta_date, ft.amount, 
							ft.outside_capital, ft.transaction_partner, ft.category_id, ft.exceptional,
							ft.periodical, ft.planned_transaction_id, ft.transferal_transaction_id, 
							ft.transferal_source, c.title category_title,
							pc.category_id parent_category_id, pc.title parent_category_title
						FROM finished_transaction ft
							LEFT OUTER JOIN category c ON ft.category_id = c.category_id
							LEFT OUTER JOIN category pc on c.parent_id = pc.category_id 
						WHERE account_id = " .  $this->id;
		if ($this->fetchOnlyFinishedData) {
			$sql .= ' AND ft.planned_transaction_id IS NULL';
		}
		$sql .= "
						ORDER BY ft.valuta_date ASC";
		if ($innerOrder) {
			$sql .= ", $innerOrder";
		}
		$sql .= "
					) ft1
				) ft2
		";
		
		$where = $this->getFilterSQL();
		$where = preg_replace('/ft2\.parent_category_id = ([0-9]+)/', '(ft2.category_id = \1 OR ft2.parent_category_id = \1)', $where);
		$where = preg_replace('/ft2\.parent_category_id != ([0-9]+)/', '((ft2.category_id IS NULL OR ft2.category_id != \1) AND (ft2.parent_category_id IS NULL OR ft2.parent_category_id != \1))', $where);
		//$where = preg_replace('/pc\\.title = (\'.*?[^\\\\]\')/', '(pc\\.title = \1 OR c\\.title = \1)', $where);
		$where = preg_replace('/ft2\.__SUM__[^\\n]+?(\$|\\n)/', "1=1\n", $where);
		$where = trim(preg_replace('/ft2\.__TYPE__[^\\n]+?(\$|\\n)/', "1=1\n", $where));
		if($where) {
			$sql .= " WHERE $where\n ";
		} 
		
		if($order) {
			$sql .= " ORDER BY $order\n ";
		}
		

		$this->badgerDb->query('SELECT @balance := 0');
		
		$result = $this->badgerDb->query($sqlPrepare);
		if (PEAR::isError($result)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $this->dbResultFinished->getMessage());
		}

		$this->dbResultFinished =& $this->badgerDb->query($sql);
		if (PEAR::isError($this->dbResultFinished)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $this->dbResultFinished->getMessage());
		}
		
		$this->finishedDataFetched = true; 	
	}

	/**
	 * Prepares and executes the SQL query for planned transactions.
	 * 
	 * @throws BadgerException If an SQL error occured.
	 */
	private function fetchPlannedFromDB() {
		if ($this->plannedDataFetched) {
			return;
		}
		
		if ($this->getTypeFilter() == 'finished') {
			return;
		}

		$sql = "SELECT pt.planned_transaction_id, pt.title, pt.description, pt.amount, 
				pt.outside_capital, pt.transaction_partner, pt.begin_date, pt.end_date, pt.repeat_unit, 
				pt.repeat_frequency, pt.category_id, pt.transferal_transaction_id, 
				pt.transferal_source, c.title category_title,
				pc.category_id parent_category_id, pc.title parent_category_title
			FROM planned_transaction pt
				LEFT OUTER JOIN category c ON pt.category_id = c.category_id
				LEFT OUTER JOIN category pc on c.parent_id = pc.category_id 
			WHERE pt.account_id = " .  $this->id . " ";
//				AND pt.begin_date <= '". $this->targetFutureCalcDate->getDate() . "'
//				AND pt.end_date > NOW()\n"; 	

		$where = $this->getFilterSQL();
		$where = str_replace('ft2.', 'pt.', $where);
		$where = preg_replace('/pt\.parent_category_id = ([0-9]+)/', '(pt.category_id = \1 OR pt.parent_category_id = \1)', $where);
		$where = preg_replace('/pt\.parent_category_id != ([0-9]+)/', '((pt.category_id IS NULL OR pt.category_id != \1) AND (pt.parent_category_id IS NULL OR pt.parent_category_id != \1))', $where);
		//$where = preg_replace('/pc\\.title = (\'.*?[^\\\\]\')/', '(pc\\.title = \1 OR c\\.title = \1)', $where);
		$where = preg_replace('/pt\.__TYPE__[^\\n]+?(\$|\\n)/', "1=1\n", $where);
		$where = preg_replace('/pt\.__SUM__[^\\n]+?(\$|\\n)/', "1=1\n", $where);
		$where = trim(preg_replace("/pt\.valuta_date[^\\n]+?(\$|\\n)/", "1=1\n", $where));
		if($where) {
			$sql .= " AND $where\n ";
		} 
		
		$order = $this->getOrderSQL();				
		$order = str_replace('ft2.', 'pt.', $order);
		$order = preg_replace('/pt\.__TYPE__ (asc|desc),*/', '', $order);
		$order = preg_replace('/pt\.__SUM__ (asc|desc),*/', '', $order);
		$order = trim(preg_replace('/pt\.valuta_date (asc|desc),*/', '', $order));
		
		if (substr($order, -1, 1) === ',') {
			$order = substr($order, 0, strlen($order) - 1);
		}
		
		if($order) {
			$sql .= " ORDER BY $order\n ";
		}
		
		$sql = str_replace('ft2.', 'pt.', $sql);
		$this->dbResultPlanned =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($this->dbResultPlanned)) {
			throw new BadgerException('Account', 'SQLError', "SQL: $sql\n" . $this->dbResultPlanned->getMessage());
		}
		
		$row = false;
		
		$this->plannedDataFetched = true;
	}
	
	private function getTypeFilter() {
		foreach ($this->filter as $currentFilter) {
			if ($currentFilter['key'] == 'type') {
				return getBadgerTranslation2('Account', $currentFilter['val']);
			}
		}
		
		return false;
	}
	
	private function ordinal($cardinal) {
		global $us;
		
		switch ($us->getProperty('badgerLanguage')) {
			case 'en':
				$cardinal = (int) $cardinal;
				$digit = substr($cardinal, -1, 1);
				$tens = round($cardinal/10);
				if($tens == 1) {
					return $cardinal . 'th';
				}
				
				switch($digit) {
					case 1:
						return $cardinal . 'st';
					case 2:
						return $cardinal . 'nd';
					case 3:
						return $cardinal . 'rd';
					default:
						return $cardinal . 'th';
				}
				break;
			
			case 'de':
				return "$cardinal.";
				break;
				
			default:
				throw new BadgerException('Account', 'unknownOrdinalisationLanguage', $us->getProperty('badgerLanguage'));
		}
   }
}
?>
