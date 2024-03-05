<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");
    
$purpose=$_GET['purpose'];
$message="Thank you,we greatly appreciate your business.Our service will be in touch very soon";

if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    $query="delete from darlelJobber_Tickets where id='$id'";
    runQuery($query);
    
    header("Location:?m=deleted&purpose=$purpose");
}
$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row){
    $idToName[$row['id']]=$row;
}
if(isset($_GET['delete-ticket'])){
    $id=$_GET['delete-ticket'];
    $query="delete from darlelJobber_tickets where id='$id'";
    runQuery($query);
}
if(isset($_GET['convert'])){
    $ticketId=$_GET['convert'];
    $ticketDeets=getRow($con,"select * from darlelJobber_tickets where id='$ticketId'");
    $customerId=$ticketDeets['addedBy'];
    $jobId=$ticketDeets['jobId'];
    
    $query="update darlelJobber_tickets set customerId='$customerId',addedBy='$session_id',type='System',scheduleType='Schedule Later'";
    runQuery($query);
    
    $firstVisitId=getRow($con,"select * from darlelJobber_visits where jobId='$jobId' order by timeAdded asc")['id'];
    $teamForFirstVisit=getAll($con,"select * from darlelJobber_teams where visitId='$firstVisitId'");
    $timeAdded=time();
    foreach($teamForFirstVisit as $row){
        $id=generateRandomString();
        $userId=$row['userId'];
        $query="insert into darlelJobber_teams set id='$id',ticketId='$ticketId',userId='$userId',timeAdded='$timeAdded'";
        runQuery($query);
    }
    
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where jobId='$jobId'");
    $timeRemaining=$invoiceDeets['finishTime']-time();
    $invoiceId=$invoiceDeets['id'];
    $query="update darlelJobber_invoices set timeRemaining='$timeRemaining',timePeriodStatus='stop' where id='$invoiceId'";
    runQuery($query);
    
    header("Location:./create_ticket.php?ticketId=$ticketId&view=1");
}

