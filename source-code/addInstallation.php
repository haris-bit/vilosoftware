<?
require("./global.php");
$new= (isset($_GET['new'])) ? 1 : 0;
$edit= (isset($_GET['edit'])) ? 1 : 0;
$isJob= (isset($_GET['jobId'])) ? 1 : 0;
$isTicket= (isset($_GET['ticketId'])) ? 1 : 0;

if($session_role=="Installation Crew")
    $extraString="&view=1";

$jobId=clear($_GET['jobId']);
$ticketId=clear($_GET['ticketId']);
$installationId = ($new) ? clear($_GET['new']) : clear($_GET['edit']);
if($edit)
    $installationDeets=getRow($con,"select * from darlelJobber_installation where id='$installationId'");
    
if(isset($_POST['submitInstallation'])){
    $timeAdded=time();
    $title=clear($_POST['title']);
    $timeline=clear($_POST['timeline']);
    if($new){
        if($isJob)
            $query="insert into darlelJobber_installation set id='$installationId',title='$title',timeline='$timeline',jobId='$jobId',addedBy='$session_id',timeAdded='$timeAdded'";
        else if($isTicket)
            $query="insert into darlelJobber_installation set id='$installationId',title='$title',timeline='$timeline',ticketId='$ticketId',addedBy='$session_id',timeAdded='$timeAdded'";
    }else
        $query="update darlelJobber_installation set title='$title',timeline='$timeline' where id='$installationId'";
    runQuery($query);
    if($new){//close the current tab?>
        <script type="text/javascript">
            window.close(); 
        </script>
    <?}
    else if($isJob)
        header("Location:./createJob.php?entryId=$jobId&m=Installation Section has been updated successfully$extraString");
    else if($isTicket){
        header("Location:./create_ticket.php?ticketId=$ticketId&m=Installation Section has been updated successfully$extraString");
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
                                    <div class="card-header">
                                        <h3 class="card-title">
				                            <?echo ($new) ? "Add" : "Edit";?> Installation Images
				                        </h3>
                                        <div class="card-toolbar">
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                            <form method="post" enctype="multipart/form-data">
							                <div class="row">
                                            <div class="col-md-6 col-12 mb-4">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">Title</label>
                                                <input type="text" name="title" class="form-control" placeholder="Enter Title" 
                                                value="<?echo ($installationDeets['title']=="") ? "Installation Images" : $installationDeets['title'];?>" required>
                                            </div>
                                            <div class="col-md-6 col-12 mb-4">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">Timeline</label>
                                                <select class="form-control" name="timeline">
                								    <option <?if($installationDeets['timeline']=="Before Installation"){echo "selected";}?> value="Before Installation">Before Installation</option>
                								    <option <?if($installationDeets['timeline']=="After Installation"){echo "selected";}?> value="After Installation">After Installation</option>
                								</select>
                                            </div>
                                            <div class="col-12 text-center">
                                                <input type="submit" name="submitInstallation" class="btn btn-primary btn-sm" value="Save Changes">
                                            </div>
                                            
                                            </div>
                                            </div>
                                            
							                </form>
                                            
                                            <div class="col-12 mb-4">
                                                <label class="d-flex align-items-center fs-6 fw-bold mb-5">
                                                    Attachments
                                                </label>
                                                <input type="file" name="attachments[]" id="attachment" class="form-control mb-5" multiple>
                                            </div>
                                            <div class="col-12">
                                                <table class="table table-rounded table-row-bordered border gs-7 text-center" id="attachmentsTable">
                                                    <tbody>
                                                    <?if($edit){
                                                    $images=getAll($con,"select * from darlelJobber_installation_images where installationId='$installationId'");
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
                resolve(xhr.response);
              } else {
                // Upload failed
                reject(xhr.statusText);
              }
            };
            xhr.onerror = () => {
              reject(xhr.statusText);
            };
            
            var formData = new FormData();
            formData.append("attachment", file);
            formData.append("purpose", "uploadFile");
            formData.append("rowId", rowId);
            formData.append("installationId", "<?echo $installationId;?>");
            xhr.open("POST", "updateInstallation.php", true);
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
        $.post("updateInstallation.php",
        {
            purpose: "removeFile",
            rowId: rowId,
        },
        function(){
            console.log("Deleted Image Successfully");
        });
        
    }
    </script>
</html>