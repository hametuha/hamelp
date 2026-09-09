/**
 * AI Overview Block Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

/**
 * Edit component for AI Overview block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {JSX.Element} Block editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { placeholder, buttonText, hintText, showSources } = attributes;

	// block.json intentionally declares no defaults for these strings: a declared
	// default is served verbatim in every locale, so the translated fallbacks below
	// (and their PHP counterparts in render.php) are used instead. See #141.
	// The three dots must stay ASCII: this string shares its msgid with the PHP
	// fallback in render.php, so switching to an ellipsis character here would
	// orphan the existing translations.
	// eslint-disable-next-line @wordpress/i18n-ellipsis
	const defaultPlaceholder = __( 'Enter your question...', 'hamelp' );
	const placeholderText = placeholder ?? defaultPlaceholder;
	const submitLabel = buttonText ?? __( 'Ask AI', 'hamelp' );
	const hint = hintText ?? __( 'Shift + Enter for a line break', 'hamelp' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'hamelp' ) }>
					<TextControl
						label={ __( 'Placeholder', 'hamelp' ) }
						value={ placeholderText }
						onChange={ ( value ) =>
							setAttributes( { placeholder: value } )
						}
					/>
					<TextControl
						label={ __(
							'Submit button label (for screen readers)',
							'hamelp'
						) }
						help={ __(
							'The front end shows only the arrow icon. This text is used as the screen reader label.',
							'hamelp'
						) }
						value={ submitLabel }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>
					<TextControl
						label={ __( 'Helper text below the input', 'hamelp' ) }
						help={ __(
							'Use this to note that Shift + Enter inserts a line break. Leave it empty to hide the text. It is hidden automatically on touch devices such as smartphones.',
							'hamelp'
						) }
						value={ hint }
						onChange={ ( value ) =>
							setAttributes( { hintText: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Sources', 'hamelp' ) }
						checked={ showSources }
						onChange={ ( value ) =>
							setAttributes( { showSources: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'hamelp-ai-overview' } ) }>
				<div className="hamelp-ai-overview__preview">
					<textarea placeholder={ placeholderText } disabled />
					<button className="hamelp-ai-overview__button" disabled>
						<svg
							className="hamelp-ai-overview__button-icon"
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="none"
							aria-hidden="true"
							focusable="false"
							xmlns="http://www.w3.org/2000/svg"
						>
							<path
								d="M12 19V5M6 11l6-6 6 6"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
						<span className="screen-reader-text">
							{ submitLabel }
						</span>
					</button>
				</div>
				{ hint && <p className="hamelp-ai-overview__hint">{ hint }</p> }
				<p className="hamelp-ai-overview__note">
					{ __( 'AI Overview - Preview', 'hamelp' ) }
				</p>
			</div>
		</>
	);
}
