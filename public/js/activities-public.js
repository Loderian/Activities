(function ($) {
    'use strict';

    //Join button event
    $(document).ready(function () {
        $('.acts-join').on('click', function (event) {
            event.preventDefault();
            let $button = $(this)
            if (!$button.attr('disabled')) {
                acts_join_leave($button.parent('.acts-join-form'), $button);
            }
        });

        function acts_join_leave($form, $button) {
            acts_toggle_button($button,true, false);
            $.post($form.attr('action'), $form.serialize(), function (response) {
                if (response.success) {
                    acts_toggle_button($button,false, response.data);
                }
            }, 'json');
        }

        function acts_toggle_button($button, disable, data) {
            let is_image = $button.hasClass('acts-join-image');
            let $image = null;
            if (is_image) {
                $image = $button.children('img');
            }
            $button.attr('disabled', disable);
            if (data) {
                $button.css('min-width', '0');
                if ($image === null) {
                    $button.html(data.joined ? $button.attr('acts_leave_text') : $button.attr('acts_join_text'));
                } else {
                    $image?.attr('src', data.joined ? $button.attr('acts_leave_text') : $button.attr('acts_join_text'));
                    $image?.attr('alt', data.joined ? $button.attr('acts_alt_leave_text') : $button.attr('acts_atl_join_text'));
                    //$button.removeClass('acts-loader');
                    //$image?.html('');
                }
                $('.acts-member-count-' + data.id).each(function (index, element) {
                    $(element).html(data.count);
                });
            } else {
                if ($image === null) {
                    $button.css('min-width', $button.css('width'));
                    $button.html('<div class="acts-loader"></div>');
                } else {
                    //$button.addClass('acts-loader');
                    $image?.html('<div class="acts-loader"></div>');
                }
            }
        }
    });
})(jQuery);
