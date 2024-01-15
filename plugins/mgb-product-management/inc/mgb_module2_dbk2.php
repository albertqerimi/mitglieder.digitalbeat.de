<?php

/**
 * this is the outsourced function render_website_tiles_dbk2 from kp-template-function.php
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
print_r("dd".$websites);
echo "</pre>";
*/

// not stored in DB. Assigned to prototype_ids
$downloads_links = [

20 => "https://drive.google.com/drive/folders/1L9bFH55xqtgqrI1AngiKsaiHghqLtOIi?usp=share_link",
8 => "https://drive.google.com/drive/folders/1L4H5XmcJlfBGtED_EyvIClBRBWtrutgf?usp=share_link",
11 => "https://drive.google.com/drive/folders/1VPtdH4K5bz9iq6QUR9a3VDXw670-_oXu?usp=share_link",
12 => "https://drive.google.com/drive/folders/1O8EzxWgNJwR2cGuDP0Z2RoNipL8U8Pc0?usp=share_link",
5 => "https://drive.google.com/drive/folders/1ulH--skAApmDgXlSk2xorvBYWoDgJK7W?usp=share_link",
13 => "https://drive.google.com/drive/folders/1Y2AooJZLwov7RWI3EIW7_TLlFFU-FbSl?usp=share_link",
14 => "https://drive.google.com/drive/folders/12bLQD08HIF8MREWc7BaTZp7qEWca8aZk?usp=share_link",
7 => "https://drive.google.com/drive/folders/1Ijs9rw6gnUicgvxmAi0B9i2HRcnxADJ1?usp=share_link"

];

ob_start();?>

<script> var uid =<?php echo(get_current_user_id());?></script>
<div class="mgb_userwebsite_container"><?php


/*
If the DBK1 user has chosen to switch to DBK2 he get's a notification box with the option to migrate his domains
*/

// START check if migration form was used by user
if (( $show_mode == 'quick' ) && ($_POST['userinput'] == 'dbk1migration')){

    $migrated_domains = ""; 

    foreach($_POST['website'] as $website) :
    
        if (strpos($website, 'XXXXX')){

            $exploded = explode('XXXXX',$website);

            $dbk1_domain = $exploded[0];
            $dbk2_protoype_id = $exploded[1];

            // assign new DBK2 Website to user with dbk1 domain 
            $migrationresult = $kp->MigrateDomain($user_id, $dbk1_domain, $dbk2_protoype_id);

            // collect all domains
            $migrated_domains.= $dbk1_domain. ",";
                
            //echo  $dbk1_domain . "->" .  $dbk2_protoype_id . " Temp Domain: " .  $migrationresult. "<br/>";

        }

    endforeach; 

    echo '
    <div style="padding:20px; margin:8px; background-color:#a2ff9c; width:100%">
        <h2>Gute Wahl. Deine Domains werden umgezogen und sind innerhalb der nächsten 24h wieder erreichbar.</h2>
    </div>
    ';

    // Send Mail to notify Tech-Support to change the DNS Settings of the migrated domains
    
    $email_subject = "Neue Domainmigration DBK1 zu DBK2 (89.238.65.185)"; 
    $email_sender = "buchwald@digitalbeat.de";
    $email_body = $migrated_domains;

    $header = 'From: '.$email_sender. "\r\n" . 'Reply-To: '.$email_sender."\r\n";
    
    mail('tech@digitalbeat.de', '=?utf-8?B?'.base64_encode($email_subject).'?=', $email_body, $header);

    
    // the user can only migrate once, so we set a flag called dbk1dbk2-migrated. 
    update_user_meta( get_current_user_id(), 'dbk1dbk2-migrated', 1 );
    

}
// END check if form sent


