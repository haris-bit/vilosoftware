<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

function secondsToMinutes($seconds) {
    return floor($seconds / 60);
}

//weekly goals started

$monthStart = strtotime(date('Y-m-01'));
$monthEnd = strtotime(date('Y-m-t'));
$startDate=strtotime(date("Y-m-d",$monthStart));
$endDate=strtotime(date("Y-m-d",$monthEnd))+82800;

$goalDeets=getRow($con,"select * from darlelJobber_goals where startDate>=$startDate and endDate<=$endDate");
$totalQuotesRequired=$goalDeets['amountOfQuotes'];
$totalAmountRequired=$goalDeets['totalAmount'];

$query="select sum(total) as totalAmount,count(id) as totalQuotes from darlelJobber_quotes where estimatorId='$session_id'
and ( ( timeAdded between $startDate and $endDate ) or ( approveTime between $startDate and $endDate) ) and ( approveStatus='Approved' or convertStatus='Converted') group by estimatorId";
$goalsQuotes=getRow($con,$query);
$totalWeeklyQuotes=$goalsQuotes['totalQuotes'];
$totalWeeklyAmount=$goalsQuotes['totalAmount'];

$totalWeeklyQuotes = ($totalWeeklyQuotes=="") ? 0 : $totalWeeklyQuotes;
$totalWeeklyAmount = ($totalWeeklyAmount=="") ? 0 : $totalWeeklyAmount;

$amountGoalCompleted=0;
$totalQuotesGoalCompleted=0;

if($totalWeeklyAmount>=$totalAmountRequired)
    $amountGoalCompleted=1;
if($totalWeeklyQuotes>=$totalQuotesRequired)
    $totalQuotesGoalCompleted=1;

//monthly goals ended

$startDate=clear($_GET['startDate']);
$endDate=clear($_GET['endDate']);
$users=getAll($con,"select *,concat(street1,',',street2,',',city,',',state,',',country,', Zip : ',zip_code) as fullAddress,concat(first_name,' ',last_name) as fullName from darlelJobber_users");

$idToInfo=[];
$idToAnalytics=[];
$idToPayments=[];


if((!isset($_GET['startDate'])) || (!isset($_GET['endDate']))){
    $firstDayOfMonth = strtotime('first day of this month');
    $lastDayOfMonth = strtotime('last day of this month');
    $startDate=date("Y-m-d",$firstDayOfMonth);
    $endDate=date("Y-m-d",$lastDayOfMonth);
    $selectedEstimators=[];
    foreach($users as $row){
        if($row['role']=="Estimator" || $row['role']=="Admin")
            $selectedEstimators[]=$row['id'];
    }
    $selectedEstimators=implode(",",$selectedEstimators);
    $startDate=$_SESSION['adminAnalyticsStartDate'];
    $endDate=$_SESSION['adminAnalyticsEndDate'];
    $selectedEstimators=$_SESSION['adminAnalyticsSelectedEstimators'];
    $firstDayOfMonth = strtotime('first day of this month');
    $lastDayOfMonth = strtotime('last day of this month');
    $startDate=date("Y-m-d",$firstDayOfMonth);
    $endDate=date("Y-m-d",$lastDayOfMonth);
    
    header("Location:?startDate=$startDate&endDate=$endDate&estimators=$selectedEstimators");
    exit();
}

$startDateTime=strtotime($startDate);
$endDateTime=strtotime($endDate)+72000;

if($startDateTime=="" || $endDateTime==""){
    $firstDayOfMonth = strtotime('first day of this month');
    $lastDayOfMonth = strtotime('last day of this month');
    $startDate=date("Y-m-d",$firstDayOfMonth);
    $endDate=date("Y-m-d",$lastDayOfMonth);
    $startDateTime=strtotime($startDate);
    $endDateTime=strtotime($endDate);
}

$_SESSION['adminAnalyticsStartDate']=$startDate;
$_SESSION['adminAnalyticsEndDate']=$endDate;
$_SESSION['adminAnalyticsSelectedEstimators']=clear($_GET['estimators']);
        
$selectedEstimators=explode(",",clear($_GET['estimators']));
if($session_role=="Estimator" || ($_GET['estimators']=="")){
    $selectedEstimators=[];
    $selectedEstimators[]=$session_id;
}
$idToInvoiceDeets=getIndexedArray("select * from darlelJobber_invoices where timeAdded between $startDateTime and $endDateTime");

foreach($users as $row){
    $idToInfo[$row['id']]=$row;
    $idToAnalytics[$row['id']]['totalSales']=0;
    $idToAnalytics[$row['id']]['conversionRate']=0;
    $idToAnalytics[$row['id']]['approvedQuotes']=0;
    $idToAnalytics[$row['id']]['totalQuotes']=0;
    $idToAnalytics[$row['id']]['totalCommission']=0;
    $idToAnalytics[$row['id']]['totalTime']=0;
    $idToAnalytics[$row['id']]['totalTimeQuotes']=0;
    $idToAnalytics[$row['id']]['averageContractValue']=0;
    $idToAnalytics[$row['id']]['averageCommission']=0;
}
$payments=getAll($con,"SELECT quoteId,invoiceId,sum(amountPaid) as paidAmount from darlelJobber_payments group by quoteId,invoiceId");
foreach($payments as $row){
    if($row['quoteId']!="")
        $idToPayments[$row['quoteId']]=$row['paidAmount'];
    else if($row['invoiceId']!="")
        $idToPayments[$row['invoiceId']]=$row['paidAmount'];
}


$jobIdToVisits=[];
$visits=getAll($con,"select * from darlelJobber_visits where timeAdded between $startDateTime and $endDateTime group by jobId");
foreach($visits as $row)
    $jobIdToVisits[$row['jobId']]=1;

/*Displaying total quotes,not approved,awaiting changes,approved,converted,paid (either complete payment checked or deposit was paid) started*/

$totalQuotes=0;
$notApprovedQuotes=0;
$awaitingChangeQuotes=0;
$approvedQuotes=0;
$convertedQuotes=0;
$paidQuotes=0;
$totalConvertedRequests=0;
$unscheduledJobs=0;
$totalOverDueInvoices=0;

