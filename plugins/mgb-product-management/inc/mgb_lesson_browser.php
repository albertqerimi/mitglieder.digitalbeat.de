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

if (!class_exists(MgbLessonBrowser::class)) {

    $mc; // whole module content

    class MgbLessonBrowser{

        function __construct($module_content, $live_coaching = false){

            $this->mc = $module_content;
            $this->live_coaching = $live_coaching;
            $this->prepare_browserdata();

        }

        function prepare_browserdata(){

            $browserdata = array();
           
            if (!$this->live_coaching){
           
            foreach ($this->mc['modul_lektionen'] as $lesson_key => $lesson ):

                foreach ($lesson['unterlektionen'] as $sublesson_key => $sublesson):

                    $browserdata[] = array(
                        'lesson'=>$lesson_key + 1, 
                        'sublesson' =>$sublesson_key + 1,
                        //'sublesson_title' => preg_replace("/\r|\n/", "", $sublesson['unterlektion_title']),
                        //'sublesson_desc' => preg_replace("/\r|\n/", "", $sublesson['unterlektion_beschreibung']),
                        'sublesson_title' => htmlspecialchars(preg_replace("/\r|\n/", "", $sublesson['unterlektion_title'])),
                        'sublesson_desc' => htmlspecialchars(preg_replace("/\r|\n/", "", $sublesson['unterlektion_beschreibung'])),
                        'sublesson_download_url' => $sublesson['download_material_link'],
                        'sublesson_video_url' =>$sublesson['unterlektion_video'],
                        'sublesson_video_thumb_url' =>$sublesson['unterlektion_video_thumb'],
                        'sublesson_tags' =>$sublesson['tags'],
                        'sublesson_video_lange' =>$sublesson['unterlektion_lange'],
                    );
                        // 'sublesson_title' => htmlspecialchars(htmlentities(preg_replace("/\r|\n/", "", $lesson['titel'] . " (" . $lesson['aufzeichnungsdatum'] . ")"), ENT_QUOTES)),
                       // 'sublesson_desc' => htmlspecialchars(htmlentities(preg_replace("/\r|\n/", "", mb_convert_encoding($lesson['beschreibung'], "UTF-8")), ENT_QUOTES)),
                       
                endforeach; 

            endforeach;

            // live-coaching = true  -> has a different data structure         
            } else {
                
                $lesson_no = 0;

                foreach ($this->mc as $lesson):
               
                    $lesson_no++;
                    $browserdata[] = array(
                        'lesson'=>'Mediathek',
                        'sublesson' =>$lesson_no,
                        'sublesson_title' => preg_replace("/\r|\n/", "", $lesson['titel']) . " (" . $lesson['aufzeichnungsdatum'] . ")",
                        'sublesson_desc' => preg_replace("/\r|\n/", "", $lesson['beschreibung']),
                        'sublesson_download_url' => "",
                        'sublesson_aufzeichnungsdatum' => $lesson['aufzeichnungsdatum'],
                        'sublesson_video_url' =>$lesson['video_url'],
                        'sublesson_video_thumb_url' =>$lesson['video_thumbnail'],
                        'sublesson_video_lange' =>$lesson['video_lange'],
                        'sublesson_tags' =>$lesson['tags'],
                    );
                    
                endforeach; 

            }
        
            // this is a better/faster/ more secure way of loading the data into a JQuery-object than using an AJAX-Call 
            ?>
            
            <script>
            jQuery(document).ready(function ($) {              
               
                lb_data = jQuery.parseJSON('<?php echo json_encode($browserdata, JSON_HEX_APOS|JSON_HEX_QUOT);?>');
             
            });
            </script>
            <?php
        
            wp_enqueue_script(
                'lesson-browser-script',
                plugin_dir_url( __DIR__ ) . '/assets/lesson_browser.js',
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