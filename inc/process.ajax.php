<?php

namespace CrmTickets\Inc;


if ( ! defined( 'ABSPATH' ) ) {
    exit; // exit if accessed directly.
}

use CrmTickets\Inc\Tickets as Tickets;

class processAjax  {

    public function __construct () {
        add_action ( 'wp_ajax_crm_tickets_view_tickets_action',   array($this, 'crm_tickets_view_tickets_handler'));
        add_action ( 'wp_ajax_crm_tickets_add_role_action',   array($this, 'crm_tickets_add_role_handler'));
        add_action ( 'wp_ajax_crm_tickets_delete_role_action',   array($this, 'crm_tickets_delete_role_handler'));
    }

    /**
     * deletes a role from the agent list
     */ 
    public function crm_tickets_delete_role_handler () {

        //check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'crm_tickets_nonce_action' )) {
            return;
        }
  
        //user has to have at least mange options privs
        if ( ! current_user_can('manage_options') ) {
            return;
        }


        if (isset($_POST ['role'])) {
            $role = sanitize_text_field($_POST [ 'role' ]);
            $roles = get_option('crm_support_tickets_agent_roles');
            $roles = $this->cleanArrayByValue($role, $roles);
            $roles = array_unique($roles);

            //scrub array
            $cleaned = array();
            foreach ($roles as $role) {
                $cleaned[] = sanitize_text_field($role);
            }

            update_option('crm_support_tickets_agent_roles', $cleaned);
        } 

        $data = array(
            'roles'  => $cleaned
        );

        echo json_encode($data);
        wp_die();
    }

    /**
     * adds an role to the agent list
     */ 
    public function crm_tickets_add_role_handler () {
 
        //check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'crm_tickets_nonce_action' )) {
            return;
        }
  
        //user has to have at least manage_options permissions
        if ( ! current_user_can('manage_options') ) {
            return;
        }

        if (isset($_POST ['role'])) {
            $role = sanitize_text_field($_POST [ 'role' ]);

            $roles = get_option('crm_support_tickets_agent_roles');
            $roles[] = $role;
            $roles = array_unique($roles);

            //scrub array
            $cleaned = array();
            foreach ($roles as $role) {
                $cleaned[] = sanitize_text_field($role);
            }
            update_option('crm_support_tickets_agent_roles', $cleaned);
        } 

        $data = array(
            'roles'  => $cleaned
        );

        echo json_encode($data);

        wp_die();
    }


    public function crm_tickets_view_tickets_handler () {

        //check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'crm_tickets_action' )) {
            return;
        }

        //user has to have at least subscriber permissions
        if ( !is_user_logged_in() ) {
            return;
        }

        if (isset($_POST ['ticket_id'])) {
            $ticket_id = sanitize_text_field($_POST [ 'ticket_id' ]);
        } 

        $Tickets = new Tickets;

        $replies_data = $Tickets->getFrontendTicketReplies($ticket_id);
        $data = array(
            'user_id'           => get_current_user_id(),
            'ticket_id'         => $ticket_id,
            'replies_data'      => $replies_data);
        echo json_encode($data);

        // echo json_encode(array('value' => 'ksdjf'));
        wp_die();
    }

    /**
     * method to clean a value and return the unset key array
     * @since 2.6.0
     * @return array
     */
     public function cleanArrayByValue ( $value, $array ){

        if (empty($value)) {
            return;
        }
        if ( is_array($array)) {
            $key = array_search($value, $array);
            unset($array[$key]); // if not exists, ok
            //now check if the key IS the value i.e. an associative array
            $bool = array_key_exists($value, $array);
            if ( $bool ) {
                unset($array[$value]); //only execute for one special case, otherwise it could delete a short value, say 1,2,3... 
            }
                    
            return $array;
        } else {
            return;
        }
     }  

}



new \CrmTickets\Inc\processAjax ();

