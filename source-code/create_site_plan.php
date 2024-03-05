<?
require("./global.php");

if((!isset($_GET['quoteId'])) || (!$logged))
    header("Location:./index.php");

$quoteId=clear($_GET['quoteId']);
$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");

$services=getAll($con,"select * from darlelJobber_services where localUseId='None' || localUseId='$quoteId'");
$nameToDeetsServices=[];
foreach($services as $row){
    $index=$row['name']." SKU =".$row['sku'];
    $nameToDeetsServices[$index]=$row;
}

if(isset($_GET['edit'])){
    $id=clear($_GET['edit']);
    $planDeets=getRow($con,"select * from darlelJobber_site_plans where id='$id'");
}

$timeAdded=time();
$view = (isset($_GET['view'])) ? 1 :0 ;
$print = (isset($_GET['print'])) ? 1 :0 ;
$new = (isset($_GET['new'])) ? 1 :0;
$sitePlanId = ($new) ? clear($_GET['new']) : $planDeets['id'];

if($new)
    $customeUrl="create_site_plan.php?new=$sitePlanId&quoteId=$quoteId";
else
    $customeUrl="create_site_plan.php?edit=$sitePlanId&quoteId=$quoteId";
    

$quoteServicesToDeets=[];
$quoteServices=getAll($con,"select * from darlelJobber_quote_details where quoteId='$quoteId'");
foreach($quoteServices as $row){
    $quoteServicesToDeets[$row['service']]=$row;
}

if(isset($_FILES['image_saved'])){
    $file=$_FILES['image_saved'];    
	$randomName = random();
	$target_dir = "./uploads/";
	$fileName_db = "sitePlan_".$randomName.basename($file["name"]).".png";
	$target_file = $target_dir . "sitePlan_".$randomName.basename($file["name"]).".png";
	
	$for_id=$quoteId;
	$sitePlanId = (isset($_GET['new'])) ? clear($_GET['new']) : clear($_GET['edit']);

	//extracting file name from site plan details
	if(!$new)
	    $fileNameDb=getRow($con,"select * from darlelJobber_site_plans where id='$sitePlanId'")['image'];
	
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
	    $timeAdded=time();
	    if($new){
	        $query="insert into darlelJobber_site_plans set id='$sitePlanId',for_id='$for_id',image='$fileName_db',timeAdded='$timeAdded'";
	    }
	    else{
	        //removing the image which was previously saved and then renaming the image which is saved now and the name of the new image will be the same as saved in database
	        unlink("uploads/$fileNameDb");
	        rename("uploads/$fileNameDb", "uploads/$fileName_db");
            $query="update darlelJobber_site_plans set image='$fileName_db' where id='$sitePlanId'";
	    }
	    runQuery($query);
	}
}

