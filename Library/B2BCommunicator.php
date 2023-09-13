<?php

namespace EMandates\Merchant\Library;

use EMandates\Merchant\Library\Configuration\Configuration;

/* CANCEL & ACQUIRER TRANSACTION */
use EMandates\Merchant\Library\Entities\{CancellationRequest,
	CancellationResponse,
	AcquirerTrxRequest,
	AcquirerTrxReqMerchant,
	AcquirerTrxReqTransaction
};

/* Validator */
use EMandates\Merchant\Library\Libraries\XmlValidator;

/**
 * Description of Communicator
 */
class B2BCommunicator extends CoreCommunicator {

	const LOCAL_INSTRUMENT = 'B2B';
	const PRODUCT_ID = 'NL:BVN:eMandatesB2B:1.0';

    /** Initiates a new B2BCommunicator
     *
     * @param Configuration $configuration
     * @param bool $logger
     */
    function __construct(Configuration $configuration = null, $logger = false){
        parent::__construct($configuration, $logger);
    }

	/**
	 * Performs a CancellationRequest and returns the appropiate CancellationResponse
	 * 
	 * @param CancellationRequest $cancellationRequest
	 * @return CancellationResponse
	 */
	public function Cancel(CancellationRequest $cancellationRequest) {
		$cancellationRequest->logger = $this->logger;
		$this->logger->Log("sending cancellation transaction request");
		$c = get_called_class();

		$AcquirerTrxReq = new AcquirerTrxRequest(
				$c::PRODUCT_ID, $c::VERSION, new AcquirerTrxReqMerchant(
				$this->Configuration->contractID, $this->Configuration->contractSubID, $this->Configuration->merchantReturnURL
				), $cancellationRequest->DebtorBankId, new AcquirerTrxReqTransaction(
				$cancellationRequest->EntranceCode, !empty($cancellationRequest->ExpirationPeriod) ? $cancellationRequest->ExpirationPeriod : null, $cancellationRequest->Language, $cancellationRequest
				)
		);

		try {
			$this->logger->Log("building idx message");
			// Serialize 
			$docTree = $AcquirerTrxReq->toXml($c::LOCAL_INSTRUMENT);

			// Send the Request
			$response = $this->PerformRequest($docTree, $this->Configuration->AcquirerUrl_TransactionReq);
			// Validate the Response and validate signature
			XmlValidator::isValidatXML($response, XmlValidator::SCHEMA_IDX, $this->logger);
			try {
				$this->signer->verify($response, $this->Configuration->crtFileAquirer);
			} catch (\Exception $e) {
				$this->signer->verify($response, $this->Configuration->crtFileAquirerAlternative);
			}

			return new CancellationResponse($response);
		} catch (\Exception $ex) {
			return new CancellationResponse($ex->getMessage(), (!empty($response) ? $response : ''));
		}
	}

}
