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

require_once BADGER_ROOT . '/core/Date.php';
require_once BADGER_ROOT . '/core/Amount.class.php';
require_once BADGER_ROOT . '/modules/account/Category.class.php';
require_once BADGER_ROOT . '/modules/account/CategoryManager.class.php';

/**
 * A finished transaction.
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1037 $
*/
class PlannedTransaction {
	/**
	 * The DB object.
	 * 
	 * @var object DB
	 */
	private $badgerDb;

	/**
	 * The Account this category belongs to.
	 * 
	 * @var object Account
	 */
	private $account;
	
	/**
	 * The id of this transaction.
	 * 
	 * @var integer
	 */
	private $id;

	/**
	 * The title of this transaction.
	 * 
	 * @var string
	 */
	private $title;
	
	private $originalTitle;

	/**
	 * The description of this transaction.
	 * 
	 * @var string
	 */
	private $description;

	/**
	 * The amount of this transaction.
	 * 
	 * @var object Amount
	 */
	private $amount;

	/**
	 * The origin of this transaction.
	 * 
	 * @var boolean
	 */
	private $outsideCapital;

	/**
	 * The transaction partner of this transaction.
	 * 
	 * @var string
	 */
	private $transactionPartner;

	/**
	 * The category of this transaction.
	 * 
	 * @var object Category
	 */
	private $category;

	/**
	 * The begin date of this transaction.
	 * 
	 * @var object Date
	 */
	private $beginDate;
	
	private $beginDateLocked;
	
	private $originalBeginDate;

	/**
	 * The end date of this transaction.
	 * 
	 * @var object Date
	 */
	private $endDate;
	
	private $endDateLocked;
	
	private $originalEndDate;

	/**
	 * The repeat unit of this transaction.
	 * 
	 * day, week, month or year
	 * 
	 * @var string
	 */
	private $repeatUnit;

	private $originalRepeatUnit;

	/**
	 * The repeat frequency of this transaction.
	 * 
	 * @var integer
	 */
	private $repeatFrequency;
	
	private $originalRepeatFrequency;
	
	
	/**
	 * The type of this transaction.
	 * 
	 * 'FinishedTransaction' or 'PlannedTransaction' (a expanded one)
	 * 
	 * @var string
	 */
	private $type;
	
	private $updateMode;
	
	const UPDATE_MODE_ALL = 1;
	const UPDATE_MODE_PREVIOUS = 2;
	const UPDATE_MODE_FOLLOWING = 3;
	
	private $otherPlannedTransaction;
	
	private $updateSplitDate;
	
	private $transferalTransaction;
	
	private $transferalSource;
	