if(isset($_POST['submitPlan'])){
    $previousPage=clear($_POST['previousPage']);
    $qty_input=$_POST['qty'];
    $service_input=$_POST['service'];
    $top_input=$_POST['top'];
    $left_input=$_POST['left'];
    $type_input=$_POST['type'];
    $optionalType_input=$_POST['optionalType'];
    
    if(isset($_GET['edit'])){
        $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where quoteId='$quoteId' && planId='$sitePlanId' group by service";
        $siteplans=getAll($con,$query);
        foreach($siteplans as $row){//deleting all previous entries in quote details which have been affected in site plan
            //deleting previous entry for this service in quote details
            
            $service=$row['service'];
            $service=clear($service);
            $query="delete from darlelJobber_quote_details where quoteId='$quoteId' && service='$service'";
            
            runQuery($query);
        }
        $query="delete from darlelJobber_site_plan_details where planId='$sitePlanId'";
        runQuery($query);
    }
    
    for($i=0;$i<count($service_input);$i++){
        $qty=$qty_input[$i];
        
        $service=$service_input[$i];
        $service=clear($service);
        $top=$top_input[$i];
        $left=$left_input[$i];
        $type=$type_input[$i];
        $optionalType=$optionalType_input[$i];
        $serviceId=$nameToDeetsServices[$service_input[$i]]['id'];
    
        $random=random();
        $query="insert into darlelJobber_site_plan_details set id='$random',optionalType='$optionalType',serviceId='$serviceId',
        planId='$sitePlanId',quoteId='$quoteId',qty='$qty',type='$type',service='$service',top_pos='$top',left_pos='$left',addedBy='$session_id',timeAdded='$timeAdded'";
        runQuery($query);
        
    }
    
    //calculating total of each service in all site plans assigned to that quote
    $totalQty=0;
    $totalQtyOptionTear=0;
    
    for($i=0;$i<2;$i++){
        if($i==0){// for teardown
            $type="TD";  
            $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where type='TD' && quoteId='$quoteId' && planId='$sitePlanId' group by service,optionalType";
        }
        else{//no teardown
            $type="NT";
            $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where quoteId='$quoteId' && planId='$sitePlanId' group by service,optionalType";
        }
        $siteplans=getAll($con,$query);
        foreach($siteplans as $row){
            $updatePrice=0;
            $service=$row['service'];
            if($i==1){//without teardown unit price
                $description=clear($quoteServicesToDeets[$service]['description']);
                $unit_price=$quoteServicesToDeets[$service]['unit_price'];//this is picking custom unit price for this service from the quote  
                if($unit_price=="" || $unit_price==0 || $unit_price==null){//if custom unit price is none then pick the unit price from services table
                    $unit_price=$nameToDeetsServices[$service]['price'];
                    $updatePrice=1;
                }
                if($description==null || $description=="")
                    $description=clear($nameToDeetsServices[$service]['description']);
            }
            else{//with teardown unit price
                $unit_price=$nameToDeetsServices[$service]['tearDownPrice'];
            }
            
            if($quoteDeets['tieredPricing']=="A" && $updatePrice){
                $increasedAmount=$unit_price*0.15;
                $unit_price+=$increasedAmount;
            }
            else if($quoteDeets['tieredPricing']=="B" && $updatePrice){
                $increasedAmount=$unit_price*0.10;
                $unit_price+=$increasedAmount;
            }
            $unit_price=round($unit_price, 2);
            
            $random=random();
            $qty=$row['total_qty'];
            $optionalType=$row['optionalType'];
            
            $optionalStatus = ($optionalType=="N.O") ? "No" : "Yes";
            
            $total=$qty*$unit_price;
            $serviceId=$nameToDeetsServices[$service]['id'];
            $image=$nameToDeetsServices[$service]['image'];
            $service=clear($service);
            $image=clear($image);
            
            //since we need only one teardown therefore we will create quote_details only for items which are not teardown
            if($i==1){
                $service=clear($row['service']);
                $query="insert into darlelJobber_quote_details set id='$random',optionalStatus='$optionalStatus',sitePlanId='$sitePlanId',quoteId='$quoteId',service='$service',
                type='$type',qty='$qty',unit_price='$unit_price',total='$total',serviceId='$serviceId',description='$description',image='$image'";
                runQuery($query);
            }
            //here we will calculate the total qty for teardown (not optional) and add it in the quoteDetails calculating full final teardown
            else if($i==0 && $optionalStatus=="No")
                $totalQty+=$qty*$unit_price;
            //here we will calculate the total qty for teardown (optional) and add it in the quoteDetails calculating full final teardown
            else if($i==0 && $optionalStatus=="Yes")
                $totalQtyOptionTear+=$qty*$unit_price;
        }
    }
    
    //if some teardown has been added then add teardown 
    $query="delete from darlelJobber_quote_details where type='TD' and quoteId='$quoteId'";
    runQuery($query);
    
    //adding teardown which is not optional 
    //if teardown is affected which means that we will have to delete TD from quotes
    $finalTearDownPrice=2.84;
    if($quoteDeets['tieredPricing']=="A")
        $finalTearDownPrice=3.26;
    else if($quoteDeets['tieredPricing']=="B")
        $finalTearDownPrice=3.12;
    
    if($totalQty>0){
        $total=$totalQty*$finalTearDownPrice;
        $random=random();
        $query="insert into darlelJobber_quote_details set id='$random',optionalStatus='No',sitePlanId='$sitePlanId',quoteId='$quoteId',
        service='Fence Tear Down',type='TD',qty='$totalQty',unit_price='$finalTearDownPrice',total='$total',
        description='Includes fence removal and desposing but not tree and branches in the way of Fence Line'";
        runQuery($query);
    }
    if($totalQtyOptionTear>0){
        $total=$totalQtyOptionTear*$finalTearDownPrice;
        $random=random();
        $query="insert into darlelJobber_quote_details set id='$random',optionalStatus='Yes',sitePlanId='$sitePlanId',quoteId='$quoteId',
        service='Fence Tear Down',type='TD',qty='$totalQtyOptionTear',unit_price='$finalTearDownPrice',total='$total',
        description='Includes fence removal and desposing but not tree and branches in the way of Fence Line'";
        runQuery($query);
    }
    updateQuote($quoteId);
    if($previousPage)
        header("Location:./createQuote.php?entryId=$quoteId");
    else
        header("Location:./create_site_plan.php?edit=$sitePlanId&quoteId=$quoteId");
}

