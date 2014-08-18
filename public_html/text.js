// Update the highlighting in the specified lines.  By default, this only updates lines
// that just came into view.  If refresh = true, it updates all visible lines.  If
// whole_document = true, it updates everything.
function update_highlighting(refresh, whole_document)
{
    var year = current_year;

    // Determine which lines need to be updated.
    var start, end;
    if (whole_document) {
        start = 0;
        end = window.lines.length - 1;
    } else if (refresh) {
        start = first_line_visible;
        end = last_line_visible;
    } else {
        if (first_line_visible >= prev_first_line_visible
            && first_line_visible <= prev_last_line_visible) {
            start = prev_last_line_visible + 1;
        } else {
            start = first_line_visible;
            if (start < 0) {
                start = 0;
            }
        }
        if (last_line_visible >= prev_first_line_visible
            && last_line_visible <= prev_last_line_visible) {
            end = prev_first_line_visible - 1;
            if (end >= window.lines.length) {
                end = window.lines.length - 1;
            }
        } else {
            end = last_line_visible;
        }
    }

    // Construct a regex to match words that are out of use in the selected year.
    var dec = ~~(year / 10);
    var cent = ~~(year / 100);
    var re = "[oln]" + cent + "(xx|" + (dec - cent * 10) + "(" + (year - dec * 10) + "|x";
    if (year < dec * 10 + 5) {
        re += "|l)";
    } else {
        re += "|r)";
    }
    if (year < cent * 100 + 50) {
        re += "|lx)";
    } else {
        re += "|rx)";
    }
    re = new RegExp(re);
    
    for (var i = start; i <= end; i++) {
        var line = lines[i];
        var children = line.getElementsByTagName("span");
        var nchildren = children.length;
        for (var j = 0; j < nchildren; j++) {
            var child = children[j];
            var usage = child.getAttribute("data-usage");
    
            if (usage) {
                var matches = usage.match(re);
                if (matches) {
                    if (matches[0][0] == "o") {
                        child.className = "old-word";
                        continue;
                    }
                    if (matches[0][0] == "l") {
                        child.className = "lapsed-word";
                        continue;
                    }
                    if (matches[0][0] == "n") {
                        child.className = "new-word";
                        continue;
                    }
                }
                child.className = "";
            }
        }
    }
}

function update_highlight_type()
{
    window.highlight_type = $("#highlight-type").val();
    $.cookie("highlight-type", window.highlight_type);
    
    $("#highlight-css-link").remove();
    if (highlight_type == "bg") {
        $("head").append('<link id="highlight-css-link" rel="stylesheet" type="text/css" href="highlight-bg.css">');
    } else if (highlight_type == "box") {
        $("head").append('<link id="highlight-css-link" rel="stylesheet" type="text/css" href="highlight-box.css">');
    }
}

// Change the stored year and update highlighting accordingly.
function set_year(year)
{
    $(".selected-year").text(year);
    if ($("#slider").slider("value") != year) {
        $("#slider").slider("value", year);
    }
    window.current_year = year;
    update_highlighting(true);
}

function animate()
{
    set_year(start_year);
    if (timer) {
        clearInterval(timer);
    }
    timer = setInterval(function() {
        set_year(current_year + 1);
        if (current_year >= end_year) {
            clearInterval(timer);
            update_location();
            return;
        }
    }, 20);
}

// Check for a change in line visibility and update the highlighting
// accordingly.
function update_visibility()
{
    var h = window.innerHeight,
        y = document.body.scrollTop;
        
    var first_line_visible = Math.floor((y - first_line_top) / line_height) - 1,
        last_line_visible = first_line_visible + Math.ceil(h / line_height) + 1;
    if (first_line_visible < 0) {
        first_line_visible = 0;
    }
    if (last_line_visible >= window.lines.length) {
        last_line_visible = window.lines.length - 1;
    }
    if (first_line_visible == window.first_line_visible
        && last_line_visible == window.last_line_visible) {
        return;
    }
    window.prev_first_line_visible = window.first_line_visible;
    window.prev_last_line_visible = window.last_line_visible;
    window.first_line_visible = first_line_visible;
    window.last_line_visible = last_line_visible;
        
    update_highlighting(window.lines);
}

function update_word_info_height()
{
    var h = window.innerHeight;
    $("#definition-area").css("max-height", h - 383 - 115 - 24);
}

function get_selection()
{
    var text = "";
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
}

function get_base_url()
{
    return "/text/" + id + "?y=" + current_year;
}

