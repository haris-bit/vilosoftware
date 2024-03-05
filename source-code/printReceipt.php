<?
require("./global.php");
 
$paymentId=clear($_GET['paymentId']);
$paymentDeets=getRow($con,"select * from darlelJobber_payments where id='$paymentId'");

if($paymentDeets['quoteId']==""){
    $invoiceId=$paymentDeets['invoiceId'];
    $invoiceNumber=getRow($con,"select * from darlelJobber_invoices where id='$invoiceId'")['invoice_number'];
    $query="select * from darlelJobber_properties where id=(select propertyId from darlelJobber_invoices where id='$invoiceId')";
    $propertyDeets=getRow($con,$query);
    $isInvoice=1;
    $displayString=" Payment Applied to Invoice #".$invoiceNumber;
}

else if($paymentDeets['invoiceId']==""){
    $quoteId=$paymentDeets['quoteId'];
    $quoteNumber=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'")['quote_number'];
    $query="select * from darlelJobber_properties where id=(select propertyId from darlelJobber_quotes where id='$quoteId')";
    $propertyDeets=getRow($con,$query);
    $isQuote=1;
    
    $displayString=" Payment Applied to Quote #".$quoteNumber;
}
    
$customerId=$paymentDeets['customerId'];
$customerDeets=getRow($con,"select * from darlelJobber_users where id='$customerId'");
$customerName=$customerDeets['first_name']." ".$customerDeets['last_name'];


$emails=$customerDeets['email'];
$phones=$customerDeets['phone'];

?>
<!DOCTYPE html>
<html lang="en">
   <head>
    <title><?echo $projectName?></title>
    <meta charset="utf-8" />
    <meta name="description" content="<?echo $projectName?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
   
   </head>
   <body style="background-color: white;">
      <div class="d-flex flex-column flex-root">
         <div class="page d-flex flex-row flex-column-fluid">
            <div class="wrapper d-flex flex-column flex-row-fluid" style="padding:0;">
               <div class="">
                  <div class="post d-flex flex-column-fluid">
                     <div style="width: 100% !important;">
                        <div class="card card-flush">
                           <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                              <div class="card-title" style="margin-left: 80px;">
                                 <img src="assets/logo.png" style="height: 200px;">
                                 <p>3805 West Osborne Avenue | Tampa, Florida 33614<br>
                                 8134430771 | customerservice@vilofence.com | vilofence.com
                                 </p>
                              </div>
                           </div>
                           <div class="card-body pt-0">
                              <div class="row" style="margin-left: 100px;margin-right: 200px;">
                                 <div class="col-6">
                                    <h4 class="mb-4">Recepient : </h4>
                                    <h3 class="mb-4"><?echo $customerName?></h3>
                                    <h5 class="mb-4"><?echo $propertyDeets['street1']." ".$propertyDeets['street2']?></h5>
                                    <h5 class="mb-4"><?echo $propertyDeets['city']." ".$propertyDeets['state']?></h5>
                                    <h5 class="mb-4"><?echo $propertyDeets['country']?></h5>
                                    <h5 class="mb-4"><?echo "Zip Code : ".$propertyDeets['zip_code']?></h5>
                                    <h5 class="mb-2">
                                       <?$emails=explode("*",$emails);
                                          foreach($emails as $row){echo $row."<br>";}?>
                                    </h5>
                                    <h5 class="mb-2">
                                       <?$phones=explode("*",$phones);
                                          foreach($phones as $row){echo $row."<br>";}?>
                                    </h5>
                                 </div>
                                 <div class="col-6" style="text-align:right;">
                                    <a class="btn btn-warning" style="background-color:#e4532f">Transaction Date : <?echo date("d M Y",$paymentDeets['transactionDate'])?></a>
                                 </div>
                                 <hr>
                              </div>
                              <div class="row" style="margin-left: 100px;margin-right: 200px;">
                                 <div class="col-12">
                                    <h1>Receipt For Deposit</h1>
                                    <h3 class="mt-2">Amount : <?echo $paymentDeets['amountPaid']?></h3>
                                    <br>
                                    <h3 class="mt-2">Title : <?echo $paymentDeets['title']?></h3>
                                    <h3 class="mt-2">Description : <?echo $paymentDeets['description']?></h3>
                                    <h3 class="mt-2">
                                    Transaction Date : <?echo date("d M Y",$paymentDeets['transactionDate'])?></h1>
                                    <h3 class="mt-2">Method Of Payment : <?echo $paymentDeets['method']?></h3>
                                    <hr class="mt-10 mb-10">
                                    <h3><?echo $displayString?></h3>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </body>
</html>