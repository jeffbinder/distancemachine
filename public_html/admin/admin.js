function rerun_text(id)
{
    show_status_box();
    $.get("reruntext.php", {"id": id}, function (data) {
        hide_status_box();
        show_success_box();
    })
    .fail(function() {
        show_error_box();
    });
}

function delete_text(id)
{
    show_status_box();
    $.get("deletetext.php", {"id": id}, function (data) {
        hide_status_box();
        show_success_box();
    })
    .fail(function() {
        show_error_box();
    });
}

function cancel_task(id)
{
    show_status_box();
    $.get("killtask.php", {"id": id}, function (data) {
        hide_status_box();
        show_success_box();
    })
    .fail(function() {
        show_error_box();
    });
}

function update_running_tasks()
{
    $.get("getrunningtasks.php", function (data) {
        $("#running-task-area").html(data);
    })
    .fail(function() {
        show_error_box();
    });
}

function update_recent_tasks()
{
    $.get("getrecenttasks.php", function (data) {
        $("#recent-task-area").html(data);
    })
    .fail(function() {
        show_error_box();
    });
}

function update_saved_texts()
{
    $.get("getsaved.php", function (data) {
        $("#saved-text-area").html(data);
    })
    .fail(function() {
        show_error_box();
    });
}

function update_all()
{
    update_running_tasks();
    update_recent_tasks();
    update_saved_texts();
}

function show_status_box()
{
    $("#status-box").css("display", "inline");
}

function show_success_box()
{
    $("#success-box").css("display", "inline");
}

function show_error_box()
{
    $("#error-box").css("display", "inline");
}

function hide_status_box()
{
    $("#status-box").css("display", "none");
}

function hide_success_box()
{
    $("#success-box").css("display", "none");
}

function hide_error_box()
{
    $("#error-box").css("display", "none");
}

$(function () {
    update_all();
    window.setInterval(update_all, 1000);

    $("#main-area").click(function(e) {
        hide_status_box();
        hide_success_box();
        hide_error_box();
    });
});
