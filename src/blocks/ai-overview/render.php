<?php
/**
 * AI Overview Block Render Template
 *
 * @package hamelp
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$placeholder  = esc_attr( $attributes['placeholder'] ?? __( 'Enter your question...', 'hamelp' ) );
$button_text  = esc_html( $attributes['buttonText'] ?? __( 'Ask AI', 'hamelp' ) );
$show_sources = ! empty( $attributes['showSources'] ) ? 'true' : 'false';

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class'             => 'hamelp-ai-overview',
		'data-show-sources' => $show_sources,
	]
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<form class="hamelp-ai-overview__form">
		<input
			type="text"
			class="hamelp-ai-overview__input"
			placeholder="<?php echo $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
			required
		/>
		<button type="submit" class="hamelp-ai-overview__button">
			<?php echo $button_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</form>
	<div class="hamelp-ai-overview__result" aria-live="polite"></div>
</div>
