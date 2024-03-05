<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

$_SESSION['oldQuotes']=1;
$timeAdded=time();
if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    runQuery("delete from darlelJobber_quotes where id='$id'");
    runQuery("delete from darlelJobber_requests where quoteId='$id'");
    runQuery("delete from darlelJobber_jobs where quoteId='$id'");
    runQuery("delete from darlelJobber_invoices where quoteId='$id'");
    runQuery("delete from darlelJobber_quote_details where quoteId='$id'");
    
    header("Location:?m=Quote has bee deleted successfully");
}
$users=getAll($con,"select * from darlelJobber_users where role='Client'");
foreach($users as $row){$idToInfo[$row['id']]=$row;}

$idToProperty=array();
$properties=getAll($con,"select * from darlelJobber_properties");
foreach($properties as $row)
{ $idToProperty[$row['id']]=$row;}


//when the client has paid through stripe 
if(isset($_GET['quoteId']) && ($_GET['m']=="p_success")){
    $quoteId=clear($_GET['quoteId']);
    $receivedKey=clear($_GET['key']);
    
    $amountReceived=clear($_GET['amount']);
    $discountReceived=clear($_GET['discount']);
    $amountKey=clear($_GET['amountKey']);
    $discountKey=clear($_GET['discountKey']);
    $generatedAmountKey=md5(md5($amountReceived."amount"));
    $generatedKey=md5(md5($quoteId."payment"));
    $generatedDiscountKey=md5(md5($discountReceived."discount"));
    
    if($receivedKey!=$generatedKey || $generatedAmountKey!=$amountKey || $generatedDiscountKey!=$discountKey){
        header("Location:?m=Invalid Payment Key . Contact the admin");
        exit();
    }    
    $method=clear($_GET['method']);
    $time=time();
    
    
    $id=random();
    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
    $customerId=$quoteDeets['customerId'];
    $quoteId=$quoteDeets['id'];
    
    $timeAdded=time();
    $discountAvailed=0;
    $discountPerc=0;
    if($method=="us_bank_account"){
        $amount=$amountReceived;
        $discountPerc=3;
        $discountAvailed=$discountReceived;
        $method="E Check";
        if(isset($_GET['cashOnly'])){
            $discountPerc=0;
            $discountAvailed=0;
        }
        $canPayThroughCard="No";
    }
    if($method==""){
        $method="Credit Card";
        $canPayThroughCard="Yes";
    }
    else if($method=="card"){
        $canPayThroughCard="Yes";
    }
    
    $amountReceived=round($amountReceived,2);
    $discountAvailed=round($discountAvailed,2);
    
    
    $query="insert into darlelJobber_payments set id='$id',customerId='$customerId',discountPerc='$discountPerc',transactionDate='$timeAdded',
    quoteId='$quoteId',title='Quote required deposit paid',method='$method',amountPaid='$amountReceived',discountAvailed='$discountAvailed',addedBy='Machine',timeAdded='$timeAdded'";
    runQuery($query);
    
    
    $query="update darlelJobber_quotes set canPayThroughCard='$canPayThroughCard',paidStatus='Paid',paidDate='$time',paidAmount=paidAmount+$amountReceived where id='$quoteId'";
    runQuery($query);
    
    updateQuote($quoteId);
    header("Location:?m=p_success");
}


