<?php
/*
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\webapi\middleware;

use Closure;

use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_engage\share\recipient\manager;

/**
 * Use this middleware if your request includes a shares / totara_engage_recipient_in argument.
 * This middleware will automatically try to resolve core_users and ensure they exist, are not deleted, and
 * are in the same tenant if multitenancy is on.
 *
 */
class require_valid_recipients implements middleware {

    /**
     * @var string
     */
    protected $totara_engage_recipient_in_argument_name;

    /**
     * @param string $totara_engage_recipient_in_argument_name the argument name for the shares property
     */
    public function __construct(string $totara_engage_recipient_in_argument_name) {
        $this->totara_engage_recipient_in_argument_name = $totara_engage_recipient_in_argument_name;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        $shares = $payload->get_variable($this->totara_engage_recipient_in_argument_name);
        if (!empty($shares)) {
            // If only one recipient, fake up a list.
            if (!empty($shares['component'])) {
                $shares = [$shares];
            }
            // Validate each recipient.
            foreach ($shares as $recipient) {
                $recipient = manager::create($recipient['instanceid'], $recipient['component'], $recipient['area']);
                $recipient->validate();
            }
        }

        return $next($payload);
    }

}