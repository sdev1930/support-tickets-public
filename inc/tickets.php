<?php

namespace CrmTickets\Inc;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use CrmTickets\Traits\Forms;
use CrmTickets\Inc\Scripts as Scripts;
use CrmProTickets\Admin\License as License;
use CrmProTickets\Inc\ProScripts as ProScripts;
use CrmTickets\Inc\Stats as Stats;


class Tickets {

	use Forms;
	private static $instance = null;

	/**
	 * make the class a singleton
	 */ 
	public static function getInstance() {
	    if (self::$instance == null) {
	      self::$instance = new Tickets();
	    }
	 
	    return self::$instance;
  }

  /**
   */
	public function __construct() {

		//register post type
		add_action ('init', array($this, 'register_custom_post_type_ticket'));

		//shortcode
		add_shortcode('crm_support_tickets', array ($this, 'renderSupportTickets'));

		//submenu
		add_action('admin_menu', array($this, 'addSubmenu'));

		//meta boxes
		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes'), 20 );

		//save meta box data
		add_action("save_post_crm_support_ticket", array( $this, "save_custom_meta_box"), 10, 3);

		//settings
		add_action('admin_init', array($this, 'registerSettings'));

		add_filter( 'wp_insert_post_data' , array($this, 'modifyPostTitle') , '99', 2 ); // Grabs the inserted post data so you can modify it.

		//columns
		add_filter( 'manage_edit-crm_support_ticket_columns', array( $this, 'addColumns'),20 );
		add_action( 'manage_crm_support_ticket_posts_custom_column', array ( $this, 'addColumnContent' ) );

		//link to pro
		$plugin = plugin_basename(CRM_SUPPORT_TICKETS_FILE); 
		add_filter("plugin_action_links_$plugin", array($this,'addLinkToProPluginPage' ));


		}


	 public function addMetaBoxes() {
	    add_meta_box( 'support-tickets-meta-box', 
	        __( 'Ticket History', 'crm-support-tickets' ), 
	        array( $this, 'supportTicketMetaBoxContent' ),  
	        'crm_support_ticket',
	        'normal',
	        'default' );

	    add_meta_box( 'support-tickets-status-box', 
	        __( 'Status', 'crm-support-tickets' ), 
	        array( $this, 'statusContent' ),  
	        'crm_support_ticket',
			'side',
			'high' );
	    add_meta_box( 'support-tickets-priority-box', 
	        __( 'Priortiy', 'crm-support-tickets' ), 
	        array( $this, 'priorityContent' ),  
	        'crm_support_ticket',
			'side',
			'high' );	  

	    add_meta_box( 'support-tickets-shortcode-box', 
	        __( 'Shortcode', 'crm-support-tickets' ), 
	        array( $this, 'shortcodeContent' ),   
	        'crm_support_ticket',
			'side',
			'high' );				  
	}

	/**
	 * show instructions for shortcode
	 */ 
	public function shortcodeContent() {
		$html = '<p>' . esc_html("Add this shortcode to any page to display tickets", "crm-support-tickets") . '</p>';
		$html .= '<p>[crm_support_tickets]</p>';

		global $allowedposttags;
		echo wp_kses($html, $allowedposttags);
	}


	/**
	 * @return the ticket priority select option
	 */ 
	public function priorityContent() {
		global $post;
		$status = get_post_meta($post->ID, 'crm_ticket_priority', true);

		?>
			<select name="priority" id="priority">
			  <option value="normal"  <?php selected( $status, 'normal')  ?> > <?php esc_html_e("Normal", "crm-support-tickets" ) ?> </option>
			  <option value="low"  <?php selected( $status, 'low') ?> > <?php esc_html_e("Low", "crm-support-tickets" ) ?> </option>
			  <option value="high"  <?php selected( $status, 'high') ?> > <?php esc_html_e("High", "crm-support-tickets" ) ?> </option>
			  <option value="critical"  <?php selected( $status, 'critical') ?> > <?php esc_html_e("Critical", "crm-support-tickets" ) ?> </option>
			</select> 
		<?php
	}

	/**
	 * @return the ticket status select option
	 */ 
	public function statusContent() {
		global $post;
		$status = get_post_meta($post->ID, 'crm_ticket_status', true);

		?>
			<select name="status" id="status">
			  <option value="open"  <?php selected( $status, 'open')  ?> > <?php esc_html_e("Open", "crm-support-tickets" ) ?> </option>
			  <option value="closed"  <?php selected( $status, 'closed') ?> > <?php esc_html_e("Closed", "crm-support-tickets" ) ?> </option>
			  <option value="answered"  <?php selected( $status, 'answered') ?> > <?php esc_html_e("Answered", "crm-support-tickets" ) ?> </option>
			  <option value="awaiting_agent_response"  <?php selected( $status, 'awaiting_agent_response') ?> > <?php esc_html_e("Awaiting Agent Response", "crm-support-tickets" ) ?> </option>
			  <option value="awaiting_customer_response"  <?php selected( $status, 'awaiting_customer_response') ?> > <?php esc_html_e("Awaiting Customer Response", "crm-support-tickets" ) ?> </option>
			</select> 
		<?php
	}

	/**
	 * @return the ticket html table
	 */ 
	public function supportTicketMetaBoxContent () {
		global $post;

		if (get_post_meta($post->ID, 'crm_ticket_agent_id', true) ) {
			$agent_id = get_post_meta($post->ID, 'crm_ticket_agent_id', true);
		} else {
			$agent_id = get_current_user_id();
		}

		$agent_arguments = array(
			'selected' 	=> $agent_id, 
			'name' 		=> 'agent_id',
			'role__in'	=> get_option('crm_support_tickets_agent_roles')
		);

		if (get_post_meta($post->ID, 'crm_ticket_customer_id', true) ) {
			$customer_id = get_post_meta($post->ID, 'crm_ticket_customer_id', true);
		} else {
			$customer_id = get_current_user_id();
		}
		//show the customer
		$customer_arguments = array(
			'selected' 	=> $customer_id, 
			'name' 		=> 'customer_id',
			'role__in'	=> array('customer', 'subscriber')
		);

		?>
		 <table>
		 	<tr>
		 		<td><?php esc_html_e("Agent Assigned", "crm-support-tickets") ?> </td>
		 		<td> <?php wp_dropdown_users($agent_arguments); ?> </td>
		 	</tr>
		 	<tr>
		 		<td><?php esc_html_e("Subject", "crm-support-tickets") ?> </td>
		 		<td> <?php  $this->getSubjectOptions (get_post_meta($post->ID, 'crm_ticket_subject', true) ); ?> </td>
		 	</tr>

			<!-- only show when adding new customer -->
		 	<?php if (!$this->isCustomerAssigned($post->ID)): ?>
			 	<tr>
			 		<td><?php esc_html_e("Assign To Customer", "crm-support-tickets") ?> </td>
			 		<td> <?php wp_dropdown_users($customer_arguments); ?> </td>
			 		<td><button class="enable_change">Enable</button></td>
			 	</tr>

			 	<!-- must manually enable to assign to a customer -->
				<script>
				 	jQuery(document).ready(function($) {
					 	$("#customer_id").attr('disabled','disabled');
					 	$(".enable_change").click(function(e) {
						 	e.preventDefault()
					 		$("#customer_id").removeAttr('disabled','disabled');
					 	});
					 });
				</script>
			<?php endif; ?>

		 </table>


		 <table>
			 	<tr>
			 		<th><?php esc_html_e("Customer Name", "crm-support-tickets") ?> </th>
			 		<th><?php esc_html_e("Customer Email", "crm-support-tickets") ?> </th>
			
			 	</tr>

		 	<tr>
		 		<td> <?php esc_html_e($this->getCustomerFullName (get_post_meta($post->ID, 'crm_ticket_customer_id', true))); ?> </td>
		 		<td> <?php esc_html_e($this->getCustomerEmail (get_post_meta($post->ID, 'crm_ticket_customer_id', true))); ?> </td>

		 	</tr>
		 </table>

		 <?php

		global $allowedposttags;
		echo wp_kses($this->showAdminTicket($post->ID), $allowedposttags);
		$this->getAdminReplyForm ();
		echo stripslashes(wp_kses($this->getAdminTicketReplies($post->ID), $allowedposttags)); 
	}

