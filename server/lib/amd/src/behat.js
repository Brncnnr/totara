/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

/**
 * This module should only be used when running behat. It catches errors and
 * records them so that it can be detected in Behat and fail steps.
 */
define([], function() {
    /* eslint-disable no-console */
    window.jsErrors = [];

    /**
     * Stores an error to return back to behat
     *
     * @param {Error} error Error Object to pass back through to Behat
     */
    function storeError(error) {
        if ((typeof error).toLowerCase() === 'string') {
            window.jsErrors.push(error);
        } else {
            window.jsErrors.push(error.message + ' | ' + error.stack);
        }
    }

    window.addEventListener('error', function(e) {
        storeError(e.error);
    });

    const originalErr = console.error;
    console.error = function() {
        originalErr.apply(null, arguments);
        for (var i = 0; i < arguments.length; i++) {
            storeError(arguments[i]);
        }
    };
});
