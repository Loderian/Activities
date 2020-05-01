(function ($) {
    'use strict';

    //Join button event
    $(document).ready(function () {
        $('.acts-join').on('click', function (event) {
            event.preventDefault();
            let $button = $(this);
            let $other_buttons = $('.acts-join[value="' + $button.attr('value') + '"]').filter(
                function() {
                    return !$button.is(this);
                }
            );
            console.log($other_buttons);
            if (!$button.attr('disabled')) {
                acts_join_leave($button.parent('.acts-join-form'), $button, $other_buttons);
            }
        });

        function acts_join_leave($form, $button, $other_buttons) {
            acts_toggle_button($button,true, false);
            $.each($other_buttons, function (i, val) {
                acts_toggle_button($(val), true, false);
            });
            $.post($form.attr('action'), $form.serialize(), function (response) {
                if (response.success) {
                    acts_toggle_button($button,false, response.data);
                    $.each($other_buttons, function (i, val) {
                        acts_toggle_button($(val), false, response.data);
                    });
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
                    $button.children('.acts-loader').remove();
                }
                $('.acts-member-count-' + data.id).each(function (index, element) {
                    $(element).html(data.count);
                });
            } else {
                if ($image === null) {
                    $button.css('min-width', $button.css('width'));
                    $button.html('<div class="acts-loader"></div>');
                } else {
                    $button.prepend('<div class="acts-loader"></div>');
                }
            }
        }
    });
})(jQuery);
