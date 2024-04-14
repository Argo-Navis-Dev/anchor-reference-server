// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

/*
* This file contains the customer controller functions. 
* It is responsible for handling the customer page events.
* Handles the customer status and customer field status change according to the following logic:   
*   - If the customer status is changed to ACCEPTED, all the fields status are changed to ACCEPTED.
*   - If any of the fields status is changed to a status which is not ACCEPTED, the customer status is changed to PROCESSING.
*   - If all the fields status are changed to ACCEPTED, the customer status is changed to ACCEPTED.
*/

import * as adminBase from './admin_base';
import $ from "jquery";

/**
 * Event handler when the app is ready.
 */
$(document).on('appReady', function (_event, routeName) {    
    if (routeName === 'customer.index') {
        init();
    }    
});

/**
 * Initializes the customer page wires the listeners.
 */
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

/**
 * Handles the customers status change event and updates the fields status accordingly.
 * @param {string} newValue 
 */
function customerStatusChange(newValue) {    
    let fieldsStatusDropdown = $("[id$='_status']"); 
    if(newValue === adminBase.CUSTOMER_STATUS.ACCEPTED) {  
        fieldsStatusDropdown.each(function(index, fieldStatusDropdown) {
            fieldStatusDropdown.value = adminBase.CUSTOMER_FIELD_STATUS.ACCEPTED;   
        });
    }
}

/**
 * Handles the customer field status change event and updates the customer status accordingly.
 * @param {string} newValue 
 */
function customerFieldStatusChanged(newValue) {
    let customerStatusField = $('#customer-status');
    if(newValue !== adminBase.CUSTOMER_FIELD_STATUS.ACCEPTED) {
        customerStatusField.val(adminBase.CUSTOMER_STATUS.PROCESSING);
    }
}