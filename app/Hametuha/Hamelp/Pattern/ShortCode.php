<?php

namespace Hametuha\Hamelp\Pattern;

/**
 * Shortcode pattern.
 *
 * @package hamelp
 */
abstract class ShortCode extends Singleton {

	protected $code = '';

	protected $inner_content = false;

	protected $dashicons = '';

	/**
	 * Do something in constructor.
	 */
	protected function init() {
		add_shortcode( $this->get_code(), [ $this, 'prepare_rendering' ] );
		add_action( 'register_shortcode_ui', [ $this, 'register_shortcode_ui' ] );
	}

	/**
	 * Return label for this shortcode.
	 *
	 * @return string
	 */
	abstract protected function get_label();

	/**
	 * Prepare and render codes.
	 *
	 * @param array $atts
	 * @param string $content
	 * @return string
	 */
	public function prepare_rendering( $atts = [], $content = '' ) {
		$default = [];
		foreach ( $this->get_code_attributes() as $attr ) {
			$default[ $attr['attr'] ] = isset( $attr['default'] ) && $attr['default'] ? $attr['default']  : '';
		}
		$atts = shortcode_atts( $default, $atts, $this->get_code() );
		return $this->trim( $this->render_code( $atts, $content ) );
	}

	/**
	 * Render shortcode content
	 *
	 * @param array  $atts
	 * @param string $content
	 * @return string
	 */
	abstract public function render_code( $atts, $content = '' );

	/**
	 * Render code setting.
	 *
	 * @return array
	 */
	protected function get_code_setting() {
		$setting = [
			'label' => $this->get_label(),
			'attrs' => array_map( [ $this, 'fill_description' ], $this->get_code_attributes() ),
		];
		if ( $this->inner_content ) {
			$setting['inner_content'] = true;
		}
		if ( $this->dashicons ) {
			$setting['listItemImage'] = $this->dashicons;
		}
		/**
		 * hamelp_shortcode_setting
		 *
		 * @param array  $setting
		 * @param string $shortcode
		 * @return array
		 */
		return apply_filters( 'hamelp_shortcode_setting', $setting, $this->get_code() );
	}

	/**
	 * Attributes for shortcode.
	 *
	 * @return array{ attr:string, label:string, type:string, default:string }[]
	 */
	protected function get_code_attributes() {
		return [];
	}

	/**
	 * Fill description with default value.
	 *
	 * @param array $attr
	 * @return array
	 */
	protected function fill_description( $attr ) {
		if ( ! isset( $attr['default'] ) || ! $attr['default']) {
			return $attr;
		}
		if ( isset( $attr['description'] ) && ! empty( $attr['description'] ) ) {
			return $attr;
		}
		$attr['description'] = sprintf( __( 'Default value is "%s".', 'hamelp' ), $attr['default'] );
		return $attr;
	}

	/**
	 * Register short code content
	 */
	public function register_shortcode_ui() {
		shortcode_ui_register_for_shortcode( $this->get_code(), $this->get_code_setting() );
	}

	/**
	 * Get code slug.
	 */
	protected function get_code() {
		if ( $this->code ) {
			return $this->code;
		} else {
			$class_name = explode( "\\", get_called_class() );
			$class_name = $class_name[ count( $class_name ) - 1 ];
			return strtolower( $class_name );
		}
	}

	/**
	 * Trim html and remove
	 *
	 * @param $html
	 * @return string
	 */
	protected function trim( $html ) {
		return implode( "\n", array_filter( array_map( function( $line ) {
			return trim( $line );
		}, explode( "\n", $html ) ) ) );
	}
}
