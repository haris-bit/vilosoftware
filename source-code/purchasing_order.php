<?
require("./global.php");
$parameters = "$_SERVER[QUERY_STRING]";

$edit=0;
if(isset($_GET['purchasing_id']))
    $edit=1;

if($logged==0)
    header("Location:./index.php");

$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row)
{$idToInfo[$row['id']]=$row;}

$purchasing_id=$_GET['purchasing_id'];
$purchasingDeets=getRow($con,"select * from darlelJobber_shop_orders where id='$purchasing_id'");
$orderId=$_GET['orderId'];

$services=getAll($con,"select * from darlelJobber_services order by timeAdded desc");
$allServices=[];
foreach($services as $row){
    $index=$row['name']." SKU =".$row['sku'];
    $allServices[$index]=$row;
}


if(isset($_POST['create_order'])){
    //quote main entry
    $title = clear($_POST['title']);
    $total=clear($_POST['final_total']);
    $timeAdded=time();
    
    if(!$edit){
        $random=generateRandomString();
        $query="insert into darlelJobber_shop_orders set id='$random',title='$title',timeAdded='$timeAdded',orderId='$orderId',addedBy='$session_id'";
        runQuery($query);
        
        $purchasing_id=$random;
    }
    else if($edit){
        $query="update darlelJobber_shop_orders set title='$title',total='$total' where id='$purchasing_id'";
        runQuery($query);
    }
    
    //purchasing order details
    if($edit){
        $query="delete from darlelJobber_order_details where purchasing_id='$purchasing_id'";
        runQuery($query);
    }
    
    $service_inp=$_POST['service'];
    $qty_inp=$_POST['qty'];
    $unit_price_inp=$_POST['unit_price'];
    $total_inp=$_POST['total'];
    $description_inp=$_POST['description'];
    $helperFile_inp=$_POST['helperFile'];
    $type_inp=$_POST['type'];
    $target_dir = "./servicesImages/";
    for($i=0;$i<count($service_inp);$i++){
        $service=clear($service_inp[$i]);
        $qty=$qty_inp[$i];
        $unit_price=$unit_price_inp[$i];
        $total=$total_inp[$i];
        $description=clear($description_inp[$i]);
        $helperFile=clear($helperFile_inp[$i]);
        $type=clear($type_inp[$i]);
        
        if( !empty( $_FILES[ 'images' ][ 'error' ][ $i ] ) )
            $image=$helperFile;
        else{
            $image = $_FILES[ 'images' ]['name'][$i];
            $tmpName = $_FILES[ 'images' ][ 'tmp_name' ][ $i ];
            $target_file = $target_dir.$image;
            move_uploaded_file( $tmpName, $target_file ); 
        }
        $random=generateRandomString();
        $query="insert into darlelJobber_order_details set id='$random',purchasing_id='$purchasing_id',service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description',image='$image'";
        runQuery($query);
    }
    header("Location:./purchasing_order.php?purchasing_id=$purchasing_id");
}

if(isset($_GET['delete_site_plan'])){
    $id=$_GET['delete_site_plan'];
    $query="delete from darlelJobber_site_plan_details where planId='$id'";
    runQuery($query);
    
    $query="delete from darlelJobber_site_plans where id='$id'";
    runQuery($query);
}

?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
	    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
          <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
            <script src="assets/plugins/global/plugins.bundle.js"></script>
        
          <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        
           <style>
               .ui-autocomplete {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    float: left;
    text-align:center;
    margin-bottom:4px;
    display: none;
    min-width: 160px;   
    padding: 4px 0;
    margin: 0 0 10px 25px;
    list-style: none;
    background-color: #ffffff;
    border-color: #ccc;
    border-color: rgba(0, 0, 0, 0.2);
    border-style: solid;
    border-width: 1px;
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    border-radius: 5px;
    -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    -moz-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    -webkit-background-clip: padding-box;
    -moz-background-clip: padding;
    background-clip: padding-box;
    *border-right-width: 2px;
    *border-bottom-width: 2px;
}

