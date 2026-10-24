/**
 * WPCalibrate Bangladesh Order Tracker - Public Scripts
 */

(function ($) {
	'use strict';

	$(function () {
		var $form = $('#wpcalibrate_lookup_form');
		if (!$form.length) {
			return;
		}

		// Smooth scroll to tracking results if loaded
		var $result = $('.wpcalibrate-result-card');
		if ($result.length) {
			$('html, body').animate({
				scrollTop: $result.offset().top - 40
			}, 400);
		}

		// Progressive enhancement: client-side field validation
		$form.on('submit', function (e) {
			var ref = $.trim($('#wpcalibrate_order_ref').val());
			var $verif = $('#wpcalibrate_verif');

			if (!ref) {
				e.preventDefault();
				alert('Please enter your Order ID or Tracking Number.');
				$('#wpcalibrate_order_ref').focus();
				return false;
			}

			if ($verif.length && !$.trim($verif.val())) {
				e.preventDefault();
				alert('Please enter your billing phone number or email for verification.');
				$verif.focus();
				return false;
			}
		});
	});
})(jQuery);
