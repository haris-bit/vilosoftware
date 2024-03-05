<?require("./global.php");

$idToContactDetails=[];
$allContacts=getAll($con,"select * from darlelJobber_contacts");
$allContactDetails=getAll($con,"select * from darlelJobber_contact_details cd inner join darlelJobber_contacts c on cd.contactId=c.id ");

foreach($allContactDetails as $row){
    if (isset($idToContactDetails[$row['contactId']])) 
        $idToContactDetails[$row['contactId']][] = $row['value'];
    else 
        $idToContactDetails[$row['contactId']] = [$row['value']];
}


$quoteId=clear($_GET['entryId']);
$parameters = "$_SERVER[QUERY_STRING]";
$view = (isset($_GET['view'])) ? 1 : 0;
$new = (isset($_GET['new'])) ? 1 : 0;
$edit = (isset($_GET['entryId'])) ? 1 : 0;
$timeAdded=time();

if($logged==0)
    header("Location:./index.php");
if($view || $session_role=="Client")
    header("Location:./viewQuote.php?entryId=$quoteId");

if(isset($_GET['override'])){
    runQuery("update darlelJobber_quotes set formStatus='Submitted' where id='$quoteId'");
    
    //formA and formB are marked as submitted when the quote is overriden
    runQuery("update darlelJobber_formA set submissionStatus='Submitted' where quoteId='$quoteId'");
    runQuery("update darlelJobber_formB set submissionStatus='Submitted' where quoteId='$quoteId'");
    
    $customerId=$quoteDeets['customerId'];
    header("Location:?entryId=$quoteId&triggerModal=email");
}

$labels=getAll($con,"select * from darlelJobber_labels order by timeAdded desc");
$labelNameToColor=[];
foreach($labels as $row)
    $labelNameToColor[$row['title']]=$row['colorCode'];


$clients=getAll($con,"select * from darlelJobber_users where role='Client'");
$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row)
{$idToInfo[$row['id']]=$row;}

$properties=getAll($con,"select * from darlelJobber_properties");

$entryId=clear($_GET['entryId']);
$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$entryId'");

//making quote status
if($quoteDeets['sendStatus']=="Not Sent")
    $quoteStatus="Draft";
if($quoteDeets['sendStatus']=='Sent' && $quoteDeets['approveStatus']=='In Progress')
    $quoteStatus="Awaiting Response";
if($quoteDeets['approveStatus']=='Changes Requested')
    $quoteStatus="Changes Requested";
if($quoteDeets['convertStatus']=='Converted')
    $quoteStatus="Converted";
if($quoteDeets['paidStatus']!='Paid' && $quoteDeets['approveStatus']=='Approved')
    $quoteStatus="Approved But Not Paid";
if($quoteDeets['paidWithCash']=='Yes')
    $quoteStatus="Paid With Cash";



//checking if formA and formB are not filled by estimator then this quote should be sent to client
$formADeets=getRow($con,"select * from darlelJobber_formA where quoteId='$quoteId'");
$formBDeets=getRow($con,"select * from darlelJobber_formB where quoteId='$quoteId'");

$formsFilled=1;
if($formADeets['submissionStatus']!="Submitted" && $formBDeets['submissionStatus']!="Submitted" && $formADeets['estimatorSign']=="None" && $formBDeets['fenceOption']=="None"){
    $msg="'Form A and Form B are not filled . Kindly fill them to open access for sending email and message to client'";
    $formNotFilledMsg='onclick="alert('.$msg.')"';
    $formsFilled=0;
}

//get the amount that has already been paid to put in modal
$paidAmountDeets=getRow($con,"SELECT sum(amountPaid) as paidAmount,sum(amountPaid+discountAvailed) as paidAmountWithDiscount from darlelJobber_payments where quoteId='$quoteId'");
$paidAmount = ( $paidAmountDeets['paidAmount']=="" ) ? 0 : $paidAmountDeets['paidAmount'];
$paidAmountWithDiscount = ( $paidAmountDeets['paidAmountWithDiscount']=="" ) ? 0 : $paidAmountDeets['paidAmountWithDiscount'];

$totalAmountRemaining=$quoteDeets['requiredDepositAmount']-$paidAmountWithDiscount;

$userDeetsId=$quoteDeets['customerId'];
$propertyDeetsId=$quoteDeets['propertyId'];

if(isset($_GET['customerId'])){
    $userDeetsId=$_GET['customerId'];
    $propertyDeetsId=getRow($con,"select * from darlelJobber_properties where userId='$userDeetsId'");
    $propertyDeetsId=$propertyDeetsId['id'];
}

$userDeets=getRow($con,"select * from darlelJobber_users where id='$userDeetsId'");
$propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyDeetsId'");

$query="select * from darlelJobber_services where localUseId='None' || localUseId='$entryId'";
$services=getAll($con,$query);
$allServices=array("");
foreach($services as $row){
    $index=$row['name']." SKU =".$row['sku'];
    $allServices[$index]=$row;
}

if(isset($_GET['labor_delete'])){
    $query="delete from darlelJobber_quote_details where quoteId='$quoteId' && service='Labor Fees'";
    runQuery($query);
    updateQuote($quoteId);
    header("Location:?entryId=$quoteId");
}

