<?require("./global.php");

$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["attachment"]["name"]);
$installationId=clear($_POST['installationId']);
$purpose=clear($_POST['purpose']);
$image=clear( basename( $_FILES["attachment"]["name"]));

if($purpose=="uploadFile"){    
    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        $rowId=clear($_POST['rowId']);
        $image=clear( basename( $_FILES["attachment"]["name"]));
        $timeAdded=time();
        $query="insert into darlelJobber_installation_images set id='$rowId',image='$image',installationId='$installationId',addedBy='$session_id',timeAdded='$timeAdded'";
        runQuery($query);
    }
}
else if($purpose=="removeFile"){
    $rowId=clear($_POST['rowId']);
    $query="delete from darlelJobber_installation_images where id='$rowId'";
    runQuery($query);
}

?>
