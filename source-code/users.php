<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");
if(isset($_POST['addUser'])){
    $name=clear($_POST['name']);
    $email=clear($_POST['email']);
    $actionId=clear($_POST['actionId']);
    $role=clear($_POST['role']);
    $colorCode=clear($_POST['colorCode']);
    $phone=clear($_POST['phone']);
    $password=clear($_POST['password']);
    $isColorNeeded=clear($_POST['isColorNeeded']);
    
    $id=generateRandomString();
    $timeAdded=time();
    
    if($actionId==""){
        $query="insert into darlelJobber_users set id='$id',phone='$phone',isColorNeeded='$isColorNeeded',colorCode='$colorCode',name='$name',email='$email',password='$password',role='$role',timeAdded='$timeAdded'";
        runQuery($query);
        
        header("Location:./users.php?m=added");
        $subject="Welcome To Jobber";
        $message="You have been added to Vilo Software as $role <br> You can access the system from the following link and credentials<br>
        Website : <a href='$projectUrl'>Login Vilo Software</a> <br>
        Login Credentials <br>
        Email : $email <br>
        Password : $password<br>";
        sendEmailNotification_mailjet($subject, $message, $email);
    }
    else{
        $query="update darlelJobber_users set name='$name',phone='$phone',isColorNeeded='$isColorNeeded',colorCode='$colorCode',email='$email',password='$password',role='$role' where id='$actionId'";
        runQuery($query);
        header("Location:./users.php?m=updated");
    }
}

