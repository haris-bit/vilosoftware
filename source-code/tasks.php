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

$labels=getAll($con,"select * from darlelJobber_labels order by timeAdded desc");
$idToInfo=[];
$users=getAll($con,"select *,concat(street1,',',street2,',',city,',',state,',',country,', Zip : ',zip_code) as fullAddress,concat(first_name,' ',last_name) as fullName from darlelJobber_users");
$quotes=getAll($con,"select * from darlelJobber_quotes");
$jobs=getAll($con,"select * from darlelJobber_jobs");
$idToInfoQuotes=[];
$idToInfoJobs=[];
$taskIdToTeam=[];
$labelNameToColor=[];
$jobIdToEstimator=[];

foreach($labels as $row)
    $labelNameToColor[$row['title']]=$row['colorCode'];

$teams=getAll($con,"SELECT taskId, GROUP_CONCAT(userId) as userIdList FROM darlelJobber_teams GROUP BY taskId");
foreach ($teams as $row) {
    $taskId = $row['taskId'];
    $userIdList = explode(',', $row['userIdList']);
    $taskIdToTeam[$taskId] = $userIdList;
}

foreach($quotes as $row){
    $idToInfoQuotes[$row['id']]=$row;
}
foreach($jobs as $row){
    $idToInfoJobs[$row['id']]=$row;
}
foreach($users as $row){
    $idToInfo[$row['id']]=$row;
}

foreach($jobs as $row){
    $quoteId=$row['quoteId'];
    $estimatorId = $idToInfoQuotes[$quoteId]['estimatorId'];
    $jobIdToEstimator[$row['id']]=$estimatorId;
}

$filter=clear($_GET['filter']);
$labelFilter=clear($_GET['labelFilter']);
$labelFilter = ($labelFilter=="All") ? "" : $labelFilter;

