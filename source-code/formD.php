<?
require("global.php");

$quoteId=clear($_GET['quoteId']);

$formAStatus=getRow($con,"select * from darlelJobber_formA where quoteId='$quoteId'")['submissionStatus'];
$formBStatus=getRow($con,"select * from darlelJobber_formB where quoteId='$quoteId'")['submissionStatus'];

if($formAStatus=="Not Submitted"){
    header("Location:./formA.php?quoteId=$quoteId");
    exit();
}
if($formBStatus=="Not Submitted"){
    header("Location:./formB.php?quoteId=$quoteId");
    exit();
}


$formDeets=getRow($con,"select * from darlelJobber_formD where quoteId='$quoteId'");
if($formDeets['submissionStatus']=="Submitted" && (!isset($_GET['view']))){
    header("Location:./payment.php?quoteId=$quoteId");
    exit();
}
$timeAdded=time();
$view = (isset($_GET['view'])) ? 1 : 0;

if(isset($_POST['submitForm'])){
    
    $formId=clear($_POST['formId']);
    $sprinklerAssurance=clear($_POST['sprinklerAssurance']);
    
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
    
    $acceptStatus = ($sprinklerAssurance=="Yes") ? "Accepted" : "Rejected";
    $query="update darlelJobber_formD set clientSign='$clientSign',submissionStatus='Submitted',sprinklerAssurance='$sprinklerAssurance',acceptStatus='$acceptStatus' where id='$formId'";
    runQuery($query);
    
    $query="update darlelJobber_quotes set formDStatus='Submitted' where id='$quoteId'";
    runQuery($query);
    
    if($acceptStatus=="Accepted"){
        $random=random();
        $query="insert into darlelJobber_quote_details set id='$random',quoteId='$quoteId',service='Sprinkler Assurance Plan',qty='1',unit_price='55',total='55',description='Sprinkler Assurance Plan',
        type='NT'";
        runQuery($query);
        updateQuote($quoteId);
    }
    
    header("Location:./payment.php?quoteId=$quoteId");
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
											    Sprinkler Protection Plan
											</div>
										</div>
										<div class="card-toolbar">
									        <?$setView=($view)? "":"&view=1";
										    $setText=($view)? "Edit ":"View ";
										    if($formDeets['acceptStatus']=="Not Accepted"){?>
										    <!--<a class="btn btn-primary btn-sm" style="margin-right:10px;" href="?quoteId=<?echo $quoteId.$setView?>"><?echo $setText?> Form</a>
										    --><?}?>
										    <a class="btn btn-warning btn-sm" href="./viewQuote.php?entryId=<?echo $quoteId?>">Back To Quote</a>
										</div>
									</div>
									<div class="card-body pt-0">
									    <div class="row" style="margin-top:20px;">
									        <div class="col-12">
								                <p>
								                    It is Vilo Fence's objective to deliver top-quality service and products at the most reasonable cost,
								                    ensuring that your fence project runs smoothly. As such, we offer you the option to purchase a Sprinkler Protection Plan.
								                    <br><br>
                                                    We understand that, while we are not liable for damage to private lines such as sprinklers (as stated in The Contract), such damage cannot always be avoided.
                                                    <br><br>
                                                    This plan is entirely optional. If you decide to purchase it, a Non-Refundable fee of $55 will be added to the price of your fence proposal.
                                                    <br><br>
                                                    The plan guarantees that, in the event of damage to a sprinkler during fence installation, 
                                                    Vilo Fence will be responsible for repairing the broken line. All claims must be made within 30 days of fence installation.
                                                    <br><br>
                                                    <b>Please select one of the following options:</b>
                                                </p>
								            </div>
								            
								            
								            
								            <div class="col-12" style="margin-bottom:20px;">
							                    <input class="form-check-input" type="radio" value="Yes" name="sprinklerAssurance" <?if($formDeets['sprinklerAssurance']=="Yes"){echo "checked";}?> style="margin-right: 10px;" />
                                                I choose to purchase the Sprinkler Assurance Plan. I understand that the $55 fee is Non-Refundable and
							                    that if any sprinklers are damaged during fence installation, Vilo Fence will repair them. I understand
							                    that the Sprinkler Assurance Plan only covers breaks and not moving perfectly good lines during or after
							                    installation is finished. The customer is solely responsible for the process of moving perfectly good lines with no breaks.
							                    The customer and Vilo Fence representative have discussed this plan in detail, inspected the irrigation system fully,
							                    and verified that all zones are in working order
                                            </div>
								            
								            <div class="col-12">
								                <input class="form-check-input" type="radio" value="No" name="sprinklerAssurance" <?if($formDeets['sprinklerAssurance']=="No"){echo "checked";}?> style="margin-right: 10px;" />
                                                I decline the Sprinkler Assurance Plan and assume full responsibility for repairing any damaged sprinkler
								                lines resulting from fence installation with Vilo Fence. I understand that the Sprinkler Assurance Plan
								                cannot be purchased after the contract has been executed.
                                            </div>
								            
								        </div>
                                    </div>
                                    
                                    <div class="card-footer">
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
                                            <input id="submitForm" onclick="checkInputs(event)" type="submit" name="submitForm" class="btn btn-warning btn-sm" value="Submit Form">
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
        <?if($view){?>
        var radios = document.querySelectorAll('input[type="radio"]');
        for (var i = 0; i < radios.length; i++) {
            radios[i].disabled = true;
        }
        <?}?>
        
          $('#clientSign').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:200});
        });
        
        
	    function checkInputs(event){
	        
	        var allRadioInputs = $("input[type='radio']");
            var groups = {}; // object to store group names as keys and their checked status as values
            
            var allGroupsChecked = true;
            $('input[type="radio"]').each(function(index, item) {
              var name = $(this).attr('name');
              if(!$('input[name="' + name + '"]:checked').length) {
                allGroupsChecked = false;
                return false;
              }
            });
	        
	        if (!allGroupsChecked) {
              alert("Kindly select an option for sprinkler assurance");
              event.preventDefault();
            }
	    }
	    
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