/**
 * FAQ Search Box Block Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

/**
 * Edit component for FAQ Search Box block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {JSX.Element} Block editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { label, btn } = attributes;

	// See #141: block.json declares no defaults so these translated ones apply.
	const labelText = label ?? __( 'Enter keyword and hit search.', 'hamelp' );
	const btnText = btn ?? __( 'Search', 'hamelp' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'hamelp' ) }>
					<TextControl
						label={ __( 'Label', 'hamelp' ) }
						value={ labelText }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
					<TextControl
						label={ __( 'Button Text', 'hamelp' ) }
						value={ btnText }
						onChange={ ( value ) =>
							setAttributes( { btn: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'hamelp-search-box' } ) }>
				<div className="input-group">
					<input
						type="search"
						className="form-control"
						placeholder={ labelText }
						disabled
					/>
					<button
						type="button"
						className="btn btn-secondary"
						disabled
					>
						{ btnText }
					</button>
				</div>
			</div>
		</>
	);
}