	/**
	 * Creates a Planned Transaction.
	 * 
	 * @param $badgerDb object The DB object.
	 * @param $account object The Account object who created this Transaction.
	 * @param $data mixed An associative array with the values out of the DB OR the id of the Transaction.
	 * @param $repeatUnit string The repeat unit of the Transaction.
	 * @param $repeatFrequency string The repeat frequency of the Transaction.
	 * @param $beginDate object The Date object with the begin date of the Transaction.
	 * @param $endDate object The Date object with the end date of the Transaction.
	 * @param $title string The title of the Transaction.
	 * @param $amount object The Amount object with the amount of this Transaction.
	 * @param $description string The description of the Transaction.
	 * @param $transactionPartner string The transaction partner of the Transaction
	 * @param $outsideCapital boolean The origin of the Transaction.
	 */
    function __construct(
    	&$badgerDb,
    	&$account,
    	$data,
    	$repeatUnit = null,
    	$repeatFrequency = null,
    	$beginDate = null,
    	$endDate = null,
    	$title = null,
    	$amount = null,
    	$description = null,
    	$transactionPartner = null,
    	$category = null,
    	$outsideCapital = null,
    	$type = 'PlannedTransaction',
		$transferalTransaction = null
    ) {
    	$CategoryManager = new CategoryManager($badgerDb);
    	
    	$this->badgerDb = $badgerDb;
    	$this->account = $account;
    	
    	if (is_array($data)) {
			$this->id = $data['planned_transaction_id'];
    		$this->title = $data['title'];
    		$this->description = $data['description'];
    		$this->amount = new Amount($data['amount']);
    		$this->outsideCapital = $data['outside_capital'];
    		$this->transactionPartner =  $data['transaction_partner'];
    		if ($data['category_id']) {
    			$this->category = $CategoryManager->getCategoryById($data['category_id']);
    		}
    		$this->beginDate = new Date($data['begin_date']);
    		if ($data['end_date']) {
    			$this->endDate = new Date($data['end_date']);
    		}
    		$this->repeatUnit = $data['repeat_unit'];
    		$this->repeatFrequency = $data['repeat_frequency'];
			$this->transferalSource = $data['transferal_source'];
			if (!$data['transferal_transaction_id']) {
				$this->type = 'FinishedTransaction';
			} else {
				$this->type = 'FinishedTransferalTransaction';
			}
			if ($data['transferal_transaction_id']) {
				if (!is_null($repeatUnit) && ($data['transferal_transaction_id'] == $repeatUnit->getId())) {
					$this->transferalTransaction = $repeatUnit;
				} else {
					$accountManager = new AccountManager($badgerDb);
					try {
						$transferalAccount = $accountManager->getAccountByPlannedTransactionId($data['transferal_transaction_id']);
						$this->transferalTransaction = $transferalAccount->getPlannedTransactionById($data['transferal_transaction_id'], $this);
					} catch (BadgerException $ex) {
						$this->transferalTransaction = null;
					}
				}
			}
    	} else {
    		$this->id = $data;
    		$this->title = $title;
    		$this->description = $description;
    		$this->amount = $amount;
    		$this->outsideCapital = $outsideCapital;
    		$this->transactionPartner = $transactionPartner;
    		$this->category = $category;
    		$this->beginDate = $beginDate;
    		$this->endDate = $endDate;
    		$this->repeatUnit = $repeatUnit;
    		$this->repeatFrequency = $repeatFrequency;
    		$this->type = $type;
			$this->transferalTransaction = $transferalTransaction;
			$this->transferalSource = null;
    	}
    	
    	$this->updateMode = self::UPDATE_MODE_ALL;
    	$this->otherPlannedTransaction = null;
    	$this->beginDateLocked = false;
    	$this->endDateLocked = false;
    	$this->originalTitle = $this->title;
    	$this->originalBeginDate = new Date($this->beginDate);
		if (!is_null($this->endDate)) {
    		$this->originalEndDate = new Date($this->endDate);
		} else {
			$this->originalEndDate = null;
		}
    	$this->originalRepeatUnit = $this->repeatUnit;
    	$this->originalRepeatFrequency = $this->repeatFrequency;
    }
    
	/**
	 * Returns the id.
	 * 
	 * @return integer The id of this transaction.
	 */
    public function getId() {
    	return $this->id;
    }
    
	/**
	 * Returns the title.
	 * 
	 * @return string The title of this transaction.
	 */
    public function getTitle() {
    	return $this->title;
    }
    
 	/**
 	 * Sets the title.
 	 * 
 	 * @param $title string The title of this transaction.
 	 */
 	public function setTitle($title) {
		$this->title = $title;
		
		$this->doUpdate("SET title = '" . $this->badgerDb->escapeSimple($title) . "'");
	}
	
	/**
	 * Returns the description.
	 * 
	 * @return string The description of this transaction.
	 */
    public function getDescription() {
    	return $this->description;
    }
    
 	/**
 	 * Sets the description.
 	 * 
 	 * @param $description string The description of this transaction.
 	 */
 	public function setDescription($description) {
		$this->description = $description;
		
		$this->doUpdate("SET description = '" . $this->badgerDb->escapeSimple($description) . "'");
	}
	