if((isset($_POST['create_quote'])) || (isset($_POST['convertJob'])) || isset($_POST['sendEmail']) || (isset($_POST['sendSms']))){
    //quote main entry
    $title = clear($_POST['title']);
    $editApprovedQuote = clear($_POST['editApprovedQuote']);
    
    //if approved quote then make the PDF before the change was made 
    if($editApprovedQuote){
        $fileName="print_".random();
        $url=urlencode($g_website.'/printQuoteInvoice.php?quoteId='.$quoteId.'&printQuoteNotes=1');
        printPage(urldecode($url),$fileName);
        
        //getting the number from the last entered quote pdf 
        $notesTitle=getRow($con,"select * from darlelJobber_notes where quoteId='$quoteId' && timeAdded < $timeAdded && title like '%Quote Printable PDF%' order by timeAdded desc")['title'];
        if($notesTitle==null || $notesTitle=="Printable PDF")
            $notesTitle="Quote Printable PDF_1";
        else if($notesTitle!=null){
            $number = preg_replace('/[^0-9]/', '', $notesTitle);
            $number++;
            $notesTitle="Quote Printable PDF_$number";
        }
        
        $random=random();
        $query="insert into darlelJobber_notes set id='$random',title='$notesTitle',description='Quote Printable PDF of the quote ',image='$fileName.pdf',
        addedBy='$session_id',timeAdded='$timeAdded',quoteId='$quoteId'";
        runQuery($query);
    }
    
    
    $subtotal = round(clear($_POST['subtotal']),2);
    $discount = round(clear($_POST['discount']),2);
    $propertyId = clear($_POST['propertyId']);
    $message = clear($_POST['message']);
    $quote_for=clear($_POST['quote_for']);
    $final_total=round(clear($_POST['final_total']),2);
    $required_deposit=round(clear($_POST['required_deposit']),2);
    $complete_payment=clear($_POST['complete_payment']);
    $required_811=clear($_POST['required_811']);
    $cashOnly=clear($_POST['cashOnly']);
    $displayPricing=clear($_POST['displayPricing']);
    $discountType=clear($_POST['discountType']);
    $tieredPricing=clear($_POST['tieredPricing']);
    $requiredDepositType=clear($_POST['requiredDepositType']);
    $projectName=clear($_POST['projectName']);
    $contactId=clear($_POST['contactId']);
    
    
    $complete_payment = ($complete_payment=="on") ? "Yes":"No";
    $required_811 = ($required_811=="on") ? "Yes":"No";
    $cashOnly = ($cashOnly=="on") ? "Yes":"No";
    $displayPricing = ($displayPricing=="on") ? "Yes":"No";
    
    if($session_role!="Admin" && $new)
        $estimatorId=$session_id;
    else if($session_role!="Admin" && $edit)
        $estimatorId=$quoteDeets['estimatorId'];
    else if($session_role=="Admin")
        $estimatorId=clear($_POST['estimatorId']);
    
    //depending upon the type we will calculate the quote required deposit amount
    $requiredDepositAmount= ($requiredDepositType=="Percentage") ? round($final_total*($required_deposit/100),2) : $required_deposit ;
    
    if(!$edit){
        $quoteNumber=getRow($con,"select max(quote_number) as quoteNumber from darlelJobber_quotes")['quoteNumber']+1;
        
        //if the quote is new then the pricing will be by default C
        $tieredPricing="C";
        $random=random();
        $query="insert into darlelJobber_quotes set id='$random',viewedByEstimator='No',contactId='$contactId',projectName='$projectName',displayPricing='$displayPricing',startTimer='$timeAdded',estimatorId='$estimatorId',
        tieredPricing='$tieredPricing',discountType='$discountType',required_811='$required_811',complete_payment='$complete_payment',cashOnly='$cashOnly',
        required_deposit='$required_deposit',quote_number='$quoteNumber',customerId='$quote_for',title='$title',subtotal='$subtotal',discount='$discount',
        propertyId='$propertyId',message='$message',requiredDepositAmount='$requiredDepositAmount',
        timeAdded='$timeAdded',total='$final_total',addedBy='$session_id',requiredDepositType='$requiredDepositType'";
        runQuery($query);
    
        
        $quoteId=$random;
        $random=random();
        $query="insert into darlelJobber_quote_details set id='$random',quoteId='$quoteId',service='Labor Fees',qty='1',unit_price='250',total='250',type='Labor Fees'";
        runQuery($query);
        
        //creating all the forms for this quote as soon as the quote is created 
        $forms=["A","B","C","D"];
        foreach($forms as $row){
            $random=random();
            $query="insert into darlelJobber_form$row set id='$random',quoteId='$quoteId',clientId='$quote_for',timeAdded='$timeAdded'";
            runQuery($query);
        }
        $quoteDeets['tieredPricing']="C";
    }
    else if($edit){
        
        $quoteId=$entryId;
        $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
        
        $query="update darlelJobber_quotes set customerId='$quote_for',contactId='$contactId',projectName='$projectName',displayPricing='$displayPricing',estimatorId='$estimatorId',
        tieredPricing='$tieredPricing',discountType='$discountType',required_811='$required_811',complete_payment='$complete_payment',cashOnly='$cashOnly',
        required_deposit='$required_deposit',title='$title',subtotal='$subtotal',discount='$discount',propertyId='$propertyId',
        message='$message',total='$final_total',requiredDepositAmount='$requiredDepositAmount',requiredDepositType='$requiredDepositType' where id='$quoteId'";
        runQuery($query);
        
        
        //if the total price was changed then insert in quote history that price was changed
        if($quoteDeets['total']!=$final_total){
            $random=random();
            $historyTitle="Price changed from ".$quoteDeets['total']." To $final_total";
            $query="insert into darlelJobber_quote_history set id='$random',title='$historyTitle',type='Quote Changed',quoteId='$quoteId',timeAdded='$timeAdded',addedBy='$session_id'";
            runQuery($query);
        }
    }
    
    if($edit){
        //maybe some line items were removed, and if these line items are in site plan then they need to be removed from site plan as well
        //so we are saving previous quote line items and we will compare with the current line items
        $previousLineItems=getAll($con,"select service,optionalStatus from darlelJobber_quote_details where quoteId='$quoteId' && service!='Labor Fees'");
        
        //deleting quote details
        $query="delete from darlelJobber_quote_details where quoteId='$quoteId' && service!='Labor Fees'";
        runQuery($query);
    }
    
    //if changes made in approved quote then every thing is reset
    if($editApprovedQuote && isset($_POST['create_quote'])){
        $query="update darlelJobber_quotes set approveStatus='Pending Changes',sendStatus='Not Sent' where id='$quoteId'";
        runQuery($query);
    }
    
    //if the tier is changed through discount then discount should go to zero . Calculating discount percentage to check if tier has been updated or not 
    //if updated then we will update the unit prices
    $discountPercentage=0;
    if($discountType=="Percentage")
        $discountPercentage=$discount;
    else if($discountType=="Amount"){
        $discountAmount=$discount;
        $tempFinalTotal=$final_total;
        $discountPercentage = ($discountAmount / $tempFinalTotal) * 100;
        $discountPercentage=round($discountPercentage,2);
    }
    
    //first we will check whether the pricing has been changed or not 
    //(if in previous save the pricing was different and in the new save the pricing is different then we need to update the price) 
    
    $updateTier=0;
    $updateCommission=0;
    $quoteTieredPricing=$quoteDeets['tieredPricing'];
    if($quoteTieredPricing!=$tieredPricing && !$new){
        $discountPercentage=0;
        $discount=0;
        $updateCommission=1;
        $updateTier=1;
        $updatedTier=$tieredPricing;
        $quoteDeets['tieredPricing']=$tieredPricing;
        $commissionTier=$tieredPricing;
    }
    
    
    //calculating that if the discount was more then update the tier
    if(($discountPercentage >= 1) && ($quoteDeets['tieredPricing']=="A")){//checking for A tier
        $commissionTier="B";
        $updateCommission=1;
    }
    else if(($discountPercentage > 0) && ($quoteDeets['tieredPricing']=="B")){//checking for B tier
        $commissionTier="C";
        $updateCommission=1;
    }
    else if(($discountPercentage > 2) && ($quoteDeets['tieredPricing']=="C")){//checking for C tier
        $commissionTier="D";
        $updateCommission=1;
    }
    
    $service_inp=$_POST['service'];
    $qty_inp=$_POST['qty'];
    $serviceId_inp=$_POST['serviceId'];
    $unit_price_inp=$_POST['unit_price'];
    $total_inp=$_POST['total'];
    $description_inp=$_POST['description'];
    $helperFile_inp=$_POST['helperFile'];
    $type_inp=$_POST['type'];
    $optionalStatus_inp=$_POST['optionalStatus'];
    $optionalApproveStatus_inp=$_POST['optionalApproveStatus'];
    $target_dir = "./servicesImages/";
    
    for($i=0;$i<count($service_inp);$i++){
        
        $service=$service_inp[$i];
        $qty=$qty_inp[$i];
        $type=clear($type_inp[$i]);
        
        //by default the unit price will be picked from unit price input
        $unit_price=$unit_price_inp[$i];
        //but if the tier is updated then unit price should be picked from services table
        if($updateTier){
            $unit_price=$allServices[$service]['price'];
            if($unit_price=="" || $unit_price==null)//which means that this service does not exist in the table then pick from unit price input
                $unit_price=$unit_price_inp[$i];
        }
        $service=clear($service_inp[$i]);
        $total=$total_inp[$i];
    
        if($updatedTier=="A" && $type!="TD"  && $service!="Sprinkler Assurance Plan" && $updateTier){//increase unit price only when tier is changed
            $increasedAmount=$unit_price*0.15;
            $unit_price+=$increasedAmount;
        }
        else if($updatedTier=="B" && $type!="TD" && $service!="Sprinkler Assurance Plan" && $updateTier){//increase unit price only when tier is changed
            $increasedAmount=$unit_price*0.10;
            $unit_price+=$increasedAmount;
        }
        $unit_price=round($unit_price, 2);
        $total=$qty*$unit_price;
        $total=round($total, 2);
        
        $description=clear($description_inp[$i]);
        $helperFile=clear($helperFile_inp[$i]);
        $serviceId=clear($serviceId_inp[$i]);
        
        $optionalStatus=clear($optionalStatus_inp[$i]);
        $optionalApproveStatus=clear($optionalApproveStatus_inp[$i]);
        
        //updating labor fees line item
        if($type=="Labor Fees"){
            $unit_price=$unit_price_inp[$i];
            $qty=$qty_inp[$i];
            $total=round($qty*$unit_price,2);
            runQuery("update darlelJobber_quote_details set unit_price='$unit_price',qty='$qty',total='$total' where quoteId='$quoteId' and service='Labor Fees'");
            continue;
        }
        
        if(!empty( $_FILES[ 'images' ][ 'error' ][ $i ] ) )
            $image=$helperFile;
        else{
            $image = clear($_FILES[ 'images' ]['name'][$i]);
            $tmpName = $_FILES[ 'images' ][ 'tmp_name' ][ $i ];
            $target_file = $target_dir.$image;
            move_uploaded_file( $tmpName, $target_file ); 
        }
        
        $random=random();
        $query="insert into darlelJobber_quote_details set id='$random',serviceId='$serviceId',quoteId='$quoteId',optionalApproveStatus='$optionalApproveStatus',
        optionalStatus='$optionalStatus',service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description',image='$image'";
        runQuery($query);
        
        //update this service name in the site plan details
        if($optionalStatus=="No" && $serviceId!="None")
            runQuery("update darlelJobber_site_plan_details set service='$service' where optionalType='N.O' and serviceId='$serviceId' and quoteId='$quoteId'");
        else if($optionalStatus=="Yes" && $serviceId!="None")
            runQuery("update darlelJobber_site_plan_details set service='$service' where optionalType='Opt' and serviceId='$serviceId' and quoteId='$quoteId'");
    }
    
    //now all quote line items are saved 
    if($edit){
        
        /*a service that was in site plan was deleted from quote so it is removed from the site plan as well started*/
        
        //checking for services which were in previous quote details but not in new quote details
        $lines=getAll($con,"select service,optionalStatus from darlelJobber_quote_details where quoteId='$quoteId' && service!='Labor Fees'");
        $newLineItems = [];
        $newLineItemsOptional = [];
        
        foreach($lines as $row){
            if($row['optionalStatus']=="No")
                $newLineItems[]=clear($row['service']);
            else if($row['optionalStatus']=="Yes")
                $newLineItemsOptional[]=clear($row['service']);
        }
        
        //this will delete all the services that were in previous quote details but not in new quote details
        foreach($previousLineItems as $row){
            $service=clear($row['service']);
            if ((!in_array($service,$newLineItems)) && ($row['optionalStatus']=="No")){
                //if this service is not in new line items then remove from site plan details (for normal line items)
                $query="delete from darlelJobber_site_plan_details where service='$service' and quoteId='$quoteId' and optionalType='N.O'";
                runQuery($query);
            }
            else if ((!in_array($service,$newLineItemsOptional)) && ($row['optionalStatus']=="Yes")){
                //if this service is not in new line items then remove from site plan details (for optional line items)
                $query="delete from darlelJobber_site_plan_details where service='$service' and quoteId='$quoteId' and optionalType='Opt'";
                runQuery($query);
            }
        }
        /*a service that was in site plan was deleted from quote so it is removed from the site plan as well finished*/
    }
    
    if($updatedTier!="D" && $updateTier){
        runQuery("update darlelJobber_quotes set tieredPricing='$updatedTier',commissionTier='$commissionTier',discount='$discount' where id='$quoteId'");
    }
    else if($updatedTier=="D" && $updateTier){
        runQuery("update darlelJobber_quotes set tieredPricing='$updatedTier',discountType='Percentage',discount='3',commissionTier='$commissionTier' where id='$quoteId'");
    }
    else if($updateCommission){
        runQuery("update darlelJobber_quotes set commissionTier='$commissionTier' where id='$quoteId'");
    }
    //this is for updating the unit price of teardown both optional and normal teardown
    if($updateTier){
        $tierDownDeetsNormal=getRow($con,"select * from darlelJobber_quote_details where type='TD' && optionalStatus='No' && quoteId='$quoteId'");
        $tierDownDeetsOptional=getRow($con,"select * from darlelJobber_quote_details where type='TD' && optionalStatus='Yes' && quoteId='$quoteId'");
        
        $finalTearDownPrice=2.84;
        if($updatedTier=="A")
            $finalTearDownPrice=3.26;
        else if($updatedTier=="B")
            $finalTearDownPrice=3.12;
        
        $finalTotalNormal=$finalTearDownPrice*$tierDownDeetsNormal['qty'];
        $normalTDId=$tierDownDeetsNormal['id'];
        runQuery("update darlelJobber_quote_details set unit_price='$finalTearDownPrice',total='$finalTotalNormal' where id='$normalTDId'");
        
        $finalTotalOptional=$finalTearDownPrice*$tierDownDeetsOptional['qty'];
        $optionalTDId=$tierDownDeetsOptional['id'];
        runQuery("update darlelJobber_quote_details set unit_price='$finalTearDownPrice',total='$finalTotalOptional' where id='$optionalTDId'");
    }
    updateQuote($quoteId);
    
    if(isset($_POST['convertJob'])){
        //sending email and text message to client 
        $customerId=$quote_for;
        $emails=explode("*",$idToInfo[$customerId]['email']);
        
        $phones=explode("*",$idToInfo[$customerId]['phone']);
        $clientNameTitle= ($idToInfo[$customerId]['title']=="No Title") ? "" : $idToInfo[$customerId]['title']; 
        $clientName=$clientNameTitle." ".$idToInfo[$customerId]['first_name']." ".$idToInfo[$customerId]['last_name'];
        $emailMessage="
        Dear $clientName,
        <br><br>We are thrilled to inform you that your recent fence quote with Vilo Fence has been approved! Thank you for choosing Vilo Fence as your trusted partner for all your fencing needs. We are committed to ensuring your complete satisfaction throughout this process.
        <br><br>As we move forward, we would like to bring your attention to a few essential details:
        <br>
        <ol>
            <li><b>The 4-Point Rule:</b> Please review your contract carefully to confirm that all your specific requirements are accurately documented. We take pride in our commitment to delivering exactly what you envision, and the 4-Point Rule helps us ensure that every detail is captured.</li>
            <li><b>Changes to Your Order:</b> If you need to make any changes to your order, kindly notify us at least 7 business days prior to the scheduled installation date. Please be aware that any alterations made after this period may result in a $250.00 charge for the reprocessing of your order.</li>
            <li><b>Survey Documentation:</b>  If you haven't already provided a copy of your survey to our estimator, please do so at your earliest convenience. This documentation is crucial for ensuring that your fence installation adheres to all local regulations and property lines</li>
            <li><b>Deposit Payment:</b> If you haven't yet paid the required deposit, we kindly request that you do so promptly to secure your scheduled installation date. Failure to make the deposit may result in a delay in your installation.</li>
        </ol>
        <br>If you have any questions, concerns, or need further assistance, please don't hesitate to reach out to your estimator or Leo at customerservice@vilofence.com. We are here to provide you with exceptional service and support throughout your fencing project.
        <br><br>Thank you once again for choosing Vilo Fence. We look forward to exceeding your expectations and delivering a fence that perfectly suits your needs.
        <br><br>Best Regards,
        <br>Vilo Fence Team
        ";
        $smsMessage="Dear $clientName,\nGreat news! Your Vilo Fence quote has been approved! 🎉 Thank you for choosing us! \nRemember:\n1.Check your contract (the 4-Point Rule) for accuracy.\n2.Notify us 7 business days ahead for changes (or $250 fee).\n3.Share your survey with our estimator.\n4.Secure your installation date with the deposit.\nQuestions? Contact your estimator or Leo at customerservice@vilofence.com.\nWe're excited to work with you!\nBest Regards Vilo Fence Team
        ";
        //sending email to all client emails
        foreach($emails as $userEmail){
            if($userEmail!=""){
                sendEmailNotification_mailjet("Vilo Fence Quote Approval Confirmation", $emailMessage, $userEmail);
            }
        }
        //sending message to all client phone numbers
        foreach($phones as $userPhone){
            if($userPhone!=""){
                sendansms($userPhone,$smsMessage);
            }
        }
        
        //converting to job
        $jobId=random();
        $requestId=$quoteDeets['requestId'];
        
        $job_number=getRow($con,"select job_number from darlelJobber_jobs order by timeAdded desc")['job_number']+1;
        
        //updating jobId in request to make the chain
        if($requestId!="None"){
            $query="update darlelJobber_requests set jobId='$jobId' where id='$requestId'";
            runQuery($query);
        }
        
        //update notes section means that ke jo quotes ke notes the woh ab job ke notes bhi hoonge
        $query="update darlelJobber_notes set jobId='$jobId' where quoteId='$quoteId'";
        runQuery($query);
        
        //insert into jobs
        $query="insert into darlelJobber_jobs set id='$jobId',required_811='$required_811',job_number='$job_number',customerId='$quote_for',title='$title',
        propertyId='$propertyId',timeAdded='$timeAdded',addedBy='$session_id',requestId='$requestId',quoteId='$quoteId',total='$final_total'";
        runQuery($query);
        
        //update quote status and jobId in quote to maintain the chain
        runQuery("update darlelJobber_quotes set convertStatus='Converted',jobId='$jobId' where id='$quoteId'");
        header("Location:./createJob.php?entryId=$jobId");
        exit();
    }
    else if(isset($_POST['create_quote'])){
        header("Location:?entryId=$quoteId");
    }
    else if(isset($_POST['sendEmail'])){
        header("Location:?entryId=$quoteId&triggerModal=email");
    }
    else if(isset($_POST['sendSms'])){
        header("Location:?entryId=$quoteId&triggerModal=sms");
    }
}

