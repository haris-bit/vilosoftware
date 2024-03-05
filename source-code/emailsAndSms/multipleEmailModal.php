<!--$userEmails has all the phones of the current user for which the modal is opened and $userDeetsId is the id of the customer-->
<?
$fileName = basename($_SERVER['PHP_SELF']);
$loginUrl="index.php?userId=$userDeetsId";
$map=[
    "createRequest.php"=>"request",
    "createQuote.php"=>"quote",
    "createJob.php"=>"job",
    "createInvoice.php"=>"invoice",
];
$redirection=$map[$fileName];
$redirectionPage="&redirection=$redirection&entryId=$entryId";
$finalVar=$projectUrl.$loginUrl.$redirectionPage;
$purpose=ucfirst($redirection);
$description="$purpose has been made for you . Kindly <a href='$finalVar'>View The $purpose</a>";

if($fileName=="createRequest.php" ){

    $subject="Fence Estimate Request - Confirmation";
    $description="Dear Valued Customer

Thank you for choosing Vilo Fence for your estimate request. Our estimator will visit your property at the agreed-upon time to provide you with an accurate estimate.

Please be present during the estimate process to ensure your requirements are addressed. Having a copy of the property survey ready will help us assess the project accurately.

For any inquiries or to reschedule, please contact us directly at 813-270-5746 or email us at customerservice@vilofence.com. Kindly note that this email address is not monitored for replies.

Thank you for considering Vilo Fence. We look forward to meeting you and providing a comprehensive estimate.

Best regards,
Vilo Fence Team
Phone: 813-270-5746
Email: customerservice@vilofence.com
";
}

else if($fileName=="createQuote.php"){

    $subject="Fence Quote - Please Review";
    $description="Dear Valued Customer,

We hope this email finds you well. We are pleased to provide you with a detailed quote for the fence project we discussed. Please find the quote attached [ <a href='$finalVar'>Click Here To View The Quote</a> ].

The quote includes a comprehensive breakdown of the materials required, along with the corresponding costs for labor and installation. We have taken into consideration all the specifications and preferences shared with us during our consultation.

Please note that this is an automated email, and we kindly request that you do not reply directly to this address. If you have any questions or require further clarification, please reach out to us using the contact information provided below.

Thank you for considering Vilo Fence for your project. We look forward to the opportunity to work with you.

Best regards,
Vilo Fence Team
Phone: 813-270-5746
Email: customerservice@vilofence.com
";
}

$sendingEmails=[];
if($fileName=="view_client.php" || $fileName=="createInvoice.php"){
    $description="";
    $sendingEmails=$userEmails;
}
else if($fileName=="createQuote.php" || $fileName=="createRequest.php"){
    //sending emails in quote and request will be according to the contact and if no contact is selected then they will be account details
    $deets = ($fileName=="createQuote.php") ? $quoteDeets : $requestDeets;
    $countEmails=0;
    if($deets['contactId']!=""){
        $contacts=$idToContactDetails[$deets['contactId']];
        foreach($contacts as $row){
            if(strpos($row, '@') !== false){
                $sendingEmails[]=$row;
                $countEmails++;
            }
        }
        if($countEmails==0)
            $sendingEmails=$userEmails;
    }
    else
        $sendingEmails=$userEmails;
}

