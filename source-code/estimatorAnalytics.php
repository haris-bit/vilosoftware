<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

$startDate=clear($_GET['startDate']);
$endDate=clear($_GET['endDate']);
$_SESSION['normalAnalyticsStartDate']=$startDate;
$_SESSION['normalAnalyticsEndDate']=$endDate;
$users=getAll($con,"select * from darlelJobber_users");

$idToInfo=[];
$idToAnalytics=[];
$idToPayments=[];
$currentEstimator=$session_id;

if((!isset($_GET['startDate'])) || (!isset($_GET['endDate']))){
    /*$startDate=$_SESSION['normalAnalyticsStartDate'];
    $endDate=$_SESSION['normalAnalyticsEndDate'];
    */
    $firstDayOfMonth = strtotime('first day of this month');
    $lastDayOfMonth = strtotime('last day of this month');
    $startDate=date("Y-m-d",$firstDayOfMonth);
    $endDate=date("Y-m-d",$lastDayOfMonth);
    
    header("Location:?startDate=$startDate&endDate=$endDate");
    exit();
}

$selectedEstimators=[];
foreach($users as $row){
    if($row['role']=="Estimator" || $row['role']=="Admin")
    $selectedEstimators[]=$row['id'];
}

$startDateTime=strtotime($startDate);
$endDateTime=strtotime($endDate);
if($startDateTime=="" || $endDateTime==""){
    $firstDayOfMonth = strtotime('first day of this month');
    $lastDayOfMonth = strtotime('last day of this month');
    $startDate=date("Y-m-d",$firstDayOfMonth);
    $endDate=date("Y-m-d",$lastDayOfMonth);
    $startDateTime=strtotime($startDate);
    $endDateTime=strtotime($endDate);
}

$idToInvoiceDeets=getIndexedArray("select * from darlelJobber_invoices where timeAdded between $startDateTime and $endDateTime");

foreach($users as $row){
    $idToInfo[$row['id']]=$row;
    $idToAnalytics[$row['id']]['totalSales']=0;
    $idToAnalytics[$row['id']]['totalCommission']=0;
}
$payments=getAll($con,"SELECT quoteId,invoiceId,sum(amountPaid) as paidAmount from darlelJobber_payments group by quoteId,invoiceId");
foreach($payments as $row){
    if($row['quoteId']!="")
        $idToPayments[$row['quoteId']]=$row['paidAmount'];
    else if($row['invoiceId']!="")
        $idToPayments[$row['invoiceId']]=$row['paidAmount'];
}

/*Displaying total quotes,not approved,awaiting changes,approved,converted,paid (either complete payment checked or deposit was paid) started*/
$totalQuotes=0;
$notApprovedQuotes=0;
$awaitingChangeQuotes=0;
$approvedQuotes=0;
$convertedQuotes=0;
$paidQuotes=0;
$amountOfRequestedChange=0;
$amountOfRequiredDeposit=0;
$amountOfDraftQuotes=0;
$amountOfAwaitingResponse=0;
$quoteApprovedButNotPaid=0;

$quotes=getAll($con,"select * from darlelJobber_quotes where timeAdded between $startDateTime and $endDateTime");

