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
 * Represents a financial amount (of money). Cares for non-rounding arithmetic.
 * 
 * @author Eni Kao, Mampfred
 * @version $LastChangedRevision: 1054 $
 */
class Amount {
	
	/**
	 * The amount.
	 * 
	 * @var string
	 */
	private $amount;

	/**
	 * Creates an amount.
	 * 
	 * @param $amount mixed The new amount, either as Amount or as string.
	 * @param $formatted boolean true if the $amount string is formatted according to UserSettings
	 */
	public function Amount($amount = 0, $formatted = false) {
		bcscale(5);
		
		$this->set($amount, $formatted);
	}
	
	/**
	 * Returns the amount.
	 * 
	 * @return string The amount with no thousands separator, '.' as decimal separator.
	 */
	public function get() {
		return $this->amount;
	}
	
	/**
	 * Returns the formatted amount with two digits. No rounding but truncating occurs.
	 * 
	 * @return string The amount with thousands separator and decimal separator according to user settings. 
	 */
	function getFormatted() {
		global $us;
		
		$decPoint = $us->getProperty('badgerDecimalSeparator');
		$thousandsSep = $us->getProperty('badgerThousandSeparator');
		
		$str = ($this->amount ? $this->amount : '0');
		
		settype($str, 'string');

		$str = trim($str);
		
		//Sort out negative numbers
		if (substr($str, 0, 1) == '-') {
			$negative = true;
			$firstDigit = 1;
		} else {
			$negative = false;
			$firstDigit = 0;
		}
		
		$decPosition = strpos($str, '.');
	
		//if there is a decimal point
		if ($decPosition !== false) {
			//copy at most two fraction digits
			$start = $decPosition - 1;
			$result = $decPoint . substr($str, $decPosition + 1, 2);
		} else {
			$start = strlen($str) - 1;
			$result = $decPoint;
		}
		
		//Pad up to two zeros
		$result .= str_repeat('0', strlen($decPoint) + 2 - strlen($result));
	
		$count = 0;
		
		//Insert thousands separators
		for ($i = $start; $i >= $firstDigit; $i--) {
			if ($count == 3) {
				$result = $thousandsSep . $result;
				
				$count = 0;
			}
	
			$result = substr($str, $i, 1) . $result;
			$count++;	
		}
		
		//Add negative sign
		if ($negative) {
			$result = '-' . $result;
		}
		
		return $result;
	}

	/**
	 * Sets the amount;
	 * 
	 * @param $amount mixed The new amount, either as Amount or as string.
	 * @param $formatted boolean true if the $amount string is formatted according to UserSettings
	 */
	public function set($amount, $formatted = false) {
		global $us;
		
		if ($amount instanceof Amount) {
			$this->amount = $amount->amount;
		} else {
			if ($formatted) {
				$amount = str_replace(
					array ($us->getProperty('badgerThousandSeparator'), $us->getProperty('badgerDecimalSeparator')),
					array ('', '.'),
					$amount
				);
			}
			$this->amount = $amount;
		}
	}
	
	/**
	 * Adds $summand to this amount.
	 * 
	 * @param $summand mixed A number or Amount to add.
	 * @return Amount The new Amount.
	 */
	public function add($summand) {
		if ($summand instanceof Amount) {
			$this->amount = bcadd($this->amount, $summand->get());
		} else {
			$this->amount = bcadd($this->amount, $summand);
		}
		
		return $this;
	}

	/**
	 * Subtracts $subtrahend from this amount.
	 * 
	 * @param $subtrahend mixed A number or Amount to subtract.
	 * @return Amount The new Amount.
	 */
	public function sub($subtrahend) {
		if ($subtrahend instanceof Amount) {
			$this->amount = bcsub($this->amount, $subtrahend->get());
		} else {
			$this->amount = bcsub($this->amount, $subtrahend);
		}
		
		return $this;
	}

	/**
	 * Multiplys this amount by $factor.
	 * 
	 * @param $factor mixed A number or Amount to multiply by.
	 * @return Amount The new Amount.
	 */
	public function mul($factor) {
		if ($factor instanceof Amount) {
			$this->amount = bcmul($this->amount, $factor->get());
		} else {
			$this->amount = bcmul($this->amount, $factor);
		}
		
		return $this;
	}

	/**
	 * Divides this amount by $divisor.
	 * 
	 * @param $divisor mixed A number or Amount to divide by.
	 * @return Amount The new Amount.
	 */
	public function div($divisor) {
		if ($divisor instanceof Amount) {
			$this->amount = bcdiv($this->amount, $divisor->get());
		} else {
			$this->amount = bcdiv($this->amount, $divisor);
		}
		
		return $this;
	}
	
	/**
	 * Compares this Amount to $b.
	 * 
	 * @param $b object The Amount object to compare with.
	 * @return integer -1 if this is smaller than $b, 0 if they are equal, 1 if this is bigger than $b.
	 */
	public function compare($b) {
		if ($b instanceof Amount) {
			return bccomp($this->amount, $b->amount);
		} else {
			return bccomp($this->amount, $b);
		}
	}
	
	public function abs() {
		if (bccomp($this->amount, 0) < 0) {
			$this->amount = bcmul($this->amount, -1);
		}
		
		return $this;
	}
}
?>
