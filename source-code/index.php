<?
require("global.php");

if(isset($_POST['login'])){
    $email=clear($_POST['email']);
    $password=clear($_POST['password']); 
    $query="select * from darlelJobber_users where email='$email' && password='$password'";
    $userDeets=getRow($con,$query);
    $result=$con->query($query);
    if(mysqli_num_rows($result)!=0){
        $_SESSION['email']=$userDeets['email'];
        $_SESSION['calendarStartDate']=date("Y-m-d",time());
        $_SESSION['showFilter']="";
        $_SESSION['userFilter']="";
        $_SESSION['password']=$userDeets['password'];
        
        $startDate=date("Y-m-d",strtotime('first day of this month'));
        $endDate=date("Y-m-d",strtotime('last day of this month'));
        
        $adminAnalyticsStartDate=$startDate;
        $adminAnalyticsEndDate=$endDate;
        $adminAnalyticsSelectedEstimators=[];
        $users=getAll($con,"select * from darlelJobber_users where role!='Client'");
        foreach($users as $row){
            if($row['role']=="Estimator" || $row['role']=="Admin")
                $adminAnalyticsSelectedEstimators[]=$row['id'];
        }
        $adminAnalyticsSelectedEstimators=implode(",",$adminAnalyticsSelectedEstimators);
        
        $quotesStartDate=date("Y-m-d",time()-7890000 );
        $quotesEndDate=date("Y-m-d",time()+86400);
        
        $_SESSION['quotesStartDate']=$quotesStartDate;
        $_SESSION['quotesEndDate']=$quotesEndDate;
        
        $_SESSION['adminAnalyticsStartDate']=$adminAnalyticsStartDate;
        $_SESSION['adminAnalyticsEndDate']=$adminAnalyticsEndDate;
        $_SESSION['adminAnalyticsSelectedEstimators']=$adminAnalyticsSelectedEstimators;
        $_SESSION['quotesEstimators']=$userDeets['id'];
        
        $_SESSION['normalAnalyticsStartDate']=$adminAnalyticsStartDate;
        $_SESSION['normalAnalyticsEndDate']=$adminAnalyticsEndDate;
        $_SESSION['oldQuotes']=0;
        
        if($userDeets['role']=="Admin")
            header("Location:./analytics.php?startDate=$adminAnalyticsStartDate&endDate=$adminAnalyticsEndDate&estimators=$adminAnalyticsSelectedEstimators");
        else
            header("Location: ./home.php");
        exit();
    }
    else{
        header("Location: ./index.php?err=failed");
        exit();
    }
}
if(isset($_GET['userId'])){
    session_destroy();
    session_start();
    $userId=$_GET['userId'];
    $userDeets=getRow($con,"select * from darlelJobber_users where id='$userId'");
    $_SESSION['email']=$userDeets['email'];
    $_SESSION['password']=$userDeets['password'];
    
    if(isset($_GET['redirection'])){
        $map=[];
        $map['request']="createRequest.php";
        $map['quote']="createQuote.php";
        $map['job']="createJob.php";
        $map['invoice']="createInvoice.php";
        $map['form']="viewQuote.php";
        $redirection=clear($_GET['redirection']);
        $redirectionPage=$map[$redirection];
        
        $entryId=clear($_GET['entryId']);
        
        $page= ($redirection!="form") ? "$redirectionPage?entryId=$entryId&view=1" : "$redirectionPage?entryId=$entryId#formsTable";
        
        header("Location:./$page");
        exit();
    }
    else if(!isset($_GET['redirection'])){
        header("Location:./home.php");
        exit();
    }
}

if($logged==1){
    header("Location:./home.php");
    exit();
}