if((!isset($_GET['filter']) || ($_GET['filter']=="All"))){
    $queryQuotes="select * from darlelJobber_quotes order by timeAdded desc";
    if($session_role=="Client")
        $queryQuotes="select * from darlelJobber_quotes where customerId='$session_id' and sendStatus='Sent' order by timeAdded desc";
}
else{
    $filter=clear($_GET['filter']);
    if($filter=="Draft")//sentDate empty which means that the quote has not been sent yet
        $queryQuotes="select * from darlelJobber_quotes where sendStatus='Not Sent' && convertStatus!='Converted' && approveStatus='In Progress' order by timeAdded desc";
    else if($filter=="Awaiting Response")
        $queryQuotes="select * from darlelJobber_quotes where sendStatus='Sent' && approveStatus='In Progress' order by timeAdded desc";
    else if($filter=="Changes Requested")
        $queryQuotes="select * from darlelJobber_quotes where approveStatus='Changes Requested' order by timeAdded desc";
    else if($filter=="Approved")
        //$queryQuotes="select * from darlelJobber_quotes where sendStatus='Sent' && approveStatus='Approved' order by timeAdded desc";
        $queryQuotes="select q.* from darlelJobber_quotes q inner join darlelJobber_formC c on q.id=c.quoteId where q.sendStatus='Sent' && (q.approveStatus='Approved' or c.clientSign!='') order by timeAdded desc";
    else if($filter=="Converted")
        $queryQuotes="select * from darlelJobber_quotes where sendStatus='Sent' && convertStatus='Converted' order by timeAdded desc";
    else if($filter=="Requiring Deposit")
        $queryQuotes="select * from darlelJobber_quotes where paidStatus!='Paid' && complete_payment='No' order by timeAdded desc";
    else if($filter=="Approved But Not Paid")
        $queryQuotes="select * from darlelJobber_quotes where paidStatus!='Paid' && approveStatus='Approved' order by timeAdded desc";
    else if($filter=="Paid With Cash")
        $queryQuotes="select * from darlelJobber_quotes where paidWithCash='Yes' order by timeAdded desc";
    else if($filter=="Paid Without Cash")
        $queryQuotes="select * from darlelJobber_quotes where paidWithCash='No' and paidStatus='Paid' order by timeAdded desc";
    else if($filter=="Closed Lost")
        $queryQuotes="select * from darlelJobber_quotes where approveStatus='Rejected' order by timeAdded desc";
}

if(isset($_GET['override'])){
    $quoteId=clear($_GET['quoteId']);
    $query="update darlelJobber_quotes set formStatus='Submitted' where id='$quoteId'";
    runQuery($query);
    header("Location:?m=Forms have been overriden . Now the quote can be converted to a job ");
}

//creating similar quote
if(isset($_GET['similarQuote'])){
    $similarQuoteId=clear($_GET['similarQuote']);
    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$similarQuoteId'");
    $estimatorId=$quoteDeets['estimatorId'];
    $tieredPricing=$quoteDeets['tieredPricing'];
    $required_811=$quoteDeets['required_811'];
    $complete_payment=$quoteDeets['complete_payment'];
    $required_deposit=$quoteDeets['required_deposit'];
    $customerId=$quoteDeets['customerId'];
    $title=$quoteDeets['title'];
    $propertyId=$quoteDeets['propertyId'];
    $message=$quoteDeets['message'];
    $requiredDepositType=$quoteDeets['requiredDepositType'];
    $quote_number=getRow($con,"select max(quote_number) as quoteNumber from darlelJobber_quotes")['quoteNumber']+1;

    
    $random=random();
    $query="insert into darlelJobber_quotes set id='$random',estimatorId='$estimatorId',tieredPricing='$tieredPricing',required_811='$required_811',complete_payment='$complete_payment',
    required_deposit='$required_deposit',quote_number='$quote_number',customerId='$customerId',title='$title',subtotal='0',discount='0',
    propertyId='$propertyId',message='$message',requiredDepositAmount='0',commissionTier='$tieredPricing',
    timeAdded='$timeAdded',total='0',addedBy='$session_id',requiredDepositType='$requiredDepositType'";
    runQuery($query);

    $quoteId=$random;
    $random=random();
    $query="insert into darlelJobber_quote_details set id='$random',quoteId='$quoteId',service='Labor Fees',qty='1',unit_price='250',total='250',type='Labor Fees'";
    runQuery($query);
    
    //creating all the forms for this quote as soon as the quote is created 
    $forms=["A","B","C","D"];
    foreach($forms as $row){
        $random=random();
        $query="insert into darlelJobber_form$row set id='$random',quoteId='$quoteId',clientId='$customerId',timeAdded='$timeAdded'";
        runQuery($query);
    }
    $sitePlan=getAll($con,"select * from darlelJobber_site_plans where for_id='$similarQuoteId'");
    foreach($sitePlan as $nrow){
        $oldSitePlanId=$nrow['id'];
        
        $fileName = $nrow['image']; 
        $randomName=random();
        $destinationFileName="sitePlan_".$randomName.".png";
	    copy("uploads/$fileName", "uploads/$destinationFileName");
        
        $sitePlanId=random();
        $query="insert into darlelJobber_site_plans set id='$sitePlanId',for_id='$quoteId',image='$destinationFileName',timeAdded='$timeAdded'";
	    runQuery($query);
	    
	    $sitePlanDeets=getAll($con,"select * from darlelJobber_site_plan_details where planId='$oldSitePlanId'");
        foreach($sitePlanDeets as $row){
            $qty=$row['qty'];
            $service=clear($row['service']);
            $top_pos=$row['top_pos'];
            $left_pos=$row['left_pos'];
            $type=$row['type'];
            $optionalType=$row['optionalType'];
            
            $random=random();
            $query="insert into darlelJobber_site_plan_details set id='$random',optionalType='$optionalType',planId='$sitePlanId',quoteId='$quoteId',qty='$qty',type='$type',
            service='$service',top_pos='$top_pos',left_pos='$left_pos',addedBy='$session_id',timeAdded='$timeAdded'";
            runQuery($query);
        }
        
    }
    updateQuote($quoteId);
    header("Location:./createQuote.php?entryId=$quoteId");
}

