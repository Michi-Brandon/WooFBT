<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Shortcodes for custom checkbox rendering.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\FrequentlyBoughtTogether
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WFBT2' ) ) {
	exit;
} // Exit if accessed directly.

if ( ! class_exists( 'YITH_WFBT2_Shortcodes' ) ) {
	/**
	 * Shortcodes class.
	 *
	 * @since 1.0.0
	 */
	class YITH_WFBT2_Shortcodes {

		/**
		 * Single instance of the class.
		 *
		 * @since 1.0.0
		 * @var \YITH_WFBT2_Shortcodes
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class.
		 *
		 * @since 1.0.0
		 * @return \YITH_WFBT2_Shortcodes
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
		 * @since 1.0.0
		 */
		public function __construct() {
			add_shortcode( 'fbt_product_check_2', array( $this, 'render_fbt_product_with_checks' ) );
		}

		/**
		 * Render the YITH Frequently Bought Together 2 widget with checkboxes.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
		public function render_fbt_product_with_checks( $atts ) {
			if ( ! function_exists( 'do_shortcode' ) || ! shortcode_exists( 'ywfbt2_form' ) ) {
				return '<p style="color: red;">El plugin YITH WooCommerce Frequently Bought Together 2 no esta disponible.</p>';
			}

			$atts = shortcode_atts(
				array(
					'product_id'          => get_the_ID(),
					'show_price_update'   => 'yes',
					'show_selection_info' => 'yes',
					'checkbox_style'      => 'default',
					'layout'              => 'horizontal',
				),
				$atts,
				'fbt_product_check_2'
			);

			$product_id = absint( $atts['product_id'] );
			if ( ! $product_id ) {
				return '<p>ID de producto no valido.</p>';
			}

			$mode_key = defined( 'YITH_WFBT2_MODE_META' ) ? YITH_WFBT2_MODE_META : '_yith_wfbt2_mode';
			$mode = get_post_meta( $product_id, $mode_key, true );
			$is_categorical = ( 'categorical' === $mode );

			$html = do_shortcode( sprintf( '[ywfbt2_form product_id="%d"]', $product_id ) );
			if ( empty( $html ) && $is_categorical && function_exists( 'YITH_WFBT2_Frontend' ) ) {
				$frontend = YITH_WFBT2_Frontend();
				if ( $frontend && method_exists( $frontend, 'wfbt_shortcode' ) ) {
					$html = $frontend->wfbt_shortcode( array( 'product_id' => $product_id ) );
				}
			}
			if ( empty( $html ) ) {
				return '<p>No hay productos comprados juntos para este producto.</p>';
			}

			if ( $is_categorical ) {
				return $html;
			}

			$container_class = 'fbt-checkbox-container layout-' . esc_attr( $atts['layout'] );
			$html            = '<div class="' . $container_class . '">' . $html . '</div>';
			$container_selector = '.fbt-checkbox-container';

			$products_data = array();
			$html          = preg_replace_callback(
				'#<input\s+type=["\']hidden["\']\s+name=["\'](offeringID[^"\']*)["\']\s+id=["\']([^"\']+)["\']\s+class=["\']active["\']\s+value=["\'](\d+)["\']\s*/?>#',
				function( $matches ) use ( $atts, &$products_data, $is_categorical ) {
					$name    = esc_attr( $matches[1] );
					$id      = esc_attr( $matches[2] );
					$value   = esc_attr( $matches[3] );
					$product = wc_get_product( $value );

					if ( ! $product ) {
						return '';
					}

					$price         = $product->get_price();
					$price_numeric = floatval( $price );
					$price_html    = $product->get_price_html();

					$products_data[ $value ] = array(
						'id'         => $value,
						'name'       => $product->get_name(),
						'price'      => $price_numeric,
						'price_html' => $price_html,
					);

					if ( $is_categorical ) {
						return sprintf(
							'<input type="checkbox" name="%1$s" id="%2$s" class="active" value="%3$s" checked data-price="%4$s" />',
							$name,
							$id,
							$value,
							$price_numeric
						);
					}

					$checkbox_html = '';
					switch ( $atts['checkbox_style'] ) {
						case 'switch':
							$checkbox_html = sprintf(
								'<div class="fbt-switch-container">
									<label class="fbt-switch" for="%1$s">
										<input type="checkbox" name="%6$s" id="%1$s" value="%2$s" checked data-price="%5$s">
										<span class="fbt-slider"></span>
									</label>
									<span class="fbt-product-name">%3$s</span>
								</div>',
								$id,
								$value,
								esc_html( $product->get_name() ),
								wp_kses_post( $price_html ),
								$price_numeric,
								$name
							);
							break;

						case 'custom':
							$checkbox_html = sprintf(
								'<div class="fbt-custom-checkbox">
									<input type="checkbox" name="%6$s" id="%1$s" value="%2$s" checked data-price="%5$s">
									<label for="%1$s" class="fbt-custom-label">
										<span class="fbt-checkmark"></span>
										<span class="fbt-product-info">
											<strong>%3$s</strong>
											<span class="fbt-price">%4$s</span>
										</span>
									</label>
								</div>',
								$id,
								$value,
								esc_html( $product->get_name() ),
								wp_kses_post( $price_html ),
								$price_numeric,
								$name
							);
							break;

						default:
							$checkbox_html = sprintf(
								'<div class="fbt-default-checkbox">
									<input type="checkbox" name="%6$s" id="%1$s" value="%2$s" checked data-price="%5$s">
									<label for="%1$s">%3$s (%4$s)</label>
								</div>',
								$id,
								$value,
								esc_html( $product->get_name() ),
								wp_kses_post( $price_html ),
								$price_numeric,
								$name
							);
							break;
					}

					return $checkbox_html;
				},
				$html
			);

			$products_json       = wp_json_encode( $products_data );
			$show_selection_info = ( 'yes' === $atts['show_selection_info'] && ! $is_categorical ) ? 'true' : 'false';
			$show_price_update   = ( 'yes' === $atts['show_price_update'] ) ? 'true' : 'false';

			$html .= '<script>
document.addEventListener("DOMContentLoaded", function() {
	initFBTCheckboxes();
});

function initFBTCheckboxes() {
	const container = document.querySelector("' . $container_selector . '");
	if (!container) return;
	const checkboxes = container.querySelectorAll("input[type=\'checkbox\']");
	const totalPriceSpan = container.querySelector(".yith-wfbt-submit-block .total_price .woocommerce-Price-amount bdi");
	const totalPriceData = container.querySelector(".yith-wfbt-submit-block .total_price");

	const productsData = ' . $products_json . ';

	if (checkboxes.length === 0) return;

	checkboxes.forEach(function(checkbox) {
		checkbox.addEventListener("change", function() {
			if (' . $show_price_update . ') {
				updateTotalPrice();
			}
			updateSelectionInfo();
		});
	});

	function updateTotalPrice() {
		let totalPrice = 0;
		const selectedCheckboxes = container.querySelectorAll("input[type=\'checkbox\']:checked");

		selectedCheckboxes.forEach(function(checkbox) {
			const productId = checkbox.value;
			if (productsData[productId]) {
				totalPrice += parseFloat(productsData[productId].price);
			}
		});

		const formattedPrice = formatPrice(totalPrice);

		if (totalPriceSpan) {
			const currencySymbol = totalPriceSpan.querySelector(".woocommerce-Price-currencySymbol");
			const symbolText = currencySymbol ? currencySymbol.textContent : "$";
			totalPriceSpan.innerHTML = "<span class=\'woocommerce-Price-currencySymbol\'>" + symbolText + "</span>" + formattedPrice;
		}

		if (totalPriceData) {
			totalPriceData.setAttribute("data-total", Math.round(totalPrice));
		}

		console.log("Precio total actualizado:", formattedPrice);
	}

	function formatPrice(price) {
		return Math.round(price).toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, ".");
	}

	function updateSelectionInfo() {
		const selectedCount = container.querySelectorAll("input[type=\'checkbox\']:checked").length;
		let infoElement = container.querySelector(".fbt-selection-info");

		if (' . $show_selection_info . ') {
			if (!infoElement) {
				infoElement = document.createElement("div");
				infoElement.className = "fbt-selection-info";
				container.appendChild(infoElement);
			}

			if (infoElement) {
				infoElement.innerHTML = "Productos seleccionados: " + selectedCount;
			}
		}
	}

	updateSelectionInfo();

	if (' . $show_price_update . ') {
		setTimeout(function() {
			updateTotalPrice();
		}, 100);
	}
}
</script>';

			if ( ! $is_categorical ) {
				$html .= '<style>
.fbt-checkbox-container {
	margin: 20px 0;
	padding: 15px;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	background: #fafafa;
}

.fbt-checkbox-container.layout-vertical .fbt-default-checkbox,
.fbt-checkbox-container.layout-vertical .fbt-custom-checkbox,
.fbt-checkbox-container.layout-vertical .fbt-switch-container {
	display: block;
	margin-bottom: 15px;
}

.fbt-checkbox-container.layout-horizontal .fbt-default-checkbox,
.fbt-checkbox-container.layout-horizontal .fbt-custom-checkbox,
.fbt-checkbox-container.layout-horizontal .fbt-switch-container {
	display: inline-block;
	margin-right: 20px;
	margin-bottom: 10px;
}

.fbt-default-checkbox {
	padding: 8px;
}

.fbt-default-checkbox input[type="checkbox"] {
	margin-right: 8px;
	transform: scale(1.2);
}

.fbt-default-checkbox label {
	cursor: pointer;
	font-size: 14px;
}

.fbt-custom-checkbox {
	position: relative;
	padding: 12px;
	border: 2px solid #ddd;
	border-radius: 6px;
	transition: all 0.3s ease;
}

.fbt-custom-checkbox:hover {
	border-color: #0073aa;
	background: white;
}

.fbt-custom-checkbox input[type="checkbox"] {
	display: none;
}

.fbt-custom-label {
	display: flex;
	align-items: center;
	cursor: pointer;
}

.fbt-checkmark {
	width: 20px;
	height: 20px;
	border: 2px solid #ddd;
	border-radius: 3px;
	margin-right: 10px;
	position: relative;
	transition: all 0.3s ease;
}

.fbt-custom-checkbox input:checked + .fbt-custom-label .fbt-checkmark {
	background: #0073aa;
	border-color: #0073aa;
}

.fbt-checkmark::after {
	content: "";
	position: absolute;
	left: 6px;
	top: 2px;
	width: 6px;
	height: 10px;
	border: solid white;
	border-width: 0 2px 2px 0;
	transform: rotate(45deg);
	opacity: 0;
	transition: opacity 0.3s ease;
}

.fbt-custom-checkbox input:checked + .fbt-custom-label .fbt-checkmark::after {
	opacity: 1;
}

.fbt-product-info {
	display: flex;
	flex-direction: column;
}

.fbt-price {
	font-size: 12px;
	color: #666;
	margin-top: 2px;
}

.fbt-switch-container {
	display: flex;
	align-items: center;
	padding: 10px;
}

.fbt-switch {
	position: relative;
	display: inline-block;
	width: 50px;
	height: 24px;
	margin-right: 10px;
}

.fbt-switch input {
	opacity: 0;
	width: 0;
	height: 0;
}

.fbt-slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: #ccc;
	transition: .4s;
	border-radius: 24px;
}

