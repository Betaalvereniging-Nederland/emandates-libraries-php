<?php

use EMandates\Merchant\Library\Entities\{NewMandateResponse,
	AcquirerStatusResponse,
	CancellationResponse,
	AmendmentResponse
};
/**
 * Useful functions for displaying Response
 */
class Util {

	public static function showError($error) {
		echo '
            <div class="error">
                ' . ($error->ErrorCode ? $error->ErrorCode . ': ' : '') . $error->ErrorMessage . ' <br />
				' . $error->ErrorDetails . '
            </div>
        ';
	}
	
	public static function showVendorError($error){
		echo '
            <div class="error">
                ' . $error . '
            </div>
        ';
	}
	
	public static function showNewMandateResponse(NewMandateResponse $newMandateResponse){
		echo '			
			<div class="row">
				<label>TransactionCreateDateTimestamp</label>
				<input type="text" disabled="disabled" name="TransactionCreateDateTimestamp" value="' . $newMandateResponse->TransactionCreateDateTimestamp->format('m/d/Y H:i:s a') . '" />
			</div>
			<div class="row">
				<label>IssuerAuthenticationUrl</label>
				<input type="text" disabled="disabled" name="IssuerAuthenticationUrl" value="' . $newMandateResponse->IssuerAuthenticationUrl . '" />
			</div>
			<div class="row">
				<label>TransactionId</label>
				<input type="text" disabled="disabled" name="TransactionId" value="' . $newMandateResponse->TransactionId . '" />
			</div>
		';
	}
	
	public static function showAmendmentResponse(AmendmentResponse $AmendmentResponse){
		echo '			
			<div class="row">
				<label>TransactionCreateDateTimestamp</label>
				<input type="text" disabled="disabled" name="amend_TransactionCreateDateTimestamp" value="' . $AmendmentResponse->TransactionCreateDateTimestamp->format('m/d/Y H:i:s a') . '" />
			</div>
			<div class="row">
				<label>IssuerAuthenticationUrl</label>
				<input type="text" disabled="disabled" name="amend_IssuerAuthenticationUrl" value="' . $AmendmentResponse->IssuerAuthenticationUrl . '" />
			</div>
			<div class="row">
				<label>TransactionId</label>
				<input type="text" disabled="disabled" name="amend_TransactionId" value="' . $AmendmentResponse->TransactionId . '" />
			</div>
		';
	}
	
	public static function showCancellationResponse(CancellationResponse $CancellationResponse){
		echo '			
			<div class="row">
				<label>TransactionCreateDateTimestamp</label>
				<input type="text" disabled="disabled" name="cancel_TransactionCreateDateTimestamp" value="' . $CancellationResponse->TransactionCreateDateTimestamp->format('m/d/Y H:i:s a') . '" />
			</div>
			<div class="row">
				<label>IssuerAuthenticationUrl</label>
				<input type="text" disabled="disabled" name="cancel_IssuerAuthenticationUrl" value="' . $CancellationResponse->IssuerAuthenticationUrl . '" />
			</div>
			<div class="row">
				<label>TransactionId</label>
				<input type="text" disabled="disabled" name="cancel_TransactionId" value="' . $CancellationResponse->TransactionId . '" />
			</div>
		';
	}
	
	public static function showTransactionStatusResponse(AcquirerStatusResponse $AcquirerStatusRes) {
		echo '
			<div class="row">
				<label>TransactionId</label>
				<input type="text" disabled="disabled" name="transactionStatusId" value="' . $AcquirerStatusRes->TransactionId . '" />
			</div>';
		if ($AcquirerStatusRes->Status != AcquirerStatusResponse::STATUS_OPEN && $AcquirerStatusRes->Status != AcquirerStatusResponse::STATUS_PENDING) {
			echo '
				<div class="row">
					<label>StatusDateTimestamp</label>
					<input type="text" disabled="disabled" name="transactionStatusDateTimestamp" value="' . $AcquirerStatusRes->StatusDateTimestamp->format('m/d/Y H:i:s a') . '" />
				</div>';
		}
		echo '
			<div class="row">
				<label>Status</label>
				<input type="text" disabled="disabled" name="transactionStatus" value="' . $AcquirerStatusRes->Status . '" />
			</div>			

		';
	}

	public static function showDebtorBanks($debtorBanks) {

		$buckets = array();
		foreach ($debtorBanks as $bank) {
			$buckets[$bank->DebtorBankCountry][] = $bank;
		}

		$options = '';
		foreach ($buckets as $key => $bucket) {
			$options .= '<optgroup label="' . $key . '">';

			foreach ($bucket as $bank) {
				$options .= '<option value="' . $bank->DebtorBankId . '">' . $bank->DebtorBankName . '</option>';
			}

			$options .= '</optgroup>';
		}

		echo "
            <label>Select The Bank</label>
            <select name='bank'>
                " . $options . "
            </select>
        ";
	}
	
	public static function showXML($object) {
		if (!empty($object->RawMessage)) {
			$domTree = new \DOMDocument();
			$domTree->preserveWhiteSpace = false;
			$domTree->formatOutput = true;
			$domTree->loadXML($object->RawMessage);

			echo '
				<div style="max-width: 940px; clear:both;">
					<a class="toggler" href="javascript:void(0);">Show Raw Message</a>
					<pre class="hiddable" style="display: none; word-wrap: break-word;">' . htmlentities($domTree->saveXML()) . '</pre>
				</div>	
			';
		}
	}

	public static function test_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}
	
	/**
	 * Parse the $str and return a date interval
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function evaluateExpirationPeriod($str) {
		$interval = '';

		if (is_numeric($str)) { // when only a number is entered
			$interval = 'P' . $str . 'D';
		} else if (strstr($str, ':')) { // when he have a time [hours:minutes:seconds]
			$arr = explode(':', $str);
			$interval = "PT";
			if (!empty($arr[0])) {
				$interval .= $arr[0] . 'H';
			}
			if (!empty($arr[1])) {
				$interval .= $arr[1] . 'M';
			}
			if (!empty($arr[2])) {
				$interval .= $arr[2] . 'S';
			}
		} else {
			$interval = $str;
		}
		
		if(empty($interval)){
			return null;
		}
		
		return new \DateInterval(strtoupper($interval));
	}

}
