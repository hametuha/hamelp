<?php
/**
 * AI Overview hook handler.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Hooks;

use Hametuha\Hamelp\Pattern\Singleton;
use Hametuha\Hamelp\Services\FaqSearchService;
use WordPress\AI_Client\AI_Client;

/**
 * Class AiOverview
 *
 * Handles AI Overview feature initialization, REST API, and block registration.
 */
class AiOverview extends Singleton {

	/**
	 * Initialize hooks.
	 */
	protected function init() {
		// Initialize wp-ai-client
		add_action( 'init', [ AI_Client::class, 'init' ] );

		// Register REST API endpoint
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			'hamelp/v1',
			'/ai-overview',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_request' ],
				'permission_callback' => '__return_true', // Public FAQ
				'args'                => [
					'query' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Handle AI overview REST request.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error Response or error.
	 */
	public function handle_request( \WP_REST_Request $request ) {
		$query = $request->get_param( 'query' );

		// Check if AI is available
		$prompt = AI_Client::prompt_with_wp_error( $query );
		if ( ! $prompt->is_supported_for_text_generation() ) {
			return new \WP_Error(
				'ai_unavailable',
				__( 'AI feature is not configured.', 'hamelp' ),
				[ 'status' => 503 ]
			);
		}

		$service = new FaqSearchService();
		$result  = $service->generate_overview( $query );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
