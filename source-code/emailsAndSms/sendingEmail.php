<?
$entryId=clear($_GET['entryId']);
$fileName = basename($_SERVER['PHP_SELF']);

if(isset($_POST['sendEmailModal'])){
    $emails=$_POST['emails'];
    $emailDescription=$_POST['emailDescription'];
    $sendPDF = (isset($_POST['sendPDF'])) ? 1:0;
    $emailTitle=$_POST['emailTitle'];
    $redirection=$_POST['redirection'];
    
    $isQuote = ($fileName=="createQuote.php" || $redirection=="quote") ? 1 : 0;
    $isInvoice = ($fileName=="createInvoice.php" || $redirection=="invoice") ? 1 : 0;

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

    $tableNameMap=[
        "createQuote.php"=>"darlelJobber_quotes",
        "createInvoice.php"=>"darlelJobber_invoices",
    ];
    $tableName=$tableNameMap[$fileName];
    if($redirection!=""){
        $tableName = ($redirection=="quote") ? "darlelJobber_quotes" : "darlelJobber_invoices";
        $entryId=$_POST['entryId'];
    }    
    
    if($sendPDF){
        $sendingFile="$entryId.pdf";
        $query="update $tableName set pdfSnapshot='$sendingFile' where id='$entryId'";
        runQuery($query);
        
        $urlId = ($isQuote) ? "quoteId" : "invoiceId";
        $url=urlencode($g_website.'/printQuoteInvoice.php?'.$urlId.'='.$entryId);
        printPage(urldecode($url),$entryId);
        
        $file_name_link=trim("./uploads/".$sendingFile);
        $the_content_type = $file_name_link;
    	$get_file = file_get_contents($file_name_link);
    	$content = base64_encode($get_file);
        $data = [
    		"ContentType" => $the_content_type,
    		"Filename" => $file_name_link,
    		"Base64Content" => $content,
    	];
    }
    
    foreach($emails as $row){
        if(!$sendPDF)
            sendEmailNotification_mailjet($emailTitle, $emailDescription, $row);
        else if($sendPDF)
    	    sendEmailNotification_mailjet($emailTitle,$emailDescription,$row,1,$data);
    }
    if($fileName=="view_client.php"){    
        $userId=clear($_GET['id']);
        header("Location:?m=Email has been sent successfully&id=$userId");
    }
    else
        header("Location:?m=Email has been sent successfully&entryId=$entryId");
}
?>