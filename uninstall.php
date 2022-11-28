<?php


// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}


//confirm that they really want to delete all data
if ( empty(get_option('crm_support_tickets_delete_all_uninstall')) ) {
    //stop
    return;
}



global $wpdb;

// Delete entries table.
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'crm_tickets' );


// Delete all the plugin settings.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'crm_support_tickets\_%'" );

// Delete plugin user meta.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'crm_support_tickets\_%'" );

// Delete plugin term meta.
$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE 'crm_support_tickets\_%'" );


// Delete crm_support_ticket post type posts/post_meta.
$support_posts = get_posts(
    [
        'post_type'   => [ 'crm_support_ticket' ],
        'post_status' => 'any',
        'numberposts' => - 1,
        'fields'      => 'ids',
    ]
);



if ( $support_posts ) {
    foreach ( $support_posts as $support_post ) {
        wp_delete_post( $support_post, true );
    }
}