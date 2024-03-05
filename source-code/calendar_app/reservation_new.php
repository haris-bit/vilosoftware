<?include_once("./global.php");?>

<!DOCTYPE html>

<html>

    <head>
        <meta charset="UTF-8">
        <title>Vilo Fence</title>
        <link type="text/css" rel="stylesheet" href="media/layout.css" />    
        <script src="js/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    </head>

    <body>

        <?php
            $start = $_GET['start']; 
            $end = $_GET['end']; 
        ?>

        <form action="" style="padding:20px;">
            <div class="row">
                <div class="col-5">
                    <h4>Actions</h4>
                </div>
                <div class="col-7" style="text-align:right;">
                    <?
                    $newStartTime=strtotime($start);
                    $newEndTime=strtotime($end);
                    
                    $newStartTime=date("h : i A",$newStartTime);
                    $newEndTime=date("h : i A",$newEndTime);
                    ?>
                    <h5><?echo $newStartTime." -- ".$newEndTime?></h5>
                </div>
            </div>
            <?if($permission['add_requests']){?>
            <div class="space">
                <a class="btn btn-primary" target="_top" href="../createRequest.php?new=1&start=<?echo $start?>&end=<?echo $end?>">New Request</a>
            </div>
            <?}?>
            <?if($session_role!="Drafting" && $session_role!="Shop Manager"){?>
            <div class="space">
                <a class="btn btn-primary" target="_top" href="../create_ticket.php?new=1&start=<?echo $start?>&end=<?echo $end?>">New Ticket</a>
            </div>
            <div class="space">
                <a class="btn btn-primary" target="_top" href="../materialDelivery.php?new=1&start=<?echo $start?>&end=<?echo $end?>">New Material Delivery</a>
            </div>
            <?}?>
            <div class="space"><a class="btn btn-warning text-white" href="javascript:close();">Cancel</a></div>

        </form>
        <script type="text/javascript">

        function close(result) {
            if (parent && parent.DayPilot && parent.DayPilot.ModalStatic) 
                parent.DayPilot.ModalStatic.close(result);
        }
    </script>



    </body>
    <style>
		.pac-container {
    z-index: 10000 !important;
}
	</style>
</html>

