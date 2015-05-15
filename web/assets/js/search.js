/*jslint browser: true */
/*global jQuery: true */
(function ($) {
    "use strict";

    var list = $('.search-list'),
        form = $('form#search-form'),
        showResults,
        doSearch,
        searching = false,
        searchQueued = false,
        previousQuery = form.serialize(),
        firstQuery = true;

    showResults = function (data) {
		var items = [];
		$.each( data, function( key, val ) {
			items.push( "<li id='" + val._id + "'>" 
			+ '<h2>' +val._id + '<h2>'
			+ '<p><span>' + val._source.description + '</span> '
			+ '<span>stars: ' + val._source.stars + '</span></p>'
			+ "</li>" );
		});

		$('#results').html($( "<ul/>", {
				"class": "my-new-list",
				html: items.join("")
				}).html());

        searching = false;

        if (searchQueued) {
            doSearch();
            searchQueued = false;
        }
    };

    doSearch = function () {
        var currentQuery;

        if (searching) {
            searchQueued = true;
            return;
        }

        if ($('#search_query').val().match(/^\s*$/) !== null) {
            if (!firstQuery) {
                list.addClass('hidden');
            }
            return;
        }

        currentQuery = $('#search_query').val();

        if (previousQuery === currentQuery) {
            return;
        }
if (0)
        if (window.history.pushState) {
            if (firstQuery) {
                window.history.pushState(null, "Search", "/search/?q=" + encodeURIComponent($('input[type="search"]', form).val()));
                firstQuery = false;
            } else {
                window.history.replaceState(null, "Search", "/search/?q=" + encodeURIComponent($('input[type="search"]', form).val()));
            }
        }

        $.ajax({
			dataType: "json",
            url: form.attr('action'),
            data: 'q='+currentQuery,
            success: showResults
        });
		firstQuery = false;
        searching = true;
        previousQuery = currentQuery;
    };

    form.bind('keyup search', doSearch);

    form.bind('keydown', function (event) {
        var keymap,
            currentSelected,
            nextSelected;

        keymap = {
            enter: 13,
            left: 37,
            up: 38,
            right: 39,
            down: 40
        };

        if (keymap.up !== event.which && keymap.down !== event.which && keymap.enter !== event.which) {
            return;
        }

        if ($('#search_query_query').val().match(/^\s*$/) !== null) {
            document.activeElement.blur();
            return;
        }

        event.preventDefault();

        currentSelected = list.find('ul.packages li.selected');
        nextSelected = (keymap.down === event.which) ? currentSelected.next('li') : currentSelected.prev('li');

        if (keymap.enter === event.which && currentSelected.data('url')) {
            window.location = currentSelected.data('url');
            return;
        }

        if (nextSelected.length > 0) {
            currentSelected.removeClass('selected');
            nextSelected.addClass('selected');

            var elTop = nextSelected.position().top,
                elHeight = nextSelected.height(),
                windowTop = $(window).scrollTop(),
                windowHeight = $(window).height();

            if (elTop < windowTop) {
                $(window).scrollTop(elTop);
            } else if (elTop + elHeight > windowTop + windowHeight) {
                $(window).scrollTop(elTop + elHeight + 20 - windowHeight);
            }
        }
    });
}(jQuery));
