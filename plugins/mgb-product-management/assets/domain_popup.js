var domain_popup_id = 522;
var tlds = [ "de", "eu", "com", "net", "org", "info", "biz", "at", "mobi", "name" ]
var rest_route = '/wp-json/mgb/v1/check-domain/';
var rest_route_order = '/wp-json/mgb/v1/order-domain/';
var custom_domain = "";
var selected_domain = "";
var proto_id = "";

// we need these backups to have the right needle for our replace function
var backup_verfication_string1 = "0";
var backup_verfication_string2 = "0"; 


function load_domain_popup(website_name, selected_proto_id,kptype) {
            
    proto_id = selected_proto_id;
    
    // set id of website to localStorage
    // localStorage.setItem('wid', text);
    //var backtext = localStorage.getItem('wid');
       
    // Show and update the Popup
    elementorFrontend.documentsManager.documents[domain_popup_id].showModal();

    jQuery('#verify_domain_dialog').hide();
    jQuery('#confirm_domain_dialog').hide();

    // insert website_name in title 
    let popup_title = jQuery("#mgb_select_title").html();
    popup_title = popup_title.replace('{{website_name}}', website_name);
    jQuery("#mgb_select_title").html(popup_title);

    // insert website_name in confirmation
    let verify_comply = jQuery("#mgb_custom_domain_verify_comply").html();
    verify_comply = verify_comply.replace('{{website_name}}', website_name);
    jQuery("#mgb_custom_domain_verify_comply").html(verify_comply);

    // we need these backups to have the right needle for our replace function
    backup_verfication_string1 = jQuery("#mgb_custom_domain_verify_comply").html();
    backup_verfication_string2 = jQuery("#confirm_domain_dialog_p").html(); 

    jQuery("#order_ok").click(function(event) {
        event.preventDefault();
        jQuery('#verify_domain_dialog').hide();
        

/**
 *  SEND DOMAIN ORDER
*/ 
    //console.log("BEFORE REST: " + rest_route_order + selected_domain + '/' + proto_id + '/' + kptype + '/' + uid);    

    op = {};
    op['selected_domain']   = selected_domain;
    op['proto_id']          = proto_id;  
    op['kptype']            = kptype;
    op['uid']               = uid;
    
    // only needed for dsk 
    if(typeof dmv !== 'undefined'){
        op['dmv']               = dmv;
    }
    op_json = JSON.stringify(op);
    //console.log("JSON STRING: " +  op_json);
    jQuery.post( rest_route_order , op_json, function( rest_result ) {
    //console.log("REST RESULT FROM SERVER: " +  rest_result);

    });
   
    // UPDATE tile
        mgb_update_website_tile();
        jQuery('#confirm_domain_dialog').show();
    });

    jQuery("#order_cancel").click(function(event) {
        event.preventDefault();
        jQuery('#verify_domain_dialog').hide();
        jQuery('#select_domain_dialog').show();
    });

    // adjustments on main page after completing the whole process and closing the last popup page
    
    jQuery("#confirm_close").click(function(event) {
        event.preventDefault();
        
        elementorFrontend.documentsManager.documents[domain_popup_id].getModal().hide();

        if ("dsk" == kptype){

            shop_imgs=JSON.parse(localStorage.getItem('shop_imgs'));
            
            dsk_feedback_html =`<div class="mgb_userwebsite_tile mgb_userwebsite_tile-single">
            <div class="mgb_userwebsite_img_big">
               <img loading="lazy" src="`+ shop_imgs[proto_id-1] + ' ' +`">
               <!--<a href="" target="_blank">
                  <div class="mgb_icon_wrapper">
                     <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-settings-cog"></i>
                  </div>
               </a>
               <a href="" target="_blank">
                  <div class="mgb_icon_wrapper mgb_icon_wrapper_eye">
                     <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-eye"></i>
                  </div>
               </a>-->
            </div>
            <div class="mgb_userwebsite_body">
               <h2>Dein Dropshipping Shop</h2>
               <div class="mgb_userwebsite_domain">`+ selected_domain + `</div>
               <div class="mgb_userwebsite_info">
                  <span class="mgb-domain-pending-title">The chosen domain has been ordered </span><!--<i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-info-circle" title="available soon"></i><br><span><b>Temporary domain:</b> <?php echo $website->domain?></span>-->
               </div>
            </div>
            </div>`;

            jQuery('.elementor-element-3d35c42a > .elementor-widget-container > .elementor-shortcode ').html(dsk_feedback_html);
            jQuery('#mgb_instruction_chart').css("display","none");
            
        }

        if ("wbk" == kptype){
           
            wbk_feedback_html =`<div class="mgb_userwebsite_tile mgb_userwebsite_tile-single">
            <div class="mgb_userwebsite_img_big">
               <img loading="lazy" src="https://mitglieder.gruender.de/wp-content/uploads/2022/08/wbk-slider.jpg">
               <!--<a href="" target="_blank">
                  <div class="mgb_icon_wrapper">
                     <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-settings-cog"></i>
                  </div>
               </a>
               <a href="" target="_blank">
                  <div class="mgb_icon_wrapper mgb_icon_wrapper_eye">
                     <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-eye"></i>
                  </div>
               </a>-->
            </div>
            <div class="mgb_userwebsite_body">
               <h2>Deine Webinarplattform</h2>
               <div class="mgb_userwebsite_domain">`+ selected_domain + `</div>
               <div class="mgb_userwebsite_info">
                  <span class="mgb-domain-pending-title">The chosen domain has been ordered </span><!--<i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-info-circle" title="available soon"></i><br><span><b>Temporary domain:</b> <?php echo $website->domain?></span>-->
               </div>
            </div>
            </div>`;

            jQuery('.elementor-element-eb3a191 > .elementor-widget-container > .elementor-shortcode ').html(wbk_feedback_html);
            jQuery('#mgb_instruction_chart').css("display","none");
            
        }

    });

}

