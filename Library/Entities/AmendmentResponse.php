<?php

namespace EMandates\Merchant\Library\Entities;

use EMandates\Merchant\Library\Libraries\{CommunicatorException, XmlUtility};

/**
 * Represents an amendment response
 */
class AmendmentResponse {

	/**
	 * true if an error occurred, or false when no errors were encountered
	 * @var bool
	 */
	public $IsError = false;

	/**
	 * Object that holds the error if one occurs; when there are no errors, this is set to null
	 * @var ErrorResponse 
	 */
	public $Error = null;

	/**
	 * The URL to which to redirect the creditor so they can authorize the transaction
	 * @var string
	 */
	public $IssuerAuthenticationUrl = null;

	/**
	 * The transaction ID
	 * @var string
	 */
	public $TransactionId = null;

	/**
	 * \DateTime set to when this transaction was created
	 * @var \DateTime
	 */
	public $TransactionCreateDateTimestamp;

	/**
	 * The response XML
	 * 
	 * @var string 
	 */
	public $RawMessage;

	/**
	 * Deserializes the xml provided into an AmendmentResponse
	 * 
	 * @param string $xml
	 * @throws CommunicatorException
	 */
	public function __construct($xml, $response_xml = false) {
		$this->RawMessage = (!empty($response_xml) || $response_xml === '' ? $response_xml : $xml);
		$data = XmlUtility::parse($xml);

		if ($data->getName() == 'AcquirerErrorRes' || $data->getName() == 'Exception') {
			$this->_buildAcquirerErrorRes($data);
		} else if ($data->getName() != 'AcquirerTrxRes') {
			throw new CommunicatorException($data->getName() . ' was not expected.');
		} else {
			$this->_buildAmendmentRes($data);
		}
	}

	private function _buildAcquirerErrorRes($data) {
		$this->Error = new ErrorResponse($data);
		$this->IsError = true;
		$this->TransactionCreateDateTimestamp = new \DateTime((string) $data->createDateTimestamp);
	}

	private function _buildAmendmentRes($data) {
		$this->IssuerAuthenticationUrl = (string) $data->Issuer->issuerAuthenticationURL;
		$this->TransactionId = (string) $data->Transaction->transactionID;

		$this->TransactionCreateDateTimestamp = new \DateTime((string) $data->Transaction->transactionCreateDateTimestamp);
	}
}
