<?
require("global.php");
if($logged==0 || (!$permission['view_requests']))
    header("Location:./index.php");

$current_url = $_SERVER['REQUEST_URI'];
$new_param = 'm=Data updated successfully';
if (strpos($current_url, '?') !== false) 
    $updated_url = $current_url . '&' . $new_param;
else 
    $updated_url = $current_url . '?' . $new_param;

$leadSources=getAll($con,"select * from darlelJobber_lead_source order by timeAdded desc");
$idToLeadSource=[];
foreach($leadSources as $row)
    $idToLeadSource[$row['id']]=$row;
if(isset($_GET['delete-request'])){
    $id = clear($_GET['delete-request']);
    
    runQuery("delete from darlelJobber_requests where id='$id'");
    runQuery("delete from darlelJobber_quotes where requestId='$id'");
    runQuery("delete from darlelJobber_jobs where requestId='$id'");
    runQuery("delete from darlelJobber_invoices where requestId='$id'");
    
    header("Location:?m=deleted");
}

$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row){
    $idToInfo[$row['id']]=$row;
}

if(isset($_GET['send'])){
    $id=$_GET['send'];
    $query="update darlelJobber_requests set sendStatus='Request Sent' where id='$id'";
    runQuery($query);
    header("Location:?m=message");
}
$message="Your request has been received . We will get back to you shortly. Thank you";

$idToInfoProperty=[];
$query=getAll($con,"select id,concat(street1,' ',street2,',',city,',',state,',',country,'( Zip : ',zip_code,')') as 'Address' from darlelJobber_properties");
foreach($query as $row)
    $idToInfoProperty[$row['id']]=$row['Address'];    


//setting up filters for the query
$noFilterLeadSourceExtraQuery=" 1 ";
$leadSourceFilter=clear($_GET['leadSourceFilter']);
if($leadSourceFilter=="All" || (!isset($_GET['leadSourceFilter'])))
    $leadSourceFilterString="";
else{
    $leadSourceFilterString=" and leadSourceId='$leadSourceFilter'";
    $noFilterLeadSourceExtraQuery="leadSourceId='$leadSourceFilter'";
}

$search=clear($_GET['search']);
$searchEnabled = ((isset($_GET['search'])) && ($search!="")) ? 1 : 0;
$searchExtraQuery="";
if($searchEnabled)
    $searchExtraQuery=" and (CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%' or r.title like '%$search%') ";
    
$filter=clear($_GET['filter']);

$normalFilterEnabled = (!isset($_GET['filter'])  || $filter=="All" ) ? 0 : 1 ;

$page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
$lastEntryNo=!empty($_GET['lastEntryNo']) ? (int) $_GET['lastEntryNo'] : 0;
$firstEntryNo=!empty($_GET['firstEntryNo']) ? (int) $_GET['firstEntryNo'] : 0;

if((!isset($_GET['page'])) || (isset($_GET['lastEntryNo']))){
    $autoIncrementQuery=" and r.autoIncrement > $lastEntryNo ";
    $orderBy="asc";
}
else{
    $autoIncrementQuery=" and r.autoIncrement < $firstEntryNo ";
    $orderBy="desc";
}
    

