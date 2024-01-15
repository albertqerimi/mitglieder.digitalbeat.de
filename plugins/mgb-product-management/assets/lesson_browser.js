/**
 * 
 * Lesson Browser Jquery Frontend
 * 
*/
var popup_id = 1826;
var lb_pointer = 1;

// this is a backup of the full lb_data var for filtering 
var lb_full = ""; 

jQuery(document).ready(function() {


  //  if (document.querySelector('.active_websites_state .mgb_userwebsite_container .mgb_userwebsite_tile') == null) {
  //      console.log("vorhanden")
  //  }else{
  //      console.log("nicht vorhanden")
  //  }





     try {
        //var getQuickLength = document.querySelector('.quick_access_state .mgb_userwebsite_container, .mgb_userwebsite_tile').innerHTML.length;
        var getWebLength = jQuery('.active_websites_func .mgb_userwebsite_container .mgb_userwebsite_tile').length;

        if(getWebLength == 0 || getWebLength == '' || getWebLength == null) {
            console.log("website nicht vorhanden")
            jQuery(".quick_access_state").hide();
            jQuery(".active_websites_state").hide();

         }else{
            console.log("website vorhanden")
         }

      }
      catch(err) {
      }


    jQuery(window).on('elementor/frontend/init', function() {
        elementorFrontend.on( 'components:init', function() {

    // close all accordions but the first
    first_accordion = jQuery(".mgb-accordion-bar-toggle:first").parent().next();
    first_accordion.css("max-height", '5000px'); 
    first_accordion.css("margin-top", "20px");
    first_accordion.css("margin-bottom", "40px");

    jQuery('i', ".mgb-accordion-bar-toggle:not(:first)").addClass('mgb-icon-plus');
    jQuery('i', ".mgb-accordion-bar-toggle:not(:first)").removeClass('mgb-icon-minus');


    if (typeof lb_data !== 'undefined') lb_full = lb_data;
  
    jQuery(document).on('click', '.mgb-accordion-bar-toggle', function(){
        wrapper = jQuery(this).parent().next();
        
        if (jQuery('i', this).hasClass('mgb-icon-minus')){
          
            jQuery('i', this).addClass('mgb-icon-plus');
            jQuery('i', this).removeClass('mgb-icon-minus');
            wrapper.css("max-height", "0");
            wrapper.css("margin-top", "0px");
            wrapper.css("margin-bottom", "0px");

            // this is only applied on the module 2 pages 
            if ( jQuery('#mgb_instruction_chart').length ) {
                jQuery('#mgb_instruction_chart').css("display", "none");
            }
           
        } else {
         
            jQuery('i', this).addClass('mgb-icon-minus');
            jQuery('i', this).removeClass('mgb-icon-plus');
            //wrapper.css("max-height", wrapper[0].scrollHeight + "px");
            wrapper.css("max-height", '5000px');
            wrapper.css("margin-top", "20px");
            wrapper.css("margin-bottom", "40px");

            // this is only applied on the module 2 pages 
            if ( jQuery('#mgb_instruction_chart').length ) {
                jQuery('#mgb_instruction_chart').css("display", "block");
            }
           
        }
    });        

    jQuery(document).on('click', '.open_lesson_browser', function() {  
        console.log('update');
        // jQuery(document).on('click', '.mgb_userwebsite_img, .mgb_sublesson_title', function() {    
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
        
        if (lb_data[lb_pointer - 1].sublesson_download_url!=""){

            jQuery("#mgb_lesson_popup_material_btn a").attr('href', lb_data[lb_pointer - 1].sublesson_download_url);
            jQuery("#mgb_lesson_popup_material_btn").css({opacity: 0, visibility: "visible"}).animate({opacity: 1}, 300);

        } else {

            jQuery("#mgb_lesson_popup_material_btn").css("visibility", "hidden");
            jQuery("#mgb_lesson_popup_material_btn").css({opacity: 1, visibility: "hidden"}).animate({opacity: 0}, 300);
        }
        jQuery("#mgb_lesson_popup_bar > div > div >:first-child").html('Lesson ' + lb_data[lb_pointer - 1].lesson + ' | ' + 'Video ' + lb_data[lb_pointer - 1].sublesson);
        jQuery("#mgb_lesson_popup_title h4").html(lb_data[lb_pointer - 1].sublesson_title);
        jQuery("#mgb_lesson_popup_desc > div").html(lb_data[lb_pointer - 1].sublesson_desc);
       

       
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
