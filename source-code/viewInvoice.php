<?
require("./global.php");
$invoiceId=clear($_GET['entryId']);
if($logged==0 || (!$permission['view_invoices']))
    header("Location:./index.php");

$entryId=clear($_GET['entryId']);
$invoiceDeets=getRow($con,"select * from darlelJobber_invoices where id='$entryId'");

//getting quote deets to check how much money has already been deposited
$quoteId=$invoiceDeets['quoteId'];
$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");

//getting quote deets to check how much money has already been deposited
$paymentDeets=getRow($con,"SELECT sum(amountPaid) as paidAmount,sum(discountAvailed) as discountAvailed from darlelJobber_payments where quoteId='$quoteId'");
$paymentDeetsInvoice=getRow($con,"SELECT sum(amountPaid) as paidAmount,sum(discountAvailed) as discountAvailed from darlelJobber_payments where invoiceId='$invoiceId'");

$discountInQuote=$paymentDeets['discountAvailed'];
$paidAmountQuote=$paymentDeets['paidAmount'];
$paidAmountQuote = ($paidAmountQuote=="") ? 0 : $paidAmountQuote;
$discountInQuote = ($discountInQuote=="") ? 0 : $discountInQuote;

$discountInvoice=$paymentDeetsInvoice['discountAvailed'];
$paidAmountInvoice=$paymentDeetsInvoice['paidAmount']+$discountInvoice;
$paidAmountInvoice = ($paidAmountInvoice=="") ? 0 : $paidAmountInvoice;
$discountInInvoice = ($discountInInvoice=="") ? 0 : $discountInInvoice;



$userDeetsId=$invoiceDeets['customerId'];
$userDeets=getRow($con,"select * from darlelJobber_users where id='$userDeetsId'");
$propertyDeetsId=$quoteDeets['propertyId'];
$propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyDeetsId'");
$propertyAddress=$propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country']." (Zip Code : ".$propertyDeets['zip_code'].")";  


