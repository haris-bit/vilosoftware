<?
require("./global.php");

$quoteId=clear($_GET['quoteId']);
$services=getAll($con,"select * from darlelJobber_services where localUseId='None' || localUseId='$quoteId'");
$nameToDeetsServices=[];
foreach($services as $row){
    $index=$row['name']." SKU =".$row['sku'];
    $nameToDeetsServices[$index]=$row;
}

if(isset($_GET['edit'])){
    $id=clear($_GET['edit']);
    $planDeets=getRow($con,"select * from darlelJobber_site_plans where id='$id'");
}

$timeAdded=time();
$view = (isset($_GET['view'])) ? 1 :0 ;
$new = (isset($_GET['new'])) ? 1 :0;
$sitePlanId = ($new) ? clear($_GET['new']) : $planDeets['id'];

if($new)
    $customeUrl="create_site_plan.php?new=$sitePlanId&quoteId=$quoteId";
else
    $customeUrl="create_site_plan.php?edit=$sitePlanId&quoteId=$quoteId";
    

$quoteServicesToDeets=[];
$quoteServices=getAll($con,"select * from darlelJobber_quote_details where quoteId='$quoteId'");
foreach($quoteServices as $row){
    $quoteServicesToDeets[$row['service']]=$row;
}

$nameToShortTitle=[];
foreach($services as $row)
{$nameToShortTitle[$row['name']." SKU =".$row['sku']]=$row['short_title'];}

$image="";
if($planDeets['image']!=""){
    $image="./uploads/".$planDeets['image'];
    $image='"'.$image.'"';
}

?>
<html>
    <head>
    <?require("./includes/views/head.php");?>
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <link href="includes/autocompletecss.css" rel="stylesheet" type="text/css"/>
    
      <script>
  $( function() {
    var availableTags = [
    <?foreach($services as $row){?>
      `<?echo $row['name']." SKU =".$row['sku']." Price = ".$row['price']?>`,
      <?}?>
    ];
    
  $("#tags").autocomplete({
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
  }).autocomplete("widget").addClass("scrollable-autocomplete");;
});
  </script>
    
    </head>
    <body style="background-color: white;">
        <?if(isset($_GET['serviceSubmitted'])){
            $service = str_replace('\\', '', $_GET['serviceSubmitted']);
            $service = urldecode($_GET['serviceSubmitted']);
        }?>
        
        <div class="">
            <div class="col-12 btn-group">
                <a class="btn btn-danger btn-sm text-white" onclick="clearService()">
                    <p style="margin-bottom: 0;font-size: 18px;">X</p>
                </a>
                <input id="tags" name="service_input" type="text" class="form-control" placeholder="Enter Service" value="<?echo $service;?>">
            </div>
        </div>
    </body>
    <script>
        function clearService(){
            $('#tags').val("");
        }
         $(document).ready(function() {
    // Add the d-none class to the element with the class ui-helper-hidden-accessible
    $(".ui-helper-hidden-accessible").addClass("d-none");
  });
    </script>
</html>