	/**
	 * Returns the begin date.
	 * 
	 * @return object The Date object with the begin date of this transaction.
	 */
    public function getBeginDate() {
    	return $this->beginDate;
    }
    
 	/**
 	 * Sets the begin date.
 	 * 
 	 * @param $beginDate object The Date object with the begin date of this transaction.
 	 */
 	public function setBeginDate($beginDate, $updateTransferal = true) {
		if (!$this->beginDateLocked) {
			$this->beginDate = $beginDate;
			
			$this->doUpdate("SET begin_date = '" . $beginDate->getDate() . "'", false, false);
			
			if ($updateTransferal && $this->transferalTransaction) {
				$this->transferalTransaction->setBeginDate($beginDate, false);
			}
		}
	}
	
	/**
	 * Returns the end date.
	 * 
	 * @return object The Date object with the end date of this transaction.
	 */
    public function getEndDate() {
    	return $this->endDate;
    }
    
 	/**
 	 * Sets the end date.
 	 * 
 	 * @param $endDate object The Date object with the end date of this transaction.
 	 */
 	public function setEndDate($endDate, $updateTransferal = true) {
 		if (!$this->endDateLocked) {
			$this->endDate = $endDate;
			
			if (!is_null($endDate)) {
				$dateVal = "'" . $endDate->getDate() . "'";
			} else {
				$dateVal = 'NULL';
			}
	
			$this->doUpdate("SET end_date = $dateVal", false);

			if ($updateTransferal && $this->transferalTransaction) {
				$this->transferalTransaction->setEndDate($endDate, false);
			}
 		}
	}
	
	/**
	 * Returns the amount.
	 * 
	 * @return object The Amount object with the amount of this transaction.
	 */
    public function getAmount() {
    	return $this->amount;
    }
    
 	/**
 	 * Sets the amount.
 	 * 
 	 * @param $amount object The Amount object with the amount of this transaction.
 	 */
 	public function setAmount($amount) {
		$this->amount = $amount;
		
		$this->doUpdate("SET amount = '" . $amount->get() . "'", true, false);
	}
	
	/**
	 * Returns the origin.
	 * 
	 * @return boolean true if this transaction is outside capital.
	 */
    public function getOutsideCapital() {
    	return $this->outsideCapital;
    }
    
 	/**
 	 * Sets the origin.
 	 * 
 	 * @param $outsideCapital boolean true if this transaction is outside capital.
 	 */
 	public function setOutsideCapital($outsideCapital) {
		$this->outsideCapital = $outsideCapital;
		
		$this->doUpdate("SET outside_capital = " . $this->badgerDb->quoteSmart($outsideCapital));
	}
	
	/**
	 * Returns the transaction partner.
	 * 
	 * @return string The transaction partner of this transaction.
	 */
    public function getTransactionPartner() {
    	return $this->transactionPartner;
    }
    
 	/**
 	 * Sets the transaction partner.
 	 * 
 	 * @param $transactionPartner string The transaction partner of this transaction.
 	 */
 	public function setTransactionPartner($transactionPartner) {
		$this->transactionPartner = $transactionPartner;
		
		$this->doUpdate("SET transaction_partner = '" . $this->badgerDb->escapeSimple($transactionPartner) . "'");
	}
	
	/**
	 * Returns the category.
	 * 
	 * @return object The Category object with the category of this transaction.
	 */
    public function getCategory() {
    	return $this->category;
    }
 
 	/**
 	 * Sets the Category.
 	 * 
 	 * @param $category object The Category object with the category of this transaction.
 	 */
 	public function setCategory($category) {
		$this->category = $category;
		
		if (is_null($category)) {
			$catId = 'NULL';
		} else {
			$catId = $category->getId();
		}
		
		$this->doUpdate("SET category_id = $catId");
	}

	/**
	 * Returns the repeat unit.
	 * 
	 * @return string The repeat unit of this transaction.
	 */
    public function getRepeatUnit() {
    	return $this->repeatUnit;
    }
 
