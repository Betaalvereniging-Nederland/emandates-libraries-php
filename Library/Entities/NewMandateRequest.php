<?php

namespace EMandates\Merchant\Library\Entities;

use EMandates\Merchant\Library\B2BCommunicator;

use EMandates\Merchant\Library\Libraries\{MessageIdGenerator, XmlValidator, CommunicatorException};

/**
 * Description of eMandate
 */
class NewMandateRequest {

	const XMLNS = "urn:iso:std:iso:20022:tech:xsd:pain.009.001.04";
	const XSD = "http://www.w3.org/2001/XMLSchema";
	const XSI = "http://www.w3.org/2001/XMLSchema-instance";
	const NOT_PROVIDED = 'NOTPROVIDED';
	const SEPA = 'SEPA';
	const CCY = 'EUR';

	/**
	 *  An 'authentication identifier' to facilitate continuation of the session between creditor and debtor, even
	 * if the existing session has been lost. It enables the creditor to recognise the debtor associated with a (completed) transaction.
	 * @var string 
	 */
	public $EntranceCode;

	/**
	 * This field enables the debtor bank's site to select the debtor's preferred language (e.g. the language selected on the creditor's site),
	 * if the debtor bank's site supports this: Dutch = 'nl', English = 'en'
	 * @var string
	 */
	public $Language;

	/**
	 * Message ID for pain message
	 * @var string 
	 */
	public $MessageId;

	/**
	 * ID that identifies the mandate and is issued by the creditor
	 * @var string 
	 */
	public $eMandateId;

	/**
	 * Reason of the mandate
	 * @var string 
	 */
	public $eMandateReason;

	/**
	 * Reference ID that identifies the debtor to creditor, which is issued by the creditor
	 * @var string 
	 */
	public $DebtorReference;

	/**
	 * BIC of the Debtor Bank
	 * @var string 
	 */
	public $DebtorBankId;

	/**
	 * A purchaseID that acts as a reference from eMandate to the purchase-order
	 * @var string 
	 */
	public $PurchaseId;

	/**
	 * Indicates type of eMandate: one-off or sequenceType direct debit.
	 * @var string 
	 */
	public $SequenceType;

	/**
	 * Optional: The period of validity of the transaction request as stated by the creditor measured from the receipt by the debtor bank.
	 * The debtor must authorise the transaction within this period.
	 * @var \DateInterval 
	 */
	public $ExpirationPeriod;

	/**
	 * Maximum amount. Not allowed for Core, optional for B2B.
	 * @var string 
	 */
	public $MaxAmount;

	/**
	 * The object used to log internal messages and the xml files
	 * 
	 * @var Logger
	 */
	public $logger;

	/**
	 * Constructor that highlights all required fields for this object
	 * 
	 * @param string $entranceCode
	 * @param string $language
	 * @param string $messageId
	 * @param string $eMandateId
	 * @param string $eMandateReason
	 * @param string $debtorReference
	 * @param string $debtorBankId
	 * @param string $purchaseId
	 * @param string $sequenceType
	 * @param string $maxAmount - optional
	 * @param \DateInterval $expirationPeriod - optional
	 */
	public function __construct($entranceCode, $language, $messageId, $eMandateId, $eMandateReason, $debtorReference, $debtorBankId, $purchaseId, $sequenceType, $maxAmount = '', $expirationPeriod = null) {

		/* setting the required fields */
		$this->EntranceCode = $entranceCode;
		$this->Language = $language;
		$this->MessageId = $messageId;
		$this->eMandateId = $eMandateId;
		$this->eMandateReason = $eMandateReason;
		$this->DebtorReference = $debtorReference;
		$this->DebtorBankId = $debtorBankId;
		$this->PurchaseId = $purchaseId;
		$this->SequenceType = $sequenceType;

		/* setting the optional fields */
		$this->MaxAmount = str_replace(',', '.', $maxAmount);
		$this->ExpirationPeriod = $expirationPeriod;

		if (empty($this->MessageId)) {
			$this->MessageId = MessageIdGenerator::NewMessageId();
		}
	}

