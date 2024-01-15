<?php

/**
 * this is the outsourced function render_website_dsk from kp-template-function.php
 *  
 */

$shop_imgs = [
   'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-lieblingstier_mitglieder.jpg',
   'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-heimundhecke_mitglieder.jpg',
   'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-familie_mitglieder.jpg',
   'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-technikshop_mitglieder.jpg',
   'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-beauty_mitglieder.jpg'
];

$dsk_upsell_id = 90;

// check which Product is active
$kp_id = get_field('komplett_produkt_daten', $post->ID)['kp_id'];  
$kp = new MgbKomplettProdukt($kp_id);

$user_id = wp_get_current_user()->ID;
// $user_id = 45090; // michael
//$user_id = 3; 
$user_obj = get_user_by('id', $user_id); 

if (mgb_has_product($user_id, $dsk_upsell_id) ? $has_upsell = 1 : $has_upsell = 0);

$websites = $kp->ListWebsites($user_id);

// first user website  
$website = $websites[0];

/*
echo ('<pre>');
print_r($user_obj->user_pass);
echo ('</pre>');
*/
ob_start();

// STATE 1  user has no website selected yet show button and enable popup #########################################

if (!$website->id){

   render_website_notset($website, $shop_imgs, $user_id);
   return ob_get_clean();

}   

// STATE 2 user has a assigned and functional website ###############################################################

if ($website->custom_domain_order_status=='assigned'){ 
   
   render_website_assigned($website, $shop_imgs, $user_obj);
   
   // CHECK FOR UPSELL 

   if ($has_upsell){

      if ($websites[1]->id ? $upsell_website = $websites[1] : $upsell_website = 0);

      if (!$upsell_website){
         ?>
         <h3 class="mgb-dsk-bonusshop-head">Wähle jetzt deinen Bonusshop</h3>
         <?php
         render_website_notset($upsell_website, $shop_imgs, $user_id); 
         return ob_get_clean(); 
      } 

      if ($upsell_website->custom_domain_order_status=='assigned'){ 
         render_website_assigned($upsell_website, $shop_imgs, $user_obj);
         return ob_get_clean();
      } 

      render_website_pending($upsell_website, $shop_imgs);

   }

   return ob_get_clean();
} 

// STATE 3  user has a pending website #########################################################################

render_website_pending($website, $shop_imgs);
return ob_get_clean();


// INNER FUNCTIONS BEWARE OF SCOPE! #########################################################################

function render_website_assigned($website, $shop_imgs, $user_obj){

?>
   <style>
   #mgb_instruction_chart{
         display: none;
   }
   #password_copy{
      cursor:pointer;
   }
   </style>
   <script>
      jQuery(document).on('click', '#password_copy', function(event) {
         event.preventDefault();
         pw_temp = '<?php echo $website->dm_pw?>';
         dummy = jQuery('<input>').val(pw_temp).appendTo('body').select()
         document.execCommand("copy");
      }); 
   </script>

   <div class="mgb_userwebsite_tile mgb_userwebsite_tile-single">
      <div class="mgb_userwebsite_img_big">
         <img loading="lazy" src="<?php echo $shop_imgs[$website->prototype_id-1]?>">
         <a href="https://<?php echo $website->domain.'/wp-admin?u='.urlencode($user_obj->user_login).'&h='.urlencode($user_obj->user_pass)?>" target="_blank" class="customize-unpreviewable">
            <div class="mgb_icon_wrapper">
               <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-settings-cog"></i>
            </div>
         </a>
         <a href="https://<?php echo $website->domain?>" target="_blank" class="customize-unpreviewable">
            <div class="mgb_icon_wrapper mgb_icon_wrapper_eye">
               <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-eye"></i>
            </div>
         </a>
      </div>
      <div class="mgb_userwebsite_body">
         <h2>Dein Dropshipping Shop</h2>
         <div class="mgb_userwebsite_domain"><a href="https://<?php echo $website->domain?>" target="_blank"><?php echo $website->domain?></a></div>
         <div class="mgb_userwebsite_button_wrapper">
         <a href="https://<?php echo $website->custom_domain?>/webmail/?_autologin=1&d=<?php echo urlencode($website->domain)?>&u=<?php echo urlencode('kontakt@'.$website->domain)?>&p=<?php echo urlencode($website->mail_pw)?>" target="_blank" class="mgb_userwebsite_button customize-unpreviewable" role="button">
            <span class="mgb_userwebsite_button_span_wrapper">
            <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-mail"></i></span>
            <span class="mgb_userwebsite_button_inner">zum Postfach</span>
            </span>
            </a>
            <a href="https://www.dropshipping-marktplatz.de/" target="_blank" class="mgb_userwebsite_button mgb_userwebsite_button_bright" role="button">
         <span class="mgb_userwebsite_button_span_wrapper"><span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-home"></i></span> <span class="mgb_userwebsite_button_inner">Zum Marktplatz</span></span></a>
         </div>
         
         <div class="mgb_userwebsite_email_wrapper"><b>E-Mail:</b>
            <span class="mgb_userwebsite_email">kontakt@<?php echo $website->domain?></span>
         </div>
         <div class="mgb_userwebsite_email_wrapper" id="password_copy">
            <b>Marktplatz Passwort:</b>
            <span class="mgb_userwebsite_password"><?php echo $website->dm_pw?></span><i aria-hidden="true" class="mgbicon- mgb-icon-copy"></i>
         </div>
      </div>
   </div>

   <?php

}

