/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

// global object for all WF301 functions
var WF301 = {};

WF301.init = function () {};

WF301.init3rdParty = function ($) {
  // init tabs
  var urlParams = new URLSearchParams(location.search);
  if (urlParams.has("edit_rule")) {
    window.localStorage.setItem("wf301_tabs", "0");
  }

  $("#wf301_tabs")
    .tabs({
      activate: function (event, ui) {
        window.localStorage.setItem(
          "wf301_tabs",
          $("#wf301_tabs").tabs("option", "active")
        );
      },
      create: function (event, ui) {
        if (
          window.location.hash &&
          $('a[href="' + location.hash + '"]').length
        ) {
          $("#wf301_tabs").tabs(
            "option",
            "active",
            $('a[href="' + location.hash + '"]')
              .parent()
              .index()
          );
        }
      },
      active: window.localStorage.getItem("wf301_tabs"),
    })
    .show();

  // init 2nd level of tabs
  $(".wf301-tabs-2nd-level").each(function () {
    $(this).tabs({
      activate: function (event, ui) {
        window.localStorage.setItem(
          $(this).attr("id"),
          $(this).tabs("option", "active")
        );
      },
      active: window.localStorage.getItem($(this).attr("id")),
    });
  });
}; // init3rdParty

WF301.initUI = function ($) {
  // universal button to close UI dialog in any dialog
  $(".wf301-close-ui-dialog").on("click", function (e) {
    e.preventDefault();

    parent = $(this).closest(".ui-dialog-content");
    $(parent).dialog("close");

    return false;
  }); // close-ui-dialog

  // autosize textareas
  $.each($("#wf301_tabs textarea[data-autoresize]"), function () {
    var offset = this.offsetHeight - this.clientHeight;

    var resizeTextarea = function (el) {
      $(el)
        .css("height", "auto")
        .css("height", el.scrollHeight + offset + 2);
    };
    $(this)
      .on("keyup input click", function () {
        resizeTextarea(this);
      })
      .removeAttr("data-autoresize");
  }); // autosize textareas
}; // initUI

WF301.fix_dialog_close = function (event, ui) {
  jQuery(".ui-widget-overlay").bind("click", function () {
    jQuery("#" + event.target.id).dialog("close");
  });
}; // fix_dialog_close

WF301.parse_form_html = function (form_html) {
  var $ = jQuery.noConflict();
  data = {
    action_url: "",
    email_field: "",
    name_field: "",
    extra_data: "",
    method: "",
    email_fields_extra: "",
  };

  html = $.parseHTML(
    '<div id="parse-form-tmp" style="display: none;">' + form_html + "</div>"
  );

  data.action_url = $("form", html).attr("action");
  if ($("form", html).attr("method")) {
    data.method = $("form", html).attr("method").toLowerCase();
  }

  email_fields = $("input[type=email]", html);
  if (email_fields.length == 1) {
    data.email_field = $("input[type=email]", html).attr("name");
  }

  inputs = "";
  $("input", html).each(function (ind, el) {
    type = $(el).attr("type");
    if (
      type == "email" ||
      type == "button" ||
      type == "reset" ||
      type == "submit"
    ) {
      return;
    }

    name = $(el).attr("name");
    name_tmp = name.toLowerCase();

    if (
      !data.email_field &&
      (name_tmp == "email" || name_tmp == "from" || name_tmp == "emailaddress")
    ) {
      data.email_field = name;
    } else if (
      name_tmp == "name" ||
      name_tmp == "fname" ||
      name_tmp == "firstname"
    ) {
      data.name_field = name;
    } else {
      data.email_fields_extra += name + ", ";
      data.extra_data += name + "=" + $(el).attr("value") + "&";
    }
  }); // foreach

  data.email_fields_extra = data.email_fields_extra.replace(/\, $/g, "");
  data.extra_data = data.extra_data.replace(/&$/g, "");

  return data;
}; // parse_form_html

var tagElement;
var selectedTagElement;
var xhr;
var table_redirects;

function loadTags(tags) {
  tagElement.clear();
  tagElement.load(function (callback) {
    xhr && xhr.abort();
    xhr = jQuery
      .post({
        url: ajaxurl,
        data: {
          action: "wf301_run_tool",
          _ajax_nonce: wf301_vars.run_tool_nonce,
          tool: "redirect_tags",
        },
      })
      .done(function (response) {
        callback(JSON.parse(response));
      })
      .fail(function () {
        tags.forEach((tag) => {
          tagElement.addOption({
            text: tag,
            value: tag,
          });
        });
        callback();
      })
      .always(function () {
        tags.forEach((tag) => {
          tagElement.addItem(tag);
        });
      });
  });
}

