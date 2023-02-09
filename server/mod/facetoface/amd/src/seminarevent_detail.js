/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */


define([], function() {
    const CSS_COLLAPSED = 'f2f-collapsed';
    const CSS_EXPANDED = 'f2f-expanded';

    return {
        /**
         * Initialisation method
         *
         * @param {HTMLElement} root
         * @returns {Promise}
         */
        init: function(root) {
            return new Promise(function(resolve) {
                if (root.getAttribute('data-collapsible') == 'true') {
                    root.addEventListener('click', function(event) {
                        var element = event.target.closest('a[href="#"]');
                        if (element !== null) {
                            event.preventDefault();
                            var expand = element.getAttribute('aria-expanded') != 'true';
                            var target = document.getElementById(element.getAttribute('data-f2f-section-id'));
                            if (expand) {
                                target.style.display = '';
                                element.classList.remove(CSS_COLLAPSED);
                                element.classList.add(CSS_EXPANDED);
                            } else {
                                target.style.display = 'none';
                                element.classList.add(CSS_COLLAPSED);
                                element.classList.remove(CSS_EXPANDED);
                            }
                            element.setAttribute('aria-expanded', expand.toString());
                        }
                    });
                }
                resolve(true); // Nothing interesting to return.
            });
        }
    };
});
