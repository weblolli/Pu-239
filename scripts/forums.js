$(function() {
    $(".poll_select").trilemma({
        max: " . $multi_options . ",
        disablelabels: true
    });
});

$(document).ready(function() {
    $("#tool_open").click(function() {
        $("#tools").slideToggle("slow", function() {});
    });
    $("#toggle_voters").click(function() {
        $("#voters").slideToggle("slow", function() {});
    });
    $("#toggle_voters").click(function() {
        $("#voters").slideToggle("slow", function() {});
    });
});

$("#pm_open").click(function() {
    $("#pm").slideToggle("slow", function() {});
});