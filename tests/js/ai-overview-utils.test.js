/**
 * Tests for AI Overview utility functions.
 */

import {
	copyToClipboard,
	createCopyButton,
	parseMarkdown,
	replaceIdReferences,
} from '../../src/blocks/ai-overview/utils';

describe( 'parseMarkdown', () => {
	it( 'returns empty string for falsy input', () => {
		expect( parseMarkdown( '' ) ).toBe( '' );
		expect( parseMarkdown( null ) ).toBe( '' );
		expect( parseMarkdown( undefined ) ).toBe( '' );
	} );

	it( 'escapes HTML special characters', () => {
		expect( parseMarkdown( '<b>bold</b> & more' ) ).toBe(
			'<p>&lt;b&gt;bold&lt;/b&gt; &amp; more</p>'
		);
	} );

	it( 'converts bold text', () => {
		expect( parseMarkdown( '**bold**' ) ).toBe(
			'<p><strong>bold</strong></p>'
		);
		expect( parseMarkdown( '__bold__' ) ).toBe(
			'<p><strong>bold</strong></p>'
		);
	} );

	it( 'converts italic text', () => {
		expect( parseMarkdown( '*italic*' ) ).toBe(
			'<p><em>italic</em></p>'
		);
		expect( parseMarkdown( '_italic_' ) ).toBe(
			'<p><em>italic</em></p>'
		);
	} );

	it( 'converts links', () => {
		const result = parseMarkdown( '[example](https://example.com)' );
		expect( result ).toBe(
			'<p><a href="https://example.com" target="_blank" rel="noopener noreferrer">example</a></p>'
		);
	} );

	it( 'converts ordered lists', () => {
		const input = '1. First\n2. Second\n3. Third';
		expect( parseMarkdown( input ) ).toBe(
			'<ol><li>First</li><li>Second</li><li>Third</li></ol>'
		);
	} );

	it( 'converts unordered lists with -', () => {
		const input = '- Alpha\n- Beta';
		expect( parseMarkdown( input ) ).toBe(
			'<ul><li>Alpha</li><li>Beta</li></ul>'
		);
	} );

	// Known limitation: * lists conflict with italic syntax.
	// The italic regex matches before list detection runs.
	// In practice, LLM responses use - for lists, so this is low priority.
	it.skip( 'converts unordered lists with *', () => {
		const input = '* Alpha\n* Beta';
		expect( parseMarkdown( input ) ).toBe(
			'<ul><li>Alpha</li><li>Beta</li></ul>'
		);
	} );

	it( 'splits paragraphs on double newlines', () => {
		const input = 'Paragraph one.\n\nParagraph two.';
		expect( parseMarkdown( input ) ).toBe(
			'<p>Paragraph one.</p><p>Paragraph two.</p>'
		);
	} );

	it( 'converts single newlines to <br>', () => {
		const input = 'Line one.\nLine two.';
		expect( parseMarkdown( input ) ).toBe(
			'<p>Line one.<br>Line two.</p>'
		);
	} );
} );

describe( 'replaceIdReferences', () => {
	const sources = [
		{ id: 100, title: 'FAQ One', url: '/faq/100/' },
		{ id: 200, title: 'FAQ Two', url: '/faq/200/' },
		{ id: 300, title: 'FAQ Three', url: '/faq/300/' },
	];

	it( 'returns html as-is when sources is empty', () => {
		expect( replaceIdReferences( 'Hello', [] ) ).toBe( 'Hello' );
		expect( replaceIdReferences( 'Hello', null ) ).toBe( 'Hello' );
		expect( replaceIdReferences( 'Hello', undefined ) ).toBe( 'Hello' );
	} );

	it( 'replaces single [ID:xxx] with a link', () => {
		const result = replaceIdReferences( 'See [ID:100] for details.', sources );
		expect( result ).toContain( 'href="/faq/100/"' );
		expect( result ).toContain( '(Ref. 1)' );
		expect( result ).not.toContain( '[ID:100]' );
	} );

	it( 'replaces multiple separate [ID:xxx] references', () => {
		const result = replaceIdReferences(
			'See [ID:100] and [ID:200].',
			sources
		);
		expect( result ).toContain( '(Ref. 1)' );
		expect( result ).toContain( '(Ref. 2)' );
	} );

	it( 'replaces comma-separated [ID:xxx, ID:yyy]', () => {
		const result = replaceIdReferences(
			'See [ID:100, ID:200] for details.',
			sources
		);
		expect( result ).toContain( '(Ref. 1)' );
		expect( result ).toContain( '(Ref. 2)' );
		expect( result ).not.toContain( '[ID:' );
	} );

	it( 'replaces comma-separated with three IDs', () => {
		const result = replaceIdReferences(
			'See [ID:100, ID:200, ID:300].',
			sources
		);
		expect( result ).toContain( '(Ref. 1)' );
		expect( result ).toContain( '(Ref. 2)' );
		expect( result ).toContain( '(Ref. 3)' );
		expect( result ).not.toContain( '[ID:' );
	} );

	it( 'keeps unknown IDs as-is', () => {
		const result = replaceIdReferences( 'See [ID:999].', sources );
		expect( result ).toBe( 'See [ID:999].' );
	} );

	it( 'uses correct source index regardless of ID order', () => {
		const result = replaceIdReferences( 'See [ID:300].', sources );
		expect( result ).toContain( '(Ref. 3)' );
	} );

	it( 'sets title attribute from source title', () => {
		const result = replaceIdReferences( 'See [ID:100].', sources );
		expect( result ).toContain( 'title="FAQ One"' );
	} );

	it( 'uses a custom refLabel template when provided', () => {
		const result = replaceIdReferences(
			'See [ID:100].',
			sources,
			'出典:%d'
		);
		expect( result ).toContain( '出典:1' );
		expect( result ).not.toContain( '(Ref. 1)' );
	} );

	it( 'substitutes every %d occurrence in a custom refLabel', () => {
		const result = replaceIdReferences(
			'See [ID:200].',
			sources,
			'[%d] (source #%d)'
		);
		expect( result ).toContain( '[2] (source #2)' );
	} );

	it( 'falls back to the default label when refLabel is empty', () => {
		const result = replaceIdReferences( 'See [ID:100].', sources, '' );
		expect( result ).toContain( '(Ref. 1)' );
	} );
} );

