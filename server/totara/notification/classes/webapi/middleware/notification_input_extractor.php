<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Ben Fesili <ben.fesili@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * This middleware is to be used to grab the input variable
 * out of the payload and make those value parent level values
 * so that other middleware in the stack can access those variables
 */
class notification_input_extractor implements middleware {

    /**
     * @param payload $payload
     * @param Closure $next
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        $input_args = $payload->get_variable('input');
        $extracted_payload = new payload($input_args, $payload->get_execution_context());
        return $next->__invoke($extracted_payload);
    }
}