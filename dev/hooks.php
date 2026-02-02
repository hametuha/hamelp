<?php
/**
 * Development hooks for local environment.
 *
 * This file is automatically loaded in local environments.
 * To disable, define HAMELP_DEV_NO_EXTRA as true.
 *
 * @package hamelp
 */

// Only run in local environment.
$env = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
if ( 'local' !== $env || ( defined( 'HAMELP_DEV_NO_EXTRA' ) && HAMELP_DEV_NO_EXTRA ) ) {
	return;
}

/**
 * Customize user context for AI prompts.
 *
 * @param string   $context Existing context.
 * @param \WP_User $user    Current user.
 * @return string Modified context.
 */
add_filter(
	'hamelp_user_context',
	function ( $context, $user ) {
		// If this is admin, add prompt for editorial guidance.
		if ( $user->has_cap( 'manage_options' ) ) {
			$context .= "\nUser is a site administrator. Always start your response with '[Admin Mode]' and feel free to mention if any FAQ content seems incomplete or could be improved.";
		}
		return $context;
	},
	10,
	2
);