jQuery(document).ready(function() {
jQuery(window).on('elementor/frontend/init', function() {
    elementorFrontend.on( 'components:init', function() {
        
        
        // BK 2.0 Popup Call
        jQuery(document).on('click', '.domain_popup_btn' , function(event) {
            event.preventDefault();
            load_domain_popup(jQuery(this).attr('data-website'),jQuery(this).attr('data-proto-id'),'ocb');
        });

        // Pod Popup Call
        jQuery(document).on('click', '.pod_btn' , function(event) {
            event.preventDefault();
            load_domain_popup(jQuery(this).attr('data-website'),jQuery(this).attr('data-proto-id'),'pod');
        });

        // WBK Popup Call, this product has only one protoype with an ID of 3
        jQuery(document).on('click', '#choose-webinar' , function(event) {
            event.preventDefault();
            load_domain_popup('Deine Webinarplattform','3','wbk');
        });

        // DBK Popup Call, 
        jQuery(document).on('click', '.dbk_btn' , function(event) {
            event.preventDefault();
            load_domain_popup(jQuery(this).attr('data-website'),jQuery(this).attr('data-proto-id'),'dbk');
        });

        // DBK 2.0 Popup Call, 
        jQuery(document).on('click', '.dbk2_btn' , function(event) {
            event.preventDefault();
            load_domain_popup(jQuery(this).attr('data-website'),jQuery(this).attr('data-proto-id'),'dbk2');
        });

        jQuery(document).on('click', '#custom_domain_btn', function() {
            mgb_check_domains();
        });
              
        jQuery(document).on('keypress', function(e) {
            if(e.which == 13){
                mgb_check_domains();
            }
        });

  
        

        function mgb_check_domains(){

            // check and validate user input               
            
            custom_domain = jQuery('#custom_domain_input').val();
            let input_error_msg = "";
            jQuery('#custom_domain_results').html('');	

            custom_domain = custom_domain.toLowerCase();

            needle_array = [ "https://www.", "http://www.", "www.", ".de", ".eu", ".com", ".net", ".org", ".info", ".biz", ".at", ".mobi", ".name" ];

            jQuery.each(needle_array, function(key, needle) {

                custom_domain = custom_domain.replace(needle,'');

            });
            
	        let lastchr = custom_domain.substring(custom_domain.length-1, custom_domain.length);
	        let firstchr = custom_domain[0];
            if ((lastchr == "-") || (firstchr == "-")) {
                input_error_msg = "Hyphens are not possible at the beginning and end of domain names.";
            }		
            if (custom_domain.search(/^[a-zA-Z0-9üäö-]+$/) == -1) {
                input_error_msg = "Special characters are not allowed in domain names.";
            }	
            if ((custom_domain.length<3) || (custom_domain.length>32)){
                             
                input_error_msg = "The desired domain should be between 3 and 32 letters long.";
            }
                                 
            if (input_error_msg != "") {
                
                jQuery('#custom_domain_error').html(input_error_msg);
                jQuery('#custom_domain_results').empty();
                return false;
            
            }

            // user input seems fine, now let's check domains for availability

            jQuery('#custom_domain_input').val(custom_domain);
            jQuery('#custom_domain_error').html('');
            jQuery('#custom_domain_results').empty();

            encoded_custom_domain = encodeURIComponent(custom_domain);
            jQuery.each( tlds, function(key,tld) {
                
                jQuery('#custom_domain_results').append("<div class='mgb-domainname-row' id='" + tld + "row'>" + 
                "<div class='mgb-domainname'>" + custom_domain + "." + tld + "</div>" + 
                "<div id='" + tld + "btn' class='mgb-btn-domain-inactive'><a href='#'>wird geprüft...</a></div>" + 
                "</div>");

                jQuery.get( rest_route + encoded_custom_domain + '/' + tld, function( rest_result ) {
                   
                    if (rest_result) {
                       
                        jQuery("#" + tld + "btn").replaceWith("<div id='" + tld + "btn' class='mgb-btn-domain-available'><a id='" + tld + "' href='#'>order now</a></div>"); 
                    
                    } else {

                        jQuery("#" + tld + "btn").replaceWith("<div id='" + tld + "btn' class='mgb-btn-domain-occupied'><a href='#'>already claimed</a></div>"); 
                 
                    }

                });

            }); 

            jQuery('#custom_domain_results').on('click', '.mgb-domainname-row .mgb-btn-domain-available a', (function(event) {
            
                event.preventDefault();
                selected_domain = custom_domain + "." + jQuery(this).attr('id')
                mgb_verify_selection();
            }));

           

        }

        function mgb_verify_selection(){

            jQuery('#modalwunschdomain').html(selected_domain);
           
            jQuery("#mgb_custom_domain_verify_comply").html(backup_verfication_string1);
            let custom_domain_verify = jQuery("#mgb_custom_domain_verify_comply").html();
            custom_domain_verify = custom_domain_verify.replace('{{custom_domain}}', selected_domain);
            jQuery("#mgb_custom_domain_verify_comply").html(custom_domain_verify);

            jQuery("#confirm_domain_dialog_p").html(backup_verfication_string2);
            custom_domain_verify = jQuery("#confirm_domain_dialog_p").html();
            custom_domain_verify = custom_domain_verify.replace('{{custom_domain}}', selected_domain);
            jQuery("#confirm_domain_dialog_p").html(custom_domain_verify);

            jQuery('#select_domain_dialog').hide();
            jQuery('#verify_domain_dialog').show();

        }

    
    });
}); 
});


function mgb_update_website_tile(){
                        
    jQuery(".mgb_userwebsite_tile").find(`[data-proto-id='${proto_id}']`).parent().html('<div class="mgb_userwebsite_info"><span class="mgb-domain-pending-title">The chosen domain has been ordered </span><i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-info-circle" title="available soon"></i><!--<br><span><b>Temporary Domain:</b></span>--></br></div>');

}
