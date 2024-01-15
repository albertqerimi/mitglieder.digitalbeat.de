(function ($) {
    var dsLoginWidget = function ($container) {
        var $form = $container.find('form');
        $form.attr('method', 'POST');

        var $forgotPasswordButton = $container.find('a[id="DS_LOGIN__ELEMENT__LINK_FORGOT_PASSWORD"]');
        $forgotPasswordButton.on('click', function(event) {
            event.preventDefault();

            DS_LOGIN__CODE__FORGOT_PASSWORD
        });

        var $loginButton = $container.find('button[id="DS_LOGIN__ELEMENT__BUTTON_LOGIN"]');
        $('<div style="width: 100%;" id="%%ERROR_DIV_ID%%"></div>').insertBefore($loginButton);
        $loginButton
            .attr('type', 'submit')
            .on('click', function(event) {
                event.preventDefault();
                var username = encodeURIComponent( $form.find( 'input[name="%%FIELD_USERNAME%%"]').val() );
                var password = encodeURIComponent( $form.find( 'input[name="%%FIELD_PASSWORD%%"]').val() );
                var remember = $form.find( 'input[name="%%FIELD_REMEMBER%%"]').prop('checked') ? '1' : '0';

                var data = {
                    'username': username,
                    'password': password,
                    'remember': remember,
                    'errordiv': '%%ERROR_DIV_ID%%'
                };

                DS_LOGIN__CODE__AJAX_LOGIN
            });
    };

    $(document).ready(function() {
        var $shortcode = $('div[id="%%SHORTCODE_ID%%"]');

        if ('DS_LOGIN__ATTRIBUTE__LAYOUT_TYPE' === 'DS_LOGIN__SECTION__BUTTON') {
            var $dialogButton = $shortcode.find('button[id="DS_LOGIN__ELEMENT__DIALOG_BUTTON"]');
            var $dialog = $('#%%DIALOG_ID%%');
            var $dialogHeadline = $dialog.find('[id="DS_LOGIN__ELEMENT__WIDGET_HEADLINE"]');

            $dialogButton.on('click', function() {
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

            dsLoginWidget($dialog);
        }
        else {
            dsLoginWidget($shortcode);
        }
    });
})(typeof ncoreJQ !== 'undefined' ? ncoreJQ : jQuery);