.ui-menu-item > a.ui-corner-all {
    display: block;
    padding: 3px 15px;
    clear: both;
    font-weight: normal;
    line-height: 18px;
    color: #555555;
    white-space: nowrap;
    text-decoration: none;
}

.ui-state-hover, .ui-state-active {
    color: #ffffff;
    text-decoration: none;
    background-color: #0088cc;
    border-radius: 0px;
    -webkit-border-radius: 0px;
    -moz-border-radius: 0px;
    background-image: none;
}
           </style>
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
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl">
								<form action="" method="post" enctype="multipart/form-data">
									<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
										<div class="tab-content">
											<div class="tab-pane fade active show" role="tab-panel">
												<div class="d-flex flex-column gap-7 gap-lg-10">
													<div class="card card-flush py-4 mb-15">
														<div class="card-header">
															<div class="card-title">
																<h2>Purchasing Order</h2>
															</div>
														</div>
														<div class="card-body pt-0">
															<div class="mb-10 fv-row fv-plugins-icon-container">
																<label class="required form-label">Order Title</label>
																<input type="text" name="title" class="form-control mb-2" value="<?echo $purchasingDeets['title']?>" placeholder="Order Title" >
														    </div>
														    <div class="table-responsive">
                                                            <table class="table table-rounded table-striped border gy-7 gs-7">
                                                                <thead>
                                                                    <tr>
                                                                        <th >SHOP / SERVICE</th>
                                                                        <th >QTY</th>
                                                                        <th >IMAGE</th>
                                                                        <th >UNIT PRICE</th>
                                                                        <th >TOTAL</th>
                                                                        <th><a style="white-space: nowrap;" onclick="addRow()" class="btn btn-primary btn-sm"><i style="font-size: x-large;" class="las la-plus"></i>Line Item</a></th>
                                                                    </tr>
                                                                </thead>
    														    
    														    <tbody id="purchasing_section">
        														    
        														    <?if(isset($_GET['purchasing_id'])){
        													        $orderDeets=getAll($con,"select * from darlelJobber_order_details where purchasing_id='$purchasing_id'");
        													        foreach($orderDeets as $nrow){
        													            $random=random();?>
    													            <tr id="<?echo $random?>" class="<?echo $random?>">
    													                <td>
    												                        <input onfocusout="fillPerUnitCost('<?echo $random?>')" type="text" class="form-control" value="<?echo htmlspecialchars($nrow['service'])?>"  name="service[]" >
        														            <textarea class="form-control" placeholder="Description" name="description[]" rows="3"><?echo htmlspecialchars($nrow['description'])?></textarea>
            														    </td>
            														    <td>
    													                    <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="<?echo $nrow['qty']?>">
        														        </td>
        														        <td>
    														                <img style="height: 100px;width:180px;margin: 10px;" src="./servicesImages/<?echo $nrow['image']?>" alt="img" name="showImage[]">
        														            <a onclick="removeImage('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
    														                <input type="file" name="images[]" class="form-control" <?if($nrow['image']!=""){?>style="display:none;"<?}?> >
    														                <input type="text" name="helperFile[]" class="form-control" value="<?echo $nrow['image']?>" hidden>
    														                <input type="text" name="type[]" value="<?echo $nrow['type']?>" hidden>
    														            </td>
    														            <td>
    														                <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="<?echo $nrow['unit_price']?>">
        														            <?if($nrow['type']=="TD"){?>
        														                <a style="margin-top: 50px;margin-left: 20px;" class="btn btn-light-warning btn-sm">Tear Down</a>
        														            <?}?>
    														            </td>
    														            <td>
    													                    <input class="form-control" type="number" step="0.01" name="total[]" placeholder="Total" value="<?echo $nrow['total']?>">
            													        </td>
            													        <td>
        													                <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
            													        </td>
    													            </tr>
    													            <?}}?>
    													        </tbody>
														    </table>
    													    </div>
													        <hr>
													        <div class="row">
													            <div class="col-md-6 col-12"></div>
													            <div class="col-md-6 col-12" style="text-align:right;">
													                <div class="row">
													                    <div class="col-12 mb-3">
													                        <p>Total ($) :  
    													                        <input class="form-control" style="width: 60%;display: inline;" type="number" step="0.01" 
    													                        name="final_total" value="<?echo round($purchasingDeets['total'],2)?>" readonly>
												                            </p>
													                    </div>
													                    <div class="col-12">
													                        <input type="submit" class="btn btn-primary w-100" name="create_order" value="Save Order">
													                    </div>
													                </div>
													            </div>
													        </div>
													        <hr>
													        <div class="row">
													            <?if(isset($_GET['purchasing_id'])){$random=random();?>
        													    <div class="card shadow-sm">
    											                    <div class="card-header">
													                    <h3 class="card-title">Site Plans</h3>
													                    <div class="card-toolbar">
    													                    <a href="createSitePlanPurchasing.php?new=<?echo $random?>&purchasing_id=<?echo $_GET['purchasing_id']?>" class="btn btn-primary btn-sm">Create Site Plan</a>
        													            </div>
													                </div>
    													            
                                                                    <div class="card-body">
        												                <div class="table-responsive">
                                                                            <table class="table table-rounded table-striped border gy-7 gs-7">
        												                        <thead>
        													                        <tr>
        													                            <th>Title</th>
        													                            <th>Time Added</th>
        													                            <th>Actions</th>
        													                        </tr>
        													                    </thead>
        													                    <tbody>
        													                        <?
        													                        $purchasing_id=$_GET['purchasing_id'];
        													                        $sitePlans=getAll($con,"select * from darlelJobber_site_plans where for_id='$purchasing_id'");
        													                        foreach($sitePlans as $row){?>
        													                        <tr>
        													                            <td><?echo $row['title']?></td>
        													                            <td><?echo date("d M y",$row['timeAdded'])?></td>
        													                            <td>
        													                                <div class="btn-group">
        													                                    <a class="btn btn-primary btn-sm" href="./createSitePlanPurchasing.php?edit=<?echo $row['id']?>&purchasing_id=<?echo $row['for_id']?>&view=1">View</a>
        													                                    <a class="btn btn-warning btn-sm" href="./createSitePlanPurchasing.php?edit=<?echo $row['id']?>&purchasing_id=<?echo $row['for_id']?>">Edit</a>
        													                                    <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record" class="btn btn-danger btn-sm" data-url="?delete_site_plan=<?echo $row['id']?>&purchasing_id=<?echo $row['for_id']?>">Delete</a>
        													                                </div>
        													                            </td>
        													                        </tr>
        													                        <?}?>
        													                    </tbody>
        												                    </table>
        									                            </div>
													                </div>
													            </div>
													            <hr>
													            <?}?>
													    </div>
												</div>
											</div>
										</div>
									</div>
								<div></div>
							</form>
							</div>
						</div>
					</div>
					
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
			<script>var hostUrl = "assets/";</script>
    		<script src="assets/js/scripts.bundle.js"></script>
    		  <div class="modal fade" tabindex="-1" id="delete_record">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Entry</h5>
            
                            <!--begin::Close-->
                            <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                                <span class="svg-icon svg-icon-2x"></span>
                            </div>
                            <!--end::Close-->
                        </div>
            
                        <div class="modal-body">
                            <p>Are You Sure You Want To Delete This Entry ?</p>
                        </div>
            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <a href="#sd" id="delete-project" class="btn btn-danger">
                                Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                $(document).ready(function(){
                    calculateFinalTotal();
                    
                    $('form').submit(function(event) {
                      $(this).find(':submit').css('pointer-events', 'none');
                    });
              
                  $("#delete_record").on('show.bs.modal', function (e) {
                    //get data-id attribute of the clicked element
                    var url = $(e.relatedTarget).data('url');
                    console.log("modal opened", name)
                    //populate the textbox
                     $("#delete-project").attr("href", url);
                
                  });
                });
            </script>  
	
	
		</div>
	</body>
	
	<script>
	
	    var allServices=<?echo json_encode($allServices);?>;
	    function fillPerUnitCost(divId)
        {
             var serviceId=$("."+divId+" input[name='service[]']").val()
             var unitPrice=allServices[serviceId]['price'];
             var description=allServices[serviceId]['description'];
             var image=allServices[serviceId]['image'];
             
             $("."+divId+" input[name='images[]']").hide();
             $("."+divId+" img[name='showImage[]']").attr("src", "./servicesImages/"+image);
             $("."+divId+" input[name='helperFile[]']").val(image);
             var helper=$("."+divId+" input[name='helperFile[]']").val();
             $("."+divId+" textarea[name='description[]']").val(description);
             $("."+divId+" input[name='unit_price[]']").val(unitPrice);
             $("."+divId+" input[name='qty[]']").val("0");
             $("."+divId+" input[name='discount[]']").val("0");
             $("."+divId+" input[name='total[]']").val("0");
        }
        
    function calculateTotal(divId)
    {
        var unit_price=$("."+divId+" input[name='unit_price[]']").val()
        var qty=$("."+divId+" input[name='qty[]']").val();
        var total=unit_price*qty;
        $("."+divId+" input[name='total[]']").val(total);
        
        //calculating subtotal at the end
        var totalPrice=0;
        $('input[name^="total"]').each( function() {
            totalPrice += parseInt(this.value);
        });
        $("input[name='final_total']").val(totalPrice);
    }
    function calculateFinalTotal()
    {
        var totalPrice=0;
        $('input[name^="total"]').each( function() {
            totalPrice += parseInt(this.value);
        });
        $("input[name='final_total']").val(totalPrice);
    }
    function removeImage(divId)
    {
        $("."+divId+" img[name='showImage[]']").attr("src", "");
        $("."+divId+" input[name='helperFile[]']").val("");
        $("."+divId+" input[name='images[]']").show();
    }
    var availableTags = [
    <?foreach($services as $row){?>
      `<?echo $row['name']." SKU =".$row['sku']?>`,
      <?}?>
    ];
    function addRow()
    {
        var id=makeid(5);
        var string=`
        
        <tr id="`+id+`" class="`+id+`">
            <td>
                <input onfocusout="fillPerUnitCost('`+id+`')" type="text" class="form-control" name="service[]" >
                <textarea class="form-control" placeholder="Description" name="description[]" rows="3"></textarea>
	        </td>
	        <td>
	            <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="0">
		    </td>
	        <td>
	            <img style="height: 100px;width:180px;margin: 10px;" src="" alt="img" name="showImage[]">
	            <a onclick="removeImage('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
                <input type="file" name="images[]" class="form-control" >
                <input type="text" name="helperFile[]" class="form-control" hidden>
                <input type="text" name="type[]" hidden>
			</td>
			<td>
	            <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="0">
	        </td>
			<td>
		        <input class="form-control" type="number" step="0.01" name="total[]" placeholder="Total" value="0">
	        </td>
	        <td>
	            <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
	        </td>
	    </tr>
        `;
		$('#purchasing_section').append(string);
		$("."+id+" input[name='service[]']").autocomplete({
          source: function(request, response) {       var words = request.term.split(" ");       var matcher = new RegExp($.map(words, function(word) {         return "(?=.*\\b" + $.ui.autocomplete.escapeRegex(word) + "\\b)";       }).join(""), "i");       var filteredTags = $.grep(availableTags, function(value) {         value = value.label || value.value || value;         return matcher.test(value);       });       if (filteredTags.length === 0) {         filteredTags = $.grep(availableTags, function(value) {           value = value.label || value.value || value;           return value.indexOf(request.term) >= 0;         });       }       response(filteredTags);     }     
        });
    }
    function removeRow(id){
        console.log(id);
        $('#'+id).remove();
        
        $("input[name='final_total']").val("0");
    }
    
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
	</script>
</html>