//if filter is disabled
if(!$normalFilterEnabled){
    //echo "in 1 <br>";
    $query="select count(r.id) as totalPages,max(r.autoIncrement) as maxAutoInc from darlelJobber_requests 
    r inner join darlelJobber_users u on r.request_for=u.id where r.id is not null $searchExtraQuery $leadSourceFilterString";
    $totalPages=getRow($con,$query);
    
    $finalQuery="select r.*,r.id as requestId,r.addedBy as mainAddedBy from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id 
    where r.id is not null $autoIncrementQuery $searchExtraQuery $leadSourceFilterString order by autoIncrement $orderBy limit 10";
}
//if lead source filter is enabled/disabled and normal filter is enabled and search is disabled
else if($normalFilterEnabled){
    //echo "in 5 <br>";
    
    if($filter=="Today"){
        $dateToday=date("d m y",time());
        
        $query="select count(r.id) as totalPages,max(r.autoIncrement) as maxAutoInc from darlelJobber_requests 
        r inner join darlelJobber_users u on r.request_for=u.id where r.sendStatus='Request Sent' 
        && from_unixtime(r.start_date,'%d %m %y')='$dateToday' $searchExtraQuery $leadSourceFilterString";
        $totalPages=getRow($con,$query);
        
        $finalQuery="select r.*,r.id as requestId,r.addedBy as mainAddedBy from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where r.sendStatus='Request Sent' 
        && from_unixtime(r.start_date,'%d %m %y')='$dateToday' $searchExtraQuery $leadSourceFilterString $autoIncrementQuery order by r.autoIncrement $orderBy limit 10";
    }
    else if($filter=="Converted" || $filter=="Assessment Completed"){
        $query="select count(r.id) as totalPages,max(r.autoIncrement) as maxAutoInc from darlelJobber_requests 
        r inner join darlelJobber_users u on r.request_for=u.id where r.sendStatus='Request Sent' && convertStatus='Converted' $searchExtraQuery $leadSourceFilterString";
        
        $totalPages=getRow($con,$query);
        
        $finalQuery="select r.*,r.id as requestId,r.addedBy as mainAddedBy from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where r.sendStatus='Request Sent' && convertStatus='Converted'
        $searchExtraQuery $leadSourceFilterString $autoIncrementQuery order by r.autoIncrement $orderBy limit 10";
    }
    else if($filter=="Upcoming"){
        $timeTomorrow=time();
        $query="select count(r.id) as totalPages,max(r.autoIncrement) as maxAutoInc from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where
        r.sendStatus='Request Sent' && r.start_date > $timeTomorrow $searchExtraQuery $leadSourceFilterString";
        $totalPages=getRow($con,$query);
        
        
        $finalQuery="select r.*,r.id as requestId,r.addedBy as mainAddedBy from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where r.sendStatus='Request Sent' && r.start_date > $timeTomorrow
        $searchExtraQuery $leadSourceFilterString $autoIncrementQuery order by r.autoIncrement $orderBy limit 10";
    }
    else if($filter=="New"){
        $query="select count(r.id) as totalPages,max(r.autoIncrement) as maxAutoInc from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where
        r.sendStatus='Request Sent' && r.convertStatus='Not Converted' $searchExtraQuery $leadSourceFilterString";
        $totalPages=getRow($con,$query);
        
        $finalQuery="select r.*,r.id as requestId,r.addedBy as mainAddedBy from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where r.sendStatus='Request Sent'
        && r.convertStatus='Not Converted' $searchExtraQuery $leadSourceFilterString $autoIncrementQuery order by r.autoIncrement $orderBy limit 10";
    }
    else if($filter=="Over Due"){
        $query="select count(r.id) as totalPages,max(r.autoIncrement) as maxAutoInc from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where
        r.sendStatus='Request Sent' && from_unixtime(r.end_date,'%d %m %y') < DATE_FORMAT(SYSDATE(),'%d %m %y') && r.convertStatus='Not Converted' $searchExtraQuery $leadSourceFilterString";
        $totalPages=getRow($con,$query);
        
        $finalQuery="select r.*,r.id as requestId,r.addedBy as mainAddedBy from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where 
        r.sendStatus='Request Sent' && from_unixtime(r.end_date,'%d %m %y') < DATE_FORMAT(SYSDATE(),'%d %m %y') && r.convertStatus='Not Converted'
        $searchExtraQuery $leadSourceFilterString $autoIncrementQuery order by r.autoIncrement $orderBy limit 10";
    }
    else if($filter=="Late Requests"){
        $query="select count(r.id) as totalPages,max(r.autoIncrement) as maxAutoInc from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where
        r.requestStatus='Late' $searchExtraQuery $leadSourceFilterString";
        $totalPages=getRow($con,$query);
        
        $finalQuery="select r.*,r.id as requestId,r.addedBy as mainAddedBy from darlelJobber_requests r inner join darlelJobber_users u on r.request_for=u.id where r.requestStatus='Late' 
        $searchExtraQuery $leadSourceFilterString $autoIncrementQuery order by r.autoIncrement $orderBy limit 10";
    }
}