 	/**
 	 * Sets the repeat unit.
 	 * 
 	 * @param $repeatUnit string The repeat unit of this transaction.
 	 */
 	public function setRepeatUnit($repeatUnit, $updateTransferal = true) {
		$this->repeatUnit = $repeatUnit;
		
		$this->doUpdate("SET repeat_unit = '" . $repeatUnit . "'", false, false);

		if ($updateTransferal && $this->transferalTransaction) {
			$this->transferalTransaction->setRepeatUnit($repeatUnit, false);
		}
	}

	/**
	 * Returns the repeat frequency.
	 * 
	 * @return integer The repeat frequency of this transaction.
	 */
    public function getRepeatFrequency() {
    	return $this->repeatFrequency;
    }
 
 	/**
 	 * Sets the repeat frequency.
 	 * 
 	 * @param $repeatFrequency int The repeat frequency of this transaction.
 	 */
 	public function setRepeatFrequency($repeatFrequency, $updateTransferal = true) {
		$this->repeatFrequency = $repeatFrequency;
		
		$this->doUpdate("SET repeat_frequency = " . $repeatFrequency, false, false);

		if ($updateTransferal && $this->transferalTransaction) {
			$this->transferalTransaction->setRepeatFrequency($repeatFrequency, false);
		}
	}
    
    public static function sanitizeId($id) {
    	if ($id{0} === 'p') {
    		$parts = explode('_', $id);
    		
    		return (int) substr($parts[0], 1);
    	} else {
    		return (int) $id;
    	}
    }
	
	/**
	 * Returns the type.
	 * 
	 * @return string The type of this transaction.
	 */
	public function getType() {
		return $this->type;
	}
	
    public function getAccount() {
    	return $this->account;
    }
    
	public function getTransferalTransaction() {
		return $this->transferalTransaction;
	}
	
	public function setTransferalTransaction($transferalTransaction) {
		$this->transferalTransaction = $transferalTransaction;
		
		if (is_null($transferalTransaction)) {
			$id = 'NULL';
		} else {
			$id = $transferalTransaction->getId();
		}
		
		$this->doUpdate("SET transferal_transaction_id = $id", false, false);
	}
	
	public function addTransferalTransaction($transferalAccount, $transferalAmount) {
		$this->setTransferalTransaction($transferalAccount->addPlannedTransaction(
			$this->title,
			$transferalAmount,
			$this->repeatUnit,
			$this->repeatFrequency,
			$this->beginDate,
			$this->endDate,
			$this->description,
			$this->transactionPartner,
			$this->category,
			$this->outsideCapital,
			null,
			null,
			$this
		));
		
		$this->transferalTransaction->expand(new Date('1000-01-01'), getTargetFutureCalcDate(), false);
		
		$this->createTransferalConnections();

		$this->setTransferalSource(true);
	}
	
	private function createTransferalConnections() {
		$accountManager = new AccountManager($this->badgerDb);
		$thisAccount = $accountManager->getAccountById($this->account->getId());
		$otherAccount = $accountManager->getAccountById($this->transferalTransaction->getAccount()->getId());
		
		$thisFilter = array (
			array (
				'key' => 'plannedTransactionId',
				'op' => 'eq',
				'val' => $this->id
			)
		);
		$otherFilter = array (
			array (
				'key' => 'plannedTransactionId',
				'op' => 'eq',
				'val' => $this->transferalTransaction->getId()
			)
		);
		
		$order = array (
			array (
				'key' => 'valutaDate',
				'dir' => 'asc'
			)
		);
		
		$thisAccount->setFilter($thisFilter);
		$otherAccount->setFilter($otherFilter);
		$thisAccount->setOrder($order);
		$otherAccount->setOrder($order);
		
		while (
			($currentThisTransaction = $thisAccount->getNextTransaction())
			&& ($currentOtherTransaction = $otherAccount->getNextTransaction())
		) {
			while (
				$currentThisTransaction
				&& $currentOtherTransaction
				&& $currentThisTransaction->getValutaDate()->before($currentOtherTransaction->getValutaDate())
			) {
				$currentThisTransaction = $thisAccount->getNextTransaction();
			}

			while (
				$currentThisTransaction
				&& $currentOtherTransaction
				&& $currentOtherTransaction->getValutaDate()->before($currentThisTransaction->getValutaDate())
			) {
				$currentOtherTransaction = $otherAccount->getNextTransaction();
			}
			
			if (
				$currentThisTransaction
				&& $currentOtherTransaction
			) {
				$currentThisTransaction->setTransferalTransaction($currentOtherTransaction);
				$currentOtherTransaction->setTransferalTransaction($currentThisTransaction);
			}
		}
		
	}
	
