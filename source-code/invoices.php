<?
require("./global.php");
if($logged==0 || (!$permission['view_invoices']))
    header("Location:./index.php");

$discountPerc=0;
$users=getAll($con,"select * from darlelJobber_users where role='Client'");
foreach($users as $row)
{$idToInfo[$row['id']]=$row;}

if(isset($_GET['invoiceId']) && ($_GET['m']=="p_success")){
    $invoiceId=clear($_GET['invoiceId']);
    
    $discountKey=clear($_GET['discountKey']);
    $discountReceived=clear($_GET['discount']);
    $generatedDiscountKey=md5(md5($discountReceived."discount"));
    
    $receivedKey=clear($_GET['key']);
    $amountReceived=clear($_GET['amount']);
    $amountKey=clear($_GET['amountKey']);
    $generatedAmountKey=md5(md5($amountReceived."amount"));

    $generatedKey=md5(md5($invoiceId."payment"));
    if($receivedKey!=$generatedKey || $generatedAmountKey!=$amountKey || $generatedDiscountKey!=$discountKey){
        header("Location:?m=Invalid Payment Key . Contact the admin");
        exit();
    }
    
    $method=clear($_GET['method']);
    $time=time();
    $query="update darlelJobber_invoices set paidStatus='Paid',paidDate='$time' where id='$invoiceId'";
    runQuery($query);
    
    $id=generateRandomString();
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where id='$invoiceId'");
    $customerId=$invoiceDeets['customerId'];
    $timeAdded=time();
    $amountPaid=$invoiceDeets['total'];
    $discountAvailed=0;
    
    if($method=="us_bank_account"){
        $discountPerc=3;
        $discount=round($discountPerc/100,2);
        $discountAvailed=$discountReceived;
        $amountPaid=$amountPaid-$discountAvailed;
        $method="E Check";
        if(isset($_GET['cashOnly'])){
            $discountPerc=0;
            $discountAvailed=0;
            $amountPaid=$invoiceDeets['total'];
        }
    }
    $query="insert into darlelJobber_payments set id='$id',discountPerc='$discountPerc',transactionDate='$timeAdded',customerId='$customerId',invoiceId='$invoiceId',title='Invoice paid',
    method='$method',amountPaid='$amountReceived',discountAvailed='$discountAvailed',addedBy='Machine',timeAdded='$timeAdded'";
    runQuery($query);
    
    updateQuote($invoiceId);
    header("Location:?m=p_success");
}

if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    $query="delete from darlelJobber_invoices where id='$id'";
    runQuery($query);
    
    $query="delete from darlelJobber_invoice_details where invoiceId='$id'";
    runQuery($query);
    
    header("Location:./invoices.php?m=Invoice Deleted");
}

$filter=clear($_GET['filter']);
if((!isset($_GET['filter']) || ($_GET['filter']=="All"))){
    $invoiceQuery="select * from darlelJobber_invoices order by invoice_number asc";
    if($session_role=="Client")
        $invoiceQuery="select * from darlelJobber_invoices where customerId='$session_id' order by invoice_number asc";
}
else if($filter=="Draft")
    $invoiceQuery="select * from darlelJobber_invoices where sendStatus='Not Send' order by timeAdded desc";
else if($filter=="Overdue")
    $invoiceQuery="select * from darlelJobber_invoices where paidStatus='Pending' and expiryStatus='Expired' order by timeAdded desc";
else if($filter=="Paid")
    $invoiceQuery="select * from darlelJobber_invoices where paidStatus='Paid' order by timeAdded desc";
else if($filter=="Not Paid")
    $invoiceQuery="select * from darlelJobber_invoices where paidStatus='Pending' order by timeAdded desc";
else if($filter=="Sent To Client")
    $invoiceQuery="select * from darlelJobber_invoices where sendStatus='Sent' order by timeAdded desc";
