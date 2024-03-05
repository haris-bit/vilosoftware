<?
require("./global.php");

if((!isset($_GET['purchasing_id'])) || (!$logged))
    header("Location:index.php");

$purchasing_id=clear($_GET['purchasing_id']);

$services=getAll($con,"select * from darlelJobber_services where localUseId='None' || localUseId='$purchasing_id'");
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
$new = (isset($_GET['new'])) ? 1 :0;
$sitePlanId = ($new) ? clear($_GET['new']) : $planDeets['id'];

if(isset($_POST['submitPlan'])){
    $title=clear($_POST['title']);
    $qty_input=$_POST['qty'];
    $service_input=$_POST['service'];
    $top_input=$_POST['top'];
    $left_input=$_POST['left'];
    $type_input=$_POST['type'];
    
    if(isset($_GET['edit'])){
        $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where purchasing_id='$purchasing_id' && planId='$sitePlanId' group by service";
        $siteplans=getAll($con,$query);
        foreach($siteplans as $row){//deleting all previous entries in shop order details which have been affected in site plan
            //deleting previous entry for this service in shop order details
            
            $service=$row['service'];
            $service=clear($service);
            $query="delete from darlelJobber_order_details where purchasing_id='$purchasing_id' && service='$service'";
            
            runQuery($query);
        }
        $query="delete from darlelJobber_site_plan_details where planId='$sitePlanId'";
        runQuery($query);
    }
    $query="update darlelJobber_site_plans set title='$title' where id='$sitePlanId'";
    runQuery($query);

    for($i=0;$i<count($service_input);$i++){
        $qty=$qty_input[$i];
        
        $service=$service_input[$i];
        $service=clear($service);
        $top=$top_input[$i];
        $left=$left_input[$i];
        $type=$type_input[$i];
        
        $random=generateRandomString();
        $query="insert into darlelJobber_site_plan_details set id='$random',planId='$sitePlanId',purchasing_id='$purchasing_id',qty='$qty',type='$type',service='$service',top_pos='$top',left_pos='$left',addedBy='$session_id',timeAdded='$timeAdded'";
        runQuery($query);
    }
    
    //calculating total of each service in all site plans assigned to that shop order
    for($i=0;$i<2;$i++){
        if($i==0){// for teardown
            $type="TD";  
            $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where type='TD' && purchasing_id='$purchasing_id' && planId='$sitePlanId' group by service";
        }
        else{//no teardown
            $type="NT";
            $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where purchasing_id='$purchasing_id' && planId='$sitePlanId' group by service";
        }
        $siteplans=getAll($con,$query);
        foreach($siteplans as $row){
            
            $service=$row['service'];
            if($i==1)//without teardown unit price
                $unit_price=$nameToDeetsServices[$service]['price'];
            else
                $unit_price=$nameToDeetsServices[$service]['tearDownPrice'];
            
            $random=generateRandomString();
            $qty=$row['total_qty'];

            $total=$qty*$unit_price;
            $description=clear($nameToDeetsServices[$service]['description']);
            $image=$nameToDeetsServices[$service]['image'];
            $service=clear($service);
            $query="insert into darlelJobber_order_details set id='$random',sitePlanId='$sitePlanId',purchasing_id='$purchasing_id',service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description',image='$image'";
            runQuery($query);
        }
    }
    header("Location:./purchasing_order.php?purchasing_id=$purchasing_id");
}



if(isset($_FILES['image_saved'])){
    $file=$_FILES['image_saved'];    
	$randomName = generateRandomString();
	$target_dir = "./uploads/";
	$fileName_db = "sitePlan_".$randomName.basename($file["name"]).".png";
	$target_file = $target_dir . "sitePlan_".$randomName.basename($file["name"]).".png";
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
	    $fileName="sitePlan_".$randomName.basename($file["name"]).".png";
	    $timeAdded=time();
	    if($new)
	        $query="insert into darlelJobber_site_plans set id='$sitePlanId',for_id='$purchasing_id',image='$fileName',timeAdded='$timeAdded'";
	    else
            $query="update darlelJobber_site_plans set image='$fileName' where id='$sitePlanId'";
	    runQuery($query);
	}
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
    $price=clear($_POST['price']);
    $short_title=clear($_POST['short_title']);
    $timeAdded=time();
    $id=random();
    
    $query="insert into darlelJobber_services set id='$id',name='$name',short_title='$short_title',type='$type',description='$description',price='$price',timeAdded='$timeAdded',localUseId='$purchasing_id'";
    runQuery($query);
    
    $edit=clear($_GET['edit']);
    header("Location:./create_site_plan.php?edit=$edit&purchasing_id=$purchasing_id");
}
?>

