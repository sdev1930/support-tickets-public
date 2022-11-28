<?php

namespace CrmTickets\Inc;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


class Stats {


    /**
     * @return status based on key
     */ 
    public function getStats ($key) {

        $args = array(
              'post_type'       => 'crm_support_ticket',
              'post_status'     => array('publish', 'draft', 'private', 'pending' ), 
              'numberposts'     => '-1',//all
              'meta_key'        => 'crm_ticket_status',
              'meta_value'      => $key,
              'fields'          => 'ids'
            );
        $posts = get_posts($args);
        return sizeof($posts);
    }    

}

new \CrmTickets\Inc\Stats ();