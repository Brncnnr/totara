/*
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
 * @package block_totara_recommendations
 */

define([], function () {
    return {
        init: function () {
            // Bind the event + trigger for the default view/state
            var type_field = document.getElementById('id_config_block_type');
            var ratings_field = document.getElementById('id_config_ratings');
            var change = function () {
                var type = parseInt(type_field.value, 10);
                if (type === 2 || type === 3) {
                    // Course or workspace, the likes/ratings should be disabled
                    ratings_field.setAttribute('disabled', 'disabled');
                } else {
                    ratings_field.removeAttribute('disabled');
                }
            };
            type_field.addEventListener('change', change);

            // Trigger it on first load
            change();
        }
    };
});