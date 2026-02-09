<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Admin class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\FrequentlyBoughtTogether
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WFBT2' ) ) {
	exit;
} // Exit if accessed directly.

if ( ! defined( 'YITH_WFBT2_META' ) ) {
	define( 'YITH_WFBT2_META', '_yith_wfbt2_ids' );
}

if ( ! defined( 'YITH_WFBT2_MODE_META' ) ) {
	define( 'YITH_WFBT2_MODE_META', '_yith_wfbt2_mode' );
}

if ( ! defined( 'YITH_WFBT2_CATEGORIES_META' ) ) {
	define( 'YITH_WFBT2_CATEGORIES_META', '_yith_wfbt2_categories' );
}

if ( ! class_exists( 'YITH_WFBT2_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WFBT2_Admin {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var \YITH_WFBT2_Admin
		 */
		protected static $instance;

		/**
		 * Plugin options
		 *
		 * @since  1.0.0
		 * @var array
		 * @access public
		 */
		public $options = array();

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
		 * @return \YITH_WFBT2_Admin
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

			// add section in product edit page.
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_bought_together_tab' ), 10, 1 );
			add_action( 'woocommerce_product_data_panels', array( $this, 'add_bought_together_panel' ) );

			// search product.
			add_action( 'wp_ajax_yith_wfbt2_ajax_search_product', array( $this, 'yith_wfbt2_ajax_search_product' ) );
			add_action( 'wp_ajax_nopriv_yith_wfbt2_ajax_search_product', array( $this, 'yith_wfbt2_ajax_search_product' ) );
			// save tabs options.
			$product_types = apply_filters(
				'yith_wfbt2_product_types_meta_save',
				array(
					'simple',
					'variable',
					'grouped',
					'external',
					'rentable',
				)
			);
			foreach ( $product_types as $product_type ) {
				add_action( 'woocommerce_process_product_meta_' . $product_type, array( $this, 'save_bought_together_tab' ), 10, 1 );
			}
		}





		/**
		 * Add bought together tab in edit product page
		 *
		 * @since  1.0.0
		 *
		 * @param mixed $tabs Product edit admin tabs.
		 * @return mixed
		 */
		public function add_bought_together_tab( $tabs ) {

			$tabs['yith-wfbt2'] = array(
				'label'  => _x( 'Cross Selling Manual', 'tab in product data box', 'yith-woocommerce-frequently-bought-together' ),
				'target' => 'yith_wfbt2_data_option',
				'class'  => array( 'hide_if_grouped', 'hide_if_external', 'hide_if_bundle' ),
			);

			return $tabs;
		}

		/**
		 * Add bought together panel in edit product page
		 *
		 * @since  1.0.0
		 */
				public function add_bought_together_panel() {

			global $post, $product_object;

			$product_id = $post->ID;
			if ( is_null( $product_object ) ) {
				$product_object = wc_get_product( $product_id );
			}
			$to_exclude = array( $product_id );
			$meta_key = defined( 'YITH_WFBT2_META' ) ? YITH_WFBT2_META : '_yith_wfbt2_ids';
			$mode_key = defined( 'YITH_WFBT2_MODE_META' ) ? YITH_WFBT2_MODE_META : '_yith_wfbt2_mode';
			$categories_key = defined( 'YITH_WFBT2_CATEGORIES_META' ) ? YITH_WFBT2_CATEGORIES_META : '_yith_wfbt2_categories';
			$mode = yit_get_prop( $product_object, $mode_key, true );
			$mode = $mode ? $mode : 'simple';
			if ( 'set' === $mode ) {
				$mode = 'simple';
			}
			$categories = yit_get_prop( $product_object, $categories_key, true );
			if ( ! is_array( $categories ) ) {
				$categories = array();
			}
			if ( empty( $categories ) ) {
				$categories = array(
					array(
						'name'     => '',
						'products' => array(),
					),
				);
			}

			ob_start();
			?>
			<div class="yith-wfbt2-category-box" data-index="__INDEX__">
				<p class="form-field">
					<label for="yith_wfbt2_categories___INDEX___name"><?php esc_html_e( 'Nombre de Categoría', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<input type="text" id="yith_wfbt2_categories___INDEX___name" name="yith_wfbt2_categories[__INDEX__][name]" value="" style="width: 50%;"/>
				</p>
				<p class="form-field">
					<label for="yith_wfbt2_categories___INDEX___match"><?php esc_html_e( 'Texto a Buscar', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<input type="text" id="yith_wfbt2_categories___INDEX___match" name="yith_wfbt2_categories[__INDEX__][match]" value="" style="width: 50%;" placeholder="<?php esc_attr_e( 'e.g. Doble', 'yith-woocommerce-frequently-bought-together' ); ?>"/>
				</p>
				<p class="form-field">
					<label for="yith_wfbt2_categories___INDEX___message"><?php esc_html_e( 'Texto a Mostrar', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<input type="text" id="yith_wfbt2_categories___INDEX___message" name="yith_wfbt2_categories[__INDEX__][message]" value="" style="width: 50%;" placeholder="<?php esc_attr_e( 'Message to show if matched', 'yith-woocommerce-frequently-bought-together' ); ?>"/>
				</p>
				<p class="form-field">
					<label for="yith_wfbt2_categories___INDEX___products"><?php esc_html_e( 'Productos', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<?php
					yit_add_select2_fields(
						array(
							'class'             => 'wc-product-search',
							'style'             => 'width: 50%;',
							'id'                => 'yith_wfbt2_categories___INDEX___products',
							'name'              => 'yith_wfbt2_categories[__INDEX__][products]',
							'data-placeholder'  => __( 'Search for a product&hellip;', 'yith-woocommerce-frequently-bought-together' ),
							'data-multiple'     => true,
							'data-action'       => 'yith_wfbt2_ajax_search_product',
							'data-selected'     => array(),
							'value'             => '',
							'custom-attributes' => array(
								'data-exclude' => implode( ',', $to_exclude ),
							),
						)
					);
					?>
				</p>
				<p>
					<a href="#" class="button yith-wfbt2-remove-category"><?php esc_html_e( 'Remove', 'yith-woocommerce-frequently-bought-together' ); ?></a>
				</p>
			</div>
			<?php
			$category_template = ob_get_clean();

			?>

			<div id="yith_wfbt2_data_option" class="panel woocommerce_options_panel">

				<div class="options_group">
					<p class="form-field yith-wfbt2-mode-field">
						<label><?php esc_html_e( 'Selector', 'yith-woocommerce-frequently-bought-together' ); ?></label>
						<span class="yith-wfbt2-mode-options">
							<span class="yith-wfbt2-mode-option">
								<label>
									<input type="radio" name="yith_wfbt2_mode" value="simple" <?php checked( $mode, 'simple' ); ?> />
									<?php esc_html_e( 'Simple', 'yith-woocommerce-frequently-bought-together' ); ?>
								</label>
							</span>
							<span class="yith-wfbt2-mode-option">
								<label>
									<input type="radio" name="yith_wfbt2_mode" value="categorical" <?php checked( $mode, 'categorical' ); ?> />
									<?php esc_html_e( 'Categorico', 'yith-woocommerce-frequently-bought-together' ); ?>
								</label>
							</span>
						</span>
					</p>
				</div>

				<div class="yith-wfbt2-mode-set">
					<div class="options_group">
						<p class="form-field"><label
								for="yith_wfbt2_ids"><?php esc_html_e( 'Select products', 'yith-woocommerce-frequently-bought-together' ); ?></label>
							<?php
							$product_ids = yit_get_prop( $product_object, $meta_key, true );
							$product_ids = array_filter( array_map( 'absint', (array) $product_ids ) );
							$json_ids    = array();

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( is_object( $product ) ) {
									$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name() ) );
								}
							}

							yit_add_select2_fields(
								array(
									'class'             => 'wc-product-search',
									'style'             => 'width: 50%;',
									'id'                => 'yith_wfbt2_ids',
									'name'              => 'yith_wfbt2_ids',
									'data-placeholder'  => __( 'Search for a product&hellip;', 'yith-woocommerce-frequently-bought-together' ),
									'data-multiple'     => true,
									'data-action'       => 'yith_wfbt2_ajax_search_product',
									'data-selected'     => $json_ids,
									'value'             => implode( ',', array_keys( $json_ids ) ),
									'custom-attributes' => array(
										'data-exclude' => implode( ',', $to_exclude ),
									),
								)
							);
							?>
							<img class="help_tip"
								data-tip='<?php echo esc_attr__( 'Select products for "Frequently bought together" group', 'yith-woocommerce-frequently-bought-together' ) . ' 2'; ?>'
								src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16"
								width="16"/>
						</p>
					</div>
				</div>

				<div class="yith-wfbt2-mode-categorical">
					<div class="options_group">
						<div class="yith-wfbt2-categories">
							<?php
							$category_index = 0;
							foreach ( $categories as $category ) {
								$category_name     = isset( $category['name'] ) ? $category['name'] : '';
								$category_products = isset( $category['products'] ) ? $category['products'] : array();
								$category_match    = isset( $category['match'] ) ? $category['match'] : '';
								$category_message  = isset( $category['message'] ) ? $category['message'] : '';

								if ( is_string( $category_products ) ) {
									$category_products = explode( ',', $category_products );
								}
								$category_products = array_filter( array_map( 'absint', (array) $category_products ) );

								$json_ids = array();
								foreach ( $category_products as $category_product_id ) {
									$category_product = wc_get_product( $category_product_id );
									if ( is_object( $category_product ) ) {
										$json_ids[ $category_product_id ] = wp_kses_post( html_entity_decode( $category_product->get_formatted_name() ) );
									}
								}
								?>
								<div class="yith-wfbt2-category-box" data-index="<?php echo esc_attr( $category_index ); ?>">
									<p class="form-field">
										<label for="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_name"><?php esc_html_e( 'Nombre de Categoría', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<input type="text" id="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_name" name="yith_wfbt2_categories[<?php echo esc_attr( $category_index ); ?>][name]" value="<?php echo esc_attr( $category_name ); ?>" style="width: 50%;"/>
									</p>
									<p class="form-field">
										<label for="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_match"><?php esc_html_e( 'Texto a Buscar', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<input type="text" id="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_match" name="yith_wfbt2_categories[<?php echo esc_attr( $category_index ); ?>][match]" value="<?php echo esc_attr( $category_match ); ?>" style="width: 50%;"/>
									</p>
									<p class="form-field">
										<label for="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_message"><?php esc_html_e( 'Texto a Mostrar', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<input type="text" id="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_message" name="yith_wfbt2_categories[<?php echo esc_attr( $category_index ); ?>][message]" value="<?php echo esc_attr( $category_message ); ?>" style="width: 50%;"/>
									</p>
									<p class="form-field">
										<label for="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_products"><?php esc_html_e( 'Productos', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<?php
										yit_add_select2_fields(
											array(
												'class'             => 'wc-product-search',
												'style'             => 'width: 50%;',
												'id'                => 'yith_wfbt2_categories_' . $category_index . '_products',
												'name'              => 'yith_wfbt2_categories[' . $category_index . '][products]',
												'data-placeholder'  => __( 'Search for a product&hellip;', 'yith-woocommerce-frequently-bought-together' ),
												'data-multiple'     => true,
												'data-action'       => 'yith_wfbt2_ajax_search_product',
												'data-selected'     => $json_ids,
												'value'             => implode( ',', array_keys( $json_ids ) ),
												'custom-attributes' => array(
													'data-exclude' => implode( ',', $to_exclude ),
												),
											)
										);
										?>
									</p>
									<p>
										<a href="#" class="button yith-wfbt2-remove-category"><?php esc_html_e( 'Remove', 'yith-woocommerce-frequently-bought-together' ); ?></a>
									</p>
								</div>
								<?php
								$category_index++;
							}
							?>
						</div>
						<p>
							<a href="#" class="button yith-wfbt2-add-category">+ <?php esc_html_e( 'Add category', 'yith-woocommerce-frequently-bought-together' ); ?></a>
						</p>
						<input type="hidden" id="yith_wfbt2_categories_next_index" value="<?php echo esc_attr( $category_index ); ?>"/>
					</div>
				</div>

				<script type="text/template" id="yith-wfbt2-category-template">
					<?php echo $category_template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</script>

				<script>
					jQuery(function($) {
						function reorderSelect2Options($select) {
							var container = $select.next(".select2-container");
							if (!container.length) {
								return;
							}
							var ids = [];
							container.find("ul.select2-selection__rendered li.select2-selection__choice").each(function() {
								var data = $(this).data("data");
								if (data && data.id) {
									ids.push(String(data.id));
									return;
								}
								var id = $(this).data("id");
								if (id) {
									ids.push(String(id));
								}
							});
							if (!ids.length) {
								return;
							}
							ids.forEach(function(id) {
								var option = $select.find('option[value="' + id + '"]')[0];
								if (option) {
									$select.append(option);
								}
							});
						}

						function toggleMode() {
							var mode = $('input[name="yith_wfbt2_mode"]:checked').val();
							if (mode === 'categorical') {
								$('.yith-wfbt2-mode-set').hide();
								$('.yith-wfbt2-mode-categorical').show();
							} else {
								$('.yith-wfbt2-mode-set').show();
								$('.yith-wfbt2-mode-categorical').hide();
							}
						}

						toggleMode();

						$(document).on('change', 'input[name="yith_wfbt2_mode"]', toggleMode);

						var nextIndex = parseInt($('#yith_wfbt2_categories_next_index').val(), 10) || 0;

						$(document).on('click', '.yith-wfbt2-add-category', function(event) {
							event.preventDefault();
							var template = $('#yith-wfbt2-category-template').html();
							if (!template) {
								return;
							}
							var html = template.replace(/__INDEX__/g, nextIndex);
							nextIndex += 1;
							$('#yith_wfbt2_categories_next_index').val(nextIndex);
							$('.yith-wfbt2-categories').append(html);
							$(document.body).trigger('wc-enhanced-select-init');
							setTimeout(function() {
								$('.yith-wfbt2-categories .wc-product-search').each(function() {
									reorderSelect2Options($(this));
								});
							}, 0);
						});

						$(document).on('click', '.yith-wfbt2-remove-category', function(event) {
							event.preventDefault();
							$(this).closest('.yith-wfbt2-category-box').remove();
						});

						$(document).on('change', '.wc-product-search', function() {
							reorderSelect2Options($(this));
						});

						setTimeout(function() {
							$('.wc-product-search').each(function() {
								reorderSelect2Options($(this));
							});
						}, 0);
					});
				</script>

				<style>
					.yith-wfbt2-mode-field .yith-wfbt2-mode-options {
						display: flex !important;
						flex-wrap: wrap;
						gap: 16px;
						align-items: center;
					}
					.yith-wfbt2-mode-field .yith-wfbt2-mode-option {
						display: inline-flex;
					}
					.yith-wfbt2-mode-field .yith-wfbt2-mode-options label {
						float: none !important;
						clear: none !important;
						width: auto !important;
						display: inline-flex !important;
						align-items: center;
						gap: 6px;
						margin: 0;
						white-space: nowrap;
					}
					.yith-wfbt2-category-box {
						border: 1px solid #ddd;
						padding: 10px;
						margin-bottom: 12px;
					}
				</style>

			</div>

			<?php
		}

		/**
		 * Ajax action search product
		 *
		 * @since  1.0.0
		 */
		public function yith_wfbt2_ajax_search_product() {

			ob_start();

			check_ajax_referer( 'search-products', 'security' );
            // @codingStandardsIgnoreStart

			$term       = isset( $_GET['term'] ) ? (string) wc_clean( stripslashes( $_GET['term'] ) ) : '';
			$post_types = array( 'product', 'product_variation' );

			$to_exclude = isset( $_GET['exclude'] ) ? explode( ',', wc_clean( stripslashes( $_GET['exclude'] ) ) ) : false;
            // @codingStandardsIgnoreEnd
			if ( empty( $term ) ) {
				die();
			}

			$args = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				's'              => $term,
				'fields'         => 'ids',
			);

			if ( $to_exclude ) {
				$args['post__not_in'] = $to_exclude;
			}

			if ( is_numeric( $term ) ) {

				$args2 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post__in'       => array( 0, $term ),
					'fields'         => 'ids',
				);

				$args3 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => $term,
					'fields'         => 'ids',
				);

				$args4 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array( //phpcs:ignore slow query ok.
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
					'fields'         => 'ids',
				);

				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ), get_posts( $args4 ) ) );

			} else {

				$args2 = array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_query'     => array( //phpcs:ignore slow query ok.
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
					'fields'         => 'ids',
				);

				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );

			}

			$found_products = array();

			if ( $posts ) {
				foreach ( $posts as $post ) {
					$current_id = $post;
					$product    = wc_get_product( $post );
					// exclude variable product.
					if ( ! $product || $product->is_type( array( 'variable', 'external' ) ) ) {
						continue;
					} elseif ( $product->is_type( 'variation' ) ) {
						$current_id = wp_get_post_parent_id( $post );
						if ( ! wc_get_product( $current_id ) ) {
							continue;
						}
					}

					$found_products[ $post ] = rawurldecode( $product->get_formatted_name() );
				}
			}

			wp_send_json( apply_filters( 'yith_wfbt2_ajax_search_product_result', $found_products ) );
		}

		/**
		 * Save options in upselling tab
		 *
		 * @since  1.0.0
		 * @param mixed $post_id Post ID.
		 */
				public function save_bought_together_tab( $post_id ) {

			$product = wc_get_product( $post_id );
			$meta_key = defined( 'YITH_WFBT2_META' ) ? YITH_WFBT2_META : '_yith_wfbt2_ids';
			$mode_key = defined( 'YITH_WFBT2_MODE_META' ) ? YITH_WFBT2_MODE_META : '_yith_wfbt2_mode';
			$categories_key = defined( 'YITH_WFBT2_CATEGORIES_META' ) ? YITH_WFBT2_CATEGORIES_META : '_yith_wfbt2_categories';

			$mode = 'simple';
			if ( isset( $_POST['yith_wfbt2_mode'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification
				$mode = sanitize_text_field( wp_unslash( $_POST['yith_wfbt2_mode'] ) );
			}
			if ( 'set' === $mode ) {
				$mode = 'simple';
			}
			if ( ! in_array( $mode, array( 'simple', 'categorical' ), true ) ) {
				$mode = 'simple';
			}
			yit_save_prop( $product, $mode_key, $mode );

			if ( 'simple' === $mode ) {
				$products_array = array();
				if ( isset( $_POST['yith_wfbt2_ids'] ) ) {//phpcs:ignore WordPress.Security.NonceVerification
					$products_array = ! is_array( $_POST['yith_wfbt2_ids'] ) ? explode( ',', stripslashes_deep( array_map( 'sanitize_text_field', $_POST['yith_wfbt2_ids'] ) ) ) : stripslashes_deep( array_map( 'sanitize_text_field', $_POST['yith_wfbt2_ids'] ) );//phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
					$products_array = array_filter( array_map( 'intval', $products_array ) );
				}
				yit_save_prop( $product, $meta_key, $products_array );
			}

			if ( 'categorical' === $mode ) {
				$categories = array();
				$raw_categories = isset( $_POST['yith_wfbt2_categories'] ) ? wp_unslash( $_POST['yith_wfbt2_categories'] ) : array();//phpcs:ignore WordPress.Security.NonceVerification
				if ( is_array( $raw_categories ) ) {
					foreach ( $raw_categories as $category ) {
						$name = isset( $category['name'] ) ? sanitize_text_field( $category['name'] ) : '';
						$match = isset( $category['match'] ) ? sanitize_text_field( $category['match'] ) : '';
						$message = isset( $category['message'] ) ? sanitize_text_field( $category['message'] ) : '';
						$products_raw = isset( $category['products'] ) ? $category['products'] : array();
						if ( ! is_array( $products_raw ) ) {
							$products_raw = explode( ',', $products_raw );
						}
						$products_raw = array_map( 'sanitize_text_field', (array) $products_raw );
						$product_ids = array_filter( array_map( 'absint', $products_raw ) );
						$product_ids = array_values( array_unique( $product_ids ) );

						if ( '' === $name && empty( $product_ids ) && '' === $match && '' === $message ) {
							continue;
						}
						$categories[] = array(
							'name'     => $name,
							'match'    => $match,
							'message'  => $message,
							'products' => $product_ids,
						);
					}
				}
				yit_save_prop( $product, $categories_key, $categories );
			}
		}


	}
}
/**
 * Unique access to instance of YITH_WFBT2_Admin class
 *
 * @since 1.0.0
 * @return \YITH_WFBT2_Admin
 */
function YITH_WFBT2_Admin() {// phpcs:ignore WordPress.NamingConventions
	return YITH_WFBT2_Admin::get_instance();
}
