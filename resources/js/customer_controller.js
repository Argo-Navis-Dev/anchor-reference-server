import * as adminBase from './admin_base';
import $ from "jquery";

$(document).on('appReady', function (_event, routeName) {    
    if (routeName === 'customer.index') {
        init();
    }    
});

function init() {
    let customerStatusDropdown = $('#customer-status');
    customerStatusDropdown.on('change', function() {        
        let newValue = $(this).val();
        customerStatusChange(newValue);
    });

    let fieldsStatusDropdown = $("[id$='_status']"); 
    fieldsStatusDropdown.on('change', function() {        
        let newValue = $(this).val();          
        customerFieldStatusChanged(newValue);

        let allFieldsAreAccepted = true;
        fieldsStatusDropdown.each(function(index, fieldStatusDropdown) {
            if($(fieldStatusDropdown).val() !== adminBase.CUSTOMER_FIELD_STATUS.ACCEPTED) {                
                allFieldsAreAccepted = false;
            }
        });        
        if(allFieldsAreAccepted) {
            customerStatusDropdown.val(adminBase.CUSTOMER_STATUS.ACCEPTED);
        }
    });
}

function customerStatusChange(newValue) {    
    let fieldsStatusDropdown = $("[id$='_status']"); 
    if(newValue === adminBase.CUSTOMER_STATUS.ACCEPTED) {  
        fieldsStatusDropdown.each(function(index, fieldStatusDropdown) {
            fieldStatusDropdown.value = adminBase.CUSTOMER_FIELD_STATUS.ACCEPTED;   
        });
    }
}

function customerFieldStatusChanged(newValue) {
    let customerStatusField = $('#customer-status');
    if(newValue !== adminBase.CUSTOMER_FIELD_STATUS.ACCEPTED) {
        customerStatusField.val(adminBase.CUSTOMER_STATUS.PROCESSING);
    }
}