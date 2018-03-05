<?php

/**
 * Description of DirectoryRequest
 */
class DirectoryRequest {
	const XMLNS = "http://www.betaalvereniging.nl/iDx/messages/Merchant-Acquirer/1.0.0";
	const XSD = "http://www.w3.org/2001/XMLSchema";
	const XSI = "http://www.w3.org/2001/XMLSchema-instance";
	
	public $productID;
	public $version;
	public $dateTime;
	public $merchant;
	
	/**
	 * Constructor that highlights the required fields
	 * 
	 * @param string $productID
	 * @param string $version
	 * @param Merchant $merchant
	 */
	public function __construct($productID, $version, Merchant $merchant) {
		$this->productID = $productID;
		$this->version = $version;
		$this->dateTime = date('Y-m-d\TH:i:s'.substr((string)microtime(), 1, 4).'\Z');
		$this->merchant = $merchant;
	}
	
	/**
	 * Serializes the object into a DirectoryReq
	 * 
	 * @return \DOMDocument
	 */
	public function toXml() {
		$domtree = new DOMDocument('1.0', 'UTF-8');

		/* create the root element of the xml tree */
		$DirectoryReq = $domtree->createElementNS(self::XMLNS, 'DirectoryReq');
		$DirectoryReq->setAttribute('xmlns:xsd', self::XSD);
		$DirectoryReq->setAttribute('xmlns:xsi', self::XSI);

		$DirectoryReq->setAttribute('productID', $this->productID);
		$DirectoryReq->setAttribute('version', $this->version);
		$domtree->appendChild($DirectoryReq);

		/* create the timetamp */
		$createDateTimestamp = $domtree->createElement('createDateTimestamp', $this->dateTime);
		$DirectoryReq->appendChild($createDateTimestamp);

		/* create the merchant element and it's sub elements */
		$Merchant = $domtree->createElement('Merchant');
		$Merchant->appendChild($domtree->createElement('merchantID', $this->merchant->merchantID));
		$Merchant->appendChild($domtree->createElement('subID', $this->merchant->merchantSubID));
		$DirectoryReq->appendChild($Merchant);
		//-----[END] Build the XML 

		return $domtree;
	}

}

/**
 * Description of Merchant
 */
class Merchant {

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