	public function getTransferalSource() {
		return $this->transferalSource;
	}
	
	public function setTransferalSource($transferalSource) {
		$this->transferalSource = $transferalSource;
		
		$this->doUpdate("SET transferal_source = " . $this->badgerDb->quoteSmart($transferalSource), true, false);
	}
    
	public function expand($start, $end, $updateTransferal = true) {
		$date = new Date($this->beginDate);
		
		$accountManager = new AccountManager($this->badgerDb);
		$compareAccount = $accountManager->getAccountById($this->account->getId());
		
		$compareAccount->setFilter(array (
			array (
				'key' => 'plannedTransactionId',
				'op' => 'eq',
				'val' => $this->id
			)
		));
		$compareAccount->setOrder(array (
			array (
				'key' => 'valutaDate',
				'dir' => 'asc'
			)
		));
		
		$currentCompareTransaction = $compareAccount->getNextTransaction();
		
		$localEndDate = is_null($this->endDate) ? new Date('9999-12-31') : $this->endDate; 
		//While we have not reached end or endDate
		while (
			!$end->before($date)
			&& !$date->after($localEndDate)
		) {
			while (
				$currentCompareTransaction !== false
				&& $date->after($currentCompareTransaction->getValutaDate())
			) {
				$currentCompareTransaction = $compareAccount->getNextTransaction();
			}
			
			if(
				!$date->before($start)
				&& (
					($currentCompareTransaction === false)
					|| !$date->equals($currentCompareTransaction->getValutaDate())
				)
			) {
				$newFinishedTransaction = $this->account->addFinishedTransaction(
					new Amount($this->amount),
					$this->title,
					$this->description,
					new Date($date),
					$this->transactionPartner,
					$this->category,
					$this->outsideCapital,
					false,
					true,
					$this
				);
				
				if ($updateTransferal && $this->transferalTransaction) {
					$newFinishedTransaction->addTransferalTransaction(
						$this->transferalTransaction->getAccount(),
						$this->transferalTransaction->getAmount(),
						$this->transferalTransaction
					);
				}
			}			
			
			$date = $this->nextOccurence($date);

		} //while before end and endDate 

		if (!is_null($this->otherPlannedTransaction)) {
			$this->otherPlannedTransaction->expand($start, $end);
		}

	} //function expand
	
	private function nextOccurence($date, $start = null) {
		if (is_null($start)) {
			$start = $this->beginDate;
		}
		
		$dayOfMonth = $start->getDay();
		
		//do the date calculation
		switch ($this->repeatUnit){
			case 'day': 
				$date->addSeconds($this->repeatFrequency * 24 * 60 * 60);
				break;
				
			case 'week':
				$date->addSeconds($this->repeatFrequency * 7 * 24 * 60 * 60);
				break;
				
			case 'month':
				//Set the month
				$date = new Date(Date_Calc::endOfMonthBySpan($this->repeatFrequency, $date->getMonth(), $date->getYear(), '%Y-%m-%d'));
				//And count back as far as the last valid day of this month
				while($date->getDay() > $dayOfMonth){
					$date->subtractSeconds(24 * 60 * 60);
				}
				break; 
			
			case 'year':
				$newYear = $date->getYear() + $this->repeatFrequency;
				if (
					$dayOfMonth == 29
					&& $date->getMonth() == 2
					&& !Date_Calc::isLeapYear($newYear)
				) {
					$date->setDay(28);
				} else {
					$date->setDay($dayOfMonth);
				}
				
				$date->setYear($newYear);
				break;
			
			default:
				throw new BadgerException('Account', 'IllegalRepeatUnit', $this->repeatUnit);
				exit;
		} //switch
		
		return $date;
	}

