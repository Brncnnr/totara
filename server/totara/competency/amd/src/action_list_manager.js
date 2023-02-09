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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

define([
  'core/str',
  'core/templates',
  'core/notification',
  'totara_competency/paging_manager',
  'core/ajax',
], function(str, templates, notification, Paging, ajax) {
  /**
   * Class constructor for the ListManager.
   * Handles dynamic rendering of action_list.mustache and listens for propagated events from it's sub components
   *
   * @class
   * @constructor
   */
  function ListManager() {
    if (!(this instanceof ListManager)) {
      return new ListManager();
    }

    this.appendType = 'replace';
    this.counter = '';
    this.disabledItems = [];
    this.disabledItemClass = '.tw-list__cell_select_label_disabled';
    this.enabledActions = true;
    this.enabledHierarchy = true;
    this.eventListener = 'totara_core/lists';
    this.eventListenerNode = '';
    this.listDataMap = [];
    this.listRequestArgs = {
      direction: 'asc',
      filters: {},
      order: '',
    };
    this.iconList = {};
    this.orderBy = '';
    this.paging = '';
    this.rowsSelector = '[data-tw-list-row]';
    this.rowsTemplate = 'totara_competency/lists_rows';
    this.selectedItems = [];
    this.toggleLevel = '';
    this.webservice = '';
    this.widget = '';
  }

  /**
   * Triggered action events which don't have a default behaviour
   */
  ListManager.prototype.onCustomAction = function() {
    /* Null */
  };
  ListManager.prototype.onHideSubLevelItems = function() {
    /* Null */
  };
  ListManager.prototype.onItemSelected = function() {
    /* Null */
  };
  ListManager.prototype.onItemUnselected = function() {
    /* Null */
  };
  ListManager.prototype.onItemUpdate = function() {
    /* Null */
  };
  ListManager.prototype.onListHierarchyLevelChange = function() {
    /* Null */
  };
  ListManager.prototype.onListHierarchyLevelChangeExtend = function() {
    /* Null */
  };
  ListManager.prototype.onPostRequest = function() {
    /* Null */
  };
  ListManager.prototype.onPreRequest = function() {
    /* Null */
  };
  ListManager.prototype.onShowSubLevelItems = function() {
    /* Null */
  };

  /**
   * Listen for propagated events
   */
  ListManager.prototype.propagatedListEvents = function() {
    var that = this;

    this.eventListenerNode.addEventListener(
      this.eventListener + ':action',
      function(e) {
        that.onCustomAction(e);
      }
    );

    this.eventListenerNode.addEventListener(
      this.eventListener + ':add',
      function(e) {
        that.onItemSelected(e.detail.val);
      }
    );

    this.eventListenerNode.addEventListener(
      this.eventListener + ':remove',
      function(e) {
        that.onItemUnselected(e.detail.val);
      }
    );

    this.eventListenerNode.addEventListener(
      this.eventListener + ':update',
      function() {
        that.onItemUpdate();
      }
    );

    this.eventListenerNode.addEventListener(
      this.eventListener + ':hierarchyRequest',
      function(e) {
        that.onListHierarchyLevelChange(e);
        that.onListHierarchyLevelChangeExtend(e);
      }
    );

    if (this.orderBy) {
      this.orderBy.addEventListener('totara_core/select_tree:add', function(e) {
        that.setOrderBy(e.detail.val);
      });

      this.orderBy.addEventListener(
        'totara_core/select_tree:changed',
        function() {
          that.update();
        }
      );
    }

    if (this.toggleLevel) {
      this.eventListenerNode.addEventListener(
        'totara_core/lists_toggle_level:changed',
        function(e) {
          if (e.detail.level == 'all') {
            that.onShowSubLevelItems();
          } else {
            that.onHideSubLevelItems();
          }
        }
      );
    }
  };

  /**
   * Correctly format the data for displaying list
   * @param {JSON} data
   * @return {JSON}
   */
  ListManager.prototype.formatListData = function(data) {
    var active = false,
      disabled = false,
      colData,
      expandable,
      extra,
      map = this.getDataMap(),
      mapCols = map.cols,
      actionCallbacks = map.actions ? map.actions : [],
      iconCallback = map.icons ? map.icons : null,
      items = data.items,
      label,
      newDataStructure = {
        has_actions: false,
        hierarchyEnabled: map.hasHierarchy,
        noResultsText:
          map.noResultsString && map.noResultsString.value
            ? map.noResultsString.value
            : '',
        rows: [],
        row_header: {
          columns: [],
          header: true,
        },
        select_enabled: map.hasCheckboxes ? map.hasCheckboxes : false,
      },
      row,
      selectedItems = this.selectedItems,
      disabledItems = this.disabledItems,
      that = this;

    // Check Hierarchy has not been manually disabled
    if (!this.enabledHierarchy) {
      newDataStructure.hierarchyEnabled = this.enabledHierarchy;
    }
    expandable = map.hasExpandedView ? map.hasExpandedView : false;

    if (map.expandTemplate) {
      newDataStructure.expandTemplate = map.expandTemplate;
    }
    if (map.expandWebservice) {
      newDataStructure.expandWebservice = map.expandWebservice;
    }
    if (map.expandWebserviceArgs) {
      newDataStructure.expandWebserviceArgs = map.expandWebserviceArgs;
    }

    // Cast item ids to int to make the following more robust
    selectedItems = selectedItems.map(function(item) {
      return parseInt(item, 10);
    });

    disabledItems = disabledItems.map(function(item) {
      return parseInt(item, 10);
    });

    return new Promise(function(resolve) {
      that.prepareListIcons(iconCallback).then(function() {
        // Add header row columns
        for (var a = 0; a < mapCols.length; a++) {
          colData = mapCols[a];
          newDataStructure.row_header.columns.push({
            value: colData.headerString.value,
            width: colData.size,
          });
        }

        var hasActions = false;

        // Add data rows
        for (var i = 0; i < items.length; i++) {
          // Check if item ID is selected
          if (selectedItems) {
            active = selectedItems.indexOf(items[i].id) > -1;
          }

          if (disabledItems) {
            disabled = disabledItems.indexOf(items[i].id) > -1;
          }

          // Extra data to be added to row
          if (map.extraRowData) {
            extra = {};
            for (var c = 0; c < map.extraRowData.length; c++) {
              var key = map.extraRowData[c].key;
              extra[key] = items[i][map.extraRowData[c].dataPath];
            }
          } else {
            extra = '';
          }

          row = {
            active: active,
            columns: [],
            disabled: disabled,
            expandable: expandable,
            extra_data: extra ? JSON.stringify(extra) : '',
            has_children: parseInt(items[i].children_count),
            id: items[i].id,
            path: items[i].path,
          };

          // Check actions have not been manually disabled
          if (that.enabledActions) {
            row.actions = that.prepareActions(actionCallbacks, items[i]);
            if (row.actions.length > 0) {
              hasActions = true;
            }
          }

          // Add columns to row
          for (var b = 0; b < mapCols.length; b++) {
            colData = mapCols[b];
            // If first column, don't add label for mobile
            label = b === 0 ? '' : colData.headerString.value;

            // Sub templating for column
            var columnTemplate = null;
            if (colData.columnTemplate) {
              columnTemplate = colData.columnTemplate.template;
            }

            row.columns.push({
              column_template: columnTemplate,
              expand_trigger: colData.expandedViewTrigger,
              label: label,
              value: items[i][colData.dataPath],
              width: colData.size,
            });
          }
          newDataStructure.rows.push(row);
        }

        // Only now we now if any of the rows has actions
        newDataStructure.has_actions = hasActions;
        resolve(newDataStructure);
      });
    });
  };

  /**
   * Get map args
   * @return {object} request args
   */
  ListManager.prototype.getDataMap = function() {
    return this.listDataMap;
  };

  /**
   * Get the icon list
   * @return {object}
   */
  ListManager.prototype.getIconList = function() {
    return this.iconList;
  };

  /**
   * Get service request args
   * @return {object} request args
   */
  ListManager.prototype.getRequestArgs = function() {
    return this.listRequestArgs;
  };

  /**
   * Get row node selector
   * @return {object} request args
   */
  ListManager.prototype.getRowsSelector = function() {
    return this.rowsSelector;
  };

  /**
   * Get rows template
   * @return {string} template name
   */
  ListManager.prototype.getRowsTemplate = function() {
    return this.rowsTemplate;
  };

  /**
   * Return webservice string
   * @return {string} webservice name
   */
  ListManager.prototype.getWebservice = function() {
    return this.webservice;
  };

  /**
   * Extract header strings from data structure and load strings
   * @param {JSON} data
   * @return {Promise}
   */
  ListManager.prototype.loadHeaderStrings = function(data) {
    var colStrings = data.cols ? data.cols : [];
    var noResults = data.noResultsString ? data.noResultsString : '';
    var stringList = [];

    if (noResults.value) {
      // Use passed string
    } else if (data.noResultsString) {
      stringList.push({
        component: noResults.component,
        key: noResults.key,
      });
    } else {
      stringList.push({
        component: 'totara_core',
        key: 'noitems',
      });
    }

    // Get column header strings
    for (var b = 0; b < colStrings.length; b++) {
      var stringProps = colStrings[b].headerString;
      if (!stringProps.value) {
        stringList.push({
          component: stringProps.component,
          key: stringProps.key,
        });
      }
    }

    return new Promise(function(resolve) {
      if (!stringList.length) {
        resolve(data);
        return;
      }

      // When the results comes back feed the translated strings
      // back into the data array
      str.get_strings(stringList).then(function(strings) {
        // If first string is for no-results
        if (!noResults.value) {
          data.noResultsString = { value: strings[0] };
          strings.shift();
        }

        for (var b = 0; b < data.cols.length; b++) {
          if (!data.cols[b].headerString.value) {
            data.cols[b].headerString.value = strings[0];
            strings.shift();
          }
        }
        resolve(data);
      });
    });
  };

  /**
   * Prepare the Actions for a row
   * @param {function|Array} actionCallbacks could be a single callback or an array of callbacks
   * @param {Object} row the actual row the action is prepared for
   * @return {Array}
   */
  ListManager.prototype.prepareActions = function(actionCallbacks, row) {
    var actions,
      that = this,
      tmpActions = [];
    // either it's one callback function returning all the action objects as an array
    // or it is an array of callback each returning one action object
    if (typeof actionCallbacks === 'function') {
      // handle if it is a callback
      actions = actionCallbacks(row);
      if (actions && !Array.isArray(actions)) {
        notification.exception({
          message: 'Expected action callback to return an Array.',
        });
        return [];
      }
      tmpActions = actions;
    } else if (Array.isArray(actionCallbacks)) {
      if (actionCallbacks.length > 0) {
        for (var d = 0; d < actionCallbacks.length; d++) {
          if (typeof actionCallbacks[d] !== 'function') {
            notification.exception({
              message: 'Expected array of callbacks for actions setting.',
            });
            return [];
          }
          var callback = actionCallbacks[d];
          var action = callback(row);
          if (typeof action !== 'object') {
            notification.exception({
              message: 'Expected object to be returned by the action callback',
            });
            return [];
          }
          tmpActions.push(action);
        }
      } else {
        return [];
      }
    } else {
      notification.exception({
        message:
          'Expected either an Array of callbacks or one callback for actions setting.',
      });
      return [];
    }

    actions = [];
    for (var e = 0; e < tmpActions.length; e++) {
      var iconHtml = '',
        iconList = that.getIconList(),
        tmpAction = tmpActions[e];

      if (tmpAction.icon && tmpAction.icon in iconList) {
        iconHtml = iconList[tmpAction.icon];
      }
      actions.push({
        disabled: tmpAction.disabled ? tmpAction.disabled : false,
        event_key: tmpAction.eventKey ? tmpAction.eventKey : null,
        hidden: tmpAction.hidden ? tmpAction.hidden : false,
        icon: iconHtml,
        name: tmpAction.name ? tmpAction.name : '',
      });
    }

    return actions;
  };

  /**
   * Prepare all icons by loading strings, render the icons and store the html for the icons in this.iconList
   * @param {function} iconCallback
   * @return {Promise}
   */
  ListManager.prototype.prepareListIcons = function(iconCallback) {
    var that = this;

    return new Promise(function(resolve) {
      if (!iconCallback) {
        resolve();
        return;
      } else if (!iconCallback || typeof iconCallback !== 'function') {
        notification.exception({
          message: 'Expected callback for the icons setting.',
        });
        resolve();
        return;
      }

      var icons = iconCallback();
      if (!Array.isArray(icons)) {
        notification.exception({
          message: 'Expected icons callback to return an array.',
        });
        resolve();
        return;
      }

      var stringKeys = [];
      var stringKeysIndex = [];
      // Prepare a list of strings we want to request from the backend
      for (var i = 0; i < icons.length; i++) {
        if (icons[i].string && icons[i].component) {
          stringKeys.push({
            key: icons[i].string,
            component: icons[i].component,
          });

          stringKeysIndex.push(icons[i].key);
        }
      }

      if (stringKeys.length > 0) {
        // Load the strings and then render all the icons
        str.get_strings(stringKeys).then(function(strings) {
          var altText = '',
            promises = [];

          for (var i = 0; i < icons.length; i++) {
            var index = stringKeysIndex.indexOf(icons[i].key);
            if (index >= 0) {
              altText = strings[index];
            }
            var promise = that.renderIcon(
              icons[i].key,
              icons[i].name,
              icons[i].classes,
              altText
            );
            promises.push(promise);
          }
          // Only if all icons are loaded then continue
          Promise.all(promises).then(function() {
            resolve();
          });
        });
      }
    });
  };

  /**
   * Render an individual icon wrapped in a promise
   * @param {string} iconKey the unique key for this icon
   * @param {string} iconName name of the flex-icon, like delete, move-up
   * @param {string} iconClasses additional styling classese required for icon
   * @param {string} iconString the alt string for the icon
   * @return {Promise}
   */
  ListManager.prototype.renderIcon = function(
    iconKey,
    iconName,
    iconClasses,
    iconString
  ) {
    var that = this;

    return new Promise(function(resolve) {
      templates
        .renderIcon(iconName, iconString, iconClasses)
        .done(function(html) {
          // Store the rendered icon html in our list for later use
          that.iconList[iconKey] = html;
          resolve();
        });
    });
  };

  /**
   * Render list content with data provided
   * @param {data} data
   * @return {Promise}
   */
  ListManager.prototype.renderListContent = function(data) {
    var target = this.widget,
      that = this;

    return new Promise(function(resolve) {
      // If we have a results count update it
      that.renderListCount(data.total).then(function() {
        // Data needs to be correctly formatted
        that.formatListData(data).then(function(listData) {
          if (listData.expandWebservice) {
            target.setAttribute(
              'data-tw-list-webservice-expand',
              listData.expandWebservice
            );
          }
          if (listData.expandWebserviceArgs) {
            target.setAttribute(
              'data-tw-list-webservice-expand-args',
              JSON.stringify(listData.expandWebserviceArgs)
            );
          }
          if (listData.expandTemplate) {
            target.setAttribute(
              'data-tw-list-template-expand',
              listData.expandTemplate
            );
          }

          // Append to end of existing list or replace content
          if (that.appendType === 'append') {
            var rowsSelector = that.getRowsSelector(),
              rowItems = target.querySelectorAll(rowsSelector),
              lastItem = rowItems[rowItems.length - 1];

            templates
              .renderAppendByItem(
                that.getRowsTemplate(),
                listData,
                target,
                rowsSelector
              )
              .then(function() {
                target.setAttribute('data-tw-list-selectioncheck', true);
                if (that.paging) {
                  that.paging.setContentChangeType('replace');
                }
                lastItem.scrollIntoView();
                resolve();
              });
          } else {
            templates
              .renderReplace(that.getRowsTemplate(), listData, target)
              .then(function() {
                templates.runTemplateJS('');
                var firstItem = target.firstElementChild;
                if (firstItem) {
                  firstItem.scrollIntoView(false);
                }
                target.setAttribute('data-tw-list-selectioncheck', true);
                resolve();
              });
          }
        });
      });
    });
  };

  /**
   * Update list count number and string
   * @param {int} count
   * @return {Promise}
   */
  ListManager.prototype.renderListCount = function(count) {
    var counter = this.counter;
    return new Promise(function(resolve) {
      if (!counter) {
        resolve();
        return;
      }

      var countNum = counter.querySelector('[data-tw-list-count-num]'),
        stringKey = count === 1 ? 'listitem' : 'listitemplural';

      str.get_string(stringKey, 'totara_core', count).then(function(string) {
        countNum.setAttribute('data-tw-list-count-num', count);
        countNum.innerHTML = string;
        resolve();
      });
    });
  };

  /**
   * remove item from list without reloading the result
   * @param {Array} ids list of ids to remove
   * @return {promise}
   */
  ListManager.prototype.removeItemFromList = function(ids) {
    var that = this;
    return new Promise(function(resolve) {
      that.widget.setAttribute('data-tw-list-removerow', JSON.stringify(ids));

      if (that.counter) {
        var currentCount = parseInt(
            that.counter.querySelector('[data-tw-list-count-num]').innerHTML
          ),
          numRemoved = parseInt(ids.length);
        that.renderListCount(currentCount - numRemoved).then(resolve);
      } else {
        resolve();
      }
    });
  };

  /**
   * Disable toggle Level
   */
  ListManager.prototype.disableToggleLevel = function() {
    if (this.toggleLevel) {
      this.toggleLevel.setAttribute('data-tw-list-toggleLevel-disable', 'true');
    }
  };

  /**
   * Reset toggle Level
   */
  ListManager.prototype.resetToggleLevel = function() {
    if (this.toggleLevel) {
      this.toggleLevel.setAttribute(
        'data-tw-list-toggleLevel-manual',
        'current'
      );
    }
  };

  /**
   * Toggle disable state of all select boxes making sure predisabled rows stay disabled
   *
   * @param {Boolean} disable true to disable, false to enable
   */
  ListManager.prototype.toggleSelectDisable = function(disable) {
    var selects = this.widget.querySelectorAll('[data-tw-list-rowSelect');
    for (var i = 0; i < selects.length; i++) {
      if (disable) {
        selects[i].setAttribute('disabled', true);
      } else {
        if (!selects[i].closest(this.disabledItemClass)) {
          selects[i].removeAttribute('disabled');
        }
      }
    }
    if (disable) {
      this.widget
        .querySelector('[data-tw-list-selectall]')
        .setAttribute('disabled', true);
    } else {
      this.widget
        .querySelector('[data-tw-list-selectall]')
        .removeAttribute('disabled');
    }
  };

  /**
   * Set counter node
   * @param {node} parent
   */
  ListManager.prototype.setCounter = function(parent) {
    this.counter = parent;
  };

  /**
   * Set orderby for list request
   * @param {string} orderBy
   */
  ListManager.prototype.setOrderBy = function(orderBy) {
    this.setRequestArg('order', orderBy);
  };

  /**
   * Set parent node
   * @param {node} parent
   */
  ListManager.prototype.setParent = function(parent) {
    this.widget = parent;
  };

  /**
   * Set orderBy node
   * @param {node} node
   */
  ListManager.prototype.setOrderByNode = function(node) {
    this.orderBy = node;
  };

  /**
   * Set event listener node
   * @param {node} parent
   */
  ListManager.prototype.setEventListenerNode = function(parent) {
    this.eventListenerNode = parent;
  };

  /**
   * Get service request args
   * @param {string} key
   * @param {string} value
   */
  ListManager.prototype.setRequestArg = function(key, value) {
    this.listRequestArgs[key] = value;
  };

  /**
   * Set request args merging with the existing one (will override existing ones)
   * @param {Object} args
   */
  ListManager.prototype.setRequestArgs = function(args) {
    if (typeof args != 'undefined') {
      Object.assign(this.listRequestArgs, args);
    }
  };

  /**
   * Set toggleLevel node
   * @param {node} node
   */
  ListManager.prototype.setToggleLevelNode = function(node) {
    this.toggleLevel = node;
  };

  /**
   * Set webservice
   * @param {node} webservice
   */
  ListManager.prototype.setWebservice = function(webservice) {
    this.webservice = webservice;
  };

  /**
   * Set extending paging events
   * @param {node} paging
   */
  ListManager.prototype.pagingEvents = function(paging) {
    var that = this;

    /**
     * paging change triggered
     */
    paging.onChange = function() {
      that.update();
    };

    /**
     * paging change type updated
     * @param {string} type
     */
    paging.onChangeAppendType = function(type) {
      that.appendType = type;
    };
  };

  /**
   * Return the list request args for webservice
   * @return {Object}
   */
  ListManager.prototype.getUpdateRequestArgs = function() {
    var request = {
      args: this.getRequestArgs(),
      callback: [this.renderListContent.bind(this)],
      methodname: this.getWebservice(),
    };

    if (this.paging) {
      request = this.paging.extendRequestData(request);
    }
    return request;
  };

  /**
   * Sends request to webservice to load the data
   * @return {Promise}
   */
  ListManager.prototype.update = function() {
    var that = this;

    return new Promise(function(resolve, reject) {
      that.onPreRequest();
      ajax
        .getDataUpdate([that.getUpdateRequestArgs()])
        .then(function() {
          that.onPostRequest();
          resolve();
        })
        .catch(function() {
          that.onPostRequest();
          reject();
        });
    });
  };

  /**
   * Prepare data structure used for the list by preloading the header strings
   * @return {Promise}
   */
  ListManager.prototype.prepare = function() {
    var that = this;

    return new Promise(function(resolve) {
      that.loadHeaderStrings(that.listDataMap).then(function(updatedData) {
        that.listDataMap = updatedData;
        resolve();
      });
    });
  };

  /**
   * Prepare the data structure and Load the list
   * @return {Promise}
   */
  ListManager.prototype.load = function() {
    var that = this;

    return new Promise(function(resolve) {
      that.prepare().then(function() {
        that.update().then(function() {
          resolve();
        });
      });
    });
  };

  /**
   * initialisation method
   * @param {node} parent
   * @param {node} data
   * @return {ListManager|null} list manager
   */
  var init = function(parent, data) {
    var countNode = parent.querySelector('[data-tw-list-count]'),
      listNode = parent.querySelector('[data-tw-list]'),
      orderByNode = parent.querySelector('[data-tw-list-order]'),
      paging = parent.querySelector('[data-tw-paging]'),
      toggleLevelNode = parent.querySelector('[data-tw-list-toggleLevel]');

    var wgt = new ListManager();
    wgt.setParent(listNode);
    wgt.setEventListenerNode(parent);

    if (paging) {
      wgt.paging = Paging.init(parent);
      wgt.pagingEvents(wgt.paging);
    }

    if (!data.map || typeof data.map !== 'object') {
      notification.exception({
        message: 'You need to provide a map object for the list',
      });
      return null;
    }

    wgt.listDataMap = data.map;

    // If there is a count
    if (countNode) {
      wgt.setCounter(countNode);
    }

    if (orderByNode) {
      wgt.setOrderByNode(orderByNode);
    }

    if (toggleLevelNode) {
      wgt.setToggleLevelNode(toggleLevelNode);
    }

    if (data.serviceArgs) {
      wgt.setRequestArgs(data.serviceArgs);
    }

    wgt.propagatedListEvents();
    wgt.setWebservice(data.service);

    return wgt;
  };

  return {
    init: init,
  };
});
