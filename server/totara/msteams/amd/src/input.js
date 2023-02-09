/**
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
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
            var el = root.querySelector('.totara_msteams_form__input__field__control');
            /**
             * Validator.
             */
            function validate() {
                root.classList.add('totara_msteams--validated');
                root.classList.toggle('totara_msteams--valid', !!el.value.length);
            }
            /**
             * Event listener.
             * @param {event} e
             */
            function listener(e) {
                var target = e.target.closest('.totara_msteams_form__input__field__control');
                if (target === el) {
                    validate();
                }
            }
            root.addEventListener('input', listener);
            root.addEventListener('focusout', listener);
            // NOTE: we don't validate the initial state.
            // validate();
            return Promise.resolve();
        }
    };
});
