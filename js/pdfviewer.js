$.ajaxSetup({
    cache: false
});
function escapeHtml(unsafe) {
    return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
}
$('#pdf-viewer-img-div').clickNScroll();
$('#save').button().click(function() {
    $('#save-container').dialog({
        autoOpen: true,
        modal: true,
        buttons: {
            'Save': function() {
                var frm = $('#save-container form').serialize();
                window.location.assign('downloadpdf.php?mode=download&file=' + fileName + '&' + frm);
                $(this).dialog('destroy');
                return false;
            },
            'Close': function() {
                $(this).dialog('destroy');
            }
        },
        close: function() {
            $(this).dialog('destroy');
        }
    });
}).tipsy({
    gravity: 'nw'
});
$('#pdf-viewer-controls > .pdf-viewer-control-row').css('visibility', 'visible');
$('#pdf-viewer-copy-image').click(function() {
    var img = $('#pdf-viewer-img').css('background-image').match('http://.*png');
    $('#image-to-copy').attr('src', img);
    $('#image-src').val(img);
    $('#copy-image-container').dialog({
        autoOpen: true,
        modal: true,
        width: $(window).width() - 40,
        height: $(window).height() - 40,
        title: 'Select an area to copy and press the Copy button',
        buttons: {
            'Copy': function() {
                $('#copy-image-container form').submit();
            },
            'Close': function() {
                $.Jcrop('#image-to-copy').destroy();
                $('.jcrop-holder').remove();
                $(this).dialog('destroy');
            }
        },
        open: function() {
            $('#image-to-copy').Jcrop({
                onSelect: function(c) {
                    $('#x').val(c.x);
                    $('#y').val(c.y);
                    $('#w').val(c.w);
                    $('#h').val(c.h);
                }
            });
        },
        close: function() {
            $.Jcrop('#image-to-copy').destroy();
            $('.jcrop-holder').remove();
            $(this).dialog('destroy');
        }
    });
}).button().tipsy();
//INITIAL WINDOW SIZE
var toolbar = 73;
if ($('#pdf-viewer-controls').is(':hidden'))
    toolbar = 0;
