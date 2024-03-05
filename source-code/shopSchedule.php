<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");

$timeAdded=time();

$idToPropertyDeets=[];
$properties=getAll($con,"select *,concat(street1,' ',street2,' ',city,' ',state) as fullAddress from darlelJobber_properties");
foreach($properties as $row)
    $idToPropertyDeets[$row['id']]=$row['fullAddress'];

$idToUserDeets=[];
$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row)
    $idToUserDeets[$row['id']]=$row;

$idToQuoteDeets=[];
$quotes=getAll($con,"select * from darlelJobber_quotes");
foreach($quotes as $row)
    $idToQuoteDeets[$row['id']]=$row;

$idToJobDeets=[];
$jobs=getAll($con,"select * from darlelJobber_jobs");
foreach($jobs as $row)
    $idToJobDeets[$row['id']]=$row;

$days=["Monday","Tuesday","Wednesday","Thursday","Friday"];
$selectedDate=strtotime(clear($_GET['selectedDate']));
    
if(!isset($_GET['selectedDate']) || $selectedDate=="")
    $selectedDate=time();
    
$startingWeekTimeStamp = strtotime('monday this week', $selectedDate);
$fridayTimeStamp = strtotime('saturday this week', $selectedDate);
$timeAdded=time();

$appointmentsQuery="select * from darlelJobber_shop_orders where start_date >= $startingWeekTimeStamp and start_date<= $fridayTimeStamp and scheduleType='Schedule Now'";


if(isset($_POST['addShopOrder'])){
    $jobId=clear($_POST['jobId']);
    $orderType=clear($_POST['orderType']);
    $customerId=clear($_POST['customerId']);
    $salesOrderNo=clear($_POST['salesOrderNo']);
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $actionId=clear($_POST['actionId']);
    $scheduleType=clear($_POST['scheduleType']);
    $start_date=strtotime(clear($_POST['start_date']));
    $end_date=strtotime(clear($_POST['end_date']));
    $start_time=$_POST['start_time'];
    $end_time=$_POST['end_time'];
    $selectedDateFormat=date("Y-m-d",$end_date);
    
    $start_time = ($start_time=="") ? "14:30" : $start_time;
    $end_time = ($end_time=="") ? "14:30" : $end_time;
    
    $team=$_POST['team'];
    if($orderType=="Quote"){
        header("Location:./createJob.php?entryId=$jobId&openShopOrder=1");
        exit();
    }
    
    if($actionId==""){
        $shopOrderId=random();
        $query="insert into darlelJobber_shop_orders set id='$shopOrderId',customerId='$customerId',salesOrderNo='$salesOrderNo',title='$title',description='$description',
        start_date='$start_date',start_time='$start_time',end_date='$end_date',end_time='$end_time',addedBy='$session_id',timeAdded='$timeAdded',scheduleType='$scheduleType'";
        runQuery($query);
        $taskId="None";
    }
    else if($actionId!=""){
        $shopOrderId=$actionId;
        $query="update darlelJobber_shop_orders set salesOrderNo='$salesOrderNo',customerId='$customerId',title='$title',description='$description',start_date='$start_date',start_time='$start_time'
        ,end_date='$end_date',end_time='$end_time',addedBy='$session_id',timeAdded='$timeAdded',scheduleType='$scheduleType' where id='$shopOrderId'";
        runQuery($query);
        $taskId=getRow($con,"select * from darlelJobber_shop_orders where id='$shopOrderId'")['taskId'];
    }
    if($taskId=="None")
        $taskId="random";
    
    //adding team members for this request
    $newteam=$_POST['team'];
    $oldteamfetch=getAll($con,"select userId from darlelJobber_teams where shopOrderId='$shopOrderId'");
    $oldteam=[];
    foreach($oldteamfetch as $row)
        $oldteam[]=$row['userId'];
    
    //adding team members started
    $allWorkers=getAll($con,"select * from darlelJobber_users where role!='Client'");
    foreach($allWorkers as $row){
        $userId=$row['id'];
        if((in_array($userId,$newteam)) && (!in_array($userId,$oldteam))){
            
            //adding this person in the task team if a task is connected to this shop order
            if($taskId!="random"){
                $random=random();
                $query="insert into darlelJobber_teams set id='$random',taskId='$taskId',userId='$userId',timeAdded='$timeAdded'";
                runQuery($query);
                
                $title="Assigned To a Shop Task";$description="You have been added as a member in a task";$url=$projectUrl."detailedTaskView.php?taskId=$taskId";
                setNotification($title,$description,$userId,$url);
            }
            
            //adding this person in the shop team
            $random=random();
            $query="insert into darlelJobber_teams set id='$random',shopOrderId='$shopOrderId',userId='$userId',timeAdded='$timeAdded'";
            runQuery($query);
            
            $title="Assigned To a Shop Order";$description="You have been added as a member in a shop order";$url=$projectUrl."shopSchedule.php?selectedDate=$selectedDateFormat";
            setNotification($title,$description,$userId,$url);
        }
        else if((!in_array($userId,$newteam)) && (in_array($userId,$oldteam))){
            $query="delete from darlelJobber_teams where userId='$userId' &&  ( taskId='$taskId' or shopOrderId='$shopOrderId' )";
            runQuery($query);
        }
    }
    //adding team members finished
    
    //multiple file upload
    $total = count($_FILES['fileToUpload']['name']);
    for( $i=0 ; $i < $total ; $i++ ){
      $tmpFilePath = $_FILES['fileToUpload']['tmp_name'][$i];
      if ($tmpFilePath != ""){
        $newFilePath = "./uploads/" . $_FILES['fileToUpload']['name'][$i];
        if(move_uploaded_file($tmpFilePath, $newFilePath)) {
            $image=$_FILES['fileToUpload']['name'][$i];
            $random=random();
            $query="insert into darlelJobber_shop_images set id='$random',shopOrderId='$shopOrderId',title='$image',image='$image',addedBy='$session_id'";
            runQuery($query);
            
            if($taskId!="random"){
                $random=random();
                $query="insert into darlelJobber_task_images set id='$random',taskId='$taskId',image='$image',timeAdded='$timeAdded',addedBy='$session_id'";
                runQuery($query);
            }
        }
      }
    }
    
    header("Location:?selectedDate=$selectedDateFormat&m=Shop Order data has been updated successfully");
}


