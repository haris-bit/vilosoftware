<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

function secondsToMinutes($seconds) {
    return floor($seconds / 60);
}

$startDate=clear($_GET['startDate']);
$endDate=clear($_GET['endDate']);

$idToInfo=[];
$users=getAll($con,"select *,concat(street1,',',street2,',',city,',',state,',',country,', Zip : ',zip_code) as fullAddress,concat(first_name,' ',last_name) as fullName from darlelJobber_users");
foreach($users as $row)
    $idToInfo[$row['id']]=$row;
$idToAnalytics=[];
$idToPayments=[];
$selectedEstimators=[];

if((!isset($_GET['startDate'])) || (!isset($_GET['endDate']))){
    $firstDayOfMonth = strtotime('first day of this month');
    $lastDayOfMonth = strtotime('last day of this month');
    $startDate=date("Y-m-d",$firstDayOfMonth);
    $endDate=date("Y-m-d",$lastDayOfMonth);
    header("Location:?startDate=$startDate&endDate=$endDate");
    exit();
}

$startDateTime=strtotime($startDate);
$endDateTime=strtotime($endDate);
$selectedEstimators[]=$session_id;

$quotes=getAll($con,"select * from darlelJobber_quotes where  ( timeAdded between $startDateTime and $endDateTime )");
$sumAwaitingQuote=0;
$sumApprovedQuote=0;
$sumPaidQuotes=0;

