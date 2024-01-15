(function ($) {
    var oldValues = DS_SIGNUP__OLD_VALUES;
    var dsSignupWidget = function ($container) {
        var $form = $container.find('form');
        $form.attr('method', 'POST');

        var $checkbox = $form.find('input[name="%%FIELD_CHECKBOX%%"]');
        var $signupButton = $form.find('button[id="DS_SIGNUP__ELEMENT__BUTTON_SIGNUP"]');
        $signupButton
            .attr('type', 'submit')
            .on('click', function () {
                if (DS_SIGNUP__ATTRIBUTE__REQUIRE_CHECKBOX && !$checkbox.prop('checked')) {
                    alert('%%REQUIRE_CHECKBOX_TEXT%%');
                    return false;
                }
                return true;
            });

        // Replace field names in error messages
        var $errorLabel = $('.dm-alert-error .dm-alert-content label');
        $form.find('input').each(function () {
            var $input = $(this);
            var name = $input.attr('name');
            var label = $input
                .siblings('label')
                .clone()
                .children()
                .remove()
                .end()
                .text();
            $errorLabel.each(function () {
                var $label = $(this);
                $label.html($label.html().replace(name, label));
            });
            if (typeof oldValues[name] !== 'undefined') {
                $input.val(oldValues[name]);
            }
        });
        if ('DS_SIGNUP__ATTRIBUTE__LAYOUT_TYPE' === 'DS_SIGNUP__SECTION__BUTTON') {
            $errorLabel.each(function () {
                var $label = $(this);
                $label.html($label.html().split('.').join('.<br />'));
            });
        }
    };

    $(document).ready(function () {
        var $shortcode = $('div[id="%%SHORTCODE_ID%%"]');

        if ('DS_SIGNUP__ATTRIBUTE__LAYOUT_TYPE' === 'DS_SIGNUP__SECTION__BUTTON') {
            var $dialogButton = $shortcode.find('button[id="DS_SIGNUP__ELEMENT__DIALOG_BUTTON"]');
            var $dialog = $('#%%DIALOG_ID%%');
            var $dialogHeadline = $dialog.find('[id="DS_SIGNUP__ELEMENT__WIDGET_HEADLINE"]');

            $dialogButton.on('click', function () {
                $dialog.dmDialog('open');
            });

            $dialog.dmDialog({
                modal: true,
                autoOpen: DS_SHORTCODE__PLACEHOLDER__IS_POSTED,
                buttons: {},
                closeText: '%%TITLE_CLOSE%%',
                titleHtml: typeof $dialogHeadline.get(0) !== 'undefined' ? $dialogHeadline.get(0).outerHTML : '',
            });
            $dialogHeadline.remove();
            var $dialogParent = $(`#${$dialog.attr('data-parent')}`);
            $dialogParent.find('.dm-dialog').css('max-width', $dialog.find('.DS_SHORTCODE__ELEMENT_ROOT > div').css('max-width'));
            $dialogParent.find('.dm-dialog-content').css('overflow-y', 'auto');
            $dialogParent.css('z-index', 999996 - 1);

            dsSignupWidget($dialog);
        } else {
            dsSignupWidget($shortcode);
        }
    });
})(typeof ncoreJQ !== 'undefined' ? ncoreJQ : jQuery);