?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/home/head.php");?>
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body id="kt_body" class="bg-body">
		<!--begin::Main-->
		<!--begin::Root-->
		<div class="d-flex flex-column flex-root">
			<!--begin::Authentication - Sign-in -->
			<div class="d-flex flex-column flex-lg-row flex-column-fluid">
				<!--begin::Aside-->
				<div class="d-flex flex-column flex-lg-row-auto w-xl-600px positon-xl-relative" style="background-color: #18c0eb">
					<!--begin::Wrapper-->
					<div class="d-flex flex-column position-xl-fixed top-0 bottom-0 w-xl-600px scroll-y">
						<!--begin::Content-->
						<div class="d-flex flex-row-fluid flex-column text-center p-10 pt-lg-20">
							<!--begin::Logo-->
							<a href="./index.php" class="py-9 mb-5">
								<img alt="Logo" style="height: 200px  !important;" src="assets/logo.png" class="h-60px" />
							</a>
							<!--end::Logo-->
							<!--begin::Title-->
							<h1 class="fw-bolder fs-2qx pb-5 pb-md-10" style="color: white;">Welcome to <?echo $projectName?> </h1>
							<!--end::Title-->
							<!--begin::Description-->
							<p class="fw-bold fs-2" style="color: white;">Discover Amazing Management
							<br />features with great tools</p>
							<!--end::Description-->
						</div>
						<!--end::Content-->
						<!--begin::Illustration-->
						<div class="d-flex flex-row-auto bgi-no-repeat bgi-position-x-center bgi-size-contain bgi-position-y-bottom min-h-100px min-h-lg-350px" style="background-image: url(assets/media/illustrations/sketchy-1/13.png)"></div>
						<!--end::Illustration-->
					</div>
					<!--end::Wrapper-->
				</div>
				<!--end::Aside-->
				<!--begin::Body-->
				<div class="d-flex flex-column flex-lg-row-fluid py-10">
					<!--begin::Content-->
					<div class="d-flex flex-center flex-column flex-column-fluid">
						<!--begin::Wrapper-->
						<div class="w-lg-500px p-10 p-lg-15 mx-auto">
							<!--begin::Form-->
							<form method="post" class="form w-100" action="">
								<!--begin::Heading-->
								<div class="text-center mb-10">
								    <a href="./index.php" class="py-9 mb-5">
        								<img style="margin-bottom: 30px;height: 200px  !important;" alt="Logo" src="assets/logo.png" class="h-60px" />
        							</a>
									<h1 class="fw-bolder fs-2qx pb-5 pb-md-10">SIGN IN </h1>
									<?if($_GET['err']=="failed"){?>
    									<div style="margin-top: 35px;">
    									    <span  style="background-color: red;color: white;" class="alert alert-danger">Incorrect Credentials Try Again</span>
    								    </div>
									<?}?>
								</div>
								<div class="fv-row mb-10">
									<!--begin::Label-->
									<label class="form-label fs-6 fw-bolder text-dark">Email</label>
									<!--end::Label-->
									<!--begin::Input-->
									<input class="form-control form-control-lg form-control-solid" type="text" name="email" placeholder="Enter Email" autocomplete="off" />
									<!--end::Input-->
								</div>
								<!--end::Input group-->
								<!--begin::Input group-->
								<div class="fv-row mb-10">
									<!--begin::Wrapper-->
									<div class="d-flex flex-stack mb-2">
										<!--begin::Label-->
										<label class="form-label fw-bolder text-dark fs-6 mb-0">Password</label>
										<!--end::Label-->
										<!--begin::Link-->
										<!--end::Link-->
									</div>
									<input class="form-control form-control-lg form-control-solid" type="password" name="password" placeholder="Enter Password" autocomplete="off" />
									<!--end::Input-->
								</div>
								<!--end::Input group-->
								<!--begin::Actions-->
								<div class="text-center">
									<!--begin::Submit button-->
									<button name="login" type="submit" class="btn btn-lg btn-primary w-100 mb-5">
										<span class="indicator-label">Log In</span>
									</button>
								</div>
								<!--end::Actions-->
							</form>
							<!--end::Form-->
						</div>
						<!--end::Wrapper-->
					</div>
				</div>
				<!--end::Body-->
			</div>
			<!--end::Authentication - Sign-in-->
		</div>
	</body>
	<!--end::Body-->
</html>