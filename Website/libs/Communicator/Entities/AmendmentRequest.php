<?php

/**
 * Describes an amendment request
 */
class AmendmentRequest {

	const XMLNS = "urn:iso:std:iso:20022:tech:xsd:pain.010.001.04";
	const XSD = "http://www.w3.org/2001/XMLSchema";
	const XSI = "http://www.w3.org/2001/XMLSchema-instance";
	const NOT_PROVIDED = 'NOTPROVIDED';
	const SEPA = 'SEPA';
	const MD16 = 'MD16';
	const CCY = 'EUR';

	/**
	 * An 'authentication identifier' to facilitate continuation of the session between creditor and debtor, even
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
	 * Optional: The period of validity of the transaction request as stated by the creditor measured from the receipt by the debtor bank.
	 * The debtor must authorise the transaction within this period.
	 * @var DateInterval 
	 */
	public $ExpirationPeriod;

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
	 * IBAN of the original mandate
	 * @var string 
	 */
	public $OriginalIBAN;

	/**
	 * BankID of the original mandate
	 * @var string 
	 */
	public $OriginalDebtorBankId;

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
	 * @param string $eMandateId
	 * @param string $eMandateReason
	 * @param string $debtorReference
	 * @param string $debtorBankId
	 * @param string $purchaseId
	 * @param string $sequenceType
	 * @param string $originalIBAN
	 * @param string $originalDebtorBankId
	 * @param string $messageId - optional
	 * @param DateInterval $expirationPeriod - optional
	 */
	public function __construct(
	$entranceCode, $language, $eMandateId, $eMandateReason, $debtorReference, $debtorBankId, $purchaseId, $sequenceType, $originalIBAN, $originalDebtorBankId, $messageId = '', $expirationPeriod = null) {

		/* setting the required fields */
		$this->EntranceCode = $entranceCode;
		$this->Language = $language;
		$this->eMandateId = $eMandateId;
		$this->eMandateReason = $eMandateReason;
		$this->DebtorReference = $debtorReference;
		$this->DebtorBankId = $debtorBankId;
		$this->PurchaseId = $purchaseId;
		$this->SequenceType = $sequenceType;
		$this->OriginalIBAN = $originalIBAN;
		$this->OriginalDebtorBankId = $originalDebtorBankId;
		$this->MessageId = $messageId;

		/* setting the optional fields */
		$this->ExpirationPeriod = $expirationPeriod;

		if (empty($this->MessageId)) {
			$this->MessageId = MessageIdGenerator::NewMessageId();
		}
	}

