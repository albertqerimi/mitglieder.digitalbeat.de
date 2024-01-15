jQuery(document).ready(function ($) {

    $(document).on('click', '.acf-button', function () {
        $('.acf-input-wrap input').bind('keyup', handel_fields($(this)));
    });

    acf.addAction('ready_field/type=accordion', function (field) {
        let target_el_key = field.data.key;
        $("[data-key='" + target_el_key + "']").each(function () {
            let val = $(this).find('input:first').val();
            if (val != '') {
                $(this).find('.acf-accordion-title label').text(val);
            }
            $(this).find('input:first').on('keyup', function () {
                handel_fields($(this));
            });
        })
    })

    function handel_fields(el) {
        let changed = el.val();
        if (changed != '') {
            // console.log($(this).parent().find('.acf-accordion-title label'));
            el.closest('.acf-field-accordion').find('.acf-accordion-title label:first').text(changed);
        }
    }

});