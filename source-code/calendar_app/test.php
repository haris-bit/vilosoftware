<?
include_once("./global.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <?include("./phpParts/head.php")?>
        <link rel="stylesheet" href="./calender_style.css">
        <link rel="stylesheet" href="./customCss.css">
        <link rel="stylesheet" href="./navigationCss.css">
    </head>
    <body style="background:#f1f4f6">
        <div id="dp"></div>
        <script>
        var dp = new DayPilot.Calendar("dp");

// view
dp.startDate = "2013-03-25";  // or just dp.startDate = "2013-03-25";
dp.viewType = "Week";

// bubble, with async loading
dp.bubble = new DayPilot.Bubble({
    cssClassPrefix: "bubble_default",
    onLoad: function (args) {
        var ev = args.source;
        args.async = true;  // notify manually using .loaded()

        // simulating slow server-side load
        setTimeout(function () {
            args.html = "testing bubble for: <br>" + ev.text();
            args.loaded();
        }, 500);
    }
});

dp.eventDeleteHandling = "Update";

dp.onEventDeleted = function (args) {
    dp.message("Event deleted: " + args.e.text());
};

// event moving
dp.onEventMoved = function (args) {
    dp.message("Moved: " + args.e.text());
};

// event resizing
dp.onEventResized = function (args) {
    dp.message("Resized: " + args.e.text());
};

// event creating
dp.onTimeRangeSelected = function (args) {
    var name = prompt("New event name:", "Event");
    if (!name) return;
    var e = new DayPilot.Event({
        start: args.start,
        end: args.end,
        id: DayPilot.guid(),
        resource: args.resource,
        text: "Event"
    });
    dp.events.add(e);
    dp.clearSelection();
    dp.message("Created");
};

dp.onTimeRangeDoubleClicked = function (args) {
    alert("DoubleClick: start: " + args.start + " end: " + args.end + " resource: " + args.resource);
};


var e = new DayPilot.Event({
    start: new DayPilot.Date("2013-03-25T12:00:00"),
    end: new DayPilot.Date("2013-03-25T12:00:00").addHours(3),
    id: DayPilot.guid(),
    text: "Special event"
});
dp.events.add(e);


dp.onBeforeEventRender = function(args) {
    args.e.contextMenu = new DayPilot.Menu({
        items: [
            { text: "Delete", 
            onClick: (args) => {
                dp.events.remove(args.source);
            }    
            }
        ]
    });
};

dp.init();
        </script>
    </body>
    
</html>