if(isset($_POST['addTask'])){
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $actionId=clear($_POST['actionId']);
    $start_time=clear($_POST['start_time']);
    $end_time=clear($_POST['end_time']);
    
    $searchBy=clear($_POST['searchBy']);
    $completionDate=strtotime($_POST['completionDate'])+86350;
    $reminderDate=strtotime($_POST['reminderDate'])+86350;
    $needReminder=clear($_POST['needReminder']);
    $needReminder = ($needReminder=="on") ? "Yes":"No";
    
    $label=clear($_POST['label']);
    $linkedId=clear($_POST['linkedId']);
    $timeAdded=time();
    $id=random();
    
    $taskId=($actionId=="") ? random() : $actionId;
    $customerId=($searchBy=="Client") ? $linkedId : "NULL1";
    $quoteId=($searchBy=="Quote") ? $linkedId : "NULL2";
    $jobId=($searchBy=="Job") ? $linkedId : "NULL3";
    
    $updateQuoteNotes=0;
    if($searchBy=="Quote"){
        $updateQuoteNotes=1;
        $quoteDeets=$idToInfoQuotes[$quoteId];
        
        if($quoteDeets['notesId']=="None"){//which means that no attachment notes has been added for this quote
            //create notes which will contain all the attachments of this quote
            $notesId=random();
            $query="insert into darlelJobber_notes set id='$notesId',title='Attachments of All Quote Tasks',description='This notes contains all the attachments of all the tasks that are linked to this quote',
            addedBy='$session_id',timeAdded='$timeAdded',quoteId='$quoteId'";
            runQuery($query);
            
            //updating the quote with this notesId
            $query="update darlelJobber_quotes set notesId='$notesId' where id='$quoteId'";
            runQuery($query);
        }
        else
            $notesId=$quoteDeets['notesId'];
        
        //insert in quote history
        $random=random();
        $query="insert into darlelJobber_quote_history set id='$random',title='$title',type='Reminder',quoteId='$quoteId',timeAdded='$timeAdded',addedBy='$session_id'";
        runQuery($query);
    }
    
    if($actionId==""){
        $status  = ($label=="Job Error") ? "Completed" : "New";
        $query="insert into darlelJobber_tasks set id='$taskId',reminderDate='$reminderDate',needReminder='$needReminder',title='$title',description='$description',status='$status',start_time='$start_time',end_time='$end_time',
        addedBy='$session_id',searchBy='$searchBy',jobId='$jobId',customerId='$customerId',quoteId='$quoteId',label='$label',
        completionDate='$completionDate',timeAdded='$timeAdded'";
        runQuery($query);
        
        if($label=="Price Request" && $quoteId!="None"){//stops the timer of the request
            $endTimer=time();
            $query="update darlelJobber_quotes set endTimer='$endTimer' where id='$quoteId'";
            runQuery($query);
        }
    }
    else{
        $query="update darlelJobber_tasks set title='$title',reminderDate='$reminderDate',needReminder='$needReminder',jobId='$jobId',description='$description',searchBy='$searchBy',customerId='$customerId',quoteId='$quoteId'
        ,label='$label',completionDate='$completionDate',start_time='$start_time',end_time='$end_time' where id='$taskId'";
        runQuery($query);
    }
    //updating team
    $oldTeamFetch=getAll($con,"select userId from darlelJobber_teams where taskId='$taskId'");
    $oldTeam=[];
    $newteam=$_POST['team'];
    
    foreach($oldTeamFetch as $row){
        $oldTeam[]=$row['userId'];
    }
    
    foreach($users as $row){
        if($row['role']!="Client"){
            $userId=$row['id'];
            if((in_array($userId,$newteam)) && (!in_array($userId,$oldTeam))){
                //insert into team
                $id=random();
                $query="insert into darlelJobber_teams set id='$id',userId='$userId',taskId='$taskId'";
                runQuery($query);
        
                $title="Assigned To a Task";
                $description="You have been assigned a task . Click To View";
                $url=$projectUrl."detailedTaskView.php?taskId=$taskId";
                setNotification($title,$description,$userId,$url);
            }
            else if((!in_array($userId,$newteam)) && (in_array($userId,$oldTeam))){
                //delete this person
                $query="delete from darlelJobber_teams where userId='$userId' && taskId='$taskId'";
                runQuery($query);
            }
        }
    }
    //updating team completed
    
    
    //if this is a new reminder and the label is follow up then all the team will be able to see the comment which is saying 1st reminder due
    if($actionId=="" && $label=="Follow Up"){
        $random=random();
        $reminderDateUpt=date("d M Y",$completionDate);
        $commentText="New Follow Up Due On : $reminderDateUpt";
        $query="insert into darlelJobber_task_comments set id='$random',commentText='$commentText',taskId='$taskId',addedBy='$session_id',timeAdded='$timeAdded'";
        runQuery($query);
        
        //adding  comment status for all team members
        $taskTeam=getAll($con,"select * from darlelJobber_teams where taskId='$taskId'");
        foreach($taskTeam as $row){
            $random=random();
            $userId=$row['userId'];
            $query="insert into darlelJobber_task_comment_status set id='$random',taskId='$taskId',userId='$userId',status='Not Read'";
            runQuery($query);
        }
    }
    
    
    $total = count($_FILES['attachments']['name']);
    for($i=0 ; $i < $total ; $i++ ) {
        $tmpFilePath = $_FILES['attachments']['tmp_name'][$i];
        if ($tmpFilePath != ""){
            $newFilePath = "./uploads/" . $_FILES['attachments']['name'][$i];
            if(move_uploaded_file($tmpFilePath, $newFilePath)) {
                $random=random();
                $image=$_FILES['attachments']['name'][$i];
                $query="insert into darlelJobber_task_images set id='$random',taskId='$taskId',addedBy='$session_id',image='$image',timeAdded='$timeAdded'";
                runQuery($query);
                
                if($updateQuoteNotes && $notesId!="None"){
                    $random=random();
                    $query="insert into darlelJobber_notes_images set id='$random',notesId='$notesId',image='$image'";
                    runQuery($query);
                }
            }
        }
    }
    
    $displayUser=clear($_GET['userId']);
    $redirectionString="";
    if(isset($_GET['quoteId'])){
        $quoteId=clear($_GET['quoteId']);
        $redirection="createQuote.php?entryId=$quoteId&m=Reminder Has Been Created Successfully";
        $redirectionString="&redirectionPage=createQuote.php&redirectionValue=$quoteId&redirectionVar=entryId";
    }
    else if(isset($_GET['customerId'])){
        $customerId=clear($_GET['customerId']);
        $redirection="view_client.php?id=$customerId&m=Reminder Has Been Created Successfully";
        $redirectionString="&redirectionPage=view_client.php&redirectionValue=$customerId&redirectionVar=id";
    }
    else if(isset($_GET['jobId'])){
        $jobId=clear($_GET['jobId']);
        $redirection="createJob.php?entryId=$jobId&m=Reminder Has Been Created Successfully";
        $redirectionString="&redirectionPage=createJob.php&redirectionValue=$jobId&redirectionVar=entryId";
    }
    else if(isset($_GET['redirectionVar'])){
        $redirectionVar=clear($_GET['redirectionVar']);
        $redirectionValue=clear($_GET['redirectionValue']);
        $redirectionPage=clear($_GET['redirectionPage']);
        $redirectionString="something";
        $redirection="$redirectionPage?$redirectionVar=$redirectionValue&m=Reminder Has Been Created Successfully";
    }
    
    header("Location:./detailedTaskView.php?taskId=$taskId&m=Data has been updated successfully");
    /*if($actionId=="" && $label=="Job Error")
        header("Location:./createJob.php?entryId=$jobId&m=Job Error has been made successfully");
    else if($redirectionString=="" && (!isset($_GET['redirectionVar']))){
        header("Location:./detailedTaskView.php?taskId=$taskId&m=Data has been updated successfully");
        //header("Location:?m=Task data has been updated successfully&filter=$filter&label=$labelFilter&userId=$displayUser&startDate=$startDate&endDate=$endDate");
    }
    else if($redirectionString!="")
        header("Location:./$redirection");*/
    
}