?>
<html lang="en">
	<head>
		<?require("./includes/views/head.php");?>
		<link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
    </head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							
							<button type="button" id="kt_docs_sweetalert_basic" class="btn btn-primary" hidden>Toggle SweetAlert</button>
							
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    

                                <?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-<?if($m=="p_failed"){echo "danger";}else{echo "success";}?> d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <?if ($m=="p_failed"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">The payment procedure was not completed. You can pay again anytime</h4>
                                        <?}else if ($_GET['m']=="p_success"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">The invoice has been paid successfully . Thank You </h4>
                                        <?}else{?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                        <?}?>
                                    </div>
                                </div>
                                <?}?>

                                <div class="card card-flush" style="margin-bottom: 40px;">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<span class="svg-icon svg-icon-1 position-absolute ms-4">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
													</svg>
												</span>
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Invoices " />
											</div>
										</div>
										<div class="card-toolbar">
										    <?if($session_role!="Client"){
										       $option=clear($_GET['filter']);
										       $filters=array("All","Draft","Paid","Not Paid","Sent To Client","Overdue");?>
										        <select class="btn btn-primary btn-sm" style="margin-right: 10px;" onchange="location = this.value;">
										            <?foreach($filters as $row){?>
										            <option <?if($row==$option){echo "selected";}?> value="invoices.php?filter=<?echo $row?>"><?echo $row?></option>
										            <?}?>
										        </select>
										    <?}?>
										</div>
									</div>
									<div class="card-body pt-0 mobile_view_padding">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gs-7 text-center" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
													<th>Subject</th>
													<?if($session_role!="Client"){?>
													<th >Client</th>
													<?}?>
													<th class="mobile_view">Issued Date</th>
													<th class="mobile_view">Deadline</th>
													<th class="mobile_view">Time Remaining</th>
													<th class="mobile_view">Expiry Status</th>
													<th >Total</th>
													<th>Paid Status</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?
											    $invoices=getAll($con,$invoiceQuery);
											    foreach($invoices as $row){
										        
										        $invoiceExpired=0;
										        $invoiceId=$row['id'];
											    if(time() > $row['finishTime']){
											        runQuery("update darlelJobber_invoices set expiryStatus='Expired' where id='$invoiceId' ");
											        $row['expiryStatus']="Expired";
											        $invoiceExpired=1;
											    }
											    ?>
											    <tr >
											        <td><a href="./createInvoice.php?entryId=<?echo $row['id']?>&view=1"><?echo "#".$row['invoice_number']." ".$row['subject']?></a></td>
    											    <?if($session_role!="Client"){?>
													<td>
											            <a href="./view_client.php?id=<?echo $row['customerId']?>">
										                    <?if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
									                            echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
											                else   
											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
    											        </a>
									                </td>
									                <?}?>
    											    <td class="mobile_view"><?echo date("d M y",$row['issued_date'])?></td>
    											    <td class="mobile_view"><?
    											        if($row['timePeriodStatus']!="stop")
    											            echo date("d M y",$row['finishTime']);
											            else 
    											            echo "Timer Stopped (Ticket Issued)";?>
										            </td>
    											    <td class="mobile_view">
    											        <?if($row['timePeriodStatus']!="stop" && !$invoiceExpired){
											                $timeRemaining=time()-$row['finishTime'];
        											        echo secondsToTime($timeRemaining);
        											    }
        											    else if($invoiceExpired)
							                                echo "Deadline Passed";
    											        else
        											        echo "Timer Stopped (Ticket Issued)";?>
    											    </td>
    											    <td class="mobile_view"><?echo $row['expiryStatus']?></td>
    											    <td><?echo $row['total']?></td>
    											    <td><a class="badge badge-<?if($row['paidStatus']=="Paid"){echo "success";}else{echo "warning";}?> "><?echo $row['paidStatus']?></a></td>
													
													<?
													//checking if there is required deposit and whether it has been paid or not 
													$quoteId=$row['quoteId'];
													$showError=0;
													if($quoteId!=""){
													    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
													    if($quoteDeets['required_deposit']!='0' && $quoteDeets['paidStatus']=="Pending")
													        $showError=1;
													}
													
													?>
													
													<td>
													    <div class="btn-group">
													    <?if($showError){?>
													    <a style="white-space: pre;" class="btn btn-danger btn-sm mb-5">UnPaid Required Deposit</a>
													    <br>
												        <?}?>
												        <?if(($session_role=="Client") && $row['paidStatus']!="Paid" ){?>
												        <a href="./payment.php?invoiceId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Pay</a>
    									                <?}?>
											            <?if($permission['view_invoices']){?>
    									                <a href="./createInvoice.php?entryId=<?echo $row['id']?>&view=1" class="btn btn-primary btn-sm">View</a>
    									                <?}?>
											            <?if($permission['edit_invoices']){?>
    									                <a href="./createInvoice.php?entryId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Edit</a>
    									                <?}?>
											            <?if($permission['delete_invoices']){?>
    									                <!--<a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-record=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>
    													--><?}?>
    													</div>
												    </td>
											    </tr>
											    <?}?>
											</tbody>
											
										</table>
									    </div>	
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
		<script>
	    const button = document.getElementById('kt_docs_sweetalert_basic');
        button.addEventListener('click', e =>{
            e.preventDefault();
        
            Swal.fire({
                text: "Your invoice amount has been received successfully ",
                icon: "success",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-success"
                }
            });
        });
        <?if($_GET['m']=="p_success"){?>
        $( document ).ready(function() {
            $("#kt_docs_sweetalert_basic")[0].click();
        });
        <?}?>
		</script>
	</body>
</html>