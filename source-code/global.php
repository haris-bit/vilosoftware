<?
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 100);
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 100);
ini_set('session.save_path', '/tmp');


$g_currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$g_website = dirname($g_currentUrl);;
$projectName="Vilo Fence Accelator";

// error_reporting(E_ALL);
// Report simple running errors
error_reporting(E_ERROR);
ini_set('display_errors', '1');

session_start();
include_once("database.php");
//include_once("./includes/core/dbmodel.php");
include_once("roles.php");

$shrinkMenu=1;
$bodyBgColor=' style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px;background-color:#ca836f";';
$shrinkMenuAttr='data-kt-aside-minimize="on" '.$bodyBgColor;



$filenameLink = basename($_SERVER['PHP_SELF']);
$projectUrl="https://projects.anomoz.com/ke/darlelJobber/";


//running query function
function runQuery($query){
    global $con;
    $result=$con->query($query);
    if(!$result){
        $error_code = $con->errno;
        if ($error_code === 1062) {
            echo $query."<br>";
            echo "Action Failed . Kindly try again ";
            exit();
            /*$newPrimaryKey=random();
            $query = preg_replace("/id='[^']*'/", "id='$newPrimaryKey'", $query);
            runQuery($query);*/
        }
        else{
            echo $query."<br>";
            echo $con->error."<br>";
            exit();
        }
    }
}

function getAll($con,$sql){
    runQuery($sql);
	$result = $con->query($sql);
    $list = array();
    while ($row = mysqli_fetch_assoc($result)){
        $list[] = $row;
    }
    return $list;
}

function runQueryReturn($query){
    global $con;
    $result=$con->query($query);
    if(!$result){
        echo $query."<br>";
        echo $con->error;
        exit();
    }
    return $result;
}

//index mapping-> 0 = admin,
if (isset($_SESSION['email'])&&isset($_SESSION['password'])){
    
    $session_password = $_SESSION['password'];
    $session_email =  $_SESSION['email'];
    $query = "SELECT *  FROM darlelJobber_users WHERE email='$session_email' AND password='$session_password'";
    runQuery($query);
    $result = $con->query($query);
    if ($result->num_rows > 0){
        while($row = $result->fetch_assoc()) 
        {
            $logged=1;
            $session_id = $row['id'];
            $session_password = $row['password'];
            $session_name = $row['name'];
            $session_email = $row['email'];
            $session_role = $row['role'];
            $session_data = $row;
            
            $permission['edit_everyone_schedule']=0;
            $permission=$permissions[$session_role];
            if($session_role=="Admin")
                $isAdmin=true;
        }
    }
    else
        $logged=0;
}
else
    $logged=0;


if(isset($_GET['logout'])){
    session_destroy();
    header("Location:./index.php");
}
if(isset($_SESSION['clientId'])){
    $id=$_SESSION['clientId'];
    $query="select * from darlelJobber_users where id='$id'";
    runQuery($query);
    $result=$con->query($query);
    while($row=$result->fetch_assoc()){
        $logged=1;
        $session_id = $row['id'];
        $session_password = $row['password'];
        $session_name = $row['name'];
        $session_email = $row['email'];
        $session_role = $row['role'];
        $session_data = $row;
    }
    //$sessionDeets=getRow($con,"select * from darlelJobber_users where id='$id'");
    
}

function mb_htmlentities($string, $hex = true, $encoding = 'UTF-8') {
    global $con;
    return mysqli_real_escape_string($con, $string);
}


function clear($string, $hex = true, $encoding = 'UTF-8') {
    global $con;
    return mysqli_real_escape_string($con, $string);
}

function generateRandomString($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function random($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function storeFile($file){
	$target_dir = "./uploads/";
	$target_file = $target_dir.basename($file["name"]);
	
	if (move_uploaded_file($file, $target_file)) {
        $successfull=1;
	} else {
		echo "failed";
		exit();
	}
}

function sendEmailNotification_mailjet($subject, $message, $email, $containsAttachment=0, $attachments="none"){
    
    
    if($attachments=="none")
        $attachmentArray = [];
    else{
        $content_type = $attachments['ContentType'];
        $file_name = $attachments['Filename'];
        $base_content = $attachments['Base64Content'];
    
        $attachmentArray = [[
                'ContentType' => $content_type,
                'Filename' => $file_name,
                'Base64Content' => $base_content,
                ]
        ];
    }
    
    global $g_projectTitle;
    $ch = curl_init();
    
    //$from = "customer@vilosoftware.com";
    //$from = "dev.email.sender2@anomoz.com";
    $from="donotreply@vilosoftware.com";
    
    if(true){
        $vars = json_encode(array (
  'Messages' => 
  array (
    0 => 
    array (
      'From' => 
      array (
        'Email' => $from,
        'Name' => "Vilo Fence",
      ),
      'To' => 
      array (
        0 => 
        array (
          'Email' => $email,
          'Name' => 'Receiver',
        ),
      ),
      'Subject' => $subject,
      'TextPart' => $message,
      'HTMLPart' => $message,
      'CustomID' => 'AppGettingStartedTest',


      'Attachments' => $attachmentArray,
    ),
  ),
), true);
    
    }
    
    
    curl_setopt($ch, CURLOPT_URL,"https://api.mailjet.com/v3.1/send");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$vars);  //Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        'X-Apple-Tz: 0',
        'X-Apple-Store-Front: 143444,12',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Encoding: gzip, deflate',
        'Accept-Language: en-US,en;q=0.5',
        'Cache-Control: no-cache',
        'Content-Type: application/json; charset=utf-8',
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
        'X-MicrosoftAjax: Delta=true',
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, "027d3006ac122f27ae101b753c952b00:a21b983c1d22556e7d21b72095898d2e");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    $server_output = curl_exec ($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);
    
    if($httpcode != 200){
        echo $server_output;
        exit();
    }

}


