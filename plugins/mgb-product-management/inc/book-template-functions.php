<?php

defined('ABSPATH') or die();

class BookTemplateFunctions
{
    public function __construct()
    {
        add_shortcode('book_title', [$this, 'get_book_title']);
        add_shortcode('book_author', [$this, 'get_book_author']);
        add_shortcode('book_helpdesk_link', [$this, 'get_book_helpdesk_link']);
        add_shortcode('book_feedback_link', [$this, 'get_book_feedback_link']);
        add_shortcode('book_begrusungstext', [$this, 'get_book_begrusungstext']);
        add_shortcode('book_img', [$this, 'get_book_img']);
        add_shortcode('book_content', [$this, 'get_book_content']);
        add_shortcode('ebook_upsell_label', [$this, 'get_ebook_upsell_label']);
        add_shortcode('ebook_upsell_btn_desc', [$this, 'get_ebook_upsell_btn_desc']);
        add_shortcode('ebook_upsell_btn_text', [$this, 'get_ebook_upsell_btn_text']);
        add_shortcode('ebook_upsell_btn_link', [$this, 'get_ebook_upsell_btn_link']);
        add_shortcode('ebook_download_link', [$this, 'get_ebook_download_link']);
        add_shortcode('ebook_audio_download_link', [$this, 'get_ebook_audio_download_link']);
        add_shortcode('ebook_audio_length', [$this, 'get_ebook_audio_length']);
        add_shortcode('ebook_boni_length', [$this, 'get_ebook_boni_length']);
        add_shortcode('book_audio_player', [$this, 'get_book_audio_player']);
        add_shortcode('kapitel_dropdown', [$this, 'get_kapitel_dropdown']);
        add_shortcode('book_boni', [$this, 'render_book_boni']);
        add_shortcode('show_upsell_cta', [$this, 'show_upsell_cta']);
    }

    static function get_post_id(): int
    {        
        if (\Elementor\Plugin::$instance->preview->is_preview_mode() || (\Elementor\Plugin::$instance->editor->is_edit_mode())) {
            return KP_BUCH_DEFAULT_POST_ID;
        } else {
            global $post;
            if (isset($post->ID)) {
                return $post->ID;
            } else {
                return get_the_ID();
            }
        }
    }

    function get_book_title(){
        return get_field('buchdaten', $this->get_post_id())['buch_title'];
    }

    function get_book_author(){
        return get_field('buchdaten', $this->get_post_id())['buch_author'];
    }

    function get_book_helpdesk_link(){
        return get_field('buchdaten', $this->get_post_id())['helpdesk_link'];
    }

    function get_book_feedback_link(){
        return get_field('buchdaten', $this->get_post_id())['feedback_link'];
    }

    function get_book_begrusungstext(){
        return get_field('buchdaten', $this->get_post_id())['begrusungstext'];
    }

    function get_book_content(){
        return get_field('buchdaten', $this->get_post_id())['buch_inhalte'];
    }

    function get_ebook_upsell_label(){
        return get_field('buchdaten', $this->get_post_id())['buch_upsell']['upsell_title'];
    }

    function get_ebook_upsell_btn_text(){
        return get_field('buchdaten', $this->get_post_id())['buch_upsell']['upsell_preis'];
    }

    function get_ebook_upsell_btn_link(){
        return get_field('buchdaten', $this->get_post_id())['buch_upsell']['upsell_link'];
    }

    function get_ebook_upsell_btn_desc(){
        return get_field('buchdaten', $this->get_post_id())['buch_upsell']['upsell_beschreibung'];
    }

    function get_ebook_download_link(){
        return get_field('buchdaten', $this->get_post_id())['ebook_inhalt']['ebook_downloadlink'];
    }

    function get_ebook_audio_download_link(){
        return get_field('buchdaten', $this->get_post_id())['ebook_inhalt']['horbuch_downloadlink'];
    }

    function get_ebook_audio_length(){
        //TODO get total audio length (Hörbuch)
        $post_id = $this->get_post_id();
        $chapters = get_field('buchdaten', $post_id)['ebook_inhalt']['horbuch_kapiteln'];
        
        foreach ($chapters as $chapter) {
            $klength = explode(":",$chapter['kapitel_lange']);
            $klength_sec = $klength[0]*3600 + $klength[1]*60 + $klength[2];

            $length_sec += $klength_sec;
        }
        
        $length_hor = floor($length_sec / 3600);

        $length_min = floor(($length_sec - $length_hor * 3600) / 60);

        $length_sec = $length_sec - $length_min * 60 - $length_hor * 3600;

        $length = sprintf("%02d", $length_hor) . ":" . sprintf("%02d", $length_min) . ":" . sprintf("%02d", $length_sec);
        return $length;
       
    }

