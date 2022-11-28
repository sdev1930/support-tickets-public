<?php


/**
* Plugin Name: Chimney Rock - Support Tickets
* WC tested up to: 6.1.1
* Tested up to: 6.0.1
* Requires PHP: 7.0
* Version: 1.3.2
* Author: Chimney Rock Software
* Author URI: https://chimneyrockmgt.com/
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
* Text Domain: crm-support-tickets
* Domain Path: /languages/

Description: Create and manage support tickets for your customers or subscribers with ease.

Copyright 2016-2022 Chimney Rock Software
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


define('CRM_SUPPORT_TICKETS_FILE', __FILE__);
define('CRM_SUPPORT_TICKETS_PLUGIN_PATH', dirname(__FILE__));
define('CRM_SUPPORT_TICKETS_PLUGIN_TEXT_DOMAIN', 'crm-support-tickets');

update_option('crm_support_tickets_plugin_version', '1.3.2');
define('CRM_SUPPORT_TICKETS_VERSION', get_option('crm_support_tickets_plugin_version' ) );



require_once(CRM_SUPPORT_TICKETS_PLUGIN_PATH . '/loader.php');

add_action('wp_enqueue_scripts', 'crm_support_tickets_add_frontend_assets');
add_action('admin_enqueue_scripts', 'crm_support_tickets_add_admin_assets');

/**
 * backend scripts and styles
 */ 
function crm_support_tickets_add_admin_assets() {
    wp_enqueue_style( 'crm_admin_css', plugins_url('/assets/css/admin.css', __FILE__), '', CRM_SUPPORT_TICKETS_VERSION );
}

/**
 * enqueue scripts and style, localize frontend ajax url
 */ 
function crm_support_tickets_add_frontend_assets() {
    wp_enqueue_style( 'crm_tickets_css', plugins_url('/assets/css/frontend.css', __FILE__), '',  CRM_SUPPORT_TICKETS_VERSION );

    wp_register_script('crm_tickets_frontend', plugins_url('/assets/js/frontend.js', __FILE__), array('jquery'), CRM_SUPPORT_TICKETS_VERSION, true);
    wp_enqueue_script('crm_tickets_frontend');

    $variables = array(
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    );
    wp_localize_script('crm_tickets_frontend', "frontend", $variables);
}

/**
 * Deactivation hook
 */ 
use \CrmTickets\Inc\Deactivate;

/**
 * remove installed option
 */ 
function crm_support_ticket_register_deactivation () {

   require_once plugin_dir_path( __FILE__ ) . 'inc/deactivate.php';
   Deactivate::run();
}

register_deactivation_hook( __FILE__, 'crm_support_ticket_register_deactivation' );

function crm_support_tickets_text_domain_plugin_init() {
  $plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages'; /* Relative to WP_PLUGIN_DIR */
  load_plugin_textdomain( CRM_SUPPORT_TICKETS_PLUGIN_TEXT_DOMAIN, false, $plugin_rel_path );
}
add_action('init', 'crm_support_tickets_text_domain_plugin_init');
