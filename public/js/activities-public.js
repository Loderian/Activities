(function ($) {
    'use strict';

    //Join button event
    $(document).ready(function () {
        $('.acts-join-form').on('submit', function (event) {
            event.preventDefault();

            const $form = $(this);

            function toggle_button(disable, data) {
                const $button = $form.children('.acts-join-button');
                $button.attr('disabled', disable);
                if (data) {
                    $button.css('min-width', '0');
                    $button.html(data.text);
                    $('.acts-member-count-' + data.id).each(function (index, element) {
                        $(element).html(data.count);
                    });
                } else {
                    $button.css('min-width', $button.css('width'));
                    $button.html('<div class="acts-loader"></div>');
                }
            }

            toggle_button(true, false);
            $.post($form.attr('action'), $form.serialize(), function (response) {
                if (response.success) {
                    toggle_button(false, response.data);
                }
            }, 'json');
        });
    });
})(jQuery);
