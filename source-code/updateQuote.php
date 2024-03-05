<?
require("./global.php");
$title=clear($_POST['title']);
$required_811=clear($_POST['required_811']);
$complete_payment=clear($_POST['complete_payment']);
$displayPricing=clear($_POST['displayPricing']);
$estimatorId=clear($_POST['estimatorId']);
$quoteId=clear($_POST['quoteId']);
$projectName=clear($_POST['projectName']);
$cashOnly=clear($_POST['cashOnly']);


$complete_payment = ($complete_payment=="on") ? "Yes":"No";
$required_811 = ($required_811=="on") ? "Yes":"No";
$cashOnly = ($cashOnly=="on") ? "Yes":"No";
$displayPricing = ($displayPricing=="on") ? "Yes":"No";

$query="update darlelJobber_quotes set title='$title',projectName='$projectName',cashOnly='$cashOnly',
estimatorId='$estimatorId',required_811='$required_811',complete_payment='$complete_payment',displayPricing='$displayPricing' where id='$quoteId'";
runQuery($query);
?>