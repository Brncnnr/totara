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

define(['core/notification', 'core/templates', 'core/ajax'], function(notification, templates, ajax) {

    /**
     * Class constructor for a list.
     *
     * @class
     * @constructor
     */
    function List() {
        if (!(this instanceof List)) {
            return new List();
        }
        this.activeRowClass = 'tw-list__row_active';
        this.hoverRowClass = 'tw-list__row_hover';
        this.disabledBtnClass = 'tw-list__cell_action_btn_disabled';
        this.expandBoxTemplate = 'totara_competency/lists_expanded';
        this.expandedClass = 'tw-list__expanded_show';
        this.expandedClassRow = 'tw-list__row_expanded';
        this.expandContainerHtml = '';
        this.expandID = '';
        this.expandTemplate = '';
        this.expandWebservice = '';
        this.expandWebserviceArgs = '';
        this.expandTemplateAttribute = 'data-tw-list-template-expand';
        this.expandWebserviceAttribute = 'data-tw-list-webservice-expand';
        this.expandWebserviceArgsAttribute = 'data-tw-list-webservice-expand-args';
        this.removeRowData = 'data-tw-list-removerow';
        this.selectionCheckData = 'data-tw-list-selectioncheck';
        this.stateLocked = false;
        this.widget = '';
    }

    List.prototype = {

        /**
         * Check if all items are selected
         */
        checkSelection: function() {
            var allSelected = true,
                checkBoxes = this.widget.querySelectorAll('[data-tw-list-rowSelect]');

            if (!checkBoxes.length) {
                return;
            }

            for (var i = 0; i < checkBoxes.length; i++) {
                if (checkBoxes[i].checked === false) {
                    allSelected = false;
                }
            }

            // Update select all box
            this.widget.querySelector('[data-tw-list-selectAll]').checked = allSelected;
        },

        /**
         * Add event listeners
         */
        events: function() {
            var row,
                rowID = '',
                that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-list-row]')) {
                    row = e.target.closest('[data-tw-list-row]');
                    rowID = parseInt(row.getAttribute('data-tw-list-row'));
                }

                // Select all checkbox clicked
                if (e.target.closest('[data-tw-list-selectAll]')) {
                    var checkboxAll = e.target.closest('[data-tw-list-selectAll]'),
                        checkedState = checkboxAll.checked ? true : false;

                    that.toggleSelectAll(checkedState);

                // Expand trigger clicked
                } else if (e.target.closest('[data-tw-list-expandTrigger]')) {
                    e.preventDefault();

                    var expandedBox = row.nextElementSibling;
                    that.setExpandID(rowID);

                    // Already has an expanded box with content so show it
                    if (expandedBox && expandedBox.hasAttribute('data-tw-list-expanded')) {
                        var expandedBoxHidden = expandedBox.classList.contains(that.expandedClass) ? false : true;
                        that.hideAllExpandedViews();

                        if (expandedBoxHidden) {
                            expandedBox.classList.add(that.expandedClass);
                            row.classList.add(that.expandedClassRow);
                        }

                    // Add expanded box
                    } else {
                        if (that.stateLocked) {
                            return;
                        }

                        that.stateLocked = true;
                        that.hideAllExpandedViews();
                        that.displayExpandContainer(row);
                        row.classList.add(that.expandedClassRow);
                    }

                // Close icon clicked
                } else if (e.target.closest('[data-tw-list-expandedClose]')) {
                    e.preventDefault();
                    that.hideAllExpandedViews();

                // Row checkbox clicked
                } else if (e.target.closest('[data-tw-list-rowselect]')) {
                    var checkboxState = e.target.closest('[data-tw-list-rowselect]').checked,
                        eventType = checkboxState ? 'add' : 'remove';


                    that.toggleRowState(row, checkboxState);
                    that.checkSelection();

                    that.triggerEvent(eventType, {
                        extra: that.getExtraData(row),
                        val: rowID
                    });

                    that.triggerEvent('update', {});

                } else if (e.target.closest('[data-tw-list-hierarchyTrigger]')) {
                    e.preventDefault();

                    that.triggerEvent('hierarchyRequest', {
                        extra: that.getExtraData(row),
                        key: 'parent',
                        val: rowID
                    });

                } else if (e.target.closest('[data-tw-list-actionTrigger]')) {
                    e.preventDefault();

                    var node = e.target.closest('[data-tw-list-actionTrigger]');
                    if (node.classList.contains(that.disabledBtnClass)) {
                        return;
                    }

                    var actionTrigger = node.getAttribute('data-tw-list-actionTrigger');

                    that.triggerEvent('action', {
                        extra: that.getExtraData(row),
                        key: actionTrigger,
                        val: rowID
                    });
                }
            });

            this.widget.addEventListener('focusin', function(e) {
                var row = e.target.closest('[data-tw-list-row]');
                if (row) {
                    row.classList.add(that.hoverRowClass);

                }
            });

            this.widget.addEventListener('focusout', function(e) {
                var row = e.target.closest('[data-tw-list-row]');
                if (row) {
                    row.classList.remove(that.hoverRowClass);
                }
            });

            // Check if expanded view displayed when clicking out of context
            document.addEventListener('click', function(e) {
                var expandedBox = that.widget.querySelector('.' + that.expandedClass);
                if (!expandedBox || !e.target) {
                    return;
                }

                var overlayTrigger = e.target.closest('[data-tw-list-expandTrigger]'),
                    partOfOverlay = e.target.closest('.' + that.expandedClass);

                if (overlayTrigger || partOfOverlay) {
                    return;
                }

                that.hideAllExpandedViews();
            });


            // Observe row selection when content changes
            var observeSelection = new MutationObserver(function() {
                if (that.widget.getAttribute(that.selectionCheckData)) {
                    that.checkSelection();
                    that.widget.removeAttribute(that.selectionCheckData);
                }
            });

            // Start observing for selection check data attribute
            observeSelection.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.selectionCheckData],
                subtree: false
            });

            // Observe when remove row value is added.
            var observeRemoveRow = new MutationObserver(function() {
                if (that.widget.getAttribute(that.removeRowData)) {
                    var idList = JSON.parse(that.widget.getAttribute(that.removeRowData));

                    // Loop through array of rows and remove each
                    for (var i = 0; i < idList.length; i++) {
                        that.removeRow(idList[i]);
                    }
                    that.widget.removeAttribute(that.removeRowData);
                }
            });

            // Start observing for remove row data attribute
            observeRemoveRow.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.removeRowData],
                subtree: false
            });

            // Observe expand settings changed
            var observeExpandSettings = new MutationObserver(function(mutators) {
                for (var i = 0; i < mutators.length; i++) {
                    var name = mutators[i].attributeName;
                    var value = mutators[i].target.getAttribute(name);
                    switch (name) {
                        case that.expandTemplateAttribute:
                            that.expandTemplate = value;
                            break;
                        case that.expandWebserviceAttribute:
                            that.expandWebservice = value;
                            break;
                        case that.expandWebserviceArgsAttribute:
                            that.expandWebserviceArgs = value;
                            break;
                    }
                }
            });

            // Start observing for selection check data attribute
            observeExpandSettings.observe(this.widget, {
                attributes: true,
                attributeFilter: [this.expandTemplateAttribute, this.expandWebserviceAttribute, this.expandWebserviceArgsAttribute],
                subtree: false
            });
        },

        /**
         * fetch expand template
         * @param {node} selectedRow
         */
        displayExpandContainer: function(selectedRow) {
            var that = this;
            var getContainer = new Promise(function(resolve, reject) {
                // Already have the HTML so abort
                if (that.expandContainerHtml) {
                    resolve();
                    return;
                }

                // fetch the expand container html
                templates.render(that.expandBoxTemplate).done(function(html) {
                    that.expandContainerHtml = html;
                    resolve();
                }).fail(function(ex) {
                    notification.exception(ex);
                    reject();
                });
            });

            getContainer.then(function() {
                // Add expand container to DOM
                selectedRow.insertAdjacentHTML('afterend', that.expandContainerHtml);
                var expandArea = selectedRow.nextElementSibling,
                    containerTarget = expandArea.querySelector('[data-tw-list-expandedtarget]');
                // Display container
                expandArea.classList.add(that.expandedClass);
                // Get content
                that.getExpandedViewContent(containerTarget);
            });
        },

        /**
         * Get expanded view content
         * @param {node} target
         */
        getExpandedViewContent: function(target) {
            var argValues = '',
                expandedView,
                that = this;

            // If no endpoint (template doesn't need data)
            if (!this.expandWebservice) {
                expandedView = Promise.resolve(false);
            } else {

                if (this.expandWebserviceArgs) {
                    argValues = JSON.parse(this.expandWebserviceArgs);
                    argValues.id = this.expandID;
                } else {
                    argValues = {'id': this.expandID};
                }

                expandedView = ajax.getData({
                    args: argValues,
                    methodname: this.expandWebservice
                });
            }

            // Request completed
            expandedView.then(function(data) {
                templates.renderReplace(that.expandTemplate, data.results, target).then(function() {
                    templates.runTemplateJS('');
                    that.stateLocked = false;
                });
            }).catch(function() {
                that.stateLocked = false;
            });
        },


        /**
         * Get extra data passed to node
         * @param {node} node
         * @return {sting} extraData
         */
        getExtraData: function(node) {
            var extraData = node.getAttribute('data-tw-list-extraData');
            if (extraData) {
                extraData = JSON.parse(extraData);
            }

            return extraData;
        },

        /**
        * Remove expanded view
        */
        hideAllExpandedViews: function() {
            var expandedItems = this.widget.querySelectorAll('.' + this.expandedClass),
                expandedRows = this.widget.querySelectorAll('.' + this.expandedClassRow);

            for (var i = 0; i < expandedItems.length; i++) {
                expandedItems[i].classList.remove(this.expandedClass);
            }

            for (var s = 0; s < expandedRows.length; s++) {
                expandedRows[s].classList.remove(this.expandedClassRow);
            }
        },

        /**
        * Remove row
        * @param {int} id
        */
        removeRow: function(id) {
            var node = this.widget.querySelector('[data-tw-list-row="' + id + '"]');
            node.remove();
        },

        /**
        * Set expand ID
        * @param {int} id
        */
        setExpandID: function(id) {
            this.expandID = id;
        },

        /**
        * Set expand template
        */
        setExpandTemplate: function() {
            this.expandTemplate = this.widget.getAttribute(this.expandTemplateAttribute);
            this.expandWebservice = this.widget.getAttribute(this.expandWebserviceAttribute);
            this.expandWebserviceArgs = this.widget.getAttribute(this.expandWebserviceArgsAttribute);
        },

        /**
        * Set parent
        * @param {node} parent
        */
        setParent: function(parent) {
            this.widget = parent;
        },

        /**
        * Toggle row active state
        * @param {node} row
        * @param {bool} activeState
        */
        toggleRowState: function(row, activeState) {
            if (activeState) {
                row.classList.add(this.activeRowClass);
                row.setAttribute('aria-selected', true);

            } else {
                row.classList.remove(this.activeRowClass);
                row.setAttribute('aria-selected', false);
            }
        },

        /**
         * Toggle select state of all rows
         * @param {string} checkedState
         */
        toggleSelectAll: function(checkedState) {
            var checkBoxes = this.widget.querySelectorAll('[data-tw-list-rowSelect]:not([disabled])'),
                changingState,
                eventType = checkedState ? 'add' : 'remove',
                hasChanges = false,
                row = '',
                rowId = '';

            for (var i = 0; i < checkBoxes.length; i++) {
                row = checkBoxes[i].closest('[data-tw-list-row]');
                changingState = checkBoxes[i].checked === checkedState ? false : true;
                checkBoxes[i].checked = checkedState;

                this.toggleRowState(row, checkedState);

                // If the state of this checkbox is actually changing
                if (changingState) {

                    rowId = parseInt(row.getAttribute('data-tw-list-row'));
                    hasChanges = true;
                    this.triggerEvent(eventType, {
                        key: 'ID',
                        val: rowId
                    });
                }
            }

            // If there were any state changes
            if (hasChanges) {
                this.triggerEvent('update', {});
            }
        },

        /**
        * Trigger event
        * @param {string} eventName
        * @param {object} data
        */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_core/lists:' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
        }
    };

    /**
    * widget initialisation method
    * @param {node} widgetParent
    * @returns {Promise}
    */
    var init = function(widgetParent) {
        return new Promise(function(resolve) {
            // Create an instance of widget
            var wgt = new List();
            wgt.setParent(widgetParent);
            wgt.setExpandTemplate();
            wgt.events();
            wgt.checkSelection();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });