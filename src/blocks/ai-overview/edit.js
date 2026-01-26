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
	const { placeholder, buttonText, showSources } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'hamelp' ) }>
					<TextControl
						label={ __( 'Placeholder', 'hamelp' ) }
						value={ placeholder }
						onChange={ ( value ) =>
							setAttributes( { placeholder: value } )
						}
					/>
					<TextControl
						label={ __( 'Button Text', 'hamelp' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
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
					<input type="text" placeholder={ placeholder } disabled />
					<button disabled>{ buttonText }</button>
				</div>
				<p className="hamelp-ai-overview__note">
					{ __( 'AI Overview - Preview', 'hamelp' ) }
				</p>
			</div>
		</>
	);
}
