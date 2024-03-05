<?
if(isset($_POST['collectPayment'])){
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $amountPaid=clear($_POST['amountPaid']);
    $method=clear($_POST['method']);
    $discountAvailed=clear($_POST['discountAvailed']);
    $transactionDate=strtotime(clear($_POST['transactionDate']));
    $id=random();
    $receipt=random();
    $time=time();
    
    if($filenameLink=="createQuote.php"){
        $tableName="darlelJobber_quotes";
        $quoteId=clear($_GET['entryId']);
        $extraQuery="quoteId='$quoteId'";
        $customerId=$quoteDeets['customerId'];
        $requiredAmount=$quoteDeets['requiredDepositAmount'];
    }
    else if($filenameLink=="createInvoice.php"){
        $tableName="darlelJobber_invoices";
        $invoiceId=clear($_GET['entryId']);
        $extraQuery="invoiceId='$invoiceId'";
        $customerId=$invoiceDeets['customerId'];
        $entryPaidAmount=$invoiceDeets['paidAmount'];
        $requiredAmount=$invoiceDeets['total'];
    }
    $entryId=clear($_GET['entryId']);
    $query="insert into darlelJobber_payments set discountAvailed='$discountAvailed',id='$id',$extraQuery,title='$title',description='$description',
    amountPaid='$amountPaid',method='$method',transactionDate='$transactionDate',
    addedBy='$session_id',customerId='$customerId'";
    runQuery($query);

    //after insertion check if the amount is equal to the amount to be paid to mark the quote/job as paid
    $query="update $tableName set paidAmount=paidAmount+$amountPaid where id='$entryId'";
    runQuery($query);
    
    if($filenameLink=="createQuote.php")
        updateQuote($quoteId);
    else if($filenameLink=="createInvoice.php")
        updateInvoice($invoiceId);
    
    header("Location:?m=Deposit has been recorded successfully&entryId=$entryId");
}
?>