<?php 
require_once 'libs/Communicator/CoreCommunicator.php';
require_once 'libs/Communicator/B2BCommunicator.php';

require_once 'Util.php';
require_once 'DBLogger.php';

$params = array();
if(!empty($_POST)){
	foreach($_POST as $key => $value){
		$params[$key] = Util::test_input($value);
	}
}

date_default_timezone_set('UTC');

/**
 * *****************************************************************************
 *  [START] Sample Codes
 * ***************************************************************************** 
 */


//$dbLogger = new DBLogger();
// Initiate a CoreCommunicator with custom Logger
//$coreCommunicator = new CoreCommunicator(Configuration::getDefault(), $dbLogger);
// Initiate a B2BCommunicator with custom Logger
//$b2BCommunicator = new B2BCommunicator(Configuration::getDefault(), $dbLogger);


// Initiate a CoreCommunicator
$coreCommunicator = new CoreCommunicator(Configuration::getDefault());
// Initiate a B2BCommunicator
$b2BCommunicator = new B2BCommunicator(Configuration::getDefault());


// Available starting with 1.2.5 version
// $coreCommunicator = new CoreCommunicator();
// $b2BCommunicator = new B2BCommunicator();


//----------------------- DIRECTORY REQUEST ------------------------------------
if (!empty($_POST['directoryRequest'])) {
	$diRes = $coreCommunicator->Directory();
}

//----------------------- NEW EMANDATE REQUEST ---------------------------------
if (!empty($_POST['issueMandate'])) {

	$newMandateRequest = new NewMandateRequest(
			$params['entranceCode'],
			$params['language'],
			$params['messageId'],
			$params['eMandateId'],
			$params['eMandateReason'],
			$params['debtorReference'],
			$params['debtorBankId'],
			$params['purchaseId'],
			$params['sequenceType'],
			$params['maxAmt'],
			Util::evaluateExpirationPeriod($params['expirationPeriod'])
	);

	$newMandateResponse = $coreCommunicator->NewMandate($newMandateRequest);
}

//----------------------- GET TRANSACTION STATUS REQUEST -----------------------
if (!empty($_POST['getTransactionStatus'])) {
	
	$statusRequest = new StatusRequest($params['transactionId']);

	$transactionStatusResponse = $coreCommunicator->GetStatus($statusRequest);
}

//----------------------- AMEND TRANSACTION ------------------------------------
if (!empty($_POST['amendTransaction'])) {
	
	$amendmentRequest = new AmendmentRequest(
			$params['amend_entranceCode'],
			$params['amend_language'],
			$params['amend_eMandateId'],
			$params['amend_eMandateReason'],
			$params['amend_debtorReference'],
			$params['amend_debtorBankId'],
			$params['amend_purchaseId'],
			$params['amend_sequenceType'],
			$params['amend_originalIBAN'],
			$params['amend_originalDebtorBankId'],
			$params['amend_messageId'],
			Util::evaluateExpirationPeriod($params['amend_expirationPeriod'])
	);
	
	$amendmentResponse = $coreCommunicator->Amend($amendmentRequest);
}

//----------------------- CANCEL TRANSACTION -----------------------------------
if (!empty($_POST['cancelTransaction'])) {
		
	$cancellationReq = new CancellationRequest(
			$params['cancel_entranceCode'],
			$params['cancel_language'],
			$params['cancel_eMandateId'],
			$params['cancel_eMandateReason'],
			$params['cancel_debtorReference'],
			$params['cancel_debtorBankId'],
			$params['cancel_purchaseId'],
			$params['cancel_sequenceType'],
			$params['cancel_originalIBAN'],
			$params['cancel_messageId'],
            $params['cancel_maxAmount'],
			Util::evaluateExpirationPeriod($params['cancel_expirationPeriod'])
	);
	
	$cancellationResponse = $b2BCommunicator->Cancel($cancellationReq);
}
//------------------------------------------------------------------------------