	private function previousOccurence($date, $start = null) {
		if (is_null($start)) {
			$start = $this->beginDate;
		}
		
		$dayOfMonth = $start->getDay();
		
		//do the date calculation
		switch ($this->repeatUnit){
			case 'day': 
				$date->subtractSeconds($this->repeatFrequency * 24 * 60 * 60);
				break;
				
			case 'week':
				$date->subtractSeconds($this->repeatFrequency * 7 * 24 * 60 * 60);
				break;
				
			case 'month':
				//Set the month
				$date = new Date(Date_Calc::endOfMonthBySpan(-$this->repeatFrequency, $date->getMonth(), $date->getYear(), '%Y-%m-%d'));
				//And count back as far as the last valid day of this month
				while($date->getDay() > $dayOfMonth){
					$date->subtractSeconds(24 * 60 * 60);
				}
				break; 
			
			case 'year':
				$newYear = $date->getYear() - $this->repeatFrequency;
				if (
					$dayOfMonth == 29
					&& $date->getMonth() == 2
					&& !Date_Calc::isLeapYear($newYear)
				) {
					$date->setDay(28);
				} else {
					$date->setDay($dayOfMonth);
				}
				
				$date->setYear($newYear);
				break;
			
			default:
				throw new BadgerException('Account', 'IllegalRepeatUnit', $this->repeatUnit);
				exit;
		} //switch
		
		return $date;
	}

	public function expandUpdate() {
		$targetFutureCalcDate = getTargetFutureCalcDate();

		if ($this->updateMode != self::UPDATE_MODE_ALL) {
			return;
		}

		if (
			$this->originalRepeatUnit == $this->repeatUnit
			&& $this->originalRepeatFrequency == $this->repeatFrequency
		) {
			if (!is_null($this->originalEndDate)) {
				$originalEndDate = $this->originalEndDate;
			} else {
				$originalEndDate = new Date('9999-01-01');
			}
			if (!is_null($this->endDate)) {
				$endDate = $this->endDate;
			} else {
				$endDate = new Date('9999-01-01');
			}

			$this->updateExpandedDates($this->originalBeginDate, $originalEndDate);

			if ($this->originalBeginDate->before($this->beginDate)) {
				$end = new Date($this->beginDate);
				$end->subtractSeconds(24 * 60 * 60);
	
				$this->deletePlannedTransactions($this->originalBeginDate, $end, true);
			} else if ($this->originalBeginDate->after($this->beginDate)) {
				$this->expand($this->beginDate, $this->originalBeginDate);
			}
			
			if ($originalEndDate->before($endDate)) {
				if ($endDate->before($targetFutureCalcDate)) {
					$this->expand($originalEndDate, $endDate);
				} else {
					$this->expand($originalEndDate, $targetFutureCalcDate);
				}
			} else if ($originalEndDate->after($endDate)) {
				$start = new Date($endDate);
				$start->addSeconds(24 * 60 * 60);
				
				$this->deletePlannedTransactions($start, $originalEndDate, true);
			}
		} else {
			//repeat unit or frequency changed, discard all old entries and create from scratch
			$this->deletePlannedTransactions(new Date('1000-01-01'), new Date('9999-12-31'), true);					

			$this->expand(new Date('1000-01-01'), $targetFutureCalcDate);
		}
	}
	
