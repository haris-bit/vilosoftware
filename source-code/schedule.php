<?
require("./global.php");

if($logged==0){
    header("Location:./index.php");
    exit();
}

$query="select q.*,c.first_name,c.last_name from darlelJobber_quotes q inner join darlelJobber_users c on q.customerId=c.id where estimatorId='$session_id' 
and q.approveStatus='Approved' and viewedByEstimator='No'";
$approvedQuotes=getAll($con,$query);
$quotesId=[];
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
				<a href="#" data-bs-toggle="modal" data-bs-target="#approvedQuotes" class="btn btn-primary" id="approvedQuotesBtn" hidden>Add Label</a>
										
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" style="padding-top: 0px !important;" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					<div class="content d-flex flex-column flex-column-fluid">
					    <div class="post d-flex flex-column-fluid">
					        <div class="container-xxl" style="max-width: 100%;" id="kt_content_container">
		                        <iframe src="./calendar_app" class="w-100" style="height:800px;margin-top: 60px;"></iframe>
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
		
    	<div class="modal fade" id="approvedQuotes" tabindex="-1" aria-hidden="true">
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
    						    <h1 class="mb-3" id="modelTitle">Approved Quotes</h1>
    						</div>
    						<div class="row">
    						    <?foreach($approvedQuotes as $row){
    						    $quotesId[]=$row['id'];?>
    						    <div class="col-12 mb-3">
    						        <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h3 class="card-title">Quote #<?echo $row['quote_number']?> has been approved on <?echo date("d M Y",$row['approveTime'])." By ".$row['first_name']." ".$row['last_name']?> </h3>
                                            <div class="card-toolbar">
                                                <a class="btn btn-warning btn-sm" href="tasks.php?quoteId=<?echo $row['id']?>&userId=<?echo $session_id?>">Add Reminder</a>
                                            </div>
                                        </div>
                                    </div>
    						    </div>
    						    <?}
    						    $quotesId = "'" . implode("','", $quotesId) . "'";
                                runQuery("update darlelJobber_quotes set viewedByEstimator='Yes' where id in ($quotesId) ");?>
    						</div>
    
    					</form>
    				</div>
    			</div>
    		</div>
    	</div>
    	
    	<script>
    	    $( document ).ready(function() {
	            <?if(count($approvedQuotes)>0){?>
	            $("#approvedQuotesBtn")[0].click();
	            <?}?>    
            });
    	</script>
	</body>
	<!--end::Body-->
</html>