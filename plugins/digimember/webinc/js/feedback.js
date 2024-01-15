var dmFeedback = {
    cacheElements: function cacheElements() {
        var deactivationLink = jQuery('#the-list').find('[data-slug="digimember-3"] span.deactivate a');
        if (deactivationLink.length == 0) {
            deactivationLink = jQuery('#the-list').find('[data-slug="digimember"] span.deactivate a');
        }
        this.cache = {
            deactivateLink: deactivationLink,
        };
    },
    bindEvents: function bindEvents() {
        var self = this;
        self.cache.deactivateLink.on('click', function (event) {
            event.preventDefault();
            self.showModal();
        });
    },
    skip: function skip() {
        jQuery.ajax({
            url: __ncore_feedback('ajax_feedback_send_url'),
            method: "POST",
            data: { nofeedback: true },
        }).done(function() {
            dmFeedback.deactivate();
        });
    },
    deactivate: function deactivate() {
        location.href = this.cache.deactivateLink.attr('href');
    },
    showModal: function showModal() {
        dmDialogAjax_FetchUrl(__ncore_feedback('ajax_feedback_dialog_url'));
    },
    init: function init() {
        this.cacheElements();
        this.bindEvents();
    },
    send: function send(form_id) {
        var dialogForm = jQuery('#'+form_id);
        dialogForm.attr('action', __ncore_feedback('ajax_feedback_send_url'));

        jQuery.ajax({
            url: __ncore_feedback('ajax_feedback_send_url'),
            method: "POST",
            data: dialogForm.serialize(),
        }).done(function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                console.log(data.message);
            }
            dmFeedback.deactivate();
        });
    }
};

jQuery(function () {
    dmFeedback.init();
});