	private function updateExpandedDates($start, $end, $updateTransferal = true) {
		if ($this->beginDate->equals($this->originalBeginDate)) {
			return;
		}

		$accountManager = new AccountManager($this->badgerDb);
		$compareAccount = $accountManager->getAccountById($this->account->getId());
		
		$compareAccount->setFilter(array (
			array (
				'key' => 'plannedTransactionId',
				'op' => 'eq',
				'val' => $this->id
			),
			array (
				'key' => 'valutaDate',
				'op' => 'ge',
				'val' => new Date($start)
			),
			array (
				'key' => 'valutaDate',
				'op' => 'le',
				'val' => new Date($end)
			)
		));
		$compareAccount->setOrder(array (
			array (
				'key' => 'valutaDate',
				'dir' => 'asc'
			)
		));
		
		$date = new Date($this->beginDate);
		$originalDate = new Date($this->originalBeginDate);
		
		$windowStart = $this->previousOccurence(new Date($originalDate), $this->originalBeginDate);
		$windowEnd = $this->nextOccurence(new Date($originalDate), $this->originalBeginDate);
		while(!$date->after($windowStart)) {
			$date = $this->nextOccurence($date);
		}

		while ($currentCompareTransaction = $compareAccount->getNextTransaction()) {
			while ($originalDate->before($currentCompareTransaction->getValutaDate())) {
				$originalDate = $this->nextOccurence($originalDate, $this->originalBeginDate);
				$date = $this->nextOccurence($date);
			}
			
			if ($originalDate->equals($currentCompareTransaction->getValutaDate())) {
				$currentCompareTransaction->setValutaDate(new Date($date));
			}

		} //while compareTransactions 

		if (
			$updateTransferal
			&& $this->transferalTransaction
		) {
			$this->transferalTransaction->updateExpandedDates($start, $end, false);
		}
	}

	public function deletePlannedTransactions($begin, $upTo, $force = false, $updateTransferal = true) {
		if (
			$this->account->getDeleteOldPlannedTransactions()
			|| $force
		) {
			$sql = "DELETE FROM finished_transaction
					WHERE planned_transaction_id = " . $this->id . "
						AND valuta_date >= '" . $begin->getDate() . "'
						AND valuta_date <= '" . $upTo->getDate() . "'"
			;
	
			$dbResult =& $this->badgerDb->query($sql);
			
			if (PEAR::isError($dbResult)) {
				throw new BadgerException('PlannedTransaction', 'SQLError', $dbResult->getMessage());
			}
			
			if (
				$updateTransferal
				&& $this->transferalTransaction
			) {
				$this->transferalTransaction->deletePlannedTransactions($begin, $upTo, $force, false);
			}
		}
	}

	public function setUpdateMode($updateMode, $splitDate, $updateTransferal = true) {
		$this->updateMode = $updateMode;
		$this->updateSplitDate = $splitDate;

//		if (
//			$updateTransferal
//			&& $this->transferalTransaction
//		) {
//			$this->transferalTransaction->setUpdateMode($updateMode, $splitDate, false);
//		}
	}
	
