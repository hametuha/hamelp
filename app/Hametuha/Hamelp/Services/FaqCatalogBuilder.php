<?php
/**
 * FAQ Catalog Builder for AI Overview.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Services;

use Hametuha\Hamelp\Hooks\CatalogTaxonomy;
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
	 * Whether a catalog entry may be shown as a source link.
	 *
	 * Entries stored before sources existed have no flag and were all published,
	 * so a missing value means citable.
	 *
	 * @param array $item Catalog entry.
	 * @return bool
	 */
	public static function is_citable( array $item ): bool {
		return ! array_key_exists( 'citable', $item ) || (bool) $item['citable'];
	}

	/**
	 * Get the query sources the catalog is built from.
	 *
	 * A single `WP_Query` cannot express "published OR (private AND tagged)",
	 * because `post_status` is always ANDed with `tax_query`. The catalog is
	 * therefore the union of several queries, deduplicated by post ID with the
	 * first source winning.
	 *
	 * Each source is `[ 'args' => <get_posts args>, 'citable' => bool ]`.
	 * `citable` decides whether the entry may appear in the answer's source
	 * links; non-citable entries still feed the answer itself.
	 *
	 * @return array[] Keyed list of sources.
	 */
	protected function get_sources(): array {
		$post_types = array_keys( PostType::get()->get_post_types() );
		$sources    = [
			// Everything published, whatever its catalog terms.
			'published' => [
				'args'    => [
					'post_type'   => $post_types,
					'post_status' => [ 'publish' ],
				],
				'citable' => true,
			],
			// Non-published posts explicitly opted into the default catalog.
			'opted_in'  => [
				'args'    => [
					'post_type'   => $post_types,
					'post_status' => [ 'private' ],
					'tax_query'   => [
						[
							'taxonomy' => CatalogTaxonomy::TAXONOMY,
							'field'    => 'slug',
							'terms'    => [ CatalogTaxonomy::DEFAULT_TERM ],
						],
					],
				],
				'citable' => false,
			],
		];
		/**
		 * hamelp_catalog_sources
		 *
		 * Add, remove or rewrite the queries the FAQ catalog is built from.
		 * Sources are merged in order and deduplicated by post ID, so an earlier
		 * source wins when the same post matches twice.
		 *
		 * @param array[] $sources Keyed list of `['args' => array, 'citable' => bool]`.
		 * @return array[]
		 */
		return apply_filters( 'hamelp_catalog_sources', $sources );
	}

	/**
	 * Build FAQ catalog from every registered source.
	 *
	 * @return array[] Catalog entries.
	 */
	protected function build_catalog(): array {
		$catalog = [];
		foreach ( $this->get_sources() as $source ) {
			if ( empty( $source['args'] ) || ! is_array( $source['args'] ) ) {
				continue;
			}
			$citable = ! isset( $source['citable'] ) || (bool) $source['citable'];
			foreach ( $this->query_source( $source['args'] ) as $post ) {
				if ( isset( $catalog[ $post->ID ] ) ) {
					// An earlier source already claimed this post.
					continue;
				}
				$catalog[ $post->ID ] = $this->build_entry( $post, $citable );
			}
		}
		return array_values( $catalog );
	}

	/**
	 * Run a single catalog source query.
	 *
	 * Pagination and ordering are forced: a source describes *what* belongs to
	 * the catalog, not how much of it.
	 *
	 * @param array $args Source query arguments.
	 * @return \WP_Post[]
	 */
	protected function query_source( array $args ): array {
		return get_posts(
			array_merge(
				$args,
				[
					'posts_per_page'         => -1,
					'orderby'                => 'title',
					'order'                  => 'ASC',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_term_cache' => true,
				]
			)
		);
	}

	/**
	 * Get a post title without the status prefix.
	 *
	 * `get_the_title()` prepends "Private:" / "Protected:" to non-published
	 * posts, which is noise once the text is fed to the LLM. The `the_title`
	 * filters are still applied, so themes and plugins keep their say.
	 *
	 * @param \WP_Post $post Source post.
	 * @return string
	 */
	public static function get_entry_title( \WP_Post $post ): string {
		$plain = function () {
			return '%s';
		};
		add_filter( 'private_title_format', $plain );
		add_filter( 'protected_title_format', $plain );
		$title = get_the_title( $post );
		remove_filter( 'private_title_format', $plain );
		remove_filter( 'protected_title_format', $plain );
		return $title;
	}

	/**
	 * Convert a post into a catalog entry.
	 *
	 * @param \WP_Post $post    Source post.
	 * @param bool     $citable Whether the entry may be shown as a source link.
	 * @return array
	 */
	protected function build_entry( \WP_Post $post, bool $citable ): array {
		$content  = wp_strip_all_tags( $post->post_content );
		$terms    = get_the_terms( $post->ID, PostType::get()->taxonomy );
		$category = '';
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$category = $terms[0]->name;
		}
		return [
			'id'       => $post->ID,
			'title'    => self::get_entry_title( $post ),
			'category' => $category,
			'excerpt'  => mb_substr( $content, 0, self::EXCERPT_LENGTH ),
			'content'  => mb_substr( $content, 0, self::CONTENT_MAX_LENGTH ),
			'access'   => hamelp_get_accessibility( $post ),
			'status'   => $post->post_status,
			'citable'  => $citable,
		];
	}
}
