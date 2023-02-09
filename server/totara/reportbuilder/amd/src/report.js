/*
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
 * @package totara_reportbuilder
 */
define(['core/webapi'], function(WebAPI) {

    /**
     * Constructor for Report title edit widget
     *
     * @param {Element} element parent element to inline edit control
     * @constructor
     */
    function ReportController(element) {
        this.element = element;

        this.id = element.getAttribute('data-report-id');
    }

    ReportController.prototype.events = function() {
        var self = this;

        this.element.addEventListener('totara_core/inline-edit:save', function(e) {
            e.detail.preventUpdate();

            self.save(e.detail.text).then(function(res) {
                // Returned string is formatted with encoded HTML entities. We could insert it as HTML
                // but rather than risk an XSS mistake, we'll decode it and insert it into the DOM as text
                var decoder = document.createElement('textarea');
                decoder.innerHTML = res;

                e.detail.target.innerText = decoder.value;
            }).catch(function(err) {
                // Unfortunately, the webapi abstracts away any information that would be useful in telling what's
                // actually wrong. We're going to verify that the error is from the server, then assume that
                // the error is from them getting an empty string through

                if (err.message && err.message.indexOf('Internal server error')) {
                    e.detail.controller.edit();
                    e.detail.controller.showCustomError('err_required', 'form');
                }
            });
        });
    };

    /**
     * Saves change via ajax
     *
     * @param {string} title updated report title text
     * @return {Promise}
     */
    ReportController.prototype.save = function(title) {
        var promise = WebAPI.call({
            operationName: 'totara_reportbuilder_update_report_title',
            variables: {
                reportid: this.id,
                title: title
            }
        }).then(function(res) {
            // Forward on the actual returned string
            return res['totara_reportbuilder_update_report_title'];
        });

        M.util.js_pending('totara_reportbuilder-title-save');

        promise.then(function() {
            M.util.js_complete('totara_reportbuilder-title-save');
        });
        return promise;
    };

    /**
     * Initialise our widget.
     * @param {Element} element
     * @return {Promise}
     */
    function init(element) {
        return new Promise(function(resolve) {
            var controller = new ReportController(element);
            controller.events();
            resolve(controller);
        });
    }

    return {
        init: init
    };
});
