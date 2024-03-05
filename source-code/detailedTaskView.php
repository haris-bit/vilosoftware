<?
require("./global.php");
$previousPage = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

if($logged==0 || (!isset($_GET['taskId'])))
    header("Location:./index.php");

$taskId=clear($_GET['taskId']);
$taskDeets=getRow($con,"select * from darlelJobber_tasks where id='$taskId'");

$query="delete from darlelJobber_task_comment_status where taskId='$taskId' and userId='$session_id'";
runQuery($query);

$accessMode=($session_id==$taskDeets['addedBy']) ? 1 : 0;
$taskImages=getAll($con,"select * from darlelJobber_task_images where taskId='$taskId'");
$taskTeam=getAll($con,"select * from darlelJobber_teams where taskId='$taskId'");
$commentQuery="SELECT *,
  CONCAT(
    IF(DATEDIFF(NOW(), FROM_UNIXTIME(timeAdded)) > 0, CONCAT(DATEDIFF(NOW(), FROM_UNIXTIME(timeAdded)), ' days ago'),
      IF(TIMESTAMPDIFF(HOUR, FROM_UNIXTIME(timeAdded), NOW()) > 0, CONCAT(TIMESTAMPDIFF(HOUR, FROM_UNIXTIME(timeAdded), NOW()), ' hours ago'),
        CONCAT(TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(timeAdded), NOW()), ' minutes ago')
      )
    )
  ) AS timeAgo
FROM darlelJobber_task_comments where taskId='$taskId' order by timeAdded desc";
$comments=getAll($con,$commentQuery);


$users=getAll($con,"select *,concat(street1,',',street2,',',city,',',state,',',country,', Zip : ',zip_code) as fullAddress,concat(first_name,' ',last_name) as fullName from darlelJobber_users");
$idToInfo=[];
foreach($users as $row){
    $idToInfo[$row['id']]=$row;    
}

$labels=getAll($con,"select * from darlelJobber_labels order by timeAdded desc");
$labelNameToColor=[];
foreach($labels as $row){
    $labelNameToColor[$row['title']]=$row['colorCode'];
}

if(isset($_GET['label'])){
    $label=clear($_GET['label']);
    $query="update darlelJobber_tasks set label='$label' where id='$taskId'";
    runQuery($query);
    header("Location:?m=Label Updated Successfully&taskId=$taskId");
}

$quotes=getAll($con,"select * from darlelJobber_quotes");
foreach($quotes as $row){
    $idToInfoQuotes[$row['id']]=$row;
}

if(isset($_GET['completed'])){
    $id = clear($_GET['completed']);
    $query="update darlelJobber_tasks set status='Completed' where id='$id' ";
    runQuery($query);
    
    //sending notification to everyone assigned to that task and the creator of that task
    $title="Task Completed";
    $description="A task has been completed successfully . Click To View";
    $url=$projectUrl."detailedTaskView.php?taskId=$id";
    
    $team=getAll($con,"select userId from darlelJobber_teams where taskId='$id'");
    $newMember = ["userId" => $taskDeets['addedBy']];
    $team[] = $newMember;
    
    foreach($team as $row)
        setNotification($title,$description,$row['userId'],$url);
    
    header("Location:?taskId=$id");
}

if(isset($_GET['disableFollowUp'])){
    $quoteId=clear($_GET['disableFollowUp']);
    runQuery("update darlelJobber_quotes set automatedTask='Disabled' where id='$quoteId'");
    header("Location:?taskId=$taskId");
}

if(isset($_GET['rejectQuote'])){
    $quoteId=clear($_GET['rejectQuote']);
    $query="update darlelJobber_quotes set approveStatus='Rejected' where id='$quoteId'";
    runQuery($query);
    header("Location:?taskId=$taskId&m=Quote has been moved to closed lost");
    exit();
}

?>

