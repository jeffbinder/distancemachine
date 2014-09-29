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

    if (window.highlight_option == "ngrams") {
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
		}
                child.className = "";
            }
	}

    } else if (window.highlight_option.indexOf("dict-") == 0) {
        
        var dict = window.highlight_option.substring(5);
	for (var i = start; i <= end; i++) {
            var line = lines[i];
            var children = line.getElementsByTagName("span");
            var nchildren = children.length;
            for (var j = 0; j < nchildren; j++) {
		var child = children[j];
		var dicts = child.getAttribute("data-dicts");
		
		if (dicts) {
                    if (dicts.indexOf("x" + dict) > -1) {
                        child.className = "omitted-word";
                        continue;
		    } else if (dicts.indexOf("o" + dict) > -1) {
                        child.className = "obsolete-word";
                        continue;
		    } else if (dicts.indexOf("v" + dict) > -1) {
                        child.className = "vulgar-word";
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

function update_highlight_option()
{
    window.highlight_option = $("#highlight-option").val();
    if (!window.highlight_option) {
	window.highlight_option = "ngrams";
    }
    if (window.highlight_option == "ngrams") {
	$("#ngrams-key").css("display", "default");
	$("#dict-key").css("display", "none");
	$("#play-button").button("enable");
	$("#slider").slider("enable");
	$(".selected-year").css("color", "black");
    } else {
	$("#ngrams-key").css("display", "none");
	$("#dict-key").css("display", "default");
	$("#play-button").button("disable");
	$("#slider").slider("disable");
	$(".selected-year").css("color", "grey");
    }
    update_word_list();
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
        y = (document.documentElement.scrollTop||document.body.scrollTop);
        
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

function get_words_with_class(css_class)
{
    var d = {};
    $("#text-area ." + css_class).each(function (i, e) {
	d[e.innerText.toLowerCase()] = 1;
    });
    var words = [];
    for (var word in d) {
	words.push(word);
    }
    return words.sort();
}

function create_word_grid(words, css_class)
{
    var html = [];
    for (var i in words) {
	html.push("<div class='grid-word'><span class='" + css_class
                  + "'> " + words[i] + " </span></div>");
    }
    return html.join("");
}

function update_word_list()
{
    var option_text;
    if (window.highlight_option == "ngrams") {
	option_text = "The following words were marked as uncommon in <b>" + window.current_year + "</b>.";
    } else {
        var dict = window.highlight_option.substring(5);
	option_text = "The following words were highlighted based on <b>" + dict_names[dict][1] + "</b>.";
    }
    $("#word-list-option-text").html(option_text);

    var html = [];

    update_highlighting(true, true);
    if (window.highlight_option == "ngrams") {

	var words = get_words_with_class("old-word");
	if (words.length == 0) {
	    html.push("No words found that were more common earlier.");
	} else {
	    html.push("Words that were more common earlier:<div>");
	    html.push(create_word_grid(words, "old-word"));
	    html.push("</div>");
	}
	html.push("<hr />");

	words = get_words_with_class("new-word");
	if (words.length == 0) {
	    html.push("<div>No words found that were more common later.</div>");
	} else {
	    html.push("<div>Words that were more common later:</div><div>");
	    html.push(create_word_grid(words, "new-word"));
	    html.push("</div>");
	}
	html.push("<hr />");

	words = get_words_with_class("lapsed-word");
	if (words.length == 0) {
	    html.push("<div>No words found that were more common both earlier and later.</div>");
	} else {
	    html.push("<div>Words that were more common both earlier and later:</div><div>");
	    html.push(create_word_grid(words, "lapsed-word"));
	    html.push("</div>");
	}

    } else {

	words = get_words_with_class("obsolete-word");
	if (words.length == 0) {
	    html.push("No words found that were marked as rare or obsolete in the dictionary.");
	} else {
	    html.push("Words that are marked as rare or obsolete in the dictionary:<div>");
	    html.push(create_word_grid(words, "obsolete-word"));
	    html.push("</div>");
	}
	html.push("<hr />");

	words = get_words_with_class("vulgar-word");
	if (words.length == 0) {
	    html.push("<div>No words found that were marked as vulgar, colloquial, or improper in the dictionary.</div>");
	} else {
	    html.push("<div>Words that were marked as vulgar, colloquial, or improper in the dictionary:</div><div>");
	    html.push(create_word_grid(words, "vulgar-word"));
	    html.push("</div>");
	}
	html.push("<hr />");

	var words = get_words_with_class("omitted-word");
	if (words.length == 0) {
	    html.push("<div>All words in the text were found in the dictionary.</div>");
	} else {
	    html.push("<div>Words that were not found in the dictionary:</div><div>");
	    html.push(create_word_grid(words, "omitted-word"));
	    html.push("</div>");
	}
        
    }

    html = html.join("");
    $("#word-list-area").html(html);

    update_word_list_height();
}

function show_word_list()
{
    update_word_list();
    $("#word-list").css("display", "inline");
    update_word_list_height();
    hide_word_info();
}

function hide_word_list()
{
    $("#word-list").css("display", "none");
}

function update_word_list_height()
{
    var h = window.innerHeight;
    $("#word-list-area").css("max-height", h - 33
			     - $("#word-list-option-text").height()
			     - 115 - 24);
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
    var highlight_option_str = "";
    if (window.highlight_option.indexOf("dict-") == 0) {
	highlight_option_str = "&d=" + window.highlight_option.substring(5);
    }
    return "/text/" + id + "?y=" + current_year + highlight_option_str;
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
    $("#corpus-name").text(corpus_names[corpus]);

    show_print_link_message();
    $("#print-title").text(title + " (" + corpus_names[corpus] + ")");

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
    
    $("#play-button").button({
        icons: {
            primary: "ui-icon-play"
        },
        text: false
    })
    .click(function (e) {
        animate();
    });
    
    for (var i in dicts) {
	$('#highlight-option')
            .append($("<option></option>")
		    .attr("value", "dict-" + dicts[i])
		    .text("Highlighting words based on " + dict_names[dicts[i]][0])); 
	dict_names[i];
    }
    if (initial_highlight_option) {
	$("#highlight-option").val(initial_highlight_option);
    }
    update_highlight_option();

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
    update_word_list_height();
    window.onresize = function () {
        update_visibility();
        update_word_info_height();
        update_word_list_height();
    };
    
    $("#text-area,#definition-area,#word-list-area").dblclick(function (e) {
        var word = get_selection();
        show_word_info(word, window.corpus);
	hide_word_list();
    });
    $("#text-area span").on("touchstart", function (e) {
        window.touching_word = true;
    });
    $("#text-area span").on("touchmove", function (e) {
        window.touching_word = false;
    });
    $("#text-area span").on("touchend", function (e) {
        if (window.touching_word) {
            var word = e.target.textContent;
            show_word_info(word, window.corpus);
	    hide_word_list();
            return false;
        }
    });

    set_year(initial_year);
    
    $("#highlight-option").change(function (e) {
        update_highlight_option();
        update_location();
	update_highlighting(true);
    });
    
    $("#highlight-type").change(function (e) {
        update_highlight_type();
    });
    
    $("#word-lookup").keyup(function (e) {
        if (e.keyCode == 13) {
            var word = $("#word-lookup").val();
            show_word_info(word, window.corpus);
	    hide_word_list();
        }
    });

    $("#main-area").click(function(e) {
        hide_word_info();
        hide_word_list();
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
    $("#word-list").click(function(e) {
        hide_help_box();
    });
    
    // Prevent scroll wheel events from scrolling the body when the cursor is in a fixed
    // div.  This is mainly so that the user can scroll the definition area without
    // scrolling the document as a whole when they get to the end.
    $("#word-info,#word-list,#header")
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
