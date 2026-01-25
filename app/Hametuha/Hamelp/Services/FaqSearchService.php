<?php
/**
 * FAQ Search Service for AI Overview.
 *
 * @package hamelp
 */

namespace Hametuha\Hamelp\Services;

use Hametuha\Hamelp\Hooks\PostType;
use WordPress\AI_Client\AI_Client;

/**
 * Service class for FAQ search and RAG-based response generation.
 */
class FaqSearchService {

	/**
	 * Generate AI overview response based on user query.
	 *
	 * @param string $query User's question.
	 * @return array|\WP_Error Response with answer and sources, or error.
	 */
	public function generate_overview( string $query ) {
		// 1. Search FAQs with improved strategy
		$faqs = $this->search_faqs_with_fallback( $query );

		// 2. Build context
		$context = $this->build_context( $faqs );

		// 3. Get system prompt
		$system_prompt = $this->get_system_prompt( $context );

		// 4. Generate LLM response
		$response = AI_Client::prompt_with_wp_error( $query )
			->using_system_instruction( $system_prompt )
			->using_temperature( 0.3 )
			->generate_text();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return [
			'answer'  => $response,
			'sources' => array_map(
				function ( $post ) {
					return [
						'id'    => $post->ID,
						'title' => get_the_title( $post ),
						'url'   => get_permalink( $post ),
					];
				},
				$faqs
			),
		];
	}

	/**
	 * Search FAQs with multiple strategies and fallback.
	 *
	 * @param string $query Search query.
	 * @param int    $limit Maximum number of results.
	 * @return \WP_Post[] Array of FAQ posts.
	 */
	protected function search_faqs_with_fallback( string $query, int $limit = 5 ): array {
		// Strategy 1: Direct keyword search
		$faqs = $this->search_faqs( $query, $limit );
		if ( ! empty( $faqs ) ) {
			return $faqs;
		}

		// Strategy 2: Extract keywords using AI and search
		$keywords = $this->extract_keywords( $query );
		if ( ! empty( $keywords ) ) {
			foreach ( $keywords as $keyword ) {
				$faqs = array_merge( $faqs, $this->search_faqs( $keyword, $limit ) );
			}
			// Remove duplicates
			$faqs = $this->unique_posts( $faqs );
			if ( ! empty( $faqs ) ) {
				return array_slice( $faqs, 0, $limit );
			}
		}

		// Strategy 3: Fallback to recent FAQs for context
		return $this->get_recent_faqs( $limit );
	}

	/**
	 * Extract keywords from query using AI.
	 *
	 * @param string $query User query.
	 * @return string[] Extracted keywords.
	 */
	protected function extract_keywords( string $query ): array {
		$prompt = AI_Client::prompt_with_wp_error( $query );
		if ( ! $prompt->is_supported_for_text_generation() ) {
			return [];
		}

		$instruction = 'Extract 3-5 search keywords from the following question. '
			. 'Return only the keywords separated by commas, nothing else. '
			. 'Include both the original language and English translations if applicable. '
			. 'For example: "電子書籍, ebook, 価格, price, 設定"';

		$response = $prompt
			->using_system_instruction( $instruction )
			->using_temperature( 0.1 )
			->generate_text();

		if ( is_wp_error( $response ) ) {
			return [];
		}

		// Parse comma-separated keywords
		$keywords = array_map( 'trim', explode( ',', $response ) );
		return array_filter( $keywords );
	}

	/**
	 * Search FAQs by query.
	 *
	 * @param string $query  Search query.
	 * @param int    $limit  Maximum number of results.
	 * @return \WP_Post[] Array of FAQ posts.
	 */
	protected function search_faqs( string $query, int $limit = 5 ): array {
		$post_types = array_keys( PostType::get()->get_post_types() );

		$posts = get_posts(
			[
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				's'              => $query,
				'posts_per_page' => $limit,
			]
		);

		return $this->filter_by_accessibility( $posts );
	}

	/**
	 * Get recent FAQs as fallback.
	 *
	 * @param int $limit Maximum number of results.
	 * @return \WP_Post[] Array of FAQ posts.
	 */
	protected function get_recent_faqs( int $limit = 5 ): array {
		$post_types = array_keys( PostType::get()->get_post_types() );

		$posts = get_posts(
			[
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			]
		);

		return $this->filter_by_accessibility( $posts );
	}

	/**
	 * Filter posts by accessibility.
	 *
	 * @param \WP_Post[] $posts Posts to filter.
	 * @return \WP_Post[] Filtered posts.
	 */
	protected function filter_by_accessibility( array $posts ): array {
		return array_filter(
			$posts,
			function ( $post ) {
				$access = hamelp_get_accessibility( $post );
				return empty( $access ) || current_user_can( $access );
			}
		);
	}

	/**
	 * Remove duplicate posts from array.
	 *
	 * @param \WP_Post[] $posts Posts array with potential duplicates.
	 * @return \WP_Post[] Unique posts.
	 */
	protected function unique_posts( array $posts ): array {
		$seen = [];
		return array_filter(
			$posts,
			function ( $post ) use ( &$seen ) {
				if ( isset( $seen[ $post->ID ] ) ) {
					return false;
				}
				$seen[ $post->ID ] = true;
				return true;
			}
		);
	}

	/**
	 * Build context string from FAQ posts.
	 *
	 * @param \WP_Post[] $faqs Array of FAQ posts.
	 * @return string Context string for LLM.
	 */
	protected function build_context( array $faqs ): string {
		if ( empty( $faqs ) ) {
			return '';
		}

		$context = "Related FAQs:\n\n";
		$index   = 1;
		foreach ( $faqs as $faq ) {
			$content  = wp_strip_all_tags( $faq->post_content );
			$content  = mb_substr( $content, 0, 500 );
			$context .= sprintf( "[%d] %s\n%s\n\n", $index, get_the_title( $faq ), $content );
			++$index;
		}
		return $context;
	}

	/**
	 * Get system prompt for LLM.
	 *
	 * @param string $context FAQ context.
	 * @return string System prompt.
	 */
	protected function get_system_prompt( string $context ): string {
		$base = apply_filters(
			'hamelp_ai_system_prompt',
			'You are a FAQ support assistant. Answer user questions based on the provided FAQ content.

IMPORTANT RULES:
- If relevant information is found in the FAQs, reference it by number like [1] or [2] within your answer text.
- DO NOT include a separate "Related FAQ" or "参考FAQ" or "関連FAQ" section at the end of your response.
- The system will automatically display FAQ links separately, so you only need to cite numbers inline.
- If no relevant FAQ is found, provide a helpful answer based on general knowledge.
- Keep your response concise and helpful.
- Respond in the same language as the user question.'
		);

		if ( empty( $context ) ) {
			return $base . "\n\nNote: No related FAQs were found for this query.";
		}

		return $base . "\n\n" . $context;
	}
}
