<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

if(isset($_POST['addLabel'])){
    $timeAdded = time();
    $actionId = clear(($_POST['actionId']));
    $title = clear($_POST['title']);    
    if ($title[strlen($title) - 1] === ' ')
        $title = rtrim($title);
    

    $colorCode = clear($_POST['colorCode']);    
    if($actionId==""){
        $id = random();
        $actionId = $id;
        $query = "insert into darlelJobber_labels set id='$id' ,colorCode='$colorCode' , title='$title', timeAdded='$timeAdded'";
    }else{
        $query = "update darlelJobber_labels set title='$title',colorCode='$colorCode' where id='$actionId'";
    }
    runQuery($query);
    header("Location: ?m=Data was saved successfully!");
}
    
if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    $query="delete from darlelJobber_labels where id='$id'";
    runQuery($query);
    header("Location: ?m=Data has been updated successfully!");
}

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
							    <div class="card card-flush">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Labels" />
											</div>
										</div>
										<div class="card-toolbar">
											<a href="#" data-bs-toggle="modal" data-bs-target="#labelModal" class="btn btn-primary">Add Label</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gy-7 gs-7" id="kt_ecommerce_category_table">
										        <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Color Code</th>
                                                        <th>Time Added</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <? $query = "select * from darlelJobber_labels t order by t.timeAdded desc";
                                                $results = getAll($con, $query);
                                                    foreach($results as $row){?>
                                                        <tr>
                                                            <td><?echo $row['title']?></td>
                                                            <td><a style="background-color:<?echo $row['colorCode']?> !important" class="btn btn-primary btn-sm">Color Code</a></td>
                                                            <td><?echo date("d M Y",$row['timeAdded'])?></td>
                                                            <td >
                                                                    <div class="btn-group">
                                                                        <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#labelModal" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' >Edit</a>
                                                                   <!--     <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete_record" data-url="?<?if(isset($_GET['id'])){echo "id=".$_GET['id']."&";}?>delete-record=<?echo $row['id']?>">Delete</a>
                                                                   --> </div>
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
				</div>
			</div>
				<script src="lightbox/dist/js/lightbox-plus-jquery.min.js"></script>
        
			<?require("./includes/views/footerjs.php");?>
		
	    </div>
	</body>
	
	
	
	
	
	<div class="modal fade" id="labelModal" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-850px">
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
							    <h1 class="mb-3" id="modelTitle"></h1>
							</div>

                            <div>
                            <div class="form-group mb-5 notEdit">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control"   >
                            </div>
                            
                            <div class="form-group mb-5">
                                <label>Color Code</label>
                                <input type="color" name="colorCode" class="form-control"   >
                            </div>
                            	
                            <input type="text" name="actionId" value="" hidden>
                            
                            </div>
							<div class="text-center">
								<input type="submit" value="Save" name="addLabel" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	    <script>
	    $(document).ready(function(){
        $("#labelModal").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            console.log("mydata->",mydata);
            if(mydata!= null){
                $('.notEdit').hide();
                $("#modelTitle").html("Update Label");            	
                $("input[name='title']").val(mydata['title'])                                
                $("input[name='actionId']").val(mydata['id'])                                
                $("input[name='colorCode']").val(mydata['colorCode'])
            }else{
                $('.notEdit').show();
                $("#modelTitle").html("Add Label");
                $("input[name='title']").val("")
                $("input[name='actionId']").val("")
                $("input[name='colorCode']").val("#73141d")
            }
        });
    })
</script>
	
	
	
</html>