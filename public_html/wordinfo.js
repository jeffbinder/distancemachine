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

function create_x_values()
{
    window.x_values = [];
    for (var i = data_start_year; i <= data_end_year; i++) {
        x_values.push(i);
    }
    window.decades = [];
    for (var i = data_start_year; i <= data_end_year; i += 10) {
        decades.push(i);
    }
}

function compute_word_usage_stats(word, corpus, data)
{
    var counts = data['counts'];
    
    var max = 0, total = 0;
    for (var x = data_start_year; x <= data_end_year; x++) {
        var y = (counts[x] || 0) / totals[corpus][x];
        if (y > max) {
            max = y;
        }
        total += y;
    }
    var avg = total / (data_end_year - data_start_year + 1);
    
    data['avg'] = avg;
    data['max'] = max;
    data['total'] = total;
}

function update_word_usage_chart(word, corpus, data)
{
    var counts = data['counts'],
        periods = data['periods'],
        avg = data['avg'],
        max = data['max'],
        total = data['total'];

    if (!window.x_values) {
        create_x_values();
    }
    
    $("#word-usage-chart").html("");

    var w = 410,
        h = 250,
        xmargin = 60,
        ymargin = 20
        y = d3.scale.linear().domain([0, max]).range([h - ymargin, 5]),
        x = d3.scale.linear().domain([data_start_year, data_end_year]).range([xmargin, w - 10]);

    var vis = d3.select("#word-usage-chart")
        .append("svg:svg")
        .attr("width", w)
        .attr("height", h)
        .attr("font-size", "10pt");
        
    var g = vis.append("svg:g");
    
    var line = d3.svg.area()
        .x(function (d) { return x(d); })
        .y0(h - ymargin)
        .y1(function (d) { return y((counts[d] || 0) / totals[corpus][d]); })
    
    g.append("svg:path")
        .attr("d", line(x_values));
    
    g.append("svg:line")
        .attr("x1", xmargin)
        .attr("y1", h - ymargin)
        .attr("x2", w - 10)
        .attr("y2", h - ymargin)
        .attr("stroke", "black");
    
    g.selectAll(".periodLine")
        .data(periods)
      .enter().append("svg:line")
        .attr("class", "periodLine")
        .attr("x1", function (d) {return x(d[0]);})
        .attr("y1", y(avg))
        .attr("x2", function (d) {return x(d[1] || data_end_year);})
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
        .attr("stroke", function (d) {return d[0] == data_start_year ? "none" : "red";})
        .attr("stroke-width", "2px")
        .attr("fill", "none");
    
    g.selectAll(".periodRightBar")
        .data(periods)
      .enter().append("svg:line")
        .attr("class", "periodRightBar")
        .attr("x1", function (d) {return x(d[1] || data_end_year);})
        .attr("y1", y(avg) - 10)
        .attr("x2", function (d) {return x(d[1] || data_end_year);})
        .attr("y2", y(avg) + 10)
        .attr("stroke", function (d) {return !d[1] ? "none" : "red";})
        .attr("stroke-width", "2px")
        .attr("fill", "none");

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

    g.selectAll(".decade")
        .data(decades)
      .enter().append("svg:a")
        .attr("xlink:href", function (d) {
            return ('http://www.google.com/search?q="' + word
                    + '"&tbs=bks:1,cdr:1,cd_min:' + d + ',cd_max:' + (d + 10)
                    + '&num=100&lr=lang_en');
        })
        .attr("target", "_blank")
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
        });
}

function update_word_usage_text(word, data)
{
    var periods = data['periods'],
        avg = data['avg'],
        html = [];
    
    if (periods.length == 0) {
        html.push("No usage data available for this word");
    } else {
        html.push("Avg freq <b>" + format_freq(1.0 / avg) + "</b>. ");
        if (periods.length == 1) {
            if (periods[0][0] == data_start_year && !periods[0][1]) {
                html.push("In consistent use through the whole period covered");
            } else {
                html.push("Most frequently used from ");
                html.push("<span class='usage-period-text'>");
                html.push(periods[0][0]);
                html.push("-");
                html.push(periods[0][1]);
                html.push("</span>");
            }
        } else if (periods.length == 2) {
            html.push("Most frequently used from ");
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
            html.push("Most frequently used from ");
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
                html.push('<a href="javascript:show_word_info(\'' + syn + '\',\'' + corpus + '\')">');
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
    }
    
    $("#definitions").html(html.join(''));
}

// This is used to determine whether another word info request was put in before the
// present one has finished, in which case we must abort.
window.last_request_id = 0;

function show_word_info(word, corpus)
{
    $("#selected-word").text(word);
    $("#word-usage-chart").html("<div id='word-info-loading-message'>Loading...</div>");
    $("#usage-periods-text").html("");
    $("#definitions").html("");
    $("#word-info").css("display", "inline");
    
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
        }
    })
    .fail(function () {
        $("#word-usage-chart").html("<div id='word-info-loading-message'>Error loading word info.</div>");
    });
}

function hide_word_info()
{
    $("#word-info").css("display", "none");
}
