<?php

namespace EMandates\Merchant\Library\Entities;

/**
 * Describes a status request
 */
class StatusRequest {

	/**
	 * The transaction ID to check
	 * @var string 
	 */
	public $TransactionId;

	/**
	 * Constructor that highlights all required fields for this object
	 * 
	 * @param string $TransactionId
	 */
	function __construct($TransactionId) {
		$this->TransactionId = $TransactionId;
	}

}
