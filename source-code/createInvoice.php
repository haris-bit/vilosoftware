<?
require("./global.php");

$edit=0;
$view=0;
$timeAdded=time();

$invoiceId=clear($_GET['entryId']);

if($logged==0 || (!$permission['edit_invoices']))
    header("Location:./index.php");
if(isset($_GET['view'])){
    header("Location:./viewInvoice.php?entryId=$invoiceId");
    $view=1;
}

$edit = (isset($_GET['entryId'])) ? 1 : 0;
$entryId=clear($_GET['entryId']);
$invoiceDeets=getRow($con,"select * from darlelJobber_invoices where id='$entryId'");
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

$totalAmountRemainingInvoice=round($invoiceDeets['total']-$paidAmountInvoice,2);
    													                                

$userDeetsId=$invoiceDeets['customerId'];
$userDeets=getRow($con,"select * from darlelJobber_users where id='$userDeetsId'");
$propertyDeetsId=$quoteDeets['propertyId'];
$propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyDeetsId'");

$services=getAll($con,"select * from darlelJobber_services where localUseId='None' || localUseId='$quoteId' order by timeAdded desc");
$allServices=array("");
foreach($services as $row){
    $index=$row['name']." SKU =".$row['sku'];
    $allServices[$index]=$row;
}

if(isset($_GET['labor_delete'])){
    $query="delete from darlelJobber_quote_details where quoteId='$quoteId' && service='Labor Fees'";
    runQuery($query);
    updateQuote($quoteId);
    header("Location:?entryId=$invoiceId");
}

if((isset($_POST['create_invoice'])) || isset($_POST['sendEmail']) || (isset($_POST['sendSms'])) ){
    $subject = clear($_POST['subject']);
    $invoice_number = clear($_POST['invoice_number']);
    $payment_due = strtotime(clear($_POST['payment_due']));
    $issued_date = strtotime(clear($_POST['issued_date']));
    $internal_notes = clear($_POST['internal_notes']);
    $subtotal=clear($_POST['subtotal']);
    $discount=clear($_POST['discount']);
    $total=clear($_POST['final_total']);
    $message=clear($_POST['message']);
    $discountType=clear($_POST['discountType']);
    
    $startTime=$issued_date+28800;
    $finishTime=$payment_due+28800;
    $timePeriodStatus="start";
    $expiryStatus="Valid";
    $timeAdded=time();
    
    $query="update darlelJobber_invoices set subject='$subject',discountType='$discountType',internal_notes='$internal_notes',invoice_number='$invoice_number',
    payment_due='$payment_due',issued_date='$issued_date',subtotal='$subtotal',discount='$discount',total='$total',message='$message',
    startTime='$startTime',finishTime='$finishTime',timePeriodStatus='$timePeriodStatus',expiryStatus='$expiryStatus' where id='$invoiceId'";
    runQuery($query);
    
    //updating the line items started
    runQuery("delete from darlelJobber_quote_details where quoteId='$quoteId' && service!='Labor Fees'");
    $service_inp=$_POST['service'];
    $qty_inp=$_POST['qty'];
    $unit_price_inp=$_POST['unit_price'];
    $total_inp=$_POST['total'];
    $description_inp=$_POST['description'];
    $helperFile_inp=$_POST['helperFile'];
    $type_inp=$_POST['type'];
    $serviceId_inp=$_POST['serviceId'];
    $optionalStatus_inp=$_POST['optionalStatus'];
    $optionalApproveStatus_inp=$_POST['optionalApproveStatus'];
    $target_dir = "./servicesImages/";
    
    for($i=0;$i<count($service_inp);$i++){
        
        $service=$service_inp[$i];
        $qty=$qty_inp[$i];
        $service=clear($service_inp[$i]);
        $unit_price=round($unit_price_inp[$i],2);
        $total=round($total_inp[$i], 2);
        $description=clear($description_inp[$i]);
        $helperFile=clear($helperFile_inp[$i]);
        $type=clear($type_inp[$i]);
        $optionalStatus=clear($optionalStatus_inp[$i]);
        $optionalApproveStatus=clear($optionalApproveStatus_inp[$i]);
        $serviceId=clear($serviceId_inp[$i]);
        
        if($type=="Labor Fees"){
            $unit_price=$unit_price_inp[$i];
            $qty=$qty_inp[$i];
            $total=round($qty*$unit_price,2);
            runQuery("update darlelJobber_quote_details set unit_price='$unit_price',qty='$qty',total='$total' where quoteId='$quoteId' and service='Labor Fees'");
            continue;
        }
        if(!empty( $_FILES[ 'images' ][ 'error' ][ $i ] ) )
            $image=$helperFile;
        else{
            $image = clear($_FILES[ 'images' ]['name'][$i]);
            $tmpName = $_FILES[ 'images' ][ 'tmp_name' ][ $i ];
            $target_file = $target_dir.$image;
            move_uploaded_file( $tmpName, $target_file ); 
        }
        $random=random();
        $query="insert into darlelJobber_quote_details set id='$random',serviceId='$serviceId',quoteId='$quoteId',optionalApproveStatus='$optionalApproveStatus',
        optionalStatus='$optionalStatus',service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description',image='$image'";
        runQuery($query);
    }
    updateQuote($quoteId);
    //updating the line items finished
    
    
    if(isset($_POST['create_invoice']))
        header("Location:?entryId=$invoiceId&m=Data Has Been Updated Successfully");
    else if(isset($_POST['sendEmail'])){
        header("Location:?entryId=$invoiceId&triggerModal=email");
    }
    else if(isset($_POST['sendSms'])){
        header("Location:?entryId=$invoiceId&triggerModal=sms");
    }
}

