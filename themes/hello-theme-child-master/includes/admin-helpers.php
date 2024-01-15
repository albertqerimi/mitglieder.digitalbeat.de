<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * collection of admin helper functions 
 *
 * 
 */


// include Vimeo API
require get_stylesheet_directory() . '/../../plugins/mgb-product-management/inc/vimeo.php-master/autoload.php';
use Vimeo\Vimeo;


// enable SVG Support 

function mgb_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'mgb_mime_types');



// get and save vimeo video thumb URL 

function update_vimeo_data( $post_id ) {

    //komplett_produkt_daten_kp_module_0_modul_lektionen_0_unterlektionen_0_unterlektion_video
    $mainrepeater = 'komplett_produkt_daten_kp_module';
    $subfield_thumb = '_unterlektion_video_thumb'; 
    $subfield_duration = '_unterlektion_lange';

    // get the number of rows in kp_module repeater
    $count_modules = intval(get_post_meta($post_id, $mainrepeater, true));

    for ($i=0; $i<$count_modules; $i++):
    
        $count_lektionen = intval(get_post_meta($post_id, $mainrepeater.'_'.$i.'_modul_lektionen', true));
    
        for ($e=0; $e<$count_lektionen; $e++):

            $count_sublektionen = intval(get_post_meta($post_id, $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen', true));
    
             for ($o=0; $o<$count_sublektionen; $o++):
  
                // finally we got our video url fields
                $get_field = $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen_'.$o.'_unterlektion_video';
                $video_url = get_post_meta($post_id, $get_field, true);
                
                $video_id = (int) filter_var($video_url, FILTER_SANITIZE_NUMBER_INT);  
                
                if ($video_id):
                    
                    $video_data = get_vimeo_data($video_id);

                    $video_thumb_url = $video_data[0];
                    $video_duration = (int) $video_data[1];
                    
                    $minutes = intval(floor($video_duration / 60));
                    $seconds = $video_duration % 60;
                    $video_duration = sprintf("%02d", $minutes).':'.sprintf("%02d", $seconds);

                    //error_log("VIMEO RESPONSE: ".$video_duration." ".$video_thumb_url);
                    
                    $thumb_field = $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen_'.$o.$subfield_thumb;
                    $duration_field = $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen_'.$o.$subfield_duration;
                    
                    update_post_meta($post_id, $thumb_field, $video_thumb_url);
                    update_post_meta($post_id, $duration_field, $video_duration);

                endif;

             endfor;    

        endfor;    
    
    endfor;

    // now the same game for kickstart_coaching
    $mainrepeater = 'kickstart_coaching_daten_kp_module';
    $subfield_thumb = '_unterlektion_video_thumb'; 
    $subfield_duration = '_unterlektion_lange';

    // get the number of rows in kp_module repeater
    $count_modules = intval(get_post_meta($post_id, $mainrepeater, true));

    for ($i=0; $i<$count_modules; $i++):
    
        $count_lektionen = intval(get_post_meta($post_id, $mainrepeater.'_'.$i.'_modul_lektionen', true));
    
        for ($e=0; $e<$count_lektionen; $e++):

            $count_sublektionen = intval(get_post_meta($post_id, $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen', true));
    
             for ($o=0; $o<$count_sublektionen; $o++):
  
                // finally we got our video url fields
                $get_field = $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen_'.$o.'_unterlektion_video';
                $video_url = get_post_meta($post_id, $get_field, true);
                
                $video_id = (int) filter_var($video_url, FILTER_SANITIZE_NUMBER_INT);  
                
                if ($video_id):
                    
                    $video_data = get_vimeo_data($video_id);

                    $video_thumb_url = $video_data[0];
                    $video_duration = (int) $video_data[1];
                    
                    $minutes = intval(floor($video_duration / 60));
                    $seconds = $video_duration % 60;
                    $video_duration = sprintf("%02d", $minutes).':'.sprintf("%02d", $seconds);

                    //error_log("VIMEO RESPONSE: ".$video_duration." ".$video_thumb_url);
                    
                    $thumb_field = $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen_'.$o.$subfield_thumb;
                    $duration_field = $mainrepeater.'_'.$i.'_modul_lektionen_'.$e.'_unterlektionen_'.$o.$subfield_duration;
                    
                    update_post_meta($post_id, $thumb_field, $video_thumb_url);
                    update_post_meta($post_id, $duration_field, $video_duration);

                endif;

             endfor;    

        endfor;    
    
    endfor;



    // now the same game for book boni 

    $mainrepeater = 'buchdaten_buch_bonis';
    $subfield_thumb = '_video_thumbnail'; 
    $subfield_duration = '_video_lange';

    // get the number of rows in kp_module repeater
    $count_boni = intval(get_post_meta($post_id, $mainrepeater, true));
    
    for ($i=0; $i<$count_boni; $i++):
    
        $video_url = get_post_meta($post_id, $mainrepeater.'_'.$i.'_video_video_url', true);
       
        $video_id = (int) filter_var($video_url, FILTER_SANITIZE_NUMBER_INT);  
        
        if ($video_id):
            
            $video_data = get_vimeo_data($video_id);

            $video_thumb_url = $video_data[0];
            $video_duration = (int) $video_data[1];
            
            $minutes = intval(floor($video_duration / 60));
            $seconds = $video_duration % 60;
            $video_duration = sprintf("%02d", $minutes).':'.sprintf("%02d", $seconds);

            $thumb_field = $mainrepeater.'_'.$i.'_video'.$subfield_thumb;
            $duration_field = $mainrepeater.'_'.$i.'_video'.$subfield_duration;
            
            update_post_meta($post_id, $thumb_field, $video_thumb_url);
            update_post_meta($post_id, $duration_field, $video_duration);

        endif;
    
    endfor;

    // now the same game for live-coaching mediathek

    $mainrepeater = 'live_coaching_daten_mediathek_video';
    $subfield_thumb = '_video_thumbnail'; 
    $subfield_duration = '_video_lange';

    // get the number of rows in kp_module repeater
    $count_media = intval(get_post_meta($post_id, $mainrepeater, true));
    
    for ($i=0; $i<$count_media; $i++):
    
        $video_url = get_post_meta($post_id, $mainrepeater.'_'.$i.'_video_url', true);
        error_log("url: ".$video_url);
        $video_id = (int) filter_var($video_url, FILTER_SANITIZE_NUMBER_INT);  
        
        if ($video_id):
            
            $video_data = get_vimeo_data($video_id);

            $video_thumb_url = $video_data[0];
            $video_duration = (int) $video_data[1];
            
            $minutes = intval(floor($video_duration / 60));
            $seconds = $video_duration % 60;
            $video_duration = sprintf("%02d", $minutes).':'.sprintf("%02d", $seconds);

            $thumb_field = $mainrepeater.'_'.$i.$subfield_thumb;
            $duration_field = $mainrepeater.'_'.$i.$subfield_duration;
            
            update_post_meta($post_id, $thumb_field, $video_thumb_url);
            update_post_meta($post_id, $duration_field, $video_duration);

        endif;
    
    endfor;

}
add_action('acf/save_post', 'update_vimeo_data', 20);

function get_vimeo_data($video_id = 0){

   $client = new Vimeo(VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET, VIMEO_CLIENT_ACCESS_TOKEN);

   $response = $client->request('/videos/'. $video_id, array(), 'GET');
  
   if (isset($response['body']['pictures']['sizes'][3]['link']) ? $thumb_url = $response['body']['pictures']['sizes'][3]['link'] : $thumb_url = "");
  
   if (isset($response['body']['duration']) ? $duration = $response['body']['duration'] : $duration = "");
 
   return array($thumb_url, $duration);

}

add_action('acf/render_field_settings/type=text', 'add_readonly_and_disabled_to_text_field');
function add_readonly_and_disabled_to_text_field( $field ) {
	acf_render_field_setting( $field, array(
		'label'      => __('Read Only?','acf'),
		'instructions'  => '',
		'type'      => 'true_false',
		'name'      => 'readonly',
	));
	/*acf_render_field_setting( $field, array(
		'label'      => __('Disabled?','acf'),
		'instructions'  => '',
		'type'      => 'true_false',
		'name'      => 'disabled',
	));*/
}

add_filter('acf/prepare_field', 'filter_acf_prepare_field');
function filter_acf_prepare_field( $field ) {
	
	if( !empty($field['readonly']) ){
		$field['readonly'] = 1;
	}
	/*if( !empty($field['disabled']) ){
		$field['disabled'] = 1;
	}*/
	return $field;
}