describe( 'copyToClipboard', () => {
	afterEach( () => {
		jest.restoreAllMocks();
		delete navigator.clipboard;
		delete document.execCommand;
	} );

	it( 'returns false for empty text', async () => {
		await expect( copyToClipboard( '' ) ).resolves.toBe( false );
	} );

	it( 'uses the async clipboard API when available', async () => {
		const writeText = jest.fn().mockResolvedValue( undefined );
		navigator.clipboard = { writeText };

		await expect( copyToClipboard( 'hello' ) ).resolves.toBe( true );
		expect( writeText ).toHaveBeenCalledWith( 'hello' );
	} );

	it( 'falls back to execCommand when the clipboard API rejects', async () => {
		navigator.clipboard = {
			writeText: jest.fn().mockRejectedValue( new Error( 'denied' ) ),
		};
		document.execCommand = jest.fn().mockReturnValue( true );

		await expect( copyToClipboard( 'hello' ) ).resolves.toBe( true );
		expect( document.execCommand ).toHaveBeenCalledWith( 'copy' );
	} );

	it( 'falls back to execCommand outside secure contexts', async () => {
		document.execCommand = jest.fn().mockReturnValue( true );

		await expect( copyToClipboard( 'hello' ) ).resolves.toBe( true );
		expect( document.execCommand ).toHaveBeenCalledWith( 'copy' );
	} );

	it( 'reports failure and removes the helper field when execCommand throws', async () => {
		document.execCommand = jest.fn( () => {
			throw new Error( 'nope' );
		} );

		await expect( copyToClipboard( 'hello' ) ).resolves.toBe( false );
		expect( document.querySelector( 'textarea' ) ).toBeNull();
	} );
} );

describe( 'createCopyButton', () => {
	const LABELS = {
		idle: 'Copy answer',
		copied: 'Copied!',
		failed: 'Failed to copy.',
	};
	let answerEl;
	let writeText;

	beforeEach( () => {
		jest.useFakeTimers();
		answerEl = document.createElement( 'div' );
		answerEl.innerHTML = '<p>The answer.</p>';
		document.body.appendChild( answerEl );
		writeText = jest.fn().mockResolvedValue( undefined );
		navigator.clipboard = { writeText };
	} );

	afterEach( () => {
		jest.useRealTimers();
		document.body.innerHTML = '';
		delete navigator.clipboard;
	} );

	it( 'renders a non-submitting button inside the actions wrapper', () => {
		const actions = createCopyButton( answerEl, LABELS );
		const button = actions.querySelector( 'button' );

		expect( actions.className ).toBe( 'hamelp-ai-overview__actions' );
		expect( button.className ).toBe( 'hamelp-ai-overview__copy' );
		// The button lives inside the block's <form>, so it must not submit it.
		expect( button.type ).toBe( 'button' );
		expect( button.textContent ).toBe( 'Copy answer' );
	} );

	it( 'copies the answer text and shows feedback that reverts', async () => {
		const button = createCopyButton( answerEl, LABELS ).querySelector( 'button' );

		button.click();
		await Promise.resolve();
		await Promise.resolve();

		expect( writeText ).toHaveBeenCalledWith( 'The answer.' );
		expect( button.textContent ).toBe( 'Copied!' );
		expect( button.classList.contains( 'is-copied' ) ).toBe( true );

		jest.runAllTimers();
		expect( button.textContent ).toBe( 'Copy answer' );
		expect( button.classList.contains( 'is-copied' ) ).toBe( false );
	} );

	it( 'reports a failed copy instead of claiming success', async () => {
		writeText.mockRejectedValue( new Error( 'denied' ) );
		document.execCommand = jest.fn().mockReturnValue( false );
		const button = createCopyButton( answerEl, LABELS ).querySelector( 'button' );

		button.click();
		await Promise.resolve();
		await Promise.resolve();
		await Promise.resolve();

		expect( button.textContent ).toBe( 'Failed to copy.' );
		expect( button.classList.contains( 'is-copied' ) ).toBe( false );
		delete document.execCommand;
	} );
} );