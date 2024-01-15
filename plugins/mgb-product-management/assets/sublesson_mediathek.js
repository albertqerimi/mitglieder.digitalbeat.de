/**
 * 
 * Lesson Browser Jquery Frontend
 * 
*/

jQuery(document).on('click', '.mgb-coaching-filter-wrapper a', function(event) {    
    event.preventDefault();
    lb_data = lesson_filter(lb_full, jQuery(this).attr('data-fid'));
    re_render_modules(); 
    jQuery('.mgb-coaching-filter-wrapper a').removeClass('mgb-filter-item-active');
    jQuery(this).addClass('mgb-filter-item-active');

});

function lesson_filter(lessons, filter_id) 
{
    let lesson_no = 0;
    let filtered_lessons = [{}];

    if (filter_id == "0") return lb_full;
    lessons.forEach(function(lesson) {        
        console.log(lesson);
        if (Array.isArray(lesson.sublesson_tags)){
            lesson.sublesson_tags.forEach(function(tag){
                if (tag.term_id==filter_id){                    
                    filtered_lessons.push(lesson);    
                }     
            });
        }else{
            
        }

        lesson_no++;

    });

    return filtered_lessons;
}


function re_render_modules()
{
    let modules_html = "";
    let lesson_no = 1; 

    lb_data.slice(1).forEach(function(lesson)
    {
        modules_html += `<div class="mgb_sublesson_tile">
        <div class="mgb_userwebsite_img open_lesson_browser" data-lesson-no="` + lesson_no + `"><img loading="lazy" src="` + lesson.sublesson_video_thumb_url + `"></div>
        <div class="mgb_sublesson_duration"><strong>Dauer: </strong>` + lesson.sublesson_video_lange + `</div>
        <div class="mgb_sublesson_title open_lesson_browser" data-lesson-no="` + lesson_no + `"><h5>` + lesson.sublesson_title + `</h5></div>     
        </div>`;

        lesson_no++;

    });

    jQuery('.mgb_subsublesson_tile_wrapper').html(modules_html);
}