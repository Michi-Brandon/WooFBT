<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Main class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\FrequentlyBoughtTogether
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WFBT2' ) ) {
	exit;
} // Exit if accessed directly.

if ( ! class_exists( 'YITH_WFBT2' ) ) {
	/**
	 * YITH WooCommerce Frequently Bought Together 2
	 *
	 * @since 1.0.0
	 */
	class YITH_WFBT2 {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var YITH_WFBT2
		 */
		protected static $instance;

		/**
		 * Action add to cart group
		 *
		 * @since 1.0.0
		 * @var YITH_WFBT2
		 */
		public $actionadd = 'yith_bought_together_2';

		/**
		 * Plugin version
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $version = YITH_WFBT2_VERSION;


		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return YITH_WFBT2
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 * @return mixed YITH_WFBT2_Admin | YITH_WFBT2_Frontend
		 */
		public function __construct() {

            add_action( 'before_woocommerce_init', array( $this, 'declare_wc_features_support' ) );

			// Class admin.
			if ( $this->is_admin() ) {
				// require admin class.
				require_once 'class.yith-wfbt-admin.php';
				// admin class.
				YITH_WFBT2_Admin();
			} else {
				// require frontend class.
				require_once 'class.yith-wfbt-frontend.php';
				// the class.
				YITH_WFBT2_Frontend();
			}

			require_once 'class.yith-wfbt-shortcodes.php';
			YITH_WFBT2_Shortcodes();

			add_action( 'wp_loaded', array( $this, 'add_group_to_cart' ), 20 );
		}

        /**
         * Declare support for WooCommerce features.
         *
         * @since 1.25.0
         */
        public function declare_wc_features_support() {
            if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                $init = defined( 'YITH_WFBT2_FREE_INIT' ) ? YITH_WFBT2_FREE_INIT : false;
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $init, true );
            }
        }

		/**
		 * Check if is admin
		 *
		 * @since  1.1.0
		 * @access public
		 * @return boolean
		 */
		public function is_admin() {
			$context_check = isset( $_REQUEST['context'] ) && 'frontend' === $_REQUEST['context'];//phpcs:ignore WordPress.Security.NonceVerification
			$is_admin      = is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX && $context_check );

			return apply_filters( 'yith_wfbt2_check_is_admin', $is_admin );
		}

		/**
		 * Add upselling group to cart
		 *
		 * @since  1.0.0
		 */
		public function add_group_to_cart() {

			if ( ! ( isset( $_REQUEST['action'] ) && sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) === $this->actionadd && ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), $this->actionadd ) ) ) ) {
				return;
			}

			wc_nocache_headers();

			$products_added = array();
			$message        = array();
			$offered        = isset( $_POST['offeringID'] ) ? array_map( 'absint', $_POST['offeringID'] ) : false; // phpcs:ignore
			$quantities_raw = isset( $_POST['offeringQty'] ) && is_array( $_POST['offeringQty'] ) ? wp_unslash( $_POST['offeringQty'] ) : array(); // phpcs:ignore
			$quantities     = array();
			foreach ( $quantities_raw as $product_id => $qty ) {
				$quantities[ absint( $product_id ) ] = max( 1, absint( $qty ) );
			}

			if ( empty( $offered ) ) {
				return;
			}

			$main_product = isset( $_POST['yith-wfbt2-main-product'] ) ? absint( $_POST['yith-wfbt2-main-product'] ) : absint( $_POST['offeringID'][0] ); // phpcs:ignore

			foreach ( $offered as $id ) {

				$product      = wc_get_product( $id );
				$attr         = array();
				$variation_id = '';

				if ( $product->is_type( 'variation' ) ) {
					$attr         = $product->get_variation_attributes();
					$variation_id = $product->get_id();
					$product_id   = yit_get_base_product_id( $product );
				} else {
					$product_id = yit_get_prop( $product, 'id', true );
				}

				$qty = isset( $quantities[ $id ] ) ? $quantities[ $id ] : 1;
				$cart_item_key = WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $attr );
				if ( $cart_item_key ) {
					$products_added[ $cart_item_key ] = $variation_id ? $variation_id : $product_id;
					$message[ $product_id ]           = $qty;
				}
			}

			do_action( 'yith_wfbt2_group_added_to_cart', $products_added, $main_product, $offered );

			if ( ! empty( $message ) ) {
				wc_add_to_cart_message( $message );
			}

			if ( get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ) {
				$url = wc_get_cart_url();
			} else {
				// redirect to product page.
				$url = remove_query_arg( array( 'action', '_wpnonce' ) );
			}

			wp_safe_redirect( esc_url( $url ) );
			exit;

		}

	}
}

/**
 * Unique access to instance of YITH_WFBT2 class
 *
 * @since 1.0.0
 * @return YITH_WFBT2
 */
function YITH_WFBT2() { // phpcs:ignore WordPress.NamingConventions
	return YITH_WFBT2::get_instance();
}

