<?
require("./global.php");
$timeAdded=time();
if($logged==0)
    header("Location:./index.php");
if($session_role=="Client")
    header("Location:./oldQuotes.php");

if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    runQuery("delete from darlelJobber_quotes where id='$id'");
    runQuery("delete from darlelJobber_requests where quoteId='$id'");
    runQuery("delete from darlelJobber_jobs where quoteId='$id'");
    runQuery("delete from darlelJobber_invoices where quoteId='$id'");
    runQuery("delete from darlelJobber_quote_details where quoteId='$id'");
    header("Location:?m=Quote has bee deleted successfully");
}

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
    $query="insert into darlelJobber_quotes set id='$random',viewedByEstimator='No',startTimer='$timeAdded',estimatorId='$estimatorId',tieredPricing='$tieredPricing',required_811='$required_811',complete_payment='$complete_payment',
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
    exit();
}


//query writing according to the time format
$_SESSION['oldQuotes']=0;
$startDate=clear($_GET['startDate']);
$endDate=clear($_GET['endDate']);
$searchFilter=clear($_GET['search']);

if((!isset($_GET['startDate'])) || (!isset($_GET['endDate']))){
    $startDate=$_SESSION['quotesStartDate'];
    $endDate=$_SESSION['quotesEndDate'];
    header("Location:?startDate=$startDate&endDate=$endDate");
    exit();
}

if($startDate=="" || $endDate==""){
    $quotesStartDate=date("Y-m-d",time()-7890000 );
    $quotesEndDate=date("Y-m-d",time()+86400);
    header("Location:?startDate=$quotesStartDate&endDate=$quotesEndDate");
    exit();
}

$_SESSION['quotesStartDate']=$startDate;
$_SESSION['quotesEndDate']=$endDate;



if(!isset($_GET['search']))
    $searchFilter="NoSearch";
    

$timeAdded=time();


$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row){$idToInfo[$row['id']]=$row;}

$idToProperty=array();
$properties=getAll($con,"select * from darlelJobber_properties");
foreach($properties as $row)
{ $idToProperty[$row['id']]=$row;}

if(isset($_POST['addCallLog'])){
    $quoteId=clear($_POST['quoteId']);
    $from=clear($_POST['from']);
    $to=clear($_POST['to']);
    $description=clear($_POST['callLogDescription']);
    $lastContacted=time();
    
    runQuery("update darlelJobber_quotes set lastContacted='$lastContacted' where id='$quoteId'");
    
    if($to=="Draft Quotes")
        runQuery("update darlelJobber_quotes set sendStatus='Not Sent',approveStatus='In Progress' where id='$quoteId'");
    else if($to=="Negotiation Review")
        runQuery("update darlelJobber_quotes set sendStatus='Sent',approveStatus='In Progress' where id='$quoteId'");
    else if($to=="Closed Lost")
        runQuery("update darlelJobber_quotes set sendStatus='Sent',approveStatus='Rejected' where id='$quoteId'");
    else if($to=="Closed Won")
        runQuery("update darlelJobber_quotes set sendStatus='Sent',approveStatus='Approved' where id='$quoteId'");
    
    $random=random();
    $timeAdded=time();
    $query="insert into darlelJobber_call_logs set id='$random',quoteId='$quoteId',description='$description',timeAdded='$timeAdded',addedBy='$session_id',searchBy='Quote'";
    runQuery($query);
    
    $getEstimators=clear($_GET['estimators']);
    header("Location:?m=Quote has been transferred from $from to $to successfully !&startDate=$startDate&endDate=$endDate&estimators=$getEstimators");
}



$startDate=strtotime($startDate);
$endDate=strtotime($endDate)+86100;

$_SESSION['quotesEstimators']=clear($_GET['estimators']);
$selectedEstimators=explode(",",clear($_GET['estimators']));
if($session_role!="Admin" || ($_GET['estimators']=="")){
    $selectedEstimators=[];
    $selectedEstimators[]=$session_id;
}

