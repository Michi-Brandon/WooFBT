<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Frontend class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\FrequentlyBoughtTogether
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WFBT2' ) ) {
	exit;
} // Exit if accessed directly.

if ( ! class_exists( 'YITH_WFBT2_Frontend' ) ) {
	/**
	 * Frontend class.
	 * The class manage all the frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WFBT2_Frontend {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var YITH_WFBT2_Frontend
		 */
		protected static $instance;

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
		 * @return YITH_WFBT2_Frontend
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
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {
			// enqueue scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_shortcode( 'ywfbt2_form', array( $this, 'wfbt_shortcode' ) );
		}

		/**
		 * Enqueue scripts
		 *
		 * @since  1.0.0
		 */
		public function enqueue_scripts() {

			wp_enqueue_style( 'yith-wfbt2-style', YITH_WFBT2_ASSETS_URL . '/css/yith-wfbt.css', array(), YITH_WFBT2_VERSION );

			$background       = get_option( 'yith-wfbt2-button-color' );
			$background_hover = get_option( 'yith-wfbt2-button-color-hover' );
			$text_color       = get_option( 'yith-wfbt2-button-text-color' );
			$text_color_hover = get_option( 'yith-wfbt2-button-text-color-hover' );

			$inline_css = "
                .yith-wfbt-submit-block .yith-wfbt-submit-button {
                        background: {$background};
                        color: {$text_color};
                }
                .yith-wfbt-submit-block .yith-wfbt-submit-button:hover {
                        background: {$background_hover};
                        color: {$text_color_hover};
                }";

			wp_add_inline_style( 'yith-wfbt2-style', $inline_css );
		}

		/**
		 * Form Template
		 *
		 * @since  1.0.0
		 */
		public function add_bought_together_form( $product_id = false, $return = false ) {

            if( ! $product_id ) {
                global $product;
                $product_id = yit_get_base_product_id( $product );
            }

            $content = do_shortcode( '[ywfbt2_form product_id="' . $product_id . '"]' );


            if( $return ) {
                return $content;
            } else {
                echo $content;
            }
        }


		/**
		 * Frequently Bought Together Shortcode
		 *
		 * @since  1.0.5
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
				public function wfbt_shortcode( $atts ) {

			$atts = shortcode_atts(
				array(
					'product_id' => 0,
				),
				$atts
			);

			extract( $atts ); //phpcs:ignore WordPress.PHP.DontExtract

			$product_id = intval( $product_id );
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				// get global.
				global $product;
			}

			// if also global is empty return.
			if ( ! $product ) {
				return '';
			}

			$meta_key = defined( 'YITH_WFBT2_META' ) ? YITH_WFBT2_META : '_yith_wfbt2_ids';
			$mode_key = defined( 'YITH_WFBT2_MODE_META' ) ? YITH_WFBT2_MODE_META : '_yith_wfbt2_mode';
			$categories_key = defined( 'YITH_WFBT2_CATEGORIES_META' ) ? YITH_WFBT2_CATEGORIES_META : '_yith_wfbt2_categories';
			$mode = $product->get_meta( $mode_key, true );
			$mode = $mode ? $mode : 'simple';
			if ( 'set' === $mode ) {
				$mode = 'simple';
			}

			if ( $product->is_type( array( 'grouped', 'external' ) ) ) {
				return '';
			}

			$debug = isset( $_GET['yith_wfbt2_debug'] ) && current_user_can( 'manage_options' );
			$debug_data = array(
				'product_id' => $product->get_id(),
				'mode'       => $mode,
			);

			if ( 'categorical' === $mode ) {
				$categories = $product->get_meta( $categories_key, true );
				$debug_data['categories_meta'] = $categories;
				if ( empty( $categories ) || ! is_array( $categories ) ) {
					if ( $debug ) {
						return '<pre class="yith-wfbt2-debug">' . esc_html( print_r( $debug_data, true ) ) . '</pre>';
					}
					return '';
				}

				$prepared_categories = array();
				foreach ( $categories as $category ) {
					$category_name = isset( $category['name'] ) ? $category['name'] : '';
					$category_products = isset( $category['products'] ) ? $category['products'] : array();
					$category_match = isset( $category['match'] ) ? $category['match'] : '';
					$category_message = isset( $category['message'] ) ? $category['message'] : '';

					if ( is_string( $category_products ) ) {
						$category_products = explode( ',', $category_products );
					}
					$category_products = array_filter( array_map( 'absint', (array) $category_products ) );

					$products = array();
					foreach ( $category_products as $category_product_id ) {
						$current = wc_get_product( $category_product_id );
						if ( ! $current || ! $current->is_purchasable() || ! $current->is_in_stock() ) {
							continue;
						}
						$products[] = $current;
					}

					if ( empty( $products ) ) {
						continue;
					}

					$prepared_categories[] = array(
						'name'     => $category_name,
						'match'    => $category_match,
						'message'  => $category_message,
						'products' => $products,
					);
				}

				$debug_data['prepared_categories'] = array_map(
					function( $category ) {
						return array(
							'name'        => $category['name'],
							'product_ids' => array_map(
								function( $item ) {
									return $item->get_id();
								},
								$category['products']
							),
						);
					},
					$prepared_categories
				);

				if ( empty( $prepared_categories ) ) {
					if ( $debug ) {
						return '<pre class="yith-wfbt2-debug">' . esc_html( print_r( $debug_data, true ) ) . '</pre>';
					}
					return '';
				}

				$main_product = $product;
				if ( $product->is_type( 'variable' ) ) {

					$variations = $product->get_children();

					if ( empty( $variations ) ) {
						if ( $debug ) {
							return '<pre class="yith-wfbt2-debug">' . esc_html( print_r( $debug_data, true ) ) . '</pre>';
						}
						return '';
					}
					// get first product variation.
					$product_id   = array_shift( $variations );
					$main_product = wc_get_product( $product_id );
				}

				$products = array( $main_product );

				ob_start();

				wc_get_template(
					'yith-wfbt-form.php',
					array(
						'products'   => $products,
						'categories' => $prepared_categories,
						'mode'       => 'categorical',
					),
					'',
					YITH_WFBT2_DIR . 'templates/'
				);

				$html = ob_get_clean();
				if ( $debug ) {
					$html = '<pre class="yith-wfbt2-debug">' . esc_html( print_r( $debug_data, true ) ) . '</pre>' . $html;
				}
				return $html;
			}


			$group = $product->get_meta( $meta_key, true );
			$debug_data['set_product_ids'] = $group;
			if ( empty( $group ) ) {
				if ( $debug ) {
					return '<pre class="yith-wfbt2-debug">' . esc_html( print_r( $debug_data, true ) ) . '</pre>';
				}
				return '';
			}

			if ( $product->is_type( 'variable' ) ) {

				$variations = $product->get_children();

				if ( empty( $variations ) ) {
					return '';
				}
				// get first product variation.
				$product_id = array_shift( $variations );
				$product    = wc_get_product( $product_id );
			}

			$products = array();
			$products[] = $product;
			foreach ( $group as $the_id ) {
				$current = wc_get_product( $the_id );
				if ( ! $current || ! $current->is_purchasable() || ! $current->is_in_stock() ) {
					continue;
				}
				// add to main array.
				$products[] = $current;
			}

			ob_start();

			wc_get_template( 'yith-wfbt-form.php', array( 'products' => $products, 'mode' => 'simple' ), '', YITH_WFBT2_DIR . 'templates/' );

			$html = ob_get_clean();
			if ( $debug ) {
				$html = '<pre class="yith-wfbt2-debug">' . esc_html( print_r( $debug_data, true ) ) . '</pre>' . $html;
			}
			return $html;
		}

	}
}
/**
 * Unique access to instance of YITH_WFBT2_Frontend class
 *
 * @since 1.0.0
 * @return YITH_WFBT2_Frontend
 */
function YITH_WFBT2_Frontend() { // phpcs:ignore WordPress.NamingConventions
	return YITH_WFBT2_Frontend::get_instance();
}
