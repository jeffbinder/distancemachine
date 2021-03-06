function format_freq(freq)
{
    if (freq == 0) {
        return "0";
    } else if (freq >= 1000000000000) {
        return "1 / " + +(freq / 1000000000000).toFixed(2) + "T";
    } else if (freq >= 100000000000) {
        return "1 / " + +(freq / 1000000000).toFixed(0) + "B";
    } else if (freq >= 10000000000) {
        return "1 / " + +(freq / 1000000000).toFixed(1) + "B";
    } else if (freq >= 1000000000) {
        return "1 / " + +(freq / 1000000000).toFixed(2) + "B";
    } else if (freq >= 100000000) {
        return "1 / " + +(freq / 1000000).toFixed(0) + "M";
    } else if (freq >= 10000000) {
        return "1 / " + +(freq / 1000000).toFixed(1) + "M";
    } else if (freq >= 1000000) {
        return "1 / " + +(freq / 1000000).toFixed(2) + "M";
    } else if (freq >= 100000) {
        return "1 / " + +(freq / 1000).toFixed(0) + "K";
    } else if (freq >= 10000) {
        return "1 / " + +(freq / 1000).toFixed(1) + "K";
    } else if (freq >= 1000) {
        return "1 / " + +(freq / 1000).toFixed(2) + "K";
    } else if (freq >= 100) {
        return "1 / " + +freq.toFixed(0);
    } else if (freq >= 10) {
        return "1 / " + +freq.toFixed(1);
    } else if (freq >= 1) {
        return "1 / " + +freq.toFixed(2);
    }
}

function create_x_values(corpus)
{
    window.x_values = [];
    for (var i = data_start_year[corpus]; i <= data_end_year[corpus]; i++) {
        x_values.push(i);
    }
    window.decades = [];
    for (var i = data_start_year[corpus]; i <= data_end_year[corpus]; i += 10) {
        decades.push(i);
    }
    window.x_values_corpus = corpus;
}

function compute_word_usage_stats(word, corpus, data)
{
    var counts = data['counts'];
    
    var max = 0, total = 0;
    for (var x = data_start_year[corpus]; x <= data_end_year[corpus]; x++) {
        var y;
        if ((totals[corpus][x] || 0) == 0) {
            y = 0;
        } else {
            y = (counts[x] || 0) / totals[corpus][x];
        }
        if (y > max) {
            max = y;
        }
        total += y;
    }
    var avg = total / (data_end_year[corpus] - data_start_year[corpus] + 1);
    
    data['avg'] = avg;
    data['max'] = max;
    data['total'] = total;
}

