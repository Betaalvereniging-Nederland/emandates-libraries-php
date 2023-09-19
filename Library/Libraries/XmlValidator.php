<?php

namespace EMandates\Merchant\Library\Libraries;

/**
 * Description of XmlValidator
 */
class XmlValidator {

	const SCHEMA_IDX = 'SchemaIDX';
	const SCHEMA_PAIN009 = 'SchemaPain009';
	const SCHEMA_PAIN010 = 'SchemaPain010';
	const SCHEMA_PAIN011 = 'SchemaPain011';
	const SCHEMA_PAIN012 = 'SchemaPain012';

	public $SchemaIDX;
	public $SchemaPain009;
	public $SchemaPain010;
	public $SchemaPain011;
	public $SchemaPain012;

	function __construct() {

		$this->SchemaIDX = dirname(__FILE__) . './../schemas/idx.merchant-acquirer.1.0.xsd';
		$this->SchemaPain009 = dirname(__FILE__) . './../schemas/pain.009.001.04.xsd';
		$this->SchemaPain010 = dirname(__FILE__) . './../schemas/pain.010.001.04.xsd';
		$this->SchemaPain011 = dirname(__FILE__) . './../schemas/pain.011.001.04.xsd';
		$this->SchemaPain012 = dirname(__FILE__) . './../schemas/pain.012.001.04.xsd';
	}

	/**
	 * Validates the xml provided against the xsd schema
	 * 
	 * @param string $xml
	 * @return boolean
	 * @throws \Exception
	 */
	public static function isValidatXML($xml, $schemaName, $logger) {
		$validator = new self();

		libxml_use_internal_errors(true);

		$document = new \DOMDocument();
		$document->loadXML($xml);

		if (!$document->schemaValidate($validator->$schemaName)) {

			foreach (libxml_get_errors() as $error) {
				$logger->Log("xml schema is not valid: {{$error->message}}");
				throw new CommunicatorException($error->message, $error->code);
			}
			return false;
		}
		$logger->Log("xml schema is valid");
		return true;
	}

}