$maxAutoInc=$totalPages['maxAutoInc']+1;//adding plus one to display correct results 
$totalPages=ceil($totalPages['totalPages']/10);
$queryRequest=getAll($con,$finalQuery);

//this would be passed in pagination 
$lastEntryNo=max(array_column($queryRequest, 'autoIncrement'));
$firstEntryNo=min(array_column($queryRequest, 'autoIncrement'));



$extraHref="";
if($searchEnabled){  
    $tempSearch = urldecode($_GET['search']);
    $extraHref="&search=".$tempSearch;
}
if(isset($_GET['leadSourceFilter'])){
    $leadSourceFilter=clear($_GET['leadSourceFilter']);
    $extraHref=$extraHref."&leadSourceFilter=".$leadSourceFilter;
}
if(isset($_GET['filter'])){
    $filter=clear($_GET['filter']);
    $extraHref=$extraHref."&filter=".$filter;
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
							<div id="kt_content_container" class="container-xxl" style="max-width: 100%;">
							    <?if(isset($_GET['m'])){?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <?if ($_GET['m']=="message"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $message?></h4>
                                        <?}else if ($_GET['m']=="deleted"){?>
                                            <h4 class="mb-2 light" style="color: white;margin-top: 5px;">Request Has Been Deleted Successfully</h4>
                                        <?}?>
                                    </div>
                                </div>
                                <?}?>
                                
                                <div class="card card-flush" style="margin-bottom: 40px !important;">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
												<?if($session_role=="Client"){?>
												<input type="text" data-kt-ecommerce-category-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search Requests " />
											    <?}?>
											</div>
										</div>
										<div class="card-toolbar">
										    
								            <?if($session_role!="Client"){
										        $option=$_GET['filter'];
										        $filters=array("All","Today","Assessment Completed","New","Upcoming","Converted","Over Due","Late Requests");
									            $search = str_replace('\\', '', $_GET['search']);
                                                $search = urldecode($_GET['search']);
                                                ?>
										        <input name="search" type="text" class="form-control w-250px me-4 " placeholder="Search Requests" value="<?echo $search;?>" />
										        <a onclick="submitForm()" class="btn btn-success  me-2">Apply Filters</a>
											    <select class="btn btn-primary  me-2" onchange="submitForm()" name="leadSourceFilter" >
                                                    <option <?if($leadSourceFilter=="All" || (!isset($_GET['leadSourceFilter']))){echo "selected";}?> value="All">Lead Source : All</option>
                                                    <?foreach($leadSources as $row){?>
                                                    <option <?if($row['id']==$leadSourceFilter){echo "selected ";}?> value="<?echo $row['id']?>"><?echo "Lead Source : ".$row['title'];?></option>
                                                    <?}?>
                                                </select> 
										        <select name="filter" class="btn btn-primary  me-2" onchange="submitForm()">
										            <?foreach($filters as $row){?>
										            <option <?if($row==$option){echo "selected";}?> value="<?echo $row?>"><?echo $row?></option>
										            <?}?>
										        </select>
										    
									        <?}?>
								            
								            <?if($permission['add_requests']){?>
											<a href="createRequest.php?new=1" class="btn btn-primary ">New Request</a>
										    <?}?>
										
										</div>
									</div>
									<div class="card-body pt-0">
                                        <div class="table-responsive">
                                                <?if($session_role!="Client"){?>
    											<table class="table table-rounded table-striped border gs-7 text-center">
    											<thead>
    												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0 text-center">
    													<th>Title</th>
    													<th >Client</th>
    													<th class="mobile_view">Assessment</th>
    													<th class="mobile_view">Timing</th>
    													<th class="mobile_view">Status</th>
    													<th class="mobile_view">Lead Source</th>
    													<?if($isAdmin){?>
    													<th class="mobile_view">Added By</th>
    													<?}?>
    													<th>Actions</th>
    												</tr>
    											</thead>
    											<tbody class="fw-bold text-gray-600">
    											    <?foreach($queryRequest as $row){?>
											        <tr>
    											        <td><a href="./createRequest.php?entryId=<?echo $row['requestId']?>&view=1"><?echo $row['title']?></a></td>
    											        <td >
    											            <a href="view_client.php?id=<?echo $row['request_for']?>">
    											                <?
    											                if($idToInfo[$row['request_for']]['showCompanyName']=="Yes")
    									                            echo $idToInfo[$row['request_for']]['company_name']." (".$idToInfo[$row['request_for']]['first_name']." ".$idToInfo[$row['request_for']]['last_name'].")";
    											                else   
    											                    echo $idToInfo[$row['request_for']]['first_name']." ".$idToInfo[$row['request_for']]['last_name']?>
    											            </a>
    										            </td>
    											        <td class="mobile_view"><?if($row['scheduleStatus']=="Schedule Later"){echo "Schedule Later";}else{echo date("d M Y",$row['start_date'])."-".date("d M Y",$row['end_date']);}?></td>
    											        <td class="mobile_view"><?if($row['scheduleStatus']=="Schedule Later"){echo "Schedule Later";}else{echo date("h:i A",strtotime($row['start_time']))."-".date("h:i A",strtotime($row['end_time']));}?></td>
    											        <td class="mobile_view"><a class="badge badge-<?if($row['convertStatus']=="Not Converted"){echo "warning";}else{echo "success";}?>" style="white-space: pre;"><?echo $row['convertStatus']?></a></td>
    													<td class="mobile_view"><?echo $idToLeadSource[$row['leadSourceId']]['title']?></td>
    													<?if($isAdmin){?>
    													    <td class="mobile_view"><?echo $idToInfo[$row['mainAddedBy']]['name']?></td>
    													<?}?>
    											        <td>
    											            <div class="btn-group">
        											            <?if($permission['view_requests']){?>
        										                    <a href="createRequest.php?entryId=<?echo $row['requestId']?>&view=1" class="btn btn-primary btn-sm">View</a>
        										                <?}?>
        										                <?if($permission['edit_requests']){?>
        										                    <a href="createRequest.php?entryId=<?echo $row['requestId']?>" class="btn btn-warning btn-sm">Edit</a>
        										                <?}?>
        										                <?if($permission['delete_requests']){?>
            													    <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-request=<?echo $row['id']?>" class="btn btn-danger btn-sm">Delete</a>
            												    <?}?>
        												    </div>
    												    </td>
        											</tr>
    											    <?}?>
    											</tbody>
    											</table>
    											<?}else if($session_role=="Client"){?>
    											<table class="table table-rounded table-striped border gs-7 text-center" id="kt_ecommerce_category_table">
    											<thead>
    												<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
    													<th>First Day - Second Day</th>
    													<th>Property</th>
    													<th>Status</th>
    													<th>Actions</th>
    												</tr>
    											</thead>
    											<tbody class="fw-bold text-gray-600">
    											    <?
        										    $query="select *,r.id as requestId,r.addedBy as addedBy from darlelJobber_requests  r where r.sendStatus='Pending' || r.request_for='$session_id' || r.addedBy='$session_id' order by timeAdded desc";
    											    $requests=getAll($con,$query);
    											    foreach($requests as $row){?>
    											    <tr>
    											        <td><?echo date("d M Y",$row['appointment_first_day'])."-".date("d M Y",$row['appointment_second_day']);?></td>
    											        <td><?echo $idToInfoProperty[$row['propertyId']];?></td>
    											        <td><a class="btn btn-<?if($row['sendStatus']=="Pending"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['sendStatus']?></a></td>
    													<td>
    													    <div class="btn-group">
        											            <a href="createRequest.php?entryId=<?echo $row['requestId']?>&view=1" class="btn btn-primary btn-sm">View</a>
        									                    <?if($row['sendStatus']=="Pending"){?>
        										                <a href="?send=<?echo $row['id']?>" class="btn btn-warning btn-sm">Send Request</a>
        										                <a href="createRequest.php?entryId=<?echo $row['requestId']?>" class="btn btn-warning btn-sm">Edit</a>
        									                    <?}?>
    									                    </div>
        												</td>
        											</tr>
    											    <?}?>
    											</tbody>
    										    </table>	
    											<?}?>
    									    <?if($session_role!="Client"){?>
    										<div class="row w-100">
    										<nav aria-label="Page navigation example mt-3" style="margin: 1px auto;">
    											<ul class="pagination justify-content-center">
                                                    <style>
    													.selected-page-item {
    														border-bottom-color: #591df1;
    														border-bottom-width: 5px;
    														border-bottom-style: solid;
    													}
                                                        .selected-page-link {
    														border: 1px solid #591df1;
    														background: white;
    														color: #591df1;
    														margin-bottom: 25px;
    														border-width: 2px;
    													}
    												</style>
                                                    <?if($page >= 3){
                                                        ?>
                                                        <!--first page block-->
                                                        <li class="page-item">
														    <a class="page-link" href="?page=1&lastEntryNo=0&search=<?echo $tempSearch;
														    if(isset($_GET['leadSourceFilter'])){echo "&leadSourceFilter=$leadSourceFilter";}
														    if(isset($_GET['filter'])){echo "&filter=$filter";}?>">1</a>
														</li>
														<b style="font-weight: bold;font-size: x-large;margin-left: 10px;margin-right: 10px;">....</b>
                                                        <?
                                                    }
                                                    for($i = ($page - 1); $i < ($page + 2); $i++) {
    													if ($i > 0 && $i <= $totalPages) {
    													    $lastRenderedPage=$i;?>
    														<li class="page-item <? if ($page == $i) {echo "selected-page-item";} ?>">
															    <a class="page-link 
    															    <?
    															    $doNothing=0;
    															    if ($page == $i) {echo "selected-page-link";}
    															    //the pages ahead of the selected page will send their last entry no and the pages before the selected page will send their first entry no
    															    if($page > $i)
    															        $href="?page=$i&firstEntryNo=$firstEntryNo";
    															    else if($page < $i)
    															        $href="?page=$i&lastEntryNo=$lastEntryNo";
    															    else
    															        $doNothing=1;//hides the link if the on current page
    															    ?>" 
        															<?if(!$doNothing){?>href="<?echo $href.$extraHref?>"<?}?>>
															        <? echo $i ?>
    															</a>
															</li>
    												<? }}
    												if($totalPages-$lastRenderedPage >= 1){?>
    												    <!--last page block-->
    												    <b style="font-weight: bold;font-size: x-large;margin-left: 10px;margin-right: 10px;">....</b>
                                                        <li class="page-item">
														    <a class="page-link" href="?page=<?echo $totalPages?>&firstEntryNo=<?echo $maxAutoInc.$extraHref?>"><?echo $totalPages?></a>
														</li>
    												    <?}?>
												</ul>
    										</nav>
                                        </div>
    									    <?}?>
    									</div>	
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
	<?require("./includes/views/footerjs.php");?>
	
	
		</div>
	</body>
	<script>
	function submitForm(){
        var search=$("input[name='search']").val();
        var page="<?echo clear($_GET['page'])?>";
        var firstEntryNo="<?echo clear($_GET['firstEntryNo'])?>";
        var lastEntryNo="<?echo clear($_GET['lastEntryNo'])?>";
        var leadSourceFilter = $("select[name='leadSourceFilter']").val();
        var filter = $("select[name='filter']").val();
        if(page=="" && firstEntryNo=="" && lastEntryNo=="")
            window.location.href = window.location.pathname + '?leadSourceFilter=' + leadSourceFilter+'&filter='+filter+'&search='+search;
        else if(page!="" && firstEntryNo=="" && lastEntryNo=="")
            window.location.href = window.location.pathname + '?leadSourceFilter=' + leadSourceFilter+'&filter='+filter+'&page='+page+'&search='+search;
        else if(firstEntryNo!="")
	        window.location.href = window.location.pathname + '?leadSourceFilter=' + leadSourceFilter+'&filter='+filter+'&firstEntryNo='+firstEntryNo+'&page='+page+'&search='+search;
        else if(lastEntryNo!="")
	        window.location.href = window.location.pathname + '?leadSourceFilter=' + leadSourceFilter+'&filter='+filter+'&lastEntryNo='+lastEntryNo+'&page='+page+'&search='+search;
    }
	</script>
</html>