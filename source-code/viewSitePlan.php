<?
require("./global.php");

$quoteId=$_GET['quoteId'];
$purchasing_id=$_GET['purchasing_id'];
$purchasing=0;
$quoting=0;
if(isset($_GET['quoteId'])){
    $quoting=1;
    $extraQuery=" quoteId='$quoteId' ";
    $previousPage="./createQuote.php?entryId=$quoteId";
    $tableName="darlelJobber_quote_details";
}
else{
    $purchasing=1;
    $extraQuery=" purchasing_id='$purchasing_id' ";
    $previousPage="./purchasing_order.php?purchasing_id=$purchasing_id";
    $tableName="darlelJobber_order_details";
}

if($quoting)
    $localUseId=$quoteId;
else
    $localUseId=$purchasing_id;
    
$query="select * from darlelJobber_services where localUseId='None' || localUseId='$localUseId'";
$services=getAll($con,$query);
$nameToDeetsServices=array("");
foreach($services as $row)
{$nameToDeetsServices[$row['name']]=$row;}

$timeAdded=time();
$view=0;
if(isset($_GET['view']))
    $view=1;    
if((!isset($_GET['quoteId'])) && (!isset($_GET['purchasing_id'])))
    header("Location:./home.php");

if(isset($_GET['edit'])){
    $id=$_GET['edit'];
    $planDeets=getRow($con,"select * from darlelJobber_site_plans where id='$id'");
}
if(isset($_GET['new']))
    $sitePlanId=$_GET['new'];
else
    $sitePlanId=$planDeets['id'];

if(isset($_POST['submitPlan'])){
    $title=mb_htmlentities($_POST['title']);
    $qty_input=$_POST['qty'];
    $service_input=$_POST['service'];
    $top_input=$_POST['top'];
    $left_input=$_POST['left'];
    $type_input=$_POST['type'];
    
    if(isset($_GET['edit'])){
        $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where $extraQuery && planId='$sitePlanId' group by service";
        echo $query."<br>";
        $siteplans=getAll($con,$query);
        foreach($siteplans as $row){//deleting all previous entries in quote details which have been affected in site plan
            //deleting previous entry for this service in quote details
            
            $service=$row['service'];
            $service=mb_htmlentities($service);
            $query="delete from $tableName where $extraQuery && service='$service'";
            echo $query."<br>";
            
            $result=$con->query($query);
            if(!$result){
                echo $con->error;
                exit();
            }
        }
        $query="delete from darlelJobber_site_plan_details where planId='$sitePlanId'";
        $result=$con->query($query);
        echo $query."<br>";
        if(!$result){
            echo $con->error;
            exit();
        }
    }
    $query="update darlelJobber_site_plans set title='$title' where id='$sitePlanId'";
    $result=$con->query($query);
    if(!$result){
        echo $con->error;
        exit();
    }

    for($i=0;$i<count($service_input);$i++){
        $qty=$qty_input[$i];
        
        $service=$service_input[$i];
        $service=mb_htmlentities($service);
        $top=$top_input[$i];
        $left=$left_input[$i];
        $type=$type_input[$i];
        
        $random=generateRandomString();
        $query="insert into darlelJobber_site_plan_details set id='$random',planId='$sitePlanId',$extraQuery,qty='$qty',type='$type',service='$service',top_pos='$top',left_pos='$left',addedBy='$session_id',timeAdded='$timeAdded'";
        //echo $query;
        $result=$con->query($query);
        if(!$result){
            echo $con->error;
            exit();
        }
    }
    
    //calculating total of each service in all site plans assigned to that quote
    for($i=0;$i<2;$i++){
        if($i==0){// for teardown
            $type="TD";  
            $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where type='TD' && $extraQuery && planId='$sitePlanId' group by service";
        }
        else{//no teardown
            $type="NT";
            $query="select *,sum(qty) as total_qty from darlelJobber_site_plan_details where $extraQuery && planId='$sitePlanId' group by service";
        }
        $siteplans=getAll($con,$query);
        foreach($siteplans as $row){
            
            $service=$row['service'];
            if($i==1)//without teardown unit price
                $unit_price=$nameToDeetsServices[$service]['price'];
            else
                $unit_price=2.5;
            $random=generateRandomString();
            $qty=$row['total_qty'];
            $total=$qty*$unit_price;
            $description=mb_htmlentities($nameToDeetsServices[$service]['description']);
            $image=$nameToDeetsServices[$service]['image'];
            $service=mb_htmlentities($service);
            $query="insert into $tableName set id='$random',sitePlanId='$sitePlanId',$extraQuery,service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description',image='$image'";
            $result=$con->query($query);
            if(!$result){
                echo "in here<br>".$con->error;
                exit();
            }
        }
    }
    if($purchasing)
        header("Location:./purchasing_order.php?purchasing_id=$purchasing_id");
    else
        header("Location:./createQuote.php?entryId=$quoteId");
}



if(isset($_FILES['image_saved'])){
    $file=$_FILES['image_saved'];    
	$randomName = generateRandomString();
	$target_dir = "./uploads/";
	$fileName_db = "sitePlan_".$randomName.basename($file["name"]).".png";
	$target_file = $target_dir . "sitePlan_".$randomName.basename($file["name"]).".png";
	
	if($purchasing)
	    $for_id=$purchasing_id;
	else
	    $for_id=$quoteId;
	
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
	    $fileName="sitePlan_".$randomName.basename($file["name"]).".png";
	    echo $fileName."<br>";
	    $timeAdded=time();
	    if(isset($_GET['new']))
	        $query="insert into darlelJobber_site_plans set id='$sitePlanId',for_id='$for_id',image='$fileName',timeAdded='$timeAdded'";
	    else
            $query="update darlelJobber_site_plans set image='$fileName' where id='$sitePlanId'";
	    $result=$con->query($query);
	}
	else
        echo "failed";
}

