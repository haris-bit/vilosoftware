<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    $query="delete from darlelJobber_requests where id='$id'";
    runQuery($query);
    
    header("Location:./requests.php?m=deleted");
}
$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row){$idToInfo[$row['id']]=$row;}

$clients=getAll($con,"select * from darlelJobber_users where role='Client'");
foreach($clients as $row)
{$idToInfo[$row['id']]=$row;}


if(isset($_POST['addMaterialDelivery'])){
    $customerId=clear($_POST['customerId']);
    $sales_order=clear($_POST['sales_order']);
    $schedule_type=clear($_POST['schedule_type']);
    $start_time=clear($_POST['start_time']);
    $end_time=clear($_POST['end_time']);
    $actionId=clear($_POST['actionId']);
    $start_date=strtotime(clear($_POST['start_date']));
    $end_date=strtotime(clear($_POST['end_date']));
    $address=clear($_POST['address']);
    
    if($actionId==""){
        $materialDeliveryId=$id;
        $id=random();
        $query="insert into darlelJobber_material_delivery set id='$id',address='$address',customerId='$customerId',schedule_type='$schedule_type',sales_order='$sales_order',
        start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',addedBy='$session_id'";
    }
    else{
        $materialDeliveryId=$actionId;
        $query="update darlelJobber_material_delivery set customerId='$customerId',address='$address',schedule_type='$schedule_type',sales_order='$sales_order',
        start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time' where id='$actionId'";
    }
    runQuery($query);
    
    if(isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK){
        $target_dir = "./uploads/";
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $attachment=htmlspecialchars( basename( $_FILES["fileToUpload"]["name"]));
        move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
        runQuery("update darlelJobber_material_delivery set attachment='$attachment' where id='$materialDeliveryId'");
    }
    
    header("Location:?m=Data Updated Successfully");
}