foreach($quotes as $row){
    
    /*calculating estimator sales started*/
    if($row['invoiceId']=="None" && $row['paidStatus']=="Paid")//which means that the invoice for this quote is not created so sales for this quote is just this quote 
        $idToAnalytics[$row['estimatorId']]['totalSales']+=$idToPayments[$row['id']];
    else if($row['invoiceId']!="None"){
        //first check whether the corresponding invoice was added between the given start date and endDate
        $invoiceExists=$idToInvoiceDeets[$row['invoiceId']];
        if($invoiceExists!=null && $invoiceExists['paidStatus']=="Paid"){
            //which means invoice between this date exists
            $idToAnalytics[$row['estimatorId']]['totalSales']+=$idToPayments[$row['invoiceId']];
            $idToAnalytics[$row['estimatorId']]['totalSales']+=$idToPayments[$row['id']];
        }
        else if( ( $invoiceExists!=null && $invoiceExists['paidStatus']=="Pending" )|| ($invoiceExists==null && $row['paidStatus']=="Paid")){
            //this condition checks if invoice was created but not paid or invoice was created but not in the given range and it's corresponding quote was created then add that quote deposit in the sales
            $idToAnalytics[$row['estimatorId']]['totalSales']+=$idToPayments[$row['id']];
        }
    }
    /*calculating estimator sales finished*/
    
    if($row['estimatorId']!=$currentEstimator)//which means that we will not display analytics if the estimator is not in selected list
        continue;
    
    if($row['paidStatus']!="Paid" && $row['approveStatus']=="Approved")
        $quoteApprovedButNotPaid++;
    if($row['sendStatus']=="Sent" && $row['approveStatus']=="In Progress")
        $amountOfAwaitingResponse++;
    if($row['sendStatus']=="Not Sent")
        $amountOfDraftQuotes++;
    if($row['approveStatus']=="In Progress" || $row['approveStatus']=="Changes Requested"  || $row['approveStatus']=="Pending Changes")
        $notApprovedQuotes++;
    if($row['approveStatus']=="Changes Requested"  || $row['approveStatus']=="Pending Changes")
        $awaitingChangeQuotes++;
    if($row['approveStatus']=="Approved")
        $approvedQuotes++;
    if($row['convertStatus']=="Converted")
        $convertedQuotes++;
    if($row['paidStatus']=="Paid" || $row['complete_payment']=="Yes")
        $paidQuotes++;
    if($row['paidStatus']=="Pending" && $row['complete_payment']=="No")
        $amountOfRequiredDeposit++;
    if($row['approveStatus']=="Changes Requested")
        $amountOfRequestedChange++;
    
    $totalQuotes++;
    
    if($idToInvoiceDeets[$row['invoiceId']]['paidStatus']=="Paid"){
        //if the invoice of this quote is paid then we calculate the commission
        $totalPaidAmount=$idToPayments[$row['id']]+$idToPayments[$row['invoiceId']];//total payment is the sum of all payments made in quote and invoice
        $commissionTier=$row['commissionTier'];
        if($commissionTier=="A")
            $commissionedAmount=round($totalPaidAmount*(7/100),2);//7% if A tier
        else if($commissionTier=="B")
            $commissionedAmount=round($totalPaidAmount*(5/100),2);//5% if B tier
        else if($commissionTier=="C" || $commissionTier=="D")
            $commissionedAmount=round($totalPaidAmount*(3/100),2);//3% if C or D tier
        $idToAnalytics[$row['estimatorId']]['totalCommission']+=$commissionedAmount;
    }
    
}
/*Displaying total quotes,not approved,awaiting changes,approved,converted,paid (either complete payment checked or deposit was paid) finished*/


$taskIdToCommentStatus=[];
$fetch=getAll($con,"select * from darlelJobber_task_comment_status where userId='$session_id' group by taskId");
foreach($fetch as $row)
    $taskIdToCommentStatus[$row['taskId']]=$row['status'];

$amountOfTaskToDo=0;
$amountOfErrors=0;
$amountOfOverDue=0;
$commentUnanswered=0;
$amountOfCompletedTasks=0;
$amountOfDueSoon=0;

$query="SELECT DISTINCT tasks.* FROM darlelJobber_tasks tasks LEFT JOIN darlelJobber_teams teams ON tasks.id = teams.taskId
WHERE (tasks.addedBy = '$session_id' OR teams.userId = '$session_id') and completionDate between $startDateTime and $endDateTime";
$tasks=getAll($con,$query);
foreach($tasks as $row){
    $taskId=$row['id'];
	$currentTime=time();
    $timeDifference=abs($row['completionDate']-$currentTime);
    if($currentTime > $row['completionDate']){
        runQuery("update darlelJobber_tasks set status='Over Due' where id='$taskId'");
        $row['status']="Over Due";
    }
    //either half day more or less
    else if($timeDifference <= 43200){
        runQuery("update darlelJobber_tasks set status='Due Soon' where id='$taskId'");
        $row['status']="Due Soon";
    }
    
    if($row['status']=="Due Soon")
        $amountOfDueSoon++;
    if($row['status']=="Completed")
        $amountOfCompletedTasks++;
    if($row['status']!="Completed")
        $amountOfTaskToDo++;
    if($taskIdToCommentStatus[$row['id']]=="Not Read")
        $commentUnanswered++;
    if($row['label']=="Error" && $row['addedBy']!=$session_id)
        $amountOfErrors++;
    if($row['status']=="Over Due")
        $amountOfOverDue++;
}