$('#pdf-viewer-div').height($('#pdf-viewer-div').parent().height() - toolbar);
localStorage.setItem('zoom', 'w');
//WINDOW RESIZE
$(window).resize(function() {
    var toolbar = 73, zoom = localStorage.getItem('zoom');
    if ($('#pdf-viewer-controls').is(':hidden'))
        toolbar = 0;
    $('#pdf-viewer-div').height($('#pdf-viewer-div').parent().height() - toolbar);
    if (zoom === 'o') {
        $('#size1').click();
    } else if (zoom === 'w') {
        $('#size2').click();
    } else if (zoom === 'h') {
        $('#size3').click();
    }
});
function togglePanel() {
    var zoom = localStorage.getItem('zoom');
    if ($('#navpane').is(':visible')) {
        $('#navpane').hide();
    } else {
        $('#navpane').show();
    }
    if (zoom === 'o') {
        $('#size1').click();
    } else if (zoom === 'w') {
        $('#size2').click();
    } else if (zoom === 'h') {
        $('#size3').click();
    } else if (!isNaN(zoom)) {
        $('#zoom').slider("value", zoom);
    }
}
//ZOOM
$('#size1').click(function() {
    var iw = $('#pdf-viewer-img').data('imgw'),
            ih = $('#pdf-viewer-img').data('imgh');
    $('#pdf-viewer-img').css('width', iw).css('height', ih);
    $('#zoom').slider("value", 100);
    $('#zoom').next().text('100%');
    var pos = $('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width': iw,
        'height': ih,
        'left': Math.max(pos.left, 0)
    });
    localStorage.setItem('zoom', 'o');
}).button().tipsy();
$('#size2').click(function() {
    var parentw = $('#pdf-viewer-img-div').width(),
            imgw = $('#pdf-viewer-img').data('imgw'),
            imgh = $('#pdf-viewer-img').data('imgh'),
            iw = 0.98 * parentw,
            ih = imgh * iw / imgw,
            piw = 100 * iw / imgw;
    $('#pdf-viewer-img').css('width', iw).css('height', ih);
    $('#zoom').slider("value", piw);
    $('#zoom').next().text(Math.round(piw) + '%');
    var pos = $('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width': iw,
        'height': ih,
        'left': Math.max(pos.left, 0)
    });
    localStorage.setItem('zoom', 'w');
}).button().tipsy();
$('#size3').click(function() {
    var parenth = $('#pdf-viewer-img-div').height(),
            imgw = $('#pdf-viewer-img').data('imgw'),
            imgh = $('#pdf-viewer-img').data('imgh'),
            ih = 0.99 * parenth,
            iw = imgw * ih / imgh,
            piw = 100 * iw / imgw;
    $('#pdf-viewer-img-div').css('overflow', 'hidden');
    $('#pdf-viewer-img').css({
        'height': ih,
        'width': iw
    });
    $('#zoom').slider("value", piw);
    $('#zoom').next().text(Math.round(piw) + '%');
    var pos = $('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width': iw + 'px',
        'height': ih + 'px',
        'left': Math.max(pos.left, 0) + 'px'
    });
    $('#pdf-viewer-img-div').css('overflow', 'auto');
    localStorage.setItem('zoom', 'h');
}).button().tipsy();
$('#zoom').slider({
    min: 30,
    max: 150,
    value: 100,
    slide: function(e, ui) {
        var imgw = $('#pdf-viewer-img').data('imgw'),
                imgh = $('#pdf-viewer-img').data('imgh'),
                iw = ui.value * imgw / 100,
                ih = ui.value * imgh / 100;
        $('#pdf-viewer-img').css('width', iw).css('height', ih);
        $(this).next().text(Math.round(ui.value) + '%');
        var pos = $('#pdf-viewer-img').position();
        $('#highlight-container, #annotation-container').css({
            'width': iw + 'px',
            'height': ih + 'px',
            'left': Math.max(pos.left, 0) + 'px'
        });
        localStorage.setItem('zoom', ui.value);
    }
});
//OPEN FIRST PAGE AND THUMBS
$('body').data('lock', 1);
$('#pdf-viewer-img').data('pg', pg);
$.getJSON('viewpdf.php?renderpdf=1&file=' + fileName + '&page=' + pg, function(answer) {
    var imgw = answer[0], imgh = answer[1];
    $('body').data('lock', 0);
    if (isNaN(imgw)) {
        $.jGrowl(imgw, {
            theme: 'jgrowl-error'
        });
        return false;
    }
    $('#pdf-viewer-img').css('background-image', 'url("attachment.php?mode=inline&png=' + fileName + '.' + pg + '.png")')
            .data('pg', pg).data('imgw', imgw).data('imgh', imgh);
    if (navpanes) {
        $('#pageprev-button').prop('checked', true).change().button('refresh');
    }
    $('#control-page').val(pg);
    // set initial image width to 98% of the parent width
    $('#size2').click();
});
//LEFT NAVPANE
$('#pageprev-button').change(function() {
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
        togglePanel();
    }
    $('#thumbs').show();
    $('#bookmarks, #annotations-left, #search-results').hide();
    if ($('#thumbs').find('div').length === 0) {
        $.get('viewpdf.php?renderthumbs=1&file=' + fileName, function(answer) {
            var thumbs = '', i = 1;
            while (i <= totalPages) {
                thumbs = thumbs + '<div style="padding:1px 0 5px 0">Page ' + i + ':<br><img class="pdf-viewer-thumbs thumb-' + i + '" src="attachment.php?mode=inline&png=' + fileName + '.t' + i + '.png" alt="" data-title=""></div>';
                i++;
            }
            $('#thumbs').html(thumbs);
            var pg = $('#pdf-viewer-img').data('pg'), $thumb = $('#thumbs > div:eq(' + (pg - 1) + ')');
            $thumb.css('background-color', '#999fdd');
            $.getJSON('viewpdf.php?renderbookmarks=1&file=' + fileName, function(bookmarks) {
                if (bookmarks.length === 0) {
                    $('#bookmarks').html('<div style="padding:12px">No bookmarks.</div>');
                    return false;
                }
                $('#bookmarks').empty();
                $.each(bookmarks, function(key, rows) {
                    var ttl = '';
                    $('#bookmarks').append('<p class="bookmark" id="bookmark-' + key + '" data-page="' + rows.page + '">' + rows.title + '</p>');
                    $('#bookmark-' + key).css('padding-left', 6 * rows.level + 'px');
                    ttl = $('.pdf-viewer-thumbs:eq(' + (rows.page - 1) + ')').attr('data-title');
                    if (ttl === undefined)
                        ttl = '';
                    $('.pdf-viewer-thumbs:eq(' + (rows.page - 1) + ')').attr('data-title', ttl + '<div style="background-color:rgba(222,222,222,0.2);text-align:left;margin:6px 2px;line-height:1.2;padding:1px 4px">' + rows.title + '</div>');
                });
                $('#bookmarks .bookmark').click(function() {
                    $('.bookmark').css('background-color', '');
                    $(this).css('background-color', '#aaafe6');
                    if ($(this).data('page') !== $('#pdf-viewer-img').data('pg'))
                        fetch_page(fileName, $(this).data('page'));
                });
                $('.pdf-viewer-thumbs').tipsy({gravity: 'w', html: true, title: 'data-title'});
            });
        });
    }
}).button().next().tipsy({
    gravity: 'nw'
});
$('#bookmarks-button').change(function() {
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
        togglePanel();
    }
    $('#bookmarks').show();
    $('#thumbs, #annotations-left, #search-results').hide();
    if ($('#bookmarks').html() === '') {
        $('#bookmarks').html('<div style="padding:12px">Reading bookmarks.</div>');
        $.getJSON('viewpdf.php?renderbookmarks=1&file=' + fileName, function(bookmarks) {
            if (bookmarks.length === 0) {
                $('#bookmarks').html('<div style="padding:12px">No bookmarks.</div>');
                return false;
            }
            $('#bookmarks').empty();
            $.each(bookmarks, function(key, rows) {
                $('#bookmarks').append('<p class="bookmark" id="bookmark-' + key + '" data-page="' + rows.page + '">' + rows.title + '</p>');
                $('#bookmark-' + key).css('padding-left', 6 * rows.level + 'px');
            });
            $('#bookmarks .bookmark').click(function() {
                $('.bookmark').css('background-color', '');
                $(this).css('background-color', '#aaafe6');
                if ($(this).data('page') !== $('#pdf-viewer-img').data('pg'))
                    fetch_page(fileName, $(this).data('page'));
            });
        });
    }
}).button().next().tipsy();
$('#notes-button').change(function() {
    if ($('#pageprev-button').prop('checked') || $('#bookmarks-button').prop('checked') || $('#search-results-button').prop('checked')) {
        $('#pageprev-button, #bookmarks-button, #search-results-button').prop('checked', false).button('refresh');
    } else {
        togglePanel();
    }
    if ($(this).prop('checked')) {
        localStorage.setItem('pageprev-button', 'Off');
        localStorage.setItem('bookmarks-button', 'Off');
        localStorage.setItem('notes-button', 'On');
        $('#annotations-left').show();
        $('#bookmarks, #thumbs, #search-results').hide();
        var usr = '';
        if ($('#pdf-viewer-others-annotations').prop('checked') === true)
            usr = '&user=all';
        $.getJSON('annotate.php?fetch=1&type=annotation&filename=' + fileName + '&page=all' + usr, function(answer) {
            $('#annotations-left p').remove();
            if (answer.length === 0)
                $('#annotations-left').append('<p style="padding:0 6px">No notes.</p>');
            $.each(answer, function(key, rows) {
                var annot = rows.annotation, noteid = 'note-' + 10 * rows.top + '-' + 10 * rows.left;
                $('#annotations-left').append('<p class="annotation" id="annot-' + key + '" data-page="' + rows.page + '"><b>Page ' + rows.page + ', note ' + rows.id + ':</b><br> <span style="white-space:pre-wrap">' + annot + '</span></p>');
                $('#annot-' + key).data('noteid', noteid);
            });
            $('#annotations-left .annotation').click(function() {
                $('.annotation').css('background-color', '');
                $(this).css('background-color', '#aaafe6');
                if ($(this).data('page') !== $('#pdf-viewer-img').data('pg')) {
                    fetch_page(fileName, $(this).data('page'), function() {
                        if (!$('#pdf-viewer-annotations').is(':checked'))
                            $('#pdf-viewer-annotations').prop('checked', true).change().button('refresh');
                    });
                } else {
                    if (!$('#pdf-viewer-annotations').is(':checked'))
                        $('#pdf-viewer-annotations').prop('checked', true).change().button('refresh');
                }
            });
        });
        searchnotes.init();
    } else {
        localStorage.setItem('notes-button', 'Off');
    }

}).button().next().tipsy();
$('#print-notes').click(function() {
    if ($('#annotations-left').html() !== '') {
        $('.annotation').css('background-color', '');
        w = window.open('', '', 'width=800,height=400');
        w.document.write('<style type="text/css">@media print {#filter_notes {display:none}} @page {margin:0}</style>');
        w.document.write($('#annotations-left').html());
        w.print();
        //FOR IE
        w.document.close();
        //FOR OTHER BROWSERS
        w.close();
    }
}).button().tipsy();
$('#search-results-button').change(function() {
    if ($('#pageprev-button').prop('checked') || $('#bookmarks-button').prop('checked') || $('#notes-button').prop('checked')) {
        $('#pageprev-button, #bookmarks-button, #notes-button').prop('checked', false).button('refresh');
    } else {
        togglePanel();
    }
    $('#search-results').show();
    $('#annotations-left, #bookmarks, #thumbs').hide();
}).button({
    disabled: true
}).next().tipsy();
//PAGE NAVIGATION
function fetch_page(file, pg, f) {
    $('body').data('lock', 1);
    $('#pdf-viewer-img').data('pg', pg);
    $.getJSON('viewpdf.php?renderpdf=1&file=' + file + '&page=' + pg, function(answer) {
        var imgw = answer[0], imgh = answer[1];
        $('#pdf-viewer-img-div').animate({scrollTop: 0, scrollLeft: 0}, 200);
        $('#pdf-viewer-img').css('background-image', 'url("attachment.php?mode=inline&png=' + file + '.' + pg + '.png")');
        $('#control-page').val(pg);
        $('.pdfviewer-highlight').hide();
        $('.highlight-page-' + pg).show();
        $('body').data('lock', 0);
        $('#thumbs > div').css('background-color', '');
        if ($('#thumbs > div').length > 0) {
            var $thumb = $('#thumbs > div:eq(' + (pg - 1) + ')'),
                    thtop = $thumb.offset().top,
                    thbottom = thtop + $thumb.height(),
                    partop = $('#navpane').offset().top,
                    parbottom = partop + $('#navpane').height();
            $thumb.css('background-color', '#999fdd');
            if ($('#thumbs').is(':visible') && (thtop - partop < 0 || parbottom - thbottom < 0)) {
                $('#navpane').animate({
                    scrollTop: $('#navpane').scrollTop() + thtop - ((parbottom - partop) / 2)
                }, 200);
            }
        }
        if ($('#pdf-viewer-annotations').prop('checked') === true) {
            var firstpressed = 0, otherspressed = false,
                    markerchecked = $('#pdf-viewer-marker').prop('checked'),
                    notechecked = $('#pdf-viewer-note').prop('checked'),
                    othersannotations = $('#pdf-viewer-others-annotations').prop('checked');
            $('#annotation-container').empty().unbind();
            if (markerchecked === true)
                firstpressed = 1;
            if (notechecked === true)
                firstpressed = 2;
            if (othersannotations === true)
                otherspressed = true;
            $('#pdf-viewer-annotations').trigger('change', [firstpressed, otherspressed]);
        }
        if ($('#pdf-viewer-copy-text').prop('checked') === true)
            $('#pdf-viewer-copy-text').trigger('change');
        if (typeof f === 'function')
            f();
    });
    if ($('#pdf-viewer-marker-erase').is(':checked'))
        $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
}
// PAGE NAVIGATION
$('#control-first').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    if ($('#pdf-viewer-img').data('pg') === 1)
        return false;
    fetch_page(fileName, 1);
}).button().tipsy();
$('#control-prev').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    var pg = $('#pdf-viewer-img').data('pg');
    if (pg === 1)
        return false;
    pg = pg - 1;
    pg = Math.max(pg, 1);
    fetch_page(fileName, pg);
}).button().tipsy();
$('#control-next').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    var pg = $('#pdf-viewer-img').data('pg');
    if (pg === totalPages)
        return false;
    pg = pg + 1;
    pg = Math.min(pg, totalPages);
    fetch_page(fileName, pg);
}).button().tipsy();
$('#control-last').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    if ($('#pdf-viewer-img').data('pg') === totalPages)
        return false;
    fetch_page(fileName, totalPages);
}).button().tipsy();
$('#control-page').keydown(function(e) {
    if ($('body').data('lock') === 1)
        return false;
    if (e.which !== 13)
        return true;
    var pg = parseInt($(this).val());
    if (isNaN(pg) || pg < 1 || pg > totalPages) {
        $(this).val('1');
        pg = 1;
    }
    fetch_page(fileName, pg);
    return false;
}).focus(function() {
    this.select();
});
$('#thumbs').click(function(e) {
    var $t = $(e.target), pg = $('#thumbs img').index($t) + 1, currpg = $('#pdf-viewer-img').data('pg');
    if (!$t.is('img') || pg === currpg)
        return false;
    if ($('body').data('lock') === 1)
        return false;
    fetch_page(fileName, pg);
});
//SEARCH
$('#pdf-viewer-search').keydown(function(e) {
    if (e.which !== 13)
        return true;
    e.preventDefault();
    var st = $('#pdf-viewer-search').val();
    $('.pdfviewer-highlight').remove();
    if (st === '') {
        $('#pdf-viewer-clear').click();
        return false;
    }
    $('body').data('lock', 1);
    $.getJSON('searchpdf.php', {
        'search_term': st,
        'file': fileName
    }, function(answer) {
        if (jQuery.isEmptyObject(answer)) {
            $.jGrowl('No Hits.');
            $('body').data('lock', 0);
            return false;
        }
        if (answer['Error'] !== undefined) {
            $.jGrowl(answer['Error'], {
                theme: 'jgrowl-error'
            });
            $('body').data('lock', 0);
            return false;
        }
        $('#search-results .search-result, #search-results .search-result-page').remove();
        var i = 0, positions = new Array(), pgs = [], w = $('#pdf-viewer-img').width(), h = $('#pdf-viewer-img').height(), pos = $('#pdf-viewer-img').position();
        $('#highlight-container, #annotation-container').css('left', Math.max(pos.left, 0)).width(w).height(h);
        $.each(answer, function(key, rows) {
            pgs[i] = key;
            i = i + 1;
            positions[key] = new Array();
            $('#search-results').append('<p class="search-result-page" style="font-weight:bold;padding:0 6px">Page ' + key + ':</p>');
            $.each(rows, function(key2, row) {
                $('#highlight-container').append('<div class="ui-corner-all pdfviewer-highlight highlight-page-' + key + '" id="highlight-page-' + key + '-row-' + key2 + '">&nbsp;</div>');
                $('#highlight-page-' + key + '-row-' + key2).css({
                    'width': row.width + '%',
                    'height': row.height + '%',
                    'top': row.top + '%',
                    'left': row.left + '%'
                });
                positions[key][key2] = row.top;
                $('#search-results').append('<p class="search-result" data-linksto="highlight-page-' + key + '-row-' + key2 + '">' + row.text + '</p>');
            });
        });
        if ($('#search-results').is(':visible')) {
            $('#search-results-button').prop('checked', false).change().prop('checked', true).change();
        } else {
            $('#search-results-button').button('enable').prop('checked', true).change().button('refresh');
        }
        var openpg = Math.min.apply(Math, pgs);
        fetch_page(fileName, openpg, function() {
            searchresults.init();
            $("#search-results .search-result").eq(0).click();
        });
    });
}).focus(function() {
    this.select();
}).tipsy();
$('#pdf-viewer-clear').click(function() {
    $('#pdf-viewer-search').val('');
    $('.pdfviewer-highlight').remove();
    $('#highlight-container').css({
        'width': 0,
        'height': 0,
        'left': 0
    });
    $('#search-results-button').button('disable');
    $('#search-results .search-result, #search-results .search-result-page').remove();
    $('#search-results').hide();
    if ($('#navpane').is(':visible'))
        $('#pageprev-button').click();
}).button().tipsy();
$('#pdf-viewer-search-prev').click(function() {
    $('.search-result.shown').prevAll('.search-result').eq(0).click();
}).button().tipsy();
$('#pdf-viewer-search-next').click(function() {
    $('.search-result.shown').nextAll('.search-result').eq(0).click();
}).button().tipsy();
//ANNOTATIONS
$('#pdf-viewer-annotations').change(function(e, firstpressed, otherspressed) {
    if ($(this).is(':checked')) {
        if ($('#pdf-viewer-copy-text').prop('checked') === true)
            $('#pdf-viewer-copy-text').trigger('click');
        var iw = $('#pdf-viewer-img').width(), h = $('#pdf-viewer-img').height(), pos = $('#pdf-viewer-img').position();
        $('#annotation-container').show().css({
            'width': iw,
            'height': h,
            'left': Math.max(pos.left, 0)
        });
        $('#pdf-viewer-marker,#pdf-viewer-note,#pdf-viewer-marker-erase,#pdf-viewer-others-annotations').button('enable');
        $.getJSON('annotate.php?fetch=1&type=yellowmarker&filename=' + fileName + '&page=' + $('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid = 'marker-' + 10 * rows.top + '-' + 10 * rows.left;
                $('#annotation-container').append('<div class="marker marker-yellow" id="' + markid + '" data-dbid="' + rows.id + '"></div>');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%').css('width', rows.width + '%');
            });
        });
        $.getJSON('annotate.php?fetch=1&type=annotation&filename=' + fileName + '&page=' + $('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid = 'note-' + 10 * rows.top + '-' + 10 * rows.left;
                $('#annotation-container').append('<div class="marker marker-note" id="' + markid + '" data-dbid="' + rows.id + '" data-annotation="' + escapeHtml(rows.annotation) + '">' + rows.id + '</div>');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%');
            });
            $('.marker-note').tipsy({
                title: function() {
                    return '<div style="text-align:left;white-space:pre-wrap;word-wrap:break-word;margin:4px"><b>Note:</b><br>'
                            + this.getAttribute('data-annotation')
                            + '</div>';
                },
                gravity: $.fn.tipsy.autoNS,
                html: true
            });
            if (firstpressed === 1)
                $('#pdf-viewer-marker').change();
            if (firstpressed === 2)
                $('#pdf-viewer-note').change();
            if (otherspressed === true)
                $('#pdf-viewer-others-annotations').change();
        });
    } else {
        $('#annotation-container').empty().hide();
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
}).button().next().tipsy();
//YELLOW MARKER
$('#pdf-viewer-marker').change(function() {
    if ($(this).is(':checked')) {
        if ($('#pdf-viewer-note').is(':checked'))
            $('#pdf-viewer-note').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked'))
            $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
        $('#pdf-viewer-img-div').unbind().css('cursor', 'text');
        $('#pdf-viewer-img-div').css('cursor', 'text');
        $('#annotation-container').mouseenter(function() {
            $("#cursor > span").addClass('fa-pencil').parent().show();
        }).mouseleave(function() {
            $("#cursor > span").removeClass('fa-pencil').parent().hide();
        }).mousedown(function(e) {
            var markstposX = e.pageX,
                    markstposY = e.pageY,
                    prntpos = $(this).offset(),
                    posx = Math.round(1000 * (e.pageX - prntpos.left) / $(this).width()) / 10,
                    posy = Math.round(1000 * (e.pageY - prntpos.top) / $(this).height() - 5) / 10,
                    markid = 'marker-' + 10 * posy + '-' + 10 * posx;
            if ($('#' + markid).length === 1)
                return false;
            $(this).data('marker', {
                'markid': markid,
                'markstposX': markstposX,
                'markstposY': markstposY
            });
            $('<div class="marker marker-yellow" id="' + markid + '" data-dbid=""></div>').appendTo(this);
            $('#' + markid).css('top', posy + '%').css('left', posx + '%');
        }).mousemove(function(e) {
            posx = 16 + e.pageX,
                    posy = 16 + e.pageY;
            $('#cursor').css('top', posy + 'px').css('left', posx + 'px');
            if (!$(this).data('marker'))
                return false;
            var markstposX = $(this).data('marker').markstposX,
                    markw = e.pageX - markstposX,
                    markid = $(this).data('marker').markid;
            $('#' + markid).width(markw);
        }).mouseup(function(e) {
            if (!$(this).data('marker'))
                return false;
            var prntpos = $(this).offset(),
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
            $.get('annotate.php?save=1&type=yellowmarker&filename=' + fileName + '&page=' + $('#pdf-viewer-img').data('pg') + '&top=' + posy + '&left=' + posx + '&width=' + markw, function(answer) {
                $('#' + markid).attr('data-dbid', answer);
                if (answer === '') {
                    $.jGrowl('Error during saving the mark!');
                    $('#' + markid).remove();
                }
            });
        });
    } else {
        $('#pdf-viewer-img-div').clickNScroll();
        $('#pdf-viewer-img-div').css('cursor', 'pointer');
        $('#annotation-container').unbind();
    }
}).button({
    disabled: true
}).next().tipsy();
//PINNED NOTES
$('#pdf-viewer-note').change(function() {
    if ($(this).is(':checked')) {
        $('.marker-note, .marker-note-others').unbind('mouseenter mouseleave');
        if ($('#pdf-viewer-marker').is(':checked'))
            $('#pdf-viewer-marker').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked'))
            $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
        $('#pdf-viewer-img-div').unbind();
        $('#pdf-viewer-img-div').css('cursor', 'default');
        $('#annotation-container').mouseenter(function() {
            $("#cursor > span").addClass('fa-thumb-tack').parent().show();
        }).mouseleave(function() {
            $("#cursor > span").removeClass('fa-thumb-tack').parent().hide();
        }).mousemove(function(e) {
            posx = 16 + e.pageX,
                    posy = 16 + e.pageY;
            $('#cursor').css('top', posy + 'px').css('left', posx + 'px');
        }).click(function(e) {
            if ($(e.target).hasClass('marker-note')) {
                var annotation = '', markid = $(e.target).attr('id'), dbid = $(e.target).data('dbid');
                if ($('#jGrowl').find('#ta-' + markid).length === 1)
                    return false;
                if (dbid !== '')
                    annotation = $('#' + markid).data('annotation');
                $("div.jGrowl-close").click();
                var clstem = '<i class="fa fa-save ui-state-highlight" style="padding:0 2px"></i>';
                $.jGrowl('<textarea class="note-ta" id="ta-' + markid + '">' + annotation + '</textarea>',
                        {
                            header: 'Edit the note:',
                            sticky: true,
                            speed: 0,
                            closeTemplate: clstem,
                            closerTemplate: '<div>[ save all ]</div>',
                            close: function(el) {
                                var $e = $(el), txt = $e.find('textarea').val();
                                $.get('annotate.php', 'edit=1&dbid=' + dbid + '&annotation=' + encodeURIComponent(txt), function() {
                                    $('#' + markid).attr('data-annotation', escapeHtml(txt)).data('annotation', txt);
                                    if ($('#annotations-left').is(':visible'))
                                        $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                                });
                            }
                        });
            } else {
                var prntpos = $(this).offset(),
                        posx = Math.round(1000 * (e.pageX - prntpos.left) / $(this).width() - 35) / 10,
                        posy = Math.round(1000 * (e.pageY - prntpos.top) / $(this).height() - 25) / 10,
                        markid = 'note-' + 10 * posy + '-' + 10 * posx;
                if ($('#' + markid).length === 1)
                    return false;
                $('<div class="marker marker-note" id="' + markid + '"></div>').appendTo(this);
                $('#' + markid).css('top', posy + '%').css('left', posx + '%');
                $.get('annotate.php', 'save=1&type=annotation&filename=' + fileName + '&page=' + $('#pdf-viewer-img').data('pg') + '&top=' + posy + '&left=' + posx + '&annotation=',
                        function(answer) {
                            $('#' + markid).attr('data-dbid', answer).attr('data-annotation', '').data('annotation', '').text(answer);
                            if ($('#annotations-left').is(':visible'))
                                $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                        });
                var clstem = '<i class="fa fa-save ui-state-highlight" style="padding:0 2px"></i>';
                $("div.jGrowl-close").click();
                $.jGrowl('<textarea class="note-ta" name="note-ta" id="ta-' + markid + '"></textarea>',
                        {
                            header: 'Add new note:',
                            sticky: true,
                            speed: 0,
                            closeTemplate: clstem,
                            closerTemplate: '<div>[ save all ]</div>',
                            close: function(el) {
                                var $e = $(el), txt = $e.find('textarea').val(), dbid = $('#' + markid).data('dbid');
                                $.get('annotate.php', 'edit=1&dbid=' + dbid + '&annotation=' + encodeURIComponent(txt), function() {
                                    $('#' + markid).attr('data-annotation', escapeHtml(txt)).data('annotation', txt);
                                    if ($('#annotations-left').is(':visible'))
                                        $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                                });
                            }
                        });
            }
        });
    } else {
        $('#pdf-viewer-img-div').clickNScroll();
        $('#pdf-viewer-img-div').css('cursor', 'pointer');
        $('#annotation-container').unbind();
        $("div.jGrowl-close").click();
        $('.marker-note, .marker-note-others').tipsy({
            title: function() {
                return '<div style="text-align:left;white-space:pre-wrap;word-wrap:break-word;margin:4px"><b>Note:</b><br>'
                        + this.getAttribute('data-annotation')
                        + '</div>';
            },
            gravity: $.fn.tipsy.autoNS,
            html: true
        });
    }
}).button({
    disabled: true
}).next().tipsy();
//ERASE ANNOTATIONS
$('#pdf-viewer-marker-erase').change(function() {
    $(this).next().tipsy('hide');
    if ($(this).is(':checked')) {
        if ($('#pdf-viewer-marker').is(':checked'))
            $('#pdf-viewer-marker').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-note').is(':checked'))
            $('#pdf-viewer-note').prop('checked', false).change().button('refresh');
        $('#pdf-viewer-img-div').unbind().css('cursor', 'default');
        $('#pdf-viewer-img-div').css('cursor', 'default');
        var menupos = $(this).position();
        $('#pdf-viewer-delete-menu').css('top', 32 + menupos.top + 'px').css('left', menupos.left + 'px').show();
        $('#pdf-viewer-delete-menu > div').eq(0).click(function() {
            $('#pdf-viewer-delete-menu').hide();
            $('#annotation-container').mouseenter(function() {
                $("#cursor > span").addClass('fa-eraser').parent().show();
            }).mouseleave(function() {
                $("#cursor > span").removeClass('fa-eraser').parent().hide();
            }).mousemove(function(e) {
                posx = 16 + e.pageX,
                        posy = 16 + e.pageY;
                $('#cursor').css('top', posy + 'px').css('left', posx + 'px');
            }).on('selection', function(e) {
                var $this = '', type = '';
                if ($(e.target).hasClass('marker') && !$(e.target).hasClass('marker-yellow-others') && !$(e.target).hasClass('marker-note-others')) {
                    $this = $(e.target);
                } else {
                    return false;
                }
                if ($this.hasClass('marker-yellow'))
                    type = 'yellowmarker';
                if ($this.hasClass('marker-note')) {
                    type = 'annotation';
                    $this.tipsy('disable');
                }
                $.get('annotate.php?delete=1&dbid=' + $this.data('dbid') + '&type=' + type, function(answer) {
                    if (answer === '') {
                        $.jGrowl('Error during deleting the mark!');
                    } else {
                        $("div.jGrowl-close").click();
                        $this.remove();
                        $('.tipsy').remove();
                        if ($('#annotations-left').is(':visible'))
                            $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                    }
                });
            }).selectable({
                selecting: function(e, ui) {
                    $(ui.selecting).trigger('selection');
                }
            });
        });
        $('#pdf-viewer-delete-menu > div').eq(1).click(function() {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all markers?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function() {
                            $.get('annotate.php?delete=all&type=yellowmarker&filename=' + fileName, function(answer) {
                                if (answer === 'OK') {
                                    $("div.jGrowl-close").click();
                                    $('.marker-yellow').remove();
                                    $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
                                } else {
                                    $.jGrowl('Error during deleting marks! ' + answer);
                                }
                            });
                            $(this).dialog('close');
                            $('#pdf-viewer-delete-menu').hide();
                        },
                        'Close': function() {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
        $('#pdf-viewer-delete-menu > div').eq(2).click(function() {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all notes?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function() {
                            $.get('annotate.php?delete=all&type=annotation&filename=' + fileName, function(answer) {
                                if (answer === 'OK') {
                                    $("div.jGrowl-close").click();
                                    $('.marker-note').remove();
                                    if ($('#annotations-left').is(':visible'))
                                        $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                                    $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
                                } else {
                                    $.jGrowl('Error during deleting notes! ' + answer);
                                }
                            });
                            $(this).dialog('close');
                            $('#pdf-viewer-delete-menu').hide();
                        },
                        'Close': function() {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
        $('#pdf-viewer-delete-menu > div').eq(3).click(function() {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all annotations?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function() {
                            $.get('annotate.php?delete=all&type=all&filename=' + fileName, function(answer) {
                                if (answer === 'OK') {
                                    $("div.jGrowl-close").click();
                                    $('.marker').remove();
                                    if ($('#annotations-left').is(':visible'))
                                        $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                                    $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
                                } else {
                                    $.jGrowl('Error during deleting annotations! ' + answer);
                                }
                            });
                            $(this).dialog('close');
                            $('#pdf-viewer-delete-menu').hide();
                        },
                        'Close': function() {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
    } else {
        $('#pdf-viewer-delete-menu').hide();
        $('#pdf-viewer-img-div').clickNScroll();
        $('#pdf-viewer-img-div').css('cursor', 'pointer');
        if ($('#annotation-container').hasClass('ui-selectable'))
            $('#annotation-container').selectable('destroy');
        $('#annotation-container').unbind();
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
$('#pdf-viewer-others-annotations').change(function() {
    if ($(this).is(':checked')) {
        $.getJSON('annotate.php?fetchothers=1&type=yellowmarker&filename=' + fileName + '&page=' + $('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid = 'marker-' + 10 * rows.top + '-' + 10 * rows.left;
                $('#annotation-container').append('<div class="marker marker-yellow-others" id="' + markid + '" data-dbid="' + rows.id + '"></div>');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%').css('width', rows.width + '%').attr('title', rows.user).tipsy();
            });
        });
        $.getJSON('annotate.php?fetchothers=1&type=annotation&filename=' + fileName + '&page=' + $('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid = 'note-' + 10 * rows.top + '-' + 10 * rows.left;
                $('#annotation-container').append('<div class="marker marker-note-others" id="' + markid + '" data-dbid="' + rows.id + '" data-annotation="' + escapeHtml(rows.annotation) + '">' + rows.id + '</div>');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%').attr('title', rows.user);
            });
            if ($('#pdf-viewer-note').prop('checked') === false) {
                $('.marker-note-others').tipsy({
                    title: function() {
                        return '<div style="text-align:left;white-space:pre-wrap;word-wrap:break-word;margin:4px"><b>Note by '
                                + this.getAttribute('original-title') + ':</b><br>'
                                + this.getAttribute('data-annotation')
                                + '</div>';
                    },
                    gravity: $.fn.tipsy.autoNS,
                    html: true
                });
            }
        });
    } else {
        $('#annotation-container .marker-yellow-others, #annotation-container .marker-note-others').remove();
    }
}).button({
    disabled: true
}).next().tipsy();
//SEARCH IN NOTES
var searchnotes = {
    init: function() {
        $("#filter_notes").keyup(function() {
            var str = $(this).val(), $container = $('#annotations-left > p');
            if (str !== '') {
                qstr = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(qstr, 'i');
                $container.hide().filter(function() {
                    return re.test($(this).children('span').text());
                }).show();
                var re2 = new RegExp('\(' + qstr + '\)', 'gi');
                $container.each(function() {
                    if ($(this).is(':visible')) {
                        newstr = $(this).children('span').text().replace(re2, '<span style="background-color:#eea">$1</span>');
                        $(this).children('span').html(newstr);
                    }
                });
            } else {
                $container.show();
                $container.each(function() {
                    newstr = $(this).children('span').text();
                    $(this).children('span').text(newstr);
                });
            }
        }).focus(function() {
            $(this).val('');
            $('#annotations-left p').show();
            $('#annotations-left p').each(function() {
                newstr = $(this).children('span').text();
                $(this).children('span').text(newstr);
            });
        });
    }
};
$(".select_span").unbind().click(function(e) {
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
//SEARCH RESULTS CLICK
var searchresults = {
    init: function() {
        function clickResult($t, $target) {
            $('#highlight-container .pdfviewer-highlight')
                    .css('box-shadow', '').css('-webkit-box-shadow', '');
            $target.css('box-shadow', '0 0 3px 3px #666')
                    .css('-webkit-box-shadow', '0 0 3px 3px #666');
            $('#search-results .search-result').removeClass('shown').css('background-color', '');
            $t.addClass('shown').css('background-color', '#aaafe6');
            var pos = $target.position();
            $('#pdf-viewer-img-div').stop(true).animate({
                scrollTop: -100 + pos.top,
                scrollLeft: -100 + pos.left
            }, 200);
            var off = $t.offset(), curr = $('#navpane').scrollTop(),
                    bottom = $(window).height() - off.top + $t.height();
            if (off.top < 100)
                $('#navpane').animate({
                    scrollTop: curr - $(window).height() + 200
                }, 1000);
            if (bottom < 100)
                $('#navpane').animate({
                    scrollTop: curr + $(window).height() - 200
                }, 1000);
        }
        $("#search-results .search-result").click(function() {
            var $t = $(this), $target = $('#' + $(this).data('linksto')),
                    targetarr = $(this).data('linksto').split('-'),
                    targetpg = 1 * targetarr[2], pg = $('#pdf-viewer-img').data('pg');
            if (pg !== targetpg) {
                fetch_page(fileName, targetpg, function() {
                    clickResult($t, $target);
                });
            } else {
                clickResult($t, $target);
            }
        });
    }
};
// COPY TEXT
$('#pdf-viewer-copy-text').change(function() {
    if ($(this).is(':checked')) {
        if ($('#pdf-viewer-annotations').prop('checked') === true)
            $('#pdf-viewer-annotations').prop('checked', false).trigger('change').button("refresh");
        var pg = $('#pdf-viewer-img').data('pg');
        $.get('pdfhtml.php?file=' + fileName + '&page=' + pg, function(answer) {
            if (answer.substring(0, 5) === 'Error') {
                $.jGrowl(answer, {theme: 'jgrowl-error'});
                $('#pdf-viewer-copy-text').prop('checked', false).button('refresh');
                return false;
            }
            $('#pdf-viewer-img-div').unbind();
            var iw = $('#pdf-viewer-img').width(), h = $('#pdf-viewer-img').height(), pos = $('#pdf-viewer-img').position();
            $('#annotation-container').show().css({
                'cursor': 'default',
                'width': iw,
                'height': h,
                'left': Math.max(pos.left, 0)
            });
            $('#annotation-container').html(answer).show().selectable({
                distance: 10,
                stop: function() {
                    var txt = '';
                    $("#annotation-container").find(".ui-selected").each(function() {
                        txt = txt + $(this).data('text') + ' ';
                    });
                    txt = txt.replace(/(- )/g, '');
                    if (txt === '')
                        return false;
                    $('#copy-text-container').html('<textarea style="width:99%;height:98%">' + txt + '</textarea>').dialog({
                        autoOpen: true,
                        modal: true,
                        width: 640,
                        height: 480,
                        title: 'Press Ctrl+C to copy the text to clipboard.',
                        buttons: {
                            'Close': function() {
                                $(this).dialog('destroy');
                            }
                        },
                        open: function() {
                            $('#copy-text-container > textarea').select();
                        },
                        close: function() {
                            $(this).dialog('destroy');
                        }
                    });
                }
            });
        });
    } else {
        $('#annotation-container').empty().css('cursor', 'inherit').hide().selectable('destroy');
        $('#pdf-viewer-img-div').clickNScroll();
    }
}).button().next().tipsy();
//HOTKEYS
$(document).bind('keydown', 'd', function() {
    $('#control-next').click();
}).bind('keydown', 'e', function() {
    $('#control-prev').click();
});
//GET SETTINGS FROM LOCAL STORAGE
if (preview === false) {
    if (localStorage.getItem('pageprev-button') === 'On')
        $('#pageprev-button').prop('checked', true).change().button('refresh');
    if (localStorage.getItem('bookmarks-button') === 'On')
        $('#bookmarks-button').prop('checked', true).change().button('refresh');
    if (localStorage.getItem('notes-button') === 'On')
        $('#notes-button').prop('checked', true).change().button('refresh');
}
