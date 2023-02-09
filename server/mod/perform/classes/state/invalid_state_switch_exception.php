<?php
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state;

use moodle_exception;

/**
 * Exception that is thrown when an invalid state switch is attempted.
 *
 * @package mod_perform
 */
class invalid_state_switch_exception extends moodle_exception {

    public function __construct(string $from_state, string $target_state) {
        parent::__construct(
            'invalid_state_switch',
            'mod_perform',
            '',
            ['from_state' => $from_state, 'target_state' => $target_state]
        );
    }

}