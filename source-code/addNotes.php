<?
require("./global.php");
$new= (isset($_GET['new'])) ? 1 : 0;
$edit= (isset($_GET['edit'])) ? 1 : 0;

$timeAdded=time();
$currentTime=time();
$notesId = ($new) ? clear($_GET['new']) : clear($_GET['edit']);
if($edit){
    $notesDeets=getRow($con,"select * from darlelJobber_notes where id='$notesId'");
    $timeAdded=$notesDeets['timeAdded'];
}
    
/*if(isset($_POST['submitTask'])){
    $customIndexing=array(
        "jobId"=>"createJob.php",
        "requestId"=>"createRequest.php",
        "quoteId"=>"createQuote.php",
        "invoiceId"=>"createInvoice.php",
        "userId"=>"view_client.php",
        "ticketId"=>"client_ticket.php",
    );
    
    $parameters=["jobId","requestId","quoteId","invoiceId","userId","ticketId"];
    foreach($parameters as $row){
        if(isset($_GET[$row])){
            $parameterName=$row;
            $parameterValue=clear($_GET[$row]);
            $redirectPage=$customIndexing[$row];
            $redirectParameter="entryId";
            if($row=="ticketId")    
                $redirectParameter="ticketId";
            else if($row=="userId")    
                $redirectParameter="id";
        }
    }
    
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $showCrew=clear($_POST['showCrew']);
    $showCrew = ($showCrew=="on") ? "Yes":"No";
    
    if($new)
        $query="insert into darlelJobber_notes set id='$notesId',showCrew='$showCrew',title='$title',description='$description',".$parameterName."='$parameterValue',addedBy='$session_id',timeAdded='$timeAdded',lastUpdated='$currentTime'";
    else
        $query="update darlelJobber_notes set title='$title',showCrew='$showCrew',description='$description',lastUpdated='$currentTime' where id='$notesId'";
    runQuery($query);
    if($new){//close the current tab?>
        <script type="text/javascript">
            window.close(); 
        </script>
    <?}
    else
        header("Location:./$redirectPage?$redirectParameter=$parameterValue");
}*/

$customIndexing=array(
    "jobId"=>"createJob.php",
    "requestId"=>"createRequest.php",
    "quoteId"=>"createQuote.php",
    "invoiceId"=>"createInvoice.php",
    "userId"=>"view_client.php",
    "ticketId"=>"client_ticket.php",
);

$parameters=["jobId","requestId","quoteId","invoiceId","userId","ticketId"];
foreach($parameters as $row){
    if(isset($_GET[$row])){
        $parameterName=$row;
        $parameterValue=clear($_GET[$row]);
        $redirectPage=$customIndexing[$row];
        $redirectParameter="entryId";
        if($row=="ticketId")    
            $redirectParameter="ticketId";
        else if($row=="userId")    
            $redirectParameter="id";
    }
}
    
?>

