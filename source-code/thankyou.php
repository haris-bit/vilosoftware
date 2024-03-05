<?
require("./global.php");
if($logged==0 || (!isset($_GET['m'])))
    header("Location:index.php");

if($_GET['m']=="complete_payment"){
    $heading="Thank You for filling all the forms";
    $message="The complete payment will be received in the invoice for which the estimator will be in touch with you very shortly";
}
if($_GET['m']=="pay_with_cash"){
    $message="The estimator will contact you soon related to cash payment";
    $heading="Thank You For Choosing Payment Option With Cash";
    
    if(isset($_GET['quoteId'])){
        
        $quoteId=clear($_GET['quoteId']);
        $query="update darlelJobber_quotes set paidWithCash='Yes',canPayThroughCard='No' where id='$quoteId'";
        runQuery($query);
        
        /*$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
        if($quoteDeets['paidWithCash']=="Yes"){
            header("Location:./quotes.php?m=The estimator will soon contact you related to cash payment");
            exit();
        }
        
        $clientId=$quoteDeets['customerId'];
        $clientDeets=getRow($con,"select * from darlelJobber_users where id='$clientId'");
        
        $title=$clientDeets['first_name']." ".$clientDeets['last_name'];
        $description="Client chose cash option";
        $timeAdded=time();
        $completionDate=$timeAdded+86400;
        
        $requestId=$quoteDeets['requestId'];
        
        $taskId=random();
        $query="insert into darlelJobber_tasks set id='$taskId',searchBy='Quote',quoteId='$quoteId',title='$title',description='$description',label='Cash Payment',
        completionDate='$completionDate',timeAdded='$timeAdded',addedBy='admin'";
        runQuery($query);*/
        
        if($requestId!="None"){
            /*$query="select * from darlelJobber_users where role='Estimator' and
            id in (select userId from darlelJobber_teams where requestId='$requestId')";
            $estimatorId=getRow($con,$query)['id'];
            
            $random=random();
            $userId=$estimatorId;
            $query="insert into darlelJobber_teams set id='$random',userId='$userId',taskId='$taskId',timeAdded='$timeAdded'";
            runQuery($query);
            
            //sending notification for the task
            $title="Assigned To a Task";
            $description="You have been assigned a task . Click To View";
            $url=$projectUrl."detailedTaskView.php?taskId=$taskId";
            setNotification($title,$description,$userId,$url);*/
        }
    }
    else if(isset($_GET['invoiceId'])){
        $invoiceId=clear($_GET['invoiceId']);
        $query="update darlelJobber_invoices set paidWithCash='Yes' where id='$invoiceId'";
        runQuery($query);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<?require("./includes/home/head.php");?>
	</head>
	<body id="kt_body" class="auth-bg">
		<div class="d-flex flex-column flex-root">
			<div class="d-flex flex-column flex-column-fluid">
				<div class="d-flex flex-column flex-column-fluid text-center p-10 py-lg-15">
					<h1 class="mb-10 pt-lg-10">
					    Vilo Fence
					</h1>
					<div class="pt-lg-10 mb-10">
						<h1 class="fw-bolder fs-2qx text-gray-800 mb-7"><?echo $heading;?></h1>
						<div class="fw-bold fs-3 text-muted mb-5"><?echo $message;?> </div>
				        <?if($_GET['m']=="complete_payment"){?>
				        <div class="fw-bold fs-3 text-muted mb-15">
				            <a href="quotes.php" class="btn btn-success">View Your Quotes</a>
				        </div>
				        <?}?>
				    </div>
					<div class="d-flex flex-row-auto bgi-no-repeat bgi-position-x-center bgi-size-contain bgi-position-y-bottom min-h-100px min-h-lg-350px" 
					style="background-image: url(assets/media/illustrations/sketchy-1/17.png)"></div>
				</div>
				
			</div>
		</div>
	</body>
</html>