function update_word_usage_chart(word, corpus, data)
{
    if (!word) {
	word = window.last_word_usage_chart_word;
	corpus = window.last_word_usage_chart_corpus;
	data = window.last_word_usage_chart_data;
    } else {
	window.last_word_usage_chart_word = word;
	window.last_word_usage_chart_corpus = corpus;
	window.last_word_usage_chart_data = data;
    }

    var counts = data['counts'],
        periods = data['periods'],
        avg = data['avg'],
        max = data['max'],
        total = data['total'];

    if (window.x_values_corpus != corpus) {
        create_x_values(corpus);
    }
    
    // Convert the usage periods to segments for new, old, lapsed, and current.
    var start_year = data_start_year[corpus];
    var period_segments = [];
    if (periods.length) {
	if (periods[0][0] > start_year) {
	    period_segments.push({start: start_year, end: periods[0][0] - 0.5, type: "n"});
	}
	for (var i = 0; i < periods.length; i++) {
	    if (i > 0) {
		period_segments.push({start: periods[i-1][1] + 0.5,
                                      end: periods[i][0] - 0.5, type: "l"});
	    }
	    period_segments.push({start: periods[i][0] - 0.5,
				  end: (periods[i][1] || data_end_year[corpus]) + 0.5, type: "c"});
	}
	if ((periods[periods.length-1][1] || data_end_year[corpus]) < data_end_year[corpus]) {
	    period_segments.push({start: (periods[periods.length-1][1] || data_end_year[corpus]) + 0.5,
				  end: data_end_year[corpus], type: "o"});
	}
    }
    
    $("#word-usage-chart").html("");

    var w = 410,
        h = 250,
        xmargin = 60,
        ymargin = 20
        y = d3.scale.linear().domain([0, max]).range([h - ymargin, 5]),
        x = d3.scale.linear().domain([data_start_year[corpus], data_end_year[corpus]]).range([xmargin, w - 10]);

    var vis = d3.select("#word-usage-chart")
        .append("svg:svg")
        .attr("width", w)
        .attr("height", h)
        .attr("font-size", "10pt");
        
    var g = vis.append("svg:g");
    
    var line = d3.svg.area()
        .x(function (d) { return x(d); })
        .y0(h - ymargin)
        .y1(function (d) { return y(totals[corpus][d] ? (counts[d] || 0) / totals[corpus][d] : 0); });

    // Segments in background.
    if (chart_highlight_mode) {
	g.selectAll(".periodSegment")
            .data(period_segments)
	  .enter().append("svg:rect")
            .attr("x", function (d) {return x(d.start);})
            .attr("y", function (d) {return 0;})
            .attr("width", function (d) {return x(d.end) - x(d.start);})
            .attr("height", function (d) {return h - ymargin;})
            .attr("fill", function (d) {return d.type == "n" ? "pink" : d.type == "l" ? "#F0E68C" :
                                               d.type == "o" ? "lightBlue" : "none";});
    }

    // Chart colored based on the usage model.
    /*g.selectAll(".periodSegment")
        .data(period_segments)
      .enter().append("svg:path")
        .attr("d", function (d) {return line(x_values.slice(d.start - start_year, d.end - start_year + 1));})
        .attr("fill", function (d) {return d.type == "n" ? "red" : d.type == "l" ? "orange" :
                                           d.type == "o" ? "blue" : "black";});*/

    // Entire chart in black.
    g.append("svg:path")
        .attr("d", line(x_values));
    
    g.append("svg:line")
        .attr("x1", xmargin)
        .attr("y1", h - ymargin)
        .attr("x2", w - 10)
        .attr("y2", h - ymargin)
        .attr("stroke", "black");
    
    // Horizontal bar to indicate usage periods.
    if (!chart_highlight_mode) {
	g.selectAll(".periodLine")
            .data(periods)
	  .enter().append("svg:line")
            .attr("class", "periodLine")
            .attr("x1", function (d) {return x(d[0]);})
            .attr("y1", y(avg))
            .attr("x2", function (d) {return x(d[1] || data_end_year[corpus]);})
            .attr("y2", y(avg))
            .attr("stroke", "red")
            .attr("stroke-width", "2px")
            .attr("fill", "none");
	
	g.selectAll(".periodLeftBar")
            .data(periods)
	  .enter().append("svg:line")
            .attr("class", "periodLeftBar")
            .attr("x1", function (d) {return x(d[0]);})
            .attr("y1", y(avg) - 10)
            .attr("x2", function (d) {return x(d[0]);})
            .attr("y2", y(avg) + 10)
            .attr("stroke", function (d) {return d[0] == data_start_year[corpus] ? "none" : "red";})
            .attr("stroke-width", "2px")
            .attr("fill", "none");
	
	g.selectAll(".periodRightBar")
            .data(periods)
	  .enter().append("svg:line")
            .attr("class", "periodRightBar")
            .attr("x1", function (d) {return x(d[1] || data_end_year[corpus]);})
            .attr("y1", y(avg) - 10)
            .attr("x2", function (d) {return x(d[1] || data_end_year[corpus]);})
            .attr("y2", y(avg) + 10)
            .attr("stroke", function (d) {return !d[1] ? "none" : "red";})
            .attr("stroke-width", "2px")
            .attr("fill", "none");
    }

    g.append("svg:line")
        .attr("x1", xmargin)
        .attr("y1", h - ymargin)
        .attr("x2", xmargin)
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
        .text(function (d) {return d.toExponential(1);})
        .attr("x", xmargin - 7)
        .attr("y", y)
        .attr("text-anchor", "end")
        .attr("dy", 4);

    if ('current_year' in window) {
	g.append("svg:line")
            .attr("x1", x(current_year))
            .attr("y1", h - ymargin)
            .attr("x2", x(current_year))
            .attr("y2", 0)
            .attr("stroke", "#c5b390");
    }

    g.append("svg:rect")
        .attr("x", xmargin)
        .attr("y", 0)
        .attr("height", h - ymargin)
        .attr("width", w - 10)
        .attr("fill", "none")
        .style("pointer-events", "all")
        .on("mousemove", function (d) {
	    var mouse_x = x.invert(d3.mouse(this)[0]);
	    update_hovertext(mouse_x);
        })
        .on("mouseout", function (d) {
            hide_hovertext();
        });

    g.selectAll(".decade")
        .data(decades)
      .enter().append("svg:a")
        .attr("xlink:href", function (d) {
            return get_search_url(word, d, d + 10);
        })
        .attr("target", archive ? corpus + "_search" : "_blank")
      .append("svg:rect")
        .attr("class", "decade")
        .attr("x", x)
        .attr("y", 0)
        .attr("height", h - ymargin)
        .attr("width", function (d) {return x(d+9) - x(d);})
        .attr("fill", "black")
        .style("opacity", 0)
        .on("mouseover", function (d) {
            d3.select(this).style("opacity", 0.2);
        })
        .on("mouseout", function (d) {
            d3.select(this).style("opacity", 0);
	    hide_hovertext()
        })
        .on("mousemove", function (d) {
	    var mouse_x = x.invert(d3.mouse(this)[0]);
            update_hovertext(mouse_x);
        });

    var hoverbox = g.append("svg:rect")
        .attr("class", "hoverbox")
        .attr("fill", "white")
        .attr("stroke", "black")
        .style("visibility", "hidden")
        .style("pointer-events", "none");
    var hovertext = g.append("svg:text")
        .attr("class", "hovertext")
        .text("test")
        .style("visibility", "hidden")
        .style("pointer-events", "none");
    function update_hovertext(mouse_x) {
	if (mouse_x > end_year[corpus]) {
	    return;
	}
	var data_x = Math.round(mouse_x);
	var data_y = totals[corpus][data_x]
                       ? (counts[data_x] || 0) / totals[corpus][data_x] : 0;
        hovertext
            .attr("x", x(data_x) + 4)
            .attr("y", y(data_y) - 8)
	    .text(data_x + ": " + data_y.toExponential(1))
            .style("visibility", "visible");
	var bbox = hovertext.node().getBBox();
	if (bbox.y < 4) {
	    bbox.y = 4;
            hovertext.attr("y", bbox.height)
	}
	if (bbox.x + bbox.width + 5 > w) {
	    bbox.x = w - bbox.width - 5;
            hovertext.attr("x", w - bbox.width - 5)
	}
	hoverbox
	    .attr("x", bbox.x - 4)
	    .attr("y", bbox.y - 2)
	    .attr("width", bbox.width + 8)
	    .attr("height", bbox.height + 4)
            .style("visibility", "visible");
    }
    function hide_hovertext() {
        hovertext.style("visibility", "hidden");
	hoverbox.style("visibility", "hidden");
    }
}

