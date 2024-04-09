// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

/**
 * This file contains the logic for administering the customers.
 */

import * as adminBase from './admin_base';
import $ from "jquery";

$(function() {
    init();   
});

/**
 * Initializes, sets up the event handlers.
 */
function init() {   
    $('.delete-customer').on('click', function() {
        let customerId = $(this).data('customer-id');
        let customerName = $(this).data('customer-name');
        adminBase.showYesNo(`Are you sure you want to delete permanently customer: ${customerName}`, 'Delete customer?').then((yesBtnClicked) => {
            if(yesBtnClicked) {
                deleteCustomer(customerId);
            }
        });               
    }); 
}

/**
 * Deletes a customer.
 * @param {integer} userId 
 */
function deleteCustomer(userId) {
    $.ajax({
        url: `/admin-customer?id=${userId}`,
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            adminBase.setLoading(true);
        },
        success: function(response) {
            adminBase.showAlert(response.message).then(() => {
                location.reload();
            });            
        },
        error: function(xhr, status, error) {
            let responseJson = JSON.parse(xhr.responseText);
            let errorMsg = responseJson.error && responseJson.error.length > 0 ? responseJson.error : 'An unexpected error occurred!';
            adminBase.showAlert(errorMsg, 'Error');
        },
        complete: function() {
            adminBase.setLoading(false);
        }
    });
}