<?php  

namespace CrmTickets\Inc;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // exit if accessed directly.
}


class Deactivate {

    public static function run () {
        delete_option('crm_support_tickets_plugin_version');


    }

}




