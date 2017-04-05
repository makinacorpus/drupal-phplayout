
(function($, Drupal, document, dragula) {
  "use strict";

  var dropdownMenus = [];

  /**
   * Drupal behavior, find pages, spawn them, attach their behaviours.
   */
  Drupal.behaviors.layoutEdit = {
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
      });

      // Go for the drag and drop.
      // @todo disabled for now, there are too many bugs.
      if (false && dragula) {
        var $context = $(context);
        $context.find('[data-contains]').once('drag', function () {
          // Ensure this is a top level container
          var id = this.getAttribute('data-id');
          if (id && /^vbox-layout-\d+$/.test(id)) {
            var topLevel = $(this);

            // Find all nested containers, nowing that the top level container
            // itself is a container, which must behave like the others.
            var containers = [this];
            topLevel.find('[data-contains]').each(function () {
              containers.push(this);
            });

            // Aaaaaannd enable it!
            dragula(containers);
          }
        });
      }
    }
  };

}(jQuery, Drupal, document, dragula));
