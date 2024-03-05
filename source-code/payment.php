<? require("./global.php");

require_once('./vendor/autoload.php');

$stripe = new \Stripe\StripeClient([
    "api_key" => $g_stripeCred['private_test_key'], 
    "stripe_version" => "2020-08-27"
    ]
);
$discount=3;
$invoiceId=clear($_GET['invoiceId']);
$quoteId=clear($_GET['quoteId']);
$redirectLink="";
$invoiceView = (isset($_GET['invoiceId'])) ? 1 : 0;
$quoteView = (isset($_GET['quoteId'])) ? 1 : 0;

if($invoiceView){
    $partialPaymentAmount=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where invoiceId='$invoiceId'")['paidAmount'];
    $partialPaymentAmount = ($partialPaymentAmount == "" ) ? 0 : round($partialPaymentAmount,2);
    
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where id='$invoiceId'");
    $quoteId=$invoiceDeets['quoteId'];
    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
    if($invoiceDeets['paidStatus']=="Paid"){
        header("Location:./invoices.php?m=This invoice has already been paid ");
        exit();
    }
    $canPayThroughCard = ($quoteDeets['canPayThroughCard']=="Yes") ? 1 : 0 ;
    $redirectLink="&invoiceId=".$invoiceId;
    $paidAmountQuote=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where quoteId='$quoteId'")['paidAmount'];
    if($paidAmountQuote=="")
        $paidAmountQuote=0;
        
    $cashOnly=$quoteDeets['cashOnly'];
    $paidAmountQuote=round($paidAmountQuote,2);
    $invoiceDeets['total']=round($invoiceDeets['total'],2);
    $amountPayableWithCard=$invoiceDeets['total']-$partialPaymentAmount." $";
    $payHeading = ($quoteDeets['cashOnly']=="Yes") ? "Pay With E-Check/Cash" : "Pay With Card";
    $discountAvailed=($invoiceDeets['total']-$partialPaymentAmount)*($discount/100);
    $payableAmount=$invoiceDeets['total']-$discountAvailed;
    $payableAmount=round($payableAmount,2);
    $amountOnBtn=$payableAmount-$partialPaymentAmount;
    if($quoteDeets['cashOnly']=="Yes")
        $amountOnBtn=$invoiceDeets['total']-$partialPaymentAmount;
}
else if($quoteView){
    $canPayThroughCard = 1 ;
    $partialPaymentAmount=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where quoteId='$quoteId'")['paidAmount'];
    $partialPaymentAmount = ($partialPaymentAmount == "" ) ? 0 : round($partialPaymentAmount,2);
    
    //if all forms are not filled then ask to fill forms
    $formAStatus=getRow($con,"select * from darlelJobber_formA where quoteId='$quoteId'")['submissionStatus'];
    $formBStatus=getRow($con,"select * from darlelJobber_formB where quoteId='$quoteId'")['submissionStatus'];
    $formCStatus=getRow($con,"select * from darlelJobber_formC where quoteId='$quoteId'")['submissionStatus'];
    $formDStatus=getRow($con,"select * from darlelJobber_formD where quoteId='$quoteId'")['submissionStatus'];
    
    if($formAStatus=="Not Submitted" ){
        header("Location:./formA.php?quoteId=$quoteId");
        exit();
    }
    else if($formBStatus=="Not Submitted"){
        header("Location:./formB.php?quoteId=$quoteId");
        exit();
    }
    else if($formCStatus=="Not Submitted" ){
        header("Location:./viewQuote.php?entryId=$quoteId&m=Kindly approve the quote to progress further");
        exit();
    }
    else if($formDStatus=="Not Submitted" ){
        header("Location:./formD.php?quoteId=$quoteId");
        exit();
    }
    
    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
    if($quoteDeets['paidStatus']=="Paid"){
        header("Location:./oldQuotes.php?m=This quote has already been paid ");
        exit();
    }
    $redirectLink="&quoteId=".$quoteId;
    if($quoteDeets['complete_payment']=="Yes"){
        header("Location:./thankyou.php?m=complete_payment");
        exit();
    }
    $cashOnly=$quoteDeets['cashOnly'];
    $quoteDeets['subtotal']=round($quoteDeets['subtotal'],2);
    $quoteDeets['requiredDepositAmount']=round($quoteDeets['requiredDepositAmount'],2);
    $amountPayableWithCard=($quoteDeets['requiredDepositAmount']-$partialPaymentAmount)." $";
    $requiredDepositText=($quoteDeets['requiredDepositType']=="Percentage") ? " %" : " $";
    $requiredDepositText=$quoteDeets['required_deposit'].$requiredDepositText;
    $payHeading = ($quoteDeets['cashOnly']=="Yes") ? "Pay With E-Check/Cash" : "Pay With Card";
    $totalAmountCash=round($quoteDeets['total']-$quoteDeets['total']*($discount/100),2);
    $discountAvailed=($quoteDeets['requiredDepositAmount']-$partialPaymentAmount)*($discount/100);
    $displayPayableAmount=$quoteDeets['requiredDepositAmount']-(($quoteDeets['requiredDepositAmount']-$partialPaymentAmount)*($discount/100));
    $payableAmount=$quoteDeets['requiredDepositAmount']-$discountAvailed;
    $payableAmount=round($payableAmount,2);
    $amountOnBtn=($payableAmount-$partialPaymentAmount);
    if($quoteDeets['cashOnly']=="Yes")
        $amountOnBtn=($quoteDeets['requiredDepositAmount']-$partialPaymentAmount);
}

