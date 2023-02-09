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


define(['core/yui', 'core/form_duplicate_prevent'], function(Y, dupe) {
    return {
        /**
         * Initialisation method
         *
         * @param {HTMLElement} root
         * @returns {Promise}
         */
        init: function(root) {
            return new Promise(function(resolve) {
                dupe.init(root).then(function() { // eslint-disable-line promise/catch-or-return
                    // Listen to the global YUI event.
                    Y.use('moodle-core-event', function() {
                        Y.Global.on(M.core.globalEvents.FORM_ERROR, function(event) {
                            M.util.js_pending('mod_facetoface__eventinfo_form_error');
                            setTimeout(function() {
                                // Only interested in a form element inside the root element.
                                var form = root.querySelector('#' + event.formid);
                                if (form !== null && form.querySelector('#' + event.elementid) !== null) {
                                    dupe.reset();
                                }
                                M.util.js_complete('mod_facetoface__eventinfo_form_error');
                            }, 10);
                        });
                        resolve();
                    });
                });
            });
        }
    };
});
