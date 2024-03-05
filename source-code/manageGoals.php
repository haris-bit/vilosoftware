<?require("./global.php");
if($logged==0)
    header("Location:./index.php");

$thisMonthStartingDate=date("Y-m-d",strtotime(date('Y-m-01')));
$thisMonthEndingDate=date("Y-m-d",strtotime(date('Y-m-t')));
$users=getAll($con,"select * from darlelJobber_users where role='Admin' or role='Estimator'");
$idToInfo=[];
foreach($users as $row)
    $idToInfo[$row['id']]=$row;
    


if(isset($_POST['create_package'])){
    $actionId = clear(($_POST['actionId']));
    $amountOfQuotes = clear($_POST['amountOfQuotes']);    
    $totalAmount = clear($_POST['totalAmount']);    
    $startDate = strtotime($_POST['startDate']);    
    $endDate = strtotime($_POST['endDate']);    
    
    if($actionId==""){
        //checking if another goal exists with the same start or end date
        $checkExists=getRow($con,"select * from darlelJobber_goals where startDate>=$startDate and endDate<=$endDate");
        if(count($checkExists)>0){
            header("Location:?m=Another goal exists with the same starting or ending date, kindly update the current goal or add a new goal with a different start and end date");
            exit();
        }
        $id = random();
        $actionId = $id;
        $query = "insert into darlelJobber_goals set id='$id' , amountOfQuotes='$amountOfQuotes', totalAmount='$totalAmount', startDate='$startDate', endDate='$endDate', timeAdded='$timeAdded', addedBy='$session_id' ";
    }else{
        $query = "update darlelJobber_goals set amountOfQuotes='$amountOfQuotes', totalAmount='$totalAmount', startDate='$startDate', endDate='$endDate' where id='$actionId'";
    }
    runQuery($query);

    header("Location:?m=Goal Data has been saved successfully");
    exit();
}
    
