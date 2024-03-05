<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

$users=getAll($con,"select * from darlelJobber_users where role='Client'");
foreach($users as $row){
    $idToName[$row['id']]=$row;
}

$idToProperty=array();
$properties=getAll($con,"select * from darlelJobber_properties");
foreach($properties as $row)
{ $idToProperty[$row['id']]=$row;}


if(isset($_POST['addProperty'])){
    $street1=clear($_POST['street1']);
    $street2=clear($_POST['street2']);
    $city=clear($_POST['city']);
    $state=clear($_POST['state']);
    $zip_code=clear($_POST['zip_code']);
    $country=clear($_POST['country']);
    $actionId=clear($_POST['actionIdProperty']);
    $timeAdded=time();
    $random=generateRandomString();
    
    if($actionId=="")
        $query="insert into darlelJobber_properties set id='$random',userId='$session_id',street1='$street1',street2='$street2',
        city='$city',state='$state',zip_code='$zip',country='$country',type='secondary',timeAdded='$timeAdded'";
    else
        $query="update darlelJobber_properties set street1='$street1',street2='$street2',
        city='$city',state='$state',zip_code='$zip',country='$country' where id='$actionId'";
    
    runQuery($query);
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
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<!--end::Toolbar-->
						<!--begin::Post-->
						<div class="post d-flex flex-column-fluid" id="kt_post">
							
							
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
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
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Properties " />
											</div>
										</div>
										<div class="card-toolbar">
										    <a href="#" data-bs-toggle="modal" data-bs-target="#add_property" class="btn btn-primary btn-sm">Add Property</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <table class="table align-middle table-row-dashed fs-6 gy-5 table-bordered" id="kt_ecommerce_category_table">
										    <thead>
										        <tr>
										            <th>Street 1</th>
										            <th>Street 1</th>
										            <th>City</th>
										            <th>State</th>
										            <th>Zip Code</th>
										            <th>Country</th>
										            <th>Actions</th>
										        </tr>
										    </thead>
										    <tbody>
										        <?$properties=getAll($con,"select * from darlelJobber_properties where userId='$session_id' order by timeAdded desc");
										        foreach($properties as $row){?>
    										        <tr>
    										           <td><?echo $row['street1']?></td>
    										           <td><?echo $row['street2']?></td>
    										           <td><?echo $row['city']?></td>
    										           <td><?echo $row['state']?></td>
    										           <td><?echo $row['zip_code']?></td>
    										           <td><?echo $row['country']?></td> 
    										           =<td>
    										               <a href="#" data-bs-toggle="modal" data-bs-target="#add_property" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">Edit</a>
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
					
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
	<?require("./includes/views/footerjs.php");?>
	
	
		</div>
	</body>
	
	
	<div class="modal fade" id="add_property" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered mw-650px" style="max-width:1000px !important;">
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
							    
    							<div class="col-xs-12 col-sm-12 col-lg-12">
        							<div class="mb-13 text-left">
        							    <h1 class="mb-3" id="modelTitle"></h1>
        							</div>
    							</div>
							</div>
							<div class="row g-9 mb-8">
								<div class="col-md-12 fv-row">
									<input onchange="fillPropertyDetails()"  type="text" class="form-control form-control-solid"  placeholder="Street 1" name="street1" id="from" />
								</div>
								<div class="col-md-12 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Street 2" name="street2" />
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="City" name="city" />
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="State" name="state" />
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Zip Code" name="zip_code" id="zip_code"/>
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Country" name="country" />
								</div>
							</div>
						    <input type="text" name="actionIdProperty" hidden>
							<div class="text-center">
								<input  type="submit" value="Save" name="addProperty" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		<script>
	    $(document).ready(function(){
            
        $("#add_property").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            
            if(mydata!= null){
            	$("#modelTitle").html("Update Property Details");
            	$("input[name='street1']").val(mydata['street1'])
                $("input[name='street2']").val(mydata['street2'])
                $("input[name='city']").val(mydata['city'])
                $("input[name='state']").val(mydata['state'])
                $("input[name='zip']").val(mydata['zip_code'])
                $("input[name='country']").val(mydata['country'])
                $("input[name='actionIdProperty']").val(mydata['id'])
           
            }else{
            	$("#modelTitle").html("Add Property");
            	$("input[name='street1']").val("")
                $("input[name='street2']").val("")
                $("input[name='city']").val("")
                $("input[name='state']").val("")
                $("input[name='zip']").val("")
                $("input[name='country']").val("")
                $("input[name='actionIdProperty']").val("")
            }
        });
	    })
    </script>
</html>