function render_website_pending($website, $shop_imgs){

   ?>

   <div class="mgb_userwebsite_tile mgb_userwebsite_tile-single">
   <div class="mgb_userwebsite_img_big">
      <img loading="lazy" src="<?php echo $shop_imgs[$website->prototype_id-1]?>">
      <!--<a href="https://<?php echo $website->domain.'/wp-admin?u='.urlencode($user_obj->user_login).'&h='.urlencode($user_obj->user_pass)?>" target="_blank">
         <div class="mgb_icon_wrapper">
            <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-settings-cog"></i>
         </div>
      </a>-->
      <a href="https://<?php echo $website->domain?>" target="_blank">
         <div class="mgb_icon_wrapper mgb_icon_wrapper_eye">
            <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-eye"></i>
         </div>
      </a>
   </div>
   <div class="mgb_userwebsite_body">
      <h2>Dein Dropshipping Shop</h2>
      <div class="mgb_userwebsite_domain"><a href=""><?php echo $website->custom_domain?></a></div>
      <div class="mgb_userwebsite_info">
         <span class="mgb-domain-pending-title">Die gewähle Domain wurde beantragt </span><i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-info-circle" title="verfügbar in Kürze"></i><<!--br><span><b>Temporäre Domain:</b> <?php echo $website->domain?></span>-->
      </div>
   </div>
   </div>
         
   <?php

}

function render_website_notset($website, $shop_imgs, $user_id){
    // include popup js-file 
    wp_enqueue_script(
      'shop-browser-script',
      plugin_dir_url( __DIR__ ) . '/assets/dsk_shop_browser.js',
      ['jquery'],
   );

   ?>
   <script> var uid =<?php echo($user_id);?></script>
   <style>
      #choose_shop_confirm{
         max-height: 0px;
         transition: max-height 0.3s ease-out;
         overflow: hidden; 
         padding: 0px;
         border: 0px;
      }
   </style>

   <div class="elementor-element elementor-element-31b009b elementor-align-center mgb-btn mgb-btn-primary elementor-widget elementor-widget-button" data-id="31b009b" data-element_type="widget" data-widget_type="button.default">
      <div class="elementor-widget-container">
         <div class="elementor-button-wrapper">
            <a href="#" class="elementor-button-link elementor-button elementor-size-sm" id="choose-shop" role="button">
               <span class="elementor-button-content-wrapper"><span class="elementor-button-text">SHOP WÄHLEN</span></span>
            </a>
         </div>
      </div>
   </div>

   <?php
}
