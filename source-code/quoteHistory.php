<?
require("./global.php");
if($logged==0 || (!isset($_GET['quoteId'])))
    header("Location:./index.php");
$quoteId=clear($_GET['quoteId']);
$idToInfo=[];
$users=getAll($con,"select * from darlelJobber_users where role!='Client'");
foreach($users as $row)
    $idToInfo[$row['id']]=$row;
$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");

?>
<html lang="en">
	<head>
	    <link rel="stylesheet" href="lightbox/dist/css/lightbox.min.css">
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
							    <div class="card card-flush">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search History" />
											</div>
										</div>
										<div class="card-toolbar">
										    
											<a href="<?echo $_SERVER['HTTP_REFERER']; ?>" class="btn btn-primary">Go Back : <?echo "# Quote Number : ".$quoteDeets['quote_number']?></a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="table-responsive">
                                            <table class="table table-rounded table-striped border gs-7 text-center" id="kt_ecommerce_category_table">
										        <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Type</th>
                                                        <th>Added By</th>
                                                        <th>Time Added</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?$quoteHistory=getAll($con,"select * from darlelJobber_quote_history where quoteId='$quoteId' order by timeAdded desc");
                                                    foreach($quoteHistory as $row){
                                                        if($row['type']=="Call Log")
                                                            $badge="<a class='badge badge-primary text-white'>".$row['type']."</a>";
                                                        else if($row['type']=="Reminder")
                                                            $badge="<a class='badge badge-success text-white'>".$row['type']."</a>";
                                                        else if($row['type']=="Quote Changed")
                                                            $badge="<a class='badge badge-warning text-white'>".$row['type']."</a>";?>
                                                    <tr>
                                                        <td><?echo $row['title'];?></td>
                                                        <td><?echo $badge;?></td>
                                                        <td><?echo $idToInfo[$row['addedBy']]['name']?></td>
                                                        <td><?echo date("d M Y",$row['timeAdded'])?></td>
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
				<script src="lightbox/dist/js/lightbox-plus-jquery.min.js"></script>
        
			<?require("./includes/views/footerjs.php");?>
		
	    </div>
	</body>
</html>