/*printing invoice section started*/
if(isset($_GET['print'])){
    $fileName="print_".random();
    $url=urlencode($g_website.'/printQuoteInvoice.php?invoiceId='.$invoiceId);
    printPage(urldecode($url),$fileName);
    
    //getting the number from the last entered quote pdf 
    $notesTitle=getRow($con,"select * from darlelJobber_notes where invoiceId='$invoiceId' && timeAdded < $timeAdded && title like '%Invoice Printable PDF%' order by timeAdded desc")['title'];
    if($notesTitle==null || $notesTitle=="Invoice Printable PDF")
        $notesTitle="Invoice Printable PDF_1";
    else if($notesTitle!=null){
        $number = preg_replace('/[^0-9]/', '', $notesTitle);
        $number++;
        $notesTitle="Invoice Printable PDF_$number";
    }
    
    $random=random();
    $query="insert into darlelJobber_notes set id='$random',title='$notesTitle',description='Printable PDF Of The Invoice',image='$fileName.pdf',
    addedBy='$session_id',timeAdded='$timeAdded',invoiceId='$invoiceId'";
    runQuery($query);
    
    header("Location:?entryId=$invoiceId&m=PDF has been saved in notes section successfully");
}
/*printing invoice section finished*/

if(isset($_GET['paid'])){
    runQuery("update darlelJobber_invoices set paidStatus='Paid' where id='$invoiceId'");
    header("Location:?entryId=$invoiceId&m=The invoice has been marked as paid successfully");
}

