<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Categorical mode template (set-based selector).
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\FrequentlyBoughtTogether
 * @version 1.0.4
 */

if ( ! defined( 'YITH_WFBT2' ) ) {
	exit;
}

if ( empty( $categories ) || ! is_array( $categories ) ) {
	return;
}

$main_product = isset( $products[0] ) ? $products[0] : null;
if ( ! $main_product ) {
	return;
}

$url = $main_product->get_permalink();
$url = add_query_arg( 'action', 'yith_bought_together_2', $url );
$url = wp_nonce_url( $url, 'yith_bought_together_2' );

$label       = get_option( 'yith-wfbt2-button-label', __( 'Add all to Cart', 'yith-woocommerce-frequently-bought-together' ) );
$label_total = get_option( 'yith-wfbt2-total-label', __( 'Price for all', 'yith-woocommerce-frequently-bought-together' ) );
$title       = get_option( 'yith-wfbt2-form-title', __( 'Frequently Bought Together', 'yith-woocommerce-frequently-bought-together' ) ); // phpcs:ignore
$size        = apply_filters( 'yith_wcfbt2_image_size', 'yith_wfbt_image_size' );
$main_product_id = $main_product->get_id();

$prepared_sets = array();

foreach ( $categories as $category ) {
	$set_name  = isset( $category['name'] ) ? trim( $category['name'] ) : '';
	$set_image = isset( $category['image'] ) ? $category['image'] : '';
	$set_types = isset( $category['types'] ) && is_array( $category['types'] ) ? $category['types'] : array();

	if ( empty( $set_types ) ) {
		$legacy_items = isset( $category['items'] ) && is_array( $category['items'] ) ? $category['items'] : array();
		if ( ! empty( $legacy_items ) ) {
			$set_types[] = array(
				'name'              => '',
				'default_product_id' => 0,
				'trigger_product_id' => 0,
				'options'           => $legacy_items,
			);
		}
	}

	if ( empty( $set_types ) ) {
		continue;
	}

	$prepared_types = array();
	$set_total      = 0;

	foreach ( $set_types as $set_type_index => $set_type ) {
		$type_name            = isset( $set_type['name'] ) ? trim( $set_type['name'] ) : '';
		$type_default_product = isset( $set_type['default_product_id'] ) ? absint( $set_type['default_product_id'] ) : 0;
		$type_trigger_product = isset( $set_type['trigger_product_id'] ) ? $set_type['trigger_product_id'] : 0;
		if ( is_array( $type_trigger_product ) ) {
			$type_trigger_product = reset( $type_trigger_product );
		}
		$type_trigger_product = absint( $type_trigger_product );
		$type_conditional_mode = ! empty( $set_type['conditional_mode'] );
		$type_options_raw     = isset( $set_type['options'] ) && is_array( $set_type['options'] ) ? $set_type['options'] : array();
		$prepared_options     = array();
		$selected_product_id  = 0;

		if ( empty( $type_options_raw ) && isset( $set_type['products'] ) ) {
			$type_qty_map = isset( $set_type['qty'] ) && is_array( $set_type['qty'] ) ? $set_type['qty'] : array();
			$type_trigger_map = isset( $set_type['trigger'] ) && is_array( $set_type['trigger'] ) ? $set_type['trigger'] : array();
			$type_trigger_extra_map = isset( $set_type['trigger_extra_qty'] ) && is_array( $set_type['trigger_extra_qty'] ) ? $set_type['trigger_extra_qty'] : array();
			$type_products = $set_type['products'];
			if ( is_string( $type_products ) ) {
				$type_products = explode( ',', $type_products );
			}
			$type_products = array_filter( array_map( 'absint', (array) $type_products ) );
			foreach ( $type_products as $type_product_id ) {
				$type_options_raw[] = array(
					'product_id' => $type_product_id,
					'qty'        => isset( $type_qty_map[ $type_product_id ] ) ? max( 0, absint( $type_qty_map[ $type_product_id ] ) ) : 1,
					'trigger_product_id' => isset( $type_trigger_map[ $type_product_id ] ) ? absint( $type_trigger_map[ $type_product_id ] ) : 0,
					'trigger_extra_qty'  => isset( $type_trigger_extra_map[ $type_product_id ] ) ? max( 0, absint( $type_trigger_extra_map[ $type_product_id ] ) ) : 0,
				);
			}
		}

		foreach ( $type_options_raw as $type_option ) {
			$option_product = false;
			$option_id      = 0;
			$option_qty     = 1;
			$option_avail   = false;
			$option_trigger_product = 0;
			$option_trigger_extra   = 0;

			if ( is_array( $type_option ) && isset( $type_option['product'] ) && is_object( $type_option['product'] ) ) {
				$option_product = $type_option['product'];
				$option_id      = $option_product->get_id();
			} elseif ( is_array( $type_option ) && isset( $type_option['product_id'] ) ) {
				$option_id = absint( $type_option['product_id'] );
				if ( $option_id ) {
					$option_product = wc_get_product( $option_id );
				}
			}

			if ( ! $option_product || ! $option_id ) {
				continue;
			}

			$option_qty = isset( $type_option['qty'] ) ? max( 0, absint( $type_option['qty'] ) ) : 1;
			$option_avail = isset( $type_option['available'] ) ? (bool) $type_option['available'] : ( $option_product->is_purchasable() && $option_product->is_in_stock() );
			$option_trigger_product = isset( $type_option['trigger_product_id'] ) ? absint( $type_option['trigger_product_id'] ) : 0;
			$option_trigger_extra = isset( $type_option['trigger_extra_qty'] ) ? max( 0, absint( $type_option['trigger_extra_qty'] ) ) : 0;

			$option_stock_max = '';
			if ( $option_product->managing_stock() && ! $option_product->backorders_allowed() ) {
				$option_stock_qty = $option_product->get_stock_quantity();
				if ( is_numeric( $option_stock_qty ) && intval( $option_stock_qty ) > 0 ) {
					$option_stock_max = intval( $option_stock_qty );
				}
			}

			if ( $option_stock_max && $option_qty > $option_stock_max ) {
				$option_qty = $option_stock_max;
			}

			$option_name  = $option_product->get_title();
			$option_price = floatval( wc_get_price_to_display( $option_product ) );
			$option_total = $option_price * $option_qty;

			$prepared_options[] = array(
				'id'         => $option_id,
				'name'       => $option_name,
				'qty'        => $option_qty,
				'price'      => $option_price,
				'price_html' => $option_product->get_price_html(),
				'total'      => $option_total,
				'image'      => $option_product->get_image( $size ),
				'stock_max'  => $option_stock_max,
				'available'  => $option_avail,
				'trigger_product_id' => $option_trigger_product,
				'trigger_extra_qty'  => $option_trigger_extra,
			);

			if ( $option_trigger_product || $option_trigger_extra || $option_qty < 1 ) {
				$type_conditional_mode = true;
			}
		}

		if ( empty( $prepared_options ) ) {
			continue;
		}

		if ( ! $type_conditional_mode ) {
			if ( $type_default_product ) {
				foreach ( $prepared_options as $prepared_option ) {
					if ( $prepared_option['available'] && $prepared_option['id'] === $type_default_product ) {
						$selected_product_id = $prepared_option['id'];
						break;
					}
				}
			}

			if ( ! $selected_product_id ) {
				foreach ( $prepared_options as $prepared_option ) {
					if ( $prepared_option['available'] ) {
						$selected_product_id = $prepared_option['id'];
						break;
					}
				}
			}

			if ( $selected_product_id ) {
				foreach ( $prepared_options as $prepared_option ) {
					if ( $prepared_option['id'] === $selected_product_id ) {
						$set_total += $prepared_option['total'];
						break;
					}
				}
			}
		}

		$prepared_types[] = array(
			'index'               => $set_type_index,
			'name'                => '' !== $type_name ? $type_name : sprintf( __( 'Tipo %d', 'yith-woocommerce-frequently-bought-together' ), $set_type_index + 1 ),
			'selected_product_id' => $selected_product_id,
			'trigger_product_id'  => $type_trigger_product,
			'conditional_mode'    => $type_conditional_mode,
			'options'             => $prepared_options,
		);
	}

	if ( empty( $prepared_types ) ) {
		continue;
	}

	if ( empty( $set_image ) ) {
		foreach ( $prepared_types as $prepared_type ) {
			foreach ( $prepared_type['options'] as $prepared_option ) {
				if ( $prepared_option['id'] === $prepared_type['selected_product_id'] ) {
					$set_image = $prepared_option['image'];
					break 2;
				}
			}
		}
		if ( empty( $set_image ) ) {
			$set_image = $prepared_types[0]['options'][0]['image'];
		}
	}

	$set_index = count( $prepared_sets );
	$prepared_sets[] = array(
		'index' => $set_index,
		'name'  => '' !== $set_name ? $set_name : sprintf( __( 'Set %d', 'yith-woocommerce-frequently-bought-together' ), $set_index + 1 ),
		'image' => $set_image,
		'total' => $set_total,
		'types' => $prepared_types,
	);
}

