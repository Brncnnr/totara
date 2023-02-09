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
use totara_notification\delivery\channel_helper;

class validate_delivery_channel_components implements middleware {
    /**
     * @var string
     */
    private $payload_key;

    /**
     * @var bool
     */
    private $is_required;

    /**
     * validate_delivery_channel_classes constructor.
     * @param string $payload_key
     * @param bool   $is_required
     */
    public function __construct(string $payload_key, bool $is_required = false) {
        $this->payload_key = $payload_key;
        $this->is_required = $is_required;
    }

    /**
     * @param payload $payload
     * @param Closure $next
     *
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        $channel_components = $payload->get_variable($this->payload_key);
        if (null === $channel_components) {
            if ($this->is_required) {
                throw new coding_exception("The field '{$this->payload_key}' is required for validation");
            }

            return $next->__invoke($payload);
        }

        if (!is_array($channel_components)) {
            throw new coding_exception("Expecting an array of channel component names");
        }

        foreach ($channel_components as $component) {
            if (!channel_helper::is_valid_delivery_channel($component)) {
                throw new coding_exception(
                    "The channel '{$component}' is not a valid delivery channel class"
                );
            }
        }

        return $next->__invoke($payload);
    }
}