<?php
/**
 * Catalog membership taxonomy.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Hooks;

use Hametuha\Hamelp\Pattern\Singleton;

/**
 * Registers the taxonomy that decides which posts join an AI Overview catalog.
 *
 * Published posts are always part of the default catalog, so the taxonomy only
 * matters for posts a query would not pick up on its own: private FAQs, and
 * later on the extra catalogs of #107.
 *
 * @package Hametuha\Hamelp\Hooks
 */
class CatalogTaxonomy extends Singleton {

	/**
	 * Taxonomy name.
	 *
	 * @var string
	 */
	const TAXONOMY = 'hamelp_catalog';

	/**
	 * Slug of the catalog used by the AI Overview block.
	 *
	 * @var string
	 */
	const DEFAULT_TERM = 'default';

	/**
	 * Option flag telling that the default term was created once.
	 *
	 * @var string
	 */
	const OPTION_TERM_CREATED = 'hamelp_catalog_default_term_created';

	/**
	 * Register hooks.
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_taxonomy' ], 11 );
		add_action( 'admin_init', [ $this, 'ensure_default_term' ] );
	}

	/**
	 * Register the catalog taxonomy.
	 */
	public function register_taxonomy() {
		$post_types = array_keys( PostType::get()->get_post_types() );
		$args       = [
			// Not public: a catalog is an editorial grouping, not a browsable archive.
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			// Hierarchical only to get the checkbox meta box; catalogs are flat.
			'hierarchical'      => true,
			'label'             => __( 'AI Catalog', 'hamelp' ),
			'labels'            => [
				'name'          => __( 'AI Catalog', 'hamelp' ),
				'singular_name' => __( 'AI Catalog', 'hamelp' ),
				'all_items'     => __( 'All AI Catalogs', 'hamelp' ),
				'add_new_item'  => __( 'Add New AI Catalog', 'hamelp' ),
				'edit_item'     => __( 'Edit AI Catalog', 'hamelp' ),
			],
			'description'       => __( 'Groups of posts used as source material for AI Overview answers.', 'hamelp' ),
		];
		/**
		 * hamelp_catalog_taxonomy_args
		 *
		 * Customize the catalog taxonomy registration.
		 *
		 * @param array    $args       Arguments for register_taxonomy.
		 * @param string[] $post_types Post types the taxonomy is attached to.
		 * @return array
		 */
		$args = apply_filters( 'hamelp_catalog_taxonomy_args', $args, $post_types );
		register_taxonomy( self::TAXONOMY, $post_types, $args );
	}

	/**
	 * Create the default catalog term once.
	 *
	 * Without it the checkbox meta box would be empty and there would be no way
	 * to opt a private FAQ into the catalog. The option flag makes this a
	 * one-shot: a term deliberately deleted afterwards stays deleted.
	 */
	public function ensure_default_term() {
		if ( ! taxonomy_exists( self::TAXONOMY ) || get_option( self::OPTION_TERM_CREATED ) ) {
			return;
		}
		update_option( self::OPTION_TERM_CREATED, 1, false );
		if ( term_exists( self::DEFAULT_TERM, self::TAXONOMY ) ) {
			return;
		}
		wp_insert_term(
			__( 'Default Catalog', 'hamelp' ),
			self::TAXONOMY,
			[
				'slug'        => self::DEFAULT_TERM,
				'description' => __( 'The catalog used by the AI Overview block. Published posts belong to it automatically; check it on a non-published post to feed that post to the AI as well.', 'hamelp' ),
			]
		);
	}
}
