import $ from "jquery";
window.jQuery = window.$ = $

import './admin_customers';
import './admin_users';
import './admin_customer';


$(function() {
    const routeName = $('body').data('route-name');    
    $(document).trigger('appReady', [routeName]);
});