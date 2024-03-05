<?require("./global.php");
$quoteId=clear($_GET['quoteId']);
$invoiceId=clear($_GET['invoiceId']);

$estimatorIdToInfo=[];
$estimators=getAll($con,"select * from darlelJobber_users");
foreach($estimators as $row)
    $estimatorIdToInfo[$row['id']]=$row;
    
$printNotes = (isset($_GET['printQuoteNotes'])) ? 1 : 0;

if($quoteId==""){
    $isInvoice=1;
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where id='$invoiceId'");
    $quoteId=$invoiceDeets['quoteId'];
    $entries=getAll($con,"select * from darlelJobber_quote_details where quoteId='$quoteId'");
    $customerId=$invoiceDeets['customerId'];
    if($invoiceDeets['paidStatus']!="Paid")
        $notPaid=1;
        
    $quoteId=$invoiceDeets['quoteId'];
    $paidAmountQuote=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where quoteId='$quoteId'")['paidAmount'];
    if($paidAmountQuote=="")
        $paidAmountQuote=0;
    
    $discountedAmount = $invoiceDeets['total'] - ($invoiceDeets['total']*(3/100));
    $discountedAmount = round($discountedAmount,2);
    
    $tempQuoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
    $clientProjectName=$tempQuoteDeets['projectName'];
    $propertyId=$tempQuoteDeets['propertyId'];
    
}
else if($invoiceId==""){
    $isQuote=1;
    
    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
    $clientProjectName=$quoteDeets['projectName'];

    if($quoteDeets['sentDate']==""){
        $sentDate=time();
        runQuery("update darlelJobber_quotes set sentDate='$sentDate' where id='$quoteId'");
    }
    
    $entries=getAll($con,"select * from darlelJobber_quote_details where quoteId='$quoteId'");
    $customerId=$quoteDeets['customerId'];
    $propertyId=$quoteDeets['propertyId'];
    
    //get the estimator info
    $requestId=$quoteDeets['requestId'];
    $estimatorId=$quoteDeets['estimatorId'];
    if($estimatorId=="None"){//for older requests the quote will not have an estimator   
        $query="select name from darlelJobber_users where role='Estimator' && id in (select userId from darlelJobber_teams where requestId='$requestId')";
        $estimatorName=getRow($con,$query)['name'];
    }
    else//for newer quotes the quote will have an estimator
        $estimatorName=$estimatorIdToInfo[$estimatorId]['name'];    
    
    if($quoteDeets['paidStatus']!="Paid")
        $notPaid=1;
    $discountedAmount = $quoteDeets['total'] - ($quoteDeets['total']*(3/100));
    $discountedAmount = round($discountedAmount,2);
    
    $discountedAmountQuote = $quoteDeets['requiredDepositAmount'] - ($quoteDeets['requiredDepositAmount']*(3/100));
    $discountedAmountQuote = round($discountedAmountQuote,2);
}
  
$customerDeets=getRow($con,"select * from darlelJobber_users where id='$customerId'");
$customerName=$customerDeets['first_name']." ".$customerDeets['last_name'];
$propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyId'");
$zipCode="";
if($propertyDeets['zip_code']!="")
    $zipCode = " Zip Code : ".$propertyDeets['zip_code'];
    
