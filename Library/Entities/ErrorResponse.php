<?php

namespace EMandates\Merchant\Library\Entities;

/**
 * Describes an error response
 */
class ErrorResponse {

	/**
	 * Unique identification of the error occurring within the iDx transaction
	 * @var string 
	 */
	public $ErrorCode;

	/**
	 * Descriptive text accompanying Error.errorCode
	 * @var string 
	 */
	public $ErrorMessage;

	/**
	 * Details of the error
	 * @var string 
	 */
	public $ErrorDetails;

	/**
	 * Suggestions aimed at resolving the problem
	 * @var string 
	 */
	public $SuggestedAction;

	/**
	 * A (standardised) message that the merchant should show to the consumer
	 * @var string 
	 */
	public $ConsumerMessage;
	
	/**
	 * Builds an ErrorResponse from the errRes received
	 * 
	 * @param \SimpleXMLElement $errRes
	 */
	public function __construct(\SimpleXMLElement $errRes) {

		$this->ErrorCode = (string) $errRes->Error->errorCode;
		$this->ErrorMessage = (string) $errRes->Error->errorMessage;
		$this->ErrorDetails = (string) $errRes->Error->errorDetail;
		$this->SuggestedAction = (string) $errRes->Error->suggestedAction;
		$this->ConsumerMessage = (string) $errRes->Error->consumerMessage;
	}

}
