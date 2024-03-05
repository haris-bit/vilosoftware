<?
if(isset($_POST['addNotes'])){
    $title=mb_htmlentities($_POST['title']);
    $description=mb_htmlentities($_POST['description']);
    $image=mb_htmlentities($_POST['image']);
    $actionId=mb_htmlentities($_POST['actionId']);
    $timeAdded=time();
    $id=generateRandomString();
    
    $customIndexing=array(
        "createJob.php"=>"jobId",
        "createRequest.php"=>"requestId",
        "createQuote.php"=>"quoteId",
        "viewQuote.php"=>"quoteId",
        "createInvoice.php"=>"invoiceId",
        "view_client.php"=>"userId",
        "client_ticket.php"=>"ticketId",
        "create_ticket.php"=>"ticketId",
    );
    
    $columnName=$customIndexing[$filenameLink];
    $columnNameEntry=$_GET['entryId'];
    if($filenameLink=="view_client.php")
        $columnNameEntry=$_GET['id'];
    else if($filenameLink=="client_ticket.php" || $filenameLink=="create_ticket.php")
        $columnNameEntry=$_GET['ticketId'];
    
    if($actionId==""){
        $entryId=$id;
        $query="insert into darlelJobber_notes set id='$id',title='$title',description='$description',".$columnName."='$columnNameEntry',addedBy='$session_id',timeAdded='$timeAdded'";
    }
    else{
        $query="update darlelJobber_notes set title='$title',description='$description' where id='$actionId'";
        $entryId=$actionId;
    }
    runQuery($query);
    
    
    //multiple file upload
    $total = count($_FILES['fileToUpload']['name']);
    for( $i=0 ; $i < $total ; $i++ ){
      $tmpFilePath = $_FILES['fileToUpload']['tmp_name'][$i];
      if ($tmpFilePath != ""){
        $newFilePath = "./uploads/" . $_FILES['fileToUpload']['name'][$i];
        if(move_uploaded_file($tmpFilePath, $newFilePath)) {
            $image=$_FILES['fileToUpload']['name'][$i];
            $random=random();
            $query="insert into darlelJobber_notes_images set id='$random',image='$image',notesId='$entryId'";
            runQuery($query);
            
            
            //compressing this file now 
            $uploadsFolder = './uploads/';
            $uploadedFile = $_FILES['fileToUpload']['tmp_name'][$i]; // Assuming you are using a file input named 'fileToUpload'
            $extension = strtolower(pathinfo($_FILES['fileToUpload']['name'][$i], PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($uploadedFile);
                    $exif = exif_read_data($uploadedFile);
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
                    break;
                case 'png':
                    continue;
                case 'webp':
                    $image = imagecreatefromwebp($uploadedFile);
                    break;
                default:
                    exit("Unsupported file type");
            }
            
            if ($extension === 'png') {
                //imagepng($image, $uploadsFolder . $newFileName, 9); // Adjust 5 for compression level
            } else {
                imagejpeg($image, $uploadsFolder . $_FILES['fileToUpload']['name'][$i], 15); // Adjust 50 for JPEG quality
            }
            imagedestroy($image);
        }
      }
    }
    if($filenameLink=="createJob.php" || $filenameLink=="createQuote.php" || $filenameLink=="viewQuote.php" || $filenameLink=="createInvoice.php"){
        $entryId=clear($_GET['entryId']);
        header("Location:?entryId=$entryId");
        exit();
    }
}

if(isset($_GET['removeNotesImage'])){
    $id=clear($_GET['removeNotesImage']);
    $query="delete from darlelJobber_notes_images where id='$id'";
    runQuery($query);
}

if(isset($_GET['delete-record'])){
    $id = mb_htmlentities($_GET['delete-record']);
    $query="delete from darlelJobber_notes where id='$id'";
    runQuery($query);
    //removing its respective images as well
    $query="delete from darlelJobber_notes_images where notesId='$id'";
    runQuery($query);
}
?>