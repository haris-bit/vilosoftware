<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");
$contactType=["Standard","Work","Personal","Other"];
$nameTitle=["No Title","Mr.","Ms.","Mrs.","Miss.","Dr."];
$timeAdded=time();
    
$allClients=getAll($con,"SELECT * from darlelJobber_users where role='Client'");


        
$new = (isset($_GET['new'])) ? 1 : 0;
$edit = (isset($_GET['customerId'])) ? 1 : 0;
$customerId=clear($_GET['customerId']);
if($edit)
    $clientDeets=getRow($con,"select * from darlelJobber_users where id='$customerId'");

//generating the back button url
$startEnd="";
$pageRedirectionVal=clear($_GET['page']);
$pageRedirection = (isset($_GET['page'])) ? "&page=".$_GET['page'] : "" ;
        
$backButtonUrl="";
if(isset($_GET['start']))
    $startEnd="&start=".$_GET['start']."&end=".$_GET['end'];
if(isset($_GET['page']))
    $backButtonUrl="$pageRedirectionVal.php?new=1&customerId=$customerId$startEnd";

if(isset($_POST['addClient']) || isset($_POST['addClientWithAccount'])){
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
        else{
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
    $showCompanyName = ($showCompanyName=="on") ? "Yes" : "No";
    
    $id=random();
    
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
    
    if(isset($_POST['addClient'])){
        $pageRedirection=$_GET['page'];
        $action=clear($_GET['action']);
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
        
        if(isset($_GET['page']))
            header("Location:./$pageRedirection.php?new=1&customerId=$userId$startEnd");
        else
            header("Location:?customerId=$userId&m=Client Data has been updated successfully");
    }
    else if(isset($_POST['addClientWithAccount']))
        header("Location:?customerId=$userId&addContact=1&m=Client Data has been updated successfully$pageRedirection$startEnd");
}



if(isset($_POST['addContact'])){
    $phones=$_POST['phone'];
    $emails=$_POST['email'];
    $name=clear($_POST['name']);
    $actionId=clear($_POST['actionId']);
    if($actionId==""){
        $contactId=random();
        $query="insert into darlelJobber_contacts set id='$contactId',name='$name',customerId='$customerId'";
    }else{
        $contactId=$actionId;
        $query="update darlelJobber_contacts set name='$name' where id='$actionId'";
    }
    runQuery($query);
    
    runQuery("delete from darlelJobber_contact_details where contactId='$contactId'");
    
    //inserting phones
    foreach($phones as $row){
        $random=random();
        $query="insert into darlelJobber_contact_details set id='$random',contactId='$contactId',type='phone',value='$row'";
        runQuery($query);
    }
    //inserting emails 
    foreach($emails as $row){
        $random=random();
        $query="insert into darlelJobber_contact_details set id='$random',contactId='$contactId',type='email',value='$row'";
        runQuery($query);
    }
    if(!isset($_GET['redirect']))
        header("Location:?customerId=$customerId&m=Contact Data has been saved successfully$pageRedirection$startEnd");
    else{//means that the contact was added from somewhere else
        header("Location:./createQuote.php?new=1&customerId=$customerId&contactId=$contactId");
    }
}


if(isset($_GET['delete-contact'])){
    $contactId=clear($_GET['delete-contact']);
    runQuery("delete from darlelJobber_contacts where id='$contactId'");
    runQuery("delete from darlelJobber_contact_details where contactId='$contactId'");
    header("Location:?customerId=$customerId&m=Contact Data has been deleted successfully$pageRedirection$startEnd");
}
?>
<html lang="en">
	<head>
	    <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyCdk2mdBvjhJmrFA9BWswlJlOz7WoU75-k"></script>
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
							    <!--account details started-->
					            <div class="row">
							    <div class="col-sm-12 col-md-<?echo ($new) ? "12" : "8";?>  col-12">
							    <div class="card card-flush mb-10">
									<div class="card-body pt-0">
								    	<form action="" method="post" enctype="multipart/form-data">
            							    <div class="row mt-5">
                							<div class="col-xs-12 col-sm-12 col-lg-12">
                							<div class="mb-13 text-left">
                							    <div class="row">
                							        <div class="col-6">
                							            <h1 class="mt-3" id="modelTitle"><?echo ($new) ? "Add" : "Edit";?> Account</h1>
                							        </div>
                							        <div class="col-6" style="text-align:right;">
                							            <?if($edit){?>
                							            <?if($backButtonUrl!=""){?>
                							            <a href="<?echo $backButtonUrl?>" class="btn btn-warning ">Go Back</a>
                							            <?}?>
                							            <a href="view_client.php?id=<?echo $customerId?>" onclick="addHtml('contact')" class="btn btn-success ">View Customer</a>
                							            <?}?>
                							            <a class="btn btn-warning" id="duplicateClient" style="display:none;"></a>
							                            <a onclick="addHtml('contact')" class="btn btn-primary ">Add Phone No.</a>
                							            <a onclick="addHtml('email')" class="btn btn-primary ">Add Email</a>
                							        </div>
                                                </div>
                							</div>
                							<div class="row" style="margin-bottom: 20px;">
                							    <div class="col-12 text-center">
                							        <h1 class="btn btn-primary btn-hover-scale "><i style="font-size: 30px;" class="las la-user"></i> ACCOUNT DETAILS</h1>
                							    </div>
                							</div>
                							<div class="row g-9 mb-8">
                								<div class="col-md-2 fv-row">
                									<select style="background-color: #f5f8fa;" class="form-select" name="title">
                									    <?foreach($nameTitle as $row){?>
                									    <option <?if($row==$clientDeets['title']){echo "selected";}?> value="<?echo $row?>" ><?echo $row?></option>
                									    <?}?>
                									</select>
                								</div>
                								<div class="col-md-5 fv-row">
                    								<input  type="text" class="form-control form-control-solid" placeholder="First Name" name="first_name" onchange="checkName()" value="<?echo $clientDeets['first_name']?>" required />
                								</div>
                								<div class="col-md-5 fv-row">
                									<input  type="text" class="form-control form-control-solid" placeholder="Last Name" name="last_name" onchange="checkName()" value="<?echo $clientDeets['last_name']?>" required />
                								</div>
                								<div class="col-md-2 fv-row" style="margin-top: 40px;">
                                                    <input name="showCompanyName" class="form-check-input" type="checkbox" <?echo ($clientDeets['showCompanyName']=="Yes") ? "checked" : ""; ?> />
                                                    <label class="form-check-label">Show Company Name On Contract</label>
                                                </div>
                								<div class="col-md-10 fv-row">
                									<input  type="text" class="form-control form-control-solid" placeholder="Company Name" name="company_name" value="<?echo $clientDeets['company_name']?>" />
                								</div>
                							</div>
                                            <div id="contactSection">
                                                <?if($clientDeets['phone']!=""){
                                                $contacts=explode("*",$clientDeets['phone']);
                                                $contactTypeDB=explode("*",$clientDeets['contact_type']);
                                                foreach($contacts as $index=>$row){
                                                $randomId=random();?>
                                                <div class="row g-9 mb-8" id="<?echo $randomId?>">
                    								<div class="col-md-3 fv-row">
                    									<label class=" fs-6 fw-bold mb-2">Type</label>
                    									<select  style="background-color: #f5f8fa;" class="form-select" name="contact_type[]">
                    									    <?foreach($contactType as $nrow){?>
                    									    <option <?if($contactTypeDB[$index]==$nrow){echo "selected";}?> value="<?echo $nrow?>" ><?echo $nrow?></option>
                    									    <?}?>
                    									</select>
                    								</div>
                    								<div class="col-md-9 fv-row">
                    									<label class=" fs-6 fw-bold mb-2">Contact Number</label>
                        								<a class="" onclick="deleteHtml('<?echo $randomId?>')">
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
                        								<input type="text" class="form-control form-control-solid" placeholder="Enter Contact Number" value="<?echo $row?>" name="phone[]" onkeyup="applyPhoneNumberFormat(this)" />
                    								</div>
                    							</div>
                    							<?}}?>
                                            </div>
                							<div id="emailSection">
                							    <?if($clientDeets['email']!=""){
                                                $emails=explode("*",$clientDeets['email']);
                                                $emailTypeDB=explode("*",$clientDeets['email_type']);
                                                foreach($emails as $index=>$row){
                                                $randomId=random();?>
                                                <div class="row g-9 mb-8" id="<?echo $randomId?>">
                    								<div class="col-md-3 fv-row">
                    									<label class=" fs-6 fw-bold mb-2">Type</label>
                    									<select  style="background-color: #f5f8fa;" class="form-select" name="email_type[]">
                    									    <?foreach($contactType as $nrow){?>
                    									    <option <?if($emailTypeDB[$index]==$nrow){echo "selected";}?> value="<?echo $nrow?>" ><?echo $nrow?></option>
                    									    <?}?>
                    									</select>
                    								</div>
                    								<div class="col-md-9 fv-row">
                    									<label class=" fs-6 fw-bold mb-2">Contact Number</label>
                        								<a class="" onclick="deleteHtml('<?echo $randomId?>')">
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
                        								<input type="text" class="form-control form-control-solid" placeholder="Enter Contact Number" value="<?echo $row?>" name="email[]" />
                    								</div>
                    							</div>
                    							<?}}?>
                							</div>
                							</div>
                							<div class="col-xs-12 col-sm-12 col-lg-12">
                    							<div class="row" style="margin-bottom: 20px;">
                    							    <div class="col-12 text-center">
                    							        <h1 class="btn btn-primary btn-hover-scale "><i style="font-size: 30px;" class="las la-home"></i> PROPERTY DETAILS</h1>
                    							    </div>
                    							</div>
                                                <div class="row g-9 mb-8">
                                                    
                    								<div class="col-md-12 fv-row">
                    									<input onchange="fillPropertyDetails()"  type="text" class="form-control form-control-solid"  placeholder="Street 1" name="street1" value="<?echo $clientDeets['street1']?>" id="from" />
                    								</div>
                    								<div class="col-md-12 fv-row">
                    									<input  type="text" class="form-control form-control-solid" placeholder="Street 2" name="street2" value="<?echo $clientDeets['street2']?>" />
                    								</div>
                    								<div class="col-md-6 fv-row">
                    									<input  type="text" class="form-control form-control-solid" placeholder="City" name="city" value="<?echo $clientDeets['city']?>"/>
                    								</div>
                    								<div class="col-md-6 fv-row">
                    									<input  type="text" class="form-control form-control-solid" placeholder="State" name="state"  value="<?echo $clientDeets['state']?>"/>
                    								</div>
                    								<div class="col-md-6 fv-row">
                    									<input  type="text" class="form-control form-control-solid" placeholder="Zip Code" name="zip" id="zip_code" value="<?echo $clientDeets['zip_code']?>"/>
                    								</div>
                    								<div class="col-md-6 fv-row">
                    									<input  type="text" class="form-control form-control-solid" placeholder="Country" name="country" value="<?echo $clientDeets['country']?>"/>
                    								</div>
                    							</div>
                							</div>
                							</div>
                							
                						    <input type="text" name="actionId" value="<?echo $customerId?>" hidden>
                						    <div class="text-center">
                								<input  type="submit" value="Save Account" name="addClient" class="btn btn-primary">
                								<input  type="submit" value="Save Account And Add Contact" name="addClientWithAccount" class="btn btn-primary">
                							</div>
                						</form>
					                
					                </div>
					                
								</div>
								</div>
								<!--account details finished-->
					            
								<!--contacts section started-->
					            <?if($edit){?>
					            <div class="col-sm-12 col-md-4  col-12">
							    <div class="card card-flush mb-10">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
											    <h1>Manage Contacts</h1> 
											</div>
										</div>
										<div class="card-toolbar">
											<a id="addContactBtn" href="#" data-bs-toggle="modal" data-bs-target="#contactModal" class="btn btn-primary">Add Contact</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gs-7 text-center">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Emails</th>
                                                        <th>Phones</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?$contacts=getAll($con,"select * from darlelJobber_contacts where customerId='$customerId'");
                                                    foreach($contacts as $row){
                                                    $userPhones=[];
                                                    $userEmails=[];
                                                    $contactId=$row['id'];
                                                    $contactDetails=getAll($con,"select * from darlelJobber_contact_details where contactId='$contactId'");
                                                    ?>
                                                    <tr>
                                                        <td><?echo $row['name']?></td>
                                                        <td>
                                                            <?foreach($contactDetails as $nrow){
                                                            if($nrow['type']=="email"){
                                                                $userEmails[]=$nrow['value'];
                                                                echo "<badge class='btn btn-primary btn-sm me-2'>".$nrow['value']."</badge>";
                                                            }}?>
                                                        </td>
                                                        <td>
                                                            <?foreach($contactDetails as $nrow){
                                                            if($nrow['type']=="phone"){
                                                                $userPhones[]=$nrow['value'];
                                                                echo "<badge class='btn btn-primary btn-sm me-2'>".$nrow['value']."</badge>";
                                                            }}?>
                                                        </td>
                                                        <td>
                                                            <?$row['allEmails']=$userEmails;
                                                            $row['allPhones']=$userPhones;?>
                                                            <div class="btn-group">
                                                                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal" 
                                                                data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' >Edit</a>
                                                                
                                                                <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete_record" 
                                                                data-url="?delete-contact=<?echo $row['id']?>&customerId=<?echo $customerId.$pageRedirection.$startEnd?>">Delete</a>
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
								<?}?>
								<!--contacts section finished-->
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

		<script>
		
		var allClients=<?echo json_encode($allClients);?>;
		
		function checkName(){
            var firstName=$("input[name='first_name']").val().toLowerCase().trim();
            var lastName=$("input[name='last_name']").val().toLowerCase().trim();
            var actionId=$("input[name='actionId']").val();
            if(actionId==""){
                $("#duplicateClient").hide();
                            
                for(var i=0;i<allClients.length;i++){
                    var found=0;
                    
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
		
		function makeid(length){
            var result           = '';
            var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for ( var i = 0; i < length; i++ ) {
              result += characters.charAt(Math.floor(Math.random() * 
         charactersLength));
           }
           return result;
        }
        
        function deleteHtml(id){
            $("#"+id).remove();
        }
        
        function addHtml(method){
            var first_input,second_input,placeholder,label,onkeyup;
            if(method=="contact"){
                first_input="contact_type";
                second_input="phone";
                placeholder="Contact Number";
                label="Contact Number";
                value=` value="+1" `;
                onkeyup="onkeyup='applyPhoneNumberFormat(this)'";
	        }
            else{
                first_input="email_type";
                second_input="email";
                placeholder="Email Address";
                label="Email Address";
                value="";
                onkeyup="";
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
    								<input  type="text" class="form-control form-control-solid" placeholder="Enter `+placeholder+`" name="`+second_input+`[]" `+value+` `+onkeyup+`/>
								</div>
							</div>`;   
				if(method=="contact")
            	    $("#contactSection").append(string);
            	else
            	    $("#emailSection").append(string);
        }
        
        function fillPropertyDetails(){
            
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
    
    
    <!--contacts section jquery-->
    
	<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
			
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
						        <div class="row">
							        <div class="col-6">
							            <h1 class="mb-3" id="contactModalTitle"></h1>
							        </div>
							        <div class="col-6" style="text-align:right;">
							            <a onclick="addHtmlContact('contactPhoneSection','text','Enter Phone Number','phone','')" class="btn btn-primary">Add Phone No.</a>
							            <a onclick="addHtmlContact('contactEmailSection','email','Enter Email Address','email','')" class="btn btn-primary">Add Email</a>
							        </div>
                                </div>
							</div>
							
                            
                            <div class="form-group mb-5">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control form-control-solid">
                            </div>
                            
                            <div id="contactEmailSection"></div>
                            <div id="contactPhoneSection"></div>
                            
                            <input type="text" name="actionId" hidden>
                            <div class="text-center">
								<input type="submit" value="Save Changes" name="addContact" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	    <script>
	    function addHtmlContact(section,type,placeholder,inputName,value){
	        var entryId=makeid(5);
	        var onkeyup="";
	        if(section=="contactPhoneSection")
	            onkeyup="onkeyup='applyPhoneNumberFormat(this)'";
	        var string=`
	        <div class="row g-9 mb-8" id="`+entryId+`">
				<div class="col-md-12 fv-row">
					<label class=" fs-6 fw-bold mb-2">`+placeholder+`</label>
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
					<input type="`+type+`" class="form-control form-control-solid" placeholder="`+placeholder+`" name="`+inputName+`[]" value="`+value+`" `+onkeyup+`/>
				</div>
			</div>`;
			$('#'+section).append(string);
	    }
	    
	    
	    
	    
	    function applyPhoneNumberFormat(input) {
            let cleanedInput = input.value.replace(/[^\d+]/g, '');
            cleanedInput = cleanedInput.substring(0, 12);
            input.value = cleanedInput;
        }
	    
	    
	    $(document).ready(function(){
	        
	        
            $("#contactModal").on('show.bs.modal', function (e) {
                $('contactEmailSection').empty();
                $('contactPhoneSection').empty();
                
                var mydata = $(e.relatedTarget).data('mydata');
                console.log("mydata->",mydata);
                if(mydata!= null){
                    $("#contactModalTitle").html("Update Contact Details");
                    $("input[name='name']").val(mydata['name'])
                    $("input[name='actionId']").val(mydata['id'])
                
                    var allEmails=mydata['allEmails'];
                    for(var i=0;i<allEmails.length;i++)
                        addHtmlContact('contactEmailSection','email','Enter Email Address','email',allEmails[i]);
                    var allPhones=mydata['allPhones'];
                    for(var i=0;i<allPhones.length;i++)
                        addHtmlContact('contactPhoneSection','text','Enter Phone Number','phone',allPhones[i]);
                }else{
                    $("#contactModalTitle").html("Add Contact Details");
                    addHtmlContact('contactPhoneSection','text','Enter Phone Number','phone','');
                    addHtmlContact('contactEmailSection','email','Enter Email Address','email','');
                    $("input[name='actionId']").val("")
                    $("input[name='name']").val("")
                }
            });
            
            
            <?if(isset($_GET['addContact'])){?>
	        $("#addContactBtn")[0].click();
            <?}?>
	        
        });

	        
	    </script>
</html>