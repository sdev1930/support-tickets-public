<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


use CrmProTickets\Inc\License;

    require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/traits/comment_form.php');
    require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/inc/tickets.php');
    require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/inc/init.php');
    require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/inc/process.ajax.php');
    require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/inc/scripts.php'); 
    require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/inc/db.php');
    
    if(is_admin()) {
        require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/admin/class-bulk-actions.php');
        require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/admin/class_modals.php');
        require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/admin/class_filters.php');
        require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/inc/class-stats.php');
    }


//deactivate deprecated cron
$timestamp = wp_next_scheduled( 'crm_support_tickets_autoclose_hook' );
wp_unschedule_event( $timestamp, 'crm_support_tickets_autoclose_hook' ); 