if(isset($_GET['delete_site_plan'])){
    $id=$_GET['delete_site_plan'];
    $query="delete from darlelJobber_site_plan_details where planId='$id'";
    runQuery($query);
    
    $query="delete from darlelJobber_site_plans where id='$id'";
    runQuery($query);
    
    //deleting from each quote
    $query="delete from darlelJobber_quote_details where sitePlanId='$id'";
    runQuery($query);
}
if(isset($_POST['requestChange'])){
    $quoteId=clear($_GET['entryId']);
    $changes_description=clear($_POST['changes_description']);
    $query="update darlelJobber_quotes set changes_description='$changes_description',approveStatus='Changes Requested' where id='$quoteId'";
    runQuery($query);
    header("Location:?entryId=$quoteId&view=1&m=Changes have been requested");
}

//sign submission
if(isset($_POST['approveQuote'])){
    $client_approve_sign=clear($_POST['client_approve_sign']);
    $quoteId=clear($_GET['entryId']);
    
    if($quoteDeets['approveStatus']=="Pending Changes"){
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
            
            $randomA=random();
            $randomB=random();
            
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
    }
    
    $query="update darlelJobber_quotes set client_approve_sign='$client_approve_sign',approveStatus='Approved' where id='$quoteId'";
    runQuery($query);
    
    $id=random();
    $query="insert into darlelJobber_notes set id='$id',quoteId='$quoteId',title='Approval Sign',description='Click the image to view the sign',image='$client_approve_sign',timeAdded='$timeAdded',
    userId='$session_id'";
    runQuery($query);
    header("Location:./payment.php?quoteId=$quoteId");
}


if(isset($_GET['sendForm'])){
    $email=clear($_GET['email']);
    $sms=clear($_GET['sms']);
    
    //updating sentDate which means it is no longer a draft quote 
    runQuery("update darlelJobber_quotes set sentDate='$timeAdded',sendStatus='Sent' where id='$quoteId'");
    
    $userId=$userDeets['id'];
    $loginUrl="index.php?userId=$userId";
    $redirectionPage="&redirection=form&entryId=$quoteId";
    $finalVar=$projectUrl.$loginUrl.$redirectionPage;
    
    if($email){
        $userEmail=explode("*",$userDeets['email']);$userEmail=$userEmail[0];
        
        $title="Fill Forms";
        $description="Kindly <a href='$finalVar'>Fill the forms</a> that are required to make a job for your quote";
        
        sendEmailNotification_mailjet($title, $description, $userEmail);
        
        header("Location:?entryId=$quoteId&m=Forms Link Sent Successfully Via Email");
    }
    else if($sms){
        $phone=explode("*",$userDeets['phone']);$phone=$phone[0];
        $message="
        This is a message from Vilo Fence :\n
        Kindly fill the forms that are required to make a job for your quote : $finalVar";
        sendansms($phone,$message);
        
        header("Location:?entryId=$quoteId&m=Forms Link Sent Successfully Via SMS");
        
    }
    
}


/*printing quote with notes images section*/
if(isset($_GET['print'])){
    $fileName="print_".random();
    $url=urlencode($g_website.'/printQuoteInvoice.php?quoteId='.$quoteId.'&printQuoteNotes=1');
    printPage(urldecode($url),$fileName);
    
    //getting the number from the last entered quote pdf 
    $notesTitle=getRow($con,"select * from darlelJobber_notes where quoteId='$quoteId' && timeAdded < $timeAdded && title like '%Quote Printable PDF%' order by timeAdded desc")['title'];
    if($notesTitle==null || $notesTitle=="Printable PDF")
        $notesTitle="Quote Printable PDF_1";
    else if($notesTitle!=null){
        $number = preg_replace('/[^0-9]/', '', $notesTitle);
        $number++;
        $notesTitle="Quote Printable PDF_$number";
    }
    
    $random=random();
    $query="insert into darlelJobber_notes set id='$random',title='$notesTitle',description='Quote Printable PDF of the quote ',image='$fileName.pdf',
    addedBy='$session_id',timeAdded='$timeAdded',quoteId='$quoteId'";
    runQuery($query);
    
    header("Location:?entryId=$quoteId&m=PDF has been saved in notes section successfully");
}

if(isset($_GET['similarQuote'])){
    $similarQuote=clear($_GET['entryId']);
    header("Location:./quotes.php?similarQuote=$similarQuote");
    exit();
}

if(isset($_GET['paid'])){
    runQuery("update darlelJobber_quotes set paidStatus='Paid' where id='$quoteId'");
    header("Location:?entryId=$quoteId&m=The quote has been marked as paid successfully");
}


//rejected quote options
if(isset($_GET['rejected'])){
    $rejected=clear($_GET['rejected']);
    if($rejected){
        $query="update darlelJobber_quotes set approveStatus='Rejected' where id='$quoteId'";
        runQuery($query);
        header("Location:?entryId=$quoteId&m=The quote has been marked as closed lost successfully");
        exit();
    }
    else{
        $query="update darlelJobber_quotes set approveStatus='In Progress',sendStatus='Not Sent' where id='$quoteId'";
        runQuery($query);
        header("Location:?entryId=$quoteId&m=The quote has been un marked as closed lost successfully");
        exit();
    }
    
}

