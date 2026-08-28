<?php
/**
 * Catalog builder test
 *
 * @package Hamelp
 */

use Hametuha\Hamelp\Hooks\CatalogTaxonomy;
use Hametuha\Hamelp\Services\FaqCatalogBuilder;
use Hametuha\Hamelp\Services\FaqSearchService;

/**
 * Tests for the multi-source FAQ catalog.
 */
class CatalogBuilderTest extends WP_UnitTestCase {

	/**
	 * @var FaqCatalogBuilder
	 */
	protected $builder = null;

	/**
	 * Set up builder and the default catalog term.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->builder = new FaqCatalogBuilder();
		if ( ! term_exists( CatalogTaxonomy::DEFAULT_TERM, CatalogTaxonomy::TAXONOMY ) ) {
			wp_insert_term( 'Default Catalog', CatalogTaxonomy::TAXONOMY, [ 'slug' => CatalogTaxonomy::DEFAULT_TERM ] );
		}
	}

	/**
	 * Create an FAQ post.
	 *
	 * @param string $title   Post title.
	 * @param string $status  Post status.
	 * @param bool   $in_catalog Whether to assign the default catalog term.
	 * @return int
	 */
	protected function create_faq( $title, $status = 'publish', $in_catalog = false ) {
		$post_id = $this->factory->post->create(
			[
				'post_type'    => 'faq',
				'post_title'   => $title,
				'post_content' => $title . ' body',
				'post_status'  => $status,
			]
		);
		if ( $in_catalog ) {
			wp_set_object_terms( $post_id, CatalogTaxonomy::DEFAULT_TERM, CatalogTaxonomy::TAXONOMY );
		}
		return $post_id;
	}

	/**
	 * Pluck IDs out of a catalog.
	 *
	 * @param array[] $catalog Catalog entries.
	 * @return int[]
	 */
	protected function ids( array $catalog ) {
		return wp_list_pluck( $catalog, 'id' );
	}

	/**
	 * The catalog taxonomy must exist and be attached to the FAQ post type.
	 */
	public function test_taxonomy_registered() {
		$this->assertTrue( taxonomy_exists( CatalogTaxonomy::TAXONOMY ) );
		$this->assertContains( CatalogTaxonomy::TAXONOMY, get_object_taxonomies( 'faq' ) );
	}

	/**
	 * Published FAQs are in the catalog and citable, whatever their terms.
	 */
	public function test_published_faq_is_citable() {
		$post_id = $this->create_faq( 'Published FAQ' );
		$catalog = $this->builder->rebuild();
		$this->assertContains( $post_id, $this->ids( $catalog ) );
		foreach ( $catalog as $item ) {
			if ( $post_id === $item['id'] ) {
				$this->assertTrue( FaqCatalogBuilder::is_citable( $item ) );
				$this->assertSame( 'publish', $item['status'] );
			}
		}
	}

	/**
	 * A private FAQ that was not opted in stays out of the catalog.
	 */
	public function test_private_faq_without_term_is_excluded() {
		$post_id = $this->create_faq( 'Secret FAQ', 'private' );
		$catalog = $this->builder->rebuild();
		$this->assertNotContains( $post_id, $this->ids( $catalog ) );
	}

	/**
	 * A private FAQ opted into the catalog is included but not citable.
	 */
	public function test_private_faq_with_term_is_background_material() {
		$post_id = $this->create_faq( 'Opted-in FAQ', 'private', true );
		$catalog = $this->builder->rebuild();
		$this->assertContains( $post_id, $this->ids( $catalog ) );
		foreach ( $catalog as $item ) {
			if ( $post_id === $item['id'] ) {
				$this->assertFalse( FaqCatalogBuilder::is_citable( $item ) );
				$this->assertSame( 'private', $item['status'] );
			}
		}
	}