$customerAddress=$propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country'].$zipCode;      

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
        <link rel="shortcut icon" href="assets/logo.png" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
        <style>
    		.page-break {
    			page-break-before: always;
    		}
            .table tr {
                page-break-inside: avoid;
            }
    	</style>
    </head>
	<body style="background-color: white;font-size:x-large !important">
	    <div class="d-flex flex-column flex-root">
			<div class="page d-flex flex-row flex-column-fluid">
				<div class="wrapper d-flex flex-column flex-row-fluid" style="padding:0;">
					<div class="">
					    <div class="post d-flex flex-column-fluid">
					        <div style="width: 100% !important;">
					            
					            <div class="card card-flush">
									<div class="card-body pt-0">
									    <div class="row">
									        <div class="col-7 mt-5">
    							                <div class="row">
    							                    <div class="col-12">
    						                            <div class="d-flex">
        						                            <img src="assets/logo.png" style="height: 200px;">
                										    <p style="margin-top: 56px;">3805 West Osborne Avenue | Tampa, Florida 33614<br>
                                                            8134430771 | customerservice@vilofence.com | vilofence.com
                                                            </p>
                                                        </div>
            								        </div>
            								        <div class="col-12" style="margin-top:<?echo ($clientProjectName=="") ? "120px" : "80px";?>">
        								                <h1 class="mb-3" style="font-weight: 700;">Recipient : <?echo $customerName?></h1>
        									            <?if($clientProjectName!=""){?>
        									            <h1 class="mb-3" style="font-weight: 700;">Project Name  : <?echo $clientProjectName?></h1>
        									            <?}?>
        									            <p class="mb-2" style="font-size: 22px;"><?echo $customerAddress?></p>
        									            <p class="mb-2" style="font-size: 22px;">
        									                <?$emails=explode("*",$emails);
        									                $counter=1;
        									                foreach($emails as $row){echo " (".$counter.") ".$row;$counter++;}?>
        									            </p>
        									            <p class="mb-5" style="font-size: 22px;">
        									                <?$phones=explode("*",$phones);
        									                $counter=1;
        									                foreach($phones as $row){echo " (".$counter.") ".$row;$counter++;}?>
        									            </p>
        									        </div>
    							                </div>
    							            </div>
									        <div class="col-5 mt-5">
									            <?if($isQuote){?>
                                                <table class="table table-rounded border  gs-7">
                                                    <thead>
                                                        <tr style="background-color:#e65330;">
                                                            <th style="font-size: x-large;color: white;">Quote #<?echo $quoteDeets['quote_number']?></th>
                                                            <th style="text-align:right;">
                                                                <?if($notPaid){?>
                                                                <a style="margin-left: 100px;background-color: red;" class="btn btn-danger">Status : Pending</a>
                                                                <?}else{?>
                                                                <a style="margin-left: 100px;" class="btn btn-success">Status : Paid</a>
                                                                <?}?>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Sent On</td>
                                                            <td style="text-align:right;"><?echo date("d M Y",$quoteDeets['sentDate'])?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Estimator</td>
                                                            <td style="text-align:right;"><?echo $estimatorName?></td>
                                                        </tr>
                                                        <?if($quoteDeets['cashOnly']=="No"){?>
                                                        <tr style="background-color:#e65330;border-bottom: 1px solid white;">
                                                            <th style="font-size: x-large;color: white;">Total With Card : </th>
                                                            <th style="font-size: x-large;color: white;text-align:right;"><?echo "$".$quoteDeets['total']?></th>
                                                        </tr>
                                                        <?}?>
                                                        <tr style="background-color:#e65330;">
                                                            <th style="font-size: x-large;color: white;">Total With Check/Cash : </th>
                                                            <th style="font-size: x-large;color: white;text-align:right;">
                                                                <?echo ($quoteDeets['cashOnly']=="No") ? "$".$discountedAmount : "$".$quoteDeets['total'];?>
                                                            </th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <?}else{?>
                                                <table class="table table-rounded border  gs-7">
                                                    <thead>
                                                        <tr style="background-color:#e65330;">
                                                            <th style="font-size: x-large;color: white;">Invoice #<?echo $invoiceDeets['invoice_number']?></th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Issued</td>
                                                            <td style="text-align:right;"><?echo date("d M Y",$invoiceDeets['issued_date'])?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Due</td>
                                                            <td style="text-align:right;"><?echo date("d M Y",$invoiceDeets['payment_due'])?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Paid Amount In Quote</td>
                                                            <td style="text-align:right;"><?echo "$ ".$paidAmountQuote;?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Paid</td>
                                                            <?$paidDate = ($invoiceDeets['paid_date']=="None" || $invoiceDeets['paid_date']=="") ? "Not Paid" : date("d M Y",$invoiceDeets['paid_date']);?>
                                                            <td style="text-align:right;"><?echo $paidDate;?></td>
                                                        </tr>
                                                        <?if($tempQuoteDeets['cashOnly']=="No"){?>
                                                        <tr style="background-color:#e65330;border-bottom: 1px solid white;">
                                                            <th style="font-size: x-large;color: white;">Total With Card : </th>
                                                            <th style="font-size: x-large;color: white;text-align:right;"><?echo "$ ".$invoiceDeets['total']?></th>
                                                        </tr>
                                                        <?}?>
                                                        <tr style="background-color:#e65330;">
                                                            <th style="font-size: x-large;color: white;">Total With Check/Cash : </th>
                                                            <th style="font-size: x-large;color: white;text-align:right;">
                                                                <?echo ($tempQuoteDeets['cashOnly']=="No") ? "$".$discountedAmount : "$".$invoiceDeets['total'];?>
                                                            </th>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <?}?>
									        </div>
									        <div class="col-12">
									            <div class="table-responsive">
                                                   <table class="table table-rounded table-striped border gy-7 gs-7">
                                                      <thead>
                                                         <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200" style="font-size: x-large !important;">
                                                            <th>PRODUCT / SERVICE</th>
                                                            <th>DESCRIPTION</th>
                                                            <th>QTY</th>
                                                            <th>UNIT PRICE</th>
                                                            <th>TOTAL</th>
                                                         </tr>
                                                      </thead>
                                                      <tbody>
                                                          <?foreach($entries as $row){?>
                                                         <tr <?if($row['optionalStatus']=="Yes" && $row['optionalApproveStatus']=="No"){echo "style='display:none'";}?>>
                                                            <td><?echo $row['service']?></td>
                                                            <td><?echo $row['description']?></td>
                                                            <td><?echo $row['qty']?></td>
                                                            <td><?echo $row['unit_price']?></td>
                                                            <td>
                                                                <?echo $row['total'];
                                                                if($row['optionalStatus']=="Yes" && $row['optionalApproveStatus']=="No")
                                                                    echo "<br>Not Included";
                                                                ?>
                                                            </td>
                                                         </tr>
                                                         <?}?>
                                                      </tbody>
                                                   </table>
                                                </div>
									        </div>
									    </div>
                                        <?if($isQuote){?>
                                        <div class="row mt-10">
                                            <div class="col-12 text-center">
                                                <b style="font-size: 25px;">
                                                    At approval an installation appointment will be given, although a deposit of <?echo "$".$quoteDeets['requiredDepositAmount'];if($quoteDeets['cashOnly']=="No"){echo " or $".$discountedAmountQuote." with Check/Cash";}?>
                                                    will be
                                                    needed to confirm the date. Date can be subject to change until then.
                                                </b>
                                            </div>
                                            <div class="col-12 mt-8">
                                                <b style="font-size: 25px;">Terms And Conditions : <br></b>
                                                <p style="font-size: 25px;">
                                                    <br>-At approval an installation appointment will be given, although a deposit will be needed to confirm the date. Date can be subject to change until then.
                                                    <br>-Vilo Fence LLC currently accepts almost all types of payments. Nevertheless, there will be a 2.5% convenience fee of the
                                                    total amount for all credit or debit cards transactions. Credit card holders must sign the receipt and provide all the billing information to the office. In order to avoid this fee the client can always give our crews a check or cash which they will bring to the office and we will be emailing you a receipt, as well as a physical copy along with the warranty through mail (in vinyl installations only).
                                                    <br>- Customer shall provide to Vilo Fence LLC an accurate copy of the survey, if no survey is provided the Homeowner takes full responsibility of fence location, Vilo Fence LLC will not move or re-install a fence free of charge.
                                                    <br>- Quote is based in the price of the material by the time of the estimate and it is valid for fifteen (15) days from the date noted in the quote. Price may be higher than quoted if prices of the material are increased or changes are made to the job description.
                                                    <br>- Customer is responsible for clearing fence lines of bushes or debris unless otherwise set forth. If we get to the job-site and fence line is not clear, an extra fee will be discussed with the owner before proceeding to install the fence.
                                                    <br>- Customer may cancel deposit transaction (40% of the total) without any penalty or obligation within three (3) days from date noted in contract. After that period there will be no devolutions, unless there is circumstances in which we cannot install a fence such as a denial per the HOA or by the city with written proof, verbal confirmation will not be accepted as proof.
                                                    <br>- Customer has the right to make changes to the initial contract up to one week (7 business days) before the installation day without charges. In this case, Vilo Fence LLC will just add the cost for the extra material, which may be used, to the amount due.
                                                    <br>- If the customer requests a change to the installation within seven business days previous to the installation day, there will be a charge of $250.00 plus the cost of the extra material that may be used, and the installation day may be move to a different day, in order to acquire the extra material.
                                                    <br>- Underground locators only mark major utilities, customer is responsible to mark any other facilities such as in-ground pool equipment, sprinkler systems, or drain tile. Vilo Fence LLC assumes no liability for damage to unmarked or personal utilities.
                                                    <br>- Customer is responsible to make final payment within three (3) days after job is finished. If customer fails to make payment within this period, there will be a charge of 1½ % per month on all unpaid accounts.
                                                    <br>- All material remains property of Vilo Fence LLC until payment is received in full. By signing below right of access and removal is granted to Vilo Fence LLC in the event of nonpayment.
                                                    <br>- Vilo Fence LLC does not require the customer to be present at the installation time, and we will not be responsible for any
                                                    change on the schedule. However, we require the customer to be present by the end of the installation, so a final inspection can be made. Final payment shall be made to the person in charge of the crew after inspection is done. Company will be mailing a receipt along with a material warranty (for PVC) three (3) to five (5) days after payment is
                                                    received.
                                                    <br>- We reserve the right to alter the work schedule if circumstances require us to do so. We will continue to work in the majority of weather conditions, but we will always advise customers if very bad weather causes delay, or there are circumstances beyond our control which causes us to stop work for any period during the scheduled time.
                                                    <br>- We offer one (1) year warranty for labor. Any repairs should be reported to the office and allow seven (7) to ten (10) businesses days to be fixed.
                                                    <br>- If the person signing the contract, is not the owner of the property, we require a letter signed by the owner authorizing Vilo Fence LLC to install a fence at the property.
                                                </p>
                                            </div>
                                            <div class="col-6 mt-8 text-center">
                                                <b style="font-size: x-large;">Signature : _____________________</b>
                                            </div>
                                            <div class="col-6 mt-8 text-center">
                                                <b style="font-size: x-large;">Date : _____________________</b>
                                            </div>
                                        </div>
                                        
                                        <?if($printNotes && false){?>
                                        
                                        
                                        <div class="page-break"></div>
                                        <!--now we display all the images of this quote notes-->
                                        <?
                                        $quoteNotesImages=getAll($con,"SELECT * from darlelJobber_notes_images where notesId in 
                                        (select id from darlelJobber_notes where quoteId='$quoteId')");
                                        foreach($quoteNotesImages as $row){
                                            $image=$row['image'];
                                            echo "<img src='./uploads/$image'>";
                                        }
                                        }?>
                                        
                                        
                                        
                                        <?}?>
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