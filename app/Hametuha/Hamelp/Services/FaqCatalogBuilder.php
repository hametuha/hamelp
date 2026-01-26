<?php
/**
 * FAQ Catalog Builder for AI Overview.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Services;

use Hametuha\Hamelp\Hooks\PostType;

/**
 * Builds a catalog of all FAQs for use as LLM context.
 */
class FaqCatalogBuilder {

	/**
	 * Option key for FAQ catalog data.
	 *
	 * @var string
	 */
	const OPTION_CATALOG = 'hamelp_faq_catalog';

	/**
	 * Option key for last updated timestamp.
	 *
	 * @var string
	 */
	const OPTION_UPDATED = 'hamelp_faq_catalog_updated';

	/**
	 * Cron hook name for background rebuild.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'hamelp_rebuild_faq_catalog';

	/**
	 * Maximum excerpt length per FAQ entry.
	 *
	 * @var int
	 */
	const EXCERPT_LENGTH = 300;

	/**
	 * Maximum full content length per FAQ entry.
	 *
	 * @var int
	 */
	const CONTENT_MAX_LENGTH = 2000;

	/**
	 * Register the cron action hook.
	 *
	 * Must be called on every request so wp-cron can fire the event.
	 */
	public static function register_cron() {
		add_action(
			self::CRON_HOOK,
			function () {
				( new self() )->rebuild();
			}
		);
	}

	/**
	 * Get the stored FAQ catalog.
	 *
	 * @return array[] Array of FAQ catalog entries.
	 */
	public function get_catalog(): array {
		$catalog = get_option( self::OPTION_CATALOG, [] );
		return is_array( $catalog ) ? $catalog : [];
	}

	/**
	 * Get catalog filtered by current user's accessibility.
	 *
	 * @return array[] Filtered FAQ catalog entries.
	 */
	public function get_accessible_catalog(): array {
		$catalog = $this->get_catalog();
		return array_values(
			array_filter(
				$catalog,
				function ( $item ) {
					return empty( $item['access'] ) || current_user_can( $item['access'] );
				}
			)
		);
	}

	/**
	 * Rebuild the FAQ catalog and store it.
	 *
	 * @return array[] The rebuilt catalog.
	 */
	public function rebuild(): array {
		$catalog = $this->build_catalog();
		update_option( self::OPTION_CATALOG, $catalog, false );
		update_option( self::OPTION_UPDATED, time(), false );
		return $catalog;
	}

	/**
	 * Get last updated timestamp.
	 *
	 * @return int|null Unix timestamp, or null if never built.
	 */
	public function get_last_updated() {
		$time = get_option( self::OPTION_UPDATED, null );
		return $time ? (int) $time : null;
	}

	/**
	 * Schedule a background catalog rebuild via wp-cron.
	 *
	 * Called from save_post, delete_post, set_object_terms hooks.
	 */
	public static function schedule_rebuild() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time(), self::CRON_HOOK );
		}
	}

	/**
	 * Build FAQ catalog from all published FAQs.
	 *
	 * @return array[] Catalog entries.
	 */
	protected function build_catalog(): array {
		$post_types = array_keys( PostType::get()->get_post_types() );
		$taxonomy   = PostType::get()->taxonomy;

		$posts = get_posts(
			[
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$catalog = [];
		foreach ( $posts as $post ) {
			$content  = wp_strip_all_tags( $post->post_content );
			$terms    = get_the_terms( $post->ID, $taxonomy );
			$category = '';
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$category = $terms[0]->name;
			}

			$catalog[] = [
				'id'       => $post->ID,
				'title'    => get_the_title( $post ),
				'category' => $category,
				'excerpt'  => mb_substr( $content, 0, self::EXCERPT_LENGTH ),
				'content'  => mb_substr( $content, 0, self::CONTENT_MAX_LENGTH ),
				'access'   => hamelp_get_accessibility( $post ),
			];
		}
		return $catalog;
	}
}