$nameToShortTitle=array("");
foreach($services as $row)
{$nameToShortTitle[$row['name']]=$row['short_title'];}

$image="";
if($planDeets['image']!=""){
    $image="./uploads/".$planDeets['image'];
    $image='"'.$image.'"';
}

if(isset($_POST['addService'])){
    $name=mb_htmlentities($_POST['name']);
    $type=mb_htmlentities($_POST['type']);
    $description=mb_htmlentities($_POST['description']);
    $price=mb_htmlentities($_POST['price']);
    $short_title=mb_htmlentities($_POST['short_title']);
    $timeAdded=time();
    $id=generateRandomString();
    
    if($quoting)
        $localUseId=$quoteId;
    else
        $localUseId=$purchasing_id;
        
    $query="insert into darlelJobber_services set id='$id',name='$name',short_title='$short_title',type='$type',description='$description',price='$price',timeAdded='$timeAdded',localUseId='$localUseId'";
    $result=$con->query($query);

    $edit=$_GET['edit'];
    if($quoting)
        header("Location:./create_site_plan.php?edit=$edit&quoteId=$quoteId");
    else
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
      `<?echo $row['name']?>`,
      <?}?>
    ];
    $( "#tags" ).autocomplete({
      source: availableTags
    });
  } );
  </script>
    <style>
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
    <script src="./touchPunch.js"></script>
    
    <!--
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>-->

</head>
<body><!--style="position: absolute;height: 850px;width: 10000px;overflow: auto;"-->
 <form id="myform" method="post" action="" enctype="multipart/form-data">
     <div <?if(!$view){?>id="myPainterro"<?}?> style="position: absolute;height: 850px;width: 1300px;overflow: auto;">
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
                <a class="btn btn-warning text-white" href="<?echo $previousPage?>" style="background-color: rgb(247, 185, 36);">
                    Previous Page
                </a>
                <?if(!isset($_GET['new'])){?>
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
                $planId=$_GET['edit'];
                $sitePlanEntries=getAll($con,"select * from darlelJobber_site_plan_details where planId='$planId'");
                foreach($sitePlanEntries as $row){
                    $random=generateRandomString();
            ?>  
                <div id="<?echo $row['id']?>" class="<?echo $random?>">
                    <div style="padding:30px;" >
                        <div class="form-group">
                            <?if($view){?>
                            <b style="font-size: 13px;"><?echo "Qty : ".$row['qty']." ( ".$row['type']." )<br>".$nameToShortTitle[$row['service']]?></b>
                            <?}else{?>
                            <input name="qty[]" value="<?echo $row['qty']?>" style="width: 100px;" type="number" class="form-control">
                            <input name="service[]" value="<?echo htmlspecialchars($row['service'])?>" type="text" hidden>
                            <a class="btn btn-danger btn-sm text-white" onclick="removeInput('<?echo $row['id']?>')">X</a>
                            <a onclick="updateTearDown('<?echo $random?>')" name="teardown_status[]" class="btn btn-warning btn-sm text-white"><?echo $row['type']?></a>
                            <a class="btn btn-primary btn-sm text-white"><?echo $nameToShortTitle[$row['service']]?></a>
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
     $(document).ready(function(){
      <?if($view){?>
      /*$('body').css('zoom','30%'); 
      $('body').css('zoom','0.3'); 
      $('body').css('-moz-transform',scale(0.3, 0.3));
      */<?}?>
    });
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
 </script>
 <script>
    
 
    <?if($view){?>
    setTimeout( function() {
        $('.ptro-bar').hide(); 
    }, 100);
    <?}?>
    
    function removeInput(id)
    {
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
         Painterro({
            id:'myPainterro',
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
        }).show(<?echo $image?>);
        <?}?>
         
         /*var ptro = Painterro({
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
        <?if(isset($_GET['edit'])){?>
            ptro.show("./uploads/<?echo $planDeets['image']?>");
        <?}else{?>
            ptro.show();
        <?}?>*/
        
        
        var position=1;
        var nameToShortTitle=<?echo json_encode($nameToShortTitle)?>;
     
     function generateInputBox()
     {
         var service=$("input[name='service_input']").val();
         var id=makeid(5);
         var serviceVal= service.replace(/"/g, '&quot;');
         var string=`
            <div id="`+id+`" class="`+id+`">
                <div style="padding:30px;" >
                    <div class="form-group">
                        <input name="qty[]" value="0" style="width: 100px;" type="number" class="form-control">
                        <input name="type[]" value="NT" type="text" hidden>
                        <input name="service[]" value="`+serviceVal+`" type="text" hidden>
                        <input name="top[]" value="`+position+`" type="text" hidden>
                        <input name="left[]" value="`+position+`" type="text" hidden>
                        <a class="btn btn-danger btn-sm text-white" onclick="removeInput('`+id+`')">X</a>
                        <a onclick="updateTearDown('`+id+`')" name="teardown_status[]" class="btn btn-warning btn-sm text-white">NT</a>
                        <a class="btn btn-primary btn-sm text-white">`+nameToShortTitle[service]+`</a>
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
     function updateTearDown (divId)
     {
         var teardown_status = $("."+divId+" a[name='teardown_status[]']").text();
         if(teardown_status=="NT")
         {
             $("."+divId+" a[name='teardown_status[]']").text("TD");
             $("."+divId+" input[name='type[]']").val("TD");
         }
         else
         {
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
    
 </script>
 
 
</body>
</html>