$totalDraft=0;
$totalAwaitingResponse=0;
$totalChangesRequested=0;
$totalYellowQuote=0;

$sumYellowQuote=0;
$sumDraftQuote=0;
$sumAwaitingQuote=0;
$sumChangesQuote=0;
$sumApprovedQuote=0;
$sumPaidQuotes=0;
$sumConvertedQuote=0;
$avgTimeSinceLastContacted=0;
$avgTimeFromCreationToSending=0;
$totalAwaitingForAverageSending=0;
$currentTime=time();

//average response time to change requested
$avgTimeToChangeRequested=0;
$totalForChangeRequested=0;


$quotes=getAll($con,"select * from darlelJobber_quotes where approveStatus!='Rejected' and  (( timeAdded between $startDateTime and $endDateTime ) or ( approveTime between $startDateTime and $endDateTime ))");

foreach($quotes as &$row){
    $row['customStatus']="None";
    
    if(!in_array($row['estimatorId'],$selectedEstimators))//which means that we will not display analytics if the estimator is not in selected list
        continue;

    if(($row['timeAdded'] < $startDateTime || $row['timeAdded']  > $endDateTime)){//these quotes do not have a part in analytics
        $totalYellowQuote++;
        $sumYellowQuote+=$row['total'];
        $row['customStatus']="YellowQuote";
        continue;
    }
    
    
    
    //average response time to change requested started
    if($row['changesRequestedStartTime'] < $row['changesRequestedEndTime']){
        $timeDifference = ($row['endTimer'] - $row['startTimer']) / 3600; 
        $avgTimeToChangeRequested+=$timeDifference;
        $totalForChangeRequested++;
    }
    //average response time to change requested finished
    
    
    if($row['sendStatus']=="Not Sent" && $row['approveStatus']=="In Progress"){
        $row['customStatus']="Draft";
        $totalDraft++;
        $sumDraftQuote+=$row['total'];
    }
    if($row['sendStatus']=="Sent" && $row['approveStatus']=="In Progress"){
        if($row['endTimer']!="None" && $row['startTimer']!="None"){
            $timeDifference = ($row['endTimer'] - $row['startTimer']) / 3600; 
            $avgTimeFromCreationToSending+=$timeDifference;
            $totalAwaitingForAverageSending++;
        }
        
        $timeDifference = ($currentTime - $row['lastContacted']) / 3600;
        $avgTimeSinceLastContacted += $timeDifference;
        
        $row['customStatus']="Awaiting Response";
        $totalAwaitingResponse++;
        $sumAwaitingQuote+=$row['total'];
    }
    if(($row['approveStatus']=="Changes Requested" || $row['approveStatus']=="Pending Changes") && ($row['convertStatus']!="Converted" && $row['approveStatus']!="Approved")){
        $row['customStatus']="Changes Requested";
        $totalChangesRequested++;
        $sumChangesQuote+=$row['total'];
    }
    
    //adding in $sumPaidQuotes which means that the invoice for that quote is paid
    if($idToInvoiceDeets[$row['invoiceId']]['paidStatus']=="Paid")
        $sumPaidQuotes+=$row['total'];
    
    if(($row['invoiceId']!="None") && ($idToInvoiceDeets[$row['invoiceId']]['paidStatus']=="Pending" && $idToInvoiceDeets[$row['invoiceId']]['expiryStatus']=="Expired"))
    //checking for overdue invoices
        $totalOverDueInvoices++;
    if(($row['jobId']!="None") && ($jobIdToVisits[$row['jobId']]==null))//checking for unscheduled jobs
        $unscheduledJobs++;
    if($row['requestId']!="None")//means this quote came from a converted request
        $totalConvertedRequests++;
    if($row['approveStatus']=="In Progress" || $row['approveStatus']=="Changes Requested"  || $row['approveStatus']=="Pending Changes")
        $notApprovedQuotes++;
    if($row['approveStatus']=="Changes Requested"  || $row['approveStatus']=="Pending Changes")
        $awaitingChangeQuotes++;
    if($row['approveStatus']=="Approved" && $row['convertStatus']!="Converted"){
        
        if($row['commissionTier']=="A")
            $idToAnalytics[$row['estimatorId']]['averageCommission']+=7;//7% if B tier
        else if($row['commissionTier']=="B")
            $idToAnalytics[$row['estimatorId']]['averageCommission']+=5;//5% if B tier
        else if($row['commissionTier']=="C" || $row['commissionTier']=="D")
            $idToAnalytics[$row['estimatorId']]['averageCommission']+=3;//3% if C or D tier
        
        $row['customStatus']="Approved";
        $approvedQuotes++;
        if($row['convertStatus']!="Converted")
            $sumApprovedQuote+=$row['total'];
        $idToAnalytics[$row['estimatorId']]['approvedQuotes']++;
    }
    if($row['convertStatus']=="Converted"){
        $row['customStatus']="Converted";
        $convertedQuotes++;
        $sumConvertedQuote+=$row['total'];
    }
    if($row['paidStatus']=="Paid" || $row['complete_payment']=="Yes")
        $paidQuotes++;
    $totalQuotes++;
    
    $idToAnalytics[$row['estimatorId']]['averageContractValue']+=$row['total'];
    $idToAnalytics[$row['estimatorId']]['totalQuotes']++;
    
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
    
    
    
    /*calculating estimated commission started*/
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
    /*calculating estimated commission finished*/
    
    
    /*calculating total timer started*/
    if($row['endTimer']!="None" && $row['startTimer']!="None"){
        //means that the timer is completed
        $timeDifference=$row['endTimer']-$row['startTimer'];
        $idToAnalytics[$row['estimatorId']]['totalTime']+=$timeDifference;
        $idToAnalytics[$row['estimatorId']]['totalTimeQuotes']++;
    } 
    /*calculating total timer finished*/
}
unset($row);
$totalAmount=$sumYellowQuote+$sumDraftQuote+$sumAwaitingQuote+$sumChangesQuote+$sumApprovedQuote+$sumConvertedQuote;

