<?php

namespace CrmTickets\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use CrmTickets\Inc\Tickets as Tickets;
use CrmTickets\Inc\Scripts;

class AdminFilters {

    public function __construct () {
        add_action( 'restrict_manage_posts', array($this, 'filterDropdownByStatus' ));
        add_filter( 'parse_query', array($this,'filterByPostMeta' ));
    }


    /**
     * Show the filter dropdown
     * 
     * @author Ohad Raz
     * 
     * @return void
     */
    function filterDropdownByStatus(){

        $type = 'crm_support_ticket';
        if (isset($_GET['post_type'])) {
            $type = sanitize_text_field($_GET['post_type']);
        }

        if ('crm_support_ticket' == $type){

            $closed_string = esc_html('Closed', 'crm-support-tickets');
            $open_string = esc_html('Open', 'crm-support-tickets');
            $wait_agent_string = esc_html('Awaiting Agent Response', 'crm-support-tickets');
            $wait_customer_string = esc_html('Awaiting Customer Response', 'crm-support-tickets');
            $answered_string = esc_html('Answered', 'crm-support-tickets');

            $values = array(
                $closed_string          => 'closed', 
                $open_string            => 'open',
                $wait_agent_string      => 'awaiting_agent_response',
                $wait_customer_string   => 'awaiting_customer_response',
                $answered_string        => 'answered'
            );
            ?>
            <select name="crm_support_ticket_field">
            <option value=""><?php _e('Filter Status By ', 'crm-support-tickets'); ?></option>
            <?php
                $current_v = isset($_GET['crm_support_ticket_field'])? $_GET['crm_support_ticket_field']:'';
                foreach ($values as $label => $value) {
                    printf
                        (
                            '<option value="%s"%s>%s</option>',
                            esc_attr($value),
                            esc_attr($value) == $current_v ? ' selected="selected"':'',
                            esc_attr($label)
                        );
                    }
            ?>
            </select>
            <?php
        }
    }



    /**
     * if submitted filter by post meta
     * 
     * @author Ohad Raz
     * @param  (wp_query object) $query
     * 
     * @return Void
     */
    function filterByPostMeta( $query ){
        global $pagenow;

        $type = 'crm_support_ticket';
        if (isset($_GET['post_type'])) {
            $type = sanitize_text_field($_GET['post_type']);
        }

        if ( 'crm_support_ticket' == $type 
            && is_admin() 
            && $pagenow=='edit.php' 
            && isset($_GET['crm_support_ticket_field']) 
            && $_GET['crm_support_ticket_field'] != '') {

                $query->query_vars['meta_key'] = 'crm_ticket_status';
                $query->query_vars['meta_value'] = sanitize_text_field($_GET['crm_support_ticket_field']);
        }
    }
}

new \CrmTickets\Admin\AdminFilters ();