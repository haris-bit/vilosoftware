<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

$startDate=clear($_GET['startDate']);
$endDate=clear($_GET['endDate']);

if((!isset($_GET['startDate'])) || (!isset($_GET['endDate']))){
    $firstDayOfMonth = strtotime('first day of this month');
    $lastDayOfMonth = strtotime('last day of this month');
    $startDate=date("Y-m-d",$firstDayOfMonth);
    $endDate=date("Y-m-d",$lastDayOfMonth);
}

$idToInfo=[];
$users=getAll($con,"select *,concat(street1,',',street2,',',city,',',state,',',country,', Zip : ',zip_code) as fullAddress,concat(first_name,' ',last_name) as fullName from darlelJobber_users");
$quotes=getAll($con,"select * from darlelJobber_quotes");
$jobs=getAll($con,"select * from darlelJobber_jobs");
$idToInfoQuotes=[];
$idToInfoJobs=[];
$callLogIdToTeam=[];
$labelNameToColor=[];

$teams=getAll($con,"SELECT callLogId, GROUP_CONCAT(userId) as userIdList FROM darlelJobber_teams GROUP BY callLogId");
foreach ($teams as $row) {
    $callLogId = $row['callLogId'];
    $userIdList = explode(',', $row['userIdList']);
    $callLogIdToTeam[$callLogId] = $userIdList;
}

foreach($quotes as $row)
    $idToInfoQuotes[$row['id']]=$row;
foreach($jobs as $row)
    $idToInfoJobs[$row['id']]=$row;
foreach($users as $row)
    $idToInfo[$row['id']]=$row;

$filter=clear($_GET['filter']);
$labelFilter=clear($_GET['labelFilter']);
$labelFilter = ($labelFilter=="All") ? "" : $labelFilter;