	/**
	 * saves the meta box content
	 */ 
	public function save_custom_meta_box($post_id, $post, $update) {

	    if (!isset($_POST["crm_tickets_field"]) || !wp_verify_nonce($_POST["crm_tickets_field"], 'crm_tickets_action' ) ) {
	        return $post_id;
	    }

	    if(!current_user_can("edit_post", $post_id)) {    
	        return $post_id;
	    }

	    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
	        return $post_id;
	    }

	    if (isset($_POST['status'])) {
	    	$status = sanitize_text_field($_POST['status']);
	    	update_post_meta($post_id, 'crm_ticket_status', $status);
	    }

	    if (isset($_POST['priority'])) {
	    	$priority = sanitize_text_field($_POST['priority']);
	    	update_post_meta($post_id, 'crm_ticket_priority', $priority);
	    }

	    if (isset($_POST['subject'])) {
	    	$subject = sanitize_text_field($_POST['subject']);
	    	update_post_meta($post_id, 'crm_ticket_subject', $subject);
	    }

	    if (isset($_POST['customer_id'])) {
	    	$customer_id = sanitize_text_field($_POST['customer_id']);
	    	update_post_meta($post_id, 'crm_ticket_customer_id', intval($customer_id));

	    	$user_object = get_user_by('ID', $customer_id);
	    	$name = $user_object->first_name . ' ' . $user_object->last_name;
	    	update_post_meta($post_id, 'crm_ticket_customer_name', sanitize_text_field($name) );

			$email = $user_object->user_email;
			update_post_meta($post_id, 'crm_ticket_email', sanitize_text_field($email));
	    }

	    //only update replies if there is a comment
	    if (isset($_POST['adminTicketReply']) && !empty($_POST['adminTicketReply'])) {
	    	$admin_reply = sanitize_textarea_field($_POST['adminTicketReply']);
	    	$this->saveTicketReply( get_post_meta($post_id, 'crm_ticket_customer_id', true), get_current_user_id(), get_current_user_id(), $post_id, $admin_reply, $attachment = null);	

	    	//notify customer
	    	$customer_id = get_post_meta($post_id, 'crm_ticket_customer_id', true);
	    	$this->sendCustomerEmail ($customer_id, $post_id, 'reply'); 
	    }