//deleting shop image
if(isset($_GET['delete-shop-image'])){
    $id = clear($_GET['delete-shop-image']);
    $imageName=getRow($con,"select * from darlelJobber_shop_images where id='$id'")['image'];
    $imageName="uploads/".$imageName;
    unlink($imageName);
    runQuery("delete from darlelJobber_shop_images where id='$id'");
    $selectedDateFormat=date("Y-m-d",$selectedDate);
    header("Location:?selectedDate=$selectedDateFormat&m=Shop Order Image has been deleted successfully");
}

//completing the shop order
if(isset($_GET['completedShopOrder'])){
    $shopOrderId=clear($_GET['completedShopOrder']);
    $query="update darlelJobber_shop_orders set status='Completed' where id='$shopOrderId'";
    runQuery($query);
    header("Location:?selectedDate=$selectedDate&m=Shop Order completed successfully");
}

/*add shop task started*/
if(isset($_GET['createShopTask'])){
    
    $shopOrderId=clear($_GET['shopOrderId']);
    $taskId=random();
    $shopOrderDeets=getRow($con,"select * from darlelJobber_shop_orders where id='$shopOrderId'");
    //creating task for this shop order automatically
    $title="Shop Task";$description="Shop Task";$label="Shop";
    $completionDate=$shopOrderDeets['end_date'];
    
    //inserting task for this shop order
    $query="insert into darlelJobber_tasks set id='$taskId',shopOrderId='$shopOrderId',title='$title',description='$description',label='$label',
    completionDate='$completionDate',timeAdded='$timeAdded',addedBy='$session_id'";
    runQuery($query);
    
    //updating taskId of the shop order with the taskId
    runQuery("update darlelJobber_shop_orders set taskId='$taskId' where id='$shopOrderId'");
    
    //inserting shop team in task team
    $shopOrderTeam=getAll($con,"select userId from darlelJobber_teams where shopOrderId='$shopOrderId'");
    foreach($shopOrderTeam as $row){
        $userId=$row['userId'];
    
        $title="Assigned To a Shop Task";$description="You have been added as a member in a shop task";$url=$projectUrl."detailedTaskView.php?taskId=$taskId";
        setNotification($title,$description,$userId,$url);
        
        $random=random();
        $query="insert into darlelJobber_teams set id='$random',taskId='$taskId',userId='$userId',timeAdded='$timeAdded'";
        runQuery($query);
    }
    
    //inserting shop images in task images
    $shopImages=getAll($con,"select * from darlelJobber_shop_images where shopOrderId='$shopOrderId'");
    foreach($shopImages as $row){
        $image=$row['image'];
        $random=random();
        $query="insert into darlelJobber_task_images set id='$random',taskId='$taskId',image='$image',timeAdded='$timeAdded',addedBy='$session_id'";
        runQuery($query);
    }
    header("Location:./detailedTaskView.php?taskId=$taskId");
}
/*add shop task code ending*/


