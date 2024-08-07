// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

/**
 * This file contains the logic for administering the customers.
*/

import * as adminBase from './admin_base';
import $ from "jquery";

/**
 * Event handler when the app is ready.
 */
$(document).on('appReady', function (_event, pageName) {    
    if (pageName === 'customers-page') {
        init();
    }    
});


/**
 * Deletes the passed customer.
 * @param {integer} userId 
 */
function deleteCustomer(userId) {
    $.ajax({
        url: `/customer?id=${userId}`,
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

/**
 * Special renderer for the customer actions column.
 * @param {string} value 
 * @param {object} row 
 * @param {integer} index 
 * @returns 
 */
function customerActionFormatter(value, row, index) {
    return `<button type = "button" class="edit-customer btn btn-outline-info btn-circle btn-md btn-circle me-2">
                <i class="fa fa-edit"></i> 
            </button>            
            <button type="button" class="delete-customer btn btn-outline-info btn-circle btn-md btn-circle ml-2">
                <i class="fa fa-trash"></i>
            </button>`; 
}

/**
 * Event handlers for the customer actions.
 */
window.customerActionEvents = {
    'click .edit-customer': function (e, value, row, index) {
        window.open(`/customer/${row.id}`, '_self');    
    },
    'click .delete-customer': function (e, value, row, index) {        
        let customerId = row.id;
        let customerName = row['first_name'] + ' ' + row['last_name'];
        adminBase.showYesNo(`Are you sure you want to delete permanently customer: ${customerName}`, 'Delete customer?').then((yesBtnClicked) => {
            if(yesBtnClicked) {
                deleteCustomer(customerId);
            }
        });                
    }
}

/**
 * Loads the customers data.
 * @param {*} params 
 */
window.loadCustomers = function (params) {    
    $.ajax({
        url: `/load-customers`,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            adminBase.setLoading(true);
        },
        success: function (response) {            
            params.success({
                "rows": response,
                "total": response.length
            })
        },
        error: function (xhr, status, error) {
            adminBase.showAlert(responseJson.error, 'Warning');
        },
        complete: function () {
            adminBase.setLoading(false);
        }
    });
}

/**
 * Initializes the customers page wires the listeners.
 */
function init() {
    const CLEAR_FILTER = 'NONE';
    $('#customer-status-filter').on('change', function() {        
        var selectedStatus = $(this).val();
        let filterOptions = {};
        if(selectedStatus !== CLEAR_FILTER) {
            filterOptions.status = selectedStatus;
        }
        getTable().bootstrapTable('filterBy', filterOptions);    
    });

    getTable().bootstrapTable('destroy').bootstrapTable({
        height: 550,
        locale: $('#locale').val(),
        columns: [
            [
                {
                    title: 'ID',
                    field: 'id',                    
                    align: 'center',
                    valign: 'middle',
                    sortable: true
                },              
                {
                    field: 'first_name',
                    title: 'First name',
                    sortable: true,
                    align: 'center'
                },
                {
                    field: 'last_name',
                    title: 'Last name',
                    sortable: true,
                    align: 'center'
                },
                {                    
                    field: 'account_id',
                    title: 'Account ID',
                    sortable: true,
                    align: 'center'
                },
                {
                    field: 'email_address',
                    title: 'Email',
                    sortable: true,
                    align: 'center'
                },
                {
                    field: 'status',
                    title: 'Status',
                    sortable: true,
                    align: 'center'
                },                
                {
                    field: 'actions',
                    title: 'Actions',
                    align: 'center',
                    width: 200,
                    clickToSelect: false, 
                    events: window.customerActionEvents,
                    formatter: customerActionFormatter
                  }
            ]
        ]
    });    
}

/**
 * Returns the customers table element.
 * @returns jQuery object
 */
function getTable() {
    return $('#customers-table');
}