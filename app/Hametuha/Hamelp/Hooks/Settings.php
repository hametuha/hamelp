<?php
/**
 * Settings hook handler.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Hooks;

use Hametuha\Hamelp\Pattern\Singleton;

/**
 * Registers AI-related settings fields.
 */
class Settings extends Singleton {

	/**
	 * Option name for AI site context.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'hamelp_ai_context';

	/**
	 * Initialize hooks.
	 */
	protected function init() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings fields on the Reading settings page.
	 */
	public function register_settings() {
		register_setting(
			'reading',
			self::OPTION_KEY,
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => '',
			]
		);

		add_settings_section(
			'hamelp_ai_section',
			__( 'AI Overview', 'hamelp' ),
			[ $this, 'render_section' ],
			'reading'
		);

		add_settings_field(
			self::OPTION_KEY,
			__( 'Site Context', 'hamelp' ),
			[ $this, 'render_field' ],
			'reading',
			'hamelp_ai_section'
		);
	}

	/**
	 * Render section description.
	 */
	public function render_section() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Configure the AI Overview feature for the FAQ system.', 'hamelp' )
		);
	}

	/**
	 * Render the site context textarea field.
	 */
	public function render_field() {
		$value = get_option( self::OPTION_KEY, '' );
		printf(
			'<textarea name="%s" id="%s" class="large-text" rows="4">%s</textarea>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( self::OPTION_KEY ),
			esc_textarea( $value )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Describe your site context for AI. e.g., "This is a fiction publishing platform. Users are mostly writers and readers."', 'hamelp' )
		);
	}
}
