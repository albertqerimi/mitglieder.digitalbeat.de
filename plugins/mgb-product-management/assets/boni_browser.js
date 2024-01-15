/**
 * 
 * Lesson Browser Jquery Frontend
 * 
*/
var popup_id = 3127;
var lb_pointer = 1; 

jQuery(document).ready(function() {
    jQuery(window).on('elementor/frontend/init', function() {
        elementorFrontend.on( 'components:init', function() {

    jQuery(document).on('click', '.mgb_userwebsite_img, .mgb_sublesson_title', function() {    
        lb_pointer = jQuery(this).attr('data-lesson-no');
        elementorFrontend.documentsManager.documents[popup_id].showModal();
       
        lb_update_view();
    }); 

    function lb_next_lesson(){
        if (lb_pointer < lb_data.length) {
        
            lb_pointer++;
        
        } else {
        
            lb_pointer = 1;
        }    
        
        lb_update_view()
    }
    
    function lb_update_view(){
       
        // we don't have downloads connected to videos
        jQuery("#mgb_lesson_popup_material_btn").css("visibility", "hidden");
        jQuery("#mgb_lesson_popup_material_btn").css({opacity: 1, visibility: "hidden"}).animate({opacity: 0}, 300);
       
        jQuery("#mgb_lesson_popup_bar > div > div >:first-child").html('Bonus | ' + 'Video ' + lb_data[lb_pointer - 1].sublesson);
        jQuery("#mgb_lesson_popup_title h4").html(lb_data[lb_pointer - 1].sublesson_title);
        jQuery("#mgb_lesson_popup_desc > div > div").html(lb_data[lb_pointer - 1].sublesson_desc);
        
        if (lb_data[lb_pointer - 1].sublesson_video_url!=""){
            jQuery("#mgb_lesson_popup_video").html('<iframe width="100%" height="550" src="' + lb_data[lb_pointer - 1].sublesson_video_url + '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen=""></iframe>');
        }    
        
        // init vimeo player 
        var iframe = document.querySelector('iframe');
        var player = new Vimeo.Player(iframe);
        /*
        player.on('play', function() {
            console.log('Played the video');
        });
        */

        player.on('ended', function(data) {
            lb_next_lesson();
        });

    }
    
    function lb_prev_lesson(){
    
        if (lb_pointer > 1) {
        
            lb_pointer--;
        
        } else {
        
            lb_pointer = lb_data.length;
        }        

        lb_update_view()
    }

    jQuery(document).on('click', '#mgb_lesson_popup_next', lb_next_lesson);
    jQuery(document).on('click', '#mgb_lesson_popup_prev', lb_prev_lesson);


});
}); 
});