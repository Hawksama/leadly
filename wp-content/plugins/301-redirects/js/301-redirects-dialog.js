jQuery(document).ready(function($) {
  // init access links dialog
  $("#redirect-rule-dialog").dialog({
    dialogClass: "wp-dialog wf301-dialog wf301-redirect-rule-dialog",
    modal: 1,
    resizable: false,
    zIndex: 9999,
    width: 700,
    height: "auto",
    show: "fade",
    hide: "fade",
    open: function(event, ui) {
      jQuery(".ui-widget-overlay").bind("click", function() {
        jQuery("#" + event.target.id).dialog("close");
      });
    },
    close: function(event, ui) {},
    autoOpen: false,
    closeOnEscape: true
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
    open: function(event, ui) {
      jQuery(".ui-widget-overlay").bind("click", function() {
        jQuery("#" + event.target.id).dialog("close");
      });
    },
    close: function(event, ui) {},
    autoOpen: false,
    closeOnEscape: true
  });

  $('body').on('click','.add-redirect-rule', function(e){
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
    $("#submit_redirect_rule").val("Add rule");
    $("#redirect-rule-dialog")
      .dialog("option", "title", "Add New Redirect Rule")
      .dialog("open");

    return false;
  }); // add_access_link
});