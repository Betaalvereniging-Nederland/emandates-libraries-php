<?php

require_once "Configuration/Configuration.php";
require_once "B2BCommunicator.php";
require_once "Libraries/XmlUtility.php";

/* DIRECTORY */
require_once "Entities/DirectoryRequest.php";
require_once "Entities/DirectoryResponse.php";

/* TRANSACTION */
require_once "Entities/AcquirerTrxRequest.php";

/* NEW eMANDATE */
require_once "Entities/NewMandateRequest.php";
require_once "Entities/NewMandateResponse.php";

/* STATUS */
require_once "Entities/AcquirerStatusRequest.php";
require_once "Entities/AcquirerStatusResponse.php";

/* CANCEL */
require_once "Entities/CancellationRequest.php";
require_once "Entities/CancellationResponse.php";

/* AMEND */
require_once "Entities/AmendmentRequest.php";
require_once "Entities/AmendmentResponse.php";

/* Signer and Validator */
require_once "Libraries/XmlSecurity.php";
require_once "Libraries/XmlValidator.php";

require_once 'Libraries/CommunicatorException.php';
require_once 'Libraries/MessageIdGenerator.php';
require_once 'Libraries/Logger.php';

/**
 * Description of Communicator
 */
class CoreCommunicator {

	const LOCAL_INSTRUMENT = 'CORE';
	const VERSION = '1.0.0';
	const PRODUCT_ID = 'NL:BVN:eMandatesCore:1.0';

	/**
	 * The configuration object used in the Communicator
	 * 
	 * @var Configuration 
	 */
	protected $Configuration;

	/**
	 * The logger object use to log xml and internal messages
	 * 
	 * @var Logger
	 */
	protected $logger;

	/**
	 * The signer object used to sign requests and verify response
	 * 
	 * @var XmlSecurity
	 */
	protected $signer;

     /** When no configuration is passed the defaults from eMandatesConfig.php will be used.
      *
      * @param Configuration $configuration = null
      * @param bool $logger
      */
    public function __construct(Configuration $configuration = null, $logger = false)
    {
        if (is_null($configuration)) {
            $this->Configuration = Configuration::getDefault();
        } else {
            $this->Configuration = $configuration;
        }

        $this->logger = ($logger ? $logger : new Logger($this->Configuration));

        $this->signer = new XmlSecurity($this->logger);

        $this->logger->Log(get_called_class() . " initialized");
    }

	/**
	 * Performs a DirectoryRequest and returns the apropiate DirectoryResponse
	 * 
	 * @return \DirectoryResponse
	 */
	public function Directory() {
		$this->logger->Log("sending new directory request");
		$c = get_called_class();
		
		$DirectoryReq = new DirectoryRequest(
				$c::PRODUCT_ID, $c::VERSION, new Merchant($this->Configuration->contractID, $this->Configuration->contractSubID)
		);


		try {
			$this->logger->Log("building idx message");
			// Serialize the DirectoryReq
			$docTree = $DirectoryReq->toXml();

			// Send the Request
			$response = $this->PerformRequest($docTree, $this->Configuration->AcquirerUrl_DirectoryReq);

			// Validate the Response and validate signature

			XmlValidator::isValidatXML($response, XmlValidator::SCHEMA_IDX, $this->logger);
			try {
				$this->signer->verify($response, $this->Configuration->crtFileAquirer);
			} catch (Exception $e) {
				$this->signer->verify($response, $this->Configuration->crtFileAquirerAlternative);
			}

			return new DirectoryResponse($response);
		} catch (Exception $ex) {
			return new DirectoryResponse($ex->getMessage(), (!empty($response) ? $response : ''));
		}
	}

	/**
	 * Performs a new mandate request using the provided mandate
	 * 
	 * @param NewMandateRequest $NewMandateRequest
	 * @return NewMandateResponse
	 */
	public function NewMandate(NewMandateRequest $NewMandateRequest) {
		$NewMandateRequest->logger = $this->logger;
		$this->logger->Log("sending new eMandate transaction");
		$c = get_called_class();

		$AcquirerTrxReq = new AcquirerTrxRequest(
				$c::PRODUCT_ID, $c::VERSION, new AcquirerTrxReqMerchant(
				$this->Configuration->contractID, $this->Configuration->contractSubID, $this->Configuration->merchantReturnURL
				), $NewMandateRequest->DebtorBankId, new AcquirerTrxReqTransaction(
				$NewMandateRequest->EntranceCode, !empty($NewMandateRequest->ExpirationPeriod) ? $NewMandateRequest->ExpirationPeriod : null, $NewMandateRequest->Language, $NewMandateRequest
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
			} catch (Exception $e) {
				$this->signer->verify($response, $this->Configuration->crtFileAquirerAlternative);
			}

			return new NewMandateResponse($response);
		} catch (Exception $ex) {
			return new NewMandateResponse($ex->getMessage(), (!empty($response) ? $response : ''));
		}
	}

