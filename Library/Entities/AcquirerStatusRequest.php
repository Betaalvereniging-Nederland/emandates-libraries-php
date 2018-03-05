<?php

/**
 * Describes a status request
 *
 */
class AcquirerStatusRequest {

	const XMLNS = "http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0";
	const XSD = "http://www.w3.org/2001/XMLSchema";
	const XSI = "http://www.w3.org/2001/XMLSchema-instance";

	public $productID;
	public $version;
	public $dateTime;

	/**
	 * The merchant object
	 * @var AcquirerStatusReqMerchant 
	 */
	public $merchant;

	/**
	 * The transaction ID to check
	 * @var AcquirerStatusReqTransaction
	 */
	public $Transaction;

	/**
	 * Constructor that highlights all required fields for this object
	 * 	 
	 * @param string $productID
	 * @param string $version
	 * @param AcquirerStatusReqMerchant $merchant
	 * @param AcquirerStatusReqTransaction $Transaction
	 */
	function __construct($productID, $version, AcquirerStatusReqMerchant $merchant, AcquirerStatusReqTransaction $Transaction) {
		$this->productID = $productID;
		$this->version = $version;
		$this->dateTime = date('Y-m-d\TH:i:s' . substr((string) microtime(), 1, 4) . '\Z');
		$this->merchant = $merchant;
		$this->Transaction = $Transaction;
	}

	/**
	 * Serializes the object into an AcquirerStatusReq
	 * 
	 * @return \DOMDocument
	 */
	public function toXml() {
		$domtree = new DOMDocument('1.0', 'UTF-8');

		/* create the root element of the xml tree */
		$AcquirerStatusReq = $domtree->createElementNS(self::XMLNS, 'AcquirerStatusReq');
		$AcquirerStatusReq->setAttribute('xmlns:xsd', self::XSD);
		$AcquirerStatusReq->setAttribute('xmlns:xsi', self::XSI);

		$AcquirerStatusReq->setAttribute('productID', $this->productID);
		$AcquirerStatusReq->setAttribute('version', $this->version);
		$domtree->appendChild($AcquirerStatusReq);

		/* create the timetamp */
		$createDateTimestamp = $domtree->createElement('createDateTimestamp', $this->dateTime);
		$AcquirerStatusReq->appendChild($createDateTimestamp);

		/* create the merchant element and it's sub elements */
		$Merchant = $domtree->createElement('Merchant'); {
			$Merchant->appendChild($domtree->createElement('merchantID', $this->merchant->merchantID));
			$Merchant->appendChild($domtree->createElement('subID', $this->merchant->merchantSubID));
		}
		$AcquirerStatusReq->appendChild($Merchant);

		/* create the Transaction elem */
		$AcquirerStatusReq->appendChild($domtree->importNode($this->Transaction->toXML(), true));

		return $domtree;
	}

}

/**
 * Description of Merchant
 *
 */
class AcquirerStatusReqMerchant {

	public $merchantID;
	public $merchantSubID;

	/**
	 * Constructor that highlights the required fields
	 * 
	 * @param string $merchantID
	 * @param string $merchantSubID
	 */
	public function __construct($merchantID, $merchantSubID) {
		$this->merchantID = $merchantID;
		$this->merchantSubID = $merchantSubID;
	}

}

/**
 * Description of Transaction
 *
 */
class AcquirerStatusReqTransaction {

	public $transactionID;

	/**
	 * Constructor that highlights the required fields
	 * 
	 * @param string $transactionID
	 */
	function __construct($transactionID) {
		$this->transactionID = $transactionID;
	}

	/**
	 * Serializes the object into a Transaction
	 * 
	 * @return DOMElement
	 */
	public function toXML() {
		$domtree = new DOMDocument('1.0', 'UTF-8');

		/* create the Transaction elem */
		$Transaction = $domtree->createElement('Transaction');

		/* create the transactionID elem */
		$transactionID = $domtree->createElement('transactionID', $this->transactionID);
		$Transaction->appendChild($transactionID);

		return $Transaction;
	}

}

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