if(isset($_GET['completed'])){
    $id=$_GET['completed'];
    $query="update darlelJobber_tickets set completionStatus='Completed' where id='$id'";
    runQuery($query);
    
    $jobId=getRow($con,"select * from darlelJobber_tickets where id='$id'")['jobId'];
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where jobId='$jobId'");
    $invoiceId=$invoiceDeets['id'];
    
    $finishTime=time()+$invoiceDeets['timeRemaining'];
    $query="update darlelJobber_invoices set finishTime='$finishTime',timePeriodStatus='start' where id='$invoiceId'";
    runQuery($query);
}
if(isset($_GET['send'])){
    $id=$_GET['send'];
    $query="update darlelJobber_tickets set sendStatus='Ticket Sent' where id='$id'";
    runQuery($query);
    header("Location:./tickets.php?purpose=$purpose&m=message");
}
?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
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
                                    <!--begin::Icon-->
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
											<path opacity="0.3" d="M2 4V16C2 16.6 2.4 17 3 17H13L16.6 20.6C17.1 21.1 18 20.8 18 20V17H21C21.6 17 22 16.6 22 16V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4Z" fill="currentColor"></path>
											<path d="M18 9H6C5.4 9 5 8.6 5 8C5 7.4 5.4 7 6 7H18C18.6 7 19 7.4 19 8C19 8.6 18.6 9 18 9ZM16 12C16 11.4 15.6 11 15 11H6C5.4 11 5 11.4 5 12C5 12.6 5.4 13 6 13H15C15.6 13 16 12.6 16 12Z" fill="currentColor"></path>
										</svg>
									</span>
                                    <!--end::Icon-->
                                
                                    <!--begin::Wrapper-->
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        
                                        <?if ($_GET['m']=="message"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $message?></h4>
                                        <?}else if ($_GET['m']=="deleted"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">Request Has Been Deleted Successfully</h4>
                                        <?}?>
                                    </div>
                                    <!--end::Wrapper-->
                                
                                    <!--begin::Close-->
                                    <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                                        <span class="svg-icon svg-icon-2x svg-icon-light">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
												<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor"></rect>
												<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor"></rect>
											</svg>
										</span>
                                    </button>
                                    <!--end::Close-->
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
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Tickets " />
											</div>
										</div>
										<div class="card-toolbar">
										    <?if($session_role=="Client"){?>
										        <a href="client_ticket.php?new=1" class="btn btn-primary btn-sm">New Ticket</a>
									        <?}else{?>
									            <a href="create_ticket.php?new=1" class="btn btn-primary btn-sm">New Ticket</a>
									        <?}?>
										</div>
									</div>
									<div class="card-body pt-0">
									    
									    <div class="table-responsive">
									    <!--client view-->
									    <?if($purpose=="client"){?>
										<table class="table table-rounded table-striped border gy-7 gs-7" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
											        <th style="text-align: center;">Ticket No</th>
											        <th>Send Status</th>
												    <th>Actions</th>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?
											    $query="select * from darlelJobber_tickets where type='Client' order by timeAdded desc";
											    if($session_role=="Client")
											        $query="select * from darlelJobber_tickets where (addedBy='$session_id' || customerId='$session_id') order by timeAdded desc";
											    
											    $tickets=getAll($con,$query);
											    $counter=1;
											    foreach($tickets as $row){?>
											    <tr>
											        <td style="text-align: center;"><?echo $counter++;?></td>
											        <td><a class="btn btn-<?if($row['sendStatus']=="Pending"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['sendStatus']?></a></td>
													<td>
											            <div class="btn-group">
											            <?if($session_role!="Client"){?>
											            <a class="btn btn-warning btn-sm" href="?purpose=<?echo $purpose?>&convert=<?echo $row['id']?>">Convert To System Ticket</a>
										                <?}?>
										                <a href="./client_ticket.php?ticketId=<?echo $row['id']?>&view=1" class="btn btn-primary btn-sm">View</a>
										                <?if($row['sendStatus']=="Pending"){?>
										                <a href="?send=<?echo $row['id']?>&purpose=<?echo $purpose?>" class="btn btn-warning btn-sm">Send Ticket</a>
										                <a href="./client_ticket.php?ticketId=<?echo $row['id']?>&purpose=<?echo $purpose?>" class="btn btn-primary btn-sm">Edit</a>
										                <?}?>
										                <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-ticket=<?echo $row['id']?>&purpose=<?echo $purpose?>" class="btn btn-danger btn-sm">Delete</a>
    												    </div>
    												</td>
											    </tr>
											    <?}?>
											</tbody>
										</table>
										<?}else{?>
										
										<!--system view-->
										<table class="table table-rounded table-striped border gy-7 gs-7" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
													<th style="text-align: center;">Client</th>
													<th>Title</th>
													<th>Status</th>
													<th>Received By</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?
											    
											    $query="select * from darlelJobber_tickets where type='System' order by timeAdded desc";
											    if($session_role=="Installation Crew"){
											        $query="SELECT t.id,t.title,t.customerId,t.completionStatus,t.addedBy from darlelJobber_tickets t inner join darlelJobber_teams team 
										            on t.id=team.ticketId where team.userId='$session_id'";
                                                }
    										    $tickets=getAll($con,$query);
											    foreach($tickets as $row){?>
											    <tr>
											        <td style="text-align: center;"><a href="./view_client.php?id=<?echo $row['customerId']?>"><?echo $idToName[$row['customerId']]['first_name']." ".$idToName[$row['customerId']]['last_name']?></a></td>
											        <td><?echo "#".$row['ticket_number']." ".$row['title']?></td>
													<td><a class="btn btn-<?if($row['completionStatus']=="Pending"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['completionStatus']?></a></td>
													<td><?echo $idToName[$row['addedBy']]['name']?></td>
													<td>
													    <div class="btn-group">
											            <?if($row['completionStatus']=="Pending"){?>
										                    <a href="?completed=<?echo $row['id']?>&purpose=<?echo $purpose?>" class="btn btn-warning btn-sm">Mark As Complete</a>
										                <?}?>
										                <a href="./create_ticket.php?ticketId=<?echo $row['id']?>&view=1" class="btn btn-primary btn-sm">View</a>
									                    <?if($session_role!="Installation Crew"){?>
									                    <a href="./create_ticket.php?ticketId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Edit</a>
									                    <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-ticket=<?echo $row['id']?>&purpose=<?echo $purpose?>" class="btn btn-danger btn-sm">Delete</a>
    												    <?}?>
    												    </div>
												    </td>
    											</tr>
											    <?}?>
											</tbody>
											
										</table>
										<?}?>
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