?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
		<style>
		    #kt_ecommerce_category_table {
                border-collapse: collapse !important;
            }
            
            #kt_ecommerce_category_table td {
                border: 1px solid black; /* Add a border to each cell */
                padding: 10px; /* Add padding for spacing */
            }
		</style>
	    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.1.0/simple-lightbox.min.js"></script>
    </head>
	<!--end::Head-->
	<!--begin::Body-->
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" 
	style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					
					<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    <? if (isset($_GET['m'])) { ?>
    								<div class="alert alert-primary"><?echo clear($_GET['m']) ?></div>
    							<? } ?>
    							
    							<div class="card card-flush mb-15">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
										    <div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Shop Orders" />
											</div>
										</div>
										<div class="card-toolbar">
										    
										    <input type="date" name="selectedDate" class="btn btn-primary text-white " value="<?echo date("Y-m-d",$selectedDate)?>" style="margin-right: 10px;">
										    <a href="#" data-bs-toggle="modal" data-bs-target="#add_shop_order" class="btn btn-primary ">Add Order</a>
                                        </div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-row-bordered border gs-7" id="kt_ecommerce_category_table">
                                                <thead>
                                                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                                        <th>Client</th>
                                                        <th>Title</th>
                                                        <th>SO/QN</th>
                                                        <th>Team</th>
                                                        <th>Added</th>
                                                        <th>Images</th>
                                                        <?for($i = 0; $i < 6; $i++) {?>
                                                        <th><? echo date('d/m', strtotime("+$i day", $startingWeekTimeStamp)); ?></th>
                                                        <?}?>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?$appointments=getAll($con,$appointmentsQuery);
                                                    foreach($appointments as $row){
                                                    $jobId=$row['jobId'];
                                                    $jobDeets=$idToJobDeets[$row['jobId']];
                                                    $quoteId=$jobDeets['quoteId'];
                                                    $customerName=$idToUserDeets[$jobDeets['customerId']]['first_name']." ".$idToUserDeets[$jobDeets['customerId']]['last_name'];
                                                    $quoteNumber=$idToQuoteDeets[$jobDeets['quoteId']]['quote_number'];
                                                    $jobNumber=$jobDeets['job_number'];
                                                    $quoteLink="<a href='viewQuote.php?entryId=$quoteId'>Q : $quoteNumber </a>";
                                                    $jobLink="<a href='createJob.php?entryId=$jobId&view=1'>J : $jobNumber </a>";
                                                    $textToDisplay=$quoteLink." ".$jobLink;
                                                    $textToDisplayInBlock=$quoteNumber;
                                                    
                                                    if($row['salesOrderNo']!="" && $row['salesOrderNo']!="None"){//means this sales order is connected to client and has no connection with a job
                                                        $customerName=$idToUserDeets[$row['customerId']]['first_name']." ".$idToUserDeets[$row['customerId']]['last_name'];
                                                        $textToDisplay=$row['salesOrderNo'];
                                                        $textToDisplayInBlock=$row['salesOrderNo'];
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?echo $customerName;?></td>
                                                        <td><?echo $row['title'];?></td>
                                                        <td><?echo $textToDisplay;?></td>
                                                        <td>
                                                            <?$shopOrderId=$row['id'];
                                                            $assignedTeam=[];
                                                            $team=getAll($con,"select * from darlelJobber_teams where shopOrderId='$shopOrderId'");
                                                            foreach($team as $nrow){
                                                            $assignedTeam[]=$nrow['userId'];?>
                                                            <span class="badge badge-primary">
                                                                <?echo $idToUserDeets[$nrow['userId']]['name'];
                                                                $colorCode=$idToUserDeets[$nrow['userId']]['colorCode'];?>
                                                            </span>
                                                            <?}?>
                                                        </td>
                                                        <td><?echo date("d M Y",$row['timeAdded'])?></td>
                                                        <td>
                                                            <?$images=getAll($con,"select * from darlelJobber_shop_images where shopOrderId='$shopOrderId'");
                                                            foreach($images as $nrow){
                                                            $isPdf=0;
                                        	                if(strpos($nrow['image'], ".pdf") !== false)
                                        	                    $isPdf=1;?>
                                        	                <p class="btn btn-success btn-sm">
                                                                <?if(!$isPdf){?>
                                                                <a class="text-white gallery" href="uploads/<?echo $nrow['image']?>">
                                                                    <img class="example-image" style="max-height: 2.5rem;" src="uploads/<?echo $nrow['image']?>"/>    
                                                                </a>
                                                                <?}else{?>
                                                                <a class="text-white" target="_blank" href="uploads/<?echo $nrow['image']?>"><?echo $nrow['image']?></a>
        	                                                    <?}?>
                                                                <a href="?delete-shop-image=<?echo $nrow['id']?>&selectedDate=<?echo $selectedDate?>" style="margin-left: 10px;color: red;">X</a>
                                    					    </p>  
                                                            <?}?>
                                                        </td>
                                                        <?for($i = 0; $i < 6; $i++) {
                                                        $display=0;
                                                        if($row['status']=="Completed")
                                                            $colorCode="purple";
                                                        else if($colorCode=="None" || $colorCode=="")
                                                            $colorCode="green";
                                                        
                                                        $time=strtotime("+$i day", $startingWeekTimeStamp);?>
                                                        <td
                                                        <?if($row['start_date']==$time || $row['end_date']==$time || 
                                                        ($time>=$row['start_date'] && $time<=$row['end_date'])){echo "style='background-color:$colorCode;color:white;'";$display=1;}?> >
                                                            <?if($display)
                                                                echo $textToDisplayInBlock;?>
                                                        </td>
                                                        <?}?>
                                                        
                                                        <td class="text-center">
                                                            <div class="btn-group">
                                                            <?$row['assignedTeam']=$assignedTeam;
                                                            $row['start_date']=date("Y-m-d",$row['start_date']);
                                                            $row['end_date']=date("Y-m-d",$row['end_date']);?>
                                                            <?if($row['status']!="Completed"){?>
                                            				<a class="btn btn-success btn-sm" href="?completedShopOrder=<?echo $row['id']?>&selectedDate=<?echo date("Y-m-d",$selectedDate)?>">Mark As Completed</a>
                                            			    <?}?>
                                            			    <?if($row['taskId']!="None"){
    									                        $taskId=$row['taskId'];
    									                        $taskHref="detailedTaskView.php?taskId=$taskId";
    									                    }
    									                    else{
    									                        $shopOrderId=$row['id'];
    									                        $taskHref="?shopOrderId=$shopOrderId&createShopTask=1";
    									                    }?>
                                            			    <a class="btn btn-success btn-sm" href="<?echo $taskHref?>">View Reminder</a>
                                        					<?if($row['salesOrderNo']!="" && $row['salesOrderNo']!="None"){?>
                                            			    <a class="btn btn-warning btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#add_shop_order" 
                                                            data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
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
	<script src="assets/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
	
	
		</div>
	</body>
	
	
	<!--shop order modal starting-->
		<div class="modal fade" id="add_shop_order" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered" style="max-width: 900px;">
				<div class="modal-content rounded">
					<div class="modal-header pb-0 border-0 justify-content-end">
						<div class="btn  btn-icon btn-active-color-primary" data-bs-dismiss="modal">
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
						    <div class="text-left">
							    <div class="row">
							        <div class="col-9">
							            <h1>Shop Order</h1>
							        </div>
							    </div>
							</div>
							
							<div class="row mb-4">
							    <div class="col-12" id="orderType">
							        <div class="form-group">
							            <label class="mt-2 fs-6 fw-bold mb-2">Order Type</label>
							            <select name="orderType" class="form-control" onchange="manageSchedule()">
							                <option selected value="Quote">Quote</option>
							                <option value="Sales Order">Sales Order</option>
							            </select>
							        </div>
							    </div>
							    <div class="col-12" id="searchQuote">
							        <div class="form-group">
							            <label class="mt-2 fs-6 fw-bold mb-2">Select Quote</label>
							            <select id="quoteSearch" data-control="select2" class="form-control" name="jobId">
					                        <?foreach($quotes as $row){if($row['jobId']!="None"){?>
					                        <option value="<?echo $row['jobId']?>">
					                            <?echo $row['quote_number']." ".$row['approveStatus']." ".$row['convertStatus']." ";
					                            $customerId=$row['customerId'];
					                            $propertyAddress=$idToPropertyDeets[$row['propertyId']];
					                            $phones=explode("*",$row['phone']);
					                            echo $propertyAddress;
					                            foreach($phones as $nrow){echo $nrow." ";}
					                            ?>
					                        </option>
							                <?}}?>
							            </select>
							        </div>    
							    </div>
							    <div class="col-12" id="searchSales">
							        <div class="form-group">
							            <label class="mt-2 fs-6 fw-bold mb-2">Select Client</label>
							            <select id="clientSearch" data-control="select2" class="form-control" name="customerId">
					                        <?foreach($users as $row){if($row['role']=="Client"){?>
					                        <option value="<?echo $row['id']?>">
					                            <?echo $row['first_name']." ".$row['last_name'];
					                            $phones=explode("*",$row['phone']);
					                            foreach($phones as $nrow){echo $nrow." ";}
					                            ?>
					                        </option>
							                <?}}?>
							            </select>
							        </div>    
							    </div>
							    <div class="col-12" id="salesOrderWindow">
    							    <div class="row">
        							    <div class="col-4">
        							        <div class="form-group">
        							            <label class="mt-2 fs-6 fw-bold mb-2">Sales Order</label>
        							            <input type="text" name="salesOrderNo" class="form-control">
        							        </div>  
        							    </div>
        							    <div class="col-8">
        							        <div class="form-group">
        							            <label class="mt-2 fs-6 fw-bold mb-2">Title</label>
        							            <input type="text" name="title" class="form-control">
        							        </div>  
        							    </div>
        							    <div class="col-12">
        							        <div class="form-group">
        							            <label class="mt-2 fs-6 fw-bold mb-2">Description</label>
        							            <textarea class="form-control" name="description"></textarea>
        							        </div>  
        							    </div>
        							    <div class="col-12">
        							        <div class="form-group">
        							            <label class="mt-2 fs-6 fw-bold mb-2">Select Team</label>
        							            <select id="teamSelect" name="team[]" class="form-select form-select-solid" data-control="select2" 
                								data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                                    <?foreach($users as $row){if($row['role']!="Client"){?>
        							                <option value="<?echo $row['id']?>"><?echo $row['name']?></option>
        							                <?}}?>
        							            </select>
        							        </div>  
        							    </div>
        							    <div class="col-12">
        							        <div class="form-group">
        							            <label class="mt-2 fs-6 fw-bold mb-2">Images</label>
        							            <input type="file" name="fileToUpload[]" class="form-control" multiple>
        							        </div>  
        							    </div>
        							    <div class="col-12">
        							        <label class="mt-2 fs-6 fw-bold mb-2">Type</label>
            								<select onchange="manageSchedule()" class="form-control" name="scheduleType">
            								    <option selected value="Schedule Now">Schedule Now</option>
            								    <option value="Schedule Later">Schedule Later</option>
            								</select>
        							    </div>
        							    <div class="col-12">
                        					<div id="scheduleSectionShop">
            							        <div class="row">
                							        <div class="col-6">
                							            <label class="mt-2 fs-6 fw-bold mb-2">Start Date</label>
        							                    <input type="date" name="start_date" class="form-control">
                							        </div>
                							        <div class="col-6">
                							            <label class="mt-2 fs-6 fw-bold mb-2">Start Time</label>
        							                    <input type="time" name="start_time" class="form-control">
                							        </div>
                							        <div class="col-6">
                							            <label class="mt-2 fs-6 fw-bold mb-2">End Date</label>
            							                <input type="date" name="end_date" class="form-control" >
                							        </div>
                							        <div class="col-6">
                							            <label class="mt-2 fs-6 fw-bold mb-2">End Time</label>
            							                <input type="time" name="end_time" class="form-control" >
                							        </div>
            							        </div>
            							    </div>
        							    </div>
    							    </div>
							    </div>
							</div>
							
							
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input class="btn btn-primary" type="submit" name="addShopOrder" value="Save" >
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!--shop order modal ending-->
		
	
<script>

    $('option').mousedown(function(e) {
        e.preventDefault();
        var originalScrollTop = $(this).parent().scrollTop();
        $(this).prop('selected', $(this).prop('selected') ? false : true);
        var self = this;
        $(this).parent().focus();
        setTimeout(function() {
            $(self).parent().scrollTop(originalScrollTop);
        }, 0);
    return false;
});

    function manageSchedule(){
        var selectedOption=$("select[name='scheduleType']").val();
        var orderType=$("select[name='orderType']").val();
        
        if(orderType=="Quote"){
            $('#salesOrderWindow').hide();
            $('#searchQuote').show();
            $('#searchSales').hide();
        }
        else if(orderType=="Sales Order"){
            $('#salesOrderWindow').show();
            $('#searchQuote').hide();
            $('#searchSales').show();
        }
        
        if(selectedOption=="Schedule Now")
            $("#scheduleSectionShop").show();
        else
            $("#scheduleSectionShop").hide();
    }

    $(document).ready(function() {
        $('input[name="selectedDate"]').on('change', function() {
            var selectedDate = $(this).val();
            window.location.href = window.location.pathname + '?selectedDate=' + encodeURIComponent(selectedDate);
        
            
        });
        
        
        $("#clientSearch").select2({
            dropdownParent: $("#add_shop_order")
        });
        $("#quoteSearch").select2({
            dropdownParent: $("#add_shop_order")
        });
        manageSchedule();
        
        
        
        /*adding shop order modal jquery started*/

        $("#add_shop_order").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            $('#orderType').show();
            $('#teamSelect option').prop('selected', false);
            console.log(mydata);
            if(mydata== null){
                console.log("adding");
                var today_date = moment().format('YYYY-MM-DD');
                $("select[name='orderType']").val("Quote");
                $("input[name='start_date']").val(today_date);
            	$("input[name='end_date']").val(today_date);
            	$("input[name='start_time']").val("13:30");
            	$("input[name='end_time']").val("14:30");
            	$("input[name='actionId']").val("");
            	manageSchedule();
                
            }
            else if(mydata!=null){
                console.log("editing");
                $('#orderType').hide();
                $("select[name='orderType']").val("Sales Order");
                $("input[name='title']").val(mydata['title']);
                $("textarea[name='description']").val(mydata['description']);
                $("select[name='customerId']").val(mydata['customerId']);
                $("input[name='salesOrderNo']").val(mydata['salesOrderNo']);
                $("select[name='scheduleType']").val(mydata['scheduleType']);
                $("input[name='start_date']").val(mydata['start_date']);
            	$("input[name='end_date']").val(mydata['end_date']);
            	$("input[name='start_time']").val(mydata['start_time']);
            	$("input[name='end_time']").val(mydata['end_time']);
            	$("input[name='actionId']").val(mydata['id']);
            	var assignedTeam=mydata['assignedTeam'];
                for (var i = 0; i < assignedTeam.length; i++) {
                  $('#teamSelect option[value="' + assignedTeam[i] + '"]').prop('selected', true);
                }
            	manageSchedule();
            }
            $('#teamSelect').trigger('change');
            
        });
        /*adding shop order modal jquery ending*/
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const gallery = new SimpleLightbox('.gallery', {});
    });
</script>
</html>