.fbt-slider:before {
	position: absolute;
	content: "";
	height: 18px;
	width: 18px;
	left: 3px;
	bottom: 3px;
	background-color: white;
	transition: .4s;
	border-radius: 50%;
}

.fbt-switch input:checked + .fbt-slider {
	background-color: #0073aa;
}

.fbt-switch input:checked + .fbt-slider:before {
	transform: translateX(26px);
}

.fbt-product-name {
	font-size: 14px;
	font-weight: 500;
}

.fbt-selection-info {
	margin-top: 15px;
	padding: 10px;
	background: #e7f3ff;
	border-left: 4px solid #0073aa;
	font-size: 14px;
	font-weight: 500;
}

@media (max-width: 768px) {
	.fbt-checkbox-container.layout-horizontal .fbt-default-checkbox,
	.fbt-checkbox-container.layout-horizontal .fbt-custom-checkbox,
	.fbt-checkbox-container.layout-horizontal .fbt-switch-container {
		display: block;
		margin-right: 0;
		margin-bottom: 15px;
	}
}
</style>';
			}

			return $html;
		}

	}
}

/**
 * Unique access to instance of YITH_WFBT2_Shortcodes class.
 *
 * @since 1.0.0
 * @return \YITH_WFBT2_Shortcodes
 */
function YITH_WFBT2_Shortcodes() { // phpcs:ignore WordPress.NamingConventions
	return YITH_WFBT2_Shortcodes::get_instance();
}
