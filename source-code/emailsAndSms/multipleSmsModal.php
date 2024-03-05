<!--$userPhones has all the phones of the current user for which the modal is opened-->

<?
//$userDeetsId is the id of the customer
$loginUrl="index.php?userId=$userDeetsId";
$map=[
    "createRequest.php"=>"request",
    "createQuote.php"=>"quote",
    "createJob.php"=>"job",
    "createInvoice.php"=>"invoice",
];
$fileName = basename($_SERVER['PHP_SELF']);
$redirection=$map[$fileName];

$redirectionPage="&redirection=$redirection&entryId=$entryId";
$finalVar=$projectUrl.$loginUrl.$redirectionPage;

$message="This is a message from Vilo Fence : A $redirection has been made for you. Kindly view the $redirection from : $finalVar";
if($fileName=="createRequest.php")
    $message="Thank you for choosing Vilo Fence for your estimate request. Our estimator will visit your property as scheduled to provide an accurate estimate. Please be present during the estimate process and have the property survey ready.

For inquiries or to reschedule, please contact us directly at 813-270-5746 or email us at customerservice@vilofence.com. Please note that this number is not monitored for responses.

Thank you!
";
else if($fileName=="createQuote.php")
    $message="Hi there! Attached is the quote for your fence project ($finalVar) . Please review it and let us know if you have any questions. Note: This is an automated message. Kindly refrain from replying via text. For any inquiries, please contact Vilo Fence at 813-270-5746 or customerservice@vilofence.com.

Thank you!
Vilo Fence Team
";


$sendingSms=[];
if($fileName=="view_client.php" || $fileName=="createInvoice.php"){
    $message="";
    $sendingSms=$userPhones;
}
else if($fileName=="createQuote.php" || $fileName=="createRequest.php"){
    //sending sms in quote and request will be according to the contact and if no contact is selected then they will be account details
    $deets = ($fileName=="createQuote.php") ? $quoteDeets : $requestDeets;
    $countSms=0;
    if($deets['contactId']!=""){
        $contacts=$idToContactDetails[$deets['contactId']];
        foreach($contacts as $row){
            if(strpos($row, '@') === false){
                $sendingSms[]=$row;
                $countSms++;
            }
        }
        if($countSms==0)
            $sendingSms=$userPhones;
    }
    else
        $sendingSms=$userPhones;
}
if($fileName=="createInvoice.php")
    $message="Hi there! Thank you for choosing Vilo Fence for your project . Attached is the invoice for your fence project ($finalVar). Note: This is an automated message. Kindly refrain from replying via text. For any inquiries,please contact Vilo Fence at 813-270-5746 or customerservice@vilofence.com.";
?>

<div class="modal fade" id="smsModal" tabindex="-1" aria-hidden="true">
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
                  <h1 class="mb-3 text-center" id="modelTitle">Send SMS</h1>
               </div>
               <div class="row">
                    <div class="col-12" id="sendingPhones">
                       <label>Sending Phone Numbers</label>
                       
                       <div class="row mb-5">
                           <div class="col-10">
                               <input type="text" name="phone" class="form-control" placeholder="Enter Phone Number">
                            </div>
                           <div class="col-2">
                               <a class="btn btn-warning " onclick="addPhone()">Add</a>
                           </div>
                       </div>
                       <div id="phoneNumbers">
                       <?foreach($sendingSms as $row){ $random=random();?>
                            <div id="<?echo $random?>" style="display: inline;">
                                <input type="text" name="phoneNumbers[]" value="<?echo $row?>" hidden>
                                <p class="btn btn-light-success btn-sm" style="margin-right:3px;">
                                    <?echo $row;?>
                                    <a onclick="remove('<?echo $random;?>')" style="margin-left: 10px;color: red;">X</a>
                                </p>
                            </div>
                       <?}?>
                       </div>
                    </div>
                    <div class="col-12">
                        <label>Enter Description</label>
                        <textarea rows="8" class="form-control" name="smsDescription" placeholder="Enter SMS Description" required><?echo $message;?></textarea>
                    </div>
                </div>
               <div class="text-center mt-5">
                  <input type="submit" value="Send SMS" name="sendSmsModal" class="btn btn-primary">
               </div>
            </form>
         </div>
      </div>
   </div>
</div>
<script>
    function random(length) {
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
    function addPhone(){
        var phoneNumber=$("input[name='phone']").val();
        var randomVal=random(5);
        
        var string=`
        <div id="`+randomVal+`" style="display: inline;">
            <input type="text" name="phoneNumbers[]" value="`+phoneNumber+`" hidden>
            <p class="btn btn-light-success btn-sm" style="margin-right:3px;">
                `+phoneNumber+`
                <a onclick="remove('`+randomVal+`')" style="margin-left: 10px;color: red;">X</a>
            </p>
        </div>
        `;
        $('#phoneNumbers').append(string);
        $("input[name='phone']").val("");
    }
</script>