/**
 * Created by sjolshag on 12/31/15.
 */

jQuery(document).ready(function($){

    var menu = $('nav.nav-primary');
    var args = {};

    if ( menu.length !== 0) {
        args = {
            marginTop: menu.outerHeight() + 10
        };
    }

    $('div.e20r-blur-call-to-action').scrollToFixed(args);
});