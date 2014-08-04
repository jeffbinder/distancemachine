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
        region = $("#region-input").val();
        
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
    
    var data = {"id": id, "text": text, "title": title, "region": region};
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

function lookup_word()
{
    var word = $("#word-lookup").val();
    var region = $("#word-lookup-region-input").val();
    if (!window.totals) {
        load_totals();
    }
    show_word_info(word, region);
}

$(function () {
    $('#title-input').watermark('Enter a title (optional)');
    $('#text-input').watermark('Paste some text here');
    
    $("#word-lookup").keyup(function (e) {
        if (e.which == 13 || e.keyCode == 13) {
	    lookup_word();
        }
    });
    
    $("#corpus-name").text(corpus_names[$("#word-lookup-region-input").val()]);
    $("#word-lookup-region-input").change(function(e) {
        $("#corpus-name").text(corpus_names[$("#word-lookup-region-input").val()]);
    });

    $(document).click(function(e) {
        hide_word_info();
        hide_error_box();
    });
    $(document).keyup(function(e) {});
});
