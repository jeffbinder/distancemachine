window.last_request_id = 0;

// From http://stackoverflow.com/questions/487073/check-if-element-is-visible-after-scrolling.
function is_on_screen(elem)
{
    var $elem = $(elem);
    var $window = $(window);

    var docViewTop = $window.scrollTop();
    var docViewBottom = docViewTop + $window.height();

    var elemTop = $elem.offset().top;
    var elemBottom = elemTop + $elem.height();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}

window.results_to_load = [];
function clear_results_to_load()
{
    window.results_to_load = [];
}
function add_result_to_load(uri, q)
{
    results_to_load.push([uri, q]);
}

window.loading_results = false;
function load_results_on_screen()
{
    if (loading_results) {
	return;
    }
    loading_results = true;
    
    var to_load = [];

    for (var i = 0; i < results_to_load.length; i++) {
        var uri = results_to_load[i][0];
	if (is_on_screen($("#" + uri + "-loading-message"))) {
	    to_load.push(i);
	}
    }

    for (var k = 0; k < to_load.length; k++) {
	var i = to_load[k];

        var uri = results_to_load[i][0];
        var q = results_to_load[i][1];

	load_excerpts(uri, q);
    }

    for (var k = 0; k < to_load.length; k++) {
	results_to_load.splice(to_load[k] - k, 1);
    }

    loading_results = false;
}

function load_excerpts(uri, q)
{
    var excerpts_selector = "#" + uri + "-excerpts";
    $(excerpts_selector).html("<div id='excerpt-loading-message'>Loading excerpts...</div>");
    
    $.getJSON("getexcerpts.php", {"q": q, "corpus": corpus, "uri": uri}, function(data) {
        var excerpts = data['excerpts'], nmatches = data['nmatches'], html = [];
	var qi = 0;
        for (var i = 0; i < excerpts.length; i++) {
            var excerpt = excerpts[i];
            html.push("<div class='excerpt'>");
	    // The PHP places <b> tags around the instances of the word; add
	    // links to them.
	    var excerpt_parts = excerpt.split("<b>");
	    excerpt = [];
	    for (var j = 0; j < excerpt_parts.length; j++) {
		if (j > 0) {
		    excerpt.push("<b><a class='quiet-link' href='/archive/"
                                 + corpus + "/" + uri + "?q=" + q + "&qi=" + qi
                                 + "' target='" + corpus + "_view'>");
		    qi += 1;
		}
		excerpt.push(excerpt_parts[j]);
	    }
	    excerpt = excerpt.join("");
	    excerpt = excerpt.split("</b>").join("</a></b>");
            html.push(excerpt);
            html.push("</div>");
        }
        $(excerpts_selector).html(html.join(''));
	for (var i = 0; i < nmatches.length; i++) {
	    var nmatches_selector = "#" + uri + "-" + i + "-nmatches";
            if (nmatches[i] == 1) {
		$(nmatches_selector).html(nmatches[i] + " instance");
            } else {
		$(nmatches_selector).html(nmatches[i] + " instances");
	    }
        }
	$("#" + uri + "-loading-message").css("display", "none");
	$("#" + uri + "-nmatches-area").css("display", "inline");
    })
    .fail(function () {
        $(excerpts_selector).html("<div id='excerpt-loading-message'>Error loading excerpts.</div>");
    });
}

