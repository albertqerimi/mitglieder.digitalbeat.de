<?php

/**
 * this is the outsourced function render_website_tiles_bk2 from kp-template-function.php
 *  
 */

global $post;
            
$user_id = wp_get_current_user()->ID;

$user_obj = get_user_by('id', $user_id);

// check what kind of websites we want to show available or active

$show_mode = $atts['mode'];
            
// check which Product is active
$kp_id = get_field('komplett_produkt_daten', $post->ID)['kp_id'];  
$kp = new MgbKomplettProdukt($kp_id);

$websites = $kp->ListWebsites($user_id);

/*
echo "<pre>"; 
print_r($websites);
echo "</pre>";
*/

ob_start();?>

<script> var uid =<?php echo(get_current_user_id());?></script>
<div class="mgb_userwebsite_container"><?php

foreach($websites as $website) : 

    // check if website is among the ones we want to list here
    if ((isset($website->custom_domain) && ("0"!=$website->custom_domain)) ? $websiteactive = 1 : $websiteactive = 0 );
    
    if (($show_mode=='active') && ($websiteactive)):
        
        // show pending domain websites first
        if ($website->custom_domain_order_status=='assigned'? $flex_order=2 : $flex_order=1);
        ?>     
        
        <div class="mgb_userwebsite_tile" style="order:<?php echo $flex_order; ?>">
            <div class="mgb_userwebsite_img"><img loading="lazy" src=" <?php echo $website->img_url ?> "/>
    
                <a href='https://<?php echo $website->domain.'/wp-admin?u='.urlencode($user_obj->user_login).'&h='.urlencode($user_obj->user_pass)?>' target="_blank"><div class="mgb_icon_wrapper">
                    <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-settings-cog"></i>
                </div></a>
                <a href="https://<?php echo $website->domain ?>" target="_blank"><div class="mgb_icon_wrapper mgb_icon_wrapper_eye">
                    <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-eye"></i>
                </div></a>
            
            </div>    
            <div class="mgb_userwebsite_domain"><a href=""><?php echo $website->custom_domain ?></a></div>
                    <div class="mgb_userwebsite_body">
                <h2><?php echo $website->titel ?></h2>	
                
                <?php
                // fully operational websites
                if ($website->custom_domain_order_status=='assigned'):
                ?>
                                                                                
                    <div class="mgb_userwebsite_button_wrapper">
                        <a href="https://<?php echo $website->custom_domain?>/webmail/?_autologin=1&d=<?php echo urlencode($website->domain)?>&u=<?php echo urlencode('kontakt@'.$website->domain)?>&p=<?php echo urlencode($website->mail_pw)?>" target="_blank" class="mgb_userwebsite_button" role="button">
                            <span class="mgb_userwebsite_button_span_wrapper">
                                <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-mail"></i></span>
                                <span class="mgb_userwebsite_button_inner">Mailbox</span>
                            </span>
                        </a>
                    </div>
                    <div class="mgb_userwebsite_email_wrapper">
                        <span class="mgb_userwebsite_email">kontakt@<?php echo $website->custom_domain?></span>
                    </div>
                                                
                <?php 
                // websites with pending domains
                else: ?>

                    <div class="mgb_userwebsite_info">
                        <span class="mgb-domain-pending-title">The chosen domain has been ordered </span><i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-info-circle" title="available soon"></i><br><span><b>Temporary domain:</b> <?php echo $website->domain?></span>
                    </div>

                <?php
                
                endif;

                ?>

                <ul>
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Partner programs:</b> <?php echo $website->partner ?></span>
                    </li>
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Products:</b><?php echo $website->produkte ?></span>
                    </li>
                </ul>
            </div>        
        </div>
        <?php 

        elseif (($show_mode=='quick') && ($websiteactive) && ($website->custom_domain_order_status=='assigned')):
        
        ?>     
        
        <div class="mgb_userwebsite_tile mgb_userwebsite_quick_tile">
            <h5><?php echo $website->titel ?></h5>	
            <span><?php echo $website->custom_domain ?></span>
            <div>
                    <a href='https://<?php echo $website->domain.'/wp-admin?u='.urlencode($user_obj->user_login).'&h='.urlencode($user_obj->user_pass)?>' target="_blank">
                    <div class="mgb_icon_wrapper">
                        <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-settings-cog"></i>
                    </div></a>
                    <a href="https://<?php echo $website->domain ?>" target="_blank">
                    <div class="mgb_icon_wrapper">
                        <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-eye"></i>
                    </div></a>
                    <a href="https://<?php echo $website->custom_domain?>/webmail/?_autologin=1&d=<?php echo urlencode($website->domain)?>&u=<?php echo urlencode('kontakt@'.$website->domain)?>&p=<?php echo urlencode($website->mail_pw)?>" target="_blank">
                    <div class="mgb_icon_wrapper">
                        <i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-mail-envelope"></i>
                    </div></a>
            </div>                         
        </div>

        <?php 
    
    elseif (($show_mode=='available') && (!$websiteactive)):   
        
        // check if user is allowed to see website or has the upsell product 2 or the uk only upsell product 4
        if ((($website->dm_id!=1) && (!mgb_has_product($user_id, $website->dm_id)) && (!mgb_has_product($user_id, 2)) && (!mgb_has_product($user_id, 4))) || $website->dm_id==999)
        {
            continue;
        }

        // we want to hide the other language version in case the user has already one version of a website assigned: 
        $pairs = [
            2 => 56, 56 => 2,
            3 => 57, 57 => 3,
            4 => 58, 58 => 4,
            5 => 59, 59 => 5,
            6 => 60, 60 => 6,
            7 => 61, 61 => 7,
            8 => 62, 62 => 8,
            9 => 63, 63 => 9,
        ];
    
        $sibling_website = $pairs[$website->protoype_id];
        if ($this->hasCustomDomain($websites, $sibling_website))
        {
            continue;
        } 
       
        // for customers of the uk only product we only want to show the UK prototypes and hide the NL ones
        $uk_prototypes = [2,3,4,5,6,7,8,9];
        
        if ((!in_array($website->protoype_id, $uk_prototypes)) && (!mgb_has_product($user_id, $website->dm_id)))
        {
            continue;
        }

        ?> 
        <div class="mgb_userwebsite_tile">
            <div class="mgb_userwebsite_img"><img loading="lazy" src=" <?php echo $website->img_url ?> "/></div>
    
            
                <div class="mgb_userwebsite_body">
                <h2><?php echo $website->titel ?></h2>	
                <div class="mgb_userwebsite_button_wrapper">
                        <div class="domain_popup_btn" data-website='<?php echo $website->titel?>' data-proto-id='<?php echo $website->protoype_id?>'>
                        <a href="#" class="mgb_userwebsite_button mgb_userwebsite_button_dark" role="button">
                            <span class="mgb_userwebsite_button_span_wrapper">
                                <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-arrow-fwd"></i></span>
                                <span class="mgb_userwebsite_button_inner">Activate Website</span>
                            </span>
                        </a>    
                        </div>
                        <a href="https://<?php echo $website->demo_domain_name ?>" target="_blank" class="mgb_userwebsite_button mgb_userwebsite_button_bright" role="button">
                            <span class="mgb_userwebsite_button_span_wrapper">
                                <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-eye"></i></span>
                                <span class="mgb_userwebsite_button_inner">view demo</span>
                            </span>
                        </a>
                </div>

                <ul>
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Partner programs:</b> <?php echo $website->partner ?></span>
                    </li>
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Products:</b><?php echo $website->produkte ?></span>
                    </li>
                </ul>
            </div>        
        </div>
        <?php 

    endif;

endforeach;

?></div><?php

return ob_get_clean();