	private function doUpdate($sqlPart, $updateFinishedTransactions = true, $updateTransferal = true) {
		$sql = "UPDATE planned_transaction\n$sqlPart\nWHERE planned_transaction_id = " . $this->id;

		$dbResult =& $this->badgerDb->query($sql);
		if (PEAR::isError($dbResult)) {
			throw new BadgerException('PlannedTransaction', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
		
		if ($updateFinishedTransactions) {
			switch ($this->updateMode) {
				case self::UPDATE_MODE_ALL:
					break;
				
				case self::UPDATE_MODE_PREVIOUS:
				case self::UPDATE_MODE_FOLLOWING:
					$this->checkOtherPlannedTransaction($updateTransferal);
					break;
			}
		
			$sql = "UPDATE finished_transaction\n$sqlPart\nWHERE planned_transaction_id = " . $this->id;
			$dbResult =& $this->badgerDb->query($sql);
			if (PEAR::isError($dbResult)) {
				//echo "SQL Error: " . $dbResult->getMessage();
				throw new BadgerException('PlannedTransaction', 'SQLError', $dbResult->getMessage());
			}
		}

		if (
			$updateTransferal
			&& !(is_null($this->transferalTransaction))
		) {
			$this->transferalTransaction->doUpdate($sqlPart, $updateFinishedTransactions, false);
		}
	}
	
	private function checkOtherPlannedTransaction($updateTransferal = true) {
		if (is_null($this->otherPlannedTransaction)) {
			if ($this->updateMode == self::UPDATE_MODE_PREVIOUS) {
				$title = $this->originalTitle
					. ' ('
					. getBadgerTranslation2('plannedTransaction', 'afterTitle')
					. ' '
					. $this->updateSplitDate->getFormatted()
					. ')'
				;
				$beginDate = $this->nextOccurence($this->updateSplitDate);
				$endDate = $this->endDate;
				$cmpOperator = '>';
				
				$this->setEndDate($this->updateSplitDate);
				$this->endDateLocked = true;
			} else {
				$title = $this->originalTitle
					. ' ('
					. getBadgerTranslation2('plannedTransaction', 'beforeTitle')
					. ' '
					. $this->updateSplitDate->getFormatted()
					. ')'
				;
				$beginDate = $this->beginDate;
				$endDate = $this->previousOccurence($this->updateSplitDate);
				$cmpOperator = '<';
				
				$this->setBeginDate($this->updateSplitDate);
				$this->beginDateLocked = true;
			}
			
			$this->otherPlannedTransaction = $this->account->addPlannedTransaction(
				$title,
				$this->amount,
				$this->repeatUnit,
				$this->repeatFrequency,
				$beginDate,
				$endDate,
				$this->description,
				$this->transactionPartner,
				$this->category,
				$this->outsideCapital
			);
			
			$sql = "UPDATE finished_transaction
					SET title = '" . $this->badgerDb->escapeSimple($title) . "', planned_transaction_id = " . $this->otherPlannedTransaction->getId() . "
					WHERE valuta_date $cmpOperator '" . $this->updateSplitDate->getDate() . "'
						AND planned_transaction_id = " . $this->id
			;
			$dbResult =& $this->badgerDb->query($sql);
			if (PEAR::isError($dbResult)) {
				//echo "SQL Error: " . $dbResult->getMessage();
				throw new BadgerException('PlannedTransaction', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
			}

			if ($updateTransferal && $this->transferalTransaction) {
				$otherTransferalTransaction = $this->transferalTransaction->getAccount()->addPlannedTransaction(
					$title,
					$this->transferalTransaction->getAmount(),
					$this->repeatUnit,
					$this->repeatFrequency,
					$beginDate,
					$endDate,
					$this->description,
					$this->transactionPartner,
					$this->category,
					$this->outsideCapital
				);
				
				$otherTransferalTransaction->setTransferalTransaction($this->otherPlannedTransaction);
				
				$this->otherPlannedTransaction->setTransferalTransaction($otherTransferalTransaction);
				
				$this->otherPlannedTransaction->setTransferalSource($this->transferalSource);
				$otherTransferalTransaction->setTransferalSource($this->transferalTransaction->getTransferalSource());

				$sql = "UPDATE finished_transaction
						SET title = '" . $this->badgerDb->escapeSimple($title) . "', planned_transaction_id = " . $this->otherPlannedTransaction->getTransferalTransaction()->getId() . "
						WHERE valuta_date $cmpOperator '" . $this->updateSplitDate->getDate() . "'
							AND planned_transaction_id = " . $this->transferalTransaction->getId()
				;
				$dbResult =& $this->badgerDb->query($sql);
				if (PEAR::isError($dbResult)) {
					throw new BadgerException('PlannedTransaction', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());

				} //if SQL Error
			} //if updateTransferal
		} // if is_null(otherPlannedTransaction)
	} //function checkOtherPlannedTransaction
} //class PlannedTransaction
?>