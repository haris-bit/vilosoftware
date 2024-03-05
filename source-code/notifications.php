<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

if(isset($_GET['mark_all_read'])){
    $query="update darlelJobber_notifications set status='Read' where userId='$session_id'";
    runQuery($query);
    header("Location:?m=All Notifications have been marked as read");
}

if(isset($_GET['readNotification'])){
    $id=clear($_GET['readNotification']);
    $query="update darlelJobber_notifications set status='Read' where id='$id'";
    runQuery($query);
    header("Location:?m=Notification has been marked as read");
}

$users=getAll($con,"select * from darlelJobber_users where role='Client'");
foreach($users as $row)
{$idToName[$row['id']]=$row;}
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
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<!--end::Toolbar-->
						<!--begin::Post-->
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
						        <?if(isset($_GET['m'])){?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo clear($_GET['m'])?></h4>
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



								<div class="card card-flush">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<span class="svg-icon svg-icon-1 position-absolute ms-4">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
													</svg>
												</span>
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Requests " />
											</div>
										</div>
										<div class="card-toolbar">
											<a href="?mark_all_read=1" class="btn btn-warning btn-sm">Mark All As Read</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gy-7 gs-7" id="kt_ecommerce_category_table">
										<thead>
											<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
												<th style="text-align: center;">Title</th>
												<th>Description</th>
												<th>Time Added</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody class="fw-bold text-gray-600">
										    <?$requests=getAll($con,"select * from darlelJobber_notifications where userId='$session_id' and status!='Read'  order by timeAdded desc");
										    foreach($requests as $row){?>
										    <tr>
										        <td style="text-align: center;"><?echo $row['title'];if($row['status']=="New"){echo " <span style='margin-left: 10px;font-size: unset;' class='badge badge-light-success'>New</span>";};?></td>
										        <td><a href="<?echo $row['url']?>"><?echo $row['description']?></a></td>
										        <td><?echo date("d M y",$row['timeAdded']);?></td>
										        <td >
												    <a href="?readNotification=<?echo $row['id']?>" class="btn btn-success btn-sm">Read</a>
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
	
	
		</div>
	</body>
</html>