?>
<html lang="en">
	<head>
		<?require("./includes/views/head.php");?>
		<style>
		    .custom-date-input input[type="date"]::-webkit-calendar-picker-indicator {
                opacity: 0;
                width: 100%;
                height: 100%;
                cursor: pointer;
                position: absolute;
                top: 0;
                left: 0;
            }
		</style>
    </head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					
					<div class="content d-flex flex-column flex-column-fluid">
					    <div class="post d-flex flex-column-fluid">
					        <div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							
					            <div class="row mb-5">
					                <div class="col-12">
					                    <div class="row">
					                        <div class="col-md-3 text-center mb-2 mt-2">
        					                    <h3>Start Date</h3>
                                                <input type="date" name="startDate" value="<?echo $startDate?>" class="btn btn-primary text-white w-100" >
                                            </div>
                                            <div class="col-md-3 text-center mb-2 mt-2">
        					                    <h3>End Date</h3>
                                                <input type="date" name="endDate" value="<?echo $endDate?>" class="btn btn-primary text-white w-100" >
                                            </div>
                                            <div class="col-md-3 text-center mb-2 mt-2">
        					                    <h3>Pre Defined Filters</h3>
        					                    <select name="dateFilter" class="btn btn-primary text-white w-100" onchange="updateTime()">
        					                        <?$filters=["This Month","This Week","This Year","All"];
        					                        foreach($filters as $row){?>
        					                        <option value="<?echo $row?>"><?echo $row?></option>
        					                        <?}?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 text-center mb-2 mt-2">
        					                    <h3>Apply Filters</h3>
                                                <a onclick="submitForm()" class="btn btn-primary text-white w-100">Apply Filters</a>
                                            </div>
					                    </div>
					                </div>
					                
					                <div class="col-md-4 mt-5 mb-5">
                                        <div class="row">
                                            <!--amount of task to do started-->
                                            <div class="col-6">
                                                <a href="tasks.php?filter=Tasks To Do&labelFilter=&userId=<?echo $session_id?>">
                                                    <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
            											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
            											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfTaskToDo?> </p>
            											</span>
            											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">No. Of Tasks To Do  </b>
            										</div>
        										</a>
        									</div>
                                            
                                            <!--amount of task to do finished-->
				                    
                                            <!--amount of task comment started-->
                                            <div class="col-6">
                                                <a href="tasks.php?filter=Comment Unanswered&labelFilter=&userId=<?echo $session_id?>">
                                                <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $commentUnanswered?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Comment Unanswered</b>
        										</div>
        										</a>
        									</div>
                                            <!--amount of task comment finished-->
                                            
                                            <!--amount of task errors started-->
                                            <div class="col-6">
                                                <a href="tasks.php?filter=All&labelFilter=Error&userId=<?echo $session_id?>">
                                                <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfErrors?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">No. Of Errors</b>
        										</div>
        										</a>
        									</div>
                                            <!--amount of task errors finished-->
                                            
                                            <!--amount of task errors started-->
                                            <div class="col-6">
                                                <a href="tasks.php?filter=Over Due&labelFilter=&userId=<?echo $session_id?>">
                                                <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfOverDue?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Total Overdue Tasks</b>
        										</div>
        										</a>
        									</div>
                                            <!--amount of task errors finished-->
                                            
                                            
                                            
                                            <!--amount of task due soon started-->
                                            <div class="col-6">
                                                <a href="tasks.php?filter=Due Soon&labelFilter=&userId=<?echo $session_id?>">
                                                <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfDueSoon?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Due Soon Tasks</b>
        										</div>
        										</a>
        									</div>
                                            <!--amount of task due soon finished-->
                                            
                                            <!--amount of task completed started-->
                                            <div class="col-6">
                                                <a href="tasks.php?filter=Completed&labelFilter=&userId=<?echo $session_id?>">
                                                <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfCompletedTasks?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Total Completed Tasks</b>
        										</div>
        										</a>
        									</div>
                                            <!--amount of task completed finished-->
                                        </div>
                                    </div>
                                    
					                <!--sales leader chart started-->
					                <div class="col-sm-12 col-md-8 col-12 mt-5 mb-5">
					                    <div class="card shadow-sm" style="height: 420px;">
                                            <div class="card-header">
                                                <h3 class="card-title">Sales Leader Chart : </h3>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="sales_leader_chart" class="mh-300px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <!--sales leader chart finished-->
                                    
                                    
                                    <!--quote analytics chart started-->
					                <div class="col-sm-12 col-md-4 col-12 ">
					                    <div class="card shadow-sm">
                                            <div class="card-header">
                                                <h3 class="card-title">Quotes Distribution : <b> Total Quotes : <?echo $totalQuotes?> </b></h3>
                                                <div class="card-toolbar"></div>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="quotes_analytics" class="mh-400px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <!--quote analytics chart finished-->
                                    
                                    <div class="col-sm-12 col-md-8 col-12 ">
                                    <div class="row">
                                    
                                    
                                    <!--total commission in the given time started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $idToAnalytics[$currentEstimator]['totalCommission']?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Estimated Commission</b>
										</div>
									</div>
                                    <!--total commission in the given time finished-->
                                    
                                    
                                    
                                    <!--amount of quotes which requested change started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <a href="quotes.php?filter=Changes Requested">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfRequestedChange?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Quotes Requested Change</b>
										</div>
										</a>
									</div>
                                    <!--amount of quotes which requested change finished-->
                                    
                                    <!--amount of quotes which requires deposit started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <a href="quotes.php?filter=Requiring Deposit">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfRequiredDeposit?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Quotes Requiring Deposit</b>
										</div>
										</a>
									</div>
                                    <!--amount of quotes which requires deposit finished-->
                                    
                                    <!--amount of quotes as drafts started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <a href="quotes.php?filter=Draft">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfDraftQuotes?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Draft Quotes</b>
										</div>
										</a>
									</div>
                                    <!--amount of quotes as drafts finished-->
                                    
                                    <!--amount of quotes as approved started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <a href="quotes.php?filter=Approved">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $approvedQuotes?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Approved Quotes</b>
										</div>
										</a>
									</div>
                                    <!--amount of quotes as approved finished-->
                                    
                                    <!--amount of quotes as converted started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <a href="quotes.php?filter=Converted">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $convertedQuotes?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Converted Quotes</b>
										</div>
										</a>
									</div>
                                    <!--amount of quotes as approved finished-->
                                    
                                    <!--amount of quotes as converted started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <a href="quotes.php?filter=Awaiting Response">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $amountOfAwaitingResponse?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Awaiting Response</b>
										</div>
										</a>
									</div>
                                    <!--amount of quotes as approved finished-->
                                    
                                    
                                    <!--amount of quotes as approved but not paid started-->
                                    <div class="col-sm-6 col-md-4 col-6 ">
                                        <a href="quotes.php?filter=Approved But Not Paid">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $quoteApprovedButNotPaid?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Approved But Not Paid</b>
										</div>
										</a>
									</div>
                                    <!--amount of quotes as approved finished-->
                                    
                                    </div>
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
	</body>
	<script>
	    function submitForm(){
	        var startDate=$("input[name='startDate']").val();
	        var endDate=$("input[name='endDate']").val();
	        
	        window.location.href = window.location.pathname + '?startDate=' + startDate+'&endDate='+endDate;
        }
        function updateTime(){
            var currentFilter=$("select[name='dateFilter']").val();
            if(currentFilter=="This Week"){
                <?$startingTimestamp = strtotime('last Monday', time());
                $endingTimestamp = strtotime('next Sunday', time()) + 86399;?>
                var thisWeekStart="<?echo date("Y-m-d",$startingTimestamp);?>";
                var thisWeekEnd="<?echo date("Y-m-d",$endingTimestamp);?>";
                $("input[name='startDate']").val(thisWeekStart);
                $("input[name='endDate']").val(thisWeekEnd);
            }
            else if(currentFilter=="This Month"){
                <?$startingTimestamp = strtotime('first day of this month', time());
                $endingTimestamp = strtotime('last day of this month', time()) + 86399;?>
                var thisMonthStart="<?echo date("Y-m-d",$startingTimestamp);?>";
                var thisMonthEnd="<?echo date("Y-m-d",$endingTimestamp);?>";
                $("input[name='startDate']").val(thisMonthStart);
                $("input[name='endDate']").val(thisMonthEnd);
            }
            else if(currentFilter=="This Year"){
                <?$startingTimestamp = strtotime('first day of January', time());
                $endingTimestamp = strtotime('last day of December', time()) + 86399;?>
                var thisYearStart="<?echo date("Y-m-d",$startingTimestamp);?>";
                var thisYearEnd="<?echo date("Y-m-d",$endingTimestamp);?>";
                $("input[name='startDate']").val(thisYearStart);
                $("input[name='endDate']").val(thisYearEnd);
            }
            else if(currentFilter=="All"){
                var totalStart="<?echo date("Y-m-d",strtotime("2023-03-01"));?>";
                var totalEnd="<?echo date("Y-m-d",time());?>";
                $("input[name='startDate']").val(totalStart);
                $("input[name='endDate']").val(totalEnd);
            }
        }
        
	    function elementsPieChart() {
            var a = document.getElementById("quotes_analytics"),
                convertedQuotes = KTUtil.getCssVariableValue("--bs-primary"),//converted quotes
                notApprovedQuotes = KTUtil.getCssVariableValue("--bs-danger"),//not approved quotes
                paidQuotes = KTUtil.getCssVariableValue("--bs-success"),//paid quotes
                awaitingChangesQuotes = KTUtil.getCssVariableValue("--bs-warning"),//awaiting changes quotes
                approvedQuotes = KTUtil.getCssVariableValue("--bs-info");//approved quotes
            const n = {
                labels: ["Converted", "Not Approved", "Paid", "Awaiting Changes","Approved"],
                datasets: [{
                    label: "Quotes Data",
                    data: [<?echo $convertedQuotes?>,<?echo $notApprovedQuotes?>,<?echo $paidQuotes?>,<?echo $awaitingChangeQuotes?>,<?echo $approvedQuotes?>],
                    backgroundColor: [convertedQuotes, notApprovedQuotes, paidQuotes,awaitingChangesQuotes,approvedQuotes]
                }]
            };
            new Chart(a, {
                type: "pie",
                data: n,
                options: {
                    plugins: {
                        title: {
                            display: !1
                        }
                    },
                    responsive: !0
                }
            })
        }
        elementsPieChart();
        
        
        
        
        var sales_leader_chart = document.getElementById('sales_leader_chart').getContext('2d');
        
        <?
        $labelsArr=[];
        $leaderChartBars=[
            'Sales'=>[[],"#50cd89"], //first index is the data and second index is the color
        ];
        foreach($selectedEstimators as $index=>$row){
            $labelsArr[]=$idToInfo[$row]['name'];
            $leaderChartBars['Sales'][0][$index]=$idToAnalytics[$row]['totalSales'];
        }
        $labels = json_encode($labelsArr);
        
        ?>
        var labels = <?php echo $labels; ?>;
        /*sales chart analytics started*/
        var chart2 = new Chart(sales_leader_chart, {
            type: 'bar',
            data: {
                labels: labels,//these are the y labels
                datasets: [
                <?foreach($leaderChartBars as $index=>$row){?>
                {
                    label: '<?echo $index?>',
                    data: <?echo json_encode($row[0])?>,
                    backgroundColor: '<?echo $row[1];?>',
                    borderColor: '<?echo $row[1];?>',
                    borderWidth: 1
                },
                <?}?>
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }
            }
        });
        /*sales chart analytics finished*/
        
        
	</script>
</html>