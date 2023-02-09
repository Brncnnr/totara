<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use mod_perform\models\activity\activity;
use moodle_exception;

/**
 * Interceptor that uses activity related data in the incoming graphql payload.
 *
 * This requires the activity already being loaded and present in the payload.
 * The @see require_activity middleware does this so this has a dependency on it.
 */
class require_manage_capability implements middleware {

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        $activity = $payload->get_variable('activity');
        if (!$activity instanceof activity) {
            throw new \coding_exception('Activity was not loaded. Make sure a previous middleware loads the activity.');
        }

        if (!$activity->can_manage()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        return $next($payload);
    }
}
