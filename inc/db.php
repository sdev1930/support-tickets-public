<?php

namespace CrmTickets\Inc;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


class Db {

    public function __construct() {
        add_action('after_delete_post', array($this, 'cascadeRepliesTable'), 10, 2 );
    }

    /**
     * cascade to delete replies after the post is deleted
     */ 
    public function cascadeRepliesTable ($post_id, $post) {
        //check to see if post type crm_tickets
        $type = get_post_type($post);
        if ('crm_support_ticket' === $type){
            //delete replies
            $this->deleteReplies($post_id);
        }
    }

    /**
     * deletes all records with this post_id
     * @return bool - success
     */ 
    public function deleteReplies($post_id) {
        global $wpdb;
        if (!is_integer($post_id)) { return; }
        
        $table =   $wpdb->prefix . 'crm_tickets';

        $results = $wpdb->delete( $table, array(
                     'post_id'   => $post_id ) );

        return $results; //bool
    }

}

new \CrmTickets\Inc\Db ();