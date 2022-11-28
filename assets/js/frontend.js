jQuery(document).ready(function($) {

	$(".create_new_ticket").click(function()  {
		$(".crm_ticket_create_new").show();
    $('.create_new_ticket').hide();
    $("#crm_new_ticket_form :input").prop("disabled", false);  //enable new ticket form
	});


	$('.crm_ticket_container').on('click', '.crm_open_ticket', function(e) {
	  e.preventDefault();

	  var ticket_id = $(this).attr('id');
	  var nonce = $('#crm_tickets_field').val();
        $.ajax({
            method: "POST",
            url: frontend.ajaxurl,
            data: {
                action: 'crm_tickets_view_tickets_action',
                ticket_id: ticket_id,
                nonce: nonce
            }
        })
          .done(function( data ) {
            data = JSON.parse (data);
            $('#crm_replies_' + data.ticket_id).replaceWith(data.replies_data) ;
            $("#crm_new_ticket_form :input").prop("disabled", true); //disable new ticket form
             
          })

          .fail(function( data ) {
            console.log('Failed AJAX Call open form :( /// Return Data: ' + data);
          });
	}); 

  $('.crm_ticket_container').on('click', '.crm_tickets_return', function (e) {
    e.preventDefault();
    window.location=window.location;
  });

  $('.crm_ticket_container').on('click', '.crm_tickets_post_reply', function () {
    $('.crm_ticket_create_reply').show();
    $('.crm_tickets_post_reply').hide();
  });


});