	/**
	 * Serializes the object into a Document
	 * 
	 * @param string $LocalInstrumentCode
	 * @return \DOMElement
	 */
	public function toXml($LocalInstrumentCode) {
		$this->logger->Log("building eMandate");
		$this->validateExpirationPeriodAndMaxAmount();

		$domtree = new \DOMDocument('1.0', 'UTF-8');

		/* create the Document element with it's namespaces */
		$Document = $domtree->createElement('Document');
		$Document->setAttribute('xmlns', self::XMLNS);
		$Document->setAttribute('xmlns:xsd', self::XSD);
		$Document->setAttribute('xmlns:xsi', self::XSI); {
			/* create the MndtInitnReq element */
			$MndtInitnReq = $domtree->createElement('MndtInitnReq'); {
				/* create the GrpHdr element */
				$GrpHdr = $domtree->createElement('GrpHdr'); {
					/* create the MsgId and the CreDtTm elements */
					$GrpHdr->appendChild(new \DOMElement('MsgId', $this->MessageId));
					$GrpHdr->appendChild(new \DOMElement('CreDtTm', date('Y-m-d\TH:i:s'.substr((string)microtime(), 1, 4).'\Z')));
				}
				$MndtInitnReq->appendChild($GrpHdr);

				/* create the Mndt element */
				$Mndt = $domtree->createElement('Mndt'); {
					/* create MndtId elemnt */
					$Mndt->appendChild(new \DOMElement('MndtId', $this->eMandateId));

					/* create MndtReqId element */
					$Mndt->appendChild(new \DOMElement('MndtReqId', self::NOT_PROVIDED));

					/* create Tp element */
					$Tp = $domtree->createElement('Tp'); {
						/* create SvcLvl element */
						$SvcLvl = $domtree->createElement('SvcLvl');
						$SvcLvl->appendChild(new \DOMElement('Cd', self::SEPA));
						$Tp->appendChild($SvcLvl);

						/* create LclInstrm element */
						$LclInstrm = $domtree->createElement('LclInstrm');
						$LclInstrm->appendChild(new \DOMElement('Cd', $LocalInstrumentCode));
						$Tp->appendChild($LclInstrm);
					}
					$Mndt->appendChild($Tp);

					/* create Ocrncs element */
					$Ocrncs = $domtree->createElement('Ocrncs');
					$Ocrncs->appendChild(new \DOMElement('SeqTp', $this->SequenceType));
					$Mndt->appendChild($Ocrncs);

					/* create MaxAmt element */
					if (!empty($this->MaxAmount) && $LocalInstrumentCode == B2BCommunicator::LOCAL_INSTRUMENT) {
						$MaxAmt = $domtree->createElement('MaxAmt', $this->MaxAmount);
						$MaxAmt->setAttribute('Ccy', self::CCY);
						$Mndt->appendChild($MaxAmt);
					}

					/* create Rsn element */
					if (!empty($this->eMandateReason)) {
						$Rsn = $domtree->createElement('Rsn');
						$Rsn->appendChild(new \DOMElement('Prtry', $this->eMandateReason));
						$Mndt->appendChild($Rsn);
					}

					/* create Cdtr element */
					$Mndt->appendChild(new \DOMElement('Cdtr'));

					/* create Dbtr element */
					$Dbtr = $domtree->createElement('Dbtr'); {
						if (!empty($this->DebtorReference)) {
							$Id = $domtree->createElement('Id'); {
							$PrvtId = $domtree->createElement('PrvtId'); {
								$Othr = $domtree->createElement('Othr');
								$Othr->appendChild(new \DOMElement('Id', $this->DebtorReference));
								$PrvtId->appendChild($Othr);
								}
							}
							$Id->appendChild($PrvtId);
							$Dbtr->appendChild($Id);
						}
					}
					$Mndt->appendChild($Dbtr);

					/* create DbtrAgt element */
					$DbtrAgt = $domtree->createElement('DbtrAgt'); {
						$FinInstnId = $domtree->createElement('FinInstnId');
						$FinInstnId->appendChild(new \DOMElement('BICFI', $this->DebtorBankId));
						$DbtrAgt->appendChild($FinInstnId);
					}
					$Mndt->appendChild($DbtrAgt);

					/* create RfrdDoc element */
					if (!empty($this->PurchaseId)) {
						$RfrdDoc = $domtree->createElement('RfrdDoc'); {
							$Tp = $domtree->createElement('Tp'); {
								$CdOrPrtry = $domtree->createElement('CdOrPrtry');
								$CdOrPrtry->appendChild(new \DOMElement('Prtry', $this->PurchaseId));
								$Tp->appendChild($CdOrPrtry);
							}
							$RfrdDoc->appendChild($Tp);
						}
						$Mndt->appendChild($RfrdDoc);
					}
				}
				$MndtInitnReq->appendChild($Mndt);
			}
			$Document->appendChild($MndtInitnReq);
		}

		$domtree->appendChild($Document);

		XmlValidator::isValidatXML($domtree->saveXML(), XmlValidator::SCHEMA_PAIN009, $this->logger);

		return $Document;
	}
	
	/**
	 * Validates that the ExpirationPeriod is not greater than 7days
	 * and that the MaxAmount respects the standard
	 * @throws CommunicatorException
	 */
	private function validateExpirationPeriodAndMaxAmount(){
		
		//EXPIRATION PERIOD
		if(!empty($this->ExpirationPeriod)){
			$max_future = new \DateTime();
			$max_future->add(new \DateInterval('P7DT1S')); // max plus one second

			$future = new \DateTime();
			$future->add($this->ExpirationPeriod);

			$check_interval2 = $max_future->diff($future);

			if($check_interval2->invert == 0){
				throw new CommunicatorException('The Expiration Period should not be greater than 7 days!');
			}
		}
		
		//MAX AMOUNT
		if ($this->MaxAmount !== '' && !is_numeric($this->MaxAmount)) {
			throw new CommunicatorException('The MaxAmount should be a number.');
		}

		if ($this->MaxAmount !== '' && (float) $this->MaxAmount == 0) {
			throw new CommunicatorException('The MaxAmount cannot be 0.');
		}

		$tempMaxAmount = str_replace('.', '', $this->MaxAmount);
		$pos = strpos($this->MaxAmount, '.');
		$pos = ($pos !== false ? $pos : strlen($this->MaxAmount));
		
		$decimals = substr($this->MaxAmount, $pos + 1);		
		if (!empty($this->MaxAmount) && (strlen($tempMaxAmount) > 11 || strlen($decimals) > 2)) {
			throw new CommunicatorException('The MaxAmount should have maximum 2 decimals and the total number of digits should be maximum 11.');
		}
	}

}
