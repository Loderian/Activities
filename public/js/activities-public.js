(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready( function() {
		$('.acts-join-form').on( 'submit', function(event) {
			event.preventDefault();

			var $form = $(this);
			function toggle_button(disable, data=false) {
				$form.children('button').attr('disabled', disable);
				if (data) {
					$form.children('button').css('min-width', '0');
					$form.children('button').html(data.text);
					$('.acts-member-count-' + data.id).each( function(index, element) {
						$(element).html(data.count);
					});
				}
				else {
					$form.children('button').css('min-width', $form.children('button').css('width'));
					$form.children('button').html('<div class="acts-loader"></div>');
				}
			}
			toggle_button(true);
			$.post( $(this).attr('action'), $(this).serialize(), function(response) {
					toggle_button(false, response.data);
				}, 'json' );
		});
	});

})( jQuery );
