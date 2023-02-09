/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

/**
 * A module for handling all the interaction within a report builder column of a report topic.
 */
define(['core/modal_factory', 'core/modal_events'], function(ModalFactory, ModalEvent) {
  /**
   *
   * @param {HTMLDivElement} element
   * @return {ReportActions}
   * @constructor
   */
  function ReportActions(element) {
    if (!(this instanceof ReportActions)) {
      return new ReportActions(element);
    }

    this.moduleElement = element;
  }

  ReportActions.prototype = {
    constructor: ReportActions,

    /**
     * @param {String} iconClassName
     * @param {String} formClassName
     */
    listenToDelete: function(iconClassName, formClassName) {
      var icon = this.moduleElement.querySelector(iconClassName),
        form = this.moduleElement.querySelector(formClassName),
        that = this;

      if (!icon || !form) {
        return;
      }

      icon.addEventListener(
        'click',
        function(event) {

          // Preventing the url to continue.
          event.preventDefault();

          var promise = new Promise(
            function(resolve) {

              // There are usage, pop up the dialog now.
              ModalFactory.create(
                {
                  type: ModalFactory.types.CONFIRM,
                  title: that.moduleElement.getAttribute('data-modal-title'),
                  body: that.moduleElement.getAttribute('data-confirm-message'),
                },
                undefined,
                {
                  yesstr: that.moduleElement.getAttribute('data-yes-string'),
                  nostr: that.moduleElement.getAttribute('data-no-string')
                }
              ).done(
                function(modal) {
                  var root = modal.getRoot();

                  root.on(ModalEvent.yes, function() {
                    modal.hide();
                    resolve();
                  });

                  modal.show();
                }
              );
            }
          );

          // It is even worst that you cannot use any sort of ES7
          promise.then(function() {
            form.submit();
          });
        }
      );
    }
  };

  return {
    /**
     * The data attribute that are needed:
     * + data-has-usage
     * + data-confirm-message
     *
     * @param {HTMLDivElement} element
     */
    init: function(element) {
      var component = new ReportActions(element);
      component.listenToDelete(
        '.tw-reportActions__deleteIcon',
        '.tw-reportActions__deleteForm'
      );
    }
  };
});