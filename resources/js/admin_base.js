// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

/**
 * This file contains the base functions for the admin pages.
 */

import $ from "jquery";
import * as bootstrap from 'bootstrap'

export { setLoading, showAlert, showYesNo };

/**
 * Shows or hides the loading overlay.
 * @param {boolean} visible flag to show or hide the overlay.
 */
function setLoading(visible) {
    if (visible) {
        $('#loading-overlay').fadeIn(300);
        $('#loading-overlay').css('display', 'inline-flex'); 
    } else {
        $('#loading-overlay').fadeOut(300);
    }
}

/**
 * Shows a modal dialog.
 * @param {string} msg The dialog message. 
 * @param {string} title The dialog title.
 * @returns 
 */
function showAlert(msg, title) {       
    let myModal = new bootstrap.Modal($('#info-dialog'), {
        keyboard: false        
    });
    $('#info-dialog .modal-body').html(msg);         
    if(title) {
        $('#info-dialog .modal-title').html(title);
    }        
    myModal.toggle();
    return new Promise(function(resolve, _reject) {
        document.getElementById('info-dialog').addEventListener('hide.bs.modal', event => {            
            resolve();
        });
    });
}

/**
 * Shows a modal dialog with a yes/no question.
 * @param {string} msg The dialog message. 
 * @param {*} title The dialog title.
 * @returns 
 */
function showYesNo(msg, title) {    
    let myModal = new bootstrap.Modal($('#yesno-dialog'), {
        keyboard: false        
    });
    $('#yesno-dialog .modal-body').html(msg);         
    if(title) {
        $('#yesno-dialog .modal-title').html(title);
    }        
    myModal.toggle();    
    return new Promise(function(resolve, _reject) {
        $('#yesno-dialog .btn-yes').off('click').on('click', function() {
            resolve(true);
            myModal.hide();            
        });
        $('#yesno-dialog .btn-no').off('click').on('click', function() {
            resolve(false);            
            myModal.hide();               
        });
    });
}