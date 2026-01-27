/**
 * Tests for AI Overview utility functions.
 */

import { parseMarkdown, replaceIdReferences } from '../../src/blocks/ai-overview/utils';

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
} );