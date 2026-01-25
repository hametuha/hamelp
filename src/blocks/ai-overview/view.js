/**
 * AI Overview Block Frontend Script
 *
 * @package
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Convert simple markdown to HTML.
 *
 * Supports: bold, italic, links, lists (ordered/unordered), paragraphs.
 *
 * @param {string} text Markdown text.
 * @return {string} HTML string.
 */
function parseMarkdown( text ) {
	if ( ! text ) {
		return '';
	}

	// Escape HTML special characters first
	let html = text
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' );

	// Bold: **text** or __text__
	html = html.replace( /\*\*(.+?)\*\*/g, '<strong>$1</strong>' );
	html = html.replace( /__(.+?)__/g, '<strong>$1</strong>' );

	// Italic: *text* or _text_ (but not inside words)
	html = html.replace( /(?<!\w)\*([^*]+?)\*(?!\w)/g, '<em>$1</em>' );
	html = html.replace( /(?<!\w)_([^_]+?)_(?!\w)/g, '<em>$1</em>' );

	// Links: [text](url)
	html = html.replace(
		/\[([^\]]+)\]\(([^)]+)\)/g,
		'<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
	);

	// Split into paragraphs by double newlines
	const paragraphs = html.split( /\n\n+/ );

	const processedParagraphs = paragraphs.map( ( para ) => {
		const trimmed = para.trim();
		if ( ! trimmed ) {
			return '';
		}

		// Check if this is a list
		const lines = trimmed.split( '\n' );

		// Check for ordered list (1. 2. 3.)
		const hasOrderedItems = lines.some( ( line ) =>
			/^\d+\.\s/.test( line.trim() )
		);
		if ( hasOrderedItems ) {
			const isOrderedList = lines.every(
				( line ) => /^\d+\.\s/.test( line.trim() ) || ! line.trim()
			);
			if ( isOrderedList ) {
				const items = lines
					.filter( ( line ) => line.trim() )
					.map(
						( line ) =>
							`<li>${ line.replace( /^\d+\.\s/, '' ) }</li>`
					)
					.join( '' );
				return `<ol>${ items }</ol>`;
			}
		}

		// Check for unordered list (- or *)
		const hasUnorderedItems = lines.some( ( line ) =>
			/^[-*]\s/.test( line.trim() )
		);
		if ( hasUnorderedItems ) {
			const isUnorderedList = lines.every(
				( line ) => /^[-*]\s/.test( line.trim() ) || ! line.trim()
			);
			if ( isUnorderedList ) {
				const items = lines
					.filter( ( line ) => line.trim() )
					.map(
						( line ) =>
							`<li>${ line.replace( /^[-*]\s/, '' ) }</li>`
					)
					.join( '' );
				return `<ul>${ items }</ul>`;
			}
		}

		// Regular paragraph - convert single newlines to <br>
		return `<p>${ trimmed.replace( /\n/g, '<br>' ) }</p>`;
	} );

	return processedParagraphs.filter( ( p ) => p ).join( '' );
}

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

			// Parse markdown in the answer
			const parsedAnswer = parseMarkdown( data.answer );
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
