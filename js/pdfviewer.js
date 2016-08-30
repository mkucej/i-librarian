// When PDF is open, convert it to XML to SQLite in the background.
// In preview mode, don't extract text.
if (!preview && toolbar) {
    $.get('pdfcontroller.php?rendertext=1&file=' + fileName);
}
/**
 * jQuery Tooltip options for PDF notes.
 */
var toolTipOptions = {
    show: {delay: 200, duration: 75},
    hide: {delay: 0, duration: 150},
    position: {
        my: "center bottom-8",
        at: "center top",
        collision: "flipfit"
    },
    items: "div.marker-note, div.marker-note-others, [data-annotation]",
    content: function () {
        var element = $(this);
        if (element.is("div.marker-note") || element.is("div.marker-note-others")) {
            if (element.attr("data-annotation") === '') {
                return false;
            }
            return '<div style="white-space:pre-wrap;word-wrap:break-word">'
                    + element.attr("data-annotation") + '</div>';
        }
    }
};
// Some form styling.
$(".select_span").unbind().click(function (e) {
    e.stopPropagation();
    if ($(this).hasClass('ui-state-disabled'))
        e.stopImmediatepropagation();
    var $input = $(this).children('input'), $span = $(this).children('i');
    if ($input.is(':radio')) {
        var rname = $input.attr('name');
        $input.prop('checked', true);
        $(this).closest('table').find('input[name="' + rname + '"]').next().removeClass('fa-circle').addClass('fa-circle-o');
        $span.removeClass('fa-circle-o').addClass('fa-circle');
    } else if ($input.is(':checkbox')) {
        if ($span.hasClass('fa-square-o')) {
            $input.prop('checked', true);
            $span.removeClass('fa-square-o').addClass('fa-check-square');
            if ($(this).children('input[name^="category"]').length === 1)
                $input.closest('td').addClass('alternating_row');
        } else if ($span.hasClass('fa-check-square')) {
            $input.prop('checked', false);
            $span.removeClass('fa-check-square').addClass('fa-square-o');
            if ($(this).children('input[name^="category"]').length === 1)
                $input.closest('td').removeClass('alternating_row');
        }
    }
});
// Prevent double binding.
if (inline === false) {
    /**
     * AJAX defaults.
     */
    $.ajaxSetup({
        cache: false
    });
    /**
     * AJAX error handling.
     */
    $(document).ajaxError(function (event, request) {
        overlay.stop();
        if (request.statusText === 'abort') {
            // Aborts are quiet.
            return;
        } else if (request.status === 403) {
            // Sign out if session expired.
            location.reload(true);
        }
        var errorText = 'Unknown error.';
        // Display custom header or default HTTP header.
        if (request.getResponseHeader('Error-Message')) {
            errorText = request.getResponseHeader('Error-Message');
        } else if (request.statusText === 'timeout') {
            errorText = 'This request took too long. Please, try again later.';
        } else {
            errorText = request.statusText;
        }
        $.jGrowl('<b>Error</b><br>' + errorText, {
            theme: 'jgrowl-error'
        });
    });
    /**
     * Overlay layer for XHR functions.
     */
    var overlay = {
        timeID: '',
        delay: 1000,
        /**
         * Start overlay with optional delay.
         * @param {integer} delay
         */
        start: function (delay) {
            // Only one overlay at a time.
            if (this.timeID !== '') {
                this.stop(this.timeID);
            }
            // Default delay.
            if (delay === undefined) {
                delay = this.delay;
            }
            // Create overlay.
            this.timeID = setTimeout(function () {
                if ($('#overlay').length === 0) {
                    $('body').append('<div id="overlay" class="ui-widget-overlay"></div>');
                    $('#overlay').html('<img src="img/ajaxloader2.gif" alt="spinner">');
                    overlay.resize();
                }
            }, delay);
            return this.timeID;
        },
        /**
         * Stop overlay.
         */
        stop: function () {
            clearTimeout(this.timeID);
            this.timeID = '';
            $('#overlay').remove();
        },
        /**
         * Set overlay height to 100%.
         */
        resize: function () {
            $('#overlay').height($(document).height());
            $('#overlay > img').css('margin-top', $(document).height() / 2 - $('#overlay > img').height() / 2);
        }
    };
}
/**
 * Non-AJAX error handling.
 */
var errorHandling = {
    display: function (errorText) {
        if (errorText === undefined) {
            errorText = 'Unknown error.';
        }
        $.jGrowl('<b>Error</b><br>' + errorText, {
            theme: 'jgrowl-error'
        });
    }
};
// Hotkeys.
$(document).unbind('keydown').bind('keydown', 'd', function () {
    $('#control-next').click();
}).bind('keydown', 'e', function () {
    $('#control-prev').click();
}).bind('keydown', 's', function () {
    $('.nextrecord').click();
}).bind('keydown', 'w', function () {
    $('.prevrecord').click();
}).bind('keydown', 'del', function () {
    if ($('#deletebutton').is(':visible'))
        $('#deletebutton').click();
}).bind('keydown', 'q', function () {
    if ($('.backbutton').is(':visible'))
        $('.backbutton').click();
}).bind('keydown', 'esc', function () {
    if ($('#pdf-viewer-copy-text').prop('checked')) {
        $('#pdf-viewer-copy-text').click();
    } else if ($('#pdf-viewer-marker').prop('checked')) {
        $('#pdf-viewer-marker').click();
    } else if ($('#pdf-viewer-note').prop('checked')) {
        $('#pdf-viewer-note').click();
    } else if ($('#pdf-viewer-marker-erase').prop('checked')) {
        $('#pdf-viewer-marker-erase').click();
    }
}).bind('keydown', 'r', function () {
    // Go back if PDF link was clicked.
    if ($("#pdf-viewer-div").data("linkHistory") !== undefined) {
        // Scroll to destination page.
        var pg = $("#pdf-viewer-div").data("linkHistory");
        scrollHandling.page = pg;
        var pgpos = $('#pdf-viewer-img-' + pg).position().top + $('#pdf-viewer-img-div').scrollTop();
        $('#pdf-viewer-img-div').animate(
            {scrollTop: pgpos},
            {
                duration: 400,
                start: function () {
                    scrollHandling.autoScroll = true;
                },
                always: function () {
                    scrollHandling.autoScroll = false;
                }
            }
        );
        $("#pdf-viewer-div").removeData("linkHistory");
    }
}).bind('keydown', 'c', function () {
    $('#pdf-viewer-controls').toggle();
    $(window).trigger('resize');
});
// Escape HTML special chars.
function escapeHtml(unsafe) {
    return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
}
/**
 * Lazy loading of pages.
 */
$('.pdf-viewer-img').one('unveil', function () {
    var $t = $(this), page = $t.attr('id').split('-').pop();
    $t.prepend('<i class="fa fa-cog fa-spin"></i>');
    $.get('pdfcontroller.php?renderpdf=1&file=' + fileName + '&page=' + page, function (answer) {
        if (answer.substr(0, 5) === 'Error') {
            errorHandling.display(answer);
        } else {
            $t.css('background-image', 'url("attachment.php?mode=inline&png=' + fileName + '.' + page + '.jpg")');
            $t.find('i').remove();
        }
    });
});
// Scroll handling.
var scrollHandling = {
    position: 0,          // Page container scroll position.
    pagePositions: [],    // Initial page positions.
    time: 0,
    autoScroll: false,
    fastScroll: false,
    allow: true,
    delay: 50,
    page: 1,
    reallow: function () {
        scrollHandling.allow = true;
    },
    speed: function (position2, time2) {
        if (this.time === 0 || time2 - this.time > 1000) {
            return 0;
        }
        return 1000 * Math.abs(position2 - this.position) / (time2 - this.time);
    },
    currentTime: function () {
        var d = new Date();
        return d.getTime();
    },
    recordTimePosition: function ($el) {
        var newTime = this.currentTime();
        if (newTime - this.time < 100) {
            return false;
        }
        this.time = newTime;
        this.position = $el.scrollTop();
    },
    getColumns: function () {
        return Math.max(1, Math.floor($('#pdf-viewer-img-div').width() / ($('#pdf-viewer-img-1').width() + 8)));
    },
    detectPageChange: function () {
        var newpage = this.page, pageBreak = $('#pdf-viewer-img-div').height() / 2,
            $t = $('#pdf-viewer-img-' + this.page),
            imgtop = $t.position().top,
            imgbottom = imgtop + $t.height(),
            scrollPos = $('#pdf-viewer-img-div').scrollTop();
        // If current page is not in the view.
        if (imgbottom < pageBreak || imgtop > pageBreak) {
            var curPage = 1, newpage = 1;
            for (var i = 0; i < scrollHandling.pagePositions.length; i++) {
                if ((scrollPos + pageBreak) < scrollHandling.pagePositions[i]) {
                    curPage = i;
                    break;
                }
                // Last page.
                curPage = i + 1;
            }
            // Find the leftmost page (there can be multiple page columns).
            newpage = scrollHandling.pagePositions.indexOf(scrollHandling.pagePositions[curPage - 1]) + 1;
        }
        // If page changed, return it.
        if (newpage !== this.page) {
            return newpage;
        } else {
            return false;
        }
    },
    loadPage: function () {
        // Scroll thumbs.
        $('#thumbs > div').removeClass('ui-state-active');
        if ($('#thumbs > div').length > 0) {
            var $thumb = $('#thumbs > div:eq(' + (this.page - 1) + ')'),
                    thtop = $thumb.position().top,
                    thbottom = thtop + $thumb.height(),
                    parbottom = $('#navpane').height();
            $thumb.addClass('ui-state-active');
            if ($('#thumbs').is(':visible') && (thtop < 0 || parbottom - thbottom < 0)) {
                $('#navpane').stop(true, false).animate({
                    scrollTop: $('#navpane').scrollTop() + thtop + ($thumb.height() / 2) - (parbottom / 2)
                }, 200);
            }
        }
        // Change page number.
        $('#control-page').val(this.page);
        // Fetch images for current pages on scroll.
        $('#pdf-viewer-img-' + this.page).trigger('unveil');
        var columns = this.getColumns();
        for (var i = parseInt(this.page) - 2 * columns; i <= parseInt(this.page) + 2 * columns; i++) {
            if (i !== this.page) {
                $('#pdf-viewer-img-' + i).trigger('unveil');
            }
        }
        if ($("#pageprev-button").prop('checked')) {
            thumbHandler.init();
        }
    },
    postLoad: function () {
        // Save open page.
        $.get('history.php?filename=' + fileName + '&page=' + scrollHandling.page);
        // If text layer is on.
        if ($('#pdf-viewer-copy-text').prop('checked') === true) {
            $('#pdf-viewer-copy-text').trigger('change');
        } else if ($('#pdf-viewer-marker').prop('checked') === true) {
            $('#pdf-viewer-marker').trigger('change');
        }
        clearTimeout($.data(scrollHandling, 'scrollTimer'));
        $.data(scrollHandling, 'scrollTimer', setTimeout(function () {

        }, 110));
    },
    getThumbPage: function () {
        var parentheight = $('#navpane').height(),
                $t = $('#thumbs > div').eq(this.page - 1),
                imgtop = $t.position().top,
                imgbottom = imgtop + $t.height();
        /**
         * To avoid iterating over all pages when finding their position, we first
         * estimate, which page is in the viewport by dividing the parent scrollTop
         * value by the height of the first page.
         */
        // If current page is not in the view.
        if (imgbottom < parentheight / 2 || imgtop > parentheight / 2) {
            var $items = $('#thumbs > div');
            var estPage = Math.ceil(1 + ($('#navpane').scrollTop() / ($('#thumbs > div').first().outerHeight())));
            for (var i = Math.max(estPage - 10, 0), j = 0; i < Math.min(estPage + 10, $items.length); i++) {
                var $t = $($items[i]), tTop = $t.position().top;
                if (tTop < parentheight && tTop > 0) {
                    var thumbPage = i + 1;
                    break;
                }
            }
        }
        if (this.fastScroll === false && thumbPage !== undefined) {
            // Fetch thumbs.
            thumbHandler.init(thumbPage);
        }
    }
};
/**
 * Scroll event controller. The heart of the scrolling actions.
 */
