
		<script>var hostUrl = "../assets/";</script>
		<script src="../assets/plugins/global/plugins.bundle.js"></script>
		<script src="../assets/js/scripts.bundle.js"></script>
		<script src="../assets/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>
		<script src="../assets/plugins/custom/datatables/datatables.bundle.js"></script>
		<script src="../assets/js/widgets.bundle.js"></script>
		<script src="../assets/js/custom/widgets.js"></script>
		<script src="../assets/js/custom/apps/chat/chat.js"></script>
		<script src="../assets/js/custom/utilities/modals/upgrade-plan.js"></script>
		<script src="../assets/js/custom/utilities/modals/create-app.js"></script>
		<script src="../assets/js/custom/utilities/modals/users-search.js"></script>
		<script src="../assets/js/custom/utilities/modals/new-target.js"></script>
		<script src="../assets/js/custom/apps/ecommerce/catalog/categories.js"></script>
		
		
<div class="modal fade" tabindex="-1" id="delete_record">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Entry</h5>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <span class="svg-icon svg-icon-2x"></span>
                </div>
                <!--end::Close-->
            </div>

            <div class="modal-body">
                <p>Are You Sure You Want To Delete This Entry ?</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <a href="#sd" id="delete-project" class="btn btn-danger">
                    Delete
                </a>
            </div>
        </div>
    </div>
</div>
<script>

    $(document).ready(function(){
        <?if($filenameLink!="createRequest.php"){?>
        $('form').submit(function(event) {
          $(this).find(':submit').css('pointer-events', 'none');
        });
        <?}?>
        $('input[type="number"]').attr('step', '0.01');
                
        $("#delete_record").on('show.bs.modal', function (e) {
        //get data-id attribute of the clicked element
        var url = $(e.relatedTarget).data('url');
        console.log("modal opened", name)
        //populate the textbox
         $("#delete-project").attr("href", url);
    
      });
    });
</script>
