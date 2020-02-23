(function ($) {
    'use strict';

    $(document).ready(function () {
        //Location Country selectize
        if ($('#acts-location-country').length) {
            $('#acts-location-country').selectize({});
        }

        //Activity nice quick change selectize
        if ($('#acts_nice_quick_change').length) {
            $('#acts_nice_quick_change').selectize({});
        }

        //Activity responsible options
        if ($('#acts-activity-responsible').length) {
            $('#acts-activity-responsible').selectize({});
        }

        //Activity location options
        if ($('#acts-activity-location').length) {
            $('#acts-activity-location').selectize({});
        }

        //Activity plan options
        if ($('#acts-activity-plan').length) {
            $('#acts-activity-plan').selectize({});
        }

        //Activity member options
        if ($('#acts-activity-member-list').length) {
            set_member_count();

            function set_member_count() {
                if ($('#acts-activity-member-list').val() != null) {
                    $('#member_count').html($('#acts-activity-member-list').val().length);
                } else {
                    $('#member_count').html('0');
                }
            }

            $('#acts-activity-member-list').selectize({
                plugins: ['remove_button'],
                onChange: function () {
                    set_member_count()
                }
            });
        }

        //Activity export select activity
        if ($('#acts_select_activity_export').length) {
            $('#acts_select_activity_export').selectize({});
        }

        //Activity bulk selectize
        if ($('#acts_bulk_selectize').length) {
            $('#acts_bulk_selectize').selectize({
                plugins: ['remove_button']
            });
        }

        //Select all in list table
        if (!$('#activities-select-all').length) {
            $('#activities-select-all').on('change', function () {
                var all_checked = $(this).prop('checked');
                $('input[name="selected_activities[]"]').each(function (index, element) {
                    $(element).prop('checked', all_checked);
                });
            });

            $('input[name="selected_activities[]"]').on('change', function () {
                var all_checked = true;
                $('input[name="selected_activities[]"]').each(function (index, element) {
                    all_checked = $(element).prop('checked');
                    return all_checked; //false = break, true = continue
                });
                $('#activities-select-all').prop('checked', all_checked);
            });
        }

        //Show/hide columns in list tables
        if ($('#acts_name').length) {
            var columns = [];

            $('.metabox-prefs [key]').each(function (index, elem) {
                columns.push($(elem).attr('key'));
            });

            function toggleColumn(column) {
                return function () {
                    if ($('.colspanchange').length > 0) {
                        var num = parseInt($('.colspanchange').attr('colspan'));
                        if ($('#acts_' + column).prop('checked')) {
                            num++;
                        } else {
                            num--;
                        }
                        $('.colspanchange').attr('colspan', num);
                    }
                    $('thead tr #' + column).toggleClass('hidden', !$('#acts_' + column).prop('checked'));
                    $('tfoot tr #' + column).toggleClass('hidden', !$('#acts_' + column).prop('checked'));
                    $('.' + column).each(function (index, element) {
                        $(element).toggleClass('hidden', !$('#acts_' + column).prop('checked'));
                    });
                }
            }

            for (var column of columns) {
                if ($('#acts_' + column).length > 0) {

                    $('#acts_' + column).on('change', toggleColumn(column));
                }
            }
        }

        //One click select and copy export
        if ($('#acts-export-results').length) {
            $('#acts-export-results').click(function () {
                var elem = document.getElementById('acts-export-results');
                var range = document.createRange();
                range.selectNodeContents(elem);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
                document.execCommand('copy');
                $('#acts-export-copied').css('visibility', 'visible');
            });
        }

        $('#show_category_form').click(function (event) {
            event.preventDefault();

            $('#category_form').toggle();
        });

        function add_to_table(selector, data) {
            var table = $(selector);
            table.find('tr:first').clone(true).appendTo(table);
            var new_row = table.find('tr:last');
            new_row.find('a').attr('tid', data.id);
            new_row.find('a span:first').html(data.name);
            new_row.find('input').val(data.id);
            new_row.find('input[type=checkbox]').attr('checked', false);
        }

        function add_to_select(selector, data) {
            var select = $(selector);
            select.find('option:first').clone(true).appendTo(select);
            var new_option = select.find('option:last');
            new_option.val(data.id);
            new_option.html(data.name);
        }

        $('#create_category').click(function (event) {
            event.preventDefault();

            var name = $('.acts-categories input[name=category_name]');
            var parent = $('.acts-categories select[name=category_parent]');

            $('#category_form').toggle(false);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'acts_insert_cat',
                    name: $(name).val(),
                    parent: $(parent).val()
                },
                dataType: 'json',
                success: function (cat) {
                    if (!cat.success) {
                        console.error(cat.data);
                        return;
                    }

                    add_to_table('.acts-categories table tbody', cat.data);

                    add_to_select('.acts-categories select[name=category_parent]', cat.data);
                    add_to_select('.acts-category-edit select[name=category_parent]', cat.data);

                    term_data[cat.data.id] = {
                        name: cat.data.name,
                        slug: cat.data.slug,
                        desc: '',
                        parent: cat.data.parent
                    };
                    $(name).val('');
                    $(parent).val('0');
                },
                error: function (jqXHR, text, error) {
                    console.error(text);

                    $('#category_form').toggle(true);
                }
            });
        });

        $('.acts-category-name a').click(function (event) {
            event.preventDefault();

            var h = window.innerHeight * 0.90;
            var w = window.innerWidth * 0.90;
            if (w > 500) {
                w = 500;
            }

            var id = $(this).attr('tid');
            var form = $('.acts-category-edit');
            form.find('input[name=category_id]').val(id);
            form.find('input[name=category_name]').val(term_data[id].name);
            form.find('select[name=category_parent]').val(term_data[id].parent);
            form.find('textarea[name=category_description]').val(term_data[id].desc);
            $('#delete_category').toggle(term_data[id].slug !== 'uncategorized');
            window.scrollTo(0, 0);
            tb_show($(this).html(), "#TB_inline?height=" + h + "&amp;width=" + w + "&amp;inlineId=acts-category-edit");

            var wh = form.height() + 20; //Offset some paddings
            if (wh < h) {
                $('#TB_ajaxContent').height(wh);
            }
        });

        var prev_selected = $('input[name=primary_category]:checked').val();

        $(document).on('click', 'input[name=primary_category]', function (event) {
            var id = $(this).val();

            if (id != prev_selected) {
                $('input[name=primary_category][value=' + prev_selected + ']').attr('checked', false);
            }

            $('.acts-categories input[name="additional_categories[]"]').each(function (index, elem) {
                var elem_id = $(elem).val();
                if (elem_id === id) {
                    $(elem).attr('checked', false);
                } else if (elem_id === prev_selected) {
                    $(elem).attr('checked', true);
                }
            });

            prev_selected = id;
        });

        $(document).on('click', 'input[name="additional_categories[]"]', function (event) {
            $('input[name=primary_category][value=' + $(this).val() + ']').attr('checked', false);
        });

        $('#save_category').click(function (event) {
            event.preventDefault();

            var form = $('.acts-category-edit');

            $.post(form.attr('action'), form.serialize(), function (rep) {
                if (rep.success) {
                    var id = rep.data.id;
                    term_data[id].name = rep.data.name;
                    term_data[id].parent = rep.data.parent;
                    term_data[id].desc = rep.data.desc;

                    $('option[value=' + id + ']').html(rep.data.name);
                    $('a[tid=' + id + '] span:first').html(rep.data.name);
                    tb_remove();
                } else {
                    console.error(response.data);
                }
            }, 'json');
        });

        $('#delete_category').click(function (event) {
            event.preventDefault();

            var id = $('.acts-category-edit').find('input[name=category_id]').val();

            if (term_data[id].slug === 'uncategorized') {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'acts_delete_cat',
                    category_id: id
                },
                dataType: 'json',
                success: function (cat) {
                    if (!cat.success) {
                        console.error(cat.data);
                        return;
                    }

                    tb_remove();
                    $('option[value=' + id + ']').remove();
                    $('a[tid=' + id + ']').parent('td').parent('tr').remove();

                    for (var term_id in cat.data) {
                        term_data[term_id].parent = cat.data[term_id];
                    }
                },
                error: function (jqXHR, text, error) {
                    console.error(error);
                }
            });
        });

        var acts_min_sessions = 1;
        var acts_max_sessions = 50;

        var session_map = {};
        $('.acts-plan-textareas li').each(function (index, elem) {
            var session = $(elem).attr('session');
            var text = $(elem).find('textarea').html();

            session_map[session] = text;
        });

        function update_sessions_textareas() {
            var input = $('#plan_sessions');
            var sessions = parseInt($(input).val());

            if (isNaN(sessions)) {
                sessions = acts_min_sessions;
            } else if (sessions < acts_min_sessions) {
                sessions = acts_min_sessions;
                $(input).val(sessions);
            } else if (sessions > acts_max_sessions) {
                sessions = acts_max_sessions;
                $(input).val(sessions);
            }

            var last_session = parseInt($('.acts-plan-textareas li').last().attr('session'));

            if (isNaN(last_session)) {
                return;
            }

            if (sessions > last_session) {
                var html = $('.acts-plan-textareas li').last().html();
                var list = $('.acts-plan-textareas');
                for (var i = last_session + 1; i <= sessions; i++) {
                    $(list).append('<li session="' + i + '">' + html + '</li>');
                    var new_li = $('.acts-plan-textareas li[session=' + i + ']');
                    new_li.find('.acts-session-text-num').html(acts_i18n_admin.session + ' ' + i);
                    var new_textarea = new_li.find('textarea');
                    $(new_textarea).attr('name', 'session_map[' + i + ']');
                    if (session_map.hasOwnProperty(i)) {
                        $(new_textarea).html(session_map[i]);
                    } else {
                        $(new_textarea).html('');
                    }
                }
            } else {
                for (var i = last_session; i > sessions; i--) {
                    $('li[session=' + i + ']').remove();
                }
            }
        }

        $('#plan_sessions').on('input', function () {
            update_sessions_textareas();
        });

        $('.acts-filters-title').click(function () {
            $(this).find('.dashicons').toggleClass('acts-filters-expand');
            $(this).find('.dashicons').toggleClass('acts-filters-collapse');
            $('#activities-filter-wrap form').toggleClass('acts-filters-hidden');
        });
    });

})(jQuery);