function update_word_usage_text(word, data)
{
    var periods = data['periods'],
        avg = data['avg'],
        html = [];
    
    if (periods.length == 0) {
        html.push("No usage data available for this word. <a href='"
                  + ('http://www.google.com/search?q="' + word
                    + '"&tbs=bks:1,cdr:1&num=100&lr=lang_en') + "' target='_blank'>"
                  + "Search Google Books</a>.");
    } else {
        html.push("Avg freq <b>" + format_freq(1.0 / avg) + "</b>. ");
        if (periods.length == 1) {
            if (periods[0][0] == data_start_year[word_info_selected_corpus] && !periods[0][1]) {
                html.push("Appears with consist frequency through the whole period covered");
            } else {
                html.push("Most frequent from ");
                html.push("<span class='usage-period-text'>");
                html.push(periods[0][0]);
                html.push("-");
                html.push(periods[0][1]);
                html.push("</span>");
            }
        } else if (periods.length == 2) {
            html.push("Most frequent from ");
            html.push("<span class='usage-period-text'>");
            html.push(periods[0][0]);
            html.push("-");
            html.push(periods[0][1]);
            html.push("</span>");
            html.push(" and from ");
            html.push("<span class='usage-period-text'>");
            html.push(periods[1][0]);
            html.push("-");
            html.push(periods[1][1]);
            html.push("</span>");
        } else {
            html.push("Most frequent from ");
            for (var i in periods) {
                html.push("<span class='usage-period-text'>");
                html.push(periods[i][0]);
                html.push("-");
                html.push(periods[i][1]);
                html.push("</span>");
                if (i == periods.length - 2) {
                    html.push(", and ");
                } else {
                    html.push(", ");
                }
            }
        }
        html.push(".");
    }
    
    $("#usage-periods-text").html(html.join(''));
}