function display_search_results(results, q)
{
    if (results.length == 0) {
        $("#result-area").html("<div id='search-loading-message'>No results found.</div>");
        return;
    }

    results.sort(function (a, b) {
        if (a['metadata']['pub_year'] < b['metadata']['pub_year']) {
            return -1;
        }
        if (a['metadata']['pub_year'] > b['metadata']['pub_year']) {
            return 1;
        }
        return 0;
    });

    var words = q.match(/\w+|"(?:\\"|[^"])+"/g);

    var html = [];
    for (var i = 0; i < results.length; i++) {
        var result = results[i];
        var uri = result['uri'];
        var title = result['metadata']['title'];
        var pub_year = result['metadata']['pub_year'];
        
        html.push("<div class='search-result'>");
        html.push("<a href='/archive/");
        html.push(corpus + '/' + uri + '?q=' + q);
        html.push("' target='" + corpus + "_view'>");
        html.push(title);
        html.push("</a> (");
        html.push(pub_year);
        html.push(")<br/><div id='");
        html.push(uri);
	html.push("-loading-message'>Loading excerpts...</div><div id='");
        html.push(uri);
        html.push("-nmatches-area' style='display:none'>");
        var first = true;
        for (var j in words) {
	    var word = words[j];
            if (!first) {
                html.push(", ");
            }
            html.push('<span id="' + uri + '-' + j + '-nmatches"></span>');
            html.push(" of &ldquo;");
	    if (word[0] == '"') {
		html.push(word.substring(1, word.length - 1));
	    } else {
		html.push(word);
	    }
            html.push("&rdquo;");
            first = false;
        }
        html.push("</div><hr/>");
        html.push("<div class='excerpts' id='");
        html.push(uri);
        html.push("-excerpts'></div>");
        html.push("</div>");

	add_result_to_load(uri, q);
    }
    html = html.join('');
    $("#result-area").html(html);
    
    load_results_on_screen();
}

function load_search_result_totals(results)
{
    if (results.length == 0) {
	search_result_totals = null;
	return;
    }
    var totals = {};
    for (var i = 0; i < results.length; i++) {
        var result = results[i];
        var uri = result['uri'];
        var pub_year = parseInt(result['metadata']['pub_year']);
	if (pub_year in totals) {
	    totals[pub_year] += 1;
	} else {
	    totals[pub_year] = 1;
	}
    }
    search_result_totals = totals;
}

function run_search()
{
    clear_results_to_load();

    $("#result-area").html("<div id='search-loading-message'>Loading...</div>");

    var q = $("#search-box").val();
    var ymin = $("#ymin-box").val();
    var ymax = $("#ymax-box").val();

    last_request_id += 1;
    var request_id = last_request_id;
    $.getJSON("archivesearch.php", {"q": q, "corpus": corpus, "ymin": ymin, "ymax": ymax}, function(data) {
        if (request_id == last_request_id) {
            display_search_results(data, q);
	    load_search_result_totals(data);
	    update_search_chart();
        }
    })
    .fail(function () {
        $("#result-area").html("<div id='search-loading-message'>Error loading search results.</div>");
    });
}

window.word_info_request_id = 0;
window.word_info = {};
window.search_result_totals = null;

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

function update_search_chart()
{
    /*var smoothing = 0;
    for (var word in word_info) {
        var counts = word_info[word];
        var newcounts = {};
        for (var i = data_start_year[corpus]; i <= data_end_year[corpus]; i++) {
            var left = i - smoothing,
                right = i + smoothing;
            if (left < data_start_year[corpus]) left = data_start_year[corpus];
            if (right > data_end_year[corpus]) right = data_end_year[corpus];
            var sum = 0;
            for (var j = left; j <= right; j++) {
                sum += counts[j] || 0;
            }
            var avg = sum / (right - left + 1);
            newcounts[i] = avg;
        }
        word_info[word] = newcounts;
    }*/

    var maxima = {};
    for (var word in word_info) {
        var counts = word_info[word];
        maxima[word] = 0;
        for (var x = data_start_year[corpus]; x <= data_end_year[corpus]; x++) {
            var y;
            if ((totals[corpus][x] || 0) == 0) {
                y = 0;
            } else {
                y = (counts[x] || 0) / totals[corpus][x];
            }
            if (y > maxima[word]) {
                maxima[word] = y;
            }
        }
    }

    var search_result_totals_max = 0;
    if (search_result_totals) {
	for (var x = data_start_year[corpus]; x <= data_end_year[corpus]; x++) {
            var y;
            y = (search_result_totals[x] || 0);
            if (y > search_result_totals_max) {
		search_result_totals_max = y;
            }
	}
    }

    if (window.x_values_corpus != corpus) {
        create_x_values(corpus);
    }

    $("#search-chart").html("");

    var w = 600,
        h = 200,
        xmargin = 20,
        ymargin = 20,
        rightmargin = 100,
        y = d3.scale.linear().domain([0, 1]).range([h - ymargin, 5]),
        x = d3.scale.linear().domain([data_start_year[corpus], data_end_year[corpus]]).range([xmargin, w - rightmargin]);

    var vis = d3.select("#search-chart")
        .append("svg:svg")
        .attr("width", w)
        .attr("height", h)
        .attr("font-size", "10pt");
        
    var g = vis.append("svg:g");
    
    g.append("svg:line")
        .attr("x1", xmargin)
        .attr("y1", h - ymargin)
        .attr("x2", w - rightmargin)
        .attr("y2", h - ymargin)
        .attr("stroke", "black");

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
    
    if (search_result_totals) {
        var counts = search_result_totals, max = search_result_totals_max;

        var line = d3.svg.area()
            .x(function (d) { return x(d); })
            .y0(y(0))
            .y1(function (d) { return y((counts[d] || 0) / max); });

        g.append("svg:path")
            .attr("d", line(x_values))
            .attr("stroke", "none")
            .attr("fill", "#999999");
    }
    
    var colors = d3.scale.category10();
    for (var word in word_info) {
        if (maxima[word] == 0) {
            continue;
        }

        var counts = word_info[word];

        var line = d3.svg.line()
            .x(function (d) { return x(d); })
            .y(function (d) { return y((totals[corpus][d] ? (counts[d] || 0) / totals[corpus][d] : 0) / maxima[word]); });

        g.append("svg:path")
            .attr("d", line(x_values))
            .attr("stroke", colors(word))
            .attr("fill", "none");

        var lastx = x_values[x_values.length - 1];
        g.append("svg:text")
            .attr("x", x(lastx) + 5)
            .attr("y", y((totals[corpus][lastx] ? (counts[lastx] || 0) / totals[corpus][lastx] : 0) / maxima[word]))
            .attr("stroke", colors(word))
            .text(word);
    }
    
    var ymin = $("#ymin-box").val();
    var ymax = $("#ymax-box").val();
    g.append("svg:rect")
        .attr("class", "yearRange")
        .attr("x", x(ymin))
        .attr("width", x(ymax) - x(ymin))
        .attr("y", 0)
        .attr("height", h - ymargin)
        .attr("fill", "rgba(0,0,0,0.1)");
    g.append("svg:circle")
        .attr("cx", x(ymin))
        .attr("cy", h - ymargin)
        .attr("r", 4)
        .attr("fill", "lightBlue")
        .attr("stroke", "#555555")
        .call(d3.behavior.drag()
            .on("drag", function () {
                var new_ymin = x.invert(x(ymin) + d3.event.dx);
                if (new_ymin < data_start_year[corpus]) new_ymin = data_start_year[corpus];
                if (new_ymin > ymax) new_ymin = ymax;
                ymin = new_ymin;
                $("#ymin-box").val(Math.round(ymin));
                d3.select(this).attr("cx", x(ymin));
                d3.select(".yearRange")
                    .attr("x", x(ymin))
                    .attr("width", x(ymax) - x(ymin));
            })
            .on("dragend", function () {
                run_search();
            }));
    g.append("svg:circle")
        .attr("cx", x(ymax))
        .attr("cy", h - ymargin)
        .attr("r", 4)
        .attr("fill", "lightBlue")
        .attr("stroke", "#555555")
        .call(d3.behavior.drag()
            .on("drag", function () {
                var new_ymax = x.invert(x(ymax) + d3.event.dx);
                if (new_ymax < ymin) new_ymax = ymin;
                if (new_ymax > data_end_year[corpus]) new_ymax = data_end_year[corpus];
                ymax = new_ymax;
                $("#ymax-box").val(Math.round(ymax));
                d3.select(this).attr("cx", x(ymax));
                d3.select(".yearRange")
                    .attr("width", x(ymax) - x(ymin));
            })
            .on("dragend", function () {
                run_search();
            }));
}

function load_word_info()
{
    var q = $("#search-box").val();
    var words = $.trim(q).match(/\w+|"(?:\\"|[^"])+"/g);
    if (!words) {
	words = [];
    }
   
    word_info = {};

    word_info_request_id += 1;
    var request_id = word_info_request_id;
    for (var i = 0; i < words.length; i++) {
        var word = words[i];
        $.getJSON("wordcounts.php", {"word": word, "corpus": corpus},
                  (function (word) {
                      return function(data) {
                          if (request_id == word_info_request_id) {
                              word_info[word] = data;
                              update_search_chart();
                          }
                      }
                  })(word))
        .fail(function () {
            $("#search-chart").html("<div id='search-chart-message'>Error loading word info.</div>");
        });
    }
}

function load_totals()
{
    $.ajax({
        type: "GET",
        url: "gettotals.php",
        async: false,
        dataType: "json",
        success: function (data) {
            window.totals = data;
        }
    });
}

$(function () {
    // Get the URL parameters.
    var params = {};
    var param_string = window.location.search.substring(1);
    var parts = param_string.split('&');
    for (var i = 0; i < parts.length; i++) 
    {
        var d = parts[i].split('=');
        params[d[0]] = decodeURIComponent(d[1]);
    }

    if ("ymin" in params) {
        $("#ymin-box").val(params["ymin"]);
    } else {
        $("#ymin-box").val(data_start_year[corpus]);
    }

    if ("ymax" in params) {
        $("#ymax-box").val(params["ymax"]);
    } else {
        $("#ymax-box").val(data_end_year[corpus]);
    }

    load_totals();

    if ("q" in params) {
        $("#search-box").val(params["q"]);
        load_word_info();
        run_search();
    }

    $("#corpus-name").text(corpus_names[corpus]);

    var search_timout = null;
    $("#search-box,#ymin-box,#ymax-box").keyup(function (e) {
        load_word_info();
        if (e.keyCode == 13) {
            run_search();
            clearTimeout(search_timout);
        } else {
            clearTimeout(search_timout);
            search_timout = setTimeout(run_search, 1000);
        }
    });

    $(window).scroll(load_results_on_screen);
});
