/**
 * 
 * Shop Browser Jquery Frontend
 * 
*/
var shop_popup_id = 4789;
var dm_form_popup_id = 5132;

var shop_imgs = [
    'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-lieblingstier_mitglieder.jpg',
    'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-heimundhecke_mitglieder.jpg',
    'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-familie_mitglieder.jpg',
    'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-technikshop_mitglieder.jpg',
    'https://mitglieder.gruender.de/wp-content/uploads/2022/08/dsk-product-thumb-beauty_mitglieder.jpg'
];
localStorage.setItem('shop_imgs',JSON.stringify(shop_imgs));
var shop_desc = [
    '<b>Produkte:</b> Hunde-, Katzen-, Kleintier- & Vogelartikel, Futter, Spielzeug, Gehege & Lebensräume, Pflegeprodukte',
    '<b>Produkte:</b> Dekoration & Beleuchtung, Küchenausstattung, Gartenmöbel &- zubehör, Handwerkerbedarf',
    '<b>Produkte:</b> Babyartikel, Babypflege Produkte, Sicherheitsausstattung, Kinderwagen & Zubehör, Spielzeug, Kuscheltiere, Mal- und Bastelartikel',
    '<b>Produkte:</b> Mikrofone, E-Gitarren, Audiozubehör, Computerzubehör, Bildschirme, elektronische Kabel, Bühnentechnik, Handyzubehör',
    '<b>Produkte:</b> Schmuck, Accessoires, Hautpflege, Wellness Produkte'
];

var shop_prototype_ids = [
    1,
    2,
    3,
    4,
    5
];

var selected_shop = "Haustiere";
var selected_shop_prototype = 1;

// dm values
var dmv = {};   

jQuery(document).ready(function() {
    jQuery(window).on('elementor/frontend/init', function() {
        elementorFrontend.on( 'components:init', function() {
           
    jQuery(document).on('click', '#choose-shop', function(event) {
        event.preventDefault();       
        elementorFrontend.documentsManager.documents[shop_popup_id].showModal();
    }); 

    
    // STEP 3 open custom domain popup
    jQuery(document).on('submit', '#dm_form', function (event) {
    
        
        dmv['dm_firstname']     = jQuery('#dm_firstname').val();
        dmv['dm_lastname']      = jQuery('#dm_lastname').val();
        dmv['dm_street']        = jQuery('#dm_street').val();   
        dmv['dm_number']        = jQuery('#dm_number').val();
        dmv['dm_zip']           = jQuery('#dm_zip').val();
        dmv['dm_city']          = jQuery('#dm_city').val();                           
        dmv['dm_country']       = jQuery('#dm_country').val();   
        dmv['dm_phone']         = jQuery('#dm_phone').val();       

        elementorFrontend.documentsManager.documents[dm_form_popup_id].getModal().hide();
        load_domain_popup(selected_shop,selected_shop_prototype,'dsk');
        return false;
    });

    jQuery(document).on('click', 'h2 > a', function(event) {
        jQuery('h2 > a').removeClass('mgb-userwebsite-title-active');
        jQuery(this).addClass('mgb-userwebsite-title-active');

        jQuery('.mgb_userwebsite_body > div > a').html( jQuery(this).html() + ' wählen');
        jQuery('#mgb_shop_browser_img').attr('src',shop_imgs[jQuery(this).attr("data-id")]);
        jQuery('#mgb_shop_prod_desc').html(shop_desc[jQuery(this).attr("data-id")]);
        
        selected_shop_prototype = shop_prototype_ids[jQuery(this).attr("data-id")];
        selected_shop = jQuery(this).html();
        close_confirm_box();

    }); 

    // open confirm box
    jQuery(document).on('click', '#choose_shop_btn', function(event) {

        event.preventDefault();
        jQuery('#choose_shop_confirm').css("padding", "10px 4px 4px");
        jQuery('#choose_shop_confirm').css("border", "solid 1px var(--e-global-color-59e2f6e)");
        jQuery('#choose_shop_confirm').css("max-height", "300px");

    });

    // open dm_form popup
    jQuery(document).on('click', '.mgb-yn-warning-y', function(event) {
        event.preventDefault();
        elementorFrontend.documentsManager.documents[shop_popup_id].getModal().hide();
        elementorFrontend.documentsManager.documents[dm_form_popup_id].showModal();
    });

    jQuery(document).on('click', '.mgb-yn-warning-n', function(event) {
        event.preventDefault();
        close_confirm_box();

    });
    
    function close_confirm_box(){
   
        jQuery('#choose_shop_confirm').css("max-height", "0px");
        jQuery('#choose_shop_confirm').css("padding", "0px");
        jQuery('#choose_shop_confirm').css("border", "0px");

    }
    
});
}); 
});


