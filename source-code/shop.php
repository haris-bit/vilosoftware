<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");
    
$orderId=$_GET['orderId'];
$allUsers=getAll($con,"SELECT * from darlelJobber_users where role!='Customer'");
$idToInfo=[];
foreach($allUsers as $row){$idToInfo[$row['id']]=$row;}
if(isset($_POST['addOrder'])){
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $image=clear($_POST['image']);
    $actionId=clear($_POST['actionId']);
    $timeAdded=time();
    $id=generateRandomString();
    
    if($actionId==""){
        $query="insert into darlelJobber_shop_orders set id='$id',title='$title',description='$description',timeAdded='$timeAdded',status='New',addedBy='$session_id'";
        $entryId=$id;
        
        //notifying shop admin about new order
        $title="New Order Added";
        $description="You have received a new order on your shop . Click To View";
        $url=$projectUrl."shop.php";
        $shopAdmins=getAll($con,"select * from darlelJobber_users where role='Shop Admin'");
        foreach($shopAdmins as $row){
            setNotification($title,$description,$row['id'],$url);
        }
    }
    else{
        $query="update darlelJobber_shop_orders set title='$title',description='$description' where id='$actionId'";
        $entryId=$actionId;
    }
    runQuery($query);
    
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $image=htmlspecialchars( basename( $_FILES["fileToUpload"]["name"]));
        if($image!=""){
            $query="update darlelJobber_shop_orders set image='$image' where id='$entryId'";
            runQuery($query);
        }
    }
    header("Location:./shop.php?m=Data Has Been Successfully Updated");
}

if(isset($_GET['delete-order'])){
    //this means that this is purchasing order
    if(isset($_GET['orderId'])){
        $query="delete from darlelJobber_order_details where purchasing_id='$id'";
        runQuery($query);
    }
    
    $id = clear($_GET['delete-order']);
    $query="delete from darlelJobber_shop_orders where id='$id'";
    runQuery($query);
    
    header("Location:./shop.php?m=Order Has Been Successfully Deleted");
}

if(isset($_GET['completed'])){
    $id = clear($_GET['completed']);
    $orderDeets=getRow($con,"select * from darlelJobber_shop_orders where id='$id'");
    $title="Order Completed";
    $date=date("d M y",$orderDeets['timeAdded']);
    $description="The order that you placed on $date has been completed. Click To View";
    $url=$projectUrl."shop.php";
    setNotification($title,$description,$orderDeets['addedBy'],$url);
    
    $query="update darlelJobber_shop_orders set status='Completed' where id='$id' ";
    runQuery($query);
    header("Location:./shop.php?m=Order Has Been Successfully Completed");
}

