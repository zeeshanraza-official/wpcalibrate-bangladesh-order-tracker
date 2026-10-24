<?php
/**
 * Customer Tracking Page & Shortcode Handler.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tracking_Page
 *
 * Handles the [wpcalibrate_order_tracking] shortcode, customer authentication,
 * guest authorization verification, Bangladesh phone number normalization,
 * anti-enumeration rate limiting, and secure tracking timeline rendering.
 */
class Tracking_Page {

	protected Settings $settings;

	protected Shipment_Repository $repository;

	public function __construct( Settings $settings, Shipment_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;

		$this->init_hooks();
	}

	protected function init_hooks(): void {
		add_shortcode( 'wpcalibrate_order_tracking', [ $this, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'woocommerce_view_order', [ $this, 'render_in_account_order_details' ], 15 );
	}

	/**
	 * Register frontend scripts and styles.
	 */
	public function register_assets(): void {
		wp_register_style(
			'wpcalibrate-bot-public',
			WPCALIBRATE_BOT_URL . 'public/css/public.css',
			[],
			WPCALIBRATE_BOT_VERSION
		);

		wp_register_script(
			'wpcalibrate-bot-public',
			WPCALIBRATE_BOT_URL . 'public/js/public.js',
			[ 'jquery' ],
			WPCALIBRATE_BOT_VERSION,
			true
		);
	}

	/**
	 * Render tracking details inside WooCommerce "My Account > View Order" screen.
	 *
	 * @param int $order_id
	 */
	public function render_in_account_order_details( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// Security: verify current user owns this order or has shop manager privileges.
		$current_user_id = get_current_user_id();
		if ( ! current_user_can( 'manage_woocommerce' ) && (int) $order->get_customer_id() !== $current_user_id ) {
			return;
		}

		$shipments = $this->repository->get_shipments( $order );
		if ( empty( $shipments ) ) {
			return;
		}

		wp_enqueue_style( 'wpcalibrate-bot-public' );
		wp_enqueue_script( 'wpcalibrate-bot-public' );

		$courier_manager = Courier_Manager::instance();
		$show_timeline   = 'yes' === $this->settings->get( 'tracking_page', 'show_timeline', 'yes' );
		$show_link       = 'yes' === $this->settings->get( 'tracking_page', 'show_courier_link', 'yes' );

		include WPCALIBRATE_BOT_PATH . 'public/views/tracking-result.php';
	}

	/**
	 * Render public tracking shortcode [wpcalibrate_order_tracking].
	 *
	 * @param array<string, mixed>|string $atts
	 * @return string
	 */
	public function render_shortcode( $atts = [] ): string {
		wp_enqueue_style( 'wpcalibrate-bot-public' );
		wp_enqueue_script( 'wpcalibrate-bot-public' );

		$require_login = 'yes' === $this->settings->get( 'tracking_page', 'require_login', 'no' );
		if ( $require_login && ! is_user_logged_in() ) {
			return sprintf(
				'<div class="wpcalibrate-alert wpcalibrate-alert-warning"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'Please log in to your account to track your orders.', 'wpcalibrate-bangladesh-order-tracker' ),
				esc_url( wc_get_page_permalink( 'myaccount' ) ),
				esc_html__( 'Login to Account', 'wpcalibrate-bangladesh-order-tracker' )
			);
		}

		$order           = null;
		$error_message   = '';
		$shipments       = [];
		$courier_manager = Courier_Manager::instance();
		$show_timeline   = 'yes' === $this->settings->get( 'tracking_page', 'show_timeline', 'yes' );
		$show_link       = 'yes' === $this->settings->get( 'tracking_page', 'show_courier_link', 'yes' );

		// Process form submission.
		if ( isset( $_REQUEST['wpcalibrate_tracking_submit'] ) ) {
			$lookup_result = $this->process_lookup();
			if ( $lookup_result['success'] ) {
				$order     = $lookup_result['order'];
				$shipments = $this->repository->get_shipments( $order );
			} else {
				$error_message = $lookup_result['message'];
			}
		}

		ob_start();
		echo '<div class="wpcalibrate-tracker-container" id="wpcalibrate_tracker_root">';
		include WPCALIBRATE_BOT_PATH . 'public/views/tracking-form.php';

		if ( $order instanceof \WC_Order ) {
			include WPCALIBRATE_BOT_PATH . 'public/views/tracking-result.php';
		}
		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * Process and securely authorize an order lookup.
	 *
	 * @return array{success: bool, order?: \WC_Order, message?: string}
	 */
	protected function process_lookup(): array {
		// 1. Verify Nonce.
		if ( ! isset( $_REQUEST['wpcalibrate_tracking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wpcalibrate_tracking_nonce'] ) ), 'wpcalibrate_tracking_lookup' ) ) {
			return [
				'success' => false,
				'message' => __( 'Security verification expired. Please refresh and try again.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		// 2. Anti-enumeration Rate Limiting per IP.
		$ip_address = $this->get_client_ip();
		if ( $this->is_rate_limited( $ip_address ) ) {
			return [
				'success' => false,
				'message' => __( 'Too many lookup attempts. Please wait a few minutes before trying again.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$order_ref  = sanitize_text_field( wp_unslash( $_REQUEST['order_reference'] ?? '' ) );
		$phone_or_email = sanitize_text_field( wp_unslash( $_REQUEST['verification_credential'] ?? '' ) );

		if ( empty( $order_ref ) ) {
			return [
				'success' => false,
				'message' => __( 'Please enter your Order ID or Tracking Number.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		// 3. Resolve WooCommerce Order by Order ID or Tracking Number.
		$order = null;
		if ( is_numeric( $order_ref ) ) {
			$order = wc_get_order( (int) $order_ref );
		}

		if ( ! $order instanceof \WC_Order ) {
			// Try lookup by tracking number index.
			$order = $this->repository->find_order_by_tracking_number( $order_ref );
		}

		// 4. Verify Authorization.
		// If order doesn't exist, record failed attempt and return generic safe error message.
		if ( ! $order instanceof \WC_Order ) {
			$this->record_failed_attempt( $ip_address );
			return [
				'success' => false,
				'message' => __( 'No matching order could be found with the provided information. Please check your reference and phone number.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		// If user is logged in and is the customer of this order, permit access.
		$current_user_id = get_current_user_id();
		if ( $current_user_id > 0 && (int) $order->get_customer_id() === $current_user_id ) {
			$this->reset_rate_limit( $ip_address );
			return [
				'success' => true,
				'order'   => $order,
			];
		}

		// For guest lookups: Require verification against billing phone OR billing email.
		if ( empty( $phone_or_email ) ) {
			$this->record_failed_attempt( $ip_address );
			return [
				'success' => false,
				'message' => __( 'Please provide your billing phone number or email address for verification.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$billing_email = strtolower( trim( (string) $order->get_billing_email() ) );
		$billing_phone = $this->normalize_phone_number( (string) $order->get_billing_phone() );
		$input_phone   = $this->normalize_phone_number( $phone_or_email );
		$input_email   = strtolower( trim( $phone_or_email ) );

		$email_matches = ( ! empty( $billing_email ) && hash_equals( $billing_email, $input_email ) );
		$phone_matches = ( ! empty( $billing_phone ) && hash_equals( $billing_phone, $input_phone ) );

		if ( $email_matches || $phone_matches ) {
			$this->reset_rate_limit( $ip_address );
			return [
				'success' => true,
				'order'   => $order,
			];
		}

		// Verification failed: record failure to prevent brute forcing.
		$this->record_failed_attempt( $ip_address );

		return [
			'success' => false,
			'message' => __( 'No matching order could be found with the provided information. Please verify your order number and phone number.', 'wpcalibrate-bangladesh-order-tracker' ),
		];
	}

	/**
	 * Normalize Bangladesh phone numbers to 11 digits: 01XXXXXXXXX.
	 * Handles +8801..., 8801..., spaces, dashes, parentheses.
	 *
	 * @param string $phone
	 * @return string
	 */
	public static function normalize_phone_number( string $phone ): string {
		$clean = preg_replace( '/[^0-9]/', '', $phone );
		if ( empty( $clean ) ) {
			return '';
		}

		// If starts with 880 (e.g. 88017XXXXXXXX), strip 88.
		if ( str_starts_with( $clean, '8801' ) && strlen( $clean ) >= 13 ) {
			$clean = substr( $clean, 2 );
		}

		return $clean;
	}

	/**
	 * Retrieve client IP address with proxy header validation.
	 *
	 * @return string
	 */
	protected function get_client_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '127.0.0.1';
	}

	/**
	 * Check if client IP is currently rate-limited.
	 *
	 * @param string $ip
	 * @return bool
	 */
	protected function is_rate_limited( string $ip ): bool {
		$key   = 'wpcalibrate_bot_rl_' . md5( $ip );
		$fails = (int) get_transient( $key );
		$max   = (int) $this->settings->get( 'tracking_page', 'rate_limit_max_lookup', 15 );

		return $fails >= $max;
	}

	/**
	 * Increment failed lookup counter for IP address.
	 *
	 * @param string $ip
	 */
	protected function record_failed_attempt( string $ip ): void {
		$key         = 'wpcalibrate_bot_rl_' . md5( $ip );
		$window_mins = (int) $this->settings->get( 'tracking_page', 'rate_limit_window_mins', 15 );
		$fails       = (int) get_transient( $key );

		set_transient( $key, $fails + 1, $window_mins * MINUTE_IN_SECONDS );
	}

	/**
	 * Reset rate-limiting counter upon successful authorized lookup.
	 *
	 * @param string $ip
	 */
	protected function reset_rate_limit( string $ip ): void {
		delete_transient( 'wpcalibrate_bot_rl_' . md5( $ip ) );
	}
}
