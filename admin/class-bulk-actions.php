<?php

namespace CrmTickets\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use CrmTickets\Inc\Tickets as Tickets;
use CrmTickets\Inc\Scripts;

class BulkActions {

	public function __construct() {
		add_filter( 'bulk_actions-edit-crm_support_ticket', array($this, 'registerActions'), 10, 1 );
		add_filter( 'views_edit-crm_support_ticket', array($this,'addExportWithRepliesButton' ));
		add_filter( 'handle_bulk_actions-edit-crm_support_ticket', array($this, 'exportTicketsHandler'), 10, 3 );


	}


	public function addExportWithRepliesButton( $views_item ) {
		$Tickets = Tickets::getInstance();
		if (!($Tickets->isProVersionInstalled ()) ) {
		    $views_item['my-button'] = '<button  class="crm_tickets_admin_button crm_support_tickets_greyed" id="export_with_replies" type="button" onclick="crmOpenExportModal()" >' . esc_html("Export With Replies", "crm-support-tickets") . '</button>';
		    return $views_item;
		}
	}

	/**
	 * the bulk action
	 */ 
	public function registerActions( $actions ) {
		$Tickets = Tickets::getInstance();
	    $actions['export_tickets'] = esc_html( 'Export Tickets', 'crm-support-tickets');
	    return $actions;
    }
    


    /**
     * handle the bulk action
     */ 
	public function exportTicketsHandler( $redirect_to, $action, $post_ids ) {

	    if ( $action !== 'export_tickets' ) {
	    	return $redirect_to; // Exit
	    }
	
		$Tickets = Tickets::getInstance();
	    $processed_ids = array();

        $arg = array(
            'post_type' 		=> 'crm_support_ticket',
            'post_status' 		=> 'publish',
            'posts_per_page' 	=> -1,
            'post__in'			=> $post_ids,
        );
  
        global $post;
        $arr_post = get_posts($arg);
        if ($arr_post) {
  
            header('Content-type: text/csv');
            header('Content-Disposition: attachment; filename="tickets.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
  
            $file = fopen('php://output', 'w');
  
            fputcsv($file, array('Ticket Number', 'Status', 'Agent', 'Customer', 'Subject', 'Excerpt', 'Date'));
  			$processed_ids = array();

            foreach ($arr_post as $post) {
                setup_postdata($post);
                  
				$status =  sanitize_text_field($Tickets->getTicketData ($post->ID, 'status'));
				$subject = sanitize_text_field($Tickets->getTicketSubject($post->ID));
				$excerpt = sanitize_text_field($Tickets->getTicketData ($post->ID, 'excerpt'));
				$customer = sanitize_text_field($Tickets->getCustomerFullName( $Tickets->getCustomerId( $post->ID ) ));
				$date = sanitize_text_field($Tickets->getTicketData ($post->ID, 'post_date')); 
				$agent = sanitize_text_field($Tickets->getAgentFullName ($Tickets->getAgentId($post->ID)));

				if (empty($agent)) {
					$agent = esc_html("Not Assigned", "crm-support-tickets");
				}

  				$processed_ids[] = $post->ID;
                fputcsv($file, array(get_the_title(), $status, $agent, $customer,$subject, $excerpt, $date ));
            }
            fclose($file);
            return; //cannot show processed count with fputcsv

        }
	}




}

new \CrmTickets\Admin\BulkActions ();