	/**
	 * A private entry keeps its plain title, without the "Private:" prefix.
	 */
	public function test_private_entry_title_has_no_status_prefix() {
		$post_id = $this->create_faq( 'Opted-in FAQ', 'private', true );
		$catalog = $this->builder->rebuild();
		foreach ( $catalog as $item ) {
			if ( $post_id === $item['id'] ) {
				$this->assertSame( 'Opted-in FAQ', $item['title'] );
			}
		}
	}

	/**
	 * Drafts never enter the catalog, even when opted in.
	 */
	public function test_draft_is_excluded() {
		$post_id = $this->create_faq( 'Draft FAQ', 'draft', true );
		$catalog = $this->builder->rebuild();
		$this->assertNotContains( $post_id, $this->ids( $catalog ) );
	}

	/**
	 * A post matched by two sources appears once, with the first source winning.
	 */
	public function test_sources_are_deduplicated() {
		$post_id = $this->create_faq( 'Duplicated FAQ' );
		$filter  = function ( $sources ) {
			$sources['again'] = [
				'args'    => [
					'post_type'   => [ 'faq' ],
					'post_status' => [ 'publish' ],
				],
				'citable' => false,
			];
			return $sources;
		};
		add_filter( 'hamelp_catalog_sources', $filter );
		$catalog = $this->builder->rebuild();
		remove_filter( 'hamelp_catalog_sources', $filter );

		$ids = $this->ids( $catalog );
		$this->assertSame( 1, count( array_keys( $ids, $post_id, true ) ) );
		foreach ( $catalog as $item ) {
			if ( $post_id === $item['id'] ) {
				// The 'published' source comes first, so it stays citable.
				$this->assertTrue( FaqCatalogBuilder::is_citable( $item ) );
			}
		}
	}

	/**
	 * The filter can add a whole new post type as a source.
	 */
	public function test_filter_can_add_source() {
		$page_id = $this->factory->post->create(
			[
				'post_type'   => 'page',
				'post_title'  => 'Pricing',
				'post_status' => 'publish',
			]
		);
		$filter  = function ( $sources ) {
			$sources['pages'] = [
				'args'    => [
					'post_type'   => [ 'page' ],
					'post_status' => [ 'publish' ],
				],
				'citable' => true,
			];
			return $sources;
		};
		add_filter( 'hamelp_catalog_sources', $filter );
		$catalog = $this->builder->rebuild();
		remove_filter( 'hamelp_catalog_sources', $filter );

		$this->assertContains( $page_id, $this->ids( $catalog ) );
	}

	/**
	 * Entries stored before `citable` existed are treated as citable.
	 */
	public function test_legacy_entry_is_citable() {
		$this->assertTrue(
			FaqCatalogBuilder::is_citable(
				[
					'id'    => 1,
					'title' => 'Legacy',
				]
			)
		);
	}

	/**
	 * Background material must reach the LLM without an ID to cite.
	 */
	public function test_background_material_has_no_id_in_context() {
		$public     = $this->create_faq( 'Public answer' );
		$background = $this->create_faq( 'Internal answer', 'private', true );
		$catalog    = $this->builder->rebuild();

		$service = new FaqSearchService();
		$method  = new ReflectionMethod( FaqSearchService::class, 'build_full_context' );
		$method->setAccessible( true );
		$context = $method->invoke( $service, $catalog );

		$this->assertStringContainsString( sprintf( '[ID:%d]', $public ), $context );
		$this->assertStringNotContainsString( sprintf( '[ID:%d]', $background ), $context );
		// The text itself is still there: it feeds the answer.
		$this->assertStringContainsString( 'Internal answer body', $context );
		$this->assertStringContainsString( 'MUST NOT be cited', $context );
	}

	/**
	 * Stored conversations must not resolve non-published posts into links.
	 */
	public function test_resolve_sources_skips_non_published() {
		$published = $this->create_faq( 'Public answer' );
		$private   = $this->create_faq( 'Private answer', 'private', true );
		$service   = new FaqSearchService();
		$sources   = $service->resolve_sources( [ $published, $private ] );
		$this->assertSame( [ $published ], wp_list_pluck( $sources, 'id' ) );
	}
}
