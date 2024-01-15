<?php

/**
 * this is the outsourced function render_website_tiles_bk2 from kp-template-function.php
 *
 */

global $post;

$user_id = wp_get_current_user()->ID;
//$user_id = 27982;
//$user_id = 45090; //michael
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

// not stored in DB. Assigned to prototype_ids
$downloads_links = [

    2 => "https://drive.google.com/drive/folders/1O4j3NTvLpOoLxS4_hB2yD72DuInla6MI?usp=sharing",
    3 => "https://drive.google.com/drive/folders/1JR5xjvkMkJVg6_B5eFcUNHjGC2RyUzJB?usp=sharing",
    4 => "https://drive.google.com/drive/u/2/folders/1Wf8nVC09vifotnzz1qssyZ3xrdn_qmxb?usp=sharing",
    6 => "https://drive.google.com/drive/folders/1bkmY-QNo6Q0AOBahTGG2lJiHvmREdKqt?usp=sharing",
    5 => "https://drive.google.com/drive/folders/1LAQMAQzhOkCbSvQzOPkO8gHrNredDUqF?usp=sharing"

];


ob_start();?>

<script> var uid =<?php echo(get_current_user_id());?></script>
<div class="mgb_userwebsite_container"><?php

foreach($websites as $website) :

    // check if website is among the ones we want to list here
    if ((isset($website->custom_domain) && ($website->custom_domain!=0)) ? $websiteactive = 1 : $websiteactive = 0 );

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
                                <span class="mgb_userwebsite_button_inner">zum Postfach</span>
                            </span>
                        </a>
                    </div>
                    <div class="mgb_userwebsite_email_wrapper">
                        <span class="mgb_userwebsite_email">kontakt@<?php echo $website->custom_domain?></span>
                    </div>
                    <div class="mgb_userwebsite_button_wrapper">
                        <a href="<?php echo $downloads_links[$website->prototype_id]."ds";?>" target="_blank" class="<?php echo $website->prototype_id;?> testal mgb_userwebsite_button mgb_userwebsite_button_bright mgb-btn mgb-btn-secondary mgb-btn-download" role="button">
                            <span class="mgb_userwebsite_button_span_wrapper">
                            <span class="mgb_userwebsite_button_icon">
                                <i aria-hidden="true" class="mgbicon- mgb-icon-download"></i></span>
                            <span class="mgb_userwebsite_button_inner">Druckdateien</span>
                            </span>
                        </a>
                    </div>

                <?php
                // websites with pending domains
                else: ?>

                    <div class="mgb_userwebsite_info">
                        <span class="mgb-domain-pending-title">Die gewähle Domain wurde beantragt </span><i aria-hidden="true" class="mgbicon-solid- mgb-icon-solid-info-circle" title="verfügbar in Kürze"></i><br><span><b>Temporäre Domain:</b> <?php echo $website->domain?></span>
                    </div>

                <?php

                endif;

                ?>

                <ul>
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Produkte: </b><?php echo $website->produkte ?></span>
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

        // check if user is allowed to see website or has the upsell product 69
        if (($website->package=='upsell') && (!mgb_has_product($user_id, 69)))
        {
            continue;
        }

        ?>
        <div class="mgb_userwebsite_tile">
            <div class="mgb_userwebsite_img"><img loading="lazy" src=" <?php echo $website->img_url ?> "/></div>


                <div class="mgb_userwebsite_body">
                <h2><?php echo $website->titel ?></h2>

                <div class="mgb_userwebsite_button_wrapper">
                        <div class="domain_popup_btn pod_btn" data-website='<?php echo $website->titel?>' data-proto-id='<?php echo $website->prototype_id?>'>
                        <a href="#" class="mgb_userwebsite_button mgb_userwebsite_button_dark" role="button">
                            <span class="mgb_userwebsite_button_span_wrapper">
                                <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-arrow-fwd"></i></span>
                                <span class="mgb_userwebsite_button_inner">Website Aktivieren</span>
                            </span>
                        </a>
                        </div>
                        <a href="https://<?php echo $website->demo_domain_name ?>" target="_blank" class="mgb_userwebsite_button mgb_userwebsite_button_bright" role="button">
                            <span class="mgb_userwebsite_button_span_wrapper">
                                <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-eye"></i></span>
                                <span class="mgb_userwebsite_button_inner">Demo Ansehen</span>
                            </span>
                        </a>
                </div>

                <ul>

                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Produkte: </b><?php echo $website->produkte ?></span>
                    </li>
                </ul>
            </div>
        </div>
        <?php

    endif;

endforeach;

?></div><?php

return ob_get_clean();