function setNotification($title,$description,$userId,$url){
    global $con;
    $id=generateRandomString();
    $timeAdded=time();
    $query="insert into darlelJobber_notifications set id='$id',title='$title',description='$description',userId='$userId',url='$url',timeAdded='$timeAdded'";
    runQuery($query);
    //$userDeets=getRow($con,"select * from darlelJobber_users where id='$userId'");
    //sendEmailNotification_mailjet($title, $description, $userDeets['email']);
}

//home page redirection
if($filenameLink=="home.php" && $session_role!="Client"){
    						        
    $links=array(
        "Admin"=>"analytics.php",
        "Front Office"=>"schedule.php",
        "Estimator"=>"schedule.php",
        "Sales Admin"=>"schedule.php",
        "Scheduling Supervisor"=>"schedule.php",
        "Drafter"=>"tasks.php",
        "Shop Admin"=>"tasks.php",
        "Welder"=>"tasks.php",
        "Installation Crew"=>"jobs.php",
        "Crew Supervisor"=>"jobs.php",
        "Accounting"=>"invoices.php",
        "Material Drafter"=>"tasks.php"
    );
    $links=$links[$session_role];
    if($links!="")
        header("Location:./$links");
    else
        header("Location:./notifications.php");
}

require_once __DIR__ . '/Twilio/autoload.php';
use Twilio\Rest\Client;

function sendansms($phonenumber,$message){

    $smsErrMsg = null;
    try{
      // Your Account SID and Auth Token from twilio.com/console
        $sid = 'AC2427f54f20385b3ec0b286bd0bf1547c';
        $token = 'ce50a787f74d628ee1554bff61715490';
        $client = new Client($sid, $token);
        
        // Use the client to do fun stuff like send text messages!
        $client->messages->create(
            // the number you'd like to send the message to
            $phonenumber,
            array(
                // A Twilio phone number you purchased at twilio.com/console
                'from' => '+18135900065', 
                // the body of the text message you'd like to send
                'body' => $message
            )
        );
        return "SMS Sent Successfully.";
    }catch(Exception $e){
        echo "Provided Phone Number :".$phonenumber."<br>";
        $smsErrMsg = $e->getMessage();
        echo $smsErrMsg;
        exit();
    }
    return $smsErrMsg;
}

function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
}



$g_stripeCred = array(
    "private_test_key" => "sk_test_51Gz0OOHQjkfG1DwOmJD0AsqrZDQ6vG6oMb28V0WEjlTsuZQSFS5kqb5rr60BIeOgeqobp7XAK7IsOh4gVsrEyKl700IoFt2lJZ",
    "public_test_key"  => "pk_test_51Gz0OOHQjkfG1DwO9latQvA69lF4SGM6jl1DiHgWI5gkzHvI4XqlMDHDw3kQxHPZEJIZlGxxOBufbdfAPAOjVM1500HcxE0VZ2",
    "productCode" => 'prod_IfkGNQQX9TKWK9'
);
/*
$g_stripeCred = array(
    "private_test_key" => "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",
    "public_test_key"  => "pk_live_51LT1bTDlCE0FHVPkclCJjTUK6wnsOqqEQbUVW6NJgnqiayNpIOz6sApSYWy9dTn0xdqzZZOfSND3sG5JiZjQtqqg00ymOE8D3M",
    "productCode" => 'prod_OC8mMnjkfkyCFz'
);*/



function getRow($con,$sql){
	runQuery($sql);
	if ($result = $con->query($sql)) {
        $row = mysqli_fetch_assoc($result);
        return $row;
    } else {
        return false;
    }
}

