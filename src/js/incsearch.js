/*!
 * Incremental search library for Hamelp
 *
 * @handle hamelp-incsearch
 * @deps jquery
 * @strategy defer
 */

/* global HamelpIncSearch: false, jQuery: false */

( function ( $ ) {
	'use strict';

	let timer = null;
	let ajax = null;
	const wait = 500;

	const setResult = function ( total, results, $target ) {
		resetResult( $target );
		if ( ! total ) {
			putError( HamelpIncSearch.notFound, true, $target );
			return;
		}
		putError( HamelpIncSearch.found + ' ' + total, false, $target );
		const $ul = $target.find( '.hamelp-result' );
		$.each( results, function ( index, result ) {
			const $li = $(
				'<a class="hamelp-result-link list-group-item"></a>'
			);
			$li.text( result.title.rendered ).attr( 'href', result.link );
			$ul.append( $li );
		} );
	};

	const resetResult = function ( $target ) {
		$target.find( '.hamelp-result' ).empty();
	};

	const putError = function ( message, error, $target ) {
		const $div = $( '<div></div>' );
		$div.text( message );
		if ( error ) {
			$div.addClass(
				'hamelp-message list-group-item list-group-item-warning'
			);
		} else {
			$div.addClass(
				'hamelp-message list-group-item list-group-item-info'
			);
		}
		$target.find( '.hamelp-result' ).append( $div );
	};

	$( '.hamelp-search-input' ).keyup( function () {
		// If time is on, stop.
		if ( timer ) {
			clearTimeout( timer );
		}
		if ( ajax ) {
			ajax.abort();
			ajax = null;
		}
		const $input = $( this );
		const $container = $input.parents( '.hamelp-search-box' );
		timer = setTimeout( function () {
			resetResult( $container );
			const term = $input.val();
			if ( ! term.length ) {
				ajax = null;
				$container.removeClass( 'loading' );
				return;
			}
			$container.addClass( 'loading' );
			ajax = $.get( HamelpIncSearch.endpoint + '?search=' + term )
				.done( function ( result, textStatus, request ) {
					setResult(
						parseInt(
							request.getResponseHeader( 'X-WP-Total' ),
							10
						),
						result,
						$container
					);
				} )
				.fail( function ( response ) {
					let message = '';
					if (
						response.responseJSON &&
						response.responseJSON.message
					) {
						message = response.responseJSON.message;
					}
					putError( message, true, $container );
				} )
				.always( function () {
					ajax = null;
					$container.removeClass( 'loading' ).addClass( 'loaded' );
				} );
		}, wait );
	} );
} )( jQuery );
