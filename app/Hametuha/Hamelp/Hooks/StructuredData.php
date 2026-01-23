<?php

namespace Hametuha\Hamelp\Hooks;

use Hametuha\Hamelp\Pattern\Singleton;

/**
 * Display Structured data
 *
 * @package hamelp
 */
class StructuredData extends Singleton {

	/**
	 * Constructor
	 */
	protected function init() {
		if ( ! apply_filters( 'hamelp_render_json_ld', true ) ) {
			return;
		}
		add_action( 'wp_head', [ $this, 'render_json_ld' ], 20 );
	}

	/**
	 * Render JSON LD in head tag.
	 */
	public function render_json_ld() {
		$post_types = array_keys( PostType::get()->get_post_types() );
		if ( ! is_singular( $post_types ) ) {
			// If this is not faq, do nothing.
			return;
		}
		$json = $this->get_json_ld( get_queried_object() );
		$json = wp_json_encode( $json );
		if ( ! $json ) {
			return;
		}
		?>
		<script type="application/ld+json">
		<?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD structured data ?>
		</script>
		<?php
	}

	/**
	 * Get array which represents JSON ld
	 *
	 * @param \WP_Post $post
	 * @return array
	 */
	public function get_json_ld( $post ) {
		$json = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => [
				[
					'@type'          => 'Question',
					'name'           => get_the_title( $post ),
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => apply_filters( 'the_content', $post->post_content ),
					],
				],
			],
		];
		return apply_filters( 'hamelp_json_ld_data', $json, $post );
	}
}