if(isset($_GET['delete-material-delivery'])){
    $id = clear($_GET['delete-material-delivery']);
    $stmt = $con->prepare("delete from darlelJobber_material_delivery where id=?");
    $stmt->bind_param("s", $id);
    if(!$stmt->execute()){echo "err";}
    
    header("Location:?m=Data Deleted Successfully");
}
?>
<html lang="en">
	<!--begin::Head-->
	<head>
	    <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyCdk2mdBvjhJmrFA9BWswlJlOz7WoU75-k"></script>
		<?require("./includes/views/head.php");?>
		<style>
	    .modal{
            z-index: 1000;   
        }
        .modal-backdrop{
            z-index: 900;        
        }
	    </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.js"></script>
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    <?if(isset($_GET['m'])){?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
											<path opacity="0.3" d="M2 4V16C2 16.6 2.4 17 3 17H13L16.6 20.6C17.1 21.1 18 20.8 18 20V17H21C21.6 17 22 16.6 22 16V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4Z" fill="currentColor"></path>
											<path d="M18 9H6C5.4 9 5 8.6 5 8C5 7.4 5.4 7 6 7H18C18.6 7 19 7.4 19 8C19 8.6 18.6 9 18 9ZM16 12C16 11.4 15.6 11 15 11H6C5.4 11 5 11.4 5 12C5 12.6 5.4 13 6 13H15C15.6 13 16 12.6 16 12Z" fill="currentColor"></path>
										</svg>
									</span>
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $_GET['m']?></h4>
                                    </div>
                                </div>
                                <?}?>
                                
                                <div class="card card-flush mb-15">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<span class="svg-icon svg-icon-1 position-absolute ms-4">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
													</svg>
												</span>
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Material Delivery " />
											</div>
										</div>
										<div class="card-toolbar">
										    <a id="addMaterialDelivery" href="#" data-bs-toggle="modal" data-bs-target="#add_material_delivery" class="btn btn-primary btn-sm">New Material Delivery</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gy-7 gs-7 w-100 text-center" id="kt_ecommerce_category_table">
    											<thead>
    												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
    													<th >Client</th>
    													<th >Sales Order</th>
    													<th >Schedule Type</th>
    													<th >Start - End</th>
    													<th >Timing</th>
    													<th >Address</th>
    													<th >Attachment</th>
    													<th >Actions</th>
    												</tr>
    											</thead>
    											<tbody class="fw-bold text-gray-600">
    											    <?
        										    $query="select * from darlelJobber_material_delivery order by timeAdded desc";
        										    $requests=getAll($con,$query);
        										    foreach($requests as $row){
        										    ?>
    											    <tr>
    											        <td style="text-align: center;">
    											            <a href="./view_client.php?id=<?echo $row['customerId']?>">
    											                <?
    											                if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
    									                            echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
    											                else   
    											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
    											            </a>
    										            </td>
    											        <td><?echo $row['sales_order']?></td>
    											        <td><?echo $row['schedule_type']?></td>
    											        <td><?if($row['schedule_type']=="Schedule Now"){echo date("d M Y",$row['start_date'])." - ".date("d M Y",$row['end_date']);}else{echo "Schedule Later";}?></td>
    											        <td><?if($row['schedule_type']=="Schedule Now"){echo date("h:i A",strtotime($row['start_time']))."-".date("h:i A",strtotime($row['end_time']));}else{echo "Schedule Later";}?></td>
        										        <td><?echo $row['address']?></td>
    											        <td>
    											            <?$isPdf=0;
                                        	                if(strpos($row['attachment'], ".pdf") !== false)
                                        	                    $isPdf=1;
                                    	                    if(!$isPdf){?>
                                                            <a class="badge badge-success text-white gallery" href="uploads/<?echo $row['attachment']?>">
                                                                <img class="example-image" style="max-height: 2.5rem;" src="uploads/<?echo $row['attachment']?>"/>    
                                                            </a>
                                                            <?}else{?>
                                                            <a class="text-white" target="_blank" href="uploads/<?echo $row['attachment']?>"><?echo $row['attachment']?></a>
    	                                                    <?}?>
											            </td>
    											        <td>
        										            <div class="btn-group">
        										            <?
    										                $row['start_date']=date("Y-m-d",$row['start_date']);
            										        $row['end_date']=date("Y-m-d",$row['end_date']);
            										        $row['start_time']=date("H:i",strtotime($row['start_time']));
            										        $row['end_time']=date("H:i",strtotime($row['end_time']));
                										    ?>
        									                <a id=<?echo $row['id']?> href="#" data-bs-toggle="modal" data-bs-target="#add_material_delivery" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">View/Edit</a>
        									                <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-material-delivery=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>
        											        </div>
        											    </td>
        											</tr>
    											    <?}?>
    											</tbody>
    						</table>
									    </div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
	<?require("./includes/views/footerjs.php");?>
	<script src="assets/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
	
	
		</div>
	</body>
	
	
	
	
	<div class="modal fade" id="add_material_delivery" tabindex="-1" aria-hidden="true">
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
							<div class="row">
    							<div class="col-8">
    							    <h3 id="modelTitle"></h3>
    						    </div>
    							<div class="col-4">
							    	<a class="btn btn-primary btn-sm" href="./addClient.php?new=1&add_client=1&page=materialDelivery">Create New Client</a>
					            </div>
    						</div>   
							<div class="row">
							    <div class="col-12 mt-10">
							        <label>Select Client</label>
							        <select id="select2insidemodal" class="form-select" data-control="select2" data-placeholder="Select A Client" name="customerId">
    							        <option disabled selected>Select Client</option>
    								    <?foreach($clients as $row){?>
    							            <option value="<?echo $row['id']?>" >
    							                <?
    									        if($row['showCompanyName']=="Yes")
    								                echo  $row['company_name']." (".$row['first_name']." ".$row['last_name'].")";
                                                else
    									            echo $row['first_name']." ".$row['last_name'];
    									        ?>
    					                </option>
    								    <?}?>
							        </select>
							    </div>
							    <div class="col-12 mt-10">
							        <label>Address</label>
							        <input type="text" name="address" id="from" class="form-control">
							    </div>
							    <div class="col-12 mt-10">
							        <label>Sales Order</label>
							        <input type="text" name="sales_order" class="form-control">
							    </div>
							    <div class="col-12 mt-10">
							        <label>Attachment</label>
							        <input type="file" name="fileToUpload" class="form-control">
							    </div>
							    <div class="col-12 mt-10">
							        <label>Schedule Type</label>
							        <select name="schedule_type" class="form-control" onchange="changeDisplay()">
							            <option value="Schedule Now">Schedule Now</option>
							            <option value="Schedule Later">Schedule Later</option>
							        </select>
							    </div>
							</div>
							<div id="schedulingSection">
							    <div class="row">
							        <div class="col-6 mt-10">
							            <label>Start Date</label>
							            <input type="date" class="form-control" name="start_date">
							        </div>
							        <div class="col-6 mt-10">
							            <label>End Date</label>
							            <input type="date" class="form-control" name="end_date">
							        </div>
							        <div class="col-6 mt-10">
							            <label>Start Time</label>
							            <input type="time" class="form-control" name="start_time">
							        </div>
							        <div class="col-6 mt-10">
							            <label>End Time</label>
							            <input type="time" class="form-control" name="end_time">
							        </div>
							    </div>
							</div>
						    <input type="text" name="actionId" hidden>
						    <div class="text-center">
								<input  type="submit" value="Save" name="addMaterialDelivery" class="btn btn-primary mt-10">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		
		<script>
		
		var start_date="<?echo date("Y-m-d",time());?>";
		var end_date="<?echo date("Y-m-d",time());?>";
		var start_time="08:00";
		var end_time="09:00";
		
		<?if(isset($_GET['start'])){?>
		    start_date="<?echo date("Y-m-d",strtotime($_GET['start']));?>";
		    end_date="<?echo date("Y-m-d",strtotime($_GET['end']));?>";
		    start_time="<?echo date("H:i",strtotime($_GET['start']));?>";
		    end_time="<?echo date("H:i",strtotime($_GET['end']));?>";
		<?}?>
		
		function changeDisplay(){
            var display=$("select[name='schedule_type']").val();
            if(display=="Schedule Now")
                $('#schedulingSection').show();
            else
                $('#schedulingSection').hide();
        }
	    $(document).ready(function(){
        
            <?if(isset($_GET['id'])){?>
    	    setTimeout(function(){
    	        $("#<?echo $_GET['id']?>")[0].click();
            },1);
        	<?}else if(((isset($_GET['new'])) && (isset($_GET['start']))) || (isset($_GET['customerId']))){?>
        	setTimeout(function(){
    	        $("#addMaterialDelivery")[0].click();
            },1);
        	<?}?>
            $("#select2insidemodal").select2({
                dropdownParent: $("#add_material_delivery")
            });
            
        
        $("#add_material_delivery").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            console.log("mydata", mydata)
            if(mydata!= null){
            	
            	$("#modelTitle").html("View/Edit Material Delivery");
                
                var select = $('#select2insidemodal').select2();
                select.val(mydata['customerId']);
                select.trigger('change');
                
                $("input[name='sales_order']").val(mydata['sales_order'])
                $("select[name='schedule_type']").val(mydata['schedule_type'])
                $("input[name='actionId']").val(mydata['id'])
                
                $("input[name='start_date']").val(mydata['start_date'])
                $("input[name='end_date']").val(mydata['end_date'])
                $("input[name='start_time']").val(mydata['start_time'])
                $("input[name='end_time']").val(mydata['end_time'])
                $("input[name='address']").val(mydata['address'])
                
                changeDisplay();
            
                
            }else{
            	$("#modelTitle").html("Add Material Delivery");
            	
                $("select[name='customerId']").val("Select Client")
                $("input[name='sales_order']").val("")
                $("select[name='schedule_type']").val("Schedule Now")
                
                $("input[name='start_date']").val(start_date)
                $("input[name='end_date']").val(end_date)
                $("input[name='start_time']").val(start_time)
                $("input[name='end_time']").val(end_time)
                $("input[name='actionId']").val("")
                $("input[name='address']").val("")
                
                <?if(isset($_GET['customerId'])){?>
                var select = $('#select2insidemodal').select2();
                select.val("<?echo clear($_GET['customerId']);?>");
                select.trigger('change');
                <?}?>
                
            }
        });
    })
	
	
        function initialize() {
          
          var input = document.getElementById('from');
          if(input=="")
            return false;
          var options = {
            types: ['address'],
          };
          autocomplete = new google.maps.places.Autocomplete(input, options);
          google.maps.event.addListener(autocomplete, 'place_changed', function() {
            var place = autocomplete.getPlace();
          });
        }
        google.maps.event.addDomListener(window, "load", initialize);
        
        
        document.addEventListener('DOMContentLoaded', function() {
            const gallery = new SimpleLightbox('.gallery', {});
        });
    </script>
	

	
</html>