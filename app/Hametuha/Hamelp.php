<?php

namespace Hametuha;

use Hametuha\Hamelp\Pattern\Singleton;

/**
 * Hamelp object.
 *
 * @package hamelp
 */
class Hamelp extends Singleton {

	/**
	 * Do something in constructor.
	 */
	protected function init() {
		// Load hooks.
		foreach ( [ 'Hooks', 'Widgets' ] as $dir ) {
			$dir_path = __DIR__ . '/Hamelp/' . $dir;
			if ( ! is_dir( $dir_path ) ) {
				continue;
			}
			foreach ( scandir( $dir_path ) as $file ) {
				if ( preg_match( '#^([^_.].*)\.php$#u', $file, $match ) ) {
					$class_name = "Hametuha\\Hamelp\\{$dir}\\{$match[1]}";
					if ( class_exists( $class_name ) ) {
						call_user_func( [ $class_name, 'get' ] );
					}
				}
			}
		}
		// Register WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'hamelp', Hamelp\Commands::class );
		}
	}
}