    function get_ebook_boni_length(){
        //TODO get total boni length (videos)
        return '40:00';
    }

    function get_book_audio_player(){
        //list of chapters
        $post_id = $this->get_post_id();
        $chapters = get_field('buchdaten', $post_id)['ebook_inhalt']['horbuch_kapiteln'];
        $chapter_urls = [];
        foreach ($chapters as $chapter) {
            $chapter_urls[] = $chapter['kapitel_url'];
        }

        ob_start();
        ?>
        <input type="range" value="0" min="0" max="100" step="1" data-chaper="0">
        <script>
            const list_chapters = [<?php echo '"' . implode('","', $chapter_urls) . '"' ?>];
            const url = list_chapters[0];
            const audio = new Audio(url);
            const playBtn = document.getElementById("audio-player-play");
            const progressEl = document.querySelector('input[type="range"]');
            const timer = document.getElementById('timer');
            let mouseDownOnSlider = false;

            audio.addEventListener("loadeddata", () => {
                progressEl.value = 0;
            });
            audio.addEventListener("timeupdate", () => {
                if (!mouseDownOnSlider) {
                    progressEl.value = audio.currentTime / audio.duration * 100;
                }
            });
            audio.addEventListener("ended", () => {
                playBtn.textContent = "▶️";
            });

            playBtn.addEventListener("click", () => {
                audio.paused ? audio.play() : audio.pause();

                playBtn.innerHTML = audio.paused ? '<i aria-hidden="true" class="mgbicon-player- mgb-icon-player-play"></i>' : '<i aria-hidden="true" class="mgbicon-player- mgb-icon-player-pause"></i>';
            });

            progressEl.addEventListener("change", () => {
                const pct = progressEl.value / 100;
                audio.currentTime = (audio.duration || 0) * pct;
            });
            progressEl.addEventListener("mousedown", () => {
                mouseDownOnSlider = true;
            });
            progressEl.addEventListener("mouseup", () => {
                mouseDownOnSlider = false;
            });
            var update = setInterval(function () {
                var mins = Math.floor(audio.currentTime / 60);
                var secs = Math.floor(audio.currentTime % 60);
                if (secs < 10) {
                    secs = '0' + String(secs);
                }
                document.getElementById('timer').innerHTML = mins + ':' + secs + ' min';
            }, 10);

            //on select other chapter
            document.getElementById('chapter').addEventListener("change", () => {
                //console.log(document.getElementById('chapter').value)
                // audio = new Audio(list_chapters[document.getElementById('chapter').value])
                audio.src = list_chapters[document.getElementById('chapter').value]
                audio.load();
                playBtn.click();
            });

            document.getElementById('audio-player-back').addEventListener("click", () => {
                let current = parseInt(document.getElementById('chapter').value);
                if (current == 0) {
                    return;
                }
                // audio = new Audio(list_chapters[document.getElementById('chapter').value])
                audio.src = list_chapters[current - 1]
                audio.load();
                playBtn.click();
                document.getElementById('chapter').value = current - 1;
            });

            document.getElementById('audio-player-skip').addEventListener("click", () => {
                let current = parseInt(document.getElementById('chapter').value);
                console.log(current);
                console.log(list_chapters.length);
                if (current + 1 == list_chapters.length) {
                    return;
                }
                // audio = new Audio(list_chapters[document.getElementById('chapter').value])
                audio.src = list_chapters[current + 1]
                audio.load();
                playBtn.click();
                document.getElementById('chapter').value = current + 1;
            });
        </script>
        <?php
        return ob_get_clean();
    }

