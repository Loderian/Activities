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
			$('#acts_bulk_selectize').selectize({
				plugins: ['remove_button']
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
