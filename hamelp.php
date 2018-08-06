<?php
/**
 * Plugin Name:     Hamelp
 * Plugin URI:     	https://wordpress.org/extend/plugins/hamelp
 * Description:     FAQ generator by Hametuha.
 * Version:         1.0.0
 * Author:          Takahashi_Fumiki
 * Author URI:      https://takahashifumiki.com
 * Text Domain:     hamelp
 * Domain Path:     /languages
 * License:         GPL3 or Later
 * @package         hamelp
 */

// Do not load directory.
defined( 'ABSPATH' ) || die();

// Check version and load plugin if possible.
add_action( 'plugins_loaded', 'hamelp_init' );

function hamelp_init() {
	// i18n.
	load_plugin_textdomain( 'hamelp', false, basename( dirname( __FILE__ ) ) . '/languages' );
	if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
		require dirname( __FILE__ ) . '/vendor/autoload.php';
		call_user_func( [ 'Hametuha\\Hamelp', 'get' ]);
	} else {
		add_action( 'admin_notices', 'hamelp_version_error' );
	}
}

/**
 * Display version error
 *
 * @internal
 */
function hamelp_version_error() {
	// translators: %1$s required PHP version, %2$s current PHP version.
	printf( '<div class="error"><p>%s</p></div>', sprintf( esc_html__( 'Hamelp requires PHP %1$s, but your PHP version is %2$s. Please consider upgrade.', 'hamelp' ), phpversion() ) );
}

/**
 * Get asset url
 *
 * @return string
 */
function hamelp_asset_url() {
	return plugin_dir_url( __FILE__ ) . 'assets';
}

/**
 * Get plugin version.
 */
function hamelp_version() {
	static $version = null;
	if ( is_null( $version ) ) {
		$file_info = get_file_data( __FILE__, [
			'version' => 'Version:'
		] );
		$version = trim( $file_info['version'] );
	}
	return $version;
}
