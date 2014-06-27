<?php
$defflip = (!cfip()) ? exit(header('HTTP/1.1 401 Unauthorized')) : 1;

// Check user to ensure they are admin
if (!$user->isAuthenticated() || !$user->isAdmin($_SESSION['USERDATA']['id'])) {
  header("HTTP/1.1 404 Page not found");
  die("404 Page not found");
}
error_reporting(E_ALL);

$projectId = intval($_GET['project']);

if($projectId > 0)
{
    $hasInvested = $groupbuy->hasInvested($projectId, $_SESSION['USERDATA']['id']);

    if($hasInvested)
    {
        $projectDetails = $groupbuy->getProjectDetails($projectId);
        $projectInvestors = $groupbuy->getProjectInvestors($projectId);
        
        $smarty->assign("PROJECTDETAILS", $projectDetails);
        $smarty->assign("PROJECTINVESTORS", $projectInvestors);
    }
}



// Tempalte specifics
$smarty->assign("CONTENT", "default.tpl");
?>
