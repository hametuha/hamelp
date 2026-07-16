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
						label={ __(
							'送信ボタンのラベル（スクリーンリーダー用）',
							'hamelp'
						) }
						help={ __(
							'フロントエンドには↑アイコンのみ表示され、この文言はスクリーンリーダーの読み上げ用ラベルとして使われます。',
							'hamelp'
						) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>
					<TextControl
						label={ __( '入力欄下の補助テキスト', 'hamelp' ) }
						help={ __(
							'Shift+Enterで改行できることの案内などに。空にすると非表示。スマートフォン等のタッチ端末では自動的に非表示になります。',
							'hamelp'
						) }
						value={ hintText }
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
					<textarea placeholder={ placeholder } disabled />
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
							{ buttonText }
						</span>
					</button>
				</div>
				{ hintText && (
					<p className="hamelp-ai-overview__hint">{ hintText }</p>
				) }
				<p className="hamelp-ai-overview__note">
					{ __( 'AI Overview - Preview', 'hamelp' ) }
				</p>
			</div>
		</>
	);
}
