/**
 * WPCalibrate Bangladesh Order Tracker - Admin Scripts
 */

(function ($) {
	'use strict';

	$(function () {
		var config = window.wpcalibrateBotAdmin || {};

		// 1. Order Meta Box: Add Shipment
		$('#wpcalibrate_add_shipment_btn').on('click', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var $wrap = $btn.closest('.wpcalibrate-metabox-wrap');
			var orderId = $wrap.data('order-id');
			var trackingNum = $.trim($('#wpcalibrate_tracking_number').val());
			var courierCode = $('#wpcalibrate_courier_code').val();
			var status = $('#wpcalibrate_status').val();
			var estDelivery = $('#wpcalibrate_est_delivery').val();
			var customUrl = $('#wpcalibrate_custom_url').val();
			var eventNote = $('#wpcalibrate_event_note').val();
			var internalNotes = $('#wpcalibrate_internal_notes').val();
			var sendEmail = $('#wpcalibrate_send_email').is(':checked') ? 1 : 0;
			var $feedback = $('#wpcalibrate_metabox_feedback');

			if (!trackingNum) {
				alert('Please enter a tracking or consignment number.');
				$('#wpcalibrate_tracking_number').focus();
				return;
			}

			$btn.prop('disabled', true).text(config.i18n.saving || 'Saving...');
			$feedback.hide().removeClass('notice-error notice-success');

			$.ajax({
				url: config.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wpcalibrate_bot_save_shipment',
					nonce: config.nonce,
					order_id: orderId,
					courier_code: courierCode,
					tracking_number: trackingNum,
					status: status,
					estimated_delivery: estDelivery,
					custom_tracking_url: customUrl,
					event_note: eventNote,
					internal_notes: internalNotes,
					send_customer_email: sendEmail,
					is_customer_visible: 1
				}
			})
			.done(function (res) {
				if (res.success) {
					// Clear form fields
					$('#wpcalibrate_tracking_number').val('');
					$('#wpcalibrate_event_note').val('');
					$('#wpcalibrate_internal_notes').val('');
					// Reload order page or meta box to display updated cards
					window.location.reload();
				} else {
					$feedback.text(res.data && res.data.message ? res.data.message : config.i18n.error).css('color', '#d63638').show();
					$btn.prop('disabled', false).text('Add Shipment');
				}
			})
			.fail(function () {
				$feedback.text(config.i18n.error || 'Request failed').css('color', '#d63638').show();
				$btn.prop('disabled', false).text('Add Shipment');
			});
		});

		// 2. Order Meta Box: Delete Shipment
		$(document).on('click', '.wpcalibrate-delete-btn', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var shipmentId = $btn.data('shipment-id');
			var $wrap = $btn.closest('.wpcalibrate-metabox-wrap');
			var orderId = $wrap.data('order-id');

			if (!confirm(config.i18n.confirm_delete || 'Are you sure you want to remove this shipment?')) {
				return;
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: config.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wpcalibrate_bot_delete_shipment',
					nonce: config.nonce,
					order_id: orderId,
					shipment_id: shipmentId
				}
			})
			.done(function (res) {
				if (res.success) {
					$('#wpcalibrate_shipment_' + shipmentId).fadeOut(300, function () {
						$(this).remove();
						if ($('.wpcalibrate-shipment-card').length === 0) {
							$('#wpcalibrate_shipments_container').html('<div class="wpcalibrate-empty-shipments"><p>No shipments have been added to this order yet.</p></div>');
						}
					});
				} else {
					alert(res.data && res.data.message ? res.data.message : config.i18n.error);
					$btn.prop('disabled', false);
				}
			})
			.fail(function () {
				alert(config.i18n.error || 'Request failed');
				$btn.prop('disabled', false);
			});
		});

		// 3. Order Meta Box: Sync Status with Courier API
		$(document).on('click', '.wpcalibrate-sync-btn', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var shipmentId = $btn.data('shipment-id');
			var $wrap = $btn.closest('.wpcalibrate-metabox-wrap');
			var orderId = $wrap.data('order-id');
			var origHtml = $btn.html();

			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Syncing...');

			$.ajax({
				url: config.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wpcalibrate_bot_sync_shipment',
					nonce: config.nonce,
					order_id: orderId,
					shipment_id: shipmentId
				}
			})
			.done(function (res) {
				if (res.success) {
					alert(res.data && res.data.message ? res.data.message : 'Status synced successfully.');
					window.location.reload();
				} else {
					alert(res.data && res.data.message ? res.data.message : config.i18n.error);
					$btn.prop('disabled', false).html(origHtml);
				}
			})
			.fail(function () {
				alert(config.i18n.error || 'Sync request failed.');
				$btn.prop('disabled', false).html(origHtml);
			});
		});

		// 4. Settings Page: Test Provider Connection
		$('.wpcalibrate-test-btn').on('click', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var providerId = $btn.data('provider');
			var $resultBox = $('#wpcalibrate_test_result_' + providerId);
			var origText = $btn.text();

			$btn.prop('disabled', true).text(config.i18n.testing || 'Testing...');
			$resultBox.removeClass('success error').hide().text('');

			var credentials = {};
			// Gather inputs for this provider
			$btn.closest('.wpcalibrate-provider-box').find('input, select').each(function () {
				var name = $(this).attr('name');
				if (name) {
					var match = name.match(/\[integrations\]\[[^\]]+\]\[([^\]]+)\]/);
					if (match && match[1]) {
						credentials[match[1]] = $(this).val();
					}
				}
			});

			$.ajax({
				url: config.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wpcalibrate_bot_test_provider',
					nonce: config.nonce,
					provider_id: providerId,
					credentials: credentials
				}
			})
			.done(function (res) {
				if (res.success) {
					$resultBox.addClass('success').text(res.data && res.data.message ? res.data.message : 'Connection successful.').show();
				} else {
					$resultBox.addClass('error').text(res.data && res.data.message ? res.data.message : 'Connection failed.').show();
				}
			})
			.fail(function () {
				$resultBox.addClass('error').text(config.i18n.error || 'Server error while testing connection.').show();
			})
			.always(function () {
				$btn.prop('disabled', false).text(origText);
			});
		});
	});
})(jQuery);
