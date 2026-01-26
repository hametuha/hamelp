<?php
/**
 * Settings hook handler.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Hooks;

use Hametuha\Hamelp\Pattern\Singleton;

/**
 * Registers Hamelp settings page and fields.
 */
class Settings extends Singleton {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'hamelp-settings';

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'hamelp_settings';

	/**
	 * Option prefix for all AI settings.
	 *
	 * @var string
	 */
	const OPTION_PREFIX = 'hamelp_ai_';

	/**
	 * Option prefix for rate limiting settings.
	 *
	 * @var string
	 */
	const RATE_PREFIX = 'hamelp_rate_';

	/**
	 * Initialize hooks.
	 */
	protected function init() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add settings page under Settings menu.
	 */
	public function add_menu_page() {
		add_options_page(
			__( 'Hamelp Settings', 'hamelp' ),
			__( 'Hamelp', 'hamelp' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Get AI context field definitions.
	 *
	 * @return array[] Field definitions keyed by option suffix.
	 */
	protected function get_ai_fields(): array {
		return [
			'site_description' => [
				'label'       => __( 'Site Description', 'hamelp' ),
				'description' => __( 'What is this site? What does it do?', 'hamelp' ),
				'placeholder' => __( 'e.g., This is a fiction publishing platform called "Hametuha".', 'hamelp' ),
				'rows'        => 2,
			],
			'target_users'     => [
				'label'       => __( 'Target Users', 'hamelp' ),
				'description' => __( 'Who are the people likely asking questions?', 'hamelp' ),
				'placeholder' => __( 'e.g., Writers who publish novels, or readers.', 'hamelp' ),
				'rows'        => 2,
			],
			'response_tone'    => [
				'label'       => __( 'Response Tone', 'hamelp' ),
				'description' => __( 'How should the AI respond?', 'hamelp' ),
				'placeholder' => __( 'e.g., Polite and friendly.', 'hamelp' ),
				'rows'        => 1,
			],
			'notes'            => [
				'label'       => __( 'Notes', 'hamelp' ),
				'description' => __( 'Anything the AI should avoid or keep in mind?', 'hamelp' ),
				'placeholder' => __( 'e.g., Do not give legal advice. Do not recommend competing services.', 'hamelp' ),
				'rows'        => 2,
			],
		];
	}

	/**
	 * Get rate limiting field definitions.
	 *
	 * @return array[] Field definitions keyed by option suffix.
	 */
	protected function get_rate_fields(): array {
		return [
			'per_ip'        => [
				'label'       => __( 'Per IP Limit', 'hamelp' ),
				'description' => __( 'Maximum requests per IP within the time window.', 'hamelp' ),
				'type'        => 'number',
				'default'     => 5,
				'min'         => 1,
			],
			'window'        => [
				'label'       => __( 'Time Window (minutes)', 'hamelp' ),
				'description' => __( 'Time window for per-IP rate limiting.', 'hamelp' ),
				'type'        => 'number',
				'default'     => 10,
				'min'         => 1,
			],
			'daily'         => [
				'label'       => __( 'Daily Global Limit', 'hamelp' ),
				'description' => __( 'Maximum total requests per day across all users.', 'hamelp' ),
				'type'        => 'number',
				'default'     => 100,
				'min'         => 1,
			],
			'require_login' => [
				'label'       => __( 'Require Login', 'hamelp' ),
				'description' => __( 'Only allow logged-in users to use AI Overview.', 'hamelp' ),
				'type'        => 'checkbox',
				'default'     => '',
			],
		];
	}

	/**
	 * Register settings and fields.
	 */
	public function register_settings() {
		// AI Overview fields.
		$fields = $this->get_ai_fields();
		foreach ( $fields as $suffix => $field ) {
			$option_name = self::OPTION_PREFIX . $suffix;
			register_setting(
				self::OPTION_GROUP,
				$option_name,
				[
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'default'           => '',
				]
			);
		}

		add_settings_section(
			'hamelp_ai_section',
			__( 'AI Overview', 'hamelp' ),
			[ $this, 'render_section' ],
			self::PAGE_SLUG
		);

		foreach ( $fields as $suffix => $field ) {
			$option_name = self::OPTION_PREFIX . $suffix;
			add_settings_field(
				$option_name,
				$field['label'],
				[ $this, 'render_textarea' ],
				self::PAGE_SLUG,
				'hamelp_ai_section',
				[
					'option_name' => $option_name,
					'description' => $field['description'],
					'placeholder' => $field['placeholder'],
					'rows'        => $field['rows'],
				]
			);
		}

		// Rate limiting fields.
		$rate_fields = $this->get_rate_fields();
		foreach ( $rate_fields as $suffix => $field ) {
			$option_name = self::RATE_PREFIX . $suffix;
			$is_number   = 'number' === $field['type'];
			register_setting(
				self::OPTION_GROUP,
				$option_name,
				[
					'type'              => $is_number ? 'integer' : 'string',
					'sanitize_callback' => $is_number ? 'absint' : 'sanitize_text_field',
					'default'           => $field['default'],
				]
			);
		}

		add_settings_section(
			'hamelp_rate_section',
			__( 'Rate Limiting', 'hamelp' ),
			[ $this, 'render_rate_section' ],
			self::PAGE_SLUG
		);

		foreach ( $rate_fields as $suffix => $field ) {
			$option_name = self::RATE_PREFIX . $suffix;
			$renderer    = 'checkbox' === $field['type'] ? 'render_checkbox' : 'render_number';
			add_settings_field(
				$option_name,
				$field['label'],
				[ $this, $renderer ],
				self::PAGE_SLUG,
				'hamelp_rate_section',
				[
					'option_name' => $option_name,
					'description' => $field['description'],
					'default'     => $field['default'],
					'min'         => $field['min'] ?? null,
				]
			);
		}
	}

	/**
	 * Render settings page.
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Hamelp Settings', 'hamelp' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render section description.
	 */
	public function render_section() {
		printf(
			'<p>%s</p>',
			esc_html__( 'These settings help the AI generate more accurate answers to FAQ questions.', 'hamelp' )
		);
	}

	/**
	 * Render rate limiting section description.
	 */
	public function render_rate_section() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Protect the AI Overview endpoint from excessive usage. Each request costs LLM tokens.', 'hamelp' )
		);
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_textarea( array $args ) {
		$value = get_option( $args['option_name'], '' );
		printf(
			'<textarea name="%s" id="%s" class="large-text" rows="%d" placeholder="%s">%s</textarea>',
			esc_attr( $args['option_name'] ),
			esc_attr( $args['option_name'] ),
			(int) $args['rows'],
			esc_attr( $args['placeholder'] ),
			esc_textarea( $value )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html( $args['description'] )
		);
	}

	/**
	 * Render a number input field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_number( array $args ) {
		$value = get_option( $args['option_name'], $args['default'] );
		$min   = isset( $args['min'] ) ? sprintf( ' min="%d"', (int) $args['min'] ) : '';
		printf(
			'<input type="number" name="%s" id="%s" value="%d" class="small-text"%s />',
			esc_attr( $args['option_name'] ),
			esc_attr( $args['option_name'] ),
			(int) $value,
			$min // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		printf(
			'<p class="description">%s</p>',
			esc_html( $args['description'] )
		);
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox( array $args ) {
		$value = get_option( $args['option_name'], '' );
		printf(
			'<label><input type="checkbox" name="%s" id="%s" value="1" %s /> %s</label>',
			esc_attr( $args['option_name'] ),
			esc_attr( $args['option_name'] ),
			checked( $value, '1', false ),
			esc_html( $args['description'] )
		);
	}

	/**
	 * Get composed site context string for system prompt.
	 *
	 * @return string Composed context, empty if nothing is set.
	 */
	public static function get_site_context(): string {
		$parts = [
			'site_description' => __( 'About this site: %s', 'hamelp' ),
			'target_users'     => __( 'Target users: %s', 'hamelp' ),
			'response_tone'    => __( 'Response tone: %s', 'hamelp' ),
			'notes'            => __( 'Important notes: %s', 'hamelp' ),
		];

		$lines = [];
		foreach ( $parts as $suffix => $template ) {
			$value = get_option( self::OPTION_PREFIX . $suffix, '' );
			if ( ! empty( $value ) ) {
				$lines[] = sprintf( $template, trim( $value ) );
			}
		}
		return implode( "\n", $lines );
	}
}
