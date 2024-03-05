"use strict";
var KTAppEcommerceCategories = function() {
    var t, e, n = () => {
        
    };
    return {
        init: function() {
    var currentPath = window.location.pathname;
    var fileName = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    var t = document.querySelector("#kt_ecommerce_category_table");
    var e, dataTable;

    if (t) {
        if (fileName != 'shopSchedule.php') {
            dataTable = $(t).DataTable({
                pageLength: 10,
                order: [],
                info: false
            });
        } else if(fileName == 'shopSchedule.php') {
            dataTable = $(t).DataTable({
                paging: false, // Disable paging
                order: [],
                info: false
            });
        }
        dataTable.on("draw", function() {
            n();
        });

        document.querySelector('[data-kt-ecommerce-category-filter="search"]').addEventListener("keyup", function(t) {
            dataTable.search(t.target.value).draw();
        });

        n();
    }
}

    }
}();
KTUtil.onDOMContentLoaded((function() {
    KTAppEcommerceCategories.init()
}));