?>
<html lang="en">
	<head>
	    <?require("./includes/views/head.php");?>
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <link href="includes/autocompletecss.css" rel="stylesheet" type="text/css"/>
        <style>
            .mobileView{
                display:none;
            }
            .computerView{
                display:block;
            }
            
            @media only screen and (max-width: 700px) {
                .mobileView{
                    display:block;
                }
                .computerView{
                    display:none;
                }
            }
        </style>
    </head>
	
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl">
								
								<form action="" method="post" enctype="multipart/form-data" id="quoteForm">
									<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
										<div class="row">
										    <div class="col-12">
        										<ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-bold mb-n2">
        											<?if($invoiceDeets['requestId']!="None" && $permission['view_requests']){?>
        											<li class="nav-item">
        												<a href="createRequest.php?entryId=<?echo $invoiceDeets['requestId']?>&view=1" class="nav-link text-active-primary pb-4 ">Request</a>
        											</li>
        											<?}?>
        											<?if($invoiceDeets['quoteId']!="None" && $permission['view_quotes']){?>
        											<li class="nav-item">
        												<a href="createQuote.php?entryId=<?echo $invoiceDeets['quoteId']?>&view=1" class="nav-link text-active-primary pb-4 ">Quote</a>
        											</li>
        											<?}?>
        											<?if($invoiceDeets['jobId']!="None" && $permission['view_jobs']){?>
        											<li class="nav-item">
        												<a href="createJob.php?entryId=<?echo $invoiceDeets['jobId']?>&view=1" class="nav-link text-active-primary pb-4 ">Job</a>
        											</li>
        											<?}?>
        											<li class="nav-item">
        												<a href="createInvoice.php?entryId=<?echo $invoiceId?>&view=1" class="nav-link text-active-primary pb-4 active">Invoice</a>
        											</li>
        											<?if($permission['view_client']){?>
        											<li class="nav-item">
        												<a href="view_client.php?id=<?echo $invoiceDeets['customerId']?>" class="nav-link text-active-primary pb-4 ">View Client</a>
        											</li>
        											<?}?>
        										</ul>
    										</div>
    									</div>
										<div class="tab-content">
											<div class="tab-pane fade active show" role="tab-panel">
												<div class="d-flex flex-column gap-7 gap-lg-10">
													<div class="card card-flush py-4" style="margin-bottom:100px;">
														<div class="card-header">
															<div class="card-title">
																<h2><?echo "# ".$invoiceDeets['invoice_number']?> Invoice For
											                        <a <?if($session_role!="Client"){?>href="view_client.php?id=<?echo $invoiceDeets['customerId']?>"<?}?>>
											                            <p style="display: inline;color: #73141d;" id="clientName">
											                                <?
											                                if($userDeets['showCompanyName']=="Yes")
                            								                    echo $userDeets['company_name']." (".$userDeets['first_name']." ".$userDeets['last_name'].")";
                                                                            else
										                                        echo $userDeets['first_name']." ".$userDeets['last_name'];
                                    									    ?>
											                            </p>
										                            </a>
											                    </h2>
											                </div>
											                <?if($session_role!="Client"){?>
															<div class="card-toolbar">
									                            <a class="btn btn-primary btn-sm" href="createInvoice.php?entryId=<?echo $invoiceId?>" >Edit Invoice</a>
										                    </div>
										                    <?}?>
														</div>
														<div class="card-body pt-0 pb-0">
															<div class="row">
															    <div class="col-sm-12 col-xs-12 col-md-6 col-xl-6">
    															    <div class="col-12">
    															        <h5>Title : <?echo $invoiceDeets['title']?></h5>
    															    </div>
    															    <div class="col-12">
    															        <h5>Address : <?echo $propertyAddress?></h5>
    															        <?//google map address button when invoice is viewed
    															        if($session_role!="Client"){
    														                $googleMapAddress=$propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country'];?>
    										                                <a target="_blank" class="btn btn-warning btn-sm mb-2" href="https://www.google.com/maps/search/?api=1&query=<?echo $googleMapAddress?>">View Address</a>
											                            <?}?>
            														</div>
            														<div class="col-12">
            														    <h5>Contact Details : </h5>
    															        <?
    													                $email=explode("*",$userDeets['email']);
                        											    $phone=explode("*",$userDeets['phone']);
                        											    
                        											    foreach($phone as $row)
                        											    {echo "<p>".$row."</p>";}
                        											    
    													                foreach($email as $row)
                        											    {echo "<p>".$row."</p>";}
    													                ?>
            														</div>
        														</div>
        														<div class="col-sm-12 col-xs-12 col-md-6 col-xl-6">
        														        <h3>Invoice Details</h3>
													                    <hr>
														                <h5>Issued Date : <?echo date('d M Y',$invoiceDeets['issued_date']);?></h5> 
														                <h5>Payment Due : <?echo date('d M Y',$invoiceDeets['payment_due']);?></h5> 
														            </div>
														        </div>
        													</div>
															
															
															<div class="row" style="margin-top: 15px;padding:10px;">
															    <h5 style="padding-left: 25px;">PRODUCT / SERVICE </h5>
            													<!--mobile view-->
            													<div class="table-responsive mobileView">
                                                                    <table class="table table-rounded border gy-7 gs-7">
                                                                        <tbody>
                                                                            <?
                                                                            $query="select * from darlelJobber_quote_details where quoteId='$quoteId' order by entryNo asc";
            													            $invoiceDeetsDetailed=getAll($con,$query);
                													        foreach($invoiceDeetsDetailed as $nrow){?>
                													        <tr <?if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No"){echo "style='display:none'";}?>>
                                                                                <td colspan="3" style="padding: 10;">
                                                                                    <b><?echo $nrow['service']?></b>
                                                                                    <p><?echo $nrow['description']?></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr class="text-center" 
                                                                            style="border-top: 1px solid #26181878;border-bottom: 1px solid #26181878;<?if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No"){echo "display:none";}?>">
                                                                                <td style="padding: 10;border-right: 1px solid #26181878; "><b>Qty.</b><p><?echo $nrow['qty']?></p></td>
                                                                                <td style="padding: 10;border-right: 1px solid #26181878; "><b>Unit Price</b><p> <?echo "$".$nrow['unit_price']?></p></td>
                                                                                <td style="padding: 10;">
                                                                                    <b>Total</b>
                                                                                    <p>
                                                                                        <?echo "$".$nrow['total'];
                                                                                        if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No")
                                                                                            echo "<br> <b class='notIncluded'>Not Included </b>";?>
                                                                                    </p>
                                                                                </td>
                                                                            </tr>
                                                                            <?}?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                
                                                                <!--computer view-->
                                                                <div class="table-responsive computerView">
                                                                    <table class="table table-rounded table-striped border gy-7 gs-7">
                                                                        <thead>
                                                                            <tr class="fw-bolder fs-6 text-gray-800">
                                                                                <th>PRODUCT / SERVICE</th>
                                                                                <th>Description</th>
                                                                                <th>QTY</th>
                                                                                <th>UNIT PRICE</th>
                                                                                <th>TOTAL</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?
            													            $query="select * from darlelJobber_quote_details where quoteId='$quoteId' order by entryNo asc";
            													            $invoiceDeetsDetailed=getAll($con,$query);
                													        foreach($invoiceDeetsDetailed as $nrow){?>
                													        
                                                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200"
                                                                            <?if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No"){echo "style='display:none'";}?>>
                                                                                <td><?echo $nrow['service']?></td>
                                                                                <td><?echo $nrow['description']?></td>
                                                                                <td><?echo $nrow['qty']?></td>
                                                                                <td><?echo $nrow['unit_price']?></td>
                                                                                <td>
                                                                                    <?echo $nrow['total'];
                                                                                    if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No")
                                                                                            echo "<br> <b class='notIncluded'>Not Included </b>";?>
                                                                                </td>
                                                                            </tr>
                                                                            <?}?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
														    <hr>
													        <div class="row" style="padding: 20px;">
													            <div class="col-xs-12 col-md-8">
													                <h3>Client Message : </h3>
													                <p><?echo $invoiceDeets['message'];?></p>
													            </div>
													            <div class="col-xs-12 col-md-4 text-right">
													                <div class="row" style="text-align: center;">
												                        <p>Subtotal($) : <?echo $invoiceDeets['subtotal']?></p><hr>
													                    <?if($invoiceDeets['discountType']=="Amount")
													                        $discountedAmount=$invoiceDeets['discount'];
												                        else if($invoiceDeets['discountType']=="Percentage")
													                        $discountedAmount=$invoiceDeets['subtotal']*($invoiceDeets['discount']/100);?>
													                    <p>Discount($) : <?echo round($discountedAmount,2)?></p><hr>
													                    <p>Paid Amount In Quote($) : <?echo $paidAmountQuote;?></p><hr>
													                    <p>Discount Availed In Quote($) : <?echo $discountInQuote;?></p><hr>
													                    <p>Total($) : <?echo $invoiceDeets['total']?></p><hr>
													                    <?if($paidAmountInvoice!=0){?>
													                    <p>Paid Amount ($) : <?echo $paidAmountInvoice?></p><hr>
													                    <?}?>
													                    <p>Required Balance Today ($) : <?echo $invoiceDeets['total']-$paidAmountInvoice?></p><hr>
													                    <p>Choose Balance Method : </p><hr>
													                    <p>
													                        <?
													                        $tempTotal=$invoiceDeets['total']-$paidAmountInvoice;
													                        $payWithACH = round($tempTotal-($tempTotal*(3/100)),2);
													                        if($quoteDeets['cashOnly']=="Yes")
													                            $payWithACH=$invoiceDeets['total']-$paidAmountInvoice;?>
												                            <a class="btn btn-success" style="padding-right: 10px;" href="payment.php?invoiceId=<?echo $invoiceId?>">
												                                Pay With ACH-ECheck ($) : <?echo $payWithACH?>
											                                </a>
												                        </p><hr>
													                    <p>
											                                <a class="btn btn-success <?if($quoteDeets['cashOnly']=="Yes"){echo "d-none";}?>" style="padding-right: 10px;" href="payment.php?invoiceId=<?echo $invoiceId?>">
											                                    Pay With Credit Card ($) : <?echo $invoiceDeets['total']-$paidAmountInvoice?>
										                                    </a>
												                        </p>
													                    <div class="col-12 mt-5">
													                        <table class="table table-rounded table-row-bordered border gs-7" style="text-align: center;width: 100%;">
													                            <thead>
													                                <tr><th>Deposits Uptil Now</th></tr>
													                            </thead>
													                            <tbody>
													                                <tr><td><?echo "Amount Paid In Quote = ".$paidAmountQuote." and Discount Availed = ".$discountInQuote?></td></tr>
    													                            <?$payedThroughCard=1;
    													                            $payments=getAll($con,"select * from darlelJobber_payments where invoiceId='$invoiceId' order by timeAdded desc");
													                                foreach($payments as $row){
													                                    if($invoiceDeets['paidStatus']=="Paid" &&  ( $row['method']!="Credit Card" && $row['method']!="card" ))
													                                            $payedThroughCard=0;
													                                        $string="Deposit of amount $".$row['amountPaid']." made on ".date("d M Y",$row['timeAdded']).
    													                                    " with Title = ".$row['title']." and method = ".$row['method']." 
    													                                    and discount availed = $".$row['discountAvailed'];?>
												                                    <tr><td><?echo $string?></td></tr>
													                                <?}
													                                if($invoiceDeets['paidStatus']=="Paid"){
    												                                    if($payedThroughCard || $quoteDeets['cashOnly']=="Yes")
    												                                        $displayRequiredDeposit = $invoiceDeets['total'];
    												                                    else{//other method should give 3% discount 
    											                                            $displayRequiredDeposit = $invoiceDeets['total']-($invoiceDeets['total']*(3/100));
    												                                        $displayRequiredDeposit=round($displayRequiredDeposit,2);
    												                                    }
												                                    $totalAmountRemaining=round($displayRequiredDeposit-$paymentDeetsInvoice['paidAmount'],2);?>
													                                <tr class="mt-4"><td><?echo "Required Deposit = ".round($displayRequiredDeposit,2)?></td></tr>
													                                <tr><td><?echo "Received Amount = ".round($paymentDeetsInvoice['paidAmount'],2);?></td></tr>
													                                <tr><td><?echo "Remaining Amount = ".round($totalAmountRemaining,2);?></td></tr>
													                                <?}?>
													                            </tbody>
													                        </table>
													                    </div>
													                </div>
													            </div>
													        </div>
													</div>
												</div>
											</div>
										</div>
									</div>
								<div></div>
							</form>
							</div>
						</div>
					</div>
					
					<div class="footer py-4 d-flex flex-lg-column" id="kt_footer" style="position: fixed;bottom: 0;width: 100%;">
					    <div class="row w-100">
					        <?if($invoiceDeets['paidStatus']!="Paid" && $session_role=="Client" ){?>
    					    <div class="col-12 text-center" style="padding-left: 30px;">
    					        <a href="payment.php?invoiceId=<?echo $invoiceId?>" class="btn btn-success btn-sm mr-10 mb-2 w-100" style="width: 38% !important;">Pay Invoice</a>
    			            </div>
                			<?}?>
                			<div class="col-12">
        					    <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
        							<div class="text-dark order-2 order-md-1">
        								<span class="text-muted fw-bold me-1">2023©</span>
        								<a href="<?echo $projectUrl?>" target="_blank" class="text-gray-800 text-hover-primary"><?echo $projectName?></a>
        							</div>
        						</div>
    						</div>
						</div>
					</div>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
			<script>var hostUrl = "assets/";</script>
    		<script src="assets/js/scripts.bundle.js"></script>
    		<script>
		    $(document).ready(function(){
                $('form').submit(function(event) {
                  $(this).find(':submit').css('pointer-events', 'none');
                });
            });
    		</script>
        </div>
	</body>
</script>
</html>