<html lang="en">
	<head>
	    <?require("./includes/views/head.php");?>
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
							    <div class="card shadow-sm mb-15 mt-5">
                                    <form action="" method="post">
							                
                                    <div class="card-header">
                                        <h3 class="card-title">
				                            <?echo ($new) ? "Add" : "Edit";?> Notes
				                        </h3>
                                        
                                        <div class="card-toolbar">
                                            <div class="form-check me-3 mb-3 mt-3">
                                                <input name="showCrew" class="form-check-input" type="checkbox" <?if($notesDeets['showCrew']=="Yes"){echo "checked";}?>/>
                                                <h3 class="form-check-label ">Show Notes To Crew ? </h3>
                                            </div>
                                            <h3>Time Stamp : <?echo date("d M Y h:i A ",$timeAdded);?></h3>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                            <div class="row">
                                            <div class="col-12 mb-4">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">Title</label>
                                                <input type="text" name="title" class="form-control" placeholder="Enter Title" value="<?echo ($notesDeets['title']=="") ? "Site Pictures" : $notesDeets['title'];?>" required>
                                            </div>
                                            <div class="col-12 mb-4">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">Description</label>
                                                <textarea name="description" class="form-control" placeholder="Enter Description" rows="10"><?echo $notesDeets['description']?></textarea>
                                            </div>
                                            <div class="col-12 text-center">
                                                <a id="submitForm" onclick="updateInfo()" class="btn btn-primary btn-sm">Save Changes</a>
                                                <input id="submitForm"  type="submit" name="submitTask" class="d-none" value="Save Changes">
                                            </div>
                                            
                                            </div>
                                            </div>
                                            
							                </form>
                                            
                                            <div class="col-12 mb-4">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">
                                                    Attachments
                                                </label>
                                                <input type="file" id="attachment" class="form-control mb-5" multiple>
                                            </div>
                                            <div class="col-12">
                                                <table class="table table-rounded table-row-bordered border gs-7 text-center" id="attachmentsTable">
                                                    <tbody>
                                                    <?if($edit){
                                                    $images=getAll($con,"select * from darlelJobber_notes_images where notesId='$notesId'");
                                                    foreach($images as $row){?>
                                                    <tr id="<?echo $row['id']?>">
                                                        <td><?echo $row['image']?></td>
                                                        <td><?echo "Uploaded"?></td>
                                                        <td>
                                                            <a style="padding: 20px;" onclick="removeImage('<?echo $row['id']?>')" class="btn btn-danger btn-sm">
                                                                <i style="font-size:x-large;" class="las la-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    
                                                    <?}}?>
                                                    </tbody>
                                                </table>
                                            </div>
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
	
	
	<script>
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
		
	    
    // Function to handle file uploads
    function uploadFile(file,rowId) {
          return new Promise((resolve, reject) => {
            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', (event) => {
              if (event.lengthComputable) {
                var percentComplete = (event.loaded / event.total) * 100;
                // Update the progress bar for the corresponding file
                var progressBar = document.getElementById('progressBar_'+rowId);
                progressBar.style.width = percentComplete + '%';
                progressBar.innerHTML = Math.round(percentComplete) + '%';
              }
            });
            xhr.onload = () => {
              if (xhr.status === 200) {
                // File uploaded successfully
                console.log("uploaded successfully");
                resolve(xhr.response);
                var progressBar = document.getElementById('progressBar_'+rowId);
                progressBar.classList.add('bg-success');
                progressBar.innerHTML = 'Uploaded Successfully';
                $('#deleteBtn_'+rowId).show();
              } else {
                // Upload failed
                reject(xhr.statusText);
                var progressBar = document.getElementById('progressBar_'+rowId);
                progressBar.classList.add('bg-danger');
                progressBar.innerHTML = 'Upload Failed';
                console.error('Upload error:', error);
              }
            };
            xhr.onerror = () => {
              reject(xhr.statusText);
            };
            
            var formData = new FormData();
            formData.append("attachment", file);
            formData.append("purpose", "uploadFile");
            formData.append("rowId", rowId);
            formData.append("notesId", "<?echo $notesId;?>");
            xhr.open("POST", "updateNotes.php", true);
            xhr.send(formData);
          });
        }

    // Function to handle file selection
    function handleFileSelect(event) {
      var files = event.target.files;
      var attachmentsTable = document.getElementById('attachmentsTable');
      
      // Iterate over each selected file
      for (var i = 0; i < files.length; i++) {
        var file = files[i];
        
        // Create a new row for the file
        var newRow = attachmentsTable.insertRow();
        var rowId=makeid(8);
        newRow.id = rowId;
        // Create a cell for the file name
        var nameCell = newRow.insertCell();
        nameCell.innerHTML = file.name;
        
        // Create a cell for the progress bar
        var progressBarCell = newRow.insertCell();
        progressBarCell.innerHTML = `<div class="progress"><div id="progressBar_`+rowId+`" class="progress-bar" style="width: 0%"></div></div>`;
        
        var deleteBtn=`<a id="deleteBtn_`+rowId+`" style="padding: 20px;display:none;" onclick="removeImage('`+rowId+`')" class="btn btn-danger btn-sm"><i style="font-size:x-large;" class="las la-trash"></i></a>`;
        var deleteBtnCell = newRow.insertCell();
        deleteBtnCell.innerHTML = deleteBtn ;
        
        
        // Upload the file and update progress
        uploadFile(file,rowId)
          .then((response) => {
            //$('#deleteBtn_'+rowId).show();
          })
          .catch((error) => {
            // Error occurred during upload, update the progress bar
            var progressBar = document.getElementById(file.name + '_progress');
            progressBar.classList.add('bg-danger');
            progressBar.innerHTML = 'Upload Failed';
            console.error('Upload error:', error);
          });
      }
    }
    
    // Attach the event listener to the file input
    var fileInput = document.getElementById('attachment');
    fileInput.addEventListener('change', handleFileSelect);

    //either method could be filename or file id and based upon that file detail will be provided
    function removeImage(rowId){
        $('#'+rowId).remove();
        $.post("updateNotes.php",
        {
            purpose: "removeFile",
            rowId: rowId,
        },
        function(){
            console.log("Deleted Image Successfully");
        });
    }
    function updateInfo(){
        $("#submitForm").text("Submitting Form");
        var showCrew = $('input[name=showCrew]').is(":checked");
        var title = $("input[name='title']").val();
        var description = $("textarea[name='description']").val();
        var newQuote="<?echo $new?>";
        var notesId="<?echo $notesId?>";
        var parameterName="<?echo $parameterName?>";
        var parameterValue="<?echo $parameterValue?>";
        
        $.post("updateNotes.php",
        {
            purpose: "updateNotes",
            showCrew:showCrew,
            title:title,
            description:description,
            newQuote:newQuote,
            notesId:notesId,
            parameterName:parameterName,
            parameterValue:parameterValue,
        },
        function(){
            <?if($new)echo "window.close(); ";else{?>
            window.location.href = "<?echo "$redirectPage?$redirectParameter=$parameterValue";?>";
            <?}?>
        });
    }
    </script>
</html>