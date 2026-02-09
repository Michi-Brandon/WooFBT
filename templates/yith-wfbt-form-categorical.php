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
	$set_items = isset( $category['items'] ) && is_array( $category['items'] ) ? $category['items'] : array();

	if ( empty( $set_items ) ) {
		continue;
	}

	$prepared_items = array();
	$set_total      = 0;

	foreach ( $set_items as $set_item ) {
		$item_product = false;
		$item_id      = 0;
		$item_qty     = 1;

		if ( is_array( $set_item ) && isset( $set_item['product'] ) && is_object( $set_item['product'] ) ) {
			$item_product = $set_item['product'];
			$item_id      = $item_product->get_id();
			$item_qty     = isset( $set_item['qty'] ) ? max( 1, absint( $set_item['qty'] ) ) : 1;
		} elseif ( is_array( $set_item ) && isset( $set_item['product_id'] ) ) {
			$item_id  = absint( $set_item['product_id'] );
			$item_qty = isset( $set_item['qty'] ) ? max( 1, absint( $set_item['qty'] ) ) : 1;
			if ( $item_id ) {
				$item_product = wc_get_product( $item_id );
			}
		}

		if ( ! $item_product || ! $item_id || ! $item_product->is_purchasable() || ! $item_product->is_in_stock() ) {
			continue;
		}

		$item_stock_max = '';
		if ( $item_product->managing_stock() && ! $item_product->backorders_allowed() ) {
			$item_stock_qty = $item_product->get_stock_quantity();
			if ( is_numeric( $item_stock_qty ) && intval( $item_stock_qty ) > 0 ) {
				$item_stock_max = intval( $item_stock_qty );
			}
		}

		if ( $item_stock_max && $item_qty > $item_stock_max ) {
			$item_qty = $item_stock_max;
		}

		if ( $item_qty < 1 ) {
			continue;
		}

		$item_name  = $item_product->get_title();
		$item_price = floatval( wc_get_price_to_display( $item_product ) );
		$item_total = $item_price * $item_qty;

		$prepared_items[] = array(
			'id'         => $item_id,
			'name'       => $item_name,
			'qty'        => $item_qty,
			'price'      => $item_price,
			'price_html' => $item_product->get_price_html(),
			'total'      => $item_total,
			'image'      => $item_product->get_image( $size ),
			'stock_max'  => $item_stock_max,
		);
		$set_total += $item_total;
	}

	if ( empty( $prepared_items ) ) {
		continue;
	}

	if ( empty( $set_image ) ) {
		$set_image = $prepared_items[0]['image'];
	}

	$set_index = count( $prepared_sets );
	$prepared_sets[] = array(
		'index' => $set_index,
		'name'  => '' !== $set_name ? $set_name : sprintf( __( 'Set %d', 'yith-woocommerce-frequently-bought-together' ), $set_index + 1 ),
		'image' => $set_image,
		'total' => $set_total,
		'items' => $prepared_items,
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
						<div class="yith-wfbt-set-options-title"><?php echo esc_html__( 'Set contiene', 'yith-woocommerce-frequently-bought-together' ); ?></div>
						<?php foreach ( $prepared_set['items'] as $prepared_item ) : ?>
							<div
								class="yith-wfbt-category-option yith-wfbt-set-item"
								data-product-id="<?php echo esc_attr( $prepared_item['id'] ); ?>"
								data-price="<?php echo esc_attr( $prepared_item['price'] ); ?>"
								data-qty="<?php echo esc_attr( $prepared_item['qty'] ); ?>"
								<?php echo $prepared_item['stock_max'] ? 'data-max="' . esc_attr( $prepared_item['stock_max'] ) . '"' : ''; ?>
							>
								<span class="yith-wfbt-option-image">
									<?php echo $prepared_item['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="yith-wfbt-option-details">
									<span class="yith-wfbt-option-name"><?php echo esc_html( $prepared_item['name'] ); ?></span>
									<span class="yith-wfbt-set-item-prices">
										<span class="yith-wfbt-set-item-qty">
											<?php
											echo esc_html(
												sprintf(
													/* translators: %d quantity */
													__( 'Cantidad: %d', 'yith-woocommerce-frequently-bought-together' ),
													intval( $prepared_item['qty'] )
												)
											);
											?>
										</span>
										<span class="yith-wfbt-set-item-unit">
											<?php esc_html_e( 'Unidad', 'yith-woocommerce-frequently-bought-together' ); ?>:
											<?php echo $prepared_item['price_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
										<span class="yith-wfbt-set-item-total">
											<?php esc_html_e( 'Total', 'yith-woocommerce-frequently-bought-together' ); ?>:
											<?php echo wc_price( $prepared_item['total'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
									</span>
								</span>
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

			function getSetTemplate(setIndex) {
				return container.querySelector('.yith-wfbt-set-template[data-set-index="' + setIndex + '"]');
			}

			function getSetItemsData(setIndex) {
				var template = getSetTemplate(setIndex);
				if (!template) {
					return [];
				}

				var itemNodes = template.querySelectorAll(".yith-wfbt-set-item");
				var items = [];
				Array.prototype.forEach.call(itemNodes, function(itemNode) {
					var productId = parseInt(itemNode.getAttribute("data-product-id"), 10);
					var qty = parseInt(itemNode.getAttribute("data-qty"), 10);
					var max = parseInt(itemNode.getAttribute("data-max"), 10);
					var price = parseFloat(itemNode.getAttribute("data-price") || 0);

					if (!productId) {
						return;
					}
					if (!qty || qty < 1) {
						qty = 1;
					}
					if (max && max > 0 && qty > max) {
						qty = max;
					}
					if (qty < 1) {
						return;
					}

					items.push({
						productId: productId,
						qty: qty,
						price: price
					});
				});

				return items;
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

				setNode.classList.toggle("is-selected", !!selected);
				var trigger = setNode.querySelector(".yith-wfbt-set-trigger");
				if (trigger) {
					trigger.setAttribute("aria-pressed", selected ? "true" : "false");
				}
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
				var isOpen = setNode.classList.contains("is-open");

				if (!isSelected) {
					setSetSelected(setNode, true);
					openSetPanel(setNode);
				} else if (isOpen) {
					setSetSelected(setNode, false);
					closeSetPanel();
				} else {
					openSetPanel(setNode);
				}

				syncAll();
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

	.yith-wfbt-set-options-title {
		font-size: 14px;
		font-weight: 600;
		color: #222;
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