function update_word_definitions(word, corpus, data)
{
    var wordnet_definitions = data['wordnet-definitions'],
        html = [];
        
    if (!wordnet_definitions.length) {
        html.push("No <a href=\"http://wordnet.princeton.edu/\" target=\"_blank\">WordNet</a> definitions available.");
    } else {
        html.push("<a href=\"http://wordnet.princeton.edu/\" target=\"_blank\">WordNet</a> definitions:");
    }
    
    for (var i in wordnet_definitions) {
        var definition = wordnet_definitions[i];
        var lemma = definition[0], pos = definition[1], rank = definition[2],
            synonyms = definition[3].split(';'), def = definition[4];
        // var samples = definition[5].split(';');
        html.push("<div>" + rank);
        html.push(" (");
        html.push(pos);
        html.push(") ");
        var first = true;
        for (var j in synonyms) {
            var syn = synonyms[j];
            if (!first) {
                html.push(", ");
            }
            if (syn == lemma) {
                html.push("<b>");
                html.push(syn);
                html.push("</b>");
            } else if (syn.indexOf(" ") != -1) {
                html.push(syn);
            } else {
                html.push('<a href="javascript:push_history();show_word_info(\'' + syn + '\',\'' + corpus + '\')">');
                html.push(syn);
                html.push("</a>");
            }
            first = false;
        }
        html.push(": ");
        html.push(def);
        /*for (var j in samples) {
            var sample = samples[j];
            html.push("<br/><i>&ldquo;");
            html.push(sample);
            html.push("&rdquo;</i>");
        }*/
        html.push("</div>");
    }
    
    var dict_definitions = data['dict-definitions'];
    
    for (var i in dicts) {
        var dict = dicts[i];
        var definitions = dict_definitions[dict],
            dict_name = dict_names[dict];
            
        html.push("<hr/>");
        
        if (!definitions.length) {
            html.push(dict_name[0] + " definition not available.");
        } else {
            html.push("Definition from <b>" + dict_name[1] + "</b>:");
        }
    
        for (var i in definitions) {
            var definition = definitions[i][0];
            pars = definition.split('\n');
            for (var j in pars) {
                html.push("<div>" + pars[j] + "</div>");
            }
        }
        
        html.push("<div><a href='javascript:push_history();show_reverse_lookup_box(\"" + word
                  + "\", \"" + dict + "\")'>Find definitions in " + dict_name[0]
                  + " that use this word</a>.</div>");
    }
    
    $("#definitions").html(html.join(''));
}

// This is used to determine whether another word info request was put in before the
// present one has finished, in which case we must abort.
window.last_request_id = 0;