if(isset($_POST['addCallLog']) || isset($_POST['addCallLogWithReminder'])){
    $description=clear($_POST['description']);
    $actionId=clear($_POST['actionId']);
    $linkedId=clear($_POST['linkedId']);
    $searchBy=clear($_POST['searchBy']);
    $customerId=($searchBy=="Client") ? $linkedId : "NULL1";
    $quoteId=($searchBy=="Quote") ? $linkedId : "NULL2";
    $timeAdded=time();
    
    if($actionId==""){
        $callLogId=random();
        $query="insert into darlelJobber_call_logs set id='$callLogId',description='$description',quoteId='$quoteId',searchBy='$searchBy',customerId='$customerId',timeAdded='$timeAdded',addedBy='$session_id'";
        runQuery($query);
    }
    else{
        $callLogId=$actionId;
        $query="update darlelJobber_call_logs set description='$description',quoteId='$quoteId',searchBy='$searchBy',customerId='$customerId' where id='$callLogId'";
        runQuery($query);
    }
    
    //if it's linked to quote then update the last contacted
    if($quoteId!="NULL2"){
        $lastContacted=time();
        runQuery("update darlelJobber_quotes set lastContacted='$lastContacted' where id='$quoteId'");
        
        //insert in quote history
        $random=random();
        $query="insert into darlelJobber_quote_history set id='$random',title='$description',type='Call Log',quoteId='$quoteId',timeAdded='$timeAdded',addedBy='$session_id'";
        runQuery($query);
    }
    //updating team
    $oldTeamFetch=getAll($con,"select userId from darlelJobber_teams where callLogId='$callLogId'");
    $oldTeam=[];
    $newteam=$_POST['team'];
    foreach($oldTeamFetch as $row)
        $oldTeam[]=$row['userId'];
    
    foreach($users as $row){
        if($row['role']!="Client"){
            $userId=$row['id'];
            if((in_array($userId,$newteam)) && (!in_array($userId,$oldTeam))){
                //insert into team
                $id=random();
                $query="insert into darlelJobber_teams set id='$id',userId='$userId',callLogId='$callLogId'";
                runQuery($query);
            }
            else if((!in_array($userId,$newteam)) && (in_array($userId,$oldTeam))){
                //delete this person
                $query="delete from darlelJobber_teams where userId='$userId' && callLogId='$callLogId'";
                runQuery($query);
            }
        }
    }
    //updating team completed
    
    
    //if this is a second follow up of the first follow up then do this 
    if($actionId=="" && isset($_GET['followUp'])){
        $needReminder = clear($_POST['needReminder']);
        $needReminder = ($needReminder=="on") ? "Yes":"No";
        
        $needFollowUp = clear($_POST['needFollowUp']);
        $needFollowUp = ($needFollowUp=="on") ? "Yes":"No";
        
        //inserting in comment the call log for the task id 
        $taskId=clear($_GET['taskId']);
        $query="insert into darlelJobber_task_comments set id='$random',commentText='Call Log : $description',taskId='$taskId',addedBy='$session_id',timeAdded='$timeAdded'";
        runQuery($query);
        
        if($needFollowUp=="Yes"){
            $completionDate = strtotime($_POST['completionDate']);
            $query="update darlelJobber_tasks set completionDate='$completionDate' where id='$taskId'";
            runQuery($query);
            
            $random=random();
            $reminderDateUpt=date("d M Y",$completionDate);
            $commentText="New Follow Up Due On : $reminderDateUpt";
            $query="insert into darlelJobber_task_comments set id='$random',commentText='$commentText',taskId='$taskId',addedBy='$session_id',timeAdded='$timeAdded'";
            runQuery($query);
            
            //adding comment status for all team members
            $taskTeam=getAll($con,"select * from darlelJobber_teams where taskId='$taskId'");
            foreach($taskTeam as $row){
                $random=random();
                $userId=$row['userId'];
                $query="insert into darlelJobber_task_comment_status set id='$random',taskId='$taskId',userId='$userId',status='Not Read'";
                runQuery($query);
            }
            
            //if need a reminder
            if($needReminder=="Yes"){
                $reminderDate=strtotime($_POST['reminderDate']);
                $query="update darlelJobber_tasks set needReminder='Yes',reminderDate='$reminderDate' where id='$taskId'";
                runQuery($query);
            }
        }
        else{
            $query="update darlelJobber_tasks set status='Completed' where id='$taskId'";
            runQuery($query);
        }
        
    }
    
    $displayUser=clear($_GET['userId']);
    $redirectionString="";
    if(isset($_GET['quoteId'])){
        $quoteId=clear($_GET['quoteId']);
        $redirection="createQuote.php?entryId=$quoteId&m=Call Log Has Been Created Successfully";
        $redirectionString="&redirectionPage=createQuote.php&redirectionValue=$quoteId&redirectionVar=entryId";
    }
    else if(isset($_GET['customerId'])){
        $customerId=clear($_GET['customerId']);
        $redirection="view_client.php?id=$customerId&m=Call Log Has Been Created Successfully";
        $redirectionString="&redirectionPage=view_client.php&redirectionValue=$customerId&redirectionVar=id";
    }
    
    $taskId=clear($_GET['taskId']);
    if(isset($_POST['addCallLog']) && isset($_GET['taskId']))
        header("Location:./detailedTaskView.php?taskId=$taskId");
    else if(isset($_POST['addCallLog']) && $redirectionString=="")
        header("Location:?m=Task data has been updated successfully&userId=$displayUser&startDate=$startDate&endDate=$endDate");
    else if(isset($_POST['addCallLogWithReminder']))
        header("Location:./tasks.php?userId=$session_id&callLogId=$callLogId$redirectionString");
    else if(isset($_POST['addCallLog']) && $redirectionString!=""){
        header("Location:./$redirection");
    }
}

if(isset($_GET['delete-call-log'])){
    $id = clear($_GET['delete-call-log']);
    $query="delete from darlelJobber_call_logs where id='$id'";
    runQuery($query);
    $query="delete from darlelJobber_teams where callLogId='$id'";
    runQuery($query);
    header("Location:?m=Task has been deleted successfully");
}

$displayUser=clear($_GET['userId']);
if(!isset($_GET['userId'])){
    $displayUser=$session_id;
    header("Location:?userId=$session_id");
    exit();
}

$startDate=strtotime($startDate);
$endDate=strtotime($endDate);

$queryCallLogs="select DISTINCT t.* from darlelJobber_call_logs t left join darlelJobber_teams tt on t.id=tt.callLogId where ( t.addedBy='$displayUser' or tt.userId='$displayUser' )
and t.timeAdded between $startDate and $endDate order by t.timeAdded desc";
?>

