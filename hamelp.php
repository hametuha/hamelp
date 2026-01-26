<?php
/**
 * Plugin Name:     Hamelp
 * Plugin URI:      https://wordpress.org/plugins/hamelp
 * Description:     FAQ generator by Hametuha.
 * Version:         1.0.4
 * Author:          Takahashi Fumiki
 * Author URI:      https://takahashifumiki.com
 * Text Domain:     hamelp
 * Domain Path:     /languages
 * Requires at least: 6.6
 * Requires PHP:    7.4
 * License:         GPL3 or Later
 *
 * @package         hamelp
 */

// Do not load directory.
defined( 'ABSPATH' ) || die();

/**
 * Check version and load plugin if possible.
 */
function hamelp_init() {
	// i18n.
	load_plugin_textdomain( 'hamelp', false, basename( __DIR__ ) . '/languages' );
	if ( version_compare( phpversion(), '7.4.0', '>=' ) ) {
		require __DIR__ . '/vendor/autoload.php';
		call_user_func( [ 'Hametuha\\Hamelp', 'get' ] );
	} else {
		add_action( 'admin_notices', 'hamelp_version_error' );
	}
}
add_action( 'plugins_loaded', 'hamelp_init' );


/**
 * Register all file in wp-dependencies.json
 *
 * @return void
 */
function hamelp_register_assets() {
	$path = __DIR__ . '/wp-dependencies.json';
	if ( ! file_exists( $path ) ) {
		return;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$deps = json_decode( file_get_contents( $path ), true );
	if ( empty( $deps ) ) {
		return;
	}
	// Register all assets in json
	foreach ( $deps as $dep ) {
		if ( empty( $dep['path'] ) ) {
			continue;
		}
		$url = plugin_dir_url( __DIR__ . '/assets' ) . $dep['path'];
		switch ( $dep['ext'] ) {
			case 'css':
				wp_register_style( $dep['handle'], $url, $dep['deps'], $dep['hash'], $dep['media'] );
				break;
			case 'js':
				$footer = [ 'in_footer' => $dep['footer'] ];
				if ( in_array( $dep['strategy'], [ 'defer', 'async' ], true ) ) {
					$footer['strategy'] = $dep['strategy'];
				}
				wp_register_script( $dep['handle'], $url, $dep['deps'], $dep['hash'], $footer );
				break;
		}
	}
}
add_action( 'init', 'hamelp_register_assets' );

/**
 * Display version error
 *
 * @internal
 */
function hamelp_version_error() {
	// translators: %1$s required PHP version, %2$s current PHP version.
	printf( '<div class="error"><p>%s</p></div>', sprintf( esc_html__( 'Hamelp requires PHP %1$s, but your PHP version is %2$s. Please consider upgrade.', 'hamelp' ), '7.4', esc_html( phpversion() ) ) );
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
		$file_info = get_file_data(
			__FILE__,
			[
				'version' => 'Version:',
			]
		);
		$version   = trim( $file_info['version'] );
	}
	return $version;
}

/**
 * Get accessibility of help document.
 *
 * @param null|int|WP_post $post
 *
 * @return string
 */
function hamelp_get_accessibility( $post = null ) {
	$post = get_post( $post );
	return (string) get_post_meta( $post->ID, '_accessibility', true );
}

/**
 * Register all blocks in assets/blocks directory.
 *
 * @return void
 */
function hamelp_register_blocks() {
	$blocks_dir = __DIR__ . '/assets/blocks';
	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}
	foreach ( scandir( $blocks_dir ) as $block_name ) {
		if ( '.' === $block_name[0] ) {
			continue;
		}
		$block_path = $blocks_dir . '/' . $block_name;
		if ( is_dir( $block_path ) && file_exists( $block_path . '/block.json' ) ) {
			register_block_type( $block_path );
		}
	}
}
add_action( 'init', 'hamelp_register_blocks' );