<html lang="en">
	<head>
	    <link rel="stylesheet" href="lightbox/dist/css/lightbox.min.css">
        <?require("./includes/views/head.php");?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.js"></script>
    </head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed"
	style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div class="container-xxl" style="max-width: 100%;">
						    
						    <?if(isset($_GET['m'])){?>
						    <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                    <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?$m=clear($_GET['m']);echo $m?></h4>
                                </div>
                            </div>
                            <?}?>
							    
							    
							<form method="post" enctype="multipart/form-data">
							    <div class="card shadow-sm mb-15 mt-5">
                                    <div class="card-header">
                                        <?
						                if($taskDeets['shopOrderId']!="None"){
						                    $text="Shop";
						                    $shopOrderId=$taskDeets['shopOrderId'];
						                    $selectedDate=date("Y-m-d",$taskDeets['completionDate']);
						                    if($permission['view_jobs']){
    						                    $jobId=getRow($con,"select * from darlelJobber_shop_orders where id='$shopOrderId'")['jobId'];
    						                    if($jobId=="")//means that this order was created from shop schedule page 
					                                $link="shopSchedule.php?selectedDate=$selectedDate";
					                            else
    						                        $link="createJob.php?entryId=".$jobId;
						                    }
						                    else
						                        $link="detailedTaskView.php?taskId=$taskId";
						                }
						                else if($taskDeets['jobId']!="NULL3"){
						                    $text="Job Visit";
						                    $jobId=$taskDeets['jobId'];
						                    if($permission['view_jobs'])
    						                    $link="createJob.php?entryId=".$jobId;
						                    else
						                        $link="detailedTaskView.php?taskId=$taskId";
						                }
						                
						                else if($taskDeets['searchBy']=="Client"){
						                    $text=$idToInfo[$taskDeets['customerId']]['fullName'];
						                    $link = ($permission['view_client']) ? "view_client.php?id=".$taskDeets['customerId'] : "detailedTaskView.php?taskId=$taskId";
						                }
						                else{
						                    $text="#".$idToInfoQuotes[$taskDeets['quoteId']]['quote_number']." ".$idToInfo[$idToInfoQuotes[$taskDeets['quoteId']]['customerId']]['fullName'];
					                        $link = ($permission['view_quotes']) ? "viewQuote.php?entryId=".$taskDeets['quoteId'] : "detailedTaskView.php?taskId=$taskId";
						                    if($permission['view_quotes'])
						                        $link="viewQuote.php?entryId=".$taskDeets['quoteId'];
						                }?>
						                <h3 class="card-title">
				                            <a href="<?echo $link?>">Reminder Linked To : <?echo $text?></a>
                                        </h3>
                                        
                                        
                                        <div class="card-toolbar">
                                            
                                            <a class="btn btn-warning btn-sm" style="margin-right: 5px;" href="tasks.php?userId=<?echo $session_id?>&editTask=<?echo $taskId?>">Edit</a>
                                            <a class="btn btn-success btn-sm" style="margin-right: 5px;" href="<?echo $previousPage?>">Go Back</a>
                                            
                                            <!--disable constant follow up tasks-->
                                            <?
                                            $quoteDeets=$idToInfoQuotes[$taskDeets['quoteId']];
                                            $quoteId=$quoteDeets['id'];
                                            $commentBeforeSubmit=0;
                                            if($quoteDeets['automatedTask']=="Enabled" && $taskDeets['label']=="Follow Up"){
                                            $commentBeforeSubmit=1;
                                            $message="'Kindly confirm that you have commented the reason of disabling the follow up in the comment section'";
                                            $onclick='onclick="return confirm('.$message.')"';
                                            ?>
                                            
                                            <a <?if($commentBeforeSubmit){echo $onclick;}?> class="btn btn-danger btn-sm" style="margin-right: 5px;" href="?taskId=<?echo $taskId?>&disableFollowUp=<?echo $quoteId;?>">Disable Follow Up</a>
                                            
                                            <?}if($taskDeets['status']!="Completed"){
                                            $displayOnClick=0;
                                            if($taskDeets['label']=="Follow Up"){
                                                $displayOnClick=1;
                                                $message="'Kindly confirm that you have provided a comment in the comment section before completing this task '";
                                            }
                                            if($taskDeets['label']=="Approved Quote"){
                                                $displayOnClick=0;
                                                $message="'Kindly confirm that you have created a visit and commented the visit day in the comment section before completing this task'";
                                            }
                                            $onclick='onclick="return confirm('.$message.')"';
                                            ?>
                                            
                                            
                                            <a <?if($displayOnClick){echo $onclick;}?> class="btn btn-primary btn-sm" style="margin-right: 5px;" href="?completed=<?echo $taskId?>">Mark As Completed</a>
                                            <?}?>
                                            
                                            <!--meaning that this task was made for a quote as a follow up and this is not rejected then give the user the option to make it rejected-->
                                            <?if($quoteDeets['approveStatus']!="Rejected" && $quoteDeets!=null){?>
                                            <a class="btn btn-danger btn-sm text-white me-2" href="detailedTaskView.php?taskId=<?echo $taskId."&rejectQuote=".$quoteDeets['id']?>">Rejected Quote</a>
                                            <?}?>
                                            
                                            <select class="btn btn-primary btn-sm" style="margin-right: 5px;background-color:<?echo $labelNameToColor[$taskDeets['label']]?>!important" 
                                            <?if($accessMode || $session_role=="Admin"){?> onchange="location = this.value; <?}?> ">
                                                <option disabled>Change Label To</option>
                                                <?foreach($labels as $row){?>
						                        <option <?if($row['title']==$taskDeets['label']){echo "selected";}?> value="detailedTaskView.php?taskId=<?echo $taskId?>&
						                        label=<?echo $row['title']?>"><?echo $row['title']?></option>
                                                <?}?>
                                            </select>
                                            
                                            <?$colorStatus= ($taskDeets['status']=="New") ? "warning" : "success";?>
                                            <a class="btn btn-<?echo $colorStatus?> btn-sm" style="margin-left: 5px;margin-right: 5px;"><?echo "Status : ".$taskDeets['status']?></a>
                                            <a class="btn btn-primary btn-sm" 
                                            href="callLogs.php?userId=<?echo $session_id?>&taskId=<?echo $taskId;if($taskDeets['label']=="Follow Up"){echo "&followUp=1";}?>">Add Call Log</a>
                                            
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">Title</label>
                                                <p class="mb-5"><?echo $taskDeets['title']?></p>
                                            </div>
                                            <div class="col-12">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">Description</label>
                                                <p class="mb-5"><?echo $taskDeets['description']?></p>
                                            </div>
                                            
                                            <div class="col-sm-12 col-xs-12 col-md-6 col-lg-6 col-xl-6 col-12">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">
                                                    Attachments
                                                    <div class="progress">
                                                      <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="margin-left:10px;"></div>
                                                    </div>
                                                </label>
                                                <div class="d-flex">
                                                    <input type="file" name="attachment" id="attachment" class="form-control mb-5">
                                                    <a id="submitForm" class="btn btn-primary" style="height: 45px;white-space: pre;">Upload Image</a>
                                                </div>
                                                <div id="taskAttachments">
                                                    <?foreach($taskImages as $row){$random=$row['id'];?>
                                                    <p id="<?echo $random?>" style="margin-right:20px;">
                                                        <a class="gallery"  href="uploads/<?echo $row['image']?>">
                                                            <img class="example-image" style="max-height: 4.3755rem;max-width:100px;" src="./uploads/<?echo $row['image']?>" onerror="this.style.display='none'" />
                                                        </a>
                                                        <a class="btn btn-warning btn-sm" onclick="mentionImage('<?echo $random?>')">Comment</a>
        	                                            <?if($row['addedBy']==$session_id){?>
        	                                            <a class="btn btn-danger btn-sm" onclick="remove('<?echo $random?>','image')">Delete</a>
                                                        <?}?>
                                                        <p>Added By : <?echo $idToInfo[$row['addedBy']]['name']?>  Time Added : <?echo date("d M Y",$row['timeAdded'])?></p>
        	                                        </p>
                                            	    <?}?>
                                                </div>
                                            </div>
                                            
                                            <div class="col-sm-12 col-xs-12 col-md-6 col-lg-6 col-xl-6 col-12">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">Team Members</label>
                                                <?if($accessMode || $session_role=="Admin"){?>
                                                <select onchange="updateTeam(this.value)" id="assignedMember" class="form-select mb-5" data-control="select2" data-placeholder="Select an option" 
                                                name="assignedMember" required>
    								                <?foreach($users as $row){
                							            if($row['role']!="Client"){?>
                								        <option value="<?echo $row['id']?>"><?echo $row['name']?></option>
                								    <?}}?>
								                </select>
								                <?}?>
                                                <div id="teamMembers">
                                                    <?foreach($taskTeam as $row){$random=$row['id'];$workerName=$idToInfo[$row['userId']]['name'];?>
                                                    <p id="<?echo $random?>" class="btn btn-light-success btn-sm" style="margin-right:3px;">
                                                        <?echo $workerName;
                                                        if($accessMode || $session_role=="Admin"){?>
                                                        <a onclick="remove('<?echo $random?>','worker')" style="margin-left: 10px;color: red;">X</a>
                                            	        <?}?>
                                            	        <input type="text" name="team[]" value="<?echo $row['userId']?>" hidden>
                                            	    </p>
                                            	    <?}?>
                                        	    </div>
                                            </div>
                                            
                                            
                                            <div class="col-12">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5 mt-5">Comment Section</label>
                                                <div class="btn-group w-100">
                                                    <textarea class="form-control" name="comment" placeholder="Enter Comment" rows="2"></textarea>
                                                    <a onclick="updateComment('addComment','noIdNeeded')" class="btn btn-primary">Submit</a>
                                                </div>
                                                
                                                <!--comments section-->
                                                <div class="card">
                                                   <div class="card-body" id="commentSection" style="padding-left: 0;">
                                                      <?foreach($comments as $row){$random=$row['id'];?>
                                                      <div class="media" id="<?echo $random?>">
                                                            <div class="media-body">
                                                                <h5 class="mt-0"><?echo $idToInfo[$row['addedBy']]['name']?></h5>
                                                                <p><?echo $row['commentText']?></p>
                                                                <small class="text-muted"><?echo $row['timeAgo']?></small>
                                                                <small class="text-muted">Posted on <?echo date("M d ,Y",$row['timeAdded'])?></small>
                                                                <?if($row['addedBy']!=$session_id){?>
                                                                <a onclick="updateComment('replyComment','<?echo $row['addedBy']?>')" style="color:#ffc700;cursor: default;font-size: initial;margin-right:4px;">Reply</a>
                                                                <?}
                                                                //$row['addedBy']==$session_id
                                                                if($session_role=="Admin"){?>
                                                                <a onclick="updateComment('deleteComment','<?echo $random?>')" style="color:red;cursor: default;font-size: initial;">Delete</a>
                                                                <?}?>
                                                            </div>
                                                        <hr>
                                                      </div>
                                                      <?}?>
                                                   </div>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
							</form>
							</div>
						    
						</div>
					</div>
					<?require("./includes/views/footer.php");?>
				</div>
			</div>
			<script src="lightbox/dist/js/lightbox-plus-jquery.min.js"></script>
            <?require("./includes/views/footerjs.php");?>
		
	    </div>
	</body>
	
	
	<script>
	    var idToInfo=<?echo json_encode($idToInfo);?>;
	    
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
		
	    function updateTeam(workerId){
	        var random=makeid(5);
	        var workerName=idToInfo[workerId]['name'];
	        var string=`
	        <p id="`+random+`" class="btn btn-light-success btn-sm" style="margin-right:3px;">
                `+workerName+`
                <a onclick="remove('`+random+`','worker')" style="margin-left: 10px;color: red;">X</a>
    	        <input type="text" name="team[]" value="`+workerId+`" hidden>
    	    </p>
	        `;
	        $('#teamMembers').append(string);
	        
	        $.post("updateTask.php",
            {
                taskId: "<?echo $taskId?>",
                purpose: "addMember",
                workerId: workerId,
                random:random
            },
            function(){
                console.log("Added Member Successfully");
            });
	    }
	    
	    function remove(id,purpose){
	        $('#'+id).fadeOut('slow', function() {
                $(this).remove();
            });
	        if(purpose=="worker"){
    	        $.post("updateTask.php",
                {
                    taskId: "<?echo $taskId?>",
                    purpose: "removeMember",
                    teamEntryId: id,
                },
                function(){
                    console.log("Deleted Member Successfully");
                });
	        }
	        else if(purpose=="image"){
	            $.post("updateTask.php",
                {
                    purpose: "deleteTaskImage",
                    imageEntry: id,
                },
                function(){
                    console.log("Deleted Image Successfully");
                });
	        }
	    }
	    
    //async file upload
    document.getElementById("submitForm").onclick = function() {
    
    var fileInput = document.getElementById("attachment");
    var file = fileInput.files[0];
    var fileName = file.name;
    var random=makeid(5);
    var progressBar = $('.progress-bar');
    progressBar.css('width', '0%');
    
    var formData = new FormData();
    formData.append("attachment", file);
    formData.append("purpose", "fileUpload");
    formData.append("random", random);
    formData.append("taskId", "<?echo $taskId?>");

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "updateTask.php", true);
    xhr.upload.onprogress = function(e) {
      if (e.lengthComputable) {
        var percentComplete = (e.loaded / e.total) * 100;
        percentComplete=percentComplete.toFixed(1);
        percentComplete+=50;
        progressBar.css('width', percentComplete + 'px');
        progressBar.text(percentComplete + '%');
      }
    };

    xhr.onload = function() {
      if (xhr.status === 200) {
        console.log("File uploaded successfully");
        progressBar.css('width', '0%');
        progressBar.text('');
                
        var string=`
            <p id="`+random+`" style="margin-right:20px;">
                <a href="uploads/`+fileName+`" target="_blank">
                    <img class="example-image" style="max-height: 4.3755rem;max-width:100px;" src="./uploads/`+fileName+`" />
                </a>
                <a class="btn btn-warning btn-sm" onclick="mentionImage('`+random+`')">Comment</a>
        	    <a class="btn btn-danger btn-sm" onclick="remove('`+random+`','image')">Delete</a>
        	    <p>Added By : <?echo $idToInfo[$session_id]['name']?>  Time Added : Just Now</p>
            </p>
    	    
        `;
        $('#taskAttachments').append(string);
      } else {
          alert("Error Uploading File");
      }
    };

    xhr.send(formData);
  };
  
    function mentionImage(divId){
        var src = $('#'+divId+' img').attr('src');
        var fileName = src.split('/').pop();
        
        console.log(fileName);
        //var string=`<a class="example-image-link"  href="uploads/`+fileName+`" data-lightbox="example-1">`+fileName+`</a>`;
        var string="*"+fileName+"* ";
        var commentText=$("textarea[name='comment']").val();
        commentText=commentText+" "+string+" ";
        $("textarea[name='comment']").val(commentText);
        $("textarea[name='comment']").focus();
        
    }

    function updateComment(purpose,commentId){
        if(purpose=="addComment"){
            var random=makeid(5);
            var commentText=$("textarea[name='comment']").val();
            
            $.post("updateTask.php",
            {
                taskId: "<?echo $taskId?>",
                random: random,
                purpose: "addComment",
                commentText: commentText,
            },
            function(){
                var workerName=idToInfo;
                var pattern = /\*(.*?)\*/g; // regular expression pattern to match text between * *
                var replacement = "<a class='example-image-link'  href='uploads/$1' data-lightbox='example-1'>$1</a>";
                commentText = commentText.replace(pattern, replacement); // replace all occurrences of pattern with replacement
                
                var string=`
                    <div class="media" id="`+random+`">
                        <div class="media-body">
                            <h5 class="mt-0"><?echo $idToInfo[$session_id]['name']?></h5>
                            <p>
                            `+commentText+`
                            </p>
                            <small class="text-muted">Just Added</small>
                            <small class="text-muted">Posted on <?echo date("M d ,Y",time())?></small>
                            <?if($session_role=="Admin"){?>    
                            <a onclick="updateComment('deleteComment','`+random+`')" style="color:red;cursor: default;font-size: initial;">Delete</a>
                            <?}?>
                        </div>
                        <hr>
                    </div>
                `;
                $(string).hide().prependTo('#commentSection').fadeIn('slow');
                
                console.log("Added Comment Successfully");
                $("textarea[name='comment']").val("");
            });
        }
        
        else if(purpose=="deleteComment"){
            $.post("updateTask.php",
            {
                purpose: "deleteComment",
                commentId: commentId,
            },
            function(){
                console.log("deleted comment");
                $('#'+commentId).fadeOut('slow', function() {
                    $(this).remove();
                });
            });
        }
        else if(purpose=="replyComment"){
            var userId=commentId;//this is the userId of the user who published the comment to which we are replying to
            var commentText=$("textarea[name='comment']").val();
            var userName=idToInfo[userId]['name'];
            commentText=commentText+" @"+userName+"@ ";
            $("textarea[name='comment']").val(commentText);
            $("textarea[name='comment']").focus();
        }
    }
    
    
    document.addEventListener('DOMContentLoaded', function() {
        const gallery = new SimpleLightbox('.gallery', {});
    });
	</script>
</html>