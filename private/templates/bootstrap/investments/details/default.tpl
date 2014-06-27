  <div class="row">
    <div class="col-lg-12">
    
    <div class="panel panel-info">
      <div class="panel-heading">
        <i class="fa fa-tachometer fa-fw"></i> Project Investors
      </div>
      <div class="panel-body no-padding table-responsive">
        <table class="table table-striped table-bordered table-hover">
          <thead>
            <tr>
              <th>Investor</th>
              <th>Shares</th>
              <th>%</th>
            </tr>
          </thead>
          <tbody>
{section invest $PROJECTINVESTORS}
      {math assign="inv_percent" equation="round(investment / total_investment * 100, 2)" investment=$PROJECTINVESTORS[invest].investment total_investment=$PROJECTINVESTORS[invest].total_invested}
            <tr>
              <td>{$PROJECTINVESTORS[invest].username}</td>
              <td>{$PROJECTINVESTORS[invest].investment|number_format:4}</td>
              <td>{$inv_percent|number_format:2}</td>
            </tr>
{/section}
          </tbody>
        </table>
      </div>
      <div class="panel-footer">
        <!--
          <h6>
          <i class="fa fa-ban fa-fw"></i>no Donation
          <i class="fa fa-star-o fa-fw"></i> 0&#37;&#45;2&#37; Donation 
          <i class="fa fa-trophy fa-fw"></i> 2&#37; or more Donation
          </h6>
        -->
      </div>
    </div>
      
    </div>
  </div>
