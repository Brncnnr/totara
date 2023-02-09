/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

define(['core/webapi', 'core/modal_factory', 'core/modal_events'], function(WebAPI, ModalFactory, ModalEvent) {
  var SERVICE = {
    approveContent: 'totara_reportedcontent_approve_review',
    removeContent: 'totara_reportedcontent_remove_review',
  };

  var swapForStatus = function(report, status) {
    document.querySelector('#reportedcontent-actions-' + report + ' .actions').style.display = 'none';
    document.querySelector('#reportedcontent-actions-' + report + ' .loading').style.display = 'none';
    document.querySelector('#reportedcontent-actions-' + report + ' .status-' + status).style.display = 'block';
  };

  var setTimeReviewed = function(report, timeReviewed) {
    var selector = document.querySelector('#reportedcontent-time-reviewed-' + report);
    if (selector) {
      selector.textContent = timeReviewed;
    }
  };

  var loading = function(report) {
    document.querySelector('#reportedcontent-actions-' + report + ' .loading').style.display = 'block';
    document.querySelector('#reportedcontent-actions-' + report + ' .actions').style.display = 'none';
  };

  var init = function() {
    var parent = document.querySelector('table[data-source="rb_source_reportedcontent"]');
    // If there are no results on the page, there's nothing to boot/bind.
    if (!parent) {
      return;
    }

    var doAction = function(action, report) {
      M.util.js_pending(action);
      loading(report);
      WebAPI.call({
        operationName: action,
        variables: {
          review_id: report,
        },
      }).then(function(data) {
        swapForStatus(report, data.review.status);
        setTimeReviewed(report, data.review.time_reviewed_description);
        M.util.js_complete(action);
      });
    };

    // Bind our event to each button
    var buttonClicked = function(event) {
      var target = event.target || undefined;
      if (!target || target.tagName !== 'BUTTON') {
        return;
      }

      var report = target.getAttribute('data-report') || null;
      var action = target.getAttribute('data-action') || null;

      if (!report || !action) {
        return;
      }

      if (action === 'approve') {
        doAction(SERVICE.approveContent, report);
      } else if (action === 'remove') {
        // Create & show a confirmation modal instead
        ModalFactory.create({
          type: ModalFactory.types.CONFIRM,
          title: target.getAttribute('data-modal-title'),
          body: target.getAttribute('data-modal-content'),
        }, undefined, {
          yesstr: target.getAttribute('data-yes'),
          nostr: target.getAttribute('data-no'),
        }).done(function(modal) {
          modal.getRoot().on(ModalEvent.yes, function() {
            doAction(SERVICE.removeContent, report);
          });

          modal.show();
        });
      }
    };

    parent.addEventListener('click', buttonClicked);
  };

  return {
    init: init,
  };
});