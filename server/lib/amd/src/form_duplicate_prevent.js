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
 * @package core
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 * @module  core/form_duplicate_prevent
 */

define([], function() {
    var inprocess = false;

    /**
     * Prevents duplicate form events on the page
     *
     * @param {Event} event a bubbled form submit event
     */
    function preventDuplicates(event) {
        if (inprocess) {
            event.preventDefault();
            return;
        }
        inprocess = true;
    }

    return {
        init: function(node) {
            node.addEventListener('submit', preventDuplicates);
            return Promise.resolve();
        },

        reset: function() {
            inprocess = false;
        }
    };
});