function get_url()
{
    return window.location.host + get_base_url();
}

function show_save_box(url)
{
    $("#save-box").css("display", "inline");
    $("#url-area").val(url).select();
}

function update_location()
{
    if (saved) {
        window.history.replaceState("", "", get_base_url());
    }
}

function hide_save_box()
{
    $("#save-box").css("display", "none");
}

function show_save_error_box()
{
    $("#save-error-box").css("display", "inline");
}

function hide_save_error_box()
{
    $("#save-error-box").css("display", "none");
}

function save_text()
{
    if (saved) {
        var url = get_url();
        show_save_box(url);
    } else {
        var data = {"id": id};
        $.post("savetext.php", data, function (response) {
            var url = get_url();
            show_save_box(url);
            $("#save-link").text("Text saved");
            saved = true;
            update_location();
        }, "json")
	    .fail(function() {
            show_save_error_box();
	    });
    }
}

function show_help_box()
{
    $("#help-box").css("display", "inline");
}

function hide_help_box()
{
    $("#help-box").css("display", "none");
    $("#help-cookie-message").css("display", "none");
}

function show_print_link_message()
{
    $("head").append('<link id="use-print-link-css-link" rel="stylesheet" type="text/css" href="use-print-link.css">');
}

function hide_print_link_message()
{
    $("#use-print-link-css-link").remove();
}

function print_text()
{
    hide_print_link_message();

    update_highlighting(false, true);
    window.print();

    show_print_link_message();
}

$(window).load(function () {
    if (saved) {
        $("#save-link").text("Text saved");
    }
    $("#corpus-name").text(corpus_names[region]);

    show_print_link_message();
    $("#print-title").text(title + " (" + corpus_names[region] + ")");

    window.timer = null;

    window.lines = $("#text-area").children();
    var first_line = $("#text-area > .line:first-child");
    var second_line = $("#text-area > .line:nth-child(2)");
    window.first_line_top = first_line.offset().top;
    if (second_line.length) {
        window.line_height = second_line.offset().top - first_line_top;
    } else {
        // There is only one line of text.  This value doesn't matter.
        window.line_height = 10;
    }
    
    window.highlight_type = $.cookie("highlight-type");
    if (window.highlight_type) {
        $("#highlight-type").val(window.highlight_type);
    } else {
        window.highlight_type = "bg";
    }
    update_highlight_type();
    
    if ($.cookie("help-displayed") == "yes") {
        $("#help-cookie-message").css("display", "none");
    } else {
        $.cookie("help-displayed", "yes");
        show_help_box()
    }
    
    // No jQuery here for efficiency's sake.
    update_visibility();
    window.onscroll = update_visibility;
    
    update_word_info_height();
    window.onresize = function () {
        update_visibility();
        update_word_info_height();
    };
    
    $("#slider").slider({
        value: end_year,
        min: start_year,
        max: end_year,
        slide: function(event, ui) {
            set_year(parseInt(ui.value));
        },
        stop: function(event, ui) {
            update_location();
        }
    });
    set_year(initial_year);
    
    $("#play-button").button({
        icons: {
            primary: "ui-icon-play"
        },
        text: false
    })
    .click(function (e) {
        animate();
    });
    
    $("#text-area").dblclick(function(e) {
        var word = get_selection();
        show_word_info(word, window.region);
    });
    
    $("#highlight-type").change(function (e) {
        update_highlight_type();
    });
    
    $("#word-lookup").keyup(function (e) {
        if (e.keyCode == 13) {
            var word = $("#word-lookup").val();
            show_word_info(word, window.region);
        }
    });

    $("#main-area").click(function(e) {
        hide_word_info();
        hide_help_box();
        hide_save_box();
        hide_save_error_box();
    });
    $("#header").click(function(e) {
        hide_save_box();
    });
    $("#word-info").click(function(e) {
        hide_help_box();
    });
    
    // Prevent scroll wheel events from scrolling the body when the cursor is in a fixed
    // div.  This is mainly so that the user can scroll the definition area without
    // scrolling the document as a whole when they get to the end.
    $("#word-info,#header")
        .hover(function(e) {
            $("body").css("overflow", "hidden");
        }, function(e) {
            $("body").css("overflow", "auto");
        });
    
    // Periodically touch the tmp file as long as the page is open so it doesn't get
    // culled.
    var timer = setInterval(function() {
        if (!saved) {
            $.post("touchtmpfile.php", {"id": id}, function(d) {}, "json");
        }
    }, 600000);
    
    $("#header-loading").css("display", "none");
});
