<?
require("global.php");

$quoteId=clear($_GET['quoteId']);
$formDeets=getRow($con,"select * from darlelJobber_formA where quoteId='$quoteId'");
/*if($formDeets['submissionStatus']=="Submitted" && (!isset($_GET['view']))){
    header("Location:./payment.php?quoteId=$quoteId");
    exit();
}*/
$timeAdded=time();
$view = (isset($_GET['view'])) ? 1 : 0;

/*if($formDeets['estimatorSign']=="None" && $session_role=="Client")
    header("Location:./viewQuote.php?entryId=$quoteId&m=Waiting on your estimator to complete please check with them and come back to fill Form A Estimate Checklist ");
*/
$questions=[
    [
    "label"=>"Do you have a copy of the property survey?",
    "inputName"=>"surveyCopy",
    "options"=>["Pending","No","Yes"],
    ],
    [
    "label"=>"If yes, client is responsible for application",
    "inputName"=>"inHOA",
    "options"=>["No","Yes"],
    ],
    [
    "label"=>"Are there any HOA setbacks ?",
    "inputName"=>"rulesHOADiscussed",
    "options"=>["No","Yes","No HOA"],
    ],
    [
    "label"=>"Responsibility of fence permit?",
    "inputName"=>"responsibilityFencePermit",
    "options"=>["No Permit","Vilo Fence","Customer"],
    ],
    [
    "label"=>"Responsibility of fence clearing?",
    "inputName"=>"fenceClearing",
    "options"=>["No Clearing","Vilo Fence","Customer"],
    ],
    [
    "label"=>"Is there water on site?",
    "inputName"=>"waterOnSite",
    "options"=>["No","Yes"],
    ],
    [
    "label"=>"Is there electricity on site?",
    "inputName"=>"electricityOnSite",
    "options"=>["No","Yes"],
    ],
    [
    "label"=>"Is pool open or covered?",
    "inputName"=>"poolStatus",
    "options"=>["No Pool","Enclosed","Open"],
    ],
    [
    "label"=>"Are there trees on fence line ? ",
    "inputName"=>"treeAvoidanceDiscussed",
    "options"=>["No","Yes","No Trees"],
    ],
    [
    "label"=>"Are any gates racked?",
    "inputName"=>"gatesRacked",
    "options"=>["No","Yes"],
    ],
    [
    "label"=>"What color of hardware ? ",
    "inputName"=>"hardwareColor",
    "options"=>["Black","White"],
    ],
    [
    "label"=>"Have gate swing and width been discussed ?",
    "inputName"=>"gateSwingDiscussed",
    "options"=>["No","Yes"],
    ],
    [
    "label"=>"Have photos and videos of current site conditions been taken?",
    "inputName"=>"currentPhotosTaken",
    "options"=>["No","Yes"],
    ],
    [
    "label"=>"Is Fence and set back marked with flags or paint ?",
    "inputName"=>"fencedMarkedWith",
    "options"=>["Yes","No"],
    ],
    [
    "label"=>"Have payment options been explained?",
    "inputName"=>"paymentDiscussed",
    "options"=>["No","Yes"],
    ],
];