?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							
							
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    <button type="button" id="kt_docs_sweetalert_basic" class="btn btn-primary" hidden>Toggle SweetAlert</button>
								<?if(isset($_GET['m'])){ $m=$_GET['m'];?>
                                <div class="alert alert-dismissible bg-<?if($m=="p_failed"){echo "warning";}else{echo "success";}?> d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <?if ($m=="p_failed"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">The payment procedure was not completed. You can pay again anytime </h4>
                                        <?}else if ($_GET['m']=="p_success"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">The quote required deposit has been paid successfully . Thank You </h4>
                                        <?}else{?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                        <?}?>
                                    </div>
                                </div>
                                <?}?>
							    <div class="card card-flush" style="margin-bottom: 40px !important;">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
                                                <span class="svg-icon svg-icon-1 position-absolute ms-4">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
													</svg>
												</span>
                                                <input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Quotes " />
											</div>
										</div>
										<div class="card-toolbar">
										    <?if($session_role!="Client"){
										       $option=clear($_GET['filter']);
										       $filters=array("All","Draft","Awaiting Response","Changes Requested","Approved","Converted","Approved But Not Paid","Paid With Cash",
										       "Paid Without Cash","Requiring Deposit","Closed Lost");?>
										        <select class="btn btn-primary btn-sm" style="margin-right: 10px;" onchange="location = this.value;">
										            <?foreach($filters as $row){?>
										            <option <?if($row==$option){echo "selected";}?> value="oldQuotes.php?filter=<?echo $row?>"><?echo $row?></option>
										            <?}?>
										        </select>
										    <a href="quotes.php" class="btn btn-primary btn-sm me-2">New Quote View</a>
										    <?}?>
										    
										    <?if($permission['add_quotes']){?>
											<a href="createQuote.php?new=1" class="btn btn-primary btn-sm ">New Quote</a>
										    <?}?>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gs-7 text-center" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
													<th>Title</th>
													<?if($session_role!='Client'){?>
													<th>Client</th>
													<th>Project Name</th>
													<th class="d-none" >Client Details</th>
													<th class="mobile_view">Created</th>
													<th>Changes Requested</th>
													<th>Status</th>
													<?}?>
													<th class="mobile_view">Property</th>
													<th>Total</th>
													<th class="mobile_view">Required Deposit</th>
													<th>Paid Status</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?
											    $quotes=getAll($con,$queryQuotes);
											    foreach($quotes as $row){
										        $allPhones="";
										        $allEmails="";
										        $phones=explode("*",$idToInfo[$row['customerId']]['phone']);
											    $emails=explode("*",$idToInfo[$row['customerId']]['email']);
											    $phones = implode(' ', $phones);
											    $emails = implode(' ', $emails);
											    ?>
    											    <tr>
    											        <td><a href="viewQuote.php?entryId=<?echo $row['id']?>"><?echo "#".$row['quote_number']." ".$row['title']?></a></td>
    											        <?if($session_role!='Client'){?>
													    <td >
													        <a href="./view_client.php?id=<?echo $row['customerId']?>">
													            <?if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
    									                            echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
    											                else   
    											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
    											            </a>
												        </td>
												        <td ><?echo $row['projectName']?></td>
    											        <td class="d-none"><?echo $emails."<br>".$phones?></td>
    											        <td class="mobile_view"><?echo date("M d, Y",$row['timeAdded'])?></td>
    											        <td><?echo $row['changes_description']?></td>
    											        <td>
											                <?
    											            $buttonClass="";
    											            if($row['approveStatus']=="Approved" && $row['paidStatus']!="Paid")
    											                $buttonClass="danger";
    											            else if($row['approveStatus']=="Approved")
											                    $buttonClass="success";
    											            else
    											                $buttonClass="warning";
    											            ?>
    											            <span class="badge badge-<?echo $buttonClass;?> mb-1"><?echo "Approve : ".$row['approveStatus']?></span>
												            <span class="badge badge-<?if($row['convertStatus']=="Converted"){echo "success";}else{echo "warning";}?>"><?echo $row['convertStatus']?></span>
												        </td>
													    <?}
    											        $address=$idToProperty[$row['propertyId']];
    											        $address=$address['street1']." ".$address['street2']." ".$address['state']." ".$address['city']." ".$address['country'];?>
    											        <td class="mobile_view"><?echo $address?></td>
                                                        <td><?echo $row['total']?></td>
    											        <td class="mobile_view"><?echo $row['requiredDepositAmount']." $"?></td>
                                                        <td>
                                                            <span class="badge badge-<?if($row['paidStatus']=="Paid"){echo "success";}else{echo "warning";}?>"><?echo $row['paidStatus']?></span>
                                                            
                                                            <?if($row['paidWithCash']=="Yes" && $row['paidStatus']!="Paid"){?>
                                                            <span class="badge badge-warning mb-1">Cash</span>
                                                            <?}?>
                                                        </td>
													    <td>
													        <div class="btn-group">
													        <?if($isAdmin && $row['formStatus']!='Submitted'){?>
													        <!--<a href="?quoteId=<?echo $row['id']?>&override=1" class="btn btn-danger btn-sm">Override</a>-->
													        <?}
        									                if($isAdmin){?>
        									                <!--<a style="white-space: pre;" href="?similarQuote=<?echo $row['id']?>" class="btn btn-warning btn-sm">Similar Quote</a>-->
        									                <?}?>
													        
													        <?if($session_role=="Client" && $row['paidStatus']!="Paid" && $row['approveStatus']=="Approved" && $row['complete_payment']=="No" && $row['paidWithCash']=="No" ){?>
    												        <a href="./payment.php?quoteId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Pay</a>
        									                <?}?>
                                                            <?if($permission['view_quotes']){?>
    										                <a href="viewQuote.php?entryId=<?echo $row['id']?>" class="btn btn-primary btn-sm">View</a>
    										                <?}?>
    										                <?if($permission['edit_quotes']){?>
    										                <a href="./createQuote.php?entryId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Edit</a>
    										                <?}?>
    										                <?if($permission['delete_quotes']){?>
        													<!--<a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-record=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>-->
        													<?}?>
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
	</body>
	<script>
	    const button = document.getElementById('kt_docs_sweetalert_basic');
        button.addEventListener('click', e =>{
            e.preventDefault();
        
            Swal.fire({
                text: "Your quote's deposit has been received successfully ",
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
</html>