if($fileName=="createInvoice.php"){

    $subject="Fence Invoice ";
    $description="Dear Valued Customer,

We hope this email finds you well. Thank you for choosing Vilo Fence for your project , kindly find the invoice attached [ <a href='$finalVar'>Click Here To View The Invoice</a> ] 

Best regards,
Vilo Fence Team
Phone: 813-270-5746
Email: customerservice@vilofence.com
";
}
?>
<div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered mw-650px">
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
                  <h1 class="mb-3 text-center" id="modelTitle">Send Email</h1>
               </div>
               <div class="row">
                    <div class="col-12 mb-5" id="sendingPhones">
                       <label>Sending Emails</label>
                       
                       <div class="row mb-5">
                           <div class="col-10">
                               <input type="text" name="email" class="form-control" placeholder="Enter Email Address">
                            </div>
                           <div class="col-2">
                               <a class="btn btn-warning " onclick="addEmail()">Add</a>
                           </div>
                       </div>
                       <div id="emails">
                       <?foreach($sendingEmails as $row){ $random=random();?>
                            <div id="<?echo $random?>" style="display: inline;">
                                <input type="text" name="emails[]" value="<?echo $row?>" hidden>
                                <p class="btn btn-light-success btn-sm" style="margin-right:3px;">
                                    <?echo $row;?>
                                    <a onclick="remove('<?echo $random;?>')" style="margin-left: 10px;color: red;">X</a>
                                </p>
                            </div>
                       <?}?>
                       </div>
                    </div>
                    <div class="col-12 mb-5">
                        <label>Enter Title</label>
                        <input type="text" name="emailTitle" class="form-control" placeholder="Enter Title" value="<?echo $subject?>" required>
                    </div>
                    <div class="col-12 mb-5">
                        <label>Enter Description</label>
                        <textarea rows="8" class="form-control" name="emailDescription" placeholder="Enter Email Description" required><?echo $description;?></textarea>
                    </div>
                    <div class="col-12 mb-5" id="sendPdfCheckBox">
                        <div class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" name="sendPDF" type="checkbox" />
                            <label class="form-check-label">
                                Send PDF with the Email
                            </label>
                        </div>
                    </div>
                </div>
               <div class="text-center">
                   <input type="text" name="entryId" hidden>
                   <input type="text" name="redirection" hidden>
                   <input type="submit" value="Send Email" name="sendEmailModal" class="btn btn-primary">
               </div>
            </form>
         </div>
      </div>
   </div>
</div>
<script>

    $(document).ready(function(){
        $("#emailModal").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            console.log(mydata)
            var fileName="<?echo $fileName?>";
            if(mydata!=null && mydata!="simpleMultipleMail"){
                var userDeetsId=mydata['userDeetsId'];
                var redirection=mydata['redirection'];
                var entryId=mydata['entryId'];
                var projectUrl="<?echo $projectUrl?>";
                var purpose=redirection.charAt(0).toUpperCase() + redirection.slice(1);
                
                var loginUrl="index.php?userId="+userDeetsId;
                var redirectionPage="&redirection="+redirection+"&entryId="+entryId;
                var finalVar=projectUrl+loginUrl+redirectionPage;
                var message=purpose+" has been made for you . Kindly <a href='"+finalVar+"'>View The "+purpose+"</a>";
                
                $("input[name='redirection']").val(redirection);
                $("input[name='entryId']").val(entryId);
                $("textarea[name='emailDescription']").val(message);
            }
            else{
                $("input[name='redirection']").val("");
                $("input[name='entryId']").val("");
            }
            if (typeof mydata === 'undefined'){ 
                mydata={};
                mydata['hidePDF']=0;
                console.log("undefined");
            }
            
            if(mydata=="simpleMultipleMail" || fileName=="createRequest.php" || mydata['hidePDF']== 1 )
                $('#sendPdfCheckBox').hide();
            else
                $('#sendPdfCheckBox').show();
            
        });
    });

    function randomEmail(length) {
        var result           = '';
        var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var charactersLength = characters.length;
        for ( var i = 0; i < length; i++ ) {
            result += characters.charAt(Math.floor(Math.random() * 
            charactersLength));
        }
        return result;
    }
    function remove(id){
        $('#'+id).remove();
    }
    function addEmail(){
        var email=$("input[name='email']").val();
        var randomVal=randomEmail(5);
        
        var string=`
        <div id="`+randomVal+`" style="display: inline;">
            <input type="text" name="emails[]" value="`+email+`" hidden>
            <p class="btn btn-light-success btn-sm" style="margin-right:3px;">
                `+email+`
                <a onclick="remove('`+randomVal+`')" style="margin-left: 10px;color: red;">X</a>
            </p>
        </div>
        `;
        $('#emails').append(string);
        $("input[name='email']").val("");
    }
</script>