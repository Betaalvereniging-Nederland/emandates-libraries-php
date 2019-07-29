<?php

/**
 * Represents a status response
 */
class AcquirerStatusResponse {

	const STATUS_OPEN = "Open";
	const STATUS_PENDING = "Pending";
	const STATUS_SUCCESS = "Success";
	const STATUS_FAILURE = "Failure";
	const STATUS_EXPIRED = "Expired";
	const STATUS_CANCELLED = "Cancelled";

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
	 * The transaction ID
	 * @var type 
	 */
	public $TransactionId = null;

	/**
	 * Possible values: Open, Pending, Success, Failure, Expired, Cancelled
	 * @var string 
	 */
	public $Status = null;

	/**
	 * DateTime when the status was created, or null if no such date available (for example, when mandate has expired)
	 * @var DateTime 
	 */
	public $StatusDateTimestamp = null;

	/**
	 * The acceptance report returned in the status response
	 * @var AcceptanceReport 
	 */
	public $AcceptanceReport;

	/**
	 * The object used to log internal messages and the xml files
	 * 
	 * @var Logger
	 */
	public $logger;

	/**
	 * The response XML
	 * 
	 * @var string 
	 */
	public $RawMessage;

	/**
	 * Deserializes the xml provided into a AcquirerStatusRes
	 * 
	 * @param string $xml
	 * @throws CommunicatorException
	 */
	public function __construct($xml, $logger, $response_xml = false) {
		$this->logger = $logger;
		
		$this->RawMessage = (!empty($response_xml) || $response_xml === '' ? $response_xml : $xml);
		$data = XmlUtility::parse($xml);
		
		if ($data->getName() == 'AcquirerErrorRes' || $data->getName() == 'Exception') {
			$this->_buildAcquirerErrorRes($data);
		} else if ($data->getName() != 'AcquirerStatusRes') {
			throw new CommunicatorException($data->getName() . ' was not expected.');
		} else {
			$this->Status = (string) $data->Transaction->status;

			if ($this->Status == AcquirerStatusResponse::STATUS_SUCCESS) {
				//validate status response p012
				$this->_validateResponseDocument($xml);
			}
			$this->_buildStatusResponse($data);
		}
	}

	private function _buildAcquirerErrorRes($data) {
		$this->Error = new ErrorResponse($data);
		$this->IsError = true;
	}

	private function _buildStatusResponse($data) {
		$this->TransactionId = (string) $data->Transaction->transactionID;
		$this->StatusDateTimestamp = new DateTime((string) $data->Directory->DirectoryDateTimestamp);
		
		if ($this->Status == AcquirerStatusResponse::STATUS_SUCCESS) {
			$temp_dom = new DOMDocument('1.0', 'UTF-8');
			$temp_dom->loadXML($data->asXML());
			$Document = $temp_dom->getElementsByTagName('Document');

			$domtree = new DOMDocument('1.0', 'UTF-8');
			$domtree->appendChild($domtree->importNode($Document->item(0), true));
		
			$this->AcceptanceReport = new AcceptanceReport($domtree);
		}
	}

	/**
	 * Validates the Document of the xml provided
	 * 
	 * @param string $xml
	 */
	private function _validateResponseDocument($xml) {
		$temp_dom = new DOMDocument('1.0', 'UTF-8');
		$temp_dom->loadXML($xml);
		$Document = $temp_dom->getElementsByTagName('Document');

		$domtree = new DOMDocument('1.0', 'UTF-8');
		$domtree->appendChild($domtree->importNode($Document->item(0), true));

		XmlValidator::isValidatXML($domtree->saveXML(), XmlValidator::SCHEMA_PAIN012, $this->logger);
	}

}

/**
 * Received as part of a status response, corresponding to the pain.012 message
 */
class AcceptanceReport {

	/**
	 * Message Identification
	 * @var string 
	 */
	public $MessageId;

	/**
	 * Message timestamp
	 * @var DateTime
	 */
	public $DateTime;

	/**
	 * Validation reference
	 * @var string
	 */
	public $ValidationReference;

