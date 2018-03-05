<?php

/**
 * Configuration parameters required by the Communicator lib.
 */

/** 
 * eMandate.ContractID as supplied to you by the creditor bank.
 * If the eMandate.ContractID has less than 9 digits, use leading zeros to fill out the field.
 */
$emandates_config_params['contractID'] = '1234123456';

/**
 * eMandate.ContractSubId as supplied to you by the creditor bank.
 * If you do not have a ContractSubId, use 0 for this field.
 */
$emandates_config_params['contractSubID'] = '1';

/**
 * A valid URL to which the debtor banks redirects to, after the debtor has authorized a transaction.
 */
$emandates_config_params['merchantReturnURL'] = 'http://google.com';

/**
 * The URL to which the library sends Directory request messages.
 */
$emandates_config_params['AcquirerUrl_DirectoryReq'] = "-";

/**
 * The URL to which the library sends Transaction request messages (including eMandates messages).
 */
$emandates_config_params['AcquirerUrl_TransactionReq'] = "-";

/** The URL to which the library sends Status request messages
 */
$emandates_config_params['AcquirerUrl_StatusReq'] = "-";

/**
 * LogPath and naming conventions
 * for the pattern: logs/Y-m-d/His.u-DirectoryRes.xml
 * the path is: logs/2015-02-11/115423.321-DirectoryRes.xml
 */

/**
 * This tells the library that it should save ISO pain raw messages or not. Default is true.
 */
$emandates_config_params['enableXMLLogs'] = true;

/**
 * A directory on the disk where the library saves ISO pain raw messages.
 */
$emandates_config_params['logPath'] = 'logs/';

/**
 * A string that describes a pattern to distinguish the ISO pain raw messages.
 */
$emandates_config_params['folderNamePattern'] = 'Y-m-d';

/**
 * A string that describes a pattern to distinguish the ISO pain raw messages.
 */
$emandates_config_params['fileNamePrefix'] = 'His.u';

/**
 * This tells the library that it should output debug logging messages.
*/
$emandates_config_params['enableInternalLogs'] = true;

/**
 * A file on the disk where the library should write the default log messages.
*/
$emandates_config_params['fileName'] = 'eMandates.txt';

/**
 * The password for the private key of the signing certificate.
 */
$emandates_config_params['passphrase'] = '-';

/**
 * The file containing the private key to use for signing messages to the creditor bank (aka the signing certificate).
 */
$emandates_config_params['keyFile'] = './-';

/**
 * The file containing the public key of the signing certificate.
 */
$emandates_config_params['crtFile'] = './-';

/**
 * The file containing the public key of the certificate to use to validate messages from the creditor bank.
 */
$emandates_config_params['crtFileAquirer'] = './-';

/**
 * Alternative file containing the public key of the certificate to use to validate messages from the creditor bank.
 */
$emandates_config_params['crtFileAquirerAlternative'] = './-';