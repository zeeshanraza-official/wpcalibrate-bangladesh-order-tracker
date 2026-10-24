<?php
/**
 * Customer Tracking Lookup Form View.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables available from caller:
 *
 * @var string $error_message
 */

$entered_ref   = isset( $_REQUEST['order_reference'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order_reference'] ) ) : '';
$entered_phone = isset( $_REQUEST['verification_credential'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['verification_credential'] ) ) : '';
?>

<div class="wpcalibrate-track-card wpcalibrate-form-card">
	<div class="wpcalibrate-card-header">
		<h2 class="wpcalibrate-form-title"><?php esc_html_e( 'Track Your Order', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
		<p class="wpcalibrate-form-desc"><?php esc_html_e( 'Enter your order reference and billing phone number to see real-time shipment updates.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
	</div>

	<?php if ( ! empty( $error_message ) ) : ?>
		<div class="wpcalibrate-alert wpcalibrate-alert-error" role="alert" aria-live="polite">
			<span class="wpcalibrate-alert-icon">&#9888;</span>
			<span><?php echo esc_html( $error_message ); ?></span>
		</div>
	<?php endif; ?>

	<form method="post" action="" class="wpcalibrate-lookup-form" id="wpcalibrate_lookup_form" novalidate>
		<?php wp_nonce_field( 'wpcalibrate_tracking_lookup', 'wpcalibrate_tracking_nonce' ); ?>

		<div class="wpcalibrate-field-group">
			<label for="wpcalibrate_order_ref" class="wpcalibrate-label">
				<?php esc_html_e( 'Order ID or Tracking Number', 'wpcalibrate-bangladesh-order-tracker' ); ?>
				<span class="wpcalibrate-required" aria-hidden="true">*</span>
			</label>
			<input type="text"
				   id="wpcalibrate_order_ref"
				   name="order_reference"
				   value="<?php echo esc_attr( $entered_ref ); ?>"
				   class="wpcalibrate-input"
				   placeholder="<?php esc_attr_e( 'e.g. 1024 or 15BAEB8A', 'wpcalibrate-bangladesh-order-tracker' ); ?>"
				   required
				   autocomplete="off">
		</div>

		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="wpcalibrate-field-group">
				<label for="wpcalibrate_verif" class="wpcalibrate-label">
					<?php esc_html_e( 'Billing Phone Number or Email', 'wpcalibrate-bangladesh-order-tracker' ); ?>
					<span class="wpcalibrate-required" aria-hidden="true">*</span>
				</label>
				<input type="text"
					   id="wpcalibrate_verif"
					   name="verification_credential"
					   value="<?php echo esc_attr( $entered_phone ); ?>"
					   class="wpcalibrate-input"
					   placeholder="<?php esc_attr_e( 'e.g. 01712345678 or customer@domain.com', 'wpcalibrate-bangladesh-order-tracker' ); ?>"
					   required
					   autocomplete="tel">
				<small class="wpcalibrate-hint"><?php esc_html_e( 'For verification, enter the phone number or email used during checkout.', 'wpcalibrate-bangladesh-order-tracker' ); ?></small>
			</div>
		<?php endif; ?>

		<div class="wpcalibrate-form-actions">
			<button type="submit" name="wpcalibrate_tracking_submit" value="1" class="wpcalibrate-btn wpcalibrate-btn-primary">
				<span><?php esc_html_e( 'Track Shipment', 'wpcalibrate-bangladesh-order-tracker' ); ?></span>
				<span class="wpcalibrate-btn-arrow">&rarr;</span>
			</button>
		</div>
	</form>
</div>
