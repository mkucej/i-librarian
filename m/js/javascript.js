//  overlay feedback
var timeId = '';
var dooverlay = function() {
    if ($('#overlay').length === 1)
        return false;
    $('body').append('<div id="overlay" style="height:' + $(document).height() + 'px">&nbsp;</div>');
    $('#overlay').html('<img src="../img/ajaxloader2.gif" alt="" style="display:block;margin:auto;margin-top:' + (-32 + 0.5 * $(window).height()) + 'px">');
};
var clearoverlay = function() {
    clearTimeout(timeId);
    $('#overlay').remove();
};
// INITIAL WINDOW LOAD
$(window).load(function() {
    $('#bottom-panel').load('display.php?browse[]=all&select=library', function() {
        displaywindow.init('library');
    });
    $.ajaxSetup({
        cache: false
    });
    $(document).ajaxStart(function() {
        timeId = setTimeout(dooverlay, 500);
    });
    $(document).ajaxStop(function() {
        clearoverlay();
    });
    $(document).ajaxSuccess(function(e, xhr) {
        if (xhr.responseText === 'signed_out')
            top.location.reload(true);
    });
    $("#top-page").bind('create',function(){
         if($("#bottom-panel").css('visibility')==='hidden') $("#bottom-panel").css('visibility','visible').css('display','none').fadeIn(800);
         if($("#bottom-panel .accordeon").next().children().is(':hidden')) $('#bottom-panel .accordeon').next().children().fadeIn(800);
     });
    index2.init();
});

