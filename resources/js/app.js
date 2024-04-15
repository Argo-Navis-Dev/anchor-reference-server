// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

import $ from "jquery";
window.jQuery = window.$ = $

import './customers_controller';
import './users_controller';
import './customer_controller';

/**
 * This event is triggered when the app is ready.
 */
$(function() {
    const pageName = $('body').data('page-name');        
    $(document).trigger('appReady', [pageName]);
});