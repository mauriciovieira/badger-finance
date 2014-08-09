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
require_once BADGER_ROOT . '/modules/account/PlannedTransaction.class.php';

/**
 * A finished transaction.
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1035 $
*/
class FinishedTransaction {
	

	/**
	 * The DB object.
	 * 
	 * @var object DB
	 */
	private $badgerDb;
	
	/**
	 * The Account this transaction belongs to.
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
	
	/**
	 * The description of this transaction.
	 * 
	 * @var string
	 */
	private $description;
	
	/**
	 * The valuta date of this transaction.
	 * 
	 * @var object Date
	 */
	private $valutaDate;
	
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
	 * Is this an exceptional transaction?
	 * 
	 * @var boolean
	 */
	private $exceptional;
	
	/**
	 * Is this transaction exceptional?
	 * 
	 * @var boolean
	 */
	private $periodical;
	
	/**
	 * The corresponding planned transaction, if any.
	 * 
	 * @var object PlannedTransaction
	 */
	private $sourcePlannedTransaction;
	
	/**
	 * The type of this transaction.
	 * 
	 * 'FinishedTransaction' or 'PlannedTransaction' (a expanded one)
	 * 
	 * @var string
	 */
	private $type;
	
	private $balance;
	
	private $transferalTransaction;
	
	private $transferalSource;
	
	/**
	 * Creates a Finished Transaction.
	 * 
	 * @param $badgerDb object The DB object.
	 * @param $account object The Account object who created this Transaction.
	 * @param $data mixed An associative array with the values out of the DB OR the id of the Transaction.
	 * @param $title string The title of the Transaction.
	 * @param $amount object The Amount object with the amount of this Transaction.
	 * @param $description string The description of the Transaction.
	 * @param $valutaDate object The Date object with the valuta date of the Transaction.
	 * @param $transactionPartner string The transaction partner of the Transaction
	 * @param $outsideCapital boolean The origin of the Transaction.
	 * @param $type string The type of the Transaction.
	 */
	function __construct(
		&$badgerDb,
		&$account,
		$data,
		$title = null,
		$amount = null,
		$description = null,
		$valutaDate = null,
		$transactionPartner = null,
		$category = null,
		$outsideCapital = null,
		$exceptional = null,
		$periodical = null,
		$sourcePlannedTransaction = null,
		$type = 'FinishedTransaction',
		$transferalTransaction = null
	) {
		global $logger;
		$this->badgerDb = $badgerDb;
		$this->account = $account;
		
		if (is_array($data)) {
			$v = var_export($data,true);
			$logger->log("FinishedTransaction::__construct data: "  . $v);

			$this->id = $data['finished_transaction_id'];
			$this->title = $data['title'];
			$this->description = $data['description'];
			$this->amount = new Amount($data['amount']);
			$this->outsideCapital = $data['outside_capital'];
			$this->transactionPartner =  $data['transaction_partner'];
			if ($data['category_id']) {
				$cm = new CategoryManager($badgerDb);
				$this->category = $cm->getCategoryById($data['category_id']);
			}
			if ($data['valuta_date']) {
				$this->valutaDate = new Date($data['valuta_date']);
			}
			$this->exceptional = $data['exceptional'];
			$this->periodical = $data['periodical'];
			$logger->log("FinishedTransaction::__construct data[balance]: " . $data['balance']);
			$this->balance = new Amount($data['balance']);
			$logger->log("FinishedTransaction::__construct balance->get " . $this->balance->get());
			$this->transferalSource = $data['transferal_source'];
			$planned = false;
			if ($data['planned_transaction_id']) {
				try {
					$this->sourcePlannedTransaction = $account->getPlannedTransactionById($data['planned_transaction_id']);
					$planned = true;
				} catch (BadgerException $ex) {
					$this->sourcePlannedTransaction = null;
				}
			}
			$transferal = false;
			if ($data['transferal_transaction_id']) {
				if (!is_null($title) && $data['transferal_transaction_id'] == $title->getId()) {
					$this->transferalTransaction = $title;
					$transferal = true;
				} else {
					$accountManager = new AccountManager($badgerDb);
					try {
						$transferalAccount = $accountManager->getAccountByFinishedTransactionId($data['transferal_transaction_id']);
						$this->transferalTransaction = $transferalAccount->getFinishedTransactionById($data['transferal_transaction_id'], $this);
						$transferal = true;
					} catch (BadgerException $ex) {
						$this->transferalTransaction = null;
					}
				}
			}
			$this->type =
				($planned ? 'Planned' : 'Finished')
				. ($transferal ? 'Transferal' : '')
				. 'Transaction'
			;
		} else {
			$this->id = $data;
			$this->title = $title;
			$this->description = $description;
			$this->valutaDate = $valutaDate;
			$this->amount = $amount;
			$this->outsideCapital = $outsideCapital;
			$this->transactionPartner = $transactionPartner;
			$this->category = $category;
			$this->exceptional = $exceptional;
			$this->periodical = $periodical;
			$this->sourcePlannedTransaction = $sourcePlannedTransaction;
			$this->balance = null;
			$this->type = $type;
			$this->transferalTransaction = $transferalTransaction;
			$this->transferalSource = null;
			
		} //if database output given
	} //function __construct
	
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
	 * Returns the valuta date.
	 * 
	 * @return object The Date object with the valuta date of this transaction.
	 */
	public function getValutaDate() {
		return $this->valutaDate;
	}
	
