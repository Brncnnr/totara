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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

define([], function() {
    return {
        /**
         * Initialisation method
         *
         * @param {HTMLElement} root
         * @returns {Promise}
         */
        init: function(root) {
            root.addEventListener('change', function(event) {
                var ones = Array.prototype.slice.apply(root.querySelectorAll('.mod_facetoface__archive__select-one'));
                var all = root.querySelector('.mod_facetoface__archive__select-all');
                var btn = root.querySelector('.mod_facetoface__archive__submit');
                if (event.target === all) {
                    var checked = all.checked;
                    ones.forEach(function(el) {
                        el.checked = checked;
                    });
                    btn.disabled = !checked;
                    return;
                }
                if (ones.includes(event.target)) {
                    all.checked = ones.every(function(el) {
                        return el.checked;
                    });
                    btn.disabled = !ones.find(function(el) {
                        return el.checked;
                    });
                    return;
                }
            });
        }
    };
});
