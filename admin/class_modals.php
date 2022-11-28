<?php

namespace CrmTickets\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}



class Modal {

    public function __construct(){
        add_action( 'current_screen', array($this,'setExportModal' ));
        add_action( 'current_screen', array($this,'setCannedModal' ));
    }

    public function setExportModal () {

        $currentScreen = get_current_screen();
        if(  $currentScreen->id === "edit-crm_support_ticket" ) {
            add_action ('admin_head', array($this, 'getExportModal'));
        }
            
    }

    public function setCannedModal () {

        $currentScreen = get_current_screen();
        if(  $currentScreen->id === "crm_support_ticket" ) {
            add_action ('admin_head', array($this, 'getCannedModal'));
        }
            
    }

    public function getCannedModal() {
        ?>
        <div class="hidden crm_tickets_modal" id="crm_modal_go_pro_canned_replies">
            <span onclick="document.getElementById('crm_modal_go_pro_canned_replies').classList.add('hidden')" class="crm_top_right btnSelect crm_modal_button ">&times;</span>
            <p style="font-size: 40px;">
            <?php esc_html_e('Adding canned replies is a Pro Feature. ', 'crm-support-tickets') ?> </p> 
            <p style="font-size: 40px;"><a href="https://chimneyrockmgt.com/support-tickets/">Go PRO</a></p> 
        </div>
        <script>
                function crmOpenExportModal() {
                  var element = document.getElementById("crm_modal_go_pro_canned_replies");
                  element.classList.remove("hidden");
            }
        </script>
        <?php

    }

    public function getExportModal() {
        ?>
        <div class="hidden crm_tickets_modal" id="crm_modal_go_pro_export_replies">
            <span onclick="document.getElementById('crm_modal_go_pro_export_replies').classList.add('hidden')" class="crm_top_right btnSelect crm_modal_button ">&times;</span>
            <p style="font-size: 40px;">
            <?php esc_html_e('Exporting tickets with replies is a Pro Feature. ', 'crm-support-tickets') ?> </p> 
            <p style="font-size: 40px;"><a href="https://chimneyrockmgt.com/support-tickets/">Go PRO</a></p> 
        </div>
        <script>
                function crmOpenExportModal() {
                  var element = document.getElementById("crm_modal_go_pro_export_replies");
                  element.classList.remove("hidden");
            }
        </script>
        <?php

    }

}

new \CrmTickets\Admin\Modal ();