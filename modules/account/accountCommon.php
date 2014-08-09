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

require_once BADGER_ROOT . '/modules/account/Account.class.php';
require_once BADGER_ROOT . '/core/Date/Span.php';

/**
 * Returns the Account balance for $account at the end of each day between $startDate and $endDate.
 * 
 * Considers the planned transactions of $account.
 * 
 * @param object $account The Account object for which the balance should be calculated. 
 * It should be 'fresh', i. e. no transactions of any type should have been fetched from it.
 * @param object $startDate The first date the balance should be calculated for as Date object.
 * @param object $endDate The last date the balance should be calculated for as Date object.
 * @return array Array of Amount objects corresponding to the balance of $account at each day between
 * $startDate and $endDate. The array keys are the dates as ISO-String (yyyy-mm-dd). 
 */
function getDailyAmount($account, $startDate, $endDate, $isoDates = true, $startWithBalance = false, $includePlannedTransactions = false) {
	global $badgerDb;
	global $logger;
	
	$account->setTargetFutureCalcDate($endDate);
	$account->setOrder(array (array ('key' => 'valutaDate', 'dir' => 'asc')));
	if (!$includePlannedTransactions) {
		$account->setType('finished');
	}
	
	$result = array();
	
	$startDate->setHour(0);
	$startDate->setMinute(0);
	$startDate->setSecond(0);
	
	$endDate->setHour(0);
	$endDate->setMinute(0);
	$endDate->setSecond(0);

	$currentDate = new Date($startDate);
	$currentAmount = new Amount();
	$firstRun = true;
	
	//foreach transaction
	while ($currentTransaction = $account->getNextTransaction()) {
		if ($currentDate->after($endDate)) {
			//we reached $endDAte
			break;
		}
		
		if ($firstRun && $startWithBalance) {
			$logger->log("getDailyAmount calls getAmount and getBalance");
			$currentAmount = new Amount($currentTransaction->getBalance());
			$currentAmount->sub($currentTransaction->getAmount());
			$firstRun = false;
		}

		//fill all dates between last and this transaction with the old amount
		while (is_null($tmp = $currentTransaction->getValutaDate()) ? false : $currentDate->before($tmp)) {
			if ($isoDates) {
				$key = $currentDate->getDate();
			} else {
				$key = $currentDate->getTime();
			}
			$result[$key] = new Amount($currentAmount);
			
			$currentDate->addSeconds(24 * 60 * 60);
			
			if ($currentDate->after($endDate)) {
				//we reached $endDAte
				break;
			}
		}

		$currentAmount->add($currentTransaction->getAmount());
	}
	
	if ($firstRun && $startWithBalance) {
		$newAccountManager = new AccountManager($badgerDb);
		$newAccount = $newAccountManager->getAccountById($account->getId());
		
		$newAccount->setOrder(array (array ('key' => 'valutaDate', 'dir' => 'asc')));
		
		while ($newTransaction = $newAccount->getNextTransaction()) {
			$currentDate = $newTransaction->getValutaDate();
			
			if ($currentDate->after($startDate)) {
				//we reached $endDAte
				break;
			}
			
			$currentAmount = new Amount($newTransaction->getBalance());
		}
		
		$currentDate = new Date($startDate);
		
		if ($isoDates) {
			$key = $currentDate->getDate();
		} else {
			$key = $currentDate->getTime();
		}
		
		$result[$key] = new Amount($currentAmount);
	}
	
	//fill all dates after the last transaction with the newest amount
	while (Date::compare($currentDate, $endDate) <= 0) {
		if ($isoDates) {
			$key = $currentDate->getDate();
		} else {
			$key = $currentDate->getTime();
		}
		$result[$key] = new Amount($currentAmount);
		
		$currentDate->addSeconds(24 * 60 * 60);
	}

	return $result;
}

function getSpendingMoney($accountId, $startDate) {
	global $badgerDb;

	$accountManager = new AccountManager($badgerDb);
	
	$account = $accountManager->getAccountById($accountId);
	
	$account->setType('finished');

	$account->setOrder(array (array ('key' => 'valutaDate', 'dir' => 'asc')));
	
	$account->setFilter(array (
		array (
			'key' => 'valutaDate',
			'op' => 'ge',
			'val' => $startDate
		),
		array (
			'key' => 'periodical',
			'op' => 'eq',
			'val' => false
		),
		array (
			'key' => 'exceptional',
			'op' => 'eq',
			'val' => false
		)
	));
	
	$sum = new Amount();
	$realStartDate = false;
	
	while ($currentTransaction = $account->getNextFinishedTransaction()) {
		if (!$realStartDate) {
			$realStartDate = $currentTransaction->getValutaDate();
		}

		$sum->add($currentTransaction->getAmount());
	}
	
	$span = new Date_Span($realStartDate, new Date());
	$count = $span->toDays();

	if ($count > 0) {
		$sum->div($count);
	}
	
	return $sum;
}

