<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");


$current_url = $_SERVER['REQUEST_URI'];
$new_param = 'm=Data updated successfully';
if (strpos($current_url, '?') !== false) 
    $updated_url = $current_url . '&' . $new_param;
else 
    $updated_url = $current_url . '?' . $new_param;

if(isset($_POST['addService'])){
    $name=clear($_POST['name']);
    $type=clear($_POST['type']);
    $tearDownPrice=clear($_POST['tearDownPrice']);
    $description=clear($_POST['description']);
    $price=clear($_POST['price']);
    $image=clear($_POST['image']);
    $short_title=clear($_POST['short_title']);
    $sku=clear($_POST['sku']);
    $actionId=clear($_POST['actionId']);
    $timeAdded=time();
    $id=generateRandomString();
    
    if($actionId==""){
        $query="insert into darlelJobber_services set id='$id',sku='$sku',tearDownPrice='$tearDownPrice',name='$name',short_title='$short_title',type='$type',description='$description',price='$price',timeAdded='$timeAdded'";
        $entryId=$id;
    }
    else{
        $query="update darlelJobber_services set name='$name',sku='$sku',tearDownPrice='$tearDownPrice',short_title='$short_title',type='$type',description='$description',price='$price' where id='$actionId'";
        $entryId=$actionId;
    }
    runQuery($query);
    
    $target_dir = "servicesImages/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $image=htmlspecialchars( basename( $_FILES["fileToUpload"]["name"]));
        if($image!=""){
            $query="update darlelJobber_services set image='$image' where id='$entryId'";
            runQuery($query);
        }
    }
    header("Location:$updated_url" );
}


if(isset($_GET['delete-service'])){
    $id = clear($_GET['delete-service']);
    $query="delete from darlelJobber_services where id='$id'";
    runQuery($query);
    header("Location:?m=Service Has Been Deleted Successfully !" );
}

//showing services 
$page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
$lastEntryNo=!empty($_GET['lastEntryNo']) ? (int) $_GET['lastEntryNo'] : 0;
$firstEntryNo=!empty($_GET['firstEntryNo']) ? (int) $_GET['firstEntryNo'] : 0;

$searchEnabled=0;
if(isset($_GET['search'])){
    $searchEnabled=1;
    $search=clear($_GET['search']);
}

$searchTerms = explode(" ", $search); 
$conditions = array();

foreach ($searchTerms as $term) {
    $conditions[] = "name LIKE '%$term%'";
}
    
//if no search then
if(!$searchEnabled)
    $totalPages=getRow($con,"select count(id) as totalPages,max(autoIncrement) as maxAutoInc from darlelJobber_services where localUseId='None'");
//if search then calculate total page for that search result
else if($searchEnabled){
    $query="select count(id) as totalPages,max(autoIncrement) as maxAutoInc from darlelJobber_services where localUseId='None'
    and ".implode(" AND ", $conditions)." or type like '%$search%' or description like '%$search%' or price like '%$search%' or short_title like '%$search%' or sku like '%$search%'";
    $totalPages=getRow($con,$query);
}
 
$maxAutoInc=$totalPages['maxAutoInc']+1;//adding plus one to display correct results 
$totalPages=ceil($totalPages['totalPages']/10);

//if new page or next page is opened then reference point is last entry no
if((!isset($_GET['page'])) || (isset($_GET['lastEntryNo']))){
    $query="select * from darlelJobber_services where autoIncrement > $lastEntryNo and localUseId='None' order by autoIncrement asc limit 10";
    if($searchEnabled)
        $query="select * from darlelJobber_services where autoIncrement > $lastEntryNo and localUseId='None'and 
        (".implode(" AND ", $conditions)." or type like '%$search%' or description like '%$search%' or price like '%$search%' or short_title like '%$search%' or sku like '%$search%')
        order by autoIncrement asc limit 10";
}
else{//means that previous page is opened
    $query="select * from darlelJobber_services where autoIncrement < $firstEntryNo and localUseId='None' order by autoIncrement desc limit 10";
    if($searchEnabled)
        $query="select * from darlelJobber_services where autoIncrement < $firstEntryNo and localUseId='None' and 
        (".implode(" AND ", $conditions)." or type like '%$search%' or description like '%$search%' or price like '%$search%' or short_title like '%$search%' or sku like '%$search%')
        order by autoIncrement desc limit 10";
}
$displayServices=getAll($con,$query);

