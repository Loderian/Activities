(function ($) {
    'use strict';

    $(document).ready(function () {
        if ($('#acts-nice-preview-plan').length) {
            let plan_name = '';
            let $plan_name = $('.acts-nice-plan-name')
            if ($plan_name.length) {
                plan_name = $plan_name.html();
            }

            function expand_text(session, edit) {
                let session_li = $('.acts-nice-session[session=' + session + ']');
                if (edit) {
                    $(session_li).find('.acts-nice-session-text').toggleClass('acts-nice-session-hidden', false);
                    $(session_li).find('.acts-nice-session-expand .dashicons').toggleClass('dashicons-arrow-down', false);
                    $(session_li).find('.acts-nice-session-expand .dashicons').toggleClass('dashicons-arrow-up', true);
                } else {
                    $(session_li).find('.acts-nice-session-text').toggleClass('acts-nice-session-hidden');
                    $(session_li).find('.acts-nice-session-expand .dashicons').toggleClass('dashicons-arrow-down');
                    $(session_li).find('.acts-nice-session-expand .dashicons').toggleClass('dashicons-arrow-up');
                }
            }

            $(document).on('click', '.acts-nice-session-expand', function () {
                expand_text($(this).parent().attr('session'), false);
            });
            $(document).on('click', '.acts-nice-session-edit', function () {
                let width = window.innerWidth;
                let $session_parent = $(this).parent();
                let $textfield = $session_parent.find('.acts-nice-session-text');
                if (width > 768) {
                    if ($($textfield).find('.acts-nice-session-empty').length) {
                        $($textfield).find('.acts-nice-session-empty').remove();
                    }
                    let name = $($textfield).attr('name');
                    let text = $($textfield).html();
                    let css = $($textfield).attr('class');

                    $($textfield).replaceWith(function () {
                        return $('<textarea />', {class: css, name: name}).append(text);
                    });

                    expand_text($session_parent.attr('session'), true);
                } else {
                    width = width * 0.90;
                    let height = window.innerHeight * 0.90;
                    if (width > 650) {
                        width = 650;
                    }

                    $('.acts-plan-session-edit-box h3').html(plan_name);
                    $('.acts-plan-session-edit-box h4').html('Session ' + $session_parent.attr('session'));
                    let $edit_box_textarea = $('.acts-plan-session-edit-box textarea')
                    $edit_box_textarea.height(height * 0.85);
                    console.log($textfield.find('.acts-nice-session-empty').length)
                    if ($textfield.find('.acts-nice-session-empty').length) {
                        $edit_box_textarea.val('');
                    } else {
                        $edit_box_textarea.val($textfield.html())
                    }


                    tb_show(plan_name, "#TB_inline?height=" + height + "&amp;width=" + width + "&amp;inlineId=acts-plan-session-edit");

                    let wh = $('.acts-plan-session-edit-box').height() + 20;
                    if (wh < height) {
                        $('#TB_ajaxContent').height(height);
                    }
                }
            });

            $('input[name=plan_name]').on('input', function (event) {
                if ($(event.target).val() === plan_name) {
                    $('#create_plan').val(acts_i18n_nice.update_plan);
                } else {
                    $('#create_plan').val(acts_i18n_nice.create_plan);
                }
            });

            $('#create_plan').click(function (event) {
                event.preventDefault();

                let name = $('input[name=plan_name]').val();
                if (name === '') {
                    return;
                }
                let sessions = 0;
                let session_map = {};
                $('.acts-nice-session[session]').each(function (index, elem) {
                    let session = $(elem).attr('session');
                    if ($(elem).find('.acts-nice-session-text .acts-nice-session-empty').length) {
                        $(elem).find('.acts-nice-session-text .acts-nice-session-empty').remove();
                    }
                    let text = '';
                    if ($(elem).find('div.acts-nice-session-text').length) {
                        text = $(elem).find('div.acts-nice-session-text').html();
                    } else {
                        text = $(elem).find('textarea.acts-nice-session-text').val();
                    }

                    session_map[session] = text;
                    sessions++;
                });

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'acts_create_plan',
                        item_id: $('input[name=plan_id]').val(),
                        name: name,
                        session_map: session_map,
                        sessions: sessions,
                        description: ''
                    },
                    dataType: 'json',
                    success: function (resp) {
                        let $new_plan_response = $('.acts-nice-new-response');
                        $new_plan_response.toggleClass('acts-response-success', resp.success);
                        $new_plan_response.toggleClass('acts-response-error', !resp.success);

                        $new_plan_response.html(resp.data);
                    },
                    error: function (jqXHR, text, error) {
                        console.error(text);
                    }
                });
            });
        }
    });
})(jQuery);