function getCategorySelectArray($includeSubCategories = false) {
	global $badgerDb;
	$cm = new CategoryManager($badgerDb);
	$order = array (
		array (
			'key' => 'parentTitle',
			'dir' => 'asc'
		),
		array (
			'key' => 'title',
			'dir' => 'asc'
		)
 	);
	
	$cm->setOrder($order);
	
	$parentCats = array();
 	$parentCats['NULL'] = "";
	
	while ($cat = $cm->getNextCategory()) {
		$cat->getParent();
	}
	
	$cm->resetCategories();
	
	while ($cat = $cm->getNextCategory()) {
		if(is_null($cat->getParent())) {
			$parentCats[$cat->getId()] = $cat->getTitle();
			$children = $cat->getChildren();
			//echo "<pre>"; print_r($children); echo "</pre>";
			if ($children) {
				if ($includeSubCategories) {
					$parentCats['-' . $cat->getId()] = $cat->getTitle() . ' ' . getBadgerTranslation2('accountCommon', 'includeSubCategories');
				}
				foreach ($children as $key => $value) {
					$parentCats[$value->getId()] = " - " . $value->getTitle();
				};
			};
		};
	};
 
	return $parentCats;
}

class CompareTransaction {
	private $order;

	public function __construct($order) {
		$this->order = $order;
	}
	
	/**
	 * Compares two transactions according to $this->order.
	 * 
	 * For use with usort type of sort functions.
	 * 
	 * @param $aa object The first Transaction object.
	 * @param $bb object The second Transaction object.
	 * 
	 * @return integer -1 if $aa is smaller than $bb, 0 if they are equal, 1 if $aa is bigger than $bb.
	 */
	public function compare($aa, $bb) {
		$tmp = 0;
	
		$default = 0;
		
		$repeatUnits = array (
			'day' => 1,
			'week' => 2,
			'month' => 3,
			'year' => 4
		);
	
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
			//echo "a: " . $a->getId() . "<br />";
			
			switch ($this->order[$run]['key']) {
				case 'transactionId':
				case 'plannedTransactionId':
				case 'finishedTransactionId':
					$tmp = $a->getId() - $b->getId();
					break;
				
				case 'type':
					$tmp = strncasecmp($a->getType(), $b->getType(), 9999);
					break;

				case 'accountTitle':
					$tmp = strncasecmp($a->getAccount()->getTitle(), $b->getAccount()->getTitle(), 9999);
					break;
	
				case 'title':
					$tmp = strncasecmp($a->getTitle(), $b->getTitle(), 9999);
					//echo $tmp;
					break;
				
				case 'description':
					$tmp = strncasecmp($a->getDescription(), $b->getDescription(), 9999);
					break;
					
				case 'valutaDate':
					if ($a->getValutaDate() && $b->getValutaDate()) {
						$tmp = Date::compare($a->getValutaDate(), $b->getValutaDate());
					} else if ($a->getValutaDate() && !$b->getValutaDate()) {
						$tmp = 1;
					} else if (!$a->getValutaDate() && $b->getValutaDate()) {
						$tmp = -1;
					}
					break;
				
				case 'beginDate':
					$tmp = Date::compare($a->getBeginDate(), $b->getBeginDate());
					break;
				
				case 'endDate':
					if ($a->getEndDate() && $b->getEndDate()) {
						$tmp = Date::compare($a->getEndDate(), $b->getEndDate());
					}
					break;
				
				case 'amount':
					$tmp = $a->getAmount()->compare($b->getAmount());
					break;
		
				case 'outsideCapital':
					$tmp = $a->getOutsideCapital()->sub($b->getOutsideCapital());
					break;
		
				case 'transactionPartner':
					$tmp = strncasecmp($a->getTransactionPartner(), $b->getTransactionPartner(), 9999);
					break;
				
				case 'categoryId':
					if ($a->getCategory() && $b->getCategory()) {
						$tmp = $a->getCategory()->getId() - $b->getCategory()->getId();
					} else if ($a->getCategory() && !$b->getCategory()) {
						$tmp = -1;
					} else if (!$a->getCategory() && $b->getCategory()) {
						$tmp = 1;
					}
					break;
				
				case 'categoryTitle':
					if ($a->getCategory() && $b->getCategory()) {
						$tmp = strncasecmp($a->getCategory()->getTitle(), $b->getCategory()->getTitle(), 9999);
					} else if ($a->getCategory()) {
						$tmp = -1;
					} else if ($b->getCategory()) {
						$tmp = 1;
					}
					//echo "tmp: $tmp</pre>";
					break;
				
				case 'parentCategoryId':
					if ($a->getCategory() && $a->getCategory()->getParent() && $b->getCategory() && $b->getCategory()->getParent()) {
						$tmp = $a->getCategory()->getParent()->getId() - $b->getCategory()->getParent()->getId();
					} else if ($a->getCategory() && $a->getCategory()->getParent()) {
						$tmp = -1;
					} else if ((!$a->getCategory() || !$a->getCategory()->getParent())) {
						$tmp = 1;
					}
					break;
				
				case 'parentCategoryTitle':
					if ($a->getCategory() && $a->getCategory()->getParent() && $b->getCategory() && $b->getCategory()->getParent()) {
						$tmp = strncasecmp($a->getCategory()->getParent()->getTitle(), $b->getCategory()->getParent()->getTitle(), 9999);
					} else if ($a->getCategory() && $a->getCategory()->getParent()) {
						$tmp = -1;
					} else if ($b->getCategory() && $b->getCategory()->getParent()) {
						$tmp = 1;
					}
					//echo "tmp: $tmp</pre>";
					break;
				
				case 'concatCategoryTitle':
					$aTitle = '';
					$bTitle = '';
					if ($a->getCategory() && $a->getCategory()->getParent()) {
						$aTitle = $a->getCategory()->getParent()->getTitle() . ' - ';
					}
					if ($a->getCategory()) {
						$aTitle .= $a->getCategory()->getTitle();
					}
					if ($b->getCategory() && $b->getCategory()->getParent()) {
						$bTitle = $b->getCategory()->getParent()->getTitle() . ' - ';
					}
					if ($b->getCategory()) {
						$bTitle .= $b->getCategory()->getTitle();
					}
					if ($aTitle != '' && $bTitle != '') {
						$tmp = strncasecmp($aTitle, $bTitle, 9999);
					} else if ($aTitle != '') {
						$tmp = -1;
					} else if ($bTitle != '') {
						$tmp = 1;
					}
					//echo "tmp: $tmp</pre>";
					break;
	
				case 'repeatUnit':
					$tmp = $repeatUnits[$a->getRepeatUnit()] - $repeatUnits[$b->getRepeatUnit()];
					break;
				
				case 'repeatFrequency':
					$tmp = $a->getRepeatFrequency() - $b->getRepeatFrequency();
					break;
				
				case 'sum':
					$tmp = 0;
					break;
			}
			
			if ($tmp != 0) {
				return $tmp;
			}
		}
	
