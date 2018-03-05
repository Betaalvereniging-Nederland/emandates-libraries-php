<?php

/**
 * AquirerTrxReq description
 */
class AcquirerTrxRequest {

	const XMLNS = "http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0";
	const XSD = "http://www.w3.org/2001/XMLSchema";
	const XSI = "http://www.w3.org/2001/XMLSchema-instance";

	public $productID;
	public $version;
	public $dateTime;

	/**
	 * The merchant object
	 * @var AcquirerTrxReqMerchant 
	 */
	public $merchant;

	/**
	 * The Bank id
	 * @var string 
	 */
	public $issuerID;

	/**
	 *
	 * @var AcquirerTrxReqTransaction 
	 */
	public $Transaction;
	
	/**
	 * Constructor that highlights the required fields
	 * 
	 * @param string $productID
	 * @param string $version
	 * @param AcquirerTrxReqMerchant $merchant
	 * @param string $issuerID
	 * @param AcquirerTrxReqTransaction $Transaction
	 */
	public function __construct($productID, $version, AcquirerTrxReqMerchant $merchant, $issuerID, AcquirerTrxReqTransaction $Transaction) {
		$this->productID = $productID;
		$this->version = $version;
		$this->dateTime = date('Y-m-d\TH:i:s'.substr((string)microtime(), 1, 4).'\Z');
		$this->merchant = $merchant;
		$this->issuerID = $issuerID;
		$this->Transaction = $Transaction;
	}
	
	/**
	 * Serializes the object into an AcquirerTrxReq
	 * 
	 * @param string $LocalInstrumentCode
	 * @return \DOMDocument
	 */
	public function toXml($LocalInstrumentCode) {
		$domtree = new DOMDocument('1.0', 'UTF-8');

		/* create the root element of the xml tree */
		$AcquirerTrxReq = $domtree->createElementNS(self::XMLNS, 'AcquirerTrxReq');
		$AcquirerTrxReq->setAttribute('xmlns:xsd', self::XSD);
		$AcquirerTrxReq->setAttribute('xmlns:xsi', self::XSI);

		$AcquirerTrxReq->setAttribute('productID', $this->productID);
		$AcquirerTrxReq->setAttribute('version', $this->version);
		$domtree->appendChild($AcquirerTrxReq);

		/* create the timetamp */
		$createDateTimestamp = $domtree->createElement('createDateTimestamp', $this->dateTime);
		$AcquirerTrxReq->appendChild($createDateTimestamp);

		/* create the Issuer */
		$issuer = $domtree->createElement('Issuer'); {
			$issuer->appendChild(new DOMElement('issuerID', $this->issuerID));
		}
		$AcquirerTrxReq->appendChild($issuer);

		/* create the merchant element and it's sub elements */
		$Merchant = $domtree->createElement('Merchant'); {
			$Merchant->appendChild($domtree->createElement('merchantID', $this->merchant->merchantID));
			$Merchant->appendChild($domtree->createElement('subID', $this->merchant->merchantSubID));
			$Merchant->appendChild($domtree->createElement('merchantReturnURL', $this->merchant->merchantReturnURL));
		}
		$AcquirerTrxReq->appendChild($Merchant);

		/* create the Transaction elem */
		$AcquirerTrxReq->appendChild($domtree->importNode($this->Transaction->toXML($LocalInstrumentCode), true));

		return $domtree;
	}

}

/**
 * Description of Merchant
 */
class AcquirerTrxReqMerchant {

	public $merchantID;
	public $merchantSubID;
	public $merchantReturnURL;
	
	/**
	 * Constructor that highlights the required fields
	 * 
	 * @param string $merchantID
	 * @param string $merchantSubID
	 * @param string $merchantReturnURL
	 */
	public function __construct($merchantID, $merchantSubID, $merchantReturnURL) {
		$this->merchantID = $merchantID;
		$this->merchantSubID = $merchantSubID;
		$this->merchantReturnURL = $merchantReturnURL;
	}

}

class AcquirerTrxReqTransaction {

	public $entranceCode;
	public $expirationPeriod;
	public $language;

	/**
	 *
	 * @var NewMandateRequest | AmendmentRequest | CancellationRequest 
	 */
	public $container;
	
	/**
	 * Constructor that highlights the required fields
	 * 
	 * @param string $entranceCode
	 * @param DateInterval $expirationPeriod
	 * @param string $language
	 * @param NewMandateRequest | AmendmentRequest | CancellationRequest $container
	 */
	public function __construct($entranceCode, $expirationPeriod, $language, $container) {
		$this->entranceCode = $entranceCode;
		$this->expirationPeriod = $expirationPeriod;
		$this->language = $language;
		$this->container = $container;
	}
	
	/**
	 * Serializes the object into a Transaction
	 * 
	 * @param string $LocalInstrumentCode
	 * @return DOMElement
	 */
	public function toXml($LocalInstrumentCode) {
		$domtree = new DOMDocument('1.0', 'UTF-8');

		/* create the Transaction elem */
		$Transaction = $domtree->createElement('Transaction');

		if (!empty($this->expirationPeriod)) {
			$expirationPeriod = $domtree->createElement('expirationPeriod', $this->DateIntervalToStr($this->expirationPeriod));
			$Transaction->appendChild($expirationPeriod);
		}

		$language = $domtree->createElement('language', $this->language);
		$Transaction->appendChild($language);

		$entranceCode = $domtree->createElement('entranceCode', $this->entranceCode);
		$Transaction->appendChild($entranceCode);

		/* create the container element */
		$container = $domtree->createElement('container');
		$container->appendChild($domtree->importNode($this->container->toXML($LocalInstrumentCode), true));
		$Transaction->appendChild($container);

		return $Transaction;
	}
	
	/**
	 * Returns the str representation of a DateInterval
	 * 
	 * @param DateInterval $dateInterval
	 * @return string
	 */
	private function DateIntervalToStr($dateInterval) {
		$interval = 'P';
		
		$allowedKeys = array('y', 'm', 'd', 'h', 'i', 's');
		$timeKeys = array('h', 'i', 's');
		$addedTime = false;

		foreach (get_object_vars($dateInterval) as $key => $value) {
			if (in_array($key, $allowedKeys)) {
				if (in_array($key, $timeKeys) && !$addedTime && !empty($value)) {
					$interval .= 'T';
					$addedTime = true;
				}
				if (!empty($value)) {
					$interval .= $value . ( $key == 'i' ? 'M' : strtoupper($key));
				}
			}
		}

		return $interval;
	}
	
}