if(isset($_GET['delete-user'])){
    $id = clear($_GET['delete-user']);
    $query="delete from darlelJobber_users where id='$id'";
    runQuery($query);
    
    header("Location:./users.php?m=deleted");
}
?>
<html lang="en">
	<head>
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
						<!--end::Toolbar-->
						<!--begin::Post-->
						<div class="post d-flex flex-column-fluid" id="kt_post">
							
							
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    <?$m=clear($_GET['m']);
                                if($m=="added" ||  $m=="updated" || $m=="deleted" || $m=="e_taken"){?>
                                <div class="alert alert-dismissible bg-<?if($m=="e_taken" || $m=="deleted"){echo "danger";}else{echo "success";}?> d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
											<path opacity="0.3" d="M2 4V16C2 16.6 2.4 17 3 17H13L16.6 20.6C17.1 21.1 18 20.8 18 20V17H21C21.6 17 22 16.6 22 16V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4Z" fill="currentColor"></path>
											<path d="M18 9H6C5.4 9 5 8.6 5 8C5 7.4 5.4 7 6 7H18C18.6 7 19 7.4 19 8C19 8.6 18.6 9 18 9ZM16 12C16 11.4 15.6 11 15 11H6C5.4 11 5 11.4 5 12C5 12.6 5.4 13 6 13H15C15.6 13 16 12.6 16 12Z" fill="currentColor"></path>
										</svg>
									</span>
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <?if($m=="added"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">New user has been added successfully</h4>
                                        <?}else if ($m=="updated"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">User information has been updated successfully</h4>
                                        <?}else if ($m=="deleted"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">User information has been deleted successfully</h4>
                                        <?}?>
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



								<div class="card card-flush mb-20">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
										    <div class="d-flex align-items-center position-relative my-1">
												<span class="svg-icon svg-icon-1 position-absolute ms-4">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
														<rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
														<path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
													</svg>
												</span>
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Users" />
											</div>
										</div>
										<div class="card-toolbar">
											<a href="#" data-bs-toggle="modal" data-bs-target="#add_user" class="btn btn-primary btn-sm">Add User</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gy-7 gs-7" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0" style="border: 0;border-bottom: 1px solid black;color: black !important;">
												    <td>Name</td>
												    <td>Email</td>
												    <td>Phone</td>
												    <td>Password</td>
												    <td>Role</td>
												    <td>Color Code</td>
												    <td>Actions</td>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?$users=getAll($con,"select * from darlelJobber_users where role!='Client' && email!='admin@portal.com' order by timeAdded desc");
											    foreach($users as $row){?>
											    <tr>
											        <td><?echo $row['name']?></td>
											        <td><?echo $row['email']?></td>
											        <td><?echo $row['phone']?></td>
											        <td><?echo $row['password']?></td>
											        <td><?echo $row['role']?></td>
											        <td>
											            <?if($row['isColorNeeded']=="Yes"){?>
											            <a class="btn btn-primary btn-sm" style="background-color: <?echo $row['colorCode'];?> !important;">Applicable</a>
											            <?}else if($row['isColorNeeded']=="No"){echo "Not Applicable";}?>
											        </td>
											        <td>
											            <div class="btn-group">
    												        <?if($session_role=="Admin"){?>
												            <a href="tasks.php?userId=<?echo $row['id']?>" class="btn btn-primary btn-sm">View Tasks</a>
    												        <?}?>
    												        <a href="#" data-bs-toggle="modal" data-bs-target="#add_user" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">View/Edit</a>
        									                <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-user=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>
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
				</div>
			</div>
			
	<?require("./includes/views/footerjs.php");?>
	
	
		</div>
	</body>
	<div class="modal fade" id="add_user" tabindex="-1" aria-hidden="true">
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
						<form id="myform" action="" method="post">
							<div class="mb-13 text-left">
							    <div class="row">
							        <div class="col-9">
							            <h1 class="mb-3" id="modelTitle"></h1>
							        </div>
							    </div>
							
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Name</span>
								</label>
								<input type="text" class="form-control form-control-solid" placeholder="Full Name" name="name" required />
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Email</span>
								</label>
								<input type="email" class="form-control form-control-solid" placeholder="Email Address" name="email" required />
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Phone</span>
								</label>
								<input type="text" class="form-control form-control-solid" placeholder="Phone Number" name="phone" required />
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Role</span>
								</label>
								<select style="background-color: #f5f8fa;" class="form-select" name="role" required>
							        <?$array=array("Admin","Crew Supervisor","Installation Crew","Shop Manager","Drafting","Estimator","Material Drafter","Customer Service","Welder");
							        foreach($array as $row){?>
								    <option value="<?echo $row?>"><?echo $row?></option>
								    <?}?>
								</select>
							</div>
							
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Is Color Needed</span>
								</label>
								<select name="isColorNeeded" class="form-select" onchange="updateColorView()" style="background-color: #f5f8fa;">
								    <option value="Yes">Yes</option>
								    <option value="No">No</option>
								</select>
							</div>
							
							<div class="flex-column mb-8 fv-row" id="colorInput">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Color Code</span>
								</label>
								<input type="color" class="form-control form-control-solid" placeholder="Color Code" name="colorCode" required />
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Password</span>
								</label>
								<input type="text" class="form-control form-control-solid" placeholder="Password" name="password" required />
							</div>
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input required type="submit" value="Save" name="addUser" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
		
		function updateColorView(){
	        $("#colorInput").hide();
	        var isColorNeeded=$("select[name='isColorNeeded']").val();
		    console.log(isColorNeeded);
		    
		    if(isColorNeeded=="Yes")
		        $("#colorInput").show();
	        else if(isColorNeeded=="No")
		        $("#colorInput").hide();
	    }
		
		$(document).ready(function(){
	    
	    $("#add_user").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            
            if(mydata!= null){
            	$("#modelTitle").html("Update User");
                $("input[name='name']").val(mydata['name'])
                $("input[name='email']").val(mydata['email'])
                $("select[name='role']").val(mydata['role'])
                $("input[name='phone']").val(mydata['phone'])
                $("input[name='password']").val(mydata['password']);
                $("input[name='colorCode']").val(mydata['colorCode']);
                $("input[name='actionId']").val(mydata['id']);
                $("select[name='isColorNeeded']").val(mydata['isColorNeeded']);
            }else{
            	$("#modelTitle").html("Add User");
                $("input[name='name']").val("")
                $("input[name='email']").val("")
                $("input[name='phone']").val("")
                $("select[name='role']").val("")
                $("input[name='password']").val("");
                $("input[name='colorCode']").val("#000000");
                $("input[name='actionId']").val("");
                $("select[name='isColorNeeded']").val("Yes");
            }
            updateColorView();
        });
	    })
	    
	</script>
</html>