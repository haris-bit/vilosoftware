<?
$entryId=clear($_GET['entryId']);
$fileName = basename($_SERVER['PHP_SELF']);

$isRequest = ($fileName=="createRequest.php") ? 1 : 0;
$isQuote = ($fileName=="createQuote.php") ? 1 : 0;
$isJob = ($fileName=="createJob.php") ? 1 : 0;
$isInvoice = ($fileName=="createInvoice.php") ? 1 : 0;

if(isset($_POST['sendSmsModal'])){
    
    //if is quote then update the pending changes to pending 
    if($isQuote){
        $sentDate=time();
        $quoteId=clear($_GET['entryId']);
        $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
        $endTimer=time();
        if($quoteDeets['endTimer']=="None")
            $query="update darlelJobber_quotes set sendStatus='Sent',sentDate='$sentDate',approveStatus='In Progress',endTimer='$endTimer',lastContacted='$endTimer',changesRequestedEndTime='$endTimer' where id='$quoteId'";
        else
            $query="update darlelJobber_quotes set sendStatus='Sent',sentDate='$sentDate',approveStatus='In Progress',lastContacted='$endTimer',changesRequestedEndTime='$endTimer' where id='$quoteId'";
        runQuery($query);
    }
    
    $phoneNumbers=$_POST['phoneNumbers'];
    $smsDescription=$_POST['smsDescription'];
    foreach($phoneNumbers as $row)
        sendansms($row,$smsDescription);
        
    if($fileName=="view_client.php"){    
        $userId=clear($_GET['id']);
        header("Location:?m=SMS has been sent successfully&id=$userId");
    }
    else    
        header("Location:?m=SMS has been sent successfully&entryId=$entryId");
}
?>