if((isset($_POST['pay_with_card']) || (isset($_POST['pay_with_echeck'])))){
    
    if(isset($_POST['pay_with_card']))
        $paymentMethod="card";
    else
        $paymentMethod="us_bank_account";
    if($invoiceView){
        $discount=0;
        if($paymentMethod=="card" || ($quoteDeets['cashOnly']=="Yes"))
            $amount=$invoiceDeets['total']-$partialPaymentAmount;
        else if($paymentMethod=="us_bank_account"){
            $amount=$amountOnBtn;
            $discount=$discountAvailed;
        }
        
        $invoiceId=$invoiceDeets['id'];
        $key=md5(md5($invoiceId."payment"));
        $amountKey=md5(md5($amount."amount"));
        $discountKey=md5(md5($discount."discount"));
        
        $extraSuccessUrl = ($quoteDeets['cashOnly']=="Yes") ? "&cashOnly=1" : "";
        $successUrl = $projectUrl."invoices.php?m=p_success&invoiceId=$invoiceId&method=$paymentMethod$extraSuccessUrl&key=$key&amountKey=$amountKey&amount=$amount&discountKey=$discountKey&discount=$discount";
        $cancel_url = $projectUrl."payment.php?m=p_failed&invoiceId=$invoiceId";
    }
    else{
        $amount=$quoteDeets['requiredDepositAmount']-$partialPaymentAmount;
        $discount=0;
        if($paymentMethod=="us_bank_account" && $quoteDeets['cashOnly']=="No"){
            $discount=$discountAvailed;
            $amount=$amountOnBtn;
        }
        $quoteId=$quoteDeets['id'];
        $key=md5(md5($quoteId."payment"));
        $amountKey=md5(md5($amount."amount"));
        $discountKey=md5(md5($discount."discount"));
        
        $extraSuccessUrl = ($quoteDeets['cashOnly']=="Yes") ? "&cashOnly=1" : "";
        $successUrl = $projectUrl."oldQuotes.php?m=p_success&quoteId=$quoteId&method=$paymentMethod$extraSuccessUrl&key=$key&amountKey=$amountKey&amount=$amount&discountKey=$discountKey&discount=$discount";
        $cancel_url = $projectUrl."payment.php?m=p_failed&quoteId=$quoteId";
    }
    if($amount==0){
        header("Location:?m=0_amount$redirectLink");
        exit();
    }
    $amount=round($amount,2);
    if($invoiceView){
        $customerId=$invoiceDeets['customerId'];
        $propertyId=$invoiceDeets['propertyId'];
    }
    else if($quoteView){
        $customerId=$quoteDeets['customerId'];
        $propertyId=$quoteDeets['propertyId'];
    }
    $customerDeets=getRow($con,"select * from darlelJobber_users where id='$customerId'");
    $propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyId'");
    $customerName=$customerDeets['first_name']." ".$customerDeets['last_name'];
    $customerEmails=explode("*",$customerDeets['email']);
    $customerPhones=explode("*",$customerDeets['phone']);
    
    $singleEmail=$customerEmails[0];
    $singlePhone=$customerPhones[0];
    
    if($singlePhone==null)
        $singlePhone="None";
    
    $allPhones=str_replace("*"," ",$customerDeets['email']);
    $allEmails=str_replace("*"," ",$customerDeets['phone']);
    
    $address = [
        'line1' => $propertyDeets['street1']." ".$propertyDeets['street2'],
        'city' => $propertyDeets['city'],
        'state' => $propertyDeets['state'],
        'country' => $propertyDeets['country'],
    ];
    
    
    $customer = $stripe->customers->create([
        'description' => $customerName, 
        'address'=>$address,
        'email' => $singleEmail, 
        'phone' => $singlePhone,
        'metadata' => [
            'allPhones' => $allPhones,
            'allEmails' => $allEmails,
        ],
    ]);
    
    //if the payment is made with echeck
    if($paymentMethod=="us_bank_account"){
        $session = $stripe->checkout->sessions->create([
          'payment_method_types' => ["us_bank_account"],
          'payment_method_options' => [
            'us_bank_account' => [
              'verification_method' => 'instant',
              'financial_connections' => ['permissions' => ['payment_method']],
            ],
          ],
          'line_items' => [[
            'price_data' => [
              'product' => $g_stripeCred['productCode'],
              'unit_amount' => $amount*100,
              'currency' => 'usd',
            ],
            'quantity' => 1,
          ]],
          'mode' => 'payment',
          'success_url' => $successUrl,
          'cancel_url' => $cancel_url,
          'customer' => $customer->id
        ]);
    }
    //if payment is made through card 
    else if($paymentMethod=="card"){
        $session = $stripe->checkout->sessions->create([
          'payment_method_types' => ["card"],
          'line_items' => [[
            'price_data' => [
              'product' => $g_stripeCred['productCode'],
              'unit_amount' => $amount*100,
              'currency' => 'usd',
            ],
            'quantity' => 1,
          ]],
          'mode' => 'payment',
          'success_url' => $successUrl,
          'cancel_url' => $cancel_url,
          'customer' => $customer->id
        ]);
    }
    ?>
        <script src="https://js.stripe.com/v3/"></script>
                                            
        <script>
            var stripe = Stripe('<?echo $g_stripeCred['public_test_key']?>');
               stripe.redirectToCheckout({
                    sessionId: '<?echo $session['id']?>'
                  }).then(function (result) {

                  });
        </script>
<?}?>
<!DOCTYPE html>