if($searchFilter=="NoSearch")
    $searchFilter="";

$searchQuery=" and ( quote_number LIKE '%$searchFilter%' or title LIKE '%$searchFilter%' or projectName LIKE '%$searchFilter%' ) ";
$timeAddedQuery="and timeAdded between $startDate and $endDate";
$inClause = implode('","', $selectedEstimators);
$estimatorIdQuery = 'and estimatorId in ("' . $inClause . '")';

if($searchFilter!="")
    $timeAddedQuery="";


//draft quotes started
$darftQuery="select * from darlelJobber_quotes where sendStatus='Not Sent' and convertStatus='Not Converted' and approveStatus='In Progress'  $timeAddedQuery $searchQuery $estimatorIdQuery order by timeAdded desc";
$draftQuotes=getAll($con,$darftQuery);
$totalDraftAmount=getRow($con,"select sum(total) as totalDraftAmount from darlelJobber_quotes where sendStatus='Not Sent' and convertStatus='Not Converted' and approveStatus='In Progress' $timeAddedQuery $searchQuery $estimatorIdQuery")['totalDraftAmount'];


//negotiation review quotes started
$negotiationReviewQuery="select * from darlelJobber_quotes where sendStatus='Sent' and ( approveStatus='In Progress' or approveStatus='Changes Requested' ) 
$timeAddedQuery $searchQuery $estimatorIdQuery
ORDER BY CASE WHEN approveStatus = 'Changes Requested' THEN 1 WHEN approveStatus = 'In Progress' THEN 2 END, lastContacted asc";
$negotiationReviewQuotes=getAll($con,$negotiationReviewQuery);
$totalNegotiationReviewAmount=getRow($con,"select sum(total) as totalNegotiationReviewAmount from darlelJobber_quotes where sendStatus='Sent' and ( approveStatus='In Progress' or approveStatus='Changes Requested' )
$timeAddedQuery $searchQuery $estimatorIdQuery")['totalNegotiationReviewAmount'];

//closed lost quotes started
$closedLostQuery="select * from darlelJobber_quotes where approveStatus='Rejected' $timeAddedQuery $searchQuery $estimatorIdQuery order by timeAdded desc";
$closedLostQuotes=getAll($con,$closedLostQuery);
$totalclosedLostAmount=getRow($con,"select sum(total) as totalclosedLostAmount from darlelJobber_quotes where approveStatus='Rejected' $timeAddedQuery $searchQuery $estimatorIdQuery")['totalclosedLostAmount'];


//closed won quotes started
$closedWonQuery="select * from darlelJobber_quotes where approveStatus='Approved' $timeAddedQuery $searchQuery $estimatorIdQuery
ORDER BY CASE WHEN convertStatus = 'Not Converted' THEN 1 WHEN convertStatus = 'Converted' THEN 2 END, timeAdded DESC";
$closedWonQuotes=getAll($con,$closedWonQuery);
$totalclosedWonAmount=getRow($con,"select sum(total) as totalclosedWonAmount from darlelJobber_quotes where approveStatus='Approved' $timeAddedQuery $searchQuery $estimatorIdQuery")['totalclosedWonAmount'];

?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
		<style>
		    #kt_content_container::-webkit-scrollbar {
                height: 15px;
            }
            .table-responsive::-webkit-scrollbar {
                height: 15px;
            }
            .table-responsive {
                height: 410px !important;
            }
        </style>
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
							<div id="kt_content_container" class="container-xxl" style="overflow-x:auto;margin-bottom: 50px;">
						    	
						    	<?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
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
						    	
						    	<a href="#" data-bs-toggle="modal" data-bs-target="#callLogModal" class="btn btn-primary" id="callLogModalBtn" hidden></a>
								<button type="button" id="kt_docs_sweetalert_basic" class="btn btn-primary" hidden>Toggle SweetAlert</button>
								<div class="row">
							        <div class="col-12">
							            <div class="row mb-5">
					                        <div class="col-md-3 col-xl-2 col-6  text-center mt-1 mb-1">
        					                    <h3>Start Date</h3>
                                                <input type="date" name="startDate"  value="<?echo date("Y-m-d",$startDate)?>"  class="btn btn-primary text-white w-100" >
                                            </div>
                                            <div class="col-md-3 col-xl-2 col-6  text-center mt-1 mb-1 ">
        					                    <h3>End Date</h3>
                                                <input type="date" name="endDate"  value="<?echo date("Y-m-d",$endDate)?>"  class="btn btn-primary text-white w-100" >
                                            </div>
                                            <div class="col-md-6 col-xl-2 col-12 text-center mt-1 mb-1">
        					                    <h3>Search All Quotes</h3>
                                                <input type="text" name="search" value="<?echo $searchFilter?>" class="form-control" placeholder="Search All Quotes">
        					                </div>
                                            <div class="col-md-6 col-xl-2 text-center mb-1 mt-1">
        					                    <h3>User Filter</h3>
                                                <select name="estimators[]" class="form-select form-select-solid" data-control="select2" 
                                                data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                                    <?foreach($users as $row){if($row['role']=="Estimator" || $row['role']=="Admin"){?>
                                                    <option <?if(in_array($row['id'],$selectedEstimators)){echo "selected";}?> value="<?echo $row['id']?>"><?echo $row['name']?></option>
                                                    <?}}?>
                                                </select>
                                            </div>
                                            <div class="col-xl-1 col-6 text-center mt-1 mb-1">
        					                    <h3>Submit</h3>
                                                <a onclick="submitForm()" class="btn btn-success w-100">Submit</a>
        					                </div>
                                            <div class="col-xl-2 col-6 text-center mt-1 mb-1">
        					                    <h3>Add Quote </h3>
    					                        <a href="createQuote.php?new=1" class="btn btn-primary w-100">Add Quote</a>
                                            </div>
                                            <div class="col-md-6 col-xl-1 col-12 text-center mt-1 mb-1">
        					                    <h3>Old Quotes</h3>
                                                <a href="oldQuotes.php" class="btn btn-success w-100">Old Quotes</a>
        					                </div>
				                        </div>
					                </div>
							    </div>
							    <div class="col-12">
							        <div style="display: inline-flex;">
							        
							        <!--draft tab started-->
							        <div class="mb-5 me-2" style="width: 420px !important;">
										<div class="card  ">
											<div class="card-header border-0 pt-5">
												<h3 class="card-title align-items-start flex-column">
													<span class="card-label fw-bold text-dark">Draft Quotes</span>
													<span class="card-label mt-2 fw-bold text-dark">Total : <?echo $totalDraftAmount?>$</span>
												</h3>
												<div class="card-toolbar">
											        <span class="svg-icon svg-icon-1 position-absolute ms-4">
    													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
    														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
    														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
    													</svg>
    												</span>
                                                    <input type="text" data-kt-draft="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Quotes " />
											    </div>
											</div>
											<div class="card-body pt-7 px-0">
											    <div class="table-responsive" id="draftSection"  style="padding-left: 20px;padding-right: 20px;max-height: 410px; overflow-y: auto;">
                                                    <table class="table table-rounded  border gs-7 text-center" id="draft" ondrop="drop(event)" ondragover="allowDrop(event)">
                                                        <thead>
                                                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
            													<th>Q.No</th>
            													<th>Client</th>
            													<th>Total</th>
            												</tr>
    											        </thead>
    											        <tbody class="fw-bold text-gray-600">
            											    <?foreach($draftQuotes as $row){?>
            											    <tr id="draftSection" draggable="true" ondragstart="drag(event)" data-section="draftSection" data-rowid="<?= $row['id'] ?>">
            											        <td>
            											            <a href="viewQuote.php?entryId=<?echo $row['id']?>"><?echo "#".$row['quote_number']?></a><br>
            											            <div class="btn-group mt-2">
                											            <?if($permission['view_quotes']){?>
                										                <a href="viewQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-primary btn-sm me-1"><i class="text-white bi bi-eye fs-2x"></i></a>
                										                <?}if($permission['edit_quotes']){?>
                										                <a href="./createQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-warning btn-sm"><i class="text-white bi bi-pencil fs-2x"></i></a>
                										                <?}?>
            										                </div>
        											            </td>
            											        <td>
        													        <a href="./view_client.php?id=<?echo $row['customerId']?>">
        													            <?if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
            									                            echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
            											                else   
            											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
            											            </a>
        												        </td>
        												        <td><?echo $row['total']?></td>
            											    </tr>
                                                            <?}
                                                            if(count($draftQuotes)==0){?>
        											        <tr id="draftSection" draggable="true" ondragstart="drag(event)" data-section="draftSection" data-rowid="<?= "null" ?>">
        											            <td colspan="4">Drag it here to make the quote a part of draft quptes</td>
        											        </tr>
        											        <?}?>
        											    </tbody>
    											    </table>
											    </div>
											</div>
										</div>
									</div>
									<!--draft tab finished-->
								
								
								    <!--negotiation review tab started-->
							        <div class="mb-5 me-2" style="width: 420px !important;">
										<div class="card  ">
											<div class="card-header border-0 pt-5">
												<h3 class="card-title align-items-start flex-column">
													<span class="card-label fw-bold text-dark">Negotiation Review</span>
													<span class="card-label mt-2 fw-bold text-dark">Total : <?echo $totalNegotiationReviewAmount?>$</span>
												</h3>
												<div class="card-toolbar">
											        <span class="svg-icon svg-icon-1 position-absolute ms-4">
    													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
    														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
    														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
    													</svg>
    												</span>
											        <input type="text" data-kt-review="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Quotes " />
											    </div>
											</div>
											<div class="card-body pt-7 px-0">
											    <div id="reviewSection"  style="padding-left: 20px;padding-right: 20px;max-height: 410px; overflow-y: auto;">
                                                    <table class="table table-rounded  border gs-7 text-center" id="review" ondrop="drop(event)" ondragover="allowDrop(event)">
                                                        <thead>
                                                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
            													<!--in quote number add the status as well-->
            													<th>Q.No</th>
            													<th>Client</th>
            													<th>Address</th>
            													<th>Last Contacted</th>
            													<th>Total</th>
            												</tr>
    											        </thead>
    											        <tbody class="fw-bold text-gray-600">
            											    <?foreach($negotiationReviewQuotes as $row){
            											    $changes_description=$row['changes_description'];
            											    if ($row['approveStatus'] == "Changes Requested") {
                                                                $json_data = str_replace("'",'"',$changes_description);
                                                                $statusBadge = "<a href='#' class='badge badge-warning' data-bs-toggle='modal' data-bs-target='#changesDescriptionModal' data-mydata='" . $json_data . "'>Changes Requested<br>(Click To View)</a>";
                                                            }
                                                            else if($row['approveStatus']=="In Progress")
            											       $statusBadge="<a class='badge badge-success'>Awaiting Response</a>"; 
            											    ?>
            											    <tr id="reviewSection" draggable="true" ondragstart="drag(event)" data-section="reviewSection" data-rowid="<?= $row['id'] ?>">
            											        <!--in quote number add the status as well-->
            													<td>
            													    <a href="viewQuote.php?entryId=<?echo $row['id']?>"><?echo "#".$row['quote_number']?></a><br><?echo $statusBadge?><br>
            													    <div class="btn-group mt-2">
            													        <?if($permission['view_quotes']){?>
                										                <a href="viewQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-primary btn-sm me-1"><i class="text-white bi bi-eye fs-2x"></i></a>
                										                <?}if($permission['edit_quotes']){?>
                										                <a href="./createQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-warning btn-sm"><i class="text-white bi bi-pencil fs-2x"></i></a>
                										                <?}?>
            										                </div>
            													</td>
            											        <td>
        													        <a href="./view_client.php?id=<?echo $row['customerId']?>">
        													            <?if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
            									                            echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
            											                else   
            											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
            											            </a>
        												        </td>
        												        <td>
        												        <?$address=$idToProperty[$row['propertyId']];
            											        $address=$address['street1']." ".$address['street2']." ".$address['state'];
            											        echo $address;?>
            											        </td>
            											        <td><?echo date("d M Y",$row['lastContacted'])?></td>
            											        <td><?echo $row['total']?></td>
            											    </tr>
                                                            <?}
                                                            if(count($negotiationReviewQuotes)==0){?>
        											        <tr id="reviewSection" draggable="true" ondragstart="drag(event)" data-section="reviewSection" data-rowid="<?= "null" ?>">
        											            <td colspan="6">Drag it here to make the quote a part of negotiation review</td>
        											        </tr>
        											        <?}?>
        											    </tbody>
    											    </table>
    											</div>
											</div>
										</div>
									</div>
									<!--draft tab finished-->
									
									<!--closed lost tab started-->
							        <div class="mb-5 me-2" style="width: 420px !important;">
										<div class="card  ">
											<div class="card-header border-0 pt-5">
												<h3 class="card-title align-items-start flex-column">
													<span class="card-label fw-bold text-dark">Closed Lost</span>
													<span class="card-label mt-2 fw-bold text-dark">Total : <?echo $totalclosedLostAmount?>$</span>
												</h3>
												<div class="card-toolbar">
											        <span class="svg-icon svg-icon-1 position-absolute ms-4">
    													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
    														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
    														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
    													</svg>
    												</span>
											        <input type="text" data-kt-lost="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Quotes " />
											    </div>
											</div>
											<div class="card-body pt-7 px-0">
											    <div class="table-responsive" id="lostSection" style="padding-left: 20px;padding-right: 20px;max-height: 410px; overflow-y: auto;">
                                                    <table class="table table-rounded  border gs-7 text-center" id="lost" ondrop="drop(event)" ondragover="allowDrop(event)">
                                                        <thead>
                                                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
            													<th>Q.No</th>
            													<th>Client</th>
            													<th>Total</th>
            												</tr>
    											        </thead>
    											        
    											        <tbody class="fw-bold text-gray-600">
            											    <?foreach($closedLostQuotes as $row){?>
            											    <tr id="lostSection" draggable="true" ondragstart="drag(event)" data-section="lostSection" data-rowid="<?= $row['id'] ?>">
            											        <td>
            											            <a href="viewQuote.php?entryId=<?echo $row['id']?>"><?echo "#".$row['quote_number']?></a><br>
            											            <div class="btn-group mt-2">
            													        <?if($permission['view_quotes']){?>
                										                <a href="viewQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-primary btn-sm me-1"><i class="text-white bi bi-eye fs-2x"></i></a>
                										                <?}if($permission['edit_quotes']){?>
                										                <a href="./createQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-warning btn-sm"><i class="text-white bi bi-pencil fs-2x"></i></a>
                										                <?}?>
            										                </div>
            											        </td>
            											        <td>
        													        <a href="./view_client.php?id=<?echo $row['customerId']?>">
        													            <?if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
            									                            echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
            											                else   
            											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
            											            </a>
        												        </td>
        												        <td><?echo $row['total']?></td>
            											    </tr>
                                                            <?}
                                                            if(count($closedLostQuotes)==0){?>
        											        <tr id="lostSection" draggable="true" ondragstart="drag(event)" data-section="lostSection" data-rowid="<?= "null" ?>">
        											            <td colspan="4">Drag it here to make the quote a part of closed lost</td>
        											        </tr>
        											        <?}?>
        											    </tbody>
    											    </table>
    											</div>
											</div>
										</div>
									</div>
									<!--closed lost tab finished-->
									
									<!--closed won tab started-->
							        <div class="mb-5 me-2" style="width: 420px !important;">
										<div class="card  ">
											<div class="card-header border-0 pt-5">
												<h3 class="card-title align-items-start flex-column">
													<span class="card-label fw-bold text-dark">Closed Won</span>
													<span class="card-label mt-2 fw-bold text-dark">Total : <?echo $totalclosedWonAmount?>$</span>
												</h3>
												<div class="card-toolbar">
											        <span class="svg-icon svg-icon-1 position-absolute ms-4">
    													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
    														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
    														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
    													</svg>
    												</span>
											        <input type="text" data-kt-won="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Quotes " />
											    </div>
											</div>
											<div class="card-body pt-7 px-0">
											    <div class="table-responsive" id="wonSection" style="padding-left: 20px;padding-right: 20px;max-height: 410px; overflow-y: auto;">
											    <table class="table table-rounded border gs-7 text-center" id="won" ondrop="drop(event)" ondragover="allowDrop(event)">
                                                    <thead>
                                                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
        													<th>Q.No</th>
        													<th>Client</th>
        													<th>Total</th>
        												</tr>
											        </thead>
										            <tbody class="fw-bold text-gray-600">
            											    <?foreach($closedWonQuotes as $row){
            											    if($row['convertStatus']=="Converted"){
            											        $badge="<a class='badge badge-success'>Converted</a>";
            											        $converted=1;
            											        $backgroundColor="style='background-color: #92ffc4 !important;'";
            											    }
            											    else if($row['convertStatus']=="Not Converted"){
            											        $badge="<a class='badge badge-danger'>Not Converted</a>";
            											        $converted=1;
            											        $backgroundColor="style='background-color: #ffb8bf !important;'";
            											    }
            											    ?>
            											    <tr <?echo $backgroundColor?> id="wonSection" draggable="true" ondragstart="drag(event)" data-section="wonSection" data-rowid="<?= $row['id'] ?>">
            											        <td>
            											            <a href="viewQuote.php?entryId=<?echo $row['id']?>"><?echo "#".$row['quote_number']?></a><br>
            											            <?echo $badge;?><br>
            											            <div class="btn-group mt-2">
            													        <?if($permission['view_quotes']){?>
                										                <a href="viewQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-primary btn-sm me-1"><i class="text-white bi bi-eye fs-2x"></i></a>
                										                <?}if($permission['edit_quotes']){?>
                										                <a href="./createQuote.php?entryId=<?echo $row['id']?>" class="text-white badge badge-warning btn-sm"><i class="text-white bi bi-pencil fs-2x"></i></a>
                										                <?}?>
            										                </div>
            											        </td>
            											        <td>
        													        <a href="./view_client.php?id=<?echo $row['customerId']?>">
        													            <?if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
            									                            echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
            											                else   
            											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
            											            </a>
        												        </td>
        												        <td><?echo $row['total']?></td>
            											    </tr>
                                                            <?}
                                                            if(count($closedWonQuotes)==0){?>
        											        <tr id="wonSection" draggable="true" ondragstart="drag(event)" data-section="wonSection" data-rowid="<?= "null" ?>">
        											            <td colspan="4">Drag it here to make the quote a part of closed won</td>
        											        </tr>
        											        <?}?>
        											    </tbody>
											        </table>
											    </div>
											</div>
										</div>
									</div>
									<!--closed lost tab finished-->
								
								
								</div>
								</div>
							</div>
						</div>
					</div>
					<?require("./includes/views/footer.php");?>
					
				</div>
			</div>
			<?require("./includes/views/footerjs.php");?>
	    </div>
	    
	    
	    <!--call log pop-up started-->
	    <div class="modal fade" id="callLogModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered mw-850px">
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
							    <h1 class="mb-3" id="modelTitle"></h1>
							</div>

                            <div class="form-group mb-5 notEdit">
                                <label><b style="font-size: large;">Call Log Entry</b></label>
                                <textarea name="callLogDescription" class="form-control" rows="8" required></textarea>
                            </div>
                            
                            <input type="text" name="quoteId" hidden>
                        	<input type="text" name="from" hidden>
                            <input type="text" name="to" hidden>
                            
                            <div class="text-center">
								<input type="submit" value="Submit Call Log" name="addCallLog" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		<!--changesDescriptionModa started-->
	    <div class="modal fade" id="changesDescriptionModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered mw-850px">
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
							    <h1 class="mb-3" id="modelTitle">Changes Description</h1>
							</div>
                            <div class="form-group mb-5">
                                <label>Changes Description</label>
                                <textarea name="changes_description" class="form-control" rows="8" required></textarea>
                            </div>
                        </form>
					</div>
				</div>
			</div>
		</div>
		
	    <script>
	    
	    function submitForm(){
	        var startDate=$("input[name='startDate']").val();
	        var endDate=$("input[name='endDate']").val();
	        var search=$("input[name='search']").val();
	        if(search=="")  
	            search="NoSearch";
	        var estimators=$("select[name='estimators[]']").val();
	        window.location.href = window.location.pathname + '?startDate=' + startDate+'&endDate='+endDate+'&search='+search+'&estimators='+estimators;
        }
	    
	    $(document).ready(function(){
            $("#changesDescriptionModal").on('show.bs.modal', function (e) {
                var mydata = $(e.relatedTarget).data('mydata');
                $("textarea[name='changes_description']").val(mydata)
                console.log(mydata);
            });
        });
        
        function drag(ev) {
            ev.dataTransfer.setData("text", ev.target.getAttribute("data-section") + "|" + ev.target.getAttribute("data-rowid"));
        }
    
        function drop(ev) {
            ev.preventDefault();
            var data = ev.dataTransfer.getData("text").split("|");
            var map = {"draftSection": "Draft Quotes","reviewSection": "Negotiation Review","lostSection": "Closed Lost","wonSection": "Closed Won"};
            var fromSection = map[data[0]];
            var rowId = data[1];
            var toSection = map[ev.target.parentElement.getAttribute("id")];
            if(fromSection==null || toSection==null || rowId==null || rowId=="null"){
                alert("Drag and drop action not properly performed . Kindly try again ");
            }
            else if (fromSection !== toSection) {
                $("#modelTitle").html("Moving From "+fromSection+" To " + toSection);            	
                $("#callLogModalBtn")[0].click();
                $("input[name='quoteId']").val(rowId)                                
                $("input[name='from']").val(fromSection)                                
                $("input[name='to']").val(toSection)                                
            }
        }
    
        function allowDrop(ev) {
            ev.preventDefault();
        }
        </script>
		
		
	
	</body>
	

	
	<script>
	/*datatables section started*/
	<?$tables=["draft","review","lost","won"];
	foreach($tables as $row){?>
	    var <?echo $row?>Quotes = function() {
        var t, e, n = () => {};
        return {
        init: function() {
            var t = document.querySelector("#<?echo $row?>");
            var e, dataTable;
            if (t) {
                dataTable = $(t).DataTable({"bPaginate": false,order: [],info: false});
                dataTable.on("draw", function() {n();});
                document.querySelector('[data-kt-<?echo $row?>="search"]').addEventListener("keyup", function(t) {
                    dataTable.search(t.target.value).draw();
                });
                n();
            }
        }
    }
    }();
    KTUtil.onDOMContentLoaded((function() {
        <?echo $row?>Quotes.init()
    }));
    <?}?>
    /*datatables section finished*/
    
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
    $( document ).ready(function() {
    <?if($_GET['m']=="p_success"){?>
        $("#kt_docs_sweetalert_basic")[0].click();
    <?}?>
    });
    </script>
	
</html>