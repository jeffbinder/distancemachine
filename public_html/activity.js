function update_activity()
{
    $.get("getactivity.php", function (data) {
        $("#activity-area").html(data);
    })
    .fail(function() {
        $("#activity-area").html("Error getting list of recent searches!");
    });
}

$(function () {
    update_activity();
    window.setInterval(update_activity, 1000);
});