var scrollController = function () {
    // Scrolling using control buttons.
    // Only once per 0.5 sec.
    if (scrollHandling.autoScroll) {
        if (scrollHandling.allow) {
            // Don't run detectPageChange(), page was set by the control button.
            scrollHandling.fastScroll = false;
            scrollHandling.loadPage();
            scrollHandling.postLoad();
            scrollHandling.allow = false;
            setTimeout(scrollHandling.reallow, 500);
        }
    } else {
        // Scrolling using scrollbar.
        /**
         * Ignore fast scrollbar movement. The user is not interested in pages
         * that he/she scrolls through too fast.
         */
        scrollHandling.fastScroll = false;
        if (scrollHandling.speed(
                $('#pdf-viewer-img-div').scrollTop(),
                scrollHandling.currentTime()
                ) > 10000) {

            scrollHandling.fastScroll = true;
        }
        scrollHandling.recordTimePosition($('#pdf-viewer-img-div'));
        // Only fire in intervals.
        if (scrollHandling.allow) {
            // If page changed, do postLoad, but ignore if fast scrolled.
            var newpage = scrollHandling.detectPageChange();
            if (newpage !== false) {
                if (scrollHandling.fastScroll === false) {
                    scrollHandling.page = newpage;
                    scrollHandling.loadPage();
                    scrollHandling.postLoad();
                }
            }
            scrollHandling.allow = false;
            setTimeout(scrollHandling.reallow, scrollHandling.delay);
        }
    }
    /**
     * When scrolling stops, load a page again, because inervals don't
     * co-fire with scroll events.
     */
    clearTimeout($.data(window, 'scrollTimer'));
    $.data(window, 'scrollTimer', setTimeout(function () {
        scrollHandling.fastScroll = false;
        var newpage = scrollHandling.detectPageChange();
        // If pages changed during timeout.
        if (newpage !== false) {
            scrollHandling.page = newpage;
            scrollHandling.loadPage();
            scrollHandling.postLoad();
        }
    }, 75));
};
$('#navpane').on('scroll', function () {
    // Only appply to thumbs.
    if (!$('#thumbs').is(':visible')) {
        return;
    }
    // Scrolling using nav scrollbar.
    /**
     * Ignore fast scrollbar movement. The user is not interested in pages
     * that he/she scrolls through too fast.
     */
    scrollHandling.fastScroll = false;
    if (scrollHandling.speed(
            $('#navpane').scrollTop(),
            scrollHandling.currentTime()
            ) > 2000) {
        scrollHandling.fastScroll = true;
    }
    scrollHandling.recordTimePosition($('#navpane'));
    // Only fire in intervals.
    if (scrollHandling.allow) {
        scrollHandling.getThumbPage();
        scrollHandling.allow = false;
        setTimeout(scrollHandling.reallow, scrollHandling.delay);
    }
    /**
     * When scrolling stops, load a page again, because inervals don't
     * co-fire with scroll events.
     */
    clearTimeout($.data(window, 'scrollTimer2'));
    $.data(window, 'scrollTimer2', setTimeout(function () {
        scrollHandling.fastScroll = false;
        scrollHandling.getThumbPage();
    }, 75));
});
/**
 * Fetch text layer from the server.
 */
var getText = {
    jqxhr: '',
    callerID: '',
    init: function (f) {
        // Disable button.
        if (this.callerID.button("instance") !== undefined) {
            this.callerID.button('disable');
        }
        // Select pages to fetch: +/- 4 pages.
        var page = parseInt(scrollHandling.page),
                from = Math.max((page - 4), 1);
        // Only allow one request at a time.
        if (typeof this.jqxhr === 'object') {
            this.jqxhr.abort();
        }
        // Start overlay. Longer timeout, because PDF conversion can take longer.
        overlay.timeID = overlay.start();
        // Fetch response from server.
        this.jqxhr = $.getJSON('pdfcontroller.php?gettextlayer=1&file=' + fileName + '&from=' + from).done(function (answer) {
            // Remove overlay.
            overlay.stop();
            // Re-enable button.
            if (getText.callerID.button("instance") !== undefined) {
                getText.callerID.button('enable');
            }
            $('#pdf-viewer-img-div').find('.text-container').empty();
            // Assemble and insert the divs with text.
            var divs = '';
            $.each(answer, function (key, rows) {
                if (from !== parseInt(rows.p)) {
                    // Append divs to the page.
                    if ($('#pdf-viewer-img-' + from + ' > .text-container').length === 0) {
                        $('#pdf-viewer-img-' + from).append('<div class="text-container"></div>');
                    }
                    $('#pdf-viewer-img-' + from + ' > .text-container').show().html(divs);
                    // Start new page.
                    from = parseInt(rows.p);
                    divs = '<div class="pdf-text-div" style="top:' + rows.t + '%;left:' + rows.l + '%;width:' + rows.w + '%;height:' + rows.h + '%">' + rows.tx + '</div>';
                } else {
                    divs += '<div class="pdf-text-div" style="top:' + rows.t + '%;left:' + rows.l + '%;width:' + rows.w + '%;height:' + rows.h + '%">' + rows.tx + '</div>';
                }
            });
            // Append divs to the last page.
            if ($('#pdf-viewer-img-' + from + ' > .text-container').length === 0) {
                $('#pdf-viewer-img-' + from).append('<div class="text-container"></div>');
            }
            $('#pdf-viewer-img-' + from + ' > .text-container').html(divs);
            // We are done. Call function f().
            if (typeof f === 'function') {
                f();
            }
        }).fail(function (response) {
            // Remove overlay.
            overlay.stop();
            // Re-enable and uncheck button.
            if (response.statusText !== 'abort' && getText.callerID.button("instance") !== undefined) {
                getText.callerID.button('enable').prop('checked', false).trigger('change').button('refresh');
            }
        });
    }
};
/**
 * Fetch PDF links from the server.
 */