foreach($quotes as $row){
    if($row['sendStatus']=="Sent" && $row['approveStatus']=="In Progress")
        $sumAwaitingQuote+=$row['total'];
    if($row['approveStatus']=="Approved" && $row['invoiceId']=="None")
        $sumApprovedQuote+=$row['total'];
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
and t.status!='Completed' order by t.completionDate asc";

if(isset($_GET['completeReminder'])){
    $id = clear($_GET['completeReminder']);
    $query="update darlelJobber_tasks set status='Completed' where id='$id' ";
    runQuery($query);
    header("Location:?m=Reminder has been marked as completed successfully&startDate=$startDate&endDate=$endDate");
}
/*complete reminder finished*/



$queryTickets="select DISTINCT t.* from darlelJobber_tickets t left join darlelJobber_teams tt on t.id=tt.ticketId where ( t.estimatorId='$session_id' or tt.userId='$session_id' ) 
and t.timeAdded between $startDateTime and $endDateTime and t.completionStatus!='Completed' order by t.timeAdded desc";
if(isset($_GET['completed'])){
    $id=clear($_GET['completed']);
    $query="update darlelJobber_tickets set completionStatus='Completed' where id='$id'";
    runQuery($query);
    
    $jobId=getRow($con,"select * from darlelJobber_tickets where id='$id'")['jobId'];
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where jobId='$jobId'");
    $invoiceId=$invoiceDeets['id'];
    
    $finishTime=time()+$invoiceDeets['timeRemaining'];
    $query="update darlelJobber_invoices set finishTime='$finishTime',timePeriodStatus='start' where id='$invoiceId'";
    runQuery($query);
    header("Location:?m=Ticket has been marked as completed successfully&startDate=$startDate&endDate=$endDate");
}


/*jobs section started*/
$idToProperty=array();
$properties=getAll($con,"select * from darlelJobber_properties");
foreach($properties as $row)
{ $idToProperty[$row['id']]=$row;}

$idToContactDetails=[];
$allContacts=getAll($con,"select * from darlelJobber_contacts");
$allContactDetails=getAll($con,"select * from darlelJobber_contact_details cd inner join darlelJobber_contacts c on cd.contactId=c.id ");

foreach($allContactDetails as $row){
    if (isset($idToContactDetails[$row['contactId']])) 
        $idToContactDetails[$row['contactId']][] = $row['value'];
    else 
        $idToContactDetails[$row['contactId']] = [$row['value']];
}

/*jobs section finished*/



//all tasks
$allTasks = getAll($con, $queryTasks);

foreach ($allTasks as &$row) {
    $commentRead = 0;
    $taskId = $row['id'];
    $query = "SELECT * FROM darlelJobber_task_comment_status WHERE taskId='$taskId' AND status='Not Read' AND userId='$session_id'";
    $result = runQueryReturn($query);

    if (mysqli_num_rows($result) == 0) {
        $commentRead = 1;
    }

    // Use 'reminderSent' instead of 'commentRead'
    $row['commentRead'] = $commentRead;
}
unset($row);

function customSort($a, $b) {
    // Cast to integers before subtraction
    return (int)$a['commentRead'] - (int)$b['commentRead'];
}

// Use usort to sort the array
usort($allTasks, 'customSort');

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
        					                        <option disabled selected>Select Predefined Date Range</option>
        					                        <?$filters=["This Month","This Week","Last 30 Days","Last 90 Days","This Year","All"];
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
        										        foreach($allTasks as $row){
        									            $taskId=$row['id'];
        									            ?>
        										        <tr style="<?if(!$row['commentRead']){echo "background-color: #ff7d89 !important;";}?>">
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
            										                <a class="btn btn-primary btn-sm" href="?completeReminder=<?echo $row['id']?>&startDate=<?echo date("Y-m-d",$startDateTime)?>&endDate=<?echo date("Y-m-d",$endDateTime)?>">
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
                                    
                                    <!--reminders section started-->
                                    <div class="col-md-6 col-12">
                                        <div class="card card-flush mb-10">
        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
        										<div class="card-title">
        											<h3>Tickets</h3>
        										</div>
        										<div class="card-toolbar"></div>
        									</div>
        									<div class="card-body pt-0">
        									    <div class="table-responsive">
                                                    <table class="table table-rounded table-row-bordered border gs-7 text-center dataTable" >
        										    <thead>
        										        <tr>
        										            <th >Client</th>
        													<th>Title</th>
        													<th>Status</th>
        													<th>Received By</th>
        													<th>Actions</th>
        										        </tr>
        										    </thead>
        										    <tbody>
        										        <?
        									            $tickets=getAll($con,$queryTickets);
        										        foreach($tickets as $row){
        									            $taskId=$row['id'];?>
        										        <tr>
        											        <td ><a href="./view_client.php?id=<?echo $row['customerId']?>"><?echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?></a></td>
        											        <td><?echo "#".$row['ticket_number']." ".$row['title']?></td>
        													<td><a class="badge badge-<?if($row['completionStatus']=="Pending"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['completionStatus']?></a></td>
        													<td><?echo $idToInfo[$row['addedBy']]['name']?></td>
        													<td>
        													    <div class="btn-group">
        											            <?if($row['completionStatus']=="Pending"){?>
        										                    <a href="?completed=<?echo $row['id']?>&startDate=<?echo date("Y-m-d",$startDateTime)?>&endDate=<?echo date("Y-m-d",$endDateTime)?>"class="btn btn-warning btn-sm">Mark As Complete</a>
        										                <?}?>
        										                <a href="./create_ticket.php?ticketId=<?echo $row['id']?>&view=1" class="btn btn-primary btn-sm">View</a>
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
                                    
                                    
                                    <!--jobs section started-->
                                    <div class="col-md-6 col-12">
                                        <div class="card card-flush mb-10">
        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
        										<div class="card-title">
        											<h3>Jobs</h3>
        										</div>
        										<div class="card-toolbar"></div>
        									</div>
        									<div class="card-body pt-0">
        									    <div class="table-responsive">
                                                    <table class="table table-rounded table-row-bordered border gs-7 text-center dataTable" >
        										    <thead>
        										        <tr>
    										            	<th>Client</th>
        													<th>Title</th>
        													<th>Contact</th>
        													<th>Property</th>
        													<th>Total</th>
        													<th>Job Status</th>
        													<th>Actions</th>
        												</tr>
        										    </thead>
        										    <tbody>
        										        <?$query="select j.*,q.contactId,q.propertyId from darlelJobber_jobs j inner join darlelJobber_quotes q on j.id=q.jobId where q.estimatorId='$session_id' 
        										        and q.jobId!='None' and q.timeAdded between $startDateTime and $endDateTime and j.job_status!='Completed'";
    										            $jobs=getAll($con,$query);
    										            foreach($jobs as $row){?>
    										            <tr>
    											            <td>
    											                <a href="./view_client.php?id=<?echo $row['customerId']?>">
    											                    <?echo "#".$row['job_number']." ".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'];?>
    											                </a>
    								                        </td>
        											        <td><a href="./createJob.php?entryId=<?echo $row['id']?>&view=1"><?echo $row['title']?></a></td>
    											            <td>
    											                <?$contacts=$idToContactDetails[$row['contactId']];
    											                foreach($contacts as $nrow)
    											                echo $nrow." ";?>
    											            </td>
    											            <?$address=$idToProperty[$row['propertyId']];
        											        $address=$address['street1'].",".$address['street2'].",".$address['state'].",".$address['city'].",".$address['country'];?>
        											        <td><?echo $address?></td>
        											        <td><?echo $row['total']?></td>
    											            <td>
    									                        <a class="btn btn-<?if($row['job_status']=="Pending"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['job_status']?></a>
    											            </td>
    											            <td>
    											                <div class="btn-group">
        											                <a href="./createJob.php?entryId=<?echo $row['id']?>&view=1" class="btn btn-primary btn-sm">View</a>
            										                <a href="./createJob.php?entryId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Edit</a>
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
    	$( document ).ready(function() {
    	    
            $(".dataTable").DataTable();
            calculateTotalAmount();
        });
	    
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
    </script>
</html>