if(isset($_GET['delete-record'])){
    $id = clear($_GET['delete-record']);
    $query = "delete from darlelJobber_goals where id='$id'";
    runQuery($query);
    header("Location:?m=Goal Data has been deleted successfully");
    exit();
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
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    
							    <?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m;?></h4>
                                    </div>
                                </div>
                                <?}?>
						    	
						    	<div class="card card-flush mb-15">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Weekly Goals" />
											</div>
										</div>
										<div class="card-toolbar">
											<a href="#" data-bs-toggle="modal" data-bs-target="#goalsModal" class="btn btn-primary">Add Weekly Goals</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded border gs-7 text-center" id="kt_ecommerce_category_table">
                                                <thead>
                                                    <tr>
                                                        <th>Amount Of Quotes</th>
                                                        <th>Total Amount</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Users Who Completed Total Amount</th>
                                                        <th>Users Who Completed Total Quotes</th>
                                                        <th>Users Who Completed Both</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php  $query = "select * from darlelJobber_goals t order by t.timeAdded desc";
                                                $results = getAll($con, $query);
                                                    foreach($results as $row){
                                                    $completedQuotes=[];
                                                    $completedAmount=[];
                                                    $completedBoth=[];
                                                    $currentMonth=0;
                                                    
                                                    if($thisMonthStartingDate==date("Y-m-d",$row['startDate']) && $thisMonthEndingDate==date("Y-m-d",$row['endDate']))
                                                        $currentMonth=1;?>
                                                        <tr <?if($currentMonth){echo "style='background-color: #f8cdcf !important;'";}?>>
                                                            <td><?php echo $row['amountOfQuotes']?></td>
                                                            <td><?php echo $row['totalAmount']?></td>
                                                            <td><?php echo date("d M Y",$row['startDate']);?></td>
                                                            <td><?php echo date("d M Y",$row['endDate']);?></td>
                                                            <?
                                                            foreach($users as $nrow){
                                                                $estimtorId=$nrow['id'];
                                                                $startDate=$row['startDate'];
                                                                $endDate=$row['endDate'];
                                                                $query="select sum(total) as totalAmount,count(id) as totalQuotes from darlelJobber_quotes 
                                                                where  ( timeAdded between $startDate and $endDate ) and ( approveTime between $startDate and $endDate) 
                                                                and approveStatus='Approved' and estimatorId='$estimtorId'";
                                                                $goalsQuotes=getRow($con,$query);
                                                                $totalWeeklyQuotes=$goalsQuotes['totalQuotes'];
                                                                $totalWeeklyAmount=$goalsQuotes['totalAmount'];
                                                                
                                                                if($totalWeeklyQuotes>=$row['amountOfQuotes']){
                                                                    $completedQuotes[]=$estimtorId;
                                                                }
                                                                if($totalWeeklyAmount>=$row['totalAmount']){
                                                                    $completedAmount[]=$estimtorId;
                                                                }
                                                                if($totalWeeklyAmount>=$row['totalAmount'] && $totalWeeklyQuotes>=$row['amountOfQuotes']){
                                                                    $completedBoth[]=$estimtorId;
                                                                }
                                                            }
                                                            ?>
                                                            <td>
                                                                <?foreach($completedQuotes as $nrow){ echo "<a class='badge badge-success me-2 mb-2'>".$idToInfo[$nrow]['name']."</a>";}?>
                                                            </td>
                                                            <td>
                                                                <?foreach($completedAmount as $nrow){ echo "<a class='badge badge-success me-2 mb-2'>".$idToInfo[$nrow]['name']."</a>";}?>
                                                            </td>
                                                            <td>
                                                                <?foreach($completedBoth as $nrow){ echo "<a class='badge badge-success me-2 mb-2'>".$idToInfo[$nrow]['name']."</a>";}?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group">
                                                                    <?$row['startDate']=date("Y-m-d",$row['startDate']);$row['endDate']=date("Y-m-d",$row['endDate']);?>
                                                                    <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#goalsModal" 
                                                                    data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
                                                                    <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete_record" data-url="?delete-record=<?php echo $row['id']?>">Delete</a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php }?>
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
			<div class="modal fade" id="goalsModal" tabindex="-1" aria-hidden="true">
			
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
                            <div class="form-group mb-5">
                                <label>Amount Of Quotes</label>
                                <input type="number" name="amountOfQuotes" class="form-control"   >
                            </div>
                            	
                            <div class="form-group mb-5">
                                <label>Total Amount</label>
                                <input type="number" name="totalAmount" class="form-control"   >
                            </div>
                            	
                            <div class="form-group mb-5">
                                <label>Start Date</label>
                                <input type="date" name="startDate" class="form-control"   >
                            </div>
                            	
                            <div class="form-group mb-5">
                                <label>End Date</label>
                                <input type="date" name="endDate" class="form-control"   >
                            </div>
                            <input type="text" name="actionId" value="" hidden>
                            </div>

							<div class="text-center">
								<input type="submit" value="Save" name="create_package" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
    <script>
    	$(document).ready(function(){
          $("#goalsModal").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            console.log("mydata->",mydata);
            if(mydata!= null){
            	$("#modelTitle").html("Update Weekly Goals");            	
                $("input[name='amountOfQuotes']").val(mydata['amountOfQuotes'])                
                $("input[name='totalAmount']").val(mydata['totalAmount'])                
                $("input[name='startDate']").val(mydata['startDate'])                
                $("input[name='endDate']").val(mydata['endDate'])                                
                $("input[name='actionId']").val(mydata['id'])
            }else{
                $("#modelTitle").html("Add Weekly Goals");
                $("input[name='amountOfQuotes']").val("0")
                $("input[name='totalAmount']").val("0")
                $("input[name='startDate']").val("<?echo $thisMonthStartingDate?>")
                $("input[name='endDate']").val("<?echo $thisMonthEndingDate;?>")
                $("input[name='actionId']").val("")
            }
        });
    })
    </script>

	</body>
	
	
	
	
	

</html>