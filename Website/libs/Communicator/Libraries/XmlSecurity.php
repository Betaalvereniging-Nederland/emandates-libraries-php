<?php

require_once 'xmlseclibs.php';
require_once 'CommunicatorException.php';

const BEGIN_CERTIFICATE = '-----BEGIN CERTIFICATE-----';
const END_CERTIFICATE = '-----END CERTIFICATE-----';

class XmlSecurity {

	private $logger;

	function __construct($logger) {
		$this->logger = $logger;
	}

	public function sign(DOMDocument $docTree, $privateCertificatePath, $privateKeyPath, $passphrase) {
		$this->logger->Log("signing xml...");
		
		/* The GOOD thing that fixed the xignature */
		$doc = new DOMDocument();
		$doc->loadXML($docTree->saveXML());
		
		$signature = new XMLSecurityDSig();		
		$signature->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);		
		$signature->addReference(
			$doc,
			XMLSecurityDSig::SHA256,
			array(
				'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
				'http://www.w3.org/2001/10/xml-exc-c14n#'
			),
			array('force_uri' => true)
		);

		$key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

		$key->passphrase = $passphrase;
		$key->loadKey($privateKeyPath, TRUE);

		$signature->sign($key);

		$fingerprint = $this->getFingerprint($privateCertificatePath);

		$this->logger->Log("embedding thumbprint: {{$fingerprint}}");

		$signature->addKeyInfoAndName($fingerprint);
		
		$signature->appendSignature($doc->documentElement);

		$this->logger->Log("finished signing xml");

		return $doc;
	}
	
	private function checkIdxSignature($doc, $certificatePath) {
		$signature = new XMLSecurityDSig();
		
		$signature->locateSignature($doc);
		$signature->canonicalizeSignedInfo();

		try {
			$signature->validateReference();
		} catch (Exception $ex) {
			$this->logger->Log("Reference Validation Failed");
			throw new CommunicatorException('Reference Validation Failed', $ex->getCode());
		}

		$key = $signature->locateKey();
		if (!$key) {
			$this->logger->Log("Cannot locate the key");
			throw new CommunicatorException('Cannot locate the key.');
		}

		$key->loadKey($this->normalizeCertificate($certificatePath));

		if ($signature->verify($key) == 1) {
			$this->logger->Log("signature is valid");
			return true;
		} else {
			$this->logger->Log("The signature could not be verified.");
			throw new CommunicatorException('The signature could not be verified.');
		}
	}
	
	public function checkEmandateSignature($emandatedoc) {
		$str = $emandatedoc->saveXML();
		$str = str_replace(' xmlns:default="http://www.w3.org/2000/09/xmldsig#"', '', str_replace('default:', '', $str));
				
                $emandatedoc->loadXML($str);		
		
		$certificate = $emandatedoc->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'X509Certificate')->item(0);
		$cert = $this->normalizeEmbeddedCertificate($certificate->nodeValue);
		
		$signature = new XMLSecurityDSig();
		
		$signature->locateSignature($emandatedoc);
		$signature->canonicalizeSignedInfo();
		
		try {
			$signature->validateReference();
		} catch (Exception $ex) {
			$this->logger->Log("emandate signature: reference validation failed");
			throw new CommunicatorException('emandate signature: reference validation failed', $ex->getCode());
		}
		
		$key = $signature->locateKey();
		if (!$key) {
			$this->logger->Log("emandate signature: cannot locate the key");
			throw new CommunicatorException('emandate signature: cannot locate the key.');
		}
		
		$key->loadKey($cert);
		
		if ($signature->verify($key) == 1) {
			$this->logger->Log("emandate signature: signature is valid");
			return true;
		} else {
			$this->logger->Log("emandate signature: the signature could not be verified.");
			throw new CommunicatorException('emandate signature: the signature could not be verified.');
		}
	}
	
	public function verify($xml, $certificatePath) {
		$this->logger->Log("checking signature...");
		
		$doc = new DOMDocument();
		$doc->loadXML($xml);
		
		$signature = new XMLSecurityDSig();
		if ($signature->signaturesCount($doc) == 2) {
			// TODO: first check emandate signature
/*
                        //Previous approach
			$eMandate = $doc->getElementsByTagNameNS('urn:iso:std:iso:20022:tech:xsd:pain.012.001.04', 'Document')->item(0);
			$newEmandate = $eMandate->cloneNode(TRUE);
			$emandatedoc = new DOMDocument();
			$newEmandate = $emandatedoc->importNode($newEmandate, TRUE);
			$emandatedoc->appendChild($newEmandate);
*/

                        //New approach
			$eMandate = $doc->getElementsByTagNameNS('urn:iso:std:iso:20022:tech:xsd:pain.012.001.04', 'Document')->item(0);
                        $eMandateC14N = $eMandate->C14N();
			$emandatedoc = new DOMDocument();
                        $emandatedoc->preserveWhiteSpace = true;
                        $emandatedoc->loadXML($eMandateC14N);
                        $s3=$emandatedoc->saveXML();
                         
			$this->checkEmandateSignature($emandatedoc);
                }
		
		return $this->checkIdxSignature($doc, $certificatePath);
	}
	
	private function getFingerprint($path) {
		$contents = $this->normalizeCertificate($path);

		if (is_null($contents) || $contents == false) {
			$this->logger->Log("Empty signing certificate.");
			throw new CommunicatorException('Empty signing certificate.');
		}
		
		$contents = str_replace(END_CERTIFICATE, '', str_replace(BEGIN_CERTIFICATE, '', $contents));
		$contents = base64_decode($contents);
		return strtoupper(sha1($contents));
	}

	private function normalizeCertificate($path) {
		$origcontent = file_get_contents($path);
		
		if (is_null($origcontent) || $origcontent == false) {
			$this->logger->Log("Empty acquirer certificate.");
			throw new CommunicatorException('Empty acquirer certificate.');
		}
		
		$begin = '-----BEGIN CERTIFICATE-----';
		$end = '-----END CERTIFICATE-----';
		
		$certificate = $origcontent;
		$certificate = str_replace($begin, '', $certificate);
		$certificate = str_replace($end, '', $certificate);
		$certificate = preg_replace('~[\r\n\s\t]+~', '', $certificate);
		$certificate = trim($certificate);
		
		$certificate = chunk_split($certificate, 64);
		
		$certificate =
		    $begin . PHP_EOL .
		    $certificate. // no need for EOL here, it's added by chunk_split
		    $end;
		    
		return $certificate;
	}
	
	private function normalizeEmbeddedCertificate($text) {
		$blocks = array();
		$contents = $text;
		while (strlen($contents) > 0) {
			array_push($blocks, substr($contents, 0, 64));
			$contents = substr($contents, 64);
			$contents = ltrim($contents);
		}
		
		$str = BEGIN_CERTIFICATE . "\r\n" . implode("\r\n", $blocks) . "\r\n" . END_CERTIFICATE;
		
		return $str;
	}
}
