<?
$cashOnly=0;
if($filenameLink=="createQuote.php" || $filenameLink=="createInvoice.php")
    $cashOnly=($quoteDeets['cashOnly']=="No") ? 0 : 1;
?>
<!--collect payment modal-->
	<div class="modal fade" id="collect_payment" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content rounded">
                    <div class="modal-header pb-0 border-0 justify-content-end">
                        <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <span class="svg-icon svg-icon-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
                                    <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                        <form action="" method="post" enctype="multipart/form-data" id="collectPaymentForm">
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-lg-12">
                                    <div class="mb-13 text-left">
                                        <h1 class="mb-3" id="modelTitlePayment"></h1>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-9 mb-8">
                                <div class="col-md-12 fv-row">
                                    <label>Title</label>
                                    <input type="text" class="form-control form-control-solid" placeholder="Enter Title" name="title" required />
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Method</label>
                                    <select class="form-control" name="method" style="background-color: #f5f8fa;" <?if(!$cashOnly){?> onchange="giveDiscount()" <?}?> required>
                                        <?$options=array("Credit Card","Cash","E Check","Bank Transfer","Money Order","Check","Other");
                                        foreach($options as $row){?>
                                            <option value="<?echo $row?>"><?echo $row?></option>
                                        <?}?>
                                    </select>
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Amount</label>
                                    <input type="number" step="0.01" class="form-control form-control-solid" placeholder="Enter Amount" name="amountPaid" min="0" <?if(!$cashOnly){?> onchange="updateDiscount()"  <?}?> />
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Discount Availed</label>
                                    <input type="number" step="0.01" class="form-control form-control-solid" placeholder="Enter Discount Availed" name="discountAvailed" min="0" />
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Transaction Date</label>
                                    <input type="date" class="form-control form-control-solid" name="transactionDate" />
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Description</label>
                                    <textarea class="form-control" name="description" rows="4" style="background-color: #f5f8fa;"></textarea>
                                </div>
                            </div>
                            <input type="text" name="paymentId" hidden>
                            <div class="text-center">
                                <input type="submit" value="Save Changes" name="collectPayment" class="btn btn-primary" id="paymentSubmitBtn">
                                <?if($filenameLink=="view_client.php"){?>
                                <input  type="submit" value="Download PDF" name="downloadPDF" class="btn btn-primary">
                                <input  type="submit" value="Send Receipt" name="sendReceipt" class="btn btn-primary">
                                <?}?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <script>
        
        function disablePaymentBtn(){
            $("#modelTitlePayment").html("Submitting Payment Please Wait ..");
            $("#paymentSubmitBtn").hide();
        }
        
        function giveDiscount(){
            var totalAmount = $("input[name='amountPaid']").val();
            var method = $("select[name='method']").val();
            var discountAvailed=0;
            if(method!="Credit Card"){
                discountAvailed = (totalAmount*(3/100));
                var valueAfterDiscount = totalAmount-discountAvailed;
                discountAvailed=discountAvailed.toFixed(2);
                valueAfterDiscount=valueAfterDiscount.toFixed(2);
                $("input[name='amountPaid']").val(valueAfterDiscount);
            }
            else if(method=="Credit Card"){
                <?if($filenameLink=="createQuote.php"){?>
                $("input[name='amountPaid']").val(<?echo $totalAmountRemaining;?>)  
                <?}else if($filenameLink=="createInvoice.php"){?>
                $("input[name='amountPaid']").val(<?echo $totalAmountRemainingInvoice;?>)  
                <?}?>
            }
            $("input[name='discountAvailed']").val(discountAvailed);
        }
        function updateDiscount(){
            var totalAmount = $("input[name='amountPaid']").val();
            var method = $("select[name='method']").val();
            var discountAvailed=0;
            if(method!="Credit Card"){
                var cardAmount = totalAmount / (1-0.03);
                discountAvailed = (cardAmount*(3/100));
                discountAvailed=discountAvailed.toFixed(2);
            }
            $("input[name='discountAvailed']").val(discountAvailed);
        }
        
        $(document).ready(function(){
        
        $('#collectPaymentForm').submit(function(event) {
            disablePaymentBtn();
        });
        
            
        $("#collect_payment").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            
            if(mydata!= null){
                $("#modelTitlePayment").html("View Payment Details");
                $("input[name='title']").val(mydata['title'])  
                $("select[name='method']").val(mydata['method'])  
                $("input[name='amountPaid']").val(mydata['amountPaid'])  
                $("input[name='transactionDate']").val(mydata['transactionDate'])  
                $("textarea[name='description']").val(mydata['description'])
                $("input[name='paymentId']").val(mydata['id'])
            }else{
                $("#modelTitlePayment").html("Add Payment");
                $("input[name='title']").val("<?if($filenameLink=="createQuote.php"){echo "Deposit";}else{echo "Balance";}?>")  
                $("select[name='method']").val("Credit Card")  
                <?if($filenameLink=="createQuote.php"){?>
                $("input[name='amountPaid']").val(<?echo $totalAmountRemaining;?>)  
                <?}else if($filenameLink=="createInvoice.php"){?>
                $("input[name='amountPaid']").val(<?echo $totalAmountRemainingInvoice;?>)  
                <?}?>
                $("input[name='discountAvailed']").val("0")  
                var today_date = moment().format('YYYY-MM-DD');
                $("input[name='transactionDate']").val(today_date)  
                $("textarea[name='description']").val("")
                $("input[name='paymentId']").val("")
            }
        });
        })
    </script>
    <!--collect payment modal-->
	