$avgTimeFromCreationToSending = ($avgTimeFromCreationToSending > 0) ? $avgTimeFromCreationToSending / $totalAwaitingForAverageSending : 0;
$avgTimeFromCreationToSending=round($avgTimeFromCreationToSending,2);

$avgTimeSinceLastContacted = ($totalAwaitingResponse > 0) ? $avgTimeSinceLastContacted / $totalAwaitingResponse : 0;
$avgTimeSinceLastContacted=round($avgTimeSinceLastContacted,2);


$avgTimeToChangeRequested = ($avgTimeToChangeRequested > 0) ? $avgTimeToChangeRequested / $totalForChangeRequested : 0;
$avgTimeToChangeRequested=round($avgTimeToChangeRequested,2);

/*Displaying total quotes,not approved,awaiting changes,approved,converted,paid (either complete payment checked or deposit was paid) finished*/


/*calculating conversion rate started*/
foreach($users as $row){
    $approved=$idToAnalytics[$row['id']]['approvedQuotes'];
    $total=$idToAnalytics[$row['id']]['totalQuotes'];
    
    $rate=round(($approved/$total)*100,2);
    if(is_nan($rate))
        $rate=0;
    $idToAnalytics[$row['id']]['conversionRate']=$rate;
    
    $totalTime=$idToAnalytics[$row['id']]['totalTime'];
    $sumQuotes=$idToAnalytics[$row['id']]['totalTimeQuotes'];
    
    $idToAnalytics[$row['id']]['totalTime']=round(($totalTime/$sumQuotes),2);
    if(is_nan($idToAnalytics[$row['id']]['totalTime']))
        $idToAnalytics[$row['id']]['totalTime']=0;
    else
        $idToAnalytics[$row['id']]['totalTime']=secondsToMinutes($idToAnalytics[$row['id']]['totalTime']);
    
    if($idToAnalytics[$row['id']]['averageContractValue']==0 && $total==0)
        $idToAnalytics[$row['id']]['averageContractValue']=0;
    else
        $idToAnalytics[$row['id']]['averageContractValue']=round(($idToAnalytics[$row['id']]['averageContractValue']/$total),2);

    $idToAnalytics[$row['estimatorId']]['averageCommission']=round($idToAnalytics[$row['estimatorId']]['averageCommission']/$approved);
}
/*calculating conversion rate finished*/



/*calculating visits unscheduled started*/
$unscheduledVisits=getRow($con,"select *,count(id) as unscheduledVisits from darlelJobber_visits where type='Schedule Later' and timeAdded between $startDateTime and $endDateTime")['unscheduledVisits'];
if($unscheduledVisits=="")
    $unscheduledVisits=0;
/*calculating visits unscheduled finished*/

/*calculating late requests started*/
$totalLateRequests=getRow($con,"select count(id) as totalLateRequests from darlelJobber_requests where requestStatus='Late' and timeAdded between $startDateTime and $endDateTime")['totalLateRequests'];
if($totalLateRequests=="")
    $totalLateRequests=0;
/*calculating late requests finished*/

/*calculating amount of late jobs started*/
$lateVisits=0;
$allVisits=getAll($con,"select * from darlelJobber_visits where timeAdded between $startDateTime and $endDateTime");
foreach($allVisits as $row){
    $visitId=$row['id'];
    $tempDate=$row['end_date']+172800;
    if((time() > $tempDate) && ($row['completionStatus']=="Not Completed") && ($row['type']=="Schedule Now")){
        runQuery("update darlelJobber_visits set visitStatus='Late' where id='$visitId'");
        $row['visitStatus']='Late';
    }
    if($row['visitStatus']=="Late")
        $lateVisits++;
}
/*calculating amount of late jobs finished*/

/*complete reminder started*/
$idToInfoQuotes=[];
$idToInfoJobs=[];
$quotesTasks=getAll($con,"select * from darlelJobber_quotes");
$jobsTasks=getAll($con,"select * from darlelJobber_jobs");

foreach($quotesTasks as $row)
    $idToInfoQuotes[$row['id']]=$row;
foreach($jobsTasks as $row)
    $idToInfoJobs[$row['id']]=$row;

$queryTasks="select DISTINCT t.* from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where ( t.addedBy='$session_id' or tt.userId='$session_id' ) 
and t.timeAdded between $startDateTime and $endDateTime order by t.completionDate asc";

