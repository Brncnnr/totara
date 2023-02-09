/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package block_totara_report_graph
 */
define(['core/str', 'core/templates', 'core/config'], function(str, templates, cfg) {
    /**
     * Initialise graph loader.
     * @param {Element} element
     */
    function init(element) {
        var blockid = parseInt(element.getAttribute('data-block_totara_report_graph-blockid'), 10);
        M.util.js_pending('block_totara_report_graph-graphloading');

        /**
         * Handles when the graph can't be retrieved
         */
        var requestFail = function() {
            str.get_string('errorrendergraph', 'block_totara_report_graph').done(function(s) {
                element.innerHTML = s;
            });
        };

        /**
         * Displays the graph
         *
         * @param {Object} responses context required for chartjs template
         */
        var requestSuccess = function(responses) {
            if (typeof responses.chart === 'undefined') {
                requestFail();
            } else {
                templates.render('totara_reportbuilder/chartjs', responses).done(function(html, js) {
                    element.innerHTML = html;
                    templates
                        .runTemplateJS(js)
                        .then(function() {
                            M.util.js_complete('block_totara_report_graph-graphloading');
                        }
                    );
                });
            }
        };

        var data = new FormData();
        data.append('blockid', blockid);
        data.append('sesskey', cfg.sesskey);
        data.append('currentlanguage', cfg.currentlanguage);

        fetch(cfg.wwwroot + '/blocks/totara_report_graph/ajax_graph.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function(response) {
            return response.json();
        }).then(requestSuccess)
            .catch(requestFail);
    }

    return {
        init: init
    };
});
