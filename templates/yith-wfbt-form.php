<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Form template
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\FrequentlyBoughtTogether
 * @version 1.0.4
 */

if ( ! defined( 'YITH_WFBT2' ) ) {
	exit;
} // Exit if accessed directly.

global $product;

if ( ! isset( $products ) ) {
	return;
}

$mode = isset( $mode ) ? $mode : 'simple';

if ( 'categorical' === $mode ) {
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

	$main_price = floatval( wc_get_price_to_display( $main_product ) );
	$total      = $main_price;
	$prepared_categories = array();

	foreach ( $categories as $category ) {
		$category_products = isset( $category['products'] ) ? $category['products'] : array();
		if ( empty( $category_products ) ) {
			continue;
		}
		$default_product = $category_products[0];
		$total          += floatval( wc_get_price_to_display( $default_product ) );
		$prepared_categories[] = $category;
	}

	if ( empty( $prepared_categories ) ) {
		return;
	}

	$category_index = 0;
	?>
	<div class="yith-wfbt-section woocommerce yith-wfbt2-section yith-wfbt-categorical">
		<?php
		if ( $title ) {
			echo '<h3>' . esc_html( $title ) . '</h3>';
		}
		?>

		<form class="yith-wfbt-form" method="post" action="<?php echo esc_url( $url ); ?>">
			<input type="hidden" name="yith-wfbt2-main-product" value="<?php echo esc_attr( $main_product->get_id() ); ?>" />

			<div class="yith-wfbt-header">
				<div class="yith-wfbt-categories-bar">
					<?php
					foreach ( $prepared_categories as $category ) {
						$category_name     = isset( $category['name'] ) ? $category['name'] : '';
						$category_products = isset( $category['products'] ) ? $category['products'] : array();
						$category_match    = isset( $category['match'] ) ? trim( $category['match'] ) : '';
						$category_message  = isset( $category['message'] ) ? $category['message'] : '';

						if ( empty( $category_products ) ) {
							continue;
						}

						$default_product = $category_products[0];
						$default_id      = $default_product->get_id();
						$default_image   = $default_product->get_image( $size );
						?>
						<div
							class="yith-wfbt-category"
							data-category-index="<?php echo esc_attr( $category_index ); ?>"
							data-default-id="<?php echo esc_attr( $default_id ); ?>"
							data-match="<?php echo esc_attr( $category_match ); ?>"
							data-message="<?php echo esc_attr( $category_message ); ?>"
						>
							<div class="yith-wfbt-category-title">
								<?php
								if ( $category_name ) {
									echo esc_html( $category_name );
								} else {
									echo esc_html( sprintf( __( 'Category %d', 'yith-woocommerce-frequently-bought-together' ), $category_index + 1 ) );
								}
								?>
							</div>
							<button type="button" class="yith-wfbt-category-trigger" aria-expanded="false">
								<span class="yith-wfbt-category-image">
									<?php echo $default_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="yith-wfbt-category-chevron" aria-hidden="true"></span>
							</button>
							<div class="yith-wfbt-category-options" data-category-index="<?php echo esc_attr( $category_index ); ?>" hidden>
								<button type="button" class="yith-wfbt-category-close" aria-label="<?php echo esc_attr__( 'Close', 'yith-woocommerce-frequently-bought-together' ); ?>">X</button>
								<?php
								foreach ( $category_products as $category_product ) {
									$category_product_id    = $category_product->get_id();
									$category_product_price = wc_get_price_to_display( $category_product );
									$category_product_name  = $category_product->get_title();
									$category_stock_max     = '';
									if ( $category_product->managing_stock() && ! $category_product->backorders_allowed() ) {
										$category_stock_qty = $category_product->get_stock_quantity();
										if ( is_numeric( $category_stock_qty ) && intval( $category_stock_qty ) > 0 ) {
											$category_stock_max = intval( $category_stock_qty );
										}
									}
									$category_product_image = $category_product->get_image( $size );
									$option_selected         = ( $category_product_id === $default_id ) ? ' is-selected' : '';
									?>
									<label class="yith-wfbt-category-option<?php echo esc_attr( $option_selected ); ?>">
										<input
											type="checkbox"
											class="yith-wfbt-category-checkbox"
											name="offeringID[]"
											value="<?php echo esc_attr( $category_product_id ); ?>"
											data-price="<?php echo esc_attr( $category_product_price ); ?>"
											<?php checked( $category_product_id, $default_id ); ?>
										/>
										<span class="yith-wfbt-option-image">
											<?php echo $category_product_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
										<span class="yith-wfbt-option-details">
											<span class="yith-wfbt-option-name">
												<?php echo esc_html( $category_product_name ); ?>
											</span>
											<span class="yith-wfbt-option-price">
												<?php echo $category_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</span>
											<span class="yith-wfbt-option-qty yith-wfbt-qty-control" data-product-id="<?php echo esc_attr( $category_product_id ); ?>">
												<button type="button" class="yith-wfbt-qty-btn" data-qty-action="dec" data-product-id="<?php echo esc_attr( $category_product_id ); ?>">-</button>
												<input
													type="number"
													class="yith-wfbt-qty-input"
													name="offeringQty[<?php echo esc_attr( $category_product_id ); ?>]"
													data-product-id="<?php echo esc_attr( $category_product_id ); ?>"
													value="1"
													min="1"
													<?php echo $category_stock_max ? 'max="' . esc_attr( $category_stock_max ) . '"' : ''; ?>
												/>
												<button type="button" class="yith-wfbt-qty-btn" data-qty-action="inc" data-product-id="<?php echo esc_attr( $category_product_id ); ?>">+</button>
											</span>
										</span>
									</label>
									<?php
								}
								?>
							</div>
						</div>
						<?php
						$category_index++;
					}
					?>
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

			<div class="yith-wfbt-category-panel" hidden></div>

			<?php
			$main_product_id     = $main_product->get_id();
			$main_product_price  = wc_get_price_to_display( $main_product );
			$main_stock_max      = '';
			if ( $main_product->managing_stock() && ! $main_product->backorders_allowed() ) {
				$main_stock_qty = $main_product->get_stock_quantity();
				if ( is_numeric( $main_stock_qty ) && intval( $main_stock_qty ) > 0 ) {
					$main_stock_max = intval( $main_stock_qty );
				}
			}
			?>
			<div class="yith-wfbt-main-notice" hidden></div>
			<div class="yith-wfbt-current-product">
				<div class="yith-wfbt-selected-item is-selected">
					<label>
						<input
							type="checkbox"
							class="yith-wfbt-category-checkbox"
							name="offeringID[]"
							value="<?php echo esc_attr( $main_product_id ); ?>"
							data-price="<?php echo esc_attr( $main_product_price ); ?>"
							checked
						/>
						<span class="yith-wfbt-selected-name">
							<?php echo esc_html__( 'This Product', 'yith-woocommerce-frequently-bought-together' ) . ': ' . esc_html( $main_product->get_title() ); ?>
						</span>
						<span class="yith-wfbt-selected-meta">
							<span class="yith-wfbt-selected-price">
								<?php echo $main_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="yith-wfbt-option-qty yith-wfbt-qty-control" data-product-id="<?php echo esc_attr( $main_product_id ); ?>">
								<button type="button" class="yith-wfbt-qty-btn" data-qty-action="dec" data-product-id="<?php echo esc_attr( $main_product_id ); ?>">-</button>
								<input
									type="number"
									class="yith-wfbt-qty-input"
									name="offeringQty[<?php echo esc_attr( $main_product_id ); ?>]"
									data-product-id="<?php echo esc_attr( $main_product_id ); ?>"
									value="1"
									min="1"
									<?php echo $main_stock_max ? 'max="' . esc_attr( $main_stock_max ) . '"' : ''; ?>
								/>
								<button type="button" class="yith-wfbt-qty-btn" data-qty-action="inc" data-product-id="<?php echo esc_attr( $main_product_id ); ?>">+</button>
							</span>
						</span>
					</label>
				</div>
			</div>

		</form>
	</div>

	<script>
		document.addEventListener("DOMContentLoaded", function() {
			var container = document.querySelector(".yith-wfbt-categorical");
			if (!container) {
				return;
			}

			var totalPriceSpan = container.querySelector(".yith-wfbt-total-block .total_price .woocommerce-Price-amount bdi");
			var totalPriceData = container.querySelector(".yith-wfbt-total-block .total_price");
			var basePrice = 0;

			var panel = container.querySelector(".yith-wfbt-category-panel");
			var activeCategory = null;
			var activeOptions = null;

			function formatPrice(price) {
				return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
			}

			function getQtyInput(productId) {
				if (!productId) {
					return null;
				}
				return container.querySelector('.yith-wfbt-qty-input[name="offeringQty[' + productId + ']"]');
			}

			function getQtyValue(productId) {
				var input = getQtyInput(productId);
				var value = input ? parseInt(input.value, 10) : 1;
				if (!value || value < 1) {
					value = 1;
				}
				var max = input ? parseInt(input.getAttribute("max"), 10) : null;
				if (max && value > max) {
					value = max;
				}
				return value;
			}

			function updateQtyButtons(productId) {
				var control = container.querySelector('.yith-wfbt-qty-control[data-product-id="' + productId + '"]');
				if (!control) {
					return;
				}
				var input = control.querySelector(".yith-wfbt-qty-input");
				if (!input) {
					return;
				}
				var decButton = control.querySelector('.yith-wfbt-qty-btn[data-qty-action="dec"]');
				var incButton = control.querySelector('.yith-wfbt-qty-btn[data-qty-action="inc"]');
				var qty = parseInt(input.value, 10);
				if (!qty || qty < 1) {
					qty = 1;
				}
				var max = parseInt(input.getAttribute("max"), 10);
				var hasMax = max && max > 0;
				if (decButton) {
					decButton.disabled = qty <= 1;
				}
				if (incButton) {
					incButton.disabled = hasMax ? qty >= max : false;
				}
			}

			function setQtyValue(productId, qty) {
				var input = getQtyInput(productId);
				if (!input) {
					return;
				}
				var max = parseInt(input.getAttribute("max"), 10);
				if (max && qty > max) {
					qty = max;
				}
				input.value = qty;
				updateQtyButtons(productId);
			}

			function updateTotal() {
				var total = basePrice;
				var checkboxes = container.querySelectorAll(".yith-wfbt-category-checkbox:checked");
				Array.prototype.forEach.call(checkboxes, function(checkbox) {
					var productId = checkbox.value;
					var qty = getQtyValue(productId);
					var price = parseFloat(checkbox.getAttribute("data-price") || 0);
					total += price * qty;
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

			function updateMainNotice() {
				var notice = container.querySelector(".yith-wfbt-main-notice");
				if (!notice) {
					return;
				}
				var categories = container.querySelectorAll(".yith-wfbt-category");
				var categoryConfig = {};
				Array.prototype.forEach.call(categories, function(category) {
					var index = category.getAttribute("data-category-index");
					if (!index) {
						return;
					}
					categoryConfig[index] = {
						match: (category.getAttribute("data-match") || "").toLowerCase(),
						message: category.getAttribute("data-message") || ""
					};
				});

				var matchedMessages = [];
				var checkedOptions = container.querySelectorAll(".yith-wfbt-category-checkbox:checked");
				Array.prototype.forEach.call(checkedOptions, function(checkbox) {
					var option = checkbox.closest(".yith-wfbt-category-option");
					if (!option) {
						return;
					}
					var optionsRoot = checkbox.closest(".yith-wfbt-category-options");
					if (!optionsRoot) {
						return;
					}
					var index = optionsRoot.getAttribute("data-category-index");
					if (!index || !categoryConfig[index]) {
						return;
					}
					var matchText = categoryConfig[index].match;
					var message = categoryConfig[index].message;
					if (!matchText || !message) {
						return;
					}
					var nameEl = option.querySelector(".yith-wfbt-option-name");
					var nameText = nameEl ? nameEl.textContent.toLowerCase() : "";
					if (nameText && nameText.indexOf(matchText) !== -1) {
						if (matchedMessages.indexOf(message) === -1) {
							matchedMessages.push(message);
						}
					}
				});

				if (matchedMessages.length) {
					notice.innerHTML = matchedMessages.map(function(msg) {
						return "<div class=\"yith-wfbt-main-notice-item\">" + msg + "</div>";
					}).join("");
					notice.removeAttribute("hidden");
				} else {
					notice.textContent = "";
					notice.setAttribute("hidden", "hidden");
				}
			}

			function syncCategory(category) {
				if (!category) {
					return;
				}
				var optionsRoot = (activeCategory === category && activeOptions) ? activeOptions : category;
				var checkedItems = optionsRoot.querySelectorAll(".yith-wfbt-category-checkbox:checked");
				var firstChecked = checkedItems.length ? checkedItems[0] : null;
				var options = optionsRoot.querySelectorAll(".yith-wfbt-category-option");
				Array.prototype.forEach.call(options, function(item) {
					var checkbox = item.querySelector(".yith-wfbt-category-checkbox");
					if (checkbox && checkbox.checked) {
						item.classList.add("is-selected");
					} else {
						item.classList.remove("is-selected");
					}
				});

				var hasSelection = checkedItems.length > 0;
				var lastSelectedId = category.getAttribute("data-last-selected-id");
				var lastSelectedCheckbox = null;
				if (lastSelectedId) {
					lastSelectedCheckbox = optionsRoot.querySelector(".yith-wfbt-category-checkbox[value='" + lastSelectedId + "']");
				}

				if (hasSelection) {
					category.classList.remove("has-no-selection");
					if (!lastSelectedCheckbox || !lastSelectedCheckbox.checked) {
						if (firstChecked) {
							lastSelectedId = firstChecked.value;
							category.setAttribute("data-last-selected-id", lastSelectedId);
							lastSelectedCheckbox = firstChecked;
						}
					}
				} else {
					category.classList.add("has-no-selection");
				}

				var sourceOption = null;
				if (hasSelection) {
					if (lastSelectedCheckbox && lastSelectedCheckbox.checked) {
						sourceOption = lastSelectedCheckbox.closest(".yith-wfbt-category-option");
					} else if (firstChecked) {
						sourceOption = firstChecked.closest(".yith-wfbt-category-option");
					}
				} else {
					if (lastSelectedCheckbox) {
						sourceOption = lastSelectedCheckbox.closest(".yith-wfbt-category-option");
					}
					if (!sourceOption) {
						var defaultId = category.getAttribute("data-default-id");
						if (defaultId) {
							var defaultCheckbox = optionsRoot.querySelector(".yith-wfbt-category-checkbox[value='" + defaultId + "']");
							if (defaultCheckbox) {
								sourceOption = defaultCheckbox.closest(".yith-wfbt-category-option");
							}
						}
					}
				}

				var imageTarget = category.querySelector(".yith-wfbt-category-image");
				if (!imageTarget) {
					return;
				}
				if (!sourceOption) {
					imageTarget.innerHTML = '';
					return;
				}
				var image = sourceOption.querySelector(".yith-wfbt-option-image");
				if (image) {
					imageTarget.innerHTML = image.innerHTML;
				}
			}


			function closeCategory(category) {
				if (!category) {
					return;
				}
				var trigger = category.querySelector(".yith-wfbt-category-trigger");
				if (trigger) {
					trigger.setAttribute("aria-expanded", "false");
				}
				category.classList.remove("is-open");
				if (activeCategory === category && activeOptions) {
					if (panel && panel.contains(activeOptions)) {
						category.appendChild(activeOptions);
					}
					activeOptions.setAttribute("hidden", "hidden");
					activeCategory = null;
					activeOptions = null;
					if (panel) {
						panel.setAttribute("hidden", "hidden");
					}
				}
			}

			function closeAllCategories() {
				var categories = container.querySelectorAll(".yith-wfbt-category");
				Array.prototype.forEach.call(categories, function(category) {
					closeCategory(category);
				});
			}

			function openCategory(category) {
				if (!category) {
					return;
				}
				var options = category.querySelector(".yith-wfbt-category-options");
				var trigger = category.querySelector(".yith-wfbt-category-trigger");
				if (!options) {
					return;
				}
				activeCategory = category;
				activeOptions = options;
				if (panel) {
					panel.appendChild(options);
					panel.removeAttribute("hidden");
				}
				options.removeAttribute("hidden");
				if (trigger) {
					trigger.setAttribute("aria-expanded", "true");
				}
				category.classList.add("is-open");
			}

			container.addEventListener("change", function(event) {
				if (event.target.classList.contains("yith-wfbt-qty-input")) {
					var productId = event.target.getAttribute("data-product-id");
					var value = parseInt(event.target.value, 10);
					if (!value || value < 1) {
						value = 1;
					}
					var max = parseInt(event.target.getAttribute("max"), 10);
					if (max && value > max) {
						value = max;
					}
					event.target.value = value;
					var input = getQtyInput(productId);
					if (input && input !== event.target) {
						input.value = value;
					}
					updateQtyButtons(productId);
					updateMainNotice();
					updateTotal();
					return;
				}
				if (!event.target.classList.contains("yith-wfbt-category-checkbox")) {
					return;
				}
				var category = event.target.closest(".yith-wfbt-category");
				if (!category && activeCategory) {
					category = activeCategory;
				}
				if (category && event.target.checked) {
					category.setAttribute("data-last-selected-id", event.target.value);
				}
				syncCategory(category);
				updateMainNotice();
				updateTotal();
			});

			container.addEventListener("click", function(event) {
				var closeButton = event.target.closest(".yith-wfbt-category-close");
				if (closeButton) {
					closeAllCategories();
					event.preventDefault();
					event.stopPropagation();
					return;
				}
				if (event.target.classList.contains("yith-wfbt-qty-input")) {
					event.stopPropagation();
					return;
				}
				if (event.target.classList.contains("yith-wfbt-qty-btn")) {
					var action = event.target.getAttribute("data-qty-action");
					var productId = event.target.getAttribute("data-product-id");
					var qty = getQtyValue(productId);
					if (action === "inc") {
						qty += 1;
					} else {
						qty = Math.max(1, qty - 1);
					}
					setQtyValue(productId, qty);
					updateMainNotice();
					updateTotal();
						event.preventDefault();
					event.stopPropagation();
					return;
				}
				var trigger = event.target.closest(".yith-wfbt-category-trigger");
				if (!trigger) {
					return;
				}
				var category = trigger.closest(".yith-wfbt-category");
				if (!category) {
					return;
				}
				var isOpen = category.classList.contains("is-open");
				closeAllCategories();
				if (!isOpen) {
					openCategory(category);
				}
				event.preventDefault();
			});

			document.addEventListener("click", function(event) {
				if (!container.contains(event.target)) {
					closeAllCategories();
				}
			});


			var categories = container.querySelectorAll(".yith-wfbt-category");
			Array.prototype.forEach.call(categories, function(category) {
				syncCategory(category);
			});
			updateMainNotice();
			var qtyInputs = container.querySelectorAll(".yith-wfbt-qty-input");
			Array.prototype.forEach.call(qtyInputs, function(input) {
				var productId = input.getAttribute("data-product-id");
				updateQtyButtons(productId);
			});
			updateTotal();
		});
	</script>

	<style>
		.yith-wfbt2-section {
			margin: 20px 0;
			padding: 16px;
			border: 1px solid #e0e0e0;
			border-radius: 8px;
			background: #fafafa;
		}

		.yith-wfbt-header {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			gap: 20px;
			align-items: flex-start;
		}

		.yith-wfbt-categories-bar {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			gap: 16px;
			align-items: flex-start;
		}

		.yith-wfbt-category {
			position: relative;
			text-align: center;
		}

		.yith-wfbt-category-title {
			font-weight: 600;
			margin-bottom: 6px;
			font-size: 12px;
		}

		.yith-wfbt-category-trigger {
			border: 1px solid #e0e0e0;
			background: #fff;
			padding: 6px;
			border-radius: 6px;
			cursor: pointer;
			position: relative;
			overflow: hidden;
		}

		.yith-wfbt-category-chevron {
			position: absolute;
			bottom: 8px;
			right: 8px;
			width: 8px;
			height: 8px;
			border-right: 2px solid #000;
			border-bottom: 2px solid #000;
			transform: rotate(45deg);
			transform-origin: center;
			pointer-events: none;
		}

		.yith-wfbt-category.is-open .yith-wfbt-category-chevron {
			transform: rotate(-135deg);
		}

		.yith-wfbt-category-image img {
			display: block;
			width: 70px;
			height: auto;
		}

		.yith-wfbt-category.has-no-selection .yith-wfbt-category-trigger::before,
		.yith-wfbt-category.has-no-selection .yith-wfbt-category-trigger::after {
			content: "";
			position: absolute;
			top: 50%;
			left: 8px;
			right: 8px;
			height: 2px;
			background: rgba(0, 0, 0, 0.35);
			transform-origin: center;
			z-index: 2;
			pointer-events: none;
		}

		.yith-wfbt-category.has-no-selection .yith-wfbt-category-trigger::before {
			transform: rotate(45deg);
		}

		.yith-wfbt-category.has-no-selection .yith-wfbt-category-trigger::after {
			transform: rotate(-45deg);
		}

		.yith-wfbt-summary {
			min-width: 200px;
		}

		.yith-wfbt-summary .yith-wfbt-submit-block {
			margin: 0 0 10px;
		}

		.yith-wfbt-summary .yith-wfbt-submit-button {
			width: 100%;
		}

		.yith-wfbt-category-panel {
			width: 100%;
			margin-top: 16px;
		}

		.yith-wfbt-category-options {
			max-height: 260px;
			position: relative;
			margin-top: 12px;
			padding: 28px 10px 16px;
			background: #fff;
			border: 1px solid #e5e5e5;
			border-radius: 8px;
			box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
			display: flex;
			flex-direction: column;
			gap: 8px;
			min-width: 0;
			max-width: none;
			width: 100%;
			min-height: 0;
			overflow-x: hidden;
			overflow-y: auto;
			-webkit-overflow-scrolling: touch;
			z-index: 20;
		}

		.yith-wfbt-category-close {
			position: absolute;
			top: 8px;
			right: 8px;
			width: 18px;
			height: 18px;
			padding: 0;
			border: none;
			background: transparent;
			color: #000;
			font-size: 16px;
			line-height: 1;
			font-weight: 600;
			cursor: pointer;
		}

		.yith-wfbt-category-options::after {
			content: "";
			position: sticky;
			bottom: 0;
			left: 0;
			right: 0;
			height: 18px;
			background: linear-gradient(to bottom, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.9));
			pointer-events: none;
		}

		.yith-wfbt-category-options[hidden] {
			display: none;
		}

		.yith-wfbt-category-option {
			position: relative;
			display: grid;
			grid-template-columns: auto auto minmax(0, 1fr);
			gap: 10px;
			align-items: center;
			padding: 6px;
			border-radius: 4px;
			cursor: pointer;
			min-width: 0;
			max-width: none;
			flex: 0 0 auto;
		}

		.yith-wfbt-category-checkbox {
			margin: 0;
			width: 16px;
			height: 16px;
		}

		.yith-wfbt-category-option.is-selected {
			background: #f1f1f1;
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

		.yith-wfbt-option-qty {
			flex-wrap: wrap;
		}

		.yith-wfbt-qty-control {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			flex-wrap: nowrap;
		}


		.yith-wfbt-qty-btn {
			width: 22px;
			height: 22px;
			border: 1px solid #d0d0d0;
			background: #fff;
			border-radius: 4px;
			line-height: 1;
			text-align: center;
			cursor: pointer;
			padding: 0;
			font-size: 14px;
			font-weight: 600;
			color: #333;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			appearance: none;
			-webkit-appearance: none;
		}

		.yith-wfbt-qty-btn:disabled {
			opacity: 0.45;
			cursor: not-allowed;
		}

		.yith-wfbt-qty-input::-webkit-outer-spin-button,
		.yith-wfbt-qty-input::-webkit-inner-spin-button {
			-webkit-appearance: none;
			margin: 0;
		}

		.yith-wfbt-qty-input {
			-moz-appearance: textfield;
		}

		.yith-wfbt-qty-input {
			width: 52px;
			min-width: 52px;
			padding: 2px 4px;
			text-align: center;
			border: 1px solid #d0d0d0;
			border-radius: 4px;
			box-sizing: border-box;
			font-size: 12px;
			color: #333;
			background: #fff;
		}

		.yith-wfbt-selected-meta {
			display: flex;
			flex-direction: column;
			align-items: flex-end;
			gap: 6px;
			margin-left: auto;
		}

		.yith-wfbt-selected-item .yith-wfbt-qty-control {
			margin: 0;
		}

		.yith-wfbt-current-product {
			margin-top: 16px;
		}

		.yith-wfbt-selected-item {
			background: #fff;
			border: 1px solid #e5e5e5;
			border-radius: 6px;
			padding: 8px 10px;
			margin-bottom: 8px;
		}

		.yith-wfbt-selected-item.is-selected {
			background: #f1f1f1;
		}

		.yith-wfbt-selected-item.is-fixed label {
			cursor: default;
		}

		.yith-wfbt-selected-item.is-fixed input {
			cursor: default;
		}

		.yith-wfbt-selected-item label {
			display: flex;
			flex-direction: row;
			align-items: center;
			gap: 10px;
			cursor: pointer;
			flex-wrap: wrap;
		}

		.yith-wfbt-selected-item input {
			margin: 0;
		}

		.yith-wfbt-selected-name {
			flex: 1 1 auto;
			min-width: 0;
			word-break: break-word;
		}

		.yith-wfbt-main-notice {
			margin: 6px 0 8px;
			font-size: 15px;
			color: #222;
		}

		.yith-wfbt-main-notice-item {
			margin-top: 4px;
		}

		.yith-wfbt-main-notice-item:first-child {
			margin-top: 0;
		}

		.yith-wfbt-selected-price {
			white-space: nowrap;
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
			.yith-wfbt-selected-item label {
				flex-wrap: nowrap;
				align-items: center;
			}
			.yith-wfbt-selected-name {
				flex: 1 1 auto;
				min-width: 0;
			}
			.yith-wfbt-selected-meta {
				width: auto;
				align-items: flex-end;
			}
		}
	</style>

	<?php
	return;
}

$index  = 0;
$thumbs = '';
$checks = '';
$total  = 0;

/**
 *  Current product
 *
 * @var WC_Product $current_product
 */
// set query.
$url = ! is_null( $product ) ? $product->get_permalink() : '';
$url = add_query_arg( 'action', 'yith_bought_together_2', $url );
$url = wp_nonce_url( $url, 'yith_bought_together_2' );
$size = apply_filters( 'yith_wcfbt2_image_size', 'yith_wfbt_image_size' );
foreach ( $products as $current_product ) {
	/**
	 * Current processed product instance.
	 *
	 * @var WC_Product $current_product
	 */
	// Get correct id if product is variation.
	$current_product_is_variation = $current_product->is_type( 'variation' );
	$current_product_id           = $current_product->get_id();
	$current_product_price        = wc_get_price_to_display( $current_product );
	$current_product_link         = $current_product->get_permalink();
	$current_product_image        = $current_product->get_image( $size );
	$current_product_title        = $current_product->get_title();

	if ( $index > 0 ) {
		$thumbs .= '<td class="image_plus image_plus_' . esc_attr( $index ) . '" data-rel="offeringID_' . esc_attr( $index ) . '">+</td>';
	}
	$thumbs .= '<td class="image-td" data-rel="offeringID_' . esc_attr( $index ) . '"><a href="' . esc_url( $current_product_link ) . '">' . $current_product_image . '</a></td>';

	ob_start();
	?>
	<li class="yith-wfbt-item">
		<label for="offeringID_<?php echo esc_attr( $index ); ?>">

			<input type="hidden" name="offeringID[]" id="offeringID_<?php echo esc_attr( $index ); ?>" class="active"
				value="<?php echo esc_attr( $current_product_id ); ?>"/>

			<span class="product-name">
				<?php echo ! $index ? esc_html__( 'This Product', 'yith-woocommerce-frequently-bought-together' ) . ': ' . esc_html( $current_product_title ) : esc_html( $current_product_title ); ?>
			</span>

			<?php

			if ( $current_product_is_variation ) {
				$attributes = $current_product->get_variation_attributes();
				$variations = array();

				foreach ( $attributes as $key => $attribute ) {
					$key = str_replace( 'attribute_', '', $key );

					$terms = get_terms(
						array(
							'taxonomy'   => sanitize_title( $key ),
							'menu_order' => 'ASC',
							'hide_empty' => false,
						)
					);

					foreach ( $terms as $term ) {//phpcs:ignore
						if ( ! is_object( $term ) || ! in_array( $term->slug, array( $attribute ), true ) ) {
							continue;
						}
						$variations[] = esc_html( $term->name );
					}
				}

				if ( ! empty( $variations ) ) {
					echo '<span class="product-attributes"> &ndash; ' . implode( ', ', $variations ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}

			// echo product price.
			echo ' &ndash; <span class="price">' . $current_product->get_price_html() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>

		</label>
	</li>
	<?php
	$checks .= ob_get_clean();
	// increment total.
	$total += floatval( $current_product_price );

	// increment index.
	$index++;
}

if ( $index < 2 ) {
	return; // exit if only available product is current.
}

// set button label.
$label       = get_option( 'yith-wfbt2-button-label', __( 'Add all to Cart', 'yith-woocommerce-frequently-bought-together' ) );
$label_total = get_option( 'yith-wfbt2-total-label', __( 'Price for all', 'yith-woocommerce-frequently-bought-together' ) );
$title       = get_option( 'yith-wfbt2-form-title', __( 'Frequently Bought Together', 'yith-woocommerce-frequently-bought-together' ) );//phpcs:ignore

?>

<div class="yith-wfbt-section woocommerce yith-wfbt2-section">
	<?php
	if ( $title ) {
		echo '<h3>' . esc_html( $title ) . '</h3>';
	}
	?>

	<form class="yith-wfbt-form" method="post" action="<?php echo esc_url( $url ); ?>">

		<table class="yith-wfbt-images">
			<tbody>
			<tr>
				<?php echo $thumbs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</tr>
			</tbody>
		</table>

		<div class="yith-wfbt-submit-block">
			<div class="price_text">
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

		<ul class="yith-wfbt-items">
			<?php echo $checks; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</ul>

	</form>
</div>

<style>
	.yith-wfbt2-section {
		margin: 20px 0;
		padding: 16px;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		background: #fafafa;
	}
</style>
