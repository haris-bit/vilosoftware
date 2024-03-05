<?require("./global.php");

$parameters = "$_SERVER[QUERY_STRING]";
if($logged==0)
    header("Location:./index.php");
$quoteId=clear($_GET['entryId']);
$timeAdded=time();
$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row)
{$idToInfo[$row['id']]=$row;}
$entryId=clear($_GET['entryId']);
$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$entryId'");

//getting contact details
$contactId=$quoteDeets['contactId'];
$contactDeets=getRow($con,"select * from darlelJobber_contacts where id='$contactId'");
$contactRows=getAll($con,"select * from darlelJobber_contact_details where contactId='$contactId'");

//get the amount that has already been paid to put in modal
$paidAmountDeets=getRow($con,"SELECT sum(amountPaid) as paidAmount,sum(amountPaid+discountAvailed) as paidAmountWithDiscount from darlelJobber_payments where quoteId='$quoteId'");
$paidAmount = ( $paidAmountDeets['paidAmount']=="" ) ? 0 : $paidAmountDeets['paidAmount'];
$paidAmountWithDiscount = ( $paidAmountDeets['paidAmountWithDiscount']=="" ) ? 0 : $paidAmountDeets['paidAmountWithDiscount'];

$totalAmountRemaining=$quoteDeets['requiredDepositAmount']-$paidAmountWithDiscount;

$userDeetsId=$quoteDeets['customerId'];
$userDeets=getRow($con,"select * from darlelJobber_users where id='$userDeetsId'");
$propertyDeetsId=$quoteDeets['propertyId'];
$propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyDeetsId'");
$zipCode = ($propertyDeets['zip_code']!="") ? " (Zip Code : ".$propertyDeets['zip_code'].")" : "";

$propertyAddress=$propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country'].$zipCode;  
$googleMapAddress=$propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country'];

if(isset($_POST['requestChange'])){
    $quoteId=clear($_GET['entryId']);
    $changes_description=clear($_POST['changes_description']);
    $query="update darlelJobber_quotes set changes_description='$changes_description',approveStatus='Changes Requested',changesRequestedStartTime='$timeAdded' where id='$quoteId'";
    runQuery($query);
    
    $random=random();
    $query="insert into darlelJobber_notes set id='$random',title='Changes Requested',description='$changes_description',addedBy='admin',timeAdded='$timeAdded',quoteId='$quoteId',lastUpdated='$timeAdded'";
    runQuery($query);
    
    
    //create a task and assigned member is the estimator
    /*$taskId=random();
    $completionDate=time()+86400;
    $query="insert into darlelJobber_tasks set id='$taskId',searchBy='Quote',quoteId='$quoteId',title='Change Requested On A Quote',description='$changes_description',label='Change Requested',
    completionDate='$completionDate',timeAdded='$timeAdded',addedBy='admin'";
    runQuery($query);
    
    //assigning to estimator of that quote
    $estimatorId=$quoteDeets['estimatorId'];
    $random=random();
    $userId=$estimatorId;
    $query="insert into darlelJobber_teams set id='$random',userId='$userId',taskId='$taskId',timeAdded='$timeAdded'";
    runQuery($query);
    
    //sending notification for the task
    $title="Assigned To a Task";
    $description="You have been assigned a task . Click To View";
    $url=$projectUrl."detailedTaskView.php?taskId=$taskId";
    setNotification($title,$description,$userId,$url);
    
    //estimator admin Id (orlando)
    $userId="ZHC3H6CXLN";//estimator admin
    $random=random();
    $query="insert into darlelJobber_teams set id='$random',userId='$userId',taskId='$taskId',timeAdded='$timeAdded'";
    runQuery($query);
    setNotification($title,$description,$userId,$url);*/
    
    header("Location:?entryId=$quoteId&view=1&m=Changes have been requested successfully");
}

$quoteQuery="select *,sum(qty) as 'totalQty' from darlelJobber_quote_details where quoteId='$entryId' group by service,type,optionalStatus order by optionalStatus desc";
//if count remains zero then no need to poke the client that have you checked optional line item
$countOptional=0;

//getting the estimator details (name,email,phone)
$estimatorId=$quoteDeets['estimatorId'];
if($estimatorId=="None"){   
    $requestId=$quoteDeets['requestId'];
    $query="select userId from darlelJobber_teams where requestId='$requestId' && userId in (select id from darlelJobber_users where role='Estimator')";
    $estimatorId=getRow($con,$query)['userId'];
}

