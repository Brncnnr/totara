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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\participant_section;

use mod_perform\state\state;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class representing the availability status of a participant section.
 *
 * @package mod_perform
 */
abstract class participant_section_availability extends state {

    /**
     * Close availability.
     *
     * @return void
     */
    abstract public function close(): void;

    /**
     * Open availability.
     */
    abstract public function open(): void;

    /**
     * @inheritDoc
     */
    public static function get_type(): string {
        return 'availability';
    }

}
