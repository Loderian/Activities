(function( $ ) {
  'use strict';

  $(document).ready( function() {
    var show = true;
    $('#acts-show-nice-settings').on( 'click', function() {
      console.log('click');
      if (show) {
        $('#acts-nice-settings').css('transform', 'none');
      }
      else {
        $('#acts-nice-settings').css('transform', 'translate(100%, 0)');
      }
      show = !show;
    });


    //Activity nice logo control
    if ( $('#acts-nice-settings').length ) {
      if ( $('#acts-nice-logo').attr( 'src' ) == '' ) {
        $('#acts-nice-logo').hide();
      }

      function on_image_load() {
        imagesLoaded( document.querySelector('#acts-nice-logo'), function() {
          $('#acts-nice-info').css('min-height', $('#acts-nice-logo').height());
        });
      }

      on_image_load();

      var file_frame;

      $('#acts_upload_nice_logo').on( 'click', function( event ) {
        event.preventDefault();

        var selected = parseInt( $('#acts_nice_logo_id').val() );

        if ( file_frame ) {
          file_frame.on('open', function() {
            if ( selected ) {
              var selection = file_frame.state().get('selection');
              selection.add(wp.media.attachment(selected));
            }
          });
          file_frame.open();
          return;
        }

        file_frame = wp.media.frames.file_frame = wp.media({
          title: acts_i18n.select_img_title,
          library: {
            type: 'image',
          },
          multiple: false
        });

        file_frame.on( 'open', function() {
          if ( selected ) {
            var selection = file_frame.state().get('selection');
            selection.add(wp.media.attachment(selected));
          }
        });

        file_frame.on( 'select', function() {
          var attachment = file_frame.state().get('selection').first().toJSON();

          $('#acts-nice-logo').attr('src', attachment.url);
          on_image_load();
          $('#acts-nice-logo').show();
          $('#acts_nice_logo_id').val( attachment.id );
        });

        file_frame.open();
      });

      $('#acts_remove_nice_logo').on( 'click', function( event ) {
        event.preventDefault();

        $('#acts-nice-logo').attr('scr', '');
        $('#acts-nice-logo').hide();
        $('#acts_nice_logo_id').val('');
        $('#acts-nice-info').css('min-height', 0);
      });

      $('input[name=header]').on( 'input', function() {
        $('#acts-nice-header').html( $('input[name=header]').val().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'));
      });
    }

    //Activity nice info control
    if ($('#acts-nice-settings').length) {
      var activity_fields = ['start', 'end', 'short-desc', 'location', 'responsible', 'long-desc'];

      function display_func(id) {
        $('#acts-nice-' + id).toggle($('#' + id).prop('checked'));
      }

      $('#acts_nice_start_spacing').toggle( $('#start').prop('checked') && $('#end').prop('checked') );
      $('#acts_nice_location_spacing').toggle( $('#location').prop('checked') && $('#responsible').prop('checked') );

      function display_handler(id) {
        return function(event) {
          display_func(id);
          if (id == 'start' || id == 'end') {
            $('#acts_nice_start_spacing').toggle( $('#start').prop('checked') && $('#end').prop('checked') );
          }
          else if ( id == 'location' || id == 'responsible' ) {
            $('#acts_nice_location_spacing').toggle( $('#location').prop('checked') && $('#responsible').prop('checked') );
          }
        }
      }

      for (var i = 0; i < activity_fields.length; i++) {
        var id = activity_fields[i];
        display_func(id);

        $('#' + id).on( 'change', display_handler(id));
      }
    }

    //Activity nice members control
    if ($('#acts-nice-settings').length) {
      var prev_times;

      function append_checkbox_html(start, end) {
        $('div.acts-nice-members-time').each( function(index, elem) {
          var id = $(elem).attr('uid');
          var attended = '';
          if (all_member_info.hasOwnProperty(id)) {
            attended = all_member_info[id]['acts_attended'];
          }
          for (var i = start; i < end; i++) {
            var checked = '';
            if (i < attended.length && attended.charAt(i) == '1') {
              checked = 'checked="checked"';
            }
            $(elem).append('<input type="checkbox" name="time[' + id + '][' + i + ']" time=' + i + ' ' + checked + '/>')
          }
        });
      }

      function update_sessions() {
        var times = parseInt($('#time-slots').val());
        var max = parseInt($('#time-slots').attr('max'));
        var exist = -1;
        if ($('.acts-nice-members-time').length) {
          exist = $('input[time]:last').attr('time');
        }
        else {
          return;
        }

        if (isNaN(times)) {
          times = 0;
        }
        else if (times > max) {
          times = max;
          $('#time-slots').val(times);
        }
        else if (times < 0) {
          times = 0;
          $('#time-slots').val(times);
        }

        if (prev_times == times || exist == times - 1) {
          return;
        }
        if (times > prev_times) {
          append_checkbox_html(prev_times, times);
        }
        else if (prev_times > times) {
          for (var i = prev_times - 1; i >= times; i--) {
            $('input[type="checkbox"][time=' + i + ']').remove();
          }
        }
        else {
          //If prev_times is NaN (page refresh)
          $('div.acts-nice-members-time').html('');
          append_checkbox_html(0, times);
        }

        prev_times = times;
      }

      $('#time-slots').on( 'input', function() {
        update_sessions();
      });

      function mark_session(mark) {
        var time = parseInt($('#acts-time-mark').val());
        if (isNaN(time) || time < 1) {
          time = 1;
        }

        $('input[time=' + (time - 1) + ']').attr('checked', mark);
        if (mark) {
          time++;
        }
        else {
          time--;
        }
        if ($('input[time=' + (time - 1) + ']').length) {
          $('#acts-time-mark').val(time);
        }
      }

      $('#mark_session_on').on( 'click', function(event) {
        event.preventDefault();

        mark_session(true);
      });

      $('#mark_session_off').on( 'click', function(event) {
        event.preventDefault();

        mark_session(false);
      });

      function checkWl(elem) {
        var valid = true;
        $(elem).val().split(',').forEach( function(str) {
          if (!meta_whitelist.has(str.trim())) {
            $(elem).css('border-color', 'red');
            $(elem).css('background-color', 'rgba(201, 76, 76, 0.3)');
            valid = false;
          }
        });
        if (valid) {
          $(elem).css('border-color', 'green');
          $(elem).css('background-color', 'rgba(63, 191, 63, 0.3)');
        }
      }

      function change_color(elem, color = '') {
        var text = $(elem).closest('li').children('input[name="nice_color_key[]"]').val();
        if (color === '') {
          color = $(elem).val();
        }
        text.split(',').forEach( function(str) {
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
        $('input[name="nice_color[]"]').each( function(index, elem) {
          change_color(elem);
        });
      }

      if ($('#acts-nice-color').length) {
        var html_color = '<li><input type="text" value="" name="nice_color[]" />';
        html_color += '<input type="text" name="nice_color_key[]" value="" />';
        html_color += ' <input type="submit" name="delete_color" value="-" class="delete-color button" />';
        html_color += '</li>';

        $('input[name="nice_color_key[]"]').each( function(index, elem) {
          checkWl(elem);
          $(elem).on( 'input', function() {
            checkWl(elem);
          });
        });

        function add_color_control() {
          $('input[type=text][name="nice_color[]"]').wpColorPicker({
            change: function(event, ui) {
              change_color(event.target, ui.color.toString());
            }
          });
        }

        add_color_control();

        $('#add-color').on( 'click', function( event ) {
          event.preventDefault();

          $('#acts-nice-color').append(html_color);
          add_color_control();

          var elem = $('#acts-nice-color').children().last('li').children('input[name="nice_color_key[]"]');
          $(elem).on( 'input', function() {
            checkWl(elem);
          });
        });

        $(document).on( 'click', 'input[type=submit][name=delete_color]', function( event ) {
          event.preventDefault();
          var text = $(this).siblings('input').val();
          if ( $('.acts-nice-custom-' + text).length ) {
            $('.acts-nice-custom-' + text).css('background-color', '');
          }
          $(this).parent('li').remove();
        });
      }

      if ($('#acts-nice-custom').length) {
        var html_custom = '<li><input type="text" name="nice_custom[]" />';
        html_custom +=	'<select name="nice_custom_col[]">';
        html_custom +=	'<option value="1">Column 1</option>';
        html_custom +=	'<option value="2">Column 2</option>';
        html_custom +=	'</select>';
        html_custom +=	' <input type="submit" name="delete_custom" value="-" class="delete-custom button" /></li>';

        $('input[name="nice_custom[]"]').each( function(index, elem) {
          checkWl(elem);
          $(elem).on( 'input', function() {
            checkWl(elem);
          });
        });

        $('#add-custom').on( 'click', function( event ) {
          event.preventDefault();

          $('#acts-nice-custom').append(html_custom);

          var elem = $('#acts-nice-custom').children().last('li').children('input[name="nice_custom[]"]');
          elem.on( 'input', function() {
            checkWl(elem);
          });
        });

        $(document).on( 'click', 'input[type=submit][name=delete_custom]', function( event ) {
          event.preventDefault();

          $(this).parent('li').remove();
        });
      }

      var link = $('#acts-nice-user-link').attr('href');

      $('.acts-user-quick-edit').click( function( event ) {
        event.preventDefault();

        var id = $(this).attr('uid');
        if (!all_member_info.hasOwnProperty(id)) {
          return;
        }
        $('#acts-nice-user-link').attr('href', link + '?user_id=' + id);
        var user_info = all_member_info[id];

        $('input[name=uid]').val(id);
        for(var key in user_info) {
          if (key == 'acts_full_name') {
            continue;
          }
          else if (key == 'acts_user_avatar') {
            $('#acts-user-avatar').attr('src', '');
            $('.acts-quick-img-wrap .acts-nice-loader').show();
            $('#acts-user-avatar').attr('src', user_info[key]);
            imagesLoaded( document.querySelector('#acts-user-avatar'), function() {
              $('.acts-quick-img-wrap .acts-nice-loader').hide();
            });
          }
          else if ($('#acts-quick-' + key).length) {
            if ($('#acts-quick-' + key).is('select') && $('#acts-quick-' + key).attr('class') === 'selectized') {
              var sel = $('#acts-quick-' + key).eq(0).data('selectize');
              if (sel) {
                sel.setValue(user_info[key], true);
                continue;
              }
            }

            $('#acts-quick-' + key).val(user_info[key]);
          }
        }

        var h = window.innerHeight * 0.90;
        var w = window.innerWidth * 0.90;
        if ( w > 650 ) {
          w = 650;
        }

        tb_show(all_member_info[id].acts_full_name, "#TB_inline?height=" + h + "&amp;width=" + w + "&amp;inlineId=acts-quick-user-edit");

        var wh = $('.acts-quick-edit-box').height();
        if ( wh < h ) {
          $('#TB_ajaxContent').height(wh);
        }
      });

      $('.acts-quick-edit-box').on( 'submit', function( event ) {
        event.preventDefault();

        $.post( $(this).attr('action'), $(this).serialize(), function(response) {
            if (response.success) {
              var user_info = response.data;
              var id = user_info['ID'];
              delete user_info['ID'];
              update_user_info(id, user_info);
              write_member_info(new Set([id]));
              tb_remove();
            }
            else {
              console.error('An error occured updating user.');
            }
          }, 'json' );
      });

      var id = parseInt($('#item-id').val());

      var all_member_info = {};

      function load_custom_fields() {
        if ( $('input[type=text][name="nice_custom[]"]').length ) {
          var custom_fields = {};

          $('input[type=text][name="nice_custom[]"]').each( function( index, element ) {
            custom_fields[index] = {
              name: $(element).val(),
              col: $(element).siblings('select[name="nice_custom_col[]"]').val()
            }
          });

          return custom_fields;
        }
        else {
          return {};
        }
      }

      function disable_member_info_controls(disable) {
        $('#acts-reload-members').attr('disabled', disable);
        $('#time-slots').attr('disabled', disable);
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
        for (var key in new_info) {
          all_member_info[id][key] = new_info[key];
        }
      }

      function load_member_info(write) {
        disable_member_info_controls(true);
        var info_type = $('input[name=member_info]:checked').val();
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'acts_get_member_info',
            item_id: id,
            custom: load_custom_fields()
          },
          dataType: 'json',
          success: function(member_info) {
            if (!member_info.success) {
              console.error('Could not load user info');
              return;
            }
            for(var id in member_info.data) {
              update_user_info(id, member_info.data[id]);
            }
            if ( write ) {
              write_member_info(new Set());
            }

            update_sessions();
          },
          error: function(jqXHR, text, error) {
            console.error(text);
          },
          complete: function() {
            disable_member_info_controls(false);
          }
        });
      }

      load_member_info(false);

      //Column 1
      var prepared_keys_1 = {
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
      var prepared_keys_2 = {
        wp: [],
        bill: [
          'billing_phone'
        ],
        ship: []
      };

      function write_prep_col(user_info, col, list) {
        col.html('');

        for(var i in list) {
          var val = user_info[list[i]];
          switch (list[i]) {
            case 'billing_city':
            case 'shipping_city':

              val += ' ' + user_info[list[parseInt(i)+1]];
              if (val.trim() != '') {
                col.append('<li>' + val + '</li>');
              }
              break;

            case 'billing_postcode':
            case 'shipping_postcode':
              break;

            default:
              col.append('<li>' + val + '</li>');
              break;
          }
        }
      }

      function write_custom_col(user_info, col, custom_fields) {
        col.html('');

        for(var i in custom_fields) {
          var list = custom_fields[i];
          var display_list = [];

          for(var r in list) {
            var key = list[r].trim();
            if (user_info.hasOwnProperty(key)) {
              var val = user_info[key];
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

      function write_member_info(users) {
        if (all_member_info.length == 0) {
          return;
        }
        var type = $('input[name=member_info]:checked').val();
        var custom_input = load_custom_fields();
        var custom_fields = {
          1: [],
          2: []
        }
        for(var i in custom_input) {
          if (custom_input.hasOwnProperty(i)) {
            custom_fields[custom_input[i]['col']].push(custom_input[i]['name'].split(','));
          }
        }

        for(var id in all_member_info) {
          if (users.size > 0 && !users.has(parseInt(id))) {
            continue;
          }
          if (all_member_info.hasOwnProperty(id)) {
            var col1 = $('#col1-id' + id);
            var col2 = $('#col2-id' + id);
            var user_info = all_member_info[id];

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

      $('input[type=radio][name=member_info]').on( 'change', function () {
        var type = $('input[name=member_info]:checked').val();
        if ( all_member_info[type] === undefined ) {
          load_member_info(true);
        }
        else {
          write_member_info(new Set());
        }
      });

      $('#acts-reload-members').on( 'click', function (event) {
        event.preventDefault();

        load_member_info(true);
      });
    }

    //Activity nice folder print
    if ($('#folder_print').length) {
      $('#folder_print').click( function(event) {
        event.preventDefault();

        $('#acts-nice-wrap').css('padding-left', '20mm');
        window.print();
        $('#acts-nice-wrap').css('padding-left', '7mm');
      });
    }
  });
})(jQuery);