include("./emailsAndSms/sendingSms.php");
include("./emailsAndSms/sendingEmail.php");
include("./notes/notes.php");
include("./collectPayment/collectPaymentPhp.php");
?>
<html lang="en">
	<head>
		<?require("./includes/views/head.php");?>
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <link href="includes/autocompletecss.css" rel="stylesheet" type="text/css"/>
    </head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl">
							    <?if(isset($_GET['m'])){ $m=$_GET['m'];?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0"></span>
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                    </div>
                                </div>
                                <?}?>
								<form action="" method="post" enctype="multipart/form-data" id="invoiceForm">
									
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
													<div class="card card-flush py-4">
														<div class="card-header">
															<div class="card-title">
																<h2>
																    <?echo "#".$invoiceDeets['invoice_number']?> Invoice For
																    <a style="color: #73141d;" href="view_client.php?id=<?echo $invoiceDeets['customerId']?>">
																    <?
																    if($userDeets['showCompanyName']=="Yes")
                        								                echo $userDeets['company_name']." (".$userDeets['first_name']." ".$userDeets['last_name'].")";
                                                                    else
								                                        echo $userDeets['first_name']." ".$userDeets['last_name'];
                            									    ?>
                            									    </a>
											                    </h2>
															</div>
															<div class="card-toolbar">
										                        <?if($edit){?>
										                            <a href="?entryId=<?echo $invoiceId?>&print=1" style="margin-right:10px;" class="btn btn-success btn-sm">Print</a>
											                        <?if($invoiceDeets['paidStatus']=="Pending"){?>
                                                                    <a class="btn btn-warning btn-sm" style="margin-right:10px;" href="?entryId=<?echo $invoiceId?>&paid=1" 
                                                                    onclick="return confirm('Are you sure you want to mark this invoice as paid ? ')">Mark As Paid</a>
								                                    <?}?>
											                        <a class="btn btn-primary btn-sm" href="#"  data-bs-toggle="modal" data-bs-target="#collect_payment">Collect Payment</a>
										                        <?}?>
										                    </div>
														</div>
														<div class="card-body pt-0">
														    <div class="row">
														        <div class="col-md-7 col-xs-12 col-sm-12">
														            <div class="mb-10 fv-row fv-plugins-icon-container">
        																<label class="required form-label">Subject</label>
        																<input type="text" name="subject" class="form-control mb-2" value="<?echo $invoiceDeets['subject']?>" placeholder="Subject" >
        														    </div>
        														    <div class="row">
        														        <div class="col-6">
        														            <h3>Billing Address</h3>
        														            <p><?echo $propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country'];?></p>
        														        </div>
        														        <div class="col-6">
        														            <h3>Contact Details</h3>
        														            <?$userPhones=explode("*",$userDeets['phone']);
        														            foreach($userPhones as $row){?>
        														            <p><?echo $row?></p>
        														            <?}?>
        														            <?$userEmails=explode("*",$userDeets['email']);
        														            foreach($userEmails as $row){?>
        														            <p><?echo $row?></p>
        														            <?}?>
        														        </div>
        														    </div>
														        </div>
														        <div class="col-md-5 col-xs-12 col-sm-12">
														            <div class="row">
														                <div class="col-12">
														                    <h3>Invoice Details</h3>
														                    <hr>
														                </div>
														                <div class="col-12">
														                    <div class="row">
														                        <div class="col-6">
														                            <h5>Invoice Number</h5> 
														                        </div>
														                        <div class="col-6">
														                            <input type="number"  class="form-control" name="invoice_number" value ="<?echo $invoiceDeets['invoice_number']?>">
														                        </div>
														                    </div>
														                    <hr>
														                </div>
														                <div class="col-12">
														                    <div class="row">
														                        <div class="col-6">
														                            <h5>Issued Date</h5> 
														                        </div>
														                        <div class="col-6">
														                            <input required type="date"  class="form-control" name="issued_date" value ="<?php echo date('Y-m-d',$invoiceDeets['issued_date'])?>"></h5>
														                        </div>
														                    </div>
														                    <hr>
														                </div>
														                <div class="col-12">
														                    <div class="row mb-5">
														                        <div class="col-6">
														                            <h5>Payment Due</h5> 
														                        </div>
														                        <div class="col-6">
													                                <?if($invoiceDeets['payment_due']=="")
    														                        $invoiceDeets['payment_due']=time()+95040;?>
        														                    <input required type="date" class="form-control" name="payment_due" value ="<?php echo date('Y-m-d',$invoiceDeets['payment_due'])?>"></h5>
        														                </div>
														                    </div>
														                </div>
														                <hr>
														            </div>
														        </div>
														    </div>
														    
														    <!--line items table started-->
														    <div class="table-responsive">
													            <table class="table table-rounded table-striped border gy-7 gs-7">
                                                                    <thead class="text-center">
                                                                        <tr>
                                                                            <th >PRODUCT / SERVICE</th>
                                                                            <th >QTY</th>
                                                                            <th >Image</th>
                                                                            <th >UNIT PRICE</th>
                                                                            <th >TOTAL</th>
                                                                            <th>
                                                                                <a style="white-space: nowrap;" onclick="addRow()" class="btn btn-primary btn-sm">
                                                                                    <i style="font-size: x-large;" class="las la-plus"></i>
                                                                                    Line Item
                                                                                </a>
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="quote_section">
                                                                        <?$query="select * from darlelJobber_quote_details where quoteId='$quoteId' order by entryNo asc";
            													        $quoteDeetsDetailed=getAll($con,$query);
            													        foreach($quoteDeetsDetailed as $nrow){$random=random();?>
            													        <tr id="<?echo $random?>" class="<?echo $random?>" 
            													        <?if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No"){echo "style='display:none'";}?>>
                                                                            <td >
                                                                                <input name="serviceId[]" value="<?echo $nrow['serviceId']?>" hidden>
    	                                                                        <input name="optionalApproveStatus[]" value="<?echo $nrow['optionalApproveStatus']?>" hidden>
	                                                                            <input name="optionalStatus[]" value="<?echo $nrow['optionalStatus']?>" hidden>
	                                                                            <input onfocusout="fillPerUnitCost('<?echo $random?>')" type="text" class="form-control" value="<?echo htmlspecialchars($nrow['service'])?>"  name="service[]" style="width: 550;">
    														                    <textarea class="form-control" placeholder="Description" name="description[]" rows="3" style="width: 550;"><?echo htmlspecialchars($nrow['description'])?></textarea>
    														                </td>
    														                <td >
    														                    <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="<?echo $nrow['qty']?>" style="width: 150;">
            														            <?
            														            $text = ($nrow['optionalApproveStatus']=="Yes") ? "Included" : "Excluded";
            														            $color = ($nrow['optionalApproveStatus']=="Yes") ? "success" : "warning";
            														            
            														            if($nrow['optionalStatus']=="Yes")
    														                        echo "<a class='btn btn-$color btn-sm mt-10'>Optional Line Item $text</a>"?>
            														        </td>
            														        <td >
        														                <img style="height: 100px;width:180px;margin: 10px;" src="./servicesImages/<?echo $nrow['image']?>" alt="img" name="showImage[]">
            														            <a onclick="removeImage('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
        														                <input type="file" name="images[]" class="form-control" <?if($nrow['image']!=""){?>style="display:none;"<?}?> >
        														                <input type="text" name="helperFile[]" class="form-control" value="<?echo $nrow['image']?>" hidden>
        														                <input type="text" name="type[]" value="<?echo $nrow['type']?>" hidden>
    														                </td>
    														                <?if($session_role!="Installation Crew"){?>
                                                                            <td >
    														                    <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="<?echo $nrow['unit_price']?>" style="width: 200;">
            														            <?if($nrow['type']=="TD"){?>
            														                <a class="btn btn-light-warning btn-sm mt-2">Tear Down</a>
            														            <?}?>
    														                </td>
    														                <td >
														                        <input class="form-control" type="number" step="0.01" name="total[]" placeholder="Total" value="<?echo $nrow['unit_price']*$nrow['qty']?>" style="width: 200;" readonly>
    													                    </td>
    													                    <td class="text-center">
    													                        <?if($nrow['type']!="Labor Fees"){?>
            													                    <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
            													                <?}else{?>
            													                    <a href="?entryId=<?echo $invoiceId?>&labor_delete=1" style="padding: 20px;" class="btn btn-danger btn-sm"><i style="font-size: x-large;" class="las la-trash"></i></a>
            													                <?}?>
    													                    </td>
    													                    <?}?>
                                                                        </tr>
                                                                        <?}?>
													                </tbody>
                                                                </table>
														    </div>
                                                            <!--line items table finished-->
                                                            
    													    <hr style="margin-bottom:30px;">
														    <div class="row">
														        <div class="col-xs-12 col-md-6">
														            <label>Client Message</label>
													                <textarea rows="5" class="form-control" name="message" placeholder="Client Message"><?echo $invoiceDeets['message']?></textarea>
														        </div>
														        <div class="col-xs-12 col-md-6" style="text-align: right;">
													                <div class="row">
													                    <div class="col-12">
													                        <p>Subtotal($) :  
													                        <input  class="form-control" style="width: 60%;display: inline;" type="number" step="0.01" name="subtotal" value="<?echo $invoiceDeets['subtotal']?>" readonly></p>
													                    </div>
													                    <hr>
													                    <div class="col-12">
													                        <p>
													                            Discount :
													                            <?$invoiceDeets['discount'] = ($invoiceDeets['discount']=="")? 0 : $invoiceDeets['discount'];?>
													                            <input class="form-control" style="width: 40%;display: inline;" onkeyup="calculateFinalTotal()" type="number" step="0.01" name="discount" value="<?echo $invoiceDeets['discount']?>">
													                            <select onchange="calculateFinalTotal()" name="discountType" class="form-control" style="width: 20%;display: inline;">
													                                <option <?echo ($invoiceDeets['discountType']=="Percentage") ? "selected":"";?> value="Percentage">%</option>
													                                <option <?echo ($invoiceDeets['discountType']=="Amount") ? "selected":"";?> value="Amount">$</option>
													                            </select>
													                            <b id="discountedAmount"></b>
													                        </p>
													                    </div>
													                    <hr>
													                    <div class="col-12 mb-4 d-flex">
												                            <a class="btn btn-success btn-sm w-50" style="margin-right: 10px;"><?echo "Discount Availed In Quote ($) : ".$discountInQuote?></a>
													                        <a class="btn btn-success btn-sm w-50"><?echo "Paid Amount In Quote ($) : ".$paidAmountQuote?></a>
													                    </div>
													                    <hr>
													                    <?if($paidAmountInvoice!=0){?>
													                    <div class="col-12">
													                        <p>Paid Amount ($): 
													                        <input class="form-control" style="width: 40%;display: inline;" readonly type="number" value="<?echo $paidAmountInvoice?>"></p>
													                    </div>
													                    <hr>
													                    <?}?>
													                    <div class="col-12">
													                        <p>Total($): 
													                        <input class="form-control" style="width: 40%;display: inline;"  type="number" step="0.01" name="final_total" value="<?echo $invoiceDeets['total']?>" readonly>
													                        </p>
													                    </div>
													                    <hr>
													                    <div class="col-12 d-flex" style="text-align: right;margin-bottom: 10px;">
        													                <input id="updateInvoiceBtn" type="submit" class="btn btn-primary w-50" name="create_invoice" value="Update Invoice" style="margin-right: 10px;">
        												                    <button type="button" class="btn btn-warning w-50 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Save And</button>
                                                                            <ul class="dropdown-menu" style="width: 30%;">
            														            <li><input type="submit" class="btn btn-warning btn-sm w-100" name="sendEmail" value="Send Email"></li>    
        												                        <li><input type="submit" class="btn btn-warning btn-sm w-100" name="sendSms" value="Send Sms"></li>
                                                                            </ul>
                                                                            <a id="sendEmailBtn" data-bs-toggle="modal" data-bs-target="#emailModal" hidden>Send Email</a>
                                                                            <a id="sendSmsBtn" data-bs-toggle="modal" data-bs-target="#smsModal" hidden>Send SMS</a>
                                                                        </div>
                                                                        
                                                                        <hr>
													                    <div class="col-12">
													                        <div class="table-responsive">
                                                                                <table class="table table-rounded table-row-bordered border gs-7 text-center">
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
													                                    $totalAmountRemainingInvoice=round($displayRequiredDeposit-$paidAmountInvoice,2);?>
    													                                <tr class="mt-4"><td><?echo "Required Deposit = ".round($displayRequiredDeposit,2)?></td></tr>
    													                                <tr><td><?echo "Received Amount = ".round($paymentDeetsInvoice['paidAmount'],2);?></td></tr>
    													                                <tr><td><?echo "Remaining Amount = ".round($displayRequiredDeposit-$paymentDeetsInvoice['paidAmount'],2);?></td></tr>
    													                                <?}?>
    													                            </tbody>
                                    	                                        </table>
													                        </div>
													                    </div>
													                    
													                </div>
													            </div>
													        </div>
														    <hr>
													        
													        <!--notes section-->
											                <?include("./notes/notes_table.php");?>
												        </div>
    												</div>
    											</div>
    										</div>
    									</div>
    								<div>
    							</div>
    						</form>
    				    </div>
    				</div>
    	        </div>
					<?require("./includes/views/footer.php");?>
				</div>
			</div>
			
			<script>var hostUrl = "assets/";</script>
    		<script src="assets/js/scripts.bundle.js"></script>
        </div>
	</body>
	
	<script>
	    var required_deposit=<?echo $quoteDeets['requiredDepositAmount']?>;
        var quotePaidStatus="<?echo $quoteDeets['paidStatus']?>";
        var paidAmountQuote="<?echo $paidAmountQuote?>";
        var discountInQuote="<?echo $discountInQuote?>";
        
        
        $(document).ready(function(){
            
            document.addEventListener("keydown", function(event) {
                if (event.key === "Enter" && event.target.tagName !== "TEXTAREA") {
                    event.preventDefault();
                }
            });
            
            /*when invoice is submitted and sending options is selected started*/
	        <?if($_GET['triggerModal']=="email"){?>
    	        $("#sendEmailBtn")[0].click();
            <?}else if($_GET['triggerModal']=="sms"){?>
    	        $("#sendSmsBtn")[0].click();
            <?}?>
	        /*when invoice is submitted and sending options is selected finished*/
	        
	        $('form').submit(function(event) {
              $(this).find(':submit').css('pointer-events', 'none');
            });
            
            calculateFinalTotal();
	        $("input[name='service[]']").autocomplete({
              source: function(request, response) {
              var words = request.term.split(" ");
              var pattern = $.map(words, function(word) {
                return "(?=.*" + $.ui.autocomplete.escapeRegex(word) + ")";
              }).join("");
              var matcher = new RegExp(pattern, "i");
              var filteredTags = $.grep(availableTags, function(value) {
                value = value.label || value.value || value;
                return matcher.test(value.toLowerCase());
              });
              response(filteredTags);
            }
	        });
	        
	    });
	    function makeid(length) {
            var result           = '';
            var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for ( var i = 0; i < length; i++ ) {
              result += characters.charAt(Math.floor(Math.random() * 
         charactersLength));
           }
           return result;
        }
	    var allServices=<?echo json_encode($allServices);?>;
	    
	    function fillPerUnitCost(divId){
            var serviceId=$("."+divId+" input[name='service[]']").val()
            var unitPrice=allServices[serviceId]['price'];
            var filledUnitPrice=$("."+divId+" input[name='unit_price[]']").val();
            var description=allServices[serviceId]['description'];
            var image=allServices[serviceId]['image'];
            
            $("."+divId+" input[name='images[]']").hide();
            $("."+divId+" img[name='showImage[]']").attr("src", "./servicesImages/"+image);
            $("."+divId+" input[name='helperFile[]']").val(image);
            var helper=$("."+divId+" input[name='helperFile[]']").val();
            
            $("."+divId+" textarea[name='description[]']").val(description);
            if(filledUnitPrice=="" || filledUnitPrice==null || filledUnitPrice==0){
                $("."+divId+" input[name='unit_price[]']").val(unitPrice);
                $("."+divId+" input[name='qty[]']").val("0");
                $("."+divId+" input[name='total[]']").val("0");
            }
            
            calculateFinalTotal()
        }
        
    function calculateTotal(divId){
        var unit_price=$("."+divId+" input[name='unit_price[]']").val()
        var qty=$("."+divId+" input[name='qty[]']").val();
        var total=unit_price*qty;
        $("."+divId+" input[name='total[]']").val(total);
        calculateFinalTotal()
    }
    
    function calculateFinalTotal(){
        var totalPrice=0;
        $('input[name^="total"]').each( function() {
            var divId = $(this).closest('tr').attr('class');
            var optionalApproveStatus=$("."+divId+" input[name='optionalApproveStatus[]']").val();
            var optionalStatus=$("."+divId+" input[name='optionalStatus[]']").val();
            if(optionalApproveStatus=="Yes" || optionalStatus=="No" )
                totalPrice += parseFloat(this.value);
        });
        totalPrice = totalPrice.toFixed(2);
        $("input[name='subtotal']").val(totalPrice);
        
        var discount=$("input[name='discount']").val();
        var discountType=$("select[name='discountType']").val();
        var discountedAmount=0;
        
        if(discountType=="Percentage"){
            discountedAmount=totalPrice*(discount/100);
            discountedAmount = discountedAmount.toFixed(2);
        }
        else if(discountType=="Amount")
            discountedAmount=discount;
        totalPrice=totalPrice-discountedAmount;
        totalPrice=totalPrice-paidAmountQuote;
        totalPrice=totalPrice-discountInQuote;
        var paidAmountInvoice="<?echo $paidAmountInvoice?>";
        totalPrice=totalPrice-paidAmountInvoice;
        totalPrice = totalPrice.toFixed(2);
        $("input[name='final_total']").val(totalPrice);
        $('#discountedAmount').html(' = $'+discountedAmount)
    }
    
    var availableTags = [
    <?foreach($services as $row){?>
      `<?echo $row['name']." SKU =".$row['sku']?>`,
      <?}?>
    ];
    function addRow(){
        var id=makeid(5);
        var string=`
	    <tr id="`+id+`" class="`+id+`">
		    <td>
		        <input name="serviceId[]" value="None" hidden>
    	        <input name="optionalApproveStatus[]" value="No" hidden>
    	        <input name="optionalStatus[]" value="No" hidden>
                <input onfocusout="fillPerUnitCost('`+id+`')" type="text" class="form-control" name="service[]"  style="width: 550;">
                <textarea class="form-control" placeholder="Description" name="description[]" rows="3" style="width: 550;"></textarea>
            </td>   
            <td>
                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="0" style="width: 150;">
	        </td>
	        <td>
                <img style="height: 100px;width:180px;margin: 10px;" src="" alt="img" name="showImage[]">
	            <a onclick="removeImage('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
                <input type="file" name="images[]" class="form-control" >
                <input type="text" name="helperFile[]" class="form-control" hidden>
                <input type="text" name="type[]" hidden>
			</td>
			<td>
                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="0" style="width: 200;">
            </td>
	        <td>
                <input class="form-control" type="number" step="0.01" name="total[]" readonly placeholder="Total" value="0" style="width: 200;">
            </td>
	        <td class="text-center">
                <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
            </td>
       </tr>`;
		$('#quote_section').append(string);
		$("."+id+" input[name='service[]']").autocomplete({
            source: function(request, response) {
              var words = request.term.split(" ");
              var pattern = $.map(words, function(word) {
                return "(?=.*" + $.ui.autocomplete.escapeRegex(word) + ")";
              }).join("");
              var matcher = new RegExp(pattern, "i");
              var filteredTags = $.grep(availableTags, function(value) {
                value = value.label || value.value || value;
                return matcher.test(value.toLowerCase());
              });
              response(filteredTags);
            }
          }).autocomplete("widget").addClass("scrollable-autocomplete");
    }
    
    function removeRow(id){
        $('#'+id).remove();
        $("input[name='subtotal']").val("0");
        $("input[name='final_total']").val("0");
        calculateFinalTotal();
    }
    
    
    <?if(isset($_GET['updateInvoice'])){?>
        $(document).ready(function(){
            $("#updateInvoiceBtn")[0].click();
        });
    <?}?>
    
    </script>
	<?include("./notes/notes_js.php");
    include("./emailsAndSms/multipleSmsModal.php");
    include("./emailsAndSms/multipleEmailModal.php");
    include("./collectPayment/collectPayment.php");?>
</html>
