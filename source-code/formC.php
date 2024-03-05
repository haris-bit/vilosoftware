<?
require("global.php");
$quoteId=clear($_GET['quoteId']);
$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
$formDeets=getRow($con,"select * from darlelJobber_formC where quoteId='$quoteId'");
if(($formDeets['submissionStatus']=="Submitted") && ($quoteDeets['approveStatus']=="Approved") && (!isset($_GET['view']))){
    header("Location:./payment.php?quoteId=$quoteId");
    exit();
}
$timeAdded=time();
$view = (isset($_GET['view'])) ? 1 : 0;

//marking all the remaining as not accepted then marking those whose is in this array as accepted optional line items
if(isset($_GET['approveOptional'])){
    $approveOptional=$_GET['approveOptional'];
    runQuery("update darlelJobber_quote_details set optionalApproveStatus='No' where quoteId='$quoteId'");
    
    $approveOptionalArr = implode(",", array_map(function($id) {
        return "'" . $id . "'";
    }, $approveOptional));
    
    if($approveOptionalArr!=""){
        runQuery("update darlelJobber_quote_details set optionalApproveStatus='Yes' where quoteId='$quoteId' && id in ($approveOptionalArr)");
        updateQuote($quoteId);
    }
}

$questions=[
    [
    "label"=>"Verbal Commitments: At Vilo Fence, we follow the 4-point rule, meaning that only items outlined in the contract are valid. Verbal commitments are not contractual and will not be handled as such. If your estimator has promised you something that is not on the contract,
    it will not be completed. To ensure that all items discussed are on the contract and completed correctly, please verify their inclusion before signing.",
    ],
    [
    "label"=>"Underground Utilities/Sprinklers: Vilo Fence's team contacts the required locate service to detect underground lines, including gas, cable, electric, and phone lines installed by utility companies. These lines will be notated using paint and flags. However, please note that any lines not installed by these companies will not be detected. Additionally,
    locate companies do not mark water lines that run from the meter to the house. 
    For this reason, Vilo Fence is not responsible for these items.",
    ],
    [
    "label"=>"Homeowner's Association: We provide assistance in filling out
    the HOA fence application and will supply the necessary documentation 
    (including a marked property survey, photos of the future fence, and a copy of the contract with the fence description)
    . However, please note that you are solely responsible for submitting the application and 
    providing Vilo Fence with the HOA decision/approval letter once you receive it.",
    ],
    [
    "label"=>"Payment: Vilo Fence requests 40% down payment on every fence project. 
    We accept payment in the form of cash, check, or credit card. Please note that all major credit cards are accepted, in addition E-Check, 
    check, and cash have an additional 3% discount added. Final payment is due upon the completion of the fence project and must be made within 38 hours. 
    Please sign the completion form and provide the final payment to the foreman at the job site upon the completion of the fence.",
    ],
    [
    "label"=>"Financing: Vilo Fence provides financing options for your fence project. To apply, visit our website and navigate to the finance tab. If these items are not provided before installation, 
    your project will be postponed until received. At the completion of your fence project, you will be asked to sign a completion form stating the fence project is complete and to your satisfaction.",
    ],
    [
    "label"=>"Install Date: Once all the necessary paperwork for your fence project is processed, you will receive a phone call with an install date. The required items include a signed contract, a deposit, any permit paperwork needed, the permit, HOA approval, this signed checklist, and a copy of your property survey. 
    You don't have to be present for the installation; however, please be available by phone if we need to communicate with you. Please allow us a 2-3 day installation window, weather permitting",
    ],
    [
    "label"=>"Property Survey: To ensure that the fence is installed on the correct property lines, a property survey is necessary for every fence project.
    If you do not have a property survey, we can provide one for you at an additional cost. If only property pins need to be located, we can also provide that service for a fee.",
    ],
    [
    "label"=>"Permits: If your project requires a permit, Vilo Fence can obtain the necessary permit for you. 
    However, some items and processes are needed to complete the permit process. First, we will need a copy of your property survey. Secondly, if your project is 
    $2,500.00 or greater, a notarized notice of commencement will be required. Once the permit is obtained, we will post it on the job site and call in an inspection upon completion of the fence project",
    ],
    [
    "label"=>"Pool Code: Vilo Fence is knowledgeable about the requirements for pool
    fencing and gates to meet pool code. Our team will ensure that the gates swing out and have self-closing hinges and that the latches",
    ],
    [
    "label"=>"Additional Materials: In anticipation of changes or unforeseen circumstances,
    Vilo Fence may send additional materials to the job site. Once the fence installation is completed,
    any leftover materials belong to Vilo Fence.",
    ],
    [
    "label"=>"Trees/Bushes/Hedges/Plants: Ultimately, it is the customer's responsibility to clear the fence line. Vilo Fence can trim some small items such as small bushes, hedges, and plants at a rate depending on amount of cleaning needed.
    The site is considered a construction site. While we will make every effort to not disturb any existing flowers or bushes, we cannot guarantee that no damage will occur.",
    ],
    
    
    [
    "label"=>"Personal Information: Vilo Fence does not sell any personal information to outside sources, 
    but we may ask for multiple means of contact with each customer to ensure proper communication throughout the fence installation process.",
    ],
    [
    "label"=>"Property Lines: It is Vilo Fence's policy to install fences to the property line.
    We do not install any fence off the property line unless there is written permission given from the encroached property.",
    ],
    [
    "label"=>"At Vilo Fence, we know that there may be occasions where modifications are required before fence installation. 
    To ensure there is sufficient time for the necessary adjustments to be made, we kindly ask that any changes are communicated at least 7 business
    days before installation. Changes made after this deadline will be subject to a fee of $250.00, in addition to the costs associated with any material or labor modifications.",
    ],
];





