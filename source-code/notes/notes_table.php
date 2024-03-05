<?
$idToInfoUsersNotes=[];
$tempUsers=getAll($con,"select * from darlelJobber_users where role!='Client'");
foreach($tempUsers as $row)
    $idToInfoUsersNotes[$row['id']]=$row;
    
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
    <link href="https://vjs.zencdn.net/8.5.2/video-js.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.js"></script>

    <style>
        .vjs-control-bar {
            bottom: auto;
            top: 0;
            transform: translateY(0);
            padding-top:30px;
        }
    </style>

<div class="card shadow-sm mb-10 mt-10" >
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
    	<div class="card-title">
    	    Notes Section
    	</div>
    	<div class="card-toolbar">
    	    <?if($permission['add_notes']){?>
            <!--<a href="#" data-bs-toggle="modal" data-bs-target="#add_notes" class="btn btn-primary btn-sm">Add Notes</a>-->
            <?}?>
    	    <?
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
            $urlParameter=$customIndexing[$filenameLink];
    	    $columnNameEntry=$_GET['entryId'];
            if($filenameLink=="view_client.php")
                $columnNameEntry=$_GET['id'];
            else if($filenameLink=="client_ticket.php" || $filenameLink=="create_ticket.php")
                $columnNameEntry=$_GET['ticketId'];?>
    	    <a target="_blank" href="addNotes.php?<?echo $urlParameter."=".$columnNameEntry;?>&new=<?echo random();?>" class="btn btn-primary btn-sm">Add Notes</a>
    	</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
    		    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
        	    <thead>
        	        <tr class="fw-bolder fs-6 text-gray-800 text-center">
        	            <th >Title</th>
        	            <th>Description</th>
        	            <th>Attachments</th>
        	            <th>Added By</th>
        	            <?if(!$view){?>
        	            <th>Actions</th>
        	            <?}?>
        	        </tr>
        	    </thead>
        	    
        	    <tbody>
        	        <?
        	        if($filenameLink=="createJob.php"){
            	        $quoteId=$jobDeets['quoteId'];
            	        $requestId=$jobDeets['requestId'];
            	        $invoiceId=$jobDeets['invoiceId'];
            	        $jobId=$_GET['entryId'];
            	        $entryId=$_GET['entryId'];
            	        $query="select * from darlelJobber_notes where (invoiceId='$invoiceId' ||jobId='$jobId' || quoteId='$quoteId' || requestId='$requestId')  order by timeAdded desc";
        	            $deleteHref="?entryId=$entryId";
        	        }
        	        else if($filenameLink=="createRequest.php"){
        	            $jobId=$requestDeets['jobId'];
    			        $invoiceId=$requestDeets['invoiceId'];
    			        $quoteId=$requestDeets['quoteId'];
    			        $entryId=$_GET['entryId'];
            	        $query="select * from darlelJobber_notes where (invoiceId='$invoiceId' || jobId='$jobId' || quoteId='$quoteId' || requestId='$requestId')  order by timeAdded desc";
    			        $deleteHref="?entryId=$entryId";
        	        }
    			    else if($filenameLink=="createQuote.php" || $filenameLink=="viewQuote.php"){
    			        $requestId=$quoteDeets['requestId'];
    			        $jobId=$quoteDeets['jobId'];
    			        $invoiceId=$quoteDeets['invoiceId'];
    			        $quoteId=$_GET['entryId'];
    		            $entryId=$_GET['entryId'];
            	        $deleteHref="?entryId=$entryId";
        	            $query="select * from darlelJobber_notes where (invoiceId='$invoiceId' || jobId='$jobId' || quoteId='$quoteId' || requestId='$requestId') order by timeAdded desc";
    			    }
    			    else if($filenameLink=="createInvoice.php"){
    			        $jobId=$invoiceDeets['jobId'];
    			        $requestId=$invoiceDeets['requestId'];
    			        $quoteId=$invoiceDeets['quoteId'];
    			        $invoiceId=$_GET['entryId'];
    			        $entryId=$_GET['entryId'];
            	        $deleteHref="?entryId=$entryId";
        	            $query="select * from darlelJobber_notes where (invoiceId='$invoiceId' || jobId='$jobId' || quoteId='$quoteId' || requestId='$requestId')  order by timeAdded desc";
    			    }
    			    else if($filenameLink=="view_client.php"){
    			        $clientId=$_GET['id'];
            	        $deleteHref="?id=$clientId";
        	            $query="select * from darlelJobber_notes where userId='$userId'  order by timeAdded desc";
                	}
    			    else if($filenameLink=="client_ticket.php" || $filenameLink=="create_ticket.php"){
    			        $ticketId=$_GET['ticketId'];
            	        $purpose=$_GET['purpose'];
            	        $deleteHref="?ticketId=$ticketId&purpose=$purpose";
        	            $query="select * from darlelJobber_notes where ticketId='$ticketId' order by timeAdded desc";
                    }
        	        $notes=getAll($con,$query);
        	        foreach($notes as $row){
        	            if($session_role=="Installation Crew" && $row['showCrew']=="No")
        	                continue;
        	            $crewCanSee = (strpos($row['title'], "Printable PDF") !== false) ? 0 : 1;
        	            
        	        if(($row['title']!="Approval Sign" && $crewCanSee && $session_role=="Installation Crew") || ($session_role!="Installation Crew")){
        	        ?>
        	        <tr>
        	            <td >
        	                <?echo $row['title']."<br>";?>
        	                <a class="badge badge-<?echo ($row['showCrew']=="Yes") ? "success" : "warning";?>"><?echo "Show Crew : ".$row['showCrew']?></a>
    	                </td>
        	            <td ><?echo $row['description']?></td>
        	            <td>
        	                <?
        	                $isPdf=0;
        	                if(strpos($row['image'], ".pdf") !== false)
        	                    $isPdf=1;
        	                
        	                if($row['title']!="Approval Sign" && $row['title']!="Printable PDF" && $row['title']!="Invoice Printable PDF" && !$isPdf){
        	                $notesId=$row['id'];
        	                $images=getAll($con,"select * from darlelJobber_notes_images where notesId='$notesId'");
        	                foreach($images as $nrow){
        	                $isPdf=0;
        	                $isMovie=0;
        	                
        	                if(strpos($nrow['image'], ".pdf") !== false)
        	                    $isPdf=1;
    	                    if((strpos($nrow['image'], ".MOV") !== false) || (strpos($nrow['image'], ".mov") !== false)|| (strpos($nrow['image'], ".mp4") !== false))
        	                    $isMovie=1;
    	                    if(!$isPdf){
    	                    $displayImage = str_replace('#', '%23', $nrow['image']);?>
        	                <p class="btn btn-light-success btn-sm me-3" style="padding: 5px;">
                                    <?if(!$isMovie){?>
        	                        <a  href="uploads/<?echo $displayImage?>" class="gallery">
        	                            <img class="example-image" style="max-height: 4.3755rem;" src="./uploads/<?echo $displayImage?>" onerror="this.style.display='none'" loading="lazy" />
    	                            </a>
    	                            <?}
    	                            else if($isMovie){?>
                                    <video class="video-js" controls preload="auto" width="230" height="70" data-setup="{}">
                                        <source src="uploads/<?echo $displayImage?>" type="video/mp4" />
                                    </video>
    	                            <?}
    	                            else
    	                                echo "Unsupported Format";?>
    	                        
    	                        <?if((!$view) && ($filenameLink!="viewQuote.php")){?>
                                <a onclick="return confirm('Are you sure you want to delete this image?');" style="color: red;font-size: x-large;"
                                href="<?echo $deleteHref?>&removeNotesImage=<?echo $nrow['id']?>">X</a>
    					        <?}?>
                            </p>                                                        
        	                
        	                <?}else if($isPdf){
        	                    $displayPDF = str_replace('#', '%23', $nrow['image']);?>
        	                    <a class="pdf-link" href="uploads/<?echo $displayPDF?>"><?echo $nrow['image']?></a>
        	                <?}}}
        	                else if($isPdf){
        	                    $displayPDF = str_replace('#', '%23', $row['image']);?>
        	                    <a class="pdf-link" href="uploads/<?echo $displayPDF?>">View PDF</a>
        	                <?}
        	                else if($row['title']=="Approval Sign"){?>
        	                    <a  href="uploads/<?echo $row['image']?>" class="gallery">
    	                            <img class="example-image" style="max-height: 4.3755rem;" src="./uploads/<?echo $row['image']?>" onerror="this.style.display='none'" loading="lazy" />
    	                        </a>
        	                <?}?>
    	                </td>
        	            <td><?echo $idToInfoUsersNotes[$row['addedBy']]['name']."<br> Last Updated : ".date("d M Y",$row['lastUpdated'])?></td>
        	            <?if(!$view){?>
        	            <td>
        	                <div class="btn-group">
                            <?if($permission['view_notes'] ){
        	                $row['showSaveButton']=0;?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#add_notes" data-mydata='<?echo  (json_encode($row, true));?>' class="btn btn-primary btn-sm">View</a>
        	                <?}?>
        	                <?if(($permission['edit_notes']) && ($filenameLink!="viewQuote.php") && ($row['title']!="Approval Sign") ){
        	                $row['showSaveButton']=1;?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#add_notes" data-mydata='<?echo  (json_encode($row, true));?>' class="btn btn-warning btn-sm d-none">Edit</a>
    	                    <a href="addNotes.php?<?echo $urlParameter."=".$columnNameEntry;?>&edit=<?echo $row['id'];?>" class="btn btn-warning btn-sm">Edit</a>
    	                    
    	                    <?}?>
        	                <?if(($permission['delete_notes']) && ($filenameLink!="viewQuote.php")){?>
                            <a onclick="return confirm('Are you sure you want to delete this note?');"  href="?delete-record=<?echo $row['id']?>&entryId=<?echo $entryId?>" class="btn btn-danger btn-sm">Delete</a>
        					<?}?>
        					</div>
        				</td>
        				<?}?>
                    </tr>
                    <?}}?>
        	    </tbody>
        	</table>
        </div>
    </div>
</div>



													            