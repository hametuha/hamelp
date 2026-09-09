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
 * @param {string} html     Parsed HTML string.
 * @param {Array}  sources  Array of { id, title, url } objects.
 * @param {string} refLabel Label template for each citation; `%d` is
 *                          replaced with the citation's index. Defaults to
 *                          `(Ref. %d)` when empty.
 * @return {string} HTML with references replaced by links.
 */
export function replaceIdReferences( html, sources, refLabel ) {
	if ( ! sources?.length ) {
		return html;
	}
	const label = refLabel || '(Ref. %d)';
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
				if ( ! source ) {
					return null;
				}
				const text = label.replace( /%d/g, source.index );
				return `<a href="${ source.url }" class="hamelp-ai-overview__ref" title="${ source.title }">${ text }</a>`;
			} )
			.filter( Boolean );
		return refs.length ? refs.join( ' ' ) : match;
	} );
}

/**
 * Copy text to the visitor's clipboard.
 *
 * `window.navigator.clipboard` only exists in secure contexts, which excludes
 * plain http staging sites, so fall back to the legacy `execCommand` path
 * instead of failing silently there.
 *
 * @param {string} text Text to copy.
 * @return {Promise<boolean>} Whether the text was copied.
 */
export async function copyToClipboard( text ) {
	if ( ! text ) {
		return false;
	}

	if ( window.navigator.clipboard?.writeText ) {
		try {
			await window.navigator.clipboard.writeText( text );
			return true;
		} catch {
			// Permission denied or document not focused: try the legacy path.
		}
	}

	const area = document.createElement( 'textarea' );
	area.value = text;
	// Keep the helper field out of sight and out of the tab order. `fixed`
	// positioning stops iOS from scrolling the page when it gets focus.
	area.setAttribute( 'readonly', '' );
	area.setAttribute( 'aria-hidden', 'true' );
	area.style.position = 'fixed';
	area.style.top = '-9999px';
	document.body.appendChild( area );
	area.select();

	let copied = false;
	try {
		copied = document.execCommand( 'copy' );
	} catch {
		copied = false;
	}
	document.body.removeChild( area );

	return copied;
}

/**
 * How long the copy button keeps its "Copied!" state before reverting, in ms.
 */
const COPY_FEEDBACK_DURATION = 2000;

/**
 * Build a "Copy answer" button for one answer.
 *
 * The visible text is copied rather than the raw markdown, so what lands in the
 * clipboard is exactly what the visitor reads — citation labels and the source
 * list included, which is what a pre-delivery quality check is done against.
 *
 * Labels are injected rather than translated here so this module stays free of
 * dependencies, like the rest of its helpers.
 *
 * @param {HTMLElement} answerEl The rendered answer element to copy from.
 * @param {Object}      labels   Button labels: { idle, copied, failed }.
 * @return {HTMLElement} The wrapper element holding the button.
 */
export function createCopyButton( answerEl, labels ) {
	const actions = document.createElement( 'div' );
	actions.className = 'hamelp-ai-overview__actions';

	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = 'hamelp-ai-overview__copy';
	button.textContent = labels.idle;

	let timer = null;
	button.addEventListener( 'click', async () => {
		// innerText keeps the visible line breaks; textContent is the fallback
		// for environments that do not implement it (jsdom, older engines).
		const text = answerEl.innerText ?? answerEl.textContent;
		const copied = await copyToClipboard( text );
		button.textContent = copied ? labels.copied : labels.failed;
		button.classList.toggle( 'is-copied', copied );
		window.clearTimeout( timer );
		timer = window.setTimeout( () => {
			button.textContent = labels.idle;
			button.classList.remove( 'is-copied' );
		}, COPY_FEEDBACK_DURATION );
	} );

	actions.appendChild( button );
	return actions;
}