$nameToShortTitle=[];
foreach($services as $row)
{$nameToShortTitle[$row['name']." SKU =".$row['sku']]=$row['short_title'];}

$image="";
if($planDeets['image']!=""){
    $image="./uploads/".$planDeets['image'];
    $image='"'.$image.'"';
}

if(isset($_POST['addService'])){
    $name=clear($_POST['name']);
    $type=clear($_POST['type']);
    $description=clear($_POST['description']);
    $tearDownPrice=clear($_POST['tearDownPrice']);
    $sku=clear($_POST['sku']);
    $price=clear($_POST['price']);
    $short_title=clear($_POST['short_title']);
    $timeAdded=time();
    $id=random();
    
    $query="insert into darlelJobber_services set id='$id',sku='$sku',tearDownPrice='$tearDownPrice',name='$name',short_title='$short_title',
    type='$type',description='$description',price='$price',timeAdded='$timeAdded',localUseId='$quoteId'";
    runQuery($query);
    $edit=clear($_GET['edit']);
    
    $name=$_POST['name'];
    $sku=$_POST['sku'];
    $serviceSubmitted=$name." SKU =".$sku;
    $serviceSubmitted=urlencode($serviceSubmitted);
    
    header("Location:./create_site_plan.php?edit=$edit&quoteId=$quoteId&serviceSubmitted=$serviceSubmitted");
}
?>