//this updates the required deposit, discount, and total of the given quote
function updateQuote($quoteId){
    global $con;
    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
    $total=getRow($con,"select sum(total) as totalAmount from darlelJobber_quote_details where (optionalStatus='No' || optionalApproveStatus='Yes') && quoteId='$quoteId'")['totalAmount'];    
    
    $discount=$quoteDeets['discount'];
    $discountType=$quoteDeets['discountType'];
    $requiredDeposit=$quoteDeets['required_deposit'];
    
    $total= ($total=="") ? 0 : $total;
    $subtotal=$total;
    $discountedAmount = ($discountType=="Amount") ? ($subtotal-$discount) : round( $subtotal - ($subtotal*($discount/100)) ,2);
    if($quoteDeets['requiredDepositType']=="Amount")
        $requiredDepositAmount=$requiredDeposit;
    else if($quoteDeets['requiredDepositType']=="Percentage")    
        $requiredDepositAmount=round($discountedAmount*($requiredDeposit/100),2);
    runQuery("update darlelJobber_quotes set total='$discountedAmount',subtotal='$subtotal',requiredDepositAmount='$requiredDepositAmount' where id='$quoteId'");
    
    //checking if the quote is paid or not started
    $paidAmount=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where quoteId='$quoteId'")['paidAmount'];
    if(($paidAmount >= $requiredDepositAmount) && ($quoteDeets['paidStatus']!="Paid") && ($paidAmount!="")){
        $time=time();
        runQuery("update darlelJobber_quotes set paidStatus='Paid',paidDate='$time' where id='$quoteId'");
    }
    //checking if the quote is paid or not finished
    
    
    //updating respective job
    if($quoteDeets['jobId']!="None")
        updateJob($quoteDeets['jobId']);
    if($quoteDeets['invoiceId']!="None")
        updateInvoice($quoteDeets['invoiceId']);
}

//this updates the total of job started
function updateJob($jobId){
    global $con;
    $jobDeets=getRow($con,"select * from darlelJobber_jobs where id='$jobId'");
    $quoteId=$jobDeets['quoteId'];
    $total=getRow($con,"select sum(total) as totalAmount from darlelJobber_quote_details where (optionalStatus='No' || optionalApproveStatus='Yes') && quoteId='$quoteId'")['totalAmount'];    
    runQuery("update darlelJobber_jobs set total='$total' where id='$jobId'");
}
//this updates the total of job finished


//this updates the sub-total,total,paid status of invoice started
function updateInvoice($invoiceId){
    global $con;
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where id='$invoiceId'");
    $quoteId=$invoiceDeets['quoteId'];
    $subtotal=getRow($con,"select sum(total) as totalAmount from darlelJobber_quote_details where (optionalStatus='No' || optionalApproveStatus='Yes') && quoteId='$quoteId'")['totalAmount'];
    $discountType=$invoiceDeets['discountType'];
    $discount=$invoiceDeets['discount'];
    $discountedAmount = ($discountType=="Amount") ? ($subtotal-$discount) : round( $subtotal - ($subtotal*($discount/100)) ,2);
    
    $paidAmountInQuote=getRow($con,"select sum(amountPaid+discountAvailed) as paidAmountInQuote from darlelJobber_payments where quoteId='$quoteId'")['paidAmountInQuote'];
    if($paidAmountInQuote=="")
        $paidAmountInQuote=0;
    $discountedAmount=round($discountedAmount-$paidAmountInQuote,2);
    runQuery("update darlelJobber_invoices set subtotal='$subtotal',total='$discountedAmount' where id='$invoiceId'");
    
    
    //checking if the invoice is paid or not started
    $paidAmount=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where invoiceId='$invoiceId'")['paidAmount'];
    if(($paidAmount >= $discountedAmount) && ($invoiceDeets['paidStatus']!="Paid") && ($paidAmount!="")){
        $time=time();
        runQuery("update darlelJobber_invoices set paidStatus='Paid',paidDate='$time' where id='$invoiceId'");
    }
    //checking if the invoice is paid or not finished
    
}
//this updates the sub-total,total,paid status of invoice finished

function varDump($myVar){
    echo '<pre>';
    echo json_encode($myVar, JSON_PRETTY_PRINT);
    echo '</pre>';
}

//index array
function getIndexedArray($query){
    global $con;
    $index=[];
    $entries=getAll($con,$query);
    foreach($entries as $row)
        $index[$row['id']]=$row;
    return $index;
} 

//pdf printing
function printPage($pageUrl,$receipt){
    ini_set('memory_limit', '256M');
    $key = "R403zeaT7SVKelXg";
	$url = "https://v2.convertapi.com/convert/web/to/pdf?Secret=$key&Url=".urlencode($pageUrl) ;
	
	$curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
    ));
    
    $response = curl_exec($curl);
    $responseData = json_decode($response, true);
    $pdfFile = "./uploads/$receipt.pdf";
    file_put_contents($pdfFile, base64_decode($responseData['Files'][0]['FileData']));
}
?>