<?
require("global.php");

$quoteId=clear($_GET['quoteId']);
$formDeets=getRow($con,"select * from darlelJobber_formB where quoteId='$quoteId'");
/*if($formDeets['submissionStatus']=="Submitted" && (!isset($_GET['view']))){
    header("Location:./payment.php?quoteId=$quoteId");
    exit();
}*/
$timeAdded=time();
$view= (isset($_GET['view'])) ? 1 : 0;

if($formDeets['fenceOption']=="None" && $session_role=="Client")
    header("Location:./viewQuote.php?entryId=$quoteId&m=Waiting on your estimator to complete please check with them and come back to fill Form B Fence Installation Option ");

$questions=[
    [
    "label"=>"Racked Fences: Racking method is used to match the fence to its slope. This means adjusting the fence's rails so that it matches the slope beneath it while the pickets and posts remain vertical. This type of installation is a common choice for pets 
    and small children but may require small gap coverage with dirt and grass seed or small landscape vegetation due to slight changes in grade.",
    "inputValue"=>"rackedFence",
    "image"=>"Dariel RF.jpg",
    "options"=>["Yes"],
    ],
    [
    "label"=>"Straight Top Fence: For flat yards, Straight Top Fence installation is a common option. This installation results in a straight line across the top and bottom of the fence, 
    with a small gap underneath. It is also possible to address the small gaps by placing dirt
    and grass seed or small landscape vegetation in those areas",
    "inputValue"=>"straightTop1",
    "image"=>"Dariel ST.jpg",
    "options"=>["Yes"],
    ],
    [
    "label"=>"Level Top Fence on Slope: Level Top Fence on Slope is another option for steep slopes, but it may result in large uncovered openings at the bottom of the fence. Therefore, 
    if you need a fully enclosed fence, your options are limited, 
    and it may incur additional costs. It is crucial to check 
    local code restrictions because the excessive height and spacing changes may apply in some areas of the fence.",
    "inputValue"=>"levelTop",
    "image"=>"Dariel SFoS.2.jpg",
    "options"=>["Yes"],
    ],
    [
    "label"=>"Contoured Fences: A contoured fence is a common choice that follows the lines and contours of the property precisely, providing a parallel line to the 
    ground at all points. This type of installation is 
    suitable for pets and small children, but may need to be addressed with small gaps due to slight changes in grade.",
    "inputValue"=>"contouredFence",
    "image"=>"Dariel CF.jpg",
    "options"=>["Yes"],
    ],
];