if ( empty( $prepared_sets ) ) {
	return;
}

$total = 0;
?>
<div class="yith-wfbt-section woocommerce yith-wfbt2-section yith-wfbt-categorical">
	<?php
	if ( $title ) {
		echo '<h3>' . esc_html( $title ) . '</h3>';
	}
	?>

	<form class="yith-wfbt-form" method="post" action="<?php echo esc_url( $url ); ?>">
		<input type="hidden" name="yith-wfbt2-main-product" value="<?php echo esc_attr( $main_product_id ); ?>"/>

		<div class="yith-wfbt-header">
			<div class="yith-wfbt-categories-bar">
				<?php foreach ( $prepared_sets as $prepared_set ) : ?>
					<div
						class="yith-wfbt-category yith-wfbt-set"
						data-set-index="<?php echo esc_attr( $prepared_set['index'] ); ?>"
						data-total="<?php echo esc_attr( $prepared_set['total'] ); ?>"
					>
						<div class="yith-wfbt-category-title"><?php echo esc_html( $prepared_set['name'] ); ?></div>
						<button type="button" class="yith-wfbt-category-trigger yith-wfbt-set-trigger" aria-expanded="false" aria-pressed="false">
							<span class="yith-wfbt-category-image yith-wfbt-set-image">
								<?php echo $prepared_set['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="yith-wfbt-category-chevron" aria-hidden="true"></span>
						</button>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="yith-wfbt-summary">
				<div class="yith-wfbt-submit-block yith-wfbt-total-block">
					<span class="total_price_label">
						<?php echo esc_html( $label_total ); ?>:
					</span>
					&nbsp;
					<span class="total_price" data-total="<?php echo esc_attr( $total ); ?>">
						<?php echo wc_price( $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</div>
				<button type="submit" class="yith-wfbt-submit-button button">
					<?php echo esc_html( $label ); ?>
				</button>
			</div>
		</div>

		<div class="yith-wfbt-category-panel yith-wfbt-set-panel" hidden></div>

		<div class="yith-wfbt-hidden-products"></div>

		<div class="yith-wfbt-set-templates" hidden>
			<?php foreach ( $prepared_sets as $prepared_set ) : ?>
				<div class="yith-wfbt-set-template" data-set-index="<?php echo esc_attr( $prepared_set['index'] ); ?>">
					<div class="yith-wfbt-category-options yith-wfbt-set-options" data-set-index="<?php echo esc_attr( $prepared_set['index'] ); ?>">
						<button type="button" class="yith-wfbt-category-close yith-wfbt-set-close" aria-label="<?php echo esc_attr__( 'Close', 'yith-woocommerce-frequently-bought-together' ); ?>">X</button>
						<?php foreach ( $prepared_set['types'] as $prepared_type ) : ?>
							<div
								class="yith-wfbt-set-type"
								data-type-index="<?php echo esc_attr( $prepared_type['index'] ); ?>"
								data-trigger-product-id="<?php echo esc_attr( isset( $prepared_type['trigger_product_id'] ) ? absint( $prepared_type['trigger_product_id'] ) : 0 ); ?>"
								data-conditional-mode="<?php echo esc_attr( ! empty( $prepared_type['conditional_mode'] ) ? '1' : '0' ); ?>"
							>
								<div class="yith-wfbt-set-type-title"><?php echo esc_html( $prepared_type['name'] ); ?></div>
								<?php foreach ( $prepared_type['options'] as $prepared_option ) : ?>
									<?php
									$type_conditional_mode = ! empty( $prepared_type['conditional_mode'] );
									$option_available = ! empty( $prepared_option['available'] );
									$option_selected  = ! $type_conditional_mode && $option_available && $prepared_type['selected_product_id'] === $prepared_option['id'];
									$option_classes   = 'yith-wfbt-category-option yith-wfbt-set-item yith-wfbt-type-option';
									if ( $option_selected ) {
										$option_classes .= ' is-selected';
									}
									if ( $type_conditional_mode ) {
										$option_classes .= ' is-auto';
									}
									if ( ! $option_available ) {
										$option_classes .= ' is-out-of-stock';
									}
									?>
									<div
										class="<?php echo esc_attr( $option_classes ); ?>"
										role="button"
										tabindex="<?php echo esc_attr( ( $option_available && ! $type_conditional_mode ) ? '0' : '-1' ); ?>"
										aria-disabled="<?php echo esc_attr( ( $option_available && ! $type_conditional_mode ) ? 'false' : 'true' ); ?>"
										data-available="<?php echo esc_attr( $option_available ? '1' : '0' ); ?>"
										data-selectable="<?php echo esc_attr( $type_conditional_mode ? '0' : '1' ); ?>"
										data-product-id="<?php echo esc_attr( $prepared_option['id'] ); ?>"
										data-price="<?php echo esc_attr( $prepared_option['price'] ); ?>"
										data-qty="<?php echo esc_attr( $prepared_option['qty'] ); ?>"
										data-trigger-product-id="<?php echo esc_attr( isset( $prepared_option['trigger_product_id'] ) ? absint( $prepared_option['trigger_product_id'] ) : 0 ); ?>"
										data-trigger-extra-qty="<?php echo esc_attr( isset( $prepared_option['trigger_extra_qty'] ) ? absint( $prepared_option['trigger_extra_qty'] ) : 0 ); ?>"
										<?php echo $prepared_option['stock_max'] ? 'data-max="' . esc_attr( $prepared_option['stock_max'] ) . '"' : ''; ?>
									>
										<span class="yith-wfbt-option-image">
											<?php echo $prepared_option['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
										<span class="yith-wfbt-option-details">
											<span class="yith-wfbt-option-name">
												<?php echo esc_html( $prepared_option['name'] ); ?>
												<?php if ( ! $option_available ) : ?>
													<span class="yith-wfbt-option-stock-note">(<?php esc_html_e( 'Sin stock', 'yith-woocommerce-frequently-bought-together' ); ?>)</span>
												<?php endif; ?>
											</span>
											<span class="yith-wfbt-set-item-prices">
												<span class="yith-wfbt-set-item-qty">
													<?php
													echo esc_html(
														sprintf(
															/* translators: %d quantity */
															__( 'Cantidad: %d', 'yith-woocommerce-frequently-bought-together' ),
															intval( $prepared_option['qty'] )
														)
													);
													?>
												</span>
												<span class="yith-wfbt-set-item-unit">
													<?php esc_html_e( 'Unidad', 'yith-woocommerce-frequently-bought-together' ); ?>:
													<?php echo $prepared_option['price_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												</span>
												<span class="yith-wfbt-set-item-total">
													<?php esc_html_e( 'Total', 'yith-woocommerce-frequently-bought-together' ); ?>:
													<?php echo wc_price( $prepared_option['total'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
												</span>
											</span>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</form>
</div>

<script>
	document.addEventListener("DOMContentLoaded", function() {
		var containers = document.querySelectorAll(".yith-wfbt-categorical");
		if (!containers.length) {
			return;
		}

		function formatPrice(price) {
			return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
		}

		Array.prototype.forEach.call(containers, function(container) {
			var form = container.querySelector(".yith-wfbt-form");
			if (!form) {
				return;
			}

			var panel = container.querySelector(".yith-wfbt-set-panel");
			var hiddenProductsWrap = container.querySelector(".yith-wfbt-hidden-products");
			var totalPriceSpan = container.querySelector(".yith-wfbt-total-block .total_price .woocommerce-Price-amount bdi");
			var totalPriceData = container.querySelector(".yith-wfbt-total-block .total_price");
			var activeSet = null;
			var selectedOptionsBySet = {};

			function readOptionData(optionNode) {
				if (!optionNode || optionNode.getAttribute("data-available") !== "1") {
					return null;
				}
				var productId = parseInt(optionNode.getAttribute("data-product-id"), 10);
				var qty = parseInt(optionNode.getAttribute("data-qty"), 10);
				var max = parseInt(optionNode.getAttribute("data-max"), 10);
				var price = parseFloat(optionNode.getAttribute("data-price") || 0);
				var triggerProductId = parseInt(optionNode.getAttribute("data-trigger-product-id"), 10);
				var triggerExtraQty = parseInt(optionNode.getAttribute("data-trigger-extra-qty"), 10);
				var selectable = optionNode.getAttribute("data-selectable") === "1";

				if (!productId) {
					return null;
				}
				if (isNaN(qty) || qty < 0) {
					qty = 0;
				}
				if (max && max > 0 && qty > max) {
					qty = max;
				}
				if (isNaN(triggerProductId) || triggerProductId < 0) {
					triggerProductId = 0;
				}
				if (isNaN(triggerExtraQty) || triggerExtraQty < 0) {
					triggerExtraQty = 0;
				}

				return {
					productId: productId,
					qty: qty,
					max: max,
					price: price,
					triggerProductId: triggerProductId,
					triggerExtraQty: triggerExtraQty,
					selectable: selectable
				};
			}

			function getSelectedProductIdsForSet(setIndex) {
				var ids = [];
				var typeMap = selectedOptionsBySet[setIndex] || {};
				Object.keys(typeMap).forEach(function(typeIndex) {
					if (!typeMap[typeIndex] || !typeMap[typeIndex].productId) {
						return;
					}
					var productId = parseInt(typeMap[typeIndex].productId, 10);
					if (!productId || ids.indexOf(productId) !== -1) {
						return;
					}
					ids.push(productId);
				});
				return ids;
			}

			function getEffectiveOptionQty(optionData, selectedProductIds) {
				if (!optionData) {
					return 0;
				}

				var qty = optionData.qty;
				var triggerProductId = optionData.triggerProductId || 0;
				var triggerExtraQty = optionData.triggerExtraQty || 0;
				var triggerMatched = !triggerProductId || selectedProductIds.indexOf(triggerProductId) !== -1;

				if (triggerProductId) {
					if (triggerExtraQty > 0) {
						qty = triggerMatched ? qty + triggerExtraQty : qty;
					} else {
						qty = triggerMatched ? qty : 0;
					}
				}

				if (optionData.max && optionData.max > 0 && qty > optionData.max) {
					qty = optionData.max;
				}
				if (qty < 0) {
					qty = 0;
				}

				return qty;
			}

			function updateConditionalOptionDisplay(optionNode, effectiveQty, optionData) {
				if (!optionNode) {
					return;
				}

				var visible = !!(optionData && effectiveQty > 0);
				optionNode.classList.toggle("is-hidden-by-condition", !visible);
				if (!visible) {
					return;
				}

				var qtyNode = optionNode.querySelector(".yith-wfbt-set-item-qty");
				if (qtyNode) {
					var qtyLabel = qtyNode.getAttribute("data-label");
					if (!qtyLabel) {
						qtyLabel = (qtyNode.textContent.split(":")[0] || "Cantidad").trim();
						qtyNode.setAttribute("data-label", qtyLabel);
					}
					qtyNode.textContent = qtyLabel + ": " + effectiveQty;
				}

				var totalNode = optionNode.querySelector(".yith-wfbt-set-item-total");
				if (totalNode) {
					var totalLabel = totalNode.getAttribute("data-label");
					if (!totalLabel) {
						totalLabel = (totalNode.textContent.split(":")[0] || "Total").trim();
						totalNode.setAttribute("data-label", totalLabel);
					}
					var currencySymbolNode = totalPriceSpan ? totalPriceSpan.querySelector(".woocommerce-Price-currencySymbol") : null;
					var symbolText = currencySymbolNode ? currencySymbolNode.textContent : "$";
					var totalValue = optionData.price * effectiveQty;
					totalNode.innerHTML = totalLabel + ': <span class="woocommerce-Price-currencySymbol">' + symbolText + "</span>" + formatPrice(totalValue);
				}
			}

			function setOptionSelectedWithinType(typeNode, optionNode) {
				if (!typeNode || !optionNode) {
					return;
				}
				var options = typeNode.querySelectorAll(".yith-wfbt-type-option");
				Array.prototype.forEach.call(options, function(node) {
					node.classList.remove("is-selected");
				});
				optionNode.classList.add("is-selected");
			}

			function getSetTemplate(setIndex) {
				return container.querySelector('.yith-wfbt-set-template[data-set-index="' + setIndex + '"]');
			}

			function initializeSelections() {
				selectedOptionsBySet = {};
				var templates = container.querySelectorAll(".yith-wfbt-set-template");
				Array.prototype.forEach.call(templates, function(template) {
					var setIndex = template.getAttribute("data-set-index");
					if (!setIndex) {
						return;
					}
					var typeMap = {};
					var typeNodes = template.querySelectorAll(".yith-wfbt-set-type");
					Array.prototype.forEach.call(typeNodes, function(typeNode) {
						var typeIndex = typeNode.getAttribute("data-type-index");
						if (null === typeIndex) {
							return;
						}
						if (typeNode.getAttribute("data-conditional-mode") === "1") {
							return;
						}

						var selectedNode = typeNode.querySelector('.yith-wfbt-type-option.is-selected[data-available="1"][data-selectable="1"]');
						if (!selectedNode) {
							selectedNode = typeNode.querySelector('.yith-wfbt-type-option[data-available="1"][data-selectable="1"]');
						}
						var optionData = readOptionData(selectedNode);
						if (optionData) {
							typeMap[typeIndex] = optionData;
						}
					});
					selectedOptionsBySet[setIndex] = typeMap;
				});
			}

			function getTypeTriggerProductId(typeNode) {
				if (!typeNode) {
					return 0;
				}
				var triggerProductId = parseInt(typeNode.getAttribute("data-trigger-product-id"), 10);
				return triggerProductId && triggerProductId > 0 ? triggerProductId : 0;
			}

			function isTriggerSatisfied(setIndex, triggerProductId) {
				if (!triggerProductId) {
					return true;
				}
				var typeMap = selectedOptionsBySet[setIndex] || {};
				var isSatisfied = false;
				Object.keys(typeMap).forEach(function(typeIndex) {
					if (isSatisfied || !typeMap[typeIndex]) {
						return;
					}
					var optionData = typeMap[typeIndex];
					if (parseInt(optionData.productId, 10) === triggerProductId) {
						isSatisfied = true;
					}
				});
				return isSatisfied;
			}

			function isTypeVisible(setIndex, typeNode) {
				var triggerProductId = getTypeTriggerProductId(typeNode);
				return isTriggerSatisfied(setIndex, triggerProductId);
			}

			function getSetItemsData(setIndex) {
				var typeMap = selectedOptionsBySet[setIndex] || {};
				var template = getSetTemplate(setIndex);
				var items = [];
				if (!template) {
					return items;
				}

				var selectedProductIds = getSelectedProductIdsForSet(setIndex);
				var typeNodes = template.querySelectorAll(".yith-wfbt-set-type");

				Array.prototype.forEach.call(typeNodes, function(typeNode) {
					var typeIndex = typeNode.getAttribute("data-type-index");
					if (null === typeIndex || !isTypeVisible(setIndex, typeNode)) {
						return;
					}

					var conditionalMode = typeNode.getAttribute("data-conditional-mode") === "1";
					if (conditionalMode) {
						var conditionalOptionNodes = typeNode.querySelectorAll('.yith-wfbt-type-option[data-available="1"]');
						Array.prototype.forEach.call(conditionalOptionNodes, function(optionNode) {
							var optionData = readOptionData(optionNode);
							var effectiveQty = getEffectiveOptionQty(optionData, selectedProductIds);
							if (!optionData || effectiveQty < 1) {
								return;
							}
							items.push({
								productId: optionData.productId,
								qty: effectiveQty,
								price: optionData.price
							});
							if (selectedProductIds.indexOf(optionData.productId) === -1) {
								selectedProductIds.push(optionData.productId);
							}
						});
						return;
					}

					var selectedData = typeMap[typeIndex];
					if (!selectedData) {
						return;
					}

					var selectedQty = getEffectiveOptionQty(selectedData, selectedProductIds);
					if (selectedQty < 1) {
						return;
					}

					items.push({
						productId: selectedData.productId,
						qty: selectedQty,
						price: selectedData.price
					});
					if (selectedProductIds.indexOf(selectedData.productId) === -1) {
						selectedProductIds.push(selectedData.productId);
					}
				});

				return items;
			}

			function applyPanelSelectionState(setIndex) {
				if (!panel) {
					return;
				}
				var typeMap = selectedOptionsBySet[setIndex] || {};
				var runningSelectedProductIds = getSelectedProductIdsForSet(setIndex);
				var typeNodes = panel.querySelectorAll(".yith-wfbt-set-type");
				Array.prototype.forEach.call(typeNodes, function(typeNode) {
					var typeIndex = typeNode.getAttribute("data-type-index");
					if (null === typeIndex) {
						return;
					}

					var typeVisible = isTypeVisible(setIndex, typeNode);
					typeNode.classList.toggle("is-hidden-by-trigger", !typeVisible);
					if (!typeVisible) {
						return;
					}

					var conditionalMode = typeNode.getAttribute("data-conditional-mode") === "1";
					if (conditionalMode) {
						var conditionalOptionNodes = typeNode.querySelectorAll(".yith-wfbt-type-option");
						Array.prototype.forEach.call(conditionalOptionNodes, function(optionNode) {
							var optionData = readOptionData(optionNode);
							var effectiveQty = getEffectiveOptionQty(optionData, runningSelectedProductIds);
							updateConditionalOptionDisplay(optionNode, effectiveQty, optionData);
							if (optionData && effectiveQty > 0 && runningSelectedProductIds.indexOf(optionData.productId) === -1) {
								runningSelectedProductIds.push(optionData.productId);
							}
						});
						return;
					}

					var regularOptionNodes = typeNode.querySelectorAll(".yith-wfbt-type-option");
					Array.prototype.forEach.call(regularOptionNodes, function(optionNode) {
						optionNode.classList.remove("is-hidden-by-condition");
					});

					var selectedData = typeMap[typeIndex];
					var selectedNode = null;
					if (selectedData && selectedData.productId) {
						selectedNode = typeNode.querySelector('.yith-wfbt-type-option[data-product-id="' + selectedData.productId + '"][data-available="1"][data-selectable="1"]');
					}
					if (!selectedNode) {
						selectedNode = typeNode.querySelector('.yith-wfbt-type-option[data-available="1"][data-selectable="1"]');
						var fallbackData = readOptionData(selectedNode);
						if (fallbackData) {
							if (!selectedOptionsBySet[setIndex]) {
								selectedOptionsBySet[setIndex] = {};
							}
							selectedOptionsBySet[setIndex][typeIndex] = fallbackData;
						}
					}
					if (selectedNode) {
						setOptionSelectedWithinType(typeNode, selectedNode);
						var selectedProductId = parseInt(selectedNode.getAttribute("data-product-id"), 10);
						if (selectedProductId && runningSelectedProductIds.indexOf(selectedProductId) === -1) {
							runningSelectedProductIds.push(selectedProductId);
						}
					}
				});
			}

			function closeSetPanel() {
				if (activeSet) {
					activeSet.classList.remove("is-open");
					var activeTrigger = activeSet.querySelector(".yith-wfbt-set-trigger");
					if (activeTrigger) {
						activeTrigger.setAttribute("aria-expanded", "false");
					}
				}
				if (panel) {
					panel.hidden = true;
					panel.innerHTML = "";
				}
				activeSet = null;
			}

			function openSetPanel(setNode) {
				if (!panel || !setNode) {
					return;
				}

				var setIndex = setNode.getAttribute("data-set-index");
				var template = getSetTemplate(setIndex);
				if (!template) {
					closeSetPanel();
					return;
				}

				closeSetPanel();
				panel.innerHTML = template.innerHTML;
				panel.hidden = false;
				applyPanelSelectionState(setIndex);

				activeSet = setNode;
				activeSet.classList.add("is-open");
				var trigger = activeSet.querySelector(".yith-wfbt-set-trigger");
				if (trigger) {
					trigger.setAttribute("aria-expanded", "true");
				}
			}

			function setSetSelected(setNode, selected) {
				if (!setNode) {
					return;
				}

				if (selected) {
					clearSelectedSetsExcept(setNode);
				}

				setNode.classList.toggle("is-selected", !!selected);
				var trigger = setNode.querySelector(".yith-wfbt-set-trigger");
				if (trigger) {
					trigger.setAttribute("aria-pressed", selected ? "true" : "false");
				}
			}

			function clearSelectedSetsExcept(exceptNode) {
				var selectedSets = container.querySelectorAll(".yith-wfbt-set.is-selected");
				Array.prototype.forEach.call(selectedSets, function(node) {
					if (exceptNode && node === exceptNode) {
						return;
					}
					setSetSelected(node, false);
				});
			}

			function syncHiddenProducts() {
				if (!hiddenProductsWrap) {
					return;
				}
				hiddenProductsWrap.innerHTML = "";

				var qtyMap = {};
				var selectedSets = container.querySelectorAll(".yith-wfbt-set.is-selected");
				Array.prototype.forEach.call(selectedSets, function(setNode) {
					var setIndex = setNode.getAttribute("data-set-index");
					var setItems = getSetItemsData(setIndex);
					Array.prototype.forEach.call(setItems, function(setItem) {
						if (!qtyMap[setItem.productId]) {
							qtyMap[setItem.productId] = 0;
						}
						qtyMap[setItem.productId] += setItem.qty;
					});
				});

				Object.keys(qtyMap).forEach(function(productId) {
					var parsedProductId = parseInt(productId, 10);
					var qty = qtyMap[productId];
					if (!parsedProductId || qty < 1) {
						return;
					}

					var hiddenId = document.createElement("input");
					hiddenId.type = "hidden";
					hiddenId.name = "offeringID[]";
					hiddenId.value = parsedProductId;

					var hiddenQty = document.createElement("input");
					hiddenQty.type = "hidden";
					hiddenQty.name = "offeringQty[" + parsedProductId + "]";
					hiddenQty.value = qty;

					hiddenProductsWrap.appendChild(hiddenId);
					hiddenProductsWrap.appendChild(hiddenQty);
				});
			}

			function updateTotal() {
				var total = 0;

				var selectedSets = container.querySelectorAll(".yith-wfbt-set.is-selected");
				Array.prototype.forEach.call(selectedSets, function(setNode) {
					var setIndex = setNode.getAttribute("data-set-index");
					var setItems = getSetItemsData(setIndex);
					Array.prototype.forEach.call(setItems, function(setItem) {
						total += setItem.price * setItem.qty;
					});
				});

				if (totalPriceSpan) {
					var currencySymbol = totalPriceSpan.querySelector(".woocommerce-Price-currencySymbol");
					var symbolText = currencySymbol ? currencySymbol.textContent : "$";
					totalPriceSpan.innerHTML = "<span class='woocommerce-Price-currencySymbol'>" + symbolText + "</span>" + formatPrice(total);
				}
				if (totalPriceData) {
					totalPriceData.setAttribute("data-total", Math.round(total));
				}
			}

			function syncAll() {
				syncHiddenProducts();
				updateTotal();
			}

			container.addEventListener("click", function(event) {
				var closeButton = event.target.closest(".yith-wfbt-set-close");
				if (closeButton) {
					event.preventDefault();
					closeSetPanel();
					return;
				}

				var optionNode = event.target.closest(".yith-wfbt-type-option");
				if (optionNode && panel && !panel.hidden && panel.contains(optionNode)) {
					event.preventDefault();
					if (optionNode.getAttribute("data-available") !== "1") {
						return;
					}
					var typeNode = optionNode.closest(".yith-wfbt-set-type");
					var setIndexForOption = activeSet ? activeSet.getAttribute("data-set-index") : "";
					if (!typeNode || !setIndexForOption) {
						return;
					}
					if (optionNode.getAttribute("data-selectable") !== "1" || typeNode.getAttribute("data-conditional-mode") === "1") {
						return;
					}
					var typeIndex = typeNode.getAttribute("data-type-index");
					if (null === typeIndex) {
						return;
					}
					setOptionSelectedWithinType(typeNode, optionNode);
					var optionData = readOptionData(optionNode);
					if (!optionData) {
						return;
					}
					if (!selectedOptionsBySet[setIndexForOption]) {
						selectedOptionsBySet[setIndexForOption] = {};
					}
					selectedOptionsBySet[setIndexForOption][typeIndex] = optionData;
					applyPanelSelectionState(setIndexForOption);
					syncAll();
					return;
				}

				var setTrigger = event.target.closest(".yith-wfbt-set-trigger");
				if (!setTrigger) {
					return;
				}

				event.preventDefault();
				var setNode = setTrigger.closest(".yith-wfbt-set");
				if (!setNode) {
					return;
				}

				var isSelected = setNode.classList.contains("is-selected");

				if (!isSelected) {
					setSetSelected(setNode, true);
					openSetPanel(setNode);
				} else {
					openSetPanel(setNode);
				}

				syncAll();
			});

			container.addEventListener("keydown", function(event) {
				if ("Enter" !== event.key && " " !== event.key) {
					return;
				}
				var optionNode = event.target.closest(".yith-wfbt-type-option");
				if (!optionNode) {
					return;
				}
				if (optionNode.getAttribute("data-selectable") !== "1") {
					return;
				}
				event.preventDefault();
				optionNode.click();
			});

			document.addEventListener("click", function(event) {
				if (!activeSet || !panel || panel.hidden) {
					return;
				}

				if (!container.contains(event.target)) {
					closeSetPanel();
					return;
				}

				if (event.target.closest(".yith-wfbt-set-panel") || event.target.closest(".yith-wfbt-set-trigger")) {
					return;
				}

				var clickedSet = event.target.closest(".yith-wfbt-set");
				if (!clickedSet) {
					closeSetPanel();
				}
			});

			form.addEventListener("submit", function() {
				syncHiddenProducts();
			});

			initializeSelections();
			syncAll();
		});
	});
</script>

<style>
	.yith-wfbt-section {
		margin: 20px 0;
		padding: 16px;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		background: #fafafa;
	}

	.yith-wfbt-header {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		align-items: flex-start;
		gap: 16px;
	}

	.yith-wfbt-categories-bar {
		display: flex;
		flex-wrap: wrap;
		align-items: flex-start;
		gap: 12px;
		flex: 1 1 auto;
	}

	.yith-wfbt-category {
		display: inline-flex;
		flex-direction: column;
		gap: 4px;
		min-width: 84px;
		position: relative;
	}

	.yith-wfbt-category-title {
		font-size: 14px;
		color: #111;
		margin-bottom: 6px;
		line-height: 1.2;
	}

	.yith-wfbt-category-trigger {
		position: relative;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 84px;
		height: 84px;
		padding: 0;
		border: 1px solid #d7d7d7;
		border-radius: 6px;
		background: #f8f8f8;
		cursor: pointer;
		transition: border-color 0.2s ease;
	}

	.yith-wfbt-set.is-selected .yith-wfbt-category-trigger {
		border-color: #4b4f57;
		box-shadow: inset 0 0 0 1px #4b4f57;
	}

	.yith-wfbt-category-chevron {
		position: absolute;
		right: 8px;
		bottom: 8px;
		width: 8px;
		height: 8px;
		border-right: 2px solid #111;
		border-bottom: 2px solid #111;
		transform: rotate(45deg);
		transition: transform 0.2s ease;
	}

	.yith-wfbt-set.is-open .yith-wfbt-category-chevron {
		transform: rotate(225deg);
	}

	.yith-wfbt-category-image img {
		display: block;
		max-width: 78px;
		max-height: 78px;
		width: auto;
		height: auto;
		object-fit: cover;
		border-radius: 4px;
	}

	.yith-wfbt-summary {
		display: flex;
		flex-direction: column;
		gap: 10px;
		min-width: 180px;
	}

	.yith-wfbt-total-block {
		font-size: 18px;
		line-height: 1.2;
	}

	.yith-wfbt-summary .yith-wfbt-submit-button {
		width: max-content;
	}

	.yith-wfbt-set-panel {
		margin-top: 12px;
	}

	.yith-wfbt-category-options {
		position: relative;
		background: #f7f7f7;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		padding: 12px;
		max-height: 260px;
		overflow-y: auto;
	}

	.yith-wfbt-set-type {
		margin-bottom: 12px;
	}

	.yith-wfbt-set-type.is-hidden-by-trigger {
		display: none;
	}

	.yith-wfbt-set-type:last-child {
		margin-bottom: 0;
	}

	.yith-wfbt-set-type-title {
		font-size: 14px;
		font-weight: 600;
		margin-bottom: 8px;
	}

	.yith-wfbt-category-close {
		position: absolute;
		top: 6px;
		right: 10px;
		border: 0;
		background: transparent;
		font-size: 24px;
		line-height: 1;
		color: #000;
		cursor: pointer;
		padding: 0;
	}

	.yith-wfbt-category-option {
		display: flex;
		align-items: center;
		gap: 10px;
		background: #fff;
		border: 1px solid #ececec;
		border-radius: 6px;
		padding: 8px;
		margin-bottom: 8px;
	}

	.yith-wfbt-type-option {
		cursor: pointer;
		transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
	}

	.yith-wfbt-type-option.is-auto {
		cursor: default;
	}

	.yith-wfbt-type-option.is-hidden-by-condition {
		display: none;
	}

	.yith-wfbt-type-option.is-selected {
		border-color: #4b4f57;
		box-shadow: inset 0 0 0 1px #4b4f57;
		background: #f3f4f6;
	}

	.yith-wfbt-type-option.is-out-of-stock {
		opacity: 0.62;
		cursor: not-allowed;
		pointer-events: none;
	}

	.yith-wfbt-type-option.is-out-of-stock .yith-wfbt-option-name,
	.yith-wfbt-type-option.is-out-of-stock .yith-wfbt-set-item-prices {
		text-decoration: line-through;
	}

	.yith-wfbt-option-stock-note {
		margin-left: 6px;
		font-size: 12px;
	}

	.yith-wfbt-option-image img {
		display: block;
		width: 100px;
		height: auto;
	}

	.yith-wfbt-option-details {
		display: flex;
		flex-direction: column;
		gap: 6px;
		width: 100%;
		max-width: none;
		min-width: 0;
	}

	.yith-wfbt-option-name {
		white-space: normal;
		line-height: 1.2;
		word-break: break-word;
	}

	.yith-wfbt-set-item-prices {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		font-size: 13px;
	}

	@media (max-width: 768px) {
		.yith-wfbt-header {
			flex-direction: column;
		}

		.yith-wfbt-summary {
			width: 100%;
		}

		.yith-wfbt-summary .yith-wfbt-submit-button {
			width: 100%;
		}

		.yith-wfbt-category-options {
			max-width: 90vw;
			box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
		}

		.yith-wfbt-set-item-prices {
			flex-direction: column;
			gap: 2px;
		}
	}
</style>
