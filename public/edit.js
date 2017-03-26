
(function($, Drupal, document) {
  "use strict";

  var dropdownMenus = [];

  /**
   * Drupal behavior, find pages, spawn them, attach their behaviours.
   */
  Drupal.behaviors.laoutEdit = {
    attach: function(context, settings) {

      // Emulates bootstrap dropdowns.
      $(context).find('.layout-menu').once('layout-menu', function () {
        var parent = $(this);
        var link = parent.find('> a');
        var child = parent.find('> ul');
        if (child.length) {
          child.hide();
          dropdownMenus.push(parent);
          link.click(function (event) {
            event.preventDefault();
            child.show();
          });
        }
      });

      // Close dropdowns handler
      $(document).click(function(event) {
        dropdownMenus.forEach(function (element) {
          if (!$.contains(element.get(0), event.target)) {
            element.find('> ul').hide();
          }
        });
      })
    }
  };

}(jQuery, Drupal, document));
