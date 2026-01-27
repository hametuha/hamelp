/**
 * AI Overview Block Frontend Script
 *
 * @package
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { parseMarkdown, replaceIdReferences } from './utils';

document.querySelectorAll( '.hamelp-ai-overview' ).forEach( ( container ) => {
	const form = container.querySelector( 'form' );
	const input = container.querySelector( 'input' );
	const result = container.querySelector( '.hamelp-ai-overview__result' );
	const showSources = container.dataset.showSources === 'true';

	form?.addEventListener( 'submit', async ( e ) => {
		e.preventDefault();
		const query = input.value.trim();
		if ( ! query ) {
			return;
		}

		container.classList.remove( 'has-result', 'has-error' );
		container.classList.add( 'is-loading' );
		result.innerHTML =
			'<span class="spinner"></span> ' +
			__( 'Generating answer\u2026', 'hamelp' );

		try {
			const data = await apiFetch( {
				path: '/hamelp/v1/ai-overview',
				method: 'POST',
				data: { query },
			} );

			// Parse markdown, then replace [ID:xxx] with source links.
			const parsedAnswer = replaceIdReferences(
				parseMarkdown( data.answer ),
				data.sources
			);
			let html = `<div class="hamelp-ai-overview__answer">${ parsedAnswer }</div>`;

			if ( showSources && data.sources?.length ) {
				html +=
					'<div class="hamelp-ai-overview__sources"><p>' +
					__( 'Related FAQs:', 'hamelp' ) +
					'</p><ul>';
				data.sources.forEach( ( source ) => {
					html += `<li><a href="${ source.url }">${ source.title }</a></li>`;
				} );
				html += '</ul></div>';
			}

			result.innerHTML = html;
			container.classList.remove( 'is-loading' );
			container.classList.add( 'has-result' );
		} catch ( err ) {
			result.innerHTML = `<div class="hamelp-ai-overview__error">${
				err.message || __( 'An error occurred.', 'hamelp' )
			}</div>`;
			container.classList.remove( 'is-loading' );
			container.classList.add( 'has-error' );
		}
	} );
} );
