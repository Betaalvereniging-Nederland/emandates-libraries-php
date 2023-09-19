<?php

namespace EMandates\Merchant\Library\Entities;

use EMandates\Merchant\Library\Libraries\{XmlUtility, CommunicatorException};
/**
 * Description of DebtorBank
 */
class DebtorBank {

	/**
	 * Country name
	 * @var string 
	 */
	public $DebtorBankCountry;

	/**
	 * BIC
	 * @var string 
	 */
	public $DebtorBankId;

	/**
	 * Bank name
	 * @var string 
	 */
	public $DebtorBankName;

}

/**
 * Describes a directory response
 */
class DirectoryResponse {

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
	 * \DateTime set to when this directory was last updated
	 * @var \DateTime
	 */
	public $DirectoryDateTimestamp;

	/**
	 *  List of available debtor banks     * 
	 * @var array 
	 */
	public $DebtorBanks = array();

	/**
	 * The response XML
	 * 
	 * @var string 
	 */
	public $RawMessage;

	/**
	 * Deserializes the xml into a DirectoryRes
	 * 
	 * @param string $xml
	 * @throws CommunicatorException
	 */
	public function __construct($xml, $response_xml = false) {
		$this->RawMessage = (!empty($response_xml) || $response_xml === '' ? $response_xml : $xml);
		$data = XmlUtility::parse($xml);

		if ($data->getName() == 'AcquirerErrorRes' || $data->getName() == 'Exception') {
			$this->_buildAcquirerErrorRes($data);
		} else if ($data->getName() != 'DirectoryRes') {
			throw new CommunicatorException($data->getName() . ' was not expected.');
		} else {
			$this->_buildDirectoryRes($data);
		}
	}

	private function _buildAcquirerErrorRes($data) {
		$this->Error = new ErrorResponse($data);
		$this->IsError = true;
		$this->DirectoryDateTimestamp = new \DateTime((string) $data->createDateTimestamp);
		$this->DebtorBanks = null;
	}

	private function _buildDirectoryRes($data) {
		$this->DirectoryDateTimestamp = new \DateTime((string) $data->Directory->DirectoryDateTimestamp);

		if (!empty($data->Directory->Country)) {
			foreach ($data->Directory->Country as $countryElem) {
				if (!empty($countryElem->Issuer)) {
					foreach ($countryElem->Issuer as $IssuerElem) {
						$debtor = new DebtorBank();
						$debtor->DebtorBankId = (string) $IssuerElem->issuerID;
						$debtor->DebtorBankName = (string) $IssuerElem->issuerName;

						$debtor->DebtorBankCountry = (string) $countryElem->countryNames;

						$this->DebtorBanks[] = $debtor;
					}
				}
			}
		}
	}
}