jQuery(document).ready(function ($) {
  var $selectElementControlInit = $("#redirect_tags").selectize({
    plugins: ["remove_button"],
    delimiter: ",",
    persist: false,
    createOnBlur: true,
    create: function (input) {
      return {
        value: input,
        text: input,
      };
    },
  });

  tagElement = $selectElementControlInit[0].selectize;
  loadTags([]);

  old_settings = $("#wf301_form *").not(".skip-save").serialize();

  WF301.initUI($);
  WF301.init3rdParty($);
  // open dialog to configure universal autoresponder
  $("#wf301_tabs").on("click", ".configure-autoresponder", function (e) {
    e.preventDefault();

    $("#autoresponder-config-dialog")
      .dialog("option", "title", "Auto Configure Universal Autoresponder")
      .dialog("open");

    return false;
  }); // auto_configure_autoresponder

  $(".settings_page_301redirects").on("click", "#deactivate-license", function (
    e
  ) {
    e.preventDefault();
    button = this;

    wf_301_licensing_deactivate_licence_ajax(
      "wf301",
      $("#license-key").val(),
      button
    );
    return;
  });

  // validate license
  $(".settings_page_301redirects").on("click", "#save-license", function (
    e,
    deactivate
  ) {
    e.preventDefault();
    button = this;
    safe_refresh = true;
    block = block_ui($(button).data("text-wait"));

    wf_301_licensing_verify_licence_ajax("wf301", $("#license-key").val(), button);

    return false;
  }); // validate license

  $("#wf301_keyless_activation").on("click", function (e) {
    e.preventDefault();

    button = this;
    safe_refresh = true;
    block = block_ui($(button).data("text-wait"));

    wf_301_licensing_verify_licence_ajax("wf301", "keyless", button);
    return;
  });

  $("#wf301_deactivate_license").on("click", function (e) {
    e.preventDefault();

    button = this;
    safe_refresh = true;

    wf_301_licensing_deactivate_licence_ajax(
      "wf301",
      $("#license-key").val(),
      button
    );
    return;
  });

  // fix for enter press in license field
  $("#license-key").on("keypress", function (e) {
    if (e.which == 13) {
      e.preventDefault();
      $("#save-license").trigger("click");
      return false;
    }
  }); // if enter on license key field

  $(".wf301_recreate_tables").on("click", function (e) {
    e.preventDefault();
    uid = $(this).data("ss-uid");
    button = $(this);

    wf301_swal
      .fire({
        title: $(button).data("title"),
        type: "question",
        text: $(button).data("text"),
        heightAuto: false,
        showCancelButton: true,
        focusConfirm: false,
        confirmButtonText: $(button).data("btn-confirm"),
        cancelButtonText: wf301_vars.cancel_button,
        width: 600,
      })
      .then((result) => {
        if (typeof result.value != "undefined") {
          block = block_ui($(button).data("msg-wait"));
          $.post({
            url: ajaxurl,
            data: {
              action: "wf301_run_tool",
              _ajax_nonce: wf301_vars.run_tool_nonce,
              tool: "recreate_tables",
            },
          })
            .always(function (response) {
              wf301_swal.close();
            })
            .done(function (response) {
              if (response.success) {
                window.location =
                  "options-general.php?page=301redirects#wf301_redirect";
                window.location.reload();
              } else {
                wf301_swal.fire({
                  type: "error",
                  title: wf301_vars.documented_error + " " + response.data,
                });
              }
            })
            .fail(function (response) {
              wf301_swal.fire({
                type: "error",
                title: wf301_vars.undocumented_error,
              });
            });
        } // if confirmed
      });
  });

  $(".wf301_empty_log_404").on("click", function (e) {
    e.preventDefault();
    button = $(this);

    wf301_swal
      .fire({
        title: $(button).data("title"),
        type: "question",
        text: $(button).data("text"),
        heightAuto: false,
        showCancelButton: true,
        focusConfirm: false,
        confirmButtonText: $(button).data("btn-confirm"),
        cancelButtonText: wf301_vars.cancel_button,
        width: 600,
      })
      .then((result) => {
        if (typeof result.value != "undefined") {
          block = block_ui($(button).data("msg-wait"));
          $.post({
            url: ajaxurl,
            data: {
              action: "wf301_run_tool",
              _ajax_nonce: wf301_vars.run_tool_nonce,
              tool: "empty_log_404",
            },
          })
            .always(function (response) {
              wf301_swal.close();
            })
            .done(function (response) {
              if (response.success) {
                console.log('success');  
                window.location =
                  "options-general.php?page=301redirects#wf301_settings";
                window.location.reload();
              } else {
                
                wf301_swal.fire({
                  type: "error",
                  title: wf301_vars.documented_error + " " + response.data,
                });
              }
            })
            .fail(function (response) {
              wf301_swal.fire({
                type: "error",
                title: wf301_vars.undocumented_error,
              });
            });
        } // if confirmed
      });
  });

  $(".wf301_empty_log_redirect").on("click", function (e) {
    e.preventDefault();
    button = $(this);

    wf301_swal
      .fire({
        title: $(button).data("title"),
        type: "question",
        text: $(button).data("text"),
        heightAuto: false,
        showCancelButton: true,
        focusConfirm: false,
        confirmButtonText: $(button).data("btn-confirm"),
        cancelButtonText: wf301_vars.cancel_button,
        width: 600,
      })
      .then((result) => {
        if (typeof result.value != "undefined") {
          block = block_ui($(button).data("msg-wait"));
          $.post({
            url: ajaxurl,
            data: {
              action: "wf301_run_tool",
              _ajax_nonce: wf301_vars.run_tool_nonce,
              tool: "empty_log_redirect",
            },
          })
            .always(function (response) {
              wf301_swal.close();
            })
            .done(function (response) {
              if (response.success) {
                window.location =
                  "options-general.php?page=301redirects#wf301_settings";
                window.location.reload();
              } else {
                wf301_swal.fire({
                  type: "error",
                  title: wf301_vars.documented_error + " " + response.data,
                });
              }
            })
            .fail(function (response) {
              wf301_swal.fire({
                type: "error",
                title: wf301_vars.undocumented_error,
              });
            });
        } // if confirmed
      });
  });

  

  // open Help Scout Beacon
  $(".settings_page_301redirects").on("click", ".open-beacon", function (e) {
    e.preventDefault();

    Beacon("open");

    return false;
  });

  // init Help Scout beacon
  if (wf301_vars.rebranded == false && wf301_vars.whitelabel == true) {
    Beacon("config", {
      enableFabAnimation: false,
      display: {},
      contactForm: {},
      labels: {},
    });
    Beacon("prefill", {
      name: "\n\n\n" + wf301_vars.support_name,
      subject: "WP 301 Redirects PRO in-plugin support",
      email: "",
      text: "\n\n\n" + wf301_vars.support_text,
    });
    Beacon("init", "8a9edebc-337b-4463-9db0-10754b1550eb");
  }

  // open HS docs and show article based on tool name
  $(".documentation-link").on("click", function (e) {
    e.preventDefault();

    search = $(this).data("tool-title");
    Beacon("search", search);
    Beacon("open");

    return false;
  });

  $("#wf301-refresh-templates").on("click", function (e) {
    e.preventDefault();
    wf301_vars.refresh_templates = false;
    $(this).addClass("loading");
    wf301_refresh_templates();
  });

  function wf301_stripslashes(str) {
    str = str.replace(/\\'/g, "'");
    str = str.replace(/\\"/g, '"');
    str = str.replace(/\\\\/g, "\\");
    return str;
  }

  function edit_page() {
    var page_id = jQuery("#page_404_dummy select").val();

    // if 404.php is selected redirect to theme editor
    if (page_id == 0) {
      window.location = "theme-editor.php";
    } else {
      window.location = "post.php?post=" + page_id + "&action=edit";
    }

    return false;
  }

  jQuery("#edit_404_page").bind("click", function () {
    edit_page();
  });

  // fix for enter press in support email
  $("#support_email").on("keypress", function (e) {
    if (e.which == 13) {
      e.preventDefault();
      $("#wf301-send-support-message").trigger("click");
      return false;
    }
  }); // if enter on support email

  // send support message
  $("#wf301-send-support-message").on("click", function (e) {
    e.preventDefault();
    button = $(this);

    if (
      $("#support_email").val().length < 5 ||
      $("#support_email").is(":invalid")
    ) {
      alert("We need your email address, don't you think?");
      $("#support_email").select().focus();
      return false;
    }

    if ($("#support_message").val().length < 15) {
      alert("An empty message won't do anybody any good.");
      $("#support_message").select().focus();
      return false;
    }

    button.addClass("loading");
    $.post(
      ajaxurl,
      {
        support_email: $("#support_email").val(),
        support_message: $("#support_message").val(),
        support_info: $("#support_info:checked").val(),
        _ajax_nonce: wf301_vars.nonce_submit_support_message,
        action: "wf301_submit_support_message",
      },
      function (response) {
        if (response.success) {
          alert("Message sent! Our agents will get back to you ASAP.");
        } else {
          alert(response.data);
        }
      }
    )
      .fail(function () {
        alert("Something is not right. Please reload the page and try again");
      })
      .always(function () {
        button.removeClass("loading");
      });

    return false;
  });

  // helper for linking anchors in different tabs
  $(".settings_page_301redirects").on("click", ".change_tab", function (e) {
    e.preventDefault();

    tab_name = "wf301_" + $(this).data("tab");
    tab_id = $(
      '#wf301_tabs ul.ui-tabs-nav li[aria-controls="' + tab_name + '"]'
    )
      .attr("aria-labelledby")
      .replace("ui-id-", "");
    if (!tab_id) {
      console.log("Invalid tab name - " + tab_name);
      return false;
    }

    $("#wf301_tabs").tabs("option", "active", tab_id - 1);

    if ($(this).data("tab2")) {
      tab_name2 = "tab_" + $(this).data("tab2");
      tmp = $(
        "#" + tab_name + ' ul.ui-tabs-nav li[aria-controls="' + tab_name2 + '"]'
      );
      tab_id = $("#" + tab_name + " ul.ui-tabs-nav li").index(tmp);
      if (tab_id == -1) {
        console.log("Invalid secondary tab name - " + tab_name2);
        return false;
      }

      $("#" + tab_name + " .wf301-tabs-2nd-level").tabs(
        "option",
        "active",
        tab_id
      );
    } // if secondary tab

    // get the link anchor and scroll to it
    target = this.href.split("#")[1];

    return false;
  }); // change tab

  // helper for linking anchors in different tabs
  $(".settings_page_301redirects").on("click", ".confirm_action", function (e) {
    message = $(this).data("confirm");

    if (!message || confirm(message)) {
      return true;
    } else {
      e.preventDefault();
      return false;
    }
  }); // confirm action before link click

  $("#submit_redirect_rule").on("click", function (e) {
    e.preventDefault();
    $(this).addClass("loading");
    $("#redirect-rule-dialog").dialog("close");
    block = block_ui($($(this)).data("msg-wait"));
    $.post(
      ajaxurl,
      {
        action: "wf301_run_tool",
        _ajax_nonce: wf301_vars.run_tool_nonce,
        tool: "submit_redirect_rule",
        redirect_id: $("#redirect_id").val(),
        redirect_enabled: $("#redirect_enabled").is(":checked"),
        redirect_url_from: $("#redirect_url_from").val(),
        redirect_url_to: $("#redirect_url_to").val(),
        redirect_query: $("#redirect_query").children("option:selected").val(),
        redirect_case_insensitive: $("#redirect_case_insensitive").is(
          ":checked"
        ),
        redirect_regex: $("#redirect_regex").is(
            ":checked"
        ),
        redirect_type: $("#redirect_type").children("option:selected").val(),
        redirect_position: $("#redirect_position").val(),
        redirect_tags: $("#redirect_tags").val(),
      },
      function (response) {
        if (response.success) {
          wf301_swal.close();
          $(".dataTables_empty").closest("tr").remove();
          if ($("#wf301-redirects-table tr#" + response.data.id).length == 1) {
            $("#wf301-redirects-table tr#" + response.data.id).replaceWith(
              response.data.row_html
            );
          } else {
            $("#wf301-redirects-table").prepend(response.data.row_html);
            $("#wf301-redirects-table tbody")
              .find("tr")
              .first()
              .hide()
              .show(500);
          }
        } else {
          alert(response.data);
        }
        $("#submit_redirect_rule").removeClass("loading");
      }
    ).fail(function () {
      alert("Undocumented error. Please reload the page and try again.");
    });
  }); // confirm action before link click

  $("#wf301-404-log-table").one("preInit.dt", function () {

    $("#wf301-404-log-table_filter").append(
        '<div class="wf301-group-wrapper"><select data-logs="404" class="wf301-log-group"><option value="ng">No Grouping</option><option value="ip">Group by IP</option><option value="url">Group by URL</option></select></div>'
    );

    $("#wf301-404-log-table_filter").append(
      '<div id="wf301-404-log-toggle-chart" title="' +
        (window.localStorage.getItem("wf301_404_chart") == "disabled"
          ? "Show"
          : "Hide") +
        ' 404 Chart" class="tooltip wf301-404-log-toggle-chart wf301-404-log-toggle-chart-' +
        window.localStorage.getItem("wf301_404_chart") +
        '"><i class="wf301-icon wf301-graph"></i></a>'
    );
    $("#wf301-404-log-table_filter").append(
      '<div id="wf301-404-log-toggle-stats" title="' +
        (window.localStorage.getItem("wf301_404_stats") == "disabled"
          ? "Show"
          : "Hide") +
        ' 404 Stats" class="tooltip wf301-404-log-toggle-stats wf301-404-log-toggle-stats-' +
        window.localStorage.getItem("wf301_404_stats") +
        '"><i class="wf301-icon wf301-pie"></i></a>'
    );

    $('.tooltip').tooltipster();
  });

  $("#wf301_tabs").on("click", ".wf301-404-log-toggle-chart", function () {
    if ($(this).hasClass("wf301-404-log-toggle-chart-enabled")) {
      $("#wf301_404_log .wf301-chart-placeholder").fadeOut(300);
      $(".wf301-chart-404").hide(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("404");
          },
        },
        500
      );
      $(this).removeClass("wf301-404-log-toggle-chart-enabled");
      $(this).addClass("wf301-404-log-toggle-chart-disabled");
      $(this).attr("title", "Show 404 Chart");
      window.localStorage.setItem("wf301_404_chart", "disabled");
    } else {
      $(this).removeClass("wf301-404-log-toggle-chart-disabled");
      $(this).addClass("wf301-404-log-toggle-chart-enabled");
      $(this).attr("title", "Hide 404 Chart");
      window.localStorage.setItem("wf301_404_chart", "enabled");
      $(".wf301-chart-404").show();
      create_404_chart();
      $(".wf301-chart-404").hide();
      $("#wf301_404_log .wf301-chart-placeholder").fadeOut(300);
      $(".wf301-chart-404").show(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("404");
          },
        },
        500
      );
    }

    $(this).tooltipster('destroy');
    $('.tooltip').tooltipster();
  });

  $("#wf301_tabs").on("click", ".wf301-404-log-toggle-stats", function () {
    if ($(this).hasClass("wf301-404-log-toggle-stats-enabled")) {
      $("#wf301_404_log .wf301-chart-placeholder").fadeOut(300);
      $(".wf301-stats-404").hide(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("404");
          },
        },
        500
      );
      $(this).removeClass("wf301-404-log-toggle-stats-enabled");
      $(this).addClass("wf301-404-log-toggle-stats-disabled");
      $(this).attr("title", "Show 404 Stats");
      window.localStorage.setItem("wf301_404_stats", "disabled");
    } else {
      $(this).removeClass("wf301-404-log-toggle-stats-disabled");
      $(this).addClass("wf301-404-log-toggle-stats-enabled");
      $(this).attr("title", "Hide 404 Stats");
      create_404_device_chart();
      window.localStorage.setItem("wf301_404_stats", "enabled");
      $(".wf301-stats-404").show();
      $(".wf301-stats-404").hide();
      $("#wf301_404_log .wf301-chart-placeholder").fadeOut(300);
      $(".wf301-stats-404").show(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("404");
          },
        },
        500
      );
    }

    $(this).tooltipster('destroy');
    $('.tooltip').tooltipster();
  });

  table_logs_404 = $("#wf301-404-log-table").dataTable({
    bProcessing: true,
    bServerSide: true,
    bLengthChange: 1,
    bProcessing: true,
    bStateSave: 0,
    bAutoWidth: 0,
    columnDefs: [
      {
        targets: [5],
        className: "dt-body-right",
        orderable: false,
      },
    ],
    "drawCallback": function(){
        $('.tooltip').tooltipster();
    },
    "initComplete": function(){
        $('.tooltip').tooltipster();
    },
    language: {
      loadingRecords: "&nbsp;",
      processing:
        '<div class="wf301-datatables-loader"><img width="64" src="' +
        wf301_vars.icon_url +
        '" /></div>',
      emptyTable: "No log entries for 404 events exist yet",
      searchPlaceholder: "Type something to search ...",
      search: "",
    },
    order: [[0, "desc"]],
    iDisplayLength: 25,
    sPaginationType: "full_numbers",
    dom: '<"settings_page_301redirects_top"f>rt<"bottom"lp><"clear">',
    sAjaxSource:
      ajaxurl +
      "?action=wf301_run_tool&tool=404_logs&_ajax_nonce=" +
      wf301_vars.run_tool_nonce,
  });

  $("#wf301-redirect-log-table").one("preInit.dt", function () {
    $("#wf301-redirect-log-table_filter").append(
      '<div class="wf301-group-wrapper"><select data-logs="redirect" class="wf301-log-group"><option value="ng">No Grouping</option><option value="ip">Group by IP</option><option value="url">Group by URL</option></select></div>'
    );

    $("#wf301-redirect-log-table_filter").append(
      '<div id="wf301-redirect-log-toggle-chart" title="' +
        (window.localStorage.getItem("wf301_redirect_chart") == "disabled"
          ? "Show"
          : "Hide") +
        ' Redirect Chart" class="tooltip wf301-redirect-log-toggle-chart wf301-redirect-log-toggle-chart-' +
        window.localStorage.getItem("wf301_redirect_chart") +
        '"><i class="wf301-icon wf301-graph"></i></a>'
    );

    $("#wf301-redirect-log-table_filter").append(
      '<div id="wf301-redirect-log-toggle-stats" title="' +
        (window.localStorage.getItem("wf301_redirect_stats") == "disabled"
          ? "Show"
          : "Hide") +
        ' Redirect Stats" class="tooltip wf301-redirect-log-toggle-stats wf301-redirect-log-toggle-stats-' +
        window.localStorage.getItem("wf301_redirect_stats") +
        '"><i class="wf301-icon wf301-pie"></i></a>'
    );

    $('.tooltip').tooltipster();
  });

  function center_redirect_placeholder(type) {
    var placeholder_top = 0;

    if ($("#wf301_" + type + "_log .wf301-chart-" + type + "").is(":visible")) {
      placeholder_top = placeholder_top + 70;
    }
    if ($("#wf301_" + type + "_log .wf301-stats-" + type + "").is(":visible")) {
      placeholder_top = placeholder_top + 120;
    }

    $("#wf301_" + type + "_log .wf301-chart-placeholder").css(
      "top",
      placeholder_top + "px"
    );
    if (placeholder_top == 0) {
      $("#wf301_" + type + "_log .wf301-chart-placeholder").hide();
    } else {
      $("#wf301_" + type + "_log .wf301-chart-placeholder").fadeIn(300);
      $("#wf301_" + type + "_log .wf301-chart-placeholder").css(
        "top",
        placeholder_top + "px"
      );
    }
  }

  $("#wf301_tabs").on("click", ".wf301-redirect-log-toggle-chart", function () {
    if ($(this).hasClass("wf301-redirect-log-toggle-chart-enabled")) {
      $(".wf301-chart-redirect").hide(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("redirect");
          },
        },
        500
      );
      $(this).removeClass("wf301-redirect-log-toggle-chart-enabled");
      $(this).addClass("wf301-redirect-log-toggle-chart-disabled");
      $(this).attr("title", "Show Redirect Chart");
      $("#wf301_redirect_log .wf301-chart-placeholder").fadeOut(300);
      window.localStorage.setItem("wf301_redirect_chart", "disabled");
    } else {
      $(this).removeClass("wf301-redirect-log-toggle-chart-disabled");
      $(this).addClass("wf301-redirect-log-toggle-chart-enabled");
      $(this).attr("title", "Hide Redirect Chart");
      window.localStorage.setItem("wf301_redirect_chart", "enabled");
      $(".wf301-chart-redirect").show();
      create_redirect_chart();
      $("#wf301_redirect_log .wf301-chart-placeholder").fadeOut(300);
      $(".wf301-chart-redirect").hide();
      $(".wf301-chart-redirect").show(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("redirect");
          },
        },
        500
      );
    }

    $(this).tooltipster('destroy');
    $('.tooltip').tooltipster();
  });

  $("#wf301_tabs").on("click", ".wf301-redirect-log-toggle-stats", function () {
    if ($(this).hasClass("wf301-redirect-log-toggle-stats-enabled")) {
      $("#wf301_redirect_log .wf301-chart-placeholder").fadeOut(300);
      $(".wf301-stats-redirect").hide(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("redirect");
          },
        },
        500
      );
      $(this).removeClass("wf301-redirect-log-toggle-stats-enabled");
      $(this).addClass("wf301-redirect-log-toggle-stats-disabled");
      $(this).attr("title", "Show Redirect Stats");
      window.localStorage.setItem("wf301_redirect_stats", "disabled");
    } else {
      $(this).removeClass("wf301-redirect-log-toggle-stats-disabled");
      $(this).addClass("wf301-redirect-log-toggle-stats-enabled");
      $(this).attr("title", "Hide Redirect Stats");
      window.localStorage.setItem("wf301_redirect_stats", "enabled");
      $(".wf301-stats-redirect").show();
      $(".wf301-stats-redirect").hide();
      $("#wf301_redirect_log .wf301-chart-placeholder").fadeOut(300);
      $(".wf301-stats-redirect").show(
        "blind",
        {
          direction: "vertical",
          complete: function () {
            center_redirect_placeholder("redirect");
          },
        },
        500
      );
    }
    $(this).tooltipster('destroy');
    $('.tooltip').tooltipster();
  });

  $("#wf301-redirect-log-table-group-ip-wrapper").hide();
  $("#wf301-redirect-log-table-group-url-wrapper").hide();

  $("#wf301-redirect-log-table-group-ip").one("preInit.dt", function () {
      $("#wf301-redirect-log-table-group-ip_filter").append(
        '<div class="wf301-group-wrapper"><select data-logs="redirect" class="wf301-log-group"><option value="ng">No Grouping</option><option value="ip" selected="selected">Group by IP</option><option value="url">Group by URL</option></select></div>'
      );
  
      $("#wf301-redirect-log-table-group-ip_filter").append(
        '<div id="wf301-redirect-log-toggle-chart" title="' +
          (window.localStorage.getItem("wf301_redirect_chart") == "disabled"
            ? "Show"
            : "Hide") +
          ' Redirect Chart" class="tooltip wf301-redirect-log-toggle-chart wf301-redirect-log-toggle-chart-' +
          window.localStorage.getItem("wf301_redirect_chart") +
          '"><i class="wf301-icon wf301-graph"></i></a>'
      );
  
      $("#wf301-redirect-log-table-group-ip_filter").append(
        '<div id="wf301-redirect-log-toggle-stats" title="' +
          (window.localStorage.getItem("wf301_redirect_stats") == "disabled"
            ? "Show"
            : "Hide") +
          ' Redirect Stats" class="tooltip wf301-redirect-log-toggle-stats wf301-redirect-log-toggle-stats-' +
          window.localStorage.getItem("wf301_redirect_stats") +
          '"><i class="wf301-icon wf301-pie"></i></a>'
      );
  
      $('.tooltip').tooltipster();
  });

  $("#wf301-redirect-log-table-group-url").one("preInit.dt", function () {
      $("#wf301-redirect-log-table-group-url_filter").append(
        '<div class="wf301-group-wrapper"><select data-logs="redirect" class="wf301-log-group"><option value="ng">No Grouping</option><option value="ip">Group by IP</option><option value="url" selected="selected">Group by URL</option></select></div>'
      );
  
      $("#wf301-redirect-log-table-group-url_filter").append(
        '<div id="wf301-redirect-log-toggle-chart" title="' +
          (window.localStorage.getItem("wf301_redirect_chart") == "disabled"
            ? "Show"
            : "Hide") +
          ' Redirect Chart" class="tooltip wf301-redirect-log-toggle-chart wf301-redirect-log-toggle-chart-' +
          window.localStorage.getItem("wf301_redirect_chart") +
          '"><i class="wf301-icon wf301-graph"></i></a>'
      );
  
      $("#wf301-redirect-log-table-group-url_filter").append(
        '<div id="wf301-redirect-log-toggle-stats" title="' +
          (window.localStorage.getItem("wf301_redirect_stats") == "disabled"
            ? "Show"
            : "Hide") +
          ' Redirect Stats" class="tooltip wf301-redirect-log-toggle-stats wf301-redirect-log-toggle-stats-' +
          window.localStorage.getItem("wf301_redirect_stats") +
          '"><i class="wf301-icon wf301-pie"></i></a>'
      );
  
      $('.tooltip').tooltipster();
  });

  $("#wf301-404-log-table-group-ip-wrapper").hide();
  $("#wf301-404-log-table-group-url-wrapper").hide();

  $("#wf301-404-log-table-group-ip").one("preInit.dt", function () {
    $("#wf301-404-log-table-group-ip_filter").append(
        '<div class="wf301-group-wrapper"><select data-logs="404" class="wf301-log-group"><option value="ng">No Grouping</option><option value="ip" selected="selected">Group by IP</option><option value="url">Group by URL</option></select></div>'
      );
  
      $("#wf301-404-log-table-group-ip_filter").append(
        '<div id="wf301-404-log-toggle-chart" title="' +
          (window.localStorage.getItem("wf301_404_chart") == "disabled"
            ? "Show"
            : "Hide") +
          ' 404 Chart" class="tooltip wf301-404-log-toggle-chart wf301-404-log-toggle-chart-' +
          window.localStorage.getItem("wf301_404_chart") +
          '"><i class="wf301-icon wf301-graph"></i></a>'
      );
  
      $("#wf301-404-log-table-group-ip_filter").append(
        '<div id="wf301-404-log-toggle-stats" title="' +
          (window.localStorage.getItem("wf301_404_stats") == "disabled"
            ? "Show"
            : "Hide") +
          ' 404 Stats" class="tooltip wf301-404-log-toggle-stats wf301-404-log-toggle-stats-' +
          window.localStorage.getItem("wf301_404_stats") +
          '"><i class="wf301-icon wf301-pie"></i></a>'
      );
  
      $('.tooltip').tooltipster();
  });

  $("#wf301-404-log-table-group-url").one("preInit.dt", function () {
    $("#wf301-404-log-table-group-url_filter").append(
        '<div class="wf301-group-wrapper"><select data-logs="404" class="wf301-log-group"><option value="ng">No Grouping</option><option value="ip" >Group by IP</option><option value="url" selected="selected">Group by URL</option></select></div>'
      );
  
      $("#wf301-404-log-table-group-url_filter").append(
        '<div id="wf301-404-log-toggle-chart" title="' +
          (window.localStorage.getItem("wf301_404_chart") == "disabled"
            ? "Show"
            : "Hide") +
          ' 404 Chart" class="tooltip wf301-404-log-toggle-chart wf301-404-log-toggle-chart-' +
          window.localStorage.getItem("wf301_404_chart") +
          '"><i class="wf301-icon wf301-graph"></i></a>'
      );
  
      $("#wf301-404-log-table-group-url_filter").append(
        '<div id="wf301-404-log-toggle-stats" title="' +
          (window.localStorage.getItem("wf301_404_stats") == "disabled"
            ? "Show"
            : "Hide") +
          ' 404 Stats" class="tooltip wf301-404-log-toggle-stats wf301-404-log-toggle-stats-' +
          window.localStorage.getItem("wf301_404_stats") +
          '"><i class="wf301-icon wf301-pie"></i></a>'
      );
  
      $('.tooltip').tooltipster();
  });

  $("#wf301_tabs").on("change", ".wf301-log-group", function () {
    var logs = $(this).data("logs");
    var group = $(this).parent().children("select").val();

    window.localStorage.setItem("wf301-" + logs, group);

    $("#wf301-" + logs + "-log-table-wrapper").hide();
    $("#wf301-" + logs + "-log-table-group-ip-wrapper").hide();
    $("#wf301-" + logs + "-log-table-group-url-wrapper").hide();

    if (group == "ip" || group == "url") {
      $("#wf301-" + logs + "-log-table-group-" + group + "-wrapper").show();
      if (
        !$.fn.DataTable.isDataTable(
          "#wf301-" + logs + "-log-table-group-" + group
        )
      ) {
        $("#wf301-" + logs + "-log-table-group-" + group).dataTable({
          bProcessing: true,
          bServerSide: true,
          bLengthChange: 1,
          bProcessing: true,
          bStateSave: 0,
          bAutoWidth: 0,
          aoColumnDefs: [
            {
              targets: [2],
              className: "dt-body-center",
            },
            {
              targets: [5],
              className: "dt-body-right",
              orderable: false,
            },
            {
              asSorting: ["asc"],
              aTargets: [0],
            },
          ],
          language: {
            loadingRecords: "&nbsp;",
            processing:
              '<div class="wf301-datatables-loader"><img width="64" src="' +
              wf301_vars.icon_url +
              '" /></div>',
            searchPlaceholder: "Type something to search ...",
            search: "",
          },
          order: [[2, "desc"]],
          iDisplayLength: 25,
          sPaginationType: "full_numbers",
          dom: '<"settings_page_301redirects_top"f>rt<"bottom"lp><"clear">',
          sAjaxSource:
            ajaxurl +
            "?action=wf301_run_tool&tool=get_group_logs&logs=" +
            logs +
            "&group=" +
            group +
            "&_ajax_nonce=" +
            wf301_vars.run_tool_nonce,
        });
      }
    } else {
      $("#wf301-" + logs + "-log-table-wrapper").show();
    }
  });

  $(".settings_page_301redirects").on("click", ".delete_404_entry", function (
    e
  ) {
    e.preventDefault();
    uid = $(this).data("ss-uid");
    button = $(this);

    wf301_swal
      .fire({
        title: $(button).data("title"),
        type: "question",
        text: $(button).data("text"),
        heightAuto: false,
        showCancelButton: true,
        focusConfirm: false,
        confirmButtonText: $(button).data("btn-confirm"),
        cancelButtonText: wf301_vars.cancel_button,
        width: 600,
      })
      .then((result) => {
        if (typeof result.value != "undefined") {
          block = block_ui($(button).data("msg-wait"));
          $.post({
            url: ajaxurl,
            data: {
              action: "wf301_run_tool",
              _ajax_nonce: wf301_vars.run_tool_nonce,
              tool: "delete_404_log",
              log_id: $(button).data("log-id"),
            },
          })
            .always(function (response) {
              wf301_swal.close();
            })
            .done(function (response) {
              if (response.success) {
                $("#wf301-404-log-table tr#" + response.data.id).remove();
                wf301_swal.fire({
                  type: "success",
                  heightAuto: false,
                  title: $(button).data("msg-success"),
                });
              } else {
                wf301_swal.fire({
                  type: "error",
                  heightAuto: false,
                  title: wf301_vars.documented_error + " " + data.data,
                });
              }
            })
            .fail(function (response) {
              wf301_swal.fire({
                type: "error",
                heightAuto: false,
                title: wf301_vars.undocumented_error,
              });
            });
        } // if confirmed
      });
  });

  $(".settings_page_301redirects").on(
    "click",
    ".delete_redirect_entry",
    function (e) {
      e.preventDefault();
      uid = $(this).data("ss-uid");
      button = $(this);

      wf301_swal
        .fire({
          title: $(button).data("title"),
          type: "question",
          text: $(button).data("text"),
          heightAuto: false,
          showCancelButton: true,
          focusConfirm: false,
          confirmButtonText: $(button).data("btn-confirm"),
          cancelButtonText: wf301_vars.cancel_button,
          width: 600,
        })
        .then((result) => {
          if (typeof result.value != "undefined") {
            block = block_ui($(button).data("msg-wait"));
            $.post({
              url: ajaxurl,
              data: {
                action: "wf301_run_tool",
                _ajax_nonce: wf301_vars.run_tool_nonce,
                tool: "delete_redirect_log",
                log_id: $(button).data("log-id"),
              },
            })
              .always(function (response) {
                wf301_swal.close();
              })
              .done(function (response) {
                if (response.success) {
                  $(
                    "#wf301-redirect-log-table tr#" + response.data.id
                  ).remove();
                  wf301_swal.fire({
                    type: "success",
                    heightAuto: false,
                    title: $(button).data("msg-success"),
                  });
                } else {
                  wf301_swal.fire({
                    type: "error",
                    heightAuto: false,
                    title: wf301_vars.documented_error + " " + data.data,
                  });
                }
              })
              .fail(function (response) {
                wf301_swal.fire({
                  type: "error",
                  heightAuto: false,
                  title: wf301_vars.undocumented_error,
                });
              });
          } // if confirmed
        });
    }
  );

  table_logs_redirect = $("#wf301-redirect-log-table").dataTable({
    bProcessing: true,
    bServerSide: true,
    bLengthChange: 1,
    bProcessing: true,
    bStateSave: 0,
    bAutoWidth: 0,
    aoColumnDefs: [
      {
        targets: [5],
        className: "dt-body-right",
        orderable: false,
      },
      {
        asSorting: ["asc"],
        aTargets: [0],
      },
    ],
    "drawCallback": function(){
        $('.tooltip').tooltipster();
    },
    "initComplete": function(){
        $('.tooltip').tooltipster();
    },
    language: {
      loadingRecords: "&nbsp;",
      processing:
        '<div class="wf301-datatables-loader"><img width="64" src="' +
        wf301_vars.icon_url +
        '" /></div>',
      emptyTable: "No log entries for redirects exist yet",
      searchPlaceholder: "Type something to search ...",
      search: "",
      lengthMenu: "Show _MENU_ entries",
    },
    order: [[0, "desc"]],
    iDisplayLength: 25,
    sPaginationType: "full_numbers",
    dom: '<"settings_page_301redirects_top"f>rt<"bottom"lp><"clear">',
    sAjaxSource:
      ajaxurl +
      "?action=wf301_run_tool&tool=redirect_logs&_ajax_nonce=" +
      wf301_vars.run_tool_nonce,
  });

  $("#wf301-redirects-table").one("preInit.dt", function () {
    $("#wf301-redirects-table_filter").append(
      '<a class="button button-primary add-redirect-rule">Add New Redirect Rule <i class="wf301-icon wf301-redirect"></i></a><br/><div id="tag_area" style="display: none"><label for="selected-tags">Tags:</label><input type="text" id="selected-tags"></div>'
    );

  });

  
  table_redirects = $("#wf301-redirects-table").dataTable({
    bProcessing: true,
    bServerSide: true,
    bLengthChange: 1,
    bProcessing: true,
    bStateSave: 0,
    bAutoWidth: 0,
    iDisplayLength: 11,
    sPaginationType: "full_numbers",
    "drawCallback": function(){
        $('.tooltip').tooltipster();
    },
    "initComplete": function(){
        $('.tooltip').tooltipster();
    },
    columnDefs: [
      {
        targets: [2, 5, 6, 7],
        className: "dt-body-center",
        orderable: true,
      },
      {
        targets: [8],
        className: "dt-body-right",
      },
      {
        targets: [0, 7, 8],
        orderable: false,
      },
    ],
    order: [[0, "desc"]],
    language: {
      loadingRecords: "&nbsp;",
      processing:
        '<div class="wf301-datatables-loader"><img width="64" src="' +
        wf301_vars.icon_url +
        '" /></div>',
      emptyTable:
        "No redirects created. Click on Add Redirect Rule button above to create your first rule.",
      searchPlaceholder: "Type something to search ...",
      search: "",
    },
    dom:
      '<"settings_page_301redirects_top"f>rt<"settings_page_301redirects_bottom"lp><"bottomButtonArea"><"clear">',
    sAjaxSource:
      ajaxurl +
      "?action=wf301_run_tool&tool=redirect_rules&_ajax_nonce=" +
      wf301_vars.run_tool_nonce,
    fnDrawCallback: function (oSettings) {
      var urlParams = new URLSearchParams(location.search);
      if (urlParams.has("edit_rule")) {
        $(
          '.edit_redirect_rule[data-redirect="' +
            urlParams.get("edit_rule") +
            '"]'
        ).trigger("click");
      }
      if (oSettings._iDisplayLength >= oSettings.fnRecordsDisplay()) {
        $(oSettings.nTableWrapper)
          .find(".dataTables_length")
          .css("display", "none");
        $(oSettings.nTableWrapper)
          .find(".dataTables_paginate")
          .css("display", "none");
      } else {
        $(oSettings.nTableWrapper)
          .find(".dataTables_length")
          .css("display", "block");
        $(oSettings.nTableWrapper)
          .find(".dataTables_paginate")
          .css("display", "block");
      }

      var $selectTagsElementControlInit = $("#selected-tags").selectize({
        plugins: ["remove_button"],
        delimiter: ",",
        persist: false,
        onChange: function (value) {
          $("#wf301-redirects-table")
            .DataTable()
            .column(7)
            .search(value)
            .draw();

          if (value === "") {
            $("#tag_area").hide();
          }
        },
      });

      selectedTagElement = $selectTagsElementControlInit[0].selectize;

      selectedTagElement.$control_input.on("keydown", function (e) {
        var key = e.charCode || e.keyCode;
        if (key == 8) return true;
        else e.preventDefault();
      });
    },
  });

  // onboarding
  if ($("#wf301-onboarding-tabs-wrapper").length > 0) {
    $("#wf301-onboarding-tabs-wrapper").dialog({
      dialogClass: "wp-dialog wf301-dialog wf301-onboarding-dialog",
      modal: 1,
      resizable: false,
      zIndex: 9999,
      width: 700,
      height: "auto",
      show: "fade",
      hide: "fade",
      open: function (event, ui) {
        $("#wf301-onboarding-tabs").tabs({ active: 0 });
        $(".ui-widget-overlay").addClass("wf301-onboarding-overlay");
        $("#wf301-onboarding-tabs-wrapper").dialog("option", "position", {
          my: "top",
          at: "top",
          of: window,
        });
      },
      close: function (event, ui) {},
      autoOpen: false,
      closeOnEscape: true,
    });

    $("#wf301-onboarding-tabs-wrapper")
      .dialog("option", "title", "Welcome to 301 Redirects")
      .dialog("open");

    $(".settings_page_301redirects").on(
      "click",
      ".wf301-onboarding-tab-next",
      function () {
        $("#wf301-onboarding-tabs").tabs(
          "option",
          "active",
          $(this).closest("[data-tab]").data("tab") + 1
        );
        wf301_save_onboarding_settings();
      }
    );

    $(".settings_page_301redirects").on(
      "click",
      ".wf301-onboarding-tab-previous",
      function () {
        $("#wf301-onboarding-tabs").tabs(
          "option",
          "active",
          $(this).closest("[data-tab]").data("tab") - 1
        );
        wf301_save_onboarding_settings();
      }
    );

    $(".settings_page_301redirects").on(
      "click",
      ".wf301-onboarding-tab-skip",
      function () {
        $("#wf301-onboarding-tabs-wrapper").dialog("close");
        wf301_save_onboarding_settings();
      }
    );

    $(".wf301-onboarding-tab").on("click", "li", function () {
      wf301_save_onboarding_settings();
    });

    $(window).resize(function () {
      $("#wf301-onboarding-tabs-wrapper").dialog("option", "position", {
        my: "top",
        at: "top",
        of: window,
      });
      if ($(this).width() > 900) {
        $("#wf301-onboarding-tabs-wrapper").dialog("option", "width", "700px");
      } else {
        $("#wf301-onboarding-tabs-wrapper").dialog(
          "option",
          "width",
          $(window).width() * 0.9
        );
      }
    });

    function wf301_save_onboarding_settings() {
      $.post(
        ajaxurl,
        {
          action: "wf301_run_tool",
          _ajax_nonce: wf301_vars.run_tool_nonce,
          tool: "save_onboarding_settings",
          autoredirect_404: $("#ob_autoredirect_404").is(":checked"),
          logs_upload: $("#ob_logs_upload").is(":checked"),
          page_404: $("#ob_page_404").val(),
          email_to: $("#ob_email_to").val(),
          email_reports_404: $("#ob_404_email_reports").val(),
          retention_404: $("#ob_retention_404").val(),
          email_reports_redirect: $("#ob_redirect_email_reports").val(),
          retention_redirect: $("#ob_retention_redirect").val(),
        },
        function (response) {
          if ($("#ob_autoredirect_404").is(":checked")) {
            $("#autoredirect_404").prop("checked", true);
          } else {
            $("#autoredirect_404").prop("checked", false);
          }

          if ($("#ob_logs_upload").is(":checked")) {
            $("#logs_upload").prop("checked", true);
          } else {
            $("#logs_upload").prop("checked", false);
          }

          $("#page_404").val($("#ob_page_404").val());
          $("#email_to").val($("#ob_email_to").val());
          $("#email_reports").val($("#ob_404_email_reports").val());
          $("#retention_404").val($("#ob_retention_404").val());
          $("#redirect_email_reports").val(
            $("#ob_redirect_email_reports").val()
          );
          $("#retention_redirect").val($("#ob_retention_redirect").val());
        }
      ).fail(function () {
        alert("Undocumented error. Please reload the page and try again.");
      });
    }
  } // onboarding

  // init access links dialog
  $("#redirect-rule-dialog").dialog({
    dialogClass:
      "wp-dialog wf301-dialog wf301-redirect-rule-dialog no-titlebar",
    modal: 1,
    resizable: false,
    zIndex: 9999,
    width: 1000,
    height: "auto",
    show: "fade",
    hide: "fade",
    open: function (event, ui) {
      WF301.fix_dialog_close(event, ui);
    },
    close: function (event, ui) {},
    autoOpen: false,
    closeOnEscape: true,
  });

  // init access links dialog
  $("#redirect-check-dialog").dialog({
    dialogClass: "wp-dialog wf301-dialog wf301-redirect-rule-dialog",
    modal: 1,
    resizable: false,
    zIndex: 9999,
    width: 700,
    height: "auto",
    show: "fade",
    hide: "fade",
    open: function (event, ui) {
      WF301.fix_dialog_close(event, ui);
    },
    close: function (event, ui) {},
    autoOpen: false,
    closeOnEscape: true,
  });

  $("body").on("click", ".add-redirect-rule", function (e) {
    e.preventDefault();

    $("#redirect_id").val("");
    $("#redirect_enabled").prop("checked", true);
    $("#redirect_url_from").val("");
    $("#redirect_url_to").val("");
    $("#redirect_query").val("ignore");
    $("#redirect_case_insensitive").prop("checked", true);
    $("#redirect_regex").prop("checked", false);
    $("#redirect_position").val(10);
    $("#redirect_type").val("301");
    tagElement.clear();
    $("#submit_redirect_rule").html('Add rule <i class="wf301-icon wf301-redirect"></i>');
    $("#redirect-rule-dialog")
    $("#redirect-rule-dialog").find('.dialog-title').html("Add New Redirect Rule");
    $("#redirect-rule-dialog").dialog("open");

    return false;
  }); // add_access_link

  if (window.location.hash == "#add-redirect-rule") {
    $(".add-redirect-rule").trigger("click");
  }

  $('.wf301-dialog-close').on('click', function(){
    $("#redirect-rule-dialog").dialog("close");
  });

  // display a message while an action is performed
  function block_ui(message) {
    tmp = wf301_swal.fire({
      text: message,
      type: false,
      imageUrl: wf301_vars.icon_url,
      onOpen: () => {
        //$(wf301_swal.getImage()).addClass("rotating");
      },
      imageWidth: 100,
      imageHeight: 100,
      imageAlt: message,
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      showConfirmButton: false,
      heightAuto: false,
    });

    return tmp;
  } // block_ui

  $(".settings_page_301redirects").on(
    "change",
    ".toggle_redirect_rule",
    function (e) {
      var toggle = $(this).parent();
      var row = $(this).parents("tr");

      if ($(this).is(":checked")) {
        row.removeClass("disabled");
        toggle.attr('title', 'Disable Rule');
      } else {
        row.addClass("disabled");
        toggle.attr('title', 'Enable Rule');
      }

      toggle.tooltipster('destroy');
      $('.tooltip').tooltipster();

      toggle.addClass("toggle_disabled");
      toggle.prepend('<div class="toggle_status">Updating...</div>');

      
      $.post(
        ajaxurl,
        {
          action: "wf301_run_tool",
          _ajax_nonce: wf301_vars.run_tool_nonce,
          tool: "submit_redirect_rule",
          redirect_id: $(this).data("redirect"),
          redirect_toggle: $(this).is(":checked"),
        },
        function (response) {
          if (response.success) {
          } else {
            alert(response.data);
          }
          toggle.find(".toggle_status").remove();
        }
      ).fail(function () {
        alert("Undocumented error. Please reload the page and try again.");
      });
    }
  );

  $(".settings_page_301redirects").on(
    "click",
    ".delete_redirect_rule",
    function (e) {
      e.preventDefault();
      uid = $(this).data("ss-uid");
      button = $(this);

      wf301_swal
        .fire({
          title: $(button).data("title"),
          type: "question",
          text: $(button).data("text"),
          heightAuto: false,
          showCancelButton: true,
          focusConfirm: false,
          confirmButtonText: $(button).data("btn-confirm"),
          cancelButtonText: wf301_vars.cancel_button,
          width: 600,
        })
        .then((result) => {
          if (typeof result.value != "undefined") {
            block = block_ui($(button).data("msg-wait"));
            $.post({
              url: ajaxurl,
              data: {
                action: "wf301_run_tool",
                _ajax_nonce: wf301_vars.run_tool_nonce,
                tool: "delete_redirect_rule",
                redirect_id: $(button).data("redirect"),
              },
            })
              .always(function (response) {
                wf301_swal.close();
              })
              .done(function (response) {
                if (response.success) {
                  $("#wf301-redirects-table tr#" + response.data.id).remove();
                  wf301_swal.fire({
                    type: "success",
                    heightAuto: false,
                    title: $(button).data("msg-success"),
                  });
                } else {
                  wf301_swal.fire({
                    type: "error",
                    heightAuto: false,
                    title: wf301_vars.documented_error + " " + data.data,
                  });
                }
              })
              .fail(function (response) {
                wf301_swal.fire({
                  type: "error",
                  heightAuto: false,
                  title: wf301_vars.undocumented_error,
                });
              });
          } // if confirmed
        });
    }
  );

  $(".settings_page_301redirects").on("click", ".edit_redirect_rule", function (
    e
  ) {
    e.preventDefault();
    open_edit_redirect_popup($(this));
    return false;
  });

  function open_edit_redirect_popup(button) {
    $("#redirect_id").val(button.data("redirect"));
    $("#redirect_enabled").prop(
      "checked",
      button.data("redirect-status") == "enabled"
    );
    $("#redirect_url_from").val(button.data("redirect-from"));
    $("#redirect_url_to").val(button.data("redirect-to"));
    $("#redirect_query").val(button.data("redirect-query"));
    
    $("#redirect_case_insensitive").prop(
      "checked",
      button.data("redirect-case") == "enabled"
    );
    $("#redirect_regex").prop(
        "checked",
        button.data("redirect-regex") == "enabled"
    );

    $("#redirect_position").val(button.data("redirect-position"));

    $("#redirect_type").val(button.data("redirect-type"));
    $("#submit_redirect_rule").html('Update rule <i class="wf301-icon wf301-redirect"></i>');
    $("#redirect-rule-dialog").find('.dialog-title').html("Update Redirect Rule");
    $("#redirect-rule-dialog").dialog("open");

    var tags = button
      .data("redirect-tags")
      .split(",")
      .filter((o) => o);

    tags.forEach((tag) => {
      tagElement.addItem(tag);
    });

    loadTags(tags);
  }

  $(".settings_page_301redirects").on(
    "click",
    ".create_redirect_rule_404",
    function (e) {
      e.preventDefault();
      $("#wf301_tabs").tabs("option", "active", 0);
      $("#redirect_id").val("");
      $("#redirect_enabled").prop("checked", true);
      $("#redirect_url_from").val($(this).data("url"));
      $("#redirect_url_to").val("");
      $("#redirect_query").val("ignore");
      $("#redirect_case_insensitive").prop("checked", true);
      $("#redirect_regex").prop("checked", false);
      $("#redirect_position").val(0);

      $("#redirect_type").val("301");
      $("#submit_redirect_rule").html('Add rule <i class="wf301-icon wf301-redirect"></i>');
      $("#redirect-rule-dialog").find('.dialog-title').html("Add New Redirect Rule");
      $("#redirect-rule-dialog").dialog("open");

      return false;
    }
  );

  $(".settings_page_301redirects").on("change", "#redirect_url_from", function (
    e
  ) {
    e.preventDefault();
    var from_url = $(this).val();
    console.log('Check duplicate for ' + from_url);
    if ($('[data-redirect-from="' + from_url + '"]').length > 0) {
      $(".duplicate-rule-warning").remove();
      $("#redirect-rule-dialog").prepend(
        '<div class="duplicate-rule-warning notice notice-warning"><p>A rule for ' +
          from_url +
          " already exists. You can still add a new one for this URL but the instance with the lowest priority value will be used.</p></div>"
      );
    } else {
      $(".duplicate-rule-warning").remove();
    }
    return false;
  });

  $(".settings_page_301redirects").on(
    "click",
    ".verify_redirect_rule",
    function (e) {
      e.preventDefault();

      block = block_ui($($(this)).data("msg-wait"));

      $.get(
        "https://api.redirect.li/v1/http/",
        {
          url: $(this).data("url"),
        },
        function (response) {
          wf301_swal.close();

          if (
            300 < parseInt(response.status) &&
            parseInt(response.status) < 310
          ) {
            $(".redirect-check-result")
              .removeClass("fa-times")
              .addClass("fa-check");
          } else {
            $(".redirect-check-result")
              .removeClass("fa-check")
              .addClass("fa-times");
          }

          var check_html = "";
          if (response.url) {
            check_html += "<strong>URL:</strong> " + response.url + "<br />";
          }
          if (response.intent) {
            check_html +=
              "<strong>Result:</strong> " + response.intent + "<br />";
          }
          if (response.statusMessage) {
            check_html +=
              "<strong>Status Message:</strong> " +
              response.statusMessage +
              "<br />";
          }
          if (response.status) {
            check_html +=
              "<strong>Status:</strong> " + response.status + "<br />";
          }

          $(".redirect-check-details").html(check_html);

          $("#redirect-check-dialog")
            .dialog("option", "title", "Redirect Check")
            .dialog("open");
        }
      ).fail(function () {
        alert("Undocumented error. Please reload the page and try again.");
      });
    }
  );

  Chart.defaults.global.defaultFontColor = "#23282d";
  Chart.defaults.global.defaultFontFamily =
    '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif';
  Chart.defaults.global.defaultFontSize = 12;
  var wf301_404_chart;
  var wf301_redirect_chart;
  var wf301_404_device_chart;
  var wf301_redirect_device_chart;

  function create_redirect_chart() {
    if (!wf301_vars.stats_redirect || !wf301_vars.stats_redirect.days.length) {
      $("#wf301-redirect-chart").remove();
    } else {
      if (wf301_redirect_chart) {
        wf301_redirect_chart.destroy();
      }

      var chartredirectcanvas = document
        .getElementById("wf301-redirect-chart")
        .getContext("2d");
      var gradient = chartredirectcanvas.createLinearGradient(0, 0, 0, 200);
      gradient.addColorStop(0, '#f9f9f9');
      gradient.addColorStop(1, '#ffffff');

      wf301_redirect_chart = new Chart(chartredirectcanvas, {
        type: "line",

        data: {
          labels: wf301_vars.stats_redirect.days,
          datasets: [
            {
              label: "Redirects",
              yAxisID: "yleft",
              xAxisID: "xdown",
              data: wf301_vars.stats_redirect.count,
              backgroundColor: gradient,
              borderColor: wf301_vars.chart_colors[0],
              hoverBackgroundColor: wf301_vars.chart_colors[0],
              borderWidth: 0,
            },
          ],
        },
        options: {
          animation: false,
          legend: false,
          maintainAspectRatio: false,
          tooltips: {
            mode: "index",
            intersect: false,
            callbacks: {
              title: function (value, values) {
                index = value[0].index;
                return moment(values.labels[index], "YYYY-MM-DD").format(
                  "dddd, MMMM Do"
                );
              },
            },
            displayColors: false,
          },
          scales: {
            xAxes: [
              {
                display: false,
                id: "xdown",
                stacked: true,
                ticks: {
                  callback: function (value, index, values) {
                    return moment(value, "YYYY-MM-DD").format("MMM Do");
                  },
                },
                categoryPercentage: 0.85,
                time: {
                  unit: "day",
                  displayFormats: { day: "MMM Do" },
                  tooltipFormat: "dddd, MMMM Do",
                },
                gridLines: { display: false },
              },
            ],
            yAxes: [
              {
                display: false,
                id: "yleft",
                position: "left",
                type: "linear",
                scaleLabel: {
                  display: true,
                  labelString: "Hits",
                },
                gridLines: { display: false },
                stacked: false,
                ticks: {
                  beginAtZero: false,
                  maxTicksLimit: 12,
                  callback: function (value, index, values) {
                    return Math.round(value);
                  },
                },
              },
            ],
          },
        },
      });
    }
  }

  function create_404_chart() {
    if (!wf301_vars.stats_404 || !wf301_vars.stats_404.days.length) {
      $("#wf301-404-chart").remove();
    } else {
      if (wf301_404_chart) wf301_404_chart.destroy();

      var chart404canvas = document
        .getElementById("wf301-404-chart")
        .getContext("2d");
      var gradient = chart404canvas.createLinearGradient(0, 0, 0, 200);
      gradient.addColorStop(0, "#f9f9f9");
      gradient.addColorStop(1, "#ffffff");

      wf301_404_chart = new Chart(chart404canvas, {
        type: "line",
        data: {
          labels: wf301_vars.stats_404.days,
          datasets: [
            {
              label: "404s",
              yAxisID: "yleft",
              xAxisID: "xdown",
              data: wf301_vars.stats_404.count,
              backgroundColor: gradient,
              borderColor: wf301_vars.chart_colors[0],
              hoverBackgroundColor: wf301_vars.chart_colors[0],
              borderWidth: 0,
            },
          ],
        },
        options: {
          animation: false,
          legend: false,
          maintainAspectRatio: false,
          tooltips: {
            mode: "index",
            intersect: false,
            callbacks: {
              title: function (value, values) {
                index = value[0].index;
                return moment(values.labels[index], "YYYY-MM-DD").format(
                  "dddd, MMMM Do"
                );
              },
            },
            displayColors: false,
          },

          scales: {
            xAxes: [
              {
                display: false,
                id: "xdown",
                stacked: true,
                ticks: {
                  callback: function (value, index, values) {
                    return moment(value, "YYYY-MM-DD").format("MMM Do");
                  },
                },
                categoryPercentage: 0.85,
                time: {
                  unit: "day",
                  displayFormats: { day: "MMM Do" },
                  tooltipFormat: "dddd, MMMM Do",
                },
                gridLines: { display: false },
              },
            ],
            yAxes: [
              {
                display: false,
                id: "yleft",
                position: "left",
                type: "linear",
                scaleLabel: {
                  display: true,
                  labelString: "Hits",
                },
                gridLines: { display: false },
                stacked: false,
                ticks: {
                  beginAtZero: false,
                  maxTicksLimit: 12,
                  callback: function (value, index, values) {
                    return Math.round(value);
                  },
                },
              },
            ],
          },
        },
      });
    }
  }

  Chart.defaults.doughnutLabels = Chart.helpers.clone(Chart.defaults.doughnut);
  var wf301_doughnut_helpers = Chart.helpers;
  Chart.controllers.doughnutLabels = Chart.controllers.doughnut.extend({
    updateElement: function (arc, index, reset) {
      var _this = this;
      var chart = _this.chart,
        chartArea = chart.chartArea,
        opts = chart.options,
        animationOpts = opts.animation,
        arcOpts = opts.elements.arc,
        centerX = (chartArea.left + chartArea.right) / 2,
        centerY = (chartArea.top + chartArea.bottom) / 2,
        startAngle = opts.rotation, // non reset case handled later
        endAngle = opts.rotation, // non reset case handled later
        dataset = _this.getDataset(),
        circumference =
          reset && animationOpts.animateRotate
            ? 0
            : arc.hidden
            ? 0
            : _this.calculateCircumference(dataset.data[index]) *
              (opts.circumference / (2.0 * Math.PI)),
        innerRadius =
          reset && animationOpts.animateScale ? 0 : _this.innerRadius,
        outerRadius =
          reset && animationOpts.animateScale ? 0 : _this.outerRadius,
        custom = arc.custom || {},
        valueAtIndexOrDefault = wf301_doughnut_helpers.getValueAtIndexOrDefault;

      wf301_doughnut_helpers.extend(arc, {
        // Utility
        _datasetIndex: _this.index,
        _index: index,

        // Desired view properties
        _model: {
          x: centerX + chart.offsetX,
          y: centerY + chart.offsetY,
          startAngle: startAngle,
          endAngle: endAngle,
          circumference: circumference,
          outerRadius: outerRadius,
          innerRadius: innerRadius,
          label: valueAtIndexOrDefault(
            dataset.label,
            index,
            chart.data.labels[index]
          ),
        },

        draw: function () {
          var ctx = this._chart.ctx,
            vm = this._view,
            sA = vm.startAngle,
            eA = vm.endAngle,
            opts = this._chart.config.options;

          var labelPos = this.tooltipPosition();
          var segmentLabel = (vm.circumference / opts.circumference) * 100;

          ctx.beginPath();

          ctx.arc(vm.x, vm.y, vm.outerRadius, sA, eA);
          ctx.arc(vm.x, vm.y, vm.innerRadius, eA, sA, true);

          ctx.closePath();
          ctx.strokeStyle = vm.borderColor;
          ctx.lineWidth = vm.borderWidth;

          ctx.fillStyle = vm.backgroundColor;

          ctx.fill();
          ctx.lineJoin = "bevel";

          if (vm.circumference > 0.15) {
            // Trying to hide label when it doesn't fit in segment
            ctx.beginPath();
            ctx.font = wf301_doughnut_helpers.fontString(
              opts.defaultFontSize,
              opts.defaultFontStyle,
              opts.defaultFontFamily
            );
            ctx.fillStyle = "#fff";
            ctx.textBaseline = "top";
            ctx.textAlign = "center";

            // Round percentage in a way that it always adds up to 100%
            ctx.fillText(segmentLabel.toFixed(0) + "%", labelPos.x, labelPos.y);
          }
        },
      });

      var model = arc._model;
      model.backgroundColor = custom.backgroundColor
        ? custom.backgroundColor
        : valueAtIndexOrDefault(
            dataset.backgroundColor,
            index,
            arcOpts.backgroundColor
          );
      model.hoverBackgroundColor = custom.hoverBackgroundColor
        ? custom.hoverBackgroundColor
        : valueAtIndexOrDefault(
            dataset.hoverBackgroundColor,
            index,
            arcOpts.hoverBackgroundColor
          );
      model.borderWidth = custom.borderWidth
        ? custom.borderWidth
        : valueAtIndexOrDefault(
            dataset.borderWidth,
            index,
            arcOpts.borderWidth
          );
      model.borderColor = custom.borderColor
        ? custom.borderColor
        : valueAtIndexOrDefault(
            dataset.borderColor,
            index,
            arcOpts.borderColor
          );

      // Set correct angles if not resetting
      if (!reset || !animationOpts.animateRotate) {
        if (index === 0) {
          model.startAngle = opts.rotation;
        } else {
          model.startAngle = _this.getMeta().data[index - 1]._model.endAngle;
        }

        model.endAngle = model.startAngle + model.circumference;
      }

      arc.pivot();
    },
  });

  function create_404_device_chart() {
    if (
      !wf301_vars.stats_404_devices ||
      !wf301_vars.stats_404_devices.percent.length
    ) {
      $("#wf301_404_devices_chart").remove();
    } else {
      if (wf301_404_device_chart) wf301_404_device_chart.destroy();
      devices_404_chart = new Chart(
        document.getElementById("wf301_404_devices_chart").getContext("2d"),
        {
          type: "doughnutLabels",
          data: {
            datasets: [
              {
                data: wf301_vars.stats_404_devices.percent,
                backgroundColor: [
                  wf301_vars.chart_colors[0],
                  wf301_vars.chart_colors[1],
                  wf301_vars.chart_colors[2],
                  wf301_vars.chart_colors[3]
                ],
              },
            ],
            labels: wf301_vars.stats_404_devices.labels,
          },
          options: {
            animation: false,
            responsive: true,
            segmentShowStroke: false,
            legend: {
              display: false,
            },
            tooltips: {
              callbacks: {
                label: function (tooltipItem, data) {
                  var dataset = data.datasets[tooltipItem.datasetIndex];
                  return (
                    data.labels[tooltipItem.index] +
                    ": " +
                    dataset.data[tooltipItem.index]
                  );
                },
              },
            },
          },
        }
      );
    }
  }

  function create_redirect_device_chart() {
    if (
      !wf301_vars.stats_redirect_devices ||
      !wf301_vars.stats_redirect_devices.percent.length
    ) {
      $("#wf301_redirect_devices_chart").remove();
    } else {
      if (wf301_redirect_device_chart) wf301_redirect_device_chart.destroy();
      devices_redirect_chart = new Chart(
        document
          .getElementById("wf301_redirect_devices_chart")
          .getContext("2d"),
        {
          type: "doughnutLabels",
          data: {
            datasets: [
              {
                data: wf301_vars.stats_redirect_devices.percent,
                backgroundColor: [
                  wf301_vars.chart_colors[0],
                  wf301_vars.chart_colors[1],
                  wf301_vars.chart_colors[2],
                  wf301_vars.chart_colors[3],
                ],
              },
            ],
            labels: wf301_vars.stats_redirect_devices.labels,
          },
          options: {
            animation: false,
            responsive: true,
            legend: {
              display: false,
            },
            tooltips: {
              callbacks: {
                label: function (tooltipItem, data) {
                  var dataset = data.datasets[tooltipItem.datasetIndex];
                  return (
                    data.labels[tooltipItem.index] +
                    ": " +
                    dataset.data[tooltipItem.index]
                  );
                },
              },
            },
          },
        }
      );
    }
  }

  function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
      sURLVariables = sPageURL.split("&"),
      sParameterName,
      i;

    for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split("=");

      if (sParameterName[0] === sParam) {
        return sParameterName[1] === undefined
          ? true
          : decodeURIComponent(sParameterName[1]);
      }
    }
  }

  if (window.localStorage.getItem("wf301_redirect_chart") == null) {
    window.localStorage.setItem("wf301_redirect_chart", "enabled");
  }

  if (window.localStorage.getItem("wf301_404_chart") == null) {
    window.localStorage.setItem("wf301_404_chart", "enabled");
  }

  if (window.localStorage.getItem("wf301_redirect_stats") == null) {
    window.localStorage.setItem("wf301_redirect_stats", "enabled");
  }

  if (window.localStorage.getItem("wf301_404_stats") == null) {
    window.localStorage.setItem("wf301_404_stats", "enabled");
  }

  if (
    $(".wf301-chart-redirect").length &&
    window.localStorage.getItem("wf301_redirect_chart") == "enabled"
  ) {
    $(".wf301-chart-redirect").show();
    create_redirect_chart();
    create_redirect_device_chart();
  }

  if (
    $(".wf301-chart-404").length &&
    window.localStorage.getItem("wf301_404_chart") == "enabled"
  ) {
    $(".wf301-chart-404").show();
    create_404_chart();
  }

  if (window.localStorage.getItem("wf301_redirect_stats") == "enabled") {
    $(".wf301-stats-redirect").show();
    create_404_device_chart();
  }

  if (window.localStorage.getItem("wf301_404_stats") == "enabled") {
    $(".wf301-stats-404").show();
  }

  if (wf301_vars.stats_404.total == 0) {
    var placeholder_top = 0;
    if (window.localStorage.getItem("wf301_404_stats") == "enabled") {
      placeholder_top = placeholder_top + 70;
    }
    if (window.localStorage.getItem("wf301_404_chart") == "enabled") {
      placeholder_top = placeholder_top + 120;
    }
    $(".wf301-chart-404").css("filter", "blur(3px)");
    $(".wf301-stats-404").css("filter", "blur(3px)");
    $("#wf301_404_log").append(
      '<div class="wf301-chart-placeholder">' +
        wf301_vars.stats_unavailable_404 +
        "</div>"
    );

    if (placeholder_top == 0) {
      $("#wf301_404_log .wf301-chart-placeholder").hide();
    } else {
      $("#wf301_404_log .wf301-chart-placeholder").css(
        "top",
        placeholder_top + "px"
      );
      $("#wf301_404_log .wf301-chart-placeholder").fadeIn(300);
    }
  }
  if (wf301_vars.stats_redirect.total == 0) {
    var placeholder_top = 0;
    if (window.localStorage.getItem("wf301_redirect_stats") == "enabled") {
      placeholder_top = placeholder_top + 70;
    }
    if (window.localStorage.getItem("wf301_redirect_chart") == "enabled") {
      placeholder_top = placeholder_top + 120;
    }
    $(".wf301-chart-redirect").css("filter", "blur(3px)");
    $(".wf301-stats-redirect").css("filter", "blur(3px)");
    $("#wf301_redirect_log").append(
      '<div class="wf301-chart-placeholder" style="display:none;">' +
        wf301_vars.stats_unavailable +
        "</div>"
    );

    if (placeholder_top == 0) {
      $("#wf301_redirect_log .wf301-chart-placeholder").hide();
    } else {
      $("#wf301_redirect_log .wf301-chart-placeholder").css(
        "top",
        placeholder_top + "px"
      );
      $("#wf301_redirect_log .wf301-chart-placeholder").fadeIn(300);
    }
  }

  $("#wf301_tabs").on("tabsactivate", function (event, ui) {
    var active_index = $("#wf301_tabs").tabs("option", "active");
    var active_id = $("#wf301_tabs > ul > li")
      .eq(active_index)
      .find("a")
      .attr("href")
      .replace("#", "");
    if (active_id == "wf301_redirect_log") {
      if (window.localStorage.getItem("wf301_redirect_chart") == "enabled") {
        create_redirect_chart();
        create_redirect_device_chart();
      }
    } else if (active_id == "wf301_404_log") {
      if (window.localStorage.getItem("wf301_404_chart") == "enabled") {
        create_404_chart();
        create_404_device_chart();
      }
    }
  });

  $(window).on("hashchange", function () {
    $("#wf301_tabs").tabs(
      "option",
      "active",
      $("a[href=\\" + location.hash + "]")
        .parent()
        .index()
    );
  });

  $("select.wf301-404-group").val(
    window.localStorage.getItem("wf301-404") || "ng"
  );
  $("select.wf301-redirect-group").val(
    window.localStorage.getItem("wf301-redirect") || "ng"
  );
  $(".wf301-group-change").click();

  $("body").on("click", ".wf301-tag", function (e) {
    e.preventDefault();

    var tag = $(this).data("tag");

    $("#tag_area").show();

    selectedTagElement.addOption({
      text: tag,
      value: tag,
    });
    selectedTagElement.addItem(tag);

    return false;
  }); // wf301-tag

  var selectedTab = getUrlParameter("tab");

  if (selectedTab) {
    $("#wf301_tabs").tabs(
      "option",
      "active",
      $("a[href=\\#" + selectedTab + "]")
        .parent()
        .index()
    );
  }

  $("input[type=file]").wf301File();

  $('.tooltip').tooltipster();
}); // on ready