<html lang="en">
<head>
    
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?echo $projectName?></title>
  <link rel="shortcut icon" href="assets/logo.png" />
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13$.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
  <script src="painterro-1.2.78.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    
    <style>
    .loader-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .loader {
      border: 16px solid #f3f3f3; /* Light grey */
      border-top: 16px solid #3498db; /* Blue */
      border-radius: 50%;
      width: 120px;
      height: 120px;
      animation: spin 2s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .scrollable-container {
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        appearance: none;
        margin: 0;
    }
    .smallBtn{
        line-height: 0.5;
        padding: 5;
        border:0;
        vertical-align:inherit;
    }
    <?if($view){?>
    @media only screen and (max-width:450px) {
        body {
            zoom : 33%;
            margin-top:100px;
        }
    }
    <?}?>
    .nav {
        z-index: 1;
        position: fixed;text-align: center;
        font-family: 'Open sans', sans-serif;
    }
    
    .nav a {
      text-decoration:none;
      padding: 0 10px;
    }
    .ptro-holder {
      bottom: 32px;
    }
    
    </style>
    <link href="includes/autocompletecss.css" rel="stylesheet" type="text/css"/>
    <script src="./touchPunch.js"></script>
</head>

<body><!--style="position: absolute;height: 850px;width: 10000px;overflow: auto;" height:1366;width:1024px-->
 
 <!--if view then display logo and previous page button-->
     <?if($view){?>
    <div class="row" style="z-index: 300;">
        <?if(!$print){?>
        <div class="col-8 text-center">
            <img src="assets/logo.png" style="height: 200px;">
            <h2 style="display: inline;">Site Plan View</h2>
        </div>
        <div class="col-4">
            <a href="viewQuote.php?entryId=<?echo $quoteId?>" class="btn btn-warning text-white ml-2" style="background-color: rgb(247, 185, 36);margin-top: 50px;">Go Back</a>
        </div>
        <?}else{?>
        <div class="col-12 text-center">
            <img src="assets/logo.png" style="height: 200px;">
            <h2 style="display: inline;">Site Plan View</h2>
        </div>
        <?}?>
    </div>
     <?}?>
 
 <form id="myform" method="post" action="" enctype="multipart/form-data">
     <input name="previousPage" type="text" value="0" hidden>
     <div <?if(!$view){?>id="myPainterro"<?}?> style="position: absolute;height: 1200px;width: 1024px;overflow: auto;">
         <?if($view){?>
            <img src="./uploads/<?echo $planDeets['image'];?>">
         <?}?>
     </div>
     <input type="text" name="actionId" value="<?echo $_GET['edit']?>" hidden>
     <div  style="margin: 50px;z-index: 200;position: absolute;width:1024px;<?if(!$view){?>border-bottom: 2px solid red;<?}?>">
         
         <?if(!$view){?>
         <div class="row" style="margin: 50px;">
            
            <div class="col-7 pl-0 pr-0">
                <?if(isset($_GET['serviceSubmitted']))
                    $serviceSubmitted=urlencode($_GET['serviceSubmitted']);?>
                <iframe class="w-100" src="./iframe.php?quoteId=<?echo $quoteId; if(isset($_GET['serviceSubmitted'])){echo "&serviceSubmitted=$serviceSubmitted";}?>" style="border: none;height:0px;"></iframe>
            </div>
             <div class="col-5 pl-0 pr-0">
                <a class="btn btn-primary text-white" onclick="generateInputBox()">Get Box</a>
                <input id="submitPlan" type="submit" name="submitPlan" class="btn btn-warning text-white" value="Submit" hidden>
                <a onclick="saveAndRedirect()" class="btn btn-warning text-white" style="background-color: rgb(247, 185, 36);">Go Back</a>

                <?if(!$new){?>
                <a onclick="showModal()" class="btn btn-warning text-white" style="background-color: rgb(247, 185, 36);">
                    Add Service
                </a>
                <?}?>
                
                <div id="loader" class="loader" style="display:none;" >
                  <div class="spinner"></div>
                  <span class="loader-text">...</span>
                </div>
                <div class="modal fade show" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Service</h5>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-1">
                                <label>Name</label>
                            	<input type="text" name="name" class="form-control" placeholder="Enter Name">
							</div>
							<div class="col-12 mb-1">
                                <label>Short Title</label>
                            	<input type="text" name="short_title" class="form-control" placeholder="Enter Short Title">
							</div>
							<div class="col-12 mb-1">
                                <label>SKU</label>
                            	<input type="text" name="sku" class="form-control" placeholder="Enter SKU">
							</div>
							<div class="col-12 mb-1">
                                <label>Type</label>
                                <select class="form-control" name="type">
								    <option value="">---Select Item Type---</option>
								    <option selected value="Service">Service</option>
								    <option value="Product">Product</option>
								</select>
							</div>
							<div class="col-12 mb-1">
                                <label>Description</label>
                            	<textarea class="form-control" name="description" placeholder="Enter Description" rows="5"></textarea>
                            </div>
							<div class="col-12 mb-1">
                                <label>Unit Price</label>
                            	<input type="number" name="price" class="form-control" step="0.01" placeholder="Enter Unit Price">
							</div>
							<div class="col-12 mb-1">
                                <label>Tear Down Price</label>
                            	<input type="number" name="tearDownPrice" class="form-control" step="0.01" placeholder="Enter Unit Price">
							</div>
							
                        </div>    
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                        <input type="submit" name="addService" class="btn btn-primary" value="Save Changes">
                      </div>
                    </div>
                  </div>
                </div>

             </div>
         </div>
         <?}?>
         <div id="inputBoxArea" class="row">
            <?if(isset($_GET['edit'])){
                $planId=clear($_GET['edit']);
                $sitePlanEntries=getAll($con,"select * from darlelJobber_site_plan_details where planId='$planId'");
                foreach($sitePlanEntries as $row){
                    $random=random();
            ?>  
                <div id="<?echo $row['id']?>" class="<?echo $random?>">
                    <div style="padding:30px;" >
                        <div class="form-group" <?if(!$view){ echo "style='line-height: 0;'";}?> >
                            <?if($view && $session_role!="Client"){
                            $optionalText = ($row['optionalType']=="Opt") ? " ".$row['optionalType'] : "";
                            ?>
                            <!--<p style="font-weight: bolder;font-size: large;"><?echo $row['qty']."' (".$row['type'].")<br>".$nameToShortTitle[$row['service']].$optionalText?></p>-->
                            <?}else if(!$view){?>
                            <a class="btn btn-danger btn-sm text-white smallBtn" onclick="removeInput('<?echo $row['id']?>')">X</a>
                            <a onclick="updateTearDown('<?echo $random?>')" name="teardown_status[]" class="btn btn-warning btn-sm text-white smallBtn"><?echo $row['type']?></a>
                            
                            <a onclick="updateOptional('<?echo $random?>')" name="optional_status[]" class="btn btn-warning btn-sm text-white smallBtn"><?echo $row['optionalType']?></a><br>
                            
                            <input name="qty[]" value="<?echo $row['qty']?>" style="width: 50px;" type="number"><br>
                            <a class="btn btn-primary btn-sm text-white smallBtn"><?echo $nameToShortTitle[$row['service']]?></a>
                            <input name="service[]" value="<?echo htmlspecialchars($row['service'])?>" type="text" hidden>
                            <?}?>
                            <input name="optionalType[]" value="<?echo $row['optionalType']?>" type="text" hidden>
                            <input name="type[]" value="<?echo $row['type']?>" type="text" hidden>
                            <input name="top[]" value="<?echo $row['top_pos']?>" type="text" hidden>
                            <input name="left[]" value="<?echo $row['left_pos']?>" type="text" hidden>
                        </div>
                    </div>
                </div>
                
            <?}?>
            <?}?>
         </div>
     </div>
 </form>
 <script>
 
    var nameToDeetsServices=<?echo json_encode($nameToDeetsServices);?>; 
 
    <?foreach($sitePlanEntries as $row){?>
    var id="<?echo $row['id'];?>";
    <?if(!$view){?>
    $( "#"+id).draggable({
      refreshPositions: true
    });
    <?}?>
    $("#"+id).css({ 
        position: "absolute",
        <?if($view){?>
        width:"max-content",
        <?}?>
        top:<?echo $row['top_pos']?>,
        left:<?echo $row['left_pos']?>,
    });
    <?}?>

    <?if($view){?>
    setTimeout( function() {
        $('.ptro-bar').hide(); 
    }, 100);
    <?}?>
    
    function removeInput(id){
        $('#'+id).remove();
    }
    
    $('#myform').submit(function() {
        var index=0;
        $('input[name^="qty"]').each( function() {
            
            var position=$("input[name='qty[]']").eq(index).offset();
            var top=position.top-234-48+186;
            var left=position.left-130+50;
            console.log("top"+top);
            console.log("left"+left);
            
            $("input[name='top[]']").eq(index).val(top);
            $("input[name='left[]']").eq(index).val(left);
          
            index++;
        });
        
        return true; // return false to cancel form action
    });
         //./uploads/sitePlan_26X7AUV01Jblob.png
         <?if(!$view){?>
         
         var ptro =
         Painterro({
            id:'myPainterro',
            activeColor:'#130c00',
            defaultLineWidth:'1',
            defaultPrimitiveShadowOn:'true',
            hiddenTools:["crop","pixelize","select","close","bucket","resize","settings","clear","zoomin","zoomout","rotate"],
            defaultTool:'line',
            saveHandler: function (image, done) {
                var formData = new FormData();
                formData.append('image_saved', image.asBlob());
                // you can also pass suggested filename 
                // formData.append('image', image.asBlob(), image.suggestedFileName());
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?echo $customeUrl ?>', true);
                xhr.onload = xhr.onerror = function () {
                  // after saving is done, call done callback
                  done(false); //done(true) will hide painterro, done(false) will leave opened
                  $("#submitPlan")[0].click();
                };
                xhr.send(formData);
            }
        });
        ptro.show(<?echo $image?>);
        <?if($new){?>
        ptro.save();
        <?}?>
        <?}?>
        
        var position=1;
        var nameToShortTitle=<?echo json_encode($nameToShortTitle)?>;
     
     function generateInputBox(){
        var iframe = $('iframe');
        var iframeInput = iframe.contents().find('#tags');
        var service = iframeInput.val();
        
        var priceIndex = service.indexOf("Price");
        service = priceIndex !== -1 ? service.substring(0, priceIndex).trim() : service;

        
        console.log(service);
        var id=makeid(5);
         var serviceVal= service.replace(/"/g, '&quot;');
         var string=`
            <div id="`+id+`" class="`+id+`">
                <div style="padding:30px;" >
                    <div class="form-group" style="line-height: 0;">
                        <a class="btn btn-danger btn-sm text-white smallBtn" onclick="removeInput('`+id+`')">X</a>
                        <a onclick="updateTearDown('`+id+`')" name="teardown_status[]" class="btn btn-warning btn-sm text-white smallBtn">NT</a>
                        <a onclick="updateOptional('`+id+`')" name="optional_status[]" class="btn btn-warning btn-sm text-white smallBtn">N.O</a><br>
                            
                        <input name="qty[]" value="1" style="width: 50px;" type="number" ><br>
                        <a class="btn btn-primary btn-sm text-white smallBtn">`+nameToShortTitle[service]+`</a>
                        
                        <input name="optionalType[]" value="N.O" type="text" hidden>
                        <input name="type[]" value="NT" type="text" hidden>
                        <input name="service[]" value="`+serviceVal+`" type="text" hidden>
                        <input name="top[]" value="`+position+`" type="text" hidden>
                        <input name="left[]" value="`+position+`" type="text" hidden>
                    </div>
                </div>
            </div>
         `;
         position++;
         $('#inputBoxArea').append(string);
         $( "#"+id).draggable({
          refreshPositions: true
        });
        $("#"+id).css({ 
            position: "absolute",
        });
     }
     function updateTearDown (divId){
         var teardown_status = $("."+divId+" a[name='teardown_status[]']").text();
         if(teardown_status=="NT"){
             $("."+divId+" a[name='teardown_status[]']").text("TD");
             $("."+divId+" input[name='type[]']").val("TD");
         }
         else{
             $("."+divId+" a[name='teardown_status[]']").text("NT");
             $("."+divId+" input[name='type[]']").val("NT");
         }
     }
     
     function updateOptional(divId){
         var optional_status = $("."+divId+" a[name='optional_status[]']").text();
         if(optional_status=="N.O"){
             $("."+divId+" a[name='optional_status[]']").text("Opt");
             $("."+divId+" input[name='optionalType[]']").val("Opt");
         }
         else{
             $("."+divId+" a[name='optional_status[]']").text("N.O");
             $("."+divId+" input[name='optionalType[]']").val("N.O");
         }
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
    
    function saveAndRedirect() {
        $('#loader').show();
        $("input[name='previousPage']").val("1");
        $("button[title='Save image']")[0].click();
    }

    function showModal(){
        var iframe = $('iframe');
        var iframeInput = iframe.contents().find('#tags');
        var service = iframeInput.val();
        var priceIndex = service.indexOf("Price");
        service = priceIndex !== -1 ? service.substring(0, priceIndex).trim() : service;

        
        var serviceDeets=nameToDeetsServices[service];
        
        if(serviceDeets!=null){
            $("input[name='name']").val(serviceDeets['name']);
            $("input[name='short_title']").val(serviceDeets['short_title']);
            $("input[name='sku']").val(serviceDeets['sku']);
            $("textarea[name='description']").val(serviceDeets['description']);
            $("input[name='tearDownPrice']").val(serviceDeets['tearDownPrice']);
            $("input[name='price']").val(serviceDeets['price']);
        }
        
        $('#exampleModal').show();
    } 
    
    function closeModal(){
        $('#exampleModal').hide();
    } 
    $(document).ready(function() {
        
        <?if($print){?>
        window.print();
        <?}?>
        $('form').submit(function(event) {
          $(this).find(':submit').css('pointer-events', 'none');
        });
        var newHeight = 200; // Replace with your desired height in pixels
        var iframe = $('iframe');
        iframe.css('height', newHeight);
    });
    
 </script>
</body>
         
</html>