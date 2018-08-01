/*!
 * Incremental search library for Hamelp
 * @since 0.1.0
 */

/*global HamelpIncSearch:false*/

(function ($) {

	"use strict";

	var timer = null;

	var lastFaise = '';

	var ajax = null;

	var wait = 500;

	var setResult = function(total, results, $target) {
		resetResult($target);
		if(!total){
			putError( HamelpIncSearch.notFound, true, $target );
			return;
		}
		putError( HamelpIncSearch.found + ' ' + total, false, $target );
		var $ul = $target.find('.hamelp-result');
		$.each(results, function(index, result){
			var $li = $('<a class="hamelp-result-link list-group-item"></a>');
			$li.text(result.title.rendered).attr('href', result.link);
			$ul.append($li);
		});
	};

	var resetResult = function($target){
		$target.find('.hamelp-result').empty();
	};

	var putError = function(message, error, $target) {
		var $div = $('<div></div>');
		$div.text(message);
		if(error){
			$div.addClass('hamelp-message list-group-item list-group-item-warning');
		}else{
			$div.addClass('hamelp-message list-group-item list-group-item-info');
		}
		$target.find('.hamelp-result').append($div);
	};

	$('.hamelp-search-input').keyup(function (e) {
		// If time is on, stop.
		if (timer) {
			clearTimeout(timer);
		}
		if(ajax){
			ajax.abort();
			ajax = null;
		}
		var $input     = $(this);
		var $container = $input.parents('.hamelp-search-box');
		timer = setTimeout(function(){
			resetResult($container);
			var curFraise  = $input.val();
			var term = $input.val();
			if(!term.length){
				ajax = null;
				$container.removeClass('loading');
				return;
			}
			$container.addClass('loading');
			ajax = $.get(HamelpIncSearch.endpoint + '?search=' + term).done(function(result, textStatus, request){
				setResult(parseInt(request.getResponseHeader('X-WP-Total'), 10), result, $container);
			}).fail(function(response){
				var message = '';
				if(response.responseJSON && response.responseJSON.message){
					message = response.responseJSON.message;
				}
				putError(message, true, $container);
			}).always(function(){
				ajax = null;
				$container.removeClass('loading').addClass('loaded');
			});
		}, wait);

	});

})(jQuery);
