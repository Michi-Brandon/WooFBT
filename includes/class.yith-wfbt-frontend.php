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

				$image_size = apply_filters( 'yith_wcfbt2_image_size', 'yith_wfbt_image_size' );
				$prepared_categories = array();
				foreach ( $categories as $category ) {
					$category_name  = isset( $category['name'] ) ? $category['name'] : '';
					$category_image = isset( $category['image_id'] ) ? absint( $category['image_id'] ) : 0;

					$raw_types = isset( $category['types'] ) && is_array( $category['types'] ) ? $category['types'] : array();
					if ( empty( $raw_types ) ) {
						$legacy_qty_map = isset( $category['qty'] ) && is_array( $category['qty'] ) ? $category['qty'] : array();
						$legacy_products = isset( $category['products'] ) ? $category['products'] : array();
						if ( is_string( $legacy_products ) ) {
							$legacy_products = explode( ',', $legacy_products );
						}
						$legacy_products = array_filter( array_map( 'absint', (array) $legacy_products ) );

						if ( empty( $legacy_products ) && isset( $category['items'] ) && is_array( $category['items'] ) ) {
							foreach ( $category['items'] as $legacy_item ) {
								$legacy_product_id = isset( $legacy_item['product_id'] ) ? absint( $legacy_item['product_id'] ) : 0;
								$legacy_qty        = isset( $legacy_item['qty'] ) ? max( 1, absint( $legacy_item['qty'] ) ) : 1;
								if ( ! $legacy_product_id ) {
									continue;
								}
								$legacy_products[]                   = $legacy_product_id;
								$legacy_qty_map[ $legacy_product_id ] = $legacy_qty;
							}
						}

						$legacy_products = array_values( array_unique( $legacy_products ) );
						if ( ! empty( $legacy_products ) ) {
							$raw_types[] = array(
								'name'     => '',
								'products' => $legacy_products,
								'qty'      => $legacy_qty_map,
								'trigger_product_id' => 0,
							);
						}
					}

					$prepared_types = array();
					$fallback_image = '';

					foreach ( $raw_types as $raw_type ) {
						$type_name = isset( $raw_type['name'] ) ? $raw_type['name'] : '';
						$type_qty  = isset( $raw_type['qty'] ) && is_array( $raw_type['qty'] ) ? $raw_type['qty'] : array();
						$type_trigger_product = isset( $raw_type['trigger_product_id'] ) ? $raw_type['trigger_product_id'] : 0;
						if ( is_array( $type_trigger_product ) ) {
							$type_trigger_product = reset( $type_trigger_product );
						}
						$type_trigger_product_id = absint( $type_trigger_product );
						$type_trigger_map = isset( $raw_type['trigger'] ) && is_array( $raw_type['trigger'] ) ? $raw_type['trigger'] : array();
						$type_trigger_extra_map = isset( $raw_type['trigger_extra_qty'] ) && is_array( $raw_type['trigger_extra_qty'] ) ? $raw_type['trigger_extra_qty'] : array();

						$type_products = isset( $raw_type['products'] ) ? $raw_type['products'] : array();
						if ( is_string( $type_products ) ) {
							$type_products = explode( ',', $type_products );
						}
						$type_products = array_filter( array_map( 'absint', (array) $type_products ) );

						if ( empty( $type_products ) && isset( $raw_type['items'] ) && is_array( $raw_type['items'] ) ) {
							foreach ( $raw_type['items'] as $type_item ) {
								$type_product_id = isset( $type_item['product_id'] ) ? absint( $type_item['product_id'] ) : 0;
								$type_item_qty   = isset( $type_item['qty'] ) ? max( 1, absint( $type_item['qty'] ) ) : 1;
								if ( ! $type_product_id ) {
									continue;
								}
								$type_products[]              = $type_product_id;
								$type_qty[ $type_product_id ] = $type_item_qty;
							}
						}

						$type_products = array_values( array_unique( $type_products ) );
						if ( empty( $type_products ) ) {
							continue;
						}

						$type_options      = array();
						$default_option_id = 0;
						$type_is_conditional = false;

						foreach ( $type_products as $type_product_id ) {
							$current = wc_get_product( $type_product_id );
							if ( ! $current ) {
								continue;
							}

							$qty = isset( $type_qty[ $type_product_id ] ) ? absint( $type_qty[ $type_product_id ] ) : 1;
							$qty = max( 0, $qty );
							$raw_option_trigger_ids = isset( $type_trigger_map[ $type_product_id ] ) ? $type_trigger_map[ $type_product_id ] : array();
							if ( ! is_array( $raw_option_trigger_ids ) ) {
								$raw_option_trigger_ids = explode( ',', strval( $raw_option_trigger_ids ) );
							}
							$option_trigger_product_ids = array_filter( array_map( 'absint', array_map( 'sanitize_text_field', (array) $raw_option_trigger_ids ) ) );
							$option_trigger_product_ids = array_values( array_unique( $option_trigger_product_ids ) );
							if ( empty( $option_trigger_product_ids ) && $type_trigger_product_id ) {
								$option_trigger_product_ids[] = $type_trigger_product_id;
							}
							$option_trigger_product_id = ! empty( $option_trigger_product_ids ) ? absint( $option_trigger_product_ids[0] ) : 0;
							$option_trigger_extra_qty = isset( $type_trigger_extra_map[ $type_product_id ] ) ? absint( $type_trigger_extra_map[ $type_product_id ] ) : 0;
							$option_trigger_extra_qty = max( 0, $option_trigger_extra_qty );

							if ( ! $fallback_image ) {
								$fallback_image = $current->get_image( $image_size );
							}

							$is_available = $current->is_purchasable() && $current->is_in_stock();
							if ( ! $default_option_id && $is_available ) {
								$default_option_id = $type_product_id;
							}

							$type_options[] = array(
								'product_id' => $type_product_id,
								'qty'        => $qty,
								'available'  => $is_available,
								'trigger_product_id' => $option_trigger_product_id,
								'trigger_product_ids' => $option_trigger_product_ids,
								'trigger_extra_qty' => $option_trigger_extra_qty,
								'product'    => $current,
							);

							if ( ! empty( $option_trigger_product_ids ) || $option_trigger_extra_qty || $qty < 1 ) {
								$type_is_conditional = true;
							}
						}

						if ( empty( $type_options ) ) {
							continue;
						}

						$prepared_types[] = array(
							'name'              => $type_name,
							'default_product_id' => $default_option_id,
							'trigger_product_id' => $type_trigger_product_id,
							'conditional_mode'   => $type_is_conditional,
							'options'           => $type_options,
						);
					}

					if ( empty( $prepared_types ) ) {
						continue;
					}

					$set_image = $category_image ? wp_get_attachment_image( $category_image, $image_size ) : '';
					if ( ! $set_image ) {
						$set_image = $fallback_image;
					}

					$prepared_categories[] = array(
						'name'  => $category_name,
						'image' => $set_image,
						'types' => $prepared_types,
					);
				}

				$debug_data['prepared_categories'] = array_map(
					function( $category ) {
						return array(
							'name'  => $category['name'],
							'types' => array_map(
								function( $type ) {
									return array(
										'name'     => $type['name'],
										'default'  => $type['default_product_id'],
										'trigger'  => isset( $type['trigger_product_id'] ) ? absint( $type['trigger_product_id'] ) : 0,
										'conditional_mode' => ! empty( $type['conditional_mode'] ),
										'products' => array_map(
											function( $option ) {
												return array(
													'product_id' => $option['product_id'],
													'qty'        => $option['qty'],
													'available'  => $option['available'],
													'trigger_product_id' => isset( $option['trigger_product_id'] ) ? absint( $option['trigger_product_id'] ) : 0,
													'trigger_product_ids' => isset( $option['trigger_product_ids'] ) ? array_values( array_filter( array_map( 'absint', (array) $option['trigger_product_ids'] ) ) ) : array(),
													'trigger_extra_qty' => isset( $option['trigger_extra_qty'] ) ? absint( $option['trigger_extra_qty'] ) : 0,
												);
											},
											$type['options']
										),
									);
								},
								$category['types']
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
