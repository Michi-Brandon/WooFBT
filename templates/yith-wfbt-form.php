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
	$categorical_template = __DIR__ . '/yith-wfbt-form-categorical.php';
	if ( ! file_exists( $categorical_template ) && defined( 'YITH_WFBT2_DIR' ) ) {
		$categorical_template = YITH_WFBT2_DIR . 'templates/yith-wfbt-form-categorical.php';
	}
	include $categorical_template;
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
