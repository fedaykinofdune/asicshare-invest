  <div class="row">
    <div class="col-lg-12">
    
    <div class="panel panel-info">
      <div class="panel-heading">
        <i class="fa fa-tachometer fa-fw"></i> Your Investments
      </div>
      <div class="panel-body no-padding table-responsive">
        <table class="table table-striped table-bordered table-hover">
          <thead>
            <tr>
              <th>Investment Name</th>
              <th>Address</th>
              <th>Total Shares</th>
              <th>Your Shares</th>
              <th>Your %</th>
            </tr>
          </thead>
          <tbody>
{section invest $MYINVESTMENTS}
      {math assign="my_percent" equation="round(investment / total_investment * 100, 2)" investment=$MYINVESTMENTS[invest].investment total_investment=$MYINVESTMENTS[invest].total_invested}
            <tr>
              <td><a href="{$smarty.server.SCRIPT_NAME}?page=investments&action=details&project={$MYINVESTMENTS[invest].project_id}">{$MYINVESTMENTS[invest].title}</a></td>
              <td><a href="http://dogechain.info/address/{$MYINVESTMENTS[invest].address}" target="_blank">{$MYINVESTMENTS[invest].address}</a></td>
              <td>{$MYINVESTMENTS[invest].total_invested|number_format:4}</td>
              <td>{$MYINVESTMENTS[invest].investment|number_format:4}</td>
              <td>{$my_percent|number_format:2}</td>
            </tr>
{/section}
          </tbody>
        </table>
      </div>
      <div class="panel-footer">
          <!-- <h6>
          <i class="fa fa-ban fa-fw"></i>no Donation
          <i class="fa fa-star-o fa-fw"></i> 0&#37;&#45;2&#37; Donation 
          <i class="fa fa-trophy fa-fw"></i> 2&#37; or more Donation
          </h6> -->
      </div>
    </div>
      
    </div>
  </div>
