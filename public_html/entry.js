function update_progress(progress)
{
    $("#progress-bar").val(progress);
    $("#percentage").text(progress + "%");
}
        
function generate()
{
    update_progress(0);

    $.ajaxSetup ({
	cache: false
    });

    var text = $("#text-input").val(),
        title = $("#title-input").val(),
        corpus = $("#corpus-input").val();
        
    if (!title) {
        title = "Untitled";
    }
    
    $("#input-area button,input,textarea,select").prop("disabled", true);
    $("#status-box").css("display", "inline");
    
    $.ajax({
        type: "POST",
        url: "genid.php",
        async: false,
        dataType: "json",
        data: {"text": text.substring(0, 256)},
        complete: function (response) {
            if (response.statusText == "OK") {
                window.generated_id = response.responseText;
            } else {
                window.generated_id = null;
            }
        }
    });
    
    if (!generated_id) {
        return;
    }
    var id = generated_id;
    
    var data = {"id": id, "text": text, "title": title, "corpus": corpus};
    window.generate_xhr = $.ajax({
        type: "POST",
        url: "gentext.php",
        dataType: "json",
        data: data,
        complete: function (response) {
            if (response.statusText == "OK") {
                // Reset things on this page in case the browser doesn't do it for us.
                $("#input-area button,input,textarea,select").prop("disabled", false);
                $("#status-box").css("display", "none");
                if (window.progress_timer) {
                    clearInterval(window.progress_timer);
                }
                // POST our way to the new page.  We use POST so that we can get decent
                // refresh behavior without displaying a URL that can be copied.
                $("#hidden-form").html("");
                $('<input>').attr({
                  type: 'hidden',
                  name: 'id',
                  value: id
                }).appendTo("#hidden-form");
                $("#hidden-form").submit();
            } else {
		clearInterval(window.progress_timer);
		$("#input-area button,input,textarea,select").prop("disabled", false);
		$("#status-box").css("display", "none");
		$("#error-box").css("display", "inline");
	    }
        }
    });
    
    window.progress_timer = setInterval(function() {
        $.get("getprogress.php", {"id": id}, update_progress, "json");
    }, 500);
}

function cancel_generation()
{
    window.generate_xhr.abort();
    clearInterval(window.progress_timer);
    $("#input-area button,input,textarea,select").prop("disabled", false);
    $("#status-box").css("display", "none");
}

function hide_error_box()
{
    $("#error-box").css("display", "none");
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

window.popup_history = [];
function clear_history()
{
    window.popup_history = [];
    $(".back-button").css("display", "none");
}
function push_history()
{
    if (window.word_info_visible || window.reverse_lookup_box_visible) {
        var scroll_top;
        if (window.word_info_visible) {
            scroll_top = $("#definition-area").scrollTop();
        } else if (window.reverse_lookup_box_visible) {
            scroll_top = $("#reverse-lookup-word-area").scrollTop();
        }
        popup_history.push([window.word_info_visible,
                            window.reverse_lookup_box_visible,
                            window.word_info_selected_word,
                            window.word_info_selected_corpus,
                            window.word_info_selected_dict,
                            scroll_top]);
        $(".back-button").css("display", "inline");
    }
}
function pop_history()
{
    var i = popup_history.length - 1;
    if (i == -1) {
        return;
    }
    if (popup_history[i][0]) {
        show_word_info(popup_history[i][2], popup_history[i][3], popup_history[i][5]);
        hide_reverse_lookup_box();
    } else if (popup_history[i][1]) {
        hide_word_info();
        show_reverse_lookup_box(popup_history[i][2], popup_history[i][4], popup_history[i][5]);
    }
    popup_history.pop();
    if (popup_history.length == 0) {
        $(".back-button").css("display", "none");
    }
}

function lookup_word()
{
    var word = $("#word-lookup").val();
    var corpus = $("#word-lookup-corpus-input").val();
    if (!window.totals) {
        load_totals();
    }
    show_word_info(word, corpus);
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

$(function () {
    $('#title-input').watermark('Enter a title (optional)');
    $('#text-input').watermark('Paste some text here');
    
    $("#word-lookup").keyup(function (e) {
        if (e.which == 13 || e.keyCode == 13) {
	    lookup_word();
        }
    });
    
    $("#corpus-name").text(corpus_names[$("#word-lookup-corpus-input").val()]);
    $("#word-lookup-corpus-input").change(function(e) {
        $("#corpus-name").text(corpus_names[$("#word-lookup-corpus-input").val()]);
    });
    
    update_word_info_height();
    window.onresize = function () {
        update_word_info_height();
    };
    
    $("#definition-area,#reverse-lookup-word-area").dblclick(function (e) {
        var word = get_selection();
        push_history();
        show_word_info(word, $("#word-lookup-corpus-input").val());
    });

    $("#main-area").click(function(e) {
        hide_word_info();
        hide_reverse_lookup_box();
        hide_error_box();
    });
    $(document).keyup(function(e) {});
});