<html lang="en">
<head>
    
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Site Plan Creation</title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
  <script src="painterro-1.2.78.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    
      <script>
  $( function() {
    var availableTags = [
    <?foreach($services as $row){?>
      `<?echo $row['name']." SKU =".$row['sku']?>`,
      <?}?>
    ];
    $( "#tags" ).autocomplete({
      source: function(request, response) {       var words = request.term.split(" ");       var matcher = new RegExp($.map(words, function(word) {         return "(?=.*\\b" + $.ui.autocomplete.escapeRegex(word) + "\\b)";       }).join(""), "i");       var filteredTags = $.grep(availableTags, function(value) {         value = value.label || value.value || value;         return matcher.test(value);       });       if (filteredTags.length === 0) {         filteredTags = $.grep(availableTags, function(value) {           value = value.label || value.value || value;           return value.indexOf(request.term) >= 0;         });       }       response(filteredTags);     }     
    });
  } );
  </script>
    <style>
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
 <form id="myform" method="post" action="" enctype="multipart/form-data">
     <div <?if(!$view){?>id="myPainterro"<?}?> style="position: absolute;height: 1200px;width: 1024px;overflow: auto;">
         <?if($view){?>
            <img src="./uploads/<?echo $planDeets['image'];?>">
         <?}?>
     </div>
     <input type="text" name="actionId" value="<?echo $_GET['edit']?>" hidden>
     <div style="margin: 50px;z-index: 200;position: absolute;">
         <?if(!$view){?>
         <div class="row" style="margin: 50px;">
            
             <div class="col-12">
                 <input value="<?echo $planDeets['title']?>" name="title" type="text" class="form-control" placeholder="Enter Title" style="margin-bottom:10px;">
             </div>
             <div class="col-6">
                 <input id="tags" name="service_input" type="text" class="form-control" placeholder="Enter Service">
             </div>
             <div class="col-6">
                <a class="btn btn-primary text-white" onclick="generateInputBox()">Get Input Box</a>
                <input type="submit" name="submitPlan" class="btn btn-warning text-white" value="Submit">
                <a class="btn btn-warning text-white" href="purchasing_order.php?purchasing_id=<?echo $purchasing_id?>" style="background-color: rgb(247, 185, 36);">
                    Previous Page
                </a>
                <?if(!$new){?>
                <a onclick="showModal()" class="btn btn-warning text-white" style="background-color: rgb(247, 185, 36);">
                    Add Service
                </a>
                <?}?>
             </div>
                <div class="modal fade show" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Service</h5>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <label>Name</label>
                            	<input type="text" name="name" class="form-control" placeholder="Enter Name">
							</div>
							<div class="col-12 mb-4">
                                <label>Short Title</label>
                            	<input type="text" name="short_title" class="form-control" placeholder="Enter Short Title">
							</div>
							<div class="col-12 mb-4">
                                <label>Type</label>
                                <select class="form-control" name="type">
								    <option value="">---Select Item Type---</option>
								    <option selected value="Service">Service</option>
								    <option value="Product">Product</option>
								</select>
							</div>
							<div class="col-12 mb-4">
                                <label>Description</label>
                            	<input type="text" name="description" class="form-control" placeholder="Enter Description">
							</div><div class="col-12 mb-4">
                                <label>Unit Price</label>
                            	<input type="number" name="price" class="form-control" placeholder="Enter Unit Price">
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
         <?}?>
         <div id="inputBoxArea" style="margin: 50px;" class="row">
            <?if(isset($_GET['edit'])){
                $planId=clear($_GET['edit']);
                $sitePlanEntries=getAll($con,"select * from darlelJobber_site_plan_details where planId='$planId'");
                foreach($sitePlanEntries as $row){
                    $random=random();?>  
                <div id="<?echo $row['id']?>" class="<?echo $random?>">
                    <div style="padding:30px;" >
                        <div class="form-group" <?if(!$view){ echo "style='line-height: 0;'";}?> >
                            <?if($view){?>
                            <p><?echo $row['qty']."(".$row['type'].")<br>".$nameToShortTitle[$row['service']]?></p>
                            <?}else{?>
                            <a class="btn btn-danger btn-sm text-white smallBtn" onclick="removeInput('<?echo $row['id']?>')">X</a>
                            <a onclick="updateTearDown('<?echo $random?>')" name="teardown_status[]" class="btn btn-warning btn-sm text-white smallBtn"><?echo $row['type']?></a><br>
                            
                            <input name="qty[]" value="<?echo $row['qty']?>" style="width: 50px;" type="number"><br>
                            <a class="btn btn-primary btn-sm text-white smallBtn"><?echo $nameToShortTitle[$row['service']]?></a>
                            <input name="service[]" value="<?echo htmlspecialchars($row['service'])?>" type="text" hidden>
                            <?}?>
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
            hiddenTools:["crop","pixelize","select","bucket","resize","settings","clear","zoomin","zoomout"],
            defaultTool:'line',
            saveHandler: function (image, done) {
            var formData = new FormData();
            formData.append('image_saved', image.asBlob());
            // you can also pass suggested filename 
            // formData.append('image', image.asBlob(), image.suggestedFileName());
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.onload = xhr.onerror = function () {
              // after saving is done, call done callback
              done(false); //done(true) will hide painterro, done(false) will leave opened
            };
            xhr.send(formData);
        }
        });
        ptro.show(<?echo $image?>);
        <?}?>
        
        var position=1;
        var nameToShortTitle=<?echo json_encode($nameToShortTitle)?>;
     
     function generateInputBox(){
         var service=$("input[name='service_input']").val();
         var id=makeid(5);
         var serviceVal= service.replace(/"/g, '&quot;');
         var string=`
            <div id="`+id+`" class="`+id+`">
                <div style="padding:30px;" >
                    <div class="form-group" style="line-height: 0;">
                        <a class="btn btn-danger btn-sm text-white smallBtn" onclick="removeInput('`+id+`')">X</a>
                        <a onclick="updateTearDown('`+id+`')" name="teardown_status[]" class="btn btn-warning btn-sm text-white smallBtn">NT</a><br>
                            
                        <input name="qty[]" value="0" style="width: 50px;" type="number" ><br>
                        <a class="btn btn-primary btn-sm text-white smallBtn">`+nameToShortTitle[service]+`</a>
                        
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
    function showModal(){
        $('#exampleModal').show();
    } 
    
    function closeModal(){
        $('#exampleModal').hide();
    } 
    $(document).ready(function(){
        $('form').submit(function(event) {
          $(this).find(':submit').css('pointer-events', 'none');
        });
    });
 </script>
 
 
</body>
</html>