if((isset($_POST['saveChanges'])) || (isset($_POST['submitForm']))){
    
    
    $formId=clear($_POST['formId']);
    $poolStatus=clear($_POST['poolStatus']);
    $gateSwingDiscussed=clear($_POST['gateSwingDiscussed']);
    $responsibilityFencePermit=clear($_POST['responsibilityFencePermit']);
    $paymentDiscussed=clear($_POST['paymentDiscussed']);
    $currentPhotosTaken=clear($_POST['currentPhotosTaken']);
    $rulesHOADiscussed=clear($_POST['rulesHOADiscussed']);
    $waterOnSite=clear($_POST['waterOnSite']);
    $electricityOnSite=clear($_POST['electricityOnSite']);
    $hardwareColor=clear($_POST['hardwareColor']);
    $inHOA=clear($_POST['inHOA']);
    $treeAvoidanceDiscussed=clear($_POST['treeAvoidanceDiscussed']);
    $fenceClearing=clear($_POST['fenceClearing']);
    $fencedMarkedWith=clear($_POST['fencedMarkedWith']);
    $gatesRacked=clear($_POST['gatesRacked']);
    $surveyCopy=clear($_POST['surveyCopy']);
    
    $query="update darlelJobber_formA set poolStatus='$poolStatus',gateSwingDiscussed='$gateSwingDiscussed',responsibilityFencePermit='$responsibilityFencePermit',
    paymentDiscussed='$paymentDiscussed',hardwareColor='$hardwareColor',currentPhotosTaken='$currentPhotosTaken',rulesHOADiscussed='$rulesHOADiscussed',waterOnSite='$waterOnSite',
    electricityOnSite='$electricityOnSite',inHOA='$inHOA',
    treeAvoidanceDiscussed='$treeAvoidanceDiscussed',fenceClearing='$fenceClearing',fencedMarkedWith='$fencedMarkedWith',gatesRacked='$gatesRacked',
    surveyCopy='$surveyCopy',poolStatus='$poolStatus' where id='$formId'";
    runQuery($query);
    
    
    //clientSign
    $clientSign=$_POST['clientSign'];
    if($clientSign!=""){
        $signature = json_decode($clientSign, true);
        $img = imagecreatetruecolor(600, 280);//assigning width and height of the sign
        $background = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $background);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagesetthickness($img, 5);
        
        foreach ($signature as $line)
            imageline($img, $line['lx'], $line['ly'], $line['mx'], $line['my'], $black);
        
        $clientSign =  random()."_signature.png";
        imagepng($img, "uploads/".$clientSign);
        imagedestroy($img);
    }
    else
        $clientSign = $formDeets['clientSign'];
    
    
    //estimator sign
    $estimatorSign=$_POST['estimatorSign'];
    if($estimatorSign!=""){
        $signature = json_decode($estimatorSign, true);
        $img = imagecreatetruecolor(600, 280);//assigning width and height of the sign
        $background = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $background);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagesetthickness($img, 5);
        
        foreach ($signature as $line)
            imageline($img, $line['lx'], $line['ly'], $line['mx'], $line['my'], $black);
        
        $estimatorSign = random()."_signature.png" ;
        
        imagepng($img, "uploads/".$estimatorSign);
        imagedestroy($img);
    }
    else
        $estimatorSign = $formDeets['estimatorSign'];
    
    $query="update darlelJobber_formA set estimatorSign='$estimatorSign',clientSign='$clientSign' where id='$formId'";
    runQuery($query);
    
    $submitForm = ($clientSign!="" && $clientSign!="None"  && $estimatorSign!="" && $estimatorSign!="None") ? 1 : 0 ;    
    
    if((isset($_POST['submitForm'])) || ($submitForm)){
        $query="update darlelJobber_formA set submissionTime='$timeAdded',submissionStatus='Submitted' where id='$formId'";
        runQuery($query);
        
        $query="
        SELECT q.id from darlelJobber_quotes q inner join darlelJobber_formA A on A.quoteId=q.id inner join
        darlelJobber_formB B on q.id=B.quoteId inner join darlelJobber_formC C on q.id=C.quoteId 
        where A.submissionStatus='Submitted' && B.submissionStatus='Submitted' && C.submissionStatus='Submitted' && q.id='$quoteId'";
        $result=runQueryReturn($query);
        if(mysqli_num_rows($result)==1){
            $query="update darlelJobber_quotes set formStatus='Submitted' where id='$quoteId'";
            runQuery($query);
        }
        header("Location:./payment.php?quoteId=$quoteId");
    }
    header("Location:./formB.php?quoteId=$quoteId");
}


