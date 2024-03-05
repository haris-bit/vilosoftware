                <?
                $filenameLink = basename($_SERVER['PHP_SELF']);
                $countNew=getRow($con,"select count(id) as count from darlelJobber_notifications where userId='$session_id' && status='New'");
                $countNew=$countNew['count'];
                
                $query1="select t.*,count(t.id) as countTasks from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where 
                (tt.userId='$session_id' ) and t.status='New'";
                $countNewTask=getRow($con,$query1)['countTasks'];
                
                if($countNewTask>0 && $filenameLink=="tasks.php"){
                    //marking all as read
                    $query="select * from  darlelJobber_tasks tasks where tasks.status='New' and  tasks.id in 
                    (select t.id from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where (tt.userId='$session_id' ))";
                    $nTasks=getAll($con,$query);
                    foreach($nTasks as $row){
                        $taskRowId=$row['id'];
                        runQuery("update darlelJobber_tasks set status='Read' where id='$taskRowId'");
                    }
                    
                 /*   runQuery("update darlelJobber_tasks tasks set status='Read' where tasks.id in 
                    (select t.id from darlelJobber_tasks t left join darlelJobber_teams tt on t.id=tt.taskId where (tt.userId='$session_id' ))");
                 */   
                }
                ?>
                
                <div data-kt-aside-minimize="on" id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle" style="background-color: #1f3a39;" >
					<div style="background-color: #1f3a39;" class="aside-logo flex-column-auto" id="kt_aside_logo">
						<div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle active" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
							<span class="svg-icon svg-icon-1 rotate-180">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<path opacity="0.5" d="M14.2657 11.4343L18.45 7.25C18.8642 6.83579 18.8642 6.16421 18.45 5.75C18.0358 5.33579 17.3642 5.33579 16.95 5.75L11.4071 11.2929C11.0166 11.6834 11.0166 12.3166 11.4071 12.7071L16.95 18.25C17.3642 18.6642 18.0358 18.6642 18.45 18.25C18.8642 17.8358 18.8642 17.1642 18.45 16.75L14.2657 12.5657C13.9533 12.2533 13.9533 11.7467 14.2657 11.4343Z" fill="currentColor" />
									<path d="M8.2657 11.4343L12.45 7.25C12.8642 6.83579 12.8642 6.16421 12.45 5.75C12.0358 5.33579 11.3642 5.33579 10.95 5.75L5.40712 11.2929C5.01659 11.6834 5.01659 12.3166 5.40712 12.7071L10.95 18.25C11.3642 18.6642 12.0358 18.6642 12.45 18.25C12.8642 17.8358 12.8642 17.1642 12.45 16.75L8.2657 12.5657C7.95328 12.2533 7.95328 11.7467 8.2657 11.4343Z" fill="currentColor" />
								</svg>
							</span>
						</div>
					</div>
					
					<div class="aside-menu flex-column-fluid">
						<div class="hover-scroll-overlay-y my-5 my-lg-5" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside_menu" data-kt-scroll-offset="0">
							<div class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500" id="#kt_aside_menu" data-kt-menu="true" data-kt-menu-expand="false">
								<?if($isAdmin || $session_role=="Estimator"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="analytics.php"){echo "active";}?>"  title="View Analytics" data-bs-toggle="tooltip" data-bs-trigger="hover"
									href="analytics.php?startDate=<?echo $_SESSION['adminAnalyticsStartDate']."&endDate=".$_SESSION['adminAnalyticsEndDate']."&estimators=".$_SESSION['adminAnalyticsSelectedEstimators']?>"
									data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
											<span class="svg-icon svg-icon-2">
												<i class="las la-home" style="font-size: x-large;"></i>
											</span>
										</span>
										<span class="menu-title">Analytics</span>
									</a>
								</div>
								<?}?>
								<?if($isAdmin || $session_role=="Estimator"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="toDoList.php"){echo "active";}?>"  title="View To Do List" data-bs-toggle="tooltip" data-bs-trigger="hover" href="toDoList.php" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
											<span class="svg-icon svg-icon-2">
												<i class="las la-clipboard-list" style="font-size: x-large;"></i>
											</span>
										</span>
										<span class="menu-title">To Do List</span>
									</a>
								</div>
								<?}?>
								<?if($session_role=="Client"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="home.php"){echo "active";}?>" href="home.php" title="View Home " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
											<span class="svg-icon svg-icon-2">
												<i class="las la-home" style="font-size: x-large;"></i>
											</span>
										</span>
										<span class="menu-title">Home</span>
									</a>
								</div>
								<?}?>
								<?if(false){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="properties.php"){echo "active";}?>" href="properties.php" title="Manage Properties " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
											<span class="svg-icon svg-icon-2">
												<i class="las la-home" style="font-size: x-large;"></i>
											</span>
										</span>
										<span class="menu-title">Manage Properties</span>
									</a>
								</div>
								<?}?>
								<?if($session_role!="Client"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="schedule.php"){echo "active";}?>" href="schedule.php" title="Manage Schedule " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="lar la-calendar"></i>
										</span>
										<span class="menu-title">Schedule</span>
									</a>
								</div>
								<?if($session_role!="Installation Crew"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="shopSchedule.php"){echo "active";}?>" href="shopSchedule.php" title="Manage Shop Schedule " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="lar la-calendar"></i>
										</span>
										<span class="menu-title">Shop Schedule</span>
									</a>
								</div>
								<?}}?>
								<?if($permission['view_client'] || $isAdmin){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="clients.php"){echo "active";}?>" href="clients.php" title="Manage Clients " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-user-circle"></i>
										</span>
										<span class="menu-title">Clients</span>
									</a>
								</div>
								<?}?>
								
								<?if($permission['view_requests'] || $session_role=="Client" || $isAdmin){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="requests.php"){echo "active";}?>" href="requests.php" title="Manage Requests " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-file-download"></i>
										</span>
										<span class="menu-title">Requests</span>
									</a>
								</div>
								<?}
								
								$href="quotes.php?estimators=".$_SESSION['quotesEstimators']."&startDate=".$_SESSION['quotesStartDate']."&endDate=".$_SESSION['quotesEndDate'];
								$quoteLink = ($session_role=="Client" || $_SESSION['oldQuotes']) ? "oldQuotes.php" : $href;?>
								<?if($permission['view_quotes'] || $session_role=="Client"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink==$quoteLink){echo "active";}?>" href="<?echo $quoteLink?>" title="Manage Quotes" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="lab la-quora"></i>
										</span>
										<span class="menu-title">Quotes</span>
									</a>
								</div>
								<?}?>
								<?if($permission['view_jobs'] || $isAdmin){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="jobs.php"){echo "active";}?>" href="jobs.php" title="Manage Jobs " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-hammer"></i>
										</span>
										<span class="menu-title">Jobs</span>
									</a>
								</div>
								<?}?>
								<?if($permission['view_invoices'] || $isAdmin){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="invoices.php"){echo "active";}?>" href="invoices.php" title="Manage Invoices " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-file-invoice-dollar"></i>
										</span>
										<span class="menu-title">Invoices</span>
									</a>
								</div>
								<?}?>
								<?if($permission['view_material_delivery']){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="materialDelivery.php"){echo "active";}?>" href="materialDelivery.php" title="Manage Material Delivery " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-truck"></i>
										</span>
										<span class="menu-title">Material Delivery</span>
									</a>
								</div>
								<?}?>
                                <?if($permission['view_shop']){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="shop.php"){echo "active";}?>" href="shop.php" title="Manage Shop Orders " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-shopping-basket"></i>
										</span>
										<span class="menu-title">Shop</span>
									</a>
								</div>
								<?}?>
								<?if($session_role!="Client"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="tasks.php"){echo "active";}?>" href="tasks.php" title="Manage Reminders " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
                                          <i style="font-size: x-large; position: relative;" class="las la-clipboard-list">
                                            <?if($countNewTask!=0){?>
                                            <span class="badge badge-circle badge-success" style="position: absolute; top: -10px; right: -10px;font-size: smaller;"><?echo $countNewTask?></span>
                                            <?}?>
                                          </i>
                                        </span>
                                        <span class="menu-title">Reminders</span>
									</a>
								</div>
								<?}?>
								<?if($session_role!="Client"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="callLogs.php"){echo "active";}?>" href="callLogs.php" title="Manage Call Logs "
									data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
                                          <i style="font-size: x-large; position: relative;" class="las la-clipboard-list"></i>
                                        </span>
                                        <span class="menu-title">Call Logs</span>
									</a>
								</div>
								<?}?>
								<?if($permission['view_tickets']){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="tickets.php" && $_GET['purpose']=="system"){echo "active";}?>" href="tickets.php?purpose=system" title="Manage Tickets " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-toolbox"></i>
										</span>
										<span class="menu-title">Tickets</span>
									</a>
								</div>
								<?}?>
								<?if($session_role!="Drafting" && $session_role!="Shop Manager" &&  $session_role!="Installation Crew"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="tickets.php" && $_GET['purpose']=="client"){echo "active";}?>" href="tickets.php?purpose=client" title="Manage Tickets " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
										    <i style="font-size: x-large;" class="las la-toolbox"></i>
										</span>
										<span class="menu-title">Submit Repair Claim</span>
									</a>
								</div>
								<?}?>
								<?if($session_role!="Client"){?>
								<div class="menu-item">
									<a class="menu-link <?if($filenameLink=="notifications.php"){echo "active";}?>" href="notifications.php" title="View Notifications" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										
										<span class="menu-icon">
                                          <i style="font-size: x-large; position: relative;" class="lar la-bell">
                                            <?if($countNew!=0){?>
                                            <span class="badge badge-circle badge-success" style="position: absolute; top: -10px; right: -10px;font-size: smaller;"><?echo $countNew?></span>
                                            <?}?>
                                          </i>
                                        </span>
										
										<span class="menu-title">Notifications</span>
									</a>
								</div>
								<?}?>
								
								<?if($isAdmin || $session_role=="Estimator"){?>
								<div data-kt-menu-trigger="click" class="menu-item menu-accordion 
								<?if($filenameLink=="users.php" || $filenameLink=="leadSource.php" || $filenameLink=="labels.php" || $filenameLink=="services.php"){echo " hover show";}?>">
									<span class="menu-link <?if($filenameLink=="users.php" || $filenameLink=="leadSource.php" || $filenameLink=="labels.php" || $filenameLink=="services.php" || $filenameLink=="manageGoals.php"){echo " active";}?>" title="Manage Your Pipeline Under One Platform " data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
										<span class="menu-icon">
											<i style="font-size: x-large;" class="las la-cog"></i>
										</span>
										<span class="menu-title">Settings</span>
										<span class="menu-arrow"></span>
									</span>
									<div class="menu-sub menu-sub-accordion <?if($filenameLink=="services.php"){echo " show";}?>">
										<div class="menu-item">
											<a class="menu-link <?if($filenameLink=="services.php"){echo " active";}?> " href="./services.php">
												<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
												<span class="menu-title">Services</span>
											</a>
										</div>
									</div>
									<?if($isAdmin){?>
									<div class="menu-sub menu-sub-accordion <?if($filenameLink=="users.php"){echo " show";}?>">
										<div class="menu-item">
											<a class="menu-link <?if($filenameLink=="users.php"){echo " active";}?> " href="./users.php">
												<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
												<span class="menu-title">Users</span>
											</a>
										</div>
									</div>
									<div class="menu-sub menu-sub-accordion <?if($filenameLink=="labels.php"){echo " show";}?>">
										<div class="menu-item">
											<a class="menu-link <?if($filenameLink=="labels.php"){echo " active";}?> " href="./labels.php">
												<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
												<span class="menu-title">Labels</span>
											</a>
										</div>
									</div>
									<div class="menu-sub menu-sub-accordion <?if($filenameLink=="leadSource.php"){echo " show";}?>">
										<div class="menu-item">
											<a class="menu-link <?if($filenameLink=="leadSource.php"){echo " active";}?> " href="./leadSource.php">
												<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
												<span class="menu-title">Lead Source</span>
											</a>
										</div>
									</div>
									<div class="menu-sub menu-sub-accordion <?if($filenameLink=="manageGoals.php"){echo " show";}?>">
										<div class="menu-item">
											<a class="menu-link <?if($filenameLink=="manageGoals.php"){echo " active";}?> " href="./manageGoals.php">
												<span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
												<span class="menu-title">Goals</span>
											</a>
										</div>
									</div>
									<?}?>
								</div>
							    <?}?>
								
							</div>
						</div>
					</div>
				</div>
				