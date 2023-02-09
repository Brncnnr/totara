<?php
/**
 * This file is part of Totara Learn
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\middleware;

use Closure;
use coding_exception;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_notification\resolver\resolver_helper;

class validate_resolver_class_name implements middleware {
    /**
     * @var string
     */
    private $payload_key;

    /**
     * @var bool
     */
    private $must_exist;

    /**
     * validate_event_class_name constructor.
     * @param string $payload_key
     * @param bool   $must_exist
     */
    public function __construct(string $payload_key, bool $must_exist = false) {
        $this->payload_key = $payload_key;
        $this->must_exist = $must_exist;
    }

    /**
     * @param payload $payload
     * @param Closure $next
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        $resolver_class_name = $payload->get_variable($this->payload_key);

        if (empty($resolver_class_name)) {
            if ($this->must_exist) {
                throw new coding_exception(
                    "The payload does not have variable '{$this->payload_key}'"
                );
            }
        } else if (!resolver_helper::is_valid_event_resolver($resolver_class_name)) {
            throw new coding_exception(
                "The resolver class is not a notifiable event resolver"
            );
        }

        return $next->__invoke($payload);
    }
}
