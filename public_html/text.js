var slider_width = 300;

function regex_greater_than_helper(n)
{
    if (n.length == 1) {
        if (n < 8) {
            return "[" + (parseInt(n) + 1) + "-9]";
        } else if (n == 8) {
            return "9"
        }
        return "";
    }
    var re = [];
    var lsd = parseInt(n[0])
    if (lsd < 9) {
        var higher_lsd;
        if (lsd < 8) {
            higher_lsd = "[" + (lsd + 1) + "-9]";
        } else if (lsd == 8) {
            higher_lsd = "9"
        }
        re.push(higher_lsd + "[0-9]{" + (n.length - 1) + "}");
    }
    var higher_rest = regex_greater_than_helper(n.substr(1));
    if (higher_rest) {
        re.push(lsd + "(" + higher_rest + ")");
    }
    if (re.length == 0) {
        return "";
    } else {
        return re.join("|");
    }
}

// Creates a regex matching all integers greater than or equal to the specified integer.
function regex_greater_than(n)
{
    n = n + "";
    var re = regex_greater_than_helper(n);
    if (re) {
        re += "|";
    }
    re += "[1-9][0-9]{" + n.length + "}[0-9]*";
    return "^" + re + "$";
}

window.loge10 = Math.log(10.0);
function log10(x)
{
    return Math.log(x) / loge10;
}

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

    } else if (window.highlight_option == "freq") {
        
        // 0 indicates a word that is never used.
        var re = new RegExp(regex_greater_than(window.current_freq));
        for (var i = start; i <= end; i++) {
            var line = lines[i];
            var children = line.getElementsByTagName("span");
            var nchildren = children.length;
            for (var j = 0; j < nchildren; j++) {
                var child = children[j];
                var freq = child.getAttribute("data-freq");
                if (freq) {
                    if (freq == "0") {
                        child.className = "absent-word";
                        continue;
                    } else if (freq.match(re)) {
                        child.className = "rare-word";
                        continue;
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

log_min_freq = log10(min_freq);
log_max_freq = log10(max_freq);
function scale_freq(freq)
{
    return Math.round((log10(freq) - log_min_freq) * slider_width / (log_max_freq - log_min_freq));
}
function unscale_freq(v)
{
    return Math.round(Math.pow(10, v * (log_max_freq - log_min_freq) / slider_width
                                   + log_min_freq));
}

function update_slider_value()
{
    if (window.highlight_option == "ngrams") {
        $(".selected-year").text(current_year);
        $(".slider-value").text(current_year);
        if ($("#slider").slider("value") != current_year) {
            $("#slider").slider("value", current_year);
        }
        $("#print-header-text").html("Highlighting words uncommon in: <b>" + current_year + "</b>");
    } else if (window.highlight_option == "freq") {
        $(".slider-value").text(format_freq(current_freq));
        var scaled_freq = scale_freq(current_freq);
        if ($("#slider").slider("value") != scaled_freq) {
            $("#slider").slider("value", scaled_freq);
        }
        $("#print-header-text").html("Highlighting words with average frequency >= <b>1/" + format_freq(current_freq) + "</b>");
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
        $("#freq-key").css("display", "none");
        $("#dict-key").css("display", "none");
        $("#play-button").button("enable");
        $("#slider").slider("enable");
        $("#slider").slider({
            value: end_year,
            min: start_year,
            max: end_year,
            slide: function(event, ui) {
                set_year(parseInt(ui.value));
            }
        });
        $("#slider-value-area").css("color", "black");
    } else if (window.highlight_option == "freq") {
        $("#ngrams-key").css("display", "none");
        $("#freq-key").css("display", "default");
        $("#dict-key").css("display", "none");
        $("#play-button").button("enable");
        $("#slider").slider("enable");
        $("#slider").slider({
            value: slider_width,
            min: 0,
            max: slider_width,
            slide: function(event, ui) {
                set_freq(unscale_freq(ui.value));
            }
        });
        $("#slider-value-area").css("color", "black");
    } else {
        var dict = window.highlight_option.substring(5);
        $("#ngrams-key").css("display", "none");
        $("#freq-key").css("display", "none");
        $("#dict-key").css("display", "default");
        $("#play-button").button("disable");
        $("#slider").slider("disable");
        $("#slider-value-area").css("color", "grey");
        $("#print-header-text").html("Highlighting words based on <b>" + dict_names[dict][1] + "</b>");
    }
    update_slider_value();
    if (window.word_list_visible) {
        update_word_list();
    }
}

// Change the stored year and update highlighting accordingly.
function set_year(year)
{
    window.current_year = year;
    update_slider_value();
    update_highlighting(true);
    if (window.word_list_visible) {
        update_word_list();
    }
    if (window.stats_box_visible) {
        update_document_stats();
    }
}

function set_freq(freq)
{
    window.current_freq = freq;
    update_slider_value();
    update_highlighting(true);
    if (window.word_list_visible) {
        update_word_list();
    }
    if (window.stats_box_visible) {
        update_document_stats();
    }
}

function animate()
{
    if (window.highlight_option == "ngrams") {
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
    } else if (window.highlight_option == "freq") {
        set_freq(min_freq);
        if (timer) {
            clearInterval(timer);
        }
        timer = setInterval(function() {
            set_freq(unscale_freq(scale_freq(current_freq) + 1));
            if (current_freq <= max_freq) {
                clearInterval(timer);
                update_location();
                return;
            }
        }, 20);
    }
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
    $("#definition-area").css("max-height", h - 368
                       - $("#usage-periods-text").height() - 115 - 24);
}

function get_words_with_class(css_class, filter_apostrophes)
{
    var d = {};
    $("#text-area ." + css_class).each(function (i, e) {
        d[$(e).text().toLowerCase()] = 1;
    });
    var words = [];
    for (var word in d) {
        if (filter_apostrophes && word.indexOf("'") >= 0) {
            continue;
        }
        word = word.replace("&", "&amp;");
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
        option_text = "The following words were marked as more common earlier or later than <b>" + window.current_year + "</b>.";
    } else if (window.highlight_option == "freq") {
        option_text = "The following words were marked as rare in the " + corpus_names[corpus] + " corpus (excluding words with apostrophes).";
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

    } else if (window.highlight_option == "freq") {

        var words = get_words_with_class("rare-word", true);
        if (words.length == 0) {
            html.push("No words found with average frequency <b>&lt;= " + format_freq(current_freq)
                      + "</b>.");
        } else {
            html.push("Words with average frequency <b>&lt;= " + format_freq(current_freq)
                      + "</b>:<div>");
            html.push(create_word_grid(words, "rare-word"));
            html.push("</div>");
        }
        html.push("<hr />");

        var words = get_words_with_class("absent-word", true);
        if (words.length == 0) {
            html.push("No words found that were absent from the corpus " + data_start_year + "-.");
        } else {
            html.push("Words that are absent from the corpus altogether (" + data_start_year + "-):<div>");
            html.push(create_word_grid(words, "absent-word"));
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
    hide_stats_box();
    window.word_list_visible = true;
}

function hide_word_list()
{
    $("#word-list").css("display", "none");
    window.word_list_visible = false;
}

function update_word_list_height()
{
    var h = window.innerHeight;
    $("#word-list-area").css("max-height", h - 33
                             - $("#word-list-option-text").height()
                             - 115 - 24);
}

function compute_document_stats()
{
    var nyears = end_year - start_year + 1;

    var stats = {
        "c": new Array(nyears),
        "o": new Array(nyears),
        "n": new Array(nyears),
        "l": new Array(nyears)
    };
    for (var i = 0; i < nyears; i++) {
        stats["c"][i] = 0;
        stats["o"][i] = 0;
        stats["n"][i] = 0;
        stats["l"][i] = 0;
    }

    var dict_stats = {
        "x": {},
        "o": {},
        "v": {}
    };
    for (var dict in dict_names) {
        dict_stats["x"][dict] = 0;
        dict_stats["o"][dict] = 0;
        dict_stats["v"][dict] = 0;
    }
    
    var freq_stats = {"l": new Array(slider_width), "a": 0};
    for (var i = 0; i <= slider_width; i++) {
        freq_stats["l"][i] = 0;
    }
    
    var unscaled_freqs = new Array(slider_width);
    for (var i = 0; i <= slider_width; i++) {
        unscaled_freqs[i] = unscale_freq(i);
    }

    var start = 0, end = window.lines.length - 1;
    for (var i = start; i <= end; i++) {
        var line = lines[i];
        var children = line.getElementsByTagName("span");
        var nchildren = children.length;
        for (var j = 0; j < nchildren; j++) {
            var child = children[j];
            var usage = child.getAttribute("data-usage");
            if (usage) {
                var len = usage.length;
                for (var k = 0; k < len; k += 5) {
                    var usage_state = usage.substr(k, 1);
                    var ch = usage.substr(k+3, 1);
                    if (ch == "x") {
                        var cent = parseInt(usage.substr(k+1, 2)) * 100;
                        var idx = cent - start_year;
                        for (var a = 0; a < 100; a++) {
                            stats[usage_state][idx + a] += 1;
                        }
                    } else if (ch == "l") {
                        var cent = parseInt(usage.substr(k+1, 2)) * 100;
                        var idx = cent - start_year;
                        for (var a = 0; a < 50; a++) {
                            stats[usage_state][idx + a] += 1;
                        }
                    } else if (ch == "r") {
                        var cent = parseInt(usage.substr(k+1, 2)) * 100;
                        var idx = cent - start_year;
                        for (var a = 50; a < 100; a++) {
                            stats[usage_state][idx + a] += 1;
                        }
                    } else {
                        ch = usage.substr(k+4, 1);
                        if (ch == "x") {
                            var dec = parseInt(usage.substr(k+1, 3)) * 10;
                            var idx = dec - start_year;
                            for (var a = 0; a < 10; a++) {
                                stats[usage_state][idx + a] += 1;
                            }
                        } else if (ch == "l") {
                            var dec = parseInt(usage.substr(k+1, 3)) * 10;
                            var idx = dec - start_year;
                            for (var a = 0; a < 5; a++) {
                                stats[usage_state][idx + a] += 1;
                            }
                        } else if (ch == "r") {
                            var dec = parseInt(usage.substr(k+1, 3)) * 10;
                            var idx = dec - start_year;
                            for (var a = 5; a < 10; a++) {
                                stats[usage_state][idx + a] += 1;
                            }
                        } else {
                            var y = parseInt(usage.substr(k+1, 4));
                            var idx = y - start_year;
                            stats[usage_state][idx] += 1;
                        }
                    }
                }
            }
            var dicts = child.getAttribute("data-dicts");
            if (dicts) {
                for (var dict in dict_names) {
                    if (dicts.indexOf("x" + dict) != -1) {
                        dict_stats["x"][dict] += 1;
                    }
                    if (dicts.indexOf("o" + dict) != -1) {
                        dict_stats["o"][dict] += 1;
                    }
                    if (dicts.indexOf("v" + dict) != -1) {
                        dict_stats["v"][dict] += 1;
                    }
                }
            }
            var freq = child.getAttribute("data-freq");
            if (freq) {
                if (freq == 0) {
                    freq_stats["a"] += 1;
                } else {
                    for (var k = slider_width; k >= 0; k--) {
                        if (parseInt(freq) <= unscaled_freqs[k]) {
                            break;
                        }
                        freq_stats["l"][k] += 1;
                    }
                }
            }
        }
    }

    var scale = 100.0 / word_count;
    for (var i = 0; i < nyears; i++) {
        stats["c"][i] = word_count - stats["o"][i]
            - stats["n"][i] - stats["l"][i];
        stats["c"][i] *= scale;
        stats["o"][i] *= scale;
        stats["n"][i] *= scale;
        stats["l"][i] *= scale;
    }
    for (var dict in dict_names) {
        dict_stats["x"][dict] *= scale;
        dict_stats["o"][dict] *= scale;
        dict_stats["v"][dict] *= scale;
    }
    for (var i = 0; i <= slider_width; i++) {
        freq_stats["l"][i] *= scale;
    }
    freq_stats["a"] *= scale;

    window.document_stats = stats;
    window.document_dict_stats = dict_stats;
    window.document_freq_stats = freq_stats;
}

function create_document_x_values()
{
    window.document_x_values = [];
    for (var i = start_year; i <= end_year; i++) {
        document_x_values.push(i);
    }
}

function update_document_stats()
{
    var nyears = end_year - start_year + 1;

    if (!window.document_stats) {
        compute_document_stats();
    }
    var stats = window.document_stats;

    if (!window.document_x_values) {
        create_document_x_values();
    }

    var max = 0.0;
    for (var i = 0; i < nyears; i++) {
        if (stats["o"][i] > max) max = stats["o"][i];
        if (stats["n"][i] > max) max = stats["n"][i];
        if (stats["l"][i] > max) max = stats["l"][i];
    }

    $("#document-chart").html("");

    var w = 410,
        h = 250,
        xmargin = 60,
        ymargin = 20,
        y = d3.scale.linear().domain([0, max]).range([h - ymargin, 10]),
        x = d3.scale.linear().domain([start_year, end_year]).range([xmargin, w - 10]);

    var vis = d3.select("#document-chart")
        .append("svg:svg")
        .attr("width", w)
        .attr("height", h)
        .attr("font-size", "10pt");
        
    var g = vis.append("svg:g");
    
    function add_line(usage_class, color) {
        var line = d3.svg.line()
            .x(function (d) { return x(d); })
            .y(function (d) { return y(stats[usage_class][d - start_year]); })
        g.append("path")
            .datum(document_x_values)
            .style("fill", "none")
            .style("stroke", color)
            .style("stroke-width", "2px")
            .attr("d", line);
    }
    //add_line("c", "black");
    add_line("o", "blue");
    add_line("n", "red");
    add_line("l", "orange");
    
    g.append("svg:line")
        .attr("x1", xmargin)
        .attr("y1", h - ymargin)
        .attr("x2", w - 10)
        .attr("y2", h - ymargin)
        .attr("stroke", "black");

    g.append("svg:line")
        .attr("x1", xmargin)
        .attr("y1", h - ymargin)
        .attr("x2", xmargin)
        .attr("y2", 0)
        .attr("stroke", "black");

    g.append("svg:line")
        .attr("x1", x(current_year))
        .attr("y1", h - ymargin)
        .attr("x2", x(current_year))
        .attr("y2", 0)
        .attr("stroke", "black");
    
    g.selectAll(".xTick")
        .data(x.ticks(5))
      .enter().append("svg:line")
        .attr("class", "xTick")
        .attr("x1", x)
        .attr("y1", h - ymargin + 5)
        .attr("x2", x)
        .attr("y2", h - ymargin)
        .attr("stroke", "black");
    
    g.selectAll(".xLabel")
        .data(x.ticks(5))
        .enter().append("svg:text")
        .attr("class", "xLabel")
        .text(String)
        .attr("x", x)
        .attr("y", h - ymargin + 17)
        .attr("text-anchor", "middle");

    g.selectAll(".yTick")
        .data(y.ticks(5))
      .enter().append("svg:line")
        .attr("class", "yTick")
        .attr("y1", y)
        .attr("x1", xmargin - 5)
        .attr("y2", y)
        .attr("x2", xmargin)
        .attr("stroke", "black");

    g.selectAll(".yLabel")
        .data(y.ticks(5))
      .enter().append("svg:text")
        .attr("class", "yLabel")
        .text(function (d) {return d + "%";})
        .attr("x", xmargin - 7)
        .attr("y", y)
        .attr("text-anchor", "end")
        .attr("dy", 4);
 
    var html = [];
    html.push("<div><b>" + d3.round(stats["c"][current_year - start_year], 1)
              + "</b>% of words in the text are in common use in <b>" + current_year + "</b></div>");
    html.push("<div><b>" + d3.round(stats["o"][current_year - start_year], 1)
              + "</b>% of words are more common <span class='old-word'>earlier</span></div>");
    html.push("<div><b>" + d3.round(stats["n"][current_year - start_year], 1)
              + "</b>% of words are more common <span class='new-word'>later</span></div>");
    html.push("<div><b>" + d3.round(stats["l"][current_year - start_year], 1)
              + "</b>% of words are more common <span class='lapsed-word'>both earlier and later</span></div>");
    $("#selected-year-stats").html(html.join(""));

    var freq_stats = window.document_freq_stats;
    html = [];
    html.push("<b>" + d3.round(100.0 - freq_stats["a"], 1)
              + "</b>% of words were found in the " + corpus_names[corpus] + " corpus");
    html.push("<div><b>" + d3.round(freq_stats["a"], 1)
              + "</b>% were <span class='absent-word'>not found</span></div>");
    html.push("<div><b>" + d3.round(freq_stats["l"][scale_freq(current_freq)], 1)
              + "</b>% had average frequency <span class='rare-word'>&lt;= " + format_freq(current_freq) + "</span></div>");
    $("#freq-stats").html(html.join(""));

    var dict_stats = window.document_dict_stats;
    html = [];
    for (var i = 0; i < dicts.length; i++) {
        var dict = dicts[i];
        if (i > 0) {
            html.push("<hr />");
        }
        html.push("<b>" + d3.round(100.0 - dict_stats["x"][dict], 1)
                  + "</b>% of words were found in <b>"
                  + dict_names[dict][0] + "</b>");
        html.push("<div><b>" + d3.round(dict_stats["x"][dict], 1)
                  + "</b>% were <span class='omitted-word'>not found</span></div>"); 
        html.push("<div><b>" + d3.round(dict_stats["o"][dict], 1)
                  + "</b>% are marked as <span class='obsolete-word'>rare or obsolete</span></div>"); 
        html.push("<div><b>" + d3.round(dict_stats["v"][dict], 1)
                  + "</b>% are marked as <span class='vulgar-word'>vulgar, colloquial, or improper</span></div>"); 
    }
    $("#dictionary-stats").html(html.join(""));
}

function show_stats_box()
{
    update_document_stats();
    $("#stats-box").css("display", "inline");
    hide_word_info();
    hide_word_list();
    window.stats_box_visible = true;
}

function hide_stats_box()
{
    $("#stats-box").css("display", "none");
    window.stats_box_visible = false;
}

function update_stats_box_height()
{
    var h = window.innerHeight;
    $("#dictionary-stats-area").css("max-height", h - 467 - 115 - 24);
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
    } else if (window.highlight_option == "freq") {
        highlight_option_str = "&d=freq";
    }
    return "/text/" + id + "?y=" + current_year + "&f=" + current_freq + highlight_option_str;
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
    $(".corpus-name").text(corpus_names[corpus]);
    $(".document-title").text(title);

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
    
    window.current_year = window.initial_year;
    window.current_freq = window.initial_freq;
    
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
    update_stats_box_height();
    window.onresize = function () {
        update_visibility();
        update_word_info_height();
        update_word_list_height();
        update_stats_box_height();
    };
    
    $("#text-area,#definition-area,#word-list-area").dblclick(function (e) {
        var word = get_selection();
        show_word_info(word, window.corpus);
        hide_word_list();
        hide_stats_box();
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
            hide_stats_box();
            return false;
        }
    });

    set_year(current_year);
    set_freq(current_freq);
    
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
            hide_stats_box();
        }
    });

    $("#main-area").click(function(e) {
        hide_word_info();
        hide_word_list();
        hide_stats_box();
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
    $("#word-info,#word-list,#dictionary-stats,#header")
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