// check if user has no active dbk1 anymore and don't have already migrated 
    if (( $show_mode == 'available' ) && (!mgb_has_product($user_id, 74)) && (!get_user_meta($user_id, 'dbk1dbk2-migrated'))) {
    
    // get all custom domains of user of dbk1
    $dbk1 = new MgbKomplettProdukt('dbk');
    $dbk1_websites = $dbk1->ListWebsites($user_id);     

    $form_body = '';

    foreach($dbk1_websites as $dbk1_website) :

        if ( 'assigned' == $dbk1_website->custom_domain_order_status){
            
            $dbk2_websites_dropdown = '<option value="0">' . $dbk1_website->custom_domain . ' nicht übernehmen</option>';
            
            foreach($websites as $website) : 
        
                // make sure, that in case of upsell website the user has the proper rights and make sure the website is already activated (id!=999)
                if ((($website->dm_id!=108) && (!mgb_has_product($user_id, $website->dm_id)) && (!mgb_has_product($user_id, 112))) || $website->dm_id==999)
                {
                    continue;
                }
                
                $dbk2_websites_dropdown .='<option value="' . $dbk1_website->custom_domain . "XXXXX" . $website->prototype_id . '">' . $website->titel . '</option>';    
        
            endforeach; 
                      
            $form_body.= "<h3>" . $dbk1_website->custom_domain . "</h3>";
            $form_body.= "<select name='website[]'>". $dbk2_websites_dropdown."</select><hr style='margin-top:10px; margin-bottom:30px;'>";
        }

    endforeach; 
    ?>
    <div style="padding:20px; margin:8px; background-color:#fff6bd; width:100%">
    <h2>Domainwechsel von DBK zu DBK 2.0</h2>
    <p style="padding-bottom:30px">Nachfolgend siehst du alle deine Domains und kannst entscheiden welcher DBK 2.0 Website sie zugeordnet werden sollen. 
    <br/>Falls du "Domain nicht übernehmen" auswählst, kannst du dir eine neue Domain aussuchen. 
    </p>
    <form action="<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";?>" method="post">
        <input type="hidden" name="userinput" value="dbk1migration">  
        <?php echo $form_body;?>
        <p style="margin-top:20px;">Wichtig: Du kannst diesen Prozess nur einmal durchführen. Bist du dir wirklich sicher, dass du die Domains so wie oben von dir angegeben weiterverwenden möchtest?</p>  
        <input type="submit" value='Domain(s) jetzt zuweisen'">
    </form>
    </div>
    <?php
}


/*

END DBK1 Migration 

*/

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
                                <span class="mgb_userwebsite_button_inner">zum Postfach</span>
                            </span>
                        </a>
                    </div>
                    <div class="mgb_userwebsite_email_wrapper">
                        <span class="mgb_userwebsite_email">kontakt@<?php echo $website->custom_domain?></span>
                    </div>
                    <div class="mgb_userwebsite_button_wrapper">
                        <a href="<?php echo $downloads_links[$website->prototype_id]."ds"; ?>" target="_blank" class="<?php echo $website->prototype_id; ?> testal mgb_userwebsite_button mgb_userwebsite_button_bright mgb-btn mgb-btn-secondary mgb-btn-download" role="button">
                            <span class="mgb_userwebsite_button_span_wrapper">
                            <span class="mgb_userwebsite_button_icon">
                                <i aria-hidden="true" class="mgbicon- mgb-icon-download"></i></span>
                            <span class="mgb_userwebsite_button_inner">Produkt-Materialien</span>
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
                <!--<li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Partnerprogramme:</b> <?php echo $website->partner ?></span>
                    </li>-->
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Hauptprodukt: </b> <?php echo $website->partner ?></span>
                    </li>
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Einstiegsprodukte:</b> <?php echo $website->produkte ?></span>
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
        
        // check if user is allowed to see website or has the upsell product 112
        if ((($website->dm_id!=108) && (!mgb_has_product($user_id, $website->dm_id)) && (!mgb_has_product($user_id, 112))) || $website->dm_id==999)
        {
            continue;
        }

        ?> 
        <div class="mgb_userwebsite_tile">
            <div class="mgb_userwebsite_img"><img loading="lazy" src=" <?php echo $website->img_url ?> "/></div>
    
            
                <div class="mgb_userwebsite_body">
                <h2><?php echo $website->titel ?></h2>	
                
                <div class="mgb_userwebsite_button_wrapper">
                        <div class="dbk2_btn" data-website='<?php echo $website->titel?>' data-proto-id='<?php echo $website->prototype_id?>'>
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
                    <!--<li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Partnerprogramme:</b> <?php echo $website->partner ?></span>
                    </li>-->
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Hauptprodukt: </b> <?php echo $website->partner ?></span>
                    </li>
                    <li>
                        <span><i aria-hidden="true" class="mgbicon- mgb-icon-chev-right-large"></i></span>
                        <span class="mgb_userwebsite_li_desc"><b>Einstiegsprodukte:</b> <?php echo $website->produkte ?></span>
                    </li>
                   
            </div>        
        </div>
        <?php 

    endif;

endforeach;

?></div><?php

return ob_get_clean();