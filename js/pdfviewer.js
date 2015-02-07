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
//global overlay
var timeId = '';
var dooverlay = function() {
    if ($('#overlay').length === 1)
        return false;
    $('body').append('<div id="overlay" class="ui-widget-overlay" style="z-index:10000;cursor:wait;width:100%;height:' + $(document).height() + 'px">&nbsp;</div>');
    $('#overlay').html('<img src="img/ajaxloader2.gif" alt="" style="margin-left:48%;margin-top:' + (-32 + 0.5 * $(document).height()) + 'px">');
};
var clearoverlay = function() {
    clearTimeout(timeId);
    $('#overlay').remove();
};
//PREVENT BUTTON BLINKING
$('#pdf-viewer-controls > .pdf-viewer-control-row').css('visibility', 'visible');
//PAGE PANNING
$('#pdf-viewer-img-div').clickNScroll();
//DOWNLOAD PDF
$('#save').button().click(function() {
    $('#save-container').dialog({
        autoOpen: true,
        modal: true,
        buttons: {
            'Download': function() {
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
//COPY IMAGE
$('#pdf-viewer-copy-image').click(function() {
    var page = $('#pdf-viewer-img-div').data('pg'),
            img = $('#pdf-viewer-img-' + page).css('background-image').match('http://.*png');
    $('#image-to-copy').attr('src', img);
    $('#image-src').val(img);
    $('#copy-image-container').dialog({
        autoOpen: true,
        modal: true,
        width: $(window).width() - 40,
        height: $(window).height() - 40,
        title: 'Select an area to copy, then click "Save to Files" or "Download"',
        buttons: {
            'Save to Files': function() {
                if($('#x').val() === '') return false;
                $('#copy-image-mode').val('save');
                $('#copy-image-container form').ajaxSubmit(function(answer){
                    $.jGrowl(answer);
                });
            },
            'Download': function() {
                if($('#x').val() === '') return false;
                $('#copy-image-mode').val('download');
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
//ZOOM
$('#size1').click(function() {
    var page = pg;
    if ($('#pdf-viewer-img-div').data('pg'))
        page = $('#pdf-viewer-img-div').data('pg');
    var imgtop = $('#pdf-viewer-img-' + page).position().top;
    $('.pdf-viewer-img').each(function() {
        var $t = $(this), iw = $t.data('imgw'), ih = $t.data('imgh');
        if (iw === undefined)
            iw = $('#pdf-viewer-img-' + page).data('imgw');
        if (ih === undefined)
            ih = $('#pdf-viewer-img-' + page).data('imgh');
        $t.css('width', iw).css('height', ih);
    });
    $('#zoom').slider("value", 100);
    $('#zoom').next().text('100%');
    localStorage.setItem('zoom', 'o');
    //KEEP ZOOMED PAGE IN THE SAME VERTICAL POSITION
    var imgtop2 = $('#pdf-viewer-img-' + page).position().top;
    $('#pdf-viewer-img-div').scrollTop($('#pdf-viewer-img-div').scrollTop() + imgtop2 - imgtop);
}).button().tipsy();
$('#size2').click(function() {
    var piw, page = pg, parentw = $('#pdf-viewer-img-div').width();
    if ($('#pdf-viewer-img-div').data('pg'))
        page = $('#pdf-viewer-img-div').data('pg');
    var imgtop = $('#pdf-viewer-img-' + page).position().top;
    $('.pdf-viewer-img').each(function(i) {
        var $t = $(this), imgw = $t.data('imgw'), imgh = $t.data('imgh');
        if (imgw === undefined)
            imgw = $('#pdf-viewer-img-' + page).data('imgw');
        if (imgh === undefined)
            imgh = $('#pdf-viewer-img-' + page).data('imgh');
        var iw = -30 + parentw, ih = imgh * iw / imgw;
        piw = 100 * iw / imgw;
        $t.css('width', iw).css('height', ih);
    });
    $('#zoom').slider("value", piw);
    $('#zoom').next().text(Math.round(piw) + '%');
    localStorage.setItem('zoom', 'w');
    //KEEP ZOOMED PAGE IN THE SAME VERTICAL POSITION
    var imgtop2 = $('#pdf-viewer-img-' + page).position().top;
    $('#pdf-viewer-img-div').scrollTop($('#pdf-viewer-img-div').scrollTop() + imgtop2 - imgtop);
}).button().tipsy();
$('#zoom').slider({
    min: 50,
    max: 150,
    value: 100,
    slide: function(e, ui) {
        var page = $('#pdf-viewer-img-div').data('pg'),
                imgtop = $('#pdf-viewer-img-' + page).position().top;
        $('.pdf-viewer-img').each(function() {
            var $t = $(this), imgw = $t.data('imgw'), imgh = $t.data('imgh');
            if (imgw === undefined)
                imgw = $('#pdf-viewer-img-' + page).data('imgw');
            if (imgh === undefined)
                imgh = $('#pdf-viewer-img-' + page).data('imgh');
            var iw = ui.value * imgw / 100, ih = ui.value * imgh / 100;
            $t.css('width', iw).css('height', ih);
        });
        $(this).next().text(Math.round(ui.value) + '%');
        localStorage.setItem('zoom', ui.value);
        //KEEP ZOOMED PAGE IN THE SAME VERTICAL POSITION
        var imgtop2 = $('#pdf-viewer-img-' + page).position().top;
        $('#pdf-viewer-img-div').scrollTop($('#pdf-viewer-img-div').scrollTop() + imgtop2 - imgtop);
        //WHEN SECOND PAGE POPS IN ON THE RIGHT
        if (imgtop2 - imgtop === 0)
            $('#pdf-viewer-img-div').trigger('scroll');
    }
});
//INITIAL WINDOW SIZE
var toolbar = 73;
if ($('#pdf-viewer-controls').is(':hidden'))
    toolbar = 0;
$('#pdf-viewer-div').height($('#pdf-viewer-div').parent().height() - toolbar);
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
    } else {
        $('#pdf-viewer-img-div').trigger('scroll');
    }
});
function togglePanel() {
    var zoom = localStorage.getItem('zoom');
    if ($('#navpane').is(':visible')) {
        $('#navpane').hide();
    } else {
        $('#navpane').show();
    }
    $(window).trigger('resize');
}
//INITIAL ZOOM IS WINDOW WIDTH
if (!localStorage.getItem('zoom') || !isNaN(localStorage.getItem('zoom')))
    localStorage.setItem('zoom', 'w');
//LAZY LOAD UNVEIL IMAGE
$('.pdf-viewer-img').one('unveil', function(e, page, resize) {
    clearoverlay();
    timeId = setTimeout(dooverlay, 1000);
    if (page === undefined)
        var page = $(this).attr('id').split('-').pop();
    $.getJSON('viewpdf.php?renderpdf=1&file=' + fileName + '&page=' + page, function(answer) {
        var imgw = answer[0], imgh = answer[1];
        $('body').data('lock', 0);
        if (isNaN(imgw)) {
            $.jGrowl(imgw, {
                theme: 'jgrowl-error'
            });
            return false;
        }
        $('#pdf-viewer-img-' + page).css('background-image', 'url("attachment.php?mode=inline&png=' + fileName + '.' + page + '.png")')
                .data('imgw', imgw).data('imgh', imgh);
        if (resize)
            $(window).trigger('resize');
        clearoverlay();
    });
});
//SET NEW PAGE
$('#pdf-viewer-img-div').on('scroll', function() {
    clearTimeout($.data(this, 'scrollTimer'));
    $.data(this, 'scrollTimer', setTimeout(function() {
        var parentheight = $('#pdf-viewer-img-div').height();
        $('.pdf-viewer-img').each(function(i) {
            var $t = $(this),
                    imgpos = $t.position(),
                    imgtop = imgpos.top,
                    imgbottom = imgtop + $t.height();
            //FETCH IMAGE EARLY
            if ((imgtop > 0 && imgtop < parentheight / 1.2)
                    || (imgbottom > parentheight / 5 && imgbottom < parentheight)
                    || (imgtop <= 0 && imgbottom >= parentheight)) {
                $t.trigger('unveil', i + 1);
            }
            //CHANGE PAGE LATER
            if ((imgtop > 0 && imgtop < parentheight / 2)
                    || (imgbottom > parentheight / 2 && imgbottom < parentheight)
                    || (imgtop <= 0 && imgbottom >= parentheight)) {
                var previmgleft = '',
                        imgleft = imgpos.left;
                if ($t.prev().length === 1)
                    previmgleft = $t.prev().position().left;
                //ONLY CHANGE TO LEFTMOST IMAGE
                if (previmgleft === '' || imgleft <= previmgleft) {
                    var newpage = $t.attr('id').split('-').pop();
                    if (newpage !== $('#pdf-viewer-img-div').data('pg')) {
                        $('#pdf-viewer-img-div').data('pg', newpage);
                        $.get('history.php?filename=' + fileName + '&page=' + newpage);
                        $('#control-page').val(newpage);
                        //SCROLL THUMBS
                        $('#thumbs > div').css('background-color', '');
                        if ($('#thumbs > div').length > 0) {
                            var $thumb = $('#thumbs > div:eq(' + (newpage - 1) + ')'),
                                    thtop = $thumb.position().top,
                                    thbottom = thtop + $thumb.height(),
                                    parbottom = $('#navpane').height();
                            $thumb.css('background-color', '#999fdd');
                            if ($('#thumbs').is(':visible') && (thtop < 0 || parbottom - thbottom < 0)) {
                                $('#navpane').animate({
                                    scrollTop: $('#navpane').scrollTop() + thtop + ($thumb.height() / 2) - (parbottom / 2)
                                }, 200);
                            }
                        }
                    }
                }
            }
        });
    }, 150));
});
//OPEN FIRST PAGE
$('body').data('lock', 1);
$('#pdf-viewer-img-div').data('pg', pg);
$('#pdf-viewer-img-' + pg).trigger('unveil', [pg, true]);
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
                thumbs = thumbs + '<div style="padding:1px 0 5px 0">Page '
                        + i + ':<br><img class="pdf-viewer-thumbs" src="attachment.php?mode=inline&png='
                        + fileName + '.t' + i + '.png" alt="" data-title="" data-page="' + i + '"></div>';
                i++;
            }
            $('#thumbs').html(thumbs);
            var pg = $('#pdf-viewer-img-div').data('pg'), $thumb = $('#thumbs > div:eq(' + (pg - 1) + ')');
            $thumb.css('background-color', '#999fdd');
            var thumbpos = $thumb.position();
            $('#navpane').scrollTop(thumbpos.top - 100);
            $.getJSON('viewpdf.php?renderbookmarks=1&file=' + fileName, function(bookmarks) {
                $.each(bookmarks, function(key, rows) {
                    var ttl = '';
                    ttl = $('.pdf-viewer-thumbs:eq(' + (rows.page - 1) + ')').attr('data-title');
                    if (ttl === undefined)
                        ttl = '';
                    $('.pdf-viewer-thumbs:eq(' + (rows.page - 1) + ')').attr('data-title', ttl
                            + '<div style="background-color:rgba(66,66,66,0.8);text-align:left;margin:6px 2px;line-height:1.2;padding:1px 4px">' + rows.title + '</div>');
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
    if ($('#bookmarks').find('p').length === 0) {
        $('#reading-bookmarks').remove();
        $('#bookmarks').append('<div id="reading-bookmarks" style="padding:12px">Reading bookmarks.</div>');
        $.getJSON('viewpdf.php?renderbookmarks=1&file=' + fileName, function(bookmarks) {
            if (bookmarks.length === 0) {
                $('#reading-bookmarks').text('No bookmarks.');
                return false;
            }
            $('#reading-bookmarks').remove();
            $.each(bookmarks, function(key, rows) {
                $('#bookmarks').append('<p class="bookmark" id="bookmark-' + key + '" data-page="' + rows.page + '"><span>' + rows.title + '</span></p>');
                $('#bookmark-' + key).css('padding-left', 6 * rows.level + 'px');
            });
            $('#bookmarks .bookmark').click(function() {
                $('.bookmark').css('background-color', '');
                $(this).css('background-color', '#aaafe6');
                if ($(this).data('page') !== $('#pdf-viewer-img-div').data('pg')) {
                    var pgpos = $('#pdf-viewer-img-' + $(this).data('page')).position().top + $('#pdf-viewer-img-div').scrollTop();
                    $('#pdf-viewer-img-div').scrollTop(pgpos - 2);
                }
            });
            searchnotes.init();
        });
    }
}).button().next().tipsy();
$('#notes-button').change(function(e, target) {
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
        $.getJSON('annotate.php?fetch=1&type=annotation&filename=' + fileName + usr, function(answer) {
            $('#annotations-left div.annotation, #annotations-left p').remove();
            if (answer.length === 0)
                $('#annotations-left').append('<p style="padding:0 6px">No notes.</p>');
            $.each(answer, function(key, rows) {
                var annot = rows.annotation, noteid = 'note-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                $('#annotations-left').append('<div class="annotation" id="nav-' + noteid + '" data-linksto="'
                        + noteid + '" data-page="' + rows.page + '" data-dbid="' + rows.id
                        + '"><div class="ui-state-highlight">Page ' + rows.page + ', note ' + rows.id
                        + ':</div><div class="alternating_row" style="white-space:pre-wrap;padding:0.25em;word-wrap:break-word">' + annot
                        + '</div><textarea style="width:180px;height:10em;resize:vertical;display:none"></textarea>'
                        + '<div class="ui-state-highlight note-edit"><i class="fa fa-pencil"></i> Edit</div>'
                        + '<div class="ui-state-highlight note-save" style="display:none"><i class="fa fa-save"></i> Save</div></div>');
            });
            $('#annotations-left .annotation').click(function() {
                if (!$('#pdf-viewer-annotations').prop('checked'))
                    $('#pdf-viewer-annotations').prop('checked', true).change().button('refresh');
                $('.annotation').css('background-color', '');
                $(this).css('background-color', '#aaafe6');
                if (parseInt($(this).data('page')) !== parseInt($('#pdf-viewer-img-div').data('pg'))) {
                    var pgpos = $('#pdf-viewer-img-' + $(this).data('page')).position().top + $('#pdf-viewer-img-div').scrollTop();
                    $('#pdf-viewer-img-div').scrollTop(pgpos - 2);
                }
                $('.marker-note').removeClass('marker-edit');
                $('#' + $(this).data('linksto')).addClass('marker-edit');
            });
            $('#annotations-left .note-edit').click(function() {
                var $t = $(this), $con = $t.prev().prev(),
                        $ta = $(this).prev('textarea');
                $con.hide();
                $ta.show().val($con.text());
                $t.hide().next().show();
            });
            $('#annotations-left .note-save').click(function() {
                var $t = $(this), $con = $t.prev().prev().prev(),
                        $ta = $(this).prev().prev('textarea'),
                        txt = $ta.val(), dbid = $t.parent().data('dbid'),
                        $target = $('#' + $t.parent().data('linksto'));
                $.get('annotate.php', 'edit=1&dbid=' + dbid + '&annotation=' + encodeURIComponent(txt), function() {
                    $target.attr('data-annotation', escapeHtml(txt));
                    $con.text(txt).show();
                    $ta.hide().val('');
                    $t.hide().prev().show();
                });
            });
            if (target) {
                $(target).find('.note-edit').click();
                $('#navpane').scrollTop($(target).position().top + $('#navpane').position().top - 100);
            }
        });
        searchnotes.init();
    } else {
        localStorage.setItem('notes-button', 'Off');
    }

}).button().next().tipsy();
$('#print-notes').click(function() {
    if ($('#annotations-left').html() !== '') {
        $('.annotation').css('background-color', '');
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
// PAGE NAVIGATION
$('#control-first').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    if ($('#pdf-viewer-img-div').data('pg') === 1)
        return false;
    var pgpos = $('#pdf-viewer-img-1').position().top + $('#pdf-viewer-img-div').scrollTop();
    $('#pdf-viewer-img-div').animate({'scrollTop': pgpos - 2}, 400);
}).button().tipsy();
$('#control-prev').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    var pg = parseInt($('#pdf-viewer-img-div').data('pg')),
            imgtop = $('#pdf-viewer-img-' + pg).position().top;
    if (pg === 1)
        return false;
    for (i = pg - 1; i >= 1; i--) {
        var imgtop2 = $('#pdf-viewer-img-' + i).position().top;
        if (imgtop2 < imgtop) {
            var pgpos = imgtop2 + $('#pdf-viewer-img-div').scrollTop();
            $('#pdf-viewer-img-div').animate({'scrollTop': pgpos - 2}, 250);
            return false;
        }
    }
}).button().tipsy();
$('#control-next').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    var pg = parseInt($('#pdf-viewer-img-div').data('pg')),
            imgtop = $('#pdf-viewer-img-' + pg).position().top;
    if (pg === totalPages)
        return false;
    for (i = pg + 1; i <= totalPages; i++) {
        var imgtop2 = $('#pdf-viewer-img-' + i).position().top;
        if (imgtop2 > imgtop) {
            var pgpos = imgtop2 + $('#pdf-viewer-img-div').scrollTop();
            $('#pdf-viewer-img-div').animate({'scrollTop': pgpos - 2}, 250);
            return false;
        }
    }
}).button().tipsy();
$('#control-last').click(function() {
    if ($('body').data('lock') === 1)
        return false;
    if ($('#pdf-viewer-img-div').data('pg') === totalPages)
        return false;
    var pgpos = $('#pdf-viewer-img-' + totalPages).position().top + $('#pdf-viewer-img-div').scrollTop();
    $('#pdf-viewer-img-div').animate({'scrollTop': pgpos - 2}, 400);
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
    var pgpos = $('#pdf-viewer-img-' + pg).position().top + $('#pdf-viewer-img-div').scrollTop();
    $('#pdf-viewer-img-div').scrollTop(pgpos - 2);
    return false;
}).focus(function() {
    this.select();
});
$('#thumbs').click(function(e) {
    var $t = $(e.target), pg = $t.data('page'),
            currpg = $('#pdf-viewer-img-div').data('pg'),
            pgpos = $('#pdf-viewer-img-' + pg).position().top + $('#pdf-viewer-img-div').scrollTop();
    if (pg === undefined || pg === currpg || $('body').data('lock') === 1)
        return false;
    $('#pdf-viewer-img-div').scrollTop(pgpos - 2);
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
        $('body').data('lock', 0);
        if (jQuery.isEmptyObject(answer)) {
            $.jGrowl('No Hits.');
            return false;
        }
        if (answer['Error'] !== undefined) {
            $.jGrowl(answer['Error'], {
                theme: 'jgrowl-error'
            });
            return false;
        }
        $('#search-results .search-result, #search-results .search-result-page').remove();
        $('.highlight-container').show();
        var page = '';
        $.each(answer, function(key, row) {
            $('<div class="ui-corner-all pdfviewer-highlight" id="highlight-page-' + key + '"></div>')
                    .appendTo('#pdf-viewer-img-' + row.page + ' > .highlight-container');
            $('#highlight-page-' + key).css({
                'width': row.width + '%',
                'height': row.height + '%',
                'top': row.top + '%',
                'left': row.left + '%'
            });
            if (page !== row.page) {
                page = row.page;
                $('#search-results').append('<p class="search-result-page" style="font-weight:bold;padding:0 6px">Page ' + row.page + ':</p>');
            }
            $('#search-results').append('<p class="search-result" data-linksto="highlight-page-' + key + '">' + row.text + '</p>');
        });
        if ($('#search-results').is(':visible')) {
            $('#search-results-button').prop('checked', false).change().prop('checked', true).change();
        } else {
            $('#search-results-button').button('enable').prop('checked', true).change().button('refresh');
        }
        searchresults.init();
        $('#search-results').find('.search-result').eq(0).click();
    });
}).focus(function() {
    this.select();
}).tipsy();
$('#pdf-viewer-clear').click(function() {
    $('#pdf-viewer-search').val('');
    $('.pdfviewer-highlight').remove();
    $('.highlight-container').hide();
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
$('#pdf-viewer-annotations').change(function() {
    if ($(this).is(':checked')) {
        //UNCHECK COPY TEXT BUTTON
        if ($('#pdf-viewer-copy-text').prop('checked') === true)
            $('#pdf-viewer-copy-text').trigger('click');
        //ENABLE ANNOTTATION BUTTONS
        $('#pdf-viewer-marker,#pdf-viewer-note,#pdf-viewer-marker-erase,#pdf-viewer-others-annotations').button('enable');
        //SHOW ANNOTATION CONTAINER AND BIND FUNCTIONS TO IT
        $('.annotation-container').show().click(function(e) {
            var $t = $(e.target);
            //EDITING NOTES
            if ($t.hasClass('marker-note')) {
                //EDIT THE NOTE IN NAV WINDOW
                var target = '#nav-' + $t.attr('id');
                if ($('#annotations-left').is(':hidden')) {
                    $('#notes-button').prop('checked', true).trigger('change', target);
                } else {
                    $(target).find('.note-edit').click();
                    $('#navpane').scrollTop($(target).position().top + $('#navpane').position().top - 100);
                }
            }
            //CREATING NEW NOTES
            if ($('#pdf-viewer-note').prop('checked') && !$(e.target).hasClass('marker-note')) {
                //CREATE NEW PINNED NOTE ON CLICK
                var prntpos = $(this).offset(),
                        pg = $(this).parent().attr('id').split('-').pop(),
                        posx = Math.round(1000 * (e.pageX - prntpos.left) / $(this).width() - 35) / 10,
                        posy = Math.round(1000 * (e.pageY - prntpos.top) / $(this).height() - 25) / 10,
                        markid = 'note-' + pg + '-' + 10 * posy + '-' + 10 * posx;
                if ($('#' + markid).length === 1)
                    return false;
                $('<div class="marker marker-note" id="' + markid + '" data-dbid="" data-annotation=""></div>').appendTo(this)
                        .tipsy({
                            title: function() {
                                return '<div style="text-align:left;white-space:pre-wrap;word-wrap:break-word;margin:4px"><b>Note:</b><br>'
                                        + this.getAttribute('data-annotation')
                                        + '</div>';
                            },
                            gravity: $.fn.tipsy.autoNS,
                            html: true
                        });
                ;
                $('#' + markid).css('top', posy + '%').css('left', posx + '%');
                //SAVE NEW NOTE, GET DBID
                $.get('annotate.php', 'save=1&type=annotation&filename=' + fileName + '&page=' + pg + '&top=' + posy + '&left=' + posx + '&annotation=',
                        function(answer) {
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
        }).mouseenter(function() {
            if ($('#pdf-viewer-note').prop('checked')
                    || $('#pdf-viewer-marker-erase').prop('checked'))
                $("#cursor").show();
        }).mouseleave(function() {
            if ($('#pdf-viewer-note').prop('checked')
                    || $('#pdf-viewer-marker-erase').prop('checked'))
                $("#cursor").hide();
        }).mousemove(function(e) {
            var posx = 16 + e.pageX, posy = 16 + e.pageY;
            $('#cursor').css('top', posy + 'px').css('left', posx + 'px');
        });
        //FETCH MARKERS
        $.getJSON('annotate.php?fetch=1&type=yellowmarker&filename=' + fileName, function(answer) {
            var divs = '', pdfpg = '1';
            $.each(answer, function(key, rows) {
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
        //FETCH NOTES
        $.getJSON('annotate.php?fetch=1&type=annotation&filename=' + fileName, function(answer) {
            var divs = '', pdfpg = '1';
            $.each(answer, function(key, rows) {
                var markid = 'note-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                if (pdfpg !== rows.page) {
                    $('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html($('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html() + divs);
                    divs = '<div class="marker marker-note" id="' + markid + '" data-dbid="' + rows.id + '" data-annotation="'
                            + escapeHtml(rows.annotation) + '" style="top:' + rows.top + '%;left:' + rows.left
                            + '%;width:' + rows.width + '%">' + rows.id + '</div>';
                    pdfpg = rows.page;
                } else {
                    divs += '<div class="marker marker-note" id="' + markid + '" data-dbid="' + rows.id + '" data-annotation="'
                            + escapeHtml(rows.annotation) + '" style="top:' + rows.top + '%;left:' + rows.left
                            + '%;width:' + rows.width + '%">' + rows.id + '</div>';
                }
            });
            $('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html($('#pdf-viewer-img-' + pdfpg + ' > .annotation-container').html() + divs);
            $('.marker-note').tipsy({
                title: function() {
                    return '<div style="text-align:left;white-space:pre-wrap;word-wrap:break-word;margin:4px"><b>Note:</b><br>'
                            + this.getAttribute('data-annotation')
                            + '</div>';
                },
                gravity: $.fn.tipsy.autoNS,
                html: true
            });
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
}).button().next().tipsy();
//BLUE MARKER PEN
$('#pdf-viewer-marker').change(function() {
    if ($(this).is(':checked')) {
        //UNCHECK OTHER BUTTONS
        if ($('#pdf-viewer-note').is(':checked'))
            $('#pdf-viewer-note').prop('checked', false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked'))
            $('#pdf-viewer-marker-erase').prop('checked', false).change().button('refresh');
        //REMOVE DRAG-SCROLL
        $('#pdf-viewer-img-div').unbind('mouseup mousedown mouseout');
        //ADD CURSOR CLASS
        $("#cursor > span").addClass('fa-pencil');
        //ADD TEXT LAYER ON TOP
        $('#pdf-viewer-copy-text').prop('checked', true).change().button('disable');
        //BIND CURSOR TO TEXT LAYER
        $('.text-container').mouseenter(function() {
            $("#cursor").show();
        }).mouseleave(function() {
            $("#cursor").hide();
        }).mousemove(function(e) {
            var posx = 16 + e.pageX, posy = 16 + e.pageY;
            $('#cursor').css('top', posy + 'px').css('left', posx + 'px');
        })
        //MARKERS ARE ADDED IN pdf-viewer-copy-text FUNCTION
    } else {
        $('#pdf-viewer-img-div').clickNScroll().css('cursor', 'pointer');
        $("#cursor").hide().find('span').removeClass('fa-pencil');
        $('.text-container').unbind('mouseenter mouseleave mousemove');
        //REMOVE TEXT LAYER ON TOP
        $('#pdf-viewer-copy-text').prop('checked', false).change().button('enable');
    }
}).button({
    disabled: true
}).next().tipsy();
//PINNED NOTES
$('#pdf-viewer-note').change(function() {
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
        $('#pdf-viewer-img-div').clickNScroll().css('cursor', 'pointer');
        //REMOVE CURSOR
        $("#cursor").hide().find('span').removeClass('fa-thumb-tack');
    }
}).button({
    disabled: true
}).next().tipsy();
//ERASE ANNOTATIONS
$('#pdf-viewer-marker-erase').change(function() {
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
        $('#pdf-viewer-delete-menu > div').eq(0).click(function() {
            $('#pdf-viewer-delete-menu').hide();
            $('.annotation-container').selectable({
                stop: function(e, ui) {
                    var markers = '', notes = '';
                    $(".ui-selected", this).each(function() {
                        if ($(this).hasClass('marker-yellow')) {
                            markers += '&dbids[]=' + $(this).data('dbid');
                        } else if ($(this).hasClass('marker-note')) {
                            notes += '&dbids[]=' + $(this).data('dbid');
                            $(this).tipsy('disable');
                        }
                    });
                    if (markers !== '') {
                        $.get('annotate.php?delete=1&type=yellowmarker' + markers, function(answer) {
                            if (answer === '') {
                                $.jGrowl('Error during deleting the mark!');
                            } else {
                                $('.ui-selected').filter('.marker-yellow').remove();
                            }
                        });
                    }
                    if (notes !== '') {
                        $.get('annotate.php?delete=1&type=annotation' + notes, function(answer) {
                            if (answer === '') {
                                $.jGrowl('Error during deleting the mark!');
                            } else {
                                $('.ui-selected').filter('.marker-note').remove();
                                $('.tipsy').remove();
                                if ($('#annotations-left').is(':visible'))
                                    $('#notes-button').prop('checked', false).change().prop('checked', true).change();
                            }
                        });
                    }
                }
            });
        });
        //DELETE ALL MARKERS
        $('#pdf-viewer-delete-menu > div').eq(1).click(function() {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all markers?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function() {
                            $.get('annotate.php?delete=all&type=yellowmarker&filename=' + fileName, function(answer) {
                                if (answer === 'OK') {
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
        //DELETE NOTES
        $('#pdf-viewer-delete-menu > div').eq(2).click(function() {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all notes?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function() {
                            $.get('annotate.php?delete=all&type=annotation&filename=' + fileName, function(answer) {
                                if (answer === 'OK') {
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
        //DELETE ALL ANNOATIONS
        $('#pdf-viewer-delete-menu > div').eq(3).click(function() {
            $('#confirm-container').html('<p><span class="fa fa-exclamation-triangle ui-state-error-text" style="margin:2px 6px 2em 0"></span>Delete all annotations?</p>')
                    .dialog('option', 'buttons', {
                        'Delete': function() {
                            $.get('annotate.php?delete=all&type=all&filename=' + fileName, function(answer) {
                                if (answer === 'OK') {
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
        //HIDE MENU
        $('#pdf-viewer-delete-menu').hide();
        //ENABLE DRAG-SCROLL
        $('#pdf-viewer-img-div').clickNScroll().css('cursor', 'pointer');
        //UNBIND SELECTABLE
        if ($('.annotation-container').hasClass('ui-selectable'))
            $('.annotation-container').selectable('destroy');
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
$('#pdf-viewer-others-annotations').change(function() {
    if (!$('#annotations-left').is(':hidden'))
        $('#notes-button').prop('checked', false).change().prop('checked', true).change();
    if ($(this).is(':checked')) {
        $.getJSON('annotate.php?fetchothers=1&type=yellowmarker&filename=' + fileName, function(answer) {
            $.each(answer, function(key, rows) {
                var markid = 'marker-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                $('<div class="marker marker-yellow-others" id="' + markid + '" data-dbid="' + rows.id + '"></div>')
                        .appendTo('#pdf-viewer-img-' + rows.page + ' > .annotation-container');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%').css('width', rows.width + '%').attr('title', rows.user).tipsy();
            });
        });
        $.getJSON('annotate.php?fetchothers=1&type=annotation&filename=' + fileName + '&page=' + $('#pdf-viewer-img-div').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid = 'note-' + rows.page + '-' + 10 * rows.top + '-' + 10 * rows.left;
                $('<div class="marker marker-note-others" id="' + markid + '" data-dbid="' + rows.id + '" data-annotation="' + escapeHtml(rows.annotation) + '">' + rows.id + '</div>')
                        .appendTo('#pdf-viewer-img-' + rows.page + ' > .annotation-container');
                $('#' + markid).css('top', rows.top + '%').css('left', rows.left + '%').attr('title', rows.user);
            });
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
        });
    } else {
        $('.annotation-container .marker-yellow-others, .annotation-container .marker-note-others').remove();
    }
}).button({
    disabled: true
}).next().tipsy();
//SEARCH IN NOTES AND BOOKMARKS
var searchnotes = {
    init: function() {
        $(".pdf_filter").keyup(function() {
            var str = $(this).val(), $container = $(this).siblings('p, div');
            if (str !== '') {
                qstr = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(qstr, 'i');
                $container.hide().filter(function() {
                    return re.test($(this).children('span,.alternating_row').text());
                }).show();
                var re2 = new RegExp('\(' + qstr + '\)', 'gi');
                $container.each(function() {
                    if ($(this).is(':visible')) {
                        newstr = $(this).children('span,.alternating_row').text().replace(re2, '<span style="background-color:#eea">$1</span>');
                        $(this).children('span,.alternating_row').html(newstr);
                    }
                });
            } else {
                $container.show();
                $container.each(function() {
                    newstr = $(this).children('span,.alternating_row').text();
                    $(this).children('span,.alternating_row').text(newstr);
                });
            }
        }).focus(function() {
            $(this).val('');
            $(this).siblings('p, div').show();
            $(this).siblings('p, div').each(function() {
                newstr = $(this).children('span,.alternating_row').text();
                $(this).children('span,.alternating_row').text(newstr);
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
        $("#search-results .search-result").click(function() {
            var $t = $(this), $target = $('#' + $t.data('linksto')),
                    targetpg = $target.parent().parent().attr('id').split('-').pop(),
                    pg = $('#pdf-viewer-img-div').data('pg');
            if (pg !== targetpg) {
                var pgpos = $('#pdf-viewer-img-' + targetpg).position().top + $('#pdf-viewer-img-div').scrollTop();
                $('#pdf-viewer-img-div').scrollTop(pgpos - 2);

            }
            setTimeout(function() {
                var pgpos = $('#pdf-viewer-img-' + targetpg).position().top + $('#pdf-viewer-img-div').scrollTop() + $target.position().top - 200;
                $('#pdf-viewer-img-div').scrollTop(pgpos - 2).scrollLeft($target.position().left - 200);
            }, 200);
            $('.highlight-container .pdfviewer-highlight').css('box-shadow', '');
            $target.css('box-shadow', '0 0 6px 3px rgba(0,0,0,0.33)');
            $('#search-results .search-result').removeClass('shown').css('background-color', '');
            $t.addClass('shown').css('background-color', '#aaafe6');
        });
    }
};
// COPY TEXT
$('#pdf-viewer-copy-text').change(function() {
    if ($(this).is(':checked')) {
        timeId = setTimeout(dooverlay, 1000);
        //REMOVE DRAG-SCROLL
        $('#pdf-viewer-img-div').unbind('mouseup mousedown mouseout');
        //HIDE ANNOTATIONS, EXCEPT WHEN MARKER IS ON
        if ($('#pdf-viewer-annotations').prop('checked') === true && $('#pdf-viewer-marker').prop('checked') === false)
            $('#pdf-viewer-annotations').prop('checked', false).trigger('change').button("refresh");
        //GET CURRENT PAGE
        var pg = $('#pdf-viewer-img-div').data('pg');
        //SHOW TEXT CONTAINER
        $('.text-container').show().css('cursor', 'default');
        //IF ALREADY POPULATED, EXIT FUNCTION
        if (!$('#pdf-viewer-img-div').find('.text-container').is(':empty')) {
            clearoverlay();
            return false;
        }
        //FETCH TEXT FROM SERVER
        $.getJSON('pdfhtml.php?file=' + fileName, function(answer) {
            var divs = '', pdfpg = 1;
            $.each(answer, function(key, rows) {
                //ERROR MESSAGE
                if (rows.toString().substring(0, 5) === 'Error') {
                    $.jGrowl(answer, {theme: 'jgrowl-error'});
                    $('#pdf-viewer-copy-text').prop('checked', false).button('refresh');
                    return false;
                }
                if (pdfpg !== rows.page) {
                    $('#pdf-viewer-img-' + pdfpg + ' > .text-container').html(divs);
                    divs = '<div class="pdf-text-div" style="top:' + rows.top + '%;left:' + rows.left + '%;width:' + rows.width + '%;height:' + rows.height + '%">' + rows.text + '</div>';
                    pdfpg = rows.page;
                } else {
                    divs += '<div class="pdf-text-div" style="top:' + rows.top + '%;left:' + rows.left + '%;width:' + rows.width + '%;height:' + rows.height + '%">' + rows.text + '</div>';
                }
            });
            $('#pdf-viewer-img-' + pdfpg + ' > .text-container').html(divs);
            clearoverlay();
            //TEXT SELECTABLE UI
            $('.text-container').selectable({
                distance: 0,
                stop: function() {
                    //SELECT TEXT TO COPY
                    if ($('#pdf-viewer-marker').prop('checked') === false) {
                        //GET SELECTED TEXT
                        var txt = '';
                        $(this).find(".ui-selected").each(function() {
                            txt = txt + $(this).text() + ' ';
                        });
                        txt = txt.replace(/(- )/g, '');
                        if (txt === '')
                            return false;
                        //OPEN DIALOG, COPY SELECTED TEXT
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
                        //GET ROW DIMENSIONS AND SAVE MARKERS
                    } else {
                        var $t = $(this), $d = $t.prev(), pg = $t.parent().attr('id').split('-').pop(),
                                markers = [], divs = '';
                        $t.find(".ui-selected").each(function() {
                            //GET COORDINATES
                            var postop = Math.round(1000 * $(this).position().top / $t.height()) / 10,
                                    posleft = Math.round(1000 * $(this).position().left / $t.width()) / 10,
                                    w = Math.round(1000 * $(this).width() / $t.width()) / 10,
                                    markid = 'marker-' + pg + '-' + 10 * postop + '-' + 10 * posleft;
                            //IF UNIQUE, ADD MARKER TO PAGE AND ARRAY
                            if ($('#' + markid).length === 0) {
                                markers.push({id: markid, top: postop, left: posleft, width: w});
                                divs += '<div id="' + markid + '" class="marker marker-yellow" style="top:'
                                        + postop + '%;left:' + posleft + '%;width:'
                                        + w + '%;height:1.2%" data-dbid=""></div>';
                            }
                        });
                        $d.html($d.html() + divs);
                        //SEND MARKERS ARRAY TO SERVER TO SAVE AND GET DBIDS BACK
                        var finald = {'markers': markers, 'save': 1, 'type': 'yellowmarker', 'filename': fileName, 'page': pg};
                        $.ajax({
                            url: 'annotate.php', //?save=1&type=yellowmarker&filename=' + fileName + '&page=' + pg,
                            data: finald,
                            type: 'post',
                            dataType: 'json',
                            success: function(answer) {
                                $.each(answer, function(key, row) {
                                    $('#' + row.markid).attr('data-dbid', row.dbid);
                                });
                            }
                        });
                    }
                }
            });
        });
    } else {
        //HIDE OPEN CONTAINERS, LEAVE CONTENT CACHED
        $('.text-container').filter(':visible').css('cursor', 'inherit').hide();
        $('#pdf-viewer-img-div').clickNScroll();
        clearoverlay();
    }
}).button().next().tipsy();
//HOTKEYS
$(document).unbind('keydown').bind('keydown', 'd', function() {
    $('#control-next').click();
}).bind('keydown', 'e', function() {
    $('#control-prev').click();
}).bind('keydown', 's', function() {
    $('.nextrecord').click();
}).bind('keydown', 'w', function() {
    $('.prevrecord').click();
}).bind('keydown', 'del', function() {
    if ($('#deletebutton').is(':visible'))
        $('#deletebutton').click();
}).bind('keydown', 'q', function() {
    if ($('.backbutton').is(':visible'))
        $('.backbutton').click();
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
