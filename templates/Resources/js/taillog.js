/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

var offset = 0,
    timer,
    pre = document.getElementById('output'),
    scrolling = true;

// Check every 200msec for fresh data
var interval = 200;

// Function that asks the API for new data
function reloadData() {
    clearTimeout(timer);
    $.getJSON("api/log?" + window.logtype + "&offset=" + offset, function (data) {
        pre.append(data.lines);

        if (scrolling) {
            pre.scrollTop(pre.scrollHeight);
        }

        offset = data.offset;
    });

    timer = setTimeout(reloadData, interval);
}

$(function () {
    // Get offset at first loading of page
    $.getJSON("api/log?" + window.logtype, function (data) {
        offset = data.offset;
    });
    // Trigger function that looks for new data
    reloadData();
});

$("#chk1").click(function () {
    $("#chk2").prop("checked", this.checked);
    scrolling = this.checked;
});
$("#chk2").click(function () {
    $("#chk1").prop("checked", this.checked);
    scrolling = this.checked;
});
