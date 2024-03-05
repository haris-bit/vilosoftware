<?
require_once '_db.php';

$isVisit = (isset($_GET['visitId'])) ? 1 : 0;
$visitId = $_GET['visitId'];
$users=getAll($con,"select * from darlelJobber_users where role!='Client'");

if($isVisit){
    $jobId=$_GET['jobId'];
    $visitDeets=getRow($con,"select * from darlelJobber_visits where id='$visitId'");
    $jobId=$visitDeets['jobId'];
    $quoteDeets=getRow($con,"select * from darlelJobber_quotes q inner join darlelJobber_jobs j on q.jobId=j.id  where j.id='$jobId'");
    
    $selectedTeam=[];
    $visitTeam=getAll($con,"select * from darlelJobber_teams where visitId='$visitId'");
    foreach($visitTeam as $row)
        $selectedTeam[]=$row['userId'];
}

if(isset($_POST['updateVisit'])){
    $visitId=$_POST['visitId'];
    $jobId=$_POST['jobId'];
    $title=$_POST['title'];
    $description=$_POST['description'];
    
    $query="update darlelJobber_visits set title='$title',description='$description' where id='$visitId'";
    runQuery($query);
    $taskId=getRow($con,"select * from darlelJobber_visits where id='$visitId'")['taskId'];
    if($taskId=="None")
        $taskId="random";
    //adding team members for this request
    $newteam=$_POST['team'];
    $oldteam=$selectedTeam;
    
    //adding team members started
    foreach($users as $row){
        $userId=$row['id'];
        if((in_array($userId,$newteam)) && (!in_array($userId,$oldteam))){
            //adding this person in the visit team
            $random=random();
            $query="insert into darlelJobber_teams set id='$random',visitId='$visitId',jobId='$jobId',userId='$userId',timeAdded='$timeAdded'";
            runQuery($query);
            
            //adding this person in the visit task team
            if($taskId!="random"){
                $random=random();
                $query="insert into darlelJobber_teams set id='$random',taskId='$taskId',userId='$userId',timeAdded='$timeAdded'";
                runQuery($query);
            }
        }
        else if((!in_array($userId,$newteam)) && (in_array($userId,$oldteam))){
            if($taskId!="random")
                $query="delete from darlelJobber_teams where userId='$userId' && (visitId='$visitId' or taskId='$taskId' )";
            else
                $query="delete from darlelJobber_teams where userId='$userId' && visitId='$visitId'";
            runQuery($query);
        }
    }
    //adding team members finished
    header("Location:?visitId=$visitId&jobId=$jobId");
}

?>
<!DOCTYPE html>
<html>

    <head>
        <?include("../includes/views/calendarHead.php");?>
	</head>

    <body>
    <form action="" method="post" enctype="multipart/form-data" style="padding: 30px;">
    	<div class="mb-13 text-left">
            <div class="row">
                <div class="col-7">
                    <h1 class="mb-3"><?if($isVisit){echo "Visit Details";}?></h1>
                </div>
                <div class="col-5">
                    <h1 class="mb-3">Quote Number : <?echo "#".$quoteDeets['quote_number']?></h1>
                </div>
            </div>
        </div>
    	
    	<div class="d-flex flex-column mb-8 fv-row">
    		<label class="d-flex align-items-center fs-6 fw-bold mb-2">Visit Title</label>
    		<input type="text" class="form-control form-control-solid" placeholder="Enter Visit Title" name="title"  value="<?echo $visitDeets['title'] ?>" required />
    	</div>
    	
    	<div class="d-flex flex-column mb-8 fv-row">
    		<label class="d-flex align-items-center fs-6 fw-bold mb-2">Visit Description</label>
    		<input type="text" class="form-control form-control-solid" placeholder="Enter Description" name="description"  value="<?echo $visitDeets['description'] ?>" />
    	</div>
    	
    	<div class="d-flex flex-column mb-8 fv-row">
    		<label class="d-flex align-items-center fs-6 fw-bold mb-2">Visit Team</label>
    		<select name="team[]" class="form-select form-select-solid" data-control="select2" 
			data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                <?foreach($users as $row){?>
                <option <?if(in_array($row['id'],$selectedTeam)){echo "selected";}?> 
                value="<?echo $row['id']?>"><?echo $row['name']?></option>
                <?}?>
            </select>
    	</div>
    	<input type="text" name="visitId" value="<?echo $visitId?>" hidden>
    	<input type="text" name="jobId" value="<?echo $jobId?>" hidden>
    	<div class="text-center">
    	    <a class="btn btn-warning" target="_top" href="../createJob.php?entryId=<?echo $jobId?>">Edit Job</a>
            <input type="submit" value="Save Changes" name="updateVisit" class="btn btn-primary">
    	</div>
    </form>
    <?include("../includes/views/calendarFooterJs.php");?>
	
    </body>
</html>