//this would be passed in pagination 
$lastEntryNo=max(array_column($displayServices, 'autoIncrement'));
$firstEntryNo=min(array_column($displayServices, 'autoIncrement'));

?>

<html lang="en">
	<head>
	    <link rel="stylesheet" href="lightbox/dist/css/lightbox.min.css">
        <?require("./includes/views/head.php");?>
	</head>
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
							    
							    <?if(isset($_GET['m'])){$m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                    </div>
                                </div>
                                <?}?>
                                
                                <div class="card card-flush mb-20 mt-10">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
										    Services	
										</div>
										<form method="get" action="" enctype="multipart/form-data" style="margin-top: 10px;">
            							<div class="card-toolbar">
										    <div class="d-flex align-items-center">
    										    <div class="btn-group">
        										    <?$tempSearch = urldecode($_GET['search']);?>
        										    <input type="text" name="search" class="form-control" placeholder="Input Search" value="<?echo $tempSearch;?>">
        										    <input type="submit" class="btn btn-primary" value="Search">
            									    <?if($isAdmin){?>
            									    <a href="#" data-bs-toggle="modal" data-bs-target="#add_service" class="btn btn-warning " style="white-space: nowrap;">Add Service</a>
        										    <?}?>
        										</div>
    								        </div>
										</div>
										</form>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gs-7 text-center">
										    <thead>
										        <tr>
										            <th>Name</th>
										            <th>Short Title</th>
										            <th>Description</th>
										            <th>Unit Price</th>
										            <th>Tear Down Price</th>
										            <th>SKU</th>
										            <th>Image</th>
										            <?if($isAdmin){?>
            									    <th>Actions</th>
										            <?}?>
										        </tr>
										    </thead>
										    <tbody>
										        
										        <?foreach($displayServices as $row){?>
										        <tr>
										            <td><?echo $row['name']?></td>
										            <td><?echo $row['short_title']?></td>
										            <td><?echo $row['description']?></td>
										            <td><?echo $row['price']?></td>
										            <td><?echo $row['tearDownPrice']?></td>
										            <td><?echo $row['sku']?></td>
										            <td>
									                    <a class="example-image-link" href="servicesImages/<?echo $row['image']?>" data-lightbox="example-1"><img class="example-image" style="max-height: 4.3755rem;" src="./servicesImages/<?echo $row['image']?>" onerror="this.style.display='none'" /></a>
                                                    </td>
										            <td>
										                <?if($isAdmin){?>
            									        <div class="btn-group">
										                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_service" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">Edit</a>
        													<a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-service=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>
        												</div>
        												<?}?>
    												</td>
                                                </tr>
                                                <?}?>
										    </tbody>
										</table>
										
										<!--pagination section-->
										
    									<div class="row w-100">
    										<nav aria-label="Page navigation example mt-3" style="margin: 1px auto;">
    											<ul class="pagination justify-content-center">
                                                    <style>
    													.selected-page-item {
    														border-bottom-color: #591df1;
    														border-bottom-width: 5px;
    														border-bottom-style: solid;
    													}
    
    													.selected-page-link {
    														border: 1px solid #591df1;
    														background: white;
    														color: #591df1;
    														margin-bottom: 25px;
    														border-width: 2px;
    													}
    												</style>
                                                    <?
                                                    if($page >= 3){
                                                        ?>
                                                        <!--first page block-->
                                                        <li class="page-item">
														    <a class="page-link" href="?page=1&lastEntryNo=0">1</a>
														</li>
														<b style="font-weight: bold;font-size: x-large;margin-left: 10px;margin-right: 10px;">....</b>
                                                        <?
                                                    }
                                                    
                                                    ?>
    												<? for ($i = ($page - 1); $i < ($page + 2); $i++) {
    													if ($i > 0 && $i <= $totalPages) {
    													    $lastRenderedPage=$i;
    													?>
    														<li class="page-item <? if ($page == $i) {echo "selected-page-item";} ?>">
															    <a class="page-link 
    															    <?
    															    $doNothing=0;
    															    if ($page == $i) {echo "selected-page-link";}
    															    //the pages ahead of the selected page will send their last entry no and the pages before the selected page will send their first entry no
    															    if($page > $i)
    															        $href="?page=$i&firstEntryNo=$firstEntryNo";
    															    else if($page < $i)
    															        $href="?page=$i&lastEntryNo=$lastEntryNo";
    															    else
    															        $doNothing=1;//hides the link if the on current page
    															    ?>" 
        															<?if(!$doNothing){?>href="<?echo $href?><?if($searchEnabled){echo "&search=$search";}?>"<?}?>>
															        <? echo $i ?>
    															</a>
															</li>
    												<? }}
    												if($totalPages-$lastRenderedPage >= 1){
    												    ?>
    												    <!--last page block-->
    												    <b style="font-weight: bold;font-size: x-large;margin-left: 10px;margin-right: 10px;">....</b>
                                                        <li class="page-item">
														    <a class="page-link" href="?page=<?echo $totalPages?>&firstEntryNo=<?echo $maxAutoInc?><?if($searchEnabled){echo "&search=$search";}?>"><?echo $totalPages?></a>
														</li>
    												    <?}?>
												</ul>
    										</nav>
                                        </div>
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
	
        <script src="lightbox/dist/js/lightbox-plus-jquery.min.js"></script>
	
	
		</div>
	</body>
	<div class="modal fade" id="add_service" tabindex="-1" aria-hidden="true">
			
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
									<span class="required">Item Type</span>
								</label>
								<select class="form-control" name="type">
								    <option value="">---Select Item Type---</option>
								    <option value="Service">Service</option>
								    <option value="Product">Product</option>
								</select>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Name</span>
								</label>
								<input type="text" name="name" class="form-control" placeholder="Enter Name">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Short Title</span>
								</label>
								<input type="text" name="short_title" class="form-control" placeholder="Enter Short Title">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Description</span>
								</label>
								<textarea class="form-control" name="description" placeholder="Enter Description"></textarea>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Unit Price</span>
								</label>
								<input type="number" name="price" class="form-control" placeholder="Enter Price">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Tear Down Price</span>
								</label>
								<input type="number" name="tearDownPrice" step="0.01" class="form-control" placeholder="Enter Tear Down">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">SKU</span>
								</label>
								<input type="text" name="sku" class="form-control" placeholder="Enter SKU">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Image</span>
									<small id="image_display">
									    <a id="a_tag" target="_blank" href="./servicesImages/">View Previously Uploaded Image</a>
									</small>
								</label>
								<input type="file" name="fileToUpload" class="form-control" placeholder="Enter Price">
							</div>
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input type="submit" value="Save" name="addService" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
	    $(document).ready(function(){
        $("#add_service").on('show.bs.modal', function (e) {
            //get data-id attribute of the clicked element
            var mydata = $(e.relatedTarget).data('mydata');
            console.log(mydata);
            //console.log("mydata", mydata)
            if(mydata!= null){
            	$("#modelTitle").html("Update Service");
                $("input[name='name']").val(mydata['name'])
                $("input[name='short_title']").val(mydata['short_title'])
                $("select[name='type']").val(mydata['type'])
                $("textarea[name='description']").val(mydata['description'])
                $("input[name='price']").val(mydata['price'])
                $("input[name='tearDownPrice']").val(mydata['tearDownPrice'])
                $("input[name='actionId']").val(mydata['id'])
                $("input[name='sku']").val(mydata['sku'])
                if(mydata['image']!=""){
                    $("#image_display").show();
                    $("#a_tag").attr("href", "./servicesImages/"+mydata['image'])
                }
                else
                    $("#image_display").hide();
            }else{
				$("#modelTitle").html("Add Service");
				$("input[name='name']").val("")
				$("input[name='short_title']").val("")
                $("select[name='type']").val("")
                $("textarea[name='description']").val("")
                $("input[name='price']").val("0")
                $("input[name='tearDownPrice']").val("2.5")
                $("input[name='actionId']").val("")
                $("input[name='sku']").val("")
                $("#image_display").hide();
            }
        });
	    })
	</script>
</html>