if(isset($_GET['delete-task'])){
    $id = clear($_GET['delete-task']);
    $query="delete from darlelJobber_tasks where id='$id'";
    runQuery($query);
    $query="delete from darlelJobber_task_images where taskId='$id'";
    runQuery($query);
    
    header("Location:?m=Task has been deleted successfully");
}

if(isset($_GET['completed'])){
    $id = clear($_GET['completed']);
    $query="update darlelJobber_tasks set status='Completed' where id='$id' ";
    runQuery($query);
    
    $title="Task Completed";
    $description="A task has been completed successfully . Click To View";
    $url=$projectUrl."detailedTaskView.php?taskId=$id";
    
    $team=getAll($con,"select userId from darlelJobber_teams where taskId='$id'");
    $taskDeets=getRow($con,"select * from darlelJobber_tasks where id='$id'");
    
    $newMember = ["userId" => $taskDeets['addedBy']];
    $team[] = $newMember;
    
    
    foreach($team as $row)
        setNotification($title,$description,$row['userId'],$url);
    
    
    header("Location:?m=Task has been marked as completed successfully");
}

/*if(isset($_GET['removeImage'])){
    $id = clear($_GET['removeImage']);
    $query="delete from darlelJobber_task_images where id='$id' ";
    runQuery($query);
    header("Location:?m=Task Image has been deleted successfully");
}*/


$displayUser=clear($_GET['userId']);
if(!isset($_GET['userId'])){
    $displayUser=$session_id;
    header("Location:?userId=$session_id");
    exit();
}

$startDate=strtotime($startDate);
$endDate=strtotime($endDate);


if($filter=="All Tasks In The System")
    $queryTasks="select * from darlelJobber_tasks where label like '%$labelFilter%' and timeAdded between $startDate and $endDate order by completionDate asc";
else if($filter=="All"){
    $queryTasks="select DISTINCT t.* from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where ( t.addedBy='$displayUser' or tt.userId='$displayUser' )
    and label like '%$labelFilter%' and t.timeAdded between $startDate and $endDate order by t.completionDate asc";
}
else if($filter=="Assigned Reminders" || (!isset($_GET['filter'])) || ($filter=="")){
    $filter="Assigned Reminders";
    $queryTasks="select DISTINCT t.* from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where tt.userId='$displayUser' 
    and label like '%$labelFilter%' and status!='Completed' and t.timeAdded between $startDate and $endDate order by t.completionDate asc";
}
else if($filter=="Sent Tasks")
    $queryTasks="select DISTINCT t.* from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId 
    where t.addedBy='$displayUser' and label like '%$labelFilter%' and status!='Completed' and t.timeAdded between $startDate and $endDate order by t.completionDate asc";
else if($filter=="Completed"  || $filter=="Due Soon")
    $queryTasks="select DISTINCT t.* from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where ( t.addedBy='$displayUser' or tt.userId='$displayUser' )
    and t.status='$filter' and label like '%$labelFilter%' and t.timeAdded between $startDate and $endDate order by t.completionDate asc";
