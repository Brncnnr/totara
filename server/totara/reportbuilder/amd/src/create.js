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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara
 * @subpackage totara_reportbuilder
 */

define(['core/templates', 'core/webapi', 'core/flex_icon', 'core/config', 'core/str', 'core/notification'], function(templates, WebAPI, flexicon, config, str, notification) {

    var PAGE_SIZE = 20;

    /**
     * Class constructor for Create Report.
     *
     * @class
     * @constructor
     * @param {HTMLElement} widget The element that autointialise was called on
     */
    function CreateReport(widget) {
        if (!(this instanceof CreateReport)) {
            return new CreateReport();
        }
        var that = this;

        this.widget = widget;
        this.gridContainer = widget.querySelector('[data-tw-report-create-container]');
        this.loadContainer = widget.querySelector('[data-tw-report-create-load]');
        this.loadButton = this.loadContainer.innerHTML; // save the intial state
        this.tiles = [];

        this.stringPromise = str.get_strings([
            {key: 'template', component: 'totara_reportbuilder'},
            {key: 'reportsourcepill', component: 'totara_reportbuilder'}
        ]).then(function(strings) {
            that.strings = {
                template: strings[0],
                reportsource: strings[1]
            };

            return that.strings;
        }).catch(notification.exception);

        var promises = [
            'graphicons/report_tile_image_column',
            'graphicons/report_tile_image_line',
            'graphicons/report_tile_image_bar',
            'graphicons/report_tile_image_pie',
            'graphicons/report_tile_image_scatter',
            'graphicons/report_tile_image_area',
            'graphicons/report_tile_image_donut',
            'graphicons/report_tile_image_progress',
            'graphicons/report_tile_image_nograph'
        ].map(function(icon) {
            return flexicon.getIconData(icon, 'totara_core', {alt: ''});
        });

        this.graphPromises = Promise.all(promises).then(function(icons) {
            that.graphIcons = {
                'column': icons[0],
                'line': icons[1],
                'bar': icons[2],
                'pie': icons[3],
                'scatter': icons[4],
                'area': icons[5],
                'doughnut': icons[6],
                'progress': icons[7],
                'none': icons[8]
            };
            return that.graphIcons;
        }).catch(notification.exception);

        // Filter variables
        this.filters = [];
        this.search = '';

        this.currentIndex = 0; // how many items we've loaded from the current source
        this.currentRequest = null;

        // Tender in the loading spinner, because we're going to need it before long
        this.loadingSpinner = templates.render('totara_reportbuilder/loading_overlay', {});

        this.loadRecords(PAGE_SIZE);
    }

    CreateReport.prototype = {
        constructor: CreateReport,

        /**
         * Register event listeners
         */
        events: function() {
            var that = this;

            this.widget.querySelector('[data-tw-report-create-container]').addEventListener('click', function(event) {
                if (event.target.matches('[data-tw-create-report-create]')) {
                    event.preventDefault();
                    that.createReport(event.target);
                    return;
                }
            });

            this.loadContainer.addEventListener('click', function(event) {
                if (event.target.matches('button')) {
                    var loading = that.displayLoadingSpinner(that.loadContainer, true);
                    event.preventDefault();
                    that.loadRecords(PAGE_SIZE, loading);
                }
            });

            this.widget.addEventListener('totara_core/grid:add', function(event) {
                that.openDetails(event);
            });

            // Filter handlers
            this.widget.addEventListener('totara_core/select_multi:add', function(event) {
                that.filters.push(event.detail.val);
                that.updateFilters();
            });

            this.widget.addEventListener('totara_core/select_multi:remove', function(event) {
                that.filters.splice(that.filters.indexOf(event.detail.val), 1);
                that.updateFilters();
            });

            // Search handlers
            this.widget.addEventListener('totara_core/select_search_text:add', function(event) {
                that.search = event.detail.val;
                that.updateFilters();
            });

            this.widget.addEventListener('totara_core/select_search_text:remove', function(event) {
                that.search = '';
                that.updateFilters();
            });

            // Listener for mobile toggle events
            this.widget.addEventListener('totara_core/toggle_filter_panel:changed', function(e) {
                var target = that.widget.querySelector(e.detail.targetwidget);
                target.classList.toggle(e.detail.toggleClass);
                that.updateFilters();
            });
        },

        /**
         * Open the details panel, and fetch the correct data for the item
         *
         * @param {CustomEvent} event The event that triggered this call
         */
        openDetails: function(event) {
            var keys = event.detail.val.split('-');
            var className = keys[0];
            var type = keys[1];
            var target = this.widget.querySelector('[data-tw-grid-item-ID="' + event.detail.val + '"]');

            if (!target) { return; }
            event.preventDefault();

            var detailsContainer = target.querySelector('[data-tw-report-create-details]');
            if (!detailsContainer) { return; }

            // Only load details if we haven't done it before
            if (detailsContainer.innerHTML !== '') {
                this.displayLoadingSpinner(detailsContainer);
                var operation = 'totara_reportbuilder_' + type;
                M.util.js_pending('totara_reportbuilder-create--open_details');

                WebAPI.call({
                    operationName: operation,
                    variables: {
                        key: className
                    }
                }).then(function(res) {
                    var data = res[operation];
                    var columns = [];

                    data.defaultcolumns.forEach(function(value) {
                        if (columns.length === 0) {
                            columns.push({
                                groupName: value.type,
                                values: [value.name]
                            });
                        } else {
                            var item = columns.filter(function(column) {
                                return column.groupName == value.type;
                            });

                            if (item.length) {
                                item[0].values.push(value.name);
                            } else {
                                columns.push({
                                    groupName: value.type,
                                    values: [value.name]
                                });
                            }
                        }
                    });

                    data.defaultcolumns = columns;

                    return templates.render('totara_reportbuilder/details', data);
                }).then(function(html) {
                    detailsContainer.innerHTML = html;
                    M.util.js_complete('totara_reportbuilder-create--open_details');
                }).catch(notification.exception);
            }

        },

        /**
         * Create a new report from either a template or a source
         *
         * @param {HTMLElement} button The pressed button ("Create", or "Create and View")
         */
        createReport: function(button) {
            var href = config.wwwroot + button.getAttribute('data-tw-create-report-create');
            var target = button.closest('[data-tw-grid-item]');
            var keys = target.getAttribute('data-tw-grid-item-ID').split('-');
            var operation = '';
            var variables = {
                key: keys[0]
            };

            if (keys[1] === 'template') {
                operation = 'totara_reportbuilder_create_report_from_template';
            } else {
                operation = 'totara_reportbuilder_create_report';
            }

            M.util.js_pending('totara_reportbuilder-create--createReport');
            WebAPI.call({
                operationName: operation,
                variables: variables
            }).then(function(res) {
                var id = res[operation];
                window.location.href = href += '?id=' + id;
                // no js complete as navigation is happening
            }).catch(notification.exception);
        },

        /**
         * Load more records based on the current filters and search fields
         *
         * @param {Number} limit The number of records to load at once
         * @param {Promise} loading A loading promise
         */
        loadRecords: function(limit, loading) {
            var that = this;
            var operation = 'totara_reportbuilder_creation_sources';
            var tiles = this.tiles;

            M.util.js_pending('totara_reportbuilder-create--loadRecords');
            var request = WebAPI.call({
                operationName: operation,
                variables: {
                    start: this.currentIndex,
                    limit: limit,
                    label: this.filters,
                    search: this.search
                }
            });
            that.request = request;

            Promise.all([request, this.stringPromise, this.graphPromises]).then(function(results) {
                var res = results[0];
                var strings = results[1];
                var graphs = results[2];
                var data = res[operation];

                // insert templates first
                data.templates.forEach(function(result) {
                    tiles.push({
                        template_name: 'totara_reportbuilder/create_report_tile',
                        template_data: {
                            'title': result.fullname,
                            'classname': result.key,
                            'type': 'template',
                            'primary_label': result.label,
                            'secondary_label': strings.template,
                            'template': 'totara_reportbuilder/create_report_tile',
                            'graphimagetemplate': graphs[result.graph].template,
                            'graphimagedata': graphs[result.graph].context,
                            'itemid': result.key + '-template'
                        }
                    });
                });

                // then insert report sources
                data.sources.forEach(function(result) {
                    tiles.push({
                        template_name: 'totara_reportbuilder/create_report_tile',
                        template_data: {
                            'title': result.fullname,
                            'classname': result.key.replace('rb_source_', ''),
                            'type': 'source',
                            'primary_label': result.label,
                            'secondary_label': strings.reportsource,
                            'template': 'totara_reportbuilder/create_report_tile',
                            'graphimagetemplate': graphs.none.template,
                            'graphimagedata': graphs.none.context,
                            'itemid': result.key.replace('rb_source_', '') + '-source'
                        }
                    });
                });

                if (tiles.length < data.totalcount) {
                    that.currentIndex += limit;
                } else {
                    that.currentIndex = 0;
                }

                var template = templates.render('totara_core/grid', {
                    tiles: tiles,
                    single_column: false
                });
                var resultsString = str.get_string('xrecords', 'totara_reportbuilder', tiles.length);

                return Promise.all([template, resultsString, loading]);
            }).then(function(html) {
                // If this request isn't the most recent request, bail
                if (request !== that.currentRequest) { return ''; }

                if (that.currentIndex === 0) {
                    // We're out of records, so mark that we shouldn't load anymore
                    that.loadContainer.setAttribute('data-tw-report-create-disabled', true);
                } else {
                    that.loadContainer.removeAttribute('data-tw-report-create-disabled');
                }

                // update results string
                that.widget.querySelector('[data-totara_reportbuilder-create_report-results_count]').innerHTML = html[1];

                // update the grid display
                that.gridContainer.innerHTML = html[0];
                that.currentIndex = that.gridContainer.querySelectorAll('[data-tw-grid-item]').length;
                that.widget.setAttribute('data-tw-report-create-loaded', true);
                that.loadContainer.innerHTML = that.loadButton;
                that.tiles = tiles;
                return templates.runTemplateJS();
            }).then(function() {
                M.util.js_complete('totara_reportbuilder-create--loadRecords');
            }).catch(notification.exception);

            // Track the request
            this.currentRequest = request;
        },

        /**
         * Reset the main container and pagination index, and load more records with new filter and search information
         */
        updateFilters: function() {
            var loading = this.displayLoadingSpinner(this.gridContainer, true);
            // We've changed our filters, so we need to reset a bunch of things and reload the items
            this.currentIndex = 0;
            this.tiles = [];

            this.loadRecords(PAGE_SIZE, loading);
        },

        /**
         * Displays the loading spinner inside a container
         *
         * @param {HTMLElement} container The container to place the spinner inside
         * @param {Boolean} overwrite Whether the spinner should overwrite any HTML content inside the container
         *
         * @returns {Promise}
         */
        displayLoadingSpinner: function(container, overwrite) {
            return this.loadingSpinner.then(function(html) {
                if (overwrite || container.innerHTML.trim() === '') {
                    container.innerHTML = html;
                }
            });
        }
    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new CreateReport(parent);
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });