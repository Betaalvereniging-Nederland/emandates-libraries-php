<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CommunicatorException
 */
class CommunicatorException extends Exception {
	
	/**
	 * Createst an Exception with custom message so it can be parsed by the ErrorResponse
	 * 
	 * @param string $message
	 * @param string | int $code
	 */
	public function __construct($message, $code = false) {
		// some code
		$new_message = "
			<Exception>
				<Error>
					<errorCode>$code</errorCode>
					<errorMessage>$message</errorMessage>
				</Error>
			</Exception>
		";
		// make sure everything is assigned properly
		parent::__construct($new_message, $code);
	}

}