	    if (isset($_POST['agent_id'])) {
	    	$agent_id = sanitize_text_field($_POST['agent_id']);
	    	update_post_meta($post_id, 'crm_ticket_agent_id', intval($agent_id));

	    	$user_object = get_user_by('ID', $agent_id);
	    	$name = $user_object->first_name . ' ' . $user_object->last_name;
	    	update_post_meta($post_id, 'crm_ticket_agent_name', sanitize_text_field($name) );
	    }
	}
	

	/**
	 * @return bool, is a customer already assigned to this ticket
	 * @param ticket_id
	 */ 
	public function isCustomerAssigned($ticket_id) {
		if (get_post_meta($ticket_id, 'crm_ticket_customer_id', true)) {
			return true;
		}
	}


	public function getPrettyName($status) {
		switch ($status) {
			case 'open':
				return esc_html("Open", "crm-support-tickets");
				break;

			case 'closed':
				return esc_html("Closed", "crm-support-tickets");
				break;

			case 'awaiting_agent_response':
				return esc_html("Awaiting Agent Response", "crm-support-tickets");
				break;

			case 'awaiting_customer_response':
				return esc_html("Awaiting Customer Response", "crm-support-tickets");
				break;

			case 'answered':
				return esc_html("Answered", "crm-support-tickets");
				break;

			case 'low':
				return esc_html("Low", "crm-support-tickets");
				break;			

			case 'normal':
				return esc_html("Normal", "crm-support-tickets");
				break;		

			case 'high':
				return esc_html("High", "crm-support-tickets");
				break;		

			case 'critical':
				return esc_html("Critical", "crm-support-tickets");
				break;										



			default:
				// code...
				break;
		}
	}


	/**
	* @return column content for custom post type crm_support_ticket
	*
	*/ 
	public function addColumnContent ( $column ) {
		global $post;

		if ( 'agent_id' === $column ) {
			esc_html_e(get_post_meta($post->ID, 'crm_ticket_agent_name', true));
		  }

		  //swap out the title for the post_id
		 if ('title' === $column) {
			echo intval($post->ID);
		 } 

		 if ('status' === $column) {
			esc_html_e( $this->getPrettyName(get_post_meta($post->ID, 'crm_ticket_status', true)));
		 }   

		 if ('priority' === $column) {
			esc_html_e( $this->getPrettyName(get_post_meta($post->ID, 'crm_ticket_priority', true)));
		 }   

		if ('customer_name' === $column) {
			esc_html_e(get_post_meta($post->ID, 'crm_ticket_customer_name', true));
		 } 

		if ('email' === $column) {
			esc_html_e(get_post_meta($post->ID, 'crm_ticket_email', true));
		 } 

		 if ('subject' === $column) {
			esc_html_e(get_post_meta($post->ID, 'crm_ticket_subject', true));
		 } 
	}


	/**
	* @return columns for custom post type crm_support_ticket
	*
	*/ 
	public function addColumns($columns) {
		//get the date value
		$date = $columns['date'];
		unset($columns['date']);

		$columns['title'] = 'ID'; //rename to ID from Title 
		$columns['agent_id'] = esc_html('Agent', 'crm-support-tickets');
		$columns['status'] = esc_html('Status', 'crm-support-tickets');
		$columns['priority'] = esc_html('Priority', 'crm-support-tickets');
		$columns['subject'] = esc_html('Subject', 'crm-support-tickets');
		$columns['customer_name'] = esc_html('Name', 'crm-support-tickets');
		$columns['email'] = esc_html('Email', 'crm-support-tickets');

		//re insert the date to the end
		$columns['date'] = $date;

		return $columns;
	}

	/**
	* instead of the default Auto Draft wp creates, even though Title is not supported, swap the title witth the post id
	* if you try to unset the title from the column, you cannot edit it
	*/ 
	public function modifyPostTitle( $data, $postarr ){
		if($data['post_type'] == 'crm_support_ticket') { 
		  $title = $postarr['ID'];
		  $data['post_title'] =  intval($title) ; //Updates the post title to your new title.
		}
		return $data; // Returns the modified data.
	}

	public function addSubmenu () {

		add_submenu_page(
			'edit.php?post_type=crm_support_ticket',
			__('Settings', 'crm-support-tickets'),
			__('Settings', 'crm-support-tickets'),
			'manage_options',
			'crm-settings', //slug
			array( $this, 'settingsPageContent' )		
		);	

		add_submenu_page(
			'edit.php?post_type=crm_support_ticket',
			__('Reports', 'crm-support-tickets'),
			__('Reports', 'crm-support-tickets'),
			'manage_options',
			'crm-reports', //slug
			array( $this, 'reportPageContent' )		
		);			
	}


	/**
	 * @return the settings page content
	 */ 
 	public function settingsPageContent () {

 		?>
 		 <div class="crm_settings">
 			<h2> <?php esc_html_e('Support Tickets Settings', 'crm-support-tickets') ?> </h2>
 			<p> <?php esc_html_e('Add the shortcode [crm_support_tickets] to any page where you want to show your support tickets', 'crm-support-tickets') ?> </p>
			<form method="post" action="options.php">
				<table>
				    <!-- <th> <?php esc_html_e('Admin Email', 'crm-support-tickets') ?> </th> -->

				    <!-- License Key -->
				    <?php if ($this->isProVersionInstalled()):
	
						//check status
						$Updater = new \CrmProTickets\Updater ();
						$Updater->postHeaders ();
						License::applyResults( sanitize_text_field( $Updater->getBody () ) );
					?>

				    <tr >
				        <th class="crm_ticket_setting">
				            <label for="crm_pro_support_tickets_license_key"><?php esc_html_e('License Key', 'crm-support-tickets'); ?></label>
				        </th>
				        <td>
				            <input class="crm_wide_setting" type="text" id="crm_pro_support_tickets_license_key" name="crm_pro_support_tickets_license_key" value="<?php echo esc_html( get_option('crm_pro_support_tickets_license_key') ); ?>" /><span class="crm_tickets_ml30">Status <?php echo License::getProLicenseStatus(); ?> </span>
				        </td>
				
				    </tr>
					<?php endif; ?>


				    <!-- slug -->
				    <tr >
				        <th class="crm_ticket_setting">
				            <label for="crm_support_tickets_slug_to_tickets"><?php esc_html_e('Slug', 'crm-support-tickets'); ?></label>
				        </th>
				        <td>
				            <input class="crm_wide_setting" type="text" id="crm_support_tickets_slug_to_tickets" name="crm_support_tickets_slug_to_tickets" value="<?php echo esc_html( get_option('crm_support_tickets_slug_to_tickets') ); ?>" />
				        </td>
				    </tr>

				    <tr>
				        <td></td>
				        <td>
				            <?php esc_html_e('Enter the slug of the page where you have placed the shortode.  This will enable a link to be generated showing the customer where to view their tickets.  To find the slug, edit the page and expand the Permalink menu item. Example: ticket-page', 'crm-support-tickets'); ?>
				        </td>
				    </tr>

				    <!-- emails -->
				    <tr valign="top">
				        <th scope="row">
				            <label for="crm_support_tickets_enable_admin_email"><?php esc_html_e('Enable Admin Emails', 'crm-support-tickets'); ?></label>
				        </th>

				        <td>
				            <input name="crm_support_tickets_enable_admin_email" id="crm_support_tickets_enable_admin_email" type="checkbox" value="1" class="code"
				            <?php echo (checked(1, get_option('crm_support_tickets_enable_admin_email'), 1)); ?>
				            />
				        </td>
				    </tr>

				    <tr>
				        <td></td>
				        <td><?php esc_html_e('When enabled, an email to admin will be sent for new tickets and new ticket replies', 'crm-support-tickets'); ?></td>
				    </tr>

				    <tr valign="top">
				        <th scope="row">
				            <label for="crm_support_tickets_enable_customer_email"><?php esc_html_e('Enable Customer Emails', 'crm-support-tickets'); ?></label>
				        </th>
				        <td>
				            <input name="crm_support_tickets_enable_customer_email" id="crm_support_tickets_enable_customer_email" type="checkbox" value="1" class="code"
				            <?php echo (checked( 1,  get_option( 'crm_support_tickets_enable_customer_email', true), true )); ?>
				            />
				        </td>
				    </tr>

				    <tr>
				        <td></td>
				        <td><?php esc_html_e('When enabled, an email to the customer will be sent for new tickets and new ticket replies', 'crm-support-tickets'); ?></td>
				    </tr>

				    <tr >
				        <th class="crm_ticket_setting">
				            <label for="crm_support_tickets_email_from_name"><?php esc_html_e('"From" Name', 'crm-support-tickets'); ?></label>
				        </th>
				        <td>
				            <input class="crm_wide_setting" type="text" id="crm_support_tickets_email_from_name" name="crm_support_tickets_email_from_name" value="<?php echo esc_html( get_option('crm_support_tickets_email_from_name') ); ?>" />
				        </td>
				    </tr>

				    <tr>
				        <td></td>
				        <td>
				            <?php esc_html_e('Enter the name emails should be sent from.  Default: Blog Name', 'crm-support-tickets'); ?>
				        </td>
				    </tr>



				    <tr >
				        <th class="crm_ticket_setting">
				            <label for="crm_support_tickets_from_email"><?php esc_html_e('"From" Email', 'crm-support-tickets'); ?></label>
				        </th>
				        <td>
				            <input class="crm_wide_setting" type="text" id="crm_support_tickets_from_email" name="crm_support_tickets_from_email" value="<?php echo esc_html( get_option('crm_support_tickets_from_email') ); ?>" />
				        </td>
				    </tr>

				    <tr>
				        <td></td>
				        <td>
				            <?php esc_html_e('Enter the email address that emails should be sent from.  Default: Admin Email', 'crm-support-tickets'); ?>
				        </td>
				    </tr>


					<!-- VIEW ROLES/DELETE -->
					<tr>
						<td class="crm_ticket_gap_30"></td>
					</tr>
					<tr valign="top">
					    <th scope="row">
					        <?php esc_html_e('Agent Roles', 'crm-support-tickets'); ?>
					    </th>

					    <td>
					        <?php echo('administrator'); ?> 
					    </td>
					</tr>


					<?php foreach ($this->getAgentRoles() as $key => $role) {
						if('administrator' == $role) {
							continue;
						}
					?>
					<tr valign="top">
					    <th>
					    </th>

					    <td>
					        <?php  esc_html_e($role); ?>  <span class="crm_support_tickets_looksLikebtn crm_ticket_ml_20 crm_delete_this_role" id="<?php esc_html_e($role) ?>">Delete</span>
					    </td>
					</tr>

					<?php
					} //foreach
					?>

					<!-- ADD ROLES -->
					<tr>
						<td></td>
						<td><span class="crm_support_tickets_looksLikebtn crm_add_role">Add Role</span></td>
					</tr>

					<tr>
						<td></td>
						<td>
					<?php $roles = $this->getEditableRoles() ; ?>
					<?php 
						?> <div class="show_roles hidden"> <?php
							foreach($roles as $role => $name) {
								if ('administrator' === $role ) { continue; }

								if ($this->isRoleSet($role)) { continue; }
								echo '<p>' . esc_html($role) . '<span class="crm_support_tickets_looksLikebtn crm_ticket_ml_20 crm_add_this_role" id="' . esc_attr($role) . '">Add</span></p>'; 
							}
						?> </div>	
					</td>
					</tr>

					<!-- AUTO CLOSE -->
					<?php if ($this->isProVersionInstalled()): ?>
					    <tr>
					        <td class="crm_ticket_gap_30"></td>
					    </tr>
					    <tr valign="top">
					        <th scope="row">
					            <label for="crm_support_tickets_enable_auto_close"><?php esc_html_e('Enable Auto Close Tickets', 'crm-support-tickets'); ?></label>
					        </th>

					        <td>
					            <input name="crm_support_tickets_enable_auto_close" id="crm_support_tickets_enable_auto_close" type="checkbox" value="1" class="code"
					            <?php echo (checked( 1,  get_option( 'crm_support_tickets_enable_auto_close'), true )); ?>
					            />
					        </td>
					    </tr>

					    <tr>
					        <td></td>
					        <td><?php esc_html_e('When enabled, tickets will be closed based on the number of days of inactivity that you specify.', 'crm-support-tickets'); ?></td>
					    </tr>

					    <tr valign="top">
					        <th scope="row">
					            <label for="crm_support_tickets_auto_close_days"><?php esc_html_e('Days Of Inactivity', 'crm-support-tickets'); ?></label>
					        </th>

					        <td>
						        <input name="crm_support_tickets_auto_close_days" id="crm_support_tickets_auto_close_days" type="number" min="1" step="1" class="code" value="<?php echo esc_attr(get_option( 'crm_support_tickets_auto_close_days', '7')); ?>" required >
					        </td>
					    </tr>

					    <tr>
					        <td></td>
					        <td><?php esc_html_e('Set the number of days of inactivity that tickets should be closed automatically.  You must enable the auto close function for this to take effect.', 'crm-support-tickets'); ?></td>
					    </tr>
					<?php else:  ?>
					 <tr>
					        <td class="crm_ticket_gap_30"></td>
					    </tr>
					    <tr valign="top">
					        <th scope="row">
					            <label class="crm_support_tickets_greyed"  for="crm_support_tickets_enable_auto_close"><?php esc_html_e('Enable Auto Close Tickets', 'crm-support-tickets'); ?></label>
					        </th>

					        <td>
					       		<a href="https://chimneyrockmgt.com/support-tickets/"> <?php esc_html_e('Go Pro', 'crm-support-tickets'); ?></a><?php esc_html_e(' Auto Closing tickets based on inactivity is a Pro Feature.', 'crm-support-tickets'); ?>
					        </td>
					    </tr>    
					<?php endif; ?>    


					<!-- CAPTCHA -->
				    <tr>
				        <td class="crm_ticket_gap_30"></td>
				    </tr>
				    <tr valign="top">
				        <th scope="row">
				            <label for="crm_support_tickets_enable_captcha"><?php esc_html_e('Enable Google Recaptcha', 'crm-support-tickets'); ?></label>
				        </th>

				        <td>
				            <input name="crm_support_tickets_enable_captcha" id="crm_support_tickets_enable_captcha" type="checkbox" value="1" class="code"
				            <?php echo (checked( 1,  get_option( 'crm_support_tickets_enable_captcha'), true )); ?>
				            />
				        </td>
				    </tr>

				    <tr>
				        <td></td>
				        <td><?php esc_html_e('When enabled, the Google Recaptcha will be shown if your site and secret key are saved.', 'crm-support-tickets'); ?></td>
				    </tr>


				    <tr>
				        <th scope="row">
				            <label for="crm_support_tickets_captcha_site_key"><?php esc_html_e('Google Recaptcha Site Key', 'crm-support-tickets'); ?></label>
				        </th>
				        <td>
				            <input class="crm_wide_setting" type="text" id="crm_support_tickets_captcha_site_key" name="crm_support_tickets_captcha_site_key" value="<?php echo esc_html( get_option('crm_support_tickets_captcha_site_key') ); ?>" />
				        </td>
				    </tr>



				    <tr>
				        <td></td>
				        <td><?php esc_html_e('Enter the recaptcha site key.', 'crm-support-tickets'); ?></td>
				    </tr>

				    <tr>
				        <th scope="row">
				            <label for="crm_support_tickets_captcha_secret_key"><?php esc_html_e('Google Recaptcha Secret Key', 'crm-support-tickets'); ?></label>
				        </th>
				        <td>
				            <input class="crm_wide_setting" type="text" id="crm_support_tickets_captcha_secret_key" name="crm_support_tickets_captcha_secret_key" value="<?php echo esc_html( get_option('crm_support_tickets_captcha_secret_key') ); ?>" />
				        </td>
				    </tr>

				    <tr>
				        <td></td>
				        <td><?php esc_html_e('Enter the recaptcha secret key.', 'crm-support-tickets'); ?></td>
				    </tr>
				</table>

			<table style="margin-top: 50px;">
				  <!-- Uninstall -->

					<tr> 
						<th> <?php esc_html_e('Uninstall Settings', 'crm-support-tickets') ?> </th>
					</tr>
					<tr valign="top">
					  <th scope="row"><label for="crm_support_tickets_delete_all_uninstall"><?php esc_html_e('Delete All Data On Uninstall', 'crm-support-tickets'); ?></label></th>

				  	  <td ><input  name="crm_support_tickets_delete_all_uninstall" id="crm_support_tickets_delete_all_uninstall" type="checkbox" value="1" class="code" <?php echo (checked( 1,  get_option( 'crm_support_tickets_delete_all_uninstall' ), false )); ?>  /></td>
		 			  <td> <?php esc_html_e('When enabled, ALL DATA including existing tickets will be permanently deleted when you uninstall the plugin.  Warning - make sure you really want to do this.  It is best to backup your data before performing this action', 'crm-support-tickets'); ?></td>	
				  </tr>


				<?php settings_fields( 'crm_support_tickets_options_group' ); ?>
			</table>
				<?php  submit_button(); ?>
			</form>
		
		</div>
		<?php
		echo wp_nonce_field( 'crm_tickets_nonce_action', 'crm_tickets_nonce_field' );
		Scripts::addRole();
 	}


 	/**
 	 * show pro license status if installed
 	 */ 
 	public function showProLicenseStatus() {

 		if ($this->isProVersionInstalled()) {
	 		$status = License::getProLicenseStatus();
	 		error_log(' in base tickets, calling pro tickets getProLicenseStatus ' . $status);
	 	}
 	}

 	/**
 	 * need to know whether the role in question has already been added to the option
 	 * @return bool
 	 */ 
 	public function isRoleSet($role) {
 		$roles = get_option('crm_support_tickets_agent_roles');
 		if ( in_array($role, $roles)) {
 			return true;
 		}
 	}


 	public function getAgentRoles () {
 		//on initial load, set the default
 		if (empty(get_option('crm_support_tickets_agent_roles'))) {
 			$agent_roles = array('administrator');
 			update_option('crm_support_tickets_agent_roles', $agent_roles );
 		}

 		return get_option('crm_support_tickets_agent_roles');
 	}


 	public function registerSettings() {
 		//strings
	   $settings = array(
	   	'crm_support_tickets_slug_to_tickets',
	   	'crm_support_tickets_captcha_site_key',
	   	'crm_support_tickets_captcha_secret_key',);

	   //strings
	   foreach ($settings as $setting) {
	   		add_option($setting);

	   		$args = array(
			'type' 				=> 'string', 
			'sanitize_callback' => 'sanitize_text_field',
			'default'			 => NULL,
				);
			register_setting( 'crm_support_tickets_options_group', $setting, $args );
		}


		/**
		 * custom
		 */ 
   		add_option('crm_support_tickets_email_from_name', get_bloginfo('name'));
   		add_option('crm_support_tickets_auto_close_days', '7');

   		$args = array(
		'type' 				=> 'string', 
		'sanitize_callback' => 'sanitize_text_field',
		'default'			=> get_bloginfo('name'),
			);
		register_setting( 'crm_support_tickets_options_group', 'crm_support_tickets_email_from_name', $args );

   		$args = array(
		'type' 				=> 'string', 
		'sanitize_callback' => 'sanitize_text_field',
			);
		register_setting( 'crm_support_tickets_options_group', 'crm_support_tickets_auto_close_days', $args );
		/**
		 * custom
		 */ 


   		add_option('crm_support_tickets_from_email', $this->getAdminEmail());

   		$args = array(
		'type' 				=> 'string', 
		'sanitize_callback' => 'sanitize_text_field',
		'default'			=> $this->getAdminEmail(),
			);
		register_setting( 'crm_support_tickets_options_group', 'crm_support_tickets_from_email', $args );

			
		/**
		 * default on
		 */ 
		$checkboxes_on = array(
			'crm_support_tickets_enable_customer_email',
			'crm_support_tickets_enable_admin_email',

		);

	   foreach ($checkboxes_on as $checkbox) {
	   		add_option($checkbox, '1'); //default

			$args = array(
				'type' 				=> 'boolean', 
				'sanitize_callback' => 'sanitize_text_field',
				'default' 			=> 1,
			);
			register_setting( 'crm_support_tickets_options_group', $checkbox, $args );
		}

		/**
		 * default off
		 */ 
		$checkboxes_on = array(
			'crm_support_tickets_enable_captcha',
			'crm_support_tickets_delete_all_uninstall',
			'crm_support_tickets_enable_auto_close'

		);

	   foreach ($checkboxes_on as $checkbox) {
	   		add_option($checkbox);

			$args = array(
				'type'				=> 'boolean', 
				'sanitize_callback' => 'sanitize_text_field',
				'default' 			=> null,
			);
			register_setting( 'crm_support_tickets_options_group', $checkbox, $args );
		}

		/**
		 * Pro Settings
		 */ 
		if ($this->isProVersionInstalled()) {
	
			add_option('crm_pro_support_tickets_license_key', null);

	   		$args = array(
			'type' 				=> 'string', 
			'sanitize_callback' => 'sanitize_text_field',
			'default'			=> null,
				);
			register_setting( 'crm_support_tickets_options_group', 'crm_pro_support_tickets_license_key', $args );
			}
 	}

 	/**
 	 * checking the pro version license class
 	 */ 
 	public function isProVersionInstalled() {
 		if (class_exists( License::class ) && class_exists(ProScripts::class)) {
 			return true;
 		}
 	}

	public function renderSupportTickets() {
		if (is_user_logged_in() ) {
			return $this->FrontendTicketDashboard();
		} else {
			$html = '<div class="crm_ticket_center">';
				$html .= '<h2>' . esc_html('Please log in in order to view/submit support tickets', 'crm-support-tickets') . '</h2>';
				$html .='<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" alt="' . esc_attr( "Login", "crm-support-tickets")  . '">' .
			    esc_html( "Login", "crm-support-tickets" ) . '</a>';
		    $html .= '</div>';
			return $html;
		}
	}

	/**
	 * user is already logged in
	 */ 
	public function FrontendTicketDashboard() {

		$html = '<div class="crm_ticket_container">';
			$html .= '<button class="create_new_ticket crm_tickets_button">' . esc_html("Create New Ticket", "crm-support-tickets") . '</button>';
			$html .= $this->getCommentForm ();
			$html .= $this->showExistingFrontendTickets();
		$html .= '</div>';	

		return $html;
	}


	public function showAdminTicket($post_id) {
		$html = '<div class="crm_ticket_container">';
		$html .= $this->showIndividualTicket($post_id, true);
		$html .= '</div>';
		return $html;

	}


	public function showExistingFrontendTickets() {
		$ids = $this->getTicketIds(get_current_user_id());
		if ($ids && is_array($ids)) {
			$html = '<div class="crm_ticket_container">';
			foreach ($ids as $post_id) {
				$html .= $this->showIndividualTicket($post_id);
			}

			$html .= '</div>'; //container
			$html .= $this->processForm();
			return $html;
		} else {
			$html = $this->processForm();
			$html .= '<h3>' . esc_html("You don't have any tickets yet", "crm-support-tickets") . '</h3>';
			return $html;
		}
	}


	public function showIndividualTicket($post_id, $hide_view_ticket_button = false) {
				$html = '<table width=	"100%" class="crm_tickets_table" id="' . esc_attr($post_id) . '">';
					$html .= '<th>' . esc_html("Ticket Id", "crm-support-tickets") . '</th>';
					$html .= '<th>' . esc_html("Subject", "crm-support-tickets") . '</th>';
					$html .= '<th>' . esc_html("Status", "crm-support-tickets") . '</th>';
					$html .= '<th>' . esc_html("Excerpt", "crm-support-tickets") . '</th>';
					$html .= '<th>' . esc_html("Created", "crm-support-tickets") . '</th>';
					if (false === $hide_view_ticket_button) {
						$html .= '<th>' . esc_html("Action", "crm-support-tickets") . '</th>';
					}

					$html .= '<tr>';
						$html .= '<td>' . esc_html($post_id) . '</td>';
						$html .= '<td>' . esc_html($this->getTicketData ($post_id , 'subject')) . '</td>';
						$html .= '<td>' . esc_html($this->getTicketData ($post_id , 'status')) . '</td>';
						$html .= '<td>' . esc_html($this->getTicketData ($post_id , 'excerpt')) . '</td>';
						$html .= '<td>' . esc_html($this->getTicketData ($post_id , 'post_date')) . '</td>';
						if (false === $hide_view_ticket_button) {
							$html .= '<td width="120px"> <button id="' . esc_attr($post_id) . '" class="crm_open_ticket crm_tickets_button">' . esc_html("View Ticket", "crm-support-tickets") . '</button></td>';
						}
					$html .= '</tr>';
				$html .= '</table>';
				$html .= '<div class="replies_data" id="crm_replies_' . esc_attr($post_id) . '">';
				$html .= '</div>'; //reply div

				$html .= '<br>';

				return $html;
	}


	public function getTicketData ($ticket_id, $context) {

		$post = get_post($ticket_id);

		switch ($context) {
			case 'subject':
				$data = get_post_meta($ticket_id, 'crm_ticket_subject', true);
				break;

			case 'status':
				$data = $this->getPrettyName( get_post_meta($ticket_id, 'crm_ticket_status', true));
				break;

			case 'post_date':
				$data = $post->post_date;
				$data = date('l, M j, Y ', strtotime($data));
				break;

			case 'content':
				$data = $post->post_content;
				break;												
			
			case 'excerpt':
				$data = $post->post_content;
				$data = substr($data,0,60) . '.....';
				break;

			case 'long_excerpt':
				$data = $post->post_content;
				$data = substr($data,0,200) . '.....';
	
				break;

			default:
				// code...
				break;
		}
		return $data;
	}



	/**
	 * @return bool (passed google recaptcha challenge)
	 */ 
	public function isPassedCaptcha () {


		$sitekey = esc_html(get_option('crm_support_tickets_captcha_site_key'));

		// run the check if they are using recaptcha
		if (get_option('crm_support_tickets_enable_captcha')) {
		    if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {

		    	/** captcha posted **/
		        $secret = esc_html(get_option('crm_support_tickets_captcha_secret_key'));
				$args = array(
					'timeout'     => 5,
					'redirection' => 5,
					'httpversion' => '1.0',
					'user-agent'  => 'WordPress/' . esc_url (get_bloginfo( 'url' )),
					'blocking'    => true,
					'headers'     => array(),
					'cookies'     => array(),
					'body'        => null,
					'compress'    => false,
					'decompress'  => true,
					'sslverify'   => true,
					'stream'      => false,
					'filename'    => null
				);

				$response =  wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret='. esc_attr($secret) .'&response=' . $_POST['g-recaptcha-response'], array( 'timeout' => 120, 'httpversion' => '1.1' ) );      
		        $body = wp_remote_retrieve_body( $response ); //get body
		        $responseData = json_decode($body); //need the body as an object

		        if($responseData->success) {
		        	return true;
		        } else {
		        	return false; //failed the captcha
		        }
		        /** captcha posted **/

		    } else {
		    	//failed to try the captcah
		    	return false;
		    }
		} else {
			//pass them through, recaptcha is not enabled
			return true;
		}
	}
 

	public function processForm() {

		if (isset( $_POST['crm_tickets_field'] ) && wp_verify_nonce( $_POST['crm_tickets_field'], 'crm_tickets_action' ) ) {


			if (!$this->isPassedCaptcha()) {
				?> <script>
					alert('Sorry the reCaptcha Failed. Reload the page and try again.')
					window.location=window.location; //to prevent form submission error
					</script> <?php
				return; //stop processing form
			}
			/**
			 * new tick processing
			 */


			 if (isset($_POST['crm_new_ticket'])) {
				// create post object with the form values
				$my_cptpost_args = array(
				'post_content'  => sanitize_textarea_field($_POST['ticketContent']),
				'post_status'   => 'publish',
				'post_type' 	=> 'crm_support_ticket',
				'post_author'	=> $this->getAdminId(),	

				);

				// insert the post into the database
				$post_id = wp_insert_post( $my_cptpost_args);

				//now update the post to set the title
				if(!is_wp_error($post_id)){
					$args = array(
					'ID'			=> $post_id,
					'post_title'	=> $post_id);
					wp_update_post($args);
				} else {
					error_log('Error inserting ticket' . $post_id->get_error_message());
				}

				$user_id =  get_current_user_id();
				$user_object = get_user_by('ID', $user_id);
				$agent_id = null;
				$commentor_id = $user_id;
				$status = null;
				$post_content = sanitize_textarea_field($_POST['ticketContent']);

				$subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']): null;
				update_post_meta($post_id, 'crm_ticket_subject', $subject);

				//set initial status
				update_post_meta($post_id, 'crm_ticket_status', 'awaiting_agent_response');
				update_post_meta($post_id, 'crm_ticket_priority', 'normal');

				//set user info
				update_post_meta($post_id, 'crm_ticket_customer_id', $user_id);
				update_post_meta($post_id, 'crm_ticket_customer_name', $user_object->first_name . ' ' . $user_object->last_name);
				update_post_meta($post_id, 'crm_ticket_email',  $user_object->user_email);

				//update the custom table with replies to/from
				$this->saveTicketReply($user_id, $agent_id, $commentor_id, $post_id, $post_content, $attachment = null);	
				$this->sendCustomerEmail ($user_id, $post_id, 'new_ticket'); 
				$this->sendAdminEmail ($post_id, 'new_ticket'); 

				//refresh
				?>
					<script>
						 window.location=window.location;
					</script>
				<?php

			} else if (isset($_POST['crm_reply_ticket'])) {
				/**
				 * reply ticket processing, 
				 */ 
				$user_id =  get_current_user_id();
				$agent_id = null;
				$commentor_id = $user_id;
				// $status = 'awaiting_agent_response';
				$post_content = sanitize_textarea_field($_POST['ticketContent']);
				$post_id = isset($_POST['crm_ticket_id']) ? sanitize_text_field($_POST['crm_ticket_id']): null;

				update_post_meta($post_id, 'crm_ticket_status', 'awaiting_agent_response');
				//update the custom table with replies to/from
				$this->saveTicketReply($user_id, null, $commentor_id, $post_id, $post_content, $attachment = null);

				//notify admin,cust replied
				$this->sendAdminEmail ($post_id, 'reply'); 
			} 	
		}
	}

	public function getAdminTicketReplies($ticket_id) {

		// get all replies for this ticket
		$replies = null;
		$html = null;
		$ticket_id = intval($ticket_id);

	 	global $wpdb;
	 	$table =   $wpdb->prefix . 'crm_tickets';
	 	$replies = $wpdb->get_results (
	 		$wpdb->prepare( "SELECT * FROM $table WHERE post_id = %d", $ticket_id ) 
		 	);


		if ($replies && is_array($replies)) {
			foreach ($replies as $reply) {

				$date = date('l, M j, Y ', strtotime($reply->last_updated));

				//if user commented then id, if admin, then id
				$user_id = $reply->user_id;
				$agent_id = $reply->agent_id;
				$commentor_id = $reply->commentor_id;
	
				$content = $reply->post_content;
				$user = get_user_by('ID', $commentor_id);

				$background = $this->getBackgroundClassByUser($commentor_id);
		        $username = $user->first_name . ' ' . $user->last_name;

	    		$html .= '<div class="crm_tickets_preserve_lines" style="margin-top: 20px; margin-left: 20px; background-color: ' . esc_attr($background) . ';">';
	      		$html .= '<p>' . get_avatar($commentor_id, '40') . '  ' . $this->getCommentorType($commentor_id) . '  ' . esc_html($username) . '  ' . esc_html(": Commented on ", "crm-support-tickets") . '<span >' . esc_html($date) . ' </span></p>';
	      		$html .= '<p style="white-space: pre-line">' . esc_html($content) . '</p>';
	    		$html .= '</div>';		
			}
		}


		return $html;
	}

	/**
	* show a different color based on who is commenting
	* @return css color
	*/ 
	public function getBackgroundClassByUser ($user_id) {

		$user = get_userdata( $user_id );
		if (is_object($user)) {
			$roles = $user->roles; // obtaining the role 
		} else {
			$roles = null;
		}

		$agent_roles = $this->getAgentRoles();

		if(count(array_intersect( $agent_roles, $roles)) > 0){

			return '#dddddd';//agent color
		} else {
			return '#C2D5FE'; //subscriber color
		}

	}

	/**
	 * @return escaped html type (customer or agent)
	 */ 
	public function getCommentorType($user_id) {
		$user = get_userdata( $user_id );
		if (is_object($user)) {
			$roles = $user->roles; // obtaining the role 
		} else {
			$roles = null;
		}

		$agent_roles = $this->getAgentRoles();

		if(count(array_intersect( $agent_roles, $roles)) > 0){
			return esc_html('Agent', 'crm-support-tickets');
		} else {
			return esc_html('Customer', 'crm-support-tickets');
		}		
	}


	/**
	 * @return an array of all availble roles
	 */ 
	function getEditableRoles() {
	    global $wp_roles;

	    $all_roles = $wp_roles->roles;
	    $editable_roles = apply_filters('editable_roles', $all_roles);

	    $summary = array();

	    foreach ($all_roles as $key => $role) {
	    	$summary[$key] = $role['name'];
	    }

	    return $summary;
	}
	public function getFrontendTicketReplies($ticket_id) {

		// get all replies for this ticket
		$replies = null;
		$html = null;
		$ticket_id = intval($ticket_id);

	 	global $wpdb;
	 	$table =   $wpdb->prefix . 'crm_tickets';
	 	$user_id = get_current_user_id();
	 	$replies = $wpdb->get_results ( 
	 		$wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d and post_id = %d", $user_id, $ticket_id )
	 	);

		if ($replies && is_array($replies)) {
			foreach ($replies as $reply) {

				$date = date('l, M j, Y ', strtotime($reply->last_updated));
				$user_id = $reply->user_id;
				$agent_id = $reply->agent_id;
				$commentor_id = $reply->commentor_id;

				$content = $reply->post_content;

				$user = get_user_by('ID', $commentor_id);
		        $username = $user->first_name . ' ' . $user->last_name;
		        $background = $this->getBackgroundClassByUser($commentor_id);

	    		$html .= '<div style="margin-top: 20px; margin-left: 20px; background-color: ' . esc_attr($background) . ';">';
	    		$html .= '<p>'. get_avatar($commentor_id, '40') . '  ' .  esc_html($username) . '  ' . esc_html(": Commented on ", "crm-support-tickets") . '<span >' . esc_html($date) . ' </span></p>';
	    		$html .= '<div>' . esc_html($reply->attachment) . '</div>';
	    		$html .= '<p style="white-space: pre-line">' . stripslashes(esc_html($content)) . '</p>';
	    		$html .= '</div>';
			}

			$html .= '<button class="crm_tickets_post_reply crm_tickets_button">' . esc_html("Post Reply") . '</button>' . '<button class="crm_tickets_return crm_tickets_button" style="margin-left: 20px">' . esc_html("Return To Tickets", "crm-support-tickets") . '</button>';
			$html .= $this->getReplyForm ($ticket_id);

		}

		return $html;
	}


	public function isOpen ($ticket_id) {
		$status = get_post_meta($ticket_id, 'crm_ticket_status', true);
		if ('Closed' !== $status) {
			return true;
		}
	}

	/**
	 * @return array of valid product ids that are being scanned
	 * @param scan_mode take_inventory, reduce, receive_orders
	 */ 
	public function getTicketIds($user_id) {

		$args = array(
			  'post_type'   	=> 'crm_support_ticket',
			  'post_status' 	=> array('publish', 'draft', 'private', 'pending' ), //bail on trash
			  'numberposts'		=> '-1',//all
			  'meta_key'		=> 'crm_ticket_customer_id',
			  'meta_value'		=> intval($user_id),
			  'fields'			=> 'ids'
			);

		$ids = get_posts($args);
		return $ids;	
	}


	/**
	 * save the scan
	 *   Examples:
	 *     wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 *     wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 *
	 */ 
	public static function saveTicketReply($user_id, $agent_id, $commentor_id, $post_id, $post_content, $attachment = null) {
		global $wpdb;

		$table =   $wpdb->prefix . 'crm_tickets';
		$wpdb->insert( 
		  $table, 
		  array( 
		    'user_id'		=> intval($user_id),
		    'agent_id'		=> intval($agent_id),
		    'commentor_id'	=> intval($commentor_id),
		    'post_id'		=> intval($post_id),
		    'post_content'	=> sanitize_textarea_field($post_content),

		  ), array (
		  	'%d',
		  	'%d',
		  	'%d',
		  	'%d',
		  	'%s',
		  ) 
		);
	}

	public function getCustomerEmail($user_id) {
		$user = get_user_by('ID', $user_id);
		if (is_object($user)) {
			$email = $user->user_email;
			return $email;
		}
	}

	public function getCustomerFirstName($user_id) {
		$user = get_user_by('ID', $user_id);
		if (is_object($user)) {
			$name = $user->first_name;
			return $name;
		}		
	}

	public function getCustomerLastName($user_id) {
		$user = get_user_by('ID', $user_id);
		if (is_object($user)) {
			$name = $user->last_name;
			return $name;
		}		
	}

	public function getCustomerFullName ($user_id) {
		return $this->getCustomerFirstName($user_id) . ' ' . $this->getCustomerLastName($user_id);
	}

	public function getAgentFullName ($user_id) {
		$user = get_user_by('ID', $user_id);
		if (is_object($user)) {
			$full_name = $user->first_name . ' ' . $user->last_name;
			return $full_name;

		}	
	}

	public function getAgentId($ticket_id) {
		return get_post_meta($ticket_id, 'crm_ticket_agent_id', true);

	}

	public function getTicketSubject ($ticket_id) {
		return get_post_meta($ticket_id, 'crm_ticket_subject', true);
	}

	public function getAdminId () {
		$user = get_user_by('email', $this->getAdminEmail());
		if (is_object($user)) {
			return $user->ID;
		}
	}


	public function getAdminEmail() {
			return get_bloginfo('admin_email');
	}

	public function isSendingCustomerEmails() {
		return get_option('crm_support_tickets_enable_customer_email', true);
	}

	public function isSendingAdminEmails() {
		return get_option('crm_support_tickets_enable_admin_email', true);
	}

	public function getCustomerId ($ticket_id) {
		return get_post_meta($ticket_id, 'crm_ticket_customer_id', true);
	}

	public function getTicketExcerpt ($ticket_id) {
		//void
	}

	/**
	 * if they have the slug set, we can calculate the url
	 */ 
	public function getTicketUrl() {
		if (get_option('crm_support_tickets_slug_to_tickets')) {
			return get_bloginfo('wpurl') . '/' . get_option('crm_support_tickets_slug_to_tickets');
		}
	}

	public function getTicketDate($ticket_id) {
		$post = get_post($ticket_id);
		if (is_object($post)) {
			return $post->post_date;
		}
	}

	/**
	 * @param ticket_id
	 * @param context (new_ticket, reply)
	 */ 
	public function sendCustomerEmail ($user_id, $ticket_id, $context) {

        $to = $this->getCustomerEmail($user_id);

        if (!$this->isSendingCustomerEmails()) {
            return;
        }

        if (!$to) {
        	//no email to send to
        	return;
        }

        switch ($context) {
        	case 'new_ticket':
        		$subject = esc_html('We Have Recieved Your Ticket ', 'crm-support-tickets') . esc_html($ticket_id);
        		$message = '<p>' . esc_html("Hello ", 'crm-support-tickets') . esc_html($this->getCustomerFirstName($user_id)) . '</p>';
				$message .= '<p>' . esc_html("Thank you for using our ticketing system.", 'crm-support-tickets') . '</p>';
				$message .= '<p>' . esc_html("We have created a new ticket.", 'crm-support-tickets') . '</p>';
        		break;
        	
        	case 'reply':
        		$subject = esc_html('New Message Regarding Your Ticket ', 'crm-support-tickets') . esc_html($ticket_id);
        		$message = '<p>' . esc_html("Hello ", 'crm-support-tickets') . esc_html($this->getCustomerFirstName($user_id)) . '</p>';
				$message .= '<p>' . esc_html("Thank you for using our ticketing system.", 'crm-support-tickets') . '</p>';
				$message .= '<p>' . esc_html("We have replied to your ticket.", 'crm-support-tickets') . '</p>';
        		break;
        }

		$message .= '<p>' . esc_html("Ticket ID: ", 'crm-support-tickets') . esc_html($ticket_id) .'</p>';
		$message .= '<p>' . esc_html("Ticket Subject: ", 'crm-support-tickets') . esc_html($this->getTicketSubject($ticket_id)) .'</p>';
		$message .= '<p>' . esc_html("Posted On: ", 'crm-support-tickets') . esc_html($this->getTicketDate($ticket_id)) . '</p>';

		if (get_option('crm_support_tickets_slug_to_tickets')) {
			$message .= '<p>' . esc_html("You may review your ticket here ", 'crm-support-tickets') . $this->getTicketUrl() . '</p>';
		}


        $attachments = null;
        $headers = "Content-type: text/html";
        add_filter( 'wp_mail_from_name', array($this, 'filterEmailName'));
		add_filter( 'wp_mail_from', array($this, 'filterEmailFromEmail'));

        wp_mail($to, $subject, $message, $headers, $attachments);

        remove_filter( 'wp_mail_from_name', array($this, 'filterEmailName')); 
        remove_filter( 'wp_mail_from', array($this, 'filterEmailFromEmail'));
	}


	public function filterEmailName() {
		 return get_option('crm_support_tickets_email_from_name');
	}


	public function filterEmailFromEmail() {
		return get_option('crm_support_tickets_from_email');
	}


	/**
	 * @param ticket_id
	 * @param context (new_ticket, reply)
	 */ 
	public function sendAdminEmail ($ticket_id, $context) {

        $to = $this->getAdminEmail();
        if (!$this->isSendingAdminEmails()) {
            return;
        }

        if (empty($to)) {
        	error_log('Cannot send admin email, address not set ' . __FUNCTION__ );
        	return;
        }

        switch ($context) {
        	case 'new_ticket':
        		$subject = esc_html('New Ticket Received ', 'crm-support-tickets') . esc_html($ticket_id);
        		$message = null;
        		break;
        	
        	case 'reply':
        		$subject = esc_html('New Reply From Customer For Ticket: ', 'crm-support-tickets') . esc_html($ticket_id);
        		$message = '<p>' . esc_html("Hello ", 'crm-support-tickets') . esc_html($this->getCustomerFirstName($this->getCustomerId ($ticket_id))) . '</p>';
        		break;
        }

		$message .= '<p>' . esc_html("Customer: ", 'crm-support-tickets') . esc_html($this->getCustomerFullName ($this->getCustomerId ($ticket_id)) ) .'</p>';
		$message .= '<p>' . esc_html("Ticket ID: ", 'crm-support-tickets') . esc_html($ticket_id) .'</p>';
		$message .= '<p>' . esc_html("Ticket Subject: ", 'crm-support-tickets') . esc_html($this->getTicketSubject($ticket_id)) .'</p>';
		$message .= '<p>' . esc_html("Posted On: ", 'crm-support-tickets') . esc_html($this->getTicketDate($ticket_id)) . '</p>';
		$message .= '<p>' . esc_html("Excerpt: ", 'crm-support-tickets') . esc_html($this->getTicketData ($ticket_id, 'long_excerpt') ) . '</p>';

        $attachments = null;
        $headers = "Content-type: text/html";

        add_filter( 'wp_mail_from_name', array($this, 'filterEmailName'));
		add_filter( 'wp_mail_from', array($this, 'filterEmailFromEmail'));

        wp_mail($to, $subject, $message, $headers, $attachments);

        remove_filter( 'wp_mail_from_name', array($this, 'filterEmailName')); 
        remove_filter( 'wp_mail_from', array($this, 'filterEmailFromEmail'));
	}	
	/**
	* register the post type
	*/ 
	public function register_custom_post_type_ticket  () {

		$labels = array(
		  'name'               => _x( 'Support Tickets', 'post type general name', 'crm-support-tickets' ),
		  'singular_name'      => _x( 'Support Ticket', 'post type singular name', 'crm-support-tickets' ),
		  'add_new'            => _x( 'Add New', 'book', 'crm-support-tickets' ),
		  'add_new_item'       => __( 'Add New Support Ticket', 'crm-support-tickets' ),
		  'edit_item'          => __( 'Edit Support Ticket', 'crm-support-tickets' ),
		  'new_item'           => __( 'New Support Ticket', 'crm-support-tickets' ),
		  'all_items'          => __( 'All Support Tickets', 'crm-support-tickets' ),
		  'view_item'          => __( 'View Support Ticket', 'crm-support-tickets' ),
		  'search_items'       => __( 'Search Support Tickets', 'crm-support-tickets' ),
		  'not_found'          => __( 'No Support Tickets found', 'crm-support-tickets' ),
		  'not_found_in_trash' => __( 'No Support Tickets found in the Trash', 'crm-support-tickets' ), 
		  'menu_name'          => __('Support Tickets', 'crm-support-tickets')
		);

		
		$should_show = $this->sufficentPriveledges();
		$args = array(
			'labels'        		=> $labels,
			'description'   		=> 'Holds our products and product specific data',
			'exclude_from_search'	=> true,
			'publicly_queryable'	=> false,
			'show_ui'				=> $should_show,
			'show_in_nav_menus'		=> false,
			'menu_position' 		=> 57,
			'supports'      		=> false,
			'has_archive'   		=> true,
		);
		register_post_type( 'crm_support_ticket', $args ); 

	}

	/**
	 * @return bool, does this user have sufficient privs to view the admin support tickets
	 * note that if they select a role lower than conributor, wp won't allow them in the admin area anyway
	 */ 
	public function sufficentPriveledges(){

		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );
		if (is_object($user)) {
			$roles = $user->roles; // obtaining the role 
		} else {
			$roles = null;
		}

		if(empty($roles)) {
			//stop, array_intersect requires an array
			return false;
		}

		$agent_roles = $this->getAgentRoles();

		if(count(array_intersect( $agent_roles, $roles)) > 0){

			return true; //user is in agent roles
		} else {
			return false; //not in user role
		}
	}

	/**
	 * add link to pro if license is not set
	 */ 
	public function addLinkToProPluginPage($links) { 
		if (!$this->isProVersionInstalled() ) {
			$link_to_pro = '<a href="https://chimneyrockmgt.com/support-tickets/">' . esc_html('Upgrade To Premium', 'crm-support-tickets') . '</a>'; 
			$links[] = $link_to_pro;
		}
		return $links; 
	}

	public function reportPageContent() {

		$Stats = new Stats;

		?>
		<h2 class="crm_ticket_center"> <?php esc_html_e('Ticket Status', 'crm-support-tickets'); ?> </h2>
		<svg id="ticket_chart" style="width:720px;height:300px"></svg>
		<script src="https://d3js.org/d3.v4.js"></script>
		<script>
			let data = {
			    "<?php esc_html_e($this->getPrettyName('open'))  ?>": <?php esc_html_e($Stats->getStats ('open')); ?>,
			    "<?php esc_html_e($this->getPrettyName('closed'))  ?>": <?php esc_html_e($Stats->getStats ('closed')); ?>,
			    "<?php esc_html_e($this->getPrettyName('awaiting_agent_response'))  ?>": <?php esc_html_e($Stats->getStats ('awaiting_agent_response')); ?>,
			    "<?php esc_html_e($this->getPrettyName('awaiting_customer_response'))  ?>": <?php esc_html_e($Stats->getStats ('awaiting_customer_response')); ?>,
			    "<?php esc_html_e($this->getPrettyName('answered'))  ?>": <?php esc_html_e($Stats->getStats ('answered')); ?>,
			};

			let margin = {top: 20, right: 20, bottom: 30, left: 40};
			let svgWidth = 720, svgHeight = 300;
			let height = svgHeight- margin.top- margin.bottom, width = svgWidth - margin.left - margin.right;
			let sourceNames = [], sourceCount = [];

			let x = d3.scaleBand().rangeRound([0, width]).padding(0.1),
			    y = d3.scaleLinear().rangeRound([height, 0]);
			for(let key in data){
			    if(data.hasOwnProperty(key)){
			        sourceNames.push(key);
			        sourceCount.push(parseInt(data[key]));
			    }
			}
			x.domain(sourceNames);
			y.domain([0, d3.max(sourceCount, function(d) { return d; })]);

			let svg = d3.select("#ticket_chart").append("svg");
			svg.attr('height', svgHeight)
			    .attr('width', svgWidth);

			svg = svg.append("g")
			         .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

			svg.append("g")
			    .attr("class", "axis axis--x")
			    .attr("transform", "translate(0," + height + ")")
			    .call(d3.axisBottom(x));

			svg.append("g")
			    .attr("class", "axis axis--y")
			    .call(d3.axisLeft(y).ticks(5))
			    ;
			        
			// Create rectangles
			let bars = svg.selectAll('.bar')
			    .data(sourceNames)
			    .enter()
			    .append("g");

			bars.append('rect')
			    .attr('class', 'bar')
			    .attr("fill" , "gray")
			    .attr("x", function(d) { return x(d); })
			    .attr("y", function(d) { return y(data[d]); })
			    .attr("width", x.bandwidth())
			    .attr("height", function(d) { return height - y(data[d]); });

			    
			bars.append("text")
			    .text(function(d) { 
			        return data[d];
			    })
			    .attr("x", function(d){
			        return x(d) + x.bandwidth()/2;
			    })
			    .attr("y", function(d){
			        return y(data[d]) - 5;
			    })
			    .attr("font-family" , "sans-serif")
			    .attr("font-size" , "14px")
			    .attr("fill" , "black")
			    .attr("text-anchor", "middle");

			</script>
			<?php
	}

}

use \CrmTickets\Inc\Tickets as Tickets;
Tickets::getInstance ();