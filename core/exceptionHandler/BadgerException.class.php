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
 * class used to extend the default exception class 
 * 
 * @author baxxter, sperber 
 * @version $LastChangedRevision: 869 $
 */
class BadgerException extends Exception
{
	/**
	 * AdditionalInfo regarding the exception
	 * 
	 * @var string 
	 */
	private $additionalInfo;
  	private $badgerErrorId;
  	private $badgerErrorPage;
   // Redefine the exception so code isn't optional
   	public function __construct($badgerErrorPage, $badgerErrorId, $additionalInfo = NULL) {
		$this->additionalInfo = $additionalInfo;
		$this->badgerErrorPage = $badgerErrorPage;
		$this->badgerErrorId = $badgerErrorId;
       	// call default exception constructor
		parent::__construct($message = NULL, $code = 42);
   	}
 /**
 * function to receive the additionalInfo
 * 
 * @return String
 */
	public function getAdditionalInfo (){
		return $this->additionalInfo;		
	}
/**
 * function to receive the badgerErrorPage
 * 
 * @return String
 */
	public function getBadgerErrorPage (){
		return $this->badgerErrorPage;		
	}

/**
 * function to receive the badgerErrorId
 * 
 * @return String
 */
	public function getBadgerErrorId (){
		return $this->badgerErrorId;		
	}	
}
?>