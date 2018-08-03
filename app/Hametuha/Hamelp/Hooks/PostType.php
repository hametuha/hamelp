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
		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		add_action( 'add_meta_boxes', function( $post_type, $post ) {
			if ( $this->is_supported( $post_type ) ) {
				add_meta_box( 'faq_accessibility', __( 'Access', 'hamelp' ), [ $this, 'do_meta_box' ], $post_type, 'side' );
			}
		}, 10, 2 );
		add_filter( 'the_content', [ $this, 'restrict_content' ], 20 );
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
	 * Is post type supported.
	 *
	 * @param string $post_type
	 * @return bool
	 */
	public function is_supported( $post_type ) {
		$post_types = $this->get_post_types();
		return array_key_exists( $post_type, $post_types );
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

	/**
	 * @param int     $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( isset( $_POST['_hamela11ypnonce'] ) && wp_verify_nonce(  $_POST['_hamela11ypnonce'], 'hamlep_accessibility' ) ) {
			update_post_meta( $post_id, '_accessibility', $_POST['hamelp_accessibility'] );
		}
	}

	/**
	 * @param int $post
	 */
	public function do_meta_box( $post ) {
		wp_nonce_field( 'hamlep_accessibility', '_hamela11ypnonce', false );
		$current_value = $this->get_accessibility( $post );
		global $wp_roles;
		?>
		<p class="description">
			<?php esc_html_e( 'You can limit access to this post.', 'hamelp' ) ?>
		</p>
		<?php foreach ( $this->get_accessibility_type() as $key => $value ) : ?>
			<p>
				<label>
					<input type="radio" name="hamelp_accessibility" value="<?php echo esc_attr( $key ) ?>" <?php checked( $key, $current_value ) ?> />
					<?php echo esc_html( $value['label'] ) ?>
				</label>
			</p>
		<?php endforeach;
	}

	/**
	 * Get accessibility types.
	 *
	 * @return array
	 */
	protected function get_accessibility_type() {
		$types = [
			'' => [
				'label'    => __( 'Not restricted', 'hamelp' ),
				'callback' => null,
			],
		];
		global $wp_roles;
		foreach ( [ 'subscriber', 'contributor', 'author', 'editor', 'administrator' ] as $role ) {
			$types[ $role ] = [
				'label'    => translate_user_role( $wp_roles->role_names[ $role ] ),
				'callback' => null,
			];
		}
		/**
		 * hamelp_access_type
		 *
		 * Callback gets accessibility string and content.
		 *
		 * @param array $types 'accessibility' => ['label' => 'Accessible User', 'callback' => 'my_accessibility_callback' ]
		 */
		return apply_filters( 'hamelp_access_type', $types );
	}

	/**
	 * Get accessibility.
	 *
	 * @param null|int|\WP_Post $post
	 * @return string
	 */
	public function get_accessibility( $post = null ) {
		$post = get_post( $post );
		return (string) get_post_meta( $post->ID, '_accessibility', true );
	}

	/**
	 * Return content.
	 *
	 * @param string $content
	 * @return string
	 */
	public function restrict_content( $content ) {
		if ( ! $this->is_supported( get_post_type() ) ) {
			return $content;
		}
		$accessibility = $this->get_accessibility();
		$can = true;
		$accessibility_type = $this->get_accessibility_type();
		switch ( $accessibility ) {
			case 'administrator':
			case 'editor':
			case 'author':
			case 'contributor':
			case 'subscriber':
				$can = current_user_can( $accessibility );
				break;
			default:
				if ( isset( $accessibility_type[ $accessibility ] ) && is_callable( $accessibility_type[ $accessibility ]['callback'] ) ) {
					$can = call_user_func_array( $accessibility_type[ $accessibility ]['callback'], [ $accessibility, $content ] );
				}
				break;
		}
		if ( $can ) {
			return $content;
		}
		$obj = get_post_type_object( get_post_type() );
		$login_url = wp_login_url( get_permalink() );
		$message = wp_kses_post( sprintf(
			__( 'This %1$s is restricted and accessible only for %2$s. If you are not logged in please <a href="%3$s" class="alert-link" rel="nofollow">log in</a>.', 'hamelp' ),
			$obj->label,
			$accessibility_type[ $accessibility ]['label'],
			$login_url
		) );
		$message = sprintf( '<div class="hamelp-alert alert alert-warning">%s</div>', $message );
		/**
		 * hamelp_restricted_content
		 *
		 * Filter hook for restricted contents.
		 *
		 * @param string $message
		 * @param string $accessibility
		 * @return string
		 */
		$message = apply_filters( 'hamelp_restricted_content', $message, $accessibility );
		return $message;
	}
}
