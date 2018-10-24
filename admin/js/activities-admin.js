(function( $ ) {
	'use strict';

	$(document).ready( function() {
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

		//Activity member options
		if ($('#acts-activity-member-list').length) {
			set_member_count();

			function set_member_count() {
				if ( $('#acts-activity-member-list').val() != null ) {
					$('#member_count').html($('#acts-activity-member-list').val().length);
				}
				else {
					$('#member_count').html('0');
				}
			}

			$('#acts-activity-member-list').selectize({
				plugins: ['remove_button'],
				onChange: function() { set_member_count() }
			});
		}

		//Activity export select activity
		if ($('#acts_select_activity_export').length) {
			$('#acts_select_activity_export').selectize({});
		}

		//Activity bulk selectize
		if ($('#acts_bulk_selectize').length) {
			$('#acts_bulk_selectize').selectize({});
		}

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

			function update_html() {
				var times = parseInt( $('#time-slots').val() );
				var max = parseInt( $('#time-slots').attr('max') );
				if ( times > max ) {
					times = max;
					$('#time-slots').val( times );
				}
				else if ( times < 0 ) {
					times = 0;
					$('#time-slots').val( times );
				}
				if ( prev_times == times ) {
					return;
				}
				if ( times > prev_times ) {
					for (var i = 0; i < times - prev_times; i++) {
						$('div.acts-nice-members-time').append( '<input type="checkbox" name="time' + (prev_times + i + 1) + '" />' );
				 	}
				}
				else if ( prev_times > times ) {
					for (var i = prev_times; i > times; i--) {
						$('input[type="checkbox"][name=time'+i+']').remove();
					}
				}
				else {
					$('div.acts-nice-members-time').each( function( index, element ) {
						$(element).html('');
					});
					for (var i = 0; i < times; i++) {
						$('div.acts-nice-members-time').append( '<input type="checkbox" name="time' + (i + 1) + '" />' );
				 	}
				}
				prev_times = times;
			}

			update_html();

			$('#time-slots').on( 'input', function() {
				update_html();
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
					return 'none';
				}
			}

			function disable_member_info_controls(disable) {
				$('#acts-reload-members').attr('disabled', disable);
				$('#use_wp_info').attr('disabled', disable);
				$('#use_wc_bill_info').attr('disabled', disable);
				$('#use_wc_ship_info').attr('disabled', disable);
				$('#add-custom').attr('disabled', disable);
				$('input[type=text][name="nice_custom[]"]').each( function( index, element ) {
					$(element).attr('disabled', disable);
				});
				$('select[name="nice_custom_col[]"]').each( function( index, element ) {
					$(element).attr('disabled', disable);
				});
				$('input[type=submit][name=delete_custom]').each( function( index, element ) {
					$(element).attr('disabled', disable);
				});
				$('#add-color').attr('disabled', disable);
				$('input[type=text][name="nice_color_key[]"]').each( function( index, element ) {
					$(element).attr('disabled', disable);
				});
				$('input[type=text][name="nice_color[]"]').each( function( index, element ) {
					$(element).attr('disabled', disable);
				});
				$('input[type=submit][name=delete_color]').each( function( index, element ) {
					$(element).attr('disabled', disable);
				});
				$('.acts-nice-loader').toggle(disable);
			}

			function load_member_info(write) {
				disable_member_info_controls(true);
				var info_type = $('input[name=member_info]:checked').val();
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acts_get_member_info',
						type: info_type,
						item_id: id,
						custom: load_custom_fields()
					},
					dataType: 'json',
					success: function(member_info) {
						if (!member_info.success) {
							return;
						}
						all_member_info[info_type] = member_info.data;
						if ( write ) {
							write_member_info();
						}
						if ($('#acts-nice-color').length) {
							reload_color();
						}
					},
					error: function(jqXHR, text, error) {
						console.log(jqXHR);
						console.log(text);
						console.log(error);
					},
					complete: function() {
						disable_member_info_controls( false );
					}
				});
			}

			load_member_info( true );

			function write_member_info() {
				var type = $('input[name=member_info]:checked').val();

				for (var id in all_member_info[type]) {
					if ( $('#col1-id' + id).length > 0 && $('#col2-id' + id).length > 0 ) {
						$('#col1-id' + id).html(all_member_info[type][id]['col1']);
						$('#col2-id' + id).html(all_member_info[type][id]['col2']);
					}
				}
			}

			$('input[type=radio][name=member_info]').on( 'change', function () {
				var type = $('input[name=member_info]:checked').val();
				if ( all_member_info[type] === undefined ) {
					load_member_info( true );
				}
				else {
					write_member_info();
				}
			});

			$('#acts-reload-members').on( 'click', function (event) {
				event.preventDefault();

				var type = $('input[name=member_info]:checked').val();
				for ( var all_types in all_member_info ) {
					if ( all_types === type ) {
						load_member_info( true );
					}
					else {
						delete all_member_info[all_types];
					}
				}
			});
		}

		//Select all in list table
		if ( !$('#activities-select-all').length ) {
			$('#activities-select-all').on( 'change', function() {
				var all_checked = $(this).prop('checked');
				$('input[name="selected_activities[]"]').each( function( index, element ) {
					$(element).prop('checked', all_checked);
				});
			});

			$('input[name="selected_activities[]"]').on( 'change', function() {
				var all_checked = true;
				$('input[name="selected_activities[]"]').each( function( index, element ) {
					all_checked = $(element).prop('checked');
					return all_checked; //false = break, true = continue
				});
				$('#activities-select-all').prop('checked', all_checked);
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

		//Show/hide columns in list tables
		if ($('#acts_name').length) {
			var columns = ['short_desc', 'long_desc', 'start', 'end', 'responsible', 'location', 'address', 'description', 'city', 'postcode', 'country'];

			function toggleColumn(column) {
				return function() {
					if ($('.colspanchange').length > 0) {
						var num = parseInt($('.colspanchange').attr('colspan'));
						if ($('#acts_' + column).prop('checked')) {
							num++;
						}
						else {
							num--;
						}
						$('.colspanchange').attr( 'colspan', num );
					}
					$('thead tr #' + column).toggleClass('hidden', !$('#acts_' + column).prop('checked'));
					$('tfoot tr #' + column).toggleClass('hidden', !$('#acts_' + column).prop('checked'));
					$('.' + column).each( function(index, element) {
						$(element).toggleClass('hidden', !$('#acts_' + column).prop('checked'));
					});
				}
			}

			for (var column of columns) {
				if ($('#acts_' + column).length > 0) {

					$('#acts_' + column).on( 'change', toggleColumn(column) );
				}
			}
		}

		//One click select and copy export
		if ($('#acts-export-results').length) {
			$('#acts-export-results').click( function() {
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
	});

})(jQuery);
