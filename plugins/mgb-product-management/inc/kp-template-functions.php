<?php

defined('ABSPATH') or die();

if (!class_exists(Kp_Template_Functions::class)) {
    class Kp_Template_Functions
    {
        
        public $module_id;
        
        public function __construct()
        {
            
            add_action( 'parse_query', function() {
              
                $this->module_id = get_query_var('modul');
            
                if (intval($this->module_id) - 1 <=0) $this->module_id = 1;

            } );

           
            add_shortcode('product_title', [$this, 'get_product_title']);
            add_shortcode('product_subtitle', [$this, 'get_product_subtitle']);
            add_shortcode('product_desc', [$this, 'get_product_desc']);
            add_shortcode('product_img', [$this, 'get_product_img']);
            add_shortcode('product_permalink', [$this, 'get_product_permalink']);
            add_shortcode('product_live_coaching_url', [$this, 'get_live_coaching_url']);
            add_shortcode('product_video_url', [$this, 'get_product_video_url']);
            add_shortcode('product_module_tiles', [$this, 'render_module_tiles']);
            add_shortcode('product_helpdesk_url', [$this, 'get_helpdesk_url']);

            add_shortcode('user_sync_data', [$this, 'do_user_sync_data']);

            add_shortcode('module_title', [$this, 'get_module_title']);
            add_shortcode('module_desc', [$this, 'get_module_desc']);
            add_shortcode('module_nav_tiles', [$this, 'render_module_nav_tiles']);
            add_shortcode('module_helpdesk_url', [$this, 'get_module_helpdesk_url']);
            add_shortcode('module_back_forth', [$this, 'render_module_back_forth']);
            add_shortcode('module_icon', [$this, 'get_module_icon']);
            add_shortcode('module_lessons', [$this, 'render_module_lessons']);

            add_shortcode('website_tiles_bk2', [$this, 'render_website_tiles_bk2']);
            add_shortcode('website_tiles_dbk', [$this, 'render_website_tiles_dbk']);
            add_shortcode('website_tiles_dbk2', [$this, 'render_website_tiles_dbk2']);
            add_shortcode('website_tiles_pod', [$this, 'render_website_tiles_pod']);
            add_shortcode('website_dsk', [$this, 'render_website_dsk']);
            add_shortcode('website_wbk', [$this, 'render_website_wbk']);
        
            add_shortcode('webinar_tiles_wbk', [$this, 'render_webinar_tiles_wbk']);

            add_shortcode('webinar_title', [$this, 'get_webinar_title']);
            add_shortcode('webinar_speaker', [$this, 'get_webinar_speaker']);
            add_shortcode('webinar_angebot', [$this, 'get_webinar_angebot']);
            add_shortcode('webinar_inhalte', [$this, 'get_webinar_inhalte']);
            add_shortcode('webinar_upsell', [$this, 'get_webinar_upsell']);
            add_shortcode('webinar_speakerdesc', [$this, 'get_webinar_speakerdesc']);
            add_shortcode('webinar_activate_link', [$this, 'get_webinar_activate_link']);
            add_shortcode('webinar_aff_link', [$this, 'get_webinar_aff_link']);
            add_shortcode('webinar_download_link', [$this, 'get_webinar_download_link']);

            add_shortcode('live_coaching_schedule', [$this, 'render_live_coaching_schedule']);
            add_shortcode('live_coaching_desc', [$this, 'get_live_coaching_desc']);
            add_shortcode('live_coaching_subtitle', [$this, 'get_live_coaching_subtitle']);
            add_shortcode('live_coaching_modules', [$this, 'render_live_coaching_modules']);
            add_shortcode('live_coaching_module_nav_tiles', [$this, 'render_live_coaching_modules_nav_tiles']);
            add_shortcode('live_coaching_module_back_forth', [$this, 'render_live_coaching_module_back_forth']);

            add_shortcode('toolbox_lessons', [$this, 'render_toolbox_lessons']);
            add_shortcode('bonus_lessons', [$this, 'render_bonus_lessons']);
            add_shortcode('bonus_days_to_next_bonus', [$this, 'get_days_to_next_bonus']);

            add_shortcode('mediathek_lessons', [$this, 'render_mediathek_lessons']);

            add_shortcode('kickstart_modules', [$this, 'render_kickstart_modules']);
            add_shortcode('kickstart_workbook', [$this, 'render_kickstart_workbook']);

            add_shortcode('trafficmasterplan_modules', [$this, 'render_trafficmasterplan_modules']);

            add_shortcode('ki_aufzeichnungen_link', [$this, 'render_ki_aufzeichnungen_link']);
            add_shortcode('ki_potentialanalyse', [$this, 'render_ki_potentialanalyse']);


        }

        static function get_post_id(): int
        {
            if (\Elementor\Plugin::$instance->preview->is_preview_mode() || isset($_GET['elementor_library'])) {
                return 1418;
            } else {
                global $post;
                return $post->ID;
            }
        }
        
        function do_user_sync_data(){

            $cu = wp_get_current_user();

            $kp_id = get_field('komplett_produkt_daten', $post->ID)['kp_id']; 
            
            //echo "<br>" . $cu->user_login . " -> " . $cu->user_pass . "[". $kp_id ."]" ; 

            $mgbkp = new MgbKomplettProdukt($kp_id); 
            
            $sync_result = $mgbkp->UpdateUserData($cu);

            /*
            echo "<pre>"; 
            print_r($sync_result);
            echo "</pre>"; 
            */
        }


       /*
       mediathek functions
       */

        function render_mediathek_lessons()
        {

            $lessons = get_field('live_coaching_daten', $this->get_post_id())['mediathek']['video']; 
            if (isset($lessons)) $lessons = array_reverse($lessons);
            // load lesson browser class
            include_once 'mgb_lesson_browser.php'; 

            new MgbLessonBrowser($lessons, true);

            wp_enqueue_script(
                'mediathek',
                plugin_dir_url( __DIR__ ) . '/assets/mediathek.js',
                ['jquery'],
            );

            ob_start();
                    
            // we need a 2D array key to load the popup and jump to the right lesson
            $lesson_no = 0; 
            
           
            $tags = get_tags(array(
                'taxonomy' => 'post_tag',
                'orderby' => 'name',
                'hide_empty' => false // for development
              ));
            

            ?><div class="mgb-coaching-filter-wrapper">
            <a href="#" class="mgb-filter-item-active" data-fid='0'>Alle</a>    
            <?php
            foreach ($tags as $tag):
            ?>
            <a href="#" data-fid='<?php echo $tag->term_id; ?>'><?php echo $tag->name; ?></a>
            <?php
            endforeach;  
            ?></div>
            <div class="mgb_sublesson_tile_wrapper" style="max-height:8000px"> 
            <?php

                foreach ($lessons as $lesson):
                
                    $lesson_no++;
                    ?>
                    <div class="mgb_sublesson_tile">
                        <div class="mgb_userwebsite_img open_lesson_browser" data-lesson-no="<?php echo $lesson_no?>"><img loading="lazy" src="<?php echo $lesson['video_thumbnail']; ?>"></div>
                        <div class="mgb_sublesson_duration"><span class="mgb_sublesson_date"><?php echo $lesson['aufzeichnungsdatum'];?></span><strong>Dauer: </strong><?php echo $lesson['video_lange'];?></div>
                        <div class="mgb_sublesson_title open_lesson_browser" data-lesson-no="<?php echo $lesson_no?>"><h5><?php echo $lesson['titel'];?></h5></div>     
                    </div>
                <?php
                endforeach;    

            ?> </div> <?php

            return ob_get_clean();
        }



        /*
       live-coaching functions
        */

        function get_days_to_next_bonus()
        {

            $lessons = get_field('live_coaching_daten', $this->get_post_id())['boni']['bonus']; 
            
            $user_id = wp_get_current_user()->ID;
            $user_orderdate = new DateTime(mgb_get_orderdate($user_id, DM_IC_PLATIN));
            $today = new DateTime("now");
            $interval = $user_orderdate->diff($today);

            $days_since_order = $interval->days;

            foreach ($lessons as $lesson):
            
                if ($days_since_order<$lesson['freischaltung']){
                    ?>

                        <div class="mgb-coaching-countdown-wrapper">
                            <span class="mgb-coaching-countdown-title">Tage bis zur nächsten Freischaltung:</span>
                            <span class="mgb-coaching-countdown-days"><?php echo $lesson['freischaltung'] - $days_since_order -1?></span>
                        </div>

                    <?php

                    break;
                } 

            endforeach;

        }


        function render_bonus_lessons(){

            $lessons = get_field('live_coaching_daten', $this->get_post_id())['boni']['bonus']; 
            
            $user_id = wp_get_current_user()->ID;
            $user_orderdate = new DateTime(mgb_get_orderdate($user_id, DM_IC_PLATIN));
            $today = new DateTime("now");
            $interval = $user_orderdate->diff($today);
            
            $days_since_order = $interval->days;
            
            ob_start();
           
            $l=1;
            
            // we only want to show the next available bonus lesson not all of them
            $preview_lesson = 0;
            $activation_date = ""; 
            $add_css = "";
          
            foreach ($lessons as $lesson):
            
                if ($days_since_order<$lesson['freischaltung']){
                    $preview_lesson = 1;
                    $activation_date = $user_orderdate->add(new DateInterval('P'.$lesson['freischaltung'].'D'))->format('d.m.y');
                    $add_css = "mgb-coaching-bonus-item-inactive";
                } 
                ?>
                <div class="mgb-coaching-bonus-item-wrapper <?php echo $add_css?>">
                    <div class="mgb-coaching-time-wraper">
                    <span class="mgb-coaching-count"><?php echo $l?></span>
                    <span class="mgb-coaching-day"><?php echo $activation_date;?></span>
                    </div>
                    <div class="mgb-coaching-data-wraper">
                    <div class="mgb-coaching-name-wrapper">
                        <h3 class="mgb-coaching-title"><?php echo $lesson['titel'];?></h3>
                        <h6 class="mgb-coaching-subtitle"><?php echo $lesson['untertitel'];?></h6>
                    </div>
                    </div>
                    <div class="elementor-button-wrapper">
                            <a href="<?php echo $lesson['bonus_url'];?>" class="elementor-button-link elementor-button elementor-size-sm" role="button" target="_blank">
                                    <span class="elementor-button-content-wrapper">
                                        <span class="elementor-button-icon elementor-align-icon-right">
                                <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-forward-medium"></i>			</span>
                                        <span class="elementor-button-text">zum bonus</span>
                        </span>
                                    </a>
                        </div>
                    </div> 
            
                
                    
                <?php

                if ($preview_lesson) break;

                $l++;

            endforeach;

            return ob_get_clean();

        }

        private function render_toolbox_tool($tool){
                        
            ?>
            <div class="mgb-coaching-tools-item-wpapper">
            <div class="mgb-coaching-tools-col1">
                <img src="<?php echo $tool['bild']['url'];?>">
                <div class="elementor-button-wrapper">
                    <a href="<?php echo $tool['tool_url'];?>" class="elementor-button-link elementor-button elementor-size-sm mgb-btn mgb-btn-alt mgb-btn-fwd" role="button" target="_blank">
                        <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-icon elementor-align-icon-right">
                                <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-forward-medium"></i>
                            </span>
                            <span class="elementor-button-text">zum tool</span>
                        </span>
                    </a>
                </div>
            </div>
            <div class="mgb-coaching-tools-col2">
                <h3><?php echo $tool['titel'];?></h3>
                <div id="mgb_coaching_tools_desc" class="mgb-coaching-tools-description elementor-text-editor elementor-clearfix">
                <?php echo $tool['beschreibung'];?>
                </div>
            </div>
            </div>            
           
            <?php

        }

        private function render_ex_link($tool){
                        
            ?>
            <div class="mgb-coaching-tools-item-wpapper">
            <div class="mgb-coaching-tools-col1">
                <img src="<?php echo $tool['bild']['url'];?>">
                <div class="elementor-button-wrapper">
                    <a href="<?php echo $tool['url'];?>" class="elementor-button-link elementor-button elementor-size-sm mgb-btn mgb-btn-alt mgb-btn-fwd" role="button" target="_blank">
                        <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-icon elementor-align-icon-right">
                                <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-forward-medium"></i>
                            </span>
                            <span class="elementor-button-text"><?php echo $tool['button_download_text'];?></span>
                        </span>
                    </a>
                </div>
            </div>
            <div class="mgb-coaching-tools-col2">
                <h3><?php echo $tool['titel'];?></h3>
                <div id="mgb_coaching_tools_desc" class="mgb-coaching-tools-description elementor-text-editor elementor-clearfix">
                <?php echo $tool['beschreibung'];?>
                </div>
            </div>
            </div>            
           
            <?php

        }
        
        function render_toolbox_lessons(){

            wp_enqueue_script(
                'lesson-browser-script',
                plugin_dir_url( __DIR__ ) . '/assets/lesson_browser.js',
                ['jquery'],
            );
            
            $lessons = get_field('live_coaching_daten', $this->get_post_id())['toolbox']['tool_gruppen']; 
            
            ob_start();
                   
            foreach ($lessons as $lesson):
            ?>
           <div class="mgb-accordion-bar" style="place-content: center space-between; align-items: stretch;">
                <div class="mgb-accordion-bar-title"><h4><?php echo $lesson['titel'];?></h4></div>
                <!--<div class="mgb-accordion-bar-checkbox"><label class="container-checkbox">&nbsp; <input type="checkbox"> <span class="checkmark"></span></label></div>-->
               <!--<div class="mgb-accordion-bar-duration"><span><?php echo $lesson['lektion_lange'];?></span></div>-->
                <div class="mgb-accordion-bar-toggle"><i aria-hidden="true" class="mgbicon- mgb-icon-minus"></i></div>
           </div>
           <div class="mgb_sublesson_tile_wrapper"> 
                <?php

                foreach ($lesson['tool'] as $tool):
                
                    $sub_lesson_no++;
                    $this->render_toolbox_tool($tool);

                endforeach;    

                ?>
            </div>    
               
            <?php

            endforeach;

            return ob_get_clean();

        }


        function render_live_coaching_module_back_forth()
        {
            $user_id = wp_get_current_user()->ID;
            
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['mediathek'];    
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['toolbox']; 
            
            if (mgb_has_product($user_id, DM_IC_PLATIN)){
                    
                $modules[] = get_field('live_coaching_daten', $this->get_post_id())['boni'];  
            
            }
            
            $mod_titles = array('Mediathek','Toolbox','Bonus');
            $mod_count = 0;
            $post_permalink = get_permalink($this->get_post_id());
            $post_permalink .= "?modul="; 

            $back_mod_id = $this->module_id - 1;
            $forth_mod_id = $this->module_id + 1;
                       
            ob_start();
            ?>
            
            <div class="mgb-module-arrow-nav-wrapper">
            
            <?php  
            
            if ($back_mod_id > 0 ):
            
            ?>
            <a href="<?php echo $post_permalink.$back_mod_id;?>" class="mgb-module-arrow-nav-prev">
                    <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-back-medium"></i>
                    <span class="mgb-module-arrow-nav-title">Modul <?php echo $back_mod_id;?> | <?php echo $mod_titles[$back_mod_id-1]?></span>
                    <span class="mgb-module-arrow-nav-title-mobile">Modul  <?php echo $mod_titles[$back_mod_id-1]?></span>
            </a>
            
            <?php 
            
            endif;
            if ($forth_mod_id <= count($modules) ):

            ?>    
            <a href="<?php echo $post_permalink.$forth_mod_id;?>" class="mgb-module-arrow-nav-next">
                    <span class="mgb-module-arrow-nav-title">Modul <?php echo $forth_mod_id;?> | <?php echo $mod_titles[$forth_mod_id-1]?></span>
                    <span class="mgb-module-arrow-nav-title-mobile">Modul <?php echo $mod_titles[$forth_mod_id-1]?></span>
                    <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-forward-medium"></i>
            </a>
            
            <?php
            
            endif;

            ?>
            
            </div>
            
            <?php
            return ob_get_clean();
        
        }


        function render_live_coaching_modules_nav_tiles()
        {
            global $post;

            $user_id = wp_get_current_user()->ID;
            
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['mediathek'];    
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['toolbox']; 
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['boni']; 
            $mod_count = 0;
            $post_permalink = get_permalink($this->get_post_id());
            $post_permalink = rtrim($post_permalink, '/');

            ob_start();
            ?><div class="mgb-module-navbar"><?php
            
            foreach($modules as $module) : 
                     
                $mod_count++;
                
                if (!mgb_has_product($user_id, DM_IC_GOLD) && !mgb_has_product($user_id, DM_IC_PLATIN) && $mod_count == 1) continue;
                if (!mgb_has_product($user_id, DM_IC_GOLD) && !mgb_has_product($user_id, DM_IC_PLATIN) && $mod_count == 2) continue;
                if (!mgb_has_product($user_id, DM_IC_PLATIN) && $mod_count == 3) continue;

                $mod_link = $post_permalink."?modul=".$mod_count;

                if ($mod_count==$this->module_id ? $active_css = "mgb_module_nav_tile_a_active" : $active_css ='');
                
                if ($module['modul_icon']=="") $module['modul_icon']="https://mitglieder.gruender.de/wp-content/uploads/2022/05/gruender-logo-image-figure.svg";
                                
                // READ SVG    
                $svg_data = file_get_contents($module['modul_icon']);
                if (!$svg_data) echo "SVG not read";

                             
                $mod_title = match($mod_count){
                    1 => "Mediathek",
                    2 => "Toolbox", 
                    3 => "Bonus",
                }; 

            ?> 
            <a class="mgb_module_nav_tile_a <?php echo $active_css; ?>" href="<?php echo $mod_link;?>">
               <div class="mgb-module-navbar-item <?php echo $active_css; ?>">
                    <div class="mgb-module-navbar-icon"><?php print_r($svg_data)?></div>
                    <span class="mgb-module-navbar-count">Modul <?php echo $mod_count?></span>
                    <span class="mgb-module-navbar-title"><?php echo $mod_title;?>
               </div>    
            </a>        

            <?php endforeach;
        
            ?></div><?php
            
            return ob_get_clean();
        
        }


        function render_live_coaching_modules()
        {
            global $post;

            $user_id = wp_get_current_user()->ID;
            
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['mediathek'];    
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['toolbox']; 
            $modules[] = get_field('live_coaching_daten', $this->get_post_id())['boni']; 
            
            $mod_count = 0;
            $post_permalink = get_permalink($this->get_post_id());
            $post_permalink = rtrim($post_permalink, '/');

            ob_start();
            ?><div class="mgb_modules_container"><?php
            
            foreach($modules as $module) : 
            
                $mod_count++;

                if (!mgb_has_product($user_id, DM_IC_GOLD) && !mgb_has_product($user_id, DM_IC_PLATIN) && $mod_count == 1) continue;
                if (!mgb_has_product($user_id, DM_IC_GOLD) && !mgb_has_product($user_id, DM_IC_PLATIN) && $mod_count == 2) continue;
                if (!mgb_has_product($user_id, DM_IC_PLATIN) && $mod_count == 3) continue;

                $mod_link = $post_permalink."?modul=".$mod_count;
                
                if ($module['modul_icon']=="") $module['modul_icon']="https://mitglieder.gruender.de/wp-content/uploads/2022/05/gruender-logo-image-figure.svg";

                $cat = get_field('kategorie', $this->get_post_id());

                if ('live-coaching' == $cat){
                    
                    $module['modul_title'] = match(intval($mod_count)){
                        1 => "Mediathek",
                        2 => "Toolbox", 
                        3 => "Bonus",
                    }; 
                    
                }
    
                // READ SVG    
                $svg_data = file_get_contents($module['modul_icon']);
                
                if (!$svg_data) echo "SVG not read";

            ?> 
                <a class="mgb_modules_tile_a" href="<?php echo $mod_link;?>">
                <div class="mgb_modules_tile">
                    <div class="mgb_modules_pos"><?php echo $mod_count?></div>
                    <div class="mgb_modules_title"><?php echo $module['modul_title']?></div>
                    <div class="mgb_modules_icon"><?php print_r($svg_data);?></div>
                    <div class="mgb_modules_desc"><?php echo $module['modulbeschreibung']?></div>
                </div>    
                </a>    

            <?php endforeach;
        
            ?></div><?php
            
            return ob_get_clean();
  
        }

      
        function get_live_coaching_desc()
        {         
            return get_field('live_coaching_daten', $this->get_post_id())['beschreibung'];            
        }

        function get_live_coaching_subtitle()
        {         
            return get_field('live_coaching_daten', $this->get_post_id())['untertitel'];            
        }


        function render_live_coaching_schedule()
        {         
            ob_start();
                        
            $events = get_field('live_coaching_daten', $this->get_post_id())['termine'];
            $wochentage = array('Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag');

            $c=0;
            foreach ($events as $event):

                
                // we want to show only 4 events and make sure we don't list events older than "yesterday" 
                $event_date = DateTime::createFromFormat('Y-m-d H:i:s', $event['datumuhrzeit']); 
                $now = new DateTime("now");
                
                if (!$event_date) break; 
                
                $diff = date_diff($now, $event_date);
                
                // skip old event entries
                if ($diff->format('%R%a')<0) continue;

                $c++;
                if ($c > 10 ) break;

                // some formating to match the layout
                $event_ending_time = clone $event_date;
                $event_custom_ending_time = $event['endzeit'];
                $event_ending_time->add(new DateInterval("PT2H"));
                $event_time = $event_date->format('H:i'). "-" .$event_custom_ending_time;
                $event_day_of_week = $wochentage[$event_date->format('N')-1];
                $event_day = $event_date->format('d');
                $event_month = $event_date->format('M');

            ?> 
             <div class="mgb-coaching-termine-wrapper">
                <div class="mgb-coaching-time-wraper">
                    <span class="mgb-coaching-date"><?php echo $event_day;?> <?php echo $event_month;?></span>
                    <span class="mgb-coaching-day"><?php echo $event_day_of_week;?></span>
                    <span class="mgb-coaching-time"><?php echo $event_time;?></span>
                </div>
                <div class="mgb-coaching-data-wraper">
                    <img src="<?echo $event['speakerbild'] ?>">
                    <div class="mgb-coaching-name-wrapper">
                        <h3 class="mgb-coaching-title"><?echo $event['titel'] ?></h3>
                        <h6 class="mgb-coaching-speaker"><?echo $event['speaker'] ?></h6>
                    </div>
                </div>
                <div class="elementor-button-wrapper">
                        <a href="<?echo $event['coaching_url'] ?>" target="_blank" class="elementor-button-link elementor-button elementor-size-sm mgb-btn mgb-btn-alt mgb-btn-fwd" role="button">
                            <span class="elementor-button-content-wrapper">
                                <span class="elementor-button-icon elementor-align-icon-right">
                                    <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-forward-medium"></i>
                                </span>
                                <span class="elementor-button-text">zum coaching</span>
                            </span>
                        </a>
                </div>
             </div>    
            
            <?php
            endforeach;
            ?>
                        
            <?php

            return ob_get_clean();
        }


       
        function get_webinar_title()
        {         
            if (get_query_var('webinar')=="") return "webinartitel";
            return get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['webinartitel'];            
        }

        function get_webinar_speaker()
        {         
            if (get_query_var('webinar')=="") return "speakername";
            return get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['speakername'];            
        }

        function get_webinar_angebot()
        {         
            if (get_query_var('webinar')=="") return "webinarangebot";
            return get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['webinarangebot'];            
        }

        function get_webinar_inhalte()
        {         
            if (get_query_var('webinar')=="") return "webinarinhalte";
            return get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['webinarinhalte'];            
        }

        function get_webinar_upsell()
        {         
            if (get_query_var('webinar')=="") return "";
            
            $upsell_text = get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['webinarupsell'];   
            
            if ($upsell_text=="") return "";

            ob_start();

            ?>
            <div class="elementor-element elementor-element-74b11cd3 mgb-border mgb-border-subtle elementor-widget-divider--view-line elementor-widget elementor-widget-divider" data-id="74b11cd3" data-element_type="widget" data-widget_type="divider.default">
				<div class="elementor-widget-container">
					<div class="elementor-divider">
			            <span class="elementor-divider-separator"><span>
		            </div>
				</div>
			</div>
            <div class="elementor-element elementor-element-398f3ea3 elementor-widget elementor-widget-heading" data-id="398f3ea3" data-element_type="widget" data-widget_type="heading.default">
			    <div class="elementor-widget-container">
			        <h5 class="elementor-heading-title elementor-size-default">Upsell</h5>		
                </div>
			</div>
            <div class="elementor-element elementor-element-56b52749 elementor-widget__width-initial elementor-widget elementor-widget-text-editor" data-id="56b52749" data-element_type="widget" data-widget_type="text-editor.default">
				<div class="elementor-widget-container">
					<div class="elementor-text-editor elementor-clearfix">
				        <p><?php echo $upsell_text?></p>
					</div>
				</div>
			</div>
            <?php
            return ob_get_clean();

        }

        function get_webinar_speakerdesc()
        {         
            if (get_query_var('webinar')=="") return "speakerbeschreibung";
            return get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['speakerbeschreibung'];            
        }

        function get_webinar_activate_link()
        {         
            if (get_query_var('webinar')=="") return "aktivierungslink";
            return get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['aktivierungslink'];            
        }

        function get_webinar_aff_link()
        {         
            if (get_query_var('webinar')=="") return "affiliatelink"; 
            ob_start();
            ?>
            <script>
                jQuery(document).on('click', '#aff_link', function(event) {
                    event.preventDefault();
                    pw_temp = '<?php echo get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['affiliatelink'];?>';
                    dummy = jQuery('<input>').val(pw_temp).appendTo('body').select()
                    document.execCommand("copy");
                }); 
            </script>
            <?php
            return ob_get_clean();            
        }

        function get_webinar_download_link()
        {         
            if (get_query_var('webinar')=="") return "downloadlink";
            return get_field('webinare', $this->get_post_id())[get_query_var('webinar')-1]['downloadlink'];            
        }

      
        
        function get_product_title()
        {
            global $post;
            $cat = get_field('kategorie', $post->ID);

            return match ($cat) {
                'buch' => get_field('buchdaten', $post->ID)['buch_title'],
                'komplett-produkt' => get_field('komplett_produkt_daten', $post->ID)['kp_title'],
                'legacy-produkt' => get_field('legacy_produkt_daten', $post->ID)['kp_title'],
                'kickstart-coaching' => get_field('kickstart_coaching_daten', $post->ID)['kp_title'],
                'traffic-masterplan' => get_field('traffic_masterplan_daten', $post->ID)['kp_title'],
                'live-coaching', 'zertifizierungslehrgang' => get_the_title($this->get_post_id()),
                default => 'Product Title',
            };
        }

        function get_helpdesk_url()
        {
            global $post;
            return get_field('komplett_produkt_daten', $post->ID)['helpdesk_url'];            
        }

        function get_product_subtitle()
        {
            global $post;
            $cat = get_field('kategorie', $this->get_post_id());
            if ('kickstart-coaching' == $cat){
                
                $acf_group = 'kickstart_coaching_daten';
            
            } else {

                $acf_group = 'komplett_produkt_daten';

            }
            return get_field($acf_group, $post->ID)['kp_untertitle'];            
        }
        
        function get_product_desc()
        {
            global $post;
            $cat = get_field('kategorie', $post->ID);

            return match ($cat) {
                'buch' => get_field('buchdaten', $post->ID)['buch_beschreibung'],
                'komplett-produkt' => get_field('komplett_produkt_daten', $post->ID)['kp_beschreibung'],
                'legacy-produkt' => get_field('legacy_produkt_daten', $post->ID)['kp_beschreibung'],
                'live-coaching', 'zertifizierungslehrgang' => get_field('live_coaching_daten', $post->ID)['beschreibung'],
                'kickstart-coaching' => get_field('kickstart_coaching_daten', $post->ID)['kp_beschreibung'],
                'traffic-masterplan' => get_field('traffic_masterplan_daten', $post->ID)['kp_beschreibung'],
                default => 'Product Description',
            };
        }

        function get_product_permalink()
        {
            global $post;

            $cat = get_field('kategorie', $post->ID);

            $cu = wp_get_current_user();

            $legacy_link = get_field('legacy_produkt_daten', $post->ID)['legacy_url'].'?u='.urlencode($cu->user_login).'&h='.urlencode($cu->user_pass);

            return match ($cat) {
                'legacy-produkt' => $legacy_link,
                default => get_permalink($post)
            };
        }

        function get_product_video_url()
        {
            
            $cat = get_field('kategorie', $this->get_post_id());

            return match ($cat) {
                'buch' => get_field('buchdaten', $this->get_post_id())['produkt_video'],
                'komplett-produkt' => get_field('komplett_produkt_daten', $this->get_post_id())['produkt_video'],
                'live-coaching', 'zertifizierungslehrgang' => get_field('live_coaching_daten', $this->get_post_id())['produkt_video'],
                'traffic-masterplan' => get_field('traffic_masterplan_daten', $post->ID)['produkt_video'],
                'kickstart-coaching' => get_field('kickstart_coaching_daten', $post->ID)['produkt_video'],
            };
             
        }

        function get_live_coaching_url()
        {
            global $post;
            return get_field('komplett_produkt_daten', $post->ID)['live_coaching_url'];            
        }

        function get_product_img()
        {
            global $post;
            $cat = get_field('kategorie', $post->ID);

            $img_id = match ($cat) {
                'buch' => get_field('buchdaten', $post->ID)['buch_bild_product'],
                'komplett-produkt' => get_field('komplett_produkt_daten', $post->ID)['kp_bild'],
                'legacy-produkt' => get_field('legacy_produkt_daten', $post->ID)['kp_bild'],
                'live-coaching', 'zertifizierungslehrgang' => get_field('live_coaching_daten', $post->ID)['bild'],
                'traffic-masterplan' => get_field('traffic_masterplan_daten', $post->ID)['kp_bild'],
                'kickstart-coaching' => get_field('kickstart_coaching_daten', $post->ID)['kp_bild'],
                default => 0,
            };
            $img_url = home_url('/wp-content/plugins/elementor/assets/images/placeholder.png');
            if($img_id){
                $img_url = wp_get_attachment_url($img_id);
            }
            ob_start();
            ?>
            <div class="product-img">
                <img src="<?php echo $img_url;?>">
            </div>
            <?php
            return ob_get_clean();
        }

        function get_module_title(){

            $cat = get_field('kategorie', $this->get_post_id());

            if ('kickstart-coaching' == $cat){
                
                $mod_title = get_field('kickstart_coaching_daten', $this->get_post_id())['kp_module'][$this->module_id - 1]['modul_title'];

                return "Modul " . $this->module_id . " | " . $mod_title;
            }

            if ('live-coaching' == $cat){
                
                $mod_title = match(intval($this->module_id)){
                    1 => "Mediathek",
                    2 => "Toolbox", 
                    3 => "Bonus",
                    default => "default".$this->module_id,
                }; 

                return "Modul " . $this->module_id . " | " . $mod_title;
            }

            if ('traffic-masterplan' == $cat){
                
                $mod_title = get_field('traffic_masterplan_daten', $this->get_post_id())['kp_module'][$this->module_id - 1]['modul_title'];

                return "Modul " . $this->module_id . " | " . $mod_title;
            }

            if (!get_field('komplett_produkt_daten', $this->get_post_id())['kp_module'][$this->module_id - 1 ]) $this->module_id = 1;
                      
            return "Modul " . $this->module_id . " | " . get_field('komplett_produkt_daten', $this->get_post_id())['kp_module'][$this->module_id - 1]['modul_title'];

        }

        function get_module_icon(){
            
            $cat = get_field('kategorie', $this->get_post_id());

            if ('live-coaching' == $cat){
                
                //error_log("MODUL-ID:" . $this->module_id);
                
                $svg_url  = match(intval($this->module_id)){
                    1 => get_field('live_coaching_daten', $this->get_post_id())['mediathek']['modul_icon'],
                    2 => get_field('live_coaching_daten', $this->get_post_id())['toolbox']['modul_icon'], 
                    3 => get_field('live_coaching_daten', $this->get_post_id())['boni']['modul_icon'],
                }; 
            
            } else {
                    
                $acf_group = match ($cat) {
                    'kickstart-coaching' => 'kickstart_coaching_daten',
                    'traffic-masterplan' => 'traffic_masterplan_daten',
                    default => 'komplett_produkt_daten',
                };

                    $svg_url =  get_field($acf_group, $this->get_post_id())['kp_module'][$this->module_id - 1]['modul_icon'];

            }

            if (!empty($svg_url)) $svg_data = file_get_contents($svg_url);    

            if (!$svg_data) echo "SVG not read";
  
            return "<div class='mgb-module-header-icon'>".$svg_data."</div>";    

        }

        function get_module_helpdesk_url(){

            $cat = get_field('kategorie', $this->get_post_id());
            if ('kickstart-coaching' == $cat){
                
                $acf_group = 'kickstart_coaching_daten';
            
            } else {

                $acf_group = 'komplett_produkt_daten';

            }

            if (!get_field($acf_group, $this->get_post_id())['kp_module'][$this->module_id - 1 ]) $this->module_id = 1;
    
            return get_field($acf_group, $this->get_post_id())['kp_module'][$this->module_id - 1]['helpdesk_link'];
        }
       
        function get_module_desc(){

            $cat = get_field('kategorie', $this->get_post_id());

            if ('kickstart-coaching' == $cat){
                
                $module_desc = get_field('kickstart_coaching_daten', $this->get_post_id())['kp_module'][$this->module_id - 1]['modul_desc'];

                return $module_desc;
            }
             
            if ('live-coaching' == $cat){
                
                $modules[1] = get_field('live_coaching_daten', $this->get_post_id())['mediathek'];    
                $modules[2] = get_field('live_coaching_daten', $this->get_post_id())['toolbox']; 
                $modules[3] = get_field('live_coaching_daten', $this->get_post_id())['boni']; 
                $module_desc = $modules[$this->module_id]['modulbeschreibung'];    

                return $module_desc;
            }    

            if ('traffic-masterplan' == $cat){
                
                $module_desc = get_field('traffic_masterplan_daten', $this->get_post_id())['kp_module'][$this->module_id - 1]['modul_desc'];

                return $module_desc;
            }
            
            if (!get_field('komplett_produkt_daten', $post->ID)['kp_module'][$this->module_id - 1 ]) $this->module_id = 1;

            $module_desc = get_field('komplett_produkt_daten', $post->ID)['kp_module'][$this->module_id - 1]['modul_desc'];
            
            if ($module_desc == "") $module_desc = "Modulbeschreibung noch leer.";

            return $module_desc;

        }

        public function module_activated($module){

            $user_id = wp_get_current_user()->ID;
            $user_orderdate = new DateTime(mgb_get_orderdate($user_id,post_to_dm($this->get_post_id())));
            
            $today = new DateTime("now");
            $interval = $user_orderdate->diff($today);
            $days_since_order = $interval->days;
            
            if (0 >= ($module['freischaltung'] - $days_since_order -1)){

                return 1;

            } else {

                return 0;

            }

        }

        function render_module_tiles()
        {
            global $post;
            $modules = get_field('komplett_produkt_daten', $post->ID)['kp_module'];   
            //$toolbox_module = get_field('live_coaching_daten', 4449)['toolbox']; 
            $mod_count = 0;
            $post_permalink = get_permalink($post);
            $post_permalink = rtrim($post_permalink, '/');

            //print_r($modules);
            ob_start();
            ?><div class="mgb_modules_container"><?php
            
            foreach($modules as $module) : 
            
                

                $mod_count++;
                
                if (!$this->module_activated($module)) continue;
                
                $mod_link = $post_permalink."?modul=".$mod_count;
                
               // echo get_query_var('modul',1);

                // SET DEFAULT VALUES: 
                if ($module['modul_desc']=="") $module['modul_desc']="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.";
                if ($module['modul_icon']=="") $module['modul_icon']="https://mitglieder.gruender.de/wp-content/uploads/2022/05/gruender-logo-image-figure.svg";
                
                // READ SVG    
                $svg_data = file_get_contents($module['modul_icon']);
                
                if (!$svg_data) echo "SVG not read";

   
               


            ?> 

                <a class="mgb_modules_tile_a" href="<?php echo $mod_link;?>">
                <div class="mgb_modules_tile">
                    <div class="mgb_modules_pos"><?php echo $mod_count?></div>
                    <div class="mgb_modules_title"><?php echo $module['modul_title']?></div>
                    <div class="mgb_modules_icon"><?php print_r($svg_data)?></div>
                    <div class="mgb_modules_desc"><?php echo $module['modul_desc']?></div>
                </div>    
                </a>    

            <?php endforeach;
        
            ?></div><?php
            
            return ob_get_clean();
        
        }

        function get_product_modules(){

            global $post;
            $modules = get_field('komplett_produkt_daten', $post->ID)['kp_module'];    
            
            $modules = get_field('live_coaching_daten', 4449)['toolbox'];
            //error_log("MODULES: " . json_encode($modules));
            return $modules; 
        } 

        function render_module_nav_tiles()
        {
            global $post;
            $user_id = wp_get_current_user()->ID;
            $cat = get_field('kategorie', $this->get_post_id());
            
            $acf_group = match ($cat) {
                'kickstart-coaching' => 'kickstart_coaching_daten',
                'traffic-masterplan' => 'traffic_masterplan_daten',
                default => 'komplett_produkt_daten',
            };

            $this->get_product_modules();
            $modules = get_field($acf_group, $post->ID)['kp_module'];    
            $mod_count = 0;
            $post_permalink = get_permalink($post);
            $post_permalink = rtrim($post_permalink, '/');

            ob_start();
            ?><div class="mgb-module-navbar"><?php
            
            foreach($modules as $module) : 
            
                $mod_count++;
                              
                if (!$this->module_activated($module)) continue; 
                            
                if ('kickstart-coaching' == $cat){
                    if (!mgb_has_product($user_id, DM_KSC_MODULE_2) && $mod_count == 2) continue;
                    if (!mgb_has_product($user_id, DM_KSC_MODULE_3) && $mod_count == 3) continue;    
                }
                $mod_link = $post_permalink."?modul=".$mod_count;

                if ($mod_count==$this->module_id ? $active_css = "mgb_module_nav_tile_a_active" : $active_css ='');
                
                // SET DEFAULT VALUES: 
                if ($module['modul_icon']=="") $module['modul_icon']="https://mitglieder.gruender.de/wp-content/uploads/2022/05/gruender-logo-image-figure.svg";
                
                // READ SVG    
                $svg_data = file_get_contents($module['modul_icon']);
                if (!$svg_data) echo "SVG not read";

            ?> 
            <a class="mgb_module_nav_tile_a <?php echo $active_css; ?>" href="<?php echo $mod_link;?>">
               <div class="mgb-module-navbar-item <?php echo $active_css; ?>">
                    <div class="mgb-module-navbar-icon"><?php print_r($svg_data)?></div>
                    <span class="mgb-module-navbar-count">Modul <?php echo $mod_count?></span>
                    <span class="mgb-module-navbar-title"><?php echo $module['modul_title']?>
               </div>    
            </a>        

            <?php endforeach;
        
            ?></div><?php
            
            return ob_get_clean();
        
        }

        function render_module_back_forth(){

            $cat = get_field('kategorie', $this->get_post_id());
            $user_id = wp_get_current_user()->ID;
            
            $acf_group = match ($cat) {
                'kickstart-coaching' => 'kickstart_coaching_daten',
                'traffic-masterplan' => 'traffic_masterplan_daten',
                default => 'komplett_produkt_daten',
            };
            
            $modules = get_field($acf_group, $this->get_post_id())['kp_module'];

            // we need to save the original module positions before we delete the not activated ones; 
            
            for ($i = 0; $i < count($modules); $i++) {
                $modules[$i]['modul_number']=$i + 1;  
            }
            
            $modules = array_filter($modules, [$this, 'module_activated']);
            $modules = array_values($modules); 
           
            $active_module_in_array_pos = 0;
            
            
            for ($i = 0; $i < count($modules); $i++) {
                
                if ($modules[$i]['modul_number'] == $this->module_id){
                    $active_module_in_array_pos = $i;
                    //echo "<b>".$modules[$i]['modul_title']."->". $modules[$i]['modul_number']."</b><br>";
                } else {
                    //echo $modules[$i]['modul_title']."->". $modules[$i]['modul_number'] . "<br>";
                }
            }

            

            $back_module_in_array_pos = $active_module_in_array_pos - 1;
            $forth_module_in_array_pos = $active_module_in_array_pos + 1;
            //echo "<br>array_pos:" . $active_module_in_array_pos . "back: " . $back_module_in_array_pos . "forth: " . $forth_module_in_array_pos ."<br>"; 
            //echo "<br>in array: ". $modules[$back_module_in_array_pos]['modul_number'];
        
           
           
            if ('kickstart-coaching' == $cat){
                
                if (!mgb_has_product($user_id, DM_KSC_MODULE_2)) return;
                if (!mgb_has_product($user_id, DM_KSC_MODULE_3) && $this->module_id == 2) return;       

            } 
                                    
            $mod_count = 0;
            $post_permalink = get_permalink($this->get_post_id());
            $post_permalink .= "?modul="; 
                     
            ob_start();
            ?>
            
            <div class="mgb-module-arrow-nav-wrapper">
            
            <?php  
            
            if ($back_module_in_array_pos >= 0 ):
            
            ?>
            <a href="<?php echo $post_permalink.$modules[$back_module_in_array_pos]['modul_number'];?>" class="mgb-module-arrow-nav-prev">
                    <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-back-medium"></i>
                    <span class="mgb-module-arrow-nav-title">Modul <?php echo $modules[$back_module_in_array_pos]['modul_number']?> | <?php echo $modules[$back_module_in_array_pos]['modul_title']?></span>
                    <span class="mgb-module-arrow-nav-title-mobile">Modul <?php echo $modules[$back_module_in_array_pos]['modul_number']?></span>
            </a>
            
            <?php 
            
            endif;
            if ($forth_module_in_array_pos < count($modules) ):

            ?>    
            <a href="<?php echo $post_permalink.$modules[$forth_module_in_array_pos]['modul_number'];?>" class="mgb-module-arrow-nav-next">
                    <span class="mgb-module-arrow-nav-title">Modul <?php echo $modules[$forth_module_in_array_pos]['modul_number'];?> | <?php echo $modules[$forth_module_in_array_pos]['modul_title']?></span>
                    <span class="mgb-module-arrow-nav-title-mobile">Modul <?php echo $modules[$forth_module_in_array_pos]['modul_number']?></span>
                    <i aria-hidden="true" class="mgbicon- mgb-icon-arrow-forward-medium"></i>
            </a>
            
            <?php
            
            endif;

            ?>
            
            </div>
            
            <?php
            return ob_get_clean();
        }

        
        
        private function render_module_sub_lesson($sub_lesson, $sublesson_no){
            // TODO unterlektion_beschreibung unterlektion_video

            ?>
            <div class="mgb_sublesson_tile">
                <div class="mgb_userwebsite_img open_lesson_browser" data-lesson-no="<?php echo $sublesson_no?>"><img loading="lazy" src="<?php echo $sub_lesson['unterlektion_video_thumb']; ?>"></div>
                <div class="mgb_sublesson_duration"><strong>Dauer: </strong><?php echo $sub_lesson['unterlektion_lange'];?></div>
                <div class="mgb_sublesson_title open_lesson_browser" data-lesson-no="<?php echo $sublesson_no?>"><h5><?php echo $sub_lesson['unterlektion_title'];?></h5></div>     
            </div>
            <?php

        }
        
       

        function render_module_lessons(){

            $cat = get_field('kategorie', $this->get_post_id());
            $acf_group = match ($cat) {
                'kickstart-coaching' => 'kickstart_coaching_daten',
                'traffic-masterplan' => 'traffic_masterplan_daten',
                default => 'komplett_produkt_daten',
            };
                                    
            $lessons = get_field($acf_group, $this->get_post_id())['kp_module'][$this->module_id - 1]['modul_lektionen']; 

            $module_content = get_field($acf_group, $this->get_post_id())['kp_module'][$this->module_id - 1];
            $modulesTools = get_field('live_coaching_daten', 4449)['toolbox'];

            // load lesson browser class
            include_once 'mgb_lesson_browser.php'; 

            new MgbLessonBrowser($module_content);

            ob_start();
                     
            // we need a 2D array key to load the popup and jump to the right lesson
            $sub_lesson_no = 0; 
            
            foreach ($lessons as $lesson):

            ?>
            <?php
            if($module_content['modul_title'] == 'Toolbox'){
                ?>
            <div>
            
            <?php
            
            foreach ($modulesTools as $tool):
           
                
                foreach ($tool as $finalTool):
                    ?>
                    <div class="mgb-accordion-bar" style="place-content: center space-between; align-items: stretch;">
                        <div class="mgb-accordion-bar-title"><h4><?php echo $finalTool['titel'];?></h4></div>
                        <!--<div class="mgb-accordion-bar-checkbox"><label class="container-checkbox">&nbsp; <input type="checkbox"> <span class="checkmark"></span></label></div>-->
                        <!--<div class="mgb-accordion-bar-duration"><span><?php echo $finalTool['lektion_lange'];?></span></div>-->
                        <div class="mgb-accordion-bar-toggle"><i aria-hidden="true" class="mgbicon- mgb-icon-minus"></i></div>
                    </div>
                    
                    <div class="mgb_sublesson_tile_wrapper"> 

               <?php

                foreach ($finalTool['tool'] as $modulTool):
                
                    $sub_lesson_no++;
                    $this->render_toolbox_tool($modulTool);

                endforeach;
                ?> 
                </div>
                <?php   
                endforeach;    

            endforeach;    
            ?>
            </div>
                <?php
            }
            if($module_content['modul_title'] !== 'Toolbox'){

                        
            ?>



           <div class="mgb-accordion-bar" style="place-content: center space-between; align-items: stretch;">
                <div class="mgb-accordion-bar-title"><h4><?php echo $lesson['lektion_title'];?></h4></div>
                <!--<div class="mgb-accordion-bar-checkbox"><label class="container-checkbox">&nbsp; <input type="checkbox"> <span class="checkmark"></span></label></div>-->
                <div class="mgb-accordion-bar-duration"><span><?php echo $lesson['lektion_lange']?></span></div>
                <div class="mgb-accordion-bar-toggle"><i aria-hidden="true" class="mgbicon- mgb-icon-minus"></i></div>
           </div>
           <div class="mgb_sublesson_tile_wrapper">
           <?php   
                    global $post;  
                    $kp_id = get_field('komplett_produkt_daten', $post->ID)['kp_id'];                
                    //$title = $lesson[1]['lektion_title']; , lesson:".serialize($lesson)
                    $title = $lesson['lektion_title'];

                    error_log(sprintf("\033[35m%s\033[0m", "Cat: $cat, Module ID: $this->module_id, kp_id: $kp_id, title: $title"));

                    if ('KICERT' == $kp_id && 'Modul 2: KI-Tool Bibliothek' == $title) {

                        wp_enqueue_script(
                            'mediathek',
                            plugin_dir_url( __DIR__ ) . '/assets/sublesson_mediathek.js',
                            ['jquery'],
                        );

                        $custom_tags = get_terms(
                            array(
                                'taxonomy' => 'Zusatzkategorie',
                                'orderby' => 'name',
                                'hide_empty' => false
                            )
                        );
                        error_log(sprintf("\033[35m%s\033[0m", "Tags: ".serialize($custom_tags)));

                        ?><div style="margin-left:4px !important" class="mgb-coaching-filter-wrapper">
                        <a href="#" class="mgb-filter-item-active" data-fid='0'>Alle</a>    
                        <?php
                        foreach ($custom_tags as $tag):
                        ?>
                        <a href="#" data-fid='<?php echo $tag->term_id; ?>'><?php echo $tag->name; ?></a>
                        <?php
                        endforeach;  
                        ?></div>
                        <div class="mgb_subsublesson_tile_wrapper">
                <?php }
              
                // 21.11.23 ACF Bug with multiple nested repeater fields (CH)
                if ('video' == $lesson['lektion_type'] || 'website' == $lesson['lektion_type']) { 
                    foreach ($lesson['unterlektionen'] as $sub_lesson):
                        
                        $sub_lesson_no++;
                        
                        $this->render_module_sub_lesson($sub_lesson, $sub_lesson_no);                    
                        
                    endforeach;    
                }

                // 21.11.23 ACF Bug with multiple nested repeater fields (CH)
                if ('exlink' == $lesson['lektion_type']) { 
                    foreach ($lesson['ex_links'] as $sub_lesson):
                        
                        $this->render_ex_link($sub_lesson);                    
                        
                    endforeach;
                }   
                
                if ('KICERT' == $kp_id && 'Modul 2: KI-Tool Bibliothek' == $title) {
                ?>
                </div>
                <?php } ?>

            </div>    
            <?php
            }
            endforeach;
            
            return ob_get_clean();

        }

        function render_website_tiles_bk2($atts)
        {
            
            // outsourced to reduce filesize
            return include('mgb_module2_bk2.php');
        }

        function render_website_tiles_pod($atts)
        {
            
            // outsourced to reduce filesize
            return include('mgb_module2_pod.php');
        }

        function render_website_dsk(){

            // outsourced to reduce filesize
            return include('mgb_module2_dsk.php');
        }

        function render_website_tiles_dbk($atts)
        {
            
            // outsourced to reduce filesize
            return include('mgb_module2_dbk.php');
        }

        function render_website_tiles_dbk2($atts)
        {
            
            // outsourced to reduce filesize
            return include('mgb_module2_dbk2.php');
        }

        function render_website_wbk(){

            // outsourced to reduce filesize
            return include('mgb_module2_wbk.php');
        }
    
        function render_webinar_tiles_wbk(){
            
            $wbk_upsell_id = 81;

            // check which Product is active
            $kp_id = get_field('komplett_produkt_daten', $post->ID)['kp_id'];  
           
            $user_id = wp_get_current_user()->ID;

            if (mgb_has_product($user_id, $wbk_upsell_id) ? $has_upsell = 1 : $has_upsell = 0);

            $webinars = get_field('webinare', $this->get_post_id()); 

            $c = 0; 
            ob_start();

            ?> <div class="mgb_userwebsite_container"> <?php

            
            foreach($webinars as $webinar):
            $c++;

            //  show upsell webinars only to user with upsell product
            if (($webinar['upsell']=='1') && (!$has_upsell)) continue;

            ?>

            <div class="mgb_userwebsite_tile mgb_userwebsite_tile_wbk">
            <div class="mgb_userwebinar_img"><img loading="lazy" src="<?php echo $webinar['bild']['url'] ?>"></div>
                <div class="mgb_userwebsite_body">
                <h2><?php echo $webinar['speakername'] ?></h2>  
                <h3><?php echo $webinar['webinartitel'] ?></h3>  
                <div class="mgb_userwebsite_button_wrapper">
                    <div>
                    <a href="<?php echo $webinar['aktivierungslink'] ?>" class="mgb_userwebsite_button mgb_userwebsite_button_dark mgb-btn mgb-btn-alt mfb-btn-fwd" role="button" target="_blank">
                        <span class="mgb_userwebsite_button_span_wrapper">
                            <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-arrow-fwd"></i></span>
                            <span class="mgb_userwebsite_button_inner">Webinar Aktivieren</span>
                        </span>
                    </a>    
                    </div>
                <div class="mgb_userwebsite_button_wrapper">
                    <a href="<?php echo $webinar['downloadlink'] ?>" target="_blank" class="mgb_userwebsite_button mgb_userwebsite_button_bright mgb-btn mgb-btn-secondary mgb-btn-download" role="button">
                        <span class="mgb_userwebsite_button_span_wrapper">
                            <span class="mgb_userwebsite_button_icon"><i aria-hidden="true" class="mgbicon- mgb-icon-download"></i></span>
                            <span class="mgb_userwebsite_button_inner">Material herunterladen</span>
                        </span>
                    </a>
                </div>
        <!--    <div class="mgb-toggle-tile-state-wrapper">
                    <span class="mgb-toggle-text">Webinar nicht aktiviert</span>
                    <label class="mgb-toggle">
                        <input type="checkbox">
                        <span class="mgb-toggle-slider"></span>
                    </label>
                </div> -->
                </div>
                <div class="mgb_userwebsite_teaser">
                <?php echo $webinar['teaser'] ?>
                </div>
                <div class="elementor-button-wrapper">
                <a href="<?php echo $_SERVER['REQUEST_URI']. '&webinar='.$c ;?>" class="elementor-button elementor-size-xs mgb-btn mgb-btn-readmore" role="button">
                            <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-text">mehr dazu…</span></span>
                </a>
            </div>
            </div>
                
            </div>

            <?php 
            endforeach; ?>
            </div> 
            <?php      
            
                   
            return ob_get_clean();
        
        }
    
        function render_kickstart_workbook()
        {
            $cat = get_field('kategorie', $this->get_post_id());
            if ('kickstart-coaching' != $cat) return;
            global $post;

            $workbook_url = get_field('kickstart_coaching_daten', $post->ID)['kp_module'][$this->module_id - 1]['workbook_url'];    
            $workbook_download_label = get_field('kickstart_coaching_daten', $post->ID)['kp_module'][$this->module_id - 1]['workbook_download_label']; 
            if ($workbook_url == "") return;

            ob_start();
            ?>
            <div class="elementor-element elementor-element-16c8e118 elementor-align-left mgb-btn mgb-btn-primary elementor-widget__width-auto elementor-widget elementor-widget-button" data-id="16c8e118" data-element_type="widget" data-widget_type="button.default">
				<div class="elementor-widget-container">
					<div class="elementor-button-wrapper">
			            <a href="<?php echo $workbook_url?>" target="_blank" class="elementor-button-link elementor-button elementor-size-sm" role="button">
						<span class="elementor-button-content-wrapper">
						    <span class="elementor-button-text"><?php echo $workbook_download_label?></span>
		                </span>
					    </a>
		            </div>
				</div>
			</div>

            <?php
            
            return ob_get_clean();
        
        }

        function render_kickstart_modules()
        {
            global $post;

            $user_id = wp_get_current_user()->ID;

            $modules = get_field('kickstart_coaching_daten', $post->ID)['kp_module'];    
            
            ob_start();
            ?><div class="mgb_modules_container"><?php
            
            foreach($modules as $module) : 
                       
                $mod_count++;
                
                if (!mgb_has_product($user_id, DM_KSC_MODULE_2) && $mod_count == 2) continue;
                if (!mgb_has_product($user_id, DM_KSC_MODULE_3) && $mod_count == 3) continue;

                $mod_link = $post_permalink."?modul=".$mod_count;
                
               // echo get_query_var('modul',1);

                // SET DEFAULT VALUES: 
                if ($module['modul_desc']=="") $module['modul_desc']="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.";
                if ($module['modul_icon']=="") $module['modul_icon']="https://mitglieder.gruender.de/wp-content/uploads/2022/05/gruender-logo-image-figure.svg";
                
                // READ SVG    
                $svg_data = file_get_contents($module['modul_icon']);
                
                if (!$svg_data) echo "SVG not read";

            ?> 
                <a class="mgb_modules_tile_a" href="<?php echo $mod_link;?>">
                <div class="mgb_modules_tile">
                    <div class="mgb_modules_pos"><?php echo $mod_count?></div>
                    <div class="mgb_modules_title"><?php echo $module['modul_title']?></div>
                    <div class="mgb_modules_icon"><?php print_r($svg_data)?></div>
                    <div class="mgb_modules_desc"><?php echo $module['modul_desc']?></div>
                </div>    
                </a>    


            <?php endforeach;
        
            ?></div><?php
            
            return ob_get_clean();
        
        }

        function render_trafficmasterplan_modules()
        {
            global $post;
            $modules = get_field('traffic_masterplan_daten', $post->ID)['kp_module'];   
           
            //$modules.= get_field('live_coaching_daten', 4449)['toolbox']; 
            $mod_count = 0;
            $post_permalink = get_permalink($post);
            $post_permalink = rtrim($post_permalink, '/');
            //print_r($modules);
            ob_start();
            ?><div class="mgb_modules_container"><?php
            


            foreach($modules as $module) : 
            
                $mod_count++;
                $mod_link = $post_permalink."?modul=".$mod_count;
                
               // echo get_query_var('modul',1);

                // SET DEFAULT VALUES: 
               // if ($module['modul_desc']=="") $module['modul_desc']="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.";
                if ($module['modul_icon']=="") $module['modul_icon']="https://mitglieder.gruender.de/wp-content/uploads/2022/05/gruender-logo-image-figure.svg";
                
                // READ SVG    
                $svg_data = file_get_contents($module['modul_icon']);
                
                if (!$svg_data) echo "SVG not read";

            ?> 

                <a class="mgb_modules_tile_a" href="<?php echo $mod_link;?>">
                <div class="mgb_modules_tile">
                    <div class="mgb_modules_pos"><?php echo $mod_count?></div>
                    <div class="mgb_modules_title"><?php echo $module['modul_title']?></div>
                    <div class="mgb_modules_icon"><?php print_r($svg_data)?></div>
                    <div class="mgb_modules_desc"><?php echo $module['modul_desc']?></div>
                </div>    
                </a>    

            <?php endforeach;
        
            ?></div><?php
            
            return ob_get_clean();
        
        }

        function render_ki_aufzeichnungen_link()
        {
            
            $user_id = wp_get_current_user()->ID;
            if (!mgb_has_product($user_id, DM_KI_AFUZEICHNUNGEN)){
            
                return;

            }

            ob_start();
          

            ?> 
            <div class="elementor-widget-container">
                <div class="elementor-button-wrapper">
                    <a href="https://mitglieder.gruender.de/produkte/zertifikatslehrgang-ki-consultant" class="elementor-button-link elementor-button elementor-size-sm" role="button">
                        <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-text">Aufzeichnungen</span>
                        </span>
                    </a>
                </div>
            </div>
            
            <?php
            
            return ob_get_clean();
        
        }

        function render_ki_potentialanalyse()
        {
            $user_ID = get_current_user_id(); 



            ob_start();

            echo do_shortcode( '[elementor-template id="6992"]' );
            
            return ob_get_clean();

        }

    }

   new Kp_Template_Functions();
}