	/**
	 * Original Message ID
	 * @var string 
	 */
	public $OriginalMessageId;

	/**
	 * Refers to the type of validation request that preceded the acceptance report
	 * @var string
	 */
	public $MessageNameId;

	/**
	 * Whether or not the mandate is accepted by the debtor	 
	 * @var bool 
	 */
	public $AcceptedResult;

	/**
	 * Original mandate ID
	 * @var string
	 */
	public $OriginalMandateId;

	/**
	 * Mandate request ID
	 * @var string
	 */
	public $MandateRequestId;

	/**
	 * SEPA
	 * @var string
	 */
	public $ServiceLevelCode;

	/**
	 * Core or B2B
	 * @var string
	 */
	public $LocalInstrumentCode;

	/**
	 * Sequence Type: sequenceType or one-off
	 * @var string
	 */
	public $SequenceType;

	/**
	 * Maximum amount
	 * @var string
	 */
	public $MaxAmount;

	/**
	 * Reason for eMandate
	 * @var string
	 */
	public $eMandateReason;

	/**
	 * Direct Debit ID of the Creditor
	 * @var string
	 */
	public $CreditorId;

	/**
	 * SEPA
	 * @var string
	 */
	public $SchemeName;

	/**
	 * Name of the Creditor
	 * @var string
	 */
	public $CreditorName;

	/**
	 * Country of the postal address of the Creditor
	 * @var string
	 */
	public $CreditorCountry;

	/**
	 * The Creditor’s address: P.O. Box or street name + building + add-on + Postcode + City.
	 * Second Address line only to be used if 70 chars are exceeded in the first line
	 * @var array(string)
	 */
	public $CreditorAddressLine = array();

	/**
	 * Name of the company (or daughter-company, or label etc.) for which the Creditor is processing eMandates.
	 * May only be used when meaningfully different from CreditorName
	 * @var string
	 */
	public $CreditorTradeName;

	/**
	 * Account holder name of the account that is used for the eMandate
	 * @var string
	 */
	public $DebtorAccountName;

	/**
	 * Reference ID that identifies the Debtor to the Creditor. Issued by the Creditor
	 * @var string
	 */
	public $DebtorReference;

	/**
	 * Debtor’s bank account number
	 * @var string
	 */
	public $DebtorIBAN;

	/**
	 * BIC of the Debtor bank
	 * @var string
	 */
	public $DebtorBankId;

	/**
	 * Name of the person signing the eMandate. In case of multiple signing, all signer names must be included in this field, separated by commas.
	 * If the total would exceed the maximum of 70 characters, the names are cut off at 65 characters and “e.a.” is added after the last name.
	 * @var string
	 */
	public $DebtorSignerName;
	
	/**
	 * The response XML
	 * 
	 * @var string 
	 */
	public $RawMessage;

