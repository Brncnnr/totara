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
 * @package theme_msteams
 */

(function(global) {
    // The Microsoft Teams SDK should be available already.
    if ('microsoftTeams' in global) {
        global.microsoftTeams.initialize(function() {
            var form = document.getElementById('scormviewform');
            if (form !== null) {
                // Simulate <form target=_blank rel=noopener>
                // because no mainstream web browsers have implemented it so far.
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // Note that URLSearchParams does not exist in IE11.
                    var params = new URLSearchParams(new FormData(form));
                    var url = form.action + '?' + params.toString();
                    window.open(url, '_blank', 'noopener noreferrer');
                });
            }
        });
    }
})(window);