	/**
	 * Performs a status request usign the provided $statusRequest
	 * 
	 * @param StatusRequest $statusRequest
	 * @return AcquirerStatusResponse
	 */
	public function GetStatus(StatusRequest $statusRequest) {
		$this->logger->Log("sending new status request");
		$c = get_called_class();

		$AcquirerStatusReq = new AcquirerStatusRequest(
				$c::PRODUCT_ID, $c::VERSION, new AcquirerStatusReqMerchant(
				$this->Configuration->contractID, $this->Configuration->contractSubID
				), new AcquirerStatusReqTransaction(
				$statusRequest->TransactionId
				)
		);
		try {
			$this->logger->Log("building idx message");
			// Serialize 
			$docTree = $AcquirerStatusReq->toXml();

			// Send the Request
			$response = $this->PerformRequest($docTree, $this->Configuration->AcquirerUrl_StatusReq);
			// Validate the Response and validate signature
			XmlValidator::isValidatXML($response, XmlValidator::SCHEMA_IDX, $this->logger);
			try {
				$this->signer->verify($response, $this->Configuration->crtFileAquirer);
			} catch (Exception $e) {
				$this->signer->verify($response, $this->Configuration->crtFileAquirerAlternative);
			}

			return new AcquirerStatusResponse($response, $this->logger);
		} catch (Exception $ex) {
			return new AcquirerStatusResponse($ex->getMessage(), $this->logger, (!empty($response) ? $response : ''));
		}
	}

	/**
	 * Performs an amendment to a mandate using the provided $amendmentRequest
	 * 
	 * @param AmendmentRequest $amendmentRequest
	 * @return AmendmentResponse
	 */
	public function Amend(AmendmentRequest $amendmentRequest) {
		$amendmentRequest->logger = $this->logger;
		$this->logger->Log("sending new amend request");
		$c = get_called_class();

		$AcquirerTrxReq = new AcquirerTrxRequest(
				$c::PRODUCT_ID, $c::VERSION, new AcquirerTrxReqMerchant(
				$this->Configuration->contractID, $this->Configuration->contractSubID, $this->Configuration->merchantReturnURL
				), $amendmentRequest->DebtorBankId, new AcquirerTrxReqTransaction(
				$amendmentRequest->EntranceCode, !empty($amendmentRequest->ExpirationPeriod) ? $amendmentRequest->ExpirationPeriod : null, $amendmentRequest->Language, $amendmentRequest
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
			} catch (Exception $e) {
				$this->signer->verify($response, $this->Configuration->crtFileAquirerAlternative);
			}

			return new AmendmentResponse($response);
		} catch (Exception $ex) {
			return new AmendmentResponse($ex->getMessage(), (!empty($response) ? $response : ''));
		}
	}

	/*
	 * *************************************************************************
	 * PROTECTED METHODS
	 * *************************************************************************
	 */

	/**
	 * Sends the xml to the provided url and returns the response
	 * Throws an Exception if there was something wrong with the curl
	 * 
	 * @param string $xml
	 * @param string $url
	 * @return string
	 * @throws Exception
	 */
	protected function PerformRequest($docTree, $url) {

		// Sign the xml
		$docTree = $this->signer->sign($docTree, $this->Configuration->crtFile, $this->Configuration->keyFile, $this->Configuration->passphrase);

		// Validate the request xml against the .xsd schema
		if (XmlValidator::isValidatXML($docTree->saveXML(), XmlValidator::SCHEMA_IDX, $this->logger)) {

			$this->logger->Log("sending request to {{$url}} ");

			// Log the xml before sending
			$this->logger->LogXmlMessage($docTree);

			//setting the curl parameters.
			$headers = array(
				"Content-type: text/xml;charset=utf-8",
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);

			// send xml request to server
			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
                        
                        //\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);//default is 2
                        //\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);//default is 1
                        \curl_setopt($ch, CURLOPT_CAINFO, __DIR__. "/cacert.pem");//make senses when CURLOPT_SSL_VERIFYPEER is 1

			// no need to set this because we already set SSLVERSION to be TLS 1.x;
			// furthermore, when compiled against libnss and not openssl,
			// TLSv1 is not a valid cipher name
			// curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');

			curl_setopt($ch, CURLOPT_POSTFIELDS, $docTree->saveXML());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$data = curl_exec($ch);

			// check for curl errors
			if ($data === false) {
				$error = curl_error($ch);
				curl_close($ch);

				$this->logger->Log($error);
				throw new Exception($error);
			} else {
				curl_close($ch);

                $doc = @simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOENT);
				if (!$doc) {
					$this->logger->Log("Raw Response : " . $data);
					throw new CommunicatorException($data);
				}

				// Log the xml received
				$this->logger->LogXmlMessage($data, true);

				return $data;
			}
		}
	}

	/**
	 * Returns the current version of the library.
	 */
	public static function getVersion() {
		return "1.16.5";
	}

}
