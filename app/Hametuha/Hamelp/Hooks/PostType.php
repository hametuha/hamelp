<?php

namespace Hametuha\Hamelp\Hooks;


use Hametuha\Hamelp\Pattern\Singleton;

/**
 * Post type handler
 *
 * @package Hametuha\Hamelp\Hooks
 */
class PostType extends Singleton {

	public $taxonomy = 'faq_cat';

	/**
	 * Do something in constructor.
	 */
	protected function init() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_filter( 'template_include', [ $this, 'template_include' ] );
	}

	/**
	 * Register post type and taxonomies.
	 */
	public function register_post_type() {
		$post_types = $this->get_post_types();
		foreach ( $post_types as $post_type => $label ) {
			$args          = [
				'label'           => $label,
				'description'     => '',
				'public'          => true,
				'menu_position'   => 20,
				'menu_icon'       => 'dashicons-editor-help',
				'supports'        => array( 'title', 'editor', 'author', 'comments' ),
				'has_archive'     => true,
				'capability_type' => 'page',
				'rewrite'         => array( 'slug' => $post_type ),
				'show_in_rest'    => true,
			];
			/**
			 * hamelp_post_type_args
			 *
			 * Customize setting via this filter.
			 *
			 * @param array  $args      Arguments for register_post_type.
			 * @param string $post_type Post type slug.
			 * @return array
			 */
			$args = apply_filters( 'hamelp_post_type_args', $args, $post_type );
			register_post_type( $post_type, $args );
		}
		// FAQ taxonomy.
		$tax_args = [
			'public' => true,
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => 'faq-cat' ),
			'label'        => __( 'FAQ Category', 'hamelp' ),
			'show_admin_column' => true,
		];
		/**
		 * hamelp_taxonomy_args
		 *
		 * Customize taxonomy setting via this filter.
		 * @param array $tax_args
		 * @return array
		 */
		$tax_args = apply_filters( 'hamelp_taxonomy_args', $tax_args );
		register_taxonomy( $this->taxonomy, array_keys( $post_types ), $tax_args );
	}

	/**
	 *
	 *
	 * @return array
	 */
	public function get_post_types() {
		/**
		 * hamelp_post_types
		 *
		 * You can add post types via this filters.
		 *
		 * @param array $post_types 'post' => 'Post'.
		 * @return array
		 */
		return apply_filters( 'hamelp_post_types', [
			'faq' => __( 'FAQ', 'hamelp' ),
		] );
	}

	/**
	 * Override template if it's FAQ.
	 *
	 * @param string $template
	 * @return string
	 */
	public function template_include( $template ) {
		$post_types = array_keys( $this->get_post_types() );
		if ( is_singular( $post_types ) ) {
			// Do nothing because it's normal thing.
		} elseif( is_post_type_archive( $post_types ) || is_tax( $this->taxonomy ) ) {
			$template = $this->template_path( 'archive-faq.php', $template );
		}
		return $template;
	}

	/**
	 * Get template file.
	 *
	 * @param string $file
	 * @param string $default Default file path.
	 * @return string
	 */
	public function template_path( $file, $default = '' ) {
		$directory_to_scan = [
			get_template_directory(),
			get_template_directory() . '/template-parts/hamelp',
		];
		if ( get_template_directory() != get_stylesheet_directory() ) {
			$directory_to_scan[] = get_stylesheet_directory();
			$directory_to_scan[] = get_stylesheet_directory() . '/template-parts/hamelp';
		}
		/**
		 * hamelp_template_to_scan
		 *
		 * Latter is prior.
		 *
		 * @param array  $directory_to_scan
		 * @param string $file
		 * @return array
		 */
		$directory_to_scan = apply_filters( 'hamelp_template_to_scan', $directory_to_scan, $file );
		$found = $default;
		foreach ( $directory_to_scan as $dir ) {
			$file_path = $dir . '/' . $file;
			if ( file_exists( $file_path ) ) {
				$found = $file_path;
			}
		}
		return $found;
	}
}