<html lang="en">
	<head>
	    <?require("./includes/views/head.php");?>
	</head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    
							    
						    	<?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                    </div>
                                </div>
                                <?}?>
						    	
							    
					            <div class="row">
					                <div class="col-12">
					                    <div class="row">
					                        <div class="col-md-3 col-6 text-center mt-1 mb-1">
        					                    <h3>Start Date</h3>
                                                <input type="date" name="startDate"  value="<?echo date("Y-m-d",$startDate)?>"  class="btn btn-primary text-white w-100" onchange="submitForm()">
                                            </div>
                                            <div class="col-md-3 col-6 text-center mt-1 mb-1 ">
        					                    <h3>End Date</h3>
                                                <input type="date" name="endDate"  value="<?echo date("Y-m-d",$endDate)?>"  class="btn btn-primary text-white w-100" onchange="submitForm()">
                                            </div>
                                            <?if($session_role=="Admin"){?>
    										<div class="col-md-3 col-6 text-center mt-1 mb-1 ">
        					                    <h3>User Filter </h3>
        					                    <select name="userId" class="btn btn-primary w-100"  onchange="submitForm()" >
                                                    <?foreach($users as $row){if($row['role']!="Client"){?>
                                                    <option <?if($row['id']==$displayUser){echo "selected ";}?> value="<?echo $row['id']?>">
                                                        <?echo $row['name']?>
                                                    </option>
                                                    <?}}?>
                                                </select>
                                            </div>
                                            <?}?>
                                            <div class="col-md-<?echo ($session_role=="Admin") ? "3":"6";?> col-6 text-center mt-1 mb-1">
        					                    <h3>Add Call Log </h3>
    					                        <a id="addCallLogBtn" href="#" data-bs-toggle="modal" data-bs-target="#add_call_log" class="btn btn-primary w-100">Add Call Log</a>
                                            </div>
				                        </div>
					                </div>
							    </div>
							    <div class="card card-flush mb-10">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Call Logs" />
											</div>
										</div>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-row-bordered border gs-7 text-center" id="kt_ecommerce_category_table">
										    <thead>
										        <tr>
										            <th>Purpose</th>
										            <th>Description</th>
										            <th>Team</th>
										            <th class="text-center">Actions</th>
										        </tr>
										    </thead>
										    <tbody>
										        <?
									            $tasks=getAll($con,$queryCallLogs);
										        foreach($tasks as $row){
									            $callLogId=$row['id'];
										        ?>
										        <tr>
										            <td>
										                <?if($row['searchBy']=="Client")
										                    $text = $idToInfo[$row['customerId']]['fullName'];
										                else if($row['searchBy']=="Quote")
										                    $text = "#".$idToInfoQuotes[$row['quoteId']]['quote_number']." ".$idToInfo[$idToInfoQuotes[$row['quoteId']]['customerId']]['fullName'];
										                
										                if($permission['view_client'] && $row['searchBy']=="Client")
										                    $link="view_client.php?id=".$row['customerId'];
										                else if($permission['view_quotes'] && $row['searchBy']=="Quote")
										                    $link="viewQuote.php?entryId=".$row['quoteId'];
										                else     
										                    $link="callLogs.php";?>
									                    <a href="<?echo $link?>"><?echo $text?></a>
										            </td>
										            <td><?echo $row['description']?></td>
										            <td>
										                <?$team=$callLogIdToTeam[$callLogId];
										                foreach($team as $nrow){
										                    $name=$idToInfo[$nrow]['name'];
										                    $btn="<p class='badge badge-light-success btn-sm' style='margin-right:3px;'>$name</p>";
										                    echo $btn;
										                }?>
										            </td>
										            <td class="text-center">
										                <div class="btn-group">
    										                <?if($row['addedBy']==$session_id){?>
											                <a class="btn btn-warning btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#add_call_log" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
											                <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-call-log=<?echo $row['id']?>">Delete</a>
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
				</div>
			</div>
		    <?require("./includes/views/footerjs.php");?>
		</div>
	</body>
	<div class="modal fade" id="add_call_log" tabindex="-1" aria-hidden="true">
			
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
							
							<div class="row mb-5">
							    <div class="col-4">
							        <label class="d-flex align-items-center fs-6 fw-bold mb-2">Search By</label>
							        <select onchange="changeLinkedId()" name="searchBy" class="form-control" >
						                <option value="Quote" selected>Quote</option>
							            <option value="Client">Client</option>
							        </select>
							    </div>
							    <div class="col-8">
							        <label class="d-flex align-items-center fs-6 fw-bold mb-2">Search Criteria</label>
							        <select name="linkedId" id="linkedId" class="form-select" data-control="select2" data-placeholder="Select an option" style="height: 40px;" required>
							        </select>
							    </div>
							</div>
							
							<?if(isset($_GET['followUp'])){?>
							<div class="row mb-3">
    							<div class="col-12">
    							    <div class="row">
    							        <div class="col-md-3 col-6">
            								<div class="form-check mt-11">
                                                <input onchange="updateLook()" name="needFollowUp" class="form-check-input" type="checkbox" />
                                                <label class="form-check-label">Second Follow Up ? </label>
                                            </div>
            						    </div>
            						    <div class="col-md-9 col-6" id="needFollowUp" style="display:none;">
            								<label class="d-flex align-items-center fs-6 fw-bold mb-2"><span class="required">Due Date</span></label>
            								<input type="date" name="completionDate" class="form-control" value="<?echo date("Y-m-d",time());?>">
            						    </div>
    							    </div>
    							</div>
    							
    							<div class="col-12" id="needFollowUpReminder" style="display:none;">
    							    <div class="row mt-2">
            						    <div class="col-md-3 col-6">
            								<div class="form-check mt-11">
                                                <input onchange="updateLook()" name="needReminder" class="form-check-input" type="checkbox" />
                                                <label class="form-check-label">Need Reminder ? </label>
                                            </div>
            						    </div>
            						    <div class="col-md-9 col-6" id="needReminder" style="display:none;">
            								<label class="d-flex align-items-center fs-6 fw-bold mb-2"><span class="required">Reminder Date</span></label>
            								<input type="date" name="reminderDate" class="form-control" value="<?echo date("Y-m-d",time());?>">
            						    </div>
        						    </div>
    						    </div>
							</div>
							<?}?>
							
							 
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Description</span>
								</label>
								<textarea class="form-control" name="description" placeholder="Enter Description" rows="5"></textarea>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Team Member</span>
								</label>
								<select onchange="updateTeam(this.value)" id="assignedMember" class="form-select" data-control="select2" data-placeholder="Select an option" name="assignedMember" required>
								    <option disabled selected>Select Team Member</option>
								    <?foreach($users as $row){
							            if($row['role']!="Client"){?>
								        <option value="<?echo $row['id']?>"><?echo $row['name']?></option>
								    <?}}?>
								</select>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">Team Members</label>
								<div class="d-flex" id="teamMembers"></div>
							</div>
							
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input type="submit" value="Save" name="addCallLog" class="btn btn-primary">
								<input type="submit" value="Save And Add Reminder" name="addCallLogWithReminder" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
		
		var users=<?echo json_encode($users);?>;
		var quotes=<?echo json_encode($quotes);?>;
		var idToInfo=<?echo json_encode($idToInfo);?>;
		var callLogIdToTeam=<?echo json_encode($callLogIdToTeam);?>;
		
		function updateLook(){
	        $('#needFollowUp').hide();
	        $('#needReminder').hide();
	        $('#needFollowUpReminder').hide();
	        
	        var needFollowUp= $("input[name='needFollowUp']").is(':checked');
            needFollowUp = (needFollowUp) ? "on" : "off";
            if(needFollowUp=="on"){
                $('#needFollowUp').show();
                $('#needFollowUpReminder').show();
            }
	        
	        var needReminder= $("input[name='needReminder']").is(':checked');
            needReminder = (needReminder) ? "on" : "off";
            if(needReminder=="on"){
                $('#needReminder').show();
            }
        }
		
		function makeid(length) {
            let result = '';
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            const charactersLength = characters.length;
            let counter = 0;
            while (counter < length) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
                counter += 1;
            }
            return result;
        }
        
        function submitForm(){
            var startDate = $("input[name='startDate']").val();
            var endDate = $("input[name='endDate']").val();
            var labelFilter = $("select[name='labelFilter']").val();
            var filter = $("select[name='filter']").val();
            <?//if it is admin then he can use the user filter
            if($isAdmin){?>
            var userId = $("select[name='userId']").val();
            <?}else{?>
            var userId = "<?echo $session_id?>";
            <?}?>
            window.location.href = window.location.pathname + '?startDate=' + startDate+'&endDate='+endDate+'&labelFilter='+labelFilter+'&filter='+filter+'&userId='+userId;
        }
		
		$(document).ready(function(){
        
        $("#assignedMember").select2({
            dropdownParent: $("#add_call_log")
        });
        changeLinkedId();
        
        $("#add_call_log").on('show.bs.modal', function (e) {
            
            var mydata = $(e.relatedTarget).data('mydata');
               
            if(mydata!= null){
                $("#modelTitle").html("Edit Call Log");
                
                $("select[name='searchBy']").val(mydata['searchBy']);
                $("textarea[name='description']").val(mydata['description']);
                $("input[name='actionId']").val(mydata['id']);
                
                changeLinkedId();
                var select = $('#linkedId').select2();
                var linkedId = (mydata['searchBy']=="Client") ? mydata['customerId'] : mydata['quoteId'];
                select.val(linkedId);
                select.trigger('change');
                
                $('#teamMembers').empty();
                var callLogId=mydata['id'];
                var currentTeam=callLogIdToTeam[callLogId];
                if(currentTeam != null){
                    for(var i=0;i<currentTeam.length;i++)
                        updateTeam(currentTeam[i]);
                }
            }
            
            else{
                
                $('#teamMembers').empty();
                updateTeam("<?echo $session_id?>");
                $("#modelTitle").html("Add Call Log");
                $("select[name='searchBy']").val("Quote");
                changeLinkedId();
                
                $("textarea[name='description']").val("");
                $("input[name='actionId']").val("");
                
                //if quoteId in the url then it means that we need to auto select fields according to quote 
		        <?if(isset($_GET['quoteId'])){
		            $quoteId=clear($_GET['quoteId']);?>
    		        $("select[name='linkedId']").val("<?echo $quoteId?>");
    		        $('#linkedId').trigger('change');
                <?}else if(isset($_GET['customerId'])){
		            $customerId=clear($_GET['customerId']);?>
    		        $("select[name='searchBy']").val("Client");
    		        changeLinkedId();
    		        $("select[name='linkedId']").val("<?echo $customerId?>");
    		        $('#linkedId').trigger('change');
                <?}else if(isset($_GET['taskId'])){
                    $taskId=clear($_GET['taskId']);
                    $taskDeets=getRow($con,"select * from darlelJobber_tasks where id='$taskId'");
                    if($taskDeets['searchBy']=="Quote")
                        $linkedId=$taskDeets['quoteId'];
                    else if($taskDeets['searchBy']=="Client")
                        $linkedId=$taskDeets['customerId'];
                    else if($taskDeets['searchBy']=="Job"){
                        $taskDeets['searchBy']="Quote";
                        $linkedId=$idToInfoJobs[$taskDeets['jobId']]['quoteId'];
                    }
                    ?>
    		        $("select[name='searchBy']").val("<?echo $taskDeets['searchBy']?>");
    		        changeLinkedId();
    		        $("select[name='linkedId']").val("<?echo $linkedId?>");
    		        $('#linkedId').trigger('change');
                <?}?>
            }
        });
        
        <?//if call log is opened from quote or customer page
        if(isset($_GET['quoteId']) || isset($_GET['customerId']) || isset($_GET['taskId'])){?>
        $("#addCallLogBtn")[0].click();
        <?}?>
        
	    });
	    
	    function updateTeam(workerId){
	        var random=makeid(5);
	        var workerName=idToInfo[workerId]['name'];
	        var string=`
	        <p id="`+random+`" class="btn btn-light-success btn-sm" style="margin-right:3px;">
                `+workerName+`
                <a onclick="remove('`+random+`')" style="margin-left: 10px;color: red;">X</a>
    	        <input type="text" name="team[]" value="`+workerId+`" hidden>
    	    </p>
	        `;
	        $('#teamMembers').append(string);
	    }
	    
	    function remove(id){
	        $('#'+id).remove();
	    }
	    
	    function changeLinkedId(){
	        var searchBy=$("select[name='searchBy']").val();
	        var selectLinkedId = $('#linkedId').select2();
            selectLinkedId.empty();
            
	        if(searchBy=="Client"){
	            for(var i=0;i<users.length;i++){
                    if(users[i]['role']=="Client"){
                        var option=users[i]['fullName']+" "+users[i]['fullAddress'];
                        var newOption = new Option(option, users[i]['id']);
                        selectLinkedId.append(newOption);
                    }
                }
            }
	        else if(searchBy=="Quote"){
	            for(var i=0;i<quotes.length;i++){
	                var clientName="";
	                if (idToInfo[quotes[i]['customerId']])
                        clientName = idToInfo[quotes[i]['customerId']]['fullName'];
                    var option="#"+quotes[i]['quote_number']+" "+clientName;
                    var newOption = new Option(option, quotes[i]['id']);
                    selectLinkedId.append(newOption);
                }
	        }
	        $("#linkedId").select2({
                dropdownParent: $("#add_call_log")
            });
            selectLinkedId.trigger('change');
	    }
	</script>
</html>