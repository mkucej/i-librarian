var hosted = false;
// ALSO EDIT THE URL IN TOPINDEX

//WPAD fake functions
function FindProxyForURL() {
    return '';
}
function isPlainHostName() {
    return false;
}
function dnsDomainIs() {
    return false;
}
function localHostOrDomainIs() {
    return false;
}
function isResolvable() {
    return true;
}
function isInNet() {
    return false;
}
function dnsResolve() {
    return '';
}
function myIpAddress() {
    return '';
}
function dnsDomainLevels() {
    return '2';
}
function shExpMatch() {
    return false;
}
function weekdayRange() {
    return true;
}
function dateRange() {
    return true;
}
function timeRange() {
    return true;
}
function escapeHtml(unsafe) {
    return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
}
/**
 * AJAX global settings.
 */
$.ajaxSetup({
    cache: false
});
// AJAX error handling.
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
// When AJAX completes.
$(document).ajaxComplete(function () {
    $('.tipsy').remove();
});
//global overlay
var timeId = '';
var dooverlay = function () {
    if ($('#overlay').length === 1)
        return false;
    $('body').append('<div id="overlay" class="ui-widget-overlay"></div>');
    $('body').append('<i id="overlay-cog" class="fa fa-cog fa-spin" style="font-size: 64px;position: fixed;left:48%;top:' + (-32 + 0.5 * $(document).height()) + 'px"></i>');
    $('#pdf-div').css('visibility', 'hidden');
};
var clearoverlay = function () {
    clearTimeout(timeId);
    $('#overlay, #overlay-cog').remove();
    $('#pdf-div').css('visibility', '');
};
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
jQuery.fn.extend({
    insertAtCaret: function (myValue) {
        return this.each(function (i) {
            if (document.selection) {
                //For browsers like Internet Explorer
                this.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                this.focus();
            } else if (this.selectionStart || this.selectionStart === 0) {
                //For browsers like Firefox and Webkit based
                var startPos = this.selectionStart;
                var endPos = this.selectionEnd;
                var scrollTop = this.scrollTop;
                this.value = this.value.substring(0, startPos) + myValue + this.value.substring(endPos, this.value.length);
                this.focus();
                this.selectionStart = startPos + myValue.length;
                this.selectionEnd = startPos + myValue.length;
                this.scrollTop = scrollTop;
            } else {
                this.value += myValue;
                this.focus();
            }
        });
    }
});
$.extend($.fn.tipsy.defaults, {
    turnOff: false
});
$.extend($.ui.autocomplete.prototype.options, {
    open: function (event, ui) {
        $(this).autocomplete("widget").css({
            "width": ($(this).width() + "px")
        });
    }
});
$(window).load(function () {
    var h = $(window).height() - 41;
    $('#top-panel').load('topindex.php', function () {
        topindex.init();
    });
    $('#bottom-panel').height(h).load('leftindex.php?select=library', function () {
        leftindex.init('library');
    });
    $('#addrecord-panel, #items-container').height(h);
    $(window).resize(function () {
        var h = $(window).height() - 41;
        $('#bottom-panel, #addrecord-panel, #items-container').height(h);
    });
    $.extend($.ui.dialog.prototype.options, {
        modal: true,
        resizable: false,
        draggable: false
    });
    $("#dialog-confirm").dialog({
        autoOpen: false,
        open: function () {
            $("#pdf-div").css('visibility', 'hidden');
        },
        close: function () {
            $("#pdf-div").css('visibility', '');
        }
    });
    $("#advancedsearch").dialog({
        autoOpen: false,
        width: 960,
        buttons: {
            "Search": function () {
                $('#advancedsearchform').submit();
            },
            "Save": function () {
                $('#advancedsearchform').ajaxSubmit({
                    data: {
                        savesearch: '1'
                    },
                    success: function () {
                        if ($("#savedsearch_container").is(':visible')) {
                            $("#savedsearch_container").load('ajaxleftindex.php?open[]=savedsearch', function () {
                                $("#savedsearch_container").find('button').button();
                            });
                        }
                    }
                });
            },
            "Clear": function () {
                $.get('search.php?newsearch=1', function () {
                    $("#advanced-filter").focus().blur();
                    $("#advancedsearchform").find('input[type="text"]').val('');
                });
            },
            "Close": function () {
                $(this).dialog('close');
            }
        }
    });
    $("#expertsearch").dialog({
        autoOpen: false,
        width: 960,
        buttons: {
            "Search": function () {
                $('#expertsearchform').submit();
            },
            "Save": function () {
                $('#expertsearchform').ajaxSubmit({
                    data: {
                        savesearch: '1'
                    },
                    success: function () {
                        if ($("#savedsearch_container").is(':visible')) {
                            $("#savedsearch_container").load('ajaxleftindex.php?open[]=savedsearch', function () {
                                $("#savedsearch_container").find('button').button();
                            });
                        }
                    }
                });
            },
            "Clear": function () {
                $.get('search.php?newsearch=1', function () {
                    $("#expert-filter").focus().blur();
                    $('#expertsearchform').find('textarea, input[name="searchname"]').val('');
                });
            },
            "Close": function () {
                $(this).dialog('close');
            }
        }
    });
    $('#open-dirs').dialog({
        autoOpen: false,
        width: 600,
        height: 450,
        title: 'Select directory',
        buttons: {
            'Select': function () {
                $(this).dialog('close');
                var dir = $('#filetree-input').val();
                dir = dir.substr(0, ($('#filetree-input').val().length - 1));
                $('input[name="directory"]:visible').val(dir);
            },
            Cancel: function () {
                $(this).dialog('close');
            }
        }
    });
    // keyboard
    $('#keyboard').draggable({
        axis: "y",
        handle: "#keyboard-drag",
        snap: "body",
        containment: "body",
        iframeFix: true
    });
    $('#keyboard-header >div:first, #keyboard > .keyboard-content, #keyboard-close').mousedown(function () {
        return false;
    });
    $('#keyboard > .keyboard-content > div').click(function () {
        var char = $(this).html();
        if ($('#notes_ifr').length === 1) {
            tinymce.execCommand("mceInsertContent", !1, char);
        } else {
            $(':focus').insertAtCaret(char).trigger('keyup');
        }
    });
    $('#keyboard-close').click(function () {
        $('#keyboard').stop(true, false).fadeOut(200);
    });
    $('#keyboard-header > div > div').click(function () {
        $(this).siblings().removeClass('keyboard-header-active');
        $(this).addClass('keyboard-header-active');
    });
    $('#keyboard-arrows-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-arrows').show();
    });
    $('#keyboard-currency-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-currency').show();
    });
    $('#keyboard-greek-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-greek').show();
    });
    $('#keyboard-latin-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-latin').show();
    });
    $('#keyboard-math-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-math').show();
    });
    $('#keyboard-math2-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-math2').show();
    });
    $('#keyboard-super-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-super').show();
    });
    $('#keyboard-technical-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-technical').show();
    });
    $('#keyboard-other-open').click(function () {
        $('#keyboard').find('.keyboard-content').hide();
        $('#keyboard-other').show();
    });
    //HOTKEY F2
    $('body').bind('keydown', function (e) {
        if (e.which === 113) {
            if ($('#keyboard').is(':hidden')) {
                $('#keyboard').stop(true, false).fadeIn(200);
            } else {
                $('#keyboard-close').click();
            }
            return false;
        }
    });
});
var common = {
    init: function () {
        $(".select_span").unbind('click').click(function (e) {
            e.stopPropagation();
            if ($(this).hasClass('ui-state-disabled'))
                e.stopImmediatepropagation();
            var $input = $(this).children('input'), $span = $(this).find('i');
            if ($input.is(':radio')) {
                var rname = $input.attr('name');
                $input.prop('checked', true);
                $(this).closest('table').find('input[name="' + rname + '"]').each(function () {
                    $(this).parent().find('i').removeClass('fa-circle').addClass('fa-circle-o');
                });
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
    }
};
var browsedirs = {
    init: function () {
        $('#filetree').fileTree({
            root: $('#filetree').data('root')
        }, function (file) {
            if ($('#win-drive').length === 1 && $('#win-drive').length !== '') {
                $('#filetree-input').val($('#win-drive').val() + ':' + file);
                
            } else {
                $('#filetree-input').val(file);
            }
        });
        $('#win-drive').keyup(function () {
            $('#filetree').fileTree({
                root: $('#win-drive').val() + ':/'
            }, function (file) {
                $('#filetree-input').val(file);
            });
        });
    }
};
var omnitool = {
    init: function () {
        common.init();
        $("#lock").click(function () {
            $(":checkbox[value=8]").prop('disabled', false);
            $(":checkbox[value=8]").parent('td').removeClass('ui-state-disabled').addClass('select_span');
            common.init();
        });
        $('#omnitooldiv select').each(function () {
            var $t = $(this);
            $(this).selectmenu({position: {my: "right top", at: "right bottom"}}).data("ui-selectmenu")._resizeMenu = function () {
                this.menu.width(parseInt($t.next('span').width()));
                if ($t.find('option').length > 12)
                    this.menu.height('300');
            };
        });
    }
};
var details = {
    init: function () {
        $(".vacuum").click(function () {
            timeId = setTimeout(dooverlay, 100);
            var db = $(this).data('db'), $s = $(this).siblings('.file-size');
            $.get('vacuum.php?db=' + db, function (answer) {
                $s.text(answer);
                $.jGrowl('Database vacuumed.');
                clearoverlay();
            });
        }).tipsy({
            gravity: 'w',
            title: function () {
                return 'Remove empty space and defragment the database file.';
            }
        });
        $(".integrity").click(function () {
            timeId = setTimeout(dooverlay, 100);
            var db = $(this).data('db'), $s = $(this).siblings('.file-size');
            $.get('integrity.php?db=' + db, function (answer) {
                if (answer === 'ok' || answer === 'OK') {
                    $.jGrowl('Database is OK.');
                } else {
                    $.jGrowl(answer + 'Database is corrupted.', {
                        theme: 'jgrowl-error'
                    });
                }
                clearoverlay();
            });
        });
        $.get('checkbinaries.php?binary=pdftotext', function (answer) {
            if (answer === "OK") {
                $('#details-1').text('working');
                $('#details-2').text('OK');
            } else {
                $('#details-1').text('not working');
                $('#details-2').text('!!!');
            }
        });
        $.get('checkbinaries.php?binary=pdfinfo', function (answer) {
            if (answer === "OK") {
                $('#details-3').text('working');
                $('#details-4').text('OK');
            } else {
                $('#details-3').text('not working');
                $('#details-4').text('!!!');
            }
        });
        $.get('checkbinaries.php?binary=pdftohtml', function (answer) {
            if (answer === "OK") {
                $('#details-5').text('working');
                $('#details-6').text('OK');
            } else {
                $('#details-5').text('not working');
                $('#details-6').text('!!!');
            }
        });
        $.get('checkbinaries.php?binary=ghostscript', function (answer) {
            if (answer === "OK") {
                $('#details-7').text('working');
                $('#details-8').text('OK');
            } else {
                $('#details-7').text('not working');
                $('#details-8').text('!!!');
            }
        });
        $.get('checkbinaries.php?binary=pdfdetach', function (answer) {
            if (answer === "OK") {
                $('#details-11').text('working');
                $('#details-12').text('OK');
            } else {
                $('#details-11').text('not working');
                $('#details-12').text('!!!');
            }
        });
        $.get('checkbinaries.php?binary=tesseract', function (answer) {
            if (answer === "OK") {
                $('#details-13').text('working');
                $('#details-14').text('OK');
            } else {
                $('#details-13').text('not working');
                $('#details-14').text('!!!');
            }
        });
        $.get('checkbinaries.php?binary=soffice', function (answer) {
            if (answer === "OK") {
                $('#details-15').text('working');
                $('#details-16').text('OK');
            } else {
                $('#details-15').text('not working');
                $('#details-16').text('!!!');
            }
        });
        $("#clear-trash").click(function () {
            timeId = setTimeout(dooverlay, 100);
            $.get('cleartrash.php', function (answer) {
                $.jGrowl('Temp directory cleaning: ' + answer);
                clearoverlay();
            });
        }).tipsy({
            gravity: 'w',
            title: function () {
                return 'Erase global cached data. User-specific login sessions will remain unaffected.';
            }
        });
        $('#details-form').find('span').click(function () {
            var soffice = $(this).prev().val();
            $.get('details.php?soffice_path=' + soffice, function () {
                $.jGrowl('LibreOffice path saved.');
            });
        });
    }
};
var topindex = {
    init: function () {
        $('#bottomrow > a').click(function (event) {
            event.preventDefault();
            var link = $(this).attr('id');
            if (link === 'link-library') {
                $('#bottom-panel').load('leftindex.php?select=library', function () {
                    leftindex.init('library');
                    $('#bottom-panel').show();
                    $('#addrecord-panel, #items-container').hide();
                    $('#items-container').empty();
                });
            } else
            if (link === 'link-shelf') {
                $('#bottom-panel').load('leftindex.php?select=shelf', function () {
                    leftindex.init('shelf');
                    $('#bottom-panel').show();
                    $('#addrecord-panel, #items-container').hide();
                    $('#items-container').empty();
                });
            } else
            if (link === 'link-clipboard') {
                $('#bottom-panel').load('leftindex.php?select=clipboard', function () {
                    leftindex.init('clipboard');
                    $('#bottom-panel').show();
                    $('#addrecord-panel, #items-container').hide();
                    $('#items-container').empty();
                });
            } else
            if (link === 'link-desk') {
                $('#bottom-panel').load('leftindex.php?select=desk', function () {
                    desktop.init();
                    $('#bottom-panel').show();
                    $('#addrecord-panel, #items-container').hide();
                    $('#items-container').empty();
                });
            } else
            if (link === 'link-record') {
                if ($('#addrecord-panel').html() === '') {
                    $('#addrecord-panel').load('addarticle.php', function () {
                        addarticle.init(true);
                        $('#bottom-panel, #items-container').hide().empty();
                        $('#addrecord-panel').show();
                    });
                } else {
                    $('#bottom-panel, #items-container').hide().empty();
                    $('#addrecord-panel').show();
                }
            } else
            if (link === 'link-tools') {
                $('#bottom-panel').load('tools.php', function () {
                    tools.init();
                    $('#bottom-panel').show();
                    $('#addrecord-panel, #items-container').hide();
                    $('#items-container').empty();
                });
            }
        }).tipsy({
            gravity: 'n'
        });
        $("#keyboardswitch").click(function () {
            if ($('#keyboard').is(':visible')) {
                $('#keyboard-close').click();
            } else {
                $('#keyboard').stop(true, false).fadeIn(200);
            }
        }).tipsy({
            gravity: 'n'
        });
        $('#link-signout').click(function () {
            $('#dialog-confirm').html('<p><i class="fa fa-power-off ui-state-error-text" style="margin-right:16px;font-size:2em"></i><span style="position:relative;bottom:4px">Do you want to sign out?</span></p>')
                    .dialog("option", "buttons", {
                        "Sign Out": function () {
                            $.get('authenticate.php?action=signout', function () {
                                var newurl = top.location.href.split('?').shift();
                                top.location.assign(newurl);
                            });
                        },
                        "Cancel": function () {
                            $(this).dialog("close");
                        }
                    }).dialog({
                width: 'auto',
                height: 'auto',
                title: 'Sign Out?'
            }).dialog('open');
        }).tipsy();
        $('#bottomrow > a.topindex').click(function () {
            $('#bottomrow > a.topindex').each(function () {
                $(this).removeClass('topindex_clicked');
            });
            $(this).addClass('topindex_clicked').blur();
        });
        var rnum = Math.floor(Math.random() * 11);
        if (rnum > 6 && hosted === false) {
            $.getScript('wpad.php', function () {
                var proxystr = FindProxyForURL('', 'www.crossref.org');
                $.get('downloadnewversion.php?proxystr=' + encodeURIComponent(proxystr), function (answer) {
                    if (answer === 'yes')
                        $.jGrowl('New version of I, Librarian available.');
                });
            });
        }
    }
};
var index2 = {
    init: function () {
        $('#first-loader', window.parent.document).fadeOut(400, function () {
            $(this).remove();
        });
        common.init();
        $('#signin-container').offset({
            top: ($(window).height() / 2) - ($('#signin-container').height() / 2),
            left: ($(window).width() / 2) - ($('#signin-container').width() / 2)
        });
        $(window).resize(function () {
            $('#signin-container').offset({
                top: ($(window).height() / 2) - ($('#signin-container').height() / 2),
                left: ($(window).width() / 2) - ($('#signin-container').width() / 2)
            });
            $('#sign-options-list').hide();
        });
        $('#signin-container input:password:first').focus();
        $('#signin-container input:text:first').focus();
        $('#signinbutton').click(function () {
            var $form = $('#signinform'), passwd = $form.find('input[name=pass]').val(), username = '', queryString = $form.formSerialize();
            if ($form.find('input[name=user]').length > 0)
                username = $form.find('input[name=user]').val();
            if ($form.find('select[name=user]').length > 0)
                username = $form.find('select[name=user] option:selected').val();
            if (username !== '' && passwd !== '') {
                $.post('authenticate.php', queryString, function (answer) {
                    if (answer === 'OK') {
                        top.location.reload(true);
                    } else {
                        $.jGrowl(answer);
                    }
                });
            }
            return false;
        }).button();
        $('#signinform').submit(function () {
            $('#signinbutton').click();
            return false;
        });
        if ($('#signinform').find('select').length === 1) {
            $('#signinform').find('select').selectmenu({
                select: function () {
                    $('#signin-container input:password:first').focus();
                }
            }).data("ui-selectmenu")._resizeMenu = function () {
                this.menu.width(parseInt($('#signinform').find('select').next('span').width()));
                if ($('#signinform select').find('option').length > 12)
                    this.menu.height('300');
            };
        }
        $('#signupbutton').click(function () {
            var $form = $('#signupform'), passwd = $form.find('input[name=pass]').val(),
                    username = $form.find('input[name=user]').val(), passwd2 = $form.find('input[name=pass2]').val();
            if (username === '' || passwd === '' || passwd2 === '') {
                return false;
            } else
            if (passwd !== passwd2) {
                $.jGrowl('Password typo.');
            } else {
                $.post('authenticate.php', {
                    'form': 'signup',
                    'user': username,
                    'pass': passwd,
                    'pass2': passwd2
                }, function (answer) {
                    if (answer === 'OK') {
                        top.location.reload(true);
                    } else {
                        $.jGrowl(answer);
                    }
                });
            }
            return false;
        }).button();
        $('#signupform').submit(function () {
            $('#signupbutton').click();
            return false;
        });
        $('#register').click(function () {
            $('#signupform').show();
            $('#signinform').hide();
        });
        $('#login').click(function () {
            $('#signupform').hide();
            $('#signinform').show();
        });
        $('#sign-options').click(function () {
            $(this).blur();
            var pos = $(this).parent().find('label').offset();
            $('#sign-options-list').toggle().offset({
                top: pos.top + $(this).parent().height() + 1,
                left: pos.left
            });
        }).button();
        $('#credits').click(function () {
            window.open('http://i-librarian.net');
        });
        $("#openresetpassword").click(function () {
            $('#signupform').hide();
            $('#signinform').hide();
            $('#resetpassword-container').load('resetpassword.php', function () {
                resetpassword.init();
            }).show();
        });
        $("#tos-link").click(function (e) {
            e.preventDefault();
            $('#tos-container').load('tos.html', function () {
                $(this).dialog({
                    width: '600',
                    height: '480',
                    buttons: {
                        'Close': function () {
                            $(this).dialog('close');
                        }
                    }
                });
            });
        });
    }
};
var resetpassword = {
    init: function () {
        $("#resetpasswordbutton").button();
        $("#backtologin").click(function () {
            $('#signinform').show();
            $('#resetpassword-container').hide();
        });
        $('#resetpasswordform').ajaxForm({
            beforeSubmit: function (arr) {
                if (arr[0]['value'] === '')
                    return false;
            },
            success: function (answer) {
                $.jGrowl(answer);
                $('#signinform').show();
                $('#resetpassword-container').hide();
            }
        });
    }
};
var settings = {
    init: function () {
        $(":submit").button();
        if ($('input[name=disallow_signup]:checked').length === 0) {
            $('td.default_permissions').addClass('ui-state-disabled');
            $('input[name=default_permissions]').prop('disabled', true);
        }
        $('input[name=disallow_signup]').click(function () {
            if ($(this).is(':checked')) {
                $('td.default_permissions').removeClass('ui-state-disabled');
                $('input[name=default_permissions]').prop('disabled', false);
            } else {
                $('td.default_permissions').addClass('ui-state-disabled');
                $('input[name=default_permissions]').prop('disabled', true);
            }
        });
        $('#form-settings').ajaxForm(function () {
            $('#right-panel').load('settings.php', function () {
                settings.init();
                $.jGrowl('Settings saved.');
                $('#addrecord-panel').load('addarticle.php', function () {
                    addarticle.init(true);
                });
            });
        });
    }
};
var tools = {
    init: function () {
        $('#tools-left').find('button').each(function(){
            $(this).button()
                .button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        });
        $('#right-panel').load('rtfscan.php', function () {
            rtfscan.init();
        });
        $('#tools-left').click(function (e) {
            e.preventDefault();
            var $t = $(e.target), ref = '', scrpt = '';
            if (!$t.hasClass('ui-button')) {
                $t = $t.parent();
            }
            if ($t.attr('id') === 'settingslink') {
                ref = 'settings.php';
                scrpt = 'settings';
            } else
            if ($t.attr('id') === 'rtfscanlink') {
                ref = 'rtfscan.php';
                scrpt = 'rtfscan';
            } else
            if ($t.attr('id') === 'detailslink') {
                ref = 'details.php';
                scrpt = 'details';
            } else
            if ($t.attr('id') === 'fontslink') {
                ref = 'fonts.php';
                scrpt = 'fonts';
            } else
            if ($t.attr('id') === 'userslink') {
                ref = 'users.php';
                scrpt = 'users';
            } else
            if ($t.attr('id') === 'synclink') {
                ref = 'synchronize.php';
                scrpt = 'synchronize';
            } else
            if ($t.attr('id') === 'backuplink') {
                ref = 'backup.php';
                scrpt = 'backup';
            } else
            if ($t.attr('id') === 'duplicateslink') {
                ref = 'selectduplicate.php';
                scrpt = 'selectduplicate';
            } else
            if ($t.attr('id') === 'renamejournallink') {
                ref = 'rename_journal.php';
                scrpt = 'rename_journal';
            } else
            if ($t.attr('id') === 'renamecategorylink') {
                ref = 'rename_category.php';
                scrpt = 'rename_category';
            } else
            if ($t.attr('id') === 'citationstyleslink') {
                ref = 'citationstyles.php';
                scrpt = '';
            } else
            if ($t.attr('id') === 'reindexlink') {
                ref = 'batchreindex.php?intro=1';
                scrpt = 'batchreindex';
            } else
            if ($t.attr('id') === 'aboutlink') {
                ref = 'about.php';
                scrpt = '';
            }
            if ($t.hasClass('ui-button')) {
                $(this).blur();
                $('#right-panel').load(ref, function () {
                    if (typeof window[scrpt] === 'object')
                        window[scrpt].init();
                });
            }
        });
    }
};
var addarticle = {
    init: function (load) {
        if (load) {
            $('#addarticle-right').load('upload.php', function () {
                upload.init();
            });
        }
        $('#addarticle-left').find('button').each(function(){
            $(this).button()
                .button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        });
        $('#addarticle-left table').click(function () {
            $('.saved-search, .flagged-items, .select-import').removeClass('clicked');
        });
        $(".select-import").click(function () {
            $(".select-import, #addarticle-left span").removeClass('clicked');
            $(this).addClass('clicked');
        });
        // bind links
        $('#addarticle-left').click(function (e) {
            e.preventDefault();
            var $t = $(e.target), ref = '', scrpt = '', assoc = {
                uploadlink: 'upload',
                importlink: 'importmetadata',
                batchimportlink: 'selectimport',
                importlocalhost: 'batchimport',
                pubmedlink: 'download_pubmed',
                pmclink: 'download_pmc',
                nasalink: 'download_nasa',
                arxivlink: 'download_arxiv',
                highwirelink: 'download_highwire',
                ieeelink: 'download_ieee',
                springerlink: 'download_springer'};
            $.each(assoc, function (key, val) {
                if ($t.hasClass('ui-button-text')) {
                    $t = $t.parent();
                }
                if ($t.attr('id') === key) {
                    ref = val + '.php';
                    scrpt = val;
                    return false;
                }
            });
            if ($t.attr('id') === 'importany') {
                $(this).blur();
                $.getScript('wpad.php', function () {
                    var proxystr = FindProxyForURL('', 'www.crossref.org');
                    $('#addarticle-right').load('remoteuploader.php?proxystr=' + encodeURIComponent(proxystr), function () {
                        remoteuploader.init();
                    });
                });
            } else if ($t.hasClass('ui-button') || $t.hasClass('select-import')) {
                $(this).blur();
                $('#addarticle-right').load(ref, function () {
                    if (typeof window[scrpt] === 'object')
                        window[scrpt].init();
                });
            }
        });
        // bind flagged item links
        $(".flagged-items").click(function () {
            timeId = setTimeout(dooverlay, 1000);
            var $this = $(this);
            $('.saved-search, .flagged-items, .select-import').removeClass('clicked');
            $this.addClass('clicked');
            var db, pg, pg2, pg3, scrpt;
            if ($this.hasClass('pubmed')) {
                db = 'pubmed', pg = 'download_pubmed.php',
                        pg2 = 'download_pubmed.php?tagged_query=',
                        pg3 = '[PMID]', scrpt = 'download_pubmed';
            } else
            if ($this.hasClass('pmc')) {
                db = 'pmc', pg = 'download_pmc.php',
                        pg2 = 'download_pmc.php?pmc_tagged_query=',
                        pg3 = '[UID]', scrpt = 'download_pmc';
            } else
            if ($this.hasClass('nasaads')) {
                db = 'nasaads', pg = 'download_nasa.php',
                        pg2 = 'download_nasa.php?nasa_bibcode=',
                        pg3 = '', scrpt = 'download_nasa';
            } else
            if ($this.hasClass('arxiv')) {
                db = 'arxiv', pg = 'download_arxiv.php',
                        pg2 = 'download_arxiv.php?arxiv_selection7=id&arxiv_query7=',
                        pg3 = '', scrpt = 'download_arxiv';
            }
            $.getJSON('flagged.php?database=' + db, function (answer) {
                if (answer.length === 0) {
                    $.jGrowl('No flagged items for this database');
                    $('#addarticle-right').load(pg, function () {
                        window[scrpt].init();
                        clearoverlay();
                    });
                } else {
                    var uids = answer.toString();
                    $('#addarticle-right').load(pg2 + uids + pg3, function () {
                        window[scrpt].init();
                        clearoverlay();
                    });
                }
            });
        });
        // bind empty flagged item links
        $('.empty-flagged').click(function () {
            var $t = $(this);
            $('#dialog-confirm').html('<p><i class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;margin:2px 7px 2em 0px"></i> '
                    + 'Do you want to empty the list of flagged items?</p>')
                    .dialog({
                        width: 'auto',
                        height: 'auto',
                        title: 'Empty flagged item list?'
                    })
                    .dialog('option', 'buttons', {
                        'Yes': function () {
                            var $d = $(this), db, scrpt;
                            if ($t.hasClass('pubmed')) {
                                db = 'pubmed', scrpt = 'download_pubmed';
                            } else
                            if ($t.hasClass('pmc')) {
                                db = 'pmc', scrpt = 'download_pmc';
                            } else
                            if ($t.hasClass('nasaads')) {
                                db = 'nasaads', scrpt = 'download_nasa';
                            } else
                            if ($t.hasClass('arxiv')) {
                                db = 'arxiv', scrpt = 'download_arxiv';
                            }
                            $.get('flagged.php?empty=1&database=' + db, function () {
                                $('#' + db + '-flagged-count').html('0');
                                $('#addarticle-right').load(scrpt + '.php', function () {
                                    window[scrpt].init();
                                });
                                $d.dialog('close');
                            });
                        },
                        'No': function () {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
        // bind saved search links
        $('.saved-search').click(function () {
            $('.saved-search, .flagged-items, .select-import').removeClass('clicked');
            $(this).addClass('clicked');
            var scrpt, srch;
            if ($(this).hasClass('pubmed')) {
                srch = $(this).attr('id').substr(20), scrpt = 'download_pubmed';
            } else
            if ($(this).hasClass('pmc')) {
                srch = $(this).attr('id').substr(17), scrpt = 'download_pmc';
            } else
            if ($(this).hasClass('nasaads')) {
                srch = $(this).attr('id').substr(21), scrpt = 'download_nasa';
            } else
            if ($(this).hasClass('arxiv')) {
                srch = $(this).attr('id').substr(19), scrpt = 'download_arxiv';
            } else
            if ($(this).hasClass('highwire')) {
                srch = $(this).attr('id').substr(22), scrpt = 'download_highwire';
            } else
            if ($(this).hasClass('ieee')) {
                srch = $(this).attr('id').substr(18), scrpt = 'download_ieee';
            } else
            if ($(this).hasClass('springer')) {
                srch = $(this).attr('id').substr(22), scrpt = 'download_springer';
            }
            $('#addarticle-right').load(scrpt + '.php?load=1&saved_search=' + srch, function () {
                window[scrpt].init();
            });
        });
        // bind delete search links
        $('.del-saved-search').click(function () {
            var $t = $(this);
            $('#dialog-confirm').html('<p><i class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;margin:2px 7px 2em 0px"></i> '
                    + 'Do you want to permanently delete this search?</p>')
                    .dialog({
                        width: 'auto',
                        height: 'auto',
                        title: 'Delete search?'
                    })
                    .dialog('option', 'buttons', {
                        'Yes': function () {
                            var $d = $(this), srch, scrpt;
                            if ($t.hasClass('pubmed')) {
                                srch = $t.next().attr('id').substr(20), scrpt = 'download_pubmed';
                            } else
                            if ($t.hasClass('pmc')) {
                                srch = $t.next().attr('id').substr(17), scrpt = 'download_pmc';
                            } else
                            if ($t.hasClass('nasaads')) {
                                srch = $t.next().attr('id').substr(21), scrpt = 'download_nasa';
                            } else
                            if ($t.hasClass('arxiv')) {
                                srch = $t.next().attr('id').substr(19), scrpt = 'download_arxiv';
                            } else
                            if ($t.hasClass('highwire')) {
                                srch = $t.next().attr('id').substr(22), scrpt = 'download_highwire';
                            } else
                            if ($t.hasClass('ieee')) {
                                srch = $t.next().attr('id').substr(18), scrpt = 'download_ieee';
                            } else
                            if ($t.hasClass('springer')) {
                                srch = $t.next().attr('id').substr(22), scrpt = 'download_springer';
                            }
                            $.get(scrpt + '.php?delete=1&saved_search=' + srch, function () {
                                $t.parent().next('div').addBack().remove();
                                $('#addarticle-right').load(scrpt + '.php', function () {
                                    window[scrpt].init();
                                });
                                $d.dialog('close');
                            });
                        },
                        'No': function () {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
    }
};
var fonts = {
    init: function () {
        $(":submit").button();
        $('form').ajaxForm(function () {
            var newurl = top.location.href.split('?').shift();
            top.location.assign(newurl);
        });
        $('input[name="main_window_link_color"]').keyup(function () {
            var col = $(this).val();
            if (col.length === 6)
                $("#lorem-link").css('color', '#' + col);
        });
        $('input[name="main_window_title_font_family"]').keyup(function () {
            var ff = $(this).val();
            if (ff.length > 3)
                $("#lorem-title").css('font-family', ff);
        });
        $('input[name="main_window_title_font_size"]').keyup(function () {
            var fs = $(this).val();
            if (fs > 10 && fs < 19)
                $("#lorem-title").css('font-size', fs + 'px');
        });
        $('input[name="main_window_font_size"]').keyup(function () {
            var fs = $(this).val();
            if (fs > 10 && fs < 15)
                $("#lorem-text").css('font-size', fs + 'px');
        });
        $('input[name="main_window_font_family"]').keyup(function () {
            var ff = $(this).val();
            if (ff.length > 3)
                $("#lorem-text").css('font-family', ff);
        });
        $('input[name="main_window_color"]').keyup(function () {
            var col = $(this).val();
            if (col.length === 6)
                $("#lorem-text").css('color', '#' + col);
            if (col.length === 6)
                $("#lorem-title").css('color', '#' + col);
        });
        $('input[name="main_window_line_height"]').keyup(function () {
            var lh = $(this).val();
            if (lh >= 1 && lh <= 3)
                $("#lorem-text").css('line-height', lh);
        });
        $('input[name="main_window_abstract_font_family"]').keyup(function () {
            var ff = $(this).val();
            if (ff.length > 3)
                $("#lorem-abstract").css('font-family', ff);
        });
        $('input[name="main_window_abstract_font_size"]').keyup(function () {
            var fs = $(this).val();
            if (fs > 10 && fs < 15)
                $("#lorem-abstract").css('font-size', fs + 'px');
        });
        $('input[name="main_window_abstract_line_height"]').keyup(function () {
            var lh = $(this).val();
            if (lh >= 1 && lh <= 3)
                $("#lorem-abstract").css('line-height', lh);
        });
        $('input[name="main_window_background_color"]').keyup(function () {
            var col = $(this).val();
            if (col.length === 6)
                $("#lorem-abstract").css('background-color', '#' + col);
        });
        $('input[name="alternating_row_background_color"]').keyup(function () {
            var col = $(this).val();
            if (col.length === 6)
                $("#lorem-alternating-row").removeClass('alternating_row').css('background-color', '#' + col);
        });
        $('.input-number').css('border', 'none').spinner();
    }
};
var importmetadata = {
    init: function () {
        common.init();
        $("#importbutton").click(function (e) {
            e.preventDefault();
            if ($("#importform input:file").val() === '' && $("#importform textarea").val() === '') {
                $.jGrowl('There are no metadata to import.');
                return false;
            }
            if ($("#importform input[type=radio]:checked").length > 0) {
                $.jGrowl('Import has started. Library may become unresponsive during this process.');
                $('#importform').ajaxSubmit(function (answer) {
                    if (answer === '')
                        return false;
                    if (answer.substr(0, 5) === 'Error') {
                        $.jGrowl(answer, {
                            theme: 'jgrowl-error'
                        });
                    } else {
                        $.jGrowl(answer);
                    }
                    $("#importform input:file").val('');
                });
            } else {
                $.jGrowl('Select the metadata format.');
            }
        }).button();
        $("#filtercategories").keyup(function () {
            var str = $(this).val(), $container = $('.categorydiv').find(':checkbox').closest('tr');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $container.hide().filter(function () {
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function () {
            $(this).val('');
            $(":checkbox").closest('tr').show();
        });
    }
};
var batchimport = {
    init: function () {
        common.init();
        $('.batch-errors').each(function () {
            var err = $(this).html();
            if (err)
                $.jGrowl(err, {
                    life: 10000,
                    theme: 'jgrowl-error'
                });
        });
        $('#batchimportbutton').click(function (e) {
            e.preventDefault();
            var dir = $('#batchimportform input[name=directory]').val();
            if (dir === '') {
                $.jGrowl('Enter a directory name first.');
                return false;
            }
            $.get("batchimport.php", {
                directory: dir,
                check_dir: 1
            }, function (answer) {
                if (answer === '1') {
                    var proxystr, queryString = $('#batchimportform').formSerialize();
                    $.getScript('wpad.php', function () {
                        proxystr = FindProxyForURL('', 'www.crossref.org');
                        queryString = queryString + '&proxystr=' + encodeURIComponent(proxystr);
                        $('#addarticle-right').load('batchimport.php?' + queryString, function () {
                            batchimport.init();
                        });
                    });
                }
                if (answer === '0') {
                    $('#batchimportform input:text').val('');
                    $.jGrowl('Error! Directory does not exist, <br> or is not readable.', {
                        theme: 'jgrowl-error'
                    });
                }
            });
        });
        $('#batchimportform').submit(function (e) {
            e.preventDefault();
            $('#batchimportbutton').click();
        });
        $("#batchimportbutton, #batchimportbutton2").button();
        $("#open1").click(function () {
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $("#table1").show();
            $("#table2").hide();
        });
        $("#open2").click(function () {
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $("#table1").hide();
            $("#table2").show();
        });
        $("#batchimportbutton2").click(function (e) {
            e.preventDefault();
            if ($('#batchimportform2 input[name="database_pubmed"]:checked').is(':checked')
                    || $('#batchimportform2 input[name="database_nasaads"]:checked').is(':checked')
                    || $('#batchimportform2 input[name="database_crossref"]:checked').is(':checked')) {
                if ($('#batchimportform2 input[name="log"]:checked').val() === '2') {
                    var pollID;
                    function pollLog() {
                        var username = $('#username-span').text();
                        $.get('ajaxlog.php?user=' + username, function (prog) {
                            if (prog !== '') {
                                $('#log-output').html(prog);
                            }
                            pollID = setTimeout(pollLog, 500);
                        });
                    }
                    $('#dialog-confirm').html('<div id="log-output"></div>')
                            .dialog('option', 'buttons', {
                                'Close': function () {
                                    clearTimeout(pollID);
                                    $(this).dialog('close');
                                }
                            }).dialog({
                        width: 600,
                        height: 400,
                        title: 'Progress'
                    }).dialog('open');
                    pollLog();
                } else {
                    $.jGrowl('Batch Import is in progress.');
                }
                var myquery = $('#batchimportform2').serialize();
                $.get('batchimport.php', myquery, function (answer) {
                    if ($('#batchimportform2 input[name="log"]:checked').val() === '2') {
                        clearTimeout(pollID);
                    }
                    if (answer.substring(0, 5) === 'Error') {
                        $.jGrowl(answer, {
                            theme: 'jgrowl-error',
                            life: 6000
                        });
                    } else {
                        $.jGrowl('Batch Import has finished.');
                    }
                    if ($('#batchimportform2').length === 1)
                        $('#addarticle-right').load('batchimport.php', function () {
                            batchimport.init();
                        });
                });
            } else {
                $.jGrowl('Select internet database.');
            }
        });
        $('.open-dirs-button').click(function (e) {
            e.preventDefault();
            $.get('browsedirs.php', function (answer) {
                $('#open-dirs').html(answer).dialog('open');
                browsedirs.init();
            });
        }).button();
        $("#filtercategories").keyup(function () {
            var str = $(this).val(), $container = $('.categorydiv').find(':checkbox').closest('tr');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $container.hide().filter(function () {
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function () {
            $(this).val('');
            $(":checkbox").closest('tr').show();
        });
    }
};
var remoteuploader = {
    init: function () {
        common.init();
        var fd, uploader = new plupload.Uploader({
            runtimes: 'html5,flash',
            urlstream_upload: true,
            multiple_queues: true,
            browse_button: 'select-button',
            container: 'uploaderOverlay',
            max_file_size: '200mb',
            url: 'remoteuploader.php',
            file_data_name: 'Filedata',
            flash_swf_url: 'js/plupload/plupload.flash.swf'
        });
        $("#select-button").button().click(function (e) {
            e.preventDefault();
        });
        $("#import-button").click(function (e) {
            e.preventDefault();
            if (!$('#batchimportform2 input[name="database_pubmed"]:checked').is(':checked')
                    && !$('#batchimportform2 input[name="database_nasaads"]:checked').is(':checked')
                    && !$('#batchimportform2 input[name="database_crossref"]:checked').is(':checked')) {
                $.jGrowl('Select internet database.');
                return false;
            }
            dooverlay();
            $.jGrowl('Batch import is in progress.');
            uploader.start();
        }).button();
        uploader.init();
        uploader.bind('FilesAdded', function (up, files) {
            $('#file-count').text(uploader.files.length);
            $("#import-button").button('enable');
            up.refresh(); // Reposition Flash/Silverlight
        });
        uploader.bind('Error', function (up, err) {
            $.jGrowl(err.message + (err.file ? "<br>File: " + err.file.name : ""),
                    {
                        theme: 'jgrowl-error'
                    });
            up.refresh(); // Reposition Flash/Silverlight
        });
        uploader.bind('BeforeUpload', function (up) {
            fd = $('#batchimportform2').serialize();
            up.settings.url = 'remoteuploader.php?' + fd;
        });
        uploader.bind('FileUploaded', function (up, file, response) {
            $.jGrowl(file.name + '<br>' + response.response);
        });
        uploader.bind('UploadComplete', function (up) {
            $('#file-count').text('0');
            $("#import-button").button('disable');
            up.splice();
            clearoverlay();
        });
        $("#filtercategories").keyup(function () {
            var str = $(this).val(), $container = $('.categorydiv').find(':checkbox').closest('tr');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $container.hide().filter(function () {
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function () {
            $(this).val('');
            $(":checkbox").closest('tr').show();
        });
    }
};
var selectimport = {
    init: function () {
        $("#select-localhost").click(function () {
            $('.select-import').removeClass('clicked');
            $("#import-localhost").addClass('clicked');
            $('#addarticle-right').load('batchimport.php', function () {
                batchimport.init();
            });
        });
        $("#select-remote").click(function () {
            $('.select-import').removeClass('clicked');
            $("#import-any").addClass('clicked');
            $.getScript('wpad.php', function () {
                var proxystr = FindProxyForURL('', 'www.crossref.org');
                $('#addarticle-right').load('remoteuploader.php?proxystr=' + encodeURIComponent(proxystr), function () {
                    remoteuploader.init();
                });
            });
        });
    }
};
var synchronize = {
    init: function () {
        $("#right-panel input:submit").button();
        jQuery("<img>").attr("src", "img/ajaxloader.gif");
        $('#form-synchronize').ajaxForm({
            beforeSubmit: function (arr) {
                if (arr[0]['value'] === '')
                    return false;
                dooverlay();
            },
            success: function (answer) {
                clearoverlay();
                if (answer.substring(0, 5) === 'Error') {
                    $.jGrowl(answer, {
                        theme: 'jgrowl-error'
                    });
                } else {
                    $.jGrowl('Copying is complete.');
                }
            }
        });
        $('#right-panel .open-dirs-button').click(function (e) {
            e.preventDefault();
            $.get('browsedirs.php', function (answer) {
                $('#open-dirs').html(answer).dialog('open');
                browsedirs.init();
            });
        }).button();
    }
};
var backup = {
    init: function () {
        $("input:submit").button();
        jQuery("<img>").attr("src", "img/ajaxloader.gif");
        $('.form-backup').ajaxForm({
            beforeSubmit: function (arr) {
                if (arr[1]['value'] === '')
                    return false;
                dooverlay();
            },
            success: function (answer) {
                clearoverlay();
                if (answer.substring(0, 5) === 'Error') {
                    $.jGrowl(answer, {
                        theme: 'jgrowl-error'
                    });
                } else {
                    $.jGrowl('Copying is complete.');
                }
            }
        });
        $('.open-dirs-button').click(function (e) {
            e.preventDefault();
            $.get('browsedirs.php', function (answer) {
                $('#open-dirs').html(answer).dialog('open');
                browsedirs.init();
            });
        }).button();
        $('#select-backup').click(function () {
            dooverlay();
            $('#right-panel').load('backup.php?backup=1', function () {
                backup.init();
                clearoverlay();
            });
        });
        $('#select-restore').click(function () {
            if ($(this).hasClass('ui-state-disabled'))
                return false;
            $('#right-panel').load('backup.php?restore=1', function () {
                backup.init();
            });
        });
        $('#unlock-restore').click(function () {
            $('#select-restore').removeClass('ui-state-disabled');
        }).tipsy({
            gravity: 's'
        });
    }
};
var users = {
    init: function () {
        $("input:submit").button();
        $('form').ajaxForm({
            beforeSubmit: function (arr) {
                if (arr[0]['value'] === '')
                    return false;
            },
            success: function (answer) {
                if (answer.substring(0, 5) === 'Error') {
                    $.jGrowl(answer, {theme: 'jgrowl-error'});
                } else {
                    $('#right-panel').load('users.php', function () {
                        users.init();
                        $.jGrowl('Changes saved.');
                    });
                }
            }
        });
        $("#delete-confirm").dialog({
            autoOpen: false,
            resizable: false,
            buttons: {
                'Delete User': function () {
                    var myform = $(this).data('myForm'), $this = $(this);
                    $(myform).ajaxSubmit(function () {
                        $this.dialog('close');
                        $('#right-panel').load('users.php', function () {
                            users.init();
                        });
                    });
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
        $("#right-panel .deletebutton").click(function () {
            var myForm = $(this).closest('form');
            $("#delete-confirm").html('<p><span class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;margin:2px 7px 2em 0px"></span> This user will be permanently deleted. Are you sure?</p>')
                    .data('myForm', myForm).dialog('open');
            return false;
        });
    }
};
var selectduplicate = {
    init: function () {
        $("#duplicate-similar").click(function () {
            dooverlay();
            $('#right-panel').load('duplicates.php?find_duplicates=similar', function () {
                clearoverlay();
                duplicates.init();
            });
        });
        $("#duplicate-identical").click(function () {
            dooverlay();
            $('#right-panel').load('duplicates.php?find_duplicates=identical', function () {
                duplicates.init();
                clearoverlay();
            });
        });
        $("#duplicate-hash").click(function () {
            dooverlay();
            $('#right-panel').load('duplicates.php?find_duplicates=hash', function () {
                duplicates.init();
                clearoverlay();
            });
        });
    }
};
var duplicates = {
    init: function () {
        $("input:submit").button();
        $('form').ajaxForm({
            success: function (answer) {
                $('#right-panel').html(answer);
            }
        });
    }
};
var rename_journal = {
    init: function () {
        $("#right-panel input:submit").button();
        $('#edit-journal-list').unbind().click(function (e) {
            var $t = $(e.target), jour = $t.text();
            if (!$t.hasClass('journal-name'))
                return false;
            if ($t.next('div.journal-name-child').length === 1) {
                $t.next('div').remove();
                return false;
            }
            $t.after('<div class="journal-name-child"></div>');
            $t.next('div.journal-name-child').css('padding-left', '12px').html('<img src="img/ajaxloader.gif">')
                    .load('rename_journal.php?details=1&journal=' + encodeURIComponent(jour), function () {
                        $("#edit-journal-list button").button();
                        rename_journal.init();
                    });
        });
        $('#edit-secondary-title-list').unbind().click(function (e) {
            var $t = $(e.target), jour = $t.text();
            if (!$t.hasClass('journal-name'))
                return false;
            if ($t.next('div.journal-name-child').length === 1) {
                $t.next('div').remove();
                return false;
            }
            $t.after('<div class="journal-name-child"></div>');
            $t.next('div.journal-name-child').css('padding-left', '12px').html('<img src="img/ajaxloader.gif">')
                    .load('rename_journal.php?details=1&secondary_title=' + encodeURIComponent(jour), function () {
                        $("#edit-secondary-title-list button").button();
                        rename_journal.init();
                    });
        });
        $(".rename-journal-button").unbind().click(function () {
            if ($(this).next('span').is(':hidden')) {
                var oldj = $(this).next('span').text(),
                        newj = $(this).next().next(':text').val(),
                        parentstr = $(this).parent('div').prev('div').text(), parenttype;
                if ($(this).parent('div').parent('div').is('#edit-journal-list'))
                    parenttype = 'parent_journal';
                if ($(this).parent('div').parent('div').is('#edit-secondary-title-list'))
                    parenttype = 'parent_secondary_title';
                var formdata = {
                    'new_journal': newj,
                    'old_journal': oldj,
                    'change_journal': 1
                };
                formdata[parenttype] = parentstr;
                $(this).parent('div').css('padding-left', '12px').html('<img src="img/ajaxloader.gif">');
                $('#rename-journal-form').ajaxSubmit({
                    data: formdata,
                    success: function () {
                        $('#right-panel').load('rename_journal.php', function () {
                            rename_journal.init();
                        });
                    }
                });
                $(this).next('span').show().next('input').remove();
            } else {
                var jour = $(this).next('span').text();
                $(this).next('span').hide().after('<input type="text" value="' + jour + '" style="width:70%">');
                rename_journal.init();
            }
        });
        $(".rename-secondary-title-button").unbind().click(function () {
            if ($(this).next('span').is(':hidden')) {
                var oldj = $(this).next('span').text(),
                        newj = $(this).next().next(':text').val(),
                        parentstr = $(this).parent('div').prev('div').text(), parenttype;
                if ($(this).parent('div').parent('div').is('#edit-journal-list'))
                    parenttype = 'parent_journal';
                if ($(this).parent('div').parent('div').is('#edit-secondary-title-list'))
                    parenttype = 'parent_secondary_title';
                var formdata = {
                    'new_secondary_title': newj,
                    'old_secondary_title': oldj,
                    'change_journal': 1
                };
                formdata[parenttype] = parentstr;
                $(this).parent('div').css('padding-left', '12px').html('<img src="img/ajaxloader.gif">');
                $('#rename-journal-form').ajaxSubmit({
                    data: formdata,
                    success: function () {
                        $('#right-panel').load('rename_journal.php', function () {
                            rename_journal.init();
                        });
                    }
                });
                $(this).next('span').show().next('input').remove();
            } else {
                var jour = $(this).next('span').text();
                $(this).next('span').hide().after('<input type="text" value="' + jour + '" style="width:70%">');
                rename_journal.init();
            }
        });
        $('#edit-journal-list input:text, #edit-secondary-title-list input:text').keydown(function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $(this).prev().prev('button').click();
            }
        });
    }
};
var rename_category = {
    init: function () {
        function renameCategory(catid, cat, f) {
            $.get('rename_category.php', {
                'change_category': 1,
                'old_category': catid,
                'new_category': cat
            }, function (answer) {
                if (answer === 'OK') {
                    if (typeof f === 'function')
                        f();
                    $.jGrowl('Category was renamed.');
                } else {
                    $.jGrowl(answer, {theme: 'jgrowl-error'});
                }
            });
        }
        function deleteCategory(catid, f) {
            $.get('rename_category.php', {
                'delete_category': 1,
                'old_category': catid
            }, function (answer) {
                if (answer === 'OK') {
                    if (typeof f === 'function')
                        f();
                    $.jGrowl('Category was deleted.');
                } else {
                    $.jGrowl(answer, {theme: 'jgrowl-error'});
                }
            });
        }
        $('#right-panel form').ajaxForm({
            success: function (answer) {
                if (answer !== '')
                    $.jGrowl(answer, {theme: 'jgrowl-error'});
                $('#right-panel').load('rename_category.php', function () {
                    rename_category.init();
                });
            }
        });
        $('#right-panel button').button();
        $('#right-panel').find('.editcategory').bind('keydown', function (e) {
            if (e.which === 13) {
                $(this).prev().click();
                return false;
            }
            if (e.which === 27) {
                $(this).val($(this).data('content')).blur();
            }
            ;
        }).click(function () {
            $('#right-panel').find('.editcategory');
            $(this).focus();
        });
        $('#right-panel').find('.renamebutton').click(function () {
            var $el = $(this).next(), catid = $el.data('id'),
                    origcat = $el.data('content'), cat = $.trim($el.val());
            $el.val(cat);
            $el.prop('contenteditable', true).focus();
            if (origcat !== cat) {
                renameCategory(catid, cat, function () {
                    $el.data('content', cat).blur();
                });
            }
        });
        $('#right-panel').find('.deletebutton').click(function () {
            var $el = $(this).next().next(), catid = $el.data('id'), cat = $.trim($el.val());
            $("#dialog-error").dialog();
            $("#dialog-error").html('<p><div class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;height:3em;margin:2px 7px 20px 0px"></div> Category "<strong>' + escapeHtml(cat.substring(0, 30)) + '</strong>" will be permanently deleted. Are you sure?</p>')
                    .dialog('option', 'title', 'Delete Category?').dialog("option", "buttons", {
                'Delete': function () {
                    deleteCategory(catid, function () {
                        $el.closest('tr').remove();
                    });
                    $(this).dialog('close');
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }).dialog('open');
        });
    }
};
var items = {
    init: function (selfile) {
        var lw = 233, sel = $('body').data('sel');
        if (localStorage.getItem('liwidth') !== undefined)
            lw = localStorage.getItem('liwidth');
        if (lw === '0') {
            $('#items-left').hide();
            $('#items-container .middle-panel i').removeClass('fa-caret-left').addClass('fa-caret-right');
        }
        $('#file-panel').height($('#items-right').height());
        $(window).resize(function () {
            $('#items-right iframe').height(0);
            $('#file-panel').height($('#items-right').height());
            $('#items-right iframe').height($('iframe').parent().height());
            $("#floating-notes").trigger('dragstop');
        });
        $("#items-container .middle-panel").click(function () {
            var $l = $(this).prev();
            if ($l.is(':hidden')) {
                $l.show();
                localStorage.setItem('liwidth', '233');
                $(this).children().removeClass('fa-caret-right').addClass('fa-caret-left');
            } else {
                $l.hide();
                localStorage.setItem('liwidth', '0');
                $(this).children().removeClass('fa-caret-left').addClass('fa-caret-right');
            }
            $(window).trigger('resize');
        });
        if (selfile === '' || selfile === undefined)
            selfile = $('#items-left > div.listleft:first').data('id');
        if (isNaN(selfile) || $('#list-item-' + selfile).length !== 1) {
            $.jGrowl('The item dos not exist.', {
                theme: 'jgrowl-error'
            });
            $('.backbutton').click();
            return false;
        }
        var tposition = $('#list-item-' + selfile).position();
        $("#items-left").scrollTop(tposition.top - $(window).height() / 3);
        $('#list-title-copy').text($('body').data('list-title'));
        $('#items-left').click(function (e) {
            var $target = $(e.target);
            if ($target.hasClass('listleft')) {
                var file = $target.data('id'), pdf = $target.data('file');
                $('#items-right').data('file', file).data('pdf', pdf);
                if ($('#items-container').data('tab') !== undefined) {
                    $('#' + $('#items-container').data('tab')).click();
                } else {
                    $('#file-item').click();
                }
                $.get('history.php?file=' + file);
                $target.siblings('div.items').removeClass('clicked');
                $target.addClass('clicked');
            }
        });
        $('#list-item-' + selfile).click();
        $("#nav-prev").button().button('widget').removeClass('ui-corner-all');
        $("#nav-next").button().button('widget').removeClass('ui-corner-all');
        $('#nav-next,#nav-prev').click(function () {
            var fileid = $(this).data('id');
            $('#items-container').load('items.php?file=' + fileid, function () {
                items.init(fileid);
            });
        });
        $("#items-container .items-nav").button();
        $("#items-container .items-nav").each(function () {
            $(this).button('widget').removeClass('ui-corner-all');
        });
        $('.prevrecord').click(function () {
            var file = $('#items-left > .clicked').prevAll('.listleft').first().data('id');
            if ($('#list-item-' + file).length < 1) {
                if ($('#nav-prev').length === 0)
                    return false;
                file = $('#nav-prev').data('id');
                $('#items-container').load('items.php', 'file=' + file, function () {
                    items.init(file);
                    return false;
                });
            }
            $('.listleft').removeClass('clicked');
            $('#list-item-' + file).click();
            var off = $('#list-item-' + file).offset(), curr = $('#items-left').scrollTop();
            if (off === undefined) {
                return;
            }
            if (off.top < 50 || off.top > $(window).height() - 50) {
                $('#items-left').stop().animate({
                    scrollTop: off.top + curr - $(window).height() / 3
                }, 1000);
            }
        }).tipsy({
            gravity: 'n'
        });
        $('.nextrecord').click(function () {
            var file = $('#items-left > .clicked').nextAll('.listleft').first().data('id');
            if ($('#list-item-' + file).length < 1) {
                if ($('#nav-next').length === 0)
                    return false;
                file = $('#nav-next').data('id');
                $('#items-container').load('items.php', 'file=' + file, function () {
                    items.init(file);
                    return false;
                });
            }
            $('.listleft').removeClass('clicked');
            $('#list-item-' + file).click();
            var off = $('#list-item-' + file).offset(), curr = $('#items-left').scrollTop();
            if (off === undefined) {
                return;
            }
            if (off.top < 50 || off.top > $(window).height() - 50) {
                $('#items-left').stop().animate({
                    scrollTop: off.top + curr - $(window).height() / 3
                }, 1000);
            }
        }).tipsy({
            gravity: 'n'
        });
        $('.backbutton').click(function () {
            $(this).mouseout();
            $('#items-container').hide().empty();
            $('#top-panel, #bottom-panel').show();
            var iconswidth = $('#icon-container').width(),
                    iconnum = Math.floor(iconswidth / 364),
                    icmarg = Math.max(4, (iconswidth - iconnum * 360) / (iconnum + 1));
            $('#icon-container').find('.thumb-items').css('margin-left', icmarg);
            //RE-BIND BOTTOM LAYER HOTKEYS
            $(document).unbind('keydown').bind('keydown', 'd', function () {
                if ($('.nextpage').is(':visible'))
                    $('.nextpage').click();
            }).bind('keydown', 'a', function () {
                if ($('.prevpage').is(':visible'))
                    $('.prevpage').click();
            });
        }).tipsy({
            gravity: 'nw'
        });
        $("#delete-file").dialog({
            autoOpen: false,
            buttons: {
                'Delete Record': function () {
                    var file = $('#items-right').data('file');
                    $.get('items.php?delete=1&file=' + file, function (answer) {
                        if (answer !== '') {
                            $.jGrowl(answer, {
                                theme: 'jgrowl-error'
                            });
                            return false;
                        }
                        var ref = $('body').data('right-panel-url');
                        $('#right-panel').load(ref, function () {
                            displaywindow.init(sel, ref);
                            var newfile;
                            if ($('#items-left').find('.listleft').length === 1) {
                                $('.backbutton').click();
                            } else {
                                newfile = $('#items-left > .clicked').nextAll('.listleft').first().data('id');
                                if ($('#list-item-' + newfile).length < 1) {
                                    newfile = $('#nav-next').data('id');
                                    if (!newfile) {
                                        newfile = $('#items-left > .clicked').prevAll('.listleft').first().data('id');
                                        if ($('#list-item-' + newfile).length < 1)
                                            newfile = $('#nav-prev').data('id');
                                    }
                                }
                                $('#items-container').load('items.php', 'file=' + newfile, function () {
                                    items.init(newfile);
                                });
                            }
                        });
                    });
                    $(this).dialog('close');
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            },
            open: function () {
                $("#pdf-div").css('visibility', 'hidden');
            },
            close: function () {
                $("#pdf-div").css('visibility', '');
            }
        });
        $('#items-menu').find('.tab').click(function () {
            $(this).siblings('.tab').removeClass('tabclicked');
            $(this).addClass('tabclicked');
        });
        $("#deletebutton").click(function () {
            $("#delete-file").html('<p><div class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;margin:2px 6px 2em 0"></div> This record and all associated files will be permanently deleted. Are you sure?</p>')
                    .dialog('open');
        }).tipsy({
            gravity: 'w'
        });
        $("#printbutton").click(function () {
            if ($('#file-pdf').hasClass('tabclicked')) {
                $.jGrowl('PDFs cannot be printed from here.');
                return false;
            }
            window.print();
        });
        var menudelay3, enterdelay3;
        $('#file-item').click(function () {
            clearTimeout(enterdelay3);
            $('#items-item-menu').hide();
            var file = $('#items-right').data('file');
            $('#items-container').data('tab', 'file-item');
            $('#file-panel').load('file_top.php?file=' + file, function () {
                filetop.init(sel);
            });
        }).mouseenter(function () {
            clearTimeout(menudelay3);
            var offset = $('#file-item').offset();
            $('#items-item-menu').css('top', offset.top).css('left', offset.left + $('#file-item').outerWidth() + 1);
            enterdelay3 = setTimeout(function () {
                $('#items-item-menu').show();
            }, 250);
        }).mouseleave(function () {
            clearTimeout(enterdelay3);
            menudelay3 = setTimeout(function () {
                $('#items-item-menu').hide()
            }, 100);
        });
        $('#items-item-menu').mouseenter(function () {
            clearTimeout(menudelay3);
        }).mouseleave(function () {
            menudelay3 = setTimeout(function () {
                $('#items-item-menu').hide();
            }, 100);
        }).click(function () {
            var file = $('#items-right').data('file');
            $('#items-item-menu').hide();
            window.open('stable.php?id=' + file);
        });
        var menudelay, enterdelay;
        $('#file-pdf').click(function () {
            clearTimeout(enterdelay);
            $('#items-pdf-menu').hide();
            var filename = $('#items-right').data('pdf'),
                    mode = $(this).data('mode');
            $('#items-container').data('tab', 'file-pdf');
            if (mode === 'internal')
                $('#file-panel').load('pdfviewer.php?inline=1&file=' + filename);
            if (mode === 'external') {
                $('#file-panel').html('<object id="pdf-div" type="application/pdf" typemustmatch="true" data="'
                        + 'pdfcontroller.php?downloadpdf=1&file='
                        + filename
                        + '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0&zoom=page-width'
                        + '"><div style="text-align:center;padding-top:270px;color:#b6b8bc;font-size:36px">No PDF</div></object>');
            }
        }).mouseenter(function () {
            clearTimeout(menudelay);
            var offset = $('#file-pdf').offset();
            $('#items-pdf-menu').css('top', offset.top).css('left', offset.left + $('#file-pdf').outerWidth() + 1);
            enterdelay = setTimeout(function () {
                $('#items-pdf-menu').show()
            }, 250);
        }).mouseleave(function () {
            clearTimeout(enterdelay);
            menudelay = setTimeout(function () {
                $('#items-pdf-menu').hide();
            }, 100);
        });
        $('#items-pdf-menu').mouseenter(function () {
            clearTimeout(menudelay);
        }).mouseleave(function () {
            menudelay = setTimeout(function () {
                $('#items-pdf-menu').hide()
            }, 100);
        });
        $('#file-edit').click(function () {
            var file = $('#items-right').data('file');
            $('#items-container').data('tab', 'file-edit');
            $('#file-panel').load('edit.php?file=' + file, function () {
                edit.init();
            });
        });
        $('#items-pdf-menu-a').click(function () {
            var filename = $('#items-left').find('.clicked').data('file'), title = $('#items-left').find('.clicked').text();
            var mode = $(this).data('mode');
            if (mode === 'internal')
                window.open('pdfviewer.php?file=' + filename + '&title=' + title);
            if (mode === 'external')
                window.open('pdfcontroller.php?downloadpdf=1&file=' + filename + '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0&zoom=page-width');
        });
        $('#items-pdf-menu-b').click(function () {
            var filename = $('#items-left').find('.clicked').data('file');
            window.location.assign('pdfcontroller.php?downloadpdf=1&mode=download&file=' + filename);
        });
        var menudelay2, enterdelay2;
        $('#file-notes').click(function () {
            clearTimeout(enterdelay2);
            $('#items-notes-menu').hide();
            var file = $('#items-right').data('file');
            $('#items-container').data('tab', 'file-notes');
            $('#file-panel').load('notes.php?file=' + file);
        }).mouseenter(function () {
            clearTimeout(menudelay2);
            var offset = $('#file-notes').offset();
            $('#items-notes-menu').css('top', offset.top).css('left', offset.left + $('#file-notes').outerWidth() + 1);
            enterdelay2 = setTimeout(function () {
                $('#items-notes-menu').show()
            }, 250);
        }).mouseleave(function () {
            clearTimeout(enterdelay2);
            menudelay2 = setTimeout(function () {
                $('#items-notes-menu').hide()
            }, 100);
        });
        $('#items-notes-menu').mouseenter(function () {
            clearTimeout(menudelay2);
        }).mouseleave(function () {
            menudelay2 = setTimeout(function () {
                $('#items-notes-menu').hide()
            }, 100);
        }).click(function () {
            var file = $('#items-right').data('file');
            $('#items-notes-menu').hide();
            // if open, close open notes safely
            if ($('#floating-notes > .ui-widget-header > .fa-times-circle').length === 1)
                $('#floating-notes > .ui-widget-header > .fa-times-circle').click();
            $('#floating-notes').children('div').eq('1').load('notes.php?editnotes=1&file=' + file, function () {
                notes.init();
            });
        });
        $('#file-categories').click(function () {
            var file = $('#items-right').data('file');
            $('#items-container').data('tab', 'file-categories');
            $('#file-panel').load('categories.php?file=' + file, function () {
                categories.init();
            });
        });
        $('#file-files').click(function () {
            var file = $('#items-right').data('file');
            $('#items-container').data('tab', 'file-files');
            $('#file-panel').load('files.php?file=' + file, function () {
                filemanager.init();
            });
        });
        $('#file-discussion').click(function () {
            var file = $('#items-right').data('file');
            $('#items-container').data('tab', 'file-discussion');
            $('#file-panel').load('file_discussion.php?file=' + file, function () {
                filediscussion.init();
            });
        });
        $('#exportfilebutton').click(function () {
            var file = $('#items-right').data('file');
            if ($('#exportdialog').html() === '') {
                $('#exportdialog').load('export.php?export_files=' + file, function () {
                    $(this).dialog('option', 'title', 'Export').dialog('open');
                    common.init();
                    exportitems.init();
                    $('#selectall').click(function () {
                        $('input[name="column\\[\\]"]').prop('checked', true);
                        $('input[name="column\\[\\]"]').next().removeClass('fa-square-o').addClass('fa-check-square');
                    });
                    $('#unselectall').click(function () {
                        $('input[name="column\\[\\]"]').prop('checked', false);
                        $('input[name="column\\[\\]"]').next().removeClass('fa-check-square').addClass('fa-square-o');
                    });
                });
            } else {
                $('#exportform').find('input[name="export_files"]').val(file);
                $('#exportdialog').dialog('option', 'title', 'Export').dialog('open');
            }
        });
        $('#emailbutton').click(function (e) {
            e.preventDefault();
            var file = $('#items-right').data('file');
            $.get('ajaxemail.php?id=' + file, function (answer) {
                if (answer.substr(0, 5) === 'Error') {
                    $.jGrowl(answer, {theme: 'jgrowl-error'});
                    return false;
                }
                location.assign(answer);
            });
        });
        if ($('#items-container').data('tab') !== undefined) {
            $('#' + $('#items-container').data('tab')).click();
        } else {
            $('#file-item').click();
        }
        //HOTKEYS
        $(document).unbind('keydown').bind('keydown', 's', function () {
            $('.nextrecord').click();
        }).bind('keydown', 'w', function () {
            $('.prevrecord').click();
        }).bind('keydown', 'del', function () {
            if ($('#deletebutton').is(':visible'))
                $('#deletebutton').click();
        }).bind('keydown', 'q', function () {
            $('.backbutton').click();
        });
    }
};
var notes = {
    init: function () {
        // notes window is resizable and draggable
        $("#floating-notes").resizable({
            handles: "all",
            minWidth: 600,
            start: function () {
                $('#iframe-fix').show();
            },
            stop: function () {
                $('#iframe-fix').hide();
                localStorage.setItem('notes-height', $('#floating-notes').height());
            },
            resize: function () {
                $('.mce-edit-area, #notes_ifr').height($(this).height() - $('.mce-toolbar-grp').outerHeight() - $('#floating-notes > .ui-widget-header').outerHeight() - 18);
            }
        }).draggable({
            containment: 'body',
            handle: 'div.ui-widget-header'
        }).on('dragstop', function () {
            // On resize the dialog can be out of bounds! Ridiculous jQuery UI bug.
            if ($("#floating-notes").offset().top > $(window).height()) {
                $("#floating-notes").css('top', '10px');
            }
            if ($("#floating-notes").offset().left > $(window).width()) {
                $("#floating-notes").css('left', '10px');
            }
        });
        // copy title to notes window
        $('#floating-notes > .ui-widget-header > div').text($('#items-left').find('.clicked').text());
        // bind click handlers for utility buttons
        $('#floating-notes > .ui-widget-header > .fa-times-circle').unbind().click(function () {
            var ed = tinymce.get('notes');
            if (ed)
                ed.remove();
            $('#floating-notes').hide().children('div').eq('1').empty();
        });
        $('#floating-notes > .ui-widget-header > .fa-plus-circle').unbind().click(function () {
            var notesh = localStorage.getItem('notes-height');
            if (!notesh)
                notesh = 400;
            $('#floating-notes').height(notesh).resizable("enable");
        });
        $('#floating-notes > .ui-widget-header > .fa-minus-circle').unbind().click(function () {
            if ($('#floating-notes').height() > ($('#floating-notes > .ui-widget-header').outerHeight() + 10)) {
                localStorage.setItem('notes-height', $('#floating-notes').height());
                $('#floating-notes').height($('#floating-notes > .ui-widget-header').outerHeight()).resizable("disable");
            }
        });
        // always maximize new window
        $('#floating-notes > .ui-widget-header > .fa-plus-circle').click();
        // show notes
        $('#floating-notes').show();
        // get user set height, if exists
        var notesh = localStorage.getItem('notes-height');
        // default height
        if (!notesh)
            notesh = 400;
        // set proper height for TinyMCE
        var divh = notesh - $('#floating-notes > .ui-widget-header').outerHeight() - 116,
                file = $('#items-right').data('file');
        // initiate tinyMCE
        tinymce.init({
            selector: "#notes",
            body_id: "notes_ifr",
            content_css: "style.php",
            height: divh,
            menubar: false,
            statusbar: false,
            browser_spellcheck: true,
            plugins: ["save print table image advlist code link anchor charmap textcolor searchreplace"],
            toolbar1: "save print code | undo redo cut copy paste | formatselect fontsizeselect ",
            toolbar2: "bold italic underline strikethrough subscript superscript removeformat | alignleft aligncenter alignright alignjustify |  bullist numlist outdent indent",
            toolbar3: "forecolor backcolor | table | image | charmap | link unlink anchor | searchreplace",
            save_enablewhendirty: false,
            save_onsavecallback: savenotes,
            image_list: "imagelist.php?id=" + file,
            fontsize_formats: "12px 13px 14px 15px 16px 18px 20px 26px",
            invalid_elements: "script,iframe,embed,object",
            extended_valid_elements: "math,maction,maligngroup,malignmark,menclose,merror,mfenced,mfrac,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded"
                    + "mphantom,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msup,msubsup,mtable,mtd,mtext,mtr,munder,munderover"
        });
        function savenotes() {
            var sel = $('body').data('sel'), ref = $('body').data('right-panel-url');
            var ed = tinymce.get('notes');
            ed.setProgressState(1);
            $('#form-notes').ajaxSubmit(function () {
                ed.setProgressState(0);
                if ($('#items-container').data('tab') === 'file-notes')
                    $('#file-notes').click();
                if ($('#items-container').data('tab') === 'file-item')
                    $('#file-item').click();
                $('#right-panel').load(ref, function () {
                    displaywindow.init(sel, ref);
                });
            });
        }
    }
};
var exportitems = {
    init: function () {
        $('#citation-style').autocomplete({
            source: "ajaxstyles.php",
            minLength: 1,
            appendTo: "#exportdialog",
            position: {my: "left top", at: "left bottom"}
        });
        $('#citation-style').keydown(function () {
            $('input[name="format"][value="citations"]').parent().click();
            $('input[name="output"][value="inline"]').parent().click();
            $('#exportform').attr('target', 'exportwindow');
            $('#last-style-tr').find('input').prop('checked', false);
            $('#last-style-tr').find('i').removeClass('fa-check-square').addClass('fa-square-o');
        });
        if (localStorage.getItem('laststyle')) {
            $('#last-style-tr').find('span').text(localStorage.getItem('laststyle'));
            $('#last-style-tr').find('input').val(localStorage.getItem('laststyle'));
            $('#last-style-tr').show();
        }
        $('#last-style-tr > td').click(function () {
            $('input[name="format"][value="citations"]').parent().click();
            $('input[name="output"][value="inline"]').parent().click();
            $('#exportform').attr('target', 'exportwindow');
        });
        $('#selectall').click(function () {
            $('input[name="column\\[\\]"]').prop('checked', true);
            $('input[name="column\\[\\]"]').next().removeClass('fa-square-o').addClass('fa-check-square');
        });
        $('#unselectall').click(function () {
            $('input[name="column\\[\\]"]').prop('checked', false);
            $('input[name="column\\[\\]"]').next().removeClass('fa-check-square').addClass('fa-square-o');
        });
        $('#export-radio').buttonset();
    }
};
var categories = {
    init: function () {
        common.init();
        var file = $('#categoriesform input[name="file"]').val();
        $("#filtercategories").keyup(function () {
            var str = $(this).val(), $container = $(':checkbox').closest('tr');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $container.hide().filter(function () {
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function () {
            $(this).val('');
            $(":checkbox").closest('tr').show();
        });
        $("#suggestions").click(function (e) {
            var $t = $(e.target), $icon = '', catid = '';
            if ($t.is('i')) {
                $icon = $t;
                catid = $t.parent().data('catid');
            } else {
                $icon = $t.children('i');
                catid = $t.data('catid');
            }
            $('#file-panel td.select_span[data-catid=' + catid + ']').click();
            if ($icon.hasClass('fa-check-square')) {
                $icon.removeClass('fa-check-square').addClass('fa-square-o');
            } else {
                $icon.removeClass('fa-square-o').addClass('fa-check-square');
            }
        });
        $('#newcatbutton, #file-panel .categorieslist .select_span').click(function () {
            var $this = $(this), sel = $('body').data('sel'), ref = $('body').data('right-panel-url');
            $('#categoriesform').ajaxSubmit(function (answer) {
                if (answer.substr(0, 5) === 'Error') {
                    $.jGrowl(answer, {
                        theme: 'jgrowl-error'
                    });
                    $this.find('input').prop('checked', false);
                    $this.find('i').removeClass('fa-check-square').addClass('fa-square-o');
                }
                if ($this.attr('id') === 'newcatbutton') {
                    $('#file-panel').load('categories.php?file=' + file, function () {
                        categories.init();
                    });
                } else {
                    var catid = $this.data('catid');
                    if ($this.hasClass('alternating_row')) {
                        $('#suggestions > span[data-catid=' + catid + '] > i').removeClass('fa-square-o').addClass('fa-check-square');
                    } else {
                        $('#suggestions > span[data-catid=' + catid + '] > i').removeClass('fa-check-square').addClass('fa-square-o');
                    }
                }
                $('#right-panel').load(ref, function () {
                    displaywindow.init(sel, ref);
                });
            });
        });
        $('#categoriesform').submit(function () {
            return false;
        });
        $('#newcatbutton').button();
    }
};
var edit = {
    init: function () {
        common.init();
        authorinputs.init(false);
        editorinputs.init(false);
        var sel = $('body').data('sel'), ref = $('body').data('right-panel-url');
        $('#savemetadata').click(function (e) {
            e.preventDefault();
            var file = $('input[name="file"]').val();
            $('#metadataform').ajaxSubmit({
                success: function (answer) {
                    if (answer.substring(0, 5) === 'Error') {
                        $.jGrowl(answer, {
                            theme: 'jgrowl-error'
                        });
                        return false;
                    } else {
                        answer = answer.substring(6);
                        $.jGrowl('Changes saved for:<br>' + answer);
                        $('#list-item-' + file).html(answer);
                        $('#right-panel').load(ref, function () {
                            displaywindow.init(sel, ref);
                        });
                    }
                }
            });
        }).button();
        $('#autoupdate').click(function (e) {
            e.preventDefault();
            $.jGrowl('Querying the database.');
            var file = $('#metadataform input[name="file"]').val();
            $(this).mouseout();
            $.getScript('wpad.php', function () {
                proxystr = FindProxyForURL('', 'www.pubmed.org');
                $('#metadataform').ajaxSubmit({
                    data: {
                        'proxystr': proxystr,
                        'autoupdate': 1
                    },
                    success: function (answer) {
                        if (answer.substring(0, 5) === 'Error') {
                            $.jGrowl(answer, {
                                theme: 'jgrowl-error',
                                life: 6000
                            });
                            return false;
                        } else {
                            answer = answer.substring(6);
                            $.jGrowl('Item updated:<br>' + answer);
                            if ($('#metadataform input[name="file"]').val() === file) {
                                $('#file-panel').load('edit.php?file=' + file, function () {
                                    edit.init();
                                });
                            }
                            $('#list-item-' + file).html(answer);
                            $('#right-panel').load(ref, function () {
                                displaywindow.init(sel, ref);
                            });
                        }
                    }
                });
            });
        }).button().tipsy();
        $('#addurlrow').click(function () {
            $(this).closest('tr').after('<tr><td class="threedleft">URL:</td><td class="threedright"><input type="text" size="80" name="url[]" style="width: 99%" value=""></td></tr>');
        });
        $('#adduidrow').click(function () {
            $(this).closest('tr').after('<tr><td class="threedleft">Database UID:</td><td class="threedright"><input type="text" size="80" name="uid[]" style="width: 99%" value=""></td></tr>');
        });
        $('.addauthorrow').click(function () {
            $(this).closest('.author-inputs')
                    .append('<div class="new">Last name: <input type="text" name="last_name[]" value=""> &nbsp;<i class="fa fa-exchange flipnames"></i>&nbsp; First name: <input type="text" name="first_name[]" value=""></div>');
            $(this).closest('.editor-inputs')
                    .append('<div class="new">Last name: <input type="text" value=""> &nbsp;<i class="fa fa-exchange flipnames"></i>&nbsp; First name: <input type="text" value=""></div>');
            authorinputs.init(true);
            editorinputs.init(true);
        });
        $('#metadataform input[name="uid\\[\\]"]').tipsy({
            html: true,
            gravity: 'e'
        });
        $('#metadataform textarea[name="keywords"]').tipsy({
            html: true,
            gravity: 'e'
        });
        $('#metadataform input[name="journal"]').autocomplete({
            source: "ajaxjournals.php?search=journal",
            minLength: 1
        });
        $('#metadataform input[name="secondary_title"]').autocomplete({
            source: "ajaxjournals.php?search=secondary_title",
            minLength: 1
        });
        $('select[name="reference_type"]').on('change', function () {
            var type = $(this).find('option:selected').text();
            if (type === 'article') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Full journal name:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'book') {
                $('.td-title').text('Book title:');
                $('.td-secondary-title').text('Series title:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'chapter') {
                $('.td-title').text('Chapter title:');
                $('.td-secondary-title').text('Book title:');
                $('.td-tertiary-title').text('Series title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'thesis') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('School:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'conference') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Conference:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'patent') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Source:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Assignee:');
            } else {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Secondary title:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            }
        });
        $('#metadataform input[name="journal_abbr"]').autocomplete({
            source: "ajaxjournals.php?search=journal",
            minLength: 1
        });
        $('#metadataform input[name="secondary_title"]').autocomplete({
            source: "ajaxjournals.php?search=secondary_title",
            minLength: 1
        });
        $('#file-panel').scroll(function () {
            $('#metadataform input[name="journal_abbr"],#metadataform input[name="secondary_title"]').autocomplete('close');
        });
    }
};
var authorinputs = {
    init: function (newrow) {
        this.formatAuthors();
        $e = '';
        // selection based on if new row added, or initial binding
        if (newrow === true) {
            $e = $('.author-inputs .new');
        } else {
            $e = $('.author-inputs');
        }
        $e.find('input').bind('keyup blur', function () {
            authorinputs.formatAuthors();
        });
        $e.find('input:even').autocomplete({
            source: 'ajaxfilter.php?open[]=authors',
            minLength: 1,
            select: function (e, data) {
                var auth = data.item.value.split(', ');
                $(e.target).val(auth[0]).parent().find('input:odd').val(auth[1]).trigger('keyup');
                return false;
            }
        });
        $('.author-inputs').find('.new').removeClass('new');
        $('.flipnames').unbind('click').click(function () {
            var prev = $(this).prev().val(), next = $(this).next().val();
            $(this).prev().val(next);
            $(this).next().val(prev).trigger('keyup');
        });
    },
    formatAuthors: function () {
        var authors = [], $el = $('.author-inputs');
        $el.find('div').each(function (i) {
            authors[i] = '';
            var last = $.trim($(this).children('input').eq(0).val());
            if (last !== '')
                authors[i] = escapeHtml('L:"' + last + '",F:"' + $.trim($(this).children('input').eq(1).val()) + '"');
        });
        authors = jQuery.grep(authors, function (n) {
            return (n !== '');
        });
        var author = authors.join(';');
        $el.next('input[name="authors"]').val(author);
    }
};
var editorinputs = {
    init: function (newrow) {
        this.formatEditors();
        $e = '';
        // selection based on if new row added, or initial binding
        if (newrow === true) {
            $e = $('.editor-inputs .new');
        } else {
            $e = $('.editor-inputs');
        }
        $e.find('input').bind('keyup blur', function () {
            editorinputs.formatEditors();
        });
        $e.find('input:even').autocomplete({
            source: 'ajaxfilter.php?open[]=editors',
            minLength: 1,
            select: function (e, data) {
                var auth = data.item.value.split(', ');
                $(e.target).val(auth[0]).parent().find('input:odd').val(auth[1]).trigger('keyup');
                return false;
            }
        });
        $('.editor-inputs').find('.new').removeClass('new');
        $('.flipnames').unbind('click').click(function () {
            var prev = $(this).prev().val(), next = $(this).next().val();
            $(this).prev().val(next);
            $(this).next().val(prev).trigger('keyup');
        });
    },
    formatEditors: function () {
        var authors = [], $el = $('.editor-inputs');
        $el.find('div').each(function (i) {
            authors[i] = '';
            var last = $.trim($(this).children('input').eq(0).val());
            if (last !== '')
                authors[i] = escapeHtml('L:"' + last + '",F:"' + $.trim($(this).children('input').eq(1).val()) + '"');
        });
        authors = jQuery.grep(authors, function (n) {
            return (n !== '');
        });
        var author = authors.join(';');
        $el.next('input[name="editor"]').val(author);
    }
};
var desktop = {
    init: function (openproj) {
        common.init();
        var firstid = '', lw = 233;
        if (openproj === undefined) {
            if ($('.projectcontainer:first').length === 1)
                firstid = $('.projectcontainer:first').attr('id').split('-').pop();
        } else {
            firstid = openproj;
        }
        $('body').data('sel', 'desk').data('proj', firstid);
        if (localStorage.getItem('lwidth') !== undefined)
            lw = localStorage.getItem('lwidth');
        if (lw === '0') {
            $('#leftindex-left').hide();
            $('#bottom-panel .middle-panel i').removeClass('fa-caret-left').addClass('fa-caret-right');
        }
        $('#right-panel').load("display.php", "browse[]=all&select=desk&project=" + firstid, function () {
            displaywindow.init('desk', "display.php?browse[]=all&select=desk&project=" + firstid);
        });
        $(".projectheader").click(function () {
            var proj = $(this).next('div.projectcontainer').attr('id').split('-').pop(), ref = 'display.php?browse[]=all&select=desk&project=' + proj;
            $('#right-panel').load(ref, function () {
                $("#right-panel").scrollTop(0);
                displaywindow.init('desk', ref);
            });
        });
        $("#bottom-panel .middle-panel").click(function () {
            var $l = $(this).prev();
            if ($l.is(':hidden')) {
                $l.show();
                localStorage.setItem('lwidth', '233');
                $(this).children().removeClass('fa-caret-right').addClass('fa-caret-left');
            } else {
                $l.hide();
                localStorage.setItem('lwidth', '0');
                $(this).children().removeClass('fa-caret-left').addClass('fa-caret-right');
            }
        });
        //////////////////////////////quick search///////////////////////////////////
        var radioRows = [
            '#global_separator',
            '#anywhere_separator',
            '#fulltext_separator',
            '#pdfnotes_separator',
            '#notes_separator'];
        for (var i = 0; i < radioRows.length; i++) {
            $(radioRows[i]).buttonset()
                .buttonset('widget')
                .find('label')
                .removeClass('ui-corner-left')
                .removeClass('ui-corner-right');
        }
        $("#quicksearch #search").button().click(function () {
            $(this).blur();
            if ($("#quicksearch input:text:visible").val() === '')
                return false;
            var q = $("#quicksearch").formSerialize();
            timeId = setTimeout(dooverlay, 500);
            $('#right-panel').load('search.php?' + q, function () {
                clearoverlay();
                $("#right-panel").scrollTop(0);
                displaywindow.init('desk', 'search.php?' + q);
            });
            return false;
        }).button('widget')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        $("#quicksearch #clear").button().click(function () {
            $(this).blur();
            $("#quicksearch input:text:visible").val('').focus();
            $("#quicksearch input[value='AND']").parent('td.select_span').click();
            $.get('search.php?newsearch=1');
        }).button('widget')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        $("#quicksearch").submit(function () {
            $("#quicksearch #search").click();
            return false;
        });
        $('#search-menu div:eq(0)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(0).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(0).show();
            $("#quicksearch").find('.separators').eq(0).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('global');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 0);
        });
        $('#search-menu div:eq(1)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(1).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(1).show();
            $("#quicksearch").find('.separators').eq(1).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('metadata');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 1);
        });
        $('#search-menu div:eq(2)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(2).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(2).show();
            $("#quicksearch").find('.separators').eq(2).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('pdf');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 2);
        });
        $('#search-menu div:eq(3)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(3).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(3).show();
            $("#quicksearch").find('.separators').eq(3).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('pdfnotes');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 3);
        });
        $('#search-menu div:eq(4)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(4).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(4).show();
            $("#quicksearch").find('.separators').eq(4).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('notes');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 4);
        });
        $('#search-menu div').tipsy({'gravity': 'nw'});
        // clicked tab memory
        var clickedTab = localStorage.getItem("search-menu-tab");
        if (!clickedTab)
            clickedTab = 0;
        $('#search-menu div:eq(' + clickedTab + ')').click();
        $("#advancedsearchbutton").click(function () {
            $(this).blur();
            var proj = $('body').data('proj');
            $("#advancedsearch").load('advancedsearch.php?select=desk&project=' + proj, function () {
                $("#advancedsearch").dialog('option', 'title', 'Advanced search of Desk').dialog('open');
                advancedsearch.init();
            });
        }).button().button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        $("#expertsearchbutton").click(function () {
            $(this).blur();
            var proj = $('body').data('proj');
            $("#expertsearch").load('expertsearch.php?select=desk&project=' + proj, function () {
                $("#expertsearch").dialog('option', 'title', 'Expert search of Desk').dialog('open');
                expertsearch.init();
            });
        }).button().button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        $('#project-' + firstid).show();
        $('#leftindex-left .projectheader').click(function () {
            $('#leftindex-left div.projectcontainer').hide();
            $(this).next('div.projectcontainer').show();
            var projectID = $(this).next('div.projectcontainer').attr('id').split('-').pop();
            $('#quicksearch input[name=project]').val(projectID);
            $('body').data('sel', 'desk').data('proj', projectID);
        });
        $('#leftindex-left').find('.projectheader').each(function(){
            $(this).button()
                .button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        });
        $("#dialog-confirm").dialog('option', 'buttons', {
            'Yes': function () {
                var $form = $(this).data('form');
                $(this).dialog('close');
                $form.ajaxSubmit(function () {
                    $('#bottom-panel').load('desktop.php', function () {
                        desktop.init();
                    });
                });
            },
            Cancel: function () {
                $(this).dialog('close');
            }
        }).dialog({
            width: 'auto',
            height: 'auto'
        });
        $("#leftindex-left .deletebutton").button().click(function () {
            $("#dialog-confirm").html('<p><i class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;margin:2px 7px 2em 0px"></i> This project will be permanently deleted. Are you sure?</p>')
                    .data('form', $(this).closest('form')).dialog('option', 'title', 'Delete the project?').dialog('open');
            return false;
        });
        $("#leftindex-left .emptybutton").button().click(function () {
            $("#dialog-confirm").html('<p><i class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;margin:2px 7px 2em 0px"></i> All records will be removed from this project. Are you sure?</p>')
                    .data('form', $(this).closest('form')).dialog('option', 'title', 'Empty the project?').dialog('open');
            return false;
        });
        $("#leftindex-left .renamebutton").button().click(function () {
            var $form = $(this).closest('form'), proj = jQuery.trim($form.find('input[name="project"]').val());
            if (proj !== '') {
                $(this).closest('form').ajaxSubmit(function () {
                    $('#bottom-panel').load('leftindex.php?select=desk', function () {
                        desktop.init();
                    });
                });
            }
            return false;
        });
        $("#leftindex-left .desk-active").click(function () {
            var activeval = 0, projectID = $(this).closest('div.projectcontainer').attr('id').split('-').pop();
            if ($(this).find('i').hasClass('fa-check-square'))
                activeval = 1;
            if (projectID !== '') {
                $.get("ajaxdesk.php", {
                    active: activeval,
                    projectID: projectID
                });
            }
            return false;
        });
        $("#leftindex-left .adduser").click(function () {
            var userID = $('select[name=adduser] option:selected').val(), projectID = $(this).closest('div').attr('id').split('-').pop(),
                    $option = $(this).closest('div.projectcontainer').find('select[name="adduser"] option[value="' + userID + '"]'),
                    $select = $(this).closest('div.projectcontainer').find('select[name="removeuser"]');
            $.get("ajaxdesk.php", {
                adduser: '',
                userID: userID,
                projectID: projectID
            },
                    function (answer) {
                        if (answer === 'done') {
                            $option.prependTo($select).removeAttr("selected");
                            var listitems = $select.children('option').get();
                            listitems.sort(function (a, b) {
                                var compA = $(a).text().toUpperCase();
                                var compB = $(b).text().toUpperCase();
                                return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
                            });
                            $.each(listitems, function (idx, itm) {
                                $select.append(itm);
                            });
                        } else if (answer.substr(0, 5) === 'Error') {
                            $.jGrowl(answer, {
                                theme: 'jgrowl-error'
                            });
                        }
                    });
        });
        $("#leftindex-left .removeuser").click(function () {
            var userID = $('select[name=removeuser] option:selected').val(), projectID = $(this).closest('div').attr('id').split('-').pop(),
                    $option = $(this).closest('div.projectcontainer').find('select[name="removeuser"] option[value="' + userID + '"]'),
                    $select = $(this).closest('div.projectcontainer').find('select[name="adduser"]');
            $.get("ajaxdesk.php", {
                removeuser: '',
                userID: userID,
                projectID: projectID
            },
                    function (answer) {
                        if (answer === 'done') {
                            $option.prependTo($select).removeAttr("selected");
                            var listitems = $select.children('option').get();
                            listitems.sort(function (a, b) {
                                var compA = $(a).text().toUpperCase();
                                var compB = $(b).text().toUpperCase();
                                return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
                            });
                            $.each(listitems, function (idx, itm) {
                                $select.append(itm);
                            });
                        }
                    });
        });
        $("#createproject").button({
            text: true
        }).click(function () {
            var $form = $(this).closest('form'), proj = jQuery.trim($form.find('input[name="project"]').val());
            if (proj !== '') {
                $(this).closest('form').ajaxSubmit(function () {
                    $('#bottom-panel').load('leftindex.php?select=desk', function () {
                        desktop.init();
                    });
                });
            }
            return false;
        });
    }
};
var displaywindow = {
    init: function (sel, divhref) {
        $('.bibtex').click(function () {
            $(this).select();
        });
        $('#right-panel .navigation:eq(0)').focus().blur();
        $('#first-loader', window.parent.document).fadeOut(400, function () {
            $(this).remove();
        });
        var showid = '';
        function briefShow($el) {
            clearTimeout(showid);
            $el.css('white-space', 'inherit');
        }
        function briefHide($el) {
            clearTimeout(showid);
            $el.css('white-space', 'nowrap');
        }
        $('#right-panel .brief').mouseover(function () {
            var $t = $(this);
            showid = setTimeout(function () {
                briefShow($t);
            }, 500);
        }).mouseout(function () {
            briefHide($(this));
        });
        $('body').data('list-title', $('#list-title').text())
                .data('right-panel-url', divhref);
        $("#right-panel .pgdown").click(function () {
            var pgupoffset = $("#right-panel .pgup").offset();
            $("#right-panel").animate({
                scrollTop: pgupoffset.top
            }, 200);
        });
        $("#right-panel .pgup").click(function () {
            $("#right-panel").animate({
                scrollTop: 0
            }, 200);
        });
        $('#display-content').find('.anotherurl').tipsy({gravity: 's'});
        var openid = '', regexp = /id=\d*/gi, idarr = top.location.search.match(regexp);
        if (idarr)
            openid = idarr[0].split('=').pop();
        if (openid !== '' && $(document).data('idlock') === undefined) {
            $('#bottom-panel').hide();
            $('#items-container').show().load('items.php', 'file=' + openid, function () {
                items.init(openid);
                $(document).data('idlock', 'locked');
            });
        }
        var iconswidth = $('#icon-container').width(),
                iconnum = Math.floor(iconswidth / 364),
                icmarg = Math.max(4, (iconswidth - iconnum * 360) / (iconnum + 1));
        $('#icon-container').find('.thumb-items').css('margin-left', icmarg);
        $(window).resize(function () {
            var iconswidth = $('#icon-container').width(),
                    iconnum = Math.floor(iconswidth / 364),
                    icmarg = Math.max(4, (iconswidth - iconnum * 360) / (iconnum + 1));
            $('#icon-container').find('.thumb-items').css('margin-left', icmarg);
            $('#menuwrapper').remove();
        });
        $('#bottom-panel .middle-panel').click(function () {
            var iconswidth = $('#icon-container').width(),
                    iconnum = Math.floor(iconswidth / 364),
                    icmarg = Math.max(4, (iconswidth - iconnum * 360) / (iconnum + 1));
            $('#icon-container').find('.thumb-items').css('margin-left', icmarg);
        });
        $('#display-content .thumb-titles').click(function () {
            var file = $(this).closest('.thumb-items').attr('id').split('-').pop();
            $('#bottom-panel').hide();
            $('#items-container').show().load('items.php', 'file=' + file, function () {
                items.init(file);
            });
        });
        $('#display-content a.navigation').click(function (e) {
            var ref = $(this).prop('href');
            e.preventDefault();
            timeId = setTimeout(dooverlay, 500);
            $('#right-panel').load(ref, function () {
                clearoverlay();
                $("#right-panel").scrollTop(0);
                displaywindow.init(sel, ref);
                $('body').data('right-panel-url', ref);
            });
        });
        $('.quick-view').tipsy({gravity: 'nw', fallback: 'Quick View'});
        $('.quick-view-external').tipsy({gravity: 'nw', fallback: 'Item View in New Window'});
        $('#display-content').click(function (event) {
            var t = event.target;
            if ($(t).hasClass('titles') || $(t).hasClass('thumb-titles')) {
                var file;
                if ($(t).closest('div.items').length === 1) {
                    file = $(t).closest('div.items').attr('id').split('-').pop();
                } else {
                    file = $(t).closest('div.thumb-items').attr('id').split('-').pop();
                }
                $('#bottom-panel').hide();
                $('#items-container').show().load('items.php', 'file=' + file, function () {
                    items.init(file);
                });
            } else if ($(t).hasClass('quick-view') || $(t).hasClass('fa-info-circle')) {
                var file = $(t).closest('.item-container').attr('id').split('-').pop();
                $("#dialog-confirm").html('<p style="text-align:center;font-size:1.33em;padding-top:20%"><img src="img/ajaxloader.gif"> Loading details...</p>')
                        .dialog({
                            width: '90%',
                            height: $(window).height() - 40,
                            title: 'Item Details',
                            buttons: {
                                'Edit': function () {
                                    $(this).dialog("close");
                                    $('#bottom-panel').hide();
                                    $('#items-container').show().load('items.php', 'file=' + file, function () {
                                        items.init(file);
                                    });
                                },
                                'Close': function () {
                                    $(this).dialog("close");
                                }
                            }
                        }).dialog('open');
                $("#dialog-confirm").load('file_top.php?file=' + file, function () {
                    filetop.init();
                });
            } else if ($(t).hasClass('quick-view-external') || $(t).hasClass('fa-external-link-square')) {
                var file = $(t).closest('.item-container').attr('id').split('-').pop();
                window.open('stable.php?id=' + file);
            } else
            if ($(t).hasClass('fa-star')) {
                var $t = $(t), rating = $t.index() + 1,
                        file = $t.closest('.items, .thumb-items').attr('id').split('-').pop();
                $.get("ajaxrating.php", {"file": file, "rating": rating}, function () {
                    $t.siblings('.fa-star').addBack().removeClass('ui-state-error-text').removeClass('ui-priority-secondary');
                    if (rating === 1) {
                        $t.addClass('ui-state-error-text');
                        $t.siblings('.fa-star').addClass('ui-priority-secondary');
                    }
                    if (rating === 2) {
                        $t.prev().addBack().addClass('ui-state-error-text');
                        $t.next().addClass('ui-priority-secondary');
                    }
                    if (rating === 3)
                        $t.siblings('.fa-star').addBack().addClass('ui-state-error-text');
                });
            } else
            if ($(t).hasClass('update_clipboard')) {
                var $t = $(t), file = $t.closest('.items, .thumb-items').attr('id').split('-').pop();
                if ($t.is('i'))
                    $t = $t.parent();
                $.get("ajaxclipboard.php", {
                    'file': file,
                    'selection': sel
                },
                        function (answer) {
                            if (answer === "added") {
                                $t.addClass('clicked');
                                $t.children('i').removeClass('fa-square-o').addClass('fa-check-square ui-state-error-text');
                            } else if (answer === "removed") {
                                $t.removeClass('clicked');
                                $t.children('i').removeClass('fa-check-square ui-state-error-text').addClass('fa-square-o');
                            } else if (answer.substr(0, 5) === "Error") {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error'
                                });
                            }
                            if (sel === "clipboard")
                                $('#right-panel').load($('body').data('right-panel-url'), function () {
                                    displaywindow.init(sel, $('body').data('right-panel-url'));
                                });
                        }
                );
            } else
            if ($(t).hasClass('update_shelf')) {
                var $t = $(t), file = $t.closest('.items, .thumb-items').attr('id').split('-').pop();
                if ($t.is('i'))
                    $t = $t.parent();
                $.get("ajaxshelf.php", {
                    'file': file,
                    'selection': sel
                },
                        function (answer) {
                            if (answer === "added") {
                                $t.addClass('clicked');
                                $t.children('i').removeClass('fa-square-o').addClass('fa-check-square ui-state-error-text');
                            } else if (answer === "removed") {
                                $t.removeClass('clicked');
                                $t.children('i').removeClass('fa-check-square ui-state-error-text').addClass('fa-square-o');
                            } else if (answer.substr(0, 5) === "Error") {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error'
                                });
                            }
                            if (sel === "shelf")
                                $('#right-panel').load($('body').data('right-panel-url'), function () {
                                    displaywindow.init(sel, $('body').data('right-panel-url'));
                                });
                        }
                );
            } else
            if ($(t).hasClass('update_project')) {
                var $t = $(t), file = $t.closest('.items, .thumb-items').attr('id').split('-').pop();
                if ($t.is('i'))
                    $t = $t.parent();
                var project = $t.data("projid");
                $.get("ajaxdesk.php", {
                    'file': file,
                    'selection': sel,
                    'displayedproject': $('body').data('proj'),
                    'project': project
                },
                        function (answer) {
                            if (answer === "added") {
                                $t.addClass('clicked');
                                $t.children('i').removeClass('fa-square-o').addClass('fa-check-square ui-state-error-text');
                            } else if (answer === "removed") {
                                $t.removeClass('clicked');
                                $t.children('i').removeClass('fa-check-square ui-state-error-text').addClass('fa-square-o');
                            } else if (answer.substr(0, 5) === "Error") {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error'
                                });
                            }
                            if (sel === "desk" && parseInt(project) === parseInt($('body').data('proj')))
                                $('#right-panel').load($('body').data('right-panel-url'), function () {
                                    displaywindow.init(sel, $('body').data('right-panel-url'));
                                });
                        }
                );
            }
        });
        $('#select-display, #select-order, #select-number').selectmenu({
            change: function (e, data) {
                var tid = $(this).attr('id'), newurl = $('body').data('right-panel-url');
                timeId = setTimeout(dooverlay, 500);
                $.get('ajaxdisplay.php?value=' + data.item.value, function (answer) {
                    if (answer.substr(0, 5) === 'Error')
                        $.jGrowl(answer);
                    if (tid !== 'select-display')
                        newurl = newurl.replace(/from=\d*/g, "from=0");
                    $('#right-panel').load(newurl, function () {
                        clearoverlay();
                        displaywindow.init(sel);
                    });
                });
            }
        });
        $("#omnitooldiv").dialog({
            autoOpen: false,
            width: 780,
            buttons: {
                'Submit': function () {
                    var $t = $(this), omnitool = $('input[name=omnitool]:checked').val(),
                            project3 = $('select[name=project3] option:selected').val(),
                            project4 = $('select[name=project4] option:selected').val();
                    var category = [];
                    $.each($('#omnitoolcategories input:checked'), function () {
                        category.push($(this).val());
                    });
                    var category2 = [];
                    $.each($('input[name="category2[]"]'), function () {
                        if ($(this).val() !== '' && $(this).val() !== 'Add new category')
                            category2.push($(this).val());
                    });
                    $t.dialog('disable');
                    timeId = setTimeout(dooverlay, 1000);
                    $.post('omnitool.php', {
                        'omnitool': omnitool,
                        'project3': project3,
                        'project4': project4,
                        'category[]': category,
                        'category2[]': category2
                    },
                            function () {
                                clearoverlay();
                                var ref = $('body').data('right-panel-url');
                                $('#right-panel').load(ref, function () {
                                    displaywindow.init(sel, ref);
                                });
                                $t.dialog('enable').dialog('close');
                            });
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
        $("#omnitoolbutton").click(function () {
            var total = $('#total-items').text(), ttl = 'Omnitool - select a task to perform with all ' + total + ' items.';
            $("#omnitooldiv").load('omnitool.php', function () {
                $(this).dialog('option', 'title', ttl).dialog('open');
                omnitool.init();
            });
        }).button();
        $('#exportdialog').dialog({
            autoOpen: false,
            width: '60em',
            open: function () {
                $('#pdf-div').css('visibility', 'hidden');
                if ($(this).parent().height() > ($(window).height() - 20)) {
                    $(this).dialog("option","height", $(window).height() - 20);
                }
            },
            close: function () {
                $('#pdf-div').css('visibility', '');
            },
            buttons: {
                "Export": function () {
                    $(this).dialog('close');
                    $('#exportform').attr('action', 'export.php?_=' + Math.random());
                    if ($('input[name="output"]:checked').val() === 'inline') {
                        $('#exportform').attr('target', 'exportwindow');
                    } else {
                        $('#exportform').attr('target', '');
                    }
                    if ($('#citation-style').val() !== '')
                        localStorage.setItem('laststyle', $('#citation-style').val());
                    if ($('#last-style-tr').find('input').is(':checked'))
                        localStorage.setItem('laststyle', $('#last-style-tr').find('input').val());
                    $('#last-style-tr').find('span').text(localStorage.getItem('laststyle'));
                    $('#last-style-tr').find('input').val(localStorage.getItem('laststyle'));
                    $('#exportform').submit();
                },
                "Reset": function () {
                    $('#exportform').resetForm();
                    $('#exportform input:checkbox').each(function () {
                        $(this).next('i').removeClass('fa-check-square').addClass('fa-square-o');
                        if ($(this).is(':checked'))
                            $(this).next('i').removeClass('fa-square-o').addClass('fa-check-square');
                    });
                    $('#exportform input:radio').each(function () {
                        $(this).next('i').removeClass('fa-circle').addClass('fa-circle-o');
                        if ($(this).is(':checked'))
                            $(this).next('i').removeClass('fa-circle-o').addClass('fa-circle');
                    });
                },
                "Close": function () {
                    $(this).dialog("close");
                }
            }
        });
        $('#exportbutton').click(function () {
            var total = $('#total-items').text(), ttl = 'Export ' + total + ' items';
            if (total === '1')
                ttl = 'Export 1 item';
            if ($('#exportdialog').html() === '') {
                $('#exportdialog').load('export.php', function () {
                    $(this).dialog('option', 'title', ttl).dialog('open');
                    common.init();
                    exportitems.init();
                });
            } else {
                $('#exportform').find('input[name="export_files"]').val('session');
                $('#exportdialog').dialog('option', 'title', ttl).dialog('open');
            }
        }).button();
        $("#printlist").click(function () {
            window.print();
        }).button();
        $("#rss-link").button();
        $("#right-panel .author_expander").unbind().click(function () {
            var $container = $(this).parent();
            if ($(this).hasClass('fa-plus-circle')) {
                $container.css('white-space', 'inherit');
                $(this).removeClass('fa-plus-circle').addClass('fa-minus-circle');
            } else {
                $container.css('white-space', 'nowrap');
                $(this).removeClass('fa-minus-circle').addClass('fa-plus-circle');
            }
        });
        $('#display-content').find('.thumb-items a,.titles-pdf a').bind('contextmenu', function (e) {
            var filename = $(this).closest('.thumb-items,.items').data('file');
            e.preventDefault();
            $('#contextmenu').remove();
            $('body').append('<div id="menuwrapper"><div class="alternating_row item-sticker" id="contextmenu"><i class="fa fa-download"></i> Download PDF</div></div>');
            var mtop = e.pageY, mleft = e.pageX + 20;
            if (mleft + 150 > $(window).width())
                mleft = mleft - 170;
            if (mtop + 50 > $(window).height())
                mtop = mtop - 40;
            $('#contextmenu').css('top', mtop).css('left', mleft);
            $('#menuwrapper').width($(document).width()).height($(document).height());
            $('#contextmenu').click({
                filename: filename
            }, function (event) {
                window.location.assign('pdfcontroller.php?downloadpdf=1&mode=download&file=' + event.data.filename);
                $('#menuwrapper').remove();
            });
            $('#menuwrapper').mousedown(function (e) {
                if (e.target === this)
                    $(this).remove();
            });
        });
        // after reload, check item list in overlay layer, if displayed
        if ($('#items-left').length === 1) {
            var files = '';
            $('#items-left').find('.listleft').each(function (i) {
                var file = $(this).data('id');
                files = files + '&files[]=' + file;
            });
            $.getJSON('items.php?checkitem=1' + files, function (answer) {
                $.each(answer, function (i, f) {
                    $('#list-item-' + parseInt(f)).removeClass('listleft').addClass('ui-state-disabled');
                });
            });
        }
        //HOTKEYS
        $(document).unbind('keydown').bind('keydown', 'd', function () {
            if ($('.nextpage').is(':visible'))
                $('.nextpage').click();
        }).bind('keydown', 'a', function () {
            if ($('.prevpage').is(':visible'))
                $('.prevpage').click();
        }).bind('keydown', 's', function () {
            $('.nextrecord').click();
        }).bind('keydown', 'w', function () {
            $('.prevrecord').click();
        }).bind('keydown', 'q', function () {
            $('.backbutton').click();
        });
    }
};
var filetop = {
    init: function (sel) {
        $('#file-panel2 a.pdf_link').tipsy({
            gravity: 's'
        });
        common.init();
        $('#file-top-notes').find('p').first().css('margin-top', '0');
        $('#file-panel2').find('.anotherurl').tipsy({gravity: 's'});
        $('#file-panel2').click(function (event) {
            var t = event.target;
            if ($(t).hasClass('fa-star')) {
                var $t = $(t), rating = $t.index() + 1,
                        file = $t.closest('.items').attr('id').split('-').pop();
                $t = $t.add($('#display-item-' + file).find('.star').eq($t.index()));
                $.get("ajaxrating.php", {"file": file, "rating": rating}, function () {
                    $t.siblings('.fa-star').addBack().removeClass('ui-state-error-text').removeClass('ui-priority-secondary');
                    if (rating === 1) {
                        $t.addClass('ui-state-error-text');
                        $t.siblings('.fa-star').addClass('ui-priority-secondary');
                    }
                    if (rating === 2) {
                        $t.prev().addBack().addClass('ui-state-error-text');
                        $t.next().addClass('ui-priority-secondary');
                    }
                    if (rating === 3)
                        $t.siblings('.fa-star').addBack().addClass('ui-state-error-text');
                });
            } else
            if ($(t).hasClass('update_clipboard')) {
                var $t = $(t), file = $t.closest('div.items').attr('id').split('-').pop(), tid = $t.closest('.items').attr('id');
                if ($t.is('i'))
                    $t = $t.parent();
                tid = tid.replace('file-', 'display-');
                var $t = $t.add($('#' + tid + ' span.update_clipboard'));
                $.get("ajaxclipboard.php", {
                    'file': file,
                    'selection': sel
                },
                        function (answer) {
                            if (answer === "added") {
                                $t.addClass('clicked');
                                $t.children('i').removeClass('fa-square-o').addClass('fa-check-square ui-state-error-text');
                            } else if (answer === "removed") {
                                $t.removeClass('clicked');
                                $t.children('i').removeClass('fa-check-square ui-state-error-text').addClass('fa-square-o');
                            } else if (answer.substr(0, 5) === "Error") {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error'
                                });
                            }
                            if (sel === "clipboard") {
                                var ref = $('body').data('right-panel-url');
                                $('#right-panel').load(ref, function () {
                                    displaywindow.init('clipboard', ref);
                                });
                                if ($('.listleft').length === 1) {
                                    $('.backbutton').click();
                                } else {
                                    var file = $('#items-left > .clicked').nextAll('.listleft').first().data('id');
                                    if ($('#list-item-' + file).length < 1) {
                                        file = $('#nav-next').data('id');
                                        if (!file) {
                                            file = $('#items-left > .clicked').prevAll('.listleft').first().data('id');
                                            if ($('#list-item-' + file).length < 1)
                                                file = $('#nav-prev').data('id');
                                        }
                                    }
                                    $('#items-container').load('items.php', 'file=' + file, function () {
                                        items.init(file);
                                    });
                                }
                            }
                        }
                );
            } else
            if ($(t).hasClass('update_shelf')) {
                var $t = $(t), file = $t.closest('div.items').attr('id').split('-').pop(), tid = $t.closest('.items').attr('id');
                if ($t.is('i'))
                    $t = $t.parent();
                tid = tid.replace('file-', 'display-');
                var $t = $t.add($('#' + tid + ' span.update_shelf'));
                $.get("ajaxshelf.php", {
                    'file': file,
                    'selection': sel
                },
                        function (answer) {
                            if (answer === "added") {
                                $t.addClass('clicked');
                                $t.children('i').removeClass('fa-square-o').addClass('fa-check-square ui-state-error-text');
                            } else if (answer === "removed") {
                                $t.removeClass('clicked');
                                $t.children('i').removeClass('fa-check-square ui-state-error-text').addClass('fa-square-o');
                            } else if (answer.substr(0, 5) === "Error") {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error'
                                });
                            }
                            if (sel === "shelf") {
                                var ref = $('body').data('right-panel-url');
                                $('#right-panel').load(ref, function () {
                                    displaywindow.init('clipboard', ref);
                                });
                                if ($('.listleft').length === 1) {
                                    $('.backbutton').click();
                                } else {
                                    var file = $('#items-left > .clicked').nextAll('.listleft').first().data('id');
                                    if ($('#list-item-' + file).length < 1) {
                                        file = $('#nav-next').data('id');
                                        if (!file) {
                                            file = $('#items-left > .clicked').prevAll('.listleft').first().data('id');
                                            if ($('#list-item-' + file).length < 1)
                                                file = $('#nav-prev').data('id');
                                        }
                                    }
                                    $('#items-container').load('items.php', 'file=' + file, function () {
                                        items.init(file);
                                    });
                                }
                            }
                        }
                );
            } else
            if ($(t).hasClass('update_project')) {
                var $t = $(t), file = $t.closest('div.items').attr('id').split('-').pop(),
                        tid = $t.closest('.items').attr('id');
                if ($t.is('i'))
                    $t = $t.parent();
                var project = $t.data("projid");
                tid = tid.replace('file-', 'display-');
                var $t = $t.add($('#' + tid + ' span.update_project[data-projid="' + project + '"]'));
                $.get("ajaxdesk.php", {
                    'file': file,
                    'project': project,
                    'selection': sel,
                    'displayedproject': $('body').data('proj')
                },
                        function (answer) {
                            if (answer === "added") {
                                $t.addClass('clicked');
                                $t.children('i').removeClass('fa-square-o').addClass('fa-check-square ui-state-error-text');
                            } else if (answer === "removed") {
                                $t.removeClass('clicked');
                                $t.children('i').removeClass('fa-check-square ui-state-error-text').addClass('fa-square-o');
                            } else if (answer.substr(0, 5) === "Error") {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error'
                                });
                            }
                            if (sel === "desk" && answer === "removed" && parseInt(project) === parseInt($('body').data('proj'))) {
                                $('#bottom-panel').load('leftindex.php?select=desk&project=' + project, function () {
                                    desktop.init(project);
                                });
                                if ($('.listleft').length === 1) {
                                    $('.backbutton').click();
                                } else {
                                    var file = $('#items-left > .clicked').nextAll('.listleft').first().data('id');
                                    if ($('#list-item-' + file).length < 1) {
                                        file = $('#nav-next').data('id');
                                        if (!file) {
                                            file = $('#items-left > .clicked').prevAll('.listleft').first().data('id');
                                            if ($('#list-item-' + file).length < 1)
                                                file = $('#nav-prev').data('id');
                                        }
                                    }
                                    $('#items-container').load('items.php', 'file=' + file, function () {
                                        items.init(file);
                                    });
                                }
                            }
                        }
                );
            }
        });
        $("#file-panel2 .author_expander").unbind().click(function () {
            var $container = $(this).parent();
            if ($(this).hasClass('fa-plus-circle')) {
                $container.css('white-space', 'inherit');
                $(this).removeClass('fa-plus-circle').addClass('fa-minus-circle');
            } else {
                $container.css('white-space', 'nowrap');
                $(this).removeClass('fa-minus-circle').addClass('fa-plus-circle');
            }
        });
        $('.bibtex').click(function () {
            $(this).select();
        });
        $('#file-panel2').find('a.pdf_link').bind('contextmenu', function (e) {
            var filename = $(this).closest('.items').data('file');
            e.preventDefault();
            $('#contextmenu').remove();
            $('body').append('<div id="menuwrapper"><div class="alternating_row item-sticker" id="contextmenu"><i class="fa fa-download"></i> Download PDF</div></div>');
            var mtop = e.pageY, mleft = e.pageX + 20;
            if (mleft + 150 > $(window).width())
                mleft = mleft - 170;
            if (mtop + 50 > $(window).height())
                mtop = mtop - 40;
            $('#contextmenu').css('top', mtop).css('left', mleft);
            $('#menuwrapper').width($(document).width()).height($(document).height());
            $('#contextmenu').click({
                filename: filename
            }, function (event) {
                window.location.assign('pdfcontroller.php?downloadpdf=1&mode=download&file=' + event.data.filename);
                $('#menuwrapper').remove();
            });
            $('#menuwrapper').mousedown(function (e) {
                if (e.target === this)
                    $(this).remove();
            });
        });
    }
};
var filediscussion = {
    init: function () {
        var file = $('#filediscussionform input[name="file"]').val();
        function loadmessages() {
            if ($('#messages').length < 1) {
                clearInterval($('body').data('loadmessageID'));
                return false;
            }
            $('#messages').load('file_discussion.php', 'read=1&file=' + file);
        }
        clearInterval($('body').data('loadmessageID'));
        loadmessages();
        var loadmessageID = setInterval(loadmessages, 5000);
        $('body').data('loadmessageID', loadmessageID);
        $('#newmessage').click(function (e) {
            e.preventDefault();
            var $elem = $(this).prev('textarea'), newmessage = $elem.val();
            if (newmessage !== '') {
                $.post('file_discussion.php', {
                    'file': file,
                    'newmessage': newmessage
                }, function (answer) {
                    if (answer === 'OK') {
                        $elem.val('');
                        loadmessages();
                    }
                });
            }
        }).button();
        $('#deletediscussion').click(function () {
            $('#dialog-confirm').html('<p><i class="fa fa-exclamation-triangle ui-state-error-text" style="float:left;margin:2px 6px;padding-bottom:2em"></i>Do you want to permanently delete this discussion?</p>')
                    .dialog({
                        width: 'auto',
                        height: 'auto',
                        title: 'Delete discussion?',
                        buttons: [
                            {
                                text: "Delete",
                                click: function () {
                                    $.get('file_discussion.php', {
                                        'file': file,
                                        'delete': 1
                                    }, function (answer) {
                                        if (answer === 'OK')
                                            loadmessages();
                                    });
                                    $(this).dialog("close");
                                }
                            },
                            {
                                text: "Close",
                                click: function () {
                                    $(this).dialog("close");
                                }
                            }]
                    }).dialog('open');
        }).button();
    }
};
var filemanager = {
    init: function () {
        if ($('#player-controls').length === 0)
            $('head').append('<link id="player-controls" type="text/css" href="css/player-controls.css" rel="stylesheet">');
        $.ajaxSetup({
            cache: true
        });
        $.getScript('js/jplayer/jquery.jplayer.min.js');
        $.ajaxSetup({
            cache: false
        });
        var sel = $('body').data('sel'), ref = $('body').data('right-panel-url');
        $('#filesform :text').bind('keyup blur', function () {
            var inputstring = $(this).val(), newstring = inputstring.replace(/[^a-zA-Z0-9\-\_\.]/g, '_');
            if (newstring !== inputstring) {
                $(this).val(newstring);
                $.jGrowl('Only letters, numbers, and characters -_. are allowed.', {
                    theme: 'jgrowl-error'
                });
            }
        });
        $('#filelist tr.file-highlight').mouseover(function () {
            $(this).addClass('alternating_row');
        }).mouseout(function () {
            $(this).removeClass('alternating_row');
        });
        var timeoutId = '';
        function showpreview(filename) {
            $('#preview').html('<img src="attachment.php?mode=inline&attachment=' + encodeURI(filename) + '" style="width:100%;display:block">')
                    .fadeIn(200);
        }
        $('#filelist i.fa-image').mouseover(function () {
            if (navigator.appName.toUpperCase() !== 'MICROSOFT INTERNET EXPLORER') {
                var filename = $(this).closest('tr').attr('id').substring(4);
                timeoutId = setTimeout(function () {
                    showpreview(filename);
                }, 500);
            }
        }).mouseout(function () {
            if (navigator.appName.toUpperCase() !== 'MICROSOFT INTERNET EXPLORER') {
                clearTimeout(timeoutId);
                $('#preview').hide();
            }
        });
        $('#filelist div.file-remove').click(function () {
            var filename = $(this).closest('tr.file-highlight').attr('id').substring(4);
            $("#dialog-confirm")
                    .html('<p><i class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;height:3em;margin:2px 7px 20px 0px"></i> File "<i>' + filename.substring(5, 30) + '</i>" will be permanently deleted. Are you sure?</p>')
                    .data('filename', filename).data('tr', $(this).closest('tr.file-highlight'))
                    .dialog({
                        width: 'auto',
                        height: 'auto',
                        title: 'Delete File?'
                    })
                    .dialog("option", "buttons", {
                        'Delete File': function () {
                            var $this = $(this), filename = $this.data('filename'), $tr = $this.data('tr');
                            $.ajax({
                                type: "GET",
                                url: "ajaxsupplement.php",
                                cache: false,
                                data: "files_to_delete[]=" + filename,
                                dataType: "text",
                                success: function () {
                                    $tr.next('tr').addBack().remove();
                                    $this.dialog('close');
                                    $('#right-panel').load(ref, function () {
                                        displaywindow.init(sel, ref);
                                    });
                                }
                            });
                        },
                        Cancel: function () {
                            $(this).dialog('close');
                        }
                    }).dialog('open');
        });
        $('#filelist div.file-rename').click(function () {
            var $tr = $(this).closest('tr.file-highlight'), filename = $tr.attr('id').substring(4), oldname = filename.substring(5),
                    $input = $tr.find('input'), newname = $input.val();
            if ($input.is(':visible') && oldname !== newname) {
                $('#filesform').ajaxSubmit({
                    success: function () {
                        $('#file-files').click();
                    }
                });
            } else if ($input.is(':hidden')) {
                $('#filesform tr.file-highlight a').show();
                $('#filelist input.rename_container:text').hide();
                $('#filelist input.rename_container:text').each(function () {
                    var originalname = $(this).closest('tr.file-highlight').attr('id').substring(9);
                    $(this).val(originalname);
                });
            }
            $input.prevAll('a').addBack().toggle();
        });
        $('#filesform :text').keyup(function (e) {
            if (e.which === 13)
                $(this).closest('tr.file-highlight').find('div.file-rename').click();
        });
        $('#filesform, #uploadfiles').submit(function () {
            return false;
        });
        $('#filelist .fa-film, #filelist .fa-music').tipsy({
            gravity: 's'
        });
        $('#submituploadfiles').click(function () {
            var values = new Array(), file = $('#items-right').data('file');
            $('#uploadfiles :file,#uploadfiles :text').each(function (i) {
                if ($(this).val() !== '')
                    values[i] = $(this).val();
            });
            if (values.length === 0)
                return false;
            $.jGrowl('Saving in progress.');
            var newfile = $('#uploadfiles input[name=form_new_file]').val() + $('#uploadfiles input[name=form_new_file_link]').val();
            $.getScript('wpad.php', function () {
                proxystr = FindProxyForURL('', 'www.crossref.org');
                $('#uploadfiles').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    resetForm: true,
                    success: function (answer) {
                        if (answer.substring(0, 5) === 'Error') {
                            $.jGrowl(answer, {
                                theme: 'jgrowl-error',
                                life: 6000
                            });
                        }
                        if (file === $('#items-right').data('file')) {
                            if (newfile !== '') {
                                if (top.frames.topframe.frames.pdf) {
                                    top.frames.topframe.frames.pdf.location.reload(true);
                                } else if ($('#file-panel2').is(':visible')) {
                                    $('#file-item').click();
                                }
                            }
                            if ($('#filelist').length === 1)
                                $('#file-files').click();
                        }
                        $.jGrowl('File saving done.');
                        $('#right-panel').load(ref, function () {
                            displaywindow.init(sel, ref);
                        });
                    }
                });
            });
        }).button();
        $('#file-panel .reindex').click(function () {
            var $t = $(this);
            if ($t.hasClass('ui-state-disabled'))
                return false;
            $t.addClass('ui-state-disabled').children('i').addClass('fa-spin');
            var fileid = $(this).attr('id').split('-').pop();
            $.get('reindexpdf.php?file=' + fileid, function (answer) {
                if (answer !== '') {
                    $.jGrowl(answer, {
                        theme: 'jgrowl-error'
                    });
                } else {
                    $.jGrowl('PDF re-indexed.');
                }
                $t.removeClass('ui-state-disabled').children('i').removeClass('fa-spin');
            });
        });
        $('#file-panel .ocr').click(function () {
            timeId = setTimeout(dooverlay, 100);
            var $t = $(this);
            if ($t.hasClass('ui-state-disabled'))
                return false;
            $t.addClass('ui-state-disabled');
            var fileid = $t.closest('tr').data('fileid');
            $.get('ocr.php?file=' + fileid, function (answer) {
                if (answer !== '') {
                    $.jGrowl(answer, {
                        theme: 'jgrowl-error'
                    });
                } else {
                    $.jGrowl('PDF text extracted.');
                    $('#file-files').click();
                }
                $t.removeClass('ui-state-disabled');
                clearoverlay();
            });
        });
        $('#filelist .video').click(function () {
            var file = $(this).closest('tr').attr('id').substr(4), $videocontainer = $(this).closest('tr').next('tr').find('.videocontainer'),
                    extension = file.split('.').pop(), medium = {}, suppl = '';
            if (extension === 'm4v') {
                medium = {'m4a': location.href.replace('index2.php', '') + '/attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'm4v';
            }
            if (extension === 'ogv') {
                medium = {'ogv': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'ogv';
            }
            if (extension === 'webmv') {
                medium = {'webmv': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'webmv';
            }
            if (extension === 'flv') {
                medium = {'flv': location.href.replace('index2.php', '') + '/attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'flv';
            }
            if ($videocontainer.is(':hidden')) {
                $('.audiocontainer,.videocontainer').empty().hide();
                $videocontainer.html('<div id="jquery_jplayer_1" class="jp-jplayer"></div>' +
                        '<div id="jp_container_1">' +
                        '<div class="jp-gui ui-widget ui-widget-content ui-corner-all">' +
                        '<ul>' +
                        '<li class="jp-play ui-state-default ui-corner-all"><a href="javascript:;" class="jp-play ui-icon ui-icon-play" tabindex="1" title="play">play</a></li>' +
                        '<li class="jp-pause ui-state-default ui-corner-all"><a href="javascript:;" class="jp-pause ui-icon ui-icon-pause" tabindex="1" title="pause">pause</a></li>' +
                        '<li class="jp-stop ui-state-default ui-corner-all"><a href="javascript:;" class="jp-stop ui-icon ui-icon-stop" tabindex="1" title="stop">stop</a></li>' +
                        '<li class="jp-mute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-mute ui-icon ui-icon-volume-off" tabindex="1" title="mute">mute</a></li>' +
                        '<li class="jp-unmute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-unmute ui-icon ui-icon-volume-off" tabindex="1" title="unmute">unmute</a></li>' +
                        '<li class="jp-volume-max ui-state-default ui-corner-all"><a href="javascript:;" class="jp-volume-max ui-icon ui-icon-volume-on" tabindex="1" title="max volume">max volume</a></li>' +
                        '</ul>' +
                        '<div class="jp-progress-slider"></div>' +
                        '<div class="jp-volume-slider"></div>' +
                        '<div class="jp-current-time"></div>' +
                        '<div class="jp-duration"></div>' +
                        '<div class="jp-clearboth"></div>' +
                        '</div>' +
                        '</div>').show();
                var myPlayer = $("#jquery_jplayer_1"), myPlayerData,
                        fixFlash_mp4, // Flag: The m4a and m4v Flash player gives some old currentTime values when changed.
                        fixFlash_mp4_id, // Timeout ID used with fixFlash_mp4
                        ignore_timeupdate, // Flag used with fixFlash_mp4
                        options = {
                            ready: function (event) {
                                // Hide the volume slider on mobile browsers. ie., They have no effect.
                                if (event.jPlayer.status.noVolume) {
                                    // Add a class and then CSS rules deal with it.
                                    $(".jp-gui").addClass("jp-no-volume");
                                }
                                // Determine if Flash is being used and the mp4 media type is supplied. BTW, Supplying both mp3 and mp4 is pointless.
                                fixFlash_mp4 = event.jPlayer.flash.used && /m4a|m4v/.test(event.jPlayer.options.supplied);
                                // Setup the player with media.
                                $(this).jPlayer("setMedia", medium).jPlayer('play');
                            },
                            timeupdate: function (event) {
                                if (!ignore_timeupdate) {
                                    myControl.progress.slider("value", event.jPlayer.status.currentPercentAbsolute);
                                }
                            },
                            volumechange: function (event) {
                                if (event.jPlayer.options.muted) {
                                    myControl.volume.slider("value", 0);
                                } else {
                                    myControl.volume.slider("value", event.jPlayer.options.volume);
                                }
                            },
                            swfPath: "js/jplayer",
                            supplied: suppl,
                            cssSelectorAncestor: "#jp_container_1",
                            wmode: "window",
                            size: {width: "430px"}
                        },
                myControl = {
                    progress: $(options.cssSelectorAncestor + " .jp-progress-slider"),
                    volume: $(options.cssSelectorAncestor + " .jp-volume-slider")
                };
                // Instance jPlayer
                myPlayer.jPlayer(options);
                // A pointer to the jPlayer data object
                myPlayerData = myPlayer.data("jPlayer");
                // Define hover states of the buttons
                $('.jp-gui ul li').hover(
                        function () {
                            $(this).addClass('ui-state-hover');
                        },
                        function () {
                            $(this).removeClass('ui-state-hover');
                        }
                );
                // Create the progress slider control
                myControl.progress.slider({
                    animate: "fast",
                    max: 100,
                    range: "min",
                    step: 0.1,
                    value: 0,
                    slide: function (event, ui) {
                        var sp = myPlayerData.status.seekPercent;
                        if (sp > 0) {
                            // Apply a fix to mp4 formats when the Flash is used.
                            if (fixFlash_mp4) {
                                ignore_timeupdate = true;
                                clearTimeout(fixFlash_mp4_id);
                                fixFlash_mp4_id = setTimeout(function () {
                                    ignore_timeupdate = false;
                                }, 1000);
                            }
                            // Move the play-head to the value and factor in the seek percent.
                            myPlayer.jPlayer("playHead", ui.value * (100 / sp));
                        } else {
                            // Create a timeout to reset this slider to zero.
                            setTimeout(function () {
                                myControl.progress.slider("value", 0);
                            }, 0);
                        }
                    }
                });
                // Create the volume slider control
                myControl.volume.slider({
                    animate: "fast",
                    max: 1,
                    range: "min",
                    step: 0.1,
                    value: $.jPlayer.prototype.options.volume,
                    slide: function (event, ui) {
                        myPlayer.jPlayer("option", "muted", false);
                        myPlayer.jPlayer("option", "volume", ui.value);
                    }
                });
            } else {
                $videocontainer.html('').hide();
            }
        });
        $('#filelist .audio').click(function () {
            var file = $(this).closest('tr').attr('id').substr(4), $audiocontainer = $(this).closest('tr').next('tr').find('.audiocontainer'),
                    extension = file.split('.').pop(), medium = {}, suppl = '';
            if (extension === 'mp3') {
                medium = {'mp3': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'mp3';
            }
            if (extension === 'm4a') {
                medium = {'m4a': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'm4a';
            }
            if (extension === 'oga' || extension === 'ogg') {
                medium = {'oga': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'oga';
            }
            if (extension === 'webma') {
                medium = {'webma': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'webma';
            }
            if (extension === 'fla') {
                medium = {'fla': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'fla';
            }
            if (extension === 'wav') {
                medium = {'wav': 'attachment.php?view=inlin&attachment=' + escape(file)};
                suppl = 'wav';
            }
            if ($audiocontainer.is(':hidden')) {
                $('.audiocontainer,.videocontainer').empty().hide();
                $audiocontainer.html('<div id="jquery_jplayer_1" class="jp-jplayer"></div>' +
                        '<div id="jp_container_1">' +
                        '<div class="jp-gui ui-widget ui-widget-content ui-corner-all">' +
                        '<ul>' +
                        '<li class="jp-play ui-state-default ui-corner-all"><a href="javascript:;" class="jp-play ui-icon ui-icon-play" tabindex="1" title="play">play</a></li>' +
                        '<li class="jp-pause ui-state-default ui-corner-all"><a href="javascript:;" class="jp-pause ui-icon ui-icon-pause" tabindex="1" title="pause">pause</a></li>' +
                        '<li class="jp-stop ui-state-default ui-corner-all"><a href="javascript:;" class="jp-stop ui-icon ui-icon-stop" tabindex="1" title="stop">stop</a></li>' +
                        '<li class="jp-mute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-mute ui-icon ui-icon-volume-off" tabindex="1" title="mute">mute</a></li>' +
                        '<li class="jp-unmute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-unmute ui-icon ui-icon-volume-off" tabindex="1" title="unmute">unmute</a></li>' +
                        '<li class="jp-volume-max ui-state-default ui-corner-all"><a href="javascript:;" class="jp-volume-max ui-icon ui-icon-volume-on" tabindex="1" title="max volume">max volume</a></li>' +
                        '</ul>' +
                        '<div class="jp-progress-slider"></div>' +
                        '<div class="jp-volume-slider"></div>' +
                        '<div class="jp-current-time"></div>' +
                        '<div class="jp-duration"></div>' +
                        '<div class="jp-clearboth"></div>' +
                        '</div>' +
                        '</div>').show();
                var myPlayer = $("#jquery_jplayer_1"), myPlayerData,
                        fixFlash_mp4, // Flag: The m4a and m4v Flash player gives some old currentTime values when changed.
                        fixFlash_mp4_id, // Timeout ID used with fixFlash_mp4
                        ignore_timeupdate, // Flag used with fixFlash_mp4
                        options = {
                            ready: function (event) {
                                // Hide the volume slider on mobile browsers. ie., They have no effect.
                                if (event.jPlayer.status.noVolume) {
                                    // Add a class and then CSS rules deal with it.
                                    $(".jp-gui").addClass("jp-no-volume");
                                }
                                // Determine if Flash is being used and the mp4 media type is supplied. BTW, Supplying both mp3 and mp4 is pointless.
                                fixFlash_mp4 = event.jPlayer.flash.used && /m4a|m4v/.test(event.jPlayer.options.supplied);
                                // Setup the player with media.
                                $(this).jPlayer("setMedia", medium).jPlayer('play');
                            },
                            timeupdate: function (event) {
                                if (!ignore_timeupdate) {
                                    myControl.progress.slider("value", event.jPlayer.status.currentPercentAbsolute);
                                }
                            },
                            volumechange: function (event) {
                                if (event.jPlayer.options.muted) {
                                    myControl.volume.slider("value", 0);
                                } else {
                                    myControl.volume.slider("value", event.jPlayer.options.volume);
                                }
                            },
                            swfPath: "js/jplayer",
                            supplied: suppl,
                            cssSelectorAncestor: "#jp_container_1",
                            wmode: "window"
                        },
                myControl = {
                    progress: $(options.cssSelectorAncestor + " .jp-progress-slider"),
                    volume: $(options.cssSelectorAncestor + " .jp-volume-slider")
                };
                // Instance jPlayer
                myPlayer.jPlayer(options);
                // A pointer to the jPlayer data object
                myPlayerData = myPlayer.data("jPlayer");
                // Define hover states of the buttons
                $('.jp-gui ul li').hover(
                        function () {
                            $(this).addClass('ui-state-hover');
                        },
                        function () {
                            $(this).removeClass('ui-state-hover');
                        }
                );
                // Create the progress slider control
                myControl.progress.slider({
                    animate: "fast",
                    max: 100,
                    range: "min",
                    step: 0.1,
                    value: 0,
                    slide: function (event, ui) {
                        var sp = myPlayerData.status.seekPercent;
                        if (sp > 0) {
                            // Apply a fix to mp4 formats when the Flash is used.
                            if (fixFlash_mp4) {
                                ignore_timeupdate = true;
                                clearTimeout(fixFlash_mp4_id);
                                fixFlash_mp4_id = setTimeout(function () {
                                    ignore_timeupdate = false;
                                }, 1000);
                            }
                            // Move the play-head to the value and factor in the seek percent.
                            myPlayer.jPlayer("playHead", ui.value * (100 / sp));
                        } else {
                            // Create a timeout to reset this slider to zero.
                            setTimeout(function () {
                                myControl.progress.slider("value", 0);
                            }, 0);
                        }
                    }
                });
                // Create the volume slider control
                myControl.volume.slider({
                    animate: "fast",
                    max: 1,
                    range: "min",
                    step: 0.1,
                    value: $.jPlayer.prototype.options.volume,
                    slide: function (event, ui) {
                        myPlayer.jPlayer("option", "muted", false);
                        myPlayer.jPlayer("option", "volume", ui.value);
                    }
                });
            } else {
                $audiocontainer.html('').hide();
            }
        });
    }
};
var advancedsearch = {
    init: function () {
        common.init();
        $('#advancedsearchform input[name="anywhere"]').focus();
        $('#advancedsearchtabs').click(function (event) {
            var $t = $(event.target);
            $(this).find('div').removeClass('clicked');
            if ($t.attr('id') === 'advtab-search-ref') {
                $('#advancedsearchform .refrow').show();
                $('#fulltextrow, #notesrow, #pdfnotesrow').hide();
                $('#advancedsearchform input[name="searchtype"]').val('metadata');
            } else
            if ($t.attr('id') === 'advtab-search-pdf') {
                $('#advancedsearchform .refrow, #notesrow, #pdfnotesrow').hide();
                $('#fulltextrow').show();
                $('#advancedsearchform input[name="searchtype"]').val('pdf');
            } else
            if ($t.attr('id') === 'advtab-search-pdfnotes') {
                $('#advancedsearchform .refrow, #fulltextrow, #notesrow').hide();
                $('#pdfnotesrow').show();
                $('#advancedsearchform input[name="searchtype"]').val('pdfnotes');
            } else
            if ($t.attr('id') === 'advtab-search-notes') {
                $('#advancedsearchform .refrow, #fulltextrow, #pdfnotesrow').hide();
                $('#notesrow').show();
                $('#advancedsearchform input[name="searchtype"]').val('notes');
            }
        });
        $('#advancedsearchtabs')
                .buttonset()
                .buttonset('widget')
                .find('.ui-corner-left')
                .removeClass('ui-corner-left')
                .addClass('ui-corner-tl')
                .end()
                .find('.ui-corner-right')
                .removeClass('ui-corner-right')
                .addClass('ui-corner-tr');
        $('#advancedsearchform').submit(function (e) {
            var searchval = $(this).find(':text:visible').map(function () {
                return $(this).val();
            }).get().join(''), sel = $('body').data('sel'), proj = $('body').data('proj');
            if (proj === undefined)
                proj = '';
            if (searchval !== '') {
                $("#advancedsearch").dialog('disable');
                var q = $(this).formSerialize();
                timeId = setTimeout(dooverlay, 1000);
                $('#right-panel').load('search.php?select=' + sel + '&project=' + proj + '&' + q, function () {
                    clearoverlay();
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, 'search.php?select=' + sel + '&project=' + proj + '&' + q);
                    $("#advancedsearch").dialog('enable').dialog('close');
                });
            }
            e.preventDefault();
        });
        $("#advanced-filter").keyup(function () {
            var str = $(this).val(), $container = $('#advancedsearchform input[name="category\\[\\]"]').closest('tr');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $container.hide().filter(function () {
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function () {
            $(this).val('');
            $('#advancedsearchform input[name="category\\[\\]"]').closest('tr').show();
        });
    }
};
var expertsearch = {
    init: function () {
        common.init();
        $('#expertsearchform textarea:visible').focus();
        $('#expertsearchform').find('button').button().tipsy({
            gravity: 's'
        }).click(function () {
            var buttontext = $(this).children().text(), $txa = $('#expertsearchform textarea:visible'), textareatext = $txa.val();
            $txa.insertAtCaret(' ' + buttontext + ' ');
            textareatext = $txa.val();
            $txa.val(textareatext.replace(/\s+/gi, ' '));
            $('.expertsearchform textarea').keyup();
            return false;
        });
        $('#expertsearchtabs').click(function (event) {
            var $t = $(event.target);
            $(this).find('div').removeClass('clicked');
            if ($t.attr('id') === 'tab-search-ref') {
                $('#expertsearchform textarea').hide().eq(0).show().focus();
                $('.metadata-buttons').css('visibility', 'visible');
                $('#expertsearchform input[name="searchtype"]').val('metadata');
            } else
            if ($t.attr('id') === 'tab-search-pdf') {
                $('#expertsearchform textarea').hide().eq(1).show().focus();
                $('.metadata-buttons').css('visibility', 'hidden');
                $('#expertsearchform input[name="searchtype"]').val('pdf');
            } else
            if ($t.attr('id') === 'tab-search-pdfnotes') {
                $('#expertsearchform textarea').hide().eq(2).show().focus();
                $('.metadata-buttons').css('visibility', 'hidden');
                $('#expertsearchform input[name="searchtype"]').val('pdfnotes');
            } else
            if ($t.attr('id') === 'tab-search-notes') {
                $('#expertsearchform textarea').hide().eq(3).show().focus();
                $('.metadata-buttons').css('visibility', 'hidden');
                $('#expertsearchform input[name="searchtype"]').val('notes');
            }
        });
        $('#expertsearchtabs')
                .buttonset()
                .buttonset('widget')
                .find('.ui-corner-left')
                .removeClass('ui-corner-left')
                .addClass('ui-corner-tl')
                .end()
                .find('.ui-corner-right')
                .removeClass('ui-corner-right')
                .addClass('ui-corner-tr');
        $('#expertsearchform').submit(function (e) {
            var searchval = $(this).find('textarea:visible').val(), sel = $('body').data('sel'), proj = $('body').data('proj');
            if (proj === undefined)
                proj = '';
            if (searchval !== '') {
                $("#expertsearch").dialog('disable');
                var q = $(this).formSerialize();
                timeId = setTimeout(dooverlay, 1000);
                $('#right-panel').load('search.php?select=' + sel + '&project=' + proj + '&' + q, function () {
                    clearoverlay();
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, 'search.php?select=' + sel + '&project=' + proj + '&' + q);
                    $("#expertsearch").dialog('enable').dialog('close');
                });
            }
            e.preventDefault();
        });
        $("#expert-filter").keyup(function () {
            var str = $(this).val(), $container = $('#expertsearchform input[name="category\\[\\]"]').closest('tr');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $container.hide().filter(function () {
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function () {
            $(this).val('');
            $('#expertsearchform input[name="category\\[\\]"]').closest('tr').show();
        });
    }
};
var upload = {
    init: function () {
        common.init();
        authorinputs.init(false);
        editorinputs.init(false);
        $('.upload-errors').each(function () {
            var err = $(this).html();
            if (err)
                $.jGrowl(err, {
                    life: 5000,
                    theme: 'jgrowl-error'
                });
            $(this).remove();
        });
        $('.uploadsave').unbind('click').click(function (e) {
            e.preventDefault();
            var $t = $(this), $f = $t.closest(".uploadform"), title = $f.find('textarea[name="title"]').val(), proxystr;
            $t.button("option", "disabled", true);
            if (title !== '' && title !== undefined) {
                $.jGrowl('Document recording has started. You may continue to work.');
                if ($f.parent('div').hasClass('save_container')) {
                    $f.parent('div').empty().hide();
                } else {
                    $('#addarticle-right').load('upload.php', function () {
                        upload.init();
                    });
                }
                $.getScript('wpad.php', function () {
                    proxystr = FindProxyForURL('', 'www.crossref.org');
                    $f.ajaxSubmit({
                        data: {
                            'proxystr': proxystr
                        },
                        dataType: 'json',
                        success: function (answer) {
                            if (answer['error'] !== undefined) {
                                $.each(answer['error'], function (key, err) {
                                    $.jGrowl(err, {
                                        theme: 'jgrowl-error'
                                    });
                                });
                            }
                            if (answer['message'] !== undefined) {
                                $.each(answer['message'], function (key, mess) {
                                    mess = mess.replace(/&lt;/g, '<');
                                    mess = mess.replace(/&gt;/g, '>');
                                    $.jGrowl(mess);
                                });
                            }
                        }
                    });
                });
            } else {
                if ($f.find('textarea[name="title"]').length === 1) {
                    $.jGrowl('Title is required.');
                    $t.button("option", "disabled", false);
                    return false;
                }
                var $uidinp = $f.find('input[name="uid\\[\\]"]'),
                        $sel = $f.find('select').val();
                if ($sel !== '') {
                    $uidinp.val($sel + ':' + $uidinp.val());
                }
                timeId = setTimeout(dooverlay, 1000);
                $.getScript('wpad.php', function () {
                    proxystr = FindProxyForURL('', 'www.crossref.org');
                    $f.ajaxSubmit({
                        data: {
                            'proxystr': proxystr
                        },
                        success: function (answer) {
                            clearoverlay();
                            if (answer.substring(0, 5) === 'Error') {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error',
                                    life: 6000
                                });
                                $t.button("option", "disabled", false);
                            } else {
                                $('#addarticle-right').empty().html(answer);
                                upload.init();
                            }
                        }
                    });
                });
            }
        }).button();
        $(".open1").unbind('click').click(function () {
            var $t = $(this).closest('table');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $t.siblings(".table1").show();
            $t.siblings(".table2").hide();
            $t.siblings(".table3").hide();
        });
        $(".open2").unbind('click').click(function () {
            var $t = $(this).closest('table');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $t.siblings(".table1").hide();
            $t.siblings(".table2").show();
            $t.siblings(".table3").hide();
        });
        $(".open3").unbind('click').click(function () {
            var $t = $(this).closest('table');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $t.siblings(".table1").hide();
            $t.siblings(".table2").hide();
            $t.siblings(".table3").show();
        });
        $(".suggestions").unbind('click').click(function (e) {
            var $target = $(e.target), val = $target.text().trim(), $td = $target.parent().parent().find('td.select_span').filter(function () {
                return $(this).text().trim() === val;
            });
            if ($td.is(':not(:animated)')) {
                $td.click().stop(true, true)
                        .animate({
                            'padding-left': 20
                        }, 200).animate({
                    'padding-left': 0
                }, 200)
                        .animate({
                            'padding-left': 2
                        }, 50).animate({
                    'padding-left': 0
                }, 50);
            }
        });
        $('.addurlrow').unbind('click').click(function () {
            $(this).closest('tr').after('<tr><td class="threedleft">URL:</td><td class="threedright"><input type="text" size="80" name="url[]" style="width: 99%" value=""></td></tr>');
        });
        $('.adduidrow').unbind('click').click(function () {
            $(this).closest('tr').after('<tr><td class="threedleft">Database UID:</td><td class="threedright"><input type="text" size="80" name="uid[]" style="width: 99%" value=""></td></tr>');
        });
        $('.addauthorrow').unbind('click').click(function () {
            $(this).closest('.author-inputs')
                    .append('<div class="new">Last name: <input type="text" name="last_name[]" value=""> First name: <input type="text" name="first_name[]" value=""></div>');
            $(this).closest('.editor-inputs')
                    .append('<div class="new">Last name: <input type="text" value=""> First name: <input type="text" value=""></div>');
            authorinputs.init(true);
            editorinputs.init(true);
        });
        $('.uploadform input[name="uid\\[\\]"]').tipsy({
            html: true,
            gravity: 'e'
        });
        $('.uploadform input[name="secondary_title"]').tipsy({
            html: true,
            gravity: 'e'
        });
        $('.uploadform textarea[name="title"]').tipsy({
            html: true,
            gravity: 'e'
        });
        $('.uploadform textarea[name="keywords"]').tipsy({
            html: true,
            gravity: 'e'
        });
        $('.uploadform input[name="journal_abbr"]').autocomplete({
            source: "ajaxjournals.php?search=journal",
            minLength: 1
        });
        $('.uploadform input[name="secondary_title"]').autocomplete({
            source: "ajaxjournals.php?search=secondary_title",
            minLength: 1
        });
        $('#addarticle-right').scroll(function () {
            $('.uploadform input[name="journal_abbr"],.uploadform input[name="secondary_title"]').autocomplete('close');
        });
        $('#button-none').click(function (e) {
            e.preventDefault();
            $('#addarticle-right').load('upload.php?none=1', function () {
                upload.init();
            });
        }).button();
        $('select[name="reference_type"]').on('change', function () {
            var type = $(this).find('option:selected').text();
            if (type === 'article') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Full journal name:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'book') {
                $('.td-title').text('Book title:');
                $('.td-secondary-title').text('Series title:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'chapter') {
                $('.td-title').text('Chapter title:');
                $('.td-secondary-title').text('Book title:');
                $('.td-tertiary-title').text('Series title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'thesis') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('School:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'conference') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Conference:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            } else if (type === 'patent') {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Source:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Assignee:');
            } else {
                $('.td-title').text('Title:');
                $('.td-secondary-title').text('Secondary title:');
                $('.td-tertiary-title').text('Tertiary title:');
                $('.td-affiliation').text('Affiliation:');
            }
        });
        $('#uid-help').tipsy();
        $("#filtercategories").unbind('keyup').keyup(function () {
            var str = $(this).val(), $container = $('.categorydiv').find(':checkbox').closest('tr');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $container.hide().filter(function () {
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function () {
            $(this).val('');
            $(":checkbox").closest('tr').show();
        });
    }
};
var leftindex = {
    init: function (sel, displayref) {
        common.init();
        $('body').data('sel', sel);
        var lw = 233;
        if (localStorage.getItem('lwidth') !== undefined)
            lw = localStorage.getItem('lwidth');
        if (lw === '0') {
            $('#leftindex-left').hide();
            $('#bottom-panel .middle-panel i').removeClass('fa-caret-left').addClass('fa-caret-right');
        }
        if (displayref === '' || displayref === undefined)
            displayref = "display.php?browse[]=all&select=" + sel;
        $('body').data('right-panel-url', displayref);
        timeId = setTimeout(dooverlay, 500);
        $('#right-panel').load(displayref, function () {
            clearoverlay();
            displaywindow.init(sel, displayref);
        });
        $("#bottom-panel .middle-panel").click(function () {
            var $l = $(this).prev();
            if ($l.is(':hidden')) {
                $l.show();
                localStorage.setItem('lwidth', '233');
                $(this).children().removeClass('fa-caret-right').addClass('fa-caret-left');
            } else {
                $l.hide();
                localStorage.setItem('lwidth', '0');
                $(this).children().removeClass('fa-caret-left').addClass('fa-caret-right');
            }
        });
        $("#advancedsearchbutton").click(function () {
            $(this).blur();
            $("#advancedsearch").load('advancedsearch.php?select=' + sel, function () {
                $("#advancedsearch").dialog('option', 'title', 'Advanced search of ' + sel).dialog('open');
                advancedsearch.init();
            });
        }).button().button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        $("#expertsearchbutton").click(function () {
            $(this).blur();
            $("#expertsearch").load('expertsearch.php?select=' + sel, function () {
                $("#expertsearch").dialog('option', 'title', 'Expert search of ' + sel).dialog('open');
                expertsearch.init();
            });
        }).button().button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        //////////////////////////////quick search///////////////////////////////////
        var radioRows = [
            '#global_separator',
            '#anywhere_separator',
            '#fulltext_separator',
            '#pdfnotes_separator',
            '#notes_separator'];
        for (var i = 0; i < radioRows.length; i++) {
            $(radioRows[i]).buttonset()
                .buttonset('widget')
                .find('label')
                .removeClass('ui-corner-left')
                .removeClass('ui-corner-right');
        }
        $("#quicksearch #search").button().click(function () {
            $(this).blur();
            if ($("#quicksearch input:text:visible").val() === '')
                return false;
            var q = $("#quicksearch").formSerialize();
            timeId = setTimeout(dooverlay, 500);
            $('#right-panel').load('search.php?' + q, function () {
                clearoverlay();
                $("#right-panel").scrollTop(0);
                displaywindow.init(sel, 'search.php?' + q);
            });
            return false;
        }).button('widget')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        $("#quicksearch #clear").button().click(function () {
            $(this).blur();
            $("#quicksearch input:text:visible").val('').focus();
            $("#quicksearch input[value='AND']").parent('td.select_span').click();
            $.get('search.php?newsearch=1');
        }).button('widget')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        $("#quicksearch").submit(function () {
            $("#quicksearch #search").click();
            return false;
        });
        $('#search-menu div:eq(0)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(0).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(0).show();
            $("#quicksearch").find('.separators').eq(0).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('global');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 0);
        });
        $('#search-menu div:eq(1)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(1).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(1).show();
            $("#quicksearch").find('.separators').eq(1).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('metadata');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 1);
        });
        $('#search-menu div:eq(2)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(2).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(2).show();
            $("#quicksearch").find('.separators').eq(2).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('pdf');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 2);
        });
        $('#search-menu div:eq(3)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(3).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(3).show();
            $("#quicksearch").find('.separators').eq(3).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('pdfnotes');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 3);
        });
        $('#search-menu div:eq(4)').click(function () {
            // switch classes
            $(this).siblings().removeClass('tabclicked');
            $(this).addClass('tabclicked');
            // show text field and separators
            $(".quicksearch").find(':text').hide().attr('disabled', true);
            $(".quicksearch").find(':text').eq(4).show().attr('disabled', false);
            $("#quicksearch").find('.separators').hide();
            $("#quicksearch").find(':radio').attr('disabled', true);
            $("#quicksearch").find('.separators').eq(4).show();
            $("#quicksearch").find('.separators').eq(4).find(':radio').attr('disabled', false);
            // change hidden fields
            $('#quicksearch input[name="searchtype"]').val('notes');
            $('#quicksearch input[name="searchmode"]').val('advanced');
            // save state to localstorage
            localStorage.setItem("search-menu-tab", 4);
        });
        $('#search-menu div').tipsy({'gravity': 'nw'});
        // clicked tab memory
        var clickedTab = localStorage.getItem("search-menu-tab");
        if (!clickedTab)
            clickedTab = 0;
        $('#search-menu div:eq(' + clickedTab + ')').click();
        /////////////////////////////button effects///////////////////////////////////
        $('#leftindex-left').find('button.menu').each(function(){
            $(this).button()
                .button('widget')
                .removeClass('ui-corner-all')
                .click(function () {
                    $(this).removeClass('ui-state-focus');
                });
        });
        /////////////////////////////category navigation///////////////////////////////////

        $("#categorylink").click(function () {
            $(this).blur();
            var $first_categories = $("#first_categories"), $categories_top_container = $("#categories_top_container");
            $first_categories.html('');
            $categories_top_container.toggle();
            if ($categories_top_container.css('display') !== 'none') {
                $.get('ajaxleftindex.php', 'open[]=category&select=' + sel, function (answer) {
                    $first_categories.html(answer);
                }, 'html');
                return false;
            }
        });
        ///////////////////////////////filter categories///////////////////////////

        $("#filter_categories").keyup(function () {
            var str = $(this).val(), $span = $('#first_categories > div > div');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp(str, 'i');
                $span.hide().filter(function () {
                    return re.test($(this).children('span').text());
                }).show();
            } else {
                $('#first_categories > div > div').show();
            }
        }).focus(function () {
            $(this).val('');
            $('#first_categories > div > div').show();
        });
        ///////////////////////////////category tree///////////////////////////

        $('#first_categories').click(function (event) {
            var $t = $(event.target), urlstring = '';
            if ($t.hasClass('cat1')) {
                var $divid = $t.next('div'), category1 = $t.parent().attr('id').split('-').pop(),
                        ref = 'display.php?browse[' + category1 + ']=category&select=' + sel;
                $("span.cat1").removeClass('clicked');
                $t.addClass('clicked');
                if (category1 > 0) {
                    urlstring = "open[" + category1 + "]=category&select=" + sel;
                    $.get('ajaxleftindex.php', urlstring, function (answer) {
                        $("span.cat1").next('div').each(function () {
                            $(this).html('').hide();
                        });
                        $divid.html(answer).show();
                    }, 'html');
                } else {
                    $("span.cat1").next('div').each(function () {
                        $(this).html('').hide();
                    });
                }
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                event.preventDefault();
            } else
            if ($t.hasClass('cat2')) {
                var $divid = $t.next('div'),
                        category1 = $t.parent().parent().parent().attr('id').split('-').pop(),
                        category2 = $t.parent().attr('id').split('-').pop(),
                        ref = 'display.php?browse[' + category1 + ']=category&browse[' + category2 + ']=category&select=' + sel;
                $("span.cat2").removeClass('clicked');
                $t.addClass('clicked');
                if (category1 !== '')
                    urlstring = "open[" + category1 + "]=category&select=" + sel;
                if (category2 !== '')
                    urlstring = "open[" + category1 + "]=category&open[" + category2 + "]=category&select=" + sel;
                $.get('ajaxleftindex.php', urlstring, function (answer) {
                    $("span.cat2").next('div').each(function () {
                        $(this).html('').hide();
                    });
                    $divid.html(answer).show();
                }, 'html');
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                event.preventDefault();
            } else
            if ($t.hasClass('cat3')) {
                var $divid = $t.next('div'),
                        category1 = $t.parent().parent().parent().parent().parent().attr('id').split('-').pop(),
                        category2 = $t.parent().parent().parent().attr('id').split('-').pop(),
                        category3 = $t.parent().attr('id').split('-').pop(),
                        ref = 'display.php?browse[' + category1 + ']=category&browse[' + category2 + ']=category&browse[' + category3 + ']=category&select=' + sel;
                $("span.cat3").removeClass('clicked');
                $t.addClass('clicked');
                if (category1 !== '')
                    urlstring = "open[" + category1 + "]=category&select=" + sel;
                if (category2 !== '')
                    urlstring = "open[" + category1 + "]=category&open[" + category2 + "]=category&select=" + sel;
                if (category3 !== '')
                    urlstring = "open[" + category1 + "]=category&open[" + category2 + "]=category&open[" + category3 + "]=category&select=" + sel;
                $.get('ajaxleftindex.php', urlstring, function (answer) {
                    $("span.cat3").next('div').each(function () {
                        $(this).html('').hide();
                    });
                    $divid.html(answer).show();
                }, 'html');
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                event.preventDefault();
            } else
            if ($t.hasClass('cat4')) {
                var category1 = $t.parent().parent().parent().parent().parent().parent().attr('id').split('-').pop(),
                        category2 = $t.parent().parent().parent().parent().attr('id').split('-').pop(),
                        category3 = $t.parent().parent().attr('id').split('-').pop(),
                        category4 = $t.attr('id').split('-').pop(),
                        ref = 'display.php?browse[' + category1 + ']=category&browse[' + category2 + ']=category&browse[' + category3 + ']=category&browse[' + category4 + ']=category&select=' + sel;
                $("span.cat4").removeClass('clicked');
                $t.addClass('clicked');
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                event.preventDefault();
            }
        });
        /////////////////////////////addition date navigation///////////////////////////////////

        $('#additiondatelink').click(function () {
            $(this).blur();
            var $datepicker = $('#datepicker');
            $datepicker.toggle();
            if ($datepicker.is(':hidden')) {
                $datepicker.datepicker('destroy');
                return false;
            }
            timeId = setTimeout(dooverlay, 500);
            $.getJSON('ajaxleftindex.php?open[]=dates&select=' + sel, function (answer) {
                clearoverlay();
                var mindt = answer['mindate'], maxdt = answer['maxdate'];
                if (maxdt === '') {
                    $datepicker.text('No items.');
                    return false;
                }
                $('#datepicker').datepicker({
                    inline: true,
                    firstDay: 1,
                    dateFormat: 'yy-mm-dd',
                    minDate: mindt,
                    maxDate: maxdt,
                    beforeShowDay: function (date) {
                        var y = date.getFullYear(), m = 1 + date.getMonth(), d = date.getDate();
                        if (m < 10)
                            m = "0" + m;
                        if (d < 10)
                            d = "0" + d;
                        var f = y + "-" + m + "-" + d;
                        if (answer.datecount[f] === undefined) {
                            return [false, '', 'no items added on this day'];
                        } else {
                            var s = '';
                            if (answer.datecount[f] > 1)
                                s = "s";
                            return [true, '', answer.datecount[f] + ' item' + s];
                        }
                    },
                    onSelect: function (dateText) {
                        timeId = setTimeout(dooverlay, 500);
                        $('#right-panel').load('display.php?browse[' + dateText + ']=addition_date&select=' + sel, function () {
                            clearoverlay();
                            $("#right-panel").scrollTop(0);
                            displaywindow.init(sel, 'display.php?browse[' + dateText + ']=addition_date&select=' + sel);
                        });
                    }
                });
            });
        });
        /////////////////////////////author navigation///////////////////////////////////

        $("#authorlink").click(function () {
            $(this).blur();
            var $authors_container = $("#authors_container"), $authors_top_container = $("#authors_top_container");
            $('body').data('letter', 'A');
            $authors_container.data("from", 0).empty();
            $("#filter_authors").focus().blur();
            $authors_top_container.toggle();
            $("#next_authors").hide();
            $("#prev_authors").hide();
            $("#prevprev_authors").hide();
            if ($authors_top_container.css('display') !== 'none') {
                $("#authors_header").find('.letter').removeClass('clicked').eq(0).addClass('clicked');
                timeId = setTimeout(dooverlay, 500);
                $.get('ajaxleftindex.php', 'open[]=authors&select=' + sel + '&first_letter=A&from=0', function (answer) {
                    if ($(answer).find('span').length === 1000) {
                        $("#next_authors").show();
                        $("#prev_authors").show();
                        $("#prevprev_authors").show();
                    }
                    $authors_container.html(answer);
                    clearoverlay();
                }, 'html');
                return false;
            }
        });
        $("#authors_header").click(function (e) {
            var target = e.target, $target = $(target);
            if (!$target.hasClass('letter'))
                return false;
            var $authors_container = $("#authors_container"), letter = $target.text();
            $('body').data('letter', letter);
            $authors_container.data("from", 0);
            timeId = setTimeout(dooverlay, 500);
            $.get('ajaxleftindex.php', 'open[]=authors&select=' + sel + '&first_letter=' + letter + '&from=0', function (answer) {
                $("#filtered_authors").empty();
                if ($(answer).find('span').length < 1000) {
                    $("#next_authors").hide();
                    $("#prev_authors").hide();
                    $("#prevprev_authors").hide();
                } else {
                    $("#next_authors").show();
                    $("#prev_authors").show();
                    $("#prevprev_authors").show();
                }
                $authors_container.show().html(answer);
                $target.siblings('span').removeClass('clicked');
                $target.addClass('clicked');
                clearoverlay();
            }, 'html');
        });
        $("#next_authors").click(function () {
            var letter = $('body').data('letter');
            var $authors_container = $("#authors_container");
            if ($authors_container.find('span').length < 1000)
                return false;
            if ($authors_container.data("from") === undefined)
                $authors_container.data("from", 0);
            var from = $authors_container.data("from") + 1000;
            timeId = setTimeout(dooverlay, 500);
            $.get('ajaxleftindex.php', 'open[]=authors&select=' + sel + '&first_letter=' + letter + '&from=' + from, function (answer) {
                $authors_container.html(answer);
                if ($authors_container.find('span').length < 1000)
                    $("#next_authors").hide();
                clearoverlay();
            }, 'html');
            $authors_container.data("from", from);
        });
        $("#prev_authors").click(function () {
            var letter = $('body').data('letter');
            var $authors_container = $("#authors_container");
            if ($authors_container.data("from") === undefined)
                $authors_container.data("from", 0);
            var from = $authors_container.data("from") - 1000;
            if (from < 0)
                return false;
            timeId = setTimeout(dooverlay, 500);
            $.get('ajaxleftindex.php', 'open[]=authors&select=' + sel + '&first_letter=' + letter + '&from=' + from, function (answer) {
                $authors_container.html(answer);
                $("#next_authors").show();
                clearoverlay();
            }, 'html');
            $authors_container.data("from", from);
        });
        $("#prevprev_authors").click(function () {
            var $authors_container = $("#authors_container"), letter = $('body').data('letter');
            if ($authors_container.data("from") === undefined || $authors_container.data("from") === 0)
                return false;
            timeId = setTimeout(dooverlay, 500);
            $.get('ajaxleftindex.php', 'open[]=authors&select=' + sel + '&first_letter=' + letter + '&from=0', function (answer) {
                $authors_container.html(answer);
                $("#next_authors").show();
                clearoverlay();
            }, 'html');
            $authors_container.data("from", 0);
        });
        $("#authors_container, #filtered_authors").click(function (e) {
            var target = e.target, $target = $(target);
            if ($target.is('span')) {
                e.preventDefault();
                timeId = setTimeout(dooverlay, 500);
                var auth = $target.attr('id'), ref = 'display.php?browse[' + auth + ']=authors&select=' + sel;
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                    clearoverlay();
                });
                $target.siblings('span').removeClass('clicked');
                $target.addClass('clicked');
            }
        });
        /////////////////////////////filter authors///////////////////////////////////
        var timeid2 = '';
        $("#filter_authors").keyup(function () {
            var $t = $(this);
            clearTimeout(timeid2);
            var filterstring = $t.val();
            if (filterstring.length > 1) {
                filterstring = encodeURI(filterstring);
                var $filtered_authors = $("#filtered_authors"), $authors_container = $("#authors_container");
                function getauthors(filterstring) {
                    timeId = setTimeout(dooverlay, 500);
                    $.get('ajaxfilter.php', 'open[]=authors&select=' + sel + '&filter=' + filterstring, function (answer) {
                        $authors_container.hide();
                        $("#next_authors").hide();
                        $("#prev_authors").hide();
                        $("#prevprev_authors").hide();
                        $filtered_authors.html(answer);
                        clearoverlay();
                    }, 'html');
                }
                timeid2 = setTimeout(function () {
                    getauthors(filterstring);
                }, 500);
            } else {
                var from = $('#authors_container').data('from');
                $("#authors_container").show();
                $("#filtered_authors").empty();
                if ($("#authors_container").find("span").length === 1000 || from > 0) {
                    $("#next_authors").show();
                    $("#prev_authors").show();
                    $("#prevprev_authors").show();
                }
            }
        }).focus(function () {
            $(this).val('');
            $("#filter_authors").trigger('keyup');
        }).blur(function () {
            if ($(this).val() === '') {
                $("#authors_container").show();
                $("#filtered_authors").empty();
            }
        });
        /////////////////////////////journals navigation///////////////////////////////////

        $("#journallink").click(function () {
            $(this).blur();
            var $journals_container = $("#journals_container"), $journals_top_container = $("#journals_top_container");
            $journals_container.html('');
            $journals_top_container.toggle();
            if ($journals_top_container.is(':visible')) {
                $.get('ajaxleftindex.php', 'open[]=journal&select=' + sel, function (answer) {
                    $journals_container.html(answer);
                }, 'html');
                return false;
            }
        });
        ///////////////////////////////filter journals///////////////////////////

        $('#filter_journals').keyup(function () {
            var str = $(this).val(), $element = $('#journals_container > div > div');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp('^' + str, 'i');
                $element.hide().filter(function () {
                    return re.test($(this).children('span').text());
                }).show();
            } else {
                $('#journals_container > div > div').show();
            }
        }).focus(function () {
            $(this).val('');
            $('#journals_container > div > div').show();
        });
        ///////////////////////////////journal tree///////////////////////////

        $("#journals_container").click(function (e) {
            var $t = $(e.target);
            if ($t.hasClass('jour')) {
                var $divid = $t.next('div'), journal = $t.parent().attr('id'), ref = 'display.php?browse[' + journal + ']=journal&select=' + sel;
                if ($("#journals_top_container").is(':visible') && $divid.is(':hidden')) {
                    var urlstring = '';
                    if (journal !== '')
                        urlstring = "open[" + journal + "]=journal&select=" + sel;
                    $.get('ajaxleftindex.php', urlstring, function (answer) {
                        $('.jour').next('div').each(function () {
                            $(this).html('').hide();
                        });
                        $('.jour').removeClass('clicked');
                        $t.addClass('clicked');
                        $divid.show().html(answer);
                    }, 'html');
                }
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
            } else
            if ($t.hasClass('jour2')) {
                var journal = $t.parent().parent().parent().attr('id'), year = $t.html(),
                        ref = 'display.php?browse[' + journal + ']=journal&browse[' + year + ']=year&select=' + sel;
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                $t.siblings().removeClass('clicked');
                $t.addClass('clicked');
            }
        });
        /////////////////////////////secondary titles navigation///////////////////////////////////

        $("#secondarytitlelink").click(function () {
            $(this).blur();
            var $secondarytitles_container = $("#secondarytitles_container"), $secondarytitles_top_container = $("#secondarytitles_top_container");
            $secondarytitles_container.html('');
            $secondarytitles_top_container.toggle();
            if ($secondarytitles_top_container.is(':visible')) {
                $.get('ajaxleftindex.php', 'open[]=secondary_title&select=' + sel, function (answer) {
                    $secondarytitles_container.html(answer);
                }, 'html');
                return false;
            }
        });
        ///////////////////////////////filter secondary titles///////////////////////////

        $("#filter_secondarytitles").keyup(function () {
            var str = $(this).val(), $element = $('#secondarytitles_container > div > div');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp('^' + str, 'i');
                $element.hide().filter(function () {
                    return re.test($(this).children('span').text());
                }).show();
            } else {
                $('#secondarytitles_container > div > div').show();
            }
        }).focus(function () {
            $(this).val('');
            $('#secondarytitles_container > div > div').show();
        });
        ///////////////////////////////secondary title tree///////////////////////////

        $("#secondarytitles_container").click(function (e) {
            var $t = $(e.target);
            if ($t.hasClass('sec')) {
                var $divid = $t.next('div'), sec = $t.parent().attr('id'), ref = 'display.php?browse[' + sec + ']=secondary_title&select=' + sel;
                if ($("#secondarytitles_top_container").is(':visible') && $divid.is(':hidden')) {
                    var urlstring = '';
                    if (sec !== '')
                        urlstring = "open[" + sec + "]=secondary_title&select=" + sel;
                    $.get('ajaxleftindex.php', urlstring, function (answer) {
                        $(".sec").next('div').each(function () {
                            $(this).html('').hide();
                        });
                        $(".sec").removeClass('clicked');
                        $t.addClass('clicked');
                        $divid.show().html(answer);
                    }, 'html');
                }
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                e.preventDefault();
            } else
            if ($t.hasClass('sec2')) {
                var sec = $t.parent().parent().parent().attr('id'), year = $t.html(),
                        ref = 'display.php?browse[' + sec + ']=secondary_title&browse[' + year + ']=year&select=' + sel;
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                $t.siblings().removeClass('clicked');
                $t.addClass('clicked');
            }
        });
        /////////////////////////////tertiary titles navigation///////////////////////////////////

        $("#tertiarytitlelink").click(function () {
            $(this).blur();
            var $tertiarytitles_container = $("#tertiarytitles_container"), $tertiarytitles_top_container = $("#tertiarytitles_top_container");
            $tertiarytitles_container.html('');
            $tertiarytitles_top_container.toggle();
            if ($tertiarytitles_top_container.is(':visible')) {
                $.get('ajaxleftindex.php', 'open[]=tertiary_title&select=' + sel, function (answer) {
                    $tertiarytitles_container.html(answer);
                }, 'html');
                return false;
            }
        });
        ///////////////////////////////filter tertiary titles///////////////////////////

        $("#filter_tertiarytitles").keyup(function () {
            var str = $(this).val(), $element = $('#tertiarytitles_container > div > div');
            if (str !== '') {
                str = str.replace(/([^a-zA-Z0-9])/g, '\\$1');
                var re = new RegExp('^' + str, 'i');
                $element.hide().filter(function () {
                    return re.test($(this).children('span').text());
                }).show();
            } else {
                $('#tertiarytitles_container > div > div').show();
            }
        }).focus(function () {
            $(this).val('');
            $('#tertiarytitles_container > div > div').show();
        });
        ///////////////////////////////tertiary title tree///////////////////////////

        $("#tertiarytitles_container").click(function (e) {
            var $t = $(e.target);
            if ($t.hasClass('sec')) {
                var $divid = $t.next('div'), sec = $t.parent().attr('id'), ref = 'display.php?browse[' + sec + ']=tertiary_title&select=' + sel;
                if ($("#tertiarytitles_top_container").is(':visible') && $divid.is(':hidden')) {
                    var urlstring = '';
                    if (sec !== '')
                        urlstring = "open[" + sec + "]=tertiary_title&select=" + sel;
                    $.get('ajaxleftindex.php', urlstring, function (answer) {
                        $(".sec").next('div').each(function () {
                            $(this).html('').hide();
                        });
                        $(".sec").removeClass('clicked');
                        $t.addClass('clicked');
                        $divid.show().html(answer);
                    }, 'html');
                }
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                e.preventDefault();
            } else
            if ($t.hasClass('sec2')) {
                var sec = $t.parent().parent().parent().attr('id'), secondary_title = $t.text(),
                        ref = 'display.php?browse[' + sec + ']=tertiary_title&browse[' + encodeURI(secondary_title) + ']=secondary_title&select=' + sel;
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                $t.siblings().removeClass('clicked');
                $t.addClass('clicked');
            }
        });
        /////////////////////////////keyword navigation///////////////////////////////////

        $("#keywordlink").click(function () {
            $(this).blur();
            var $keywords_container = $("#keywords_container"), $keywords_top_container = $("#keywords_top_container");
            $keywords_container.empty();
            $keywords_container.data("from", 0);
            $("#next_keywords").hide();
            $("#prev_keywords").hide();
            $("#prevprev_keywords").hide();
            $keywords_top_container.toggle();
            if ($keywords_top_container.is(':visible')) {
                $.get('ajaxleftindex.php', 'open[]=keywords&select=' + sel + '&from=0', function (answer) {
                    $keywords_container.html(answer);
                    if ($keywords_container.find('.key').length >= 1000) {
                        $("#next_keywords").show();
                        $("#prev_keywords").show();
                        $("#prevprev_keywords").show();
                    }
                }, 'html');
                return false;
            }
        });
        $("#next_keywords").click(function () {
            var $keywords_container = $("#keywords_container");
            if ($keywords_container.data("from") === undefined)
                $keywords_container.data("from", 0);
            var from = $keywords_container.data("from") + 1000;
            $.get('ajaxleftindex.php', 'open[]=keywords&select=' + sel + '&from=' + from, function (answer) {
                $keywords_container.html(answer);
                if ($keywords_container.find('.key').length < 1000)
                    $("#next_keywords").hide();
            }, 'html');
            $keywords_container.data("from", from);
        });
        $("#prev_keywords").click(function () {
            var $keywords_container = $("#keywords_container");
            if ($keywords_container.data("from") === undefined)
                $keywords_container.data("from", 0);
            var from = $keywords_container.data("from") - 1000;
            if (from < 0)
                return false;
            $.get('ajaxleftindex.php', 'open[]=keywords&select=' + sel + '&from=' + from, function (answer) {
                $keywords_container.html(answer);
                $("#next_keywords").show();
            }, 'html');
            $keywords_container.data("from", from);
        });
        $("#prevprev_keywords").click(function () {
            var $keywords_container = $("#keywords_container");
            if ($keywords_container.data("from") === undefined || $keywords_container.data("from") === 0)
                return false;
            var sel = $('body').data('selection');
            $.get('ajaxleftindex.php', 'open[]=keywords&select=' + sel + '&from=0', function (answer) {
                $keywords_container.html(answer);
                $("#next_keywords").show();
            }, 'html');
            $keywords_container.data("from", 0);
        });
        /////////////////////////////filter keywords/////////////////////////////

        $("#filter_keywords").keyup(function () {
            var filterstring = $(this).val();
            if (filterstring.length > 1) {
                filterstring = encodeURI(filterstring);
                $.get('ajaxfilter.php', 'open[]=keywords&select=' + sel + '&filter=' + filterstring, function (answer) {
                    $("#filtered_keywords").show().html(answer);
                    $("#keywords_container").hide();
                    $("#next_keywords").hide();
                    $("#prev_keywords").hide();
                    $("#prevprev_keywords").hide();
                }, 'html');
            } else {
                var from = $("#keywords_container").data('from');
                $("#filtered_keywords").hide().empty();
                $("#keywords_container").show();
                if ($("#keywords_container").find('.key').length === 1000 || from > 0) {
                    $("#next_keywords").show();
                    $("#prev_keywords").show();
                    $("#prevprev_keywords").show();
                }
            }
        }).focus(function () {
            $(this).val("");
            $("#filter_keywords").trigger('keyup');
        }).blur(function () {
            if ($(this).val() === '') {
                $("#keywords_container").show();
                $("#filtered_keywords").hide().empty();
            }
        });
        $("#keywords_container, #filtered_keywords").click(function (e) {
            var $t = $(e.target);
            if ($t.hasClass('key')) {
                var key = $t.attr('id'), ref = 'display.php?browse[' + key + ']=keywords&select=' + sel;
                $('#right-panel').load(ref, function () {
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel, ref);
                });
                $t.siblings().removeClass('clicked');
                $t.addClass('clicked');
            }
        });
        /////////////////////////////saved search navigation///////////////////////////////////

        $("#savedsearchlink").click(function () {
            $(this).blur();
            var $div = $('#savedsearch_container');
            $div.toggle();
            if ($div.is(':visible')) {
                $div.load('ajaxleftindex.php?open[]=savedsearch', function () {
                    $div.find('button').button();
                });
            } else {
                $div.empty();
            }
        });
        $("#savedsearch_container").click(function (e) {
            var $t = $(e.target);
            if ($t.hasClass('savedsearch')) {
                var searchanme = $t.attr('id');
                $.get('search.php?loadsearch=1&searchname=' + searchanme, function (answer) {
                    $('#right-panel').load('search.php?select=' + sel + '&project=&' + answer, function () {
                        $("#right-panel").scrollTop(0);
                        displaywindow.init(sel, 'search.php?select=' + sel + '&project=&' + answer);
                    });
                });
                $('.savedsearch').removeClass('clicked');
                $t.addClass('clicked');
            }
        });
        $("#savedsearch_container").delegate(".rename-search", "click", function () {
            var $t = $(this), searchname = $t.prev().attr('id'), searchstr = decodeURIComponent(searchname);
            if ($t.prev().is(':visible')) {
                $t.prev().hide();
                $t.parent().prepend('<input type="text" value="' + escapeHtml(searchstr) + '" style="width:180px;margin:2px 0">');
            } else {
                var searchname2 = $t.prev().prev().val();
                if (searchname === encodeURIComponent(searchname2)) {
                    $t.prev().prev().remove();
                    $t.prev().show();
                    return false;
                }
                $.get('search.php?renamesearch=1&searchname=' + searchname + '&searchname2=' + encodeURIComponent(searchname2), function () {
                    $t.prev().prev().remove();
                    $t.prev().attr('id', encodeURIComponent(searchname2)).html(escapeHtml(searchname2)).show();
                });
            }
        });
        $("#savedsearch_container").delegate(".delete-search", "click", function () {
            var $t = $(this);
            $('body').append('<div id="dialog-delete-search" title="Delete search"></div>');
            $("#dialog-delete-search").html('<p><span class="ui-state-error-text fa fa-exclamation-triangle" style="float:left;padding:2px 7px 2em 0px"></span> This search will be permanently deleted. Are you sure?</p>')
                    .data('t', $t);
            $("#dialog-delete-search").dialog({
                autoOpen: true,
                buttons: {
                    'Delete': function () {
                        var $t = $(this).data('t'), searchanme = $t.prev().prev().attr('id');
                        $.get('search.php?deletesearch=1&searchname=' + searchanme, function (answer) {
                            if (answer === 'OK')
                                $t.parent().remove();
                            if ($('#savedsearch_container button').length < 1)
                                $("#savedsearch_container").text('No saved searches.');
                        });
                        $(this).dialog('close').remove();
                    },
                    'Cancel': function () {
                        $(this).dialog('close').remove();
                    }
                }
            });
        });
        /////////////////////////////miscellaneous navigation///////////////////////////////////

        $("#misclink").click(function () {
            $(this).blur();
            var $div = $('#misc_container');
            $div.toggle();
            $('.misc').removeClass('clicked');
        });
        $("#misc_container").click(function (e) {
            var $t = $(e.target);
            if ($t.attr('id') === 'noshelf') {
                ref = 'display.php?browse[Not+in+Shelf]=miscellaneous&select=' + sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if ($t.attr('id') === 'nopdf') {
                ref = 'display.php?browse[No+PDF]=miscellaneous&select=' + sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if ($t.attr('id') === 'noindex') {
                ref = 'display.php?browse[Not+Indexed]=miscellaneous&select=' + sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if ($t.attr('id') === 'myitems') {
                ref = 'display.php?browse[My+Items]=miscellaneous&select=' + sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if ($t.attr('id') === 'othersitems') {
                ref = "display.php?browse[Others'+Items]=miscellaneous&select=" + sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if ($t.attr('id') === 'withnotes') {
                ref = "display.php?browse[Items+with+Notes]=miscellaneous&select=" + sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if ($t.attr('id') === 'discussed') {
                ref = "display.php?browse[Discussed+Items]=miscellaneous&select=" + sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            }
            $('#right-panel').load(ref, function () {
                $("#right-panel").scrollTop(0);
                displaywindow.init(sel, ref);
            });
        });
        //HISTORY BUTTON
        $("#historylink").click(function () {
            $(this).blur();
            var ref = "display.php?browse[Viewed+in+the+last+8+hours]=history&select=library";
            $('#right-panel').load(ref, function () {
                $("#right-panel").scrollTop(0);
                displaywindow.init('library', ref);
            });
        });
    }
};
var downloadcommon = {
    init: function (dbase) {
        common.init();
        var showid, scrpt = 'download_' + dbase;
        function briefShow($el) {
            clearTimeout(showid);
            $el.css('white-space', 'inherit');
        }
        function briefHide($el) {
            clearTimeout(showid);
            $el.css('white-space', 'nowrap');
        }
        $('#addarticle-right .brief').mouseover(function () {
            var $t = $(this);
            showid = setTimeout(function () {
                briefShow($t);
            }, 400);
        }).mouseout(function () {
            briefHide($(this));
        });
        $('#download-reset').button().click(function (e) {
            e.preventDefault();
            $('#download-form').resetForm();
        });
        $("#addarticle-right .pgdown").click(function () {
            var pgupoffset = $("#addarticle-right .pgup").offset();
            $("#addarticle-right").animate({
                scrollTop: pgupoffset.top
            }, 100);
        });
        $("#addarticle-right .pgup").click(function () {
            $("#addarticle-right").animate({
                scrollTop: 0
            }, 100);
        });
        $('#download-clear').button().click(function (e) {
            e.preventDefault();
            $('#addarticle-right').load('download_' + dbase + '.php?newsearch', function () {
                window[scrpt].init();
            });
        });
        if (dbase === 'pubmed') {
            $('#download-form input[name="searchname"]').keydown(function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#download-save').click();
                }
            });
        } else {
            $('#download-form input[name="' + dbase + '_searchname"]').keydown(function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#download-save').click();
                }
            });
        }
        $('#addarticle-right .navigation').click(function (e) {
            e.preventDefault();
            timeId = setTimeout(dooverlay, 200);
            var ref = $(this).attr('href');
            $.getScript('wpad.php', function () {
                proxystr = FindProxyForURL('', 'www.pubmed.org');
                $.get(ref + '&proxystr=' + proxystr, function (answer) {
                    clearoverlay();
                    if (answer.substring(0, 5) === 'Error') {
                        $.jGrowl(answer, {
                            theme: 'jgrowl-error',
                            life: 6000
                        });
                        return false;
                    }
                    $('#addarticle-right').scrollTop(0).html(answer);
                    window[scrpt].init();
                }
                );
            });
        });
        $("#addarticle-right .author_expander").unbind().click(function () {
            var $container = $(this).parent();
            if ($(this).hasClass('fa-plus-circle')) {
                $container.css('white-space', 'inherit');
                $(this).removeClass('fa-plus-circle').addClass('fa-minus-circle');
            } else {
                $container.css('white-space', 'nowrap');
                $(this).removeClass('fa-minus-circle').addClass('fa-plus-circle');
            }
        });
        $('#download-search').click(function (e) {
            e.preventDefault();
            timeId = setTimeout(dooverlay, 1);
            var proxystr;
            $.getScript('wpad.php', function () {
                proxystr = FindProxyForURL('', 'www.crossref.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function (answer) {
                        clearoverlay();
                        if (answer.substring(0, 5) === 'Error') {
                            $.jGrowl(answer, {
                                theme: 'jgrowl-error',
                                life: 6000
                            });
                            return false;
                        }
                        $('#addarticle-right').empty().html(answer);
                        window[scrpt].init();
                    }
                });
                if (dbase === 'pubmed' || dbase === 'pmc' || dbase === 'nasa' || dbase === 'arxiv') {
                    var srch;
                    if (dbase === 'pubmed') {
                        srch = $('#download-form').find('input[name="searchname"]').val();
                    } else {
                        srch = $('#download-form').find('input[name="' + dbase + '_searchname"]').val();
                    }
                    srch = encodeURIComponent(srch);
                    srch = srch.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
                    $('#saved-search-' + dbase + '-' + srch).next().next().text('0 hours ago');
                }
            });
        }).button();
        //HOTKEYS
        $(document).unbind('keydown').bind('keydown', 'd', function () {
            if ($('#addarticle-right .nextpage').is(':visible'))
                $('#addarticle-right .nextpage').click();
        }).bind('keydown', 'a', function () {
            if ($('#addarticle-right .prevpage').is(':visible'))
                $('#addarticle-right .prevpage').click();
        });
    }
};
var download_pubmed = {
    init: function () {
        downloadcommon.init('pubmed');
        $('#download-save').click(function (e) {
            e.preventDefault();
            var srch = $('#download-form').find('input[name="searchname"]').val();
            if (srch === '')
                return false;
            var srchid = encodeURIComponent(srch);
            srchid = srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function () {
                    if ($('#saved-search-pubmed-' + srchid).length === 0) {
                        $('#pubmed-container').append('<div class="pubmed"><div class="ui-state-default del-saved-search pubmed" style="float:right;margin:1px 0;padding:0 4px"><i class="fa fa-trash-o"></i></div>'
                                + '<span class="saved-search pubmed" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#pubmed-container > div.pubmed:last > span.saved-search').attr('id', 'saved-search-pubmed-' + encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-pubmed-' + srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button();
        $('input.matcher').bind('keyup blur', function () {
            $('textarea.tagged_query').val('');
        });
        $('textarea.tagged_query').bind('keyup blur', function () {
            $('input.matcher').each(function () {
                $(this).val('');
            });
        });
        $("#addarticle-right div.titles").click(function () {
            var $this = $(this), $abstract = $this.parent().siblings("div.abstract_container"), $c = $(this).parent().siblings('div.save_container'),
                    $firstcontainer = $this.parent().siblings('.firstcontainer'), uid = $this.closest('div.items').attr('id').split('-').pop();
            if ($firstcontainer.is(':visible')) {
                if ($abstract.html() === '') {
                    if ($('body').data('click') === true)
                        return false;
                    $('body').data('click', true);
                    timeId = setTimeout(dooverlay, 1000);
                    var proxystr;
                    $.getScript('wpad.php', function () {
                        proxystr = FindProxyForURL('', 'www.crossref.org');
                        $.get('fetch.php?id=' + uid + '&proxystr=' + proxystr, function (answer) {
                            $('body').data('click', false);
                            clearoverlay();
                            if (answer.substring(0, 5) === 'Error') {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error',
                                    life: 6000
                                });
                                return false;
                            }
                            $abstract.html(answer).show();
                            $firstcontainer.hide();
                            fetch.init();
                        });
                    });
                } else {
                    $abstract.show();
                    if ($c.html() !== '')
                        $c.show();
                    $firstcontainer.hide();
                }
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
        });
        $("#addarticle-right div.flag").click(function () {
            var $this = $(this), uid = $this.closest('div.items').attr('id').substring(4), count = parseInt($('#pubmed-flagged-count').text());
            $.get('flagged.php?database=pubmed&uid=' + uid, function (answer) {
                if (answer === 'added') {
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#pubmed-flagged-count').text(count + 1);
                } else
                if (answer === 'removed') {
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#pubmed-flagged-count').text(count - 1);
                }
            });
        });
        $('#download-form textarea[name="tagged_query"]').keydown(function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#download-search').click();
            }
        });
        $('#download-form input[name="searchname"]').keydown(function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#download-save').click();
            }
        });
    }
};
var download_pmc = {
    init: function () {
        downloadcommon.init('pmc');
        $('#download-save').click(function (e) {
            e.preventDefault();
            var srch = $('#download-form').find('input[name="pmc_searchname"]').val();
            if (srch === '')
                return false;
            var srchid = encodeURIComponent(srch);
            srchid = srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function () {
                    if ($('#saved-search-pmc-' + srchid).length === 0) {
                        $('#pmc-container').append('<div class="pmc"><div class="ui-state-default del-saved-search pmc" style="float:right;margin:1px 0;padding:0 4px"><i class="fa fa-trash-o"></i></div>'
                                + '<span class="saved-search pmc" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#pmc-container > div.pmc:last > span.saved-search').attr('id', 'saved-search-pmc-' + encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-pmc-' + srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button();
        $('input.matcher').bind('keyup blur', function () {
            $('textarea.pmc_tagged_query').val('');
        });
        $('textarea.pmc_tagged_query').bind('keyup blur', function () {
            $('input.matcher').each(function () {
                $(this).val('');
            });
        });
        $("#addarticle-right div.titles").click(function () {
            var $this = $(this), $abstract = $this.parent().siblings("div.abstract_container"), $c = $(this).parent().siblings('div.save_container'),
                    $firstcontainer = $this.parent().siblings('.firstcontainer'), uid = $this.closest('div.items').attr('id').split('-').pop(),
                    pmcid = $this.closest('div.items').data('pmcid');
            if ($firstcontainer.is(':visible')) {
                if ($abstract.html() === '') {
                    if ($('body').data('click') === true)
                        return false;
                    $('body').data('click', true);
                    timeId = setTimeout(dooverlay, 1000);
                    var proxystr;
                    $.getScript('wpad.php', function () {
                        proxystr = FindProxyForURL('', 'www.crossref.org');
                        $.get('fetch_pmc.php?id=' + uid + '&pmcid=' + pmcid + '&proxystr=' + proxystr, function (answer) {
                            $('body').data('click', false);
                            clearoverlay();
                            if (answer.substring(0, 5) === 'Error') {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error',
                                    life: 6000
                                });
                                return false;
                            }
                            $abstract.html(answer).show();
                            $firstcontainer.hide();
                            fetch.init();
                        });
                    });
                } else {
                    $abstract.show();
                    if ($c.html() !== '')
                        $c.show();
                    $firstcontainer.hide();
                }
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
        $("#addarticle-right div.flag").click(function () {
            var $this = $(this), uid = $this.closest('div.items').data('pmcid'), count = parseInt($('#pmc-flagged-count').text());
            $.get('flagged.php?database=pmc&uid=' + uid, function (answer) {
                if (answer === 'added') {
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#pmc-flagged-count').text(count + 1);
                } else
                if (answer === 'removed') {
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#pmc-flagged-count').text(count - 1);
                }
            });
        });
        $('#download-form textarea[name="pmc_tagged_query"]').keydown(function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#download-search').click();
            }
        });
    }
};
var download_nasa = {
    init: function () {
        downloadcommon.init('nasa');
        $('#download-save').click(function (e) {
            e.preventDefault();
            var srch = $('#download-form').find('input[name="nasa_searchname"]').val();
            if (srch === '')
                return false;
            var srchid = encodeURIComponent(srch);
            srchid = srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function () {
                    if ($('#saved-search-nasaads-' + srchid).length === 0) {
                        $('#nasaads-container').append('<div class="nasaads"><div class="ui-state-default del-saved-search nasaads"><i class="fa fa-trash-o"></i></div>'
                                + '<span class="saved-search nasaads" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#nasaads-container > div.nasaads:last > span.saved-search').attr('id', 'saved-search-nasaads-' + encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-nasaads-' + srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button();
        $("#addarticle-right div.titles").click(function () {
            var $this = $(this), $abstract = $this.parent().siblings("div.abstract_container"), $c = $(this).parent().siblings('div.save_container'),
                    $firstcontainer = $this.parent().siblings('.firstcontainer');
            if ($firstcontainer.is(':visible')) {
                $abstract.show();
                if ($c.html() !== '')
                    $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
        $("#addarticle-right div.flag").click(function () {
            var $this = $(this), uid = $this.closest('div.items').data('uid'), count = parseInt($('#nasaads-flagged-count').text());
            $.get('flagged.php?database=nasaads&uid=' + uid, function (answer) {
                if (answer === 'added') {
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#nasaads-flagged-count').text(count + 1);
                } else
                if (answer === 'removed') {
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#nasaads-flagged-count').text(count - 1);
                }
            });
        });
        $('#download-form textarea').tipsy({
            fallback: 'One search term per line.'
        });
    }
};
var download_arxiv = {
    init: function () {
        downloadcommon.init('arxiv');
        $('#download-save').click(function (e) {
            e.preventDefault();
            var srch = $('#download-form').find('input[name="arxiv_searchname"]').val();
            if (srch === '')
                return false;
            var srchid = encodeURIComponent(srch);
            srchid = srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function () {
                    if ($('#saved-search-arxiv-' + srchid).length === 0) {
                        $('#arxiv-container').append('<div class="arxiv"><div class="ui-state-default del-saved-search arxiv" style="float:right;margin:1px 0;padding:0 4px"><i class="fa fa-trash-o"></i></div>'
                                + '<span class="saved-search arxiv" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#arxiv-container > div.arxiv:last > span.saved-search').attr('id', 'saved-search-arxiv-' + encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-arxiv-' + srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button();
        $("#addarticle-right div.titles").click(function () {
            var $this = $(this), $abstract = $this.parent().siblings("div.abstract_container"), $c = $(this).parent().siblings('div.save_container'),
                    $firstcontainer = $this.parent().siblings('.firstcontainer');
            if ($firstcontainer.is(':visible')) {
                $abstract.show();
                if ($c.html() !== '')
                    $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
        $("#addarticle-right div.flag").click(function () {
            var $this = $(this), uid = $this.closest('div.items').data('uid'), count = parseInt($('#arxiv-flagged-count').text());
            $.get('flagged.php?database=arxiv&uid=' + uid, function (answer) {
                if (answer === 'added') {
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#arxiv-flagged-count').text(count + 1);
                } else
                if (answer === 'removed') {
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#arxiv-flagged-count').text(count - 1);
                }
            });
        });
    }
};
var download_highwire = {
    init: function () {
        downloadcommon.init('highwire');
        $('#download-save').click(function (e) {
            e.preventDefault();
            var srch = $('#download-form').find('input[name="highwire_searchname"]').val();
            if (srch === '')
                return false;
            var srchid = encodeURIComponent(srch);
            srchid = srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function () {
                    if ($('#saved-search-highwire-' + srchid).length === 0) {
                        $('#highwire-container').append('<div class="highwire"><div class="ui-state-default del-saved-search highwire" style="float:right;margin:1px 0;padding:0 4px"><i class="fa fa-trash-o"></i></div>'
                                + '<span class="saved-search highwire" id=""></span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#highwire-container > div.highwire:last > span.saved-search').attr('id', 'saved-search-highwire-' + encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button();
        $("#addarticle-right div.titles").click(function () {
            var $this = $(this), $abstract = $this.parent().siblings("div.abstract_container"), $c = $(this).parent().siblings('div.save_container'),
                    $firstcontainer = $this.parent().siblings('.firstcontainer');
            if ($firstcontainer.is(':visible')) {
                $abstract.show();
                if ($c.html() !== '')
                    $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
    }
};
var download_ieee = {
    init: function () {
        downloadcommon.init('ieee');
        $('#download-save').click(function (e) {
            e.preventDefault();
            var srch = $('#download-form').find('input[name="ieee_searchname"]').val();
            if (srch === '')
                return false;
            var srchid = encodeURIComponent(srch);
            srchid = srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function () {
                    if ($('#saved-search-ieee-' + srchid).length === 0) {
                        $('#ieee-container').append('<div class="ieee"><div class="ui-state-default del-saved-search ieee"><i class="fa fa-trash-o"></i></div>'
                                + '<span class="saved-search ieee" id=""></span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#ieee-container > div.ieee:last > span.saved-search').attr('id', 'saved-search-ieee-' + encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button();
        $("#addarticle-right div.titles").click(function () {
            var $this = $(this), $abstract = $this.parent().siblings("div.abstract_container"),
                    $c = $(this).parent().siblings('div.save_container'),
                    $firstcontainer = $this.parent().siblings('.firstcontainer'),
                    uid = $this.closest('.items').data('uid');
            if ($firstcontainer.is(':visible')) {
                fetch.init();
                $abstract.show();
                if ($c.html() !== '')
                    $c.show();
                $firstcontainer.hide();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
        });
        $('#download-form input[name="ieee_type"]').change(function () {
            if ($(this).val() === 'metadata') {
                $('.ieee-searchin').each(function () {
                    $(this).find('option:first').text('Metadata');
                });
            } else {
                $('.ieee-searchin').each(function () {
                    $(this).find('option:first').text('Full Text');
                });
            }
        });
    }
};
var download_springer = {
    init: function () {
        downloadcommon.init('springer');
        $('#download-save').click(function (e) {
            e.preventDefault();
            var srch = $('#download-form').find('input[name="springer_searchname"]').val();
            if (srch === '')
                return false;
            var srchid = encodeURIComponent(srch);
            srchid = srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g, '\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function () {
                    if ($('#saved-search-springer-' + srchid).length === 0) {
                        $('#springer-container').append('<div class="springer"><div class="ui-state-default del-saved-search springer" style="float:right;margin:1px 0;padding:0 4px"><i class="fa fa-trash-o"></i></div>'
                                + '<span class="saved-search springer" id=""></span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#springer-container > div.springer:last > span.saved-search').attr('id', 'saved-search-springer-' + encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button();
        $("#addarticle-right div.titles").click(function () {
            var $this = $(this), $abstract = $this.parent().siblings("div.abstract_container"),
                    $c = $(this).parent().siblings('div.save_container'),
                    $firstcontainer = $this.parent().siblings('.firstcontainer'),
                    doi = $this.closest('.items').data('doi'), pdf = $this.closest('.items').data('pdf');
            if ($firstcontainer.is(':visible')) {
                if ($abstract.html() === '') {
                    if ($('body').data('click') === true)
                        return false;
                    $('body').data('click', true);
                    timeId = setTimeout(dooverlay, 1000);
                    var proxystr;
                    $.getScript('wpad.php', function () {
                        proxystr = FindProxyForURL('', 'www.crossref.org');
                        $.get('fetch_crossref.php?doi=' + doi + '&pdf=' + pdf + '&proxystr=' + proxystr, function (answer) {
                            $('body').data('click', false);
                            clearoverlay();
                            if (answer.substring(0, 5) === 'Error') {
                                $.jGrowl(answer, {
                                    theme: 'jgrowl-error',
                                    life: 6000
                                });
                                return false;
                            }
                            $abstract.html(answer).show();
                            $firstcontainer.hide();
                            fetch.init();
                        });
                    });
                } else {
                    $abstract.show();
                    if ($c.html() !== '')
                        $c.show();
                    $firstcontainer.hide();
                }
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
        });
    }
};
var fetch = {
    init: function () {
        $("#addarticle-right .author_expander").unbind('click').click(function () {
            var $container = $(this).parent();
            if ($(this).hasClass('fa-plus-circle')) {
                $container.css('white-space', 'inherit');
                $(this).removeClass('fa-plus-circle').addClass('fa-minus-circle');
            } else {
                $container.css('white-space', 'nowrap');
                $(this).removeClass('fa-minus-circle').addClass('fa-plus-circle');
            }
        });
        $('#addarticle-right .save-item').unbind('click').click(function (e) {
            e.preventDefault();
            var $t = $(this), $f = $t.closest('.fetch-form'), $c = $t.closest('.abstract_container').next('.save_container');
            $t.button('option', 'disabled', true);
            $f.ajaxSubmit({
                success: function (answer) {
                    $c.show().html(answer);
                    upload.init();
                }
            });
        }).button();
        $('#addarticle-right .quick-save-item').unbind('click').click(function (e) {
            e.preventDefault();
            var $t = $(this), $f = $t.closest('.fetch-form');
            $t.button('option', 'disabled', true);
            $.getScript('wpad.php', function () {
                var proxystr = FindProxyForURL('', 'www.crossref.org');
                $f.ajaxSubmit({
                    data: {
                        form_sent: 1,
                        shelf: 1,
                        'proxystr': proxystr
                    },
                    dataType: 'json',
                    success: function (answer) {
                        if (answer['error'] !== undefined) {
                            $.each(answer['error'], function (key, err) {
                                $.jGrowl(err, {
                                    theme: 'jgrowl-error'
                                });
                            });
                        }
                        if (answer['message'] !== undefined) {
                            $.each(answer['message'], function (key, mess) {
                                mess = mess.replace(/&lt;/g, '<');
                                mess = mess.replace(/&gt;/g, '>');
                                $.jGrowl(mess);
                            });
                            $t.closest('div.abstract_container').siblings('div.titles').css('color', '#999');
                        }
                    }
                });
            });
        }).button();
    }
};
var rtfscan = {
    init: function () {
        common.init();
        var start;
        $('#rtfscanform').find(':submit').button();
        if (localStorage.getItem('laststyle')) {
            $('#last-style-td').find('span').text(localStorage.getItem('laststyle'));
            $('#last-style-td').find('input').val(localStorage.getItem('laststyle'));
            $('#last-style-td').parent().show();
        }
        $('#citation-style').autocomplete({
            source: "ajaxstyles.php",
            minLength: 1
        }).keydown(function () {
            $('#last-style-td').find('input').prop('checked', false);
            $('#last-style-td').find('i').removeClass('fa-check-square').addClass('fa-square-o');
        });
        $('#rtfscanform').ajaxForm({
            beforeSubmit: function (arr, $f) {
                var req = true;
                $.each(arr, function (i, val) {
                    if (val.name === 'manuscript' && val.value === '') {
                        $.jGrowl('Manuscript file missing.');
                        req = false;
                        return false;
                    } else
                    if (val.name === 'citation-style' && val.value === '' && $f.find('input[name="last-style"]').prop('checked') !== true) {
                        $.jGrowl('Citation style missing.');
                        req = false;
                        return false;
                    }
                });
                if (!req) {
                    return false;
                }
                start = new Date().getTime();
                $('#rtfscan-results').html('<b>Log</b><br>Reading the file.<br>');
                if ($('#citation-style').val() !== '')
                    localStorage.setItem('laststyle', $('#citation-style').val());
                if ($('#last-style-td').find('input').is(':checked'))
                    localStorage.setItem('laststyle', $('#last-style-td').find('input').val());
                $('#last-style-td').find('span').text(localStorage.getItem('laststyle'));
                $('#last-style-td').find('input').val(localStorage.getItem('laststyle'));
                $('#last-style-td').parent().show();
            },
            success: function (answer) {
                localStorage.setItem('rtfname', $('#rtfscanform').find('input[name="manuscript"]').val());
                var answer = JSON.parse(answer);
                $.ajax({
                    url: 'js/csl/citeproc.min.js',
                    dataType: "script",
                    cache: true,
                    success: function () {
                        // list of references in json format
                        var references = answer.references;
                        // list of citations in json format
                        var citations = answer.citations;
                        // user-selected style
                        var style = answer.style;
                        // errors
                        var errors = answer.errors;
                        if (errors) {
                            $.each(errors, function (key, val) {
                                $('#rtfscan-results').html($('#rtfscan-results').html() + '<b>' + val + '</b><br>');
                            });
                        }
                        // extract all reference keys
                        var itemIDs = [];
                        for (var key in references) {
                            itemIDs.push(key);
                        }
                        // initialize citeproc object
                        citeprocSys = {
                            retrieveLocale: function (lang) {
                                var xhr = new XMLHttpRequest();
                                xhr.open('GET', 'js/csl/locales/locales-' + lang + '.xml', false);
                                xhr.send(null);
                                return xhr.responseText;
                            },
                            retrieveItem: function (id) {
                                return references[id];
                            }
                        };
                        var citeproc = new CSL.Engine(citeprocSys, style);
                        citeproc.setOutputFormat("rtf");
                        citeproc.updateItems(itemIDs);
                        // render bibliography
                        var bibResult = citeproc.makeBibliography();
                        var refs = '';
                        $.each(bibResult[1], function (key, val) {
                            refs += '\n\\par ' + val;
                        });
                        // render citations
                        var cites = [];
                        $.each(citations, function (key, val) {
                            var cite = citeproc.appendCitationCluster(val, true);
                            cites[key] = cite[0][1];
                        });
                        $('#rtfscan-results').html($('#rtfscan-results').html()
                                + 'Found ' + itemIDs.length + ' references cited '
                                + citations.length + 'x.<br>Formatting the file.<br>');
                        var finald = {'bibliography': refs, 'cites': cites, 'rtfname': localStorage.getItem('rtfname')};
                        $.ajax({
                            url: 'rtfscan.php',
                            data: finald,
                            type: 'post',
                            dataType: 'json',
                            success: function (answer) {
                                // errors
                                var errors = answer.errors;
                                if (errors) {
                                    $.each(errors, function (key, val) {
                                        $('#rtfscan-results').html($('#rtfscan-results').html() + '<b>' + val + '</b><br>');
                                    });
                                }
                                var end = new Date().getTime();
                                var time = Math.round((end - start) / 100) / 10;
                                $('#rtfscan-results').html($('#rtfscan-results').html()
                                        + 'Finished in ' + time
                                        + ' sec.<br><br><a id="rtf-download" href="attachment.php?rtf=formatted-'
                                        + localStorage.getItem('rtfname').split('\\').pop()
                                        + '">Download formatted manuscript</a>');
                                $('#rtf-download').button();
                            }
                        });
                    }
                });
            }
        });
        $('#rtfscanform').submit(function (e) {
            e.preventDefault();
        });
    }
};
var batchreindex = {
    init: function () {
        $('#right-panel').find('button').click(function () {
            $(this).button('disable');
            $(this).parent().append(' Working <img src="img/ajaxloader.gif">');
            $('#right-panel').load('batchreindex.php');
        }).button();
    }
};