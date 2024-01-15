(function ($) {
    $(document).ready(function() {
        var $shortcode = $('#%%SHORTCODE_ID%%');
        var $form = $shortcode.find('form');
        var $exportButton = $shortcode.find('button[id="DS_ACCOUNT__ELEMENT__EXPORT_BUTTON"]');
        // var $saveButton = $shortcode.find('button[id="DS_ACCOUNT__ELEMENT__SAVE_BUTTON"]');
        var $deleteButton = $shortcode.find('button[id="DS_ACCOUNT__ELEMENT__DELETE_BUTTON"]');

        $form
            .attr('method', 'POST');

        $exportButton
            .attr('type', 'button')
            .on('click', function () {
                if (confirm('%%MSG_EXPORT%%')) {
                    $form.append('<input type="hidden" name="dm_export_personal_data" value="1" />');
                    $form.submit();
                }
            });

        $deleteButton
            .attr('type', 'button')
            .on('click', function() {
                DS_ACCOUNT__CODE__DELETE_ACCOUNT
            });

        // Replace field names in error messages
        var $errorLabel = $('.dm-alert-error .dm-alert-content label');
        $form.find('input').each(function() {
            var $input = $(this);
            var name = $input.attr('name');
            var label = $input
                .siblings('label')
                .clone()
                .children()
                .remove()
                .end()
                .text();
            $errorLabel.each(function() {
                var $label = $(this);
                $label.html($label.html().replace(name, label));
            });
        });
    });
})(typeof ncoreJQ !== 'undefined' ? ncoreJQ : jQuery);