<?php

namespace EMandates\Merchant\Library\Libraries;

/**
 * Class that automatically generates MessageId's. You may use this to set the MessageId field manually, or you can use
 * the constructors for NewMandateRequest, AmendmentRequest or CancellationRequest to do it automatically.
 */
class MessageIdGenerator {

	/**
	 * Returns a string of 16 alphanumeric characters
	 * @return string
	 */
	public static function NewMessageId() {
		$ok = false;
		while (!$ok) {
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = ''; //chr(45); // "-"
			$uuid = ''//chr(123)// "{"
					. substr($charid, 0, 8) . $hyphen
					. substr($charid, 8, 4) . $hyphen
					. substr($charid, 12, 4) . $hyphen
					. substr($charid, 16, 4) . $hyphen
					. substr($charid, 20, 12)
					. ''; //chr(125); // "}"
			$base64 = preg_replace("/[^A-Za-z0-9 ]/", '', base64_encode($uuid));
			$base64 = substr($base64, 0, 16);

			if (strlen($base64) == 16) {
				$ok = true;
			}
		}
		return $base64;
	}

}