var getLinks = {
    linkHistory: '',
    init: function () {
        $.getJSON('pdfcontroller.php?getlinks=1&file=' + fileName).done(function (answer) {
            // Assemble search results.
            var page = '', parser = document.createElement('a');
            $.each(answer, function (key, row) {
                var ttl = row.lk;
                if (ttl.substring(0,9) === fileName) {
                    parser.href = row.lk;
                    // Scroll to destination page.
                    ttl = 'Go to page ' + parser.hash.substring(1);
                }
                $('<div class="pdfviewer-link" id="link-page-' + key + '"></div>')
                        .appendTo('#pdf-viewer-img-' + row.p);
                $('#link-page-' + key).css({
                    'width': row.w + '%',
                    'height': row.h + '%',
                    'top': row.t + '%',
                    'left': row.l + '%'
                }).attr('data-href', row.lk).attr('title', ttl);
                if (page !== row.p) {
                    page = row.p;
                }
            });
            $('#pdf-viewer-img-div').on('click', '.pdfviewer-link', function () {
                var href = $(this).data('href');
                if (href.substring(0,4) === 'http') {
                    // External link. TODO: confirm dialog.
                    window.open(href);
                } else if (href.substring(0,6) === 'mailto') {
                    // E-mail links.
                    location.href = href;
                } else {
                    // History.
                    this.linkHistory = scrollHandling.page;
                    $('#pdf-viewer-div').data("linkHistory", this.linkHistory);
                    // Parse the link.
                    var parser = document.createElement('a');
                    parser.href = href;
                    // Scroll to destination page.
                    var pg = parseInt(parser.hash.substring(1));
                    scrollHandling.page = pg;
                    var pgpos = $('#pdf-viewer-img-' + pg).position().top + $('#pdf-viewer-img-div').scrollTop();
                    $('#pdf-viewer-img-div').animate(
                        {scrollTop: pgpos},
                        {
                            duration: 400,
                            start: function () {
                                scrollHandling.autoScroll = true;
                            },
                            always: function () {
                                scrollHandling.autoScroll = false;
                            }
                        }
                    );
                }
            });
        });
    }
};
/**
 * Fetch thumbs from the server.
 */
var thumbHandler = {
    init: function (page) {
        if (page === undefined) {
            // When scrolling the right (page) panel.
            page = scrollHandling.page;
            var $thumb = $('#thumbs > div:eq(' + (page - 1) + ')');
            // Scroll thumbs to current page.
            var thumbpos = $thumb.position().top + $('#navpane').scrollTop();
            $('#navpane').scrollTop(thumbpos - $('#navpane').height() / 2 + $thumb.height() / 2);
            // Colorize current page.
            $thumb.addClass('ui-state-active');
        } else {
            // When scrolling the left (navigation) panel.
            var $thumb = $('#thumbs > div:eq(' + (page - 1) + ')');
        }
        var from = 1 + Math.floor((page - 1) / 10) * 10;
        // Get 30 thumbs.
        for (var i = Math.max(1, (from - 10)); i <= Math.min((from + 10), totalPages); i = (i + 10)) {
            // Exit if already loaded.
            if ($('#img-thumb-' + i).attr('src') === "img/ajaxloader.gif") {
                this.get(i);
            }
        }
    },
    get: function (from) {
        // Exit if being called.
        if (sessionStorage.getItem('thumblock' + from) !== null) {
            return;
        }
        sessionStorage.setItem('thumblock' + from, 1);
        $.ajax({
            url: 'pdfcontroller.php',
            data: {
                renderthumbs: 1,
                file: fileName,
                from: from
            },
            success: function (answer) {
                // Error message.
                if (answer.substr(0, 5) === 'Error') {
                    errorHandling.display(answer);
                    return false;
                }
                // Fetch images.
                for (var j = from; j <= from + 9; j++) {
                    $("#img-thumb-" + j).attr("src","").attr("src", "attachment.php?mode=inline&png="
                            + fileName + ".t" + j + ".jpg").css("margin-top", "0");
                }
            },
            complete: function () {
                sessionStorage.removeItem('thumblock' + from);
            }
        });
    },
    addBookmarks: function () {
        if ($('#bookmarks').find('p').length > 0) {
            return;
        }
        getBookmarks.init();
    }
};
/**
 * Fetch bookmarks from the server.
 */
var getBookmarks = {
    init: function () {
        // Get bookmarks.
        $.getJSON('pdfcontroller.php?renderbookmarks=1&file=' + fileName, function (answer) {
            // Add bookmarks to thumbs.
            $.each(answer, function (key, rows) {
                var ttl = '';
                ttl = $('.pdf-viewer-thumbs:eq(' + (rows.page - 1) + ')').attr('data-title');
                if (ttl === undefined)
                    ttl = '';
                $('.pdf-viewer-thumbs:eq(' + (rows.page - 1) + ')').attr('data-title', ttl
                        + '<div style="background-color:rgba(60,60,70,1);text-align:left;margin:6px 2px;line-height:1.3;padding:0.25em 0.5em">' + rows.title + '</div>');
            });
            $('.pdf-viewer-thumbs').tipsy({gravity: 'w', html: true, title: 'data-title'});
            // Add bookmarks to left navigation pane.
            if (answer.length === 0) {
                $('#reading-bookmarks').text('No bookmarks.');
                return false;
            }
            $('#reading-bookmarks').remove();
            var pages = '';
            $.each(answer, function (key, rows) {
                $('#bookmarks').append('<p class="bookmark bookmark-level-' + rows.level + '" id="bookmark-' + key + '" data-page="' + rows.page + '">' + rows.title + '</p>');
                $('#bookmark-' + key).css('padding-left', 6 * rows.level + 'px');
            });
            // Navigation: click bookmarks.
            $('#bookmarks .bookmark').click(function () {
                $('.bookmark').removeClass('ui-state-active');
                $(this).addClass('ui-state-active');
                if ($(this).data('page') !== scrollHandling.page) {
                    var pgpos = $('#pdf-viewer-img-' + $(this).data('page')).position().top + $('#pdf-viewer-img-div').scrollTop();
                    $('#pdf-viewer-img-div').animate(
                            {scrollTop: pgpos},
                            {
                                duration: 400,
                                start: function () {
                                    scrollHandling.autoScroll = true;
                                },
                                always: function () {
                                    scrollHandling.autoScroll = false;
                                }
                            }
                    );
                }
            });
            // Regexp search.
            searchbookmarks.init();
        });
    }
};
/**
 * Annotation object.
 */
var annotations = {
    get: function (annotType, others, f) {
        // Display all users' annotations?
        var users = '';
        if (others !== undefined && others === true) {
            users = '&users=other';
        }
        // Get annotations.
        $.getJSON('pdfcontroller.php?' + annotType + '=1&file=' + fileName + users, function (answer) {
            if (typeof f === 'function') {
                f(answer);
            }
        });
    },
    edit: function (dbid, txt, f) {
        $.get('pdfcontroller.php', 'editannotation=1' + '&file=' + fileName + '&dbid=' + dbid + '&text=' + encodeURIComponent(txt), function (answer) {
            if (parseInt(answer) === 1) {
                $.jGrowl('PDF note saved.');
            } else {
                errorHandling.display('The note was not saved.');
            }
            if (typeof f === 'function') {
                f();
            }
        });
    }
};
// Regexp search bookmarks in left panel.
var searchbookmarks = {
    init: function () {
        var filterId;
        // Highlight function.
        $('#bookmarks').off().on('highlight', 'p', function (e, re2) {
            var $t = $(this);
            $t.addClass('hit').html($t.text().replace(re2, '<span style="background-color:#eea">$1</span>'));
        });
        $("#bookmarks .pdf_filter").off().on('keyup', function () {
            clearTimeout(filterId);
            filterId = setTimeout(function () {searchbookmarks.filter()}, 300);
        }).on('focus', function () {
            $(this).val('');
            searchbookmarks.reset();
        });
    },
    filter: function () {
        var str = $("#bookmarks .pdf_filter").val(), $container = $('#bookmarks').find('p');
        if (str.length > 1) {
            // Escape all non-alphanum characters in the query.
            qstr = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
            var re = new RegExp(qstr, 'i'), re2 = new RegExp('\(' + qstr + '\)', 'gi');
            // Show only elements with the matching content.
            $container.hide().filter(function () {
                return re.test($(this).text());
            }).trigger('highlight', re2).show();
        } else {
            searchbookmarks.reset();
        }
    },
    reset: function () {
        $('#bookmarks > .hit').each(function () {
            $(this).html($(this).text());
        }).removeClass('hit');
        $('#bookmarks > p').show();
    }
};
// Regexp search notes in left panel.
var searchnotes = {
    init: function () {
        var filterId;
        // Highlight function.
        $('#annotations-left').off().on('highlight', '.annotation', function (e, re2) {
            var $t = $(this), $c = $t.children('.alternating_row');
            $t.addClass('hit');
            $c.html($c.text().replace(re2, '<span style="background-color:#eea">$1</span>'));
        });
        $("#annotations-left .pdf_filter").off().on('keyup', function () {
            clearTimeout(filterId);
            filterId = setTimeout(function () {searchnotes.filter();}, 300);
        }).on('focus', function () {
            $(this).val('');
            searchnotes.reset();
        });
    },
    filter: function () {
        var str = $("#annotations-left .pdf_filter").val(), $container = $('#annotations-left').find('.annotation');
        if (str.length > 1) {
            // Escape all non-alphanum characters in the query.
            qstr = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
            var re = new RegExp(qstr, 'i'), re2 = new RegExp('\(' + qstr + '\)', 'gi');
            // Show only elements with the matching content.
            $container.hide().filter(function () {
                return re.test($(this).children('.alternating_row').text());
            }).trigger('highlight', re2).show();
        } else {
            searchnotes.reset();
        }
    },
    reset: function () {
        $('#annotations-left > .hit > .alternating_row').each(function () {
            $(this).html($(this).text());
        });
        $('#annotations-left > .annotation').removeClass('hit').show();
    }
};
// Search results in left panel.
var searchresults = {
    init: function () {
        $("#search-results .search-result").click(function () {
            var $t = $(this), $target = $('#' + $t.data('linksto')),
                    targetpg = parseInt($target.parent().parent().attr('id').split('-').pop()),
                    pg = parseInt(scrollHandling.page);
            if (pg !== targetpg) {
                scrollHandling.page = targetpg;
                var pgpos = $('#pdf-viewer-img-' + targetpg).position().top + $('#pdf-viewer-img-div').scrollTop();
                $('#pdf-viewer-img-div').animate(
                        {scrollTop: pgpos},
                        {
                            duration: 400,
                            start: function () {
                                scrollHandling.autoScroll = true;
                            },
                            always: function () {
                                searchresults.adjustSearchResultPos($target, targetpg);
                                scrollHandling.autoScroll = false;
                            }
                        }
                );
            } else {
                searchresults.adjustSearchResultPos($target, targetpg);
            }
            $('.highlight-container .pdfviewer-highlight').css('box-shadow', '');
            $target.css('box-shadow', '0 0 10px 10px rgba(0,0,155,0.33)');
            $('#search-results .search-result').removeClass('shown').removeClass('ui-state-active');
            $t.addClass('shown').addClass('ui-state-active');
        });
    },
    // If search result out of view, scroll.
    adjustSearchResultPos: function ($target, targetpg) {
        var parentheight = $('#pdf-viewer-img-div').height(),
                divtop = $target.position().top + $('#pdf-viewer-img-' + targetpg).position().top;
        // Scroll to bottom of the page.
        if (divtop > parentheight) {
            var pgpos = $('#pdf-viewer-img-' + targetpg).position().top
                    + $('#pdf-viewer-img-div').scrollTop()
                    + $('#pdf-viewer-img-' + targetpg).height()
                    - parentheight;
            $('#pdf-viewer-img-div').animate({'scrollTop': pgpos}, 200);
        }
        // Scroll to top of the page.
        if (divtop < 0) {
            var pgpos = $('#pdf-viewer-img-' + targetpg).position().top
                    + $('#pdf-viewer-img-div').scrollTop();
            $('#pdf-viewer-img-div').animate({'scrollTop': pgpos}, 200);
        }
    }
};
// Window resize.
$(window).resize(function () {
    var toolbarH = $('#pdf-viewer-controls').outerHeight(), zoom = localStorage.getItem('zoom');
    if ($('#pdf-viewer-controls').is(':hidden'))
        toolbarH = 0;
    $('#pdf-viewer-div').height($('#pdf-viewer-div').parent().height() - toolbarH);
    if (zoom === 'o') {
        $('#size1').click();
    } else if (zoom === 'w') {
        $('#size2').click();
    } else if (!isNaN(zoom)) {
        $("#zoom").trigger('slidestop');
        $('#pdf-viewer-img-div').trigger('scroll');
    }
    overlay.resize();
});
/**
 * Toolbar binding appears here in order of appearance in GUI from left to right.
 */
