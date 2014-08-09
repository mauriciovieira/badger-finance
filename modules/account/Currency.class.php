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
 * A currency.
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 869 $
 */
class Currency {

	/**
	 * The ID of the currency in the database.
	 * 
	 * @var integer
	 */
	private $id;
	
	/**
	 * The symbol of the currency (e. g. EUR, USD).
	 * 
	 * @var string
	 */
	private $symbol;
	
	/**
	 * The long name of the currency (e. g. Euro United States Dollar).
	 * 
	 * @var string
	 */
	private $longName;
	
	/**
	 * The DB object.
	 * 
	 * @var object DB
	 */
	private $badgerDb;
	
	/**
	 * The CurrencyManager this currency belongs to.
	 * 
	 * @var object CurrencyManager
	 */
	private $currencyManager;

	/**
	 * Creates a currency.
	 * 
	 * @param $badgerDb object The DB object.
	 * @param $currencyManager object The CurrencyManager object who created this Currency.
	 * @param $data mixed An associative array with the values out of the DB OR the id of the Currency.
	 * @param $symbol string The symbol of the currency.
	 * @param $longName string The long name of the currency.
	 */
	public function __construct(&$badgerDb, &$currencyManager, $data, $symbol = null, $longName = null) {
    	$this->badgerDb = $badgerDb;
    	$this->currencyManager = $currencyManager;

    	if (is_array($data)) {
    		$this->id = $data['currency_id'];
    		$this->symbol = $data['symbol'];
    		$this->longName = $data['long_name'];
    	} else {
			$this->id = $data;
			$this->symbol = $symbol;
			$this->longName = $longName;
    	}
	}
	
	/**
	 * Returns the ID.
	 * 
	 * @return integer The ID of the currency.
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Returns the symbol.
	 * 
	 * @return string The symbol of the currency.
	 */
	public function getSymbol() {
		return $this->symbol;
	}
	
	/**
	 * Sets the symbol.
	 * 
	 * @param string $symbol The symbol of the currency.
	 */
 	public function setSymbol($symbol) {
		$this->symbol = $symbol;
		
		$sql = "UPDATE currency
			SET symbol = '" . $this->badgerDb->escapeSimple($symbol) . "'
			WHERE currency_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Currency', 'SQLError', $dbResult->getMessage());
		}
	}

	/**
	 * Returns the long name.
	 * 
	 * @return string The long name of the currency.
	 */
	public function getLongName() {
		return $this->longName;
	}

	/**
	 * Sets the long name.
	 * 
	 * @param string $longName The long name of the currency.
	 */
 	public function setLongName($longName) {
		$this->longName = $longName;
		
		$sql = "UPDATE currency
			SET long_name = '" . $this->badgerDb->escapeSimple($longName) . "'
			WHERE currency_id = " . $this->id;
	
		$dbResult =& $this->badgerDb->query($sql);
		
		if (PEAR::isError($dbResult)) {
			//echo "SQL Error: " . $dbResult->getMessage();
			throw new BadgerException('Currency', 'SQLError', $dbResult->getMessage());
		}
	}
}
?>