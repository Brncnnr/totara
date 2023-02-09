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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totaraa_reportbuilder
 */
define(['core/yui'], function(YUI) {

    /**
     * Handles resizing of the graph area
     *
     * @class
     * @param {HTMLDivElement} node the node
     */
    var Resizer = function(node) {
        this.node = node;

        // Is this in a block?
        this.blockNode = node.closest('[data-instanceid]');
        if (this.blockNode) {
            this.blockinstanceid = this.blockNode.getAttribute('data-instanceid');
        }

        this.addEvents();
        this.checkResize();
    };

    Resizer.prototype = {
        /** @type {HTMLDivElement} */
        node: null,

        /** @type {HTMLDivElement} */
        blockNode: null,

        /** @type {Number} */
        blockinstanceid: -1,

        /** @type {Boolean} */
        resizePending: false,

        addEvents: function() {
            var that = this;
            window.addEventListener('resize', this.checkResize.bind(this));

            // Dock may not be present at the time this is called
            this.addDockListeners();

            // trigger the resize when moving a block around
            if (document.body.classList.contains('editing')) {
                var checkMove = function(mutationList) {
                    mutationList.filter(function(mutation) {
                        return mutation.type === 'childList' && mutation.target.hasAttribute('data-blockregion');
                    }).forEach(function (mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            // Make sure that only moving this block triggers a resize check
                            if (node.id == 'inst' + that.blockinstanceid) {
                                that.checkResize();
                            }
                        });
                    });
                };

                var obs = new MutationObserver(checkMove);
                obs.observe(document.body, {attributes: false, childList: true, subtree: true});
            }
        },

        /**
         * Add Listeners associated with the dock
         */
        addDockListeners: function() {
            var that = this;
            YUI.use('moodle-core-dock', function() {
                var dock = M.core.dock.get();

                // The name of this event isn't exactly what you expect
                // It fires when the block is displayed in the dock
                dock.on('dock:resizepanelcomplete', function () {
                    var dockNode = document.querySelector('#dock .block_current_learning');
                    if (dockNode) {
                        that.checkResize();
                    }
                });

                dock.on('dock:itemremoved', function() {
                    var block = document.getElementById('inst' + that.blockinstanceid);
                    if (block) {
                        that.checkResize();
                    }
                });
            });
        },

        /**
         * Prevents resizing on every size change
         */
        checkResize: function() {
            if (this.resizePending) {
                return;
            }
            this.resizePending = true;
            setTimeout(this.doResize.bind(this), 50);
        },

        /**
         * Does the actual resize
         */
        doResize: function () {
            var width = parseInt(getComputedStyle(this.node).width, 10);
            var graphsPerRow = Math.floor(width / 214) || 1;
            var charts = this.node.querySelectorAll('.rb-chartjs__chart');

            graphsPerRow = Math.min(charts.length, graphsPerRow);

            this.node.setAttribute('data-items-per-row', graphsPerRow);

            if (graphsPerRow === 1 && this.node.firstElementChild) {
                var style = getComputedStyle(this.node.firstElementChild);
                if (style.maxWidth === style.width) {
                    var parentWidth = getComputedStyle(this.node).width;
                    var margin = (parseInt(parentWidth, 10) - parseInt(style.width, 10)) / 2;

                    charts.forEach(function(node) {
                        node.style.marginLeft = margin + 'px';
                        node.style.marginRight = margin + 'px';
                    });
                } else {
                    charts.forEach(function(node) {
                        node.style.marginLeft = '';
                        node.style.marginRight = '';
                    });
                }
            }
            this.resizePending = false;
        }
    };

    return {
        /**
         * Initialises the component
         *
         * @param {HTMLElement} node The Element to handle this
         * @returns {Promise} resolved once complete
         */
        init: function(node) {
            var resizer = new Resizer(node);

            Promise.resolve(resizer);
        }
    };
});