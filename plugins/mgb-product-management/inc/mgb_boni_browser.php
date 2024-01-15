<?php

/**
 * 
 * Lesson Browser displays alle Videos of a a module
 * 
 * 
 * 
 * 
 */

defined('ABSPATH') or die();

if (!class_exists(MgbBoniBrowser::class)) {

    $mc; // whole module content

    class MgbBoniBrowser{

        function __construct($boni_videos){

            $this->bv = $boni_videos;
            $this->prepare_browserdata();

        }

        function prepare_browserdata(){

            $browserdata = array();
            
            foreach ($this->bv as $lesson_key => $lesson):

                $browserdata[] = array(
                    'sublesson'=>$lesson_key + 1, 
                    'sublesson_title' =>$lesson['title'],
                    'sublesson_desc' =>$lesson['beschreibung'],
                    'sublesson_video_url' =>$lesson['video_url'],
                    'sublesson_video_thumb_url' =>$lesson['video_thumbnail']
                );
                    
            endforeach; 
        
            // this is a better/faster/ more secure way of loading the data into a JQuery-object than using an AJAX-Call 
            ?>
            <script>
            jQuery(document).ready(function ($) {              
                lb_data = jQuery.parseJSON('<?php echo json_encode($browserdata)?>');
            });
            </script>
            <?php
        
            wp_enqueue_script(
                'lesson-browser-script',
                plugin_dir_url( __DIR__ ) . '/assets/boni_browser.js',
                ['jquery'],
            );

            wp_enqueue_script(
                'vimeo-script',
                plugin_dir_url( __DIR__ ) . '/assets/3rdparty/player.js',
                ['jquery'],
            );
        
        }

    }

}