/**
 * *****************************************************************************
 *  [END] Sample Codes
 * ***************************************************************************** 
 */

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Creditor's Application</title>
        <style>
            body{ width: 1024px; margin: 0 auto; }
            #content{ background-color: lightgrey; padding: 10px; border-radius: 5px; }            
            .row{ float:left; display: block; margin-left: 10px; margin-bottom: 10px; height: 21px;}
            .row_final{ clear: both; display: block; margin: 10px;}
            .row label, .row_final label{ display: inline-block; text-align: right; }
            .error{ background-color: lightcoral; padding: 5px 10px; border-radius: 5px; }
			.wrapper{ margin-bottom: 10px; background-color: darkgray}
			fieldset{ border-radius: 5px; }
			legend{ background-color: lightgrey; }
			input[type=submit]{ color: white; font-weight: bold; border-radius: 5px; border: 1px #285e8e; padding: 5px 10px; background-color: #3276b1;}
        </style>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    </head>
    <body>  
        <h3>Vendor's Application</h3>
        <div id='content'>
            <form method="POST" action=''>
				<!-- Directory Request -->
                <fieldset class="wrapper">                    
                    <legend>Directory Request</legend>

                    <div class='row_final'>
						<label></label>
                        <input type='submit' name='directoryRequest' value='DirectoryRequest' />
                    </div>

                    <div class='row_final'>
						<?php
						if (!empty($_POST['directoryRequest']) && !empty($diRes)) {
							
							if ($diRes->IsError) {
								Util::showError($diRes->Error);
							} else {
								Util::showDebtorBanks($diRes->DebtorBanks);
							}
							
							Util::showXML($diRes);
						}
						?>
                    </div>

                </fieldset>
				
				<!-- Issuing eMandate -->
                <fieldset class="wrapper">                    
                    <legend>Issuing eMandate</legend>
					
					<fieldset>
						<legend>Transaction Information</legend>
					<div class='row'>
						<label>entranceCode</label>
						<input type='text' name='entranceCode' value='<?php echo (!empty($_POST['entranceCode']) ? $_POST['entranceCode'] : ''); ?>' placeholder="entranceCode" />
					</div>
					
					<div class='row'>
						<label>expirationPeriod</label>
						<input type='text' name='expirationPeriod' value='<?php echo (!empty($_POST['expirationPeriod']) ? $_POST['expirationPeriod'] : ''); ?>' placeholder="PT20M" />
					</div>

					<div class='row'>
						<label>language</label>
						<input type='text' name='language' value='<?php echo (!empty($_POST['language']) ? $_POST['language'] : ''); ?>' placeholder="en" />
					</div>
						
					</fieldset>
					
					<fieldset>
						<legend>eMandate Information</legend>

					<div class='row'>
						<label>debtorBankId</label>
						<input type='text' name='debtorBankId' value='<?php echo (!empty($_POST['debtorBankId']) ? $_POST['debtorBankId'] : ''); ?>' placeholder="debtorBankId" />
					</div>

					<div class='row'>
						<label>debtorReference</label>
						<input type='text' name='debtorReference' value='<?php echo (!empty($_POST['debtorReference']) ? $_POST['debtorReference'] : ''); ?>' placeholder="debtorReference" />
					</div>

					<div class='row'>
						<label>eMandateId</label>
						<input type='text' name='eMandateId' value='<?php echo (!empty($_POST['eMandateId']) ? $_POST['eMandateId'] : ''); ?>' placeholder="eMandateId max 35 chars" />
					</div>

					<div class='row'>
						<label>messageId</label>
						<input type='text' name='messageId' value='<?php echo (!empty($_POST['messageId']) ? $_POST['messageId'] : ''); ?>' placeholder="messageId max 35 chars" />
					</div>
					
					<div class='row'>
						<label>maxAmt</label>
						<input type='text' name='maxAmt' value='<?php echo (!empty($_POST['maxAmt']) ? $_POST['maxAmt'] : ''); ?>' placeholder="15.15" />
					</div>
						
					<div class='row'>
						<label>sequenceType</label>
						<select name="sequenceType">
							<option value="RCUR"<?php echo (!empty($_POST['sequenceType']) && $_POST['sequenceType'] == 'RCUR' ? ' selected="selected"' : ''); ?>>RCUR</option>
							<option value="OOFF"<?php echo (!empty($_POST['sequenceType']) && $_POST['sequenceType'] == 'OOFF' ? ' selected="selected"' : ''); ?>>OOFF</option>
						</select>
					</div>

					<div class='row'>
						<label>eMandateReason</label>
						<input type='text' name='eMandateReason' value='<?php echo (!empty($_POST['eMandateReason']) ? $_POST['eMandateReason'] : ''); ?>' placeholder="eMandateReason" />
					</div>

					<div class='row'>
						<label>purchaseId</label>
						<input type='text' name='purchaseId' value='<?php echo (!empty($_POST['purchaseId']) ? $_POST['purchaseId'] : ''); ?>' placeholder="purchaseId" />
					</div>
					</fieldset>

					<div class='row_final'>
						<label></label>
						<input type='submit' name='issueMandate' value='Issue eMandate' />
					</div>
					<div class='row_final'>
						<?php
						if (!empty($_POST['issueMandate']) && !empty($newMandateResponse)) {

							if (!empty($errors['issueMandate'])) {
								Util::showVendorError($errors['issueMandate']);
							} else if ($newMandateResponse->IsError) {
								Util::showError($newMandateResponse->Error);
							} else {
								Util::showNewMandateResponse($newMandateResponse);
							}
							
							Util::showXML($newMandateResponse);
						}
						?>
					</div>

				</fieldset>
				
				<!-- Get Transaction Status -->
				<fieldset class="wrapper">
					<legend>Get Transaction Status</legend>

					<div class="row">
						<label>transactionId</label>
						<input type="text" name="transactionId" value="<?php echo (!empty($_POST['transactionId']) ? $_POST['transactionId'] : (!empty($newMandateResponse->TransactionId) ? $newMandateResponse->TransactionId : '')); ?>" />
					</div>

					<div class='row_final'>
						<label></label>
						<input type='submit' name='getTransactionStatus' value='Get Transaction Status' />
					</div>

					<div class='row_final'>
						<?php
						if (!empty($_POST['getTransactionStatus']) && !empty($transactionStatusResponse)) {

							if (!empty($errors['getTransactionStatus'])) {
								Util::showVendorError($errors['getTransactionStatus']);
							} else if ($transactionStatusResponse->IsError) {
								Util::showError($transactionStatusResponse->Error);
							} else {
								Util::showTransactionStatusResponse($transactionStatusResponse);
							}
							
							Util::showXML($transactionStatusResponse);
						}
						?>
					</div>
				</fieldset>
				
				<!-- Amend Transaction -->
				<fieldset class="wrapper">
					<legend>Amend Transaction</legend>
					<fieldset>
						<legend>Transaction Information</legend>
					<div class='row'>
						<label>entranceCode</label>
						<input type='text' name='amend_entranceCode' value='<?php echo (!empty($_POST['amend_entranceCode']) ? $_POST['amend_entranceCode'] : ''); ?>' placeholder="entranceCode"  />
					</div>
					
					<div class='row'>
						<label>expirationPeriod</label>
						<input type='text' name='amend_expirationPeriod' value='<?php echo (!empty($_POST['amend_expirationPeriod']) ? $_POST['amend_expirationPeriod'] : ''); ?>' placeholder="PT20M"  />
					</div>
					
					<div class='row'>
						<label>language</label>
						<input type='text' name='amend_language' value='<?php echo (!empty($_POST['amend_language']) ? $_POST['amend_language'] : ''); ?>' placeholder="en"  />
					</div>
						
					</fieldset>
					
					<fieldset>
						<legend>eMandate Information</legend>
					
					<div class='row'>
						<label>debtorBankId</label>
						<input type='text' name='amend_debtorBankId' value='<?php echo (!empty($_POST['amend_debtorBankId']) ? $_POST['amend_debtorBankId'] : ''); ?>' placeholder="debtorBankId"  />
					</div>
					
					<div class='row'>
						<label>DebtorReference</label>
						<input type='text' name='amend_debtorReference' value='<?php echo (!empty($_POST['amend_debtorReference']) ? $_POST['amend_debtorReference'] : ''); ?>' placeholder="debtorReference"  />
					</div>
					
					<div class='row'>
						<label>eMandateId</label>
						<input type='text' name='amend_eMandateId' value='<?php echo (!empty($_POST['amend_eMandateId']) ? $_POST['amend_eMandateId'] : ''); ?>' placeholder="eMandateId"  />
					</div>
					
					<div class='row'>
						<label>messageId</label>
						<input type='text' name='amend_messageId' value='<?php echo (!empty($_POST['amend_messageId']) ? $_POST['amend_messageId'] : ''); ?>' placeholder="messageId"  />
					</div>

					<div class='row'>
						<label>sequenceType</label>
						<select name="amend_sequenceType">
							<option value="RCUR"<?php echo (!empty($_POST['amend_sequenceType']) && $_POST['amend_sequenceType'] == 'RCUR' ? ' selected="selected"' : ''); ?>>RCUR</option>
							<option value="OOFF"<?php echo (!empty($_POST['amend_sequenceType']) && $_POST['amend_sequenceType'] == 'OOFF' ? ' selected="selected"' : ''); ?>>OOFF</option>
						</select>
					</div>
					
					<div class='row'>
						<label>eMandateReason</label>
						<input type='text' name='amend_eMandateReason' value='<?php echo (!empty($_POST['amend_eMandateReason']) ? $_POST['amend_eMandateReason'] : ''); ?>' placeholder="eMandateReason"  />
					</div>
					
					<div class='row'>
						<label>purchaseId</label>
						<input type='text' name='amend_purchaseId' value='<?php echo (!empty($_POST['amend_purchaseId']) ? $_POST['amend_purchaseId'] : ''); ?>' placeholder="purchaseId"  />
					</div>					
					
					<div class='row'>
						<label>originalIBAN</label>
						<input type='text' name='amend_originalIBAN' value='<?php echo (!empty($_POST['amend_originalIBAN']) ? $_POST['amend_originalIBAN'] : ''); ?>' placeholder="originalIBAN"  />
					</div>
					
					<div class='row'>
						<label>originalDebtorBankId</label>
						<input type='text' name='amend_originalDebtorBankId' value='<?php echo (!empty($_POST['amend_originalDebtorBankId']) ? $_POST['amend_originalDebtorBankId'] : ''); ?>' placeholder="originalDebtorBankId"  />
					</div>
						
					</fieldset>
					
					<div class='row_final'>
						<label></label>
						<input type='submit' name='amendTransaction' value='Amend Transaction' />
					</div>

					<div class='row_final'>
						<?php
						if (!empty($_POST['amendTransaction']) && !empty($amendmentResponse)) {

							if (!empty($errors['amendTransaction'])) {
								Util::showVendorError($errors['amendTransaction']);
							} else if ($amendmentResponse->IsError) {
								Util::showError($amendmentResponse->Error);
							} else {
								Util::showAmendmentResponse($amendmentResponse);
							}
							
							Util::showXML($amendmentResponse);
						}
						?>
					</div>
				</fieldset>
					
				<!-- Cancel Transaction -->
				<fieldset class="wrapper">
					<legend>Cancel Transaction</legend>
					<fieldset>
						<legend>Transaction Information</legend>
					<div class='row'>
						<label>entranceCode</label>
						<input type='text' name='cancel_entranceCode' value='<?php echo (!empty($_POST['cancel_entranceCode']) ? $_POST['cancel_entranceCode'] : ''); ?>' placeholder="entranceCode"  />
					</div>
					
					<div class='row'>
						<label>expirationPeriod</label>
						<input type='text' name='cancel_expirationPeriod' value='<?php echo (!empty($_POST['cancel_expirationPeriod']) ? $_POST['cancel_expirationPeriod'] : ''); ?>' placeholder="PT20M"  />
					</div>
					
					<div class='row'>
						<label>language</label>
						<input type='text' name='cancel_language' value='<?php echo (!empty($_POST['cancel_language']) ? $_POST['cancel_language'] : ''); ?>' placeholder="en"  />
					</div>
						
					</fieldset>
					
					<fieldset>
						<legend>eMandate Information</legend>
					
					<div class='row'>
						<label>debtorBankId</label>
						<input type='text' name='cancel_debtorBankId' value='<?php echo (!empty($_POST['cancel_debtorBankId']) ? $_POST['cancel_debtorBankId'] : 'INGBNL2A'); ?>' placeholder="debtorBankId"  />
					</div>
					
					<div class='row'>
						<label>debtorReference</label>
						<input type='text' name='cancel_debtorReference' value='<?php echo (!empty($_POST['cancel_debtorReference']) ? $_POST['cancel_debtorReference'] : ''); ?>' placeholder="debtorReference"  />
					</div>
					
					<div class='row'>
						<label>eMandateId</label>
						<input type='text' name='cancel_eMandateId' value='<?php echo (!empty($_POST['cancel_eMandateId']) ? $_POST['cancel_eMandateId'] : ''); ?>' placeholder="eMandateId"  />
					</div>
					
					<div class='row'>
						<label>messageId</label>
						<input type='text' name='cancel_messageId' value='<?php echo (!empty($_POST['cancel_messageId']) ? $_POST['cancel_messageId'] : ''); ?>' placeholder="messageId" />
					</div>
					
					<div class='row'>
						<label>sequenceType</label>
						<select name="cancel_sequenceType">
							<option value="RCUR"<?php echo (!empty($_POST['cancel_sequenceType']) && $_POST['cancel_sequenceType'] == 'RCUR' ? ' selected="selected"' : ''); ?>>RCUR</option>
							<option value="OOFF"<?php echo (!empty($_POST['cancel_sequenceType']) && $_POST['cancel_sequenceType'] == 'OOFF' ? ' selected="selected"' : ''); ?>>OOFF</option>
						</select>						
					</div>
					
					<div class='row'>
						<label>eMandateReason</label>
						<input type='text' name='cancel_eMandateReason' value='<?php echo (!empty($_POST['cancel_eMandateReason']) ? $_POST['cancel_eMandateReason'] : ''); ?>' placeholder="eMandateReason"  />
					</div>
					
					<div class='row'>
						<label>purchaseId</label>
						<input type='text' name='cancel_purchaseId' value='<?php echo (!empty($_POST['cancel_purchaseId']) ? $_POST['cancel_purchaseId'] : ''); ?>' placeholder="purchaseId"  />
					</div>
					
					<div class='row'>
						<label>originalIBAN</label>
						<input type='text' name='cancel_originalIBAN' value='<?php echo (!empty($_POST['cancel_originalIBAN']) ? $_POST['cancel_originalIBAN'] : 'NL01ZZZ12345678'); ?>' placeholder="originalIBAN"  />
					</div>

                    <div class='row'>
                        <label>maxAmount</label>
                        <input type='text' name='cancel_maxAmount' value='<?php echo (!empty($_POST['cancel_maxAmount']) ? $_POST['cancel_maxAmount'] : ''); ?>' placeholder="maxAmount"  />
                    </div>

					</fieldset>
					
					<div class='row_final'>
						<label></label>
						<input type='submit' name='cancelTransaction' value='Cancel Transaction' />
					</div>

					<div class='row_final'>
						<?php
						if (!empty($_POST['cancelTransaction']) && !empty($cancellationResponse)) {

							if (!empty($errors['cancelTransaction'])) {
								Util::showVendorError($errors['cancelTransaction']);
							} else if ($cancellationResponse->IsError) {
								Util::showError($cancellationResponse->Error);
							} else {
								Util::showCancellationResponse($cancellationResponse);
							}
							
							Util::showXML($cancellationResponse);
						}
						?>
					</div>
				</fieldset>
								
            </form>
        </div>

		<script type='text/javascript'>
			$(document).ready(function () {

				$('select[name=bank]').on('change', function () {
					$('input[name=debtorBankId]').val($('select[name=bank]').val());
				});
				
				$('.toggler').on('click', function(){
					$(this).siblings('.hiddable').toggle(
							function() {
								//$(this).show();
							}, function() {
								//$(this).hide();
							});
				});

			});
		</script>
    </body>
</html>
