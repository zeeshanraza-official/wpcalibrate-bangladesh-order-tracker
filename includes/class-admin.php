<?php
/**
 * Admin Management Class.
 *
 * @package WPCalibrate\BangladeshOrderTracker
 */

declare(strict_types=1);

namespace WPCalibrate\BangladeshOrderTracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Manages WordPress admin menus, settings page, WooCommerce order meta boxes,
 * HPOS list columns, and secure administrative AJAX handlers.
 */
class Admin {

	protected Settings $settings;

	protected Shipment_Repository $repository;

	public function __construct( Settings $settings, Shipment_Repository $repository ) {
		$this->settings   = $settings;
		$this->repository = $repository;

		$this->init_hooks();
	}

	protected function init_hooks(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ], 20 );
		add_action( 'admin_head', [ $this, 'render_admin_menu_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// WooCommerce Order Meta Boxes (compatible with both HPOS and legacy post meta).
		add_action( 'add_meta_boxes', [ $this, 'register_order_meta_box' ], 20, 2 );
		add_action( 'woocommerce_process_shop_order_meta', [ $this, 'save_order_meta_box_fallback' ], 10, 2 );

		// Order List tracking column (Legacy and HPOS).
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_list_column' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_order_list_column' ], 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_order_list_column' ] );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'render_order_list_column_hpos' ], 10, 2 );

		// Administrative AJAX Actions.
		add_action( 'wp_ajax_wpcalibrate_bot_save_shipment', [ $this, 'ajax_save_shipment' ] );
		add_action( 'wp_ajax_wpcalibrate_bot_delete_shipment', [ $this, 'ajax_delete_shipment' ] );
		add_action( 'wp_ajax_wpcalibrate_bot_sync_shipment', [ $this, 'ajax_sync_shipment' ] );
		add_action( 'wp_ajax_wpcalibrate_bot_test_provider', [ $this, 'ajax_test_provider' ] );
	}

	/**
	 * Output inline CSS in admin_head to strictly constrain the top-level menu icon to standard 20x20px.
	 */
	public function render_admin_menu_styles(): void {
		?>
		<style id="wpcalibrate-admin-menu-icon-style">
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image,
			#adminmenu #toplevel_page_wpcalibrate div.wp-menu-image {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image img,
			#adminmenu #toplevel_page_wpcalibrate div.wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 0 !important;
				margin: 0 !important;
				object-fit: contain !important;
				opacity: 0.85;
				transition: opacity 0.15s ease;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current div.wp-menu-image img {
				opacity: 1 !important;
			}
		</style>
		<?php
	}

	/**
	 * Register shared WPCalibrate parent menu and isolated plugin submenu.
	 */
	public function register_menus(): void {
		global $menu;

		$parent_exists = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'wpcalibrate' === $item[2] ) {
					$parent_exists = true;
					break;
				}
			}
		}

		// 1. Create or reuse top-level WPCalibrate parent menu.
		if ( ! $parent_exists ) {
			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				'manage_woocommerce',
				'wpcalibrate',
				[ $this, 'render_parent_dashboard' ],
				$this->get_menu_icon(),
				58.2
			);
		}

		// 2. Add isolated submenu for Bangladesh Order Tracker.
		add_submenu_page(
			'wpcalibrate',
			__( 'Bangladesh Order Tracker - WPCalibrate', 'wpcalibrate-bangladesh-order-tracker' ),
			__( 'BD Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ),
			'manage_woocommerce',
			'wpcalibrate-order-tracker',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Retrieve approved branding icon for dashboard menu.
	 */
	protected function get_menu_icon(): string {
		$icon_path = WPCALIBRATE_BOT_PATH . 'branding/icon-white.png';
		if ( file_exists( $icon_path ) ) {
			return WPCALIBRATE_BOT_URL . 'branding/icon-white.png';
		}
		return 'dashicons-location-alt';
	}

	/**
	 * Enqueue admin scripts & styles strictly on plugin screens and order edit pages.
	 *
	 * @param string $hook
	 */
	public function enqueue_assets( string $hook ): void {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$is_plugin_page = ( 'wpcalibrate_page_wpcalibrate-order-tracker' === $hook || false !== strpos( $screen_id, 'wpcalibrate-order-tracker' ) );
		$is_order_page  = ( 'shop_order' === $screen_id || 'woocommerce_page_wc-orders' === $screen_id );

		if ( ! $is_plugin_page && ! $is_order_page ) {
			return;
		}

		wp_enqueue_style(
			'wpcalibrate-bot-admin',
			WPCALIBRATE_BOT_URL . 'admin/css/admin.css',
			[],
			WPCALIBRATE_BOT_VERSION
		);

		wp_enqueue_script(
			'wpcalibrate-bot-admin',
			WPCALIBRATE_BOT_URL . 'admin/js/admin.js',
			[ 'jquery' ],
			WPCALIBRATE_BOT_VERSION,
			true
		);

		wp_localize_script(
			'wpcalibrate-bot-admin',
			'wpcalibrateBotAdmin',
			[
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'wpcalibrate_bot_admin_nonce' ),
				'i18n'       => [
					'confirm_delete' => __( 'Are you sure you want to remove this shipment?', 'wpcalibrate-bangladesh-order-tracker' ),
					'saving'         => __( 'Saving shipment...', 'wpcalibrate-bangladesh-order-tracker' ),
					'saved'          => __( 'Shipment saved successfully.', 'wpcalibrate-bangladesh-order-tracker' ),
					'syncing'        => __( 'Syncing status with courier API...', 'wpcalibrate-bangladesh-order-tracker' ),
					'testing'        => __( 'Testing connection...', 'wpcalibrate-bangladesh-order-tracker' ),
					'error'          => __( 'An unexpected error occurred. Please try again.', 'wpcalibrate-bangladesh-order-tracker' ),
				],
			]
		);
	}

	/**
	 * Render WPCalibrate parent dashboard overview.
	 */
	public function render_parent_dashboard(): void {
		?>
		<div class="wrap wpcalibrate-dashboard-wrap">
			<div class="wpcalibrate-header">
				<img src="<?php echo esc_url( WPCALIBRATE_BOT_URL . 'branding/icon-dark.png' ); ?>" alt="WPCalibrate" class="wpcalibrate-brand-logo" />
				<div>
					<h1><?php esc_html_e( 'WPCalibrate Dashboard', 'wpcalibrate-bangladesh-order-tracker' ); ?></h1>
					<p class="description"><?php esc_html_e( 'High-performance e-commerce and logistics solutions for WooCommerce stores.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
				</div>
			</div>
			<div class="wpcalibrate-card-grid">
				<div class="wpcalibrate-card">
					<h2><?php esc_html_e( 'Bangladesh Order Tracker', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<p><?php esc_html_e( 'Seamless shipment tracking, Bangladesh courier integrations (Steadfast, Pathao, RedX, Paperfly, Sundarban), and customer tracking portal.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-order-tracker' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Open Tracker Settings', 'wpcalibrate-bangladesh-order-tracker' ); ?>
					</a>
				</div>
				<div class="wpcalibrate-card">
					<h2><?php esc_html_e( 'Need Assistance?', 'wpcalibrate-bangladesh-order-tracker' ); ?></h2>
					<p><?php esc_html_e( 'Our engineering and support team is available via email or WhatsApp.', 'wpcalibrate-bangladesh-order-tracker' ); ?></p>
					<p>
						<strong><?php esc_html_e( 'Email:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> support@wpcalibrate.com<br>
						<strong><?php esc_html_e( 'WhatsApp:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> +447474795976<br>
						<strong><?php esc_html_e( 'Marketplace:', 'wpcalibrate-bangladesh-order-tracker' ); ?></strong> <a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">marketplace.wpcalibrate.com</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render main settings page with isolated tabs.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-bangladesh-order-tracker' ) );
		}

		// Handle settings form save.
		$notice = null;
		if ( isset( $_POST['wpcalibrate_bot_save_settings'] ) ) {
			check_admin_referer( 'wpcalibrate_bot_save_settings_action', 'wpcalibrate_bot_nonce' );
			$raw_settings = isset( $_POST['wpcalibrate_bot'] ) && is_array( $_POST['wpcalibrate_bot'] ) ? wp_unslash( $_POST['wpcalibrate_bot'] ) : [];
			$this->settings->update_all( $raw_settings );

			// Check if license update was submitted.
			if ( isset( $_POST['wpcalibrate_bot_license_key'] ) ) {
				$license_key = sanitize_text_field( wp_unslash( $_POST['wpcalibrate_bot_license_key'] ) );
				$existing    = $this->settings->get_license();
				if ( ! empty( $license_key ) && '********' !== $license_key ) {
					$this->settings->update_license(
						[
							'key'     => $license_key,
							'status'  => 'active',
							'expires' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
						]
					);
				}
			}

			$notice = [
				'type'    => 'success',
				'message' => __( 'Settings updated successfully.', 'wpcalibrate-bangladesh-order-tracker' ),
			];
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		$tabs        = [
			'overview'      => __( 'Overview', 'wpcalibrate-bangladesh-order-tracker' ),
			'general'       => __( 'General Settings', 'wpcalibrate-bangladesh-order-tracker' ),
			'tracking_page' => __( 'Tracking Page', 'wpcalibrate-bangladesh-order-tracker' ),
			'couriers'      => __( 'Couriers & URLs', 'wpcalibrate-bangladesh-order-tracker' ),
			'notifications' => __( 'Notifications', 'wpcalibrate-bangladesh-order-tracker' ),
			'integrations'  => __( 'API Integrations', 'wpcalibrate-bangladesh-order-tracker' ),
			'privacy'       => __( 'Privacy & Retention', 'wpcalibrate-bangladesh-order-tracker' ),
			'diagnostics'   => __( 'Diagnostics', 'wpcalibrate-bangladesh-order-tracker' ),
			'license'       => __( 'License', 'wpcalibrate-bangladesh-order-tracker' ),
			'support'       => __( 'Support', 'wpcalibrate-bangladesh-order-tracker' ),
		];

		if ( ! array_key_exists( $current_tab, $tabs ) ) {
			$current_tab = 'overview';
		}

		$all_settings    = $this->settings->get_all();
		$courier_manager = Courier_Manager::instance();
		$providers       = $courier_manager->get_providers();

		include WPCALIBRATE_BOT_PATH . 'admin/views/settings-page.php';
	}

	/**
	 * Register meta box on order screen for both HPOS and traditional post screens.
	 *
	 * @param string $post_type_or_screen
	 * @param \WP_Post|\WC_Order|null $post_or_order
	 */
	public function register_order_meta_box( string $post_type_or_screen, $post_or_order = null ): void {
		$screen_ids = [
			'shop_order',
			'woocommerce_page_wc-orders',
		];

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screen_ids[] = wc_get_page_screen_id( 'shop-order' );
		}

		if ( in_array( $post_type_or_screen, array_unique( $screen_ids ), true ) ) {
			add_meta_box(
				'wpcalibrate_bot_order_tracking',
				__( 'Bangladesh Shipment Tracking', 'wpcalibrate-bangladesh-order-tracker' ),
				[ $this, 'render_order_meta_box' ],
				$post_type_or_screen,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the order meta box UI.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order
	 */
	public function render_order_meta_box( $post_or_order ): void {
		$order = ( $post_or_order instanceof \WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID ?? 0 );
		if ( ! $order instanceof \WC_Order ) {
			echo '<p>' . esc_html__( 'Unable to load order.', 'wpcalibrate-bangladesh-order-tracker' ) . '</p>';
			return;
		}

		$shipments       = $this->repository->get_shipments( $order );
		$courier_manager = Courier_Manager::instance();
		$couriers        = $courier_manager->get_couriers_list();
		$statuses        = Shipment_Repository::get_statuses();

		wp_nonce_field( 'wpcalibrate_bot_order_metabox', 'wpcalibrate_bot_order_nonce' );

		include WPCALIBRATE_BOT_PATH . 'admin/views/order-metabox.php';
	}

	/**
	 * Fallback save handler if order is saved via traditional form submit.
	 *
	 * @param int $order_id
	 * @param \WP_Post|\WC_Order|null $post
	 */
	public function save_order_meta_box_fallback( int $order_id, $post = null ): void {
		if ( ! isset( $_POST['wpcalibrate_bot_order_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpcalibrate_bot_order_nonce'] ) ), 'wpcalibrate_bot_order_metabox' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// If a new quick shipment was submitted along with order save.
		if ( ! empty( $_POST['wpcalibrate_bot_quick_tracking'] ) ) {
			$tracking = sanitize_text_field( wp_unslash( $_POST['wpcalibrate_bot_quick_tracking'] ) );
			$courier  = sanitize_key( wp_unslash( $_POST['wpcalibrate_bot_quick_courier'] ?? 'steadfast' ) );
			$status   = sanitize_key( wp_unslash( $_POST['wpcalibrate_bot_quick_status'] ?? 'shipped' ) );

			$this->repository->save_shipment(
				$order_id,
				[
					'courier_code'    => $courier,
					'tracking_number' => $tracking,
					'status'          => $status,
					'event_note'      => __( 'Created from Order details screen.', 'wpcalibrate-bangladesh-order-tracker' ),
				],
				'manual'
			);
		}
	}

	/**
	 * Add "Shipment Tracking" column to order list.
	 *
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function add_order_list_column( array $columns ): array {
		$new_columns = [];
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'order_status' === $key || 'status' === $key ) {
				$new_columns['wpcalibrate_tracking'] = __( 'BD Tracking', 'wpcalibrate-bangladesh-order-tracker' );
			}
		}

		if ( ! isset( $new_columns['wpcalibrate_tracking'] ) ) {
			$new_columns['wpcalibrate_tracking'] = __( 'BD Tracking', 'wpcalibrate-bangladesh-order-tracker' );
		}

		return $new_columns;
	}

	/**
	 * Render tracking column for traditional posts screen.
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public function render_order_list_column( string $column, int $post_id ): void {
		if ( 'wpcalibrate_tracking' !== $column ) {
			return;
		}
		$order = wc_get_order( $post_id );
		if ( $order ) {
			$this->render_tracking_badges( $order );
		}
	}

	/**
	 * Render tracking column for HPOS screen.
	 *
	 * @param string $column
	 * @param \WC_Order $order
	 */
	public function render_order_list_column_hpos( string $column, \WC_Order $order ): void {
		if ( 'wpcalibrate_tracking' !== $column ) {
			return;
		}
		$this->render_tracking_badges( $order );
	}

	/**
	 * Output shipment status badges in order table.
	 *
	 * @param \WC_Order $order
	 */
	protected function render_tracking_badges( \WC_Order $order ): void {
		$shipments = $this->repository->get_shipments( $order );
		if ( empty( $shipments ) ) {
			echo '<span class="wpcalibrate-badge wpcalibrate-badge-none">' . esc_html__( 'No Tracking', 'wpcalibrate-bangladesh-order-tracker' ) . '</span>';
			return;
		}

		$courier_manager = Courier_Manager::instance();

		foreach ( $shipments as $s ) {
			$tracking = esc_html( $s['tracking_number'] ?? '' );
			$courier  = esc_html( $s['courier_name'] ?? '' );
			$status   = sanitize_key( $s['status'] ?? 'pending' );
			$label    = esc_html( $s['status_label'] ?? $status );
			$url      = $courier_manager->resolve_tracking_url( (string) ( $s['courier_code'] ?? '' ), (string) ( $s['tracking_number'] ?? '' ), (string) ( $s['custom_tracking_url'] ?? '' ) );

			echo '<div class="wpcalibrate-order-badge-row">';
			echo '<span class="wpcalibrate-badge wpcalibrate-badge-' . esc_attr( $status ) . '">' . $label . '</span> ';
			if ( ! empty( $url ) ) {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="wpcalibrate-tracking-link" title="' . esc_attr( $courier ) . '">';
				echo $tracking;
				echo '</a>';
			} else {
				echo '<span class="wpcalibrate-tracking-num" title="' . esc_attr( $courier ) . '">' . $tracking . '</span>';
			}
			echo '</div>';
		}
	}

	/**
	 * AJAX handler: Save or update a shipment on an order.
	 */
	public function ajax_save_shipment(): void {
		check_ajax_referer( 'wpcalibrate_bot_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wpcalibrate-bangladesh-order-tracker' ) ], 403 );
		}

		$order_id = absint( $_POST['order_id'] ?? 0 );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			wp_send_json_error( [ 'message' => __( 'Invalid order.', 'wpcalibrate-bangladesh-order-tracker' ) ], 400 );
		}

		$shipment_data = [
			'id'                  => sanitize_key( $_POST['shipment_id'] ?? '' ),
			'courier_code'        => sanitize_key( $_POST['courier_code'] ?? 'steadfast' ),
			'tracking_number'     => sanitize_text_field( wp_unslash( $_POST['tracking_number'] ?? '' ) ),
			'custom_tracking_url' => esc_url_raw( wp_unslash( $_POST['custom_tracking_url'] ?? '' ) ),
			'status'              => sanitize_key( $_POST['status'] ?? 'pending' ),
			'estimated_delivery'  => sanitize_text_field( wp_unslash( $_POST['estimated_delivery'] ?? '' ) ),
			'internal_notes'      => sanitize_textarea_field( wp_unslash( $_POST['internal_notes'] ?? '' ) ),
			'event_note'          => sanitize_text_field( wp_unslash( $_POST['event_note'] ?? '' ) ),
			'is_customer_visible' => ! empty( $_POST['is_customer_visible'] ),
		];

		if ( empty( $shipment_data['tracking_number'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Tracking number is required.', 'wpcalibrate-bangladesh-order-tracker' ) ], 400 );
		}

		try {
			$saved = $this->repository->save_shipment( $order, $shipment_data, 'manual' );

			// Check if notification email should be dispatched.
			if ( ! empty( $_POST['send_customer_email'] ) ) {
				$notifications = new Notifications( $this->settings, $this->repository );
				$notifications->dispatch_shipment_status_email( $order, $saved );
			}

			wp_send_json_success(
				[
					'message'  => __( 'Shipment saved successfully.', 'wpcalibrate-bangladesh-order-tracker' ),
					'shipment' => $saved,
				]
			);
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * AJAX handler: Delete a shipment.
	 */
	public function ajax_delete_shipment(): void {
		check_ajax_referer( 'wpcalibrate_bot_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wpcalibrate-bangladesh-order-tracker' ) ], 403 );
		}

		$order_id    = absint( $_POST['order_id'] ?? 0 );
		$shipment_id = sanitize_key( $_POST['shipment_id'] ?? '' );

		if ( empty( $order_id ) || empty( $shipment_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing required parameters.', 'wpcalibrate-bangladesh-order-tracker' ) ], 400 );
		}

		$deleted = $this->repository->delete_shipment( $order_id, $shipment_id );
		if ( $deleted ) {
			wp_send_json_success( [ 'message' => __( 'Shipment removed.', 'wpcalibrate-bangladesh-order-tracker' ) ] );
		}

		wp_send_json_error( [ 'message' => __( 'Shipment could not be found or removed.', 'wpcalibrate-bangladesh-order-tracker' ) ], 404 );
	}

	/**
	 * AJAX handler: Sync shipment status live with courier API.
	 */
	public function ajax_sync_shipment(): void {
		check_ajax_referer( 'wpcalibrate_bot_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wpcalibrate-bangladesh-order-tracker' ) ], 403 );
		}

		$order_id    = absint( $_POST['order_id'] ?? 0 );
		$shipment_id = sanitize_key( $_POST['shipment_id'] ?? '' );
		$order       = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			wp_send_json_error( [ 'message' => __( 'Invalid order.', 'wpcalibrate-bangladesh-order-tracker' ) ], 400 );
		}

		$shipment = $this->repository->get_shipment( $order, $shipment_id );
		if ( ! $shipment ) {
			wp_send_json_error( [ 'message' => __( 'Shipment record not found.', 'wpcalibrate-bangladesh-order-tracker' ) ], 404 );
		}

		$courier_code    = $shipment['courier_code'] ?? '';
		$tracking_number = $shipment['tracking_number'] ?? '';
		$provider        = Courier_Manager::instance()->get_provider( $courier_code );

		if ( ! $provider || ! $provider->supports_api() ) {
			wp_send_json_error( [ 'message' => __( 'Automated API tracking is not supported for this courier.', 'wpcalibrate-bangladesh-order-tracker' ) ], 400 );
		}

		$credentials = $this->settings->get( 'integrations', $courier_code, [] );
		$result      = $provider->track_shipment( $tracking_number, (array) $credentials );

		if ( ! $result['success'] ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: %s: error reason */
						__( 'Courier API sync error: %s', 'wpcalibrate-bangladesh-order-tracker' ),
						$result['error_message'] ?? $result['status_message']
					),
				]
			);
		}

		// Update shipment with new status and append timeline event.
		$new_status = $result['normalized_status'];
		$this->repository->add_status_event(
			$order,
			$shipment_id,
			$new_status,
			$result['status_message'],
			$courier_code . '_api',
			true
		);

		$updated_shipment = $this->repository->get_shipment( $order, $shipment_id );

		wp_send_json_success(
			[
				'message'  => sprintf(
					/* translators: 1: courier name, 2: status message */
					__( 'Synced with %1$s: %2$s', 'wpcalibrate-bangladesh-order-tracker' ),
					$provider->get_name(),
					$result['status_message']
				),
				'shipment' => $updated_shipment,
			]
		);
	}

	/**
	 * AJAX handler: Test provider connection credentials from settings tab.
	 */
	public function ajax_test_provider(): void {
		check_ajax_referer( 'wpcalibrate_bot_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wpcalibrate-bangladesh-order-tracker' ) ], 403 );
		}

		$provider_id = sanitize_key( $_POST['provider_id'] ?? '' );
		$provider    = Courier_Manager::instance()->get_provider( $provider_id );

		if ( ! $provider ) {
			wp_send_json_error( [ 'message' => __( 'Unknown courier provider.', 'wpcalibrate-bangladesh-order-tracker' ) ], 404 );
		}

		$credentials = isset( $_POST['credentials'] ) && is_array( $_POST['credentials'] ) ? wp_unslash( $_POST['credentials'] ) : [];
		// If credentials are empty or masked, fetch stored credentials.
		$stored = $this->settings->get( 'integrations', $provider_id, [] );
		foreach ( (array) $stored as $k => $v ) {
			if ( empty( $credentials[ $k ] ) || '********' === $credentials[ $k ] ) {
				$credentials[ $k ] = $v;
			}
		}

		$result = $provider->test_connection( (array) $credentials );
		if ( $result['success'] ) {
			wp_send_json_success( [ 'message' => $result['message'] ] );
		} else {
			wp_send_json_error( [ 'message' => $result['message'] ] );
		}
	}
}
