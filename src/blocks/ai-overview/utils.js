/**
 * Utility functions for AI Overview block.
 *
 * @package
 */

/**
 * Convert simple markdown to HTML.
 *
 * Supports: bold, italic, links, lists (ordered/unordered), paragraphs.
 *
 * @param {string} text Markdown text.
 * @return {string} HTML string.
 */
export function parseMarkdown( text ) {
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

/**
 * Replace [ID:xxx] references in HTML with linked source indices.
 *
 * Handles both single [ID:42] and comma-separated [ID:42, ID:55] patterns.
 *
 * @param {string} html    Parsed HTML string.
 * @param {Array}  sources Array of { id, title, url } objects.
 * @return {string} HTML with references replaced by links.
 */
export function replaceIdReferences( html, sources ) {
	if ( ! sources?.length ) {
		return html;
	}
	const sourceMap = {};
	sources.forEach( ( source, i ) => {
		sourceMap[ source.id ] = { ...source, index: i + 1 };
	} );
	return html.replace( /\[ID:\d+(?:,\s*ID:\d+)*\]/g, ( match ) => {
		const ids = [ ...match.matchAll( /ID:(\d+)/g ) ].map( ( m ) =>
			parseInt( m[ 1 ], 10 )
		);
		const refs = ids
			.map( ( id ) => {
				const source = sourceMap[ id ];
				return source
					? `<a href="${ source.url }" class="hamelp-ai-overview__ref" title="${ source.title }">(Ref. ${ source.index })</a>`
					: null;
			} )
			.filter( Boolean );
		return refs.length ? refs.join( ' ' ) : match;
	} );
}