?>
<html lang="en">
	<head>
		<?require("./includes/views/head.php");?>
	</head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> data-kt-aside-minimize="on" id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div class="container-xxl">
							<form method="post" action="" id="myForm">
							    
							    <div class="card card-flush" style="margin-bottom: 40px !important;">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
											    Pre Installation Check List Form A <?if($session_role=="Client"){echo " : Estimator filled the form . Please sign at the bottom";}?>
											</div>
										</div>
										<div class="card-toolbar">
										    <?
										    $setView=($view)? "":"&view=1";
										    $setText=($view)? "Edit ":"View ";
										    if($formDeets['submissionStatus']=="Not Submitted"){?>
										    <!--<a class="btn btn-primary btn-sm" style="margin-right:10px;" href="?quoteId=<?echo $quoteId.$setView?>"><?echo $setText?> Form</a>
										    --><?}?>
										    <a class="btn btn-warning btn-sm" href="./viewQuote.php?entryId=<?echo $quoteId?>">Back To Quote</a>
										</div>
									</div>
									<div class="card-body pt-0">
									        <?foreach ($questions as $i=>$row){
									        $label=$row['label'];
                                            $inputName=$row['inputName'];
                                            $value=$formDeets[$inputName];
                                            $options=$row['options'];
                                            
                                            if(!in_array($value,$options)){
                                                $lastIndex = count($options) - 1;
                                                $value=$options[$lastIndex];
                                            }
                                            ?>
									    <div class="row" style="margin-top:20px;">
									        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 col-xl-6 col-6">
								                <p>
								                    <?echo ($i+1).". ".$label;?>
                                                </p>
								            </div>
								            <div class="col-xs-6 col-sm-6 col-md-6 col-xl-6 col-lg-6 col-6" style="text-align:right;">
                                                <div style="display: flex; flex-wrap: wrap; justify-content: center;">
                                                    <?foreach($options as $nrow){?>
                                                        <div style="margin-right: 10px;">
                                                        <input class="form-check-input" type="radio" value="<?echo $nrow?>" name="<?echo $inputName?>"  <?if($value==$nrow){echo "checked";}?>   />
                                                        <label class="form-check-label" >
                                                            <?echo $nrow?>
                                                        </label>
                                                    </div>
                                                    <?}?>
                                                </div>
                                            </div>
                                        </div>
                                        <hr style="height: 3px;">
                                        
                                        <?}?>
                                        
                                        
                                        
									</div>
									
									<div class="card-footer">
    									<div class="row">
    									    
    									    <div class="col-md-6 col-xl-6 col-lg-6 col-sm-12 col-12 text-center">
    									        <?if(!$view && $session_role!="Client"){?>
    									        <div class="sigPad" id="estimatorSign" style="width:100%;">
                                                    <ul class="sigNav">
                                                        <li class="clearButton btn">
                                                            <b style="margin-right: 30px;">Estimator Sign</b>
                                                            <a class="btn btn-primary btn-sm" href="#clear">Clear</a>
                                                        </li>
                                                    </ul>
                                                    
                                                    <div class="sig sigWrapper" style="height:auto;">
                                                        <div class="typed"></div>
                                                        <canvas class="pad"  height="160" style="border: 1px solid #ccc !important;"></canvas>
                                                        <input type="hidden" name="estimatorSign" class="output">
                                                    </div>
                                                </div>
                                                <?}?>
                                                
                                                <?if($view || (!$view && $session_role=="Client")){?>
                                                    <b>Estimator Sign</b><br>
                                                    <img style="height: 130px;" src="./uploads/<?echo $formDeets['estimatorSign']?>" onerror="this.style.display='none'">
    									        <?}?>
    									    </div>
    									    <div class="col-md-6 col-xl-6 col-lg-6 col-sm-12 col-12 text-center">
    									        <?if(!$view){?>
    									        <div class="sigPad" id="clientSign" style="width:100%;">
                                                    <ul class="sigNav">
                                                        <li class="clearButton btn">
                                                            <b style="margin-right: 30px;">Client Sign</b>
                                                            <a class="btn btn-primary btn-sm" href="#clear">Clear</a>
                                                        </li>
                                                    </ul>
                                                    
                                                    <div class="sig sigWrapper" style="height:auto;">
                                                        <div class="typed"></div>
                                                        <canvas class="pad"  height="160" style="border: 1px solid #ccc !important;"></canvas>
                                                        <input type="hidden" name="clientSign" class="output">
                                                    </div>
                                                </div>
                                                <?}?>
                                                
                                                
                                                <?if($view){?>
                                                    <b>Client Sign</b><br>
                                                    <img style="height: 130px;" src="./uploads/<?echo $formDeets['clientSign']?>" onerror="this.style.display='none'">
    									        <?}?>
    									    </div>
    									</div>
									</div>
									
									<?if(!$view){?>
                                    <hr>
									<div class="card-footer">
                                        <div class="text-center">
                                            
                                            <input name="formId" value="<?echo $formDeets['id']?>" type="text" hidden>
                                            <?if($session_role!="Client"){?>
                                            <input type="submit" name="saveChanges" class="btn btn-primary btn-sm" value="Save Changes">
                                            <?}if($session_role=="Client"){?>
                                            <input id="submitForm" type="submit" name="submitForm" class="btn btn-warning btn-sm" value="Submit Form">
                                            <?}?>
                                        </div>
                                    </div>
                                    <?}?>
									
								</div>
								</form>   
							</div>
						</div>
					</div>
					<?require("./includes/views/footer.php");?>
				</div>
			</div>
			<?require("./includes/views/footerjs.php");?>
	    </div>
	</body>
	
	
	<!--signature pad-->
    <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/assets/numeric-1.2.6.min.js"></script> 
    <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/assets/bezier.js"></script> 
    <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/jquery.signaturepad.js"></script>
    <!--signature pad-->
	
	<script>
	    $(document).ready(function() {
	        
	        <?if($view || $session_role=="Client"){?>
            var radios = document.querySelectorAll('input[type="radio"]');
            for (var i = 0; i < radios.length; i++) {
                radios[i].addEventListener('click', function(event) {
                    event.preventDefault();
                });
            }
	        <?}?>
	        
	        
	        
            $('#estimatorSign').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:200,validateFields :false});
            $('#clientSign').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:200,validateFields :false});
          
        });
        
        <?if($session_role=="Client"){?>
        var yourForm = $('#myForm');
        var submitButton = $('#submitForm');
        
        yourForm.on('submit', function (event) {
            var clientSign=$("input[name='clientSign']").val();
            if(clientSign==null || clientSign==""){
                alert("Kindly sign before submitting the form");
                event.preventDefault();
                location.reload();
            }
        });
        <?}?>
    </script>
	
</html>