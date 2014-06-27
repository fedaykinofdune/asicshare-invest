<?php
$defflip = (!cfip()) ? exit(header('HTTP/1.1 401 Unauthorized')) : 1;

class GroupBuy extends Base {
  protected $table = 'groupbuy';

  /*
   * Get the ID of an existing payment record
   */
  public function getPaymentRecord($aData) {
    $stmt = $this->mysqli->prepare("SELECT * FROM ".$this->table."_recpayments WHERE address = ? AND txid = ? LIMIT 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ss', $aData['address'], $aData['txid']) && $stmt->execute() && $result = $stmt->get_result())
      if($result->num_rows > 0)
      {
        $row = $result->fetch_assoc();
        return $row['id'];
      }
      else
      {
        return false;
      }
    return $this->sqlError();
  }
  
  /*
   * Update the confirmations of a payment record, if it doesn't exist create it.
   */
  public function updatePaymentRecord($aData) {
    
    if($id = $this->getPaymentRecord($aData))
    {
      $stmt = $this->mysqli->prepare("UPDATE ".$this->table."_recpayments
                                     SET confirmations=?, blockhash=?, blockindex=?, blocktime=?
                                     WHERE id = ?");
      if ($this->checkStmt($stmt) && $stmt->bind_param('isiii', $aData['confirmations'], $aData['blockhash'], $aData['blockindex'], $aData['blocktime'], $id) && $result = $stmt->execute())
      {
        if($aData['blockhash'])
        {
          $aData['id'] = $id;
          $this->updatePseudoBlock($aData);
        }
        return true;
      }
    }
    else
    {
      $stmt = $this->mysqli->prepare("INSERT INTO ".$this->table."_recpayments (account, address, category, amount, confirmations, blockhash, blockindex, blocktime, txid, time, timereceived, last_updated)
                                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
                                   ON DUPLICATE KEY UPDATE confirmations=?, blockhash=?, blockindex=?, blocktime=?");
      if ($this->checkStmt($stmt) && $stmt->bind_param('sssdisiisiiiisii', $aData['account'], $aData['address'], $aData['category'], $aData['amount'], $aData['confirmations'], $aData['blockhash'], $aData['blockindex'], $aData['blocktime'], $aData['txid'], $aData['time'], $aData['timereceived'], time(), $aData['confirmations'], $aData['blockhash'], $aData['blockindex'], $aData['blocktime']) && $result = $stmt->execute())
      {
        if($aData['blockhash'])
        {
          $aData['id'] = $this->mysqli->insert_id;
          $this->updatePseudoBlock($aData);
        }
        return true;
      }
    }
    return $this->sqlError();
  }
  
  /*
   * Just about everything checks for a block, so this creates a fake one to make MPOS happy
   * This checks to see if we've already created this one.
   */
  public function getPseudoBlock($aData)
  {
    $blockhash = $aData['blockhash'].'-'.$aData['id'];
    $stmt = $this->mysqli->prepare("SELECT * FROM ".$this->block->getTableName()." WHERE blockhash = ? LIMIT 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param('s', $blockhash) && $stmt->execute() && $result = $stmt->get_result())
      if($result->num_rows > 0)
      {
        $row = $result->fetch_assoc();
        return $row['id'];
      }
      else
      {
        return false;
      }
    return $this->sqlError();
  }
  
  /*
   * Just about everything checks for a block, so this creates a fake one to make MPOS happy
   * We keep confirmations accurate for kicks.
   */
  public function updatePseudoBlock($aData) {
    $blockhash = $aData['blockhash'].'-'.$aData['id'];
    if($id = $this->getPseudoBlock($aData))
    {
      $stmt = $this->mysqli->prepare("UPDATE ".$this->block->getTableName()."
                                     SET confirmations=?
                                     WHERE blockhash = ?");
      if ($this->checkStmt($stmt) && $stmt->bind_param('is', $aData['confirmations'], $blockhash) && $stmt->execute())
        return true;
    }
    else
    {
      $stmt = $this->mysqli->prepare("INSERT INTO ".$this->block->getTableName()." (blockhash, confirmations, amount, time, accounted, account_id, worker_name, share_id, payment_id)
                                     VALUES (?, ?, ?, ?, 1, 11, 'Group Buy', 1, ?)
                                     ON DUPLICATE KEY UPDATE confirmations=?");
      if ($this->checkStmt($stmt) && $stmt->bind_param('sidiii', $blockhash, $aData['confirmations'], $aData['amount'], $aData['time'], $aData['id'], $aData['confirmations']) && $stmt->execute())
        return true;
    }
    return $this->sqlError();
  }
  
  /*
   * Get the project by payment address
   */
  public function getProjectByAddress($address) {
    $stmt = $this->mysqli->prepare("SELECT * FROM ".$this->table."_projects WHERE address = ? LIMIT 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param('s', $address) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_assoc();
    return $this->sqlError();
  }
  
  /*
   * Get all the users in a project
   */
  public function getUsersByProject($projectId) {
    $ret = array();
    $stmt = $this->mysqli->prepare("SELECT i.*, i.account_id as id, a.no_fees
                                   FROM ".$this->table."_investments AS i
                                   LEFT JOIN " . $this->user->getTableName() . " AS a ON (a.id = i.account_id)
                                   WHERE i.project_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $projectId) && $stmt->execute() && $result = $stmt->get_result())
    {
      while($row = $result->fetch_assoc())
      {
        $ret[] = $row;
      }
      return $ret;
    }
    return $this->sqlError();
  }
  
  /*
   * Look for unpaid payments.
   */
  public function getUnpaidPayments()
  {
    $ret = array();
    $stmt = $this->mysqli->prepare("SELECT r.*, b.id AS block_id
                                    FROM ".$this->table."_recpayments AS r, ".$this->block->getTableName()." AS b
                                    WHERE r.id = b.payment_id
                                    AND r.status = 'pending'");
    if ($this->checkStmt($stmt) && $stmt->execute() && $result = $stmt->get_result())
    {
      while($row = $result->fetch_assoc())
      {
        $ret[] = $row;
      }
      return $ret;
    }
    return $this->sqlError();
  }
  
  /*
   * Update the status of payments.
   */
  public function updatePaymentRecordStatus($id, $status)
  {
    $stmt = $this->mysqli->prepare("UPDATE ".$this->table."_recpayments
                                   SET status = ?
                                   WHERE id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('si', $status, $id) && $stmt->execute())
      return true;
    return $this->sqlError();
  }
  
  /*
   * This checks to see if we've paid someone already.  Acts as a sanity check, but also lets
   * a round start with only some of the users registered.  Can reset the status of the payment and
   * only missing paymetns will be done.
   */
  public function paymentDone($paymentId, $accountId)
  {
    $stmt = $this->mysqli->prepare("SELECT * FROM ".$this->table."_procpayments WHERE payment_id = ? AND account_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $paymentId, $accountId) && $stmt->execute() && $result = $stmt->get_result())
      return $result->num_rows > 0;
    return true; //error, lets assume we've paid so we're not double paying
  }
  
  /*
   * Log the payment
   */
  public function logPayment($payment_id, $account_id, $payout, $fee, $donation, $status) {
    $stmt = $this->mysqli->prepare("INSERT INTO ".$this->table."_procpayments (account_id, payment_id, amount, fee, donation, status, last_updated)
                                   VALUES (?,?,?,?,?,?,?)");
    if ($this->checkStmt($stmt) && $stmt->bind_param('iidddsi', $account_id, $payment_id, $payout, $fee, $donation, $status, time()) && $stmt->execute())
      return true;
    return $this->sqlError();
  }
  
  /*
   * Stats functions, WIP
   */
  public function getAccountInvestments($account_id)
  {
    $ret = array();
    $stmt = $this->mysqli->prepare("SELECT p.title, p.investment as total_invested, p.address, i.*
                                   FROM ".$this->table."_projects AS p, ".$this->table."_investments AS i
                                   WHERE p.id = i.project_id
                                   AND i.account_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $account_id) && $stmt->execute() && $result = $stmt->get_result())
    {
      while($row = $result->fetch_assoc())
      {
        $ret[] = $row;
      }
      return $ret;
    }
    return $this->sqlError();
  }
  
  public function hasInvested($project_id, $account_id)
  {
    $stmt = $this->mysqli->prepare("SELECT * FROM ".$this->table."_investments WHERE project_id = ? AND account_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $project_id, $account_id) && $stmt->execute() && $result = $stmt->get_result())
      return $result->num_rows > 0;
    return true;
  }
  
  public function getProjectDetails($project_id)
  {
    $stmt = $this->mysqli->prepare("SELECT * FROM ".$this->table."_projects WHERE project_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $project_id) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_assoc();
    return true;
  }
  
  public function getProjectInvestors($project_id)
  {
    $ret = array();
    $stmt = $this->mysqli->prepare("SELECT i.*, a.username, p.investment as total_invested
                                   FROM ".$this->table."_projects AS p, ".$this->table."_investments AS i, accounts as a
                                   WHERE i.account_id = a.id
                                   AND p.id = i.project_id
                                   AND i.project_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $project_id) && $stmt->execute() && $result = $stmt->get_result())
    {
      while($row = $result->fetch_assoc())
      {
        $ret[] = $row;
      }
      return $ret;
    }
    return $this->sqlError();
  }
}

// Automatically load our class for furhter usage
$groupbuy = new GroupBuy();
$groupbuy->setDebug($debug);
$groupbuy->setMysql($mysqli);
$groupbuy->setConfig($config);
$groupbuy->setUser($user);
$groupbuy->setBlock($block);
$groupbuy->setErrorCodes($aErrorCodes);