(function ($) {
  var multipleSupport = typeof $("<input/>")[0].multiple !== "undefined",
    isIE = /msie/i.test(navigator.userAgent);

  $.fn.wf301File = function () {
    return this.each(function () {
      var $file = $(this).addClass("wf301-file-upload-hidden"),
        $wrap = $('<div class="wf301-file-upload-wrapper">'),
        $input = $('<input type="text" class="upload-input" />'),
        $button = $(
          '<button type="button" class="file-upload-button button-primary button-gray"><i class="wf301-icon wf301-import"></i> Browse</button>'
        ),
        $label = $(
          '<label class="upload-button" for="' +
            $file[0].id +
            '"><i class="wf301-icon wf301-import"></i> Browse</label>'
        );
      $file.css({
        position: "absolute",
        left: "-9999px",
      });

      $wrap.insertAfter($file).append($file, $input, isIE ? $label : $button);
      $file.attr("tabIndex", -1);
      $button.attr("tabIndex", -1);

      $button.click(function () {
        $file.focus().click();
      });

      $file.change(function () {
        var files = [],
          fileArr,
          filename;
        if (multipleSupport) {
          fileArr = $file[0].files;
          for (var i = 0, len = fileArr.length; i < len; i++) {
            files.push(fileArr[i].name);
          }
          filename = files.join(", ");
        } else {
          filename = $file.val().split("\\").pop();
        }

        $input.val(filename).attr("title", filename).focus();
      });

      $input.on({
        blur: function () {
          $file.trigger("blur");
        },
        keydown: function (e) {
          if (e.which === 13) {
            // Enter
            if (!isIE) {
              $file.trigger("click");
            }
          } else if (e.which === 8 || e.which === 46) {
            $file.replaceWith(($file = $file.clone(true)));
            $file.trigger("change");
            $input.val("");
          } else if (e.which === 9) {
            return;
          } else {
            return false;
          }
        },
      });
    });
  };

  // Old browser fallback
  if (!multipleSupport) {
    $(document).on("change", "input.wf301File", function () {
      var $this = $(this),
        uniqId = "wf301File_" + new Date().getTime(),
        $wrap = $this.parent(),
        $inputs = $wrap
          .siblings()
          .find(".file-upload-input")
          .filter(function () {
            return !this.value;
          }),
        $file = $(
          '<input type="file" id="' +
            uniqId +
            '" name="' +
            $this.attr("name") +
            '"/>'
        );
      setTimeout(function () {
        if ($this.val()) {
          if (!$inputs.length) {
            $wrap.after($file);
            $file.wf301File();
          }
        } else {
          $inputs.parent().remove();
          $wrap.appendTo($wrap.parent());
          $wrap.find("input").focus();
        }
      }, 1);
    });
  }
})(jQuery);
