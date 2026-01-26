<?php

namespace Hametuha\Hamelp\Hooks;

use Hametuha\Hamelp\Pattern\ShortCode;

/**
 * Render search box.
 *
 * @package Render search box.
 */
class SearchBoxShortCode extends ShortCode {

	/**
	 * Shortcode name.
	 *
	 * @var string
	 */
	protected $code = 'hamelp-search';

	/**
	 * Dashicon class name for shortcode UI.
	 *
	 * @var string
	 */
	protected $dashicons = 'dashicons-search';

	/**
	 * Whether the search box has been rendered.
	 *
	 * @var bool
	 */
	protected static $rendered = false;

	/**
	 * Return label for this shortcode.
	 *
	 * @return string
	 */
	protected function get_label() {
		return __( 'FAQ Search Box', 'hamelp' );
	}

	/**
	 * Render shortcode content
	 *
	 * @todo Should allow multiple post types.
	 * @param array  $atts
	 * @param string $content
	 * @return string
	 */
	public function render_code( $atts, $content = '' ) {
		$place_holder = esc_attr( $atts['label'] );
		$button_label = esc_html( $atts['btn'] );
		$post_types   = implode(
			array_map(
				function ( $post_type ) {
					return sprintf( '<input type="hidden" name="post_type" value="%s" />', esc_attr( $post_type ) );
				},
				array_keys( PostType::get()->get_post_types() )
			)
		);
		$query        = get_search_query();
		$action       = esc_url( apply_filters( 'hamelp_endpoint', home_url( '' ) ) );
		if ( ! self::$rendered ) {
			wp_enqueue_script( 'hamelp-incsearch' );
			wp_enqueue_style( 'hamelp-incsearch' );
			wp_localize_script(
				'hamelp-incsearch',
				'HamelpIncSearch',
				[
					'endpoint' => rest_url( '/wp/v2/faq' ),
					'found'    => __( 'Found Posts:', 'hamelp' ),
					'notFound' => __( 'No posts found. Please change the query.', 'hamelp' ),
				]
			);
			self::$rendered = true;
		}
		$html = <<<HTML
			<form class="hamelp-search-box" action="{$action}">
				{$post_types}
				<div class="input-group">
					<input type="search" class="form-control hamelp-search-input" name="s" placeholder="{$place_holder}" value="{$query}" />
					<button class="btn btn-secondary hamelp-search-button" type="submit">{$button_label}</button>
				</div>
				<div class="hamelp-result-wrapper">
					<div class="hamelp-result list-group">
					</div>
				</div>
			</form>
HTML;
		return $html;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_code_attributes() {
		return [
			[
				'attr'    => 'label',
				'label'   => __( 'Label', 'hamelp' ),
				'type'    => 'text',
				'default' => __( 'Enter keyword and hit search.', 'hamelp' ),
			],
			[
				'attr'    => 'btn',
				'label'   => __( 'Button Text', 'hamelp' ),
				'type'    => 'text',
				'default' => __( 'Search', 'hamelp' ),
			],
		];
	}
}