<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
		<style>
		    .mobileBorder{
                <?if($cashOnly=="No"){?>
                border-right:2px solid black;
                <?}?>
            }
            @media only screen and (max-width: 700px) {
                .mobileBorder{
                    border-right:0px solid black;
                }
            }
		</style>
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" 
	style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl">
							    
							    <?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-<?if($m=="p_failed"){echo "warning";}else{echo "success";}?> d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <?if ($m=="p_failed"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">The payment procedure was not completed. You can pay again anytime </h4>
                                        <?}else if ($m=="0_amount"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">You cannot pay 0 $ . Kindly contact your estimator to fix this issue </h4>
                                        <?}?>
                                    </div>
                                </div>
                                <?}?>
							    <div class="row">
							        <div class="col-lg-2 col-md-2 col-xs-0 col-0"></div>
							        <div class="col-lg-8 col-md-8 col-xs-12 col-12">
							            <div class="card card-flush">
        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
        										<div class="card-title">
        											<h1>Deposit Payment Options</h1>
        										</div>
        										<div class="card-toolbar">
        										    <?if($invoiceView){?>
        										    <a href="viewInvoice.php?entryId=<?echo $invoiceId?>" class="btn btn-warning">Back To Invoice</a>
                                                    <?}else if($quoteView){?>
        										    <a href="viewQuote.php?entryId=<?echo $quoteId?>" class="btn btn-warning">Back To Quote</a>
                                                    <?}?>
        										</div>
        									</div>
        									<div class="card-body pt-0">
        									    <!--invoice view started-->
        									    <?if($invoiceView){?>
    									        <form action="" method="post">
        									        <div class="row">
        									            <div class="col-md-6 col-lg-6 col-xl-6 col-12 mobileBorder">
        									                <h3 class="mt-4"><?echo $payHeading?></h5>
        								                    <h5 class="mt-4">Total : <?echo round($invoiceDeets['total'],2)." $"?></h5>
                                                            <h5 class="mt-4">Amount Payable : <?echo $invoiceDeets['total']." $"?></h5>
                                                            <?if($partialPaymentAmount!=0){?>
                                                            <h5 class="mt-4">Deposited Amount : <?echo $partialPaymentAmount." $"?></h5>
                                                            <h5 class="mt-4">Amount Payable Now : <?echo $invoiceDeets['total']-$partialPaymentAmount." $"?></h5>
                                                            <?}?>
                                                            
                                                        </div>
                                                        <div class="col-md-6 col-lg-6 col-xl-6 col-12 <?if($quoteDeets['cashOnly']=="Yes"){echo "d-none";}?>">
                                                            <h3 class="mt-4">Pay With E-Check/Cash</h5>
        								                    <h5 class="mt-4">Total : <?echo $payableAmount."$"?></h5>
                                                            <h5 class="mt-4">Amount Payable : <?echo $payableAmount." $"?></h5>
                                                            <?if($partialPaymentAmount!=0){?>
                                                            <h5 class="mt-4">Deposited Amount : <?echo $partialPaymentAmount." $"?></h5>
                                                            <h5 class="mt-4">Amount Payable Now : <?echo $amountOnBtn." $"?></h5>
                                                            <?}?>
                                                        </div>
                                                        <div class="col-md-6 col-lg-6 col-xl-6 col-12 mobileBorder">
                                                            <?if($canPayThroughCard){?>
                                                            <input type="submit" name="pay_with_card" class="btn-block btn btn-primary btn-sm my-2 <?if($cashOnly=="Yes"){echo "d-none";}?>"
                                                            value="Pay With Card (<?echo $amountPayableWithCard?>)">
        									                <?}?>
                                                        </div>
                                                        <div class="col-md-6 col-lg-6 col-xl-6 col-12 ">
                                                            <input type="submit" name="pay_with_echeck" class="btn-block btn btn-primary btn-sm my-2" value="Pay With ECheck (<?echo $amountOnBtn." $"?>)">
        									                <a class="btn-block btn btn-success btn-sm my-2" href="thankyou.php?m=pay_with_cash&<?echo "invoiceId=$invoiceId"?>">Pay With Cash</a>
                                                        </div>
                                                    </div>
        									    </form>
        									    <?}?>
        									    <!--invoice view finished-->
        									    
        									    
        									    <!--quote view started-->
        									    <?if($quoteView){?>
        									    <form action="" method="post">
        									        <div class="row">
        									            <div class="col-md-6 col-lg-6 col-xl-6 col-12 mobileBorder">
        									                <h3 class="mt-4"><?echo $payHeading?></h5>
        								                    <h5 class="mt-4">Total : <?echo round($quoteDeets['total'],2)." $"?></h5>
                                                            <h5 class="mt-4">Required Deposit : <?echo $requiredDepositText?></h5>
                                                            <h5 class="mt-4">Amount Payable : <?echo $quoteDeets['requiredDepositAmount']." $"?></h5>
                                                            <?if($partialPaymentAmount!=0){?>
                                                            <h5 class="mt-4">Deposited Amount : <?echo $partialPaymentAmount." $"?></h5>
                                                            <h5 class="mt-4">Amount Payable Now : <?echo round($quoteDeets['requiredDepositAmount']-$partialPaymentAmount,2)." $"?></h5>
                                                            <?}?>
                                                        </div>
                                                        <div class="col-md-6 col-lg-6 col-xl-6 col-12 <?if($quoteDeets['cashOnly']=="Yes"){echo "d-none";}?>">
                                                            <h3 class="mt-4">Pay With E-Check/Cash</h5>
        								                    <h5 class="mt-4">Total : <?echo $totalAmountCash." $"?></h5>
                                                            <h5 class="mt-4">Required Deposit : <?echo $requiredDepositText?></h5>
                                                            <h5 class="mt-4">Amount Payable : <?echo $displayPayableAmount." $"?></h5>
                                                            <?if($partialPaymentAmount!=0){?>
                                                            <h5 class="mt-4">Deposited Amount : <?echo $partialPaymentAmount." $"?></h5>
                                                            <h5 class="mt-4">Amount Payable Now : <?echo $amountOnBtn." $"?></h5>
                                                            <?}?>
                                                        </div>
                                                        <div class="col-md-6 col-lg-6 col-xl-6 col-12 mobileBorder">
                                                            <?if($canPayThroughCard){?>
                                                            <input type="submit" name="pay_with_card" class="btn-block btn btn-primary btn-sm my-2 <?if($cashOnly=="Yes"){echo "d-none";}?>"
                                                            value="Pay With Card (<?echo $amountPayableWithCard?>)">
        									                <?}?>
                                                        </div>
                                                        <div class="col-md-6 col-lg-6 col-xl-6 col-12 ">
    									                    <input type="submit" name="pay_with_echeck" class="btn-block btn btn-primary btn-sm my-2" value="Pay With ECheck (<?echo $amountOnBtn." $"?>)">
        									                <a class="btn-block btn btn-success btn-sm my-2" href="thankyou.php?m=pay_with_cash&<?echo "quoteId=$quoteId"?>">Pay With Cash</a>
                                                        </div>
                                                    </div>
        									    </form>
        									    <?}?>
        									    <!--quote view finished-->
        									</div>
        								</div>
							        </div>
							        
							        
							        <div class="col-12 text-center">
							            <img src="./assets/logo.png" style="height: 200px;">
							        </div>
							    </div>
							</div>
						</div>
					</div>
					
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
	<?require("./includes/views/footerjs.php");?>
	
	
		</div>
	</body>
</html>
