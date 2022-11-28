<?php

namespace CrmTickets\Traits;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use CrmProTickets\Inc\ProScripts;
use CrmTickets\Inc\Tickets;

trait Forms {

	public function getCommentForm () {

		$html = '
			<script async src="https://www.google.com/recaptcha/api.js"></script>
			<!-- Submit New Ticket -->
			<form id="crm_new_ticket_form" class="crm_ticket_create_new crm_ticket_hidden" method="post" enctype="multipart/form-data" action="">
			<input type="hidden" name="crm_new_ticket" id="crm_new_ticket" value="set">
			<h2> Create New Ticket </h2>
			 <label for="subject">Choose a subject:</label>
			 ' . $this->getSubjectOptions () . '
			<label for=”ticketContent”>Describe the issue:</label>
			<textarea id="ticketContent" name="ticketContent" rows="4" cols="50"></textarea>
			<button type=”submit" class="crm_tickets_button">' . esc_html("Submit", "crm-support-tickets") . '</button>';

		if(get_option('crm_support_tickets_enable_captcha')):
				$html .= '<div class="g-recaptcha" data-sitekey="' . esc_html(get_option('crm_support_tickets_captcha_site_key') ) . ' "></div>';
		endif;

		$html .= wp_nonce_field( "crm_tickets_action", "crm_tickets_field", "", "" );
		$html .= '</form>';

		return $html;
	}

	public function getAdminReplyForm () {

		$Tickets = Tickets::getInstance();

		global $post;
		if ($this->isCustomerSet($post->ID)):

		    ?>
			<input type="hidden" name="crm_new_ticket" id="crm_new_ticket" value="set">
			<label for=”adminTicketReply”> <?php esc_html_e('Post A Reply', 'crm-support-tickets') ; ?> </label>
			<textarea style="width: 100%; max-width: 100%;" id="adminTicketReply" name="adminTicketReply" rows="8" cols="50"></textarea>

			<!-- preserve new lines -->
			<style>
				.crm_tickets_preserve_lines {
					white-space: pre-line;
				}
			</style>

			<?php

			global $allowedposttags;
			
			echo wp_kses(apply_filters('crm_pro_support_tickets_open_search_canned_button', ''), $allowedposttags);
			echo wp_kses(apply_filters('crm_pro_support_tickets_canned_button', ''), $allowedposttags);
			if ($Tickets->isProVersionInstalled()) {
				ProScripts::getCanned();
			} else {
				$this->getProCannedHolderButton();
			}
			
			?>

		<?php else: ?>	
			<p> <?php esc_html_e('You must first assign a customer before posting replies', 'crm-support-tickets'); ?></p>	

		<?php endif; ?>	

			<?php wp_nonce_field( "crm_tickets_action", "crm_tickets_field" ); ?>

		<?php




	}


	public function getProCannedHolderButton () {
		?>
		<button class="crm_support_tickets_greyed crm_tickets_admin_button crm_show_canned_modal" > <?php esc_html_e ("Search Canned Responses (Go Pro)", 'crm-support-tickets'); ?></button>
		<script>
			jQuery(document).ready(function($) {

		    	$('.crm_show_canned_modal').click(function(e){
		    		e.preventDefault()
		    		$('#crm_modal_go_pro_canned_replies').removeClass('hidden');
		    	});
		    });
		</script>
		<?php
	}

	/**
	 * @return bool - has the customer been saved for this post
	 * @since 1.2
	 */ 
	public function isCustomerSet ($ticket_id) {

		if (get_post_meta($ticket_id, 'crm_ticket_customer_id', true)) {
			return true;
		}
	}

	/**
	 * @param selected - optional, the option to pre select
	 */ 
	public function getSubjectOptions ($selected = null) {

		$technical = $question = $request = $other = null;
		switch ($selected) {
			case 'Technical':
				// code...
				$technical = 'selected';
				break;

			case 'Question':
				$question = 'selected';
				break;

			case 'Request':
				$request = 'selected';
				break;

			case 'Other':
				$other = 'selected';
				break;

			default:
				// code...	
				break;
		}
		$html = '
			<select name="subject" id="subject" class="">
			  <option value="Technical" ' . esc_attr( $technical) . '>Technical Issue</option>
			  <option value="Question"' . esc_attr( $question) . ' >Question</option>
			  <option value="Request"' . esc_attr( $request) . ' >Request</option>
			  <option value="Other"' . esc_attr( $other) . '>Other</option>
			</select>';

		return $html;
	}
	/**
	 * note, we are returning the nonce field instead of echoing it
	 * the reply form goes to a different $_POST processing area
	 * @param $ticket_id
	 * @param $hidden - to show or hide the form, optional
	 */ 
	public function getReplyForm ($ticket_id, $hidden = true) {

		switch ($hidden) {
			case true :
				$class = 'crm_ticket_hidden';
				break;
			
			default:
				$class = null;
				break;
		}
		$form = 
		'<form id="crm_reply_form_' . esc_attr($ticket_id) .'" class="crm_ticket_create_reply ' . esc_attr($class) . ' " method="post" enctype="multipart/form-data" action="">
			<input type="hidden" name="crm_reply_ticket" id="crm_reply_ticket" value="set">
			<input type="hidden" name="crm_ticket_id" id="crm_ticket_id" value="' . esc_attr($ticket_id) . '">

			<label for=”ticketContent”>Post A Reply</label>
			<textarea id="ticketContent" name="ticketContent" rows="4" cols="50"></textarea>
			<div id="RecaptchaField2"></div>
			<button class="crm_tickets_button" type=”submit”>' . esc_html("Submit", "crm-support-tickets") . '</button>' . wp_nonce_field( "crm_tickets_action", "crm_tickets_field", "", "") . '
		</form>';

		//dynamically add the google recaptcha reply 
		if (get_option('crm_support_tickets_enable_captcha')) {
			$form .= '<script> grecaptcha.render("RecaptchaField2", {"sitekey" : "' . esc_html(get_option("crm_support_tickets_captcha_site_key") ) . '"}); </script>';
		}

		return $form;
	
	}
}