// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

/**
 * This file contains the logic for administering the users.
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
    $('.delete-user').on('click', function() {
        let userId = $(this).data('user-id');
        let userName = $(this).data('user-name');
        adminBase.showYesNo(`Are you sure you want to delete permanently user: ${userName}`, 'Delete user?').then((yesBtnClicked) => {
            if(yesBtnClicked) {                
                deleteUser(userId);
            }
        });         
        
    });
}

/**
 * Deletes the passed user.
 * @param {integer} userId 
 */
function deleteUser(userId) {    
    $.ajax({
        url: `/admin-user?id=${userId}`,
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
            adminBase.showAlert(responseJson.error, 'Warning');
        },
        complete: function() {
            adminBase.setLoading(false);
        }
    });
}