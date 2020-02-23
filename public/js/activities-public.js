(function ($) {
    'use strict';

    //Join button event
    $(document).ready(function () {
        $('.acts-join-form').on('submit', function (event) {
            event.preventDefault();

			const $form = $(this);

			function toggle_button(disable, data) {
                $form.children.namedItem('button').attr('disabled', disable);
                if (data) {
                    $form.children.namedItem('button').css('min-width', '0');
                    $form.children.namedItem('button').html(data.text);
                    $('.acts-member-count-' + data.id).each(function (index, element) {
                        $(element).html(data.count);
                    });
                } else {
                    $form.children.namedItem('button').css('min-width', $form.children('button').css('width'));
                    $form.children.namedItem('button').html('<div class="acts-loader"></div>');
                }
            }

            toggle_button(true, false);
            $.post($(this).attr('action'), $(this).serialize(), function (response) {
                if (response.success) {
                    toggle_button(false, response.data);
                }
            }, 'json');
        });
    });

})(jQuery);
