
		<div class="modal fade" id="add_notes" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-650px">
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
							    
							    <div class="row">
							        <div class="col-9">
							            <h1 class="mb-3" id="notesTitle"></h1>
							        </div>
							    </div>
							    
							</div>
							<div class=" flex-column mb-8 fv-row" id="title">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Title</span>
								</label>
								<input type="text" name="title" class="form-control" placeholder="Enter Title">
							</div>
							<div class=" flex-column mb-8 fv-row" id="description">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Description</span>
								</label>
								<textarea class="form-control" name="description" placeholder="Enter Description"></textarea>
							</div>
							<div  class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2"><span class="required">Attachment</span></label>
								<input id="fileInput" type="file" name="fileToUpload[]" multiple class="form-control">
							</div>
							<input type="text" name="actionId" hidden>
							<div class="text-center" id="saveButton">
								<input type="submit" value="Save" name="addNotes" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
	    $(document).ready(function(){
        $("#add_notes").on('show.bs.modal', function (e) {
            //get data-id attribute of the clicked element
            var mydata = $(e.relatedTarget).data('mydata');
            console.log(mydata);
            //console.log("mydata", mydata)
            <?if($filenameLink=="client_ticket.php"){?>
                //$('#title').hide();
                //$('#description').hide();
            <?}?>
            
            if(mydata!= null){
            	if(mydata['showSaveButton']==0){
        	        $('#saveButton').hide();
        	        $('#fileInput').hide();
                    $("#notesTitle").html("View Notes");
            	}
            	else{
            	    $('#saveButton').show();
        	        $('#fileInput').show();
                    $("#notesTitle").html("Update <?if($filenameLink=="client_ticket.php"){echo "Image";}else{echo "Notes";}?>");
            	}
            	$("input[name='title']").val(mydata['title'])
                $("textarea[name='description']").val(mydata['description'])
                $("input[name='actionId']").val(mydata['id'])
            }else{
                $("#notesTitle").html("Add <?if($filenameLink=="client_ticket.php"){echo "Image";}else{echo "Notes";}?>");
                $("input[name='title']").val("")
                $("textarea[name='description']").val("")
                $("input[name='actionId']").val("")
                $('#saveButton').show();
                $('#fileInput').show();
            }
        });
	    })
	    </script>
	    <script src="assets/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
        <script src="https://vjs.zencdn.net/8.5.2/video.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const gallery = new SimpleLightbox('.gallery', {});
            });
            document.addEventListener('DOMContentLoaded', function() {
            var videos = document.querySelectorAll('.video-js');
        
            videos.forEach(function(video) {
                videojs(video).on('play', function() {
                  this.requestFullscreen();
                });
              });
            });
            
            $(document).ready(function() {
                $(".pdf-link").fancybox({
                    type: "iframe", 
                    iframe: {
                        preload: false, 
                    },
                });
            });

        </script>