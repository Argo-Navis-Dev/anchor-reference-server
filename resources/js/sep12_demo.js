/**
 * This is a simple demo of how to use the SEP-10 and SEP-12 endpoint in the Anchor Reference Server.
 */
import * as bootstrap from 'bootstrap'
import $ from "jquery";

$(function() {    
    init();    
});

/**
 * Authenticates the user:
 * 1. Sends a request contianing the Stellar accountID to the server to get a transaction to be signed by the user.
 * 2. Signs the transaction with the Freighter wallet extension.
 * 3. Sends the signed transaction to the server to get a JWT token.
 * 4. If the token is received, the registration form is shown.
 * @param {string} accountId 
 */
function authenticate(accountId) { 
    setLoading(true);
    fetch(`/auth?account=${accountId}`)
        .then(response => response.json())
        .then(response => {
            let transaction = response.transaction;            
            signTransactionAndGetJWTToken(accountId, transaction).then(response => response.json()).then(response => {
                let token = response.token;
                setLoading(false);
                if(token) {
                    $('#registration-form-wrapper').fadeIn(300);
                    $('#authenticate-wrapper').fadeOut(300);
                    $('#authenticated-as-wrapper').fadeIn(300);
                    $('#authenticated-as-wrapper h3').html(`Authenticated as ${accountId}`);                    
                    localStorage.setItem("accessToken", token);
                    localStorage.setItem("accountId", accountId);
                    showAlert('JWT token received. <br> Please fill in the registration form to continue.');

                }
            }).catch(error => {   
                setLoading(false);         
                console.error(error);
            });
           
        })
        .catch(error => {
            console.error(error);            
    });
};

/**
 * Signs the passed transaction with the Freighter wallet extension and sends the signed transaction to the server to get a JWT token.
 * @param {string} accountId Stellar account id.
 * @param {string} transaction The transaction to be signed.
 * @returns Promise object.
 */
