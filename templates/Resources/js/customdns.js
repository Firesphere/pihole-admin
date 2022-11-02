/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

/* global utils:false */

var table;
var token = $("#token").text();

$(function () {
    $("#btnAdd").on("click", addCustom);

    table = $("#customEntriesTable").DataTable({
        ajax: {
            url: "api/customdns/getjson?type="+window.dnstype.toLowerCase(),
            data: {token: token},
            type: "GET",
        },
        columns: [{}, {type: window.dnstype+'-address'}, {orderable: false, searchable: false}],
        columnDefs: [
            {
                targets: 2,
                render: function (data, type, row) {
                    return (
                        '<button type="button" class="btn btn-danger btn-xs deleteCustom" data-origin=\'' +
                        row[0] +
                        "' data-target='" +
                        row[1] +
                        "'>" +
                        '<span class="far fa-trash-alt"></span>' +
                        "</button>"
                    );
                },
            },
            {
                targets: "_all",
                render: $.fn.dataTable.render.text(),
            },
        ],
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"],
        ],
        order: [[0, "asc"]],
        stateSave: true,
        stateDuration: 0,
        stateSaveCallback: function (settings, data) {
            utils.stateSaveCallback("Local"+window.dnstype+"Table", data);
        },
        stateLoadCallback: function () {
            return utils.stateLoadCallback("Local"+window.dnstype+"Table");
        },
        drawCallback: function () {
            $(".deleteCustom").on("click", deleteCustom);
        },
    });

    // Disable autocorrect in the search box
    var input = document.querySelector("input[type=search]");
    input.setAttribute("autocomplete", "off");
    input.setAttribute("autocorrect", "off");
    input.setAttribute("autocapitalize", "off");
    input.setAttribute("spellcheck", false);
});

function addCustom() {
    var origin = utils.escapeHtml($("#origin").val());
    var target = utils.escapeHtml($("#target").val());

    utils.disableAll();
    utils.showAlert("info", "", "Adding custom "+window.dnstype+" record...", "");

    $.ajax({
        url: "api/customdns/add",
        method: "post",
        dataType: "json",
        data: {action: "add", "domain": origin, "target": target, type: window.dnstype, token: token},
        success: function (response) {
            utils.enableAll();
            if (response.success) {
                utils.showAlert(
                    "success",
                    "far fa-check-circle",
                    "Custom "+window.dnstype+" added",
                    origin + ": " + target
                );

                // Clean up field values and reload table data
                $("#origin").val("").focus();
                $("#target").val("");
                table.ajax.reload();
            } else {
                utils.showAlert("error", "fas fa-times", "Failure! Something went wrong", response.message);
            }
        },
        error: function () {
            utils.enableAll();
            utils.showAlert("error", "fas fa-times", "Error while adding custom "+window.dnstype+" record", "");
        },
    });
}

function deleteCustom() {
    var target = $(this).attr("data-target");
    var origin = $(this).attr("data-origin");

    utils.disableAll();
    utils.showAlert("info", "", "Deleting custom "+window.dnstype+" record...", "");

    $.ajax({
        url: "api/customdns/delete",
        method: "post",
        dataType: "json",
        data: {action: "delete", domain: origin, target: target, type: window.dnstype, token: token},
        success: function (response) {
            utils.enableAll();
            if (response.success) {
                utils.showAlert(
                    "success",
                    "far fa-check-circle",
                    "Custom "+window.dnstype+" record deleted",
                    origin + ": " + target
                );
                table.ajax.reload();
            } else {
                utils.showAlert("error", "fas fa-times", "Failure! Something went wrong", response.message);
            }
        },
        error: function (jqXHR, exception) {
            utils.enableAll();
            utils.showAlert(
                "error",
                "fas fa-times",
                "Error while deleting custom "+window.dnstype+" record", "");
            console.log(exception); // eslint-disable-line no-console
        },
    });
}
