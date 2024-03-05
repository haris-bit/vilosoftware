<?require("./global.php");
if($logged==0 || (!$permission['view_jobs']))
    header("Location:./index.php");

if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    $query="delete from darlelJobber_jobs where id='$id'";
    runQuery($query);
    
    $query="delete from darlelJobber_job_details where quoteId='$id'";
    $result=$con->query($query);
    
    header("Location:./jobs.php?m=Jobs has been deleted successfully");
}
$users=getAll($con,"select * from darlelJobber_users where role='Client'");
foreach($users as $row)
{$idToInfo[$row['id']]=$row;}

$idToProperty=array();
$properties=getAll($con,"select * from darlelJobber_properties");
foreach($properties as $row)
{ $idToProperty[$row['id']]=$row;}


if(isset($_GET['accept_job_date'])){
    $id=$_GET['accept_job_date'];
    $query="update darlelJobber_jobs set approval_status='Approved' where id='$id'";
    $result=$con->query($query);
    if(!$result){
        echo $con->error;
        exit();
    }
    header("Location:?m=Status has been updated successfully ");
}
if(isset($_GET['mark-as-completed'])){
    $id=$_GET['mark-as-completed'];
    $query="update darlelJobber_jobs set job_status='Completed' where id='$id'";
    $result=$con->query($query);
    if(!$result){
        echo $con->error;
        exit();
    }
    
    if(isset($_GET['convert'])){
        header("Location:./createJob.php?entryId=$id&convert=1");
        exit();
    }
    header("Location:?m=Job Status has been updated successfully ");
}
$jobIdToVisits=[];
$jobIdToLateVisits=[];
$visits=getAll($con,"select * from darlelJobber_visits group by jobId");
foreach($visits as $row)
    $jobIdToVisits[$row['jobId']]=1;
    
$lateVisits=getAll($con,"select * from darlelJobber_visits where visitStatus='Late' group by jobId");
foreach($lateVisits as $row)
    $jobIdToLateVisits[$row['jobId']]=1;
?>
<html lang="en">
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
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo clear($_GET['m']);?></h4>
                                    </div>
                                </div>
                                <?}?>
                                
                                <div class="card card-flush">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Jobs " />
											</div>
										</div>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gs-7 text-center" id="kt_ecommerce_category_table">
											<thead>
												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
													<th>Title</th>
													<th >Client</th>
													<th class="mobile_view">Property</th>
													<th <?if($session_role=="Installation Crew"){echo "class='d-none'";}?>>Total</th>
													<th>Job Status</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody class="fw-bold text-gray-600">
											    <?
											    $query="select * from darlelJobber_jobs order by timeAdded desc";
											    if($permission['view_own_schedule'])
											        $query="select j.id,j.job_status,j.customerId,j.title,j.propertyId,j.total,j.approval_status
											        from darlelJobber_jobs j inner join darlelJobber_teams t on j.id=t.jobId where t.userId='$session_id'";
											    else if($session_role=='Client')
											        $query="select * from darlelJobber_jobs where customerId='$session_id' order by timeAdded desc";
											    $jobs=getAll($con,$query);
											    $filter=clear($_GET['filter']);
											    foreach($jobs as $row){
											    if($filter=="Unscheduled" && $jobIdToVisits[$row['id']]==1)//this will not show scheduled jobs
											        continue;
											    if($filter=="Late" && $jobIdToLateVisits[$row['id']]==null)//this will not show normal jobs
											        continue;
											    ?>
											        <tr>
											            <td><a href="./createJob.php?entryId=<?echo $row['id']?>&view=1"><?echo $row['title']?></a></td>
											            <td >
											                <a href="./view_client.php?id=<?echo $row['customerId']?>"><?echo "#".$row['job_number']." ";?>
											                    <?if($idToInfo[$row['customerId']]['showCompanyName']=="Yes")
									                                echo $idToInfo[$row['customerId']]['company_name']." (".$idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name'].")";
    											                else   
    											                    echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?>
        											        </a>
								                        </td>
    											        
    											        <?
    											        $address=$idToProperty[$row['propertyId']];
    											        $address=$address['street1'].",".$address['street2'].",".$address['state'].",".$address['city'].",".$address['country'];?>
    											        <td class="mobile_view"><?echo $address?></td>
    											        <td <?if($session_role=="Installation Crew"){echo "class='d-none'";}?>><?echo $row['total']?></td>
											            <td>
									                        <a class="btn btn-<?if($row['job_status']=="Pending"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['job_status']?></a>
											            </td>
											            <td>
											                <div class="btn-group">
											                <?if($isAdmin){?>
										                    <a class="btn btn-warning btn-sm" href="tasks.php?jobId=<?echo $row['id']?>&userId=<?echo $session_id?>&error=1" style="white-space: pre;">Job Error</a>
                    									    <?}?> 
											                <?if($session_role=="Client" && $row['approval_status']=="Pending"){?>
											                <a class="btn btn-warning btn-sm" href="?accept_job_date=<?echo $row['id']?>" onclick="return confirm('Are you sure you want to accpet this date as job visit ?')">Accept Job Date</a>
        													<?}?>
        													<?if($permission['view_jobs']){?>
										                    <a href="./createJob.php?entryId=<?echo $row['id']?>&view=1" class="btn btn-primary btn-sm">View</a>
    										                <?}?>
    										                <?if($permission['edit_jobs']){?>
										                    <a href="./createJob.php?entryId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Edit</a>
    										                <?if($row['job_status']!="Completed"){?>
    										                <a style="white-space: pre;" href="#" data-bs-toggle="modal" data-bs-target="#complete_job" data-url="?mark-as-completed=<?echo $row['id']?>" class="btn btn-success btn-sm">Mark As Completed</a>
    										                <?}}?>
    										                <?if($permission['delete_jobs']){?>
											                <!--<a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-record=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>
        													--><?}?>
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
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
			
			<?require("./includes/views/footerjs.php");?>
	
	</div>
	</body>
	
	
	<!--complete job modal-->	
	<div class="modal fade" tabindex="-1" id="complete_job">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Job</h5>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <span class="svg-icon svg-icon-2x"></span>
                    </div>
                </div>
    
                <div class="modal-body">
                    <p>Kindly select on of the options below</p>
                </div>
    
                <div class="modal-footer">
                    <div class="row">
                        <a href="?mark-as-completed=1&convert=1" id="convertUrl" class="btn btn-success w-100 mb-6">Complete Job And Convert To Invoice</a>
                        <a href="?mark-as-completed=1" id="simpleUrl" class="btn btn-danger w-100 mb-6">Complete Job But Do Not Convert To Invoice</a>
                        <button type="button" class="btn btn-light w-100 mb-6" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>	
		
		<script>
	    $(document).ready(function(){
            $("#complete_job").on('show.bs.modal', function (e) {
                var simpleUrl = $(e.relatedTarget).data('url');
                var convertUrl = simpleUrl.concat("&convert=1");
                
                $("#simpleUrl").attr("href", simpleUrl);
                $("#convertUrl").attr("href", convertUrl);
            });
	    })
	</script>
	
</html>