/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_core
 */

define(['core/templates'], function(templates) {
  /**
   * Class constructor for the PagingManager.
   *
   * @class
   * @constructor
   */
  function PagingManager() {
    if (!(this instanceof PagingManager)) {
      return new PagingManager();
    }
    this.contentChangeType = 'replace';
    this.eventListener = 'totara_core/paging';
    this.eventNode = '';
    this.page = 1;
    this.template = 'totara_competency/paging_content';
    this.widget = '';
  }

  // Listen for propagated events
  PagingManager.prototype.bubbledEventsListener = function() {
    var that = this;
    this.eventNode.addEventListener(this.eventListener + ':changed', function(
      e
    ) {
      that.setPageNumber(e.detail.page);
      that.setContentChangeType(e.detail.changeType);
      that.onChange();
    });
  };

  /**
   * Extend the update request with paging properties
   *
   * @param {Object} request
   * @return {Object}
   */
  PagingManager.prototype.extendRequestData = function(request) {
    request.args.page = this.getPageNumber();
    request.callback.push(this.renderPaginationBtn.bind(this));
    return request;
  };

  /**
   * Get the page append type
   *
   * @return {string} type, 'append' or 'replace'
   */
  PagingManager.prototype.getContentChangeType = function() {
    return this.contentChangeType;
  };

  /**
   * Get the propagated event name
   *
   * @return {string} event listener
   */
  PagingManager.prototype.getEventListener = function() {
    return this.eventListener;
  };

  /**
   * Get the requested page number
   *
   * @return {int}
   */
  PagingManager.prototype.getPageNumber = function() {
    return this.page;
  };

  PagingManager.prototype.onChange = function() {
    /* Null */
  };
  PagingManager.prototype.onChangeAppendType = function() {
    /* Null */
  };

  /**
   * Update paging button, either hide or update next page value
   *
   * @param {data} data
   * @return {Promise}
   */
  PagingManager.prototype.renderPaginationBtn = function(data) {
    var target = this.widget,
      that = this;

    return new Promise(function(resolve) {
      if (!target) {
        resolve();
        return;
      }

      // Update pagination button
      if (data.next) {
        templates.renderReplace(that.template, data, target).then(function() {
          that.resetPageNumber();
          resolve();
        });

        // hide button if no more pages
      } else {
        target.innerHTML = '';
        if (data.page > 0) {
          that.resetPageNumber();
        }
        resolve();
      }
    });
  };

  /**
   * Reset page number
   *
   * @param {int} page
   */
  PagingManager.prototype.resetPageNumber = function() {
    this.setPageNumber(1);
  };

  /**
   * Set the append content type
   *
   * @param {string} type, 'append' or 'replace'
   */
  PagingManager.prototype.setContentChangeType = function(type) {
    this.contentChangeType = type;
    this.onChangeAppendType(type);
  };

  /**
   * Set page number for request
   *
   * @param {int} page
   */
  PagingManager.prototype.setPageNumber = function(page) {
    this.page = page;
  };

  /**
   * Set parent node for paging
   *
   * @param {node} parent
   */
  PagingManager.prototype.setParent = function(parent) {
    this.widget = parent;
  };

  /**
   * Set node for event listening
   *
   * @param {node} parent
   */
  PagingManager.prototype.setParentEventNode = function(parent) {
    this.eventNode = parent;
  };

  /**
   * initialisation method
   *
   * @param {node} parent
   * @returns {Object} promise
   */
  var init = function(parent) {
    var wgt = new PagingManager();
    var paginationNode = parent.querySelector('[data-tw-paging]');

    wgt.setParent(paginationNode);
    wgt.setParentEventNode(parent);
    wgt.bubbledEventsListener();
    return wgt;
  };

  return {
    init: init,
  };
});
