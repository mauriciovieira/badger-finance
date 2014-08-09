<?php
/*
* ____          _____   _____ ______ _____  
*|  _ \   /\   |  __ \ / ____|  ____|  __ \ 
*| |_) | /  \  | |  | | |  __| |__  | |__) |
*|  _ < / /\ \ | |  | | | |_ |  __| |  _  / 
*| |_) / ____ \| |__| | |__| | |____| | \ \ 
*|____/_/    \_\_____/ \_____|______|_|  \_\
* Open Source Finance Management
* Visit http://www.badger-finance.org 
*
**/

//Retrieve md5´ed password from user settings
$readoutpassword = $us->getProperty('badgerPassword');
$passwordcorrect = false;
if (isset($_session['password']) && $readoutpassword == $_session['password'])
	{
		$passwordcorrect = true;
	}
	elseif(isset($_POST['password']) && md5(getGPC($_POST, 'password')) == $readoutpassword )
		{
			$passwordcorrect = true;
			//create session variable
			set_session_var('password',md5(getGPC($_POST, 'password')));
		};

if($passwordcorrect != true){
	die( getBadgerTranslation2('badger_login','backend_not_login') );
	}

?>