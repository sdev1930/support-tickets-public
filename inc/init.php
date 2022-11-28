<?php  


if ( ! defined( 'ABSPATH' ) ) {
    exit; // exit if accessed directly.
}


function crm_support_tickets_update_db_check() {

  $installed_version = get_option('crm_support_tickets_plugin_version' );
  $db_version = get_option('crm_support_tickets_db_version');

    if ( $installed_version != $db_version ) {
       $run =  crm_support_tickets_create_custom_tables(); //$run result: bool

      //update the db version
       update_option('crm_support_tickets_db_version', $installed_version);
    }
}
add_action( 'plugins_loaded', 'crm_support_tickets_update_db_check' );


/**
 * initial scans table and any changes to the original if db is out of date
 * @see https://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table for dbDelta parsing requirements, note: very picky
 */ 
function crm_support_tickets_create_custom_tables () {

     global $wpdb;
    $table_name = $wpdb->prefix . 'crm_tickets';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id integer NOT NULL AUTO_INCREMENT,
      user_id integer NULL,
      agent_id integer NULL,
      commentor_id integer NULL,
  	  post_id integer NULL,
  	  post_content longtext NULL,
  	  attachment longtext NULL,
      last_updated datetime NOT NULL default CURRENT_TIMESTAMP,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    $success = empty($wpdb->last_error);
    error_log('DATABASE created/and or updated success is ' . $success);
    return $success;
}
