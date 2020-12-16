/**
 * 301 Redirects Pro
 * https://wp301redirects.com/
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

// global object for all WF301 functions

jQuery(document).ready(function ($) {
  $(".tooltip").tooltipster();
  $(".notice-dismiss").on("click", function () {
    $.post(
      ajaxurl,
      {
        action: "wf301_run_tool",
        _ajax_nonce: wf301_vars.run_tool_nonce,
        tool: "dismiss_permalink_change",
        permalink_id: $(this).parent("div").data("permalink"),
      },
      function (response) {
        // Do nothing
      }
    ).fail(function () {
      // Do nothing
    });
  });

  if (typeof wp301_rebranding != "undefined") {
    if ($('[data-slug="301-redirects"]').length > 0) {
      $('[data-slug="301-redirects"]')
        .children(".plugin-title")
        .children("strong")
        .html("<strong>" + wp301_rebranding.name + "</strong>");
    }
  }

  if (typeof wf301_pointers != "undefined") {
    $.each(wf301_pointers, function (index, pointer) {
      if (index.charAt(0) == "_") {
        return true;
      }
      $(pointer.target)
        .pointer({
          content: "<h3>301 Redirects Pro</h3><p>" + pointer.content + "</p>",
          pointerWidth: 380,
          position: {
            edge: pointer.edge,
            align: pointer.align,
          },
          close: function () {
            $.get(ajaxurl, {
              _ajax_nonce: wf301_vars.run_tool_nonce,
              action: "wf301_dismiss_pointer",
            });
          },
        })
        .pointer("open");
    });
  }

  $("#wpwrap").on("click", ".wf301-add-redirect-rule", function (e) {
    e.preventDefault();
    var notice = $(this).closest(".notice");
    var permalink_id = $(this).data("permalink");
    $.post(
      ajaxurl,
      {
        action: "wf301_run_tool",
        _ajax_nonce: wf301_vars.run_tool_nonce,
        tool: "add_permalink_rule",
        permalink_id: permalink_id,
      },
      function (response) {
        if (response.success) {
          console.log("Success!", response.data.url);
          notice.replaceWith('<div class="notice-success notice is-dismissible"><p>The new redirect rule has been created! <a href="' + response.data.url + '">Click here to edit it</a></p></div><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></p></div>');
        } else {
          alert("An error occured creating the new rule!");
        }
      }
    ).fail(function () {
      // Do nothing
    });
  });

  (function (wp) {
    if (typeof wp !== "undefined" && typeof wp.blocks !== "undefined" && wp.data.select("core/editor") !== null) {
      wp.wf301_update_detect = "";

      wp.data.subscribe(function () {
        var isSavingPost = wp.data.select("core/editor").isSavingPost();
        var isAutosavingPost = wp.data.select("core/editor").isAutosavingPost();

        if (isSavingPost && !isAutosavingPost) {
          clearInterval(wp.wf301_update_detect);
          wp.wf301_update_detect = setTimeout(function () {
            $.post(
              ajaxurl,
              {
                action: "wf301_run_tool",
                _ajax_nonce: wf301_vars.run_tool_nonce,
                tool: "get_permalink_notices",
                permalink_id: wp.data.select("core/editor").getCurrentPostId(),
              },
              function (response) {
                display_notices(response.data);
              }
            ).fail(function () {
              // Do nothing
            });
          }, 1000);
        }
      });

      $(".block-editor").on("click", ".components-notice__dismiss", function (e) {
        $(this)
          .closest(".components-notice")
          .find(".components-notice__action")
          .each(function () {
            if ($(this).attr("href").indexOf("wf301_dismiss_permalink_notice") > 0) {
              var dismiss_id = $(this).attr("href").split("permalink_id=")[1];
              $.post(
                ajaxurl,
                {
                  action: "wf301_run_tool",
                  _ajax_nonce: wf301_vars.run_tool_nonce,
                  tool: "dismiss_permalink_change",
                  permalink_id: dismiss_id,
                },
                function (response) {
                  //$(this).closest(".components-notice").remove();
                }
              ).fail(function () {
                // Do nothing
              });
            }
          });
      });

      $(".block-editor").on("click", ".components-notice__action", function (e) {
        if ($(this).attr("href").indexOf("wf301_add_permalink_redirect_rule") > 0) {
          e.preventDefault();
          var permalink_id = $(this).attr("href").split("permalink_id=")[1];
          var $notice_wrapper = $(this).parents(".components-notice").find(".components-notice__dismiss");
          $.post(
            ajaxurl,
            {
              action: "wf301_run_tool",
              _ajax_nonce: wf301_vars.run_tool_nonce,
              tool: "add_permalink_rule",
              permalink_id: permalink_id,
            },
            function (response) {
              if (response.success) {
                wp.data.dispatch("core/notices").createNotice(
                  "success",
                  "The new redirect rule has been created!", // Text string to display.
                  {
                    isDismissible: true, // Whether the user can dismiss the notice.
                    actions: [
                      {
                        id: "wp301-notice-" + permalink_id,
                        url: response.data.url,
                        label: "Click here if you want to edit it",
                      },
                    ],
                  }
                );
                $notice_wrapper.trigger("click");
              } else {
                alert("An error occured creating the new rule!");
              }
            }
          ).fail(function () {
            // Do nothing
          });
        }

        if ($(this).attr("href").indexOf("wf301_dismiss_permalink_notice") > 0) {
          e.preventDefault();
          var $notice_wrapper = $(this).parents(".components-notice").find(".components-notice__dismiss");
          var dismiss_id = $(this).attr("href").split("permalink_id=")[1];
          $.post(
            ajaxurl,
            {
              action: "wf301_run_tool",
              _ajax_nonce: wf301_vars.run_tool_nonce,
              tool: "dismiss_permalink_change",
              permalink_id: dismiss_id,
            },
            function (response) {
              $notice_wrapper.trigger("click");
              wp.data.dispatch("core/notices").removeNotice("wp301-notice-" + dismiss_id);
            }
          ).fail(function () {
            // Do nothing
          });
        }
      });

      function display_notices(notices) {
        for (notice in notices) {
          wp.data.dispatch("core/notices").createNotice(
            "warning",
            notices[notice]["text"], // Text string to display.
            {
              isDismissible: true, // Whether the user can dismiss the notice.
              actions: [
                {
                  url: notices[notice]["create_rule"],
                  label: "Create a redirect rule",
                },
                {
                  url: notices[notice]["dismiss_notice"],
                  label: "Dismiss",
                },
              ],
            }
          );
        }
      }

      display_notices(wf301_vars.permalink_change_notices);
    }
  })(window.wp);
}); // on ready