if(isset($_POST['submitForm']) || (isset($_POST['confirm']))){
    $fenceOption=$_POST['fenceOption'];
    $formId=clear($_POST['formId']);
    
    
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
        
        $query="update darlelJobber_formB set clientSign='$clientSign' where id='$formId'";
        runQuery($query);
    }
    
    $formSubmitted=0;
    if($session_role=="Client" || (isset($_POST['confirm']))){
        $formSubmitted=1;
        $query="update darlelJobber_formB set fenceOption='$fenceOption',submissionStatus='Submitted' where id='$formId'";
    }
    else{
        $query="update darlelJobber_formB set fenceOption='$fenceOption' where id='$formId'";
    }
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
    if($formSubmitted)
        header("Location:./payment.php?quoteId=$quoteId");
    else
        header("Location:./createQuote.php?entryId=$quoteId&m=Form B has been saved successfully");
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
							   <form id="myForm" method="post" action="">
							    <div class="card card-flush" style="margin-bottom: 40px !important;">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
											    Fence Panel Layout Form B <?if($session_role=="Client"){echo " : Estimator filled out the form . Contact the estimator if you have any questions 
											    . Review and click submit at the bottom";}?>
											</div>
										</div>
										<div class="card-toolbar">
									        <?$setView=($view)? "":"&view=1";
										    $setText=($view)? "Edit ":"View ";
										    if($formDeets['submissionStatus']=="Not Submitted" && $session_role!="Client"){?>
										    <!--<a class="btn btn-primary btn-sm" style="margin-right:10px;" href="?quoteId=<?echo $quoteId.$setView?>"><?echo $setText?> Form</a>
										    --><?}?>
										    <a class="btn btn-warning btn-sm" href="./viewQuote.php?entryId=<?echo $quoteId?>">Back To Quote</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="row">
									        <div class="col-12">
									            <p>Grading Options: There are different options available for fencing installation depending on the grade of your property.
									            Please select your preferred installation method</p>
									        </div>
									    </div>
									        <?foreach ($questions as $i=>$row){
									        $label=$row['label'];
                                            $inputValue=$row['inputValue'];
                                            $options=$row['options'];
                                            $image=$row['image'];
                                            $styleTag= ($formDeets['fenceOption']==$inputValue) ? "margin-top:20px;border-style: solid;padding: 10px;border-color: #009ef7;border-width: 10px;":"margin-top:20px";
							                ?>
									    <div class="row" style="<?echo $styleTag;?>">
									        <div class="col-md-7 col-lg-9 col-xl-9 col-12">
								                <p>
								                    <?echo ($i+1).". ".$label;?>
                                                </p>
								            </div>
								            <div class="col-md-4 col-lg-2 col-xl-2 col-6 text-center">
								                <a class="example-image-link" href="./assets/<?echo $image?>" data-fslightbox="lightbox-basic" data-lightbox="example-1">
							                        <img style="width:200px;" src="./assets/<?echo $image?>" onerror="this.style.display='none'">
								                </a>
								            </div>
								            <div class="col-md-1 col-xl-1 col-lg-1 col-6" style="text-align:right;">
                                                <?foreach($options as $nrow){?>
                                                <input class="form-check-input" type="radio" value="<?echo $inputValue?>" <?if($formDeets['fenceOption']==$inputValue){echo "checked";}?> name="fenceOption"/>
                                                <label class="form-check-label" style="margin-right: 10px;" >
                                                    <?echo $nrow?>
                                                </label>
                                                <?}?>
                                            </div>
                                        </div>
                                        <?}?>
									</div>
									
									<div class="row">
									    <div class="col-12 text-center">
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
                                            <?}else if ($view){?>
                                                <b>Estimator Sign</b><br>
                                                <img style="height: 130px;" src="./uploads/<?echo $formDeets['clientSign']?>" onerror="this.style.display='none'">
									        <?}?>
									    </div>
									</div>
									
									<?if(!$view){?>
									<hr>
									<div class="card-footer">
                                        <div class="text-center">
                                            <input name="formId" value="<?echo $formDeets['id']?>" type="text" hidden>
                                            <input type="submit" name="confirm" class="btn btn-primary btn-sm" value="Confirm">
                                            <?if($session_role!="Client"){?>
                                            <input id="submitForm" type="submit" name="submitForm" class="btn btn-primary btn-sm" value="Submit Form">
                                            <?}?>
                                        </div>
                                    </div>
                                    <?}?>
								</div>
							</div>
						</div>
						</form>
					</div>
					<?require("./includes/views/footer.php");?>
				</div>
			</div>
			
			<script src="assets/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
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
	        $('#clientSign').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:200<?if($session_role!="Client"){?>,validateFields :false<?}?>});
        
	        <?if($view || $session_role=="Client"){?>
            var radios = document.querySelectorAll('input[type="radio"]');
            for (var i = 0; i < radios.length; i++) {
                radios[i].addEventListener('click', function(event) {
                    event.preventDefault();
                });
            }
	        <?}?>
	    });
	    
        var yourForm = $('#myForm');
        var submitButton = $('#submitForm');
        
        yourForm.on('submit', function (event) {
            var clickedButton = event.originalEvent.submitter.name;
            
            var clientSign=$("input[name='clientSign']").val();
            if((clientSign==null || clientSign=="") && (clickedButton=="confirm")){
                alert("Kindly sign before confiriming the submission");
                event.preventDefault();
                location.reload();
            }
        });
        
    </script>
</html>