<?
require("./global.php");
$taskId=clear($_POST['taskId']);
$taskDeets=getRow($con,"select * from darlelJobber_tasks where id='$taskId'");
$shopOrderId=$taskDeets['shopOrderId'];
$purpose=clear($_POST['purpose']);
$teamEntryId=clear($_POST['teamEntryId']);
$workerId=clear($_POST['workerId']);
$random=clear($_POST['random']);
$timeAdded=time();

$imageEntry=clear($_POST['imageEntry']);

$updateTaskCommentStatus=0;

if($purpose=="removeMember"){
    //getting the userId and removing from shop order team if this task is linked to shop
    if($taskDeets['shopOrderId']!="None"){
        $shopOrderId=$taskDeets['shopOrderId'];
        $userId=getRow($con,"select userId from darlelJobber_teams where id='$teamEntryId'")['userId'];
        runQuery("delete from darlelJobber_teams where shopOrderId='$shopOrderId' and userId='$userId'");
    }
    //if this task is attached to a visit then this member will be removed from visit as well
    if($taskDeets['visitId']!="None"){
        $visitId=$taskDeets['visitId'];
        $userId=getRow($con,"select userId from darlelJobber_teams where id='$teamEntryId'")['userId'];
        runQuery("delete from darlelJobber_teams where visitId='$visitId' and userId='$userId'");
    }
    runQuery("delete from darlelJobber_teams where id='$teamEntryId'");
}
else if($purpose=="addMember"){
    $query="insert into darlelJobber_teams set id='$random',userId='$workerId',taskId='$taskId'";
    runQuery($query);
    
    //if this task is attached to a shop order then add in shop order as well    
    if($taskDeets['shopOrderId']!="None"){
        $random=random();
        $query="insert into darlelJobber_teams set id='$random',userId='$workerId',shopOrderId='$shopOrderId'";
        runQuery($query);
    }
    //if this task is attached to a visit
    if($taskDeets['visitId']!="None"){
        $visitId=$taskDeets['visitId'];
        $random=random();
        $query="insert into darlelJobber_teams set id='$random',userId='$workerId',visitId='$visitId'";
        runQuery($query);
    }
    $title="Assigned To a Task";
    $description="You have been assigned a task . Click To View";
    $url=$projectUrl."detailedTaskView.php?taskId=$taskId";
    setNotification($title,$description,$workerId,$url);
}
else if($purpose=="fileUpload"){
    $target_dir = "./uploads/";
    $target_file = $target_dir . basename($_FILES["attachment"]["name"]);
    
    $updateNotes=0;
    if($taskDeets['quoteId']!="None"){
        $quoteId=$taskDeets['quoteId'];
        $notesId=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'")['notesId'];
        if($notesId!="None")
            $updateNotes=1;
    }
    
    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
      $sameEntryId=$random;
      $image=htmlspecialchars( basename( $_FILES["attachment"]["name"]));
      $query="insert into darlelJobber_task_images set id='$sameEntryId',addedBy='$session_id',image='$image',taskId='$taskId',timeAdded='$timeAdded'";
      runQuery($query);
      if($shopOrderId!="None"){
          $query="insert into darlelJobber_shop_images set id='$sameEntryId',addedBy='$session_id',image='$image',title='$image',shopOrderId='$shopOrderId',timeAdded='$timeAdded'";
          runQuery($query);
      }
        //if this task is related to a quote and that quoteId attachment notesId is not none then add this into that notes attachments
        if($updateNotes){
            $random=random();
            $query="insert into darlelJobber_notes_images set id='$random',notesId='$notesId',image='$image'";
            runQuery($query);
        }
  
    }
    $updateTaskCommentStatus=1;
}
else if($purpose=="deleteTaskImage"){
    runQuery("delete from darlelJobber_task_images where id='$imageEntry'");
    runQuery("delete from darlelJobber_shop_images where id='$imageEntry'");
}
else if($purpose=="addComment"){
    $commentText=$_POST['commentText'];
    
    //replacing commented file image name with lightbox image tag
    $pattern = "/\*(.*?)\*/";
    $replacement = "<a class='example-image-link'  href='uploads/$1' data-lightbox='example-1'>$1</a>";
    $commentText = preg_replace($pattern, $replacement, $commentText);
    $commentText=clear($commentText);
    
    $query="insert into darlelJobber_task_comments set id='$random',commentText='$commentText',taskId='$taskId',addedBy='$session_id',timeAdded='$timeAdded'";
    runQuery($query);

    //this matches for the comments (people) that were mentioned in the comment
    
    $users=getAll($con,"select * from darlelJobber_users");
    $nameToInfo=[];
    foreach($users as $row){
        $nameToInfo[$row['name']]=$row;    
    }

    $commentText=$_POST['commentText'];
    preg_match_all('/@([^\s@]+)@/', $commentText, $matches);
    $names = $matches[1];
    
    //sending notification
    foreach($names as $row){
        $userId=$nameToInfo[$row]['id'];
        $title="Task Update";
        $description="You have been tagged in a task";
        $url=$g_website."/detailedTaskView.php?taskId=$taskId";
        setNotification($title,$description,$userId,$url);
    }
    
    $updateTaskCommentStatus=1;
}
else if($purpose=="deleteComment"){
    $commentId=clear($_POST['commentId']);
    runQuery("delete from darlelJobber_task_comments where id='$commentId'");
}


//if a new comment or a file was uploaded then everyone's comment status will be updated for that task
if($updateTaskCommentStatus){
    $team=getAll($con,"select userId from darlelJobber_teams where taskId='$taskId'");
    $newMember = ["userId" => $taskDeets['addedBy']];
    $team[] = $newMember;
    
    foreach($team as $row){
        if($row['userId']!=$session_id){
            $id=random();
            $userId=$row['userId'];
            $query="insert into darlelJobber_task_comment_status set id='$id',taskId='$taskId',userId='$userId',status='Not Read'";
            runQuery($query);
        }
    }
}
?>