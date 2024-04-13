// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

/**
 * This file contains the logic for administering the users.
 */

import $ from "jquery";
import * as adminBase from './admin_base';
import 'bootstrap'
import 'bootstrap-table/dist/bootstrap-table.js'

$(document).on('appReady', function (_event, routeName) {    
    if (routeName === 'users.index') {
        init();
    }    
});

/**
 * Deletes the passed user.
 * @param {integer} userId 
 */
function deleteUser(userId) {    
    $.ajax({
        url: `/user?id=${userId}`,
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            adminBase.setLoading(true);
        },
        success: function (response) {
            adminBase.showAlert(response.message).then(() => {
                location.reload();
            });

        },
        error: function (xhr, status, error) {
            let responseJson = JSON.parse(xhr.responseText);
            adminBase.showAlert(responseJson.error, 'Warning');
        },
        complete: function () {
            adminBase.setLoading(false);
        }
    });
}

window.loadAdminUsersData = function (params) {    
    $.ajax({
        url: `/load-users`,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            adminBase.setLoading(true);
        },
        success: function (response) {
            console.log(response);
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

function userActionFormatter(value, row, index) {
    return `<button type = "button" class="edit-user btn btn-outline-info btn-circle btn-md btn-circle me-2">
                <i class="fa fa-edit"></i> 
            </button>            
            <button type="button" class="delete-user btn btn-outline-info btn-circle btn-md btn-circle ml-2">
                <i class="fa fa-trash"></i>
            </button>`; 
}

function dateTimeFormatter(value, row, index) {    
    return new Date(value).toLocaleString("en-US");
}

window.userActionEvents = {
    'click .edit-user': function (e, value, row, index) {
        window.open(`/user/${row.id}`, '_self');    
    },
    'click .delete-user': function (e, value, row, index) {        
        let userId = row.id;
        let userName = row.name;
        adminBase.showYesNo(`Are you sure you want to delete permanently user: ${userName}`, 'Delete user?').then((yesBtnClicked) => {
            if (yesBtnClicked) {
                deleteUser(row.id);
            }
        });                
    }
}

function init() {
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
                    field: 'name',
                    title: 'Name',
                    sortable: true,
                    align: 'center'
                },
                {
                    field: 'email',
                    title: 'Email',
                    sortable: true,
                    align: 'center'
                },
                {
                    field: 'created_at',
                    title: 'Create at',
                    sortable: true,
                    align: 'center',
                    formatter: dateTimeFormatter
                },
                {
                    field: 'actions',
                    title: 'Actions',
                    align: 'center',
                    clickToSelect: false,
                    events: window.userActionEvents,
                    formatter: userActionFormatter
                  }
            ]
        ]
    });    
}

function getTable() {
    return $('#admin-users-table');
}