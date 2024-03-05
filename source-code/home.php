<?require("./global.php");

$totalQuotes=getRow($con,"select count(id) as totalQuotes from darlelJobber_quotes where customerId='$session_id' and sendStatus='Sent'")['totalQuotes'];
$paidQuotes=getRow($con,"select count(id) as totalPaid from darlelJobber_quotes where customerId='$session_id' and sendStatus='Sent' and  
(paidStatus='Paid' or paidWithCash='Yes')")['totalPaid'];
if($totalQuotes=="")$totalQuotes=0;
if($paidQuotes=="")$paidQuotes=0;

$unPaidQuotes=$totalQuotes-$paidQuotes;

$query="SELECT * from darlelJobber_visits v inner join darlelJobber_jobs j on v.jobId=j.id where j.customerId='$session_id' order by v.start_date asc";
$visits=getAll($con,$query);
$totalVisits=count($visits);
?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
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
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;">
					    <div class="post d-flex flex-column-fluid">
					        <div class="container-xxl" id="kt_content_container">
					            <div class="row g-5">
					                
					                <!--user actions started-->
					                <div class="col-sm-12 col-md-4 col-12">
										<div class="card card-flush h-xl-100">
											<div class="card-header rounded bgi-no-repeat bgi-size-cover bgi-position-y-top bgi-position-x-center align-items-start h-250px" style="background-image:url('assets/media/svg/shapes/top-green.png')">
												<h3 class="card-title align-items-start flex-column text-white pt-15">
													<span class="fw-bolder fs-2x mb-3">Hello, 
													    <?if($session_data['title']=="No Title"){$title="";}else{$title=$session_data['title'];}?>
												        <?echo $title." ".$session_data['first_name']." ".$session_data['last_name']?>
													</span>
													<div class="fs-4 text-white">
														<span class="opacity-75">You Can Perform</span>
														<span class="position-relative d-inline-block">
															<p class="link-white opacity-75-hover fw-bolder d-block mb-1">3 Operations</p>
															<span class="position-absolute opacity-50 bottom-0 start-0 border-2 border-white border-bottom w-100"></span>
														</span>
													</div>
												</h3>
											</div>
											<div class="card-body mt-n20">
												<div class="mt-n20 position-relative">
													<div class="row g-3 g-lg-6">
														<div class="col-12">
														    <a class="btn btn-success btn-hover-scale me-5 w-100" href="./quotes.php">View Quotes</a>
														</div>
														<div class="col-12">
														    <a class="btn btn-light btn-hover-scale me-5 w-100" href="./invoices.php">View/Pay Invoices</a>
														</div>
														<div class="col-12">
														    <a class="btn btn-primary btn-hover-scale me-5 w-100" href="./client_ticket.php?new=1">Submit Repair Claim</a>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<!--user actions finished-->
									
									<!--quote analysis started-->
									<div class="col-sm-12 col-md-4 col-12">
										<div class="card h-100">
											<div class="card-body p-9" style="background-color: #ffe8e2;border-radius: 10px;">
												<div class="fs-2hx fw-bolder"><?echo "Total Quotes ".$totalQuotes?></div>
												<div class="fs-4 fw-bold text-gray-400 mb-7">Quotes Analysis</div>
												<div class="d-flex flex-wrap">
													<div class="d-flex flex-center" style="height: 200px !important;width: 150px !important;">
														<canvas id="kt_project_list_chart"></canvas>
													</div>
													<div class="d-flex flex-column justify-content-center flex-row-fluid pe-11 mb-5" style="margin-left: 50px;">
														<div class="d-flex fs-6 fw-bold align-items-center mb-3">
															<div class="bullet bg-success me-3"></div>
															<div class="text-gray-400">Paid Quotes </div>
															<div class="ms-auto fw-bolder text-gray-700"><?echo $paidQuotes?></div>
														</div>
														<div class="d-flex fs-6 fw-bold align-items-center mb-3">
															<div class="bullet bg-primary me-3"></div>
															<div class="text-gray-400">Unpaid Quotes</div>
															<div class="ms-auto fw-bolder text-gray-700"><?echo $unPaidQuotes?></div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<!--quote analysis finished-->
									
									<!--visits list started-->
									<div class="col-sm-12 col-md-4 col-12">
										<div class="card">
											<div class="card-body p-9">
												<div class="fs-2hx fw-bolder"><?echo "Total Visits ".$totalVisits;?></div>
												<div class="fs-4 fw-bold text-gray-400 mb-7">Scheduled Visits List </div>
												<?foreach($visits as $row){?>
												<div class="fs-6 d-flex justify-content-between mb-4 text-center mt-2" <?if($row['completionStatus']=="Completed"){echo "style='background-color: #aaffaa;'";}?>>
													<div class="fw-bold"><?echo $row['title']." ".$row['description']?></div>
													<div class="d-flex fw-bolder text-center">
													    <?if($row['type']=="Schedule Later"){echo "To Be Scheduled";}else{
													    echo date("d M Y",$row['start_date'])." ".date("d M Y",$row['end_date'])."<br>";
													    echo date('h:i A', strtotime($row['start_time']))." ".date('h:i A', strtotime($row['end_time']))."<br>";
													    echo $row['completionStatus'];
													    }?>
													</div>
												</div>
												<div class="separator separator-dashed"></div>
												<?}?>
											</div>
										</div>
									</div>
									<!--visits list finished-->
									
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
    <script>
var KTProjectList = {
    init: function() {
        ! function() {
            var t = document.getElementById("kt_project_list_chart");
            if (t) {
                var e = t.getContext("2d");
                new Chart(e, {
                    type: "doughnut",
                    data: {
                        datasets: [{
                            data: [<?echo $paidQuotes?>, <?echo $unPaidQuotes?>],
                            backgroundColor: ["#50CD89", "#00A3FF"]
                        }],
                        labels: ["Paid", "Unpaid"]
                    },
                    options: {
                        chart: {
                            fontFamily: "inherit"
                        },
                        cutout: "75%",
                        cutoutPercentage: 65,
                        responsive: !0,
                        maintainAspectRatio: !1,
                        title: {
                            display: !1
                        },
                        animation: {
                            animateScale: !0,
                            animateRotate: !0
                        },
                        tooltips: {
                            enabled: !0,
                            intersect: !1,
                            mode: "nearest",
                            bodySpacing: 5,
                            yPadding: 10,
                            xPadding: 10,
                            caretPadding: 0,
                            displayColors: !1,
                            backgroundColor: "#20D489",
                            titleFontColor: "#ffffff",
                            cornerRadius: 4,
                            footerSpacing: 0,
                            titleSpacing: 0
                        },
                        plugins: {
                            legend: {
                                display: !1
                            }
                        }
                    }
                })
            }
        }()
    }
};
KTUtil.onDOMContentLoaded((function() {
    KTProjectList.init()
}));
    </script>
</html>