async function signTransactionAndGetJWTToken(accountId, transaction) {
    if(await window.freighterApi.isConnected()) {
        if (!await window.freighterApi.isAllowed()) {
            if (!await window.freighterApi.setAllowed()) {
                alert("Error: Could not add Anchor Reference server to Freighter's allow List");
                return
            }
        }
        try {                                    
            let signedTransaction = await window.freighterApi.signTransaction(transaction, {network: STELLAR_NETWORK,});                    
            return fetch(`/auth?account=${accountId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({transaction: signedTransaction})
            })
            
        }catch (error) {
            console.error(error);
        };
    } else {
        alert("Error: Freighter browser extension could not be located")
    }
}

/**
 * Registers or updates the customer data in the server.
 * If the registration is successful, the customer id is stored in the local storage and the email verification form is shown.
 * @param {Object} form The HTML form element to be submitted.
 */
function registerOrUpdateCustomer(form) {    
    setLoading(true);
    let token = localStorage.getItem("accessToken");    
    let customerId = localStorage.getItem('customerId');

    let formData = new FormData($(form)[0]);
    // Remove empty values from the FormData
    let emptyKeys = [];
    for (let [key, value] of formData.entries()) {        
        if (value === '' || value?.size == 0) {
            emptyKeys.push(key);
        }
    }
    emptyKeys.forEach(key => formData.delete(key));

    $.ajax({
        url: `/customer?id=${customerId}`,            
        type: $(form).attr('method'),
        headers: {
            'Authorization': `Bearer ${token}`
        },
        data: formData, 
        processData: false, 
        contentType: false, 
        success: function(response) {            
            setLoading(false); 
            
            if(customerId == null && response.id) {                              
                localStorage.setItem("customerId", response.id);
                showAlert(`Your ID: ${response.id}. <br> Next, confirm your email address.`);
                $('.verification-form-wrapper').fadeIn(300);
                updateCustomerFormLabels();
            }else {
                showAlert(`Your information has been updated successfully.`);      
            }
            $('.registration-form-wrapper').fadeOut(300);
            $('#id_number').val('');
            $('#photo_id_front').val('');
            $('#photo_id_back').val('');
            $('#email_address').val('');                
            $('#first_name').val('');                
            $('#last_name').val('');                
                                                    
        },
        error: function(error) {
            console.error(error);
        }
    });
     
}

/**
 * Verifies the email address of the customer, if correct shows the customer info.
 */
function verifyEmail() {
    let token = localStorage.getItem("accessToken");
    let customerId = localStorage.getItem("customerId");
    
    let verificationCode = $('#verification-code').val();
    let formData = new FormData();
    formData.append('id', customerId);
    formData.append('email_address_verification', verificationCode);
    setLoading(true);
    $.ajax({
        url: '/customer/verification',
        type: 'PUT',
        headers: {
            'Authorization': `Bearer ${token}`
        },
        data: formData,
        processData: false, 
        contentType: false, 
        success: function(response) {
            setLoading(false);
            $('.verification-form-wrapper').fadeOut(300);  
            $('.customer-info-wrapper').fadeIn(300);
            showAlert(`Email address verified! <br> You can view your customer info.`); 
            refreshCustomerInfo();
        },
        error: function(error) {
            console.error(error);
        }
    });    
}

/**
 * Refreshes the customer info section.
 */
function refreshCustomerInfo() {
    let customerId = localStorage.getItem("customerId");
    let token = localStorage.getItem("accessToken");
    if(customerId) {
        setLoading(true);
        fetch(`/customer?id=${customerId}`, {
            headers: {'Authorization': `Bearer ${token}`}
        }).then(response => response.json())
            .then(response => {
                setLoading(false);
                console.log(response); 
                updateCustomerInfo(response);
            })  
            .catch(error => {
                console.error(error);            
        });
    }
}

/**
 * Updates the customer data in the page.
 * @param {Object} customerData 
 */
function updateCustomerInfo(customerData) {
    $('#provided-fields-table tbody').empty();    
    $('#customer-id-val').text(customerData.id);
    
    let statusColorClass = getStatusColorClass(customerData['status']);
    $('#customer-status-val').text(customerData.status);       
    $('#customer-status-val').removeClass('warning');
    $('#customer-status-val').removeClass('success');
    $('#customer-status-val').addClass(`${statusColorClass}`);

    $('#missing-fields-wrapper').hide();
    updateCustomerFields(customerData['provided_fields'], '#provided-fields-table tbody', true);
    if(customerData.fields) {
        updateCustomerFields(customerData['fields'], '#missing-fields-table tbody', false);
        $('#missing-fields-wrapper').fadeIn();
    }
     
}

/**
 * Update sthe customer fields in the table.
 * @param {Object} fields The KYC fields returned by the server. 
 * @param {string} tbodyId HTML table body id where the fields will be appended.
 * @param {boolean} isProvidedFields Field type, if true the passed fields object represents the provided fields otherwise represents the mssing missing fields.
 */
function updateCustomerFields(fields, tbodyId, isProvidedFields) {
    $(tbodyId).empty();
    for(let key in fields) {
        let field = fields[key];
        let statusColorClasses = getStatusColorClass(field.status);
        statusColorClasses += ' status-text flash-text ';
        let status = field.status ? field.status : '';
        if(isProvidedFields) {
            $(tbodyId).append(`<tr scope="row"><td>${key}</td><td>${field.description}</td><td class = "${statusColorClasses}">${status}</td></tr>`);
        }else {
            $(tbodyId).append(`<tr scope="row"><td>${key}</td><td>${field.description}</td></tr>`);
        }
      
    }   
}

/**
 * Returns the status color css class based on the status.
 * @param {string} status KYC data status color css class.
 * @returns 
 */
function getStatusColorClass(status) {
    return status === 'PROCESSING'? 'warning' : status === 'NEEDS_INFO' ? 'needs-info' : 'success';    
}

/**
 * Shows a modal dialog with a message and a title.
 * @param {string} msg The message to be shown in the dialog. 
 * @param {*} title The dialog title.
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
 * Deletes the customer, clears the local storage and reloads the page.
 */
function deleteCustomer() {
    let accountId = localStorage.getItem('accountId');
    if(accountId) {
        fetch(`/customer/${localStorage.getItem('accountId')}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem("accessToken")}`
            }
        })
        .then(response => {
            showAlert('The customer data has been deleted!').then(() => {
                localStorage.clear();
                location.reload();
            });
            
        }).catch(error => {
            console.error(error);
        });
    }   
}

/**
 * Initialize the page wires the listeners.
 */
function init() {
    localStorage.clear();        
    $('#retrieve-jwt-token-btn').click(function() {        
        let accountId = $('#account-id').val();        
        authenticate(accountId);         
    });

    $('#verify-btn').click(function(e) {   
        verifyEmail();             
    });
    $('#delete-btn').click(function(e) {           
        deleteCustomer();             
    });   
    
    $('.fa.fa-refresh').click(function(e) {   
        refreshCustomerInfo();
    });   
    $('#update-btn').click(function(e) {   
        $('.registration-form-wrapper').fadeIn(300);
    });

    $('#registration-form').submit(function(event) {
        event.preventDefault();
        event.stopPropagation();
        registerOrUpdateCustomer(this);
    });
}

/**
 * Updates the customer form texts based on the operation mode (create or update).
 */
function updateCustomerFormLabels() {
    let customerId = localStorage.getItem('customerId');
    if(customerId) {
        $('#registration-form-wrapper h3').html('Update KYC data');        
    }
}

/**
 * Sets the loading overlay visible or invisible
 * @param {boolean} visible flag indicating if the overlay should be visible or not.
 */
function setLoading(visible) {
    if (visible) {
        $('#loading-overlay').fadeIn(300);
        $('#loading-overlay').css('display', 'inline-flex'); 
    } else {
        $('#loading-overlay').fadeOut(300);
    }
}

