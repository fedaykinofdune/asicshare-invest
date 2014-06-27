<?php
$defflip = (!cfip()) ? exit(header('HTTP/1.1 401 Unauthorized')) : 1;

// Check user to ensure they are admin
if (!$user->isAuthenticated() || !$user->isAdmin($_SESSION['USERDATA']['id'])) {
  header("HTTP/1.1 404 Page not found");
  die("404 Page not found");
}
error_reporting(E_ALL);
$myInvestments = $groupbuy->getAccountInvestments($_SESSION['USERDATA']['id']);


$smarty->assign("MYINVESTMENTS", $myInvestments);

// Tempalte specifics
$smarty->assign("CONTENT", "default.tpl");
?>
