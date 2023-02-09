<?php
/*
 *
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

namespace contentmarketplace_linkedin;

use contentmarketplace_linkedin\totara_xapi\handler\handler;
use totara_xapi\event\xapi_statement_created;

class observer {

    public static function watch_xapi_statement_created(xapi_statement_created $event) {
        $statement = $event->other['statement'];
        $user_id = $event->userid;
        $handler = new handler($statement, $user_id);

        if (!$handler->validate_statement()) {
            // Only handle statements that match what we require for this case.
            return;
        }

        // Process valid statements to store progress or completion for linkedin.
        $handler->process();
    }
}