import $ from "jquery";
window.jQuery = window.$ = $

import './customers_controller';
import './users_controller';
import './customer_controller';


$(function() {
    const routeName = $('body').data('route-name');    
    $(document).trigger('appReady', [routeName]);
});