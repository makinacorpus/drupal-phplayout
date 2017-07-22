
(function($, Drupal, document, dragula) {
  "use strict";

  var dropdownMenus = [];

  /**
   * Find an element position amongst its parents
   *
   * @param Element element
   *
   * @returns number
   */
  function findItemPosition(element) {
    var position = 0;
    var nodes = element.parentNode.childNodes;
    for(var i = 0; i < nodes.length; i++) {
      // Do not filter with the data attribute from there because the item
      // might be dropped from another source than something we manage, and
      // it might not be replaced by the drop event (that will be done
      // later actually)
      if (nodes[i] === element) {
        break; // Found ourselves
      }
      if (nodes[i].hasAttribute('data-id')) {
        position++;
      }
    }
    return position;
  }

  /**
   * Drupal behavior, find pages, spawn them, attach their behaviours.
   */
  Drupal.behaviors.layoutEdit = {
    attach: function(context, settings) {

      var $context = $(context);

      // Emulates bootstrap dropdowns.
      $context.find('.layout-menu').once('layout-menu', function () {
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

      // For the time we are in edit mode, click on inner links must be
      // disabled, else the user will always accidentally load pages.
      $context
        .find('[data-item] div:not(.layout-menu) a')
        .once('layout-data-a')
        .addClass('disabled')
        .on('click', function (event) {
          event.preventDefault();
        })
      ;

      $context.find('.layout-menu > a[disabled=true]').once('layout-a').on('click', function (event) {
        event.preventDefault();
      });

      // Close dropdowns handler
      $(document).click(function (event) {
        dropdownMenus.forEach(function (element) {
          if (!$.contains(element.get(0), event.target)) {
            element.find('> ul').hide();
          }
        });
      });

      // Double click hiding/showing feature.
      $context.find('.layout-menu').once('drag').on('dblclick', function (event) {
        event.stopPropagation();
        event.preventDefault();
        $(this).parent().toggleClass('collapsed');
      });
    }
  };

  Drupal.behaviors.layoutDragAndDrop = {
    attach: function(context, settings) {

      var token = settings.layout.token;
      var baseurl = settings.layout.baseurl;
      var $context = $(context);

      // Go for the drag and drop.
      // @todo disabled for now, there are too many bugs.
      if (dragula) {

        var writableContainers = [];

        $context.find('[data-contains=0]').once('drag', function () {
          // Ensure this is a top level container
          var topLevel = $(this);
          var layoutId = this.getAttribute('data-id');

          // Find all nested containers, nowing that the top level container
          // itself is a container, which must behave like the others.
          var containers = [this];
          topLevel.find('[data-contains=1]').each(function () {
            containers.push(this);
            writableContainers.push(this);

            // Add the data-layout-id attribute on every containers so that
            // we won't work on wrongly scoped js variables
            this.setAttribute('data-layout-id', layoutId);
          });

          // Work only if have something work with
          if (containers.length) {

            var drake = dragula(containers, {
              isContainer: function (element) {
                return element.hasAttribute('data-contains');
              },
              invalid: function (element, handle) {
                return !$(element).closest('[data-item]').length;
              },
              revertOnSpill: true,
              removeOnSpill: false,
              direction: 'vertical'
            });

            // Handles drop
            drake.on('drop', function (element, target, source, sibling) {

              // Cancel disallowed moves
              if (!element.hasAttribute('data-id') ||
                  !target.hasAttribute('data-id') ||
                  !target.hasAttribute('data-layout-id')
              ){
                return drake.cancel(true);
              }

              // Find item and container identifier
              var itemId = element.getAttribute('data-id');
              var containerId = target.getAttribute('data-id');
              var layoutId = target.getAttribute('data-layout-id');
              var position = findItemPosition(element);

              $.ajax(baseurl + 'layout/ajax/move', {
                cache: false,
                method: 'GET',
                success: function (data) {
                  // Nothing to do here?
                },
                error: function () {
                  console.log("oups, drag did not go well");
                  drake.cancel(true);
                },
                data: {
                  "layout-edit": token,
                  layout: layoutId,
                  containerId: containerId,
                  itemId: itemId,
                  newPosition: position
                }
              });
            });
          }
        });

        //
        // Allow external items to be dropped into regions. For this to work
        // the external items provider needs to give a few information about
        // the item: the item type, the item identifier, and optionnaly the
        // default style to use. All those items will be copy only, and changed
        // on drop.
        // For using this, you must set your item container a data-layout-source
        // attribute, with the value 1, then each movable item must have the
        // data-item-type="some_type" and data-item-id="id" attributes. Default
        // style is not managed for now. For example:
        //
        // <li data-layout-source=1>
        //  <li data-item-type=node data-item-id=13></li>
        //  <li data-item-type=user data-item-id=42></li>
        // </li>
        //
        if (writableContainers.length) {

          var readonlyContainers = [];

          $context.find('[data-layout-source=1]').once('drag').each(function () {
            readonlyContainers.push(this);
          });

          // Work only if have something work with
          if (readonlyContainers.length) {
            var allContainers = writableContainers.concat(readonlyContainers);

            var drake = dragula(allContainers, {
              // Should item be copied or not, in our case, always, but let's
              // keep additional checks on the source since we have more than
              // one drake in the screen
              copy: function (element, source) {
                return source.hasAttribute("data-layout-source");
              },
              // Our containers are readonly, they cannot accept anything
              accepts: function (element, target) {
                return target.hasAttribute('data-contains');
              },
              invalid: function (element, handle) {
                return !$(element).closest('[data-item-id]').length;
              },
              revertOnSpill: true,
              removeOnSpill: false,
              direction: 'vertical'
            });

            // Removes float style on elements
            drake.on('over', function (element, source) {
              element.style.float = 'none';
            });

            // Handles drop
            drake.on('drop', function (element, target, source, sibling) {

              // Cancel disallowed moves
              if (!element.hasAttribute('data-item-type') ||
                  !element.hasAttribute('data-item-id') ||
                  !target.hasAttribute('data-id') ||
                  !target.hasAttribute('data-layout-id')
              ){
                return drake.cancel(true);
              }

              // Proceed with the AJAX query, removes the item from the DOM
              // and set the new one, and hope for that we don't have to run
              // again the Drupal behaviours to attach it to the container,
              // it seems that Dragula is a well written, and we should not
              // have to, hopefully.

              // First collect the item data
              var itemId = element.getAttribute('data-item-id');
              var itemType = element.getAttribute('data-item-type');
              var itemStyle = element.getAttribute('data-item-style') || 'default';
              var containerId = target.getAttribute('data-id');
              var layoutId = target.getAttribute('data-layout-id');
              var position = findItemPosition(element);

              // Run query
              $.ajax(baseurl + 'layout/ajax/add-item', {
                cache: false,
                method: 'GET',
                success: function (data) {
                  if (data && data.success) {
                    if (data.output) {
                      var newNode = document.createElement('div');
                      newNode.innerHTML = data.output;
                      if (newNode) {
                        element.parentNode.replaceChild(newNode.firstChild, element);
                        Drupal.attachBehaviors(element.parentNode);
                      } else {
                        console.log("output data is not valid html");
                      }
                    } else {
                      console.log("data could not be re-rendered");
                    }
                  } else {
                    target.removeChild(element);
                    console.log("operation failed");
                  }
                },
                error: function () {
                  console.log("oups, drag did not go well");
                  drake.cancel(true);
                },
                data: {
                  "layout-edit": token,
                  layout: layoutId,
                  containerId: containerId,
                  itemType: itemType,
                  itemId: itemId,
                  position: position,
                  style: itemStyle
                }
              });
            });
          }
        }
      }
    }
  };

}(jQuery, Drupal, document, dragula));
