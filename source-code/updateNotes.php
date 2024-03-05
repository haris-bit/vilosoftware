<?require("./global.php");

$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["attachment"]["name"]);
$notesId=clear($_POST['notesId']);
$purpose=clear($_POST['purpose']);
$image=clear( basename( $_FILES["attachment"]["name"]));

if($purpose=="uploadFile"){    
    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        $rowId = clear($_POST['rowId']);
        $image = clear(basename($_FILES["attachment"]["name"]));
    
        $oldImagePath = "uploads/$image";
        $newImageName = random() . $image;
    
        // Check if the uploaded file is a JPEG
        $extension = strtolower(pathinfo($oldImagePath, PATHINFO_EXTENSION));
        if ($extension === 'jpg' || $extension === 'jpeg') {
            $image = imagecreatefromjpeg($oldImagePath);
    
            // Adjust the orientation
            $exif = exif_read_data($oldImagePath);
            if ($exif !== false && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                switch ($orientation) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
    
            // Adjust the quality parameter (0 to 100) based on your needs
            imagejpeg($image, 'uploads/' . $newImageName, 15); // Adjust 15 for JPEG quality
            imagedestroy($image);
            unlink("./uploads/".$_FILES["attachment"]["name"]);
        } else {
            rename($oldImagePath, 'uploads/' . $newImageName);
        }
    
        $query = "insert into darlelJobber_notes_images set id='$rowId',image='$newImageName',notesId='$notesId'";
        runQuery($query);
    }

}
else if($purpose=="removeFile"){
    $rowId=clear($_POST['rowId']);
    $imageName=getRow($con,"select * from darlelJobber_notes_images where id='$rowId'")['image'];
    $query="delete from darlelJobber_notes_images where id='$rowId'";
    runQuery($query);
    unlink("./uploads/".$imageName);
        
}
else if($purpose=="updateNotes"){
    
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $newQuote=clear($_POST['newQuote']);
    $showCrew=clear($_POST['showCrew']);
    $parameterName=clear($_POST['parameterName']);
    $parameterValue=clear($_POST['parameterValue']);
    $currentTime=time();
    $timeAdded=time();
    $showCrew = ($showCrew=="true") ? "Yes":"No";
    
    if($newQuote)
        $query="insert into darlelJobber_notes set id='$notesId',showCrew='$showCrew',title='$title',
        description='$description',".$parameterName."='$parameterValue',addedBy='$session_id',timeAdded='$timeAdded',lastUpdated='$currentTime'";
    else{
        if($parameterName=="quoteId"){
            //inserting in quote history if crew view field is edited
            $quoteId=$parameterValue;
            $notesDeets=getRow($con,"select * from darlelJobber_notes where id='$notesId' ");
            if($showCrew!=$notesDeets['showCrew']){
                $random=random();
                $historyTitle="Crew view changed from ".$notesDeets['showCrew']." To $showCrew";
                $query="insert into darlelJobber_quote_history set id='$random',title='$historyTitle',type='Crew View Changed',quoteId='$quoteId',timeAdded='$timeAdded',addedBy='$session_id'";
                runQuery($query);
            }
        }
        $query="update darlelJobber_notes set title='$title',showCrew='$showCrew',description='$description',lastUpdated='$currentTime' where id='$notesId'";
    }
    runQuery($query);
}

?>
