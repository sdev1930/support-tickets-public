<?php 

namespace CrmTickets\Inc;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Scripts {

	public static function addRole() {

		?>
	    <script>
	    jQuery(document).ready(function($) {

	    	$('.crm_settings').on('click', '.crm_add_role', function(e){
	    		e.preventDefault()
	    		$('.show_roles').show();
	    	});

	      $('.crm_settings').on('click', '.crm_delete_this_role', function(e){
	        e.preventDefault();

		    if (window.confirm( 'Ok to delete this role?') ) {
			} else {
			return false;
			}
	          var nonce = $('#crm_tickets_nonce_field').val();
	          var role = $(this).attr('id');

	           $.ajax({
	              method: "POST",
	              url: ajaxurl,
	              data: {
	                action: 'crm_tickets_delete_role_action',
	               	role: role,
	                nonce: nonce
	              },
	            })
	          
	          .done (function(data){
	            data = JSON.parse(data)
	            window.location=window.location;
	          })

	        .fail(function() {
	            console.log('Scipts ajax delete role failure');                
	          });

	      });  //click

	      $('.crm_settings').on('click', '.crm_add_this_role', function(e){
	        e.preventDefault();

	          var nonce = $('#crm_tickets_nonce_field').val();
	          var role = $(this).attr('id');

	            $.ajax({
	              method: "POST",
	              url: ajaxurl,
	              data: {
	                action: 'crm_tickets_add_role_action',
	               	role: role,
	                nonce: nonce
	              },
	            })
	          
	          .done (function(data){
	            data = JSON.parse(data)
	            window.location=window.location;
	          })

	        .fail(function() {
	            console.log('Scipts ajax add role failure');                
	          });

	      });  //click
	    });
		</script>
		<?php
	}

}