?>
<html lang="en">
	<!--begin::Head-->
	<head>
	    <?require("./includes/views/head.php");?>
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <style>
            .highlighted-row{
                outline: 2px solid #fbfb32 !important;
            }
            .mobileView{
                display:none;
            }
            .computerView{
                display:block;
            }
            .displayPrice{
                <?if($quoteDeets['displayPricing']=="No"){echo "display:none;";}?>
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
        
        <?if($session_role=="Client"){?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.js"></script>
        <?}?>
        
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
							<!--begin::Container-->
							<div id="kt_content_container" class="container-xxl">
								
								
                                <?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0"></span>
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                    </div>
                                </div>
                                <?}?>
                                
								<form action="formC.php" method="get">
									<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
										<?if($session_role!="Client"){?>
										<div class="row">
										    <div class="col-12">
        										<ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-bold mb-n2">
        											<?if(($quoteDeets['requestId']!="None") && ($permission['view_requests'])){?>
        											<li class="nav-item">
        												<a href="createRequest.php?entryId=<?echo $quoteDeets['requestId']?>&view=1" class="nav-link text-active-primary pb-4 ">Request</a>
        											</li>
        											<?}?>
        											<li class="nav-item">
        												<a href="viewQuote.php?entryId=<?echo $quoteId?>&view=1" class="nav-link text-active-primary pb-4 active">Quote</a>
        											</li>
        											<?if(($quoteDeets['jobId']!="None") && ($permission['view_jobs'])){?>
        											<li class="nav-item">
        												<a href="createJob.php?entryId=<?echo $quoteDeets['jobId']?>&view=1" class="nav-link text-active-primary pb-4 ">Job</a>
        											</li>
        											<?}?>
        											<?if(($quoteDeets['invoiceId']!="None") && ($permission['view_invoices'])){?>
        											<li class="nav-item">
        												<a href="createInvoice.php?entryId=<?echo $quoteDeets['invoiceId']?>&view=1" class="nav-link text-active-primary pb-4 ">Invoice</a>
        											</li>
        											<?}?>
        											<?if($permission['view_client']){?>
        											<li class="nav-item">
        												<a href="view_client.php?id=<?echo $quoteDeets['customerId']?>" class="nav-link text-active-primary pb-4 ">View Client</a>
        											</li>
        											<?}?>
        										</ul>
    										</div>
    									</div>
    									<?}?>
										<div class="tab-content">
											<div class="tab-pane fade active show" role="tab-panel">
												<div class="d-flex flex-column gap-7 gap-lg-10">
													<div class="card card-flush py-4" style="margin-bottom:100px;">
														<div class="card-header">
															<div class="card-title">
																<h2><?echo "#".$quoteDeets['quote_number']?> Quote For
											                        <a <?if($session_role!="Client"){?>href="view_client.php?id=<?echo $quoteDeets['customerId']?>"<?}?>>
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
											                <?if($session_role!="Client" && $permission['edit_quotes']){?>
    														<div class="card-toolbar">
								                                <?
								                                $convertStatusColor =  ($quoteDeets['convertStatus']=="Converted") ? "success" : "warning";
									                            $paidStatusColor =  ($quoteDeets['paidStatus']=="Paid") ? "success" : "warning";
									                            ?>
											                    <a style="margin-right:10px;" class="btn btn-warning btn-sm"><?echo $quoteDeets['tieredPricing']?></a>
										                        <a style="margin-right:10px;" class="btn btn-<?echo $convertStatusColor?> btn-sm"><?echo $quoteDeets['convertStatus']?></a>
										                        <a style="margin-right:10px;" class="btn btn-<?echo $paidStatusColor?> btn-sm"><?echo "Payment ".$quoteDeets['paidStatus']?></a>
                                                                <a class="btn btn-primary btn-sm" href="createQuote.php?entryId=<?echo $quoteId?>" >Edit Quote</a>
										                    </div>
										                    <?}?>
														</div>
														<div class="card-body pt-0" style="padding: 10;">
															<div class="row mb-10 mt-3" style="padding-left: 20px;">
															    <div class="col-sm-12 col-xs-12 col-md-6 col-xl-6">
    															    <?if($session_role!="Client"){?>
    															        <h5>Title : <?echo $quoteDeets['title']?></h5>
    															    <?}if($quoteDeets['projectName']!=""){?>
    															        <h5>Project Name : <?echo $quoteDeets['projectName']?></h5>
    															    <?}?>
    															    <div>
															        <h5>Contact Details : <?echo $contactDeets['name']?> </h5>
															        <?foreach($contactRows as $row){
															            echo "<h5>".$row['type']." : ".$row['value']."</h5>";
														            }?>
    															        
													                </div>
															        <h5>Account Details : </h5>
    															        <?$email=explode("*",$userDeets['email']);
                        											    $phone=explode("*",$userDeets['phone']);
                        											    
                        											    foreach($phone as $row)
                        											    {echo "<h5>".$row."</h5>";}
                        											    
    													                foreach($email as $row)
                        											    {echo "<h5>".$row."</h5>";}?>
													                
													                <div>
											                            <h5>Address : <?echo $propertyAddress?></h5>
											                            <?if($session_role!="Client"){?>
											                            <a target="_blank" class="btn btn-warning btn-sm" href="https://www.google.com/maps/search/?api=1&query=<?echo $googleMapAddress?>">View Address</a>
											                            <?}?>
    														        </div>
													                </div>
													                    <div class="col-sm-12 col-xs-12 col-md-6 col-xl-6">
        														    <div>
            														    <h5>Fence Layout Sketch : </h5>
                													    <?
                            					                        $quoteId=clear($_GET['entryId']);
                            					                        $sitePlans=getAll($con,"select * from darlelJobber_site_plans where for_id='$quoteId'");
                            					                        foreach($sitePlans as $row){?>
                            					                            <a class="btn btn-primary" href="create_site_plan.php?edit=<?echo $row['id']?>&quoteId=<?echo $row['for_id']?>&view=1">Click To View</a>
            													        <?}?>
        													        </div>
        													        
        													        
    														        <div class="mt-5">
    														            <h5>Estimator Info : </h5>
    															        <h5>Name  : <?echo $idToInfo[$estimatorId]['name']?> </h5>
    															        <h5>
    															            Email : <?echo $idToInfo[$estimatorId]['email'];?>
    															        </h5>
    															        <h5>
    															            Phone : <?echo $idToInfo[$estimatorId]['phone'];?>
    															        </h5>
    														        </div>
															    </div>
        													        
    														    </div>
															</div>
															
															<div class="row" style="margin-top: 15px;">
															    <div class="d-flex mb-2">
    															    <h3 style="padding-left: 20px;padding-top: 10px;">PRODUCT / SERVICE </h3>
                												</div>
            													
            													<div class="table-responsive mobileView">
                                                                    <!--mobileView-->
                                                                    <table class="table table-rounded border gy-7 gs-7">
                                                                        <tbody>
                                                                            <?
            													            $quoteDeetsDetailed=getAll($con,$quoteQuery);
                													        foreach($quoteDeetsDetailed as $nrow){
                													            $random=random();
                													            $displayImage=$nrow['image'];
                													            $nrow['qty']=$nrow['totalQty'];
                													            if($nrow['optionalStatus']=="Yes")
                													                $countOptional++;
                													        ?>
                                                                            <tr style="<?if($nrow['optionalStatus']=="Yes"){echo "
                                                                                    border-top: 3px solid #fbfb32 !important;
                                                                                    border-right: 3px solid #fbfb32 !important;
                                                                                    border-left: 3px solid #fbfb32 !important;
                                                                                ";}?>">
                                                                                <td colspan="2" style="padding: 10;border-right:none!important;">
                                                                                    <b>
                                                                                        <?if($nrow['optionalStatus']=="Yes"){
                                                                                        $string = ($nrow['optionalApproveStatus']=="Yes") ? "checked" :"";
                                                                                        $string = ($quoteDeets['approveStatus']=="Approved") ? $string." disabled" : $string;?>
                                                                                        <input onclick="checkUncheck(this)" type="checkbox" name="approveOptional[]" value="<?echo $nrow['id']?>" class="form-check-input mt-1" style="margin-right: 5px;" <?echo $string?>/>
                                                                                        <?}
                                                                                        if($session_role=="Client"){
                                                                                            $skuPosition = strpos($nrow['service'], "SKU");
                                                                                            if ($skuPosition !== false) 
                                                                                                echo substr($nrow['service'], 0, strpos($nrow['service'], "SKU"));
                                                                                            else
                                                                                                echo $nrow['service'];
                                                                                        }
                                                                                        else
                                                                                            echo $nrow['service'];?>
                    														            <?if($nrow['type']=="TD"){?>
                    														                <br><a class="badge badge-success mt-2 mb-2">Tear Down</a>
                    														            <?}?>
                                                                                    </b>
                                                                                    <p><?echo $nrow['description']?></p>
                                                                                </td>
                                                                                <td style="padding: 10;text-align:right;border-left:none!important;">
                                                                                    <a <?if((file_exists("./servicesImages/$displayImage")) && ($displayImage!="")){?>
                                                                                    class="gallery2" href="servicesImages/<?echo $nrow['image']?>" <?}?>><img class="example-image" style="max-height: 4.3755rem;" src="./servicesImages/<?echo $nrow['image']?>" onerror="this.style.display='none'" /></a>
                                                                                </td>
                                                                            </tr>
                                                                            <tr class="text-center" 
                                                                            style="<?if($nrow['optionalStatus']=="Yes"){echo "
                                                                                    border-bottom: 3px solid #fbfb32 !important;
                                                                                    border-right: 3px solid #fbfb32 !important;
                                                                                    border-left: 3px solid #fbfb32 !important;
                                                                                ";}?>">
                                                                                <td  style="padding: 10;border-right:none!important; "><b>Qty.</b><p><?echo $nrow['qty']?></p></td>
                                                                                <td class="displayPrice" style="padding: 10;border-right:none!important;border-left:none!important; "><b>Unit Price</b><p> <?echo "$".$nrow['unit_price']?></p></td>
                                                                                <td  style="padding: 10;border-left:none!important;">
                                                                                    <b>Total</b>
                                                                                    <p>
                                                                                        <?echo "$".$nrow['total'];
                                                                                        if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No")
                                                                                            echo "<br> <b class='notIncluded'>Not Included </b>";
                                                                                        ?>
                                                                                    </p>
                                                                                </td>
                                                                            </tr>
                                                                            <?}?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                <div class="table-responsive computerView">
                                                                    <!--computer view-->
                                                                    <table class="table table-rounded border gy-7 gs-7 ">
                                                                        <thead>
                                                                            <tr class="fw-bolder fs-6 text-gray-800">
                                                                                <th>PRODUCT / SERVICE</th>
                                                                                <th>Description</th>
                                                                                <th>Image</th>
                                                                                <th >QTY</th>
                                                                                <th class="displayPrice">UNIT PRICE</th>
                                                                                <th >TOTAL</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?
            													            $quoteDeetsDetailed=getAll($con,$quoteQuery);
                													        foreach($quoteDeetsDetailed as $nrow){
                													            $random=random();
                													            $nrow['qty']=$nrow['totalQty'];
                													            $displayImage=$nrow['image'];
                													            if($nrow['optionalStatus']=="Yes")
                													                $countOptional++;
            													            ?>
                                                                            <tr id="<?echo $random?>" class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 <?if($nrow['optionalStatus']=="Yes"){echo "highlighted-row";}?>">
                                                                                <td>
                                                                                    <?if($nrow['optionalStatus']=="Yes"){
                                                                                    $string = ($nrow['optionalApproveStatus']=="Yes") ? "checked" :"";
                                                                                    $string = ($quoteDeets['approveStatus']=="Approved") ? $string." disabled" : $string;
                                                                                    ?>
                                                                                    <input onclick="checkUncheck(this)" type="checkbox" name="approveOptional[]" value="<?echo $nrow['id']?>" class="form-check-input" style="margin-right: 5px;" <?echo $string?> />
                                                                                    <?}
                                                                                    if($session_role=="Client"){
                                                                                        $skuPosition = strpos($nrow['service'], "SKU");
                                                                                        if ($skuPosition !== false) 
                                                                                            echo substr($nrow['service'], 0, strpos($nrow['service'], "SKU"));
                                                                                        else
                                                                                            echo $nrow['service'];
                                                                                    }
                                                                                    else
                                                                                        echo $nrow['service'];
                                                                                    ?>
                                                                                </td>
                                                                                <td><?echo $nrow['description']?></td>
                                                                                <td>
                                                                                    <a <?if((file_exists("./servicesImages/$displayImage")) && ($displayImage!="")){?>
                                                                                    class="gallery2" href="servicesImages/<?echo $nrow['image']?>" <?}?> ><img class="example-image" style="max-height: 4.3755rem;" src="./servicesImages/<?echo $nrow['image']?>" onerror="this.style.display='none'" /></a>
                                                                                </td>
                                                                                <td ><?echo $nrow['qty']?></td>
                                                                                <td class="displayPrice"><?echo $nrow['unit_price']?></td>
                                                                                <td >
                                                                                    <?
                                                                                    if($nrow['optionalStatus']=="Yes")
                                                                                        echo "<p class='total'>".$nrow['total']."</p>";
                                                                                    else
                                                                                        echo $nrow['total'];
                                                                                    
                                                                                    if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No")
                                                                                        echo "<b class='notIncluded'>Not Included </b>";
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                            <?}?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            
                                                                <script>
                                                                    document.addEventListener('DOMContentLoaded', function() {
                                                                        const gallery = new SimpleLightbox('.gallery2', {});
                                                                    });
                                                                </script>
                                                            </div>
														    
														    <!--sprinkler assurance section-->
													        <?if($quoteDeets['approveStatus']=="Approved" ){?>
													        <hr>
														    <div class="card shadow-sm" >
                                                                <div class="card-header">
                                                                    <h3 class="card-title">Sprinkler Assurance Plan</h3>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-rounded table-striped border gy-7 gs-7">
    													                    <thead>
    													                        <tr>
    													                            <th>Title</th>
    													                            <th>Status</th>
    													                            <th>Actions</th>
    													                        </tr>
    													                    </thead>
    													                    <tbody>
    													                        <?
    													                        $formD=getRow($con,"select * from darlelJobber_formD where quoteId='$quoteId'");?>
    													                        <tr>
    													                            <td>Sprinkler Assurance</td>
    													                            <td>
    													                                <?$color = ($formD['acceptStatus']=="Not Accepted")? "warning":"success"; ?>
    													                                <a class="btn btn-<?echo $color?> btn-sm"><?echo $formD['acceptStatus']?></a>
    													                            </td>
    													                            <td>
    													                                <div class="btn-group">
    													                                    <?if($formD['acceptStatus']!="Not Accepted"){?>
													                                        <a class="btn btn-primary btn-sm" href="./formD.php?quoteId=<?echo $quoteId?>&view=1">View</a>
    													                                    <?}?>
    													                                    
    													                                    <?if($formD['acceptStatus']=="Not Accepted"){?>
													                                        <a class="btn btn-warning btn-sm" href="./formD.php?quoteId=<?echo $quoteId?>">Click To Select Option</a>
    													                                    <!--<a onclick="return confirm('Are you sure you want to perform this action ? This action is irreversible')" class="btn btn-success btn-sm" href="?entryId=<?echo $quoteId?>&acceptStatus=Accepted">Accept Sprinkler Assurance</a>
    													                                    <a onclick="return confirm('Are you sure you want to perform this action ? This action is irreversible')" class="btn btn-danger btn-sm" href="?entryId=<?echo $quoteId?>&acceptStatus=Rejected">Reject Sprinkler Assurance</a>
    													                                    -->
    													                                    <?}?>
    													                                </div> 
        													                        </td>
										                                        </tr>   
    													                    </tbody>
												                        </table>
							                                        </div>
                                                                </div>
                                                            </div>
													        <?}?>
													        
													        
														    <hr>
													        <div class="row">
													            <div class="col-xs-12 col-md-6">
													                <h3>Client Message : </h3>
													                <p><?echo $quoteDeets['message'];?></p>
													            </div>
													            <div class="col-xs-12 col-md-6 text-right">
													                <div class="row" style="text-align: center;">
												                        <p>Subtotal($) : <b id="subtotal"></b></p><hr>
													                    <?if($quoteDeets['discount']!=0){?>
													                    <p>Discount($) : <b id="discount"></b>
												                        <?}?>
												                        <p>Total($) : <b id="total"></b></p><hr>
													                    <?if($paidAmountWithDiscount!=0){?>
													                    <p>Paid Amount ($) : <b><?echo $paidAmountWithDiscount?></b></p><hr>
													                    <?}?>
													                    <p>Required Deposit($) : <b id="requiredDeposit"></b></p><hr>
													                    <p>Choose Balance Method : </p><hr>
													                    <p>
												                            <input id="payWithACH" <?if($countOptional>0){?>  onclick="return confirm('Have you seen all the optional line items ? ')" <?}?> type="submit" 
                                                					        value="Pay With ACH-ECheck ($)"  class="btn btn-success" style="padding-right: 10px;">
                                                            			</p>
                                                            			<hr>
													                    <p>
												                            <input id="payWithCard" <?if($countOptional>0){?> onclick="return confirm('Have you seen all the optional line items ? ')" <?}?> type="submit" 
                                                					        value="Pay With Credit Card ($)" class="btn btn-success <?if($quoteDeets['cashOnly']=="Yes"){echo "d-none";}?>" style="padding-right: 10px;">
                                                            			</p>
                                                            			<?
													                    $payments=getAll($con,"select * from darlelJobber_payments where quoteId='$quoteId' order by timeAdded desc");
													                    if(count($payments)>0){?>
													                    <div class="col-12 mt-5">
													                        <table class="table table-rounded table-row-bordered border gs-7" style="text-align: center;width: 100%;">
													                            <thead>
													                                <tr><th><b>Deposits Uptil Now</b></th></tr>
													                            </thead>
													                            <tbody>
													                                <?$payedThroughCard=1;
													                                $totalDiscountAvailed=0;
													                                foreach($payments as $row){
													                                    $totalDiscountAvailed+=$row['discountAvailed'];
													                                    if($quoteDeets['paidStatus']=="Paid" &&  ( $row['method']!="Credit Card" && $row['method']!="card" ))
													                                        $payedThroughCard=0;
													                                    $string="Deposit of amount $".$row['amountPaid']." made on ".date("d M Y",$row['timeAdded']).
													                                    " with Title = ".$row['title']." and method = ".$row['method']." and discount availed = $".$row['discountAvailed'];?>
													                                <tr><td><?echo $string?></td></tr>
													                                <?}
													                                if($quoteDeets['paidStatus']=="Paid"){
													                                    if($payedThroughCard || $quoteDeets['cashOnly']=="Yes")
													                                        $displayRequiredDeposit = $quoteDeets['requiredDepositAmount'];
													                                    else{//other method should give 3% discount 
												                                            $displayRequiredDeposit = $quoteDeets['requiredDepositAmount']-($quoteDeets['requiredDepositAmount']*(3/100));
													                                        $displayRequiredDeposit=round($displayRequiredDeposit,2);
													                                    }
													                                    $totalAmountRemaining=round($displayRequiredDeposit-$paidAmount,2);?>
													                                <tr class="mt-4"><td><?echo "Required Deposit = ".$displayRequiredDeposit?></td></tr>
													                                <tr><td><?echo "Received Amount = ".round($paidAmount,2)?></td></tr>
													                                <tr><td><?echo "Remaining Amount = ".round($totalAmountRemaining,2);?></td></tr>
													                                <tr><td>
													                                        <?
													                                        $paymentMethod=($quoteDeets['cashOnly']=="Yes") ? "Cash/ECheck" : "Card";
													                                        echo "Project Final Balance With $paymentMethod = ".round(($quoteDeets['total']-$paidAmount-$totalDiscountAvailed),2);
												                                            ?>
										                                                </td>
									                                                </tr>
													                                <?if($quoteDeets['cashOnly']!="Yes"){?>
													                                <tr>
													                                    <td><?
												                                        $finalBalanceCash=($quoteDeets['total']-$paidAmount-$totalDiscountAvailed);
													                                    $finalBalanceCash=round($finalBalanceCash-($finalBalanceCash*(3/100)),2);
													                                    echo "Project Final Balance With Cash/ECheck = ".round($finalBalanceCash,2);?>
												                                        </td>
											                                        </tr>
													                                <?}}?>
												                                
												                                </tbody>
													                        </table>
													                    </div>
													                    <?}?>
													                </div>
													            </div>
													            
													            
													            
													            <!--forms section started-->
													            <div class="col-12">
													                <hr id="formsTable">
        														    <div class="card shadow-sm" >
                                                                        <div class="card-header">
                                                                            <h3 class="card-title">Project Details Please Review And Sign</h3>
                                                                        </div>
                                                                        <div class="card-body">
                                                                            <div class="table-responsive">
                                                                                <table class="table table-rounded table-striped border gy-7 gs-7">
            													                    <thead>
            													                        <tr>
            													                            <th>Title</th>
            													                            <th>Status</th>
            													                            <th>Actions</th>
            													                        </tr>
            													                    </thead>
            													                    <tbody>
            													                        <?
            													                        $formName=[
            													                            "A"=>"Estimate Checklist",
            													                            "B"=>"Fence Installation Option",
            													                            "C"=>"Installation Agreement",
            													                        ];
            													                        $forms = ($quoteDeets['approveStatus']=="In Progress") ? ["A","B"] : ["A","B","C"];
            													                        foreach($forms as $row){
            													                        $form=getRow($con,"select * from darlelJobber_form$row where quoteId='$quoteId'");?>
            													                        <tr>
            													                            <td><?echo $formName[$row]?></td>
            													                            <td>
            													                                <?$color = ($form['submissionStatus']=="Not Submitted")? "warning":"success"; ?>
            													                                <a class="btn btn-<?echo $color?> btn-sm"><?echo $form['submissionStatus']?></a>
            													                            </td>
            													                            <td>
            													                                <div class="btn-group">
        													                                        <?if($form['submissionStatus']!="Not Submitted" || $session_role!="Client"){?>
        													                                        <a class="btn btn-success btn-sm" href="./form<?echo $row?>.php?quoteId=<?echo $quoteId?>&view=1">View</a>
            													                                    <?}?>
            													                                    
            													                                    <?if(($session_role!="Client")||($session_role=="Client" && $form['submissionStatus']=="Not Submitted")){?>
            													                                    <a class="btn btn-warning btn-sm" href="./form<?echo $row?>.php?quoteId=<?echo $quoteId?>">Fill Form</a>
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
        														<!--forms section finished-->
    												            
    												            
    												            <?if($session_role!="Client"){?>
    												            <!--logs,notes and reminders section started-->
												                <div class="col-12 mt-3">
                                                                    <div class="card shadow-sm">
                                                                        <div class="card-body pt-0">
                                                                            <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6 mt-10">
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center active" data-bs-toggle="tab" href="#callLogs" style="color: #73141d;" >Call Log</a>
                                                                                </li>
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center" data-bs-toggle="tab" href="#notes" style="color: #73141d;" >Notes</a>
                                                                                </li>
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center" data-bs-toggle="tab" href="#reminders" style="color: #73141d;" >Reminders</a>
                                                                                </li>
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center" data-bs-toggle="tab" href="#quoteHistory" style="color: #73141d;" >Quote History</a>
                                                                                </li>
                                                                            </ul>
                                                                            
                                                                            <div class="mt-8">
                                                                                <div class="tab-content" id="myTabContent">
                                                                                    <!--call logs section started-->
                                                                                    <div class="tab-pane fade active show" id="callLogs" role="tabpanel">
                                                                                        <div class="card shadow-sm mb-10">
                                                        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                                        										<div class="card-title">
                                                        											Call Logs
                                                        										</div>
                                                        										<div class="card-toolbar">
                                                        										    <a href="callLogs.php?userId=<?echo $session_id?>&quoteId=<?echo $quoteId?>" class="btn btn-primary btn-sm">Add Call Log</a>
    					                                                                        </div>
                                                        									</div>
                                                        									<div class="card-body ">
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
                                                										                <thead>
                                                    										                <tr>
                                                    										                    <th>Description</th>
                                                    										                    <th>Time Added</th>
                                                    										                </tr>
                                                										                </thead>
                                                										                <tbody>
                                                										                    <?$callLogs=getAll($con,"select * from darlelJobber_call_logs where quoteId='$quoteId' order by timeAdded desc");
                                                                                                            foreach($callLogs as $row){?>
                                                										                    <tr>
                                                										                        <td><?echo $row['description']?></td>
                                                										                        <td><?echo date("d M Y",$row['timeAdded'])?></td>
                                                										                    </tr>
                                                										                    <?}?>
                                                										                </tbody>
                                                										            </table>
                                                        										</div>
                                                										    </div>
                                                										</div>
                                                                                    </div>
                                                                                    <!--call logs section finished-->
                                                                                    
                                                                                    <!--notes section started-->
                                                                                    <div class="tab-pane fade" id="notes" role="tabpanel">
                                                                                        <?require("./notes/notes_table.php");?>
                                                                                    </div>
                                                                                    <!--notes section finished-->
                                                                                    
                                                                                    
                                                                                    <!--reminders section started-->
                                                                                    <div class="tab-pane fade" id="reminders" role="tabpanel">
                                                                                        <div class="card shadow-sm mb-10">
                                                        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                                        										<div class="card-title">
                                                        											Reminders
                                                        										</div>
                                                        										<div class="card-toolbar">
                                                        										    <a href="tasks.php?quoteId=<?echo $quoteId?>&userId=<?echo $session_id?>" class="btn btn-success">Add Reminder</a>
    					                                                                        </div>
                                                        									</div>
                                                        									<div class="card-body ">
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
                                                										                <thead>
                                                    										                <tr>
                                                    										                    <td>Subject</td>
                                                    										                    <td>Comments</td>
                                                    										                    <td>Actions</td>
                                                    										                </tr>
                                                										                </thead>
                                                										                <tbody>
                                                										                    <?$reminders=getAll($con,"select * from darlelJobber_tasks where quoteId='$quoteId' order by timeAdded desc");
                                                                                                            foreach($reminders as $row){?>
                                                										                    <tr>
                                                										                        <td><?echo $row['title']?></td>
                                                										                        <td><?echo $row['description']?></td>
                                                										                        <td>
                                                										                            <a href="detailedTaskView.php?taskId=<?echo $row['id']?>" class="text-white badge badge-primary btn-sm me-1">
                                                										                                <i class="text-white bi bi-eye fs-2x"></i>
                                            										                                </a>
                                                										                        </td>
                                                										                    </tr>
                                                										                    <?}?>
                                                										                </tbody>
                                                										            </table>
                                                        										</div>
                                                										    </div>
                                                										</div>
                                                                                    </div>
                                                                                    <!--reminders section finished-->
                                                                                    
                                                                                    <!--quote history section started-->
                                                                                    <div class="tab-pane fade" id="quoteHistory" role="tabpanel">
                                                                                        <div class="card shadow-sm mb-10">
                                                        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                                        										<div class="card-title">
                                                        											Quote History
                                                        										</div>
                                                        										<div class="card-toolbar"></div>
                                                        									</div>
                                                        									<div class="card-body ">
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-rounded table-striped border gs-7 text-center dataTable">
                                                        										        <thead>
                                                                                                            <tr>
                                                                                                                <th>Title</th>
                                                                                                                <th>Type</th>
                                                                                                                <th>Added By</th>
                                                                                                                <th>Time Added</th>
                                                                                                            </tr>
                                                                                                        </thead>
                                                                                                        <tbody>
                                                                                                            <?$quoteHistory=getAll($con,"select * from darlelJobber_quote_history where quoteId='$quoteId' order by timeAdded desc");
                                                                                                            foreach($quoteHistory as $row){
                                                                                                                if($row['type']=="Call Log")
                                                                                                                    $badge="<a class='badge badge-primary text-white'>".$row['type']."</a>";
                                                                                                                else if($row['type']=="Reminder")
                                                                                                                    $badge="<a class='badge badge-success text-white'>".$row['type']."</a>";
                                                                                                                else if($row['type']=="Quote Changed")
                                                                                                                    $badge="<a class='badge badge-warning text-white'>".$row['type']."</a>";
                                                                                                                else
                                                                                                                    $badge="<a class='badge badge-warning text-white'>".$row['type']."</a>";?>
                                                                                                            <tr>
                                                                                                                <td><?echo $row['title'];?></td>
                                                                                                                <td><?echo $badge;?></td>
                                                                                                                <td><?echo $idToInfo[$row['addedBy']]['name']?></td>
                                                                                                                <td><?echo date("d M Y",$row['timeAdded'])?></td>
                                                                                                            </tr>
                                                                                                            <?}?>
                                                                                                        </tbody>
                                                                                                    </table>
                                                            									</div>
                                                										    </div>
                                                										</div>
                                                                                    </div>
                                                                                    <!--reminders section finished-->
                                                                                    
                                                                                    
                                                                                    
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!--logs,notes and reminders section finished-->
												                <?}?>
        												</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								<div></div>
							</div>
						</div>
					</div>
					
					
					<div class="footer py-4 d-flex flex-lg-column" id="kt_footer" style="position: fixed;bottom: 0;width: 100%;">
					    <div class="row w-100">
					        <?if(($quoteDeets['approveStatus']=="Approved" || ($session_role!="Client" && $session_role!="Estimator" && $session_role!="Admin"))){
    				            $hide="style='display:none';";}?>
    					    <div class="col-12 text-center" style="padding-left: 30px;">
    					        <input type="text" name="quoteId" value="<?echo $quoteId?>" hidden>
    					        <input <?if($countOptional>0){?> onclick="return confirm('Have you seen all the optional line items ? ')" <?}?> type="submit" 
    					        value="Approve Quote" <?echo $hide?> class="btn btn-success btn-sm mr-10 w-100" style="width: 38% !important;white-space: nowrap;">
                			</div>
    					    <div class="col-12 text-center" style="padding-left: 30px;">
    					        <a <?echo $hide?> class="btn btn-warning btn-sm mr-10 w-100 mb-2" data-bs-toggle="modal" data-bs-target="#requestChange" style="width: 38% !important;white-space: nowrap;">Request Changes</a>
    			            </div>
                			<?if($quoteDeets['paidStatus']!="Paid" && $quoteDeets['approveStatus']=="Approved" && $quoteDeets['paidWithCash']=="No"){?>
                			<div class="col-12 text-center">
    					        <a class="btn btn-success btn-sm mr-10 w-100" href="./payment.php?quoteId=<?echo $quoteDeets['id']?>" style="width: 30% !important;">Pay Deposit</a>
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
					</form>
							
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
			
            <script src="assets/js/scripts.bundle.js"></script>
            <script src="assets/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
    		
        </div>
        
        <!--signature pad-->
        <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/assets/numeric-1.2.6.min.js"></script> 
        <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/assets/bezier.js"></script> 
        <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/jquery.signaturepad.js"></script>
        <!--signature pad-->
        
        
	</body>
	
	<div class="modal fade" id="requestChange" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-650px">
				<div class="modal-content rounded">
					<div class="modal-header pb-0 border-0 justify-content-end">
						<div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<span class="svg-icon svg-icon-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
									<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
								</svg>
							</span>
						</div>
					</div>
					
					<div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
						<form action="" method="post" enctype="multipart/form-data">
						    <div class="mb-13 text-left">
					            <h1 class="mb-3" id="modelTitle">Request Changes</h1>
                            </div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Changes Description</span>
								</label>
								<textarea name="changes_description" class="form-control" placeholder="Enter Your Changes Description" rows="10" required></textarea>
							</div>
						    <div class="text-center">
								<input type="submit" value="Save" name="requestChange" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
    <script>
    
    //if a checkbox is unchecked in computer view then it should be unchecked in mobile view too 
    function checkUncheck(checkbox) {
        var checkValue = (checkbox.checked) ? "true" : "false";    
        var tr = $(checkbox).closest('tr'); // Find the closest <tr> element to the clicked button
        var bTag = tr.find('b.notIncluded'); // Find <b> tag with class 'notIncluded' within the <tr> element
        var table = tr.closest('table'); // Find the parent table element
        var bTagSecond = tr.next().find('b.notIncluded'); // Find <b> tag with class 'notIncluded' in the next <tr> element
    
        if(checkValue=="true"){
            tr.removeClass("highlighted-row");
            if (table.hasClass('mobileView')){
                console.log("in here")
                bTagSecond.hide()
            }
            else
                bTag.hide(); 
        }
        else if(checkValue=="false"){
            tr.addClass("highlighted-row");
            if (table.hasClass('mobileView')){
                console.log("in here")
                bTagSecond.show()
            }
            else
                bTag.show(); 
        }
        
        var value=checkbox.value;
        const checkboxes = document.querySelectorAll('input[type="checkbox"][value="'+value+'"]');
        if(checkbox.checked){
            checkboxes.forEach(function(check) {
                check.checked = true;
            });
        }
        else{
            checkboxes.forEach(function(check) {
                check.checked = false;
            });
        }
        calculateFinalTotal();
    }
    
    function calculateFinalTotal(){
        var quoteSum=<?echo ($quoteDeets['subtotal']=="") ? 0 : round($quoteDeets['subtotal'],2);?>;
        var approveStatus="<?echo $quoteDeets['approveStatus']?>";
        var discount=<?echo ($quoteDeets['discount']=="") ? 0 : $quoteDeets['discount'];?>;
        var discountType="<?echo $quoteDeets['discountType']?>";
        var requiredDeposit=<?echo ($quoteDeets['required_deposit']=="") ? 0 : $quoteDeets['required_deposit']?>;
        var paidAmount=<?echo $paidAmountWithDiscount?>;
        var requiredDepositType="<?echo $quoteDeets['requiredDepositType']?>";
        var cashOption="<?echo $quoteDeets['cashOnly']?>";
        
        var sum = 0;
        var totalElements = document.querySelectorAll("p.total");
        
        if(approveStatus!="Approved"){
            totalElements.forEach(function(totalElement) {
                const divId = $(totalElement).closest('tr').attr('id');
                var checkbox=$("#"+divId+" input[name='approveOptional[]']")
                
                checkbox.each(function() {
                    if (this.checked) {
                        const totalElement = $(this).closest("tr").find("p.total")[0];
                        sum += parseFloat(totalElement.textContent);
                    }
                });
            });
            quoteSum=quoteSum+sum;
        }
        
        quoteSum=quoteSum.toFixed(2);
        
        discountedAmount = (discountType=="Amount") ? (discount) : quoteSum*(discount/100);
        discountedAmount=discountedAmount.toFixed(2)
        var total=quoteSum-discountedAmount;
        total = total.toFixed(2);
        //if its percentage then calculate percentage otherwise the required deposit is equal to amount
        if(requiredDepositType=="Percentage")
            requiredDeposit=total*(requiredDeposit/100);
        
        var achDiscount=requiredDeposit-paidAmount;
        achDiscount=achDiscount-(achDiscount*(3/100));
        var payWithACH=achDiscount;
        requiredDeposit=requiredDeposit-paidAmount;
        requiredDeposit=requiredDeposit.toFixed(2)
        $("#subtotal").text(quoteSum);
        if(discount!=0)
            $("#discount").text(discountedAmount);
        $("#total").text(total);
        $("#requiredDeposit").text(requiredDeposit);
        payWithACH=payWithACH.toFixed(2)
        
        if(cashOption=="No")
            $("#payWithACH").val("Pay With ACH-ECheck ($) : "+payWithACH);
        else if(cashOption=="Yes")
            $("#payWithACH").val("Pay With ACH-ECheck ($) : "+requiredDeposit);
        
        $("#payWithCard").val("Pay With Card ($) : "+requiredDeposit);
    }
    
    $(document).ready(function() {
        calculateFinalTotal();
        $('form').submit(function(event) {
          $(this).find(':submit').css('pointer-events', 'none');
        });
    });
    
    </script>
    <?if($session_role!="Client")require("./notes/notes_js.php");?>
</html>