	/**
	 * Deserializes the data into an AcceptanceReport
	 * 
	 * @param SimpleXMLElement $data
	 */
	public function __construct($data) {
		$xpath = new DOMXpath($data);
		$xpath->registerNamespace("p", $data->documentElement->namespaceURI);
		
		$grpHdr = '/p:Document/p:MndtAccptncRpt/p:GrpHdr';
		$accDtls = '/p:Document/p:MndtAccptncRpt/p:UndrlygAccptncDtls';
		
		$this->MessageId = (string) $xpath->evaluate($grpHdr . '/p:MsgId')->item(0)->nodeValue;
		$this->DateTime = new DateTime((string) $xpath->evaluate($grpHdr . '/p:CreDtTm')->item(0)->nodeValue );
		$this->ValidationReference = (string) $xpath->evaluate($grpHdr . '/p:Authstn/p:Prtry')->item(0)->nodeValue;
		$this->OriginalMessageId = (string) $xpath->evaluate($accDtls . '/p:OrgnlMsgInf/p:MsgId')->item(0)->nodeValue;
		$this->MessageNameId = (string) $xpath->evaluate($accDtls . '/p:OrgnlMsgInf/p:MsgNmId')->item(0)->nodeValue;
		$this->AcceptedResult = (string) $xpath->evaluate($accDtls . '/p:AccptncRslt/p:Accptd')->item(0)->nodeValue;
		
		$origMndt = $accDtls . '/p:OrgnlMndt/p:OrgnlMndt';

		$this->OriginalMandateId = (string) $xpath->evaluate($origMndt . '/p:MndtId')->item(0)->nodeValue;
		$this->MandateRequestId = (string) $xpath->evaluate($origMndt . '/p:MndtReqId')->item(0)->nodeValue;
		$this->LocalInstrumentCode = (string) $xpath->evaluate($origMndt . '/p:Tp/p:LclInstrm/p:Cd')->item(0)->nodeValue;
		$this->SequenceType = (string) $xpath->evaluate($origMndt . '/p:Ocrncs/p:SeqTp')->item(0)->nodeValue;
		$this->ServiceLevelCode = (string) $xpath->evaluate($origMndt . '/p:Tp/p:SvcLvl/p:Cd')->item(0)->nodeValue;
		$this->CreditorId = (string) $xpath->evaluate($origMndt . '/p:CdtrSchmeId/p:Id/p:PrvtId/p:Othr/p:Id')->item(0)->nodeValue;
		$this->SchemeName = (string) $xpath->evaluate($origMndt . '/p:CdtrSchmeId/p:Id/p:PrvtId/p:Othr/p:SchmeNm/p:Cd')->item(0)->nodeValue;
		$this->CreditorName = (string) $xpath->evaluate($origMndt . '/p:Cdtr/p:Nm')->item(0)->nodeValue;
		$this->CreditorCountry = (string) $xpath->evaluate($origMndt . '/p:Cdtr/p:PstlAdr/p:Ctry')->item(0)->nodeValue;
		$this->CreditorAddressLine = (string) $xpath->evaluate($origMndt . '/p:Cdtr/p:PstlAdr/p:AdrLine')->item(0)->nodeValue;
		$this->DebtorAccountName = (string) $xpath->evaluate($origMndt . '/p:Dbtr/p:Nm')->item(0)->nodeValue;;
		$this->DebtorIBAN = (string) $xpath->evaluate($origMndt . '/p:DbtrAcct/p:Id/p:IBAN')->item(0)->nodeValue;
		$this->DebtorBankId = (string) $xpath->evaluate($origMndt . '/p:DbtrAgt/p:FinInstnId/p:BICFI')->item(0)->nodeValue;
		$this->DebtorSignerName = (string) $xpath->evaluate($origMndt . '/p:UltmtDbtr/p:Nm')->item(0)->nodeValue;

		$this->_processOptionalFields($xpath, $origMndt);
		
		$this->RawMessage = $data->saveXML();
	}

	private function _processOptionalFields($xpath, $origMndt) {
        $result = $xpath->evaluate($origMndt . '/p:MaxAmt/p:Value');
        if ($result->length != 0) {
            $this->MaxAmount = (string) $result->item(0)->nodeValue;
        } else {
            $result = $xpath->evaluate($origMndt . '/p:MaxAmt');
            if ($result->length != 0) {
                $this->MaxAmount = (string) $result->item(0)->nodeValue;
            } else {
                unset($this->MaxAmount);
            }
        }

		$result = $xpath->evaluate($origMndt . '/p:Rsn/p:Prtry');
		if ($result->length != 0)
			$this->eMandateReason = (string) $result->item(0)->nodeValue;
		else
			unset($this->eMandateReason);

		$result = $xpath->evaluate($origMndt . '/p:UltmtCdtr/p:Nm');
		if ($result->length != 0)
			$this->CreditorTradeName = (string) $result->item(0)->nodeValue;
		else
			unset($this->CreditorTradeName);

		$result = $xpath->evaluate($origMndt . '/p:Dbtr/p:Id/p:PrvtId/p:Othr/p:Id');
		if ($result->length != 0)
			$this->DebtorReference = (string) $result->item(0)->nodeValue;
		else
			unset($this->DebtorReference);
	}
}
