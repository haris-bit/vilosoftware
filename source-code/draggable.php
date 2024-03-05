<?require("./global.php");
if($logged==0)
    header("Location:./index.php");
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
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl" style="overflow-x:auto;">
							    
                                <input type="text" name="from" hidden>
                                <input type="text" name="to" hidden>
                                <input type="text" name="rowId" hidden>
                                <div class="row">
                                    <div class="col-6" id="section1">
                                        <table class="table table-rounded table-striped border gs-7 text-center" id="draft1" ondrop="drop(event)" ondragover="allowDrop(event)">
                                            <thead>
                                                <tr>
                                                    <th>one</th>
                                                    <th>two</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <? for ($i = 0; $i < 10; $i++) { ?>
                                                    <tr id="section1_<?= $i ?>" draggable="true" ondragstart="drag(event)" data-section="section1" data-rowid="<?= $i."firstSection" ?>">
                                                        <td>one<?= $i ?></td>
                                                        <td>two<?= $i ?></td>
                                                    </tr>
                                                <? } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-6" id="section2">
                                        <table class="table table-rounded table-striped border gs-7 text-center" id="draft2" ondrop="drop(event)" ondragover="allowDrop(event)">
                                            <thead>
                                                <tr>
                                                    <th>one</th>
                                                    <th>two</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Unique IDs for each row -->
                                                <? for ($i = 0; $i < 10; $i++) { ?>
                                                    <tr id="section2_<?= $i ?>" draggable="true" ondragstart="drag(event)" data-section="section2" data-rowid="<?= $i."secondSection" ?>">
                                                        <td>one<?= $i ?></td>
                                                        <td>two<?= $i ?></td>
                                                    </tr>
                                                <? } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                
                                
                                <script>
                                    function drag(ev) {
                                        ev.dataTransfer.setData("text", ev.target.getAttribute("data-section") + "|" + ev.target.getAttribute("data-rowid"));
                                    }
                                
                                    function drop(ev) {
                                        ev.preventDefault();
                                        var data = ev.dataTransfer.getData("text").split("|");
                                        var fromSection = data[0];
                                        var rowId = data[1];
                                        var toSection = ev.target.parentElement.getAttribute("id");
                                        if (fromSection !== toSection) {
                                            document.querySelector("input[name='from']").value = fromSection;
                                            document.querySelector("input[name='to']").value = toSection;
                                            document.querySelector("input[name='rowId']").value = rowId;
                                            alert("Row moved from " + fromSection + " to " + toSection +"rowId="+rowId);
                                        }
                                    }
                                
                                    function allowDrop(ev) {
                                        ev.preventDefault();
                                    }
                                </script>


							</div>
						</div>
					</div>
					<?require("./includes/views/footer.php");?>
					
				</div>
			</div>
			<?require("./includes/views/footerjs.php");?>
	    </div>
	</body>
	
	
	
</html>