// Download PDF button.
$('#save').button().click(function () {
    $(this).blur();
    $('#save-container').dialog({
        autoOpen: true,
        modal: true,
        buttons: {
            'Download': function () {
                $('#save-container form').submit();
                $(this).dialog('destroy');
                return false;
            },
            'Close': function () {
                $(this).dialog('destroy');
            }
        },
        close: function () {
            $(this).dialog('destroy');
        }
    });
}).tipsy({
    gravity: 'nw'
}).button('widget').click(function () {
        $(this).removeClass('ui-state-focus');
});
/**
 * Page zooming.
 */
// Zoom to 100%.
$('#size1').click(function () {
    $(this).blur();
    var page = scrollHandling.page,
        scrollTop = $('#pdf-viewer-img-div').scrollTop(),
        imgtop = $('#pdf-viewer-img-' + page).position().top;
    // Change page sizes.
    $('.pdf-viewer-img').each(function () {
        var $t = $(this);
        $t[0].style.width = $t.data('width') + 'px';
        $t[0].style.height = $t.data('height') + 'px';
    });
    // Save new page positions.
    scrollHandling.pagePositions = [];
    $('#pdf-viewer-img-div').find('.pdf-viewer-img').each(function (i) {
        scrollHandling.pagePositions[i] = $(this).position().top + scrollTop;
    });
    $('#zoom').slider("value", 100);
    $('#zoom').next().text('100%');
    localStorage.setItem('zoom', 'o');
    //KEEP ZOOMED PAGE IN THE SAME VERTICAL POSITION
    var imgtop2 = $('#pdf-viewer-img-' + page).position().top;
    $('#pdf-viewer-img-div').scrollTop(scrollTop + imgtop2 - imgtop);
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Zoom to screen width.
$('#size2').on('click', function () {
    $(this).blur();
    var page = scrollHandling.page,
        parentw = $('#pdf-viewer-img-div').width(),
        scrollTop = $('#pdf-viewer-img-div').scrollTop(),
        imgtop = $('#pdf-viewer-img-' + page).position().top,
        imgw = $('#pdf-viewer-img-' + page).data('width'),
        iw = Math.min((parentw - 30), imgw),
        piw = Math.round(100 * iw / imgw);
    // Change page sizes.
    $('#pdf-viewer-img-div').find('.pdf-viewer-img').each(function () {
        var $t = $(this),
                imgw = $t.data('width'),
                imgh = $t.data('height'),
                iw = imgw * piw / 100;
        var ih = imgh * iw / imgw;
        $t[0].style.width = iw + 'px';
        $t[0].style.height = ih + 'px';
    });
    // Save new page positions.
    scrollHandling.pagePositions = [];
    $('#pdf-viewer-img-div').find('.pdf-viewer-img').each(function (i) {
        scrollHandling.pagePositions[i] = $(this).position().top + scrollTop;
    });
    $('#zoom').slider("value", piw);
    $('#zoom').next().text(piw + '%');
    //KEEP ZOOMED PAGE IN THE SAME VERTICAL POSITION
    var imgtop2 = $('#pdf-viewer-img-' + page).position().top;
    $('#pdf-viewer-img-div').scrollTop(scrollTop + imgtop2 - imgtop);
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Zoom slider.
$('#zoom').slider({
    min: 50,
    max: 150,
    value: 100,
    step: 5
}).on("slide", function(e, ui) {
    var page = scrollHandling.page,
        imgtop = $('#pdf-viewer-img-' + page).position().top;
    $('.pdf-viewer-img').each(function () {
        this.style.width = (ui.value * $(this).data('width') / 100) + 'px';
        this.style.height = (ui.value * $(this).data('height') / 100) + 'px';
    });
    $(this).next().text(ui.value + '%');
    localStorage.setItem('zoom', ui.value);
    //KEEP ZOOMED PAGE IN THE SAME VERTICAL POSITION
    var imgtop2 = $('#pdf-viewer-img-' + page).position().top;
    $('#pdf-viewer-img-div').scrollTop($('#pdf-viewer-img-div').scrollTop() + imgtop2 - imgtop);
    //WHEN SECOND PAGE POPS IN ON THE RIGHT
    if (imgtop2 - imgtop === 0) {
        $('#pdf-viewer-img-div').trigger('scroll');
    }
}).on("slidestop", function() {
    // Save new page positions.
    scrollHandling.pagePositions = [];
    $('#pdf-viewer-img-div').find('.pdf-viewer-img').each(function (i) {
        scrollHandling.pagePositions[i] = $(this).position().top + $('#pdf-viewer-img-div').scrollTop();
    });
});
/**
 * Page navigation.
 */
// Go to first page.
$('#control-first').click(function () {
    $(this).blur();
    scrollHandling.page = 1;
    $('#pdf-viewer-img-div').animate(
            {scrollTop: 0},
            {
                duration: 400,
                start: function () {
                    scrollHandling.autoScroll = true;
                },
                always: function () {
                    scrollHandling.autoScroll = false;
                }
            }
    );
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Go to previous page.
$('#control-prev').click(function () {
    $(this).blur();
    if ($('body').data('lock') === 1) {
        return false;
    }
    scrollHandling.page = scrollHandling.page - scrollHandling.getColumns();
    if (scrollHandling.page <= 1) {
        scrollHandling.page = 1;
    }
    var pgpos = $('#pdf-viewer-img-' + scrollHandling.page).position().top + $('#pdf-viewer-img-div').scrollTop();
    $('#pdf-viewer-img-div').animate(
            {scrollTop: pgpos},
            {
                duration: 250,
                start: function () {
                    $('body').data('lock', 1);
                    scrollHandling.autoScroll = true;
                },
                always: function () {
                    $('body').data('lock', 0);
                    scrollHandling.autoScroll = false;
                }
            }
    );
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Go to page number.
$('#control-page').keydown(function (e) {
    if (e.which !== 13) {
        return true;
    }
    var pg = parseInt($(this).val());
    if (isNaN(pg) || pg < 1 || pg > totalPages) {
        $(this).val('1');
        pg = 1;
    }
    scrollHandling.page = pg;
    var pgpos = $('#pdf-viewer-img-' + pg).position().top + $('#pdf-viewer-img-div').scrollTop();
    $('#pdf-viewer-img-div').animate(
            {scrollTop: pgpos},
            {
                duration: 400,
                start: function () {
                    scrollHandling.autoScroll = true;
                },
                always: function () {
                    scrollHandling.autoScroll = false;
                }
            }
    );
    return false;
}).focus(function () {
    this.select();
});
// Go to next page.
$('#control-next').click(function () {
    $(this).blur();
    if ($('body').data('lock') === 1) {
        return false;
    }
    scrollHandling.page = scrollHandling.page + scrollHandling.getColumns();
    if (scrollHandling.page >= totalPages) {
        scrollHandling.page = totalPages;
    }
    var pgpos = $('#pdf-viewer-img-' + (scrollHandling.page)).position().top + $('#pdf-viewer-img-div').scrollTop();
    $('#pdf-viewer-img-div').animate(
            {scrollTop: pgpos},
            {
                duration: 250,
                start: function () {
                    $('body').data('lock', 1);
                    scrollHandling.autoScroll = true;
                },
                always: function () {
                    $('body').data('lock', 0);
                    scrollHandling.autoScroll = false;
                }
            }
    );
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Go to last page.
$('#control-last').click(function () {
    $(this).blur();
    if (scrollHandling.page === totalPages) {
        return false;
    }
    scrollHandling.page = totalPages;
    var pgpos = $('#pdf-viewer-img-' + totalPages).position().top + $('#pdf-viewer-img-div').scrollTop();
    $('#pdf-viewer-img-div').animate(
            {scrollTop: pgpos},
            {
                duration: 400,
                start: function () {
                    scrollHandling.autoScroll = true;
                },
                always: function () {
                    scrollHandling.autoScroll = false;
                }
            }
    );
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Copy image.
$('#pdf-viewer-copy-image').click(function () {
    $(this).blur();
    var page = scrollHandling.page,
            img = $('#pdf-viewer-img-' + page).css('background-image').match('https?://.*jpg');
    $('#image-to-copy').attr('src', img);
    $('#image-src').val(img);
    $('#copy-image-container').dialog({
        autoOpen: true,
        modal: true,
        width: Math.min($('#pdf-viewer-img-' + page).data('width') + 40, $(window).width() - 40),
        height: Math.min($('#pdf-viewer-img-' + page).data('height') + 40, $(window).height() - 40),
        title: 'Select an area to copy, then click "Save to Files" or "Download"',
        buttons: {
            'Save to Files': function () {
                if ($('#x').val() === '')
                    return false;
                $('#copy-image-mode').val('save');
                $('#copy-image-container form').ajaxSubmit(function (answer) {
                    $.jGrowl(answer);
                });
            },
            'Download': function () {
                if ($('#x').val() === '')
                    return false;
                $('#copy-image-mode').val('download');
                $('#copy-image-container form').submit();
            },
            'Close': function () {
                $.Jcrop('#image-to-copy').destroy();
                $('.jcrop-holder').remove();
                $(this).dialog('destroy');
            }
        },
        open: function () {
            $('#image-to-copy').Jcrop({
                onSelect: function (c) {
                    $('#x').val(c.x);
                    $('#y').val(c.y);
                    $('#w').val(c.w);
                    $('#h').val(c.h);
                }
            });
        },
        close: function () {
            $.Jcrop('#image-to-copy').destroy();
            $('.jcrop-holder').remove();
            $(this).dialog('destroy');
        }
    });
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Add text layer.
$('#pdf-viewer-copy-text').change(function () {
    $(this).blur();
    if ($(this).is(':checked')) {
        // Remove drag-scroll.
        $('#pdf-viewer-img-div').unbind('mouseup mousedown mouseout');
        // Hide annotations.
        if ($('#pdf-viewer-annotations').prop('checked') === true) {
            $('#pdf-viewer-annotations').prop('checked', false).trigger('change').button("refresh");
        }
        // Fetch text from server.
        getText.callerID = $(this);
        getText.init(function () {
            if ($('#pdf-viewer-img-' + scrollHandling.page).find('.pdf-text-div').length > 0) {
                // Show text containers.
                $('.text-container').show().css('cursor', 'default');
                // Bind Selectable UI to text divs.
                $('.text-container').selectable({
                    distance: 0,
                    stop: function () {
                        var txt = '';
                        $(this).find(".ui-selected").each(function () {
                            txt = txt + $(this).text() + ' ';
                        });
                        // Try to remove word hyphenation.
                        txt = txt.replace(/(- )/g, '');
                        txt = txt.replace(/\s{2,}/g, ' ');
                        txt = $.trim(txt);
                        if (txt === '')
                            return false;
                        // Insert text to textarea.
                        $('#copy-text-container').show().css('margin-left', '-5000')
                                .html('<textarea style="width:99%;height:98%">' + txt + '</textarea>');
                        $('#copy-text-container > textarea').select();
                        try {
                            // Copy text to clipboard.
                            document.execCommand('copy');
                            $.jGrowl('Copied to clipboard:<div class="brief">' + txt + '</div>');
                            $('#copy-text-container').css('margin-left', '').hide().html('');
                        } catch (err) {
                            // Failed, open dialog to copy text manually.
                            $('#copy-text-container').css('margin-left', '').hide();
                            $('#copy-text-container').dialog({
                                autoOpen: true,
                                modal: true,
                                width: 640,
                                height: 480,
                                title: 'Press Ctrl+C to copy the text to clipboard.',
                                buttons: {
                                    'Close': function () {
                                        $(this).dialog('destroy');
                                    }
                                },
                                open: function () {
                                    $('#copy-text-container > textarea').select();
                                },
                                close: function () {
                                    $(this).dialog('destroy');
                                }
                            });
                        }
                    }
                });
            } else {
                $.jGrowl('This PDF or PDF page has no text layer.');
            }
        });
    } else {
        // Unbind and remove text layer.
        var $textdivs = $('#pdf-viewer-img-div').find('.text-container');
        if ($textdivs.selectable('instance') !== undefined) {
            $textdivs.selectable('destroy');
        }
        $textdivs.remove();
        // Re-enable drag-scrolling.
        $('#pdf-viewer-img-div').clickNScroll();
    }
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
/**
 * Second row. Left navigation pane.
 */
// Page previews. Left navigation pane.
$('#pageprev-button').change(function () {
    $(this).blur();
    // Button group management.
    if ($(this).prop('checked')) {
        localStorage.setItem('pageprev-button', 'On');
        localStorage.setItem('bookmarks-button', 'Off');
        localStorage.setItem('notes-button', 'Off');
    } else {
        localStorage.setItem('pageprev-button', 'Off');
    }
    if ($('#bookmarks-button').prop('checked') || $('#notes-button').prop('checked') || $('#search-results-button').prop('checked')) {
        $('#bookmarks-button, #notes-button, #search-results-button').prop('checked', false).button('refresh');
    } else {
        $('#navpane').toggle();
        $(window).trigger('resize');
    }
    // Replace left panel content.
    $('#thumbs').show();
    $('#bookmarks, #annotations-left, #search-results').hide();
    // Fetch thumbs in batches of 10.
    if ($(this).prop('checked')) {
        thumbHandler.init();
        thumbHandler.addBookmarks();
    }
}).button().button('widget').on('click', function (e) {
    $(this).removeClass('ui-state-focus');
}).tipsy({
    gravity: 'nw'
});
// Navigate: Click on page preview.
$('#thumbs').click(function (e) {
    $(this).blur();
    var $t = $(e.target), pg = $t.data('page'),
            currpg = scrollHandling.page,
            pgpos = $('#pdf-viewer-img-' + pg).position().top + $('#pdf-viewer-img-div').scrollTop();
    if (pg === undefined || pg === currpg) {
        return false;
    }
    scrollHandling.page = pg;
    $('#pdf-viewer-img-div').animate(
            {scrollTop: pgpos},
            {
                duration: 400,
                start: function () {
                    scrollHandling.autoScroll = true;
                },
                always: function () {
                    scrollHandling.autoScroll = false;
                }
            }
    );
});
// Bookmarks. Left navigation pane.
$('#bookmarks-button').change(function () {
    $(this).blur();
    // Button group management.
    if ($(this).prop('checked')) {
        localStorage.setItem('pageprev-button', 'Off');
        localStorage.setItem('bookmarks-button', 'On');
        localStorage.setItem('notes-button', 'Off');
    } else {
        localStorage.setItem('bookmarks-button', 'Off');
    }
    if ($('#pageprev-button').prop('checked') || $('#notes-button').prop('checked') || $('#search-results-button').prop('checked')) {
        $('#pageprev-button, #notes-button, #search-results-button').prop('checked', false).button('refresh');
    } else {
        $('#navpane').toggle();
        $(window).trigger('resize');
    }
    // Replace left panel content.
    $('#bookmarks').show();
    $('#thumbs, #annotations-left, #search-results').hide();
    // Fetch bookmarks.
    if ($(this).prop('checked') && $('#bookmarks').find('p').length === 0) {
        $('#reading-bookmarks').remove();
        $('#bookmarks').append('<div id="reading-bookmarks" style="padding:12px">Reading bookmarks.</div>');
        getBookmarks.init();
    }
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Annotations. Left navigation pane.
$('#notes-button').change(function (e, target) {
    $(this).blur();
    // Button group management.
    if ($('#pageprev-button').prop('checked') || $('#bookmarks-button').prop('checked') || $('#search-results-button').prop('checked')) {
        $('#pageprev-button, #bookmarks-button, #search-results-button').prop('checked', false).button('refresh');
    } else {
        $('#navpane').toggle();
        $(window).trigger('resize');
    }
    if ($(this).prop('checked')) {
        localStorage.setItem('pageprev-button', 'Off');
        localStorage.setItem('bookmarks-button', 'Off');
        localStorage.setItem('notes-button', 'On');
        // Replace left panel content.
        $('#annotations-left').show();
        $('#bookmarks, #thumbs, #search-results').hide();
        // Fetch PDF notes.
        annotations.get('getpdfnotes', false, function (answer) {
            $('#annotations-left div.annotation, #annotations-left p').remove();
            if (answer.length === 0)
                $('#annotations-left').append('<p style="padding:0 6px">No notes.</p>');
            // Assemble annotation divs.
            $.each(answer, function (key, rows) {
                var annot = rows.annotation, noteid = 'note-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                $('#annotations-left').append('<div class="annotation" id="nav-' + noteid + '" data-linksto="'
                        + noteid + '" data-page="' + rows.page + '" data-dbid="' + rows.id
                        + '"><div class="ui-state-default">Page ' + rows.page + ', note ' + rows.id
                        + ':</div><div class="alternating_row">' + annot
                        + '</div><textarea></textarea>'
                        + '<div class="ui-state-default note-edit"><i class="fa fa-pencil"></i> Edit</div>'
                        + '<div class="ui-state-default note-save" style="display:none"><i class="fa fa-save"></i> Save</div></div>');
            });
            // Bind events to annotations.
            // Click on an annotation note.
            $('#annotations-left .annotation').click(function () {
                // If annotations not on page, display them.
                if (!$('#pdf-viewer-annotations').prop('checked')) {
                    $('#pdf-viewer-annotations')
                            .prop('checked', true)
                            .trigger('change', '#' + $(this).data('linksto'))
                            .button('refresh');
                } else {
                    $('.marker-note').removeClass('marker-edit');
                    $('#' + $(this).data('linksto')).addClass('marker-edit');
                }
                // Add background color.
                $('.annotation').removeClass('ui-state-active');
                $(this).addClass('ui-state-active');
                // Navigate to correct page.
                if (!target && parseInt($(this).data('page')) !== parseInt(scrollHandling.page)) {
                    var pgpos = $('#pdf-viewer-img-' + $(this).data('page')).position().top + $('#pdf-viewer-img-div').scrollTop();
                    $('#pdf-viewer-img-div').animate(
                            {scrollTop: pgpos},
                            {
                                duration: 400,
                                start: function () {
                                    scrollHandling.autoScroll = true;
                                },
                                always: function () {
                                    scrollHandling.autoScroll = false;
                                }
                            }
                    );
                }
            });
            // Edit button. Show textarea and Save button.
            $('#annotations-left .note-edit').click(function () {
                var $t = $(this), $cont = $t.prev().prev(),
                        $ta = $(this).prev('textarea');
                $cont.hide();
                $ta.show().val($cont.text());
                $t.hide().next().show();
            });
            // Save button. Save and hide textarea and Save button.
            $('#annotations-left .note-save').click(function () {
                var $t = $(this), $cont = $t.prev().prev().prev(),
                        $ta = $(this).prev().prev('textarea'),
                        txt = $ta.val(), dbid = $t.parent().data('dbid'),
                        $target = $('#' + $t.parent().data('linksto'));
                annotations.edit(dbid, txt, function () {
                    $target.attr('data-annotation', escapeHtml(txt));
                    $cont.text(txt).show();
                    $ta.hide().val('');
                    $t.hide().prev().show();
                });
            });
            // This code opens an annotation box, when an annotation is clicked on a page.
            if (target) {
                $(target).find('.note-edit').click();
                $('#navpane').scrollTop($(target).position().top + $('#navpane').position().top - 100);
            }
        });
        // Regexp search.
        searchnotes.init();
    } else {
        localStorage.setItem('notes-button', 'Off');
    }
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Print annotations button.
$('#print-notes').click(function () {
    $(this).blur();
    if ($('#annotations-left').html() !== '') {
        $('.annotation').removeClass('ui-state-active');
        var w = window.open('', '', 'width=800,height=400');
        w.document.write('<html><head><style type="text/css">#print-notes,.pdf_filter,.note-save,.note-edit {display:none}</style></head><body>');
        w.document.write($('#annotations-left').html());
        w.document.write('</body>');
        w.print();
        //FOR IE
        w.document.close();
        //FOR OTHER BROWSERS
        w.close();
    }
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Search results. This button only toggles the search results, which are populated by the search routine.
$('#search-results-button').change(function () {
    $(this).blur();
    // Button group management.
    if ($('#pageprev-button').prop('checked') || $('#bookmarks-button').prop('checked') || $('#notes-button').prop('checked')) {
        $('#pageprev-button, #bookmarks-button, #notes-button').prop('checked', false).button('refresh');
    } else {
        $('#navpane').toggle();
        $(window).trigger('resize');
    }
    // Replace left panel content.
    $('#search-results').show();
    $('#annotations-left, #bookmarks, #thumbs').hide();
}).button({
    disabled: true
}).next().tipsy();
/**
 * Annotation button group.
 */
// PDF annotations main button.
$('#pdf-viewer-annotations').change(function (e, noteTarget) {
    $(this).blur();
    if ($(this).is(':checked')) {
        // Uncheck Copy text button. UI selectable must be removed.
        if ($('#pdf-viewer-copy-text').prop('checked') === true)
            $('#pdf-viewer-copy-text').trigger('click');
        // Enable annotation buttons.
        $('#pdf-viewer-marker,#pdf-viewer-note,#pdf-viewer-marker-erase,#pdf-viewer-others-annotations').button('enable');
        // Bind annotation containers events.
        $('.annotation-container').show().click(function (e) {
            var $t = $(e.target);
            // Edit notes in the left panel.
            if ($t.hasClass('marker-note')) {
                var target = '#nav-' + $t.attr('id');
                // Open the left panel.
                if ($('#annotations-left').is(':hidden')) {
                    $('#notes-button').prop('checked', true).trigger('change', target);
                } else {
                    $(target).find('.note-edit').click();
                    $('#navpane').scrollTop($(target).position().top + $('#navpane').position().top - 100);
                }
            }
            // Create a new note.
            if ($('#pdf-viewer-note').prop('checked') && !$(e.target).hasClass('marker-note')) {
                //CREATE NEW PINNED NOTE ON CLICK
                var prntpos = $(this).offset(),
                        pg = $(this).parent().attr('id').split('-').pop(),
                        posx = Math.round(1000 * (e.pageX - prntpos.left) / $(this).width() - 35) / 10,
                        posy = Math.round(1000 * (e.pageY - prntpos.top) / $(this).height() - 25) / 10,
                        markid = 'note-' + pg + '-' + 10 * posy + '-' + 10 * posx;
                if ($('#' + markid).length === 1)
                    return false;
                $('<div class="marker marker-note" id="' + markid + '" data-dbid="" data-annotation=""></div>')
                        .appendTo(this);
                $('#' + markid).css('top', posy + '%').css('left', posx + '%');
                //SAVE NEW NOTE, GET DBID
                $.get('pdfcontroller.php', 'savepdfnote=1&file=' + fileName + '&page=' + pg + '&top=' + posy + '&left=' + posx,
                        function (answer) {
                            $('#' + markid).attr('data-dbid', answer).text(answer);
                            //OPEN TEXTAREA IN NAVPANE
                            var target = '#nav-' + markid;
                            if ($('#annotations-left').is(':hidden')) {
                                $('#notes-button').prop('checked', true).trigger('change', target);
                            } else {
                                $('#notes-button').prop('checked', false).change().prop('checked', true).trigger('change', target);
                            }
                        });
            }
        }).mouseenter(function () {
            // Show custom annotation cursor.
            if ($('#pdf-viewer-note').prop('checked')
                    || $('#pdf-viewer-marker-erase').prop('checked')
                    || $('#pdf-viewer-marker').prop('checked'))
                $("#cursor").show();
        }).mouseleave(function () {
            // Hide custom annotation cursor.
            if ($('#pdf-viewer-note').prop('checked')
                    || $('#pdf-viewer-marker-erase').prop('checked')
                    || $('#pdf-viewer-marker').prop('checked'))
                $("#cursor").hide();
        }).mousemove(function (e) {
            // Move custom annotation cursor.
            var posx = 16 + e.pageX, posy = 16 + e.pageY;
            $('#cursor').css('top', posy + 'px').css('left', posx + 'px');
        });
        // Fetch markers.
        annotations.get('getpdfmarkers', false, function (answer) {
            var divs = '', pdfpg = '1';
            $.each(answer, function (key, rows) {
                var markid = 'marker-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                if (pdfpg !== rows.page) {
                    $('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html($('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html() + divs);
                    divs = '<div class="marker marker-yellow" id="' + markid + '" data-dbid="' + rows.id + '" style="top:'
                            + rows.top + '%;left:' + rows.left + '%;width:' + rows.width + '%"></div>';
                    pdfpg = rows.page;
                } else {
                    divs += '<div class="marker marker-yellow" id="' + markid + '" data-dbid="' + rows.id + '" style="top:'
                            + rows.top + '%;left:' + rows.left + '%;width:' + rows.width + '%"></div>';
                }
            });
            $('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html($('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html() + divs);
        });
        // Fetch notes.
        annotations.get('getpdfnotes', false, function (answer) {
            var divs = '', pdfpg = '1';
            $.each(answer, function (key, rows) {
                var markid = 'note-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                if (pdfpg !== rows.page) {
                    $('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html($('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html() + divs);
                    divs = '<div class="marker marker-note" id="' + markid + '" data-dbid="' + rows.id + '" data-annotation="'
                            + escapeHtml(rows.annotation) + '" style="top:' + rows.top + '%;left:' + rows.left
                            + '%">' + rows.id + '</div>';
                    pdfpg = rows.page;
                } else {
                    divs += '<div class="marker marker-note" id="' + markid + '" data-dbid="' + rows.id + '" data-annotation="'
                            + escapeHtml(rows.annotation) + '" style="top:' + rows.top + '%;left:' + rows.left
                            + '%">' + rows.id + '</div>';
                }
            });
            $('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html($('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html() + divs);
            // If user clicked on a note in left panel.
            if (noteTarget !== undefined) {
                $('.marker-note').removeClass('marker-edit');
                $(noteTarget).addClass('marker-edit');
            }
        });
    } else {
        $('.annotation-container').empty().hide().unbind();
        if ($('#pdf-viewer-marker').is(':checked'))
            $('#pdf-viewer-marker').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-note').is(':checked'))
            $('#pdf-viewer-note').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked'))
            $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-others-annotations').is(':checked'))
            $('#pdf-viewer-others-annotations').prop('checked', false).change().button('refresh');
        $('#pdf-viewer-marker,#pdf-viewer-note,#pdf-viewer-marker-erase,#pdf-viewer-others-annotations').button('disable');
    }
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// BLUE MARKER PEN.
$('#pdf-viewer-marker').change(function () {
    $(this).blur();
    if ($(this).is(':checked')) {
        // UNCHECK OTHER BUTTONS.
        if ($('#pdf-viewer-note').is(':checked'))
            $('#pdf-viewer-note').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked'))
            $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
        // REMOVE DRAG-SCROLL
        $('#pdf-viewer-img-div').unbind('mouseup mousedown mouseout');
        // ADD CURSOR CLASS.
        $("#cursor > span").addClass('fa-pencil');
        $('#pdf-viewer-img-div').css('cursor', 'default');
        // Hide notes.
        $('#pdf-viewer-img-div').find('.marker-note').hide();
        // Fetch text from server.
        getText.callerID = $(this);
        getText.init(function () {
            if ($('#pdf-viewer-img-' + scrollHandling.page).find('.pdf-text-div').length > 0) {
                // There is a text layer.
                // Show text containers.
                $('.text-container').show();
                // Bind cursor to text layer.
                $('.text-container').mouseenter(function () {
                    // Show custom annotation cursor.
                    if ($('#pdf-viewer-marker').prop('checked')) {
                        $("#cursor").show();
                    }
                }).mouseleave(function () {
                    // Hide custom annotation cursor.
                    if ($('#pdf-viewer-marker').prop('checked')) {
                        $("#cursor").hide();
                    }
                }).mousemove(function (e) {
                    // Move custom annotation cursor.
                    var posx = 16 + e.pageX, posy = 16 + e.pageY;
                    $('#cursor').css('top', posy + 'px').css('left', posx + 'px');
                });
                // Bind Selectable UI to text divs.
                $('.text-container').selectable({
                    distance: 0,
                    stop: function () {
                        var $t = $(this), $d = $t.parent().find('.annotation-container'), pg = $t.parent().attr('id').split('-').pop(),
                                markers = [], divs = '';
                        $t.find(".ui-selected").each(function () {
                            // GET COORDINATES
                            var postop = Math.round(1000 * $(this).position().top / $t.height()) / 10,
                                    posleft = Math.round(1000 * $(this).position().left / $t.width()) / 10,
                                    w = Math.round(1000 * $(this).width() / $t.width()) / 10,
                                    markid = 'marker-' + pg + '-' + 10 * postop + '-' + 10 * posleft;
                            // IF UNIQUE, ADD MARKER TO PAGE AND ARRAY
                            if ($('#' + markid).length === 0) {
                                markers.push({id: markid, top: postop, left: posleft, width: w});
                                divs += '<div id="' + markid + '" class="marker marker-yellow" style="top:'
                                        + postop + '%;left:' + posleft + '%;width:'
                                        + w + '%;height:1.2%" data-dbid=""></div>';
                            }
                        });
                        $d.html($d.html() + divs);
                        // Empty selection.
                        if (markers.length === 0) {
                            return false;
                        }
                        // SEND MARKERS ARRAY TO SERVER TO SAVE AND GET DBIDS BACK
                        var finald = {'markers': markers, 'savepdfmarkers': 1, 'file': fileName, 'page': pg};
                        $.ajax({
                            url: 'pdfcontroller.php',
                            data: finald,
                            type: 'post',
                            dataType: 'json',
                            success: function (answer) {
                                $.jGrowl('PDF markers saved. Count: ' + answer.length);
                                $.each(answer, function (key, row) {
                                    $('#' + row.markid).attr('data-dbid', row.dbid);
                                });
                            }
                        });
                    }
                });
            } else {
                // There is no text layer. Freehand marking.
                $('#pdf-viewer-img-div').find('.text-container').remove();
                $('#pdf-viewer-img-div').css('cursor', 'text');
                // Bind drawing to annotation container.
                $('.annotation-container').mousedown(function (e) {
                    var pg = $(this).parent().attr('id').split('-').pop(),
                            markstposX = e.pageX,
                            markstposY = e.pageY,
                            prntpos = $(this).offset(),
                            posx = Math.round(1000 * (e.pageX - prntpos.left) / $(this).width()) / 10,
                            posy = Math.round(1000 * (e.pageY - prntpos.top) / $(this).height() - 5) / 10,
                            markid = 'marker-' + pg + '-' + 10 * posy + '-' + 10 * posx;
                    if ($('#' + markid).length === 1)
                        return false;
                    $(this).data('marker', {
                        'markid': markid,
                        'markstposX': markstposX,
                        'markstposY': markstposY
                    });
                    $('<div class="marker marker-yellow" id="' + markid + '" data-dbid=""></div>').appendTo(this);
                    $('#' + markid).css('top', posy + '%').css('left', posx + '%');
                }).mousemove(function (e) {
                    if ($(this).data('marker')) {
                        var markstposX = $(this).data('marker').markstposX,
                                markw = e.pageX - markstposX,
                                markid = $(this).data('marker').markid;
                        $('#' + markid).width(markw);
                    }
                }).mouseup(function (e) {
                    if (!$(this).data('marker'))
                        return false;
                    var pg = $(this).parent().attr('id').split('-').pop(),
                            prntpos = $(this).offset(),
                            markstposX = $(this).data('marker').markstposX,
                            markstposY = $(this).data('marker').markstposY,
                            posx = Math.round(1000 * (markstposX - prntpos.left) / $(this).width()) / 10,
                            posy = Math.round(1000 * (markstposY - prntpos.top) / $(this).height() - 6) / 10,
                            markw = Math.round(1000 * (e.pageX - markstposX) / $(this).width()) / 10,
                            markid = $(this).data('marker').markid;
                    $('#' + markid).width(markw + '%');
                    $(this).data('marker', '');
                    if (markw < 1) {
                        $('#' + markid).remove();
                        return false;
                    }
                    $.post('pdfcontroller.php',
                            {
                                'savepdfmarkers': '1',
                                'file': fileName,
                                'page': pg,
                                'markers[0]': {'id': markid, 'top': posy, 'left': posx, 'width': markw}
                            },
                            function (answer) {
                                $('#' + answer[0].markid).attr('data-dbid', answer[0].dbid);
                            },
                            'json');
                });
            }
        });
    } else {
        $('.annotation-container').unbind('mousedown mouseup');
        $('#pdf-viewer-img-div').clickNScroll().css('cursor', 'grab');
        $("#cursor").hide().find('span').removeClass('fa-pencil');
        // Unbind and remove text layer.
        var $textdivs = $('#pdf-viewer-img-div').find('.text-container');
        if ($textdivs.selectable('instance') !== undefined) {
            $textdivs.selectable('destroy');
        }
        $textdivs.remove();
        // Show notes.
        $('#pdf-viewer-img-div').find('.marker-note').show();
    }
}).button({
    disabled: true
}).next().tipsy();
//PINNED NOTES
$('#pdf-viewer-note').change(function () {
    $(this).blur();
    if ($(this).is(':checked')) {
        //UNCHECK OTHER BUTTONS
        if ($('#pdf-viewer-marker').is(':checked'))
            $('#pdf-viewer-marker').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked'))
            $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
        //ADD CURSOR CLASS
        $("#cursor > span").addClass('fa-thumb-tack');
        //REMOVE DRAG-SCROLL
        $('#pdf-viewer-img-div').unbind('mouseup mousedown mouseout').css('cursor', 'default');
    } else {
        //ENABLE DRAG-SCROLL
        $('#pdf-viewer-img-div').clickNScroll().css('cursor', 'grab');
        //REMOVE CURSOR
        $("#cursor").hide().find('span').removeClass('fa-thumb-tack');
    }
}).button({
    disabled: true
}).next().tipsy();
//ERASE ANNOTATIONS
$('#pdf-viewer-marker-erase').change(function () {
    $(this).blur();
    //HIDE TIPSY, OVERLAPS WITH MENU
    $(this).next().tipsy('hide');
    if ($(this).is(':checked')) {
        //UNCHECK OTHER BUTTONS
        if ($('#pdf-viewer-marker').is(':checked'))
            $('#pdf-viewer-marker').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-note').is(':checked'))
            $('#pdf-viewer-note').prop('checked', false).change().button('refresh');
        //REMOVE DRAG-SCROLL
        $('#pdf-viewer-img-div').unbind('mouseup mousedown mouseout').css('cursor', 'default');
        //ADD CURSOR CLASS
        $("#cursor > span").addClass('fa-eraser');
        //ADD MENU, BIND FUNCTIONS TO IT
        var menupos = $(this).position();
        $('#pdf-viewer-delete-menu').css('top', 32 + menupos.top + 'px').css('left', menupos.left + 'px').show();
        //DELETE SELECTED
        $('#pdf-viewer-delete-menu > div').eq(0).click(function () {
            $('#pdf-viewer-delete-menu').hide();
            $('#pdf-viewer-img-div').find('.annotation-container').selectable({
                stop: function (e, ui) {
                    var markers = '', notes = '';
                    $(".ui-selected", this).each(function () {
                        if ($(this).hasClass('marker-yellow')) {
                            markers += '&dbids[]=' + $(this).data('dbid');
                        } else if ($(this).hasClass('marker-note')) {
                            notes += '&dbids[]=' + $(this).data('dbid');
                        }
                    });
                    if (markers !== '') {
                        $.get('pdfcontroller.php?deleteannotation=1&type=yellowmarker&file=' + fileName + markers, function (answer) {
                            $.jGrowl('Markers deleted. Count: ' + answer + '.');
                            $('.ui-selected').filter('.marker-yellow').remove();
                        });
                    }
                    if (notes !== '') {
                        $.get('pdfcontroller.php?deleteannotation=1&type=annotation&file=' + fileName + notes, function (answer) {
                            $.jGrowl('Notes deleted. Count: ' + answer + '.');
                            $('.ui-selected').filter('.marker-note').remove();
                            $('.tipsy').remove();
                            if ($('#annotations-left').is(':visible'))
                                $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                        });
                    }
                }
            });
        });
        //DELETE ALL MARKERS
        $('#pdf-viewer-delete-menu > div').eq(1).click(function () {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all markers?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function () {
                            $.get('pdfcontroller.php?deleteannotation=1&type=yellowmarker&file=' + fileName, function (answer) {
                                $.jGrowl('Markers deleted. Count: ' + answer + '.');
                                $('.marker-yellow').remove();
                                $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
                            });
                            $(this).dialog('close');
                            $('#pdf-viewer-delete-menu').hide();
                        },
                        'Close': function () {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
        //DELETE NOTES
        $('#pdf-viewer-delete-menu > div').eq(2).click(function () {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all notes?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function () {
                            $.get('pdfcontroller.php?deleteannotation=1&type=annotation&file=' + fileName, function (answer) {
                                $.jGrowl('Notes deleted. Count: ' + answer + '.');
                                $('.marker-note').remove();
                                if ($('#annotations-left').is(':visible'))
                                    $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                                $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
                            });
                            $(this).dialog('close');
                            $('#pdf-viewer-delete-menu').hide();
                        },
                        'Close': function () {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
        //DELETE ALL ANNOATIONS
        $('#pdf-viewer-delete-menu > div').eq(3).click(function () {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all annotations?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function () {
                            $.get('pdfcontroller.php?deleteannotation=1&type=all&file=' + fileName, function (answer) {
                                $.jGrowl('Annotations deleted. Count: ' + answer + '.');
                                $('.marker').remove();
                                if ($('#annotations-left').is(':visible'))
                                    $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                                $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
                            });
                            $(this).dialog('close');
                            $('#pdf-viewer-delete-menu').hide();
                        },
                        'Close': function () {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
    } else {
        //HIDE MENU
        $('#pdf-viewer-delete-menu').hide();
        //ENABLE DRAG-SCROLL
        $('#pdf-viewer-img-div').clickNScroll().css('cursor', 'grab');
        //UNBIND SELECTABLE
        $selDivs = $('#pdf-viewer-img-div').find('.annotation-container');
        if ($selDivs.selectable('instance') !== undefined) {
            $selDivs.selectable('destroy');
        }
        //REMOVE CURSOR
        $("#cursor").hide().find('span').removeClass('fa-eraser');
        //UNBIND MENU
        $('#pdf-viewer-delete-menu > div').unbind();
    }
}).button({
    disabled: true
}).next().tipsy();
$('#confirm-container').dialog({
    autoOpen: false,
    modal: true
});
//OTHERS' ANNOTATIONS
$('#pdf-viewer-others-annotations').change(function () {
    $(this).blur();
    if ($(this).is(':checked')) {
        annotations.get('getpdfmarkers', true, function (answer) {
            $.each(answer, function (key, rows) {
                var markid = 'marker-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                $('<div class="marker marker-yellow-others" id="' + markid + '" data-dbid="' + rows.id + '"></div>')
                        .appendTo('#pdf-viewer-img-' + rows.page + ' > .annotation-container');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%').css('width', rows.width + '%');
            });
        });
        annotations.get('getpdfnotes', true, function (answer) {
            $.each(answer, function (key, rows) {
                var markid = 'note-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                $('<div class="marker marker-note-others" id="' + markid + '" data-dbid="' + rows.id
                        + '" data-annotation="<b>' + rows.username + ':</b><br>'
                        + escapeHtml(rows.annotation) + '">' + rows.id + '</div>')
                        .appendTo('#pdf-viewer-img-' + rows.page + ' > .annotation-container');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%');
            });
        });
    } else {
        $('.annotation-container .marker-yellow-others, .annotation-container .marker-note-others').remove();
    }
}).button({
    disabled: true
}).next().tipsy();
/**
 * Search button group.
 */
// Search text input.
$('#pdf-viewer-search').keydown(function (e) {
    if (e.which !== 13)
        return true;
    e.preventDefault();
    var st = $('#pdf-viewer-search').val();
    $('.pdfviewer-highlight').remove();
    if (st === '') {
        $('#pdf-viewer-clear').click();
        return false;
    }
    // Fetch results from server.
    overlay.start();
    $.getJSON('pdfcontroller.php', {
        'searchtextlayer': 1,
        'search_term': st,
        'file': fileName
    }, function (answer) {
        overlay.stop();
        // Clear previous searches.
        $('#search-results .search-result, #search-results .search-result-page').remove();
        if (jQuery.isEmptyObject(answer)) {
            $.jGrowl('No Hits.');
            return false;
        }
        if (answer['Error'] !== undefined) {
            errorHandling.display(answer['Error']);
            return false;
        }
        $('.highlight-container').show();
        // Assemble search results.
        var page = '';
        $.each(answer, function (key, row) {
            $('<div class="ui-corner-all pdfviewer-highlight" id="highlight-page-' + key + '"></div>')
                    .appendTo('#pdf-viewer-img-' + row.p + ' > .highlight-container');
            $('#highlight-page-' + key).css({
                'width': row.w + '%',
                'height': row.h + '%',
                'top': row.t + '%',
                'left': row.l + '%'
            });
            if (page !== row.p) {
                page = row.p;
                $('#search-results').append('<p class="search-result-page" style="font-weight:bold;padding:0 6px">Page ' + row.p + ':</p>');
            }
            $('#search-results').append('<p class="search-result" data-linksto="highlight-page-' + key + '">' + row.tx + '</p>');
        });
        // Open search result left panel.
        if (!$('#search-results').is(':visible')) {
            $('#search-results-button').button('enable').prop('checked', true).change().button('refresh');
        }
        // Bind search result clicks.
        searchresults.init();
        // Click on the first search result.
        $('#search-results').find('.search-result').eq(0).click();
    });
}).focus(function () {
    this.select();
}).tipsy();
// Clear search button.
$('#pdf-viewer-clear').click(function () {
    $(this).blur();
    // If results are open in left panel, close it.
    if ($('#navpane').is(':visible') && $('#search-results').is(':visible'))
        $('#search-results-button').prop('checked', false).trigger('change').button('refresh');
    // Clean up.
    $('#pdf-viewer-search').val('');
    $('.pdfviewer-highlight').remove();
    $('.highlight-container').hide();
    $('#search-results-button').button('disable');
    $('#search-results .search-result, #search-results .search-result-page').remove();
    $('#search-results').hide();
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Previous search result button.
$('#pdf-viewer-search-prev').click(function () {
    $(this).blur();
    $('.search-result.shown').prevAll('.search-result').eq(0).click();
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
// Next search result button.
$('#pdf-viewer-search-next').click(function () {
    $(this).blur();
    $('.search-result.shown').nextAll('.search-result').eq(0).click();
}).button().button('widget').click(function () {
    $(this).removeClass('ui-state-focus');
}).tipsy();
/**
 * Initial window set up.
 */
$(document).ready(function () {
    // Prevent button style blinking.
    $('#pdf-viewer-controls > .pdf-viewer-control-row').css('visibility', 'visible');
    // Page drag scrolling.
    $('#pdf-viewer-img-div').clickNScroll();
    // Initial window size.
    var toolbarH = $('#pdf-viewer-controls').outerHeight();
    if ($('#pdf-viewer-controls').is(':hidden'))
        toolbarH = 0;
    $('#pdf-viewer-div').height($('#pdf-viewer-div').parent().height() - toolbarH);
    // Set initial page number.
    scrollHandling.page = pg;
    // Initial zoom is window width.
    $('#size2').trigger('click');
    /*
     * Immmediately scroll into the requested page. Use native offsetTop,
     * because jQuery position() bugs out here.
     */
    var pgpos = $('#pdf-viewer-img-' + pg)[0].offsetTop - 4;
    $('#pdf-viewer-img-div').scrollTop(pgpos);
    // Load pages.
    scrollHandling.loadPage();
    // Everything is ready, bind scrollController.
    $('#pdf-viewer-img-div').on('scroll', scrollController);
    // Open left navigation.
    if (preview === false) {
        if (localStorage.getItem('pageprev-button') === 'On')
            $('#pageprev-button').prop('checked', true).change().button('refresh');
        if (localStorage.getItem('bookmarks-button') === 'On')
            $('#bookmarks-button').prop('checked', true).change().button('refresh');
        if (localStorage.getItem('notes-button') === 'On')
            $('#notes-button').prop('checked', true).change().button('refresh');
    }
    // Bind tooltips.
    $(document).tooltip(toolTipOptions);
    // Get links.
    if (!preview && toolbar) {
        getLinks.init();
    }
    // Search term.
    if (search_term !== '') {
        var e = jQuery.Event("keydown");
        e.which = 13; // Enter
        $('#pdf-viewer-search').val(search_term).trigger(e);
    }
});