	/**
	 * Sets the valuta date.
	 * 
	 * @param $valutaDate object The Date object with the valuta date of this transaction.
	 */
	public function setValutaDate($valutaDate) {
		$this->valutaDate = $valutaDate;
		
		$this->doUpdate("SET valuta_date = '" . $valutaDate->getDate() . "'");
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
		
		$this->doUpdate("SET amount = '" . $amount->get() . "'", false);
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
	 * Returns the type.
	 * 
	 * @return string The type of this transaction.
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * Returns if this transaction is exceptional.
	 * 
	 * @return boolean true if this transaction is exceptional, else false.
	 */
	public function getExceptional() {
		return $this->exceptional;
	}
	
	/**
	 * Sets if this transaction is exceptional.
	 * 
	 * @param $exceptional boolean true if this transaction is exceptional.
	 */
	public function setExceptional($exceptional) {
		$this->exceptional = $exceptional;
		
		$this->doUpdate("SET exceptional = " . $this->badgerDb->quoteSmart($exceptional));
	}
	
	/**
	 * Returns if this transaction is periodical.
	 * 
	 * @return boolean true if this transaction is periodical, else false.
	 */
	public function getPeriodical() {
		return $this->periodical;
	}
	
	/**
	 * Sets if this transaction is periodical.
	 * 
	 * @param $periodical boolean true if this transaction is periodical.
	 */
	public function setPeriodical($periodical) {
		$this->periodical = $periodical;
		
		$this->doUpdate("SET periodical = " . $this->badgerDb->quoteSmart($periodical));
	}
	
	/**
	 * Returns the source PlannedTransaction, if any.
	 * 
	 * @return object The PlannedTransaction object which is source of this transaction, if existent; else null.
	 */
    public function getSourcePlannedTransaction() {
    	return $this->sourcePlannedTransaction;
    }
    
    public function getPlannedTransaction() {
    	return $this->getSourcePlannedTransaction();
    }
    
    public function setPlannedTransaction($plannedTransaction) {
    	$this->sourcePlannedTransaction = $plannedTransaction;
    
		if (is_null($plannedTransaction)) {
    		$id = 'NULL';
    		$updateTransferal = true;
    	} else {
    		$id = $plannedTransaction->getId();
    		$updateTransferal = false;
    	}

		$this->doUpdate("SET planned_transaction_id = $id", $updateTransferal);
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
		
		$this->doUpdate("SET transferal_transaction_id = $id", false);
	}
	
	public function addTransferalTransaction($transferalAccount, $transferalAmount, $transferalPlannedTransaction = null) {
		$this->setTransferalTransaction($transferalAccount->addFinishedTransaction(
			$transferalAmount,
			$this->title,
			$this->description,
			$this->valutaDate,
			$this->transactionPartner,
			$this->category,
			$this->outsideCapital,
			$this->exceptional,
			$this->periodical,
			$transferalPlannedTransaction,
			null,
			null,
			$this
		));
		
		$this->setTransferalSource(true);
	}
	
	public function getTransferalSource() {
		return $this->transferalSource;
	}
	
	public function setTransferalSource($transferalSource) {
		$this->transferalSource = $transferalSource;
		
		$this->doUpdate("SET transferal_source = " . $this->badgerDb->quoteSmart($transferalSource), false);
	}
    
    public function getBalance() {
			global $logger;
			$logger->log("FinishedTransaction::getBalance() -> " . $this->balance->get());
    	return $this->balance;
    }
    
    public function getAccount() {
    	return $this->account;
    }
    
    private function doUpdate($sqlPart, $updateTransferal = true) {
		$sql = "UPDATE finished_transaction\n$sqlPart\nWHERE finished_transaction_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('FinishedTransaction', 'SQLError', "SQL: $sql\n" . $dbResult->getMessage());
		}
		
		if (
			$updateTransferal
			&& !(is_null($this->transferalTransaction))
		) {
			$this->transferalTransaction->doUpdate($sqlPart, false);
		}
    }
}
?>
