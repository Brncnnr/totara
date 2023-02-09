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
define(['core/chartjs'], function(Chart) {

    /**
     * Constructor for ChartJS widget
     *
     * @param {HTMLCanvasElement} element Canvas element to load ChartJS on
     * @constructor
     */
    function ChartJSController(element) {
        var ctx = element.getContext('2d');

        var options = JSON.parse(element.dataset.reportOptions);
        this.chart = new Chart(ctx, options);
    }

    /**
     * Initialise our widget.
     * @param {string|$} element
     * @return {Promise}
     */
    function init(element) {
        return new Promise(function(resolve) {
            var controller = new ChartJSController(element);
            resolve(controller);
        });
    }

    return {
        init: init
    };
});