require("./emailsAndSms/sendingSms.php");
require("./emailsAndSms/sendingEmail.php");
require("./notes/notes.php");
require("./collectPayment/collectPaymentPhp.php");
?>
<html lang="en">
	<head>
        <?require("./includes/views/head.php");?>
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        
        <!--signature pad-->
        <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/assets/numeric-1.2.6.min.js"></script> 
        <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/assets/bezier.js"></script> 
        <script src="https://www.jqueryscript.net/demo/Smooth-Signature-Pad-Plugin-with-jQuery-Html5-Canvas/jquery.signaturepad.js"></script>
        <!--signature pad-->
        
        <link href="includes/autocompletecss.css" rel="stylesheet" type="text/css"/>
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
							<!--begin::Container-->
							<div id="kt_content_container" class="container-xxl">
								
								
                                <?if(isset($_GET['m'])){ $m=$_GET['m'];?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0"></span>
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                    </div>
                                </div>
                                <?}?>
                                
								<form action="" method="post" enctype="multipart/form-data" id="quoteForm">
									<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10" style="margin-bottom: 40px;">
										<div class="row">
										    <div class="col-12">
        										<ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-bold mb-n2">
        											<?if((!$new && ($quoteDeets['requestId']!="None")) && ($permission['view_requests'])){?>
        											<li class="nav-item">
        												<a href="createRequest.php?entryId=<?echo $quoteDeets['requestId']?>&view=1" class="nav-link text-active-primary pb-4 ">Request</a>
        											</li>
        											<?}?>
        											<?if(!$new){?>
        											<li class="nav-item">
        												<a href="createQuote.php?entryId=<?echo $quoteId?>&view=1" class="nav-link text-active-primary pb-4 active">Quote</a>
        											</li>
        											<?}?>
        											<?if( (!$new) && ($quoteDeets['jobId']!="None") && ($permission['view_jobs'])){?>
        											<li class="nav-item">
        												<a href="createJob.php?entryId=<?echo $quoteDeets['jobId']?>&view=1" class="nav-link text-active-primary pb-4 ">Job</a>
        											</li>
        											<?}?>
        											<?if( (!$new) && ($quoteDeets['invoiceId']!="None") && ($permission['view_invoices'])){?>
        											<li class="nav-item">
        												<a href="createInvoice.php?entryId=<?echo $quoteDeets['invoiceId']?>&view=1" class="nav-link text-active-primary pb-4 ">Invoice</a>
        											</li>
        											<?}?>
        											<?if(!$new && $permission['view_client']){?>
        											<li class="nav-item">
        												<a href="view_client.php?id=<?echo $quoteDeets['customerId']?>" class="nav-link text-active-primary pb-4 ">View Client</a>
        											</li>
        											<?}?>
        											
        										</ul>
    										</div>
    									</div>
										<div class="tab-content">
											<div class="tab-pane fade active show" role="tab-panel">
												<div class="d-flex flex-column gap-7 gap-lg-10">
													<div class="card card-flush py-4">
														<div class="card-header">
															<div class="card-title">
																<h2><?echo "#".$quoteDeets['quote_number']?> Quote For
											                        <a href="#"  data-bs-toggle="modal" data-bs-target="#client_name">
											                            <p style="display: inline;color: #73141d;" id="clientName">
											                                <?
											                                if((isset($_GET['entryId'])) || (isset($_GET['customerId']))){
										                                        if($userDeets['showCompanyName']=="Yes")
                                								                    echo $userDeets['company_name']." (".$userDeets['first_name']." ".$userDeets['last_name'].")";
                                                                                else
											                                        echo $userDeets['first_name']." ".$userDeets['last_name'];
                                    									    }
											                                else
											                                    echo "Client Name";
										                                    ?>
											                            </p>
										                            </a>
											                    </h2>
											                    <input type="text" name="quote_for" value="<?echo $quoteDeets['customerId']?>" hidden>
											                    <input type="text" name="contactId" value="<?echo $quoteDeets['contactId']?>" hidden>
											                </div>
															<div class="card-toolbar">
										                        <?if($session_role=="Admin" || $session_role=="Customer Service"){?>
										                        <select style="margin-right:10px;" class="btn btn-primary btn-sm" name="estimatorId" onchange="updateSmallDetails()">
										                            <?foreach($users as $row){if(($row['role']=="Estimator" || $row['role']=="Admin" || $row['role']=="Customer Service")){?>
										                            <option <?if($quoteDeets['estimatorId']==$row['id'] || ($new && $row['email']=="admin@portal.com")){echo "selected";}?> value="<?echo $row['id']?>">
										                                <?echo $row['role']." :  ".$row['name']?>
									                                </option>
										                            <?}}?>
										                        </select>
										                        <?}?>
										                        
										                        <?if($edit){
										                            $convertStatusColor =  ($quoteDeets['convertStatus']=="Converted") ? "success" : "warning";
										                            $paidStatusColor =  ($quoteDeets['paidStatus']=="Paid") ? "success" : "warning";?>
										                            <select style="margin-right:10px;" class="btn btn-primary btn-sm" name="tieredPricing" required>
    										                            <option disabled>Select Pricing</option>
    										                            <?$pricing=["A","B","C","D"];
    										                            foreach($pricing as $row){?>
    										                            <option <?if($quoteDeets['tieredPricing']==$row){echo "selected";}?> value="<?echo $row?>">
    										                                <?echo "Tier ".$row?>
    									                                </option>
    										                            <?}?>
    										                        </select>
										                            
										                            <!--options drop down menu started-->
										                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle me-2" data-bs-toggle="dropdown" aria-expanded="false">Options</button>
										                            <ul class="dropdown-menu">
                        					                            <li><a class="dropdown-item" href="?entryId=<?echo $quoteId?>&print=1">Print</a></li>
                        					                            
                        					                            <?if($session_role=="Admin" && $quoteDeets['formStatus']!='Submitted'){?>
                                                                        <li><a class="dropdown-item" href="?entryId=<?echo $quoteId?>&override=1">Override</a></li>
                                                                        <?}if($session_role=="Admin"){?>
                                                                        <li><a class="dropdown-item" onclick="return confirm('Are you sure you want to delete this quote ? ')"
                                                                        href="quotes.php?delete-record=<?echo $quoteId?>">Delete</a></li>
                                                                        <?}
                                                                        if($quoteDeets['paidStatus']=="Pending"){?>
                                                                        <li><a class="dropdown-item" href="?entryId=<?echo $quoteId?>&paid=1" 
                                                                        onclick="return confirm('Are you sure you want to mark this quote as paid ? ')" >Mark As Paid</a></li>
    								                                    <?}?>
								                                        <li><a class="dropdown-item" href="#"  data-bs-toggle="modal" data-bs-target="#collect_payment">Collect Payment</a></li>
								                                        <?if(!$new){?>
                                                                        <li><a href="?entryId=<?echo $quoteId?>&similarQuote=1" class="dropdown-item" >Create Similar Quote</a></li>
								                                        
								                                        <!--rejected options-->
								                                        <?if($quoteDeets['approveStatus']=="Rejected"){?>
								                                        <li><a href="?entryId=<?echo $quoteId?>&rejected=0" class="dropdown-item" >Remove From Closed Lost</a></li>
								                                        <?}else if($quoteDeets['approveStatus']!="Rejected"){?>
								                                        <li><a href="?entryId=<?echo $quoteId?>&rejected=1" class="dropdown-item" >Move To Closed Lost</a></li>
								                                        <?}?>
								                                        <!--rejected options-->
								                                        
								                                        <?}?>
								                                    </ul>
								                                    <!--options drop down menu finished-->
										                            
								                                    <!--statuses drop down menu started-->
										                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Status Details</button>
										                            <ul class="dropdown-menu">
										                                <li class="dropdown-item">Commission : <?echo $quoteDeets['commissionTier']?></li>
										                                <li class="dropdown-item">Convert Status : <?echo $quoteDeets['convertStatus']?></li>
										                                <li class="dropdown-item">Payment Status : <?echo $quoteDeets['paidStatus']?></li>
										                                <li class="dropdown-item">Quote Status  : <?echo $quoteStatus?></li>
										                            </ul>
                        					                        <!--statuses drop down menu finished-->
										                        <?}?>
										                    </div>
														</div>
														<div class="card-body pt-0">
															<div class="row mb-5 mt-5">
															    <div class="col-md-3 col-12">
														        	<label class="form-label">Quote Title</label>
    																<input type="text" name="title" class="form-control mb-2" value="<?echo $quoteDeets['title']?>" placeholder="Enter Quote Title" 
    																onchange="updateSmallDetails()">
        														</div>
        														<div class=" col-md-4 col-12">
														        	<label class="form-label">Project Name</label>
    																<input type="text" name="projectName" class="form-control mb-2" value="<?echo $quoteDeets['projectName']?>" placeholder="Enter Project Name" 
    																onchange="updateSmallDetails()">
        														</div>
        														<?if($session_role!="Client"){?>
        														<div class="col-md-5 col-12 d-flex flex-wrap align-items-start mt-10">
                                                                    <div class="form-check me-3 mb-3">
                                                                        <input id="completePaymentCheckbox" name="complete_payment" class="form-check-input" type="checkbox"
            														    <?if($quoteDeets['complete_payment']=="Yes"){echo "checked";}?> onchange="updateSmallDetails()"/>
                                                                        <label class="form-check-label ">Complete Payment ? </label>
                                                                    </div>
                                                                    <div class="form-check me-3 mb-3">
                                                                        <input name="required_811" class="form-check-input" type="checkbox"
                                                                        <?if($quoteDeets['required_811']=="Yes"){echo "checked";}?> onchange="updateSmallDetails()" />
                                                                        <label class="form-check-label ">
                                                                            Required 811 ? 
                                                                        </label> 
                                                                    </div>
                                                                    <div class="form-check me-3 mb-3">
                                                                        <input name="displayPricing" class="form-check-input" type="checkbox" 
                                                                        <?if($quoteDeets['displayPricing']=="Yes"){echo "checked";}?> onchange="updateSmallDetails()" />
                                                                        <label class="form-check-label ">
                                                                            Display Pricing ? 
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check me-3 mb-3">
                                                                        <input name="cashOnly" class="form-check-input" type="checkbox" 
                                                                        <?if($quoteDeets['cashOnly']=="Yes"){echo "checked";}?> onchange="updateSmallDetails()" />
                                                                        <label class="form-check-label ">
                                                                            Cash Only ? 
                                                                        </label>
                                                                    </div>
                                                                </div>
        														<?}?>
															</div>
															<div class="row text-center">
														        <div class="col-4">
														            <h4>Property Address</h4>
														            <p id="street1"><?echo $propertyDeets['street1']?></p>
														            <p id="street2"><?echo $propertyDeets['street2']?></p>
														            <p id="city"><?echo $propertyDeets['city']?></p>
														            <p id="state"><?echo $propertyDeets['state']?></p>
														            <p id="zip_code"><?echo $propertyDeets['zip_code']?></p>
														            <p id="country"><?echo $propertyDeets['country']?></p>
														            <input type="text" name="propertyId" value="<?echo $quoteDeets['propertyId']?>" hidden>
														            <div id="change_property">
														                <?if(((isset($_GET['entryId'])) || (isset($_GET['customerId']))) && (!$view)){?>
														                  <a style="margin-right: 10px;" onclick="editProperty()" href="#">Edit</a>
														                  <a id="changePropertyBtn" href="#" data-bs-toggle="modal" data-bs-target="#changeProperty">Change</a>
														                <?}?>
														            </div>
														        </div>
														        <div class="col-4">
														            <h4>Contact Details</h4>
														            <p id="accountContactDetails"></p>
														        </div>
														        <div class="col-4">
														            <h4>
														                Account Details
													                    <?if(!$new){?>
													                    <a onclick="editContactDetails()" href="#">Edit</a>
													                    <?}?>
														            </h4>
														            <div id="contactDetails">
														                <?
														                $userEmails=explode("*",$userDeets['email']);
                        											    $userPhones=explode("*",$userDeets['phone']);
                        											    foreach($userPhones as $row)
                        											    {?><p><?echo $row?></p><?}
														                
														                foreach($userEmails as $row)
                        											    {?><p><?echo $row?></p><?}
														                
														                ?>
														            </div>
														        </div>
														    </div>
														    
														    <div class="row" style="margin-top: 15px;">
														        <div class="table-responsive">
                                                                    <table class="table table-rounded table-striped border gy-7 gs-7">
                                                                        <thead class="text-center">
                                                                            <tr>
                                                                                <th >PRODUCT / SERVICE</th>
                                                                                <th >QTY</th>
                                                                                <th >Image</th>
                                                                                <th >UNIT PRICE</th>
                                                                                <th >TOTAL</th>
                                                                                <th class="btn btn-group">
                                                                                    <a style="white-space: nowrap;" onclick="addRow()" class="btn btn-primary btn-sm"><i style="font-size: x-large;" class="las la-plus"></i>Line Item</a>
                                                                                    <a style="white-space: nowrap;" onclick="addOptional()" class="btn btn-warning btn-sm"><i style="font-size: x-large;" class="las la-plus"></i>Optional Line Item</a>
                                                                                </th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="quote_section">
                                                                            <?
													                        $query="select * from darlelJobber_quote_details where quoteId='$entryId' order by entryNo asc";
                													        $quoteDeetsDetailed=getAll($con,$query);
                													        foreach($quoteDeetsDetailed as $nrow){$random=random();?>
                													        <tr id="<?echo $random?>" class="<?echo $random?>">
                                                                                <td >
                                                                                    <input name="serviceId[]" value="<?echo $nrow['serviceId']?>" hidden>
        	                                                                        <input name="optionalApproveStatus[]" value="<?echo $nrow['optionalApproveStatus']?>" hidden>
    	                                                                            <input name="optionalStatus[]" value="<?echo $nrow['optionalStatus']?>" hidden>
    	                                                                            <input onfocusout="fillPerUnitCost('<?echo $random?>')" type="text" class="form-control" value="<?echo htmlspecialchars($nrow['service'])?>"  name="service[]" style="width: 550;">
        														                    <textarea class="form-control" placeholder="Description" name="description[]" rows="3" style="width: 550;"><?echo htmlspecialchars($nrow['description'])?></textarea>
        														                </td>
        														                <td >
        														                    <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="<?echo $nrow['qty']?>" style="width: 150;">
                														            <?
                														            $text = ($nrow['optionalApproveStatus']=="Yes") ? "Included" : "Excluded";
                														            $color = ($nrow['optionalApproveStatus']=="Yes") ? "success" : "warning";
                														            
                														            if($nrow['optionalStatus']=="Yes")
        														                        echo "<a class='btn btn-$color btn-sm mt-10'>Optional Line Item $text</a>"?>
                														        </td>
                														        <td >
            														                <img style="height: 100px;width:100px;margin: 10px;" src="./servicesImages/<?echo $nrow['image']?>" alt="img" name="showImage[]">
                														            <a onclick="removeImage('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
            														                <input type="file" name="images[]" class="form-control" <?if($nrow['image']!=""){?>style="display:none;width:250px;"<?}?> >
            														                <input type="text" name="helperFile[]" class="form-control" value="<?echo $nrow['image']?>" hidden>
            														                <input type="text" name="type[]" value="<?echo $nrow['type']?>" hidden>
        														                </td>
        														                <td >
        														                    <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="<?echo $nrow['unit_price']?>" style="width: 200;">
                														            <?if($nrow['type']=="TD"){?>
                														                <a class="btn btn-light-warning btn-sm mt-2">Tear Down</a>
                														            <?}?>
        														                </td>
        														                <td >
    														                        <input class="form-control" type="number" step="0.01" name="total[]" placeholder="Total" value="<?echo round($nrow['unit_price']*$nrow['qty'],2)?>" style="width: 200;" readonly>
        													                    </td>
        													                    <td class="text-center">
        													                        <?if($nrow['type']!="Labor Fees"){?>
                													                    <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
                													                <?}else{?>
                													                    <a href="?entryId=<?echo $quoteId?>&labor_delete=1" style="padding: 20px;" class="btn btn-danger btn-sm"><i style="font-size: x-large;" class="las la-trash"></i></a>
                													                <?}?>
        													                    </td>
                                                                            </tr>
                                                                            <?}?>
														                </tbody>
                                                                    </table>
														        </div>
														    </div>
														    <hr>
													        <div class="row">
													            <div class="col-md-4 col-lg-4 col-xl-4 col-12 mb-4">
													                <label>Client Message</label>
													                <textarea rows="5" class="form-control" name="message" placeholder="Client Message"><?echo $quoteDeets['message']?></textarea>
													            </div>
													            <div class="col-md-8 col-lg-8 col-xl-8 col-12" style="text-align: right;">
													                <div class="row">
													                    <div class="col-12">
													                        <p>Subtotal($) :  
													                        <input class="form-control" style="width: 60%;display: inline;" type="number" step="0.01" name="subtotal" value="<?echo round($quoteDeets['subtotal'],2)?>" readonly></p>
													                    </div>
													                    <hr>
													                    <div class="col-12">
													                        <?$quoteDeets['discount'] = ($new || $quoteDeets['discount']=="") ? 0 : $quoteDeets['discount'];?>
													                        <p>
													                            Discount : 
													                            <input class="form-control" style="width: 40%;display: inline;" type="number" step="0.01" onkeyup="calculateFinalTotal()" name="discount" value="<?echo $quoteDeets['discount']?>" required>
													                            <select onchange="calculateFinalTotal()" name="discountType" class="form-control" style="width: 20%;display: inline;">
													                                <option <?echo ($quoteDeets['discountType']=="Percentage" ) ? "selected":"";?> value="Percentage">%</option>
													                                <option <?echo ($quoteDeets['discountType']=="Amount" || $new) ? "selected":"";?> value="Amount">$</option>
													                            </select>
													                            <b id="discountedAmount"></b>
													                        </p>
													                    </div>
													                    <hr>
													                    <div class="col-12">
													                        <p>Total($): 
													                        <input class="form-control" style="width: 40%;display: inline;" readonly type="number" step="0.01" name="final_total" value="<?echo $quoteDeets['total']?>"></p>
													                    </div>
													                    <hr>
													                    <?if($paidAmountWithDiscount!=0){?>
													                    <div class="col-12">
													                        <p>Paid Amount ($): 
													                        <input class="form-control" style="width: 40%;display: inline;" readonly type="number" value="<?echo $paidAmountWithDiscount?>"></p>
													                    </div>
													                    <hr>
													                    <?}?>
													                    <div class="col-12">
													                        <?$required_deposit = ($new || $quoteDeets['required_deposit']=="") ? 40 : $quoteDeets['required_deposit'];?>
													                        <p>
													                            Required Deposit : 
													                            <input class="form-control" style="width: 40%;display: inline;" type="number" step="0.01" onkeyup="calculateFinalTotal()" name="required_deposit" value="<?echo $required_deposit?>" required>
													                            <select onchange="calculateFinalTotal()" name="requiredDepositType" class="form-control" style="width: 20%;display: inline;">
													                                <option <?if($quoteDeets['requiredDepositType']=="Percentage" || $new){echo "selected";};?> value="Percentage">%</option>
													                                <option <?if($quoteDeets['requiredDepositType']=="Amount"){echo "selected";};?> value="Amount">$</option>
													                            </select>
													                            <b id="required_deposit"></b>
													                        </p>
													                    </div>
													                    <hr>
													                    
													                    <?if(!$view){?>
        														        <div class="col-12 d-flex mb-3">
        													                <?
        													                $editApprovedQuote=0;
        													                if($quoteDeets['approveStatus']=="Approved"){
        													                    $confirmMessage="return confirm('Are you sure you want to edit an already approved quote ?');";
        													                    $editApprovedQuote=1;
        													                }
        													                ?>
        													                <input type="text" name="editApprovedQuote" value="<?echo $editApprovedQuote;?>" hidden>
        													                <input <?if($editApprovedQuote){?>onclick="<?echo $confirmMessage;?>"<?}?> type="submit" class="btn btn-primary w-50" name="create_quote" value="Save Quote" style="margin-right: 10px;width:30%">
        												                    <?if($quoteDeets['convertStatus']!="Converted" &&  $quoteDeets['formStatus']=="Submitted"){?>
        														            <input class="btn btn-warning w-50" type="submit" name="convertJob" value="Convert To Job" style="margin-right: 10px;width:30%">
                                                                            <?}?>
        												                    <button type="button" class="btn btn-warning w-50 dropdown-toggle" data-bs-toggle="dropdown" style="margin-right:10px;width:30%" aria-expanded="false">Save And </button>
                                                                            <ul class="dropdown-menu" style="width:30%;">
            														            <li><input <?if($formsFilled){?>  name="sendEmail" type="submit" <?}else{echo $formNotFilledMsg;?>type="button"<?}?>  class="btn btn-warning btn-sm w-100" value="Send Email" ></li>    
        												                        <li><input <?if($formsFilled){?>  name="sendSms" type="submit" <?}else{echo $formNotFilledMsg;?>type="button"<?}?> class="btn btn-warning btn-sm w-100" value="Send Sms"></li>
                                                                            </ul>
                                                                            <a id="sendEmailBtn" data-bs-toggle="modal" data-bs-target="#emailModal" hidden>Send Email</a>
                                                                            <a id="sendSmsBtn" data-bs-toggle="modal" data-bs-target="#smsModal" hidden>Send SMS</a>
                                                                        </div>
        													            <hr>
            													        <?}?>
													                    
													                    
													                    <?$payments=getAll($con,"select * from darlelJobber_payments where quoteId='$quoteId' order by timeAdded desc");
													                    if((!$new) && (count($payments)>0)){?>
													                    <div class="col-12 mt-5">
													                        <table class="table table-rounded table-row-bordered border gs-7" style="text-align: center;width: 100%;">
													                            <thead>
													                                <tr><th>Deposits Uptil Now</th></tr>
													                            </thead>
													                            <tbody>
													                                <?
													                                $totalDiscountAvailed=0;
													                                $payedThroughCard=1;
													                                foreach($payments as $row){
													                                    $totalDiscountAvailed+=$row['discountAvailed'];
													                                    if($quoteDeets['paidStatus']=="Paid" &&  ( $row['method']!="Credit Card" && $row['method']!="card" ))
													                                        $payedThroughCard=0;
													                                    $string="Deposit of amount $".$row['amountPaid']." made on ".date("d M Y",$row['timeAdded']).
													                                    " with Title = ".$row['title']." and method = ".$row['method']." and discount availed = $".$row['discountAvailed'];?>
													                                <tr>
													                                    <td><?echo $string?></td>
													                                </tr>
													                                <?}
													                                if($quoteDeets['paidStatus']=="Paid"){
													                                    if($payedThroughCard || $quoteDeets['cashOnly']=="Yes")
													                                        $displayRequiredDeposit = $quoteDeets['requiredDepositAmount'];
													                                    else{//other method should give 3% discount 
												                                            $displayRequiredDeposit = $quoteDeets['requiredDepositAmount']-($quoteDeets['requiredDepositAmount']*(3/100));
													                                        $displayRequiredDeposit=round($displayRequiredDeposit,2);
													                                    }
													                                    $totalAmountRemaining=round($displayRequiredDeposit-$paidAmount,2);?>
													                                <tr class="mt-4"><td><?echo "Required Deposit = ".$displayRequiredDeposit?></td></tr>
													                                <tr><td><?echo "Received Amount = ".$paidAmount?></td></tr>
													                                <tr><td><?echo "Remaining Amount = ".round($totalAmountRemaining,2)?></td></tr>
													                                <tr><td><?echo "Project Final Balance With Card = ".round(($quoteDeets['total']-$paidAmount-$totalDiscountAvailed),2)?></td></tr>
													                                <tr>
													                                    <td><?
												                                        $finalBalanceCash=($quoteDeets['total']-$paidAmount-$totalDiscountAvailed);
													                                    $finalBalanceCash=$finalBalanceCash-($finalBalanceCash*(3/100));
													                                    echo "Project Final Balance With Cash/ECheck = ".round($finalBalanceCash,2)?>
												                                        </td>
											                                        </tr>
													                                <?}?>
													                            </tbody>
													                        </table>
													                    </div>
													                    <?}?>
													                </div>
													            </div>
													        </div>
													        
													        <!--site plan section started-->
													        <?if(!$new){?>
													        <hr>
													        <div class="row">
													            <!--site plan section started-->
													            <div class="col-12">
													            
    											                <div class="card shadow-sm">
                                                                        <div class="card-header">
                                                                            <h3 class="card-title">Site Plans</h3>
                                                                            <div class="card-toolbar">
                                                                                <a href="create_site_plan.php?new=<?$random=random();echo $random?>&quoteId=<?echo $_GET['entryId']?>" class="btn btn-primary btn-sm">Create Site Plan</a>
            													            </div>
                                                                        </div>
                                                                        <div class="card-body">
                                                                            <div class="table-responsive">
                                                                                <table class="table table-rounded table-striped border gs-7 text-center">
        													                    <thead>
        													                        <tr>
        													                            <th>Time Added</th>
        													                            <th>Actions</th>
        													                        </tr>
        													                    </thead>
        													                    <tbody>
        													                        <?
        													                        $quoteId=$_GET['entryId'];
        													                        $sitePlans=getAll($con,"select * from darlelJobber_site_plans where for_id='$quoteId'");
        													                        foreach($sitePlans as $row){?>
        													                        <tr>
        													                            <td><?echo date("d M y",$row['timeAdded'])?></td>
        													                            <td>
        													                                <div class="btn-group">
        													                                    <a class="btn btn-success btn-sm" href="./create_site_plan.php?edit=<?echo $row['id']?>&quoteId=<?echo $row['for_id']?>&view=1&print=1">Print</a>
        													                                    <a class="btn btn-primary btn-sm" href="./create_site_plan.php?edit=<?echo $row['id']?>&quoteId=<?echo $row['for_id']?>&view=1">View</a>
        													                                    <?if(!$view){?>
        														                                <a class="btn btn-warning btn-sm" href="./create_site_plan.php?edit=<?echo $row['id']?>&quoteId=<?echo $row['for_id']?>">Edit</a>
        													                                    <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record" class="btn btn-danger btn-sm" data-url="?delete_site_plan=<?echo $row['id']?>&entryId=<?echo $row['for_id']?>">Delete</a>
        												                                        <?}?>
        													                                </div>
        													                            </td>
        													                        </tr>
        													                        <?}?>
        													                    </tbody>
        												                    </table>
    								                                        </div>
    								                                    </div>
                                                                    </div>
                                                                
                                                                </div>
                                                                <!--site plan section finished-->
													        
													            <!--forms section started-->
												                <div class="col-12 mt-3">
												                <hr id="formsTable">
													        
												                <div class="card shadow-sm">
                                                                    <div class="card-header">
                                                                        <h3 class="card-title">Required Forms</h3>
                                                                        <div class="card-toolbar">
                                                                            <a <?if($formsFilled){?> href="?entryId=<?echo $quoteId?>&sendForm=1&email=1" <?}else{echo $formNotFilledMsg;}?> class="btn btn-primary btn-sm" style="margin-right:10px;">Send Link Via Email</a>
											                                <a <?if($formsFilled){?> href="?entryId=<?echo $quoteId?>&sendForm=1&sms=1" <?}else{echo $formNotFilledMsg;}?> class="btn btn-warning btn-sm" >Send Link Via Text</a>
											                            </div>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <div class="table-responsive">
                                                                            <table class="table table-rounded table-striped border gs-7 text-center">
    													                    <thead>
    													                        <tr>
    													                            <th>Title</th>
    													                            <th>Status</th>
    													                            <th>Actions</th>
    													                        </tr>
    													                    </thead>
    													                    <tbody>
    													                        <?
    													                        $formName=[
    													                            "A"=>"Form A Estimate Checklist",
    													                            "B"=>"Form B Fence Installation Option",
    													                            "C"=>"Form C Terms And Condition",
    													                            "D"=>"Form D Sprinkler Insurance Program",
    													                        ];
    													                        $forms=["A","B","C","D"];
    													                        foreach($forms as $row){
    													                        $form=getRow($con,"select * from darlelJobber_form$row where quoteId='$quoteId'");?>
    													                        <tr>
    													                            <td><?echo $formName[$row]?></td>
    													                            <td>
    													                                <?$color = ($form['submissionStatus']=="Not Submitted")? "warning":"success"; ?>
    													                                <a class="btn btn-<?echo $color?> btn-sm"><?echo $form['submissionStatus']?></a>
    													                            </td>
    													                            <td>
    													                                <div class="btn-group">
    													                                    <a class="btn btn-warning btn-sm" href="./form<?echo $row?>.php?quoteId=<?echo $quoteId?>">Fill Form</a>
    													                                    <a class="btn btn-success btn-sm" href="./form<?echo $row?>.php?quoteId=<?echo $quoteId?>&view=1">View</a>
        													                           </div> 
        													                        </td>
										                                        </tr>
    													                        <?}?>
    													                    </tbody>
    												                    </table>
								                                    </div>
                                                                </div>
                                                            </div>
                                                            </div>
                                                                <!--forms section finished-->
												            
												                <!--logs,notes and reminders section started-->
												                <div class="col-12 mt-3">
                                                                    <div class="card shadow-sm">
                                                                        <div class="card-body pt-0">
                                                                            <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6 mt-10">
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center active" data-bs-toggle="tab" href="#callLogs" style="color: #73141d;" >Call Log</a>
                                                                                </li>
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center" data-bs-toggle="tab" href="#notes" style="color: #73141d;" >Notes</a>
                                                                                </li>
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center" data-bs-toggle="tab" href="#reminders" style="color: #73141d;" >Reminders</a>
                                                                                </li>
                                                                                <li class="nav-item col-4 " style="width: 25%;">
                                                                                    <a class="nav-link text-center" data-bs-toggle="tab" href="#quoteHistory" style="color: #73141d;" >Quote History</a>
                                                                                </li>
                                                                            </ul>
                                                                            
                                                                            <div class="mt-8">
                                                                                <div class="tab-content" id="myTabContent">
                                                                                    <!--call logs section started-->
                                                                                    <div class="tab-pane fade active show" id="callLogs" role="tabpanel">
                                                                                        <div class="card shadow-sm mb-10">
                                                        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                                        										<div class="card-title">
                                                        											Call Logs
                                                        										</div>
                                                        										<div class="card-toolbar">
                                                        										    <a href="callLogs.php?userId=<?echo $session_id?>&quoteId=<?echo $quoteId?>" class="btn btn-primary btn-sm">Add Call Log</a>
    					                                                                        </div>
                                                        									</div>
                                                        									<div class="card-body ">
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
                                                										                <thead>
                                                    										                <tr>
                                                    										                    <th>Description</th>
                                                    										                    <th>Time Added</th>
                                                    										                </tr>
                                                										                </thead>
                                                										                <tbody>
                                                										                    <?$callLogs=getAll($con,"select * from darlelJobber_call_logs where quoteId='$quoteId' order by timeAdded desc");
                                                                                                            foreach($callLogs as $row){?>
                                                										                    <tr>
                                                										                        <td><?echo $row['description']?></td>
                                                										                        <td><?echo date("d M Y",$row['timeAdded'])?></td>
                                                										                    </tr>
                                                										                    <?}?>
                                                										                </tbody>
                                                										            </table>
                                                        										</div>
                                                										    </div>
                                                										</div>
                                                                                    </div>
                                                                                    <!--call logs section finished-->
                                                                                    
                                                                                    <!--notes section started-->
                                                                                    <div class="tab-pane fade" id="notes" role="tabpanel">
                                                                                        <?require("./notes/notes_table.php");?>
                                                                                    </div>
                                                                                    <!--notes section finished-->
                                                                                    
                                                                                    
                                                                                    <!--reminders section started-->
                                                                                    <div class="tab-pane fade" id="reminders" role="tabpanel">
                                                                                        <div class="card shadow-sm mb-10">
                                                        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                                        										<div class="card-title">
                                                        											Reminders
                                                        										</div>
                                                        										<div class="card-toolbar">
                                                        										    <a href="tasks.php?quoteId=<?echo $quoteId?>&userId=<?echo $session_id?>" class="btn btn-success">Add Reminder</a>
    					                                                                        </div>
                                                        									</div>
                                                        									<div class="card-body ">
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
                                                										                <thead>
                                                    										                <tr>
                                                    										                    <td>Subject</td>
                                                    										                    <td>Comments</td>
                                                    										                    <td>Actions</td>
                                                    										                </tr>
                                                										                </thead>
                                                										                <tbody>
                                                										                    <?$reminders=getAll($con,"select * from darlelJobber_tasks where quoteId='$quoteId' order by timeAdded desc");
                                                                                                            foreach($reminders as $row){?>
                                                										                    <tr>
                                                										                        <td><?echo $row['title']?></td>
                                                										                        <td><?echo $row['description']?></td>
                                                										                        <td>
                                                										                            <a href="detailedTaskView.php?taskId=<?echo $row['id']?>" class="text-white badge badge-primary btn-sm me-1">
                                                										                                <i class="text-white bi bi-eye fs-2x"></i>
                                            										                                </a>
                                                										                        </td>
                                                										                    </tr>
                                                										                    <?}?>
                                                										                </tbody>
                                                										            </table>
                                                        										</div>
                                                										    </div>
                                                										</div>
                                                                                    </div>
                                                                                    <!--reminders section finished-->
                                                                                    
                                                                                    <!--quote history section started-->
                                                                                    <div class="tab-pane fade" id="quoteHistory" role="tabpanel">
                                                                                        <div class="card shadow-sm mb-10">
                                                        									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                                        										<div class="card-title">
                                                        											Quote History
                                                        										</div>
                                                        										<div class="card-toolbar"></div>
                                                        									</div>
                                                        									<div class="card-body ">
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-rounded table-striped border gs-7 text-center dataTable">
                                                        										        <thead>
                                                                                                            <tr>
                                                                                                                <th>Title</th>
                                                                                                                <th>Type</th>
                                                                                                                <th>Added By</th>
                                                                                                                <th>Time Added</th>
                                                                                                            </tr>
                                                                                                        </thead>
                                                                                                        <tbody>
                                                                                                            <?$quoteHistory=getAll($con,"select * from darlelJobber_quote_history where quoteId='$quoteId' order by timeAdded desc");
                                                                                                            foreach($quoteHistory as $row){
                                                                                                                if($row['type']=="Call Log")
                                                                                                                    $badge="<a class='badge badge-primary text-white'>".$row['type']."</a>";
                                                                                                                else if($row['type']=="Reminder")
                                                                                                                    $badge="<a class='badge badge-success text-white'>".$row['type']."</a>";
                                                                                                                else if($row['type']=="Quote Changed")
                                                                                                                    $badge="<a class='badge badge-warning text-white'>".$row['type']."</a>";
                                                                                                                else
                                                                                                                    $badge="<a class='badge badge-warning text-white'>".$row['type']."</a>";?>
                                                                                                            <tr>
                                                                                                                <td><?echo $row['title'];?></td>
                                                                                                                <td><?echo $badge;?></td>
                                                                                                                <td><?echo $idToInfo[$row['addedBy']]['name']?></td>
                                                                                                                <td><?echo date("d M Y",$row['timeAdded'])?></td>
                                                                                                            </tr>
                                                                                                            <?}?>
                                                                                                        </tbody>
                                                                                                    </table>
                                                            									</div>
                                                										    </div>
                                                										</div>
                                                                                    </div>
                                                                                    <!--reminders section finished-->
                                                                                    
                                                                                    
                                                                                    
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!--logs,notes and reminders section finished-->
												                
												            </div>
                                                            <?}?>
												    
												</div>
											</div>
										</div>
									</div>
								<div></div>
							</form>
							</div>
						</div>
					</div>
					
					
					<div class="footer py-4 d-flex flex-lg-column" id="kt_footer" style="position: fixed;bottom: 0;width: 100%;">
					    <div class="row">
					        <div class="col-12">
        					    <div class="row">
        					        <div class="col-sm-2 col-md-3 col-2"></div>
            					    <div class="col-sm-8 col-md-4 col-8">
            					        <?if($quoteDeets['status']=="Approved" || $session_role!="Client"){
								            $hide="style='display:none';";}?>
            					        <a <?echo $hide?> class="btn btn-warning btn-sm mr-10 w-100" data-bs-toggle="modal" data-bs-target="#requestChange">Request Changes</a>
            			                <a <?echo $hide?> id="approveQuoteButton" class="btn btn-success btn-sm mr-10 w-100" data-bs-toggle="modal" data-bs-target="#approveQuote">Approve Quote</a>
            			            </div>
            					</div>
        					</div>
        					<div class="col-12">
        					    <div class="container-fluid d-flex flex-column flex-md-row align-items-center justify-content-between">
        							<div class="text-dark order-2 order-md-1">
        								<span class="text-muted fw-bold me-1">2022©</span>
        								<a href="https://projects.anomoz.com/ke/siteManager/" target="_blank" class="text-gray-800 text-hover-primary"><?echo $projectName?></a>
        							</div>
        						</div>
    						</div>
						</div>
					</div>
					
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
            
            function updateSmallDetails(){
                <?if($edit){?>
                var title= $("input[name='title']").val();
                var projectName= $("input[name='projectName']").val();
                var required_811= $("input[name='required_811']").is(':checked');
                var cashOnly= $("input[name='cashOnly']").is(':checked');
                var complete_payment= $("input[name='complete_payment']").is(':checked');
                var displayPricing= $("input[name='displayPricing']").is(':checked');
                var estimatorId= $("select[name='estimatorId']").val();
                
                required_811 = (required_811) ? "on" : "off";
                complete_payment = (complete_payment) ? "on" : "off";
                displayPricing = (displayPricing) ? "on" : "off";
                cashOnly = (cashOnly) ? "on" : "off";
                
                $.post("updateQuote.php",
                {
                    quoteId: "<?echo $quoteId?>",
                    title: title,
                    projectName:projectName,
                    required_811: required_811,
                    complete_payment:complete_payment,
                    cashOnly:cashOnly,
                    displayPricing:displayPricing,
                    estimatorId:estimatorId
                },
                function(){
                    console.log("Updated Quote Successfully");
                });
                <?}?>
            }
                
            
                $(document).ready(function(){
                    document.addEventListener("keydown", function(event) {
                        if (event.key === "Enter" && event.target.tagName !== "TEXTAREA") {
                            event.preventDefault();
                        }
                    });
                    
                    <?if($session_role!="Admin" && $session_role!="Sales Admin"){?>
                    const checkbox = document.getElementById('completePaymentCheckbox');
                    checkbox.addEventListener('click', function(event) {
                        event.preventDefault();
                    });
                    <?}?>
                    
                    
                    <?if($view){?>
                    $("#quoteForm :input").prop("disabled", true);
                    <?}?>
                    <?if(isset($_GET['viewSign'])){?>
                        $("#approveQuoteButton")[0].click();
                    <?}?>
														            
                  $("#delete_record").on('show.bs.modal', function (e) {
                    //get data-id attribute of the clicked element
                    var url = $(e.relatedTarget).data('url');
                    console.log("modal opened", name)
                    //populate the textbox
                     $("#delete-project").attr("href", url);
                
                  });
                });
                
                //if client name is not selected
        	    $( "#quoteForm" ).submit(function( event ) {
                  var quote_for=$("input[name='quote_for']").val()
                  if(quote_for==""){
                      alert("Client name is required before form submission");
                      event.preventDefault();
                  }
                  else{
                    $(this).find(':submit').css('pointer-events', 'none');
                  }
                });
            </script>  
	
	
		</div>
	</body>
	
	<script>
	
	var allServices=<?echo json_encode($allServices);?>;
    function fillPerUnitCost(divId){
	        
             var serviceId=$("."+divId+" input[name='service[]']").val()
             var unitPrice=allServices[serviceId]['price'];
             var filledUnitPrice=$("."+divId+" input[name='unit_price[]']").val();
             var description=allServices[serviceId]['description'];
             var image=allServices[serviceId]['image'];
             
             $("."+divId+" input[name='images[]']").hide();
             $("."+divId+" img[name='showImage[]']").attr("src", "./servicesImages/"+image);
             $("."+divId+" input[name='helperFile[]']").val(image);
             var helper=$("."+divId+" input[name='helperFile[]']").val();
             
             $("."+divId+" textarea[name='description[]']").val(description);
             /*if(filledUnitPrice=="" || filledUnitPrice==null || filledUnitPrice==0){
                $("."+divId+" input[name='unit_price[]']").val(unitPrice);
                $("."+divId+" input[name='total[]']").val("0");
             }*/
             $("."+divId+" input[name='unit_price[]']").val(unitPrice);
                
             
            calculateTotal(divId);
        }
            
    function calculateTotal(divId){
        var unit_price=$("."+divId+" input[name='unit_price[]']").val()
        var qty=$("."+divId+" input[name='qty[]']").val();
        var total=unit_price*qty;
        total=total.toFixed(2);
        $("."+divId+" input[name='total[]']").val(total);
        
        calculateFinalTotal()
    }
    function calculateFinalTotal(){
        var totalPrice=0;
        $('input[name^="total"]').each( function() {
            var divId = $(this).closest('tr').attr('class');
            var optionalApproveStatus=$("."+divId+" input[name='optionalApproveStatus[]']").val();
            var optionalStatus=$("."+divId+" input[name='optionalStatus[]']").val();
            if(optionalApproveStatus=="Yes" || optionalStatus=="No" ){
                totalPrice += parseFloat(this.value);
            }
        });
        totalPrice = totalPrice.toFixed(2);
        $("input[name='subtotal']").val(totalPrice);
        var discount=$("input[name='discount']").val();
        var discountType=$("select[name='discountType']").val();
        var discountedAmount=0;
        if(discountType=="Percentage"){
            discountedAmount=totalPrice*(discount/100);
            discountedAmount = discountedAmount.toFixed(2);
        }
        else if(discountType=="Amount")
            discountedAmount=discount;
        $('#discountedAmount').html(' = $'+discountedAmount)
        
        var discountedPrice=totalPrice-discountedAmount;
        discountedPrice=discountedPrice.toFixed(2)
        $("input[name='final_total']").val(discountedPrice);
        
        
        //required deposit amount
        var paidAmount=<?echo $paidAmountWithDiscount?>;
        var requiredDepositAmount=0;
        var requiredDepositType=$("select[name='requiredDepositType']").val();
        if(requiredDepositType=="Percentage"){
            var required_deposit=$("input[name='required_deposit']").val()
            required_deposit=discountedPrice*(required_deposit/100);
            requiredDepositAmount=required_deposit;
            requiredDepositAmount=requiredDepositAmount-paidAmount;
            requiredDepositAmount = requiredDepositAmount.toFixed(2);
        }
        else if(requiredDepositType=="Amount"){
            var required_deposit=$("input[name='required_deposit']").val();
            requiredDepositAmount=required_deposit;
            requiredDepositAmount=requiredDepositAmount-paidAmount;
        }
        
        $('#required_deposit').html(' = $'+requiredDepositAmount)
        
        
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
    function addRow(){
        var id=makeid(5);
        var string=`
	    <tr id="`+id+`" class="`+id+`">
		    <td>
		        <input name="optionalApproveStatus[]" value="No" hidden>
    	        <input name="serviceId[]" value="None" hidden>
    	        <input name="optionalStatus[]" value="No" hidden>
                <input onfocusout="fillPerUnitCost('`+id+`')" type="text" class="form-control" name="service[]"  style="width: 550;">
                <textarea class="form-control" placeholder="Description" name="description[]" rows="3" style="width: 550;"></textarea>
            </td>   
            <td>
                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="0" style="width: 150;">
	        </td>
	        <td>
                <img style="height: 100px;width:100px;margin: 10px;" src="" alt="img" name="showImage[]">
	            <a onclick="removeImage('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
                <input type="file" name="images[]" style="width:250px;" class="form-control" >
                <input type="text" name="helperFile[]" class="form-control" hidden>
                <input type="text" name="type[]" hidden>
			</td>
			<td>
                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="0" style="width: 200;">
            </td>
	        <td>
                <input class="form-control" type="number" step="0.01" name="total[]" readonly placeholder="Total" value="0" style="width: 200;">
            </td>
	        <td class="text-center">
                <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
            </td>
       </tr>`;
		$('#quote_section').append(string);
		$("."+id+" input[name='service[]']").autocomplete({
            source: function(request, response) {
              var words = request.term.split(" ");
              var pattern = $.map(words, function(word) {
                return "(?=.*" + $.ui.autocomplete.escapeRegex(word) + ")";
              }).join("");
              var matcher = new RegExp(pattern, "i");
              var filteredTags = $.grep(availableTags, function(value) {
                value = value.label || value.value || value;
                return matcher.test(value.toLowerCase());
              });
              response(filteredTags);
            }
          }).autocomplete("widget").addClass("scrollable-autocomplete");
    }
    
    function addOptional(){
        var id=makeid(5);
        var string=`
				    <tr id="`+id+`" class="`+id+`">
					    <td>
				            <input name="serviceId[]" value="None" hidden>
    	                    <input name="optionalApproveStatus[]" value="No" hidden>
    	                    <input name="optionalStatus[]" value="Yes" hidden>
    	                    <input onfocusout="fillPerUnitCost('`+id+`')" type="text" class="form-control" name="service[]"  style="width: 550;">
    	                    <textarea class="form-control" placeholder="Description" name="description[]" rows="3" style="width: 550;"></textarea>
    		            </td>   
    		            <td>
    		                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="0" style="width: 150;">
    			            <a class="btn btn-warning btn-sm mt-10">Optional Line Item Excluded</a>
    			        </td>
    			        <td>
    		                <img style="height: 100px;width:100px;margin: 10px;" src="" alt="img" name="showImage[]">
    			            <a onclick="removeImage('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
    		                <input type="file" name="images[]" style="width:250px;" class="form-control" >
    		                <input type="text" name="helperFile[]" class="form-control" hidden>
    	                    <input type="text" name="type[]" hidden>
    					</td>
    					<td>
    		                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="0" style="width: 200;">
    		            </td>
    			        <td>
    		                <input class="form-control" type="number" step="0.01" name="total[]" readonly placeholder="Total" value="0" style="width: 200;">
    		            </td>
    			        <td class="text-center">
    		                <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
    		            </td>
		           </tr>`;
		$('#quote_section').append(string);
        	$("."+id+" input[name='service[]']").autocomplete({
            
      source: function(request, response) {
      var words = request.term.split(" ");
      var pattern = $.map(words, function(word) {
        return "(?=.*" + $.ui.autocomplete.escapeRegex(word) + ")";
      }).join("");
      var matcher = new RegExp(pattern, "i");
      var filteredTags = $.grep(availableTags, function(value) {
        value = value.label || value.value || value;
        return matcher.test(value.toLowerCase());
      });
      response(filteredTags);
    },
        	    
        	}).autocomplete("widget").addClass("scrollable-autocomplete");
    }
    
    function removeRow(id){
        $('#'+id).remove();
        $("input[name='subtotal']").val("0");
        $("input[name='final_total']").val("0");
        calculateFinalTotal();
    }
	</script>
	
	<!--select or create a client modal-->
	<div class="modal fade" id="client_name" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-650px" style="max-width: 900px !important;">
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
							    <div class="row">
							        <div class="col-7">
							            <h1>Select OR Create A Client</h1>
							        </div>
							        <div class="col-5" style="text-align: right;">
							            <a href="./addClient.php?add_client=1&new=1&page=createQuote" class="btn btn-primary btn-sm me-2">Create New Client</a>
							            <a id="addContactBtn" <?if($edit){echo "href='./addClient.php?customerId=".$quoteDeets['customerId']."&page=createQuote&addContact=1&redirect=1'";}else{echo "style='display:none;'";}?> 
							            class="btn btn-primary btn-sm">Add Contact</a>
							        </div>
							    </div>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Select Client</span>
								</label>
								<select id="select2insidemodal" class="form-select" data-control="select2" data-placeholder="Select an option" onchange="updateInfo()" name="client">
								    <option selected disabled>---Select Client---</option>
								    <?foreach($clients as $row){?>
								        <option <?if((($quoteDeets['customerId']==$row['id']) && (!isset($_GET['customerId']))) || ($_GET['customerId']==$row['id'])){echo "selected";}?> value="<?echo $row['id']?>">
								            <?
									        if($row['showCompanyName']=="Yes")
								                echo  $row['company_name']." (".$row['first_name']." ".$row['last_name'].")";
                                            else
									            echo $row['first_name']." ".$row['last_name'];
									        ?>
                                        </option>
								    <?}?>
								</select>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">Select Contact</label>
								<select class="form-control" name="contactId" onchange="updateContactInfo()">
								    <option selected>-- Select Contact --</option>
								    <?foreach($allContacts as $row){?>
								    <option style="display:none;" <?if($row['id']==$quoteDeets['contactId']){echo "selected";}?> class="<?echo $row['customerId']?>" value="<?echo $row['id']?>"><?echo $row['name']?></option>
								    <?}?>
								</select>
							</div>
							<a  id="closeModal" data-bs-dismiss="modal" hidden></a>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
	    $(document).ready(function() {
          $("#select2insidemodal").select2({
            dropdownParent: $("#client_name")
          });
        });
        
		    var idToInfo=<?echo json_encode($idToInfo);?>;
		    var properties=<?echo json_encode($properties);?>;
		    var idToContactDetails=<?echo json_encode($idToContactDetails);?>;
	        
	        
	        function updateContactInfo(){
		        console.log("executed");
		        $("#closeModal")[0].click();
		        $('#accountContactDetails').empty();
		        
		        var contactId=$("select[name='contactId']").val();
		        var contactName = $("select[name='contactId']").find(":selected").text();
                $("input[name='contactId']").val(contactId);
                $('#accountContactDetails').append(contactName+"<br>");
		        
		        var contactDetails=idToContactDetails[contactId];
		        if(contactDetails!=null){
    		        for(var i=0;i<contactDetails.length;i++)
    		            $('#accountContactDetails').append(contactDetails[i]+"<br>");
		        }
		    }
		    
		    function updateInfo(){
		        $('#addContactBtn').show();
		        var userId=$("select[name='client']").val();
		        $('#addContactBtn').attr('href', 'addClient.php?customerId='+userId+'&page=createQuote&addContact=1&redirect=1');
		        $('#change_property').empty();
		        $('#contactDetails').empty();
		        $("select[name='contactId'] option").hide();
		        $("select[name='contactId'] option."+userId).show();
		        
		        $("input[name='quote_for']").val(userId);
		        var userInfo=idToInfo[userId];
		        var allProperties=properties;
		        
		        //client name appending
		        var title="";
		        if(userInfo['title']=="No Title"){title="";}else{title=userInfo['title'];}
		        
		        var clientName = title.concat(" ", userInfo['first_name']," ", userInfo['last_name']);
		        var companyName=userInfo['company_name'];
                companyName = companyName.concat(" (", userInfo['first_name']," ", userInfo['last_name'],")");
                
                if(userInfo['showCompanyName']=="Yes")
                    $('#clientName').text(companyName);
                else
                    $('#clientName').text(clientName);
                
                //client property appending
                for(var i=0;i<allProperties.length;i++){
                    if(allProperties[i]['userId']==userId && <?if($new){?> allProperties[i]['type']=="primary" <?} else {?>  allProperties[i]['id']=="<?echo $quoteDeets['propertyId']?>" <?}?>){
                        $('#street1').text(allProperties[i]['street1']);
                        $('#street2').text(allProperties[i]['street2']);
                        $('#country').text(allProperties[i]['country']);
                        $('#city').text(allProperties[i]['city']);
                        $('#state').text(allProperties[i]['state']);
                        $('#zip_code').text(allProperties[i]['zip_code']);
                        $("input[name='propertyId']").val(allProperties[i]['id']);
                        break;
                    }
                }
                <?if(!$view){?>
                $('#change_property').append(`<a style="margin-right: 10px;" onclick="editProperty()" href="#" >Edit</a>`);
                $('#change_property').append(`<a id="changePropertyBtn" href="#" data-bs-toggle="modal" data-bs-target="#changeProperty">Change</a>`);
                <?}?>
                
                //contact details appending
                var contact_type = userInfo['contact_type'].split("*");
                var phone = userInfo['phone'].split("*");
                for(var i=0;i<contact_type.length;i++){
                    var phoneNumber = phone[i] ? phone[i] : "N/A";
                    if(phoneNumber=="N/A")
                        continue;
                    string=`<p>`+contact_type[i]+` :  `+phone[i]+`</p>`
                    $('#contactDetails').append(string);
                }
                
                var email_type = userInfo['email_type'].split("*");
                var email = userInfo['email'].split("*");
                for(var i=0;i<email_type.length;i++){
                    var fullEmail = email[i] ? email[i] : "N/A";
                    if(fullEmail=="N/A")
                        continue;
                    string=`<p>`+email_type[i]+` :  `+email[i]+`</p>`
                    $('#contactDetails').append(string);
                }
            }
		</script>
		
		
	
	<div class="modal fade" id="changeProperty" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-650px">
				<div class="modal-content rounded">
					<div class="modal-header pb-0 border-0 justify-content-end">
						<a id="closeChangePropertyModal" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<span class="svg-icon svg-icon-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
									<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
								</svg>
							</span>
						</a>
					</div>
					
					<div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
						<form action="" method="post" enctype="multipart/form-data">
						    <div class="mb-13 text-left">
							    <div class="row">
							        <div class="col-9">
							            <h1>Change Property</h1>
							        </div>
							        <div class="col-3">
							            <a onclick="addProperty()" class="btn btn-primary btn-sm">Add Property</a>
							        </div>
							    </div>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Select Property</span>
								</label>
								
								<select id="propertyOptions" onchange="updateProperty()" class="form-control" name="property_id">
								</select>
							</div>
							<a id="closeModalProperty" data-bs-dismiss="modal" hidden></a>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
		    
	    $(document).ready(function(){
	        /*when quote is submitted and sending options is selected started*/
	        <?if($_GET['triggerModal']=="email"){?>
    	        $("#sendEmailBtn")[0].click();
            <?}else if($_GET['triggerModal']=="sms"){?>
    	        $("#sendSmsBtn")[0].click();
            <?}if(isset($_GET['entryId'])){
                echo "updateInfo();updateContactInfo();";
            }?>
	        /*when quote is submitted and sending options is selected finished*/
	        
	        
	        <?if(isset($_GET['customerId'])){?>
	            updateInfo();
		    <?}
		    if(isset($_GET['contactId'])){?>
		        $("select[name='contactId']").val("<?echo clear($_GET['contactId'])?>");
		        updateContactInfo();
		    <?}?>
	        calculateFinalTotal();
	        $("input[name='service[]']").autocomplete({
            
              source: function(request, response) {
              var words = request.term.split(" ");
              var pattern = $.map(words, function(word) {
                return "(?=.*" + $.ui.autocomplete.escapeRegex(word) + ")";
              }).join("");
              var matcher = new RegExp(pattern, "i");
              var filteredTags = $.grep(availableTags, function(value) {
                value = value.label || value.value || value;
                return matcher.test(value.toLowerCase());
              });
              response(filteredTags);
            },
	            
	        });
	    
	    $("#changeProperty").on('show.bs.modal', function (e) {
            $('#propertyOptions').empty();
            var userId=$("select[name='client']").val();
            var allProperties=properties;
            
            var string=`<option selected disabled>---Select Property---</option>`;
            $('#propertyOptions').append(string);
            
            for(var i=0;i<allProperties.length;i++)
            {
                if(allProperties[i]['userId']==userId)
                {
                    var option=`<option value="`+allProperties[i]['id']+`">`+allProperties[i]['street1']+`,`+allProperties[i]['street2']+` `+allProperties[i]['city']+`,
                    `+allProperties[i]['state']+`,`+allProperties[i]['country']+`
                    </option>`;
                    $('#propertyOptions').append(option);
                }
            }
        });
	    });
	    function updateProperty()
	    {
	        var allProperties=properties;
	        var property_id=$("select[name='property_id']").val();
	        $("input[name='propertyId']").val(property_id);
        
	        for(var i=0;i<allProperties.length;i++)
            {
                if(allProperties[i]['id']==property_id)
                {
                    $('#street1').text(allProperties[i]['street1']);
                    $('#street2').text(allProperties[i]['street2']);
                    $('#country').text(allProperties[i]['country']);
                    $('#city').text(allProperties[i]['city']);
                    $('#state').text(allProperties[i]['state']);
                    $('#zip_code').text(allProperties[i]['zip_code']);
                    $("#closeModalProperty")[0].click();
                    break;
                }
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
		</script>
	
	<div class="modal fade" id="requestChange" tabindex="-1" aria-hidden="true">
			
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
					            <h1 class="mb-3" id="modelTitle">Request Changes</h1>
                            </div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Changes Description</span>
								</label>
								<textarea name="changes_description" class="form-control" placeholder="Changes Description" rows="10"></textarea>
							</div>
						    <div class="text-center">
								<input type="submit" value="Save" name="requestChange" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	
	<div class="modal fade" id="approveQuote" tabindex="-1" aria-hidden="true">
			
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
					            <h1 class="mb-3" id="modelTitle">Approve Quote</h1>
                            </div>
							<div class="d-flex flex-column mb-8 fv-row">
							    <div class="sigPad" id="smoothed1" style="width:100%;">
                                    <?if(!isset($_GET['viewSign'])){?>
                                    <ul class="sigNav">
                                    <li class="clearButton btn"><a href="#clear">Clear</a></li>
                                    </ul>
                                    <?}?>
                                    <div class="sig sigWrapper" style="height:auto;">
                                    <div class="typed"></div>
                                    <canvas class="pad" width="550" height="160" style="border: 1px solid #ccc !important;"></canvas>
                                    <input type="hidden" name="client_approve_sign" class="output">
                                    </div>
                                </div>
							</div>
						    <div class="text-center">
								<?if(!isset($_GET['viewSign'])){?>
                                <input type="submit" value="Save" name="approveQuote" class="btn btn-primary">
							    <?}?>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		<script>
		function addProperty(){
		    var customerId=$("input[name='quote_for']").val();
		    var quoteId="<?echo ($new) ?  "newQuote" : $quoteId ?>";
	        window.location.href ='view_client.php?id='+customerId+'&quoteId='+quoteId+'&action=addPropertyFromQuote';
        }
        function editProperty(){
            var customerId=$("input[name='quote_for']").val();
            var propertyId=$("input[name='propertyId']").val();
            var quoteId="<?echo ($new) ?  "newQuote" : $quoteId ?>";
            window.location.href ='view_client.php?id='+customerId+'&quoteId='+quoteId+'&action=editPropertyFromQuote&propertyId='+propertyId;
        }
        function editContactDetails(){
            var customerId=$("input[name='quote_for']").val();
            var quoteId="<?echo $quoteId ?>";
            window.location.href ='addClient.php?customerId='+customerId+'&action=editFromQuote&quoteId='+quoteId;
        }
		
    $(document).ready(function() {
      $('#smoothed1').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:200 <?if(isset($_GET['id'])){?>, displayOnly:true<?}?>})<?if(isset($_GET['viewSign'])){?>.regenerate(<?echo $quoteDeets['client_approve_sign']?>);<?}else{?>;<?}?>
    
        <?if(isset($_GET['propertyId'])){?>
        
        $("#changePropertyBtn")[0].click();
        $("select[name='property_id']").val("<?echo clear($_GET['propertyId'])?>");
        $("input[name='propertyId']").val("<?echo clear($_GET['propertyId'])?>");
        updateProperty();
        $("#closeChangePropertyModal")[0].click();
        setTimeout(function() {
            $("#closeChangePropertyModal")[0].click();
        }, 500); // 3000 milliseconds (3 seconds) delay
        <?}?>
        
    });
    
</script>
		<?require("./notes/notes_js.php");
    	require("./emailsAndSms/multipleSmsModal.php");
    	require("./emailsAndSms/multipleEmailModal.php");
    	require("./collectPayment/collectPayment.php");?>
</html>
