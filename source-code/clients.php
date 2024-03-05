<?
require("global.php");

if($logged==0 || (!$permission['view_client']))
    header("Location:./index.php");


$allProperties=getAll($con,"SELECT *,concat(p.street1,' ',p.street2,' ',p.city,' ',p.state,' ',p.country,' ',p.zip_code ) as fullAddress from darlelJobber_properties p inner join darlelJobber_users u on p.userId=u.id");
$idToProperties=[];
foreach($allProperties as $row){
    $idToProperties[$row['userId']]=$idToProperties[$row['userId']]."<br>".$row['fullAddress'];
}

$contactType=array("Standard","Work","Personal","Other");
$nameTitle=array("No Title","Mr.","Ms.","Mrs.","Miss.","Dr.");

$allClients=getAll($con,"SELECT * from darlelJobber_users where role='Client'");

if(isset($_POST['addClient'])){
    $contact_type=$_POST['contact_type'];
    $contact=$_POST['phone'];
    $contact_type_str="";$contact_str="";
    for($i=0;$i<count($contact_type);$i++){
        if($i==(count($contact_type)-1)){
            $contact_type_str=$contact_type_str.$contact_type[$i];
            $contact_str=$contact_str.$contact[$i];
        }
        else{
            $contact_type_str=$contact_type_str.$contact_type[$i]."*";
            $contact_str=$contact_str.$contact[$i]."*";
        }
    }
    
    
    $email_type_str="";$email_str="";
    $email_type=$_POST['email_type'];
    $email=$_POST['email']; 
    
    for($i=0;$i<count($email_type);$i++){
        if($i==(count($email_type)-1)){
            $email_type_str=$email_type_str.$email_type[$i];
            $email_str=$email_str.$email[$i];
        }
        else
        {
            $email_type_str=$email_type_str.$email_type[$i]."*";
            $email_str=$email_str.$email[$i]."*";
        }
    }
    
    $title=clear($_POST['title']);
    $first_name=clear($_POST['first_name']);
    $last_name=clear($_POST['last_name']);
    $full_name=$first_name." ".$last_name;
    $company_name=clear($_POST['company_name']); 
    
    $street1=clear($_POST['street1']);
    $street2=clear($_POST['street2']);
    $city=clear($_POST['city']);
    $state=clear($_POST['state']); 
    $zip=clear($_POST['zip']); 
    $country=clear($_POST['country']); 
    $actionId=clear($_POST['actionId']);
    $redirection=clear($_POST['redirection']);
    $showCompanyName=clear($_POST['showCompanyName']);
    if($showCompanyName=="on")
        $showCompanyName="Yes";
    else
        $showCompanyName="No";
    
    $id=random();
    $timeAdded=time();
    
    if($actionId==""){
        $query="insert into darlelJobber_users set id='$id',title='$title',name='$full_name',first_name='$first_name',last_name='$last_name',company_name='$company_name',email_type='$email_type_str',
        email='$email_str',role='Client',contact_type='$contact_type_str',phone='$contact_str',street1='$street1',street2='$street2',
        city='$city',state='$state',zip_code='$zip',country='$country',timeAdded='$timeAdded',showCompanyName='$showCompanyName',password='$id',addedBy='$session_id'";
        $userId=$id;
    }
    else{
        $query="update darlelJobber_users set title='$title',name='$full_name',first_name='$first_name',last_name='$last_name',company_name='$company_name',email_type='$email_type_str',
        email='$email_str',role='Client',contact_type='$contact_type_str',phone='$contact_str',street1='$street1',street2='$street2',
        city='$city',state='$state',zip_code='$zip',country='$country',showCompanyName='$showCompanyName' where id='$actionId'";
        $userId=$actionId;
    }
    runQuery($query);
    
    $random=random();
    if($_POST['actionId']=="")
        $query="insert into darlelJobber_properties set id='$random',userId='$userId',street1='$street1',street2='$street2',
    city='$city',state='$state',zip_code='$zip',country='$country',type='primary',timeAdded='$timeAdded'";
    
    else
        $query="update darlelJobber_properties set street1='$street1',street2='$street2',
    city='$city',state='$state',zip_code='$zip',country='$country' where type='primary' && userId='$userId'";
    
    runQuery($query);
    
    $pageRedirection=$_GET['page'];
    $action=clear($_GET['action']);
    $startEnd="";
    if($action=="editFromQuote"){
        $quoteId=clear($_GET['quoteId']);
        header("Location:./createQuote.php?entryId=$quoteId");
        exit();
    }
    else if($action=="editFromRequest"){
        $requestId=clear($_GET['requestId']);
        header("Location:./createRequest.php?entryId=$requestId");
        exit();
    }
    
    if(isset($_GET['start']))
        $startEnd="&start=".$_GET['start']."&end=".$_GET['end'];
    if(isset($_GET['page']))
        header("Location:./$pageRedirection.php?new=1&customerId=$userId$startEnd");
    else
        header("Location:?m=updated");
}
if(isset($_GET['delete-client'])){
    if($session_role=="Admin"){
        $id = clear($_GET['delete-client']);
        $query="delete from darlelJobber_users where id='$id'";
        runQuery($query);
        
        runQuery("delete from darlelJobber_requests where request_for='$id'");
        runQuery("delete from darlelJobber_quotes where customerId='$id'");
        runQuery("delete from darlelJobber_jobs where customerId='$id'");
        runQuery("delete from darlelJobber_invoices where customerId='$id'");
        
        //if a user is deleted, then delete all of his related data
        header("Location:?m=deleted");
    }
}
?>
<html lang="en">
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
	</head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    
							    <?if(isset($_GET['m'])){?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;">Data has been updated successfully</h4>
                                    </div>
                                </div>
                                <?}?>
                                
                                <div class="card card-flush" style="margin-bottom: 40px;">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Clients " />
											</div>
										</div>
										<div class="card-toolbar">
										    <?if($permission['add_client']){?>
											<a href="addClient.php?new=1" class="btn btn-primary">New Client</a>
										    <?}?>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-row-bordered border gs-7 dataTable no-footer text-center" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
													<th >Name</th>
													<th>Client Details</th>
													<th>Properties</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?
											    $clients=getAll($con,"select * from darlelJobber_users where role='Client' order by timeAdded desc");
											    foreach($clients as $row){
											        $phones=explode("*",$row['phone']);
    											    $emails=explode("*",$row['email']);
    											    $phones = implode(' ', $phones);
    											    $emails = implode(' ', $emails);
											        $title = ($row['title']=="No Title") ? "" : $row['title'];
    											?>
											    <a id="<?echo $row['id']?>" href="#" data-bs-toggle="modal" data-bs-target="#add_customer" 
											    data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'hidden></a>
								                
								                <tr>
												    <td >
												        <a href="view_client.php?id=<?echo $row['id']?>"><?echo $title." ".$row['first_name']." ".$row['last_name'];?></a>
												        <?if($row['company_name']!=""){?>
												        <br>
												        <a class="badge badge-primary"><?echo "Company : ".$row['company_name']?></a>
												        <?}?>
												    </td>
													<td><?echo $emails."<br>".$phones?></td>
    											    <td><?echo $idToProperties[$row['id']]?></td>
    											    <td>
													    <div class="btn-group">
													    <?if($permission['view_client']){?>
    													<a href="./view_client.php?id=<?echo $row['id']?>" class="btn btn-primary btn-sm">View</a>
													    <?}?>
										                <?if($permission['edit_client']){?>
										                <a href="addClient.php?customerId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Edit</a>
    									                <?}?>
										                <?if($permission['delete_client']){?>
    													<a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-client=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>
    													<?}?>
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
	
	<div class="modal fade" id="add_customer" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered mw-650px" style="max-width:1850px !important;">
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
							    <div class="row">
							        <div class="col-6">
							            <h1 class="mb-3" id="modelTitle">ADD CLIENT</h1>
							        </div>
							        <div class="col-6" style="text-align:right;">
							            <a class="btn btn-warning btn-sm" id="duplicateClient"></a>
							            <a onclick="addHtml('contact')" class="btn btn-primary btn-sm">Add Phone No.</a>
							            <a onclick="addHtml('email')" class="btn btn-primary btn-sm">Add Email</a>
							        </div>
                                </div>
							</div>
							<div class="row" style="margin-bottom: 20px;">
							    <div class="col-4"></div>
							    <div class="col-4">
							        <h1 class="btn btn-primary btn-hover-scale me-5 w-100"><i style="font-size: 30px;" class="las la-user"></i> CLIENT DETAILS</h1>
							    </div>
							    <div class="col-4"></div>
							</div>
							<div class="row g-9 mb-8">
								<div class="col-md-2 fv-row">
									<select   style="background-color: #f5f8fa;" class="form-select" name="title">
									    <?foreach($nameTitle as $row){?>
									    <option  value="<?echo $row?>" ><?echo $row?></option>
									    <?}?>
									</select>
								</div>
								<div class="col-md-5 fv-row">
    								<input  type="text" class="form-control form-control-solid" placeholder="First Name" name="first_name" onchange="checkName()" />
								</div>
								<div class="col-md-5 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Last Name" name="last_name" onchange="checkName()" />
								</div>
								<div class="col-md-2 fv-row" style="margin-top: 40px;">
								    <input name="showCompanyName" class="form-check-input" type="checkbox"/>
                                    <label class="form-check-label">
                                        Show Company Name On Contract
                                    </label>
                                </div>
								<div class="col-md-10 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Company Name" name="company_name" />
								</div>
							</div>
                            <div id="contactSection">
                            </div>
							<div id="emailSection">
                           </div>
							</div>
							<div class="col-xs-12 col-sm-12 col-lg-12">
    							<div class="row" style="margin-bottom: 20px;">
    							    <div class="col-4"></div>
    							    <div class="col-4">
    							        <h1 class="btn btn-primary btn-hover-scale me-5 w-100"><i style="font-size: 30px;" class="las la-home"></i> PROPERTY DETAILS</h1>
    							    </div>
    							    <div class="col-4"></div>
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
    									<input  type="text" class="form-control form-control-solid" placeholder="Zip Code" name="zip" id="zip_code"/>
    								</div>
    								<div class="col-md-6 fv-row">
    									<input  type="text" class="form-control form-control-solid" placeholder="Country" name="country" />
    								</div>
    							</div>
							</div>
							</div>
							
						    <input type="text" name="actionId" hidden>
						    <div class="text-center">
								<input  type="submit" value="Save" name="addClient" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
		
		var allClients=<?echo json_encode($allClients);?>;
		
		function checkName(){
            
            var firstName=$("input[name='first_name']").val().toLowerCase().trim();
            var lastName=$("input[name='last_name']").val().toLowerCase().trim();
            //to check that the model opened is to create a client
            var actionId=$("input[name='actionId']").val();
            if(actionId==""){
                $("#duplicateClient").hide();
                            
                for(var i=0;i<allClients.length;i++){
                    var found=0;
                    //console.log("current = "+allClients[i]['last_name'] +" matching with = "+lastName);
                    
                    if(allClients[i]['first_name'].toLowerCase().trim()==firstName && allClients[i]['last_name'].toLowerCase().trim()==lastName){
                        console.log("matched")
                        found=1;
                    }
                    
                    if(found){
                        $("#duplicateClient").show();
                        var clientName=allClients[i]['first_name']+" "+allClients[i]['last_name'];
                        $("#duplicateClient").text("Duplicate Client Found : "+clientName);
                        $("#duplicateClient").attr("href", "./view_client.php?id="+allClients[i]['id']);
                        return ;
                    }
                }
            }
        }
		
	    $(document).ready(function(){
	    
	        $("#add_customer").on('show.bs.modal', function (e) {
            $("#duplicateClient").hide();
            
            //get data-id attribute of the clicked element
            var mydata = $(e.relatedTarget).data('mydata');
            console.log("mydata", mydata)
            if(mydata!= null){
            	$("#modelTitle").html("Update Customer Details");
                $("select[name='title']").val(mydata['title'])
                $("input[name='first_name']").val(mydata['first_name'])
                $("input[name='last_name']").val(mydata['last_name'])
                $("input[name='company_name']").val(mydata['company_name'])  
                $("input[name='street1']").val(mydata['street1'])
                $("input[name='street2']").val(mydata['street2'])
                $("input[name='city']").val(mydata['city'])
                $("input[name='state']").val(mydata['state'])
                $("input[name='zip']").val(mydata['zip_code'])
                $("input[name='country']").val(mydata['country'])
                $("input[name='actionId']").val(mydata['id'])
                if(mydata['showCompanyName']=="Yes")
                    $("input[name='showCompanyName']").prop( "checked", true );
                else
                    $("input[name='showCompanyName']").prop( "checked", false );
                
            	$("#contactSection").empty();
            	$("#emailSection").empty();
            	
                var contact_type = mydata['contact_type'].split("*");
                var phone = mydata['phone'].split("*");
                for(var i=0;i<contact_type.length;i++)
                {
                    var entryId=makeid(5);
                    var string=`<div class="row g-9 mb-8" id="`+entryId+`">
								<div class="col-md-3 fv-row">
									<label class=" fs-6 fw-bold mb-2">Type</label>
									<select  style="background-color: #f5f8fa;" class="form-select" name="contact_type[]">
									    <?foreach($contactType as $row){?>
									    <option   value="<?echo $row?>" ><?echo $row?></option>
									    <?}?>
									</select>
								</div>
								<div class="col-md-9 fv-row">
									<label class=" fs-6 fw-bold mb-2">Contact Number</label>
									<a class="" onclick="deleteHtml('`+entryId+`')">
                    			        <span style="color: red;float:right;" class="svg-icon svg-icon-3">
                    			           <p title="Delete Entry" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="top">
                        			            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                									<path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor"></path>
                									<path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor"></path>
                									<path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor"></path>
                								</svg>
            								</p>
                						</span>
                    			    </a>
    								<input value="`+phone[i]+`" type="text" class="form-control form-control-solid" placeholder="Enter Contact Number" name="phone[]" />
								</div>
							</div>`;
							
            	$("#contactSection").append(string);
            	$("select[name='contact_type[]']").eq(i).val(contact_type[i])
                }
                var email_type = mydata['email_type'].split("*");
                var email = mydata['email'].split("*");
                for(var i=0;i<email_type.length;i++)
                {
                    var entryId=makeid(5);
                    var string=`<div class="row g-9 mb-8" id="`+entryId+`">
								<div class="col-md-3 fv-row">
									<label class=" fs-6 fw-bold mb-2">Type</label>
									<select  style="background-color: #f5f8fa;" class="form-select" name="email_type[]">
									    <?foreach($contactType as $row){?>
									    <option   value="<?echo $row?>" ><?echo $row?></option>
									    <?}?>
									</select>
								</div>
								<div class="col-md-9 fv-row">
									<label class=" fs-6 fw-bold mb-2">Email Address</label>
									<a class="" onclick="deleteHtml('`+entryId+`')">
                    			        <span style="color: red;float:right;" class="svg-icon svg-icon-3">
                    			           <p title="Delete Entry" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="top">
                        			            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                									<path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor"></path>
                									<path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor"></path>
                									<path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor"></path>
                								</svg>
            								</p>
                						</span>
                    			    </a>
    								<input  value="`+email[i]+`" type="email" class="form-control form-control-solid" placeholder="Enter Email Address" name="email[]" />
								</div>
							</div>`;
							
            	$("#emailSection").append(string);
            	$("select[name='email_type[]']").eq(i).val(email_type[i])
                }
                
                
                
                
            }else{
            	$("#modelTitle").html("Add Customer");
            	
            	
                $("input[name='showCompanyName']").prop( "checked", false );
                $("select[name='title']").val("No Title")
                $("input[name='first_name']").val("")
                $("input[name='last_name']").val("")
                $("input[name='company_name']").val("")  
                $("input[name='street1']").val("")
                $("input[name='street2']").val("")
                $("input[name='city']").val("")
                $("input[name='state']").val("")
                $("input[name='zip']").val("")
                $("input[name='country']").val("")
                $("input[name='actionId']").val("")
            	
            	$("#contactSection").empty();
            	$("#emailSection").empty();
            	var contactString=`
            	<div class="row g-9 mb-8">
								<div class="col-md-3 fv-row">
									<label class=" fs-6 fw-bold mb-2">Type</label>
									<select  style="background-color: #f5f8fa;" class="form-select" name="contact_type[]">
									    <?foreach($contactType as $row){?>
									    <option   value="<?echo $row?>" ><?echo $row?></option>
									    <?}?>
									</select>
								</div>
								<div class="col-md-9 fv-row">
									<label class=" fs-6 fw-bold mb-2">Contact Number</label>
    								<input  type="text" class="form-control form-control-solid" placeholder="Enter Contact Number" value="+1" name="phone[]" />
								</div>
							</div>`;
				var emailString=`
				            <div class="row g-9 mb-8">
								<div class="col-md-3 fv-row">
									<label class=" fs-6 fw-bold mb-2">Type</label>
									<select  style="background-color: #f5f8fa;" class="form-select" name="email_type[]">
									    <?foreach($contactType as $row){?>
									    <option value="<?echo $row?>"><?echo $row?></option>
									    <?}?>
									</select>
								</div>
								<div class="col-md-9 fv-row">
									<label class=" fs-6 fw-bold mb-2">Email Address</label>
    								<input  type="email" class="form-control form-control-solid" placeholder="Enter Email Address" name="email[]" />
								</div>
							</div>`;
				$("#contactSection").append(contactString);
    	        $("#emailSection").append(emailString);
			}
        });
	    
    	    <?if(isset($_GET['edit'])){?>
    	        $("#<?echo $_GET['edit']?>")[0].click();
            <?}?>
    	        
            <?if(isset($_GET['add_client'])){?>
                $("#add_customer_button")[0].click();
            <?}?>
	        
	    });
	    
	    
	    function makeid(length) {
            var result           = '';
            var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for ( var i = 0; i < length; i++ ) {
              result += characters.charAt(Math.floor(Math.random() * 
         charactersLength));
           }
           return result;
        }
        function deleteHtml(id)
        {
            $("#"+id).remove();
        }
        function addHtml(method)
        {
            var first_input,second_input,placeholder,label;
            if(method=="contact")
            {
                first_input="contact_type";
                second_input="phone";
                placeholder="Contact Number";
                label="Contact Number";
                value=` value="+1" `;
            }
            else
            {
                first_input="email_type";
                second_input="email";
                placeholder="Email Address";
                label="Email Address";
                value="";
            }
         var entryId=makeid(5);
         var string=`
            	<div class="row g-9 mb-8" id="`+entryId+`">
								<div class="col-md-3 fv-row">
									<label class=" fs-6 fw-bold mb-2">Type</label>
									<select  style="background-color: #f5f8fa;" class="form-select" name="`+first_input+`[]">
									    <?foreach($contactType as $row){?>
									    <option   value="<?echo $row?>" ><?echo $row?></option>
									    <?}?>
									</select>
								</div>
								<div class="col-md-9 fv-row">
									<label class=" fs-6 fw-bold mb-2">`+label+`</label>
									<a class="" onclick="deleteHtml('`+entryId+`')">
                    			        <span style="color: red;float:right;" class="svg-icon svg-icon-3">
                    			           <p title="Delete Entry" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="top">
                        			            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                									<path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor"></path>
                									<path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor"></path>
                									<path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor"></path>
                								</svg>
            								</p>
                						</span>
                    			    </a>
    								<input  type="text" class="form-control form-control-solid" placeholder="Enter `+placeholder+`" name="`+second_input+`[]" `+value+` />
								</div>
							</div>`;   
				if(method=="contact")
            	    $("#contactSection").append(string);
            	else
            	    $("#emailSection").append(string);
        }
        
        function fillPropertyDetails()
        {
            
            setTimeout(function() {
                var address = $("input[name='street1']").val();
                var addressPattern = /^(.*?)(?:, (.*?))?(?:, (.*?))?(?:, (.*?))?$/;
                var matches = address.match(addressPattern);
                var street1 = matches[1].trim();
                var street2 = matches[2] ? matches[2].trim() : "";
                var city = matches[3] ? matches[3].trim() : "";
                var country = matches[4] ? matches[4].trim() : "";
                
                $("input[name='street1']").val(street1);
                $("input[name='street2']").val(street2);
                $("input[name='city']").val(city);
                $("input[name='country']").val(country);
            }, 100);
        }

	</script>
	
	
		
		
    <script>
        var geocoder;
        var map;
        
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
            for (var i = 0; i < place.address_components.length; i++) {
              for (var j = 0; j < place.address_components[i].types.length; j++) {
                if (place.address_components[i].types[j] == "postal_code") {
                  var zip=place.address_components[i].long_name;
                  $('#zip_code').val(zip)
                  //document.getElementById('postal_code').innerHTML = place.address_components[i].long_name;
        
                }
              }
            }
          })
        }
        google.maps.event.addDomListener(window, "load", initialize);

    </script>
    
</html>