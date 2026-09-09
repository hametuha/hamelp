<?php
/**
 * Block default string i18n tests.
 *
 * @package Hamelp
 */

/**
 * Tests that user-facing block defaults stay translatable.
 *
 * `block.json` attribute defaults are NOT translated by WordPress: core only
 * translates the keys listed in `wp-includes/block-i18n.json` (title,
 * description, keywords, styles.label, variations.*). A default declared there
 * is therefore served verbatim in every locale.
 *
 * The plugin instead declares no default for those attributes and resolves them
 * at render time through `__()`, so these tests guard both halves of that
 * contract. See #141.
 */
class BlockDefaultI18nTest extends WP_UnitTestCase {

	/**
	 * Attributes whose default must not be declared in block.json.
	 *
	 * @return array[] Block name => list of attribute names.
	 */
	public function translatable_attributes() {
		return [
			'hamelp/ai-overview' => [ 'placeholder', 'buttonText', 'hintText' ],
			'hamelp/search-box'  => [ 'label', 'btn' ],
		];
	}

	/**
	 * Skip a test when the blocks have not been built.
	 *
	 * Blocks are registered from `assets/blocks`, which is gitignored and built
	 * by CI only in the asset job, so the PHPUnit job runs against a tree with
	 * no built blocks at all.
	 */
	protected function skip_without_built_blocks() {
		if ( ! is_dir( dirname( __DIR__ ) . '/assets/blocks' ) ) {
			$this->markTestSkipped( 'Blocks are not built (run `npm run build:blocks`).' );
		}
	}

	/**
	 * The registered block types declare no default for translatable strings.
	 *
	 * Asserting against the registry (not the source tree) also catches a stale
	 * build. The source tree is covered separately, so this can be skipped when
	 * the blocks have not been built.
	 */
	public function test_registered_blocks_declare_no_translatable_defaults() {
		$this->skip_without_built_blocks();
		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( $this->translatable_attributes() as $block_name => $attributes ) {
			$block = $registry->get_registered( $block_name );
			$this->assertNotNull( $block, "Block {$block_name} is not registered." );
			foreach ( $attributes as $attribute ) {
				$this->assertArrayHasKey( $attribute, $block->attributes, "{$block_name}: {$attribute} is missing." );
				$this->assertArrayNotHasKey(
					'default',
					$block->attributes[ $attribute ],
					"{$block_name}: {$attribute} must not declare a default, or it is served untranslated in every locale."
				);
			}
		}
	}

	/**
	 * The source block.json files agree with the built ones.
	 */
	public function test_source_blocks_declare_no_translatable_defaults() {
		foreach ( $this->translatable_attributes() as $block_name => $attributes ) {
			$slug = str_replace( 'hamelp/', '', $block_name );
			$path = dirname( __DIR__ ) . '/src/blocks/' . $slug . '/block.json';
			$this->assertFileExists( $path );
			$json = json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			foreach ( $attributes as $attribute ) {
				$this->assertArrayNotHasKey( 'default', $json['attributes'][ $attribute ], "src {$slug}: {$attribute} must not declare a default." );
			}
		}
	}

	/**
	 * Rendering with no attributes falls back to the translated defaults.
	 */
	public function test_ai_overview_renders_default_strings() {
		$html = hamelp_render_ai_overview();
		$this->assertStringContainsString( 'placeholder="Enter your question..."', $html );
		$this->assertStringContainsString( 'Shift + Enter for a line break', $html );
		$this->assertStringContainsString( 'hamelp-ai-overview__hint', $html );
	}

	/**
	 * The default strings are English, so translations can override them.
	 *
	 * Source strings must not be written in Japanese: gettext falls back to the
	 * source string, which would show Japanese on an English site.
	 */
	public function test_default_strings_are_ascii() {
		foreach ( [ hamelp_render_ai_overview(), hamelp_render_search_box() ] as $html ) {
			$text = wp_strip_all_tags( $html );
			$this->assertSame( $text, mb_convert_encoding( $text, 'ASCII', 'UTF-8' ), 'Default block strings must use English source strings.' );
		}
	}

	/**
	 * An explicitly empty hint hides the element instead of showing the default.
	 *
	 * Declaring no default in block.json is what keeps these two states
	 * distinguishable: an absent attribute means "use the default", an empty
	 * string means "hide it".
	 */
	public function test_ai_overview_hint_hidden_when_explicitly_empty() {
		$html = hamelp_render_ai_overview( [ 'hint_text' => '' ] );
		$this->assertStringNotContainsString( 'hamelp-ai-overview__hint', $html );
		$this->assertStringNotContainsString( 'aria-describedby', $html );
	}

	/**
	 * The search box falls back to its translated defaults too.
	 */
	public function test_search_box_renders_default_strings() {
		$html = hamelp_render_search_box();
		$this->assertStringContainsString( 'placeholder="Enter keyword and hit search."', $html );
		$this->assertStringContainsString( '>Search</button>', $html );
	}

	/**
	 * A block with no attributes set renders the translated defaults.
	 *
	 * This exercises the whole chain: no default in block.json means the key is
	 * absent from `$attributes`, so the `??` fallback in render.php fires.
	 */
	public function test_block_render_falls_back_to_defaults() {
		$this->skip_without_built_blocks();
		$html = do_blocks( '<!-- wp:hamelp/ai-overview /-->' );
		$this->assertStringContainsString( 'placeholder="Enter your question..."', $html );
		$this->assertStringContainsString( 'Shift + Enter for a line break', $html );
	}
}
