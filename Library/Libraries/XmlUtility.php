<?php

namespace EMandates\Merchant\Library\Libraries;

use EMandates\Merchant\Library\Configuration\Configuration;
use EMandates\Merchant\Library\Libraries\Logger;

/**
 * Utility class for processing XML documents
 */
class XmlUtility
{
    /** TODO: to be improved!
     * @param $xml
     * @return SimpleXMLElement
     */
    public static function parse($xml)
    {
        $log = new Logger(Configuration::getDefault());
        // $log->LogXmlMessage($xml, true, 'OriginalDoc');

        // Get default namespace before cleaning the doc
        $temp = simplexml_load_string($xml);
        $namespaceURI = '';

        // Will fetch root namespaces
        $namespaces = $temp->getNamespaces();
        if (!empty($namespaces) && is_array($namespaces)) {

            // Get default namespace value (first one) to use it later one
            $namespaceURI = array_values($namespaces)[0];
            $log->Log("OriginalDocNamespace: {$namespaceURI}");
        }

        $xml = preg_replace("/(<\\/?)[\\w]*?:/m", '$1', $xml);
        $xml = preg_replace("/xmlns:[\\w]*?=\"[^\"]*?\"/m", '', $xml);

        $element = simplexml_load_string($xml);

        // $log->LogXmlMessage($xml, true, 'ParsedDoc');

        // Make sure the default namespace xmlns is set
        $namespaces = $element->getNamespaces();
        if (empty($namespaces) && !empty($namespaceURI)) {

            // No namespace found, this will cause errors in AcceptanceReport's constructor as relies on it
            $element->addAttribute('xmlns', $namespaceURI);

            $log->Log("Empty document namespace prevented, added default namespaceURI: {$namespaceURI}");
            // $log->LogXmlMessage($element->asXML(), true, 'ParsedDocNamespaceAdded');
        }

        return $element;
    }
}