    function get_kapitel_dropdown()
    {
        $post_id = $this->get_post_id();
        $chapters = get_field('buchdaten', $post_id)['ebook_inhalt']['horbuch_kapiteln'];

        ob_start();
        $i = 0;
        ?>
        <select name="chapter" id="chapter">
            <?php foreach ($chapters as $chapter): ?>
                <option value="<?php echo $i; ?>"> <?php echo $chapter['kapitel_title']; ?></option>
                <?php $i++; endforeach; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    private function render_video_tile($sub_lesson, $sublesson_no){
        // TODO unterlektion_beschreibung unterlektion_video

        ?>
        <div class="mgb_sublesson_tile">
            <div class="mgb_userwebsite_img" data-lesson-no="<?php echo $sublesson_no?>"><img loading="lazy" src="<?php echo $sub_lesson['video_thumbnail']; ?>"></div>
            <div class="mgb_sublesson_duration"><strong>Dauer: </strong><?php echo $sub_lesson['video_lange'];?></div>
            <div class="mgb_sublesson_title" data-lesson-no="<?php echo $sublesson_no?>"><h5><?php echo $sub_lesson['title'];?></h5></div>     
        </div>
        <?php

    }

    function render_book_boni(){

        // determines if list the upsell boni as well
        $show_upsell = 0; 

        $book_data = get_field('buchdaten', self::get_post_id());
        if (!isset($book_data['buch_bonis']) || empty($book_data['buch_bonis'])) {
            return;
        }

        $boni_raw_content = $book_data['buch_bonis'];

        if ($this->user_has_upsell()? $show_upsell = 1 : $show_upsell = 0);

        ob_start();
        ?>
        <style>
        .mgb_book_boni {
            color: var( --e-global-color-primary ) !important;
            font-size: 27px;
            font-weight: 600;
            text-transform: none;
            line-height: 1.3em;
            padding-left: 15px;
        }

        .weird_padding_left{
            padding-left: 15px;
        }
        
        </style>
        
        <h6 class="mgb_book_boni">Deine Boni​</h6>
        
        <?php

        // load only video boni in array
        foreach ($boni_raw_content as $boni) {
            if ($boni["buch_boni_type"] == 'video') {
               
                // add video to array if it's an upsell video and the user has bought the upsell or if it's no upsell video and available for book owners directly 
                if ((("1" == $boni['bonus_nur_mit_upsell']) && ($show_upsell)) || ("1" != $boni['bonus_nur_mit_upsell']) ){
                    $boni_videos[] = $boni['video'];
                }
            }
        }

        // load boni browser class
        include_once 'mgb_boni_browser.php'; 

        new MgbBoniBrowser($boni_videos);
        
        $sub_lesson_no = 0; 
        
       ?>
       <div class="mgb_sublesson_tile_wrapper mgb_book_boni_tile_wrapper_fix weird_padding_left"> 
            <?php

            foreach ($boni_videos as $video):
            
                $sub_lesson_no++;
                $this->render_video_tile($video, $sub_lesson_no);
                
            endforeach;    

            ?>
        </div>    
           

        <?php
     
        // show download buttons
        foreach ($boni_raw_content as $boni) {
            if ($boni["buch_boni_type"] == 'download') {

                // add video to array if it's an upsell download and the user has bought the upsell or if it's no upsell downloads and available for book owners directly 
                if ((("1" == $boni['bonus_nur_mit_upsell']) && ($show_upsell)) || ("1" != $boni['bonus_nur_mit_upsell']) ){
                    $boni_content[] = $boni['download_boni'];
                }
            }
        }
        ?>
        <section class="boni-container">
            <div class="boni-item-container">
                <?php foreach ($boni_content as $item): ?>
                    <button onclick="window.open('<?php echo $item['boni_download_url'] ?>','_blank')"><?php echo $item['download_button_text'] ?></button>
                <?php endforeach; ?>
            </div>
        </section>
        
        <?php 
        return ob_get_clean();
    }

    function get_book_img(){
        global $post;
        $cat = get_field('kategorie', $post->ID);

        $img_id = get_field('buchdaten', $post->ID)['buch_bild'];
        
        $img_url = home_url('/wp-content/plugins/elementor/assets/images/placeholder.png');
        if ($img_id) {
            $img_url = wp_get_attachment_url($img_id);
        }
        ob_start();
        ?>
        <div class="product-img">
            <img src="<?php echo $img_url; ?>">
        </div>
        <?php
        return ob_get_clean();
    }

    function user_has_upsell(){
        
        $upsell_id = get_field('buchdaten', self::get_post_id())['buch_upsell']['upsell_buch_digimember_produkt'];
        
        if (mgb_has_product(wp_get_current_user()->ID, $upsell_id) || current_user_can('manage_options')) {
            return 1;
        } else {
            return 0;
        }

    }

    function show_upsell_cta(){
        
        // check if book has an upsell product        
        if ('1' != get_field('buchdaten', self::get_post_id())['buch_upsell_aktivieren']){
            return 0;
        }

        // check if user already bought the upsell product
        if (!$this->user_has_upsell()) {
            return 1;
        } else {
            return 0;
        }

    }

}

new BookTemplateFunctions();