<?php
/**
 * Post type test
 *
 * @package Hamelp
 */

/**
 * Sample test case.
 */
class PostTypeTest extends WP_UnitTestCase {

	/**
	 * @var \Hametuha\Hamelp\Hooks\PostType
	 */
	protected $post_type = null;

	/**
	 * Override setupper
	 */
	public function setUp():void {
		parent::setUp();
		$this->post_type = \Hametuha\Hamelp\Hooks\PostType::get();
	}


	/**
	 * A single example test.
	 */
	public function test_post_types() {
		$post_types = $this->post_type->get_post_types();
		$this->assertTrue( is_array( $post_types ) );
		foreach ( $post_types as $type => $setting ) {
			$this->assertTrue( post_type_exists( $type ) );
		}
	}

	/**
	 * Check taxonomy
	 */
	public function test_taxonomy() {
		$taxonomy = $this->post_type->taxonomy;
		$this->assertTrue( taxonomy_exists( $taxonomy ) );
	}
}
