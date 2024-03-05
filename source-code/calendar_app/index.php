<?
include_once("./global.php");
$idToJobId=[];
$visits=getAll($con,"select * from darlelJobber_visits");
foreach($visits as $row){
    $idToJobId[$row['id']]=$row['jobId'];    
}
$shopIdToJobId=[];
$shopOrders=getAll($con,"select * from darlelJobber_shop_orders");
foreach($shopOrders as $row){
    $shopIdToJobId[$row['id']]=$row['jobId'];    
}
$idToUserInfo=[];
$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row){
    $idToUserInfo[$row['id']]=$row;    
}

if(isset($_GET['view'])){
    $_SESSION['view']=$_GET['view'];
}
?>
<!DOCTYPE html>
<html>
    <head>
        <?include("./phpParts/head.php")?>
        <link rel="stylesheet" href="./calender_style.css">
        <link rel="stylesheet" href="./customCss.css">
        <link rel="stylesheet" href="./navigationCss.css">
    </head>
    <body style="background:#f1f4f6">
            <a id="showDetails" target="_top" href="" hidden></a>
            <link rel="stylesheet" href="./calender_style.css">
            <style>
            .custom-date-input {
                position: relative;
                display: inline-block;
            }
            
            .custom-date-input input[type="date"]::-webkit-calendar-picker-indicator {
                opacity: 0;
                width: 100%;
                height: 100%;
                cursor: pointer;
                position: absolute;
                top: 0;
                left: 0;
            }

            #dp{
                /*margin-left: 220px;*/
            }
            .ml-220{
                margin-left: 220px;
            }
            @media only screen and (max-width: 600px) {
              #nav {
                display:none;
              }
              #dp{
                  margin-left: 0px;
              }
              .ml-220{
                  margin-left: 0px;
              }
            }
            </style>
           
           <!--side navigation bar-->
           <div>
               <div id="mySidenav" class="sidenav">
                  <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
                  <div style="margin-top:10px;">
                      <ul id="external">
                        <?if($permission['edit_everyone_schedule']){?>  
                        
                        <h5 style="margin-top:10px;" >Unscheduled Requests</h5>
                        <div id="unscheduleRequests">
                        <?$unscheduleRequests=getAll($con,"select * from darlelJobber_requests where scheduleStatus='Schedule Later' order by timeAdded desc");
                        foreach($unscheduleRequests as $row){
                        $clientName="  ( ".$idToUserInfo[$row['request_for']]['first_name']." ".$idToUserInfo[$row['request_for']]['last_name']." )";?>
                        <li data-id="<?echo $row['id']?>" data-resource="request" data-duration="1800" unselectable="on" style="user-select: none;"><span style="cursor: move; user-select: none;" unselectable="on"><?echo $row['title'].$clientName?></span></li>
                        <?}?>
                        </div>
                        
                        <h5 style="margin-top:10px;" >Unscheduled Visits</h5>
                        <div id="unscheduleVisits">
                        <?$unscheduleVisits=getAll($con,"select * from darlelJobber_visits where type='Schedule Later' order by timeAdded desc");
                        foreach($unscheduleVisits as $row){?>
                        <li data-id="<?echo $row['id']?>" data-resource="visit" data-duration="1800" unselectable="on" style="user-select: none;"><span style="cursor: move; user-select: none;" unselectable="on"><?echo $row['title']."  (".$row['description'].")"?></span></li>
                        <?}?>
                        </div>
                        
                        <h5 style="margin-top:10px;">Unscheduled Tickets</h5>
                        <div id="unscheduleTickets">
                        <?$unscheduleTickets=getAll($con,"select * from darlelJobber_tickets where scheduleType='Schedule Later' order by timeAdded desc");
                        foreach($unscheduleTickets as $row){?>
                        <li data-id="<?echo $row['id']?>" data-resource="ticket" data-duration="1800" unselectable="on" style="user-select: none;">
                            <span style="cursor: move; user-select: none;" unselectable="on">
                                <?echo $idToUserInfo[$row['customerId']]['first_name']." ".$idToUserInfo[$row['customerId']]['last_name']."  -".$row['title'];?>
                            </span>
                        </li>
                        <?}?>
                        </div>
                        
                        <h5 style="margin-top:10px;">Unscheduled Material Delivery</h5>
                        <div id="unscheduleMaterialDelivery">
                        <?$unscheduleMaterialDelivery=getAll($con,"select * from darlelJobber_material_delivery where schedule_type='Schedule Later' order by timeAdded desc");
                        foreach($unscheduleMaterialDelivery as $row){
                        $clientName="( ".$idToUserInfo[$row['customerId']]['first_name']." ".$idToUserInfo[$row['customerId']]['last_name']." )";
                        ?>
                        <li data-id="<?echo $row['id']?>" data-resource="delivery" data-duration="1800" unselectable="on" style="user-select: none;"><span style="cursor: move; user-select: none;" unselectable="on"><?echo $row['sales_order'].$clientName;?></span></li>
                        <?}?>
                        </div>
                        
                        <!--<h5 style="margin-top:10px;">Unscheduled Shop Orders</h5>
                        <div id="unscheduleShopOrders">
                        <?$unscheduleShopOrders=getAll($con,"select * from darlelJobber_shop_orders where scheduleType='Schedule Later' order by timeAdded desc");
                        foreach($unscheduleShopOrders as $row){
                        ?>
                        <li data-id="<?echo $row['id']?>" data-resource="shop" data-duration="1800" unselectable="on" style="user-select: none;"><span style="cursor: move; user-select: none;" unselectable="on"><?echo $row['title'];?></span></li>
                        <?}?>
                        </div>-->
                        
                        <?}?>
                    </ul>
                  </div>
                </div>
           </div>
           
            <div class="hideSkipLink">
            </div>
            <div class="main">
                <div style="font-size: 20px;padding:10px;">
        
                <div class="custom-date-input">
                  <input type="date" name="dateInput" onchange="jumpDate()" value="<?echo date("Y-m-d",strtotime($calendarStartDate))?>" class="btn btn-primary btn-sm text-white" >
                </div>
                
                <b>
                    <button class="btn btn-primary btn-sm" onclick="changeDate(-7)"><<</button>
                    <button class="btn btn-primary btn-sm" onclick="changeDate(-1)"><</button>
                    <span id="current_date" style="padding:0px 5px;"></span>
                    <button  class="btn btn-primary btn-sm" onclick="changeDate(1)" >></button>
                    <button  class="btn btn-primary btn-sm" onclick="changeDate(7)" >>></button>
                </b>
               
                <!--<select class="btn btn-primary btn-sm" onchange="location = this.value;">
                    <option <?$view=$_SESSION['view'];if($view=="Day"){echo "selected";}?> value="index.php?view=Day">Daily View</option>
                    <option <?if($view=="Week"){echo "selected";}?> value="index.php?view=Week">Weekly View</option>
                </select>
                -->
                <!--instead of select we are going to make a button-->
                <?$view=$_SESSION['view'];
                if($view=="Week"){
                    $text="Daily View";
                    $link="index.php?view=Day";
                }
                else{
                    $text="Weekly View";
                    $link="index.php?view=Week";
                }
                ?>
                <a onclick="jumpToCurrDate()" class="btn btn-warning btn-sm text-white">Today's Schedule</a>
                <a href="<?echo $link?>" class="btn btn-primary btn-sm"><?echo $text?></a>
                <?if($permission['edit_everyone_schedule']){?>
                <button class="btn btn-primary btn-sm" style="cursor:pointer" onclick="openNav()">&#9776; Unscheduled</button>
                <a onclick="showModal()" class="btn btn-primary btn-sm text-white">&#9776; Filters</a>
                <?}?>
                <script>
                    function openNav() {
                      var width=document.getElementById("mySidenav").style.width;
                      if(width=="250px")
                        document.getElementById("mySidenav").style.width = "0";
                      else
                        document.getElementById("mySidenav").style.width = "250px";
                    }
                    function closeNav() {
                      document.getElementById("mySidenav").style.width = "0";
                    }
                </script>
                
                
                <!--filters modal-->
               <style>
                   .modal-dialog {
                        overflow-y: auto;
                        max-height: -webkit-calc(100vh - 200px); 
                        max-height: calc(100vh - 200px);
                    }
                    .checkbox-container {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                        gap: 10px; /* Adjust the gap between checkboxes as needed */
                        width: 100%; /* Set the width to the desired value or use other CSS units as needed */
                    }

                </style>
               <div class="modal fade show" id="filtersModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document" style="max-width: 1200px;">
                    <div class="modal-content">
                        
                        <div class="modal-header" style="padding-right: 0px;">
                            <div class="row w-100">
                                <div class="col-4">
                                    <h5 class="modal-title" id="exampleModalLabel">Apply Filters</h5>
                                </div>
                                <div class="col-8 text-right" style="padding-right: 0px;">
                                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                                    <a class="btn btn-primary text-white" onclick="applyFilters()">Apply Filters</a>
                                </div>
                            </div>
                        </div>
                      <div class="modal-body">
                        <div class="row">
                            <div class="col-12 d-flex" id="showFilter">
                                <?$types=array("requests","jobs","tickets","delivery","shop","tasks");
                                foreach($types as $row){
                                $checked=0;
                                if(strpos($_SESSION['showFilter'], $row) !== false)
                                    $checked=1;
                                ?>
                                <div class="form-check" style="margin-bottom: 6px;padding-left: 20px;padding-right: 20px;">
                                  <input style="height: 23px;width: 20px;" name="showFilter[]" class="form-check-input mt-10" type="checkbox" value="<?echo $row?>" <?if($checked){echo "checked";}?>>
                                  <label class="form-check-label" style="margin-left: 5px;">
                                    <?echo "Show ".ucfirst($row);?>
                                  </label>
                                </div>
                                <?}?>
							</div>
						</div>
						<hr>
						<div class="row" id="userFilter">
						    <div class="col-12  mb-4">
					            <h5 class="modal-title">Users Filter</h5>
						        <div class="checkbox-container">
    						        <?$users=getAll($con,"select * from darlelJobber_users where id!='admin' && role!='Client'");
    						        $count=count($users);
    						        for($i=0;$i<$count;$i++){
    						        $checked=0;
                                    if(strpos($_SESSION['userFilter'], $users[$i]['id']) !== false)
                                        $checked=1;?>
        						        <div class="form-check" style="margin-bottom: 6px;padding-left: 20px;padding-right: 20px;">
                                          <input style="height: 23px;width: 20px;" name="userFilter[]" class="form-check-input mt-10" type="checkbox" value="<?echo $users[$i]['id']?>" <?if($checked){echo "checked";}?>>
                                          <label style="margin-left: 5px;" class="form-check-label">
                                            <?echo $users[$i]['name'];?>
                                          </label>
                                        </div>
    						        <?}?>
						        </div>
						    </div>
						</div>
				      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                        <a class="btn btn-primary text-white" onclick="applyFilters()">Apply Filters</a>
                      </div>
                    </div>
                  </div>
                </div>
         
                
                </div>
                <div>
                    <div id="dp"></div>
                </div>

                <script type="text/javascript">
                
                    var idToJobId=<?echo json_encode($idToJobId);?>;
                    var shopIdToJobId=<?echo json_encode($shopIdToJobId);?>;
                    var nav = new DayPilot.Navigator("nav");
                    nav.selectMode = "month";
                    nav.showMonths = 1;
                    nav.onTimeRangeSelected = function(args) {
                        loadTimeline(args.day);
                        show_current_date(args.day)
                        loadEvents();
                    };
                    
                    $("#timerange").change(function() {
                        switch (this.value) {
                            case "week":
                                dp.days = 7;
                                nav.selectMode = "Week";
                                nav.select(nav.selectionDay);
                                break;
                            case "month":
                                dp.days = dp.startDate.daysInMonth();
                                nav.selectMode = "Month";
                                nav.select(nav.selectionDay);
                                break;
                        }
                    });

                    $("#autocellwidth").click(function() {
                        dp.cellWidth = 100;
                        dp.cellHeight = 30;  // reset for "Fixed" mode
                        dp.cellWidthSpec = $(this).is(":checked") ? "Auto" : "Fixed";
                        dp.update();
                    });

                </script>
                
                <script>
                
            currDate = "";
            function show_current_date(date){
                currDate = date;
                date = new Date(date).toLocaleString().substr(0, 9);
                $("#current_date").html(date);
                
                
            }
            
            function jumpToCurrDate(){
                $("input[name='dateInput']").val("<?echo date("Y-m-d",time());?>");
                var date=$("input[name='dateInput']").val();
                date=new DayPilot.Date(date);
                loadTimeline(date);
                show_current_date(date);
                updateSessionVariable();
            }
            
            function jumpDate(){
                var date=$("input[name='dateInput']").val();
                //console.log(date);
                date=new DayPilot.Date(date);
                loadTimeline(date);
                show_current_date(date);
                
                updateSessionVariable();
            }
            
            function changeDate(action){
                currDate = currDate.addDays(action);
                var month=currDate.getMonth()+1;
                month = month.toString().padStart(2, "0");
                var day=currDate.getDay();
                day = day.toString().padStart(2, "0");
                var newDate=currDate.getYear()+"-"+month+"-"+day;
                //console.log("new date"+newDate)
                $("input[name='dateInput']").val(newDate)
                
                loadTimeline(currDate);
                show_current_date(currDate);
                
                updateSessionVariable();
            }
            
            
            function updateSessionVariable(){
                var date=$("input[name='dateInput']").val();
                var showFilter="";
                var checkedShowFilter = $("#showFilter input:checkbox:checked").map(function(){
                  var val=$(this).val();
                  showFilter=showFilter.concat(val,",");
                }).get();
                showFilter = showFilter.substring(0, showFilter.length - 1);
                
                var userFilter="";
                var checkedUserFilter = $("#userFilter input:checkbox:checked").map(function(){
                  var val=$(this).val();
                  userFilter=userFilter.concat(val,",");
                }).get();
                userFilter = userFilter.substring(0, userFilter.length - 1);
                
                $.post("updateSessionVariables.php",
                {
                    newDate: date,
                    show_filter: showFilter,
                    user_filter: userFilter,
                },
                function(){
                    //console.log("");
                });
            }
                    var start_date=new DayPilot.Date("<?echo $_SESSION['calendarStartDate'];?>");
                    var dp = new DayPilot.Calendar("dp", {
                        startDate: start_date,//DayPilot.Date.today(),//here is the initial date
                        days: 1,
                        scale: "CellDuration",
                        cellDuration: 30,
                        dayBeginsHour: 8,
                        dayEndsHour: 24,
                        businessBeginsHour: 8,
                        businessEndsHour: 24,
                        // infiniteScrollingEnabled : true,
                        showNonBusiness: false,
                        eventMoveSkipNonBusiness: true,
                        dynamicLoading : true,
                        dynamicEventRendering : "Disabled",
                         timeHeaders: [
                            { groupBy: "Day", format: "dddd d/M/YY" },//M/d/yyyy
                            { groupBy: "Hour" },
                            { groupBy: "Day", format: "ddd d" }
                        ],
                        
                    });
                    dp.eventDeleteHandling = "Update";
                    //console.log("DayPilot.Date.today()", DayPilot.Date.today())
                    
                    show_current_date(start_date);
                    
                    dp.viewType = "<?if($_SESSION['view']=="Week"){echo "Week";}else{echo "Day";}?>";
                    //console.log("view type"+dp.viewType);
                    dp.eventHeight = 40;
                    dp.cellWidth = 100;
                    dp.cellHeight = 30;
                    dp.theme = "scheduler_blue";

                    dp.rowHeaderColumns = [
                        {title: "Name", display: "name", width: 80},
                        { groupBy: "Day", format: "ddd d" }
                        // {title: "Address", display: "address", width: 80},
                    ];

                    dp.separators = [
                        { location: new DayPilot.Date(), color: "red" }
                    ];
                    dp.onBeforeResHeaderRender = function(args) {
                        var beds = function(count) {
                            return count + "" + (count > 1 ? "s" : "");
                        };

                        switch (args.resource.status) {
                            case "Dirty":
                                args.resource.cssClass = "status_dirty";
                                break;
                            case "Cleanup":
                                args.resource.cssClass = "status_cleanup";
                                break;
                        }

                        args.resource.areas = [{
                                    top:3,
                                    right:4,
                                    height:14,
                                    width:14,
                                    action:"JavaScript",
                                    js: function(r) {
                                        var modal = new DayPilot.Modal();
                                        modal.onClosed = function(args) {
                                            loadResources();
                                        };
                                        modal.showUrl("room_edit.php?id=" + r.id);
                                    },
                                    v:"Hover",
                                    css:"icon icon-edit",
                                }];
                    };

                    dp.onScroll = function (args) {
                      var start = args.viewport.start;
                      var end = args.viewport.end;
                      var visibleRange = new DayPilot.Duration(start, end);
                    
                      DayPilot.Http.ajax({
                        url: "backend_events.php",
                        data: {
                          start: start.addTime(-visibleRange.ticks),
                          end: end.addTime(visibleRange)
                        },
                        success: function (ajax) {
                          args.events = ajax.data;
                          args.loaded();
                        }
                      });
                    };

                    //checking if the user has changing schedule rights
                    dp.onEventMove = function (args) {
                        var canEditSchedule=<?echo ($permission['edit_everyone_schedule']) ? "1" : "0";?>;
                        if(!canEditSchedule){
                            args.preventDefault();
                        }
                    };

                    //for unscheduled items or if an item is moved
                    dp.onEventMoved = function (args) {
                        if(args.external){
                            var visitDeets=args.e.data.data;
                            $.post("unscheduled.php",
                            {
                                id: visitDeets['id'],
                                resource: visitDeets['resource'],
                                newStart: args.newStart.toString(),
                                newEnd: args.newEnd.toString()
                            },
                            
                            function(data) {
                                //dp.message(data.message);
                                loadEvents();
                            });
                        }
                        else{
                            $.post("backend_resize.php",
                            {
                                id: args.e.id(),
                                resource: args.e.resource(),
                                newStart: args.newStart.toString(),
                                newEnd: args.newEnd.toString()
                            },
                            function(data) {
                                //dp.message(data.message);
                            });
                        }
                    };
                    
                    //for if an item is resized
                    dp.onEventResized = function (args) {
                        $.post("backend_resize.php",
                        {
                            id: args.e.id(),
                            resource: args.e.resource(),
                            newStart: args.newStart.toString(),
                            newEnd: args.newEnd.toString()
                        },
                        function(data) {
                            //dp.message(data.message);
                        });
                    };
                    
                    /*dp.onEventDelete = function(args) {
                        
                        var purpose=args.e.resource();;
                        var canDeleteJob=<?echo $permission['Can Delete Jobs'];?>;
                        var canDeleteRequest=<?echo $permission['Can Delete Requests'];?>;
                        if((purpose=="job" && !canDeleteJob) || (purpose=="request" && !canDeleteRequest)){
                            args.preventDefault();
                            return 0;
                        }
                        if (!confirm("Do you really want to delete this event?")) {
                            args.preventDefault();
                        }
                    };

                    dp.onEventDeleted = function(args) {
                        $.post("backend_delete.php",
                        {
                            id: args.e.id(),
                            resource: args.e.resource(),
                        },
                        function() {
                            dp.message("Deleted.");
                        });
                    };
                    */
                    
                    // this open a modal for creating actions
                    dp.onTimeRangeSelected = function (args) {
                        <?if($session_role!="Installation Crew"){?>
                        var modal = new DayPilot.Modal();
                        modal.closed = function() {
                            dp.clearSelection();
                            var data = this.result;
                            if (data && data.result === "OK")
                                loadEvents();
                        };
                        const queryString = window.location.search;
                        const urlParams = new URLSearchParams(queryString);
                        const company = urlParams.get('company');
                        modal.showUrl("reservation_new.php?start=" + args.start + "&end=" + args.end + "&resource=" + args.resource+ "&company=" + company);
                        <?}?>
                            
                    };

                    //when clicked on an event 
                    dp.onEventClick = function(args) {
                        var entryId=args.e.id();
                        var resource=args.e.resource();
                        if(resource=="job" && (<?echo ($permission['edit_jobs']==null ) ? "0" : "1";?>)){
                            //showing visit details through the modal
                            var modal = new DayPilot.Modal({height: 440,});
                            var visitId=args.e.id();
                            var jobId=idToJobId[visitId];
                            
                            modal.showUrl("modal.php?visitId="+visitId+"&jobId="+jobId);
                            modal.onClosed = function(args) {
                                loadEvents();
                            };
                        }
                        else{
                            if(resource=="job")
                                entryId=idToJobId[entryId];
                            else if(resource=="shop")
                                entryId=shopIdToJobId[entryId];
                            if(resource=="request")
                                $("#showDetails").attr("href", "../createRequest.php?view=1&entryId="+entryId+"");
                            else if(resource=="job")
                                $("#showDetails").attr("href", "../createJob.php?entryId="+entryId+"&viewFromShop=1&view=1");
                            else if(resource=="ticket")
                                $("#showDetails").attr("href", "../create_ticket.php?view=1&ticketId="+entryId+"");
                            else if(resource=="delivery")
                                $("#showDetails").attr("href", "../materialDelivery.php?id="+entryId+"");
                            else if(resource=="shop")
                                $("#showDetails").attr("href", "../createJob.php?entryId="+entryId+"#shopTable");
                            else if(resource=="tasks")
                                $("#showDetails").attr("href", "../detailedTaskView.php?taskId="+entryId);
                            $("#showDetails")[0].click();
                        }
                    };
                    
                    //for making an event as unscheduled 
                    dp.onBeforeEventRender = function(args) {
                        args.e.contextMenu = new DayPilot.Menu({
                            items: [
                                { text: "Unschedule Event", 
                                onClick: (args) => {
                                    dp.events.remove(args.source);
                                    var eventId=args.source.data.id
                                    var resource=args.source.data.resource
                                    var text=args.source.data.text
                                    
                                    var resourceToDiv = {};
                                    resourceToDiv["request"] = "unscheduleRequests";
                                    resourceToDiv["job"] = "unscheduleVisits";
                                    resourceToDiv["ticket"] = "unscheduleTickets";
                                    resourceToDiv["delivery"] = "unscheduleMaterialDelivery";
                                    //resourceToDiv["shop"] = "unscheduleShopOrders";
                                    
                                    var divId=resourceToDiv[resource]
                                    
                                    var string=`
                                    <li data-id="`+eventId+`" data-resource="`+resource+`" 
                                    data-duration="1800" unselectable="on" style="user-select: none;">
                                    <span style="cursor: move; user-select: none;" unselectable="on">
                                    `+text+`
                                    </span>
                                    </li>
                                    `;
                                    
                                    $('#'+divId).append(string);
                                    
                                    $.post("unscheduleEvent.php",{
                                        id: eventId,
                                        resource: resource,
                                    },
                                    function(){
                                        app.makeDraggable();
                                    }
                                    );
                                    
                                }    
                                }
                            ]
                        });
                    };

                    //if same day and same color then paint grey
                    dp.onBeforeCellRender = function(args) {
                      if(args.cell.start.getDay() == DayPilot.Date.today().getDay() && args.cell.start.getMonth() == DayPilot.Date.today().getMonth()){
                          args.cell.backColor = "#808080";
                      }
                    };
                    dp.init();
                    
                    //function for external drag and drop
                    const app = {
                        makeDraggable() {
                            const parent = document.getElementById("external");
                            const items = parent.getElementsByTagName("li");
                            for (let i = 0; i < items.length; i++) {
                                const e = items[i];
                                const item = {
                                    element: e,
                                    externalCursor: "copy",
                                    data: {
                                        id: e.getAttribute("data-id"),
                                        resource: e.getAttribute("data-resource"),
                                        text: e.innerText,
                                        duration: e.getAttribute("data-duration"),
                                    }
                                };
                                DayPilot.Calendar.makeDraggable(item);
                            }
                        }
                };
                
                app.makeDraggable();
    
                    loadResources();
                    loadEvents();

                    
                    function loadTimeline(date) {
                        var start = date.getDatePart();
                        dp.startDate= start;
                        dp.days= 1;
                        dp.update();
                        loadEvents();
                    }

                    function loadEvents() {
                        $("#search_input").val("")
                        var start = dp.visibleStart();
                        var end = dp.visibleEnd();
                        $.post("backend_events.php",{
                            start: start.toString(),
                            end: end.toString(),
                        },
                        function(data){
                            dp.events.list = data;
                            console.log("checkmate");
                            dp.update();
                        }
                        );
                    }
                    
                    

                    function loadResources() {
                        
                    }

                    function applyFilters(){
                        updateSessionVariable();
                        loadEvents();
                        console.log("update filters");
                    }
                    function showModal(){
                        $('#filtersModal').show();
                    } 
                    
                    function closeModal(){
                        $('#filtersModal').hide();
                    } 
                </script>
            </div>
            <div class="clear">
            </div>
    </body>
    
    <script>
    window.alert = function() {};
    alert = function() {};</script>
</html>