if(isset($_POST['submitForm'])){
    
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
    }
    else
        $clientSign = $formDeets['clientSign'];
    
    $query="update darlelJobber_formC set clientSign='$clientSign',submissionStatus='Submitted' where id='$formId'";
    runQuery($query);
    
    //approving quote
    /*if($quoteDeets['approveStatus']=="Pending Changes"){
        $jobId=$quoteDeets['jobId'];
        $invoiceId=$quoteDeets['invoiceId'];
        
        //update the respective line items of invoice and job for which this quote was created
        $quoteLineItems=getAll($con,"select *,sum(qty) as 'totalQty' from darlelJobber_quote_details where quoteId='$quoteId' group by service,type");
        foreach($quoteLineItems as $row){
            $service=clear($row['service']);
            $qty=clear($row['qty']);
            $unit_price=clear($row['unit_price']);
            $total=clear($row['total']);
            $description=clear($row['description']);
            $image=clear($row['image']);
            $type=clear($row['type']);
            
            $randomA=generateRandomString();
            $randomB=generateRandomString();
            
            //update respective job by first deleting the affected service then re inserting in job details
            $query="delete from darlelJobber_job_details where service='$service' && jobId='$jobId'";
            runQuery($query);
            
            $query="insert into darlelJobber_job_details set id='$randomA',jobId='$jobId',service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description',image='$image'";
            runQuery($query);
        
            //update respective invoice by first deleting the affected service then re inserting in invoice details
            $query="delete from darlelJobber_invoice_details where service='$service' && invoiceId='$invoiceId'";
            runQuery($query);
            $query="insert into darlelJobber_invoice_details set id='$randomB',invoiceId='$invoiceId',service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description'";
            runQuery($query);
        }
    }*/
    
    $query="update darlelJobber_quotes set client_approve_sign='$clientSign',approveStatus='Approved',approveTime='$timeAdded' where id='$quoteId'";
    runQuery($query);
    
    //complete the tasks that have follow up label with them for this quote
    $followUpTasks=getAll($con,"select * from darlelJobber_tasks where quoteId='$quoteId' and label='Follow Up'");
    foreach($followUpTasks as $row){
        $taskId=$row['id'];
        runQuery("update darlelJobber_tasks set status='Completed' where id='$taskId'");
        
        //put in task comments that this quote was approved
        $timeApproved=date("d M Y",time());
        $commentText="Quote approved by client on $timeApproved";
        $random=random();
        $query="insert into darlelJobber_task_comments set id='$random',commentText='$commentText',taskId='$taskId',addedBy='$session_id',timeAdded='$timeAdded'";
        runQuery($query);
        
    }
    
    
    //send an email to customer service notifying about the approval
    if($projectUrl!="https://projects.anomoz.com/ke/darlelJobber/"){
        $subject="Q# : ".$quoteDeets['quote_number']." Approved";
        $message="Quote number # ".$quoteDeets['quote_number'].": has been approved by the client";
        sendEmailNotification_mailjet($subject, $message, "salesmanager@vilofence.com");
    }
    
    //once the quote is approved then make a task for estimator and estimator admin . Making task started
    /*$taskId=random();
    $completionDate=time()+86400;
    $query="insert into darlelJobber_tasks set id='$taskId',searchBy='Quote',quoteId='$quoteId',title='Approved Quote',
    description='A quote to which you were assigned has been approved',label='Approved Quote',completionDate='$completionDate',timeAdded='$timeAdded',addedBy='admin'";
    runQuery($query);
    
    //assigning to estimator of that quote
    $estimatorId=$quoteDeets['estimatorId'];
    $random=random();
    $userId=$estimatorId;
    $query="insert into darlelJobber_teams set id='$random',userId='$userId',taskId='$taskId',timeAdded='$timeAdded'";
    runQuery($query);
    
    //sending notification for the task
    $title="Assigned To a Task";
    $description="You have been assigned a task . Click To View";
    $url=$projectUrl."detailedTaskView.php?taskId=$taskId";
    setNotification($title,$description,$userId,$url);
    
    //estimator admin Id (orlando)
    $userId="ZHC3H6CXLN";//estimator admin
    $random=random();
    $query="insert into darlelJobber_teams set id='$random',userId='$userId',taskId='$taskId',timeAdded='$timeAdded'";
    runQuery($query);
    setNotification($title,$description,$userId,$url);
    */
    /*Making task finished*/
    
    $randomC=random();
    $query="insert into darlelJobber_notes set id='$randomC',quoteId='$quoteId',title='Approval Sign',description='Click the image to view the sign',image='$clientSign',timeAdded='$timeAdded',
    userId='$session_id'";
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
											    Terms And Conditions
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
                                            ?>
									    <div class="row" style="margin-top:20px;">
									        <div class="col-12">
								                <p>
								                    <?echo ($i+1).". ".$label;?>
                                                </p>
								            </div>
								        </div>
								        <hr style="height: 3px;">
                                        <?}?>
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
                                            <input id="submitForm" type="submit" name="submitForm" class="btn btn-warning btn-sm" value="Submit Form">
                                            
                                        </div>
                                    </div>
                                    <?}?>
									
								    
								    
							        </form>		
								</div>
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
          $('#clientSign').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:200});
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