if(isset($_GET['completeReminder'])){
    $id = clear($_GET['completeReminder']);
    $query="update darlelJobber_tasks set status='Completed' where id='$id' ";
    runQuery($query);
    header("Location:?m=Reminder has been marked as completed successfully&startDate=$startDate&endDate=$endDate&estimators=".$_GET['estimators']);
}
/*complete reminder finished*/

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
					
					
					<div class="content d-flex flex-column flex-column-fluid mb-5">
					    <div class="post d-flex flex-column-fluid">
					        <div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
					            
					            <?if(isset($_GET['m'])){?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo clear($_GET['m'])?></h4>
                                    </div>
                                </div>
                                <?}?>
							
					            <div class="row mb-5">
					                <div class="col-12">
					                    <div class="row">
					                        <div class="col-md-6 col-lg-4 col-12 col-12 text-center mb-2 mt-2">
        					                    <h3>Estimator Filter</h3>
                                                <select name="estimators[]" class="form-select form-select-solid" data-control="select2" 
                                                data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                                    <?foreach($users as $row){if($row['role']=="Estimator" || $row['role']=="Admin"){?>
                                                    <option <?if(in_array($row['id'],$selectedEstimators)){echo "selected";}?> value="<?echo $row['id']?>"><?echo $row['name']?></option>
                                                    <?}}?>
                                                </select>
                                            </div>
					                        <div class="col-md-3 col-lg-2 col-6 text-center mb-2 mt-2">
        					                    <h3>Start Date</h3>
                                                <input type="date" name="startDate" value="<?echo $startDate?>" class="btn btn-primary text-white w-100" >
                                            </div>
                                            <div class="col-md-3 col-lg-2 col-6 text-center mb-2 mt-2">
        					                    <h3>End Date</h3>
                                                <input type="date" name="endDate" value="<?echo $endDate?>" class="btn btn-primary text-white w-100" >
                                            </div>
                                            <div class="col-md-6 col-lg-2 col-6 text-center mb-2 mt-2">
        					                    <h3>Filters</h3>
        					                    <select name="dateFilter" class="btn btn-primary text-white w-100" onchange="updateTime()">
        					                        <option disabled selected>Select Predefined Date Range</option>
        					                        <?$filters=["This Month","This Week","Last 30 Days","Last 90 Days","This Year","All"];
        					                        foreach($filters as $row){?>
        					                        <option value="<?echo $row?>"><?echo $row?></option>
        					                        <?}?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 col-lg-2 col-6 text-center mb-2 mt-2">
        					                    <h3>Apply Filters</h3>
                                                <a onclick="submitForm()" class="btn btn-primary text-white w-100">Apply Filters</a>
                                            </div>
					                    </div>
					                </div>
					                
					                <!--quote analytics chart started-->
					                <div class="col-sm-12 col-md-4 col-12 mt-5 mb-5">
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
					                
                                    <!--sales leader chart started-->
					                <div class="col-sm-12 col-md-8 col-12 mt-5 mb-5">
					                    <div class="card shadow-sm">
                                            <div class="card-header">
                                                <h3 class="card-title">Sales Leader Chart : </h3>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="sales_leader_chart" class="mh-400px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <!--sales leader chart finished-->
                                     
					                <!--estimator analytics chart started-->
					                <div class="col-sm-12 col-md-8 col-12 mt-5 mb-5">
					                    <div class="card shadow-sm">
                                            <div class="card-header">
                                                <h3 class="card-title">Estimator Analytics : </h3>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="estimator_analytics" class="mh-400px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <!--estimator analytics chart finished-->
                                    
                                    <div class="col-sm-12 col-md-4 col-12 mt-5 mb-5">
                                    <div class="row">
                                    <!--amount of visits as unscheduled started-->
                                    <div class=" col-6 ">
                                        <a href="schedule.php">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $unscheduledVisits?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Total Visits Unscheduled</b>
										</div>
										</a>
									</div>
                                    <!--amount of visits as unscheduled finished-->
                                    
                                    <!--amount of converted requests started-->
                                    <div class=" col-6 ">
                                        <a href="requests.php?filter=Converted">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalConvertedRequests?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Total Converted Requests</b>
										</div>
										</a>
									</div>
                                    <!--amount of converted requests finished-->
                                    
                                    <!--amount of jobs created but unscheduled started-->
                                    <div class=" col-6 ">
                                        <a href="jobs.php?filter=Unscheduled">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $unscheduledJobs?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Amount Of Jobs Unscheduled</b>
										</div>
										</a>
									</div>
                                    <!--amount of jobs created but unscheduled finished-->
                                    
                                    <!--amount of overdue invoices started-->
                                    <div class=" col-6 ">
                                        <a href="invoices.php?filter=Overdue">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalOverDueInvoices?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Amount Of Overdue Invoices</b>
										</div>
										</a>
									</div>
                                    <!--amount of overdue invoices finished-->
                                    
                                    <!--amount of late requests started-->
                                    <div class=" col-6 ">
                                        <a href="requests.php?filter=Late Requests">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalLateRequests?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Amount Of Late Requests</b>
										</div>
										</a>
									</div>
                                    <!--amount of late requests finished-->
                                    
                                    <!--amount of late visits started-->
                                    <div class=" col-6 ">
                                        <a href="jobs.php?filter=Late">
                                        <div class="col bg-light-primary px-6 py-8 rounded-2 mb-7 text-center">
											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $lateVisits?> </p>
											</span>
											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Amount Of Late Visits</b>
										</div>
										</a>
									</div>
                                    <!--amount of late visits finished-->
                                    
                                    
                                    </div>
                                    </div>
					                
                                    <!--individual stats quotes started-->
                                    <div class="col-sm-12 col-md-4 col-12">
                                        <div class="row">
                                            <!--draft quotes started-->
                                            <div class="col-6">
                                                <a onclick="updateQuotes('darftBtn','Draft')">
                                                    <div  id="darftBtn" class="col  px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
            											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
            											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalDraft?> </p>
            											</span>
            											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Draft Quotes</b>
            										</div>
        										</a>
                                            </div>
                                            <!--draft quotes finished-->
                                            
                                            <!--awaiting response started-->
                                            <div class="col-6">
                                                <a onclick="updateQuotes('responseBtn','Awaiting_Response')">
                                                    <div id="responseBtn" class="col  px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
            											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
            											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalAwaitingResponse?> </p>
            											</span>
            											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Awaiting Response</b>
            										</div>
        										</a>
                                            </div>
                                            <!--awaiting response finished-->
                                            
                                            <!--changes requested started-->
                                            <div class="col-6">
                                                <a  onclick="updateQuotes('changesBtn','Changes_Requested')">
                                                    <div id="changesBtn"  class="col  px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
            											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
            											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalChangesRequested?> </p>
            											</span>
            											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Changes Requested</b>
            										</div>
        										</a>
                                            </div>
                                            <!--changes requested finished-->
                                            
                                            <!--approved quotes started-->
                                            <div class="col-6">
                                                <a   onclick="updateQuotes('approvedBtn','Approved')">
                                                    <div id="approvedBtn" class="col  px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
            											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
            											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $approvedQuotes?> </p>
            											</span>
            											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Approved</b>
            										</div>
        										</a>
                                            </div>
                                            <!--approved quotes finished-->
                                            
                                            <!--converted quotes started-->
                                            <div class="col-6">
                                                <a   onclick="updateQuotes('convertedBtn','Converted')">
                                                    <div id="convertedBtn" class="col  px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
            											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
            											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $convertedQuotes?> </p>
            											</span>
            											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Converted</b>
            										</div>
        										</a>
                                            </div>
                                            <!--converted quotes finished-->
                                            
                                            <!--total yellow quotes started-->
                                            <div class="col-6">
                                                <a onclick="updateQuotes('yellowBtn','YellowQuote')">
                                                    <div id="yellowBtn" class="col  px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
            											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
            											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalYellowQuote?> </p>
            											</span>
            											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Yellow Quote</b>
            										</div>
        										</a>
                                            </div>
                                            <!--total yellow quotes finished-->
                                            
                                            <!--total quotes started-->
                                            <div class="col-12">
                                                <div class="col px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"> <?echo $totalQuotes?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Total Quotes</b>
        										</div>
    										</div>
                                            <!--total quotes finished-->
                                        </div>
                                    </div>
                                    
                                    <!--filtered quotes started-->
                                    <div class="col-sm-12 col-md-8 col-12">
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
                                                        <input type="text" data-kt-Quotes="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Quotes " />
        											</div>
        										</div>
        										<div class="card-toolbar">
        										    <a class="btn btn-primary" id="totalAmount"></a>
        										</div>
        									</div>
        									<div class="card-body pt-0" >
        									    <div class="table-responsive" style="max-height: 450px;overflow-y: auto;">
                                                    <table class="table table-rounded table-row-bordered border gs-7" id="Quotes" >
        											<thead>
        												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
        													<th>Q.No#</th>
        													<th>Client</th>
        													<th>Status</th>
        													<th>Estimator</th>
        													<th>Pricing Tier</th>
        													<th>Commission Tier</th>
        													<th>Total</th>
        												</tr>
        											</thead>
        											<tbody class="fw-bold text-gray-600">
        											    <?foreach($quotes as $row){
        											    if(!in_array($row['estimatorId'],$selectedEstimators))
                                                            continue;
                                                        //this is the quote which was made in some other timeline but approved in our current time frame
                                                        $yellowQuote= ($row['timeAdded'] < $startDateTime || $row['timeAdded']  > $endDateTime) ? 1 : 0 ;
                                                        ?>
            											    <tr class="<?echo str_replace(" ","_",$row['customStatus'])?>" <?if($yellowQuote){?> style="background-color: #fff2c2;" <?}?>>
            											        <td>
            											            <a href="createQuote.php?entryId=<?echo $row['id']?>"><?echo "#".$row['quote_number']." ".$row['title']?></a>
        											            </td>
        											            <td>
        											                <a href="view_client.php?id=<?echo $row['customerId']?>"><?echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?></a>
    											                </td>
    											                <td><?echo $row['customStatus']?></td>
    											                <td><?echo $idToInfo[$row['estimatorId']]['name']?></td>
        											            <td><?echo "P:#".$row['tieredPricing']?></td>
    											                <td><?echo "C:#".$row['commissionTier']?></td>
    											                <td><?echo $row['total']?></td>
    											            </tr>
        											    <?}?>
        											</tbody>
        											
        										</table>
        										</div>
        									</div>
								        </div>
                                    </div>
                                    <!--filtered quotes finished-->
                                    
                                    <!--percentage analytics started-->
                                    <div class="col-12">
                                        <div class="row">
                                            <!--awaiting response vs approved quotes started-->
                                            <div class="col-md-2 col-6">
                                                <div class="col px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"><?echo round(($approvedQuotes/($approvedQuotes+$totalAwaitingResponse))*100,2)."% : ".round(($totalAwaitingResponse/($approvedQuotes+$totalAwaitingResponse))*100,2)."%";?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Approved Quotes VS Awaiting Response Quotes</b>
        										</div>
    										</div>
    										<!--awaiting response vs approved quotes finished-->
                                        
                                            <!--average time since last contacted for awaiting quotes started-->
                                            <div class="col-md-2 col-6">
                                                <div class="col px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"><?echo $avgTimeSinceLastContacted." Hrs";?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Avg. Time Since Last Contacted For Awaiting Quotes</b>
        										</div>
    										</div>
    										<!--average time since last contacted for awaiting quotes finished-->
                                        
                                            <!--average time from quote creation to sending started-->
                                            <div class="col-md-2 col-6">
                                                <div class="col px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"><?echo $avgTimeFromCreationToSending." Hrs";?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Avg. Time From Quote Creation To Sending The Quote</b>
        										</div>
    										</div>
    										<!--average time from quote creation to sending finished-->
    										
    										<!--average time from changes requested to sending quote started-->
                                            <div class="col-md-2 col-6">
                                                <div class="col px-6 py-8 rounded-2 mb-7 text-center" style="background-color: rgb(183, 255, 183);">
        											<span class="svg-icon svg-icon-3x svg-icon-primary d-block my-2 text-center" >
        											    <p style="font-size: xx-large;font-weight: bold;display:inline;"><?echo $avgTimeToChangeRequested." Hrs";?> </p>
        											</span>
        											<b style="font-size: large !important;" class="text-primary fw-bold fs-6">Avg. Time From Request Changes To Awaiting Response</b>
        										</div>
    										</div>
    										<!--average time from changes requested to sending quotes finished-->
    									</div>
                                    </div>
                                    <!--percentage analytics finished-->
                                    
                                    
                                    
                                    <!--monthly analytics started-->
                                    <div class="col-12 mb-5">
                                        <div class="row">
                                            <div class="col-md-4">
                                            <div class="card card-flush" <?if($totalQuotesGoalCompleted){echo "style='background-color:#b7ffb7 !important;'";}?>>
												<div class="card-header pt-5">
													<h3 class="card-title text-gray-800">Monthly Total Quotes Analytics ( <?echo date("d M Y",$monthStart)." ".date("d M Y",$monthEnd)?> ) </h3>
												</div>
												<div class="card-body pt-5">
													<div class="d-flex flex-stack">
														<div class="text-gray-700 fw-semibold fs-6 me-2">Total Quotes Required</div>
														<div class="d-flex align-items-senter">
															<span class="text-gray-900 fw-bolder fs-6"><?echo $totalQuotesRequired?></span>
														</div>
													</div>
													<div class="separator separator-dashed my-3"></div>
													<div class="d-flex flex-stack">
														<div class="text-gray-700 fw-semibold fs-6 me-2">Completed Quotes</div>
														<div class="d-flex align-items-senter">
															<span class="text-gray-900 fw-bolder fs-6"><?echo $totalWeeklyQuotes?></span>
														</div>
													</div>
													<div class="separator separator-dashed my-3"></div>
													<div class="d-flex flex-stack">
														<div class="text-gray-700 fw-semibold fs-6 me-2">Remaining Quotes</div>
														<div class="d-flex align-items-senter">
															<span class="text-gray-900 fw-bolder fs-6"><?echo $totalQuotesRequired-$totalWeeklyQuotes?></span>
														</div>
													</div>
												</div>
											</div>
                                            </div>
                                            <div class="col-md-4">
                                            <div class="card card-flush" <?if($amountGoalCompleted){echo "style='background-color:#b7ffb7 !important;'";}?>>
												<div class="card-header pt-5">
													<h3 class="card-title text-gray-800">Monthly Total Amount Analytics ( <?echo date("d M Y",$monthStart)." - ".date("d M Y",$monthEnd)?> ) </h3>
												</div>
												<div class="card-body pt-5">
													<div class="d-flex flex-stack">
														<div class="text-gray-700 fw-semibold fs-6 me-2">Total Amount Required</div>
														<div class="d-flex align-items-senter">
															<span class="text-gray-900 fw-bolder fs-6"><?echo $totalAmountRequired?></span>
														</div>
													</div>
													<div class="separator separator-dashed my-3"></div>
													<div class="d-flex flex-stack">
														<div class="text-gray-700 fw-semibold fs-6 me-2">Completed Amount</div>
														<div class="d-flex align-items-senter">
															<span class="text-gray-900 fw-bolder fs-6"><?echo $totalWeeklyAmount?></span>
														</div>
													</div>
													<div class="separator separator-dashed my-3"></div>
													<div class="d-flex flex-stack">
														<div class="text-gray-700 fw-semibold fs-6 me-2">Remaining Amount</div>
														<div class="d-flex align-items-senter">
															<span class="text-gray-900 fw-bolder fs-6"><?echo $totalAmountRequired-$totalWeeklyAmount?></span>
														</div>
													</div>
												</div>
											</div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card card-flush" style="margin-bottom: 40px !important;">
        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
        										<div class="card-title">
        											<h3>Lead Source Analytics</h3>
        										</div>
        										<div class="card-toolbar"></div>
        									</div>
        									<div class="card-body pt-0">
        									    <div class="table-responsive">
                                                    <table class="table table-rounded table-row-bordered border gs-7">
        											<thead>
        												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
        													<th>Lead Source</th>
        													<th>% Quotes Approved</th>
        													<th>% Quotes Awaiting Response</th>
        													<th>Total Quotes</th>
        												    <th>Total Amount</th>
        												</tr>
        											</thead>
        											<tbody>
        											    <?$leadSourceQuery="SELECT count(q.id) as totalQuotes,sum(q.total) as totalAmount,ld.title AS LeadSource,
                                                        SUM(CASE WHEN q.approveStatus = 'Approved' THEN 1 ELSE 0 END) AS ApprovedCount,
                                                        SUM(CASE WHEN q.approveStatus = 'In Progress' AND q.sendStatus = 'Sent' THEN 1 ELSE 0 END) AS AwaitingCount
                                                        FROM darlelJobber_quotes q INNER JOIN darlelJobber_requests r ON q.requestId = r.id INNER JOIN darlelJobber_lead_source ld ON r.leadSourceId = ld.id
                                                        WHERE q.requestId != 'None' GROUP BY r.leadSourceId;";
                                                        $leadSourceAnalytics=getAll($con,$leadSourceQuery);
                                                        foreach($leadSourceAnalytics as $row){
                                                        $approvedPerc=round(($row['ApprovedCount']/$row['totalQuotes'])*100,2)."%";
                                                        $awaitingPerc=round(($row['AwaitingCount']/$row['totalQuotes'])*100,2)."%";?>
        											    <tr>
        											        <td><?echo $row['LeadSource']?></td>
        											        <td><?echo $approvedPerc?></td>
        											        <td><?echo $awaitingPerc?></td>
        											        <td><?echo $row['totalQuotes']?></td>
        											        <td><?echo $row['totalAmount']?></td>
        											    </tr>
        											    <?}?>
        											</tbody>
        										</table>
        										</div>
        									</div>
								        </div>
                                            </div>
                                            
                                            <!--leadSource analytics started-->
                                            <!--leadSource analytics finished-->
                                        </div>
                                    </div>
                                    <!--monthly analytics finished-->
                                    
                                    
                                    <!--reminders section started-->
                                    <div class="col-md-6 col-12">
                                        <div class="card card-flush mb-10">
        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
        										<div class="card-title">
        											<h3>Reminders</h3>
        										</div>
        										<div class="card-toolbar"></div>
        									</div>
        									<div class="card-body pt-0">
        									    <div class="table-responsive">
                                                    <table class="table table-rounded table-row-bordered border gs-7 text-center dataTable" >
        										    <thead>
        										        <tr>
        										            <th>Purpose</th>
        										            <th>Title</th>
        										            <th>Completion Date</th>
        										            <th>Timing</th>
        										            <th>Actions</th>
        										        </tr>
        										    </thead>
        										    <tbody>
        										        <?
        									            
        										        $tasks=getAll($con,$queryTasks);
        										        foreach($tasks as $row){
        									            $taskId=$row['id'];?>
        										        <tr>
        										            <td>
        										                <?if($row['searchBy']=="Client"){
        										                    $text = $idToInfo[$row['customerId']]['fullName'];
        										                }
        									                    else if($row['searchBy']=="Quote"){
        										                    $text = "#".$idToInfoQuotes[$row['quoteId']]['quote_number']." ".$idToInfo[$idToInfoQuotes[$row['quoteId']]['customerId']]['fullName'];
        										                }
        									                    else if($row['searchBy']=="Job"){
        										                    $text = "#".$idToInfoJobs[$row['jobId']]['job_number']." ".$idToInfo[$idToInfoJobs[$row['jobId']]['customerId']]['fullName'];
        										                } 
        										                if($permission['view_client'] && $row['searchBy']=="Client")
        										                    $link="view_client.php?id=".$row['customerId'];
        										                else if($permission['view_quotes'] && $row['searchBy']=="Quote")
        										                    $link="viewQuote.php?entryId=".$row['quoteId'];
        										                else if($permission['view_jobs'] && $row['searchBy']=="Job")
        									                        $link = "createJob.php?entryId=".$row['jobId']."&view=1";
        									                    else     
        										                    $link="tasks.php";
        										                $colorStatus= ($row['status']=="Completed") ? "success" : "warning";
        										                ?>
        									                    <a href="<?echo $link?>"><?echo $text?></a>
        										                <a class="badge badge-<?echo $colorStatus?> btn-sm ml-2"><?echo $row['status']?></a>
									                        </td>
        										            <td><?echo $row['title']?></td>
        										            <td><?echo date("d M y",$row['completionDate']);$row['completionDate']=date("Y-m-d",$row['completionDate']);$row['reminderDate']=date("Y-m-d",$row['reminderDate']);?></td>
        										            <td><?echo date("h:i A",strtotime($row['start_time']))." - ".date("h:i A",strtotime($row['end_time']))?></td>
        										            <td class="text-center">
        										                <div class="btn-group">
            										                <a href="detailedTaskView.php?taskId=<?echo $row['id']?>" class="btn btn-success btn-sm" >Detailed View</a>
            													    <?if($row['status']!="Completed"){?>
            										                <a class="btn btn-primary btn-sm" href="?completeReminder=<?echo $row['id']?>&startDate=<?echo date("Y-m-d",$startDateTime)?>&endDate=<?echo date("Y-m-d",$endDateTime)?>&estimators=<?echo $_GET['estimators']?>">
            										                    Mark As Completed
        										                    </a>
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
                                    <!--reminders section finished-->
                                    
                                    <!--total amount in awaiting response started-->
                                    <div class="col-md-6 col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-header">
                                                <h3 class="card-title">Amount Distribution : <b> Total Amount : <?echo round($sumAwaitingQuote+$sumApprovedQuote+$sumPaidQuotes,2)?> </b></h3>
                                                <div class="card-toolbar"></div>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="amount_analytics" class="mh-400px"></canvas>
                                            </div>
                                        </div>
									</div>
									<!--total amount in awaiting response finished-->
									
									
									<!--each estimator monthly goals analytics started-->
									<?if($session_role=="Admin"){?>
									<div class="col-12 mt-3">
                                        <div class="card card-flush" style="margin-bottom: 40px !important;">
        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
        										<div class="card-title">
        											<h3>Goal Quotes Analytics ( <?echo date("d M Y",$startDateTime)." - ".date("d M Y",$endDateTime)?> )</h3>
        										</div>
        										<div class="card-toolbar"></div>
        									</div>
        									<div class="card-body pt-0">
        									    <div class="table-responsive">
                                                    <table class="table table-rounded table-row-bordered border gs-7 text-center">
        											<thead>
        												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
        													<th>Estimator</th>
        													<th>Total Quotes Required</th>
        													<th>Completed Quotes</th>
        													<th>Remaining Quotes</th>
        													<th>Total Amount Required</th>
        												    <th>Completed Amount</th>
        												    <th>Remaining Amount</th>
        												</tr>
        											</thead>
        											<tbody>
        											    <?
        							                    $estimators = ($_GET['estimators']=="") ? $session_id : $_GET['estimators'];
        											    $inClause = explode(',',$estimators );
                                                        $inClause = "'" . implode("','", $inClause) . "'";
                                                        $query="select estimatorId,sum(total) as totalAmount,count(id) as totalQuotes from darlelJobber_quotes where  
        											    estimatorId in ($inClause) and (( timeAdded between $startDateTime and $endDateTime ) or ( approveTime between $startDateTime and $endDateTime))
        											    and ( approveStatus='Approved' or convertStatus='Converted') group by estimatorId";
                                                        $goalsQuotes=getAll($con,$query);
                                                        foreach($goalsQuotes as $row){
                                                            if($row['estimatorId']==null)
                                                                continue;
                                                            $completedQuotes = ($row['totalQuotes']=="") ? 0 : $row['totalQuotes'];
                                                            $completedAmount = ($row['totalAmount']=="") ? 0 : $row['totalAmount'];
                                                        ?>
        											    <tr>
    									                    <td><?echo $idToInfo[$row['estimatorId']]['name']?></td>
        											        <td><?echo $totalQuotesRequired?></td>
        											        <td><?echo $completedQuotes?></td>
        											        <td><?echo round($totalQuotesRequired-$completedQuotes,2);?></td>
        											        <td><?echo $totalAmountRequired?></td>
        											        <td><?echo round($completedAmount,2);?></td>
        											        <td><?echo round($totalAmountRequired-$completedAmount,2)?></td>
        											    </tr>
        											    <?}?>
        											</tbody>
        										</table>
        										</div>
        									</div>
    							        </div>
                                    </div>
								    <?}?>
									<!--each estimator monthly goals analytics finished-->
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
    	var Quotes = function() {
            var t, e, n = () => {};
            return {
            init: function() {
                var t = document.querySelector("#Quotes");
                var e, dataTable;
                if (t) {
                    dataTable = $(t).DataTable({"bPaginate": false,order: [],info: false});
                    dataTable.on("draw", function() {n();});
                    document.querySelector('[data-kt-Quotes="search"]').addEventListener("keyup", function(t) {
                        dataTable.search(t.target.value).draw();
                    });
                    n();
                }
            }
        }
        }();
    	KTUtil.onDOMContentLoaded((function() {
            Quotes.init()
        }));
        
    	$( document ).ready(function() {
    	    
            $(".dataTable").DataTable();
            calculateTotalAmount();
        });
	    function updateQuotes(btnId,className){
	        
	        var backgroundColor = $("#" + btnId).css("background-color");
	        console.log(backgroundColor);
	        
	        if(backgroundColor=="rgb(183, 255, 183)"){//if it was on then we are turning it off now (green color means on)
	            $("#"+btnId).css("background-color", "rgb(255, 225, 170)");
	            $('#Quotes').DataTable().rows('.'+className).nodes().to$().hide();
	        }
	        else if(backgroundColor=="rgb(255, 225, 170)"){//if it was off then we are turning it on now (orange means off)
                $("#"+btnId).css("background-color", "rgb(183, 255, 183)");
	            $('#Quotes').DataTable().rows('.'+className).nodes().to$().show();
	        }
	        calculateTotalAmount();
        }
        
        function calculateTotalAmount(){
            var sum = 0;
            $('#Quotes tbody tr:visible').each(function() {
                var totalValue = parseFloat($(this).find('td:eq(6)').text()); // Assuming "total" column is at index 2 (0-based index)
                if (!isNaN(totalValue)) {
                    sum += totalValue;
                }
            });
            sum=sum.toFixed(2);
            $('#totalAmount').text("Total Amount : "+sum);
        }
	    
	    function submitForm(){
	        var startDate=$("input[name='startDate']").val();
	        var endDate=$("input[name='endDate']").val();
	        var estimators=$("select[name='estimators[]']").val();
	        
	        window.location.href = window.location.pathname + '?startDate=' + startDate+'&endDate='+endDate+'&estimators='+estimators;
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
            else if(currentFilter=="Last 30 Days"){
                var startingTimestamp = Math.floor((new Date().getTime() / 1000) - (30 * 24 * 60 * 60));
                var endingTimestamp = Math.floor(new Date().getTime() / 1000);
                var last30DaysStart = new Date(startingTimestamp * 1000).toISOString().slice(0, 10);
                var last30DaysEnd = new Date(endingTimestamp * 1000).toISOString().slice(0, 10);
                $("input[name='startDate']").val(last30DaysStart);
                $("input[name='endDate']").val(last30DaysEnd);
            }
            else if(currentFilter=="Last 90 Days"){
                var startingTimestamp = Math.floor((new Date().getTime() / 1000) - (90 * 24 * 60 * 60));
                var endingTimestamp = Math.floor(new Date().getTime() / 1000);
                var last90DaysStart = new Date(startingTimestamp * 1000).toISOString().slice(0, 10);
                var last90DaysEnd = new Date(endingTimestamp * 1000).toISOString().slice(0, 10);
                $("input[name='startDate']").val(last90DaysStart);
                $("input[name='endDate']").val(last90DaysEnd);
            }
        }
        
        /*quotes_anlaytics pie chart*/
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
        
        
        /*amount_analytics pie chart*/
	    function amountAnalytics() {
            var a = document.getElementById("amount_analytics"),
                sumAwaitingQuote = KTUtil.getCssVariableValue("--bs-warning"),
                sumApprovedQuote = KTUtil.getCssVariableValue("--bs-primary"),
                sumPaidQuotes = KTUtil.getCssVariableValue("--bs-success");
            const n = {
                labels: ["Total Amount($) Of Awaiting Quotes ", "Total Amount($) Of Approved Quotes", "Total Amount($) Of Paid Quotes"],
                datasets: [{
                    label: "Quotes Amount Data",
                    data: [<?echo $sumAwaitingQuote.",".$sumApprovedQuote.",".$sumPaidQuotes?>],
                    backgroundColor: [sumAwaitingQuote, sumApprovedQuote, sumPaidQuotes]
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
        amountAnalytics();
        
        var estimator_analytics = document.getElementById('estimator_analytics').getContext('2d');
        var sales_leader_chart = document.getElementById('sales_leader_chart').getContext('2d');
        
        <?
        $labelsArr=[];
        foreach($selectedEstimators as $row)
            $labelsArr[]=$idToInfo[$row]['name'];
        $estimatorAnalyticsBars=[
            'Conversion Rate'=>[[],"#ffe06e"], 
            'Approved Quotes'=>[[],"#50cd89"],
            'Total Quotes'=>[[],"#009ef7"],
            'Commission'=>[[],"#a0ffcd"],
            'Averate Quote Conversion Time (Minutes)'=>[[],"#ff62ad"],
            'Averate Contract Value'=>[[],"#c1e9ff"],
            'Averate Commission Percentage'=>[[],"#ffef88"],
            
        ];// these are the bars
        foreach($selectedEstimators as $index=>$row){
            $estimatorAnalyticsBars['Conversion Rate'][0][$index]=$idToAnalytics[$row]['conversionRate'];
            $estimatorAnalyticsBars['Approved Quotes'][0][$index]=$idToAnalytics[$row]['approvedQuotes'];
            $estimatorAnalyticsBars['Total Quotes'][0][$index]=$idToAnalytics[$row]['totalQuotes'];
            $estimatorAnalyticsBars['Commission'][0][$index]=$idToAnalytics[$row]['totalCommission'];
            $estimatorAnalyticsBars['Averate Quote Conversion Time (Minutes)'][0][$index]=$idToAnalytics[$row]['totalTime'];
            $estimatorAnalyticsBars['Averate Contract Value'][0][$index]=$idToAnalytics[$row]['averageContractValue'];
            $estimatorAnalyticsBars['Averate Commission Percentage'][0][$index]=$idToAnalytics[$row]['averageCommission'];
        }
        $labels = json_encode($labelsArr);
        ?>
        var labels = <?php echo $labels; ?>;
        
        /*bar chart for estimator analytics started*/
        var chart = new Chart(estimator_analytics, {
            type: 'bar',
            data: {
                labels: labels,//these are the y labels
                datasets: [
                <?foreach($estimatorAnalyticsBars as $index=>$row){?>
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
        /*bar chart for estimator analytics finished*/
        
        <?
        $leaderChartBars=[
            'Sales'=>[[],"#50cd89"], 
        ];
        foreach($selectedEstimators as $index=>$row){
            $leaderChartBars['Sales'][0][$index]=$idToAnalytics[$row]['totalSales'];
        }
        ?>
        
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