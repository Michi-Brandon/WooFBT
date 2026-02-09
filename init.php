<?php // phpcs:ignore WordPress.NamingConventions
/**
 * Plugin Name: CrossSelling Manual
 * Description: Sugiere productos que suelen comprarse juntos para aumentar las ventas.
 * Version: 1.0.0
 * Text Domain: CrossSellingManual
 * WC requires at least: 10.3
 * WC tested up to: 10.5
 * Requires Plugins: woocommerce
 */

/*

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Add message if WooCommerce is not installed.
 *
 * @since 1.0.0
 * @return void
 */
function yith_wfbt2_free_install_woocommerce_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'YITH WooCommerce Frequently Bought Together 2 is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-frequently-bought-together' ); ?></p>
	</div>
	<?php
}


/**
 * Add message if premium version is installed.
 *
 * @since 1.0.0
 * @return void
 */
function yith_wfbt2_install_free_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'You can\'t activate the free version of YITH WooCommerce Frequently Bought Together 2 while you are using the premium one.', 'yith-woocommerce-frequently-bought-together' ); ?></p>
	</div>
	<?php
}

if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );


if ( ! defined( 'YITH_WFBT2_VERSION' ) ) {
	define( 'YITH_WFBT2_VERSION', '1.54.0' );
}

if ( ! defined( 'YITH_WFBT2_FREE_INIT' ) ) {
	define( 'YITH_WFBT2_FREE_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YITH_WFBT2_INIT' ) ) {
	define( 'YITH_WFBT2_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YITH_WFBT2' ) ) {
	define( 'YITH_WFBT2', true );
}

if ( ! defined( 'YITH_WFBT2_FILE' ) ) {
	define( 'YITH_WFBT2_FILE', __FILE__ );
}

if ( ! defined( 'YITH_WFBT2_URL' ) ) {
	define( 'YITH_WFBT2_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'YITH_WFBT2_DIR' ) ) {
	define( 'YITH_WFBT2_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YITH_WFBT2_TEMPLATE_PATH' ) ) {
	define( 'YITH_WFBT2_TEMPLATE_PATH', YITH_WFBT2_DIR . 'templates' );
}

if ( ! defined( 'YITH_WFBT2_ASSETS_URL' ) ) {
	define( 'YITH_WFBT2_ASSETS_URL', YITH_WFBT2_URL . 'assets' );
}

if ( ! defined( 'YITH_WFBT2_SLUG' ) ) {
	define( 'YITH_WFBT2_SLUG', 'yith-woocommerce-frequently-bought-together-2' );
}



if ( ! defined( 'YITH_WFBT2_MODE_META' ) ) {
	define( 'YITH_WFBT2_MODE_META', '_yith_wfbt2_mode' );
}

if ( ! defined( 'YITH_WFBT2_CATEGORIES_META' ) ) {
	define( 'YITH_WFBT2_CATEGORIES_META', '_yith_wfbt2_categories' );
}


// Plugin Framework Loader.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'plugin-fw/init.php';
}

/**
 * Init.
 *
 * @since 1.0.0
 * @return void
 */
function yith_wfbt2_free_init() {

	if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
		yith_plugin_fw_load_plugin_textdomain( 'yith-woocommerce-frequently-bought-together', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	// Load required classes and functions.
	require_once 'includes/class.yith-wfbt.php';

	// Let's start the game!
	YITH_WFBT2();
}

add_action( 'yith_wfbt2_free_init', 'yith_wfbt2_free_init' );

/**
 * Install.
 *
 * @since 1.0.0
 * @return void
 */
function yith_wfbt2_free_install() {

	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'yith_wfbt2_free_install_woocommerce_admin_notice' );
	} elseif ( defined( 'YITH_WFBT2_PREMIUM' ) ) {
		add_action( 'admin_notices', 'yith_wfbt2_install_free_admin_notice' );
		deactivate_plugins( plugin_basename( __FILE__ ) );
	} else {
		do_action( 'yith_wfbt2_free_init' );
	}
}

add_action( 'plugins_loaded', 'yith_wfbt2_free_install', 11 );

