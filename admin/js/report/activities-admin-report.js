(function ($) {
    'use strict';

    $(document).ready(function () {
        function getWlClass(item) {
            if (meta_whitelist.hasOwnProperty(item.trim())) {
                return 'acts-nice-wl-ok';
            } else {
                return 'acts-nice-wl-error';
            }
        }

        function get_selectize_options() {
            return {
                create: true,
                addPrecedence: true,
                plugins: ['remove_button'],
                render: {
                    item: function (item, escape) {
                        return '<div class="' + getWlClass(escape(item.value)) + '">' +
                            (item.value ? '<span>' + escape(item.value) + '</span>' : '') +
                            '</div>';
                    },
                    option_create: function (item, escape) {
                        return '<div class="' + getWlClass(escape(item.input)) + ' create">' +
                            'Add: <strong>' + (item.input ? '<span>' + escape(item.input) + '</span>' : '') +
                            '</strong></div>';
                    }
                }
            };
        }

        if ($('.acts-edit-types-options').length) {
            $('.acts-edit-types-options input').selectize(get_selectize_options());
        }

        let showUser = new Set();
        $('.acts-nice-user-info').click(function () {
            let size = $('html').width();
            let user_id = $(this).find('.acts-user-quick-edit').attr('uid');
            if (size <= 600) {
                if (!showUser.has(user_id)) {
                    $(this).find('span:first-child ul').show();
                    $(this).siblings('.acts-nice-col2').show();
                    $(this).find('.acts-nice-collapse').show();
                    $(this).find('.acts-nice-expand').hide();
                    showUser.add(user_id);
                } else {
                    //Reset all styles to make browser use css rules if the device is rotated
                    $(this).find('span:first-child ul').attr('style', '');
                    $(this).siblings('.acts-nice-col2').attr('style', '');
                    $(this).find('.acts-nice-collapse').attr('style', '');
                    $(this).find('.acts-nice-expand').attr('style', '');
                    showUser.delete(user_id);
                }
            }
        });

        let $acts_nice_settings = $('#acts-nice-settings');

        //Activity nice logo control
        if ($acts_nice_settings.length) {
            let $acts_nice_logo = $('#acts-nice-logo');
            if ($acts_nice_logo.attr('src') === '') {
                $acts_nice_logo.hide();
            }

            function on_image_load() {
                imagesLoaded(document.querySelector('#acts-nice-logo'), function () {
                    $('#acts-nice-info').css('min-height', $('#acts-nice-logo').height() + 5);
                });
            }

            let file_frame;

            $('#acts_upload_nice_logo').on('click', function (event) {
                event.preventDefault();

                let selected = parseInt($('#acts_nice_logo_id').val());

                if (file_frame) {
                    file_frame.on('open', function () {
                        if (selected) {
                            let selection = file_frame.state().get('selection');
                            selection.add(wp.media.attachment(selected));
                        }
                    });
                    file_frame.open();
                    return;
                }

                file_frame = wp.media.frames.file_frame = wp.media({
                    title: acts_i18n_nice.select_img_title,
                    library: {
                        type: 'image',
                    },
                    multiple: false
                });

                file_frame.on('open', function () {
                    if (selected) {
                        let selection = file_frame.state().get('selection');
                        selection.add(wp.media.attachment(selected));
                    }
                });

                file_frame.on('select', function () {
                    let attachment = file_frame.state().get('selection').first().toJSON();

                    $acts_nice_logo.attr('src', attachment.url);
                    on_image_load();
                    $acts_nice_logo.show();
                    $('#acts_nice_logo_id').val(attachment.id);
                });

                file_frame.open();
            });

            $('#acts_remove_nice_logo').on('click', function (event) {
                event.preventDefault();

                $acts_nice_logo.attr('scr', '');
                $acts_nice_logo.hide();
                $('#acts_nice_logo_id').val('');
                $('#acts-nice-info').css('min-height', 0);
            });

            $('input[name=header]').on('input', function () {
                $('#acts-nice-header').html($('input[name=header]').val().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'));
            });

            //Activity nice info control
            let activity_fields = ['start', 'end', 'short-desc', 'location', 'responsible', 'long-desc'];

            function display_func(id) {
                $('#acts-nice-' + id).toggle($('#' + id).prop('checked'));
            }

            $('#acts_nice_start_spacing').toggle($('#start').prop('checked') && $('#end').prop('checked'));
            $('#acts_nice_location_spacing').toggle($('#location').prop('checked') && $('#responsible').prop('checked'));

            function display_handler(id) {
                return function (event) {
                    display_func(id);
                    if (id === 'start' || id === 'end') {
                        $('#acts_nice_start_spacing').toggle($('#start').prop('checked') && $('#end').prop('checked'));
                    } else if (id === 'location' || id === 'responsible') {
                        $('#acts_nice_location_spacing').toggle($('#location').prop('checked') && $('#responsible').prop('checked'));
                    }
                }
            }

            for (let i = 0; i < activity_fields.length; i++) {
                let id = activity_fields[i];
                display_func(id);

                $('#' + id).on('change', display_handler(id));
            }

            //Do this after removing acitvity info
            on_image_load();
        }

        //Activity nice members control
        if ($acts_nice_settings.length) {
            let prev_times;
            let session_map = {};

            $('.acts-nice-session').each(function (index, elem) {
                let session = $(elem).attr('session');
                session_map[session] = $(elem).find('div').html();
            });

            function append_checkbox_html(start, end) {
                $('div.acts-nice-user-time').each(function (index, elem) {
                    let id = $(elem).attr('uid');
                    let attended = '';
                    if (all_member_info.hasOwnProperty(id)) {
                        attended = all_member_info[id]['acts_attended'];
                    }
                    for (let i = start; i < end; i++) {
                        let checked = '';
                        if (i < attended.length && attended.charAt(i) == '1') {
                            checked = 'checked="checked"';
                        }
                        $(elem).append('<input type="checkbox" name="time[' + id + '][' + i + ']" time=' + i + ' ' + checked + '/>');
                    }
                });

                let plan_box = $('.acts-nice-session-list');
                for (let session = start + 1; session <= end; session++) {
                    if ($('.acts-nice-session[session=' + session + ']').length) {
                        continue;
                    }
                    $(plan_box).append($('.acts-nice-session[session=1]').clone());
                    let new_session = $(plan_box).find('.acts-nice-session:last');
                    $(new_session).attr('session', session);
                    $(new_session).find('b span:first').html(session);
                    $(new_session).find('.acts-nice-session-text').attr('name', 'session_map[' + session + ']');
                    if (session_map.hasOwnProperty(session)) {
                        $(new_session).find('.acts-nice-session-text').html(session_map[session]);
                    } else {
                        new_session.find('.acts-nice-session-text').html('<div class="acts-nice-session-empty">' + acts_i18n_nice.empty + '</div>');
                    }
                }
            }

            let $timeSlots = $('#time-slots')
            function update_sessions() {
                let times = parseInt($timeSlots.val());
                let max = parseInt($timeSlots.attr('max'));
                let exist = -1;
                if ($('.acts-nice-user-time').length) {
                    exist = $('input[time]:last').attr('time');
                } else {
                    return;
                }

                if (isNaN(times)) {
                    times = 0;
                } else if (times > max) {
                    times = max;
                    $timeSlots.val(times);
                } else if (times < 0) {
                    times = 0;
                    $timeSlots.val(times);
                }

                if (prev_times === times || exist === times - 1) {
                    return;
                }

                let last_time = parseInt($('[time]:last').attr('time')) + 1;
                if (times > prev_times) {
                    append_checkbox_html(prev_times, times);
                } else if (prev_times > times || last_time > times) {
                    if (isNaN(prev_times)) {
                        prev_times = last_time;
                    }
                    for (let i = prev_times - 1; i >= times; i--) {
                        $('input[type="checkbox"][time=' + i + ']').remove();
                        if (i > 0) {
                            $('.acts-nice-session[session=' + (i + 1) + ']').remove();
                        }
                    }
                } else {
                    $('div.acts-nice-user-time').html('');
                    append_checkbox_html(0, times);
                }

                prev_times = times;
            }

            $timeSlots.on('input', function () {
                update_sessions();
            });

            function mark_session(mark) {
                let $timeMark = $('#acts-time-mark');
                let time = parseInt($timeMark.val());
                if (isNaN(time) || time < 1) {
                    time = 1;
                }

                let $sessionCheckboxes = $('input[time=' + (time - 1) + ']')
                $sessionCheckboxes.prop('checked', mark);
                if (mark) {
                    time++;
                } else {
                    time--;
                }
                if ($sessionCheckboxes.length) {
                    $timeMark.val(time);
                }
            }

            $('#mark_session_on').on('click', function (event) {
                event.preventDefault();

                mark_session(true);
            });

            $('#mark_session_off').on('click', function (event) {
                event.preventDefault();

                mark_session(false);
            });

            function change_color(elem, color = '') {
                let text = $(elem).closest('li').children('input[name="nice_color_key[]"]').val();
                if (color === '') {
                    color = $(elem).val();
                }
                text.split(',').forEach(function (str) {
                    str = str.trim();
                    if (str != '') {
                        if ($('.acts-nice-custom-' + str).length) {
                            $('.acts-nice-custom-' + str).css('background-color', color);
                        }
                    }
                });
            }

            reload_color();

            function reload_color() {
                $('input[name="nice_color[]"]').each(function (index, elem) {
                    change_color(elem);
                });
            }

            let $colors = $('#acts-nice-color')
            if ($colors.length) {
                $('#acts-nice-color input[name="nice_color_key[]"]').selectize(get_selectize_options());

                let html_color = '<li class="acts-nice-custom-split"><input type="text" value="" name="nice_color[]" />';
                html_color += '<input type="text" name="nice_color_key[]" value="" />';
                html_color += ' <input type="submit" name="delete_color" value="-" class="delete-color button" />';
                html_color += '</li>';

                function add_color_control() {
                    $('input[type=text][name="nice_color[]"]').wpColorPicker({
                        change: function (event, ui) {
                            change_color(event.target, ui.color.toString());
                        }
                    });
                }

                add_color_control();

                $('#add-color').on('click', function (event) {
                    event.preventDefault();


                    $colors.append(html_color);
                    add_color_control();

                    let elem = $colors.children().last('li').children('input[name="nice_color_key[]"]');

                    $(elem).selectize(get_selectize_options());
                });

                $(document).on('click', 'input[type=submit][name=delete_color]', function (event) {
                    event.preventDefault();
                    let text = $(this).siblings('input').val();
                    let $customItem = $('.acts-nice-custom-' + text)
                    if ($customItem.length) {
                        $customItem.css('background-color', '');
                    }
                    $(this).parent('li').remove();
                });
            }

            if ($('#acts-nice-custom').length) {
                $('.acts-nice-custom[col] input[type=text]').selectize(get_selectize_options());

                let html_custom = '<li class="acts-nice-custom-split"><input type="text" name="nice_custom[][]" />';
                html_custom += ' <input type="submit" name="delete_custom" value="-" class="delete-custom button" /></li>';

                //Add new custom row
                $('input[col]').on('click', function (event) {
                    event.preventDefault();

                    let col = $(this).attr('col');

                    let $list = $('ul[col=' + col + ']');

                    $list.append(html_custom);

                    let elem = $list.children().last('li').children('input[name="nice_custom[][]"]');
                    $(elem).attr('name', 'nice_custom[' + col + '][]');

                    $(elem).selectize(get_selectize_options());
                });

                $(document).on('click', 'input[type=submit][name=delete_custom]', function (event) {
                    event.preventDefault();

                    $(this).parent('li').remove();
                });
            }

            let link = $('#acts-nice-user-link').attr('href');

            $('.acts-user-quick-edit[uid]').on('click', function (event) {
                event.preventDefault();

                let id = $(this).attr('uid');
                if (!all_member_info.hasOwnProperty(id)) {
                    return;
                }
                $('#acts-nice-user-link').attr('href', link + '?user_id=' + id);
                let user_info = all_member_info[id];

                $('input[name=uid]').val(id);
                for (let key in user_info) {
                    let $acts_quick_key = $('#acts-quick-' + key);
                    if (key === 'acts_full_name') {
                        continue;
                    } else if (key === 'acts_user_avatar') {
                        let $acts_user_avatar = $('#acts-user-avatar');
                        $acts_user_avatar.attr('src', '');
                        $('.acts-quick-img-wrap .acts-nice-loader').show();
                        $acts_user_avatar.attr('src', user_info[key]);
                        imagesLoaded(document.querySelector('#acts-user-avatar'), function () {
                            $('.acts-quick-img-wrap .acts-nice-loader').hide();
                        });
                    } else if ($acts_quick_key.length) {
                        if ($acts_quick_key.is('select') && $acts_quick_key.attr('class') === 'selectized') {
                            let sel = $acts_quick_key.eq(0).data('selectize');
                            if (sel) {
                                sel.setValue(user_info[key], true);
                                continue;
                            }
                        }

                        $acts_quick_key.val(user_info[key]);
                    } else if (key === 'roles') {
                        $('.acts-quick-edit-roles').find('input[type=checkbox]').each(function () {
                                $(this).prop('checked', user_info['roles'].includes($(this).attr('user_role')));
                            }
                        );
                    }
                }

                let h = window.innerHeight * 0.90;
                let w = window.innerWidth * 0.90;
                if (w > 650) {
                    w = 650;
                }

                tb_show(all_member_info[id].acts_full_name, "#TB_inline?height=" + h + "&amp;width=" + w + "&amp;inlineId=acts-quick-user-edit");

                let wh = $('.acts-quick-edit-box').height() + 20;
                if (wh < h) {
                    $('#TB_ajaxContent').height(wh);
                }
            });

            $('.acts-quick-edit-box').on('submit', function (event) {
                event.preventDefault();
                $.post($(this).attr('action'), $(this).serialize(), function (response) {
                    if (response.success) {
                        let user_info = response.data;
                        let id = user_info['ID'];
                        delete user_info['ID'];
                        update_user_info(id, user_info);
                        write_member_info(id);
                        tb_remove();
                    } else {
                        console.error('An error occurred updating user.');
                    }
                }, 'json');
            });

            let id = parseInt($('#item-id').val());

            let all_member_info = {};

            function load_custom_fields() {
                if ($('ul[col]').length) {
                    let custom_fields = [];

                    $('ul[col]').each(function (index, elem) {
                        let col = $(elem).attr('col');
                        $(elem).find('input[name="nice_custom[' + col + '][]"]').each(function (text_index, text_elem) {
                            custom_fields.push({
                                name: $(text_elem).val(),
                                col: col
                            });
                        });
                    });

                    return custom_fields;
                } else {
                    return {};
                }
            }

            function disable_member_info_controls(disable) {
                $('#acts-reload-members').attr('disabled', disable);
                $timeSlots.attr('disabled', disable);
                $('input[type=radio][name=member_info]').attr('disabled', disable);
                $('#add-custom').attr('disabled', disable);
                $('input[type=text][name="nice_custom[]"]').attr('disabled', disable);
                $('select[name="nice_custom_col[]"]').attr('disabled', disable);
                $('input[type=submit][name=delete_custom]').attr('disabled', disable);
                $('#add-color').attr('disabled', disable);
                $('input[type=text][name="nice_color_key[]"]').attr('disabled', disable);
                $('input[type=text][name="nice_color[]"]').attr('disabled', disable);
                $('input[type=submit][name=delete_color]').attr('disabled', disable);
                $('#acts-nice-settings .acts-nice-loader').toggle(disable);
            }

            function update_user_info(id, new_info) {
                if (!all_member_info.hasOwnProperty(id)) {
                    all_member_info[id] = {};
                }
                for (let key in new_info) {
                    all_member_info[id][key] = new_info[key];
                }
            }

            function load_member_info(write) {
                disable_member_info_controls(true);
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'acts_get_member_info',
                        item_id: id,
                        custom: load_custom_fields()
                    },
                    dataType: 'json',
                    success: function (member_info) {
                        if (!member_info.success) {
                            console.error('Could not load user info');
                            return;
                        }
                        for (let id in member_info.data) {
                            update_user_info(id, member_info.data[id]);
                        }
                        if (write) {
                            write_member_info(0);
                        }

                        update_sessions();
                    },
                    error: function (jqXHR, text, error) {
                        console.error(text);
                    },
                    complete: function () {
                        disable_member_info_controls(false);
                    }
                });
            }

            load_member_info(false);

            //Column 1
            let prepared_keys_1 = {
                wp: [],
                bill: [
                    'billing_address_1',
                    'billing_address_2',
                    'billing_city',
                    'billing_postcode',
                ],
                ship: [
                    'shipping_address_1',
                    'shipping_address_2',
                    'shipping_city',
                    'shipping_postcode'
                ]
            };

            //Column 2
            let prepared_keys_2 = {
                wp: [],
                bill: [
                    'billing_phone'
                ],
                ship: []
            };

            function write_prep_col(user_info, col, list) {
                col.html('');

                for (let i in list) {
                    let val = user_info[list[i]];
                    switch (list[i]) {
                        case 'billing_city':
                        case 'shipping_city':
                            break;

                        case 'billing_postcode':
                        case 'shipping_postcode':
                            val += ' ' + user_info[list[parseInt(i) - 1]];
                            if (val.trim() != '') {
                                col.append('<li>' + val + '</li>');
                            }
                            break;

                        default:
                            col.append('<li>' + val + '</li>');
                            break;
                    }
                }
            }

            function write_custom_col(user_info, col, custom_fields) {
                col.html('');

                for (let i in custom_fields) {
                    let list = custom_fields[i];
                    let display_list = [];

                    for (let r in list) {
                        let key = list[r].trim();
                        if (user_info.hasOwnProperty(key)) {
                            let val = user_info[key];
                            if (val != '') {
                                display_list.push('<span class="acts-nice-custom-' + key + '">' + val + '</span>');
                            }
                        }
                    }
                    if (display_list.length > 0) {
                        col.append('<li>' + display_list.join(' ') + '</li>');
                    }
                }
            }

            //User id 0 to write for all users
            function write_member_info(user) {
                if (all_member_info.length == 0) {
                    return;
                }
                let type = $('input[name=member_info]:checked').val();
                let custom_input = load_custom_fields();
                let custom_fields = {
                    1: [],
                    2: []
                };
                for (let i in custom_input) {
                    if (custom_input.hasOwnProperty(i)) {
                        custom_fields[custom_input[i]['col']].push(custom_input[i]['name'].split(','));
                    }
                }

                for (let id in all_member_info) {
                    if (user != 0 && user != id) {
                        continue;
                    }
                    if (all_member_info.hasOwnProperty(id)) {
                        let col1 = $('#col1-id' + id);
                        let col2 = $('#col2-id' + id);
                        let user_info = all_member_info[id];

                        col1.find('span[key=acts_full_name]').html(user_info.acts_full_name);
                        col2.find('span[key=user_email]').html(user_info.user_email);

                        write_prep_col(user_info, col1.find('.acts-nice-prepared'), prepared_keys_1[type]);
                        write_prep_col(user_info, col2.find('.acts-nice-prepared'), prepared_keys_2[type]);

                        write_custom_col(user_info, col1.find('.acts-nice-custom-display'), custom_fields[1]);
                        write_custom_col(user_info, col2.find('.acts-nice-custom-display'), custom_fields[2]);
                    }
                }

                if ($('#acts-nice-color').length) {
                    reload_color();
                }
            }

            $('input[type=radio][name=member_info]').on('change', function () {
                let type = $('input[name=member_info]:checked').val();
                if (all_member_info[type] === undefined) {
                    load_member_info(true);
                } else {
                    write_member_info(0);
                }
            });

            $('#acts-reload-members').on('click', function (event) {
                event.preventDefault();

                load_member_info(true);
            });
        }

        //Activity nice folder print
        if ($('#folder_print').length) {
            $('#folder_print').click(function (event) {
                event.preventDefault();

                $('.acts-nice-wrap').css('padding-left', '20mm');
                window.print();
                $('.acts-nice-wrap').css('padding-left', '7mm');
            });
        }
    });
})(jQuery);