	/**
	 * Serializes the object into a Document 
	 * 
	 * @param string $LocalInstrumentCode
	 * @return DOMElement
	 */
	public function toXml($LocalInstrumentCode) {
		$this->logger->Log("building eMandate");
		$this->validateExpirationPeriod();

		$domtree = new DOMDocument('1.0', 'UTF-8');

		/* create the Document element with it's namespaces */
		$Document = $domtree->createElement('Document');
		$Document->setAttribute('xmlns', self::XMLNS);
		$Document->setAttribute('xmlns:xsd', self::XSD);
		$Document->setAttribute('xmlns:xsi', self::XSI); {
			/* create the MndtAmdmntReq element */
			$MndtAmdmntReq = $domtree->createElement('MndtAmdmntReq'); {
				/* create the GrpHdr element */
				$GrpHdr = $domtree->createElement('GrpHdr'); {
					$GrpHdr->appendChild(new DOMElement('MsgId', $this->MessageId));
					$GrpHdr->appendChild(new DOMElement('CreDtTm', date('Y-m-d\TH:i:s'.substr((string)microtime(), 1, 4).'\Z')));
				}
				$MndtAmdmntReq->appendChild($GrpHdr);

				/* create the UndrlygAmdmntDtls element */
				$UndrlygAmdmntDtls = $domtree->createElement('UndrlygAmdmntDtls'); {
					/* create the AmdmntRsn element */
					$AmdmntRsn = $domtree->createElement('AmdmntRsn'); {
						/* create the Rsn element */
						$Rsn = $domtree->createElement('Rsn');
						$Rsn->appendChild(new DOMElement('Cd', self::MD16));
						$AmdmntRsn->appendChild($Rsn);
					}
					$UndrlygAmdmntDtls->appendChild($AmdmntRsn);

					/* create the Mndt element */
					$Mndt = $domtree->createElement('Mndt'); {
						/* create the MndtId element */
						$Mndt->appendChild($domtree->createElement('MndtId', $this->eMandateId));

						/* create the MndtReqId element */
						$Mndt->appendChild($domtree->createElement('MndtReqId', self::NOT_PROVIDED));

						/* create Tp element */
						$Tp = $domtree->createElement('Tp'); {
							/* create SvcLvl element */
							$SvcLvl = $domtree->createElement('SvcLvl');
							$SvcLvl->appendChild(new DOMElement('Cd', self::SEPA));
							$Tp->appendChild($SvcLvl);

							/* create LclInstrm element */
							$LclInstrm = $domtree->createElement('LclInstrm');
							$LclInstrm->appendChild(new DOMElement('Cd', $LocalInstrumentCode));
							$Tp->appendChild($LclInstrm);
						}
						$Mndt->appendChild($Tp);

						/* create Ocrncs element */
						$Ocrncs = $domtree->createElement('Ocrncs');
						$Ocrncs->appendChild(new DOMElement('SeqTp', $this->SequenceType));
						$Mndt->appendChild($Ocrncs);

						/* create Rsn element */
						if (!empty($this->eMandateReason)) {
							$Rsn1 = $domtree->createElement('Rsn');
							$Rsn1->appendChild(new DOMElement('Prtry', $this->eMandateReason));
							$Mndt->appendChild($Rsn1);
						}

						/* create Cdtr element */
						$Mndt->appendChild(new DOMElement('Cdtr'));

						/* create Dbtr element */
						$Dbtr = $domtree->createElement('Dbtr'); {
							if (!empty($this->DebtorReference)) {
								$Id = $domtree->createElement('Id'); {
								$PrvtId = $domtree->createElement('PrvtId'); {
									$Othr = $domtree->createElement('Othr');
									$Othr->appendChild(new DOMElement('Id', $this->DebtorReference));
									$PrvtId->appendChild($Othr);
									}
								}
								$Id->appendChild($PrvtId);
								$Dbtr->appendChild($Id);
							}
						}
						$Mndt->appendChild($Dbtr);

						/* create the DbtrAgt element */
						$DbtrAgt = $domtree->createElement('DbtrAgt'); {
							$FinInstnId = $domtree->createElement('FinInstnId');
							$FinInstnId->appendChild(new DOMElement('BICFI', $this->DebtorBankId));
							$DbtrAgt->appendChild($FinInstnId);
						}
						$Mndt->appendChild($DbtrAgt);

						/* create the RfrdDoc element */
						if (!empty($this->PurchaseId)) {
							$RfrdDoc = $domtree->createElement('RfrdDoc'); {							
								$Tp = $domtree->createElement('Tp'); {
									$CdOrPrtry = $domtree->createElement('CdOrPrtry');
									$CdOrPrtry->appendChild(new DOMElement('Prtry', $this->PurchaseId));
									$Tp->appendChild($CdOrPrtry);
								}
								$RfrdDoc->appendChild($Tp);
							}
							$Mndt->appendChild($RfrdDoc);
						}
					}
					$UndrlygAmdmntDtls->appendChild($Mndt);

					/* create the OrgnlMndt element */
					$OrgnlMndt1 = $domtree->createElement('OrgnlMndt');
					$OrgnlMndt = $domtree->createElement('OrgnlMndt'); {
						/* create the MndtId element */
						$OrgnlMndt->appendChild($domtree->createElement('MndtId', $this->eMandateId));

						/* create Cdtr element */
						$OrgnlMndt->appendChild(new DOMElement('Cdtr'));

						/* create Dbtr element */
						$Dbtr = $domtree->createElement('Dbtr');
						$OrgnlMndt->appendChild($Dbtr);

						/* create DbtrAcct element */
						$DbtrAcct = $domtree->createElement('DbtrAcct'); {
							$Id = $domtree->createElement('Id');
							$Id->appendChild(new DOMElement('IBAN', $this->OriginalIBAN));
							$DbtrAcct->appendChild($Id);
						}
						$OrgnlMndt->appendChild($DbtrAcct);

						/* create the DbtrAgt element */
						$DbtrAgt = $domtree->createElement('DbtrAgt'); {
							$FinInstnId = $domtree->createElement('FinInstnId');
							$FinInstnId->appendChild(new DOMElement('BICFI', $this->OriginalDebtorBankId));
							$DbtrAgt->appendChild($FinInstnId);
						}
						$OrgnlMndt->appendChild($DbtrAgt);
					}
					$OrgnlMndt1->appendChild($OrgnlMndt);
					$UndrlygAmdmntDtls->appendChild($OrgnlMndt1);
				}
				$MndtAmdmntReq->appendChild($UndrlygAmdmntDtls);
			}
			$Document->appendChild($MndtAmdmntReq);
		}

		$domtree->appendChild($Document);

		XmlValidator::isValidatXML($domtree->saveXML(), XmlValidator::SCHEMA_PAIN010, $this->logger);

		return $Document;
	}
	
	/**
	 * Validates that the ExpirationPeriod is not greater than 7days
	 * @throws CommunicatorException
	 */
	private function validateExpirationPeriod(){
		
		//EXPIRATION PERIOD
		if(!empty($this->ExpirationPeriod)){
			$max_future = new DateTime();
			$max_future->add(new DateInterval('P7DT1S')); // max plus one second

			$future = new DateTime();
			$future->add($this->ExpirationPeriod);

			$check_interval2 = $max_future->diff($future);

			if($check_interval2->invert == 0){
				throw new CommunicatorException('The Expiration Period should not be greater than 7 days!');
			}
		}
	}
}