function show_word_info(word, corpus, scroll_top)
{
    $("#selected-word").text(word);
    $("#word-search-link").attr("href", get_search_url(word));
    if (archive) {
	$("#word-search-link").attr("target", corpus + "_search");
    } else {
	$("#word-search-link").attr("target", "_blank");
    }
    $("#word-usage-chart").html("<div id='word-info-loading-message'>Loading...</div>");
    $("#usage-periods-text").html("");
    $("#definitions").html("");
    $("#word-info").css("display", "inline");
    
    hide_reverse_lookup_box();
    window.word_info_visible = true;
    window.word_info_selected_word = word;
    window.word_info_selected_corpus = corpus;
    
    last_request_id += 1;
    var request_id = last_request_id;
    $.getJSON("wordinfo.php", {"word": word, "corpus": corpus}, function(data) {
        if (request_id == last_request_id) {
            compute_word_usage_stats(word, corpus, data);
            update_word_usage_text(word, data);
            update_word_usage_chart(word, corpus, data);
            update_word_definitions(word, corpus, data);
            if (update_word_info_height) {
                update_word_info_height();
            }
            if (scroll_top) {
                $("#definition-area").scrollTop(scroll_top);
            }
        }
    })
    .fail(function () {
        $("#word-usage-chart").html("<div id='word-info-loading-message'>Error loading word info.</div>");
    });
}

function hide_word_info()
{
    $("#word-info").css("display", "none");
    window.word_info_visible = false;
}

// Optionally implemented by the page.
function update_reverse_lookup_box_height()
{
}

function update_reverse_lookup_box(word, dict, data)
{
    var headwords = data['headwords'].sort(),
        html = [];
        
    for (var i in headwords) {
        html.push("<div class='grid-word'> " + headwords[i] + " </div>");
    }
    html = html.join("");
    
    $("#reverse-lookup-words").html(html);
    
    if (headwords.length > 0) {
        $("#reverse-lookup-text").html("Variants of the word <span id='reverse-lookup-word'>" + word
                                       + "</span> appear in the <span id='reverse-lookup-dict'>"
                                       + dict_names[dict][0] + "</span> definitions for "
                                       + "<span id='reverse-lookup-count'>" + headwords.length
                                       + "</span> words:");
    } else {
        $("#reverse-lookup-text").html("Variants of the word <span id='reverse-lookup-word'>" + word
                                       + "</span> do not appear in any <span id='reverse-lookup-dict'>"
                                       + dict_names[dict][0] + "</span> definitions.");
    }
    
    update_reverse_lookup_box_height();
}

function show_reverse_lookup_box(word, dict, scroll_top)
{
    $("#reverse-lookup-word").text(word);
    $("#reverse-lookup-dict").text(dict_names[dict][0]);
    $("#reverse-lookup-text").html("Loading...");
    $("#reverse-lookup-words").html("");
    $("#reverse-lookup-box").css("display", "inline");
    
    hide_word_info();
    window.reverse_lookup_box_visible = true;
    window.word_info_selected_word = word;
    window.word_info_selected_dict = dict;
    
    last_request_id += 1;
    var request_id = last_request_id;
    $.getJSON("reverselookup.php", {"word": word, "dict": dict}, function(data) {
        if (request_id == last_request_id) {
            $("#reverse-lookup-status").html("");
            update_reverse_lookup_box(word, dict, data);
            if (scroll_top) {
                $("#reverse-lookup-word-area").scrollTop(scroll_top);
            }
        }
    })
    .fail(function () {
        $("#reverse-lookup-text").html("Error looking up headwords.");
    });
}

function hide_reverse_lookup_box()
{
    $("#reverse-lookup-box").css("display", "none");
    window.reverse_lookup_box_visible = false;
}

$(function () {
    if (archive) {
	window.get_search_url = function (word, start_year, end_year)
	{
	    var url = '/archive/' + corpus + '/search?q=' + word;
	    if (start_year) {
		url += '&ymin=' + start_year;
	    }
	    if (end_year) {
		url += '&ymax=' + end_year;
	    }
	    return url;
	}
    } else {
	window.get_search_url = function (word, start_year, end_year)
	{
	    var url = ('http://www.google.com/search?q="' + word
		       + '"&tbs=bks:1,cdr:1');
	    if (start_year) {
		url += ',cd_min:' + start_year;
	    }
	    if (end_year) {
		url += ',cd_max:' + end_year;
	    }
	    url += '&num=100&lr=lang_en';
	    return url;
	}
    }
});