else if($filter=="Over Due")
    $queryTasks="select DISTINCT t.* from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where ( t.addedBy='$displayUser' or tt.userId='$displayUser' )
    and t.status='$filter' and label like '%$labelFilter%' order by t.completionDate asc";
else if($filter=="Tasks To Do")
    $queryTasks="SELECT DISTINCT tasks.* FROM darlelJobber_tasks tasks LEFT JOIN darlelJobber_teams teams ON tasks.id = teams.taskId
WHERE (tasks.addedBy = '$displayUser' OR teams.userId = '$displayUser') and status!='Completed' and label like '%$labelFilter%' and tasks.timeAdded between $startDate and $endDate";
else if($filter=="Comment Unanswered"){
    $queryTasks="select DISTINCT t.* from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where ( t.addedBy='$displayUser' or tt.userId='$displayUser' )
    and label like '%$labelFilter%' and t.timeAdded between $startDate and $endDate order by t.completionDate asc";
}
?>

<html lang="en">
	<head>
	    <?require("./includes/views/head.php");?>
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <style>
            .ui-menu {
                z-index: 100000 !important;
            }
        </style>
        
        <link href="includes/autocompletecss.css" rel="stylesheet" type="text/css"/>
        
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
							    
							    
					            <div class="row">
					                <div class="col-12">
					                    <div class="row">
					                        <div class="col-md-2 col-6 text-center mt-1 mb-1">
        					                    <h3>Start Date</h3>
                                                <input type="date" name="startDate"  value="<?echo date("Y-m-d",$startDate)?>"  class="btn btn-primary text-white w-100" onchange="submitForm()">
                                            </div>
                                            <div class="col-md-2 col-6 text-center mt-1 mb-1 ">
        					                    <h3>End Date</h3>
                                                <input type="date" name="endDate"  value="<?echo date("Y-m-d",$endDate)?>"  class="btn btn-primary text-white w-100" onchange="submitForm()">
                                            </div>
                                            <div class="col-md-<?echo ($session_role=="Admin") ? 2 : 3;?> col-6 text-center mt-1 mb-1 ">
        					                    <h3>Label Filter</h3>
        					                    <select class="btn btn-primary w-100" onchange="submitForm()" name="labelFilter">
                                                    <?foreach($labels as $row){?>
                                                    <option <?if(($row['title']==$labelFilter) || ($labelFilter=="" && $row['title']=="All")){echo "selected ";}?> value="<?echo $row['title']?>">
                                                        <?echo $row['title'];?>
                                                    </option>
                                                    <?}?>
                                                </select>  
                                            </div>
                                            <div class="col-md-<?echo ($session_role=="Admin") ? 2 : 3;?> col-6 text-center mt-1 mb-1 ">
        					                    <h3>Reminder Type </h3>
        					                    <select name="filter" class="btn btn-primary w-100"  onchange="submitForm()" >
                                                    <?$filters=["All","Assigned Reminders","Sent Tasks","Completed","Due Soon","Over Due","Tasks To Do","Comment Unanswered"];
                                                    if($session_role=="Admin")
                                                        $filters[]="All Tasks In The System";
                                                    foreach($filters as $row){?>
                                                    <option <?if($row==$filter){echo "selected ";}?> value="<?echo $row?>">
                                                        <?echo $row?>
                                                    </option>
                                                    <?}?>
                                                </select>
                                            </div>
                                            <?if($session_role=="Admin"){?>
    										<div class="col-md-2 col-6 text-center mt-1 mb-1 ">
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
                                            <div class="col-md-2 col-6 text-center mt-1 mb-1">
        					                    <h3>Add Reminder </h3>
    					                        <a id="addTaskBtn" href="#" data-bs-toggle="modal" data-bs-target="#add_task" class="btn btn-primary w-100">Add Reminder</a>
                                            </div>
				                        </div>
					                </div>
							    </div>
							    <div class="card card-flush mb-10">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Reminders" />
											</div>
										</div>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-row-bordered border gs-7 text-center" id="kt_ecommerce_category_table">
										    <thead>
										        <tr>
										            <th >Purpose</th>
										            <th>Title</th>
										            <th>Completion Date</th>
										            <th>Timing</th>
										            <th>Team</th>
										            <th class="text-center">Actions</th>
										        </tr>
										    </thead>
										    <tbody>
										        <?
									            
										        $tasks=getAll($con,$queryTasks);
										        foreach($tasks as $row){
									            $taskId=$row['id'];
										        
										        
									            //checking whether to mark it as red or not (if the user has not read the comments/file upload)
								                $commentRead=0;
								                $query="select * from darlelJobber_task_comment_status where taskId='$taskId' && status='Not Read' && userId='$displayUser'";
									            $result=runQueryReturn($query);
									            if(mysqli_num_rows($result)==0)
									                $commentRead=1;
								                
								                //checking if this task is due soon in order to update its status
										        if($row['status']!="Completed" && $row['status']!="Due Soon" && $row['status']!="Over Due"){
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
										        }
										        
										        $tempCompletionDate=$row['completionDate'];
										        $row['completionDate']=date("Y-m-d",$row['completionDate']);
										        $row['reminderDate']=date("Y-m-d",$row['reminderDate']);
										        ?>
										        <a id="edit<?echo $row['id']?>" class="d-none" href="#" data-bs-toggle="modal" data-bs-target="#add_task" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
											    <tr <?if($filter=="Comment Unanswered" && $commentRead){echo "class='d-none'";}?> style="<?if(!$commentRead){echo "background-color: #ff7d89 !important;";}?>">
										            <td >
										                <?
										                if($row['searchBy']=="Client"){
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
										                <a class="badge badge-<?echo $colorStatus?> btn-sm" style="margin-left: 5px;"><?echo $row['status']?></a>
									                    <a class="badge badge-primary btn-sm" style="margin-left: 5px;background-color:<?echo $labelNameToColor[$row['label']]?>!important"><?echo $row['label']?></a>
										                
									                </td>
										            <td><?echo $row['title']?></td>
										            <td><?echo date("d M y",$tempCompletionDate);?></td>
										            <td><?echo date("h:i A",strtotime($row['start_time']))." - ".date("h:i A",strtotime($row['end_time']))?></td>
										            <td>
										                <?$team=$taskIdToTeam[$taskId];
										                foreach($team as $nrow){
										                    $name=$idToInfo[$nrow]['name'];
										                    $btn="<p class='badge badge-light-success btn-sm' style='margin-right:3px;'>$name</p>";
										                    echo $btn;
										                }?>
										            </td>
										            <td class="text-center">
										                <div class="btn-group">
    										                <a href="detailedTaskView.php?taskId=<?echo $row['id']?>" class="btn btn-success btn-sm" >Detailed View</a>
    													    <?if($row['status']!="Completed"){?>
    										                <a class="btn btn-primary btn-sm" href="?completed=<?echo $row['id']?>&filter=<?echo $filter?>">Mark As Completed</a>
    													    <?if($row['label']!="Shop" && $row['label']!="Visit"){?>
    													    <a class="btn btn-warning btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#add_task" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
											                <?}
											                if($row['addedBy']==$session_id){?>
											                <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-task=<?echo $row['id']?>">Delete</a>
    											            <?}}?>
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
		
		    <script src="assets/js/scripts.bundle.js"></script>
		    <div class="modal fade" tabindex="-1" id="delete_record">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Entry</h5>
            
                            <!--begin::Close-->
                            <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                                <span class="svg-icon svg-icon-2x"></span>
                            </div>
                            <!--end::Close-->
                        </div>
            
                        <div class="modal-body">
                            <p>Are You Sure You Want To Delete This Entry ?</p>
                        </div>
            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <a href="#sd" id="delete-project" class="btn btn-danger">
                                Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            $("#delete_record").on('show.bs.modal', function (e) {
                var url = $(e.relatedTarget).data('url');
                console.log("modal opened", name)
                $("#delete-project").attr("href", url);
            });
            </script>
            
		</div>
	</body>
	<div class="modal fade" id="add_task" tabindex="-1" aria-hidden="true">


			
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
							            <option value="Job">Job</option>
							        </select>
							    </div>
							    <div class="col-8">
							        <label class="d-flex align-items-center fs-6 fw-bold mb-2">Search Criteria</label>
							        <select name="linkedId" id="linkedId" class="form-select" data-control="select2" data-placeholder="Select an option" style="height: 40px;" required>
							        </select>
							    </div>
							</div>
							 
							<div class="d-flex flex-column mb-8 fv-row">
								<div class="row">
								    <div class="col-md-4 col-12">
        								<label class="d-flex align-items-center fs-6 fw-bold mb-2"><span class="required">Subject</span></label>
        								<input onfocusout="updateLabel()" id="subject" type="text" name="title" class="form-control" placeholder="Enter Subject" required>
								    </div>
								    <div class="col-md-3 col-6">
        								<div class="form-check mt-11">
                                            <input onchange="updateReminderView()" name="needReminder" class="form-check-input" type="checkbox" />
                                            <label class="form-check-label">Need Reminder ? </label>
                                        </div>
								    </div>
								    <div class="col-md-5 col-6 mt-3" id="reminderDate">
    									<label class="d-flex align-items-center fs-6 fw-bold mb-2"><span class="required">Reminder Date</span></label>
        								<input type="date" name="reminderDate" class="form-control" required>
								    </div>
								</div>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Comments</span>
								</label>
								<textarea class="form-control" name="description" placeholder="Enter Comments" ></textarea>
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
							<div class="row mb-5">
							    
							    <div class="col-sm-12 col-md-6 col-12 mb-5">
						        	<label class="d-flex align-items-center fs-6 fw-bold mb-2">Start Time</label>
    								<input type="time" name="start_time" class="form-control" required>
    							</div>
    							<div class="col-sm-12 col-md-6 col-12 mb-5">
						        	<label class="d-flex align-items-center fs-6 fw-bold mb-2">End Time</label>
    								<input type="time" name="end_time" class="form-control" required>
    							</div>
							    
							    <div class="col-sm-12 col-md-4 col-xl-4 col-lg-4 col-12">
						        	<label class="d-flex align-items-center fs-6 fw-bold mb-2">Due Date</label>
    								<input type="date" name="completionDate" class="form-control" required>
    							</div>
							    <div class="col-sm-12 col-md-4 col-xl-4 col-lg-4 col-12">
    					        	<label class="d-flex align-items-center fs-6 fw-bold mb-2">Label</label>
    						        <select name="label" class="form-control" >
    						            <?foreach($labels as $row){?>
    						            <option value="<?echo $row['title']?>"><?echo $row['title']?></option>
    						            <?}?>
    						        </select>
						        </div>
						        <div class="col-sm-12 col-md-4 col-xl-4 col-lg-4 col-12">
					            	<label class="d-flex align-items-center fs-6 fw-bold mb-2">Attachments</label>
							        <input type="file" name="attachments[]" class="form-control" multiple>
							    </div>
							</div>
							
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input type="submit" value="Save" name="addTask" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
		
		var users=<?echo json_encode($users);?>;
		var quotes=<?echo json_encode($quotes);?>;
		var jobs=<?echo json_encode($jobs);?>;
		var idToInfo=<?echo json_encode($idToInfo);?>;
		var taskIdToTeam=<?echo json_encode($taskIdToTeam);?>;
		
		var availableTags = [
        <?foreach($labels as $row){?>
          `<?echo $row['title']?>`,
          <?}?>
        ];
        $("#subject").autocomplete({
          source: availableTags,
          minLength: 0  // Ensure autocomplete shows on focus even with no input
        }).on('focus', function() {
          $(this).autocomplete("search", "");
        });
		
		function updateLabel(){
		    var subject=$("input[name='title']").val();
		    $("select[name='label']").val(subject);
		}
		
		function updateReminderView(){
	        $('#reminderDate').hide();
	        var needReminder= $("input[name='needReminder']").is(':checked');
            needReminder = (needReminder) ? "on" : "off";
            if(needReminder=="on")
                $('#reminderDate').show();
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
        
        $('#add_task').modal({
          backdrop: 'static',
          keyboard: false
        });
        
        $("#assignedMember").select2({
            dropdownParent: $("#add_task")
        });
        changeLinkedId();
                
        
        
        $("#add_task").on('show.bs.modal', function (e) {
            
            var mydata = $(e.relatedTarget).data('mydata');
            $("input[name='needReminder']").prop("checked", false);
                
            if(mydata!= null){
                $("#modelTitle").html("Edit Reminder");
                
                $("select[name='searchBy']").val(mydata['searchBy']);
                $("select[name='label']").val(mydata['label']);
                $("input[name='completionDate']").val(mydata['completionDate']);
                $("input[name='title']").val(mydata['title']);
                $("textarea[name='description']").val(mydata['description']);
                $("input[name='actionId']").val(mydata['id']);
                $("input[name='start_time']").val(mydata['start_time']);
                $("input[name='end_time']").val(mydata['end_time']);
                $("input[name='reminderDate']").val(mydata['reminderDate']);
                if(mydata['needReminder']=="Yes")
                    $("input[name='needReminder']").prop("checked", true);
                
                changeLinkedId();
                var select = $('#linkedId').select2();
                var linkedId = (mydata['searchBy']=="Client") ? mydata['customerId'] : mydata['quoteId'];
                var linkedId="";
                if(mydata['searchBy']=="Client")
                    linkedId=mydata['customerId'];
                else if(mydata['searchBy']=="Quote")
                    linkedId=mydata['quoteId'];
                else if(mydata['searchBy']=="Job")
                    linkedId=mydata['jobId'];
                
                select.val(linkedId);
                select.trigger('change');
                
                $('#teamMembers').empty();
                var taskId=mydata['id'];
                var currentTeam=taskIdToTeam[taskId];
                if(currentTeam != null){
                    for(var i=0;i<currentTeam.length;i++)
                        updateTeam(currentTeam[i]);
                }
                
                updateReminderView();
            }
            
            else{
                updateReminderView();
                
                $('#teamMembers').empty();
                updateTeam("<?echo $session_id?>");
                $("#modelTitle").html("Add Reminder");
                $("select[name='searchBy']").val("Quote");
                changeLinkedId();
                
                $("input[name='start_time']").val("08:00");
                $("input[name='end_time']").val("10:00");
                $("input[name='title']").val("");
                $("textarea[name='description']").val("");
                $("input[name='actionId']").val("");
                $("select[name='label']").val("");
                $("input[name='completionDate']").val("<?echo date("Y-m-d",time());?>");
                $("input[name='reminderDate']").val("<?echo date("Y-m-d",time());?>");
                
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
                <?}else if(isset($_GET['callLogId'])){
		            $callLogId=clear($_GET['callLogId']);
	                $callLogDeets=getRow($con,"select * from darlelJobber_call_logs where id='$callLogId'");
	                $linkedId = ($callLogDeets['searchBy']=="Quote") ? $callLogDeets['quoteId'] : $callLogDeets['customerId'];?>
                    $("select[name='searchBy']").val("<?echo $callLogDeets['searchBy']?>");
    		        changeLinkedId();
    		        $("select[name='linkedId']").val("<?echo $linkedId?>");
    		        $('#linkedId').trigger('change');
                <?}else if(isset($_GET['jobId'])){
                    $jobId=clear($_GET['jobId']);?>
    		        $("select[name='searchBy']").val("Job");
    		        changeLinkedId();
    		        $("select[name='linkedId']").val("<?echo $jobId?>");
    		        $('#linkedId').trigger('change');
    		        <?if(isset($_GET['error'])){?>
    		        $("select[name='label']").val("Job Error");
    		        $("textarea[name='description']").val("Explain what the issue is ");
    		        //estimator of the quote already selected when job error created
    		        updateTeam("<?echo $jobIdToEstimator[$jobId]?>");
    		        //assigning crew supervisor
    		        updateTeam("294P1959FB");
    		    <?}}?>
            }
        });
        
        <?if(isset($_GET['quoteId']) || isset($_GET['jobId']) || isset($_GET['customerId']) || isset($_GET['callLogId'])){?>
        $("#addTaskBtn")[0].click();
        <?}?>
	    
		 
	    <?if(isset($_GET['editTask'])){
            $newTaskId=clear($_GET['editTask']);?>
            $("#edit<?echo $newTaskId?>")[0].click();
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
	        else if(searchBy=="Job"){
	            for(var i=0;i<jobs.length;i++){
                    var clientName="";
	                if (idToInfo[jobs[i]['customerId']])
                        clientName = idToInfo[jobs[i]['customerId']]['fullName'];
                    var option="#"+jobs[i]['job_number']+" "+clientName;
                    var newOption = new Option(option, jobs[i]['id']);
                    selectLinkedId.append(newOption);
                }
	        }
	        $("#linkedId").select2({
                dropdownParent: $("#add_task")
            });
            selectLinkedId.trigger('change');
	    }
	</script>
</html>