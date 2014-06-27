#!/usr/bin/php
<?php

/*

Copyright:: 2013, Sebastian Grewe

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

 */

// Change to working directory
chdir(dirname(__FILE__));

// Include all settings and classes
require_once('shared.inc.php');
require_once(CLASS_DIR.'/groupbuy.inc.php');

// Fetch all transactions since our last block
if ( $bitcoin->can_connect() === true ){
  $aTransactions = $bitcoin->listtransactions("", 10000); //defaults to last 10, should probably up it
} else {
  $log->logFatal('Unable to connect to RPC server backend');
  $monitoring->endCronjob($cron_name, 'E0006', 1, true);
}

// Nothing to do so bail out
if (empty($aTransactions)) {
  $log->logDebug('No new RPC transactions');
} else {
  $header = false;
  // Let us add those blocks as unaccounted
  foreach ($aTransactions as $iIndex => $aData) {
    if($aData['category'] == 'receive')
    {
      //This is a payment
      /*
      array(1) {
        [0]=>
        array(11) {
          ["account"]=>
          string(0) ""
          ["address"]=>
          string(34) "DHqjxttkv2Ldptn2z8GeEuZDv6a65c9Kmw"
          ["category"]=>
          string(7) "receive"
          ["amount"]=>
          float(1000)
          ["confirmations"]=>
          int(34)
          ["blockhash"]=>
          string(64) "3d69089d0b02df067682498f70d6d2d8ce28954d018309e9fcedf151d79ef73a"
          ["blockindex"]=>
          int(4)
          ["blocktime"]=>
          int(1401683318)
          ["txid"]=>
          string(64) "59aa7152615062a58a31db463501245b07f196b5c3c53053129e3a036fcc903f"
          ["time"]=>
          int(1401683265)
          ["timereceived"]=>
          int(1401683265)
        }
      }
      */
      
      $groupbuy->updatePaymentRecord($aData);
    }
  }
}
$aPayments = $groupbuy->getUnpaidPayments();
foreach($aPayments as $aData)
{
  if($aData['confirmations'] > 10)
  {
    $groupbuy->updatePaymentRecordStatus($aData['id'], 'processing');
    /*
      * lookup address for project
      * lookup uses for project and percentages
      * calculate payment and tx fees and donations
      * add transactions
      * let MPOS take care of the rest
    */
    $aProject = $groupbuy->getProjectByAddress($aData['address']);
    if(empty($aProject) || $aProject['investment'] == 0)
    {
      $log->logInfo("Payment to an unassociated address ".$aData['address']."!");
      continue;
    }
    
    $aUsers = $groupbuy->getUsersByProject($aProject['id']);
    if(empty($aUsers))
    {
      $log->logInfo("No users in this project ".$aProject['title']."!");
      continue;
    }
    
    foreach($aUsers as $aUser)
    {
      //check if we already paid this user
      if($aUser['investment'] > 0 && !$groupbuy->paymentDone($aData['id'], $aUser['account_id']))
      {
        $fPercentage = $aUser['investment'] / $aProject['investment'];
        $fShare = round($aData['amount'] * $fPercentage, 8);
        
        /* Borrowed from proportional_payout.php */
        // Defaults
        $aUser['fee' ] = 0;
        $aUser['donation'] = 0;
        $aUser['pool_bonus'] = 0;
        $aUser['percentage'] = $fPercentage;
        $aUser['payout'] = $fShare;
  
        // Calculate pool fees if they apply
        if ($config['fees'] > 0 && $aUser['no_fees'] == 0)
          $aUser['fee'] = round($config['fees'] / 100 * $aUser['payout'], 8);
  
        // Calculate donation amount, fees not included
        $aUser['donation'] = round($user->getDonatePercent($aUser['id']) / 100 * ( $aUser['payout'] - $aUser['fee']), 8);
  
        // Verbose output of this users calculations
        /*
        $log->logInfo(
          sprintf($strLogMask, $aBlock['height'], $aData['id'], $aData['username'], $aData['valid'], $aData['invalid'],
                  number_format($aData['percentage'], 8), number_format($aData['payout'], 8), number_format($aData['donation'], 8), number_format($aData['fee'], 8), number_format($aData['pool_bonus'], 8))
        );
        */
        
        // Add new credit transaction
        if (!$transaction->addTransaction($aUser['id'], $aUser['payout'], 'Credit', $aData['block_id']))
        {
          $groupbuy->logPayment($aData['id'], $aUser['id'], $aUser['payout'], $aUser['fee'], $aUser['donation'], 'error');
          $log->logFatal('Failed to insert new Credit transaction to database for ' . $aUser['username'] . ': ' . $transaction->getCronError());
        }
        else
        {
          $groupbuy->logPayment($aData['id'], $aUser['id'], $aUser['payout'], $aUser['fee'], $aUser['donation'], 'success');
        }
        
        // Add new fee debit for this block
        if ($aUser['fee'] > 0 && $config['fees'] > 0)
          if (!$transaction->addTransaction($aUser['id'], $aUser['fee'], 'Fee', $aData['block_id']))
            $log->logFatal('Failed to insert new Fee transaction to database for ' . $aUser['username'] . ': ' . $transaction->getCronError());
        // Add new donation debit
        if ($aUser['donation'] > 0)
          if (!$transaction->addTransaction($aUser['id'], $aUser['donation'], 'Donation', $aData['block_id']))
            $log->logFatal('Failed to insert new Donation transaction to database for ' . $aUser['username'] . ': ' . $transaction->getCronError());
      }
    }
    $groupbuy->updatePaymentRecordStatus($aData['id'], 'paid');
  }
}

require_once('cron_end.inc.php');
?>