		return $default;
	}
}

function getAllTransactions(&$finishedTransactions, $selectedFields, $order, $upperLimit, $lowerLimit) {
	global $logger;
	$logger->log("accountCommon::getAllTransactions");

	$result = array();
	$currResultIndex = 0;

	$sum = new Amount();
	
	$now = new Date();
	$now->setHour(0);
	$now->setMinute(0);
	$now->setSecond(0);
	
	if (isset($order[0]) && $order[0]['key'] == 'valutaDate') {
		$firstOrderValutaDate = true;
		$orderCompareNumber = ($order[0]['dir'] == 'asc' ? -1 : 1);
	} else {
		$firstOrderValutaDate = false;
	}
	$todayMarkerSet = false;
	
	foreach($finishedTransactions as $currentTransaction){
		$logger->log("accountCommon::getAllTransactions foreach");
		$logger->log("accountCommon::getAllTransactions foreach \$sum before add " . $sum->get());
		$sum->add($currentTransaction->getAmount());
		$logger->log("accountCommon::getAllTransactions foreach \$sum after add " . $sum->get());

		$classAmount = ($currentTransaction->getAmount()->compare(0) >= 0) ? 'dgPositiveAmount' : 'dgNegativeAmount'; 
		if ($sum->compare(0) >= 0) {
			if ($upperLimit && $sum->compare($upperLimit) > 0) {
				$classSum = 'dgOverMaxAmount';
			} else {
				$classSum = 'dgPositiveAmount'; 
			}
		} else {
			if ($lowerLimit && $sum->compare($lowerLimit) < 0) {
				$classSum = 'dgUnderMinAmount';
			} else {
				$classSum = 'dgNegativeAmount';
			}
		}
		$logger->log("accountCommon::getAllTransactions foreach \$balance below");
		$balance = $currentTransaction->getBalance(); 
		$logger->log("accountCommon::getAllTransactions foreach \$balance above");
		if ($balance->compare(0) >= 0) {
			if ($upperLimit && $balance->compare($upperLimit) > 0) {
				$classBalance = 'dgOverMaxAmount';
			} else {
				$classBalance = 'dgPositiveAmount'; 
			}
		} else {
			if ($lowerLimit && $balance->compare($lowerLimit) < 0) {
				$classBalance = 'dgUnderMinAmount';
			} else {
				$classBalance = 'dgNegativeAmount';
			}
		}

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

		if (
			$currentTransaction->getType() == 'FinishedTransaction'
			|| $currentTransaction->getType() == 'FinishedTransferalTransaction'
		) {
			$id = $currentTransaction->getId();
		} else {
			$id = 'p' . $currentTransaction->getPlannedTransaction()->getId() . '_' . $currentTransaction->getId();
		}
		
		switch ($currentTransaction->getType()) {
			case 'FinishedTransaction':
				$typeImg = 'Account/finished_transaction.png';
				$typeText = getBadgerTranslation2('Account', 'FinishedTransaction');
				break;
			
			case 'FinishedTransferalTransaction':
				if ($currentTransaction->getTransferalSource()) {
					$typeImg = 'Account/finished_transferal_source_transaction.png';
					$typeText = getBadgerTranslation2('Account', 'FinishedTransferalSourceTransaction');
				} else {
					$typeImg = 'Account/finished_transferal_target_transaction.png';
					$typeText = getBadgerTranslation2('Account', 'FinishedTransferalTargetTransaction');
				}
				break;
			
			case 'PlannedTransaction':
				$typeImg = 'Account/planned_transaction.png';
				$typeText = getBadgerTranslation2('Account', 'PlannedTransaction');
				break;

			case 'PlannedTransferalTransaction':
				if ($currentTransaction->getTransferalSource()) {
					$typeImg = 'Account/planned_transferal_source_transaction.png';
					$typeText = getBadgerTranslation2('Account', 'PlannedTransferalSourceTransaction');
				} else {
					$typeImg = 'Account/planned_transferal_target_transaction.png';
					$typeText = getBadgerTranslation2('Account', 'PlannedTransferalTargetTransaction');
				}
				break;
		}
	
		$result[$currResultIndex] = array();
		if (
			$firstOrderValutaDate
			&& $todayMarkerSet === false
			&& !is_null($currentTransaction->getValutaDate())
			&& Date::compare($now, $currentTransaction->getValutaDate()) == $orderCompareNumber
		) {
			$result[$currResultIndex]['transactionId'] = array (
				'marker' => 'today',
				'content' => $id 
			);
			$todayMarkerSet = true;
		} else {
			$result[$currResultIndex]['transactionId'] = $id; 
		}

		foreach ($selectedFields as $selectedField) {
			switch ($selectedField) {
				case 'type':
					$result[$currResultIndex]['type'] = array (
						'img' => getRelativeTplPath($typeImg),
						'title' => $typeText
					);
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
					$logger->log("accountCommon::getAllTransactions case amount getAmount getFormatted");
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
				
				case 'sum':
					$logger->log("accountCommon::getAllTransactions case sum getFormatted");
					$result[$currResultIndex]['sum'] = array (
						'class' => $classSum,
						'content' => $sum->getFormatted()
					);
					break;

				case 'balance':
					$logger->log("accountCommon::getAllTransactions case balance getBalance getFormatted");
					$result[$currResultIndex]['balance'] = array (
						'class' => $classBalance,
						'content' => $currentTransaction->getBalance()->getFormatted()
					);
					break;
				
				case 'plannedTransactionId':
					$result[$currResultIndex]['plannedTransactionId'] = (is_null($tmp = $currentTransaction->getPlannedTransaction()) ? '' : $tmp->getId());
					break; 

				case 'exceptional':
					$result[$currResultIndex]['exceptional'] = is_null($tmp = $currentTransaction->getExceptional()) ? '' : $tmp;
					break;
				
				case 'periodical':
					$result[$currResultIndex]['periodical'] = is_null($tmp = $currentTransaction->getPeriodical()) ? '' : $tmp;
					break;
				
				case 'accountTitle':
					$result[$currResultIndex]['accountTitle'] = $currentTransaction->getAccount()->getTitle();
					break;
			} //switch
		} //foreach selectedFields
		
		$currResultIndex++;
	} //foreach finishedTransactions

	return $result;		
}

function updateBalances() {
	global $badgerDb;
	
	$accountManager = new AccountManager($badgerDb);
	
	while ($accountManager->getNextAccount());
}

function getTargetFutureCalcDate() {
	global $us;
	
	$result = new Date();
	try {
		$preCalc = $us->getProperty('amountFutureCalcSpan');
		//Convert Months to seconds
		$preCalc *= 30 * 24 * 60 * 60;
	} catch (BadgerException $ex) {
		//Default: One Year
		$preCalc = 1 * 365 * 24 * 60 * 60;
	}
	$result->addSeconds($preCalc);
	
	return $result;
}
?>