var index2 = {
    init: function() {
        setTimeout(function() {
            $('#splash').fadeOut(800,function(){
                $(this).hide();
            });
        },1000);
        var sel = 'library', from = 0, project = '', search = '';
        $(document).data('sel', sel);
        $(document).data('from', from);
        $(document).data('project', project);
        $(document).data('search', search);
        $('#signinbutton').click(function() {
            var $form = $('#signinform'), passwd = $form.find('input[name=pass]').val(), username = '', queryString = $form.formSerialize();
            if ($form.find('input[name=user]').length > 0)
                username = $form.find('input[name=user]').val();
            if ($form.find('select[name=user]').length > 0)
                username = $form.find('select[name=user] option:selected').val();
            if (username !== '' && passwd !== '') {
                $.post('index2.php', queryString, function(answer) {
                    if (answer === 'OK') {
                        top.location.reload(true);
                    } else {
                        $('#mypanel').panel("open");
                    }
                });
            }
            return false;
        });
        $('#mypanel').find('button').click(function() {
            $('#mypanel').panel("close");
        });
        $('#signinform').submit(function() {
            $('#signinbutton').click();
            return false;
        });
        //signed in
        function reload_bottom_panel() {
            $("#bottom-panel").css('visibility','hidden');
            sel = $(document).data('sel');
            from = $(document).data('from');
            project = $(document).data('project');
            search = $(document).data('search');
            if (search !== '') {
                $('#bottom-panel').load('search.php?' + search + '&select=' + sel + '&project=' + project + '&from=' + from, function() {
                    displaywindow.init(sel);
                });
            } else {
                $('#bottom-panel').load('display.php?browse[]=all&select=' + sel + '&project=' + project + '&from=' + from, function() {
                    displaywindow.init(sel);
                });
            }
        }
        //TOP NAVBAR
        $('#link-library').click(function(event) {
            event.preventDefault();
            $(document).data('sel','library').data('from',0).data('search','');
            reload_bottom_panel();
        });
        $('#link-shelf').click(function(event) {
            event.preventDefault();
            $(document).data('sel','shelf').data('from',0).data('search','');
            reload_bottom_panel();
        });
        $('#link-desk').click(function(event) {
            event.preventDefault();
            var txt = $(this).find('.ui-btn-text').text();
            if (txt === 'Desk' || (txt !== 'Desk' && $(document).data('sel') === 'desk')) {
                $('#panel-desk').children().load('mobiledesk.php', function() {
                    $("#top-page").trigger('create');
                    $('#panel-desk').panel("open");
                    $('.open-project').click(function(event) {
                        event.preventDefault();
                        var projid = $(this).data('id').split('-').pop(), projname = $(this).data('project').split('-').pop();
                        $(document).data('sel', 'desk').data('project', projid).data('from', 0).data('search', '');
                        reload_bottom_panel();
                        $('#panel-desk').panel("close");
                        $('#link-desk').find('.ui-btn-text').text(projname);
                    });
                });
            } else {
                $(document).data('sel','desk').data('from',0).data('search','');
                reload_bottom_panel();
            }
        });
        $('#link-clipboard').click(function(event) {
            event.preventDefault();
            $(document).data('sel','clipboard').data('from',0).data('search','');
            reload_bottom_panel();
        });
        //REMOVE DESK ACTIVE BUTTON STATE IF USER CANCELS DESK SELECTION
        $("#panel-desk").on('panelbeforeclose', function() {
            if ($('#link-desk').find('.ui-btn-text').text() === 'Desk') {
                $('#link-desk').removeClass('ui-btn-active');
                $('#link-' + $(document).data('sel')).addClass('ui-btn-active');
            }
        });
        //MENU
        $('#clear-clipboard').click(function(event) {
            event.preventDefault();
            $.get('mobileclipboard.php?selection='+$(document).data('sel'), function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#add-clipboard').click(function(event) {
            event.preventDefault();
            $.get('mobileclipboard.php?action=add', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#link-signout').click(function() {
            $.get('index2.php?action=signout', function() {
                var newurl = top.location.href.split('?').shift();
                top.location.assign(newurl);
            });
        });
        $('#link-menu').click(function() {
            $('#panel-menu').panel("open");
        });
        $('#radio-display-titles').click(function() {
            $.get('ajaxdisplay.php', 'display=brief', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#radio-display-icons').click(function() {
            $.get('ajaxdisplay.php', 'display=icons', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#radio-orderby-id').click(function() {
            $(document).data('from', 0);
            $.get('ajaxdisplay.php', 'orderby=id', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#radio-orderby-year').click(function() {
            $(document).data('from', 0);
            $.get('ajaxdisplay.php', 'orderby=year', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#radio-orderby-journal').click(function() {
            $(document).data('from', 0);
            $.get('ajaxdisplay.php', 'orderby=journal', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#radio-orderby-rating').click(function() {
            $(document).data('from', 0);
            $.get('ajaxdisplay.php', 'orderby=rating', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        $('#radio-orderby-title').click(function() {
            $(document).data('from', 0);
            $.get('ajaxdisplay.php', 'orderby=title', function() {
                reload_bottom_panel();
                $('#panel-menu').panel("close");
            });
        });
        //QUICK SEARCH
        $("#quicksearch #search").click(function() {
            var searchvalue = $("#quicksearch input[name='anywhere']").val();
            if (searchvalue === '')
                return false;
            var q = $("#quicksearch").formSerialize();
            $(document).data('search', q).data('from',0);
            reload_bottom_panel();
            $('#panel-menu').panel("close");
            return false;
        });
        $("#quicksearch #clear").click(function() {
            $("#quicksearch input[name='anywhere']").val('').focus();
            $.get('search.php?newsearch=1');
            return false;
        });
        $("#quicksearch").submit(function() {
            $("#quicksearch #search").click();
            return false;
        });
        //NAVIGATION
        var limit=10;
        $('#page-next, #page-next2').click(function() {
            total = $('#total-items').text();
            if (total % limit === 0) {
                maxfrom = total - limit;
            } else {
                maxfrom = total - (total % limit);
            }
            if($(document).data('from')===maxfrom) return false;
            from = Math.min(maxfrom, from + limit);
            $(document).data('from', from);
            reload_bottom_panel();
            $(document).scrollTop(0);
        });
        $('#page-prev, #page-prev2').click(function() {
            if($(document).data('from')===0) return false;
            from = Math.max(0, from - limit);
            $(document).data('from', from);
            reload_bottom_panel();
            $(document).scrollTop(0);
        });
        $('#page-first').click(function() {
            if($(document).data('from')===0) return false;
            from = 0;
            $(document).data('from', from);
            reload_bottom_panel();
            $(document).scrollTop(0);
        });
        $('#page-last').click(function() {
            total = $('#total-items').text();
            if (total % limit === 0) {
                maxfrom = total - limit;
            } else {
                maxfrom = total - (total % limit);
            }
            if($(document).data('from')===maxfrom) return false;
            $(document).data('from', maxfrom);
            reload_bottom_panel();
            $(document).scrollTop(0);
        });
    }
};

var displaywindow = {
    init: function(sel) {
        // HIDE SPLASH SCREEN
        $('#splash2').fadeOut(800,function(){
            $(this).hide();
        });
        // ACCORDEON IMPROVEMENT - SCROLL TO THE HEADER
        var scrollaccordeon = function($t) {
            var htop = parseInt($t.offset().top) + parseInt($(document).scrollTop()) - 37;
            $(document).scrollTop(htop);
        };
        // SCROLL THE DIV TO THE TOP
        $(document).scrollTop(0);
        // ICON VIEW CENTER
        $('#icon-container td').css('padding-left', -2+($(window).width() % 310)/2 + 'px');
        $(window).resize(function(){
            $('#icon-container td').css('padding-left', -2+($(window).width() % 310)/2 + 'px');
        });
        // TITLE LIST VIEW
        $('#bottom-panel .accordeon').click(function() {
            var $t = $(this), fileid = $t.data('fileid');
            $t.next().children().hide();
            // LOAD ITEM, IF COLLAPSED
            if ($t.next().is(':hidden')) {
                $t.next().children().load('mobileitem.php?id=' + fileid, function() {
                    // BIND CLIPBOARD BUTTONS
                    $('#display-content .update_clipboard').click(function() {
                        var $ti = $(this), file = $ti.attr('id').split('-').pop();
                        $.get("ajaxclipboard.php", {
                            'file': file,
                            'selection': sel
                        },
                        function(answer) {
                            if (answer.substr(0, 5) === "Error") {
                                $ti.attr("checked",false).checkboxradio( "refresh" );
                            }
                            if (sel === "clipboard") {
                                from = $(document).data('from');
                                $('#bottom-panel').load('display.php?browse[]=all&select=clipboard&from=' + from, function() {
                                    displaywindow.init('clipboard');
                                });
                            }
                        });
                    });
                    // BIND ACCORDEON
                    $('#bottom-panel .ui-collapsible-content .accordeon').click(function() {
                        var $t2 = $(this);
                        // SCROLL ACCORDEON
                        setTimeout(function() {
                            scrollaccordeon($t2);
                        }, 100);
                    });
                    // RE-DRAW THE PAGE
                    $("#top-page").trigger('create');
                    // SCROLL ACCORDEON
                    scrollaccordeon($t);
                });
            }
        });
        // BIND CLIPBOARD BUTTONS
        $('#icon-container').find('.update_clipboard').click(function() {
            var $ti = $(this), file = $ti.attr('id').split('-').pop();
            $.get("ajaxclipboard.php", {
                'file': file,
                'selection': sel
            },
            function(answer) {
                if (answer.substr(0, 5) === "Error") {
                    $ti.attr("checked",false).checkboxradio( "refresh" );
                }
                if (sel === "clipboard") {
                    from = $(document).data('from');
                    $('#bottom-panel').load('display.php?browse[]=all&select=clipboard&from=' + from, function() {
                        displaywindow.init('clipboard');
                    });
                }
            });
        });
        // RE-DRAW THE PAGE
        $("#top-page").trigger('create');
        
    }
};