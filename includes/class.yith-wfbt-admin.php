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
		 * Single instance of the class.
		 *
		 * @since 1.0.0
		 * @var \YITH_WFBT2_Admin
		 */
		protected static $instance;

		/**
		 * Plugin options.
		 *
		 * @since  1.0.0
		 * @var array
		 */
		public $options = array();

		/**
		 * Plugin version.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $version = YITH_WFBT2_VERSION;

		/**
		 * Returns single instance of the class.
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
		 * Constructor.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_bought_together_tab' ), 10, 1 );
			add_action( 'woocommerce_product_data_panels', array( $this, 'add_bought_together_panel' ) );

			add_action( 'wp_ajax_yith_wfbt2_ajax_search_product', array( $this, 'yith_wfbt2_ajax_search_product' ) );
			add_action( 'wp_ajax_nopriv_yith_wfbt2_ajax_search_product', array( $this, 'yith_wfbt2_ajax_search_product' ) );

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
		 * Add tab in product data box.
		 *
		 * @param array $tabs Product tabs.
		 * @return array
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
		 * Render product panel.
		 */
		public function add_bought_together_panel() {
			global $post, $product_object;

			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}

			$product_id = $post->ID;
			if ( is_null( $product_object ) ) {
				$product_object = wc_get_product( $product_id );
			}

			$to_exclude     = array( $product_id );
			$meta_key       = defined( 'YITH_WFBT2_META' ) ? YITH_WFBT2_META : '_yith_wfbt2_ids';
			$mode_key       = defined( 'YITH_WFBT2_MODE_META' ) ? YITH_WFBT2_MODE_META : '_yith_wfbt2_mode';
			$categories_key = defined( 'YITH_WFBT2_CATEGORIES_META' ) ? YITH_WFBT2_CATEGORIES_META : '_yith_wfbt2_categories';
			$mode           = yit_get_prop( $product_object, $mode_key, true );
			$mode           = $mode ? $mode : 'simple';
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
						'qty'      => array(),
					),
				);
			}

			ob_start();
			?>
			<div class="yith-wfbt2-category-box" data-index="__INDEX__">
				<p class="form-field">
					<label for="yith_wfbt2_categories___INDEX___name"><?php esc_html_e( 'Set name', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<input type="text" id="yith_wfbt2_categories___INDEX___name" name="yith_wfbt2_categories[__INDEX__][name]" value="" style="width: 50%;"/>
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Set image', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<input type="hidden" class="yith-wfbt2-set-image-id" id="yith_wfbt2_categories___INDEX___image_id" name="yith_wfbt2_categories[__INDEX__][image_id]" value=""/>
					<span class="yith-wfbt2-set-image-preview"></span>
					<a href="#" class="button yith-wfbt2-upload-image"><?php esc_html_e( 'Select image', 'yith-woocommerce-frequently-bought-together' ); ?></a>
					<a href="#" class="button yith-wfbt2-remove-image" style="display:none;"><?php esc_html_e( 'Remove image', 'yith-woocommerce-frequently-bought-together' ); ?></a>
				</p>

				<p class="form-field">
					<label for="yith_wfbt2_categories___INDEX___products"><?php esc_html_e( 'Set products', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<?php
					yit_add_select2_fields(
						array(
							'class'             => 'wc-product-search yith-wfbt2-set-products',
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

				<input type="hidden" class="yith-wfbt2-qty-map-data" value="{}"/>
				<div class="yith-wfbt2-set-qty-wrapper">
					<label><?php esc_html_e( 'Quantity per product', 'yith-woocommerce-frequently-bought-together' ); ?></label>
					<div class="yith-wfbt2-set-qty-list"></div>
				</div>

				<p>
					<a href="#" class="button yith-wfbt2-remove-category"><?php esc_html_e( 'Remove Set', 'yith-woocommerce-frequently-bought-together' ); ?></a>
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
									<?php esc_html_e( 'Categorical', 'yith-woocommerce-frequently-bought-together' ); ?>
								</label>
							</span>
						</span>
					</p>
				</div>

				<div class="yith-wfbt2-mode-set">
					<div class="options_group">
						<p class="form-field">
							<label for="yith_wfbt2_ids"><?php esc_html_e( 'Select products', 'yith-woocommerce-frequently-bought-together' ); ?></label>
							<?php
							$product_ids = yit_get_prop( $product_object, $meta_key, true );
							$product_ids = array_filter( array_map( 'absint', (array) $product_ids ) );
							$json_ids    = array();

							foreach ( $product_ids as $related_product_id ) {
								$related_product = wc_get_product( $related_product_id );
								if ( is_object( $related_product ) ) {
									$json_ids[ $related_product_id ] = wp_kses_post( html_entity_decode( $related_product->get_formatted_name() ) );
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
						</p>
					</div>
				</div>

				<div class="yith-wfbt2-mode-categorical">
					<div class="options_group">
						<div class="yith-wfbt2-categories">
							<?php
							$category_index = 0;
							foreach ( $categories as $category ) {
								$category_name    = isset( $category['name'] ) ? $category['name'] : '';
								$category_image   = isset( $category['image_id'] ) ? absint( $category['image_id'] ) : 0;
								$category_qty_map = isset( $category['qty'] ) && is_array( $category['qty'] ) ? $category['qty'] : array();

								$category_products = isset( $category['products'] ) ? $category['products'] : array();
								if ( is_string( $category_products ) ) {
									$category_products = explode( ',', $category_products );
								}
								$category_products = array_filter( array_map( 'absint', (array) $category_products ) );

								if ( empty( $category_products ) && isset( $category['items'] ) && is_array( $category['items'] ) ) {
									foreach ( $category['items'] as $item_data ) {
										$item_product_id = isset( $item_data['product_id'] ) ? absint( $item_data['product_id'] ) : 0;
										$item_qty        = isset( $item_data['qty'] ) ? max( 1, absint( $item_data['qty'] ) ) : 1;
										if ( $item_product_id ) {
											$category_products[]                  = $item_product_id;
											$category_qty_map[ $item_product_id ] = $item_qty;
										}
									}
								}

								$category_products = array_values( array_unique( $category_products ) );
								$json_ids          = array();
								foreach ( $category_products as $category_product_id ) {
									$category_product = wc_get_product( $category_product_id );
									if ( is_object( $category_product ) ) {
										$json_ids[ $category_product_id ] = wp_kses_post( html_entity_decode( $category_product->get_formatted_name() ) );
									}
								}
								?>
								<div class="yith-wfbt2-category-box" data-index="<?php echo esc_attr( $category_index ); ?>">
									<p class="form-field">
										<label for="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_name"><?php esc_html_e( 'Set name', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<input type="text" id="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_name" name="yith_wfbt2_categories[<?php echo esc_attr( $category_index ); ?>][name]" value="<?php echo esc_attr( $category_name ); ?>" style="width: 50%;"/>
									</p>

									<p class="form-field">
										<label><?php esc_html_e( 'Set image', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<input type="hidden" class="yith-wfbt2-set-image-id" id="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_image_id" name="yith_wfbt2_categories[<?php echo esc_attr( $category_index ); ?>][image_id]" value="<?php echo esc_attr( $category_image ); ?>"/>
										<span class="yith-wfbt2-set-image-preview"><?php if ( $category_image ) { echo wp_kses_post( wp_get_attachment_image( $category_image, 'thumbnail' ) ); } ?></span>
										<a href="#" class="button yith-wfbt2-upload-image"><?php esc_html_e( 'Select image', 'yith-woocommerce-frequently-bought-together' ); ?></a>
										<a href="#" class="button yith-wfbt2-remove-image" <?php echo $category_image ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove image', 'yith-woocommerce-frequently-bought-together' ); ?></a>
									</p>

									<p class="form-field">
										<label for="yith_wfbt2_categories_<?php echo esc_attr( $category_index ); ?>_products"><?php esc_html_e( 'Set products', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<?php
										yit_add_select2_fields(
											array(
												'class'             => 'wc-product-search yith-wfbt2-set-products',
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

									<input type="hidden" class="yith-wfbt2-qty-map-data" value="<?php echo esc_attr( wp_json_encode( $category_qty_map ) ); ?>"/>
									<div class="yith-wfbt2-set-qty-wrapper">
										<label><?php esc_html_e( 'Quantity per product', 'yith-woocommerce-frequently-bought-together' ); ?></label>
										<div class="yith-wfbt2-set-qty-list"></div>
									</div>

									<p>
										<a href="#" class="button yith-wfbt2-remove-category"><?php esc_html_e( 'Remove Set', 'yith-woocommerce-frequently-bought-together' ); ?></a>
									</p>
								</div>
								<?php
								$category_index++;
							}
							?>
						</div>
						<p>
							<a href="#" class="button yith-wfbt2-add-category">+ <?php esc_html_e( 'Add Set', 'yith-woocommerce-frequently-bought-together' ); ?></a>
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

						function getStoredQtyMap($box) {
							var raw = $box.find(".yith-wfbt2-qty-map-data").val() || "{}";
							try {
								var parsed = JSON.parse(raw);
								return parsed && typeof parsed === "object" ? parsed : {};
							} catch (error) {
								return {};
							}
						}

						function collectQtyMap($box) {
							var map = getStoredQtyMap($box);
							$box.find(".yith-wfbt2-set-qty-input").each(function() {
								var productId = String($(this).data("productId"));
								var qty = parseInt($(this).val(), 10);
								map[productId] = qty && qty > 0 ? qty : 1;
							});
							return map;
						}

						function syncSetQtyRows($box) {
							var $select = $box.find(".yith-wfbt2-set-products");
							if (!$select.length) {
								return;
							}
							var map = collectQtyMap($box);
							var index = $box.data("index");
							var selectedIds = [];
							$select.find("option:selected").each(function() {
								selectedIds.push(String($(this).val()));
							});
							var $list = $box.find(".yith-wfbt2-set-qty-list");
							$list.empty();
							if (!selectedIds.length) {
								$list.append('<p class="description yith-wfbt2-set-qty-empty"><?php echo esc_js( __( 'Select products to define quantities.', 'yith-woocommerce-frequently-bought-together' ) ); ?></p>');
								$box.find(".yith-wfbt2-qty-map-data").val("{}");
								return;
							}
							var normalizedMap = {};
							selectedIds.forEach(function(productId) {
								var label = $.trim($select.find('option[value="' + productId + '"]').text()) || ("#" + productId);
								var qty = parseInt(map[productId], 10);
								if (!qty || qty < 1) {
									qty = 1;
								}
								normalizedMap[productId] = qty;
								var rowHtml = '' +
									'<div class="yith-wfbt2-set-qty-row">' +
										'<span class="yith-wfbt2-set-qty-name">' + label + '</span>' +
										'<input type="number" min="1" class="yith-wfbt2-set-qty-input" data-product-id="' + productId + '" ' +
										'name="yith_wfbt2_categories[' + index + '][qty][' + productId + ']" value="' + qty + '" />' +
									'</div>';
								$list.append(rowHtml);
							});
							$box.find(".yith-wfbt2-qty-map-data").val(JSON.stringify(normalizedMap));
						}

						function initSetBox($box) {
							var $select = $box.find(".yith-wfbt2-set-products");
							if ($select.length) {
								reorderSelect2Options($select);
							}
							syncSetQtyRows($box);
							var hasImage = parseInt($box.find(".yith-wfbt2-set-image-id").val(), 10) > 0;
							$box.find(".yith-wfbt2-remove-image").toggle(hasImage);
						}

						function toggleMode() {
							var mode = $('input[name="yith_wfbt2_mode"]:checked').val();
							if (mode === "categorical") {
								$(".yith-wfbt2-mode-set").hide();
								$(".yith-wfbt2-mode-categorical").show();
							} else {
								$(".yith-wfbt2-mode-set").show();
								$(".yith-wfbt2-mode-categorical").hide();
							}
						}

						toggleMode();
						$(document).on("change", 'input[name="yith_wfbt2_mode"]', toggleMode);

						var nextIndex = parseInt($("#yith_wfbt2_categories_next_index").val(), 10) || 0;
						$(document).on("click", ".yith-wfbt2-add-category", function(event) {
							event.preventDefault();
							var template = $("#yith-wfbt2-category-template").html();
							if (!template) {
								return;
							}
							var html = template.replace(/__INDEX__/g, nextIndex);
							nextIndex += 1;
							$("#yith_wfbt2_categories_next_index").val(nextIndex);
							var $newBox = $(html);
							$(".yith-wfbt2-categories").append($newBox);
							$(document.body).trigger("wc-enhanced-select-init");
							setTimeout(function() {
								initSetBox($newBox);
							}, 0);
						});

						$(document).on("click", ".yith-wfbt2-remove-category", function(event) {
							event.preventDefault();
							$(this).closest(".yith-wfbt2-category-box").remove();
						});

						$(document).on("change", ".yith-wfbt2-set-products", function() {
							var $select = $(this);
							reorderSelect2Options($select);
							syncSetQtyRows($select.closest(".yith-wfbt2-category-box"));
						});

						$(document).on("change", ".yith-wfbt2-set-qty-input", function() {
							var value = parseInt($(this).val(), 10);
							if (!value || value < 1) {
								value = 1;
								$(this).val(value);
							}
							var $box = $(this).closest(".yith-wfbt2-category-box");
							var map = collectQtyMap($box);
							$box.find(".yith-wfbt2-qty-map-data").val(JSON.stringify(map));
						});

						$(document).on("click", ".yith-wfbt2-upload-image", function(event) {
							event.preventDefault();
							if (typeof wp === "undefined" || !wp.media) {
								return;
							}
							var $box = $(this).closest(".yith-wfbt2-category-box");
							var frame = wp.media({
								title: '<?php echo esc_js( __( 'Select set image', 'yith-woocommerce-frequently-bought-together' ) ); ?>',
								button: { text: '<?php echo esc_js( __( 'Use this image', 'yith-woocommerce-frequently-bought-together' ) ); ?>' },
								multiple: false
							});
							frame.on("select", function() {
								var attachment = frame.state().get("selection").first().toJSON();
								var imageUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
								$box.find(".yith-wfbt2-set-image-id").val(attachment.id);
								$box.find(".yith-wfbt2-set-image-preview").html('<img src="' + imageUrl + '" alt="" />');
								$box.find(".yith-wfbt2-remove-image").show();
							});
							frame.open();
						});

						$(document).on("click", ".yith-wfbt2-remove-image", function(event) {
							event.preventDefault();
							var $box = $(this).closest(".yith-wfbt2-category-box");
							$box.find(".yith-wfbt2-set-image-id").val("");
							$box.find(".yith-wfbt2-set-image-preview").empty();
							$(this).hide();
						});

						$(".yith-wfbt2-category-box").each(function() {
							initSetBox($(this));
						});
					});
				</script>

				<style>
					.yith-wfbt2-mode-field .yith-wfbt2-mode-options { display: flex !important; flex-wrap: wrap; gap: 16px; align-items: center; }
					.yith-wfbt2-mode-field .yith-wfbt2-mode-option { display: inline-flex; }
					.yith-wfbt2-mode-field .yith-wfbt2-mode-options label { float: none !important; clear: none !important; width: auto !important; display: inline-flex !important; align-items: center; gap: 6px; margin: 0; white-space: nowrap; }
					.yith-wfbt2-category-box { border: 1px solid #ddd; padding: 10px; margin-bottom: 12px; }
					.yith-wfbt2-set-image-preview { display: inline-flex; vertical-align: middle; margin-right: 8px; min-width: 56px; min-height: 56px; align-items: center; justify-content: center; border: 1px solid #e5e5e5; background: #fff; }
					.yith-wfbt2-set-image-preview img { max-width: 56px; height: auto; display: block; }
					.yith-wfbt2-set-qty-wrapper { margin: 12px 0; }
					.yith-wfbt2-set-qty-row { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
					.yith-wfbt2-set-qty-name { flex: 1 1 auto; }
					.yith-wfbt2-set-qty-input { width: 90px; }
				</style>
			</div>
			<?php
		}

		/**
		 * Ajax action search product.
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
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
				foreach ( $posts as $search_post_id ) {
					$product = wc_get_product( $search_post_id );
					if ( ! $product || $product->is_type( array( 'variable', 'external' ) ) ) {
						continue;
					}
					if ( $product->is_type( 'variation' ) ) {
						$parent_id = wp_get_post_parent_id( $search_post_id );
						if ( ! wc_get_product( $parent_id ) ) {
							continue;
						}
					}
					$found_products[ $search_post_id ] = rawurldecode( $product->get_formatted_name() );
				}
			}

			wp_send_json( apply_filters( 'yith_wfbt2_ajax_search_product_result', $found_products ) );
		}

		/**
		 * Save panel options.
		 *
		 * @param int $post_id Post ID.
		 */
		public function save_bought_together_tab( $post_id ) {
			$product        = wc_get_product( $post_id );
			$meta_key       = defined( 'YITH_WFBT2_META' ) ? YITH_WFBT2_META : '_yith_wfbt2_ids';
			$mode_key       = defined( 'YITH_WFBT2_MODE_META' ) ? YITH_WFBT2_MODE_META : '_yith_wfbt2_mode';
			$categories_key = defined( 'YITH_WFBT2_CATEGORIES_META' ) ? YITH_WFBT2_CATEGORIES_META : '_yith_wfbt2_categories';

			$mode = 'simple';
			if ( isset( $_POST['yith_wfbt2_mode'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
				if ( isset( $_POST['yith_wfbt2_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$products_array = ! is_array( $_POST['yith_wfbt2_ids'] ) ? explode( ',', stripslashes_deep( array_map( 'sanitize_text_field', $_POST['yith_wfbt2_ids'] ) ) ) : stripslashes_deep( array_map( 'sanitize_text_field', $_POST['yith_wfbt2_ids'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$products_array = array_filter( array_map( 'intval', $products_array ) );
				}
				yit_save_prop( $product, $meta_key, $products_array );
			}

			if ( 'categorical' === $mode ) {
				$categories     = array();
				$raw_categories = isset( $_POST['yith_wfbt2_categories'] ) ? wp_unslash( $_POST['yith_wfbt2_categories'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( is_array( $raw_categories ) ) {
					foreach ( $raw_categories as $category ) {
						$name     = isset( $category['name'] ) ? sanitize_text_field( $category['name'] ) : '';
						$image_id = isset( $category['image_id'] ) ? absint( $category['image_id'] ) : 0;

						$products_raw = isset( $category['products'] ) ? $category['products'] : array();
						if ( ! is_array( $products_raw ) ) {
							$products_raw = explode( ',', $products_raw );
						}
						$products_raw = array_map( 'sanitize_text_field', (array) $products_raw );
						$product_ids  = array_filter( array_map( 'absint', $products_raw ) );
						$product_ids  = array_values( array_unique( $product_ids ) );

						$qty_map = array();
						$items   = array();
						$qty_raw = isset( $category['qty'] ) && is_array( $category['qty'] ) ? $category['qty'] : array();
						foreach ( $product_ids as $set_product_id ) {
							$qty = isset( $qty_raw[ $set_product_id ] ) ? absint( $qty_raw[ $set_product_id ] ) : 1;
							$qty = max( 1, $qty );
							$qty_map[ $set_product_id ] = $qty;
							$items[] = array(
								'product_id' => $set_product_id,
								'qty'        => $qty,
							);
						}

						if ( '' === $name && empty( $product_ids ) && ! $image_id ) {
							continue;
						}
						$categories[] = array(
							'name'     => $name,
							'image_id' => $image_id,
							'products' => $product_ids,
							'qty'      => $qty_map,
							'items'    => $items,
						);
					}
				}
				yit_save_prop( $product, $categories_key, $categories );
			}
		}
	}
}

/**
 * Unique access to instance of YITH_WFBT2_Admin class.
 *
 * @since 1.0.0
 * @return \YITH_WFBT2_Admin
 */
function YITH_WFBT2_Admin() {// phpcs:ignore WordPress.NamingConventions
	return YITH_WFBT2_Admin::get_instance();
}
