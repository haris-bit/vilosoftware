<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");
    
    
$ticketId=clear($_GET['ticketId']);
if(!isset($_GET['ticketId']))
    $ticketId="None";
else
    $ticketDeets=getRow($con,"select * from darlelJobber_tickets where id='$ticketId'");

$view=0;
if(isset($_GET['view']))
    $view=1;
    
$message="Thank you, our team will be in contact soon . We appreciate your business";

if(isset($_POST['addTicket'])){
    
    $description=clear($_POST['description']);
    $jobId=clear($_POST['jobId']);
    $ticketId=clear($_POST['ticketId']);
    $timeAdded=time();
    if($ticketId==""){
        $ticketId=generateRandomString();
        $query="insert into darlelJobber_tickets set id='$ticketId',title='Ticket',jobId='$jobId',description='$description',timeAdded='$timeAdded',addedBy='$session_id',type='Client'";
    }
    else    
        $query="update darlelJobber_tickets set jobId='$jobId',description='$description' where id='$ticketId'";
    runQuery($query);
    header("Location:?ticketId=$ticketId");
}
if(isset($_GET['delete-file'])){
    $id=$_GET['delete-file'];
    $query="delete from darlelJobber_notes where id='$id'";
    runQuery($query);
}

require("./notes/notes.php");
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
					
					<form method="post" action="" enctype="multipart/form-data" id="ticketForm"> 
                    <div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;">
					    <div class="post d-flex flex-column-fluid">
					        <div class="container-xxl" id="kt_content_container">
					            
					            <div class="row g-5 g-xl-10">
    					            <div class="card card-flush py-4" style="margin-bottom: 30px;">
    									<div class="card-header">
    										<div class="card-title">
    											<h2>Ticket Details</h2>
    						                    <input type="text" name="ticketId" value="<?echo $_GET['ticketId']?>" hidden="">
    										</div>
    									</div>
    									<div class="card-body pt-0">
    									    <div class="row">
    									        <div class="col-12" style="margin-top:10px;">
    									            <label>Select Job</label>
    									            <select class="form-control" name="jobId">
    									                <?
    									                $query="select j.id,j.title,job_number,CONCAT(street1,' ', street2 , ' ' , city , state , ' ' , country , ' (Zip : ' , zip_code , ' )') 
    									                as 'Address'  from darlelJobber_jobs j inner join darlelJobber_properties p on j.propertyId=p.id where j.customerId='$session_id'";
    									                $jobs=getAll($con,$query);
    									                foreach($jobs as $row){?>
    									                    <option <?if($ticketDeets['jobId']==$row['id']){echo "selected";}?> value="<?echo $row['id']?>"><?echo "#".$row['job_number'].$row['title']."(Address : ".$row['Address']." )"?></option>
    									                <?}?>
    									                <optio><?echo $query?></optio>
    									            </select>
    									        </div>
    									        <div class="col-12" style="margin-top:10px;">
    									            <label>Please describe affected area</label>
    									            <textarea class="form-control" name="description" placeholder="Enter Description"> <?echo $ticketDeets['description']?></textarea>
    									        </div>
    									        <div class="col-12" style="margin-top:20px;">
    									            <?if(isset($_GET['new'])){?>
    									                <h2 style="text-align:center;color:red">Kindly Create The Ticket To Upload Images</h2>
    									            <?}else{?>
            										
            										<!--notes section-->
        										    <?include("./notes/notes_table.php");?>
										            <?}?>
    									        
    									        </div>
    									       <?if(!$view){?>
								                <div class="col-12" style="text-align:center;margin-top:20px;">
								                    <input type="submit" name="addTicket" class="btn btn-primary w-50" value="Save Changes">
								                </div>
								                <?}?>
    									    </div>
    									</div>
    							    </div>
					            </div>
					            
					        </div>
					    </div>
					</div>
					
					
		            </form>		
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
	<?require("./includes/views/footerjs.php");?>
		</div>
	</body>
	
		<?include("./notes/notes_js.php");?>
		<script>
		    $(document).ready(function() {
	        <?if($view){?>
                $("#ticketForm :input").prop("disabled", true);
	        <?}?>
            });
		</script>
</html>