?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
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
					
					
					<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    <?if(isset($_GET['m'])){?>
                                <div class="alert alert-dismissible bg-<?if($m=="e_taken" || $m=="deleted"){echo "danger";}else{echo "success";}?> d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
											<path opacity="0.3" d="M2 4V16C2 16.6 2.4 17 3 17H13L16.6 20.6C17.1 21.1 18 20.8 18 20V17H21C21.6 17 22 16.6 22 16V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4Z" fill="currentColor"></path>
											<path d="M18 9H6C5.4 9 5 8.6 5 8C5 7.4 5.4 7 6 7H18C18.6 7 19 7.4 19 8C19 8.6 18.6 9 18 9ZM16 12C16 11.4 15.6 11 15 11H6C5.4 11 5 11.4 5 12C5 12.6 5.4 13 6 13H15C15.6 13 16 12.6 16 12Z" fill="currentColor"></path>
										</svg>
									</span>
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $_GET['m'];?></h4>
                                    </div>
                                    <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                                        <span class="svg-icon svg-icon-2x svg-icon-light">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
												<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect>
												<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect>
											</svg>
										</span>
                                    </button>
                                </div>
                                <?}?>


                                <?if(!isset($_GET['orderId'])){?>
								<div class="card card-flush">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
										    <div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Orders" />
											</div>
										</div>
										<div class="card-toolbar">
										    <?if($session_role=="Drafter"||$session_role=="Material Drafter"|| $session_role=="Admin"){?>
										    <a href="#" data-bs-toggle="modal" data-bs-target="#add_order" class="btn btn-primary btn-sm">Add Order</a>
										    <?}?>
									    </div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gy-7 gs-7" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0" style="border: 0;border-bottom: 1px solid black;color: black !important;">
												    <td>Title</td>
												    <td>Description</td>
												    <td>Attachment</td>
												    <td>Status</td>
												    <td>Time Added</td>
												    <td>Added By</td>
												    <td>Actions</td>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?
											    
											    $query="select * from darlelJobber_shop_orders where orderId='None' order by timeAdded desc";
											    if($session_role=="Drafter" || $session_role=="Material Drafter")
											        $query="select * from darlelJobber_shop_orders where orderId='None' && addedBy='$session_id' order by timeAdded desc";
											    $orders=getAll($con,$query);
											    foreach($orders as $row){?>
											    <tr>
											        <td><?echo $row['title']?></td>
											        <td><?echo $row['description']?></td>
											        <td><a target="_blank" href="./uploads/<?echo $row['image']?>"><?echo $row['image']?></a></td>
											        <td>
											            <?if($row['status']=="New"){echo "<span class='badge badge-warning'>New</span>";}
											            else if($row['status']=="Completed"){echo "<span class='badge badge-success'>Completed</span>";}?>
											        </td>
											        <td><?echo date("d M y",$row['timeAdded']);?></td>
											        <td><?echo $idToInfo[$row['addedBy']]['name'];?></td>
											        <td>
											            <div class="btn-group">
											            <?if($session_role=="Shop Admin" || $session_role=="Admin"){?>
											                <?if($row['status']!='Completed'){?>
											                    <a class="btn btn-primary btn-sm" href="?completed=<?echo $row['id']?>">Mark As Completed</a>
											                <?}?>
											                <a href="./shop.php?orderId=<?echo $row['id']?>" class="btn btn-primary btn-sm" >Purchasing Orders</a>
											            <?}?>
											            <a class="btn btn-warning btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#add_order" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
											            <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-order=<?echo $row['id']?>">Delete</a>
    											        </div>
    											    </td>
											    </tr>
											    <?}?>
											</tbody>
										</table>
									    </div>
									</div>
								</div>
								<?}else if(isset($_GET['orderId'])){?>
								<div class="card card-flush">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
    										Purchasing Orders
										</div>
										<div class="card-toolbar">
										    <a href="./purchasing_order.php?orderId=<?echo $orderId;?>" class="btn btn-primary btn-sm">Add Purchasing Order</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gy-7 gs-7" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0" style="border: 0;border-bottom: 1px solid black;color: black !important;">
												    <td>Title</td>
												    <td>Total</td>
												    <td>Time Added</td>
												    <td>Added By</td>
												    <td>Actions</td>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?$purhcasing_orders=getAll($con,"select * from darlelJobber_shop_orders where orderId='$orderId'");
											    foreach($purhcasing_orders as $row){?>
											    <tr>
											        <td><?echo $row['title'];?></td>
											        <td><?echo $row['total'];?></td>
											        <td><?echo date("d M y",$row['timeAdded']);?></td>
											        <td><?echo $idToInfo[$row['addedBy']]['name'];?></td>
											        <td>
										                <div class="btn-group">
    											           <a class="btn btn-warning btn-sm" href="./purchasing_order.php?orderId=<?echo $orderId?>&purchasing_id=<?echo $row['id']?>">Edit</a>
    											           <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-order=<?echo $row['id']?>&orderId=<?echo $orderId?>">Delete</a>
    											        </div>
    											    </td>
											    </tr>
											    <?}?>
											</tbody>
										</table>
									    </div>
									</div>
								</div>
								<?}?>
							</div>
						</div>
					</div>
					<?require("./includes/views/footer.php");?>
				</div>
			</div>
			
	<?require("./includes/views/footerjs.php");?>
	
	
		</div>
	</body>
	
	<!--order modal-->
	<div class="modal fade" id="add_order" tabindex="-1" aria-hidden="true">
			
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
							            <h1 class="mb-3" id="modelTitle"></h1>
							        </div>
							    </div>
							    
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Title</span>
								</label>
								<input type="text" name="title" class="form-control" placeholder="Enter Title">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Description</span>
								</label>
								<textarea class="form-control" name="description" placeholder="Enter Description"></textarea>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Image</span>
									<small id="image_display">
									    <a id="a_tag" target="_blank" href="./uploads/">View Previously Uploaded Image</a>
									</small>
								</label>
								<input type="file" name="fileToUpload" class="form-control" placeholder="Enter Price">
							</div>
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input type="submit" value="Save" name="addOrder" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	<script>
	    $(document).ready(function(){
        $("#add_order").on('show.bs.modal', function (e) {
            //get data-id attribute of the clicked element
            var mydata = $(e.relatedTarget).data('mydata');
            console.log(mydata);
            //console.log("mydata", mydata)
            if(mydata!= null){
            	$("#modelTitle").html("Update Order");
                $("input[name='title']").val(mydata['title'])
                $("textarea[name='description']").val(mydata['description'])
                $("input[name='actionId']").val(mydata['id'])
                if(mydata['image']!=null){
                    $("#image_display").show();
                    $("#a_tag").attr("href", "./uploads/"+mydata['image'])
                }
                else
                    $("#image_display").hide();
            }else{
				$("#modelTitle").html("Add Order");
				$("input[name='title']").val("")
				$("textarea[name='description']").val("")
                $("input[name='actionId']").val("")
                